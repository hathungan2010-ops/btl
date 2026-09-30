<?php
// Nhúng file cấu hình kết nối CSDL và khởi tạo Session (nằm cùng thư mục gốc)[cite: 1, 3]
require_once "config.php";

// 1. TRUY VẤN LẤY 8 SẢN PHẨM MỚI NHẤT[cite: 6]
$sql = "
    SELECT *
    FROM san_pham
    WHERE trang_thai = 1
    ORDER BY id_san_pham DESC
    LIMIT 8
";
$stmt = $conn->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. TRUY VẤN LẤY DANH SÁCH DANH MỤC[cite: 6]
$sql_category = "
    SELECT *
    FROM danh_muc
    WHERE trang_thai = 1
";
$stmt_category = $conn->query($sql_category);
$categories = $stmt_category->fetchAll(PDO::FETCH_ASSOC);

// Nhúng Header nằm cùng thư mục gốc[cite: 1, 5]
require_once "header.php";
?>

<main>
    <!-- HERO BANNER TRANG CHỦ-->
    <section class="hero">
    <a href="<?= $base_url ?>/products/index.php" class="hero-link" title="Khám phá ngay"></a>
    </section>

    <div class="container">
        <!-- KHỐI DANH MỤC[cite: 6] -->
        <section class="section">
            <h2 class="section-title">DANH MỤC</h2>
            <div class="categories">
                <?php foreach ($categories as $category): ?>
                    <a class="category" href="<?= $base_url ?>/products/index.php?category=<?= $category['id_danh_muc'] ?>">
                        <?= htmlspecialchars($category['ten_danh_muc']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- KHỐI SẢN PHẨM NỔI BẬT (ĐÃ BỌC THẺ <a> TOÀN BỘ KHUNG)[cite: 1, 6] -->
        <section class="section">
            <h2 class="section-title">SẢN PHẨM NỔI BẬT</h2>

            <div class="products">
                <?php foreach ($products as $product): ?>
                    <!-- BỌC THẺ <a> CHO TOÀN BỘ CARD ĐỂ BẤM VÀO ĐÂU CŨNG CHUYỂN SANG TRANG CHI TIẾT[cite: 1] -->
                    <a href="<?= $base_url ?>/products/detail.php?id=<?= $product['id_san_pham'] ?>" 
                       class="product-card" 
                       style="display: flex; flex-direction: column; text-decoration: none; color: inherit;">
                        
                        <!-- Ảnh sản phẩm -->
                        <div class="product-image">
                            <?php if (!empty($product['hinh_anh'])): ?>
                                <img src="<?= $base_url ?>/images/products/<?= htmlspecialchars($product['hinh_anh']) ?>"
                                     alt="<?= htmlspecialchars($product['ten_san_pham']) ?>"
                                     onerror="this.src='https://via.placeholder.com/300x300?text=Fashion+Store'">
                            <?php else: ?>
                                Fashion Store
                            <?php endif; ?>
                        </div>

                        <!-- Thông tin sản phẩm -->
                        <div class="product-info">
                            <h3 class="product-name">
                                <?= htmlspecialchars($product['ten_san_pham']) ?>
                            </h3>

                            <p class="product-description">
                                <?= htmlspecialchars($product['mo_ta']) ?>
                            </p>

                            <p class="product-price">
                                <?= number_format($product['gia_co_ban'], 0, ',', '.') ?> VNĐ
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- KHỐI GIỚI THIỆU[cite: 6] -->
        <section class="section">
            <h2 class="section-title">GIỚI THIỆU</h2>
            <div class="video-box">
                Video giới thiệu Fashion Store
            </div>
        </section>
    </div>
</main>

<?php
// Nhúng Footer nằm cùng thư mục gốc[cite: 1, 4]
require_once "footer.php";
?>