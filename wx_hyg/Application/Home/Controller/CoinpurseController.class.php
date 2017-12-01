<?php
namespace Home\Controller;

use Think\Controller;


class CoinpurseController extends MobileController{

	public function _initialize() {
		parent::_initialize();
	}

	/**
	 * 零钱宝首页
	 */
	public function index()
	{
		$userinfo = D('lqbuser')->getUserInfo(session('openid'));
		if(!$userinfo){
			header("Location:http://www.lianshengit.com/ls2014/mindex.aspx?page=register");
			exit();
		}
		$act = M('lqbsetact')->find();
		$list = M('lqbsetamt')->where(array('is_open'=>1))->order('sort asc')->select();
		$this->assign('list',$list);
		$this->assign('userinfo',$userinfo);
		$this->assign('act',$act);
		$this->display();
	}

	/**
	 * 零钱明细
	 */
	public function payDetail()
	{
		$detail = D('lqbuser')->payRecord(session('openid'));
		if(!$detail){
			$this->error('网络异常，请刷新重试！');
		}
		$this->assign('username',session('user.p_ref_name'));  //用户姓名
		$this->assign('detail',$detail['list']);
		//dump($detail['list']);exit;
		$this->display();
	}
	/**
	 * 创建订单91721704061601
	 */
	public function createOrder()
	{

		$amount = trim(I('amount'));
		if($amount > 20000){

			$ret['orderid'] = '';
			$ret['status'] = 2;
			$this->ajaxReturn($ret);
		}
		if($amount < 20){

			$ret['orderid'] = '';
			$ret['status'] = 3;
			$this->ajaxReturn($ret);
		}
		$orderId = self::_getRandomStr(10).date("YmdHis",time());
		if($orderId && $amount){
			$data['orderid'] = $orderId;
			$data['amount'] = $amount;
			S($orderId,$data,300);
			$res = D('lqbuser')->insertPatOrder($data);
			if($res){
				$ret['orderid'] = $orderId;
				$ret['status'] = 1;
				$this->ajaxReturn($ret);
			}else{
				$ret['orderid'] = $orderId;
				$ret['status'] = 0;
				$this->ajaxReturn($ret);
			}

		}else{
			$ret['orderid'] = $orderId;
			$ret['status'] = 0;
			$this->ajaxReturn($ret);
		}

	}

	public function pay()
	{
		$this->display();
	}


	/**
	 * 获取用户的零钱宝基础信息
	 */
	public function getUser()
	{
		$key = 'test';
        $params = array(
            "id"        => "oUBPqjhOAJqyCnvf5n4WOL4JfTyw",
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 3,

        );
        $url            = 'http://218.87.88.2:8083/jjlsws/CustomWeiChatSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = $this->http($url,$params);
        echo($res);
       // dump(json_decode($res, true));
	}

	public function qrcode()
	{

		$key = '01';
		//str := 'test' || vwxid || vtimestamp_str || vappid || 'test';
		$cardno = substr(md5(time()),0,18);  //随机数
		$cardno = 'test'.'||'.session('user.p_ref_mobile').'||'.date("Ymd").'||'.'01'.'||'.'test';
		$ischeck = M('lqbqrcode')->where(array('openid'=>session('openid')))->find();
		if(empty($ischeck)){
			$add['openid'] = session('openid');
			$add['code'] = $cardno;
			$add['userdata'] = json_encode(session('user'));
			$add['exp_time'] = time()+60;
			$addcode = M('lqbqrcode')->add($add);
		}else{

			$save['code'] = $cardno;
			$save['exp_time'] = time()+60;
			$savecode = M('lqbqrcode')->where(array('openid'=>session('openid')))->save($save);
		}
		$this->assign('cardno',$cardno);
		///dump($cardno);die();
		$this->display();
	}



    public function shouyi()
    {

    	$code = 38;
		$page = 1;  //第一页
    	$list = D('lqbuser')->payRecord(session('openid'),$code,$page);
		if(!$list){
			$this->error('网络异常，请刷新重试！');
		}

		$this->assign('username',session('user.p_ref_name'));  //用户姓名

		$this->assign('list',$list);
		$this->display();
    }

    public static function _createSign($params = array(), $key = '')
    {
    	$str = '';
        ksort($params);
        foreach ($params as $k => $v) {
        	$str.= $k.$v;
        }
        //return MD5(http_build_query($params) . '&key=' . $key);
        return strtoupper(MD5($key.$str.$key));
    }



    /**
	 * 发送HTTP请求方法，目前只支持CURL发送请求
	 * @param  string $url    请求URL
	 * @param  array  $params 请求参数
	 * @param  string $method 请求方法GET/POST
	 * @return array  $data   响应数据
	 *
	 */
	private function http($url, $params = array(), $method = 'GET'){
		$opts = array(
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false
		);
		/* 根据请求类型设置特定参数 */
		switch(strtoupper($method)){
			case 'GET':
				$opts[CURLOPT_URL] = $url .'?'. http_build_query($params);
				break;
			case 'POST':
				$opts[CURLOPT_URL] = $url;
				$opts[CURLOPT_POST] = 1;
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
		}
		/* 初始化并执行curl请求 */
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data  = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		curl_close($ch);
		if ($err > 0) {
			$this->error = $errmsg;
			return false;
		}else {
			return $data;
		}
	}

    /**
	 * 返回随机填充的字符串
	 */
	private static function _getRandomStr($lenght = '')	{
		$str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);
	}
}