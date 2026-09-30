<?php
require_once __DIR__.'/../config.php';$u=require_role(['khach_hang']);
$total=(int)query('SELECT COUNT(*) FROM don_hang WHERE id_nguoi_dung=?',[$u['id_nguoi_dung']])->fetchColumn();
$pages=max(1,(int)ceil($total/15));$page=max(1,min($pages,(int)input('page',$_GET,'1')));$offset=($page-1)*15;
$orders=query("SELECT * FROM don_hang WHERE id_nguoi_dung=? ORDER BY ngay_dat DESC,id_don_hang DESC LIMIT 15 OFFSET $offset",[$u['id_nguoi_dung']])->fetchAll();
$page_title='Đơn hàng của tôi';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="page-heading"><h1>Đơn hàng của tôi</h1><a class="underlined" href="<?=e(url('products/index.php'))?>">Tiếp tục mua sắm ↗</a></div>
<?php if(!$orders):?><div class="empty"><h2>Bạn chưa có đơn hàng nào</h2><a class="btn" href="<?=e(url('products/index.php'))?>">Khám phá sản phẩm</a></div>
<?php else:?><div class="table-wrap"><table><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Người nhận</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead><tbody>
<?php foreach($orders as $o):?><tr><td>#<?=$o['id_don_hang']?></td><td><?=date('d/m/Y H:i',strtotime($o['ngay_dat']))?></td><td><?=e($o['ho_ten_nhan'])?></td><td class="nowrap"><?=money($o['tong_tien'])?></td><td><?=status_badge($o['trang_thai'])?><small><?=e(PAYMENT_STATES[$o['trang_thai_thanh_toan']]??'')?></small></td><td><a class="btn btn-small btn-secondary" href="<?=e(url('orders/detail.php?id='.$o['id_don_hang']))?>">Chi tiết</a></td></tr><?php endforeach;?></tbody></table></div><?php pagination($page,$pages);endif;?></main>
<?php require __DIR__.'/../footer.php';?>
