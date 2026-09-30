// xóa 1 món hoặc xóa toàn bộ giỏ hàng
// Database xóa dòng tương ứng trong bảng chi_tiet_gio_hang 
<?php
require_once "../config.php";

// BẢO VỆ XÁC THỰC ĐĂNG NHẬP[cite: 1]
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php");
    exit();
}

$id_user = $_SESSION['user_id'];

// 1. TRƯỜNG HỢP XÓA TOÀN BỘ GIỎ HÀNG (?action=clear)[cite: 1]
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $stmt = $conn->prepare("DELETE ct FROM chi_tiet_gio_hang ct 
                            JOIN gio_hang gh ON ct.id_gio_hang = gh.id_gio_hang 
                            WHERE gh.id_nguoi_dung = ?");
    $stmt->execute([$id_user]);
} 
// 2. TRƯỜNG HỢP XÓA 1 MÓN CỤ THỂ (?id=id_chi_tiet)[cite: 1]
elseif (isset($_GET['id'])) {
    $id_chi_tiet = (int)$_GET['id'];
    if ($id_chi_tiet > 0) {
        $stmt = $conn->prepare("DELETE FROM chi_tiet_gio_hang WHERE id_chi_tiet = ?");
        $stmt->execute([$id_chi_tiet]);
    }
}

// Quay về trang giỏ hàng[cite: 1]
header("Location: " . $base_url . "/cart/index.php");
exit();
?>