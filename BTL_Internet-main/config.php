    <?php 

// KÍCH HOẠT SESSION: Bắt buộc phải có dòng này ở đầu file config để duy trì đăng nhập toàn website
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

    $host = 'localhost';
    $dbname = 'fashion_store';
    $username = 'root';
    $password = '';

    try { 
        $conn = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password
        );

        $conn->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

    } catch(PDOException $e) { 
        die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage()); 
    }
    // Đường dẫn gốc của website
    $base_url = '/fashion_store';
    ?>
    