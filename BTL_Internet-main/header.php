<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fashion Store</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= $base_url ?>/css/style.css">
</head>

<body>

<header class="header">
    <div class="header-container">
        <!-- Logo -->
        <a href="<?= $base_url ?>/index.php" class="logo">
            FASHION STORE
        </a>
        <!-- Menu -->
        <nav class="menu">
            <a href="<?= $base_url ?>/index.php">
                Trang chủ
            </a>
            
            <a href="<?= $base_url ?>/products/index.php">
                Sản phẩm
            </a>

            <a href="<?= $base_url ?>/products/index.php?category=1">
                Áo
            </a>

            <a href="<?= $base_url ?>/products/index.php?category=2">
                Quần
            </a>

        </nav>

        <!-- Search -->
        <form class="search-form"
              action="<?= $base_url ?>/products/index.php"
              method="GET">

            <input
                type="text"
                name="search"
                placeholder="Tìm kiếm sản phẩm..."
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
            >

            <button type="submit">
                Tìm
            </button>

        </form>

        <!-- KHU VỰC THAO TÁC NGƯỜI DÙNG & GIỎ HÀNG -->
        <div class="header-actions">
            <!-- Kiểm tra trạng thái đã đăng nhập -->
            <?php if (isset($_SESSION['user_id'])): ?>
                
                <!-- Hiển thị tên người dùng gọn gàng -->
                <span class="user-name">
                    Hi, <?= htmlspecialchars($_SESSION['ho_ten']) ?>
                </span>

                <!-- Lịch sử đơn hàng của tôi -->
                <a href="<?= $base_url ?>/orders/history.php">
                    Đơn hàng
                </a>

                <!-- Nút Đăng xuất riêng biệt -->
                <a href="<?= $base_url ?>/account/logout.php" class="logout-btn">
                    Đăng xuất
                </a>

            <!-- Nếu chưa đăng nhập -->
            <?php else: ?>
                
                <a href="<?= $base_url ?>/account/login.php">
                    Tài khoản
                </a>

            <?php endif; ?>

            <!-- Giỏ hàng -->
            <a href="<?= $base_url ?>/cart/index.php">
                Giỏ hàng
            </a>
        </div>
        
    </div>

</header>