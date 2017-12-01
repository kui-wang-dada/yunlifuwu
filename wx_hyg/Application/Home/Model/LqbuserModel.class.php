<?php
/**
 * 零钱包会员信息
 */

namespace Home\Model;

use Think\Model;

/**
 * @package Home\Model
 */
class LqbuserModel extends Model{

	/**
	 * 获取会员信息，这个位置需要实时调用接口获取信息，数据库可以保存一份信息，请不要取数据库信息显示页面
	 */
	public function getUserInfo($openid = '')
	{
		$key = 'test';
        $params = array(
            "id"        => $openid,
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 3,
           
        );
        $url            = 'http://220.175.154.53:8085/jjlsws/DepositCustomWeiChatSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        if($res['refcode'] == '-1' && empty($res['p_ref_change_balance'])){
        	return false;
        }
        
        $res['p_dayprofit'] = number_format(round($res['p_dayprofit'],4),4);//取两位有效数
        $res['p_refprofit'] = number_format(round($res['p_refprofit'],4),4);//取两位有效数
        $res['p_ref_change_balance'] = number_format(round($res['p_ref_change_balance'],4),4);//取两位有效数
        session('user',$res);
        $this->insertUserData($res);  //保存用户数据
        return $res;
	}
	
	/**
	 * 获取会员信息，这个位置需要实时调用接口获取信息，数据库可以保存一份信息，请不要取数据库信息显示页面
	 */
	public function getUserInfo_test($openid = '')
	{
		$key = 'test';
        $params = array(
            "id"        => $openid,
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 3,
           
        );
        $url            = 'http://218.87.88.2:8083/jjlsws/DepositCustomWeiChatSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        if($res['refcode'] == '-1' && empty($res['p_ref_change_balance'])){
        	return false;
        }
        
        $res['p_dayprofit'] = number_format(round($res['p_dayprofit'],4),4);//取两位有效数
        $res['p_refprofit'] = number_format(round($res['p_refprofit'],4),4);//取两位有效数
        $res['p_ref_change_balance'] = number_format(round($res['p_ref_change_balance'],4),4);//取两位有效数
        session('user',$res);
        //$this->insertUserData($res);  //保存用户数据
        return $res;
	}
	

	/**
	 * 验证代充用户是否为会员
	 */

	public function checkUserinfo($mobile = '')
	{
		$key = 'test';
        $params = array(
            "id"        => $mobile,
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 1,
           
        );
        $url            = 'http://220.175.154.53:8085/jjlsws/DepositCustomWeiChatSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        if($res['refcode'] == '-1' && empty($res['p_ref_change_balance'])){
        	return false;
        }
        
        session('sc_user',$res);    //代充用户信息
        return $res;
	}

	/**
	 * 用户数据插入数据库或者更新
	 */
	public function insertUserData($userinfo  = array())
	{	
		$rules = array ( 
		    array('in_time','time',1,'function'),  // 新增的时候把字段设置为1
		    array('update_time','time',2,'function'), // 对update_time字段在更新的时候写入当前时间戳
		);
		$User = M('lqbuser');
		$User->auto($rules)->token(false)->create($userinfo);
		$ischck = $User->where(array('p_ref_cardno'=>$userinfo['p_ref_cardno']))->find();
		if(empty($ischck)){
			$User->add();
		}else{
			$User->save();
		}
		
	}

	/**
	 * 订单插入数据库
	 */
	public function insertPatOrder($orderinfo = array())
	{	
		$order['openid'] = session('openid');
		$order['out_trade_no'] = $orderinfo['orderid'];
		$order['total_fee'] = $orderinfo['amount'];
		$order['status'] = 'N';    //未支付
		$order['intime'] = time();
		$add = M('lqborder')->add($order);
		if($add){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 充值、消费、收益记录
	 */
	public function payRecord($openid = '',$code = '',$page='')
	{	
		
		if(empty($page)){
			$page = 1;
		}
		$key = 'test';
        $params = array(
            "id"        => $openid,
            "code"      => $code,
            "startNo"   => $page,
            "endNo"     => 100,
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 3,
           
        );
        $url            = 'http://220.175.154.53:8085/jjlsws/DepostidListSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        $this->log('查询充值记录返回:'.json_encode($res));
        if($res['refcode'] == '-1'){
        	
        	return false;
        }
        $list['list'] = $this->filterPayDeatil($res['p_list']);
        //$list['value'] = number_format(round($res['p_value'],2),2);//取两位有效数
        $list['sum'] = $this->paySumAmt($res['p_list']);
        return $list;
        //return $res['p_list'];
	}

	/**
	 * 过滤明细里面的收益列表，金额为0的不显示，小于0.01的不显示
	 * @param list  收益明细
	 */
	public function filterPayDeatil($list = array())
	{
		foreach ($list as $k => $v) {
			
			//$list[$k]['cdlmoney'] = number_format(round($list[$k]['cdlmoney'],2),2);
			if(round($list[$k]['cdlmoney'],4) == 0){
				unset($list[$k]);
			}
			
		}
        
		return $list;
	}

	/**
	 * 最近一个月收益求和
	 */
	public function paySumAmt($list = array())
	{
		foreach ($list as $k => $v) {
			
			$sum += $list[$k]['cdlmoney'];
			
		}
        
		//return number_format(round($sum,2),2);
		return $sum;
	}


	/**
	 * 零钱宝赠送
	 */
	public function lqbGive($data = array())
	{
		$key = 'test';
        $params = array(
        	"code"      => 64,
            "id"        => session('openid'),
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 3,
            "je"        => $data['total_fee'],
            "seqno"     => $data['out_trade_no']
            
        );
        $url            = 'http://218.87.88.2:8083/jjlsws/LqbSendSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        if($res['refcode'] == '-1'){
        	return $res;            //扣除失败
        }else{  //扣减成功

        	$data['openid'] = session('openid');
        	$data['toname'] = base64_encode($data['toname']);
        	$data['name'] = base64_encode($data['fromname']);
        	$data['head_img'] = session('userinfo.head_pic');
        	$data['crm_out_trade_no'] = $res['p_cdlseqno'];
        	$data['status'] = 'G';   //G赠送中，A赠送完成，T已退回，N失效订单
        	$data['intime'] = time();
        	$insert = M('lqbgiveorder')->add($data);
        	if($insert){
        		return $insert;
        	}else{
        		return false;
        	}
        }
	}

	/**
	 * 赠送查询
	 */
	public function lqbGiveFind($crm_out_trade_no = '')
	{
		$key = 'test';
        $params = array(
        	"cdlseqno"  => $crm_out_trade_no,
            "timestamp" => date("Y-m-d H:i:s")
        );
        $url            = 'http://218.87.88.2:8083/jjlsws/LqbSendFlagSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        return $res;
	}

	/**
	 * 零钱宝接收赠送
	 */
	public function lqbGiveReceive($value = array())
	{
		$key = 'test';
        $params = array(
        	"code"      => 65,
            "id"        => session('openid'),
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => 3,
            "cdlseqno"     => $value['crm_out_trade_no']  
        );
        $url            = 'http://218.87.88.2:8083/jjlsws/LqbReceiveSrv';
        $params['sign'] = $this->_createSign($params, $key);
        $res = json_decode($this->http($url,$params),true);
        if($res['refcode'] == '-1'){     //接收crm领取信息失败
        	$ret['refcode'] = '-1';
        	$ret['refinfo'] = $res['refinfo'];
        	return $ret;
        }else{

        	$data['status'] = 'A';   //已被领取
        	$data['is_receive'] = 'Y';
        	$data['receive_openid'] = session('openid');
        	$data['receive_name'] = base64_encode(session('userinfo.nickname'));
        	$data['receive_time'] = time();
        	$save = M('lqbgiveorder')->where(array('crm_out_trade_no'=>$value['crm_out_trade_no']))->save($data);
        	if($save !== false){
        		$ret['refcode'] = '1';
        		$ret['refinfo'] = '领取成功';
        		return $ret;
        	}else{
        		$ret['refcode'] = '-1';
        		$ret['refinfo'] = '领取失败，该订单更新失败';
        		return $ret;
        	}
        }
	}

	/**
	 * 签名信息
	 */
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
     * 写入日志
     */
    private function log($data = '')
    {
        \Think\Log::write($data, 'INFO');
    }
}