<?php
namespace Home\Controller;

use Think\Controller;

/**
 * 对外接口部分
 */
class ApiController extends Controller{

	/**
     * 获取二维码解析后的码
     */
	public function getQrcode()
	{
		if(IS_POST){
			$data = file_get_contents('php://input', 'r');
            $this->writeLog("接收到pos查询code请求数据：" . $data); //json格式
            $data = json_decode($data, true);
            if(empty($data['code'])){

            	exit(json_encode(array('rescode'=>'-1','resmsg'=>'code illegal or empty!')));
            }

            $code  = M('qrcode')->where(array('code'=>$data['code']))->find();
            if(empty($code)){
            	exit(json_encode(array('rescode'=>'-1','resmsg'=>'code does not exist or has expired, please re access!')));
            }
            if($code['exp_time'] < time()){
            	exit(json_encode(array('rescode'=>'-1','resmsg'=>'code has expired, please re access!')));
            }
            $userinfo = json_decode($code['userdata'],true);
            $result['p_ref_mobile'] = $userinfo['p_ref_mobile'];
            exit(json_encode(array('rescode'=>'1','resmsg'=>$result)));

		}else{
			exit(json_encode(array('rescode'=>'-1','resmsg'=>'Access error!')));
		}

	}

    /**
     * 外部调用接口发送模板信息
     */
    public function sendTempMessage()
    {
        if(IS_POST){
            $data = file_get_contents('php://input', 'r');
            $this->writeLog("接收到发送模板信息数据：" . $data); //json格式
            $data = json_decode($data, true);
            $res = $this->sendmsg($data['openid'],$data['type'],$data['content'],$data['remark']);

            exit(json_encode(array('rescode'=>'1','resmsg'=>$res)));

        }else{
            exit(json_encode(array('rescode'=>'-1','resmsg'=>'Access error!')));
        }
    }

    public function getaccesstoken()
    {
//        $data = json_decode(file_get_contents("http://www.lianshengit.com/Sign/index.php/Home/Api/getaccess_token"),true);
//        $accesstoken = json_decode($data['refinfo'],true);
//        return $accesstoken['access_token'];
        $res = file_get_contents('http://www.lianshengit.com/Wei/index.php?s=/Api/Api/get_access_token');
        return $res;
    }

    /**
     * 发送模板信息
     */
    public function sendmsg($openid = '',$type = '',$content='',$remark='')
    {   
        $first = '尊敬的会员：您刚刚完成一次交易';
        $keyword1 = date("Y-m-d H:i:s",time());
        $keyword2 = $type;
        $keyword3 = $content;
        $remark = $remark;
        $data = '{
          "touser":"'.$openid.'",
          "template_id":"tJPzEWKcU-hgaALTZGiw-c9iLI0apGjEjEcZVj_UiRM",
          "url":"",
          "topcolor":"#173177",
             "data":{
                 "first":{
                      "value":"'.$first.'",
                      "color":"#173177"
                  },
                  "keyword1":{
                       "value":"'.$keyword1.'",
                       "color":"#173177"
                   },
                   "keyword2":{
                       "value":"'.$keyword2.'",
                       "color":"#173177"
                   },
                   "keyword3":{
                       "value":"'.$keyword3.'",
                       "color":"#173177"
                   },
                   "remark":{
                       "value":"'.$remark.'",
                       "color":"#EE3B3B"
                   }
               }
           }';
        $res=$this->sendTemplateMessage(json_decode($data,true));
        $in['openid']=$openid;
        $in['errcode']=$res['errcode'];
        //$in['errmsg']=$res['errmsg'];
        //$in['msgid']=$res['msgid'];
        $in['content']=$remark;
        $in['time']=date("Y-m-d H:i:s",time());
        $add=M('lqbtempmsg')->add($in);
        return $res;
    }
    /**
     * 发送模板信息
     */
    public function sendTemplateMessage($data){
        $result = $this->http('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$this->getaccesstoken(),json_encode($data));
        if($result){
            $json = json_decode($result,true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $json;
            }
            return $json;
        }
        return false;
    }

	private function writeLog($data = '')
    {
        \Think\Log::write($data, 'INFO');
    }

    private function http($url, $params = array(), $method = 'POST'){
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
}