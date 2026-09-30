#!/usr/bin/env python3
"""HTTP + MySQL regression tests. Only local disposable *_test databases."""
import concurrent.futures
import contextlib
import html
import http.cookiejar
import json
import os
from pathlib import Path
import re
import signal
import socket
import struct
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import zlib

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get("PHP_BIN", "php")
ENV = dict(os.environ, APP_BASE_URL="", BANK_NAME="Test Bank", BANK_ACCOUNT="123456789", BANK_OWNER="Test Store")
if not re.fullmatch(r"[a-zA-Z0-9_]+_test", ENV.get("DB_NAME", "")):
    sys.exit("Set DB_NAME to a disposable database ending in _test.")
if ENV.get("DB_HOST", "127.0.0.1") not in ("127.0.0.1", "localhost"):
    sys.exit("Tests only run against a local database.")
RUNTIME = ROOT / "tests/.runtime"
(RUNTIME / "sessions").mkdir(parents=True, exist_ok=True)
checks = 0

def check(value, message):
    global checks
    assert value, message
    checks += 1
    print(f"PASS {message}", flush=True)

def db(sql=None, params=(), **request):
    if sql is not None:
        request.update(sql=sql, params=params)
    p = subprocess.run([PHP, str(ROOT / "tests/db.php")], input=json.dumps(request),
        capture_output=True, text=True, env=ENV)
    if p.returncode:
        raise RuntimeError(p.stdout+p.stderr)
    return json.loads(p.stdout)

def scalar(sql, params=()):
    return next(iter(db(sql, params)[0].values()))

def cli(path, extra=None):
    p = subprocess.run([PHP, str(ROOT/path)], capture_output=True, text=True, env=dict(ENV, **(extra or {})))
    if p.returncode:
        raise RuntimeError(p.stdout+p.stderr)
    return p.stdout

def field(body, name):
    match = re.search(r'name="'+re.escape(name)+r'"[^>]*value="([^"]*)"', body)
    assert match, "Missing field "+name
    return html.unescape(match.group(1))

class Client:
    def __init__(self, base):
        self.base = base
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(self.jar))
    def request(self, path, data=None, files=None):
        headers = {}
        if files:
            boundary = "fashion-store-tests"
            parts = []
            for name, value in data.items():
                parts.extend([f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n'.encode(), str(value).encode(), b"\r\n"])
            for name, (filename, content, mime) in files.items():
                parts.extend([f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode(), content, b"\r\n"])
            parts.append(f"--{boundary}--\r\n".encode())
            payload = b"".join(parts)
            headers["Content-Type"] = f"multipart/form-data; boundary={boundary}"
        else:
            payload = urllib.parse.urlencode(data).encode() if data is not None else None
        try:
            r = self.opener.open(urllib.request.Request(self.base+"/"+path.lstrip("/"), data=payload, headers=headers), timeout=20)
        except urllib.error.HTTPError as error:
            r = error
        body = r.read().decode("utf-8", errors="replace")
        assert not re.search(r"Fatal error|Warning:|Deprecated:|Parse error", body), body[:2000]
        return r.status, body, r.url
    def get(self, path="index.php"):
        return self.request(path)
    def token(self, path="index.php"):
        code, body, _ = self.get(path)
        assert code == 200, (path, code)
        return field(body, "csrf")
    def post(self, path, data, files=None, token=None):
        data = dict(data)
        data.setdefault("csrf", token if token is not None else self.token())
        return self.request(path, data, files)
    def login(self, index):
        return self.post("account/login.php", {"email":f"user{index}@example.test","mat_khau":"Testing-Store-2026"}, token=self.token("account/login.php"))

def checkout(client):
    _, body, _ = client.get("orders/checkout.php")
    return {k:field(body,k) for k in ("csrf","checkout_token","quote")} | {
        "ho_ten_nhan":"Người Mua Thử","so_dien_thoai":"0901234567",
        "dia_chi_giao_hang":"12 Nguyễn Trãi, Hà Nội","phuong_thuc_thanh_toan":"COD"}

def product_form(body):
    body = body.split('<template id="variant-template">')[0]
    data = {}
    for tag in re.findall(r"<input\b[^>]*>", body):
        a = dict(re.findall(r'([\w-]+)="([^"]*)"', tag))
        if a.get("name") and a.get("type")!="file":
            data[a["name"]] = html.unescape(a.get("value",""))
    for name, options in re.findall(r'<select\b[^>]*name="([^"]+)"[^>]*>(.*?)</select>', body, re.S):
        m = re.search(r'<option\b[^>]*value="([^"]*)"[^>]*selected', options)
        data[name] = html.unescape(m.group(1)) if m else ""
    data["mo_ta"] = "Mô tả kiểm thử"
    return data

def png_fixture():
    def chunk(kind, data):
        return struct.pack(">I",len(data))+kind+data+struct.pack(">I",zlib.crc32(kind+data)&0xffffffff)
    return b"\x89PNG\r\n\x1a\n"+chunk(b"IHDR",struct.pack(">IIBBBBB",1,1,8,2,0,0,0))+chunk(b"IDAT",zlib.compress(b"\0\x20\x80\x40"))+chunk(b"IEND",b"")

@contextlib.contextmanager
def server():
    with socket.socket() as s:
        s.bind(("127.0.0.1",0))
        port=s.getsockname()[1]
    env=dict(ENV)
    if os.name!="nt":
        env["PHP_CLI_SERVER_WORKERS"]="4"
    log=open(RUNTIME/"http.log","w")
    process=subprocess.Popen([PHP,"-d",f"session.save_path={RUNTIME/'sessions'}","-S",f"127.0.0.1:{port}","-t",str(ROOT),str(ROOT/"router.php")],
        cwd=ROOT,env=env,stdout=log,stderr=log,start_new_session=True)
    base=f"http://127.0.0.1:{port}"
    try:
        for _ in range(80):
            try:
                if Client(base).get()[0]==200:
                    break
            except (OSError,urllib.error.URLError):
                pass
            time.sleep(.1)
        else:
            raise RuntimeError("PHP server failed: "+(RUNTIME/"http.log").read_text())
        yield base
    finally:
        if os.name=="nt":
            process.terminate()
        else:
            os.killpg(process.pid,signal.SIGTERM)
        process.wait(timeout=10)
        log.close()

def suite(base):
    guest=Client(base)
    check(guest.get()[1].count('class="product-card"')==8,"homepage: eight newest products")
    check(guest.get("products/index.php?search=Jeans&category=2")[1].count('class="product-card"')==1,"search plus category")
    check("Chưa có sản phẩm phù hợp" in guest.get("products/index.php?search=unmatched-query")[1],"empty search")
    check(guest.get("products/detail.php?id=999999")[0]==404,"missing product")
    check("account/login.php" in guest.get("admin/index.php")[2],"guest authentication guard")
    for path in ("database/fashion_store.sql","config.local.example.php","tests/db.php"):
        check(guest.get(path)[0]==404,"private file guard: "+path)
    guest.post("account/register.php",{"ho_ten":"<script>alert(1)</script>","email":"registered@example.test","mat_khau":"Registration2026","xac_nhan_mat_khau":"Registration2026","vai_tro":"admin"},token=guest.token("account/register.php"))
    check(scalar("SELECT vai_tro FROM nguoi_dung WHERE email='registered@example.test'")=="khach_hang","registration cannot grant admin")
    check(str(scalar("SELECT mat_khau FROM nguoi_dung WHERE email='registered@example.test'")).startswith("$2y$"),"password hashing")
    admin,staff,c,other=[Client(base) for _ in range(4)]
    for client,i in zip((admin,staff,c,other),(1,2,3,4)):
        check(client.login(i)[0]==200,"login role "+str(i))
    for path in ("admin/index.php","account/manage.php","products/add.php","products/edit.php?id=1","products/delete.php?id=1","orders/manage.php"):
        check(c.get(path)[0]==403,"customer role guard: "+path)
    for path in ("admin/index.php","account/manage.php","cart/index.php","orders/checkout.php"):
        check(staff.get(path)[0]==403,"staff role guard: "+path)
    for path in ("cart/add.php","cart/update.php","cart/delete.php","account/logout.php"):
        check(c.get(path)[0]==405,"POST-only: "+path)
    check(c.request("cart/add.php",{"id_bien_the":1,"so_luong":1,"csrf":"invalid"})[0]==403,"CSRF guard")
    c.post("cart/add.php",{"id_bien_the":1,"so_luong":2})
    line=int(scalar("SELECT id_chi_tiet FROM chi_tiet_gio_hang"))
    other.post("cart/update.php",{"id_chi_tiet":line,"so_luong":8})
    other.post("cart/delete.php",{"id_chi_tiet":line})
    check(int(scalar("SELECT so_luong FROM chi_tiet_gio_hang WHERE id_chi_tiet=?",[line]))==2,"cart ownership update/delete")
    c.post("cart/update.php",{"id_chi_tiet":line,"so_luong":99})
    check(int(scalar("SELECT so_luong FROM chi_tiet_gio_hang WHERE id_chi_tiet=?",[line]))==2,"cart stock limit")
    c.post("cart/update.php",{"id_chi_tiet":line,"so_luong":3})
    form=checkout(c)
    c.post("orders/checkout.php",dict(form,phuong_thuc_thanh_toan="forged"))
    check(int(scalar("SELECT COUNT(*) FROM don_hang"))==0,"payment allowlist")
    code,body,location=c.post("orders/checkout.php",form)
    check(code==200 and "orders/detail.php?id=" in location,"checkout success")
    oid=int(scalar("SELECT MAX(id_don_hang) FROM don_hang"))
    check(str(scalar("SELECT tong_tien FROM don_hang WHERE id_don_hang=?",[oid]))=="597000.00","database price total")
    check(str(scalar("SELECT thanh_tien FROM chi_tiet_don_hang WHERE id_don_hang=?",[oid]))=="597000.00","order detail subtotal")
    check(int(scalar("SELECT so_luong_ton FROM bien_the_san_pham WHERE id_bien_the=1"))==17,"stock decremented")
    check(int(scalar("SELECT COUNT(*) FROM chi_tiet_gio_hang"))==0,"cart cleared")
    c.post("orders/checkout.php",form)
    check(int(scalar("SELECT COUNT(*) FROM don_hang"))==1 and int(scalar("SELECT so_luong_ton FROM bien_the_san_pham WHERE id_bien_the=1"))==17,"replayed checkout idempotent")
    check(other.get(f"orders/detail.php?id={oid}")[0]==404,"order ownership")
    check(staff.get(f"orders/detail.php?id={oid}")[0]==200,"staff order inspection")
    # Product creation, MIME validation, editing conflicts and historical snapshot.
    new={"ten_san_pham":"Test Shirt","id_danh_muc":1,"mo_ta":"Test","gia_co_ban":"250000","trang_thai":1,"version":1,
         "variants[0][id_bien_the]":0,"variants[0][id_kich_thuoc]":1,"variants[0][id_mau_sac]":1,
         "variants[0][gia]":"250000","variants[0][so_luong_ton]":5,"variants[0][original_stock]":0}
    staff.post("products/add.php",new,files={"hinh_anh":("fake.png",b"<?php echo 1;?>","image/png")})
    check(int(scalar("SELECT COUNT(*) FROM san_pham"))==8,"reject fake image")
    staff.post("products/add.php",new,files={"hinh_anh":("product.png",png_fixture(),"image/png")})
    check(int(scalar("SELECT COUNT(*) FROM san_pham"))==9,"create product with uploaded image")
    filename=scalar("SELECT hinh_anh FROM san_pham WHERE id_san_pham=9")
    check(str(filename).startswith("upload-") and (ROOT/"images/products"/filename).exists(),"random upload filename")
    edit=product_form(staff.get("products/edit.php?id=1")[1]);edit["ten_san_pham"]="Renamed after order"
    staff.post("products/edit.php?id=1",edit)
    check(scalar("SELECT ten_san_pham FROM san_pham WHERE id_san_pham=1")=="Renamed after order","edit product")
    check(scalar("SELECT ten_san_pham FROM chi_tiet_don_hang WHERE id_don_hang=?",[oid])=="Áo Thun Basic","preserve order name snapshot")
    check("vừa được người khác cập nhật" in staff.post("products/edit.php?id=1",edit)[1],"optimistic version check")
    edit=product_form(staff.get("products/edit.php?id=1")[1])
    db("UPDATE bien_the_san_pham SET so_luong_ton=16 WHERE id_bien_the=1")
    check("Tồn kho vừa thay đổi" in staff.post("products/edit.php?id=1",edit)[1],"stock edit conflict")
    db("UPDATE bien_the_san_pham SET so_luong_ton=17 WHERE id_bien_the=1")
    version=int(scalar("SELECT version FROM san_pham WHERE id_san_pham=1"))
    staff.post("products/delete.php?id=1",{"version":version,"trang_thai":0})
    check(c.get("products/detail.php?id=1")[0]==404,"hide product")
    c.post("cart/add.php",{"id_bien_the":1,"so_luong":1})
    check(int(scalar("SELECT COUNT(*) FROM chi_tiet_gio_hang"))==0,"hidden variant cannot enter cart")
    check(c.get(f"orders/detail.php?id={oid}")[0]==200,"hidden product keeps order readable")
    staff.post("products/delete.php?id=1",{"version":version+1,"trang_thai":1})
    # Stale prices and multi-line stock failure.
    c.post("cart/add.php",{"id_bien_the":1,"so_luong":1});form=checkout(c)
    db("UPDATE bien_the_san_pham SET gia=210000 WHERE id_bien_the=1")
    check("Giỏ hàng hoặc giá sản phẩm vừa thay đổi" in c.post("orders/checkout.php",form)[1],"stale quote requires confirmation")
    c.post("cart/add.php",{"id_bien_the":2,"so_luong":1});form=checkout(c)
    db("UPDATE bien_the_san_pham SET so_luong_ton=0 WHERE id_bien_the=2")
    c.post("orders/checkout.php",form)
    check(int(scalar("SELECT COUNT(*) FROM don_hang"))==1 and int(scalar("SELECT so_luong_ton FROM bien_the_san_pham WHERE id_bien_the=1"))==17,"atomic rollback on insufficient stock")
    c.post("cart/delete.php",{"action":"clear"})
    db("UPDATE bien_the_san_pham SET so_luong_ton=30 WHERE id_bien_the=2")
    db("UPDATE bien_the_san_pham SET gia=199000 WHERE id_bien_the=1")
    def transition(order,expected,nxt,**extra):
        return staff.post("orders/manage.php",dict(id=order,expected_status=expected,trang_thai=nxt,**extra))
    transition(oid,"cho_xu_ly","hoan_thanh",mark_paid=1)
    check(scalar("SELECT trang_thai FROM don_hang WHERE id_don_hang=?",[oid])=="cho_xu_ly","reject skipped status")
    transition(oid,"cho_xu_ly","da_xac_nhan");transition(oid,"da_xac_nhan","dang_giao");transition(oid,"dang_giao","hoan_thanh")
    check(scalar("SELECT trang_thai FROM don_hang WHERE id_don_hang=?",[oid])=="dang_giao","reject unpaid completion")
    transition(oid,"dang_giao","hoan_thanh",mark_paid=1)
    check(scalar("SELECT trang_thai FROM don_hang WHERE id_don_hang=?",[oid])=="hoan_thanh","paid completion")
    check("597.000 ₫" in admin.get("admin/index.php")[1],"dashboard paid revenue")
    transition(oid,"hoan_thanh","da_huy")
    check(scalar("SELECT trang_thai FROM don_hang WHERE id_don_hang=?",[oid])=="hoan_thanh","completed order cannot cancel")
    c.post("cart/add.php",{"id_bien_the":2,"so_luong":2});form=checkout(c);form["phuong_thuc_thanh_toan"]="Chuyển khoản"
    c.post("orders/checkout.php",form);cancel=int(scalar("SELECT MAX(id_don_hang) FROM don_hang"))
    check("FASHION "+str(cancel) in c.get(f"orders/detail.php?id={cancel}")[1],"bank transfer instructions")
    transition(cancel,"cho_xu_ly","da_xac_nhan",mark_paid=1);transition(cancel,"da_xac_nhan","da_huy")
    check(int(scalar("SELECT so_luong_ton FROM bien_the_san_pham WHERE id_bien_the=2"))==30,"cancellation restores stock")
    transition(cancel,"da_xac_nhan","da_huy");transition(cancel,"da_huy","da_huy")
    check(int(scalar("SELECT so_luong_ton FROM bien_the_san_pham WHERE id_bien_the=2"))==30,"repeated cancellation does not restore twice")
    check(scalar("SELECT trang_thai_thanh_toan FROM don_hang WHERE id_don_hang=?",[cancel])=="can_hoan_tien","paid cancellation tracks refund")
    transition(cancel,"da_huy","da_huy",mark_refunded=1)
    check(scalar("SELECT trang_thai_thanh_toan FROM don_hang WHERE id_don_hang=?",[cancel])=="da_hoan_tien","refund confirmation")
    check("597.000 ₫" in admin.get("admin/index.php")[1],"refunded orders excluded from revenue")
    # Two separate sessions compete for the final unit.
    db("UPDATE bien_the_san_pham SET so_luong_ton=1 WHERE id_bien_the=3")
    for client in (c,other):client.post("cart/add.php",{"id_bien_the":3,"so_luong":1})
    forms=[checkout(c),checkout(other)];before=int(scalar("SELECT COUNT(*) FROM don_hang"))
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        list(pool.map(lambda pair:pair[0].post("orders/checkout.php",pair[1]),zip((c,other),forms)))
    check(int(scalar("SELECT COUNT(*) FROM don_hang"))==before+1 and int(scalar("SELECT so_luong_ton FROM bien_the_san_pham WHERE id_bien_the=3"))==0,"concurrent final-unit checkout cannot oversell")
    admin.post("account/manage.php",{"action":"create","ho_ten":"New Staff","email":"new-staff@example.test","mat_khau":"NewStaff2026!","vai_tro":"nhan_vien"})
    check(scalar("SELECT vai_tro FROM nguoi_dung WHERE email='new-staff@example.test'")=="nhan_vien","admin creates staff")
    admin.post("account/manage.php",{"action":"update","id":1,"vai_tro":"khach_hang","trang_thai":0})
    check(scalar("SELECT vai_tro FROM nguoi_dung WHERE id_nguoi_dung=1")=="admin","admin cannot lock/demote self")
    admin.post("account/manage.php",{"action":"update","id":2,"vai_tro":"khach_hang","trang_thai":1})
    check(staff.get("products/add.php")[0]==403,"role updates revoke existing session permissions")
    admin.post("account/manage.php",{"action":"update","id":4,"vai_tro":"khach_hang","trang_thai":0})
    check("account/login.php" in other.get("cart/index.php")[2],"locked account session revoked")
    c.post("account/logout.php",{})
    check("account/login.php" in c.get("cart/index.php")[2],"logout destroys session")
    escaped=Client(base)
    escaped.post("account/login.php",{"email":"registered@example.test","mat_khau":"Registration2026"},token=escaped.token("account/login.php"))
    body=escaped.get()[1]
    check("&lt;script&gt;alert(1)&lt;/script&gt;" in body and "<script>alert(1)</script>" not in body,"stored name escaped")
    (ROOT/"images/products"/filename).unlink(missing_ok=True)

def migration_suite():
    db(action="reset",schema="legacy")
    db("INSERT INTO nguoi_dung (ho_ten,email,mat_khau,vai_tro) VALUES ('Legacy','legacy@example.test','hash','customer')")
    db("INSERT INTO gio_hang (id_nguoi_dung) VALUES (1),(1)")
    db("INSERT INTO chi_tiet_gio_hang (id_gio_hang,id_bien_the,so_luong) VALUES (1,1,2),(1,1,3),(2,1,1)")
    db("INSERT INTO don_hang (id_nguoi_dung,ho_ten_nhan,so_dien_thoai,dia_chi_giao_hang,tong_tien,trang_thai) VALUES (1,'Legacy','0901234567','12 Hanoi',199000,'Hoàn thành')")
    db("INSERT INTO chi_tiet_don_hang (id_don_hang,id_bien_the,so_luong,don_gia,thanh_tien) VALUES (1,1,1,199000,199000)")
    cli("database/migrate.php");cli("database/migrate.php")
    check(scalar("SELECT vai_tro FROM nguoi_dung WHERE id_nguoi_dung=1")=="khach_hang","migration role normalization and rerun")
    check(int(scalar("SELECT COUNT(*) FROM gio_hang"))==1 and int(scalar("SELECT so_luong FROM chi_tiet_gio_hang"))==6,"migration merges duplicate carts preserving quantity")
    check(scalar("SELECT ten_san_pham FROM chi_tiet_don_hang WHERE id_don_hang=1")=="Ao Thun Basic","migration snapshots existing order")
    check(scalar("SELECT trang_thai FROM don_hang WHERE id_don_hang=1")=="hoan_thanh","migration normalizes state")
    check(scalar("SELECT trang_thai_thanh_toan FROM don_hang WHERE id_don_hang=1")=="chua_thanh_toan" and scalar("SELECT ngay_hoan_thanh FROM don_hang WHERE id_don_hang=1") is None,"migration does not invent payment/date")

if __name__=="__main__":
    db(action="reset",schema="fresh");db(action="seed")
    with server() as base:suite(base)
    migration_suite()
    print(f"SUCCESS: {checks} checks passed.",flush=True)
