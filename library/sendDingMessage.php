<?php

require 'vendor/autoload.php';

use Predis\Client as RedisClient;
use GuzzleHttp\Client as HttpClient;

class SendDingMessage {
	var $agentId = '324100974';
	var $appKey = 'dingq8oy6kiymvdlpuan';
	var $appSecret = 'ayVMEIsdTs7LuTWUkT_S471j5SdXIb9zJgS1-nQr4Prgza45PZl29NDhPFtSX3C_';
	var $userIds = ['manager7813'];
	var $redisCli;
	var $httpCli;

	public function __construct()
	{
		$this->httpCli = new HttpClient(['verify'=>false]);
		$this->redisCli = $redis = new RedisClient([
			'host' => '127.0.0.1',
			'port' => 6379,
			'password' => 'xudong7930'
		]);
	}

	/**
	 * 取得token
	 */
	private function get_access_token()
	{
		$token = $this->redisCli->get('ding_access_token');
		if (!$token) {
			$response = $this->httpCli->request('GET', 'https://oapi.dingtalk.com/gettoken', [
				'query' => [
					'appkey' => $this->appKey, 
					'appsecret' => $this->appSecret
				]
			]);
			$content = $response->getBody()->getContents();
			$content = json_decode($content, true);
			$token = $content['access_token'];
			$this->redisCli->set('ding_access_token', $token, 'EX', 7000);
		}
		return $token;
	}

	public function send_message(array $messages)
	{
		if (empty($messages)) {
			return ;
		}

		$token = $this->get_access_token();
		$reqUrl = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2';

		foreach($messages as $message) {
			$response = $this->httpCli->request('POST', $reqUrl, [
				'query' => ['access_token' => $token],
				'json' => [
					'agent_id' => $this->agentId,
					'userid_list' => implode(',', $this->userIds),
					'msg' => [
						'msgtype' => 'oa',
						'oa' => [
							'head' => ['bgcolor'=>'#ccc','title'=>'羊毛来了!'],
							'body' => [
								'title' => $message['title'],
								'content' => isset($message['content']) ? $message['content'] : ''
							],
							"message_url" => $message['url_vultr'],
							"pc_message_url" => $message['url_vultr']
						]
					]
				]
			]);
			echo $response->getBody()->getContents();
			sleep(mt_rand(2,5));
		}
	}
}

