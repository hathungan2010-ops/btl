<?php
declare(strict_types=1);

const ROLES=['khach_hang'=>'Khách hàng','nhan_vien'=>'Nhân viên','admin'=>'Admin'];
const ORDER_STATES=['cho_xu_ly'=>'Chờ xử lý','da_xac_nhan'=>'Đã xác nhận','dang_giao'=>'Đang giao','hoan_thanh'=>'Hoàn thành','da_huy'=>'Đã hủy'];
const ORDER_TRANSITIONS=['cho_xu_ly'=>['da_xac_nhan','da_huy'],'da_xac_nhan'=>['dang_giao','da_huy'],'dang_giao'=>['hoan_thanh'],'hoan_thanh'=>[],'da_huy'=>[]];
const PAYMENT_STATES=['chua_thanh_toan'=>'Chưa thanh toán','da_thanh_toan'=>'Đã thanh toán','can_hoan_tien'=>'Cần hoàn tiền','da_hoan_tien'=>'Đã hoàn tiền'];

function e($value):string { return htmlspecialchars(is_scalar($value)?(string)$value:'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function input(string $key,?array $source=null,string $default=''):string {
    $value=($source??$_POST)[$key]??$default;
    return is_string($value)?trim($value):$default;
}
function url(string $path=''):string { global $base_url; return $base_url.'/'.ltrim($path,'/'); }
function redirect(string $path):void { header('Location: '.url($path),true,303);exit; }
function query(string $sql,array $params=[]):PDOStatement { global $conn;$stmt=$conn->prepare($sql);$stmt->execute($params);return $stmt; }
function flash(string $message,string $type='success'):void { $_SESSION['flashes'][]=['message'=>$message,'type'=>$type]; }
function csrf_token():string { return $_SESSION['csrf']??($_SESSION['csrf']=bin2hex(random_bytes(32))); }
function csrf_field():string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf():void { if(!hash_equals(csrf_token(),input('csrf')))abort_page(403,'Phiên biểu mẫu hết hạn. Hãy tải lại trang.'); }
function require_post():void {
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');abort_page(405,'Thao tác này cần gửi từ biểu mẫu.');}
    verify_csrf();
}
function current_user():?array {
    static $loaded=false,$user=null;
    if(!$loaded){
        $loaded=true;
        if(!empty($_SESSION['user_id'])){
            $user=query('SELECT * FROM nguoi_dung WHERE id_nguoi_dung=? AND trang_thai=1',[$_SESSION['user_id']])->fetch()?:null;
            if($user){$user['vai_tro']=['customer'=>'khach_hang','staff'=>'nhan_vien'][$user['vai_tro']]??$user['vai_tro'];$_SESSION['vai_tro']=$user['vai_tro'];}
            else unset($_SESSION['user_id'],$_SESSION['vai_tro'],$_SESSION['ho_ten']);
        }
    }
    return $user;
}
function has_role(array $roles):bool { $u=current_user();return $u&&in_array($u['vai_tro'],$roles,true); }
function require_role(array $roles):array {
    $u=current_user();
    if(!$u){flash('Vui lòng đăng nhập để tiếp tục.','info');redirect('account/login.php');}
    if(!in_array($u['vai_tro'],$roles,true))abort_page(403,'Tài khoản không có quyền sử dụng chức năng này.');
    return $u;
}
function abort_page(int $status,string $message):void {
    http_response_code($status);$page_title=(string)$status;require __DIR__.'/../header.php';
    echo '<main id="main" class="container section"><div class="empty"><h1>'.$status.'</h1><p>'.e($message).'</p><a class="btn" href="'.e(url()).'">Về trang chủ</a></div></main>';
    require __DIR__.'/../footer.php';exit;
}
function error_message(Throwable $ex):string {
    if($ex instanceof InvalidArgumentException)return $ex->getMessage();
    error_log('Fashion Store: '.$ex->getMessage());
    return 'Không thể hoàn tất thao tác. Vui lòng tải lại trang và thử lại.';
}
function integer(string $value,int $min=1,int $max=2147483647):int {
    if(!preg_match('/^\d+$/D',$value)||strlen($value)>10||(int)$value<$min||(int)$value>$max)throw new InvalidArgumentException('Số lượng hoặc mã dữ liệu không hợp lệ.');
    return (int)$value;
}
function cents($value):int { $p=explode('.',(string)$value,2);return (int)$p[0]*100+(int)str_pad(substr($p[1]??'',0,2),2,'0'); }
function decimal(int $value):string { return intdiv($value,100).'.'.str_pad((string)($value%100),2,'0',STR_PAD_LEFT); }
function money($value):string { return number_format((float)$value,cents($value)%100?2:0,',','.').' ₫'; }
function price(string $value):string {
    if(!preg_match('/^\d{1,9}(\.\d{1,2})?$/D',$value)||cents($value)<=0)throw new InvalidArgumentException('Giá phải lớn hơn 0 và tối đa 999.999.999,99 ₫.');
    return decimal(cents($value));
}
function validate_name_email(string $name,string $email):void {
    if(mb_strlen($name)<2||mb_strlen($name)>100)throw new InvalidArgumentException('Họ tên cần từ 2 đến 100 ký tự.');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>100)throw new InvalidArgumentException('Email không hợp lệ hoặc quá dài.');
}
function validate_password(string $password):void { if(mb_strlen($password)<8||strlen($password)>72)throw new InvalidArgumentException('Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte.'); }
function validate_phone(string $phone,bool $required=true):void { if(($required||$phone!=='')&&!preg_match('/^\+?[0-9][0-9 .-]{7,18}[0-9]$/D',$phone))throw new InvalidArgumentException('Số điện thoại không hợp lệ (9–20 ký tự).'); }
function product_image(?string $name):string {
    return $name&&basename($name)===$name&&is_file(__DIR__.'/../images/products/'.$name)?url('images/products/'.rawurlencode($name)):url('images/product-placeholder.svg');
}
function payment_methods():array {
    global $bank_name,$bank_account,$bank_owner;
    $methods=['COD'=>'Thanh toán khi nhận hàng (COD)'];
    if($bank_name&&$bank_account&&$bank_owner)$methods['Chuyển khoản']='Chuyển khoản ngân hàng';
    return $methods;
}
function cart_items(int $uid):array {
    return query('SELECT ct.*,bt.gia,bt.so_luong_ton,sp.id_san_pham,sp.ten_san_pham,sp.hinh_anh,sp.trang_thai,dm.trang_thai AS danh_muc_active,kt.ten_kich_thuoc,ms.ten_mau_sac
        FROM chi_tiet_gio_hang ct JOIN gio_hang gh ON gh.id_gio_hang=ct.id_gio_hang JOIN bien_the_san_pham bt ON bt.id_bien_the=ct.id_bien_the
        JOIN san_pham sp ON sp.id_san_pham=bt.id_san_pham JOIN danh_muc dm ON dm.id_danh_muc=sp.id_danh_muc
        JOIN kich_thuoc kt ON kt.id_kich_thuoc=bt.id_kich_thuoc JOIN mau_sac ms ON ms.id_mau_sac=bt.id_mau_sac
        WHERE gh.id_nguoi_dung=? ORDER BY bt.id_bien_the',[$uid])->fetchAll();
}
function cart_total(array $items):int { return array_sum(array_map(static function($i){return cents($i['gia'])*(int)$i['so_luong'];},$items)); }
function lock_customer(int $id):void {
    $u=query('SELECT * FROM nguoi_dung WHERE id_nguoi_dung=? FOR UPDATE',[$id])->fetch();
    if(!$u||!$u['trang_thai']||!in_array($u['vai_tro'],['customer','khach_hang'],true))throw new InvalidArgumentException('Tài khoản không còn quyền mua hàng.');
}
function available_variant(int $id):array {
    $v=query('SELECT bt.*,sp.ten_san_pham,sp.hinh_anh,sp.trang_thai,dm.trang_thai AS danh_muc_active,kt.ten_kich_thuoc,ms.ten_mau_sac
        FROM bien_the_san_pham bt JOIN san_pham sp ON sp.id_san_pham=bt.id_san_pham JOIN danh_muc dm ON dm.id_danh_muc=sp.id_danh_muc
        JOIN kich_thuoc kt ON kt.id_kich_thuoc=bt.id_kich_thuoc JOIN mau_sac ms ON ms.id_mau_sac=bt.id_mau_sac
        WHERE bt.id_bien_the=? FOR UPDATE',[$id])->fetch();
    if(!$v||!$v['trang_thai']||!$v['danh_muc_active'])throw new InvalidArgumentException('Sản phẩm đã ngừng bán. Vui lòng xóa khỏi giỏ.');
    return $v;
}
function status_badge(string $state):string { return '<span class="badge badge-'.e(isset(ORDER_STATES[$state])?$state:'unknown').'">'.e(ORDER_STATES[$state]??$state).'</span>'; }
function pagination(int $page,int $pages):void {
    if($pages<=1)return;echo '<nav class="pagination" aria-label="Phân trang">';
    foreach(array_unique(array_merge([1],range(max(1,$page-2),min($pages,$page+2)),[$pages])) as $n){
        $params=$_GET;$params['page']=$n;
        echo '<a '.($n===$page?'aria-current="page" ':'').'href="?'.e(http_build_query($params)).'">'.$n.'</a>';
    }
    echo '</nav>';
}
function update_order(int $id,string $expected,string $next,bool $paid,bool $refunded,string $completedAt=''):void {
    global $conn;$conn->beginTransaction();
    try {
        $o=query('SELECT * FROM don_hang WHERE id_don_hang=? FOR UPDATE',[$id])->fetch();
        if(!$o)throw new InvalidArgumentException('Không tìm thấy đơn hàng.');
        $current=$o['trang_thai'];$payment=$o['trang_thai_thanh_toan'];
        if($current!==$expected)throw new InvalidArgumentException('Đơn hàng vừa thay đổi. Hãy kiểm tra trạng thái hiện tại.');
        if(!isset(ORDER_STATES[$next])||($next!==$current&&!in_array($next,ORDER_TRANSITIONS[$current]??[],true)))throw new InvalidArgumentException('Không được chuyển sang trạng thái này.');
        if($paid){
            if($current==='da_huy')throw new InvalidArgumentException('Không thể nhận thanh toán cho đơn đã hủy.');
            if($payment==='chua_thanh_toan')$payment='da_thanh_toan';
        }
        if($refunded){
            if($current!=='da_huy'||$payment!=='can_hoan_tien')throw new InvalidArgumentException('Không có khoản hoàn tiền đang chờ.');
            $payment='da_hoan_tien';
        }
        if($next==='hoan_thanh'&&$payment!=='da_thanh_toan')throw new InvalidArgumentException('Cần xác nhận đã nhận tiền trước khi hoàn thành.');
        if($next==='da_huy'&&$current!=='da_huy'){
            foreach(query('SELECT id_bien_the,so_luong FROM chi_tiet_don_hang WHERE id_don_hang=? ORDER BY id_bien_the',[$id])->fetchAll() as $item)
                query('UPDATE bien_the_san_pham SET so_luong_ton=so_luong_ton+? WHERE id_bien_the=?',[$item['so_luong'],$item['id_bien_the']]);
            if($payment==='da_thanh_toan')$payment='can_hoan_tien';
        }
        $completion=$o['ngay_hoan_thanh'];
        if($next==='hoan_thanh'&&!$completion){
            if($current==='hoan_thanh'){
                $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$completedAt);
                if(!$date||$date->format('Y-m-d\TH:i')!==$completedAt||$date>new DateTimeImmutable()||$date<new DateTimeImmutable($o['ngay_dat']))
                    throw new InvalidArgumentException('Cần ngày hoàn thành thực tế từ ngày đặt đến hiện tại.');
                $completion=$date->format('Y-m-d H:i:s');
            }else $completion=date('Y-m-d H:i:s');
        }
        query('UPDATE don_hang SET trang_thai=?,trang_thai_thanh_toan=?,ngay_hoan_thanh=? WHERE id_don_hang=?',[$next,$payment,$completion,$id]);
        $conn->commit();
    }catch(Throwable $ex){if($conn->inTransaction())$conn->rollBack();throw $ex;}
}
