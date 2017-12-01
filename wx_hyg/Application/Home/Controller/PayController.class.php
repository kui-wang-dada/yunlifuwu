<?php
namespace Home\Controller;

use Think\Controller;

class PayController extends MobileController {
    public function _initialize() {
		parent::_initialize();
	}
	
    public function index()
    {
        // 判断是否会员购订单
        if(strpos(I('orderid'), 'member') !== false){
            $this->member(I('orderid'));
            exit;
        }
		$orderinfo=S(I('orderid'));  //获取缓存中的订单信息
		$payok_url='http://' . $_SERVER['SERVER_NAME'] . U('Coinpurse/index');
		if($orderinfo['orderid'] != I('orderid')){   //对比订单信息

			$this->error('订单信息异常，请重新购买');

		}
		$paytype = $orderinfo['paytype']; // 订单类型
		if($paytype == 'D'){   //代充值
			$attach = '电子购物卡代充值|D|'.$orderinfo['dcopenid'];
		}else{
			$attach = '电子购物卡直接充值|P|';
		}
		$res = $this->pay($orderinfo['orderid'],$orderinfo['amount'],$attach);
		if(!$res){
			$this->error('订单创建失败，请重试');
		}

		$this->assign('jsApiParameters',json_encode($res));
		$this->assign('payok_url',$payok_url);
		$this->display();
    }


    public function pay($orderId = '',$amount = '',$attach = '')
    {
    	 $options = array(
				'appid'     =>'wx7e4a1d43eae038d0',
				'sub_appid' =>'wx6a5ca61373bac69a',
				'appsecret' =>'473585551f8fdd6df915ace4b5cdccc7',
				'mch_id'    =>'1270723401',
				'sub_mch_id'=>'1273625701',
				'payKey'    =>'789435BD82cce97cf9ae0b83BAD76C4b',
			);
			$openid=$_SESSION['openid'];
			$body = "电子购物卡充值";
			$attach = $attach;
			$money = $amount * 100;
			$notify_url = 'http://' . $_SERVER['SERVER_NAME'] . U('Wxpay/notify');
			$weObj = new \Home\Controller\WxpayController($options);
			$res= json_decode($weObj->unifiedOrder($openid,$attach,$body,$orderId,$money,$notify_url),true);
	    	if(!empty($res) && !empty($res['package']) && !empty($res['paySign'])){

	    		return $res;
	    	}else{

	    		return false;
	    	}
    }
    
    /**
     * 会员购支付
     * E-mail: nuchect@qq.com
     */
    public function member($orderid){
        $obj = M('MemberAgreement');
        $number = substr($orderid, 6);
        $info = $obj->where(['number' => $number])->find();
        if(!$info){
            redirect(U('Member/index'));
        }
        $payok_url='http://' . $_SERVER['SERVER_NAME'] . U('Member/result', ['id' => $info['id']]);
    
        $attach = "{$info['name']}合作保证金|{$info['level']}|{$info['number']}";
    
        $amount = $info['deposit'] * 100;
        $order_id = $info['number'];
        // 测试
        $amount = 0.01;
    

        $openid=$_SESSION['openid'];
        $body = "会员合作保证金";
        $money = $amount * 100;
        $notify_url = 'http://' . $_SERVER['SERVER_NAME'] . U('Wxpay/notifymember');
        $weObj = new \Home\Controller\WxpayController(C('PAY_CONFIG'));
        $res= json_decode($weObj->unifiedOrder($openid,$attach,$body,$order_id,$money,$notify_url),true);
        if(!empty($res) && !empty($res['package']) && !empty($res['paySign'])){
            $this->assign('jsApiParameters',json_encode($res));
            $this->assign('payok_url',$payok_url);
            $this->display('Pay/index');
        }else{
            $this->error('支付请求失败');
        }
    }
}