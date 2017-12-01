<?php
namespace Home\Controller;

use Think\Controller;

vendor('sendmsm.Client');
class TestController extends Controller {

	public function index()
	{	

		global $client;
		$code  =  rand(1000,9999);
		$gwUrl = 'http://sdk999ws.eucp.b2m.cn:8080/sdk/SDKService';
		/**
		 * 序列号,请通过亿美销售人员获取
		 */
		$serialNumber = '9SDK-EMY-0999-REVQM';
		/**
		 * 密码,请通过亿美销售人员获取
		 */
		$password = '260792';
		/**
		 * 登录后所持有的SESSION KEY，即可通过login方法时创建
		 */
		$sessionKey = '260792';

		/**
		 * 连接超时时间，单位为秒
		 */
		$connectTimeOut = 2;

		/**
		 * 远程信息读取超时时间，单位为秒
		 */ 
		$readTimeOut = 10;

	
		$proxyhost = false;
		$proxyport = false;
		$proxyusername = false;
		$proxypassword = false; 

		$client = new \Client($gwUrl,$serialNumber,$password,$sessionKey,$proxyhost,$proxyport,$proxyusername,$proxypassword,$connectTimeOut,$readTimeOut);
		$client->setOutgoingEncoding("utf8");

		/**
		 * 下面的操作是产生随机6位数 session key
		 * 注意: 如果要更换新的session key，则必须要求先成功执行 logout(注销操作)后才能更换
		 * 我们建议 sesson key不用常变
		 */
		//$sessionKey = $client->getSessionKey();
		//$statusCode = $client->login($sessionKey);
		$mobile = array('15826571243');
		$res = $client->sendSMS($mobile,'【中免集团】你好啊啊啊啊');
		dump($res);
	}

	public function getAccessToken()
	{
		$data=file_get_contents('php://input', 'r'); 
		$this->log($data);
	}

	/**
     * 写入日志
     */
    private function log($data = '')
    {
        \Think\Log::write($data, 'INFO');
    }

}