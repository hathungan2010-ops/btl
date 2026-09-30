<?php
require_once __DIR__.'/../config.php';$admin=require_role(['admin']);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        if(input('action')==='create'){
            $name=input('ho_ten');$email=strtolower(input('email'));$phone=input('so_dien_thoai');$role=input('vai_tro');
            $password=is_string($_POST['mat_khau']??null)?$_POST['mat_khau']:'';
            validate_name_email($name,$email);validate_phone($phone,false);validate_password($password);
            if(!isset(ROLES[$role]))throw new InvalidArgumentException('Vai trò không hợp lệ.');
            query('INSERT INTO nguoi_dung (ho_ten,email,so_dien_thoai,mat_khau,vai_tro) VALUES (?,?,?,?,?)',[$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$role]);
            flash('Đã tạo tài khoản mới.');
        }elseif(input('action')==='update'){
            $id=integer(input('id'));$role=input('vai_tro');$active=integer(input('trang_thai'),0,1);
            if(!isset(ROLES[$role]))throw new InvalidArgumentException('Vai trò không hợp lệ.');
            if($id===(int)$admin['id_nguoi_dung']&&($role!=='admin'||!$active))throw new InvalidArgumentException('Không thể tự hạ quyền hoặc khóa tài khoản đang sử dụng.');
            $conn->beginTransaction();
            $admins=query("SELECT id_nguoi_dung FROM nguoi_dung WHERE vai_tro='admin' AND trang_thai=1 ORDER BY id_nguoi_dung FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            $target=query('SELECT * FROM nguoi_dung WHERE id_nguoi_dung=? FOR UPDATE',[$id])->fetch();
            if(!$target)throw new InvalidArgumentException('Không tìm thấy tài khoản.');
            if($target['vai_tro']==='admin'&&$target['trang_thai']&&($role!=='admin'||!$active)&&count($admins)<=1)throw new InvalidArgumentException('Cần ít nhất một Admin đang hoạt động.');
            query('UPDATE nguoi_dung SET vai_tro=?,trang_thai=? WHERE id_nguoi_dung=?',[$role,$active,$id]);$conn->commit();flash('Đã cập nhật quyền và trạng thái tài khoản.');
        }else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        redirect('account/manage.php');
    }catch(Throwable $ex){if($conn->inTransaction())$conn->rollBack();$error=$ex instanceof PDOException&&$ex->getCode()==='23000'?'Email đã được sử dụng.':error_message($ex);}
}
$search=mb_substr(input('search',$_GET),0,100);
$total=(int)query('SELECT COUNT(*) FROM nguoi_dung WHERE ho_ten LIKE ? OR email LIKE ?',["%$search%","%$search%"])->fetchColumn();
$pages=max(1,(int)ceil($total/15));$page=max(1,min($pages,(int)input('page',$_GET,'1')));$offset=($page-1)*15;
$users=query("SELECT * FROM nguoi_dung WHERE ho_ten LIKE ? OR email LIKE ? ORDER BY id_nguoi_dung DESC LIMIT 15 OFFSET $offset",["%$search%","%$search%"])->fetchAll();
$page_title='Quản lý người dùng';require __DIR__.'/../header.php';
?>
<main id="main" class="container section"><div class="page-heading"><h1>Quản lý người dùng</h1><a class="btn" href="#create-user">+ Cấp tài khoản</a></div>
<?php if($error):?><div class="alert alert-error" role="alert"><?=e($error)?></div><?php endif;?>
<form class="toolbar" method="get"><div><label for="user-search">Họ tên hoặc email</label><input id="user-search" name="search" maxlength="100" value="<?=e($search)?>"></div><button class="btn">Tìm kiếm</button></form>
<div class="table-wrap"><table><thead><tr><th>Người dùng</th><th>Liên hệ</th><th>Vai trò & trạng thái</th></tr></thead><tbody>
<?php foreach($users as $u):?><tr><td><strong><?=e($u['ho_ten'])?></strong><small>#<?=$u['id_nguoi_dung']?></small></td><td><?=e($u['email'])?><small><?=e($u['so_dien_thoai'])?></small></td><td><form class="inline-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=$u['id_nguoi_dung']?>">
<select name="vai_tro" aria-label="Vai trò của <?=e($u['ho_ten'])?>"><?php foreach(ROLES as $key=>$label):?><option value="<?=$key?>" <?=$u['vai_tro']===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select>
<select name="trang_thai" aria-label="Trạng thái của <?=e($u['ho_ten'])?>"><option value="1" <?=$u['trang_thai']?'selected':''?>>Hoạt động</option><option value="0" <?=!$u['trang_thai']?'selected':''?>>Đã khóa</option></select><button class="btn btn-small">Lưu</button></form></td></tr><?php endforeach;?>
<?php if(!$users):?><tr><td colspan="3">Không tìm thấy người dùng.</td></tr><?php endif;?></tbody></table></div><?php pagination($page,$pages);?>
<section class="panel section-gap" id="create-user"><h2>Cấp tài khoản mới</h2><form class="form-grid" method="post"><?=csrf_field()?><input type="hidden" name="action" value="create">
<div><label for="new-name">Họ tên</label><input id="new-name" name="ho_ten" required minlength="2" maxlength="100" value="<?=e(input('ho_ten'))?>"></div>
<div><label for="new-email">Email</label><input id="new-email" name="email" type="email" required maxlength="100" value="<?=e(input('email'))?>"></div>
<div><label for="new-phone">Điện thoại</label><input id="new-phone" name="so_dien_thoai" type="tel" maxlength="20" value="<?=e(input('so_dien_thoai'))?>"></div>
<div><label for="new-role">Vai trò</label><select id="new-role" name="vai_tro"><?php foreach(ROLES as $key=>$label):?><option value="<?=$key?>" <?=input('vai_tro',null,'nhan_vien')===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></div>
<div><label for="new-password">Mật khẩu ban đầu</label><input id="new-password" name="mat_khau" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></div><div class="full-width"><button class="btn">Tạo tài khoản</button></div></form></section></main>
<?php require __DIR__.'/../footer.php';?>
