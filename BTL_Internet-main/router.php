<?php
if(PHP_SAPI!=='cli-server'){http_response_code(404);exit;}
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/');
if(preg_match('~(^|/)(database|databases|includes|tests)(/|$)|(^|/)\.|(^|/)config(?:\.local(?:\.example)?)?\.php$|\.(sql|log|md|ini|env)$~i',$path)){
    http_response_code(404);echo 'Không tìm thấy trang.';return true;
}
return false;
