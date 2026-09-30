<?php
// Nhúng file cấu hình kết nối CSDL và khởi tạo Session
require_once "../config.php";

$error = '';
$notification = '';

// KÍCH HOẠT THÔNG BÁO: Kiểm tra nếu khách vãng lai bị đẩy từ trang giỏ hàng/thanh toán sang
if (isset($_GET['msg']) && $_GET['msg'] === 'need_login') {
    $notification = "Vui lòng đăng nhập tài khoản để thực hiện chức năng mua hàng!";
}

// XỬ LÝ ĐĂNG NHẬP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mat_khau = $_POST['mat_khau'];

    if (empty($email) || empty($mat_khau)) {
        $error = "Vui lòng nhập đầy đủ Email và Mật khẩu!";
    } else {
        // Truy vấn lấy người dùng theo Email (chỉ tài khoản đang kích hoạt trang_thai = 1)
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE email = ? AND trang_thai = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Đối chiếu mật khẩu mã hóa[cite: 2]
        if ($user && password_verify($mat_khau, $user['mat_khau'])) {
            // 1. LƯU SESSION CỐT LÕI
            $_SESSION['user_id'] = $user['id_nguoi_dung'];
            $_SESSION['ho_ten'] = $user['ho_ten'];
            $_SESSION['vai_tro'] = $user['vai_tro']; // Lưu vai trò: 'customer' hoặc 'admin'[cite: 2]

            // 2. RẼ NHÁNH ĐIỀU HƯỚNG THEO VAI TRÒ[cite: 1, 2]
            if ($user['vai_tro'] === 'admin') {
                // Nếu là Admin -> Điều hướng vào trang quản trị
                header("Location: " . $base_url . "/admin/index.php");
            } else {
                // Nếu là Khách hàng -> Điều hướng về trang chủ
                header("Location: " . $base_url . "/index.php");
            }
            exit(); // Dừng luồng xử lý
        } else {
            $error = "Email hoặc mật khẩu không chính xác!";
        }
    }
}

require_once "../header.php";
?>

<!-- GIAO DIỆN FORM ĐĂNG NHẬP (CĂN GIỮA) -->
<div class="container section" style="max-width: 420px; margin: 60px auto;">
    <h2 class="section-title">ĐĂNG NHẬP</h2>
    
    <!-- Hiển thị thông báo nhắc nhở khách vãng lai đăng nhập -->
    <?php if ($notification): ?>
        <p style="color: #e67e22; background: #fef5e7; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 13px; font-weight: 600; border: 1px solid #fadbd8;">
            <?= $notification ?>
        </p>
    <?php endif; ?>

    <!-- Hiển thị thông báo lỗi nếu nhập sai thông tin -->
    <?php if ($error): ?>
        <p style="color: #ff4757; margin-bottom: 15px; text-align: center; font-weight: 600;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" style="background: #fff; padding: 28px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        <div style="margin-bottom: 16px;">
            <label style="font-weight: 600; font-size: 14px;">Email</label>
            <input type="email" name="email" required placeholder="example@email.com" style="width: 100%; padding: 10px 12px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
        </div>
        <div style="margin-bottom: 24px;">
            <label style="font-weight: 600; font-size: 14px;">Mật khẩu</label>
            <input type="password" name="mat_khau" required placeholder="••••••••" style="width: 100%; padding: 10px 12px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
        </div>
        <button type="submit" class="btn" style="width: 100%; border: none; cursor: pointer; text-align: center;">ĐĂNG NHẬP</button>
        <p style="text-align: center; margin-top: 16px; font-size: 14px; color: #6b7280;">Chưa có tài khoản? <a href="register.php" style="color: #ff4757; font-weight: 700;">Đăng ký ngay</a></p>
    </form>
</div>

<?php require_once "../footer.php"; ?>