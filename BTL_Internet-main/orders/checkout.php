<?php
// xử lý quy trình đơn hàng, nhập địa chỉ giao hàng và lưu đơn hàng vào csdl 
// thực hiện trừ số lượng tồn kho và xóa giỏ hàng
?> 

<?php
// Nhúng cấu hình CSDL và phiên làm việc[cite: 1, 3]
require_once "../config.php";

// BẢO VỆ XÁC THỰC: Bắt buộc người dùng phải đăng nhập[cite: 1]
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php?msg=need_login");
    exit();
}

$id_user = $_SESSION['user_id'];
$error = '';

// LẤY CHI TIẾT GIỎ HÀNG CỦA NGƯỜI DÙNG[cite: 2]
$sql_cart = "SELECT ct.id_chi_tiet, ct.id_bien_the, ct.so_luong, bt.gia, bt.so_luong_ton,
                    sp.ten_san_pham, sp.hinh_anh, kt.ten_kich_thuoc, ms.ten_mau_sac
             FROM chi_tiet_gio_hang ct
             JOIN gio_hang gh ON ct.id_gio_hang = gh.id_gio_hang
             JOIN bien_the_san_pham bt ON ct.id_bien_the = bt.id_bien_the
             JOIN san_pham sp ON bt.id_san_pham = sp.id_san_pham
             JOIN kich_thuoc kt ON bt.id_kich_thuoc = kt.id_kich_thuoc
             JOIN mau_sac ms ON bt.id_mau_sac = ms.id_mau_sac
             WHERE gh.id_nguoi_dung = ?";

$stmt = $conn->prepare($sql_cart);
$stmt->execute([$id_user]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: " . $base_url . "/cart/index.php");
    exit();
}

// Tính tổng tiền đơn hàng
$tong_tien_don_hang = 0;
foreach ($cart_items as $item) {
    $tong_tien_don_hang += $item['gia'] * $item['so_luong'];
}

// LẤY THÔNG TIN TÀI KHOẢN CỦA KHÁCH HÀNG[cite: 2]
$stmt_user = $conn->prepare("SELECT ho_ten, email, so_dien_thoai FROM nguoi_dung WHERE id_nguoi_dung = ?");
$stmt_user->execute([$id_user]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// XỬ LÝ ĐẶT HÀNG KHI BẤM NÚT SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten_nhan = trim($_POST['ho_ten_nguoi_nhan']);
    $so_dien_thoai = trim($_POST['so_dien_thoai_nguoi_nhan']);
    $dia_chi_giao_hang = trim($_POST['dia_chi_giao_hang']);
    $phuong_thuc_thanh_toan = $_POST['phuong_thuc_thanh_toan'];

    if (empty($ho_ten_nhan) || empty($so_dien_thoai) || empty($dia_chi_giao_hang)) {
        $error = "Vui lòng nhập đầy đủ thông tin người nhận và địa chỉ giao hàng!";
    } else {
        try {
            $conn->beginTransaction();

            // CÂU LỆNH INSERT KHỚP CHÍNH XÁC VỚI BẢNG don_hang CỦA BẠN[cite: 2, 9]
            $sql_order = "INSERT INTO don_hang (id_nguoi_dung, ho_ten_nhan, so_dien_thoai, dia_chi_giao_hang, tong_tien, phuong_thuc_thanh_toan, trang_thai) 
                          VALUES (?, ?, ?, ?, ?, ?, 'Chờ xử lý')";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->execute([
                $id_user, 
                $ho_ten_nhan, 
                $so_dien_thoai, 
                $dia_chi_giao_hang, 
                $tong_tien_don_hang, 
                $phuong_thuc_thanh_toan
            ]);

            $id_don_hang = $conn->lastInsertId();

            // Lưu chi tiết đơn hàng & Trừ tồn kho[cite: 2]
            $sql_order_detail = "INSERT INTO chi_tiet_don_hang (id_don_hang, id_bien_the, so_luong, don_gia) VALUES (?, ?, ?, ?)";
            $stmt_order_detail = $conn->prepare($sql_order_detail);

            $sql_update_stock = "UPDATE bien_the_san_pham SET so_luong_ton = so_luong_ton - ? WHERE id_bien_the = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);

            foreach ($cart_items as $item) {
                if ($item['so_luong'] > $item['so_luong_ton']) {
                    throw new Exception("Sản phẩm '" . $item['ten_san_pham'] . "' không đủ số lượng trong kho!");
                }

                $stmt_order_detail->execute([$id_don_hang, $item['id_bien_the'], $item['so_luong'], $item['gia']]);
                $stmt_update_stock->execute([$item['so_luong'], $item['id_bien_the']]);
            }

            // Xóa sản phẩm khỏi giỏ hàng[cite: 2]
            $sql_clear_cart = "DELETE ct FROM chi_tiet_gio_hang ct 
                               JOIN gio_hang gh ON ct.id_gio_hang = gh.id_gio_hang 
                               WHERE gh.id_nguoi_dung = ?";
            $stmt_clear_cart = $conn->prepare($sql_clear_cart);
            $stmt_clear_cart->execute([$id_user]);

            $conn->commit();

            // Chuyển hướng tới trang chi tiết đơn hàng vừa đặt[cite: 1]
            header("Location: " . $base_url . "/orders/detail.php?id=" . $id_don_hang);
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Lỗi đặt hàng: " . $e->getMessage();
        }
    }
}

require_once "../header.php";
?>

<div class="container section">
    <h2 class="section-title">THANH TOÁN ĐƠN HÀNG</h2>

    <?php if ($error): ?>
        <p style="color: #ff4757; background: #ffebeb; padding: 12px; border-radius: 8px; text-align: center; font-weight: 600; margin-bottom: 20px;">
            <?= $error ?>
        </p>
    <?php endif; ?>

    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- CỘT TRÁI: THÔNG TIN GIAO HÀNG -->
        <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #111827; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">
                1. THÔNG TIN NGƯỜI NHẬN
            </h3>

            <div style="margin-bottom: 16px;">
                <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 6px;">Họ và tên người nhận *</label>
                <input type="text" name="ho_ten_nguoi_nhan" required 
                       value="<?= htmlspecialchars($_POST['ho_ten_nguoi_nhan'] ?? $user_info['ho_ten']) ?>" 
                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 6px;">Số điện thoại người nhận *</label>
                <input type="text" name="so_dien_thoai_nguoi_nhan" required 
                       value="<?= htmlspecialchars($_POST['so_dien_thoai_nguoi_nhan'] ?? $user_info['so_dien_thoai']) ?>" 
                       style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 6px;">Địa chỉ giao hàng chi tiết *</label>
                <textarea name="dia_chi_giao_hang" required rows="3" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố" 
                          style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-family: inherit;"><?= htmlspecialchars($_POST['dia_chi_giao_hang'] ?? '') ?></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 6px;">Phương thức thanh toán *</label>
                <select name="phuong_thuc_thanh_toan" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
                    <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                    <option value="Chuyển khoản">Chuyển khoản ngân hàng</option>
                </select>
            </div>
        </div>

        <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG -->
        <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #111827; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">
                    2. ĐƠN HÀNG CỦA BẠN
                </h3>

                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <?php foreach ($cart_items as $item): 
                        $subtotal = $item['gia'] * $item['so_luong'];
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f3f4f6;">
                            <div>
                                <p style="font-weight: 700; font-size: 14px; margin: 0; color: #111827;">
                                    <?= htmlspecialchars($item['ten_san_pham']) ?>
                                </p>
                                <p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">
                                    Size: <?= $item['ten_kich_thuoc'] ?> | Màu: <?= $item['ten_mau_sac'] ?> | SL: x<?= $item['so_luong'] ?>
                                </p>
                            </div>
                            <span style="font-weight: 700; font-size: 14px; color: #111827;">
                                <?= number_format($subtotal, 0, ',', '.') ?> VNĐ
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 2px solid #e5e7eb; padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #4b5563;">
                        <span>Phí vận chuyển:</span>
                        <span style="color: #10b981; font-weight: 600;">Miễn phí</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 800; color: #111827;">
                        <span>Tổng tiền thanh toán:</span>
                        <span style="color: #ff4757;"><?= number_format($tong_tien_don_hang, 0, ',', '.') ?> VNĐ</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%; border: none; cursor: pointer; text-align: center; margin-top: 24px; font-size: 16px; padding: 14px;">
                XÁC NHẬN ĐẶT HÀNG
            </button>
        </div>
    </form>
</div>

<?php require_once "../footer.php"; ?>