<?php global $store_email; ?>
<footer class="footer" id="contact"><div class="footer-container">
<div class="footer-column"><h3>FASHION STORE</h3><p>Thời trang mỗi ngày, phong cách của bạn.</p><p>Website đồ án BTL Internet.</p></div>
<div class="footer-column"><h3>Mua sắm</h3><a href="<?=e(url('products/index.php'))?>">Khám phá bộ sưu tập</a><a href="<?=e(url('orders/history.php'))?>">Theo dõi đơn hàng</a><p>Chọn sản phẩm → Giỏ hàng → Nhập thông tin nhận hàng → Đặt hàng.</p></div>
<div class="footer-column"><h3>Liên hệ</h3><a href="mailto:<?=e($store_email)?>"><?=e($store_email)?></a><p>Thanh toán khi nhận hàng<?=count(payment_methods())>1?' hoặc chuyển khoản ngân hàng':''?>.</p></div>
</div><div class="footer-bottom">© <?=date('Y')?> Fashion Store. Bảo lưu mọi quyền.</div></footer></body></html>
