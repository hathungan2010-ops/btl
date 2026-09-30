<?php
require_once __DIR__.'/../config.php';$u=require_role(['khach_hang']);$uid=(int)$u['id_nguoi_dung'];$error='';
function checkout_quote(array $items):string {
    return hash('sha256',json_encode(array_map(static function($i){return [(int)$i['id_bien_the'],(int)$i['so_luong'],(string)$i['gia']];},$items)));
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $token=input('checkout_token');
        if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new InvalidArgumentException('Phiên đặt hàng không hợp lệ.');
        $previous=query('SELECT id_don_hang FROM don_hang WHERE checkout_token=? AND id_nguoi_dung=?',[$token,$uid])->fetchColumn();
        if($previous)redirect('orders/detail.php?id='.$previous);
        if(!isset($_SESSION['checkout_token'])||!hash_equals($_SESSION['checkout_token'],$token))throw new InvalidArgumentException('Phiên đặt hàng hết hạn. Hãy tải lại trang.');
        $name=input('ho_ten_nhan');$phone=input('so_dien_thoai');$address=input('dia_chi_giao_hang');$method=input('phuong_thuc_thanh_toan');
        if(mb_strlen($name)<2||mb_strlen($name)>100)throw new InvalidArgumentException('Họ tên người nhận cần từ 2 đến 100 ký tự.');
        validate_phone($phone);
        if(mb_strlen($address)<10||mb_strlen($address)>255)throw new InvalidArgumentException('Địa chỉ cần từ 10 đến 255 ký tự.');
        if(!isset(payment_methods()[$method]))throw new InvalidArgumentException('Phương thức thanh toán không hợp lệ.');
        $conn->beginTransaction();lock_customer($uid);$items=cart_items($uid);
        if(!$items)throw new InvalidArgumentException('Giỏ hàng đã trống. Vui lòng kiểm tra lịch sử đơn.');
        foreach($items as &$item){
            $variant=available_variant((int)$item['id_bien_the']);$quantity=integer((string)$item['so_luong'],1,99);
            if($quantity>(int)$variant['so_luong_ton'])throw new InvalidArgumentException('Không đủ tồn kho cho '.$variant['ten_san_pham'].'.');
            $item=array_merge($item,$variant,['so_luong'=>$quantity]);
        }unset($item);
        if(!hash_equals(checkout_quote($items),input('quote')))throw new InvalidArgumentException('Giỏ hàng hoặc giá sản phẩm vừa thay đổi. Hãy kiểm tra tổng tiền mới rồi đặt lại.');
        $total=cart_total($items);
        if($total<=0||$total>999999999999)throw new InvalidArgumentException('Tổng tiền vượt giới hạn.');
        query("INSERT INTO don_hang (id_nguoi_dung,ho_ten_nhan,so_dien_thoai,dia_chi_giao_hang,tong_tien,phuong_thuc_thanh_toan,trang_thai,checkout_token) VALUES (?,?,?,?,?,?,'cho_xu_ly',?)",[$uid,$name,$phone,$address,decimal($total),$method,$token]);
        $orderId=(int)$conn->lastInsertId();
        foreach($items as $item){
            query('INSERT INTO chi_tiet_don_hang (id_don_hang,id_bien_the,so_luong,don_gia,thanh_tien,ten_san_pham,ten_kich_thuoc,ten_mau_sac,hinh_anh) VALUES (?,?,?,?,?,?,?,?,?)',
                [$orderId,$item['id_bien_the'],$item['so_luong'],$item['gia'],decimal(cents($item['gia'])*$item['so_luong']),$item['ten_san_pham'],$item['ten_kich_thuoc'],$item['ten_mau_sac'],$item['hinh_anh']]);
            if(!query('UPDATE bien_the_san_pham SET so_luong_ton=so_luong_ton-? WHERE id_bien_the=? AND so_luong_ton>=?',[$item['so_luong'],$item['id_bien_the'],$item['so_luong']])->rowCount())throw new InvalidArgumentException('Tồn kho đã thay đổi. Hãy thử lại.');
        }
        query('DELETE ct FROM chi_tiet_gio_hang ct JOIN gio_hang gh ON gh.id_gio_hang=ct.id_gio_hang WHERE gh.id_nguoi_dung=?',[$uid]);
        $conn->commit();unset($_SESSION['checkout_token']);flash('Đặt hàng thành công! Mã đơn #'.$orderId.'.');redirect('orders/detail.php?id='.$orderId);
    }catch(Throwable $ex){if($conn->inTransaction())$conn->rollBack();$error=error_message($ex);}
}
$items=cart_items($uid);if(!$items){if($error)flash($error,'error');redirect('cart/index.php');}
$token=$_SESSION['checkout_token']??($_SESSION['checkout_token']=bin2hex(random_bytes(32)));
$page_title='Thanh toán';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="page-heading"><h1>Thông tin đặt hàng</h1><a class="underlined" href="<?=e(url('cart/index.php'))?>">← Giỏ hàng</a></div>
<?php if($error):?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif;?>
<form class="checkout-grid" method="post"><?=csrf_field()?><input type="hidden" name="checkout_token" value="<?=e($token)?>"><input type="hidden" name="quote" value="<?=e(checkout_quote($items))?>">
<section class="panel stack"><h2>Thông tin nhận hàng</h2>
<div><label for="receiver">Họ tên người nhận</label><input id="receiver" name="ho_ten_nhan" required minlength="2" maxlength="100" autocomplete="name" value="<?=e(input('ho_ten_nhan',null,$u['ho_ten']))?>"></div>
<div><label for="phone">Điện thoại</label><input id="phone" name="so_dien_thoai" type="tel" required maxlength="20" autocomplete="tel" value="<?=e(input('so_dien_thoai',null,$u['so_dien_thoai']??''))?>"></div>
<div><label for="address">Địa chỉ giao hàng</label><textarea id="address" name="dia_chi_giao_hang" required minlength="10" maxlength="255" autocomplete="street-address"><?=e(input('dia_chi_giao_hang',null,$u['dia_chi']??''))?></textarea></div>
<div><label for="payment">Phương thức thanh toán</label><select id="payment" name="phuong_thuc_thanh_toan"><?php foreach(payment_methods() as $value=>$label):?><option value="<?=e($value)?>" <?=input('phuong_thuc_thanh_toan',null,'COD')===$value?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></div>
<?php if(count(payment_methods())>1):?><p class="help">Chuyển khoản được đối soát thủ công. Thông tin ngân hàng sẽ hiển thị sau khi đặt hàng.</p><?php endif;?></section>
<aside class="panel summary"><h2>Đơn hàng của bạn</h2><?php foreach($items as $item):?><div class="summary-line"><div><?=e($item['ten_san_pham'])?> × <?=$item['so_luong']?><small><?=e($item['ten_kich_thuoc'].' / '.$item['ten_mau_sac'])?></small></div><strong class="nowrap"><?=money(decimal(cents($item['gia'])*(int)$item['so_luong']))?></strong></div><?php endforeach;?>
<p class="help">Phí vận chuyển: Miễn phí</p><div class="summary-total"><span>Tổng tiền</span><span><?=money(decimal(cart_total($items)))?></span></div><button class="btn btn-wide">Xác nhận đặt hàng ↗</button></aside></form></main>
<?php require __DIR__.'/../footer.php';?>
