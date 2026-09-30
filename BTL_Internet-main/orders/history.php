<?php
// lịch sử đơn hàng----
// thực hiện truy vấn toàn bộ danh sách đơn hàng mà người dùng đó đã đặt trong CSDL
// xếp đơn mới nhất lên đầu 
?>

<?php
// Nhúng cấu hình CSDL và phiên làm việc[cite: 1, 3]
require_once "../config.php";

// BẢO VỆ CHỨC NĂNG: Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php?msg=need_login");
    exit();
}

$id_user = $_SESSION['user_id'];

// Truy vấn lấy danh sách đơn hàng của người dùng[cite: 2]
$sql = "SELECT * FROM don_hang WHERE id_nguoi_dung = ? ORDER BY id_don_hang DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_user]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "../header.php";
?>

<div class="container section">
    <h2 class="section-title">LỊCH SỬ MUA HÀNG</h2>

    <?php if (!empty($orders)): ?>
        <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; font-size: 14px; color: #6b7280;">
                        <th style="padding: 12px;">Mã đơn</th>
                        <th style="padding: 12px;">Ngày đặt</th>
                        <th style="padding: 12px;">Người nhận</th>
                        <th style="padding: 12px;">Tổng tiền</th>
                        <th style="padding: 12px;">Trạng thái</th>
                        <th style="padding: 12px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6; font-size: 14px;">
                            <td style="padding: 14px 12px; font-weight: 700; color: #ff4757;">
                                #<?= $order['id_don_hang'] ?>
                            </td>
                            <td style="padding: 12px; color: #4b5563;">
                                <?= date('d/m/Y H:i', strtotime($order['ngay_dat'] ?? 'now')) ?>
                            </td>
                            <td style="padding: 12px; font-weight: 600;">
                                <!-- Sửa ho_ten_nguoi_nhan thành ho_ten_nhan[cite: 9] -->
                                <?= htmlspecialchars($order['ho_ten_nhan'] ?? '') ?>
                            </td>
                            <td style="padding: 12px; font-weight: 700;">
                                <?= number_format($order['tong_tien'], 0, ',', '.') ?> VNĐ
                            </td>
                            <td style="padding: 12px;">
                                <span style="background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                    <?= htmlspecialchars($order['trang_thai']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="<?= $base_url ?>/orders/detail.php?id=<?= $order['id_don_hang'] ?>" 
                                   class="btn" 
                                   style="padding: 6px 12px; font-size: 12px;">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;">
            <p style="color: #6b7280; font-size: 16px; margin-bottom: 20px;">Bạn chưa có đơn hàng nào.</p>
            <a href="<?= $base_url ?>/products/index.php" class="btn">MUA SẮM NGAY</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../footer.php"; ?>