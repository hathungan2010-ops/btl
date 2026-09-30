<?php
require_once __DIR__.'/../config.php';$u=require_role(['khach_hang','nhan_vien','admin']);$id=(int)input('id',$_GET);$isStaff=has_role(['nhan_vien','admin']);
$params=[$id];$where='';if(!$isStaff){$where=' AND id_nguoi_dung=?';$params[]=$u['id_nguoi_dung'];}
$o=query('SELECT * FROM don_hang WHERE id_don_hang=?'.$where,$params)->fetch();
if(!$o)abort_page(404,'Không tìm thấy đơn hàng của bạn.');
$items=query('SELECT * FROM chi_tiet_don_hang WHERE id_don_hang=? ORDER BY id_chi_tiet',[$id])->fetchAll();
$page_title='Đơn hàng #'.$id;require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="page-heading"><div><h1>Đơn hàng #<?=$id?></h1><p class="muted">Đặt lúc <?=date('H:i · d/m/Y',strtotime($o['ngay_dat']))?></p></div><a class="underlined" href="<?=e(url($isStaff?'orders/manage.php':'orders/history.php'))?>">← Danh sách đơn hàng</a></div>
<div class="checkout-grid"><section class="panel"><h2>Sản phẩm đã đặt</h2>
<?php foreach($items as $item):?><div class="summary-line"><div class="table-product"><img class="thumb" src="<?=e(product_image($item['hinh_anh']))?>" alt=""><div><strong><?=e($item['ten_san_pham'])?></strong><small><?=e($item['ten_kich_thuoc'].' / '.$item['ten_mau_sac'])?></small><small><?=money($item['don_gia'])?> × <?=$item['so_luong']?></small></div></div><strong class="nowrap"><?=money($item['thanh_tien'])?></strong></div><?php endforeach;?>
<div class="summary-total"><span>Tổng tiền</span><span><?=money($o['tong_tien'])?></span></div></section>
<aside class="panel summary stack"><h2>Thông tin giao hàng</h2><p><?=status_badge($o['trang_thai'])?></p><dl class="meta-list"><dt>Người nhận</dt><dd><?=e($o['ho_ten_nhan'])?></dd><dt>Điện thoại</dt><dd><?=e($o['so_dien_thoai'])?></dd><dt>Địa chỉ</dt><dd><?=e($o['dia_chi_giao_hang'])?></dd><dt>Thanh toán</dt><dd><?=e($o['phuong_thuc_thanh_toan'])?></dd><dt>Tình trạng</dt><dd><?=e(PAYMENT_STATES[$o['trang_thai_thanh_toan']]??'')?></dd></dl>
<?php if($o['phuong_thuc_thanh_toan']==='Chuyển khoản'&&$o['trang_thai']!=='da_huy'&&$o['trang_thai_thanh_toan']==='chua_thanh_toan'):?><div class="alert alert-info"><strong>Thông tin chuyển khoản</strong>
<?php if($bank_name&&$bank_account&&$bank_owner):?><p><?=e($bank_name)?><br>Số tài khoản: <?=e($bank_account)?><br>Chủ tài khoản: <?=e($bank_owner)?><br>Nội dung: <strong>FASHION <?=$id?></strong><br>Số tiền: <?=money($o['tong_tien'])?></p><small>Cửa hàng xác nhận sau khi đối soát.</small><?php else:?><p>Liên hệ <?=e($store_email)?> để nhận thông tin chuyển khoản.</p><?php endif;?></div><?php endif;?>
<?php if($o['trang_thai_thanh_toan']==='can_hoan_tien'):?><div class="alert alert-info">Đơn đã hủy; khoản tiền đã nhận đang chờ hoàn lại.</div><?php endif;?>
<?php if($isStaff):?><a class="btn" href="<?=e(url('orders/manage.php?search='.$id))?>">Quản lý đơn này</a><?php endif;?></aside></div></main>
<?php require __DIR__.'/../footer.php';?>
