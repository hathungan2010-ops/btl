<?php
// Nhúng cấu hình CSDL và phiên làm việc
require_once "../config.php";

// 1. BẢO VỆ CHỨC NĂNG: Khách vãng lai chưa đăng nhập sẽ bị đẩy về trang Login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php?msg=need_login");
    exit();
}

// 2. KÍCH HOẠT XỬ LÝ: Chỉ nhận dữ liệu gửi qua phương thức POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['user_id'];
    $id_bien_the = isset($_POST['id_bien_the']) ? (int)$_POST['id_bien_the'] : 0;
    $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 1;

    // Kiểm tra dữ liệu đầu vào hợp lệ
    if ($id_bien_the <= 0 || $so_luong <= 0) {
        header("Location: " . $base_url . "/products/index.php");
        exit();
    }

    try {
        // 3. TRUY VẤN LẤY THÔNG TIN TỒN KHO CỦA BIẾN THỂ VÀ ID SẢN PHẨM MẸ
        $stmt = $conn->prepare("SELECT id_san_pham, so_luong_ton FROM bien_the_san_pham WHERE id_bien_the = ?");
        $stmt->execute([$id_bien_the]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$variant) {
            header("Location: " . $base_url . "/products/index.php");
            exit();
        }

        $id_san_pham = $variant['id_san_pham'];
        $so_luong_ton = (int)$variant['so_luong_ton'];

        // 4. KIỂM TRA GIỎ HÀNG VÀ SỐ LƯỢNG MÓN NÀY ĐÃ CÓ TRONG GIỎ CỦA KHÁCH HÀNG
        $stmt = $conn->prepare("SELECT id_gio_hang FROM gio_hang WHERE id_nguoi_dung = ?");
        $stmt->execute([$id_user]);
        $gio_hang = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gio_hang) {
            // Tạo giỏ hàng mới nếu chưa có
            $stmt = $conn->prepare("INSERT INTO gio_hang (id_nguoi_dung) VALUES (?)");
            $stmt->execute([$id_user]);
            $id_gio_hang = $conn->lastInsertId();
            $so_luong_dang_co = 0;
            $chi_tiet = false;
        } else {
            $id_gio_hang = $gio_hang['id_gio_hang'];

            // Lấy số lượng đã nằm trong giỏ trước đó
            $stmt = $conn->prepare("SELECT id_chi_tiet, so_luong FROM chi_tiet_gio_hang WHERE id_gio_hang = ? AND id_bien_the = ?");
            $stmt->execute([$id_gio_hang, $id_bien_the]);
            $chi_tiet = $stmt->fetch(PDO::FETCH_ASSOC);
            $so_luong_dang_co = $chi_tiet ? (int)$chi_tiet['so_luong'] : 0;
        }

        // 5. KIỂM TRA TỔNG SỐ LƯỢNG MUA SO VỚI TỒN KHO
        $tong_so_luong = $so_luong_dang_co + $so_luong;

        if ($tong_so_luong > $so_luong_ton) {
            // Nếu vượt quá -> Tạo thông báo lỗi và quay lại trang chi tiết sản phẩm
            if ($so_luong_dang_co > 0) {
                $_SESSION['error_cart'] = "Số lượng đặt vượt quá số lượng còn lại trong kho! (Trong kho còn: $so_luong_ton, giỏ hàng của bạn đã có sẵn: $so_luong_dang_co)";
            } else {
                $_SESSION['error_cart'] = "Số lượng đặt vượt quá số lượng còn lại trong kho! (Trong kho chỉ còn: $so_luong_ton sản phẩm)";
            }
            
            header("Location: " . $base_url . "/products/detail.php?id=" . $id_san_pham);
            exit();
        }

        // 6. THƯC HIỆN LƯU VÀO CSDL NẾU ĐỦ HÀNG trong kho 
        if ($chi_tiet) {
            // Đã có -> Cập nhật số lượng cộng dồn
            $stmt = $conn->prepare("UPDATE chi_tiet_gio_hang SET so_luong = ? WHERE id_chi_tiet = ?");
            $stmt->execute([$tong_so_luong, $chi_tiet['id_chi_tiet']]);
        } else {
            // Chưa có -> Thêm mới vào chi tiết giỏ hàng[cite: 2]
            $stmt = $conn->prepare("INSERT INTO chi_tiet_gio_hang (id_gio_hang, id_bien_the, so_luong) VALUES (?, ?, ?)");
            $stmt->execute([$id_gio_hang, $id_bien_the, $so_luong]);
        }

        // Chuyển hướng sang giao diện giỏ hàng[cite: 1]
        header("Location: " . $base_url . "/cart/index.php");
        exit();

    } catch (PDOException $e) {
        die("Lỗi xử lý giỏ hàng: " . $e->getMessage());
    }
} else {
    header("Location: " . $base_url . "/products/index.php");
    exit();
}
?>