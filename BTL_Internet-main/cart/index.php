<?php
// Nhúng file config và kiểm tra đăng nhập
require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/account/login.php?msg=need_login");
    exit();
}

$id_user = $_SESSION['user_id'];

// TRUY VẤN LẤY CHI TIẾT GIỎ HÀNG KẾT NỐI VỚI BẢNG SẢN PHẨM & BIẾN THỂ
$sql = "SELECT ct.id_chi_tiet, ct.so_luong, bt.gia, bt.so_luong_ton,
               sp.ten_san_pham, sp.hinh_anh, kt.ten_kich_thuoc, ms.ten_mau_sac
        FROM chi_tiet_gio_hang ct
        JOIN gio_hang gh ON ct.id_gio_hang = gh.id_gio_hang
        JOIN bien_the_san_pham bt ON ct.id_bien_the = bt.id_bien_the
        JOIN san_pham sp ON bt.id_san_pham = sp.id_san_pham
        JOIN kich_thuoc kt ON bt.id_kich_thuoc = kt.id_kich_thuoc
        JOIN mau_sac ms ON bt.id_mau_sac = ms.id_mau_sac
        WHERE gh.id_nguoi_dung = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_user]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tong_tien_gio_hang = 0;

require_once "../header.php";
?>

<div class="container section">
    <h2 class="section-title">GIỎ HÀNG CỦA BẠN</h2>

    <?php if (!empty($cart_items)): ?>
        <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <!-- BẢNG DANH SÁCH SẢN PHẨM IN GIỎ -->
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; font-size: 14px; color: #6b7280;">
                        <th style="padding: 12px;">Sản phẩm</th>
                        <th style="padding: 12px;">Phân loại</th>
                        <th style="padding: 12px;">Đơn giá</th>
                        <th style="padding: 12px; text-align: center;">Số lượng</th>
                        <th style="padding: 12px;">Thành tiền</th>
                        <th style="padding: 12px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): 
                        $thanh_tien = $item['gia'] * $item['so_luong'];
                        $tong_tien_gio_hang += $thanh_tien;
                    ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <!-- Ảnh + Tên sản phẩm -->
                            <td style="padding: 16px 12px; display: flex; align-items: center; gap: 12px;">
                                <img src="<?= $base_url ?>/images/products/<?= htmlspecialchars($item['hinh_anh']) ?>" 
                                     alt="<?= htmlspecialchars($item['ten_san_pham']) ?>" 
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                     onerror="this.src='https://via.placeholder.com/60x60?text=No+Img'">
                                <span style="font-weight: 700; font-size: 14px; color: #111827;">
                                    <?= htmlspecialchars($item['ten_san_pham']) ?>
                                </span>
                            </td>

                            <!-- Phân loại Size - Màu -->
                            <td style="padding: 12px; font-size: 13px; color: #4b5563;">
                                Size: <?= htmlspecialchars($item['ten_kich_thuoc']) ?> <br>
                                Màu: <?= htmlspecialchars($item['ten_mau_sac']) ?>
                            </td>

                            <!-- Đơn giá -->
                            <td style="padding: 12px; font-size: 14px; font-weight: 600;">
                                <?= number_format($item['gia'], 0, ',', '.') ?> VNĐ
                            </td>

                            <!-- Form Cập nhật Số lượng-->
                            <td style="padding: 12px; text-align: center;">
                                <form action="<?= $base_url ?>/cart/update.php" method="POST" style="display: inline-flex; gap: 6px;">
                                    <input type="hidden" name="id_chi_tiet" value="<?= $item['id_chi_tiet'] ?>">
                                    <input type="number" name="so_luong" value="<?= $item['so_luong'] ?>" min="1" max="<?= $item['so_luong_ton'] ?>" 
                                           style="width: 60px; padding: 6px; border: 1px solid #d1d5db; border-radius: 6px; text-align: center;">
                                    <button type="submit" style="padding: 6px 10px; background: #374151; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">Sửa</button>
                                </form>
                            </td>

                            <!-- Thành tiền -->
                            <td style="padding: 12px; font-size: 14px; font-weight: 700; color: #ff4757;">
                                <?= number_format($thanh_tien, 0, ',', '.') ?> VNĐ
                            </td>

                            <!-- Nút Xóa[cite: 1] -->
                            <td style="padding: 12px; text-align: center;">
                                <a href="<?= $base_url ?>/cart/delete.php?id=<?= $item['id_chi_tiet'] ?>" 
                                   onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')"
                                   style="color: #ef4444; font-weight: 600; font-size: 13px;">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TỔNG TIỀN VÀ NÚT THANH TOÁN -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 16px; border-top: 2px solid #e5e7eb;">
                <a href="<?= $base_url ?>/cart/delete.php?action=clear" 
                   onclick="return confirm('Xóa toàn bộ sản phẩm trong giỏ hàng?')" 
                   style="color: #6b7280; font-size: 13px; text-decoration: underline;">Xóa tất cả</a>

                <div style="text-align: right;">
                    <p style="font-size: 18px; font-weight: 800; color: #111827;">
                        Tổng tiền: <span style="color: #ff4757;"><?= number_format($tong_tien_gio_hang, 0, ',', '.') ?> VNĐ</span>
                    </p>
                    <a href="<?= $base_url ?>/orders/checkout.php" class="btn" style="margin-top: 12px; display: inline-block;">
                        TIẾN HÀNH THANH TOÁN
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- TRƯỜNG HỢP GIỎ HÀNG TRỐNG -->
        <div style="text-align: center; padding: 60px 0; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;">
            <p style="color: #6b7280; font-size: 16px; margin-bottom: 20px;">Giỏ hàng của bạn đang trống.</p>
            <a href="<?= $base_url ?>/products/index.php" class="btn">TIẾP TỤC MUA SẮM</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once "../footer.php"; ?>