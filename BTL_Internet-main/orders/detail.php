<?php
// xem chi tiết 1 đơn hàng
// Hiển thị thông tin giao hàng và danh sách sản phẩm trong đơn đó (kết nối bảng don_hang và chi_tiet_don_hang)?>

<?php
// Nhúng cấu hình CSDL và phiên làm việc[cite: 1, 3]
require_once "../config.php";

// BẢO VỆ CHỨC NĂNG: Bắt buộc đăng nhập[cite: 1]
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php?msg=need_login");
    exit();
}

$id_user = $_SESSION['user_id'];
$id_don_hang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. TRUY VẤN LẤY THÔNG TIN ĐƠN HÀNG[cite: 2]
$stmt = $conn->prepare("SELECT * FROM don_hang WHERE id_don_hang = ? AND id_nguoi_dung = ?");
$stmt->execute([$id_don_hang, $id_user]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: " . $base_url . "/orders/history.php");
    exit();
}

// 2. TRUY VẤN CHI TIẾT CÁC MÓN HÀNG TRONG ĐƠN[cite: 2]
$sql_detail = "SELECT ct.*, bt.gia, sp.ten_san_pham, sp.hinh_anh, kt.ten_kich_thuoc, ms.ten_mau_sac
                FROM chi_tiet_don_hang ct
                JOIN bien_the_san_pham bt ON ct.id_bien_the = bt.id_bien_the
                JOIN san_pham sp ON bt.id_san_pham = sp.id_san_pham
                JOIN kich_thuoc kt ON bt.id_kich_thuoc = kt.id_kich_thuoc
                JOIN mau_sac ms ON bt.id_mau_sac = ms.id_mau_sac
                WHERE ct.id_don_hang = ?";

$stmt_detail = $conn->prepare($sql_detail);
$stmt_detail->execute([$id_don_hang]);
$order_items = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

require_once "../header.php";
?>

<div class="container section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">
            CHI TIẾT ĐƠN HÀNG #<?= $order['id_don_hang'] ?>
        </h2>
        <a href="<?= $base_url ?>/orders/history.php" style="color: #4b5563; font-weight: 600; text-decoration: underline;">
            ← Quay lại lịch sử
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- CỘT TRÁI: THÔNG TIN GIAO HÀNG -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; height: fit-content;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                THÔNG TIN NHẬN HÀNG
            </h3>
            <!-- Sửa ho_ten_nguoi_nhan -> ho_ten_nhan & so_dien_thoai_nguoi_nhan -> so_dien_thoai[cite: 9] -->
            <p style="font-size: 14px; margin-bottom: 8px;"><strong>Người nhận:</strong> <?= htmlspecialchars($order['ho_ten_nhan'] ?? '') ?></p>
            <p style="font-size: 14px; margin-bottom: 8px;"><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['so_dien_thoai'] ?? '') ?></p>
            <p style="font-size: 14px; margin-bottom: 8px;"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['dia_chi_giao_hang']) ?></p>
            <p style="font-size: 14px; margin-bottom: 8px;"><strong>Thanh toán:</strong> <?= htmlspecialchars($order['phuong_thuc_thanh_toan']) ?></p>
            <p style="font-size: 14px; margin-bottom: 8px;"><strong>Trạng thái:</strong> <span style="color: #d97706; font-weight: 700;"><?= htmlspecialchars($order['trang_thai']) ?></span></p>
        </div>

        <!-- CỘT PHẢI: DANH SÁCH MÓN HÀNG -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                SẢN PHẨM ĐÃ ĐẶT
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <?php foreach ($order_items as $item): 
                        $subtotal = $item['don_gia'] * $item['so_luong'];
                    ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 10px 0; display: flex; align-items: center; gap: 12px;">
                                <img src="<?= $base_url ?>/images/products/<?= htmlspecialchars($item['hinh_anh']) ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;"
                                     onerror="this.src='https://via.placeholder.com/50x50?text=No+Img'">
                                <div>
                                    <p style="font-weight: 700; font-size: 14px; margin: 0;"><?= htmlspecialchars($item['ten_san_pham']) ?></p>
                                    <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0 0;">Size: <?= $item['ten_kich_thuoc'] ?> | Màu: <?= $item['ten_mau_sac'] ?></p>
                                </div>
                            </td>
                            <td style="padding: 10px; text-align: center; font-size: 14px;">x<?= $item['so_luong'] ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 700; font-size: 14px; color: #111827;">
                                <?= number_format($subtotal, 0, ',', '.') ?> VNĐ
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="text-align: right; margin-top: 16px; font-size: 18px; font-weight: 800;">
                Tổng tiền: <span style="color: #ff4757;"><?= number_format($order['tong_tien'], 0, ',', '.') ?> VNĐ</span>
            </div>
        </div>
    </div>
</div>

<?php require_once "../footer.php"; ?>