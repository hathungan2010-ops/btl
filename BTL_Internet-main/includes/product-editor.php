<?php
require_once __DIR__.'/../config.php';require_role(['nhan_vien','admin']);$productId=$productId??0;
$product=$productId?query('SELECT * FROM san_pham WHERE id_san_pham=?',[$productId])->fetch():
    ['ten_san_pham'=>'','id_danh_muc'=>'','mo_ta'=>'','gia_co_ban'=>'','hinh_anh'=>'','trang_thai'=>1,'version'=>1];
if(!$product)abort_page(404,'Không tìm thấy sản phẩm.');
$categories=query('SELECT * FROM danh_muc WHERE trang_thai=1 ORDER BY ten_danh_muc')->fetchAll();
$sizes=query('SELECT * FROM kich_thuoc ORDER BY id_kich_thuoc')->fetchAll();$colors=query('SELECT * FROM mau_sac ORDER BY id_mau_sac')->fetchAll();
$existing=$productId?query('SELECT * FROM bien_the_san_pham WHERE id_san_pham=? ORDER BY id_bien_the',[$productId])->fetchAll():[];
$rows=$existing?:[['id_bien_the'=>0,'id_kich_thuoc'=>'','id_mau_sac'=>'','gia'=>'','so_luong_ton'=>0]];
$error='';$newImage=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    foreach(['ten_san_pham','id_danh_muc','mo_ta','gia_co_ban','trang_thai','version'] as $field)$product[$field]=input($field);
    $rows=is_array($_POST['variants']??null)?array_values(array_filter($_POST['variants'],'is_array')):[];
    try{
        $name=$product['ten_san_pham'];$description=$product['mo_ta'];
        if(mb_strlen($name)<2||mb_strlen($name)>150)throw new InvalidArgumentException('Tên sản phẩm cần từ 2 đến 150 ký tự.');
        if(mb_strlen($description)>10000)throw new InvalidArgumentException('Mô tả tối đa 10.000 ký tự.');
        $category=integer($product['id_danh_muc']);$basePrice=price($product['gia_co_ban']);$active=integer($product['trang_thai'],0,1);
        if(!in_array($category,array_map('intval',array_column($categories,'id_danh_muc')),true))throw new InvalidArgumentException('Danh mục không hợp lệ.');
        if(count($rows)<1||count($rows)>100)throw new InvalidArgumentException('Cần từ 1 đến 100 biến thể.');
        $validated=[];$pairs=[];$ids=[];
        foreach($rows as $row){
            $id=integer(input('id_bien_the',$row,'0'),0);$size=integer(input('id_kich_thuoc',$row));$color=integer(input('id_mau_sac',$row));
            if(!in_array($size,array_map('intval',array_column($sizes,'id_kich_thuoc')),true)||!in_array($color,array_map('intval',array_column($colors,'id_mau_sac')),true))throw new InvalidArgumentException('Size/màu không hợp lệ.');
            $pair=$size.':'.$color;
            if(isset($pairs[$pair])||($id&&isset($ids[$id])))throw new InvalidArgumentException('Mỗi tổ hợp size/màu chỉ xuất hiện một lần.');
            $pairs[$pair]=true;if($id)$ids[$id]=true;
            $validated[]=['id'=>$id,'size'=>$size,'color'=>$color,'price'=>price(input('gia',$row)),
                'stock'=>integer(input('so_luong_ton',$row),0,1000000),
                'original'=>integer(input('original_stock',$row,(string)($row['so_luong_ton']??0)),0,1000000)];
        }
        $file=$_FILES['hinh_anh']??null;
        if($file&&($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            if(!is_int($file['error'])||$file['error']!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Tải ảnh thất bại. Kiểm tra giới hạn upload của PHP.');
            if($file['size']>5*1024*1024||!is_uploaded_file($file['tmp_name']))throw new InvalidArgumentException('Ảnh không hợp lệ hoặc lớn hơn 5 MB.');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$dim=@getimagesize($file['tmp_name']);
            if(!isset($ext[$mime])||!$dim||$dim[0]>4096||$dim[1]>4096)throw new InvalidArgumentException('Chỉ nhận JPG, PNG, WebP tối đa 4096 × 4096 pixel.');
            $bitmap=@imagecreatefromstring(file_get_contents($file['tmp_name']));
            if(!$bitmap)throw new InvalidArgumentException('Không đọc được ảnh.');
            $newImage='upload-'.bin2hex(random_bytes(16)).'.'.$ext[$mime];$path=__DIR__.'/../images/products/'.$newImage;
            imagealphablending($bitmap,false);imagesavealpha($bitmap,true);
            $saved=$mime==='image/jpeg'?imagejpeg($bitmap,$path,90):($mime==='image/png'?imagepng($bitmap,$path):imagewebp($bitmap,$path,90));imagedestroy($bitmap);
            if(!$saved)throw new InvalidArgumentException('Không thể lưu ảnh. Kiểm tra quyền ghi images/products.');
        }
        $conn->beginTransaction();
        if($productId){
            $locked=query('SELECT * FROM san_pham WHERE id_san_pham=? FOR UPDATE',[$productId])->fetch();
            if(!$locked||(int)$locked['version']!==integer($product['version']))throw new InvalidArgumentException('Sản phẩm vừa được người khác cập nhật. Hãy tải lại trang.');
            $oldRows=query('SELECT * FROM bien_the_san_pham WHERE id_san_pham=? ORDER BY id_bien_the FOR UPDATE',[$productId])->fetchAll();$byId=array_column($oldRows,null,'id_bien_the');
            if(count($ids)!==count($byId))throw new InvalidArgumentException('Không xóa biến thể đã lưu; đặt tồn kho bằng 0 để ngừng bán.');
            foreach($validated as $v){
                if(!$v['id'])continue;$old=$byId[$v['id']]??null;
                if(!$old||(int)$old['id_kich_thuoc']!==$v['size']||(int)$old['id_mau_sac']!==$v['color'])throw new InvalidArgumentException('Giữ nguyên size/màu của biến thể đã lưu; thêm biến thể mới khi cần.');
                if((int)$old['so_luong_ton']!==$v['original'])throw new InvalidArgumentException('Tồn kho vừa thay đổi. Hãy tải lại trang trước khi lưu.');
            }
            query('UPDATE san_pham SET ten_san_pham=?,id_danh_muc=?,mo_ta=?,gia_co_ban=?,hinh_anh=?,trang_thai=?,version=version+1 WHERE id_san_pham=?',
                [$name,$category,$description,$basePrice,$newImage??$locked['hinh_anh'],$active,$productId]);
        }else{
            if($ids)throw new InvalidArgumentException('Biến thể không hợp lệ.');
            query('INSERT INTO san_pham (ten_san_pham,id_danh_muc,mo_ta,gia_co_ban,hinh_anh,trang_thai) VALUES (?,?,?,?,?,?)',[$name,$category,$description,$basePrice,$newImage,$active]);
            $productId=(int)$conn->lastInsertId();
        }
        foreach($validated as $v){
            if($v['id'])query('UPDATE bien_the_san_pham SET gia=?,so_luong_ton=? WHERE id_bien_the=? AND id_san_pham=?',[$v['price'],$v['stock'],$v['id'],$productId]);
            else query('INSERT INTO bien_the_san_pham (id_san_pham,id_kich_thuoc,id_mau_sac,gia,so_luong_ton) VALUES (?,?,?,?,?)',[$productId,$v['size'],$v['color'],$v['price'],$v['stock']]);
        }
        $conn->commit();flash('Đã lưu sản phẩm và biến thể.');redirect('products/index.php?manage=1');
    }catch(Throwable $ex){if($conn->inTransaction())$conn->rollBack();if($newImage&&is_file(__DIR__.'/../images/products/'.$newImage))unlink(__DIR__.'/../images/products/'.$newImage);$error=error_message($ex);}
}
$page_title=$productId?'Chỉnh sửa sản phẩm':'Thêm sản phẩm';require __DIR__.'/../header.php';
function variant_row(array $r,$index,array $sizes,array $colors):void { ?>
<tr><td><?=!empty($r['id_bien_the'])?'#'.e($r['id_bien_the']):'Mới'?><input type="hidden" name="variants[<?=e($index)?>][id_bien_the]" value="<?=e($r['id_bien_the']??0)?>"><input type="hidden" name="variants[<?=e($index)?>][original_stock]" value="<?=e($r['original_stock']??$r['so_luong_ton']??0)?>"></td>
<?php foreach(['id_kich_thuoc'=>[$sizes,'ten_kich_thuoc','Size'],'id_mau_sac'=>[$colors,'ten_mau_sac','Màu']] as $field=>$meta):?><td><select name="variants[<?=e($index)?>][<?=$field?>]" required aria-label="<?=$meta[2]?>"><option value="">Chọn <?=$meta[2]?></option><?php foreach($meta[0] as $o):?><option value="<?=$o[$field]?>" <?=e($r[$field]??'')===(string)$o[$field]?'selected':''?>><?=e($o[$meta[1]])?></option><?php endforeach;?></select></td><?php endforeach;?>
<td><input name="variants[<?=e($index)?>][gia]" type="number" min="0.01" max="999999999.99" step="0.01" required value="<?=e($r['gia']??'')?>" aria-label="Giá biến thể"></td>
<td><input name="variants[<?=e($index)?>][so_luong_ton]" type="number" min="0" max="1000000" required value="<?=e($r['so_luong_ton']??0)?>" aria-label="Tồn kho"></td>
<td><?php if(empty($r['id_bien_the'])):?><button type="button" class="text-button" data-remove-variant>Bỏ</button><?php else:?><small>Đã lưu</small><?php endif;?></td></tr>
<?php } ?>
<main id="main" class="container section"><div class="page-heading"><h1><?=e($page_title)?></h1><a class="underlined" href="<?=e(url('products/index.php?manage=1'))?>">← Danh sách sản phẩm</a></div>
<?php if($error):?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif;?>
<form class="panel stack" method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="version" value="<?=e($product['version'])?>">
<div class="form-grid">
<div><label for="name">Tên sản phẩm</label><input id="name" name="ten_san_pham" required minlength="2" maxlength="150" value="<?=e($product['ten_san_pham'])?>"></div>
<div><label for="category">Danh mục</label><select id="category" name="id_danh_muc" required><option value="">Chọn danh mục</option><?php foreach($categories as $c):?><option value="<?=$c['id_danh_muc']?>" <?=(string)$product['id_danh_muc']===(string)$c['id_danh_muc']?'selected':''?>><?=e($c['ten_danh_muc'])?></option><?php endforeach;?></select></div>
<div><label for="price">Giá cơ bản (VNĐ)</label><input id="price" name="gia_co_ban" type="number" min="0.01" max="999999999.99" step="0.01" required value="<?=e($product['gia_co_ban'])?>"><p class="help">Giá danh sách; thanh toán lấy giá của biến thể.</p></div>
<div><label for="state">Trạng thái</label><select id="state" name="trang_thai"><option value="1" <?=$product['trang_thai']?'selected':''?>>Đang bán</option><option value="0" <?=!$product['trang_thai']?'selected':''?>>Ẩn</option></select></div>
<div class="full-width"><label for="description">Mô tả</label><textarea id="description" name="mo_ta" maxlength="10000"><?=e($product['mo_ta'])?></textarea></div>
<div class="full-width"><label for="image">Ảnh sản phẩm</label><input id="image" name="hinh_anh" type="file" accept="image/jpeg,image/png,image/webp" data-image-upload><p class="help">JPG, PNG, WebP · tối đa 5 MB, mỗi chiều tối đa 4096 px. Để trống để giữ ảnh cũ.</p><img class="preview-image" src="<?=e(product_image($product['hinh_anh']))?>" alt="Xem trước sản phẩm" data-image-preview></div></div>
<h2>Size, màu và tồn kho</h2><p class="help">Giữ nguyên size/màu của biến thể đã lưu để bảo toàn đơn hàng. Đặt tồn bằng 0 để ngừng bán.</p>
<div class="table-wrap"><table class="variant-editor"><thead><tr><th>Mã</th><th>Size</th><th>Màu</th><th>Giá (VNĐ)</th><th>Tồn kho</th><th></th></tr></thead><tbody data-variant-rows data-next-index="<?=count($rows)?>"><?php foreach($rows as $i=>$r)variant_row($r,$i,$sizes,$colors);?></tbody></table></div>
<template id="variant-template"><?php variant_row(['id_bien_the'=>0,'gia'=>$product['gia_co_ban']],'__INDEX__',$sizes,$colors);?></template>
<button type="button" class="btn btn-secondary" data-add-variant>+ Thêm biến thể</button><noscript><p>Bật JavaScript để thêm nhiều biến thể.</p></noscript>
<div class="actions"><button class="btn">Lưu sản phẩm</button><a class="btn btn-secondary" href="<?=e(url('products/index.php?manage=1'))?>">Quay lại</a></div></form></main>
<?php require __DIR__.'/../footer.php';?>
