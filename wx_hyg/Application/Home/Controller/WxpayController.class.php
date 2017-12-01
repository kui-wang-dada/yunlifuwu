<?php
namespace Home\Controller;

use Think\Controller;

class WxpayController extends Controller {

	const UNIFIED_ORDER_URL       = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	const ORDER_QUERY = 'https://api.mch.weixin.qq.com/pay/orderquery';
    const KEY = 'test';
	private $appid;
	private $sub_appid;   //子商户APPid
	private $appsecret;
	private $mch_id;
	private $sub_mch_id;  //子商户的商户号
	private $payKey;      //支付key
	private $prepay_id;   //预支付id

	public function __construct($options = array()) {
		$this->appid        =  isset($options['appid'])       ? $options['appid'] : '';
		$this->sub_appid    =  isset($options['sub_appid'])   ? $options['sub_appid'] : '';
		$this->appsecret    =  isset($options['appsecret'])   ? $options['appsecret'] : '';
		$this->mch_id       =  isset($options['mch_id'])      ? $options['mch_id'] : '';
		$this->sub_mch_id   =  isset($options['sub_mch_id'])  ? $options['sub_mch_id'] : '';
		$this->payKey       =  isset($options['payKey'])      ? $options['payKey'] : '';
	}

	/**
	 * 统一下单接口生成支付请求
	 * @param  $openid      string  用户OPENID相对于当前公众号
	 * @param  $body        string  商品描述 少于127字节
	 * @param  $orderId     string  系统中唯一订单号
	 * @param  $money       integer 支付金额 单位（分）
	 * @param  $notify_url  string  通知URL
	 * @param  $extend      array|string   扩展参数
	 * @return json|boolean json 直接可赋给JSAPI接口使用，boolean错误
	 */
	public function unifiedOrder($openid, $attach, $body, $orderId, $money, $notify_url = '', $extend = array()) {
		if (strlen($body) > 127) {
			$this->error = '订单描述过长';
			return false;
		}
		$params = array(
			'sub_openid'       => $openid,
			'appid'            => $this->appid,
			'sub_appid'        => $this->sub_appid,
			'mch_id'           => $this->mch_id,
			'sub_mch_id'       => $this->sub_mch_id,
			'nonce_str'        => self::_getRandomStr(32),
			'body'             => $body,
			'attach'           => $attach,
			'out_trade_no'     => $orderId,
			'total_fee'        => $money,
			'spbill_create_ip' => '127.0.0.1',
			'notify_url'       => $notify_url,
			'trade_type'       => 'JSAPI',
		);

		// 生成签名
		$params['sign'] = self::_getOrderMd5($params);
		$response = self::_array2Xml($params);
		$data = $this->http(self::UNIFIED_ORDER_URL, $response, 'POST');
		$data = self::_extractXml($data);
        \Think\Log::write(print_r($data, true), '微信支付统一下单接口');
		if ($data) {
			if ($data['return_code'] == 'SUCCESS') {
				if ($data['result_code'] == 'SUCCESS') {
					$this->prepay_id = $data['prepay_id'];
					return $this->createPayParams();
				} else {
					//$this->error = $data['err_code_des'];
					return $data['err_code_des'];
				}
			} else {
				//$this->error = $data['return_msg'];
				return $data['return_msg'];
			}
		} else {
			//$this->error = '创建订单失败';
			return '创建订单失败';
		}
	}

	/**
	 * 生成支付参数
	 */
	public function createPayParams() {
		if (empty($this->prepay_id)) {
			$this->error = 'prepay_id参数错误';
			return false;
		}
		$params['appId']     = $this->appid;
		$params['timeStamp'] = (string)NOW_TIME;
		$params['nonceStr']  = self::_getRandomStr(32);
		$params['package']   = 'prepay_id='.$this->prepay_id;
		$params['signType']  = 'MD5';
		$params['paySign']   = self::_getOrderMd5($params);
		return json_encode($params);
	}

	public function notify(){
        // 获取xml
        $xml=file_get_contents('php://input', 'r');
        // 转成php数组
        $this->log('微信支付回调信息1：'.$xml);
        $data=$this->_extractXml($xml);
        $this->log('微信支付回调信息2：'.json_encode($data));
        // 保存原sign
        $data_sign=$data['sign'];
        // sign不参与签名
        unset($data['sign']);
        $sign=$this->_getOrderMd5($data);
        $this->log('新的签名：'.$sign);
        // 判断签名是否正确  判断支付状态

        if ($sign === $data_sign && $data['return_code']=='SUCCESS' && $data['result_code']=='SUCCESS') {

            $check = M('lqbpayinfo')->where(array('transaction_id'=>$data['transaction_id']))->find();
            if(empty($check)){
            	$data['body'] = $data['attach'];
            	$data['time'] = time();
            	$add = M('lqbpayinfo')->add($data);
            	if($add){
            		$type = 3;  // 默认为openid充值
            		$exp = explode('|',$data['attach']);    //分割，看是否为代充
            		$this->sendmsg($data['sub_openid'],$exp[0],($data['total_fee']/100).'元','如有任何疑问，请咨询客服');//发送模板信息
            		if($exp[1] == 'D' && !empty($exp[2])){   //如果类型为D并且第三个参数不为空,则为代充值

            			$type = 1;//手机号充值
            			$data['mobile'] = $exp[2];   //获取手机号

            		}
            		$resultpay = $this->updatePay($data,$type);
            		$result=true;
            	}
            }else{

            	$result=true;
            }


        }else{
            $result=false;
        }
        // 返回状态给微信服务器
        if ($result) {
             $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
             $xml .= "<xml>\n";
             $item='';
             $item .= "<return_code><![CDATA[SUCCESS]]></return_code>\n";
             $item .= "<return_msg><![CDATA[OK]]></return_msg>\n";
             $xml .=$item;
             $xml .= "</xml>\n";
             $this->log('返回给微信xml成功'.$xml,'INFO');
             header("Content-type: text/xml");
        }
        echo $xml;
    }
    
    /**
     * 会员购回调
     * E-mail: nuchect@qq.com
     */
    public function notifymember(){
        // 获取xml
        $xml=file_get_contents('php://input', 'r');
        \Think\Log::write(print_r($xml, true), '会员购支付回调');
        $data = $this->_extractXml($xml);
        \Think\Log::write(print_r($data, true), '会员购支付回调');
        // 保存原sign
        $data_sign = $data['sign'];
        // sign不参与签名
        unset($data['sign']);
        $sign = $this->_getOrderMd5($data);
        if($data_sign == $sign && $data['return_code']=='SUCCESS' && $data['result_code']=='SUCCESS'){
            // 查询支付结果
            $data_select = $this->checkPay($data['transaction_id']);
            if($data_select){
                // 保存原sign
                $data_sign = $data_select['sign'];
                // sign不参与签名
                unset($data_select['sign']);
                $sign = $this->_getOrderMd5($data_select);
                if($data_sign == $sign
                    && $data_select['return_code'] == 'SUCCESS'
                    && $data_select['trade_state'] == 'SUCCESS'
                ){
                    $data['body'] = $data['attach'];
                    $data['time'] = time();
                    $obj = M('MemberPayment');
                    $info = $obj->where(['transaction_id' => $data['transaction_id']])->find();
                    
                    $return_flag = false;
                    
                    if(!$info){
                        $id = $obj->add($data);
                        \Think\Log::write(M()->_sql(), '会员购支付成功');
                        $return_flag = $this->editMemberLevel($data['out_trade_no']);
    
                        $exp = explode('|',$data['attach']);    //分割
                        $this->sendmsg($data['sub_openid'],$exp[0],($data['total_fee']/100).'元','如有任何疑问，请咨询客服');//发送模板信息
    
                    }else{
                        $return_flag = $this->editMemberLevel($data['out_trade_no']);
                    }
                    if($return_flag){
                        echo $this->_array2Xml([
                            'return_code' => 'SUCCESS',
                            'return_msg' => 'OK'
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * 修改会员等级
     * @param $number
     * @return mixed
     * E-mail: nuchect@qq.com
     */
    public function editMemberLevel($number){
        $url = C('MEMBER_CONFIG')['editLevel'];
    
        $obj = M('MemberAgreement');
        $info = $obj->where(['number' => $number])->find();
    
        if(!$info){
            return false;
        }
        if($info['status'] > 0){
            return true;
        }
        
        $params = array(
            "id" => $info['openid'],
            "timestamp" => date("Y-m-d H:i:s"),
            "type" => 3,
            'plan' => $info['level'],
            "seqno" => $info['number'], // 支付订单号
            'flq' => $info['vouchers'],
            'je' => $info['deposit'],
        );
        $params['sign'] = $this->_createSign($params);
        \Think\Log::write(print_r($params, true), '修改会员等级');
        $res = $this->http($url, $params);
        \Think\Log::write(print_r($res, true), '修改会员等级返回');
        $res = json_decode($res, true);
        if($res && $res['refcode'] == 0){
            $res = $obj->where(['id' => $info['id']])->save([
                'status' => 1,
                'update_time' => time()
            ]);
            \Think\Log::write(M()->_sql(), '更新协议状态');
            if($res){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    /**
     * 查询支付结果
     * @param $transaction_id
     * @return array
     * E-mail: nuchect@qq.com
     */
    public function checkPay($transaction_id){
        $payconfig = C('PAY_CONFIG');
        $params = array(
            'appid'            => $payconfig['appid'],
            'sub_appid'        => $payconfig['sub_appid'],
            'mch_id'           => $payconfig['mch_id'],
            'sub_mch_id'       => $payconfig['sub_mch_id'],
            'transaction_id'       => $transaction_id,
            'nonce_str'        => self::_getRandomStr(32),
        );
    
        // 生成签名
        $params['sign'] = self::_getOrderMd5($params);
        $response = self::_array2Xml($params);
        $data = $this->http(self::ORDER_QUERY, $response, 'POST');
        $data = self::_extractXml($data);
        \Think\Log::write(print_r($data, true), '微信支付查询接口');
        return $data;
    }

    public function test()
    {

    	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
         $xml .= "<xml>\n";
         $item='';
         $item .= "<return_code><![CDATA[SUCCESS]]></return_code>\n";
         $item .= "<return_msg><![CDATA[OK]]></return_msg>\n";
         $xml .=$item;
         $xml .= "</xml>\n";
         header("Content-type: text/xml");
         echo $xml;
    }

    /**
     * 充值后同步到crm中，正常充值
     */
    private function updatePay($data = array(),$type = '')
    {
    	$real = $this->orderrealamt($data['sub_openid'],$data['out_trade_no']);
    	if(!$real){
    		$total_fee = $data['total_fee'];     //没有活动
    	}else{
    		$total_fee = $real['total_fee']; //有活动的金额
    	}
    	if($type == 1){    //手机号充值

    		$id = $data['mobile'];
    	}elseif($type == 3){  //openid充值
    		$id = $data['sub_openid'];
    	}
    	$params = array(
    		"code"      => '39',
            "id"        => $id,
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => $type,
            "seqno"     => $data['out_trade_no'],
            "vje"       => $data['total_fee']/100,

        );
        $url            = 'http://220.175.154.53:8085/jjlsws/DepositSrv';
        $params['sign'] = $this->_createSign($params);
        $this->log("接口传入参数".json_encode($params));
        $res = json_decode(($this->http($url,$params)),true);
        $this->log("调用充值接口返回数据：".json_encode($res));
        if($res['refcode'] != '-1'){   //充值成功
        	$save['paytime'] = time();
        	$save['status'] = 'P';
        	$save['order_type'] = 'P';  //直接支付
        	$save['update_time'] = time();
        	$save['desc'] = json_encode($res);
        	$up = M('lqborder')->where(array('out_trade_no'=>$data['out_trade_no']))->save($save);
        	if($up !==false){
        		if(!empty($real['give_total_fee'])){   //有赠送订单
        			$this->updateGiveTotalFee($data,$real['give_total_fee'],$type);
        		}
        		$this->log("充值完成".$data['out_trade_no']);
        	}else{
        		$this->log('充值失败'.$data['out_trade_no']);
        	}
        }else{    //充值失败

        	$fail['status'] = 'F';  //充值失败
        	$fail['order_type'] = 'P';  //直接支付
        	$fail['update_time'] = time();
        	$fail['desc'] = json_encode($res);
        	$up = M('lqborder')->where(array('out_trade_no'=>$data['out_trade_no']))->save($fail);
        	$this->log("充值失败--".$data['out_trade_no']);
        }

        return json_decode($res,true);
    }

    /**
     * 赠送订单充值
     */
    public function updateGiveTotalFee($data=array(),$give_total_fee = '',$type='')
    {
    	if($type == 1){    //手机号充值

    		$id = $data['mobile'];
    	}elseif($type == 3){  //openid充值
    		$id = $data['sub_openid'];
    	}
    	$params = array(
    		"code"      => '40',
            "id"        => $id,
            "timestamp" => date("Y-m-d H:i:s"),
            "type"      => $type,
            "seqno"     => 'G'.self::_getRandomNum(10).date("YmdHis",time()),
            "vje"       => $give_total_fee,

        );

        $url            = 'http://220.175.154.53:8085/jjlsws/DepositSrv';
        $params['sign'] = $this->_createSign($params);
        $res = json_decode(($this->http($url,$params)),true);
        $this->log("赠送订单接口传入参数".json_encode($params));
        $this->log("调用赠送充值接口返回数据：".json_encode($res));
        if($res['refcode'] != '-1'){   //充值成功
        	$add['openid'] = $data['sub_openid'];
        	$add['out_trade_no'] = $params['seqno'];   //订单号
        	$add['order_type'] = 'G';  //直接支付
        	$add['total_fee'] = $give_total_fee;
        	$add['status'] = 'P';
        	$add['intime'] = time();
        	$add['give_total_fee'] = $give_total_fee;
        	$add['source_id'] = $data['out_trade_no'];
        	$add['desc'] = json_encode($res);
        	$up = M('lqborder')->add($add);
        	if($up){
        		$this->log("赠送充值完成");
        	}else{
        		$this->log("赠送充值插入失败");
	        }
        }else{

        	$add['openid'] = $data['sub_openid'];
        	$add['out_trade_no'] = $params['seqno'];   //订单号
        	$add['order_type'] = 'G';  //直接支付
        	$add['total_fee'] = $give_total_fee;
        	$add['status'] = 'F';
        	$add['intime'] = time();
        	$add['give_total_fee'] = $give_total_fee;
        	$add['source_id'] = $data['out_trade_no'];
        	$add['desc'] = json_encode($res);
        	$up = M('lqborder')->add($add);
        	$this->log("赠送充值插入失败1");
        }

    }
    public function t()
    {
    	$res = $this->orderrealamt('oUBPqjhOAJqyCnvf5n4WOL4JfTyw','17951704122227');
    	dump($res);
    }
    /**
     * 计算用户支付金额与实际充值金额
     */
    public function orderrealamt($openid = '',$out_trade_no='')
    {
    	$orderinfo = M('lqborder')->where(array('out_trade_no'=>$out_trade_no))->find();  //订单详细
    	$act_info = M('lqbsetact')->where(array('is_open'=>1))->find();  //查询活动
    	$time = time();
    	if(!empty($act_info)){

    		if($act_info['start_time'] > $time) {    //活动未开始

    			return false;
	    	}elseif($act_info['start_time'] < $time && $act_info['end_time'] > $time){   //进行中

	    		switch ($act_info['type']) {
	    			case '1':     //首冲
	    				$iscount = M('lqborder')->where(array('openid'=>$openid,'status'=>'P'))->count();  //订单个数
	    				if($iscount < 1){   //第一次充值有效订单
	    					if($orderinfo['total_fee'] > $act_info['is_amount']){    //大于设置金额

	    						$real_amt = $orderinfo['total_fee'] + $act_info['give_total_fee'];  //实际充值金额
	    						$return['give_total_fee'] = $act_info['give_total_fee'];
	    					}else{   //小于设置金额
	    						$real_amt = $orderinfo['total_fee'];
	    					}

	    				}else{
	    				    $real_amt = $orderinfo['total_fee'];    //已经充值过了或者不满足充值金额需求
	    				}
	    			    break;
	    			default:
	    				$real_amt = $orderinfo['total_fee'];       //其他的为默认金额了
	    				break;
	    		}

	    		$return['total_fee'] = $real_amt;  //最终金额
	    		return $return;

	    	}else{  //已经结束
	    		return false;
	    	}
    	}else{
    		return false;
    	}

    }

    /**
     * 发送模板信息
     */
    public function sendmsg($openid = '',$type = '',$content='',$remark='')
    {
    	$obj = new \Home\Controller\ApiController();
    	$res = $obj->sendmsg($openid,$type,$content,$remark);
    }

	/**
	 * 计算ORDER的MD5签名
	 */
	private function _getOrderMd5($params) {
		ksort($params);
		$params['key'] = '789435BD82cce97cf9ae0b83BAD76C4b';
		$this->log(json_encode($params));
		return strtoupper(md5(urldecode(http_build_query($params))));
	}

	private static function _getRandomNum($lenght = '')	{
		$str_pol = "123456789";
		return substr(str_shuffle($str_pol), 0, $lenght);
	}

	private function _array2Xml($array) {
		$xml  = new \SimpleXMLElement('<xml></xml>');
		$this->_data2xml($xml, $array);
		return $xml->asXML();
	}

	public static function _createSign($params = array())
    {
    	$str = '';
        ksort($params);
        foreach ($params as $k => $v) {
        	$str.= $k.$v;
        }
        //return MD5(http_build_query($params) . '&key=' . $key);
        return strtoupper(MD5(self::KEY.$str.self::KEY));
    }

	/**
	 * 数据XML编码
	 * @param  object $xml  XML对象
	 * @param  mixed  $data 数据
	 * @param  string $item 数字索引时的节点名称
	 * @return string xml
	 *
	 */
	private function _data2xml($xml, $data, $item = 'item') {
		foreach ($data as $key => $value) {
			/* 指定默认的数字key */
			is_numeric($key) && $key = $item;
			/* 添加子元素 */
			if(is_array($value) || is_object($value)){
				$child = $xml->addChild($key);
				$this->_data2xml($child, $value, $item);
			} else {
				if(is_numeric($value)){
					$child = $xml->addChild($key, $value);
				} else {
					$child = $xml->addChild($key);
					$node  = dom_import_simplexml($child);
					$node->appendChild($node->ownerDocument->createCDATASection($value));
				}
			}
		}
	}

	/**
	 * XML文档解析成数组，并将键值转成小写
	 * @param  xml $xml
	 * @return array
	 */
	private static function _extractXml($xml) {
		$data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		return array_change_key_case($data, CASE_LOWER);
	}

	/**
	 * 返回随机填充的字符串
	 */
	private static function _getRandomStr($lenght = 16)	{
		$str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);
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