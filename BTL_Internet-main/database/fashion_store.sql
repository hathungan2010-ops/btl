-- Cài mới trên CSDL trống. CSDL cũ: php database/migrate.php.
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS fashion_store
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fashion_store;

-- 1. NGUOI DUNG
CREATE TABLE nguoi_dung (
    id_nguoi_dung INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(20),
    dia_chi VARCHAR(255),
    vai_tro ENUM('khach_hang','nhan_vien','admin') NOT NULL DEFAULT 'khach_hang',
    trang_thai TINYINT NOT NULL DEFAULT 1 CHECK (trang_thai IN (0,1)),
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. DANH MUC
CREATE TABLE danh_muc (
    id_danh_muc INT AUTO_INCREMENT PRIMARY KEY,
    ten_danh_muc VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    trang_thai TINYINT NOT NULL DEFAULT 1 CHECK (trang_thai IN (0,1))
) ENGINE=InnoDB;

-- 3. SAN PHAM
CREATE TABLE san_pham (
    id_san_pham INT AUTO_INCREMENT PRIMARY KEY,
    id_danh_muc INT NOT NULL,
    ten_san_pham VARCHAR(150) NOT NULL,
    mo_ta TEXT,
    gia_co_ban DECIMAL(12,2) NOT NULL CHECK (gia_co_ban > 0),
    version INT NOT NULL DEFAULT 1,
    hinh_anh VARCHAR(255),
    trang_thai TINYINT NOT NULL DEFAULT 1 CHECK (trang_thai IN (0,1)),
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_danh_muc)
        REFERENCES danh_muc(id_danh_muc)
) ENGINE=InnoDB;

-- 4. KICH THUOC
CREATE TABLE kich_thuoc (
    id_kich_thuoc INT AUTO_INCREMENT PRIMARY KEY,
    ten_kich_thuoc VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

-- 5. MAU SAC
CREATE TABLE mau_sac (
    id_mau_sac INT AUTO_INCREMENT PRIMARY KEY,
    ten_mau_sac VARCHAR(50) NOT NULL,
    ma_mau VARCHAR(20)
) ENGINE=InnoDB;

-- 6. BIEN THE SAN PHAM
CREATE TABLE bien_the_san_pham (
    id_bien_the INT AUTO_INCREMENT PRIMARY KEY,
    id_san_pham INT NOT NULL,
    id_kich_thuoc INT NOT NULL,
    id_mau_sac INT NOT NULL,
    gia DECIMAL(12,2) NOT NULL CHECK (gia > 0),
    so_luong_ton INT NOT NULL DEFAULT 0 CHECK (so_luong_ton >= 0),
    UNIQUE KEY uq_variant (id_san_pham,id_kich_thuoc,id_mau_sac),

    FOREIGN KEY (id_san_pham)
        REFERENCES san_pham(id_san_pham)
        ON DELETE CASCADE,

    FOREIGN KEY (id_kich_thuoc)
        REFERENCES kich_thuoc(id_kich_thuoc),

    FOREIGN KEY (id_mau_sac)
        REFERENCES mau_sac(id_mau_sac)
) ENGINE=InnoDB;

-- 7. GIO HANG
CREATE TABLE gio_hang (
    id_gio_hang INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    UNIQUE KEY uq_user_cart (id_nguoi_dung),
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_nguoi_dung)
        REFERENCES nguoi_dung(id_nguoi_dung)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. CHI TIET GIO HANG
CREATE TABLE chi_tiet_gio_hang (
    id_chi_tiet INT AUTO_INCREMENT PRIMARY KEY,
    id_gio_hang INT NOT NULL,
    id_bien_the INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 1 CHECK (so_luong > 0),
    UNIQUE KEY uq_cart_line (id_gio_hang,id_bien_the),

    FOREIGN KEY (id_gio_hang)
        REFERENCES gio_hang(id_gio_hang)
        ON DELETE CASCADE,

    FOREIGN KEY (id_bien_the)
        REFERENCES bien_the_san_pham(id_bien_the)
) ENGINE=InnoDB;

-- 9. DON HANG
CREATE TABLE don_hang (
    id_don_hang INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    ho_ten_nhan VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20) NOT NULL,
    dia_chi_giao_hang VARCHAR(255) NOT NULL,
    tong_tien DECIMAL(12,2) NOT NULL,
    phuong_thuc_thanh_toan VARCHAR(50) DEFAULT 'COD',
    trang_thai VARCHAR(30) NOT NULL DEFAULT 'cho_xu_ly' CHECK (trang_thai IN ('cho_xu_ly','da_xac_nhan','dang_giao','hoan_thanh','da_huy')),
    trang_thai_thanh_toan VARCHAR(30) NOT NULL DEFAULT 'chua_thanh_toan' CHECK (trang_thai_thanh_toan IN ('chua_thanh_toan','da_thanh_toan','can_hoan_tien','da_hoan_tien')),
    checkout_token VARCHAR(64) NULL,
    ngay_hoan_thanh DATETIME NULL,
    UNIQUE KEY uq_checkout_token (checkout_token),
    INDEX idx_order_date (ngay_dat),
    INDEX idx_order_completed (ngay_hoan_thanh),
    ngay_dat DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_nguoi_dung)
        REFERENCES nguoi_dung(id_nguoi_dung)
) ENGINE=InnoDB;

-- 10. CHI TIET DON HANG
CREATE TABLE chi_tiet_don_hang (
    id_chi_tiet INT AUTO_INCREMENT PRIMARY KEY,
    id_don_hang INT NOT NULL,
    id_bien_the INT NOT NULL,
    so_luong INT NOT NULL CHECK (so_luong > 0),
    don_gia DECIMAL(12,2) NOT NULL,
    thanh_tien DECIMAL(12,2) NOT NULL,
    ten_san_pham VARCHAR(150) NOT NULL,
    ten_kich_thuoc VARCHAR(20) NOT NULL,
    ten_mau_sac VARCHAR(50) NOT NULL,
    hinh_anh VARCHAR(255),
    CHECK (thanh_tien = so_luong * don_gia),

    FOREIGN KEY (id_don_hang)
        REFERENCES don_hang(id_don_hang)
        ON DELETE CASCADE,

    FOREIGN KEY (id_bien_the)
        REFERENCES bien_the_san_pham(id_bien_the)
) ENGINE=InnoDB;

CREATE TABLE dang_nhap_thu (khoa CHAR(64) PRIMARY KEY,so_lan INT NOT NULL DEFAULT 0,lan_cuoi DATETIME NOT NULL) ENGINE=InnoDB;

-- =========================
-- DU LIEU MAU
-- =========================

-- DANH MUC
INSERT INTO danh_muc (ten_danh_muc, mo_ta)
VALUES
('Áo', 'Các loại áo'),
('Quần', 'Các loại quần');

-- KICH THUOC
INSERT INTO kich_thuoc (ten_kich_thuoc)
VALUES
('S'),
('M'),
('L'),
('XL'),
('XXL');

-- MAU SAC
INSERT INTO mau_sac (ten_mau_sac, ma_mau)
VALUES
('Đen', '#000000'),
('Trắng', '#FFFFFF'),
('Xám', '#808080'),
('Xanh', '#0000FF'),
('Be', '#F5F5DC');

-- SAN PHAM
INSERT INTO san_pham
(id_danh_muc, ten_san_pham, mo_ta, gia_co_ban, hinh_anh)
VALUES
(1, 'Áo Thun Basic',
 'Áo thun cơ bản, phong cách đơn giản',
 199000, 'ao-thun-basic.jpg'),

(1, 'Áo Polo Basic',
 'Áo polo nam phong cách hiện đại',
 299000, 'ao-polo-basic.jpg'),

(1, 'Áo Sơ Mi Công Sở',
 'Áo sơ mi phù hợp đi học và đi làm',
 349000, 'ao-so-mi.jpg'),

(1, 'Hoodie Basic',
 'Hoodie phong cách trẻ trung',
 399000, 'hoodie-basic.jpg'),

(1, 'Áo Khoác Jacket',
 'Áo khoác jacket phong cách',
 499000, 'jacket.jpg'),

(2, 'Quần Jeans',
 'Quần jeans nam cơ bản',
 499000, 'quan-jeans.jpg'),

(2, 'Quần Kaki',
 'Quần kaki nam lịch sự',
 399000, 'quan-kaki.jpg'),

(2, 'Quần Short',
 'Quần short nam mặc hằng ngày',
 249000, 'quan-short.jpg');

-- BIEN THE SAN PHAM
INSERT INTO bien_the_san_pham
(id_san_pham, id_kich_thuoc, id_mau_sac, gia, so_luong_ton)
VALUES

-- Ao Thun Basic
(1, 1, 1, 199000, 20),
(1, 2, 1, 199000, 30),
(1, 3, 1, 199000, 25),
(1, 2, 2, 199000, 20),
(1, 3, 2, 199000, 15),

-- Ao Polo Basic
(2, 1, 1, 299000, 15),
(2, 2, 1, 299000, 25),
(2, 3, 1, 299000, 20),
(2, 2, 2, 299000, 20),
(2, 3, 2, 299000, 15),

-- Ao So Mi
(3, 2, 2, 349000, 20),
(3, 3, 2, 349000, 20),
(3, 4, 2, 349000, 15),

-- Hoodie
(4, 2, 1, 399000, 15),
(4, 3, 1, 399000, 20),
(4, 4, 1, 399000, 15),
(4, 3, 3, 399000, 20),

-- Jacket
(5, 2, 1, 499000, 10),
(5, 3, 1, 499000, 15),
(5, 4, 1, 499000, 10),

-- Quan Jeans
(6, 2, 1, 499000, 15),
(6, 3, 1, 499000, 20),
(6, 4, 1, 499000, 15),

-- Quan Kaki
(7, 2, 3, 399000, 15),
(7, 3, 3, 399000, 20),
(7, 4, 3, 399000, 15),

-- Quan Short
(8, 1, 1, 249000, 15),
(8, 2, 1, 249000, 20),
(8, 3, 1, 249000, 15);
