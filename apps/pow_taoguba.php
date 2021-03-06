<?php

require 'vendor/autoload.php';
require 'library/sendEmailMessage.php';

use Medoo\Medoo;
use GuzzleHttp\Client as HttpClient;
use QL\QueryList;


(new PowTaoguba)->run();
class PowTaoguba
{
    /**
     * @var GuzzleHttp\Client
     */
    public $httpCli;

    /**
     * @var Medoo\Meddo
     */
    public $dbCli;

    /**
     * @var QL\QueryList
     */
    public $qlCli;
    public $mailCli;

    public function __construct()
    {
        $this->httpCli = new HttpClient;
        $this->dbCli = new Medoo([
            'database_type' => 'sqlite',
            'database_file' => $this->get_db_file()
        ]);
        $this->qlCli = new QueryList;
        $this->mailCli = new SendEmailMessage;
    }

    public function run()
    {
        list($title, $blogUrl) = $this->fetchBlogUrl();
        if(!$blogUrl) {
            return false;
        }

        $content = $this->fetchBlogContent($blogUrl);
        if($content) {
            $this->mailCli->run([
                'subject' => $title,
                'to' => 'xudong7930@dingtalk.com',
                'content' => $content,
                'content_type' => 'text/html'
            ]);
        }
        echo 'finished!' . PHP_EOL;
    }

    public function fetchBlogContent($reqUrl)
    {
        $result = $this->qlCli->get($reqUrl);
        $content = $result->find('.p_coten')->html();
        return $content;
    }

    // 取得最新的文章
    public function fetchBlogUrl()
    {
        $reqUrl = "https://www.taoguba.com.cn";

        $el = $this->qlCli->get($reqUrl . '/blog/2335222')->find('.all_right .allblog_article .article_tittle:eq(0)');
        
        $href = $el->find('a')->attr('href');
        $title=  $el->find('a')->text();

        $blogUrl = $reqUrl . '/' . $href;
        return [$title, $blogUrl];
    }

    protected function get_db_file()
    {
        $current = dirname(__FILE__);
        $sqlFile = realpath($current . '/../database.sqlite');
        return $sqlFile;
    }
}
