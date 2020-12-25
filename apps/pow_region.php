<?php

require 'vendor/autoload.php';

use Medoo\Medoo;
use GuzzleHttp\Client as HttpClient;
use QL\QueryList;

(new PowRegion)->run();
class PowRegion 
{
    public $base_url = "http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2020";

    public $qlCli;

    public function __construct()
    {
        $this->qlCli = new QueryList;    
    }

    public function run()
    {
        // 获取省
        $req_url = $this->base_url . '/index.html';
        $provinces = $this->get_remote_content($req_url, '.provincetr a');

        $lines = [];
        foreach($provinces as $province) {

            $lines[] = $province['region_code'] . "    " .$province['region_name'] ;
            $city_data = $this->get_remote_content($province['req_url'], '.citytr');
            
            foreach($city_data as $city) {
                echo $province['region_name']."_".$city['region_name'] .PHP_EOL;

                $lines[] = $city['region_code'] . "    "  . $city['region_name'];
                $area_data = $this->get_remote_content($city['req_url'], '.countytr');
                foreach($area_data as $area) {
                    $lines[] = $area['region_code'] . "    "  . $area['region_name'];
                }
                sleep(mt_rand(0,1));
            }
        }
        if($lines) {
            $this->write_content($lines);
        }
        echo "finished";
    }

    // 文件写入
    private function write_content($lines)
    {
        $region_file = realpath(dirname(__FILE__). "/../region.txt");
        $file = fopen($region_file, 'w');
        foreach($lines as $line) {
            fwrite($file, $line."\n");
        }
        fclose($file);
    }

    // 处理数据
    private function process_data($html, $class_name)
    {
        $data = [];
        $this->qlCli->html($html)->find($class_name)->map(function($el)use(&$data, $class_name){
            if ($class_name == '.provincetr a') {
                list($region_code,$ext) = explode('.',$el->attr('href'));
                $region_name = $el->text();
                $href = $el->attr('href');
            } else {
                $region_code = $el->find('td:eq(0)')->text();
                $region_name = $el->find('td:eq(1)')->text();
                $href = $el->find('td:eq(0) a')->attr('href');
            }

            if(strlen($region_code) > 6) {
                $region_code = substr($region_code, 0, 6);
            } else {
                $repeat_times = 0;
                $repeat_times = 6-strlen($region_code);
                $region_code = $region_code . str_repeat('0', $repeat_times);
            }

            $req_url = '';
            if($href) {
                $req_url = $this->base_url . '/' . $href;
            }

            $data[] = [
                'region_code' => trim($region_code),
                'region_name' => trim($region_name),
                'req_url' => $req_url
            ];
        });
        return $data;
    }

    // 取得远程数据
    public function get_remote_content($req_url, $class_name)
    {
        try {
            $html = iconv('GBK','UTF-8',file_get_contents($req_url));
            return $this->process_data($html, $class_name);
        } catch(Exception $e) {
            throw new Exception('拉取出错：'.$req_url);
        }
        
    }
}