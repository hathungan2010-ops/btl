<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$name=getenv('DB_NAME')?:'';$host=getenv('DB_HOST')?:'127.0.0.1';
if(!preg_match('/^[a-zA-Z0-9_]+_test$/D',$name)||!in_array($host,['127.0.0.1','localhost'],true)){fwrite(STDERR,"Chỉ dùng CSDL cục bộ có hậu tố _test.\n");exit(1);}
try{
    $pdo=new PDO('mysql:host='.$host.';port='.(getenv('DB_PORT')?:'3306').';charset=utf8mb4',getenv('DB_USER')?:'root',getenv('DB_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $r=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
    if(($r['action']??'')==='reset'){
        $pdo->exec("DROP DATABASE IF EXISTS $name");$pdo->exec("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$pdo->exec("USE $name");
        $file=($r['schema']??'fresh')==='legacy'?'databases/fashion_store.sql':'database/fashion_store.sql';
        $sql=file_get_contents(__DIR__.'/../'.$file);$sql=preg_replace('/CREATE DATABASE IF NOT EXISTS fashion_store.*?;/s','',$sql);$sql=str_replace('USE fashion_store;','',$sql);
        $pdo->exec($sql);echo json_encode(['ok'=>true]);exit;
    }
    $pdo->exec("USE $name");
    if(($r['action']??'')==='seed'){
        foreach(['admin','nhan_vien','khach_hang','khach_hang'] as $i=>$role){$stmt=$pdo->prepare('INSERT INTO nguoi_dung (ho_ten,email,mat_khau,vai_tro) VALUES (?,?,?,?)');$stmt->execute(['Test User '.($i+1),'user'.($i+1).'@example.test',password_hash('Testing-Store-2026',PASSWORD_DEFAULT),$role]);}
        echo json_encode(['ok'=>true]);exit;
    }
    $stmt=$pdo->prepare($r['sql']);$stmt->execute($r['params']??[]);
    echo json_encode($stmt->columnCount()?$stmt->fetchAll():['affected'=>$stmt->rowCount(),'id'=>$pdo->lastInsertId()]);
}catch(Throwable $ex){fwrite(STDERR,$ex->getMessage()."\n");exit(1);}
