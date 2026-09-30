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
    vai_tro VARCHAR(20) DEFAULT 'customer',
    trang_thai TINYINT DEFAULT 1,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. DANH MUC
CREATE TABLE danh_muc (
    id_danh_muc INT AUTO_INCREMENT PRIMARY KEY,
    ten_danh_muc VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    trang_thai TINYINT DEFAULT 1
);

-- 3. SAN PHAM
CREATE TABLE san_pham (
    id_san_pham INT AUTO_INCREMENT PRIMARY KEY,
    id_danh_muc INT NOT NULL,
    ten_san_pham VARCHAR(150) NOT NULL,
    mo_ta TEXT,
    gia_co_ban DECIMAL(12,2) NOT NULL,
    hinh_anh VARCHAR(255),
    trang_thai TINYINT DEFAULT 1,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_danh_muc)
        REFERENCES danh_muc(id_danh_muc)
);

-- 4. KICH THUOC
CREATE TABLE kich_thuoc (
    id_kich_thuoc INT AUTO_INCREMENT PRIMARY KEY,
    ten_kich_thuoc VARCHAR(20) NOT NULL
);

-- 5. MAU SAC
CREATE TABLE mau_sac (
    id_mau_sac INT AUTO_INCREMENT PRIMARY KEY,
    ten_mau_sac VARCHAR(50) NOT NULL,
    ma_mau VARCHAR(20)
);

-- 6. BIEN THE SAN PHAM
CREATE TABLE bien_the_san_pham (
    id_bien_the INT AUTO_INCREMENT PRIMARY KEY,
    id_san_pham INT NOT NULL,
    id_kich_thuoc INT NOT NULL,
    id_mau_sac INT NOT NULL,
    gia DECIMAL(12,2) NOT NULL,
    so_luong_ton INT DEFAULT 0,

    FOREIGN KEY (id_san_pham)
        REFERENCES san_pham(id_san_pham)
        ON DELETE CASCADE,

    FOREIGN KEY (id_kich_thuoc)
        REFERENCES kich_thuoc(id_kich_thuoc),

    FOREIGN KEY (id_mau_sac)
        REFERENCES mau_sac(id_mau_sac)
);

-- 7. GIO HANG
CREATE TABLE gio_hang (
    id_gio_hang INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_nguoi_dung)
        REFERENCES nguoi_dung(id_nguoi_dung)
        ON DELETE CASCADE
);

-- 8. CHI TIET GIO HANG
CREATE TABLE chi_tiet_gio_hang (
    id_chi_tiet INT AUTO_INCREMENT PRIMARY KEY,
    id_gio_hang INT NOT NULL,
    id_bien_the INT NOT NULL,
    so_luong INT NOT NULL DEFAULT 1,

    FOREIGN KEY (id_gio_hang)
        REFERENCES gio_hang(id_gio_hang)
        ON DELETE CASCADE,

    FOREIGN KEY (id_bien_the)
        REFERENCES bien_the_san_pham(id_bien_the)
);

-- 9. DON HANG
CREATE TABLE don_hang (
    id_don_hang INT AUTO_INCREMENT PRIMARY KEY,
    id_nguoi_dung INT NOT NULL,
    ho_ten_nhan VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20) NOT NULL,
    dia_chi_giao_hang VARCHAR(255) NOT NULL,
    tong_tien DECIMAL(12,2) NOT NULL,
    phuong_thuc_thanh_toan VARCHAR(50) DEFAULT 'COD',
    trang_thai VARCHAR(30) DEFAULT 'Cho xu ly',
    ngay_dat DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_nguoi_dung)
        REFERENCES nguoi_dung(id_nguoi_dung)
);

-- 10. CHI TIET DON HANG
CREATE TABLE chi_tiet_don_hang (
    id_chi_tiet INT AUTO_INCREMENT PRIMARY KEY,
    id_don_hang INT NOT NULL,
    id_bien_the INT NOT NULL,
    so_luong INT NOT NULL,
    don_gia DECIMAL(12,2) NOT NULL,
    thanh_tien DECIMAL(12,2) NOT NULL,

    FOREIGN KEY (id_don_hang)
        REFERENCES don_hang(id_don_hang)
        ON DELETE CASCADE,

    FOREIGN KEY (id_bien_the)
        REFERENCES bien_the_san_pham(id_bien_the)
);

-- =========================
-- DU LIEU MAU
-- =========================

-- DANH MUC
INSERT INTO danh_muc (ten_danh_muc, mo_ta)
VALUES
('Ao', 'Cac loai ao'),
('Quan', 'Cac loai quan');

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
('Den', '#000000'),
('Trang', '#FFFFFF'),
('Xam', '#808080'),
('Xanh', '#0000FF'),
('Be', '#F5F5DC');

-- SAN PHAM
INSERT INTO san_pham
(id_danh_muc, ten_san_pham, mo_ta, gia_co_ban, hinh_anh)
VALUES
(1, 'Ao Thun Basic',
 'Ao thun co ban, phong cach don gian',
 199000, 'ao-thun-basic.jpg'),

(1, 'Ao Polo Basic',
 'Ao polo nam phong cach hien dai',
 299000, 'ao-polo-basic.jpg'),

(1, 'Ao So Mi Cong So',
 'Ao so mi phu hop di hoc va di lam',
 349000, 'ao-so-mi.jpg'),

(1, 'Hoodie Basic',
 'Hoodie phong cach tre trung',
 399000, 'hoodie-basic.jpg'),

(1, 'Ao Khoac Jacket',
 'Ao khoac jacket phong cach',
 499000, 'jacket.jpg'),

(2, 'Quan Jeans',
 'Quan jeans nam co ban',
 499000, 'quan-jeans.jpg'),

(2, 'Quan Kaki',
 'Quan kaki nam lich su',
 399000, 'quan-kaki.jpg'),

(2, 'Quan Short',
 'Quan short nam mac hang ngay',
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
(4, 3, 3, 399000, 399),

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