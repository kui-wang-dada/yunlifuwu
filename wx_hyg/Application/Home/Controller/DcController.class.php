<?php
namespace Home\Controller;

use Think\Controller;

/**
 * 零钱宝代充功能
 */
class DcController extends MobileController{

	public function _initialize() {
		parent::_initialize();
	}

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

	public function checkuser()
	{
		if(IS_AJAX){
			$mobile = I('phoneNumber');
			$checkuser = D('lqbuser')->checkUserinfo($mobile); //检测用户7000069 066807
			if(!$checkuser){
				$checkuser['status'] = 2;
				$this->ajaxReturn($checkuser);
			}else{
				$checkuser['status'] = 1;
				$checkuser['p_ref_name'] = substr_replace($checkuser['p_ref_name'],'*','0','3');
				//$checkuser['p_ref_mobile'] = substr_replace($checkuser['p_ref_mobile'],'*******','0','6');
				$checkuser['p_ref_cardno'] = substr_replace($checkuser['p_ref_cardno'],'*******','0','8');
				$this->ajaxReturn($checkuser);
			}
		}
	}
	/**
	 * 创建订单91721704061601
	 */
	public function createOrder()
	{

		$amount = trim(I('amount'));
		$mobile = trim(I('mobile'));

		$checkuser = D('lqbuser')->checkUserinfo($mobile); //检测用户

		if(!$checkuser){
			$ret['orderid'] = '';
			$ret['status'] = 2;
			$ret['msg'] = '充值失败，请确认手机号【'.$mobile.'】是否为会员';
			$this->ajaxReturn($ret);
		}

		$amtact = M('lqbsetact')->find();   //获取零钱宝代充配置项

		if($amount < $amtact['dc_minamt']){   //如果传入金额小于设置最小金额

			$ret['orderid'] = '';
			$ret['status'] = 2;
			$ret['msg'] = '代充金额最少充入'.$amtact['dc_minamt'].'元';
			$this->ajaxReturn($ret);
		}

		if($amount > $amtact['dc_maxamt']){   //如果传入金额大于于设置最大金额

			$ret['orderid'] = '';
			$ret['status'] = 2;
			$ret['msg'] = '代充金额最多充入'.$amtact['dc_maxamt'].'元';
			$this->ajaxReturn($ret);
		}
		
		$orderId = 'DC_'.self::_getRandomStr(8).date("YmdHis",time());
		if($orderId && $amount){
			$data['orderid'] = $orderId;
			$data['amount'] = $amount;
			$data['paytype'] = 'D';   //充值类型,代充值
			$data['dcopenid'] = $checkuser['p_ref_mobile'];   //充值类型,代充的手机号
			S($orderId,$data,300);
			$res = D('lqbuser')->insertPatOrder($data);
			if($res){
				$ret['orderid'] = $orderId;
				$ret['status'] = 1;
				$this->ajaxReturn($ret);
			}else{
				$ret['orderid'] = '';
				$ret['status'] = 0;
				$this->ajaxReturn($ret);
			}
			
		}else{
			$ret['orderid'] = '';
			$ret['status'] = 0;
			$this->ajaxReturn($ret);
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