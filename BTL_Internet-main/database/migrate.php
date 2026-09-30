<?php
// Sao lưu CSDL và dừng website trước khi nâng cấp. Chỉ chạy bằng CLI.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config.php';
function has_column(string $table,string $column):bool{return (bool)query('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$column])->fetchColumn();}
function add_column(string $table,string $column,string $definition):void{if(!has_column($table,$column))query("ALTER TABLE $table ADD COLUMN $column $definition");}
function add_index(string $table,string $index,string $definition):void{if(!query('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?',[$table,$index])->fetchColumn())query("ALTER TABLE $table ADD $definition");}
try{
    if(!query("SELECT GET_LOCK(CONCAT(DATABASE(),':fashion_migration'),10)")->fetchColumn())throw new InvalidArgumentException('Một tiến trình nâng cấp khác đang chạy.');
    if(query('SELECT 1 FROM bien_the_san_pham GROUP BY id_san_pham,id_kich_thuoc,id_mau_sac HAVING COUNT(*)>1 LIMIT 1')->fetchColumn())throw new InvalidArgumentException('Có biến thể trùng size/màu. Hãy xử lý trước khi nâng cấp.');
    $map=['Cho xu ly'=>'cho_xu_ly','Chờ xử lý'=>'cho_xu_ly','Chờ xác nhận'=>'cho_xu_ly','Da xac nhan'=>'da_xac_nhan','Đã xác nhận'=>'da_xac_nhan','Đang xử lý'=>'da_xac_nhan','Dang xu ly'=>'da_xac_nhan','Dang giao'=>'dang_giao','Đang giao'=>'dang_giao','Đang giao hàng'=>'dang_giao','Hoan thanh'=>'hoan_thanh','Hoàn thành'=>'hoan_thanh','Đã giao'=>'hoan_thanh','Đã giao hàng'=>'hoan_thanh','Da huy'=>'da_huy','Đã hủy'=>'da_huy','Đã huỷ'=>'da_huy','Hủy'=>'da_huy','Huy'=>'da_huy'];
    foreach(query('SELECT DISTINCT trang_thai FROM don_hang')->fetchAll(PDO::FETCH_COLUMN) as $s)if(!isset(ORDER_STATES[$s])&&!isset($map[$s]))throw new InvalidArgumentException('Trạng thái chưa được nhận diện: '.$s);
    foreach(query('SELECT DISTINCT vai_tro FROM nguoi_dung')->fetchAll(PDO::FETCH_COLUMN) as $r)if(!isset(ROLES[$r])&&!in_array($r,['customer','staff'],true))throw new InvalidArgumentException('Vai trò chưa được nhận diện: '.(string)$r);
    add_column('san_pham','version','INT NOT NULL DEFAULT 1');
    add_column('don_hang','trang_thai_thanh_toan',"VARCHAR(30) NOT NULL DEFAULT 'chua_thanh_toan'");
    add_column('don_hang','checkout_token','VARCHAR(64) NULL');add_column('don_hang','ngay_hoan_thanh','DATETIME NULL');
    foreach(['ten_san_pham'=>'VARCHAR(150) NULL','ten_kich_thuoc'=>'VARCHAR(20) NULL','ten_mau_sac'=>'VARCHAR(50) NULL','hinh_anh'=>'VARCHAR(255) NULL'] as $col=>$def)add_column('chi_tiet_don_hang',$col,$def);
    query('CREATE TABLE IF NOT EXISTS dang_nhap_thu (khoa CHAR(64) PRIMARY KEY,so_lan INT NOT NULL DEFAULT 0,lan_cuoi DATETIME NOT NULL) ENGINE=InnoDB');
    $conn->beginTransaction();
    query("UPDATE nguoi_dung SET vai_tro='khach_hang' WHERE vai_tro='customer'");query("UPDATE nguoi_dung SET vai_tro='nhan_vien' WHERE vai_tro='staff'");
    foreach($map as $old=>$new)query('UPDATE don_hang SET trang_thai=? WHERE trang_thai=?',[$new,$old]);
    query('UPDATE chi_tiet_don_hang ct JOIN bien_the_san_pham bt ON bt.id_bien_the=ct.id_bien_the JOIN san_pham sp ON sp.id_san_pham=bt.id_san_pham JOIN kich_thuoc kt ON kt.id_kich_thuoc=bt.id_kich_thuoc JOIN mau_sac ms ON ms.id_mau_sac=bt.id_mau_sac SET ct.ten_san_pham=sp.ten_san_pham,ct.ten_kich_thuoc=kt.ten_kich_thuoc,ct.ten_mau_sac=ms.ten_mau_sac,ct.hinh_anh=sp.hinh_anh WHERE ct.ten_san_pham IS NULL');
    query('UPDATE chi_tiet_don_hang SET thanh_tien=don_gia*so_luong WHERE thanh_tien<>don_gia*so_luong');
    foreach(query('SELECT id_nguoi_dung,MIN(id_gio_hang) AS keep_id FROM gio_hang GROUP BY id_nguoi_dung HAVING COUNT(*)>1')->fetchAll() as $r){
        foreach(query('SELECT id_gio_hang FROM gio_hang WHERE id_nguoi_dung=? AND id_gio_hang<>?',[$r['id_nguoi_dung'],$r['keep_id']])->fetchAll(PDO::FETCH_COLUMN) as $cart){
            query('UPDATE chi_tiet_gio_hang SET id_gio_hang=? WHERE id_gio_hang=?',[$r['keep_id'],$cart]);query('DELETE FROM gio_hang WHERE id_gio_hang=?',[$cart]);
        }
    }
    foreach(query('SELECT id_gio_hang,id_bien_the,MIN(id_chi_tiet) AS keep_id,SUM(so_luong) AS quantity FROM chi_tiet_gio_hang GROUP BY id_gio_hang,id_bien_the HAVING COUNT(*)>1')->fetchAll() as $r){
        query('UPDATE chi_tiet_gio_hang SET so_luong=? WHERE id_chi_tiet=?',[$r['quantity'],$r['keep_id']]);
        query('DELETE FROM chi_tiet_gio_hang WHERE id_gio_hang=? AND id_bien_the=? AND id_chi_tiet<>?',[$r['id_gio_hang'],$r['id_bien_the'],$r['keep_id']]);
    }
    $conn->commit();
    query("ALTER TABLE nguoi_dung MODIFY vai_tro ENUM('khach_hang','nhan_vien','admin') NOT NULL DEFAULT 'khach_hang'");
    query("ALTER TABLE don_hang MODIFY trang_thai VARCHAR(30) NOT NULL DEFAULT 'cho_xu_ly'");
    add_index('bien_the_san_pham','uq_variant','UNIQUE KEY uq_variant (id_san_pham,id_kich_thuoc,id_mau_sac)');
    add_index('gio_hang','uq_user_cart','UNIQUE KEY uq_user_cart (id_nguoi_dung)');
    add_index('chi_tiet_gio_hang','uq_cart_line','UNIQUE KEY uq_cart_line (id_gio_hang,id_bien_the)');
    add_index('don_hang','uq_checkout_token','UNIQUE KEY uq_checkout_token (checkout_token)');
    add_index('don_hang','idx_order_date','INDEX idx_order_date (ngay_dat)');
    add_index('don_hang','idx_order_completed','INDEX idx_order_completed (ngay_hoan_thanh)');
    echo "Nâng cấp hoàn tất. Có thể chạy lại an toàn.\nĐơn cũ chưa có bằng chứng thanh toán/ngày hoàn thành cần đối soát thủ công trước khi tính doanh thu.\n";
}catch(Throwable $ex){if($conn->inTransaction())$conn->rollBack();fwrite(STDERR,error_message($ex)."\nDDL có thể đã áp dụng một phần; sửa lỗi và chạy lại, không nhập đè SQL cài mới.\n");exit(1);}
finally{query("SELECT RELEASE_LOCK(CONCAT(DATABASE(),':fashion_migration'))");}
