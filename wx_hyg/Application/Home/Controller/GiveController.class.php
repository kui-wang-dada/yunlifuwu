<?php
namespace Home\Controller;

use Think\Controller;

/**
* 零钱宝赠送功能
* 0718
*/
class GiveController extends MobileController
{

	public function _initialize() {
		parent::_initialize();
	}

	/**
	 * 零钱宝赠送功能
	 */
	public function index()
	{
		$userinfo = D('lqbuser')->getUserInfo_test(session('openid'));
		if(!$userinfo){
			header("Location:http://www.lianshengit.com/ls2014/mindex.aspx?page=register");
			exit();
		}
		$this->assign('userinfo',$userinfo);
		$this->display();
	}

	public function creatGiveOrder()
	{
		if(IS_AJAX){

			$order['out_trade_no'] = 'give_'.self::_getRandomStr(8).date("YmdHis",time()); //订单号
			$order['total_fee'] = trim(I('money'));
			$order['template_id'] = trim(I('template_id'));
			if(empty(I('content'))){
				$content = '';
				$order['content'] = '';
			}else{
				$order['content'] = trim(I('content'));
			}

			$order['toname'] = trim(I('toname'));
			$order['fromname'] = trim(I('fromname'));
			S($order['out_trade_no'],$order,300);  //缓存起来
			if(S($order['out_trade_no']) !== false){
				$ret['out_trade_no'] = $order['out_trade_no'];
				$ret['status'] = 1;
				$this->ajaxReturn($ret);
			}else{
				$ret['out_trade_no'] = '';
				$ret['status'] = 0;
				$this->ajaxReturn($ret);
			}

		}
	}

	/**
	 * 选择主题
	 */
	public function choose()
	{
		$out_trade_no = trim(I('out_trade_no'));
		//$orderinfo = S($out_trade_no);
		$orderinfo = true;
		if($orderinfo === false){

			$this->error('订单状态有误，请重新创建订单');
		}
		$receive_url = 'http://' . $_SERVER['SERVER_NAME'] . U('Give/index');
		$img_url = 'http://' . $_SERVER['SERVER_NAME'].'/Sign/lqb/Public/img/give.png';
		$getSignPackage = $this->jssdk();
		$this->assign('getSignPackage',$getSignPackage);
		$this->assign('receive_url',$receive_url);
		$this->assign('img_url',$img_url);
		$this->assign('username',session('userinfo.nickname'));
		$this->assign('orderinfo',$orderinfo);
		$this->assign('template_id',$orderinfo['template_id']);
		$this->display();
	}

	/**
	 * 创建订单
	 */
	public function giveOrder()
	{
		$out_trade_no = trim(I('out_trade_no'));
		//$orderinfo = S($out_trade_no);
		$orderinfo = true;
		if($orderinfo === false){

			$this->error('订单状态有误，请重新创建订单');
		}
		$orderinfo['theme_id'] = trim(I('theme_id'));  //主题id
		if(empty($orderinfo['content'])){
			if ($orderinfo['theme_id'] == 1) {
				$orderinfo['content'] = '收到您真切的关心和诚挚的悼念，我谨代表家人表示深深的谢意，顺祝平安。';
			}else{
				$orderinfo['content'] = '一点心意，浓浓情义';
			}
		}
		$result = D('lqbuser')->lqbGive($orderinfo);
		if($result['refcode'] == '-1'){
			$this->error($result['refinfo']);
		}else{

			$this->redirect('Give/share',array('out_trade_no'=>$out_trade_no));
		}
	}

	/**
	 * 分享好友
	 */
	public function share()
	{
		$out_trade_no = trim(I('out_trade_no'));
		$is_ouu_trade_no = M('lqbgiveorder')->where(array('out_trade_no'=>$out_trade_no,'opeid'=>session('openid')))->find();
		if(empty($is_ouu_trade_no)){
			$this->error('订单不存在');
		}
		$receive_url = 'http://' . $_SERVER['SERVER_NAME'] . U('Give/receive',array('out_trade_no'=>$out_trade_no));
		$img_url = 'http://' . $_SERVER['SERVER_NAME'].'/Sign/lqb/Public/img/give.png';

		$getSignPackage = $this->jssdk();
		$this->assign('fromname',base64_decode($is_ouu_trade_no['name']));
		$this->assign('toname',base64_decode($is_ouu_trade_no['toname']));
		$this->assign('getSignPackage',$getSignPackage);
		$this->assign('receive_url',$receive_url);
		$this->assign('img_url',$img_url);
		$this->assign('theme_id',$is_ouu_trade_no['theme_id']);
		$this->assign('is_ouu_trade_no',$is_ouu_trade_no);
		$this->display();
	}

	/**
	 * 好友接受
	 */
	public function receive()
	{
		$out_trade_no = trim(I('out_trade_no'));
		$is_ouu_trade_no = M('lqbgiveorder')->where(array('out_trade_no'=>$out_trade_no,'opeid'=>session('openid')))->find();
		if(empty($is_ouu_trade_no)){
			$this->error('订单状态不正确');
		}
		$checkCrm = D('lqbuser')->lqbGiveFind($is_ouu_trade_no['crm_out_trade_no']);
		if($checkCrm['refcode'] == '-1'){    //订单状态不正常
			$this->error($checkCrm['refinfo']);
		}
		$this->assign('theme_id',$is_ouu_trade_no['theme_id']);
		$this->assign('is_ouu_trade_no',$is_ouu_trade_no);
		$this->assign('fromname',base64_decode($is_ouu_trade_no['name']));
		$this->assign('toname',base64_decode($is_ouu_trade_no['toname']));
		$this->assign('username',base64_decode($is_ouu_trade_no['name']));
		$this->assign('receive_name',base64_decode($is_ouu_trade_no['receive_name']));
		$this->display();
	}

	/**
	 * 领取赠送的订单
	 */
	public function createReceive()
	{
		if(IS_AJAX){
			$userinfo = D('lqbuser')->getUserInfo(session('openid'));
			if(!$userinfo){
				$ret['code'] = 3;
				$ret['msg'] = '请开通会员卡后领取';
				$this->ajaxReturn($ret);
			}
			$out_trade_no = trim(I('out_trade_no'));
			$is_ouu_trade_no = M('lqbgiveorder')->where(array('out_trade_no'=>$out_trade_no,'opeid'=>session('openid'),'status'=>'G'))->find();
			if(empty($is_ouu_trade_no)){
				$ret['code'] = 2;
				$ret['msg'] = '订单不存在';
				$this->ajaxReturn($ret);
			}
			$receive_crm  = D('lqbuser')->lqbGiveReceive($is_ouu_trade_no);
			if($receive_crm['refcode'] == '-1'){   //领取失败，crm返回错误信息

				$ret['code'] = 4;
				$ret['msg'] = $receive_crm['refinfo'];
				$this->ajaxReturn($ret);
			}else{
				$ret['code'] = 1;
				$ret['msg'] = '领取成功';
				$this->ajaxReturn($ret);
			}
		}
	}

	/**
	 * 从联盛服务器获取jssdk的秘钥
	 */
	public function gettiket()
	{
		$res = $data = json_decode(file_get_contents("http://www.lianshengit.com/Sign/jsapi_ticket.json"),true);
		return $res['jsapi_ticket'];
	}


	public function jssdk()
	{
		$jsapiTicket = $this->gettiket();
		$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


		$timestamp = time();
		$nonceStr = $this->_getRandomStr(32);

		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

		$signature = sha1($string);

		$signPackage = array(
		  "appId"     => 'wx6a5ca61373bac69a',
		  "nonceStr"  => $nonceStr,
		  "timestamp" => $timestamp,
		  "url"       => $url,
		  "signature" => $signature,
		  "rawString" => $string
		);
		return $signPackage;
	}

	 /**
	 * 返回随机填充的字符串
	 */
	private static function _getRandomStr($lenght = '')	{
		$str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);
	}
}