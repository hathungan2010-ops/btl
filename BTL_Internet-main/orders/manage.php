<?php
require_once __DIR__.'/../config.php';require_role(['nhan_vien','admin']);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{$id=integer(input('id'));update_order($id,input('expected_status'),input('trang_thai'),input('mark_paid')==='1',input('mark_refunded')==='1',input('completed_at'));flash('Đã cập nhật đơn #'.$id.'.');redirect('orders/manage.php?search='.$id);}
    catch(Throwable $ex){$error=error_message($ex);}
}
$state=input('state',$_GET);$search=mb_substr(input('search',$_GET),0,100);$where=['1=1'];$params=[];
if(isset(ORDER_STATES[$state])){$where[]='dh.trang_thai=?';$params[]=$state;}
if($search!==''){$where[]='(dh.id_don_hang=? OR dh.ho_ten_nhan LIKE ? OR dh.so_dien_thoai LIKE ?)';array_push($params,preg_match('/^\d+$/D',$search)?(int)$search:0,"%$search%","%$search%");}
$from=' FROM don_hang dh JOIN nguoi_dung nd ON nd.id_nguoi_dung=dh.id_nguoi_dung WHERE '.implode(' AND ',$where);
$total=(int)query('SELECT COUNT(*)'.$from,$params)->fetchColumn();$pages=max(1,(int)ceil($total/15));$page=max(1,min($pages,(int)input('page',$_GET,'1')));$offset=($page-1)*15;
$orders=query('SELECT dh.*,nd.email'.$from." ORDER BY dh.id_don_hang DESC LIMIT 15 OFFSET $offset",$params)->fetchAll();
$page_title='Quản lý đơn hàng';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><h1>Quản lý đơn hàng</h1>
<?php if($error):?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif;?>
<form class="toolbar" method="get"><div><label for="search-order">Mã đơn, người nhận hoặc điện thoại</label><input id="search-order" name="search" maxlength="100" value="<?=e($search)?>"></div><div><label for="state">Trạng thái</label><select id="state" name="state"><option value="">Tất cả</option><?php foreach(ORDER_STATES as $key=>$label):?><option value="<?=$key?>" <?=$state===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div><button class="btn">Lọc</button><a class="btn btn-secondary" href="<?=e(url('orders/manage.php'))?>">Bỏ lọc</a></form>
<div class="table-wrap"><table><thead><tr><th>Đơn hàng</th><th>Người nhận</th><th>Thanh toán</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead><tbody>
<?php foreach($orders as $o):$current=$o['trang_thai'];$payment=$o['trang_thai_thanh_toan'];$next=ORDER_TRANSITIONS[$current]??[];?>
<tr><td><a class="underlined" href="<?=e(url('orders/detail.php?id='.$o['id_don_hang']))?>">#<?=$o['id_don_hang']?> ↗</a><small><?=date('d/m/Y H:i',strtotime($o['ngay_dat']))?></small><strong><?=money($o['tong_tien'])?></strong></td><td><?=e($o['ho_ten_nhan'])?><small><?=e($o['so_dien_thoai'])?></small><small><?=e($o['email'])?></small></td><td><?=e($o['phuong_thuc_thanh_toan'])?><small><?=e(PAYMENT_STATES[$payment]??'')?></small></td><td><?=status_badge($current)?></td>
<td><?php if($next||$payment==='can_hoan_tien'||($current==='hoan_thanh'&&(!$o['ngay_hoan_thanh']||$payment==='chua_thanh_toan'))):?>
<form method="post" class="stack" data-confirm="Xác nhận cập nhật đơn? Chỉ xác nhận thanh toán/hoàn tiền khi đã đối soát thực tế."><?=csrf_field()?><input type="hidden" name="id" value="<?=$o['id_don_hang']?>"><input type="hidden" name="expected_status" value="<?=e($current)?>">
<select name="trang_thai" aria-label="Trạng thái đơn #<?=$o['id_don_hang']?>"><option value="<?=e($current)?>"><?=e(ORDER_STATES[$current]??$current)?> (giữ nguyên)</option><?php foreach($next as $s):?><option value="<?=$s?>"><?=ORDER_STATES[$s]?></option><?php endforeach;?></select>
<?php if($payment==='chua_thanh_toan'&&$current!=='da_huy'):?><label><input type="checkbox" name="mark_paid" value="1"> Đã nhận đủ tiền</label><?php endif;?>
<?php if($payment==='can_hoan_tien'):?><label><input type="checkbox" name="mark_refunded" value="1"> Đã hoàn tiền thực tế</label><?php endif;?>
<?php if($current==='hoan_thanh'&&!$o['ngay_hoan_thanh']):?><label>Ngày hoàn thành thực tế<input type="datetime-local" name="completed_at" required max="<?=date('Y-m-d\TH:i')?>"></label><?php endif;?>
<button class="btn btn-small">Lưu thay đổi</button></form><?php else:?><small>Đã kết thúc xử lý</small><?php endif;?></td></tr>
<?php endforeach;?><?php if(!$orders):?><tr><td colspan="5">Không có đơn phù hợp.</td></tr><?php endif;?></tbody></table></div><?php pagination($page,$pages);?>
<p class="help section-gap">Chờ xử lý → Đã xác nhận → Đang giao → Hoàn thành. Chỉ hủy trước khi giao; hủy hoàn kho đúng một lần. Hoàn thành yêu cầu đã nhận đủ tiền.</p></main>
<?php require __DIR__.'/../footer.php';?>
