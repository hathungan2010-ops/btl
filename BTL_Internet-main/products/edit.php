<?php
require_once __DIR__.'/../config.php';
require_role(['nhan_vien','admin']);$productId=(int)input('id',$_GET);
if($productId<1)abort_page(404,'Không tìm thấy sản phẩm.');
require __DIR__.'/../includes/product-editor.php';
