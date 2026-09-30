<?php
require_once __DIR__.'/../config.php';$u=require_role(['khach_hang']);$items=cart_items((int)$u['id_nguoi_dung']);$canCheckout=true;
$page_title='Giỏ hàng';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="page-heading"><h1>Giỏ hàng của bạn</h1><a class="underlined" href="<?=e(url('products/index.php'))?>">Tiếp tục mua sắm ↗</a></div>
<?php if(!$items):?><div class="empty"><h2>Giỏ hàng đang trống</h2><p>Khám phá bộ sưu tập để chọn món đồ yêu thích.</p><a class="btn" href="<?=e(url('products/index.php'))?>">Khám phá sản phẩm</a></div>
<?php else:?><div class="table-wrap"><table><thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead><tbody>
<?php foreach($items as $item):$invalid=!$item['trang_thai']||!$item['danh_muc_active']||$item['so_luong']>$item['so_luong_ton']||$item['so_luong']>99;if($invalid)$canCheckout=false;?>
<tr><td><div class="table-product"><img class="thumb" src="<?=e(product_image($item['hinh_anh']))?>" alt=""><div><strong><?=e($item['ten_san_pham'])?></strong><small><?=e($item['ten_kich_thuoc'].' / '.$item['ten_mau_sac'])?></small><?php if($invalid):?><p class="stock-warning">Ngừng bán hoặc vượt tồn kho/giới hạn. Hãy cập nhật hoặc xóa.</p><?php endif;?></div></div></td><td class="nowrap"><?=money($item['gia'])?></td>
<td><form class="inline-form" method="post" action="<?=e(url('cart/update.php'))?>"><?=csrf_field()?><input type="hidden" name="id_chi_tiet" value="<?=$item['id_chi_tiet']?>"><input type="number" name="so_luong" min="1" max="<?=max(1,min(99,(int)$item['so_luong_ton']))?>" value="<?=$item['so_luong']?>" required aria-label="Số lượng <?=e($item['ten_san_pham'])?>"><button class="btn btn-small btn-secondary">Cập nhật</button></form><small>Còn <?=$item['so_luong_ton']?> trong kho</small></td><td class="nowrap"><?=money(decimal(cents($item['gia'])*(int)$item['so_luong']))?></td>
<td><form method="post" action="<?=e(url('cart/delete.php'))?>" data-confirm="Xóa sản phẩm khỏi giỏ?"><?=csrf_field()?><input type="hidden" name="id_chi_tiet" value="<?=$item['id_chi_tiet']?>"><button class="text-button">Xóa</button></form></td></tr>
<?php endforeach;?></tbody></table></div>
<div class="cart-bottom"><form method="post" action="<?=e(url('cart/delete.php'))?>" data-confirm="Xóa toàn bộ giỏ hàng?"><?=csrf_field()?><input type="hidden" name="action" value="clear"><button class="text-button">Xóa toàn bộ giỏ hàng</button></form>
<aside class="panel summary"><h2>Tóm tắt giỏ hàng</h2><p>Phí vận chuyển: Miễn phí</p><div class="summary-total"><span>Tổng tiền</span><span><?=money(decimal(cart_total($items)))?></span></div>
<?php if($canCheckout):?><a class="btn btn-wide" href="<?=e(url('orders/checkout.php'))?>">Tiến hành thanh toán ↗</a><?php else:?><p class="alert alert-error">Vui lòng xử lý các sản phẩm không hợp lệ trước khi thanh toán.</p><?php endif;?></aside></div>
<?php endif;?></main><?php require __DIR__.'/../footer.php';?>
