<?php
// Nhúng file config để tiếp cận Session hiện tại
require_once "../config.php";

// 1. Xóa riêng từng biến thông tin người dùng trong Session
unset($_SESSION['user_id']);
unset($_SESSION['ho_ten']);
unset($_SESSION['vai_tro']);

// 2. Hủy toàn bộ dữ liệu Session trên Server
session_destroy();

// 3. Điều hướng người dùng quay lại trang đăng nhập
header("Location: " . $base_url . "/account/login.php");
exit();
?>