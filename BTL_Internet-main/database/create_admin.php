<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config.php';
try{
    $name=getenv('ADMIN_NAME')?:'Quản trị viên';$email=strtolower(trim(getenv('ADMIN_EMAIL')?:''));$password=getenv('ADMIN_PASSWORD')?:'';
    validate_name_email($name,$email);validate_password($password);
    if(query('SELECT 1 FROM nguoi_dung WHERE email=?',[$email])->fetchColumn())throw new InvalidArgumentException('Email đã tồn tại; không tự nâng quyền tài khoản cũ.');
    query("INSERT INTO nguoi_dung (ho_ten,email,mat_khau,vai_tro) VALUES (?,?,?,'admin')",[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Đã tạo Admin. Đăng nhập tại account/login.php.\n";
}catch(Throwable $ex){fwrite(STDERR,error_message($ex)."\n");exit(1);}
