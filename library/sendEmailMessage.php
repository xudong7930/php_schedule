<?php

use Predis\Command\SetUnionStore;

require 'vendor/autoload.php';



class SendEmailMessage
{
    public $mailCli;

    public function __construct()
    {
        $this->mailCli = new Swift_Mailer(
            (new Swift_SmtpTransport('smtp.qq.com', 25))
                ->setUsername('1046457211@qq.com')
                ->setPassword('cspbmfeveagwbfgd')
        );

    }

    /**
     * 发配邮件
     */
    public function run($mail)
    {
        $message = (new Swift_Message($mail['subject']))
            ->setFrom('1046457211@qq.com', 'xd100')
            ->setTo($mail['to'])
            ->setBody($mail['content']);

        $response = $this->mailCli->send($message);
        var_dump($response);
    }
}