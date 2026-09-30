<?php
require_once __DIR__.'/../config.php';require_role(['nhan_vien','admin']);
$id=(int)input('id',$_GET);$p=query('SELECT * FROM san_pham WHERE id_san_pham=?',[$id])->fetch();
if(!$p)abort_page(404,'Không tìm thấy sản phẩm.');$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $active=integer(input('trang_thai'),0,1);$version=integer(input('version'));
        if(!query('UPDATE san_pham SET trang_thai=?,version=version+1 WHERE id_san_pham=? AND version=?',[$active,$id,$version])->rowCount())throw new InvalidArgumentException('Sản phẩm đã thay đổi. Hãy tải lại trang.');
        flash($active?'Đã hiển thị lại sản phẩm.':'Đã ẩn sản phẩm.');redirect('products/index.php?manage=1');
    }catch(Throwable $ex){$error=error_message($ex);}
}
$page_title=$p['trang_thai']?'Ẩn sản phẩm':'Hiện lại sản phẩm';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="auth-shell panel"><h1><?=e($page_title)?></h1><h3><?=e($p['ten_san_pham'])?></h3><p>Sản phẩm bị ẩn không thể mua mới. Thông tin đơn hàng đã đặt vẫn được giữ nguyên.</p>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<form method="post"><?=csrf_field()?><input type="hidden" name="version" value="<?=$p['version']?>"><input type="hidden" name="trang_thai" value="<?=$p['trang_thai']?0:1?>"><div class="actions"><button class="btn">Xác nhận</button><a class="btn btn-secondary" href="<?=e(url('products/index.php?manage=1'))?>">Quay lại</a></div></form></div></main>
<?php require __DIR__.'/../footer.php';?>
