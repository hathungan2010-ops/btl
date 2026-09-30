# Fashion Store

Website PHP/PDO/MySQL. Thư mục này là gốc ứng dụng, có thể đổi tên thành `fashion_store` để chạy trên XAMPP.

## Chức năng và phân quyền

| Vai trò | Chức năng |
| --- | --- |
| Khách vãng lai | Trang chủ với 8 sản phẩm mới nhất, tìm/lọc/sắp xếp sản phẩm, xem size/màu, đăng ký/đăng nhập |
| `khach_hang` | Giỏ hàng, đặt hàng, lịch sử và chi tiết đơn của chính mình |
| `nhan_vien` | Thêm/sửa/ẩn/hiện sản phẩm, upload ảnh, quản lý biến thể và tồn kho, xử lý tất cả đơn hàng |
| `admin` | Mọi quyền quản lý của nhân viên, cấp tài khoản, đổi vai trò, khóa/mở người dùng, báo cáo kinh doanh |

Kiểm tra quyền ở máy chủ trên từng trang. Vai trò/trạng thái tài khoản được đọc lại mỗi yêu cầu. Nhân viên/Admin dùng khu vực quản lý; cần tài khoản khách hàng riêng để mua hàng.

## Cài mới

Yêu cầu PHP **8.1+** với `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `session`; MySQL **8.0.16+** hoặc MariaDB **10.6+**, bảng InnoDB. Không cần Composer, Node.js hay CDN.

1. Chép thư mục vào `htdocs/fashion_store`, bật Apache và MySQL.
2. Nhập **`database/fashion_store.sql`** bằng phpMyAdmin. File tạo CSDL `fashion_store`, 8 sản phẩm và các size/màu mẫu. Chỉ nhập vào CSDL trống.
3. Sao chép `config.local.example.php` thành `config.local.php`, chỉnh `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. Dùng `APP_BASE_URL='/fashion_store'` cho `http://localhost/fashion_store/`; sửa theo thư mục thực tế hoặc bỏ dòng này để tự nhận đường dẫn. Biến môi trường có ưu tiên cao hơn file cấu hình.
5. Tạo Admin theo hướng dẫn dưới và đăng nhập.

PHP cần quyền ghi `images/products`. Đặt `upload_max_filesize=5M`, `post_max_size=8M`, `max_input_vars>=1000`. Ảnh của sản phẩm mẫu chưa có sẽ dùng ảnh thay thế cục bộ, không gọi dịch vụ ngoài.

Để chạy bằng máy chủ PHP, đặt `APP_BASE_URL` thành `''`, mở terminal tại thư mục này:

```sh
php -S 127.0.0.1:8000 router.php
```

Mở `http://127.0.0.1:8000`. Router chặn SQL, cấu hình và script nội bộ. Apache dùng `.htaccess`; với Nginx cần chặn tương đương `database/`, `databases/`, `includes/`, `tests/`, file cấu hình/SQL và việc thực thi script trong `images/products/`.

## Tạo Admin đầu tiên

Không có mật khẩu Admin mặc định. Script chỉ chạy bằng CLI và chỉ tạo email mới, không tự nâng quyền tài khoản cũ. Trên PowerShell, thay giá trị mẫu bằng thông tin của bạn:

```powershell
$env:ADMIN_NAME="Quản trị viên"
$env:ADMIN_EMAIL="admin@example.com"
$env:ADMIN_PASSWORD="THAY_BANG_MAT_KHAU_RIENG"
php database/create_admin.php
Remove-Item Env:ADMIN_PASSWORD
```

Nếu PHP chưa có trong PATH, thay `php` bằng `C:\xampp\php\php.exe`.

Trên Bash:

```sh
read -rsp "Mật khẩu Admin: " ADMIN_PASSWORD; echo
export ADMIN_PASSWORD
ADMIN_NAME="Quản trị viên" ADMIN_EMAIL="admin@example.com" php database/create_admin.php
unset ADMIN_PASSWORD
```

Admin vào **Người dùng** để cấp tài khoản nhân viên. Đăng ký công khai luôn gán `khach_hang`, dù trình duyệt gửi vai trò khác.

## Nâng cấp CSDL cũ

`databases/fashion_store.sql` (số nhiều) giữ nguyên làm bản tham chiếu cũ; không dùng để cài bản mới.

1. Sao lưu CSDL hiện có bằng phpMyAdmin hoặc `mysqldump`.
2. Tạm dừng website, cấu hình kết nối đúng CSDL cũ.
3. Chạy `php database/migrate.php`.

Script bổ sung cột/bảng/chỉ mục, chuyển `customer → khach_hang`, `staff → nhan_vien`, chuẩn hóa trạng thái, gộp giỏ/dòng trùng và giữ tổng số lượng. Có thể chạy lại. Nếu có biến thể trùng hoặc trạng thái lạ, script dừng để xử lý rõ ràng. MySQL tự commit DDL; nếu lỗi giữa chừng, xử lý nguyên nhân rồi chạy lại, không nhập đè SQL cài mới.

Thông tin đơn cũ được bổ sung từ sản phẩm hiện tại vì bản cũ chưa lưu bản chụp tên/size/màu/ảnh. Không tự đoán thanh toán hoặc ngày hoàn thành. Nhân viên đối soát thực tế rồi đánh dấu đã nhận tiền và nhập ngày hoàn thành cho các đơn cũ tại **Quản lý đơn hàng**.

## Quy tắc nghiệp vụ

- Giỏ hàng chưa giữ chỗ tồn kho; mỗi phân loại mua 1–99 sản phẩm.
- Đặt hàng khóa dữ liệu, đọc lại giá/tồn kho, lưu đơn và chi tiết (có `thanh_tien`), trừ kho, xóa giỏ trong một giao dịch. Thay đổi giá/giỏ yêu cầu người mua xem lại tổng tiền. Token duy nhất chống gửi lại tạo đơn trùng.
- Tiền tính bằng số nguyên đơn vị 1/100 VNĐ, lưu `DECIMAL`.
- Ẩn sản phẩm là xóa mềm. Biến thể đã lưu giữ nguyên size/màu để bảo toàn đơn cũ; đặt tồn bằng 0 để ngừng bán hoặc thêm biến thể mới. Sửa kho có kiểm tra dữ liệu gốc để tránh ghi đè đơn mới phát sinh.
- Trạng thái: **Chờ xử lý → Đã xác nhận → Đang giao → Hoàn thành**. Chỉ hủy trước khi giao. Hủy hoàn kho đúng một lần, không mở lại đơn hủy hay lùi trạng thái.
- Hoàn thành cần xác nhận đã nhận đủ tiền. Hủy đơn đã trả tiền chuyển **Cần hoàn tiền**; sau khi hoàn bên ngoài, nhân viên xác nhận **Đã hoàn tiền**.
- Doanh thu chỉ tính đơn **Hoàn thành + Đã thanh toán**, theo ngày hoàn thành trong khoảng chọn. Số đơn đặt/chờ/hủy theo ngày đặt.
- Thao tác ghi dùng POST + CSRF, truy vấn PDO tham số hóa, mật khẩu băm, đổi ID session khi đăng nhập, giới hạn 5 lần đăng nhập sai/email trong 15 phút.
- Upload chỉ nhận JPG/PNG/WebP tối đa 5 MB, mỗi chiều tối đa 4096 px; kiểm tra nội dung, mã hóa lại bằng GD, đặt tên ngẫu nhiên. Giữ ảnh cũ vì lịch sử đơn có thể đang tham chiếu.

## Liên hệ và chuyển khoản

Đặt `STORE_EMAIL` thành email thật; giá trị `.example` mặc định chỉ để minh họa. Điền đủ `BANK_NAME`, `BANK_ACCOUNT`, `BANK_OWNER` để bật chuyển khoản. Nếu chưa cấu hình, checkout chỉ có COD. Nội dung chuyển khoản là `FASHION <mã đơn>`; đối soát thủ công. Chưa tích hợp VNPay/MoMo/API ngân hàng.

## Kiểm thử

`tests/integration.py` dùng Python 3 standard library, PHP CLI và MySQL/MariaDB cục bộ. Script **reset CSDL thử nghiệm**, chỉ chấp nhận tên kết thúc `_test`, không dùng CSDL thật:

```sh
DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS='' \
DB_NAME=fashion_store_test python3 tests/integration.py
```

Script tự bật/tắt máy chủ PHP, kiểm tra HTTP, phân quyền, CSRF, upload, giỏ hàng, đặt hàng đồng thời, tồn kho, trạng thái/hoàn tiền, báo cáo và nâng cấp CSDL cũ. Dữ liệu thử nghiệm được giữ lại để xem khi cần. Dùng `PHP_BIN=/duong/dan/php` nếu cần chỉ định PHP.

Kiểm tra cú pháp trên Bash:

```sh
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Khi triển khai thật: bật HTTPS, tắt `display_errors`, cấu hình DB riêng trong file không commit hoặc biến môi trường, kiểm tra các quy tắc chặn HTTP ở trên.
