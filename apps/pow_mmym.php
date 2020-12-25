<?php
// 买买买羊毛线报
require 'vendor/autoload.php';
require 'library/sendDingMessage.php';

use QL\QueryList;
use Predis\Client;


(new PowMmym)->run();

class PowMmym
{
    public $baseUrl = 'https://just998.com';
    public $contentUrl = 'http://ehd4.f3322.net/php_schedule/views/index.php?id=';
    public $qlCli;
    public $redisCli;
    public $keywords = ['红包','现金', '元', '微信', '招行', "有水", "大水", "小程序", "话费"];

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
        $reqUrl = $this->baseUrl . '/xianbao?s=zhaohang';
        $ql = $this->get_remote($reqUrl);
        $result = $ql->range('.xianbao-item .xianbao-title')->rules([
            'title' => ['a', 'attr(title)'],
            'date_at' => ['a .mr-5', 'text'],
            'href' => ['a', 'href']
        ])->queryData();
        
        $xianbaos = [];
        foreach($result as $item) {
            // 判断是否过期
            preg_match("/(分钟前|小时前|刚刚)/", $item['date_at'], $matched);
            if (empty($matched)) {
                continue;
            }


            // redis中判断是否存在
            $a = preg_split('/\.html/', $item['href']);
            list($flag, $id) = explode("/", trim($a[0], '/'));
            $key = 'mmm_' . $id;
            if($this->redisCli->exists($key)) {
                continue;
            }

            // 组合数据
            $reqUrl = $this->baseUrl . $item['href'];
            $ql2 = $this->get_remote($reqUrl);
            $content = $ql2->find('.panel-body .content')->text();
            $content = str_replace('\t', '', trim($content));
            if (empty($content)) {
                continue;
            }
            

            $content_html = $ql2->find('.panel-body .content')->html();
            $content_html = preg_replace("/href=\"/", "href=\"{$this->baseUrl}", $content_html);

            $xianbaos[$key] = [
                'title' => $item['title'],
                'content' => $content,
                'content_html' => $content_html,
                'url' => $reqUrl,
                'url_vultr' => $this->contentUrl . $key
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
