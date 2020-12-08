<?php
// 买买买羊毛线报
require 'vendor/autoload.php';
require 'library/sendDingMessage.php';

use QL\QueryList;
use Predis\Client;

$keywords = ['红包','现金', '元', '微信', '招行', "有水", "大水", "小程序", "话费"];
$targetUrls = [
    'https://just998.com/xianbao?s=zhaohang',
    // 'https://just998.com/xianbao?s=huafei',
    // 'https://just998.com/xianbao?s=baoshui',
    // 'https://just998.com/xianbao?s=vxhb',
    // 'https://just998.com/xianbao?s=miling'
];

$redis = new Client([
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => 'xudong7930'
]);

$data = array();

$ql = new QueryList;
foreach($targetUrls as $targetUrl) {
    $siteObject = $ql->get($targetUrl);
    $siteObject->find('.xianbao-item .xianbao-title')
    	->map(function($el) use(&$data, $targetUrl, $keywords) {
            $link = $el->find('a');
            $link_href = $link->attr('href');
            $link_title = $link->attr('title');

            $a = preg_split('/\.html/', $link_href);
            list($flag, $id) = explode("/", trim($a[0], '/'));
            $key = 'mmm_' . $id;

            // 非今天日期的羊毛则跳过
            $allowedKeyWords = ['小时前', '分钟', '刚刚'];
            $publish_date = $el->find('p.mt-5 small.mr-5')->text();
            $isAllowed = false;
            foreach($allowedKeyWords as $allowdKeyWord) {
                if (strpos($publish_date, $allowdKeyWord) !== false) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                return ;
            }

            // 判断是否有关键词
            $hasKeyword = false;
            foreach($keywords as $kw) {
                if (strpos($link_title, $kw) !== false) {
                    $hasKeyword = true;
                    break;
                }
            }
            if (!$hasKeyword) {
                return ;
            }

            $data[$key] = [
                'url' => "https://just998.com".$link_href,
                'title' => $link_title,
            ];
    });
}

$newYangmaos = [];
foreach($data as $key=>$yangmao) {
    $isExist = $redis->exists($key);
    if (!$isExist) {
        $newYangmaos[] = $yangmao;
        $redis->set($key, json_encode($yangmao), 'EX', 86400);
    }
}

if ($newYangmaos) {
    (new SendDingMessage)->send_message($newYangmaos);
}