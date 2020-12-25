<?php
// 买买买羊毛线报
require '../vendor/autoload.php';


$redisCli = new Predis\Client([
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => 'xudong7930'
]);      

$key = isset($_GET['id']) ? $_GET['id'] : '';
if (!$key) {
    echo 'no key';exit();
}

$val = $redisCli->get($key);
$val_arr = json_decode($val, true);
$content = $val_arr['content_html'] . "<br/> 原地址: <a href='".$val_arr['url']."' target='_blank'>".$val_arr['url']."</a>" ."<br/> Vultr: <a href='".$val_arr['url_vultr']."' target='_blank'>".$val_arr['url_vultr']."</a>";

header('Content-Type: text/html');
echo $content;  