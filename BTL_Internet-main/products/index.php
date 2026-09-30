<?php
// Nhúng file cấu hình kết nối CSDL và lùi 1 cấp thư mục để lấy config.php[cite: 1]
require_once "../config.php";

// 1. KHỞI TẠO MẢNG ĐIỀU KIỆN LỌC (Trạng thái đang kinh doanh)[cite: 2]
$where = ["sp.trang_thai = 1"];
$params = [];

// Kiểm tra nếu người dùng lọc theo Danh mục (?category=id)[cite: 1, 4, 5]
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where[] = "sp.id_danh_muc = ?";
    $params[] = (int)$_GET['category'];
}

// Kiểm tra nếu người dùng tìm kiếm từ ô Search (?search=từ_khóa)[cite: 1, 5]
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where[] = "sp.ten_san_pham LIKE ?";
    $params[] = "%" . trim($_GET['search']) . "%";
}

// 2. TRUY VẤN LẤY DỮ LIỆU SẢN PHẨM[cite: 2]
$sql = "SELECT sp.*, dm.ten_danh_muc 
        FROM san_pham sp 
        JOIN danh_muc dm ON sp.id_danh_muc = dm.id_danh_muc 
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY sp.id_san_pham DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nhúng Header chung[cite: 1]
require_once "../header.php";
?>

<div class="container section">
    <h2 class="section-title">
        <?php 
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                echo "KẾT QUẢ TÌM KIẾM: '" . htmlspecialchars($_GET['search']) . "'";
            } elseif (isset($_GET['category']) && !empty($_GET['category']) && !empty($products)) {
                echo "DANH MỤC: " . mb_strtoupper(htmlspecialchars($products[0]['ten_danh_muc']));
            } else {
                echo "TẤT CẢ SẢN PHẨM";
            }
        ?>
    </h2>

    <div class="products">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <!-- BỌC THẺ <a> TOÀN BỘ CARD ĐỂ BẤM VÀO ĐÂU CŨNG CHUYỂN SANG TRANG CHI TIẾT[cite: 1] -->
                <a href="<?= $base_url ?>/products/detail.php?id=<?= $product['id_san_pham'] ?>" 
                   class="product-card" 
                   style="display: flex; flex-direction: column;">
                    
                    <!-- Ảnh sản phẩm -->
                    <div class="product-image">
                        <?php if (!empty($product['hinh_anh'])): ?>
                            <img src="<?= $base_url ?>/images/products/<?= htmlspecialchars($product['hinh_anh']) ?>" 
                                 alt="<?= htmlspecialchars($product['ten_san_pham']) ?>"
                                 onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">
                        <?php else: ?>
                            Fashion Store
                        <?php endif; ?>
                    </div>

                    <!-- Thông tin sản phẩm -->
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($product['ten_san_pham']) ?></h3>
                        <p class="product-description"><?= htmlspecialchars($product['mo_ta']) ?></p>
                        <p class="product-price"><?= number_format($product['gia_co_ban'], 0, ',', '.') ?> VNĐ</p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #6b7280; padding: 40px 0;">
                Không tìm thấy sản phẩm nào phù hợp.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php 
// Nhúng Footer chung[cite: 1]
require_once "../footer.php"; 
?>