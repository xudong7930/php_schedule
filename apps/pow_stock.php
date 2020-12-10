<?php

require 'vendor/autoload.php';

use Medoo\Medoo;
use GuzzleHttp\Client as HttpClient;
use QL\QueryList;


(new PowStock)->run();

class PowStock {

    /**
     * 资金流入
     * @var int
     */
    const IO_IN = 1;
    
    /**
     * 资金流出
     * @var int
     */
    const IO_OUT = 2;

    /**
     * @var string
     */
    public $reqUrl = "https://upsort.com/s/rank";

    /**
     * @var GuzzleHttp\Client
     */
    public $httpCli;

    /**
     * @var Medoo\Meddo
     */
    public $dbCli;

    public function __construct()
    {
        $current = dirname(__FILE__);
        $sqlFile = realpath($current . '/../database.sqlite');

        $this->httpCli = new HttpClient;
        $this->dbCli = new Medoo([
            'database_type' => 'sqlite',
            'database_file' => $sqlFile
        ]);
    }

    public function run()
    {
        $content = $this->fetchRemoteContent();

        // 个股资金流入榜单
        $flowInStocks = $this->processQuery($content, 0);
        $flowInStocks = array_map(function($item){
            $item['type'] = self::IO_IN;
            $item['dated_at'] = date('Y-m-d H:i');
            return $item;
        }, $flowInStocks);
        $this->dbCli->insert('in_out_stocks', $flowInStocks);
        
        // 个股资金流出榜单
        $flowOutStocks = $this->processQuery($content, 1);
        $flowOutStocks = array_map(function($item){
            $item['type'] = self::IO_OUT;
            $item['dated_at'] = date('Y-m-d H:i');
            return $item;
        }, $flowOutStocks);
        $this->dbCli->insert('in_out_stocks', $flowOutStocks);

        // 雪球24小时关注股 
        $hotStocks = $this->processQuery($content, 9);
        $hotStocks = array_map(function($item){
            unset($item['amount']);
            $item['platform'] = 'xq';
            // $item['dated_at'] = date('Y-m-d H:i');
            return $item;
        }, $hotStocks);
        $this->dbCli->insert('hot_stocks', $hotStocks);   
        echo 'finished'.PHP_EOL;     
    }

    // 取得远程内容
    private function fetchRemoteContent()
    {
        $ql = new QueryList;
        $result = $ql->get($this->reqUrl);
        return $result->find('.news-main');
    }

    // 处理html内容
    public function processQuery($content, $i)
    {
        $data = [];
        $content->find(".news-box:eq({$i}) tr")->map(function($tr)use(&$data){
            $el_a = $tr->find("td>a");
            $stock_name = $el_a->text();
            $el_a_href = $el_a->attr('href');

            list($link, $stock_code) = explode("/S/", $el_a->attr('href'));
            $stock_code = substr($stock_code, 2);
            $amount = $tr->find('td:eq(2)')->text();
            $amount = trim($amount, '亿');

            $data[] = array(
                'stock_name' => $stock_name,
                'stock_code' => $stock_code,
                'amount' => $amount
            );
        });

        return $data;
    }

}


/**
CREATE TABLE "hot_stocks" (
	"id" integer, 
	"stock_name" varchar(255),
	"stock_code" varchar(6),
	"platform" tinyint(1),
	"dated_at" varchar(20),
	PRIMARY KEY (id)
);

CREATE TABLE "in_out_stocks" (
	"id" integer, 
	"stock_name" varchar(255),
	"stock_code" varchar(6),
	"type" tinyint(1),
	"amount" num,
	"dated_at" varchar(20),
	PRIMARY KEY (id)
);

CREATE TABLE "stocks" (
	"id" integer, 
	"stock_name" varchar(255),
	"stock_code" varchar(6),
	PRIMARY KEY (id)
);
*/