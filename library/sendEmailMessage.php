<?php

use Predis\Command\SetUnionStore;

require 'vendor/autoload.php';



class SendEmailMessage
{
    public $mailCli;

    public $mailConf = [
        'smtp_host' => 'smtp.qq.com',
        'smtp_host_port' => 25,
        'user' => '1046457211@qq.com',
        'password' => 'cspbmfeveagwbfgd',
    ];

    public function __construct()
    {
        $this->mailCli = new Swift_Mailer(
            (new Swift_SmtpTransport(
                $this->mailConf['smtp_host'], 
                $this->mailConf['smtp_host_port']
            ))
            ->setUsername($this->mailConf['user'])
            ->setPassword($this->mailConf['password'])
        );
    }

    /**
     * 发配邮件
     */
    public function run($mail)
    {
        $message = (new Swift_Message($mail['subject']))
            ->setFrom($this->mailConf['user'])
            ->setTo($mail['to'])
            ->setBody($mail['content'], $mail['content_type']);
        $response = $this->mailCli->send($message);
        var_dump($response);
    }
}