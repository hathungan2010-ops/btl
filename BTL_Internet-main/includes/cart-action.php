<?php
require_once __DIR__.'/../config.php';$u=require_role(['khach_hang']);require_post();
try{
    $conn->beginTransaction();lock_customer((int)$u['id_nguoi_dung']);
    if(($cartAction??'')==='add'){
        $id=integer(input('id_bien_the'));$quantity=integer(input('so_luong'),1,99);$v=available_variant($id);
        query('INSERT INTO gio_hang (id_nguoi_dung) VALUES (?) ON DUPLICATE KEY UPDATE id_nguoi_dung=VALUES(id_nguoi_dung)',[$u['id_nguoi_dung']]);
        $cartId=(int)query('SELECT id_gio_hang FROM gio_hang WHERE id_nguoi_dung=?',[$u['id_nguoi_dung']])->fetchColumn();
        $line=query('SELECT * FROM chi_tiet_gio_hang WHERE id_gio_hang=? AND id_bien_the=?',[$cartId,$id])->fetch();
        $quantity+=(int)($line['so_luong']??0);
        if($quantity>99||$quantity>(int)$v['so_luong_ton'])throw new InvalidArgumentException('Tổng số lượng trong giỏ vượt tồn kho hoặc giới hạn 99 sản phẩm mỗi phân loại.');
        if($line)query('UPDATE chi_tiet_gio_hang SET so_luong=? WHERE id_chi_tiet=?',[$quantity,$line['id_chi_tiet']]);
        else query('INSERT INTO chi_tiet_gio_hang (id_gio_hang,id_bien_the,so_luong) VALUES (?,?,?)',[$cartId,$id,$quantity]);
    }elseif(($cartAction??'')==='update'){
        $id=integer(input('id_chi_tiet'));$quantity=integer(input('so_luong'),1,99);
        $line=query('SELECT ct.* FROM chi_tiet_gio_hang ct JOIN gio_hang gh ON gh.id_gio_hang=ct.id_gio_hang WHERE ct.id_chi_tiet=? AND gh.id_nguoi_dung=? FOR UPDATE',[$id,$u['id_nguoi_dung']])->fetch();
        if(!$line)throw new InvalidArgumentException('Không tìm thấy sản phẩm trong giỏ của bạn.');
        $v=available_variant((int)$line['id_bien_the']);
        if($quantity>(int)$v['so_luong_ton'])throw new InvalidArgumentException('Số lượng vượt tồn kho hiện tại.');
        query('UPDATE chi_tiet_gio_hang SET so_luong=? WHERE id_chi_tiet=?',[$quantity,$id]);
    }elseif(($cartAction??'')==='delete'){
        if(input('action')==='clear')query('DELETE ct FROM chi_tiet_gio_hang ct JOIN gio_hang gh ON gh.id_gio_hang=ct.id_gio_hang WHERE gh.id_nguoi_dung=?',[$u['id_nguoi_dung']]);
        else{
            $id=integer(input('id_chi_tiet'));
            if(!query('DELETE ct FROM chi_tiet_gio_hang ct JOIN gio_hang gh ON gh.id_gio_hang=ct.id_gio_hang WHERE ct.id_chi_tiet=? AND gh.id_nguoi_dung=?',[$id,$u['id_nguoi_dung']])->rowCount())throw new InvalidArgumentException('Không tìm thấy sản phẩm trong giỏ của bạn.');
        }
    }else throw new InvalidArgumentException('Thao tác không hợp lệ.');
    $conn->commit();flash('Đã cập nhật giỏ hàng.');
}catch(Throwable $ex){if($conn->inTransaction())$conn->rollBack();flash(error_message($ex),'error');}
redirect('cart/index.php');
