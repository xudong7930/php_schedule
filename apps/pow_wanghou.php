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
    public $keywords = ['红包','现金', '元', '微信', '招行', "有水", "大水", "小程序", "话费"];
    public $ignored = [
        '京东','苏宁', '包邮','滴滴', '京豆', '小米','神器', 'QQ', 'Q币', 'QB',
        '电影推荐','知乎','爱奇艺'
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
            preg_match("/(".implode("|", $this->ignored).")/", $item['title'], $matched);
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
            
            $xianbaos[$key] = [
                'title' => $item['title'],
                'content' => $content,
                'url' => $reqUrl
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
