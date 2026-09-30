<?php
require_once __DIR__.'/../config.php';if(current_user())redirect('index.php');$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $name=input('ho_ten');$email=strtolower(input('email'));$phone=input('so_dien_thoai');
        $password=is_string($_POST['mat_khau']??null)?$_POST['mat_khau']:'';
        $confirm=is_string($_POST['xac_nhan_mat_khau']??null)?$_POST['xac_nhan_mat_khau']:'';
        validate_name_email($name,$email);validate_phone($phone,false);validate_password($password);
        if($password!==$confirm)throw new InvalidArgumentException('Mật khẩu xác nhận chưa trùng khớp.');
        if(query('SELECT 1 FROM nguoi_dung WHERE email=?',[$email])->fetchColumn())throw new InvalidArgumentException('Email này đã được đăng ký.');
        query("INSERT INTO nguoi_dung (ho_ten,email,so_dien_thoai,mat_khau,vai_tro) VALUES (?,?,?,?,'khach_hang')",[$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]);
        flash('Đăng ký thành công. Bạn có thể đăng nhập ngay.');redirect('account/login.php');
    }catch(Throwable $ex){$error=$ex instanceof PDOException&&$ex->getCode()==='23000'?'Email này đã được đăng ký.':error_message($ex);}
}
$page_title='Đăng ký';require __DIR__.'/../header.php';
?>
<main id="main" class="container"><div class="auth-shell"><p class="eyebrow">JOIN FASHION STORE</p><h1>Tạo tài khoản</h1>
<?php if($error):?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif;?>
<form class="panel stack" method="post"><?=csrf_field()?>
<div><label for="name">Họ và tên</label><input id="name" name="ho_ten" required minlength="2" maxlength="100" autocomplete="name" value="<?=e(input('ho_ten'))?>"></div>
<div><label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" required autocomplete="email" value="<?=e(input('email'))?>"></div>
<div><label for="phone">Số điện thoại (không bắt buộc)</label><input id="phone" name="so_dien_thoai" type="tel" maxlength="20" autocomplete="tel" value="<?=e(input('so_dien_thoai'))?>"></div>
<div><label for="password">Mật khẩu</label><input id="password" name="mat_khau" type="password" required minlength="8" maxlength="72" autocomplete="new-password"><p class="help">Tối thiểu 8 ký tự, tối đa 72 byte.</p></div>
<div><label for="confirm">Xác nhận mật khẩu</label><input id="confirm" name="xac_nhan_mat_khau" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
<button class="btn btn-wide">Đăng ký</button></form><p class="auth-foot">Đã có tài khoản? <a href="<?=e(url('account/login.php'))?>">Đăng nhập</a></p></div></main>
<?php require __DIR__.'/../footer.php';?>
