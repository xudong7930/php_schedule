<?php

require 'vendor/autoload.php';
require 'library/sendDingMessage.php';

use Medoo\Medoo;

(new HotStockNotify)->run();

class HotStockNotify
{
    public $dbCli;

    public function __construct()
    {
        $current = dirname(__FILE__);
        $sqlFile = realpath($current . '/../database.sqlite');
        $this->dbCli = new Medoo([
            'database_type' => 'sqlite',
            'database_file' => $sqlFile
        ]);
    }

    public function run()
    {

        $message_data = [];

        // 资金流入流出统计
        $sql = "select stock_name, count(*) as total from in_out_stocks group by stock_name order by total desc limit 10";
        $result = $this->dbCli->query($sql)->fetchAll();

        $message = "";
        foreach($result as $stock) {
            $message .= "名称: ".$stock['stock_name'] .", 次数: ". $stock['total'] . "\n";
        }
        $title = date("Y-m-d")."资金流入流出统计";
        $message_data[] = ['title'=>$title, 'content'=>$message, 'url'=>''];

        // 热门股
        $sql = "select stock_name, count(*) as total from hot_stocks group by stock_name order by total desc limit 10";
        $result = $this->dbCli->query($sql)->fetchAll();
        $title = date('Y-m-d')."热门股";
        $message = '';
        foreach($result as $stock_item) {
            $message .= "名称: ".$stock_item['stock_name'] . ", 次数: ".$stock_item['total'] . "\n";
        }
        $message_data[] = ['title'=>$title, 'content'=>$message, 'url'=>''];

        (new SendDingMessage())->send_message([$message_data]);

        echo 'finished!'.PHP_EOL;
    }
}