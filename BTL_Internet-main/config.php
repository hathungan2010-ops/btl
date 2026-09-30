<?php
declare(strict_types=1);

$local = is_file(__DIR__.'/config.local.php') ? require __DIR__.'/config.local.php' : [];
$setting = static function (string $key, string $default='') use ($local): string {
    $value = getenv($key);
    return $value !== false ? $value : (string)($local[$key] ?? $default);
};
date_default_timezone_set('Asia/Ho_Chi_Minh');
$documentRoot = str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$appRoot = str_replace('\\','/',__DIR__);
$autoBase = $documentRoot !== '' && strpos($appRoot,rtrim($documentRoot,'/').'/') === 0
    ? substr($appRoot,strlen(rtrim($documentRoot,'/'))) : '';
$base_url = rtrim($setting('APP_BASE_URL',$autoBase),'/');
$store_email = $setting('STORE_EMAIL','contact@fashionstore.example');
$bank_name=$setting('BANK_NAME'); $bank_account=$setting('BANK_ACCOUNT'); $bank_owner=$setting('BANK_OWNER');
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode','1');
    session_name('fashion_store_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>$base_url ?: '/',
        'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
        'httponly'=>true,'samesite'=>'Lax']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store');
}
try {
    $conn = new PDO('mysql:host='.$setting('DB_HOST','127.0.0.1').';port='.$setting('DB_PORT','3306').
        ';dbname='.$setting('DB_NAME','fashion_store').';charset=utf8mb4',
        $setting('DB_USER','root'),$setting('DB_PASS'),
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES=>false]);
    $conn->exec("SET time_zone = '+07:00'");
} catch (PDOException $ex) {
    error_log('Fashion Store database: '.$ex->getMessage());
    http_response_code(503);
    exit('Không thể kết nối CSDL. Kiểm tra cấu hình và hướng dẫn trong README.');
}
require_once __DIR__.'/includes/functions.php';
