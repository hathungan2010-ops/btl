<?php
require_once __DIR__.'/config.php';
$products=query('SELECT sp.*,dm.ten_danh_muc FROM san_pham sp JOIN danh_muc dm ON dm.id_danh_muc=sp.id_danh_muc WHERE sp.trang_thai=1 AND dm.trang_thai=1 ORDER BY sp.ngay_tao DESC,sp.id_san_pham DESC LIMIT 8')->fetchAll();
$categories=query('SELECT * FROM danh_muc WHERE trang_thai=1 ORDER BY id_danh_muc')->fetchAll();
require __DIR__.'/header.php';
?>
<main id="main"><section class="hero"><h1 class="sr-only">Fashion Store — Thời trang là cách bạn kể câu chuyện của mình</h1><a class="hero-link" href="<?=e(url('products/index.php'))?>" aria-label="Khám phá bộ sưu tập"></a></section>
<div class="container"><section class="section"><div class="section-heading"><div><p class="eyebrow">THE LATEST EDIT</p><h2>Sản phẩm mới nhất</h2></div><a class="underlined" href="<?=e(url('products/index.php'))?>">Xem tất cả ↗</a></div>
<div class="categories"><?php foreach($categories as $c):?><a class="category" href="<?=e(url('products/index.php?category='.$c['id_danh_muc']))?>"><?=e($c['ten_danh_muc'])?></a><?php endforeach;?></div>
<?php if($products):?><div class="products"><?php foreach($products as $product)require __DIR__.'/includes/product-card.php';?></div><?php else:?><div class="empty">Bộ sưu tập đang được cập nhật.</div><?php endif;?></section>
<section class="brand-story"><p class="eyebrow">EVERYDAY, YOUR WAY</p><h2>Đơn giản trong lựa chọn.<br>Tự tin trong từng ngày.</h2><p>Khám phá những món đồ dễ phối cho tủ đồ của bạn.</p><a class="btn" href="<?=e(url('products/index.php'))?>">Tìm phong cách của bạn ↗</a></section></div></main>
<?php require __DIR__.'/footer.php';?>
