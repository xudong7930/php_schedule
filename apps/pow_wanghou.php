<?php
// 网猴线报
require 'vendor/autoload.php';
require 'library/sendDingMessage.php';

use QL\QueryList;
use Predis\Client;


(new PowWanghou)->run();

class PowWanghou
{
    public $baseUrl = 'https://iehou.com';
    public $qlCli;
    public $redisCli;
    public $content_url = 'http://ehd4.f3322.net/php_schedule/views/index.php?id=';

    // 忽略关键词
    public $ignored = [
        '京东','苏宁', '包邮','滴滴', '京豆', '小米','神器', 'QQ', 'Q币', 'QB',
        '电影推荐','知乎','爱奇艺', '保税','饿了么','翼支付', '支付宝积分', '螺蛳粉',
        '滴滴','金葵花','封面', '淘宝', '值得买', '电影','旗舰店', '联通', '沃钱包', 
        '购物券', '天翼', '光大', '美团', '饿了么'
    ];

    public function __construct()
    {
        $this->redisCli = new Client([
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => 'xudong7930'
        ]);      
        $this->qlCli = new QueryList;
    }

    public function run()
    {
        $reqUrl = $this->baseUrl . '?t='.time();
        $ql = $this->get_remote($reqUrl);

        $result = $ql->range('ul.list-group .list-group-item')->rules([
            'title' => ['a.mr-1', 'text'],
            'date_at' => ['span', 'text', '', function($val){
                return date('Y').'-'.$val;
            }],
            'href' => ['a.mr-1', 'href']
        ])->queryData();

        $xianbaos = [];
        foreach($result as $item) {

            // 是否忽略
            preg_match("/[".implode("|", $this->ignored)."]{2,}/u", $item['title'], $matched);
            if( count($matched) ) {
                continue;
            }

            // redis中判断是否存在
            list($url, $id) = explode('-', trim($item['href'], '.htm'));
            $key = 'wh_' . $id;
            if($this->redisCli->exists($key)) {
                continue;
            }

            // 组合数据
            $reqUrl = $item['href'];
            $ql2 = $this->get_remote($reqUrl);
            $content = $ql2->find('.thread-body .thread-content')->text();
            $content = str_replace('\t', '', trim($content));
            if (empty($content)) {
                continue;
            }

            $content = str_replace("(adsbygoogle = window.adsbygoogle || []).push({});", '', $content);
            $content = preg_replace("/[[:blank:]|\n]+/", " ", $content);

            $content_html = $ql2->find('.thread-body .thread-content')->html();
            $content_html = preg_replace("/<script[\s\S]*?<\/script>/i", '', $content_html);
            $content_html = preg_replace("/<ins[\s\S]*?<\/ins>/i", '', $content_html);

            $xianbaos[$key] = [
                'title' => $item['title'],
                'content' => $content,
                'content_html' => $content_html,
                'url' => $reqUrl,
                'url_vultr' => $this->content_url . $key
            ];

            // 添加到redis
            $this->redisCli->set($key, json_encode($xianbaos[$key], JSON_UNESCAPED_UNICODE), 'EX', 86400);
        }

        if ($xianbaos) {
            (new SendDingMessage)->send_message($xianbaos);
        }

        echo 'finished'.PHP_EOL;
    }

    protected function get_remote($url)
    {
        return $this->qlCli->get($url);
    }
}
