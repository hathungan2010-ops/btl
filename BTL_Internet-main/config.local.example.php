<?php
// Sao chép thành config.local.php. Không commit thông tin đăng nhập thật.
return [
    'DB_HOST'=>'127.0.0.1', 'DB_PORT'=>'3306', 'DB_NAME'=>'fashion_store',
    'DB_USER'=>'root', 'DB_PASS'=>'',
    // Bỏ dòng này để tự nhận đường dẫn; dùng '' khi chạy php -S ở gốc.
    'APP_BASE_URL'=>'/fashion_store',
    'STORE_EMAIL'=>'contact@fashionstore.example',
    // Điền đủ 3 mục để bật chuyển khoản thủ công.
    'BANK_NAME'=>'', 'BANK_ACCOUNT'=>'', 'BANK_OWNER'=>'',
];
