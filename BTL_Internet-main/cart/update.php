//yêu cầu sửa số lượng từ giỏ hàng và cập nhật lại cột so_luong trong bảng chi_tiet_gio_hang
<?php
require_once "../config.php";

// BẢO VỆ XÁC THỰC ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_chi_tiet = isset($_POST['id_chi_tiet']) ? (int)$_POST['id_chi_tiet'] : 0;
    $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 1;

    // Nếu số lượng nhỏ hơn 1 -> Gắn mặc định là 1
    if ($so_luong < 1) {
        $so_luong = 1;
    }

    if ($id_chi_tiet > 0) {
        // Cập nhật số lượng mới vào CSDL[cite: 2]
        $stmt = $conn->prepare("UPDATE chi_tiet_gio_hang SET so_luong = ? WHERE id_chi_tiet = ?");
        $stmt->execute([$so_luong, $id_chi_tiet]);
    }
}

// Quay về trang giỏ hàng sau khi cập nhật thành công
header("Location: " . $base_url . "/cart/index.php");
exit();
?>