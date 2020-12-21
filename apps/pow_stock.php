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

        $ql = QueryList::get($this->reqUrl);

        // 流入排行榜
        $inMoneys = $ql->range('.news-main > .news-box:eq(0) table .rank-tr')->rules([
            'stock_code' => ['td:eq(1) > a', 'href'],
            'stock_name' => ['td:eq(1) > a', 'text'],
            'flow_amount' => ['td:eq(2)', 'text']
        ])->queryData();

        // 流出排行榜
        $outMoneys = $ql->range('.news-main > .news-box:eq(1) table .rank-tr')->rules([
            'stock_code' => ['td:eq(1) > a', 'href'],
            'stock_name' => ['td:eq(1) > a', 'text'],
            'flow_amount' => ['td:eq(2)', 'text']
        ])->queryData();
        $moneys = array_merge($inMoneys, $outMoneys);
        $moneys = array_map(function($row){
            $amount = trim($row['flow_amount'], '亿');

            $row['amount'] = $amount;
            unset($row['flow_amount']);

            $type = $amount < 0 ? self::IO_OUT : self::IO_IN;
            $row['type'] = $type;

            $row['stock_code'] = $this->processStockCode($row['stock_code']);
            $row['dated_at'] = date('Y-m-d H:i');
            return $row;
        }, $moneys);
        $this->dbCli->insert('in_out_stocks', $moneys);
        

        // 雪球热度
        $hotStocks = $ql->range('.news-main > .news-box:eq(9) table .rank-tr')->rules([
            'stock_code' => ['td:eq(1) > a', 'href'],
            'stock_name' => ['td:eq(1) > a', 'text']
        ])->queryData();
        $hotStocks = array_map(function($row){
            $row['platform'] = 'xq';
            $row['stock_code'] = $this->processStockCode($row['stock_code']);
            $row['dated_at'] = date('Y-m-d H:i');
            return $row;
        }, $hotStocks);
        $this->dbCli->insert('hot_stocks', $hotStocks);   

        echo 'finished'.PHP_EOL;     
    }

    /**
     * 处理地址：https://xueqiu.com/S/SZ000651，取得股票代码
     * @return string
     */
    private function processStockCode($url)
    {
        list($xqUrl, $code) = explode("/S/", $url);
        return preg_replace('/[SH|SZ]/', '', $code);
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