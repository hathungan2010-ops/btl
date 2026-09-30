<?php
require_once __DIR__.'/../config.php';
$manage=input('manage',$_GET)==='1';
if($manage)require_role(['nhan_vien','admin']);
$categories=query('SELECT * FROM danh_muc WHERE trang_thai=1 ORDER BY ten_danh_muc')->fetchAll();
$search=mb_substr(input('search',$_GET),0,150);$category=(int)input('category',$_GET);$state=input('state',$_GET);
$where=$manage?['1=1']:['sp.trang_thai=1','dm.trang_thai=1'];$params=[];
if($search!==''){$where[]='sp.ten_san_pham LIKE ?';$params[]='%'.$search.'%';}
if($category>0){$where[]='sp.id_danh_muc=?';$params[]=$category;}
if($manage&&in_array($state,['0','1'],true)){$where[]='sp.trang_thai=?';$params[]=$state;}
$sort=input('sort',$_GET,'newest');
$sortSql=['newest'=>'sp.ngay_tao DESC,sp.id_san_pham DESC','price_asc'=>'sp.gia_co_ban ASC,sp.id_san_pham DESC','price_desc'=>'sp.gia_co_ban DESC,sp.id_san_pham DESC'][$sort]??'sp.ngay_tao DESC,sp.id_san_pham DESC';
$from=' FROM san_pham sp JOIN danh_muc dm ON dm.id_danh_muc=sp.id_danh_muc WHERE '.implode(' AND ',$where);
$total=(int)query('SELECT COUNT(*)'.$from,$params)->fetchColumn();$pages=max(1,(int)ceil($total/12));$page=max(1,min($pages,(int)input('page',$_GET,'1')));$offset=($page-1)*12;
$products=query('SELECT sp.*,dm.ten_danh_muc,(SELECT COALESCE(SUM(so_luong_ton),0) FROM bien_the_san_pham bt WHERE bt.id_san_pham=sp.id_san_pham) AS ton_kho'.$from." ORDER BY $sortSql LIMIT 12 OFFSET $offset",$params)->fetchAll();
$page_title=$manage?'Quản lý sản phẩm':'Bộ sưu tập';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="page-heading"><div><p class="eyebrow">FASHION STORE / COLLECTION</p><h1><?=e($page_title)?></h1><p class="muted"><?=$total?> sản phẩm</p></div><?php if(has_role(['nhan_vien','admin'])):?><a class="btn" href="<?=e(url('products/add.php'))?>">+ Thêm sản phẩm</a><?php endif;?></div>
<form class="toolbar" method="get"><?php if($manage):?><input type="hidden" name="manage" value="1"><?php endif;?>
<div><label for="search">Tìm sản phẩm</label><input id="search" name="search" maxlength="150" value="<?=e($search)?>"></div>
<div><label for="category">Danh mục</label><select id="category" name="category"><option value="">Tất cả danh mục</option><?php foreach($categories as $c):?><option value="<?=$c['id_danh_muc']?>" <?=$category===(int)$c['id_danh_muc']?'selected':''?>><?=e($c['ten_danh_muc'])?></option><?php endforeach;?></select></div>
<div><label for="sort">Sắp xếp</label><select id="sort" name="sort"><?php foreach(['newest'=>'Mới nhất','price_asc'=>'Giá tăng dần','price_desc'=>'Giá giảm dần'] as $key=>$label):?><option value="<?=$key?>" <?=$sort===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div>
<?php if($manage):?><div><label for="state">Trạng thái</label><select id="state" name="state"><option value="">Tất cả</option><option value="1" <?=$state==='1'?'selected':''?>>Đang bán</option><option value="0" <?=$state==='0'?'selected':''?>>Đã ẩn</option></select></div><?php endif;?>
<button class="btn">Áp dụng</button><a class="btn btn-secondary" href="<?=e(url('products/index.php'.($manage?'?manage=1':'')))?>">Bỏ lọc</a></form>
<?php if(!$products):?><div class="empty"><h2>Chưa có sản phẩm phù hợp</h2><p>Thử đổi từ khóa hoặc danh mục.</p></div>
<?php elseif($manage):?><div class="table-wrap"><table><thead><tr><th>Sản phẩm</th><th>Danh mục</th><th>Giá cơ bản</th><th>Tồn kho</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach($products as $product):?><tr><td><div class="table-product"><img class="thumb" src="<?=e(product_image($product['hinh_anh']))?>" alt=""><strong><?=e($product['ten_san_pham'])?></strong></div></td><td><?=e($product['ten_danh_muc'])?></td><td class="nowrap"><?=money($product['gia_co_ban'])?></td><td><?=$product['ton_kho']?></td><td><?=$product['trang_thai']?'Đang bán':'Đã ẩn'?></td><td><div class="actions"><a class="btn btn-small btn-secondary" href="<?=e(url('products/edit.php?id='.$product['id_san_pham']))?>">Sửa</a><a class="btn btn-small btn-secondary" href="<?=e(url('products/delete.php?id='.$product['id_san_pham']))?>"><?=$product['trang_thai']?'Ẩn':'Hiện'?></a></div></td></tr><?php endforeach;?></tbody></table></div>
<?php else:?><div class="products"><?php foreach($products as $product)require __DIR__.'/../includes/product-card.php';?></div><?php endif;?>
<?php pagination($page,$pages);?></main><?php require __DIR__.'/../footer.php';?>
