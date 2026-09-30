<?php
require_once __DIR__.'/config.php';
$viewer=current_user();$staff=has_role(['nhan_vien','admin']);
$cartCount=$viewer&&$viewer['vai_tro']==='khach_hang'?(int)query('SELECT COALESCE(SUM(ct.so_luong),0) FROM gio_hang gh JOIN chi_tiet_gio_hang ct ON ct.id_gio_hang=gh.id_gio_hang WHERE gh.id_nguoi_dung=?',[$viewer['id_nguoi_dung']])->fetchColumn():0;
?>
<!doctype html><html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($page_title??'Phong cách mỗi ngày')?> · Fashion Store</title>
<link rel="stylesheet" href="<?=e(url('css/style.css'))?>">
<script src="<?=e(url('js/script.js'))?>" defer></script></head><body>
<a class="skip-link" href="#main">Bỏ qua menu</a>
<header class="header"><div class="header-container">
<a class="logo" href="<?=e(url())?>">FASHION STORE</a>
<form class="search-form" method="get" action="<?=e(url('products/index.php'))?>" role="search">
<label class="sr-only" for="header-search">Tìm sản phẩm</label><input id="header-search" type="search" name="search" maxlength="150" value="<?=e(input('search',$_GET))?>" placeholder="Tìm sản phẩm…"><button>Tìm</button></form>
<div class="header-actions">
<?php if($viewer):?><span class="user-name"><?=e($viewer['ho_ten'])?><small><?=e(ROLES[$viewer['vai_tro']]??'')?></small></span>
<form method="post" action="<?=e(url('account/logout.php'))?>"><?=csrf_field()?><button class="text-button">Đăng xuất</button></form>
<?php else:?><a href="<?=e(url('account/login.php'))?>">Đăng nhập</a><?php endif;?>
<?php if(!$viewer||$viewer['vai_tro']==='khach_hang'):?><a href="<?=e(url('cart/index.php'))?>">Giỏ hàng (<?=$cartCount?>)</a><?php endif;?>
</div></div>
<nav class="container menu" aria-label="Điều hướng chính">
<a href="<?=e(url())?>">Trang chủ</a><a href="<?=e(url('products/index.php'))?>">Sản phẩm</a>
<?php if($staff):?><a href="<?=e(url('products/index.php?manage=1'))?>">Quản lý sản phẩm</a><a href="<?=e(url('orders/manage.php'))?>">Quản lý đơn hàng</a>
<?php elseif($viewer):?><a href="<?=e(url('orders/history.php'))?>">Đơn hàng của tôi</a><?php else:?><a href="<?=e(url('account/register.php'))?>">Đăng ký</a><?php endif;?>
<?php if(has_role(['admin'])):?><a href="<?=e(url('account/manage.php'))?>">Người dùng</a><a href="<?=e(url('admin/index.php'))?>">Báo cáo kinh doanh</a><?php endif;?>
<a href="#contact">Liên hệ</a></nav></header>
<?php foreach($_SESSION['flashes']??[] as $notice):?><div class="container alert alert-<?=e($notice['type'])?>" role="status"><?=e($notice['message'])?></div><?php endforeach;unset($_SESSION['flashes']);?>
