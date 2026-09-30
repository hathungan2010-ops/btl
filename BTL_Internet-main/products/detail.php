<?php
require_once __DIR__.'/../config.php';$id=(int)input('id',$_GET);$staffView=has_role(['nhan_vien','admin']);
$product=query('SELECT sp.*,dm.ten_danh_muc FROM san_pham sp JOIN danh_muc dm ON dm.id_danh_muc=sp.id_danh_muc WHERE sp.id_san_pham=?'.($staffView?'':' AND sp.trang_thai=1 AND dm.trang_thai=1'),[$id])->fetch();
if(!$product)abort_page(404,'Không tìm thấy sản phẩm hoặc sản phẩm đã ngừng bán.');
$variants=query('SELECT bt.*,kt.ten_kich_thuoc,ms.ten_mau_sac FROM bien_the_san_pham bt JOIN kich_thuoc kt ON kt.id_kich_thuoc=bt.id_kich_thuoc JOIN mau_sac ms ON ms.id_mau_sac=bt.id_mau_sac WHERE bt.id_san_pham=? ORDER BY bt.id_kich_thuoc,bt.id_mau_sac',[$id])->fetchAll();
$inStock=array_filter($variants,static function($v){return $v['so_luong_ton']>0;});$page_title=$product['ten_san_pham'];require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><p class="breadcrumb"><a href="<?=e(url('products/index.php'))?>">Bộ sưu tập</a> / <?=e($product['ten_san_pham'])?></p>
<div class="detail-layout"><div class="detail-image"><img src="<?=e(product_image($product['hinh_anh']))?>" alt="<?=e($product['ten_san_pham'])?>"></div><div class="detail-info"><p class="eyebrow"><?=e($product['ten_danh_muc'])?></p><h1><?=e($product['ten_san_pham'])?></h1><p class="detail-price" data-price><?=money($product['gia_co_ban'])?></p><p class="detail-description"><?=e($product['mo_ta'])?></p>
<?php if($staffView):?><a class="btn" href="<?=e(url('products/edit.php?id='.$id))?>">Chỉnh sửa sản phẩm</a>
<?php elseif($inStock):?><form class="stack" method="post" action="<?=e(url('cart/add.php'))?>"><?=csrf_field()?>
<div><label for="variant">Chọn size và màu</label><select id="variant" name="id_bien_the" required data-variant-select><option value="">Chọn phân loại phù hợp</option><?php foreach($variants as $v):?><option value="<?=$v['id_bien_the']?>" data-price="<?=e($v['gia'])?>" data-stock="<?=$v['so_luong_ton']?>" <?=$v['so_luong_ton']<1?'disabled':''?>><?=e($v['ten_kich_thuoc'].' / '.$v['ten_mau_sac'])?> — <?=money($v['gia'])?><?=$v['so_luong_ton']<1?' (Hết hàng)':''?></option><?php endforeach;?></select><p class="help" data-stock aria-live="polite"></p></div>
<div class="quantity"><label for="quantity">Số lượng</label><input id="quantity" name="so_luong" type="number" min="1" max="99" required value="1" data-quantity></div><button class="btn btn-wide">Thêm vào giỏ hàng ↗</button></form>
<?php else:?><div class="alert alert-info">Sản phẩm hiện đã hết hàng.</div><?php endif;?></div></div></main>
<?php require __DIR__.'/../footer.php';?>
