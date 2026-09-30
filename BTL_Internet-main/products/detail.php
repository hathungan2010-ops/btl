<?php
require_once "../config.php";

// 1. LẤY VÀ ÉP KIỂU ID SẢN PHẨM TỪ URL
$id_san_pham = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Truy vấn thông tin sản phẩm chung[cite: 2]
$stmt = $conn->prepare("SELECT sp.*, dm.ten_danh_muc 
                        FROM san_pham sp 
                        JOIN danh_muc dm ON sp.id_danh_muc = dm.id_danh_muc 
                        WHERE sp.id_san_pham = ? AND sp.trang_thai = 1");
$stmt->execute([$id_san_pham]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu truyền ID bậy bạ không có trong CSDL -> Đẩy về trang danh sách
if (!$product) {
    header("Location: " . $base_url . "/products/index.php");
    exit();
}

// 2. TRUY VẤN CÁC BIẾN THỂ (KÍCH THƯỚC + MÀU SẮC + GIÁ + TỒN KHO)
// Kết nối 3 bảng: bien_the_san_pham, kich_thuoc, mau_sac[cite: 2]
$sql_variant = "SELECT bt.*, kt.ten_kich_thuoc, ms.ten_mau_sac, ms.ma_mau 
                FROM bien_the_san_pham bt
                JOIN kich_thuoc kt ON bt.id_kich_thuoc = kt.id_kich_thuoc
                JOIN mau_sac ms ON bt.id_mau_sac = ms.id_mau_sac
                WHERE bt.id_san_pham = ? AND bt.so_luong_ton > 0";
$stmt_variant = $conn->prepare($sql_variant);
$stmt_variant->execute([$id_san_pham]);
$variants = $stmt_variant->fetchAll(PDO::FETCH_ASSOC);

require_once "../header.php";
?>

<div class="container section">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: #fff; padding: 30px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        
        <!-- Khung ảnh phóng lớn -->
        <div style="height: 420px; background: #f3f4f6; border-radius: 8px; overflow: hidden;">
            <img src="<?= $base_url ?>/images/products/<?= htmlspecialchars($product['hinh_anh']) ?>" 
                 alt="<?= htmlspecialchars($product['ten_san_pham']) ?>"
                 style="width: 100%; height: 100%; object-fit: cover;"
                 onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
        </div>

        <!-- Khung chọn thông tin để thêm vào giỏ hàng -->
        <div>
            <p style="color: #6b7280; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                Danh mục: <?= htmlspecialchars($product['ten_danh_muc']) ?>
            </p>
            
            <h1 style="font-size: 28px; font-weight: 800; margin: 8px 0; color: #111827;">
                <?= htmlspecialchars($product['ten_san_pham']) ?>
            </h1>
            
            <p style="font-size: 24px; font-weight: 800; color: #ff4757; margin-bottom: 16px;">
                <?= number_format($product['gia_co_ban'], 0, ',', '.') ?> VNĐ
            </p>
            
            <p style="color: #4b5563; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
                <?= nl2br(htmlspecialchars($product['mo_ta'])) ?>
            </p>

            <!-- HIỂN THỊ THÔNG BÁO LỖI NẾU ĐẶT VƯỢT QUÁ TỒN KHO -->
            <?php if (isset($_SESSION['error_cart'])): ?>
                <div style="color: #d63031; background: #ffebeb; padding: 12px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-bottom: 20px; border: 1px solid #ff7675;">
                    <?= $_SESSION['error_cart']; ?>
                </div>
                <?php unset($_SESSION['error_cart']); ?>
            <?php endif; ?>

            <!-- FORM GỬI THÔNG TIN SẢN PHẨM VÀO GIỎ HÀNG  -->
            <form action="<?= $base_url ?>/cart/add.php" method="POST">
                <!-- Ẩn ID sản phẩm chính -->
                <input type="hidden" name="id_san_pham" value="<?= $product['id_san_pham'] ?>">

                <!-- Ô chọn Size và Màu sắc biến thể[cite: 2] -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 8px;">
                        Chọn Size & Màu sắc *:
                    </label>
                    <?php if (!empty($variants)): ?>
                        <select name="id_bien_the" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-size: 14px;">
                            <option value="">-- Chọn Kích thước & Màu sắc --</option>
                            <?php foreach ($variants as $variant): ?>
                                <option value="<?= $variant['id_bien_the'] ?>">
                                    Size: <?= htmlspecialchars($variant['ten_kich_thuoc']) ?> | 
                                    Màu: <?= htmlspecialchars($variant['ten_mau_sac']) ?> - 
                                    <?= number_format($variant['gia'], 0, ',', '.') ?> VNĐ 
                                    (Còn: <?= $variant['so_luong_ton'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p style="color: #ff4757; font-size: 14px; font-weight: 600;">Sản phẩm hiện tại đã hết hàng.</p>
                    <?php endif; ?>
                </div>

                <!-- Ô chọn số lượng mua -->
                <div style="margin-bottom: 24px;">
                    <label style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 8px;">Số lượng:</label>
                    <input type="number" name="so_luong" value="1" min="1" style="width: 100px; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; text-align: center; font-weight: 600;">
                </div>

                <!-- Nút bấm thêm vào giỏ hàng -->
                <?php if (!empty($variants)): ?>
                    <button type="submit" class="btn" style="width: 100%; border: none; cursor: pointer; text-align: center; font-size: 15px;">
                        THÊM VÀO GIỎ HÀNG
                    </button>
                <?php endif; ?>
            </form>
        </div>

    </div>
</div>

<?php require_once "../footer.php"; ?>