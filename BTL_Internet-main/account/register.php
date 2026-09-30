<?php
// Nhúng file cấu hình kết nối CSDL và khởi tạo Session
require_once "../config.php";

$error = '';
$success = '';

// KÍCH HOẠT XỬ LÝ: Chỉ thực thi khi người dùng bấm nút Submit gửi Form (phương thức POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu đầu vào và dùng trim() xóa khoảng trắng thừa
    $ho_ten = trim($_POST['ho_ten']);
    $email = trim($_POST['email']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $mat_khau = $_POST['mat_khau'];

    // 2. Kiểm tra các trường bắt buộc không được để trống
    if (empty($ho_ten) || empty($email) || empty($mat_khau)) {
        $error = "Vui lòng nhập đầy đủ Họ tên, Email và Mật khẩu!";
    } else {
        // 3. Kiểm tra xem Email đã được đăng ký trong CSDL chưa[cite: 2]
        $stmt = $conn->prepare("SELECT id_nguoi_dung FROM nguoi_dung WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email này đã được đăng ký trước đó!";
        } else {
            // 4. Mã hóa mật khẩu bảo mật bằng password_hash()
            $hashed_password = password_hash($mat_khau, PASSWORD_DEFAULT);
            
            // 5. Thêm người dùng mới vào bảng nguoi_dung 
            $sql = "INSERT INTO nguoi_dung (ho_ten, email, so_dien_thoai, mat_khau) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            // 6. Thực thi lưu dữ liệu
            if ($stmt->execute([$ho_ten, $email, $so_dien_thoai, $hashed_password])) {
                $success = "Đăng ký thành công! <a href='login.php' style='color: #ff4757; font-weight: 700;'>Đăng nhập ngay</a>";
            } else {
                $error = "Đã có lỗi xảy ra trong quá trình lưu dữ liệu.";
            }
        }
    }
}

// Nhúng phần giao diện Header
require_once "../header.php";
?>

<!-- GIAO DIỆN FORM ĐĂNG KÝ TỐI GIẢN (ĐÃ CĂN GIỮA) -->
<div class="container section" style="max-width: 450px; margin: 60px auto;">
    <h2 class="section-title">ĐĂNG KÝ TÀI KHOẢN</h2>
    
    <!-- Hiển thị thông báo lỗi hoặc thành công nếu có -->
    <?php if ($error): ?>
        <p style="color: #ff4757; margin-bottom: 15px; text-align: center; font-weight: 600;"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: #2ed573; margin-bottom: 15px; text-align: center; font-weight: 600;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" style="background: #fff; padding: 28px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        <!-- Ô nhập Họ và Tên -->
        <div style="margin-bottom: 16px;">
            <label style="font-weight: 600; font-size: 14px;">Họ và tên *</label>
            <input type="text" name="ho_ten" required placeholder="Nguyễn Văn A" style="width: 100%; padding: 10px 12px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
        </div>

        <!-- Ô nhập Email -->
        <div style="margin-bottom: 16px;">
            <label style="font-weight: 600; font-size: 14px;">Email *</label>
            <input type="email" name="email" required placeholder="example@email.com" style="width: 100%; padding: 10px 12px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
        </div>

        <!-- Ô nhập Số điện thoại -->
        <div style="margin-bottom: 16px;">
            <label style="font-weight: 600; font-size: 14px;">Số điện thoại</label>
            <input type="text" name="so_dien_thoai" placeholder="0901234567" style="width: 100%; padding: 10px 12px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
        </div>

        <!-- Ô nhập Mật khẩu -->
        <div style="margin-bottom: 24px;">
            <label style="font-weight: 600; font-size: 14px;">Mật khẩu *</label>
            <input type="password" name="mat_khau" required placeholder="••••••••" style="width: 100%; padding: 10px 12px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
        </div>

        <!-- Nút gửi Form -->
        <button type="submit" class="btn" style="width: 100%; border: none; cursor: pointer; text-align: center;">ĐĂNG KÝ</button>

        <p style="text-align: center; margin-top: 16px; font-size: 14px; color: #6b7280;">
            Đã có tài khoản? <a href="login.php" style="color: #ff4757; font-weight: 700;">Đăng nhập</a>
        </p>
    </form>
</div>

<?php 
// Nhúng phần giao diện Footer[cite: 1]
require_once "../footer.php"; 
?>