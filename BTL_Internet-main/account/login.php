<?php
require_once __DIR__.'/../config.php';
if(current_user())redirect('index.php');
$error='';$email=strtolower(input('email'));
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try {
        $password=is_string($_POST['mat_khau']??null)?$_POST['mat_khau']:'';
        $key=hash('sha256',$email);$attempt=query('SELECT * FROM dang_nhap_thu WHERE khoa=?',[$key])->fetch();
        if($attempt&&(int)$attempt['so_lan']>=5&&strtotime($attempt['lan_cuoi'])>time()-900)throw new InvalidArgumentException('Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau 15 phút.');
        $u=query('SELECT * FROM nguoi_dung WHERE email=? AND trang_thai=1',[$email])->fetch();
        $valid=password_verify($password,$u['mat_khau']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if(!$u||!$valid){
            query('INSERT INTO dang_nhap_thu (khoa,so_lan,lan_cuoi) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE so_lan=IF(lan_cuoi<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,so_lan+1),lan_cuoi=NOW()',[$key]);
            throw new InvalidArgumentException('Email hoặc mật khẩu không chính xác, hoặc tài khoản đã bị khóa.');
        }
        query('DELETE FROM dang_nhap_thu WHERE khoa=?',[$key]);session_regenerate_id(true);
        $_SESSION=['user_id'=>(int)$u['id_nguoi_dung'],'csrf'=>bin2hex(random_bytes(32))];
        if(password_needs_rehash($u['mat_khau'],PASSWORD_DEFAULT))query('UPDATE nguoi_dung SET mat_khau=? WHERE id_nguoi_dung=?',[password_hash($password,PASSWORD_DEFAULT),$u['id_nguoi_dung']]);
        flash('Đăng nhập thành công.');
        $role=['customer'=>'khach_hang','staff'=>'nhan_vien'][$u['vai_tro']]??$u['vai_tro'];
        redirect($role==='admin'?'admin/index.php':($role==='nhan_vien'?'orders/manage.php':'index.php'));
    }catch(Throwable $ex){$error=error_message($ex);}
}
$page_title='Đăng nhập';require __DIR__.'/../header.php';
?>
<main id="main" class="container"><div class="auth-shell"><p class="eyebrow">WELCOME BACK</p><h1>Chào bạn trở lại.</h1><p class="muted">Đăng nhập để tiếp tục với Fashion Store.</p>
<?php if($error):?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif;?>
<form class="panel stack" method="post"><?=csrf_field()?><div><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" autocomplete="username" required value="<?=e($email)?>"></div><div><label for="password">Mật khẩu</label><input id="password" name="mat_khau" type="password" maxlength="72" autocomplete="current-password" required></div><button class="btn btn-wide">Đăng nhập</button></form><p class="auth-foot">Chưa có tài khoản? <a href="<?=e(url('account/register.php'))?>">Đăng ký ngay</a></p></div></main>
<?php require __DIR__.'/../footer.php';?>
