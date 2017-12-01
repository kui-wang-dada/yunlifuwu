<?php
namespace Home\Controller;
use Think\Controller;
vendor('Wxpay.wxpay');
class IndexController extends Controller
{   
    public $wx_rate = '0.0035';  //微信手续费率，千分之3.5
    /**
     * 登录接口
     * @param posid pos机id
     * @param userid 用户名
     * @param password 密码
     * @param posinfo pos的一些信息
     * @param reqtiem 传入时间
     * @return {
     *      "result_code": "0",
     *      "result_des": "登录成功",
     *      "result_data": {
     *          "token": "cs30301313",
     *          "posid": "0001",
     *          "posname": "测试门店1",
     *          "postype": "2",
     *          "storeid": "00379575",
     *          "storename": "测试门店",
     *          "nodeid": "00263305",
     *          "functype": "2",
     *      }
     *  }
     */
    public function login()
    {
        if (IS_POST) {
            $data = file_get_contents('php://input', 'r'); //接受post传递过来的json数据
            $this->log("接收到APP登录请求数据：" . $data); //json格式
            $data = json_decode($data, true);
            if (empty($data)) {
                exit(self::returnmsg(ErrorCode::$data_error, '数据不合法'));
            }
            $diftime     = abs(NOW_TIME - $data['reqtime']);
            $check_posid = M('pos_posinfo')->field('pos_id,pos_name,pos_type,store_id,pos_name,store_name,node_id,func_type,login_token,login_timeout,login_user,login_pwd,server_maxposseq')->where(array('pos_id' => $data['pos_id']))->find();
            if (empty($check_posid)) {
                exit(self::returnmsg(ErrorCode::$login_pos_null, '终端不存在'));
            }
            $login_pwd = md5($check_posid['login_pwd']);
            $check_pwd = M('pos_posinfo')->where(array('login_user'=>$data['user_id'],'pos_id' => $data['pos_id']))->find(); //账号密码

            if(empty($check_pwd)){

                exit(self::returnmsg(ErrorCode::$login_pos_null, '用户名不存在'));

            }else{

                if($data['password'] !== $login_pwd){
                    exit(self::returnmsg(ErrorCode::$login_pos_null, '用户名或密码错误'));
                }
            }

            if (empty($check_posid['login_token']) || $check_posid['login_timeout'] < NOW_TIME) { //登录过期，更新token并重新给值
                $check_posid['login_token'] = self::getToken();
                $up['login_token']          = $check_posid['login_token']; //登录token
                $up['login_timeout']        = strtotime("2050-12-31"); //token过期时间
                $uptoken                    = M('pos_posinfo')->where(array('pos_id' => $data['pos_id']))->save($up);
                if ($uptoken !== false) {
                    $check_posid['server_maxposseq'] = (int)$check_posid['server_maxposseq'];
                    $check_posid['login_timeout'] = (string)strtotime("2050-12-31");
                    exit(self::returnmsg(ErrorCode::$ok, '登录成功,token更新成功', $check_posid));
                } else {
                    exit(self::returnmsg(ErrorCode::$login_error, '登录失败'));
                }
            } else {
                $check_posid['server_maxposseq'] = (int)$check_posid['server_maxposseq'];
                //更新POS的信息
                $up['device_info']          = $data['device_info']; //登录token
                $up['version']              = $data['version'];
                $up['update_time']          = date("YmdHis"); 
                $uptoken                    = M('pos_posinfo')->where(array('pos_id' => $data['pos_id']))->save($up);

                exit(self::returnmsg(ErrorCode::$ok, '登录成功', $check_posid));
            }
        } else {
            exit(self::returnmsg(ErrorCode::$illegal_request, '非法请求'));
        }
    }
    /**
     * APP检查登录
     */
    public function checkPos($data=array())
    {

        if (IS_POST) {
            $data = file_get_contents('php://input', 'r'); //接受post传递过来的json数据
            $data = json_decode($data, true);
            if (empty($data)) {
                exit(self::returnmsg(ErrorCode::$data_error, '数据不合法'));
            }
            $diftime     = abs(NOW_TIME - $data['reqtime']);
            //if($diftime>10){
            //exit(self::returnmsg(ErrorCode::$request_timeout,'请求超时'));
            //}
            $check_posid = M('pos_posinfo')->field('device_info,server_maxposseq')->where(array('pos_id' => $data['pos_id']))->find();
            if (empty($check_posid)) {
                exit(self::returnmsg(ErrorCode::$login_pos_null, '终端不存在'));
            } else {
                $check_posid['server_maxposseq'] = (int)$check_posid['server_maxposseq'];
                exit(self::returnmsg(ErrorCode::$ok, '检查成功', $check_posid));
            }
        } else {
            exit(self::returnmsg(ErrorCode::$illegal_request, '非法请求'));
        }
        
    }
    /**
     * 终端号最大序列号检查
     */
    public function checkPosidSeq($data=array())
    {
        $check_posid = M('pos_posinfo')->where(array('pos_id' => $data['pos_id']))->find();
        if($data['posseq'] < $check_posid['server_maxposseq']){

            $res = array(
                'pos_id' =>$check_posid['pos_id'],
                'server_maxposseq' => (int)$check_posid['server_maxposseq'],
            );
            exit(self::returnmsg(ErrorCode::$posseq_error, '终端序列号异常，请重试',$res));
        }
        
    }

    /**
     * 查询银联昨天最大流水号
     */
    public function checkBankSeq()
    {
        if (IS_POST) {
            $data = file_get_contents('php://input', 'r'); //接受post传递过来的json数据
             $this->log("接收到APP查询银联流水请求数据：" . $data); //json格式
            $data = json_decode($data, true);
            if (empty($data)) {
                exit(self::returnmsg(ErrorCode::$data_error, '数据不合法'));
            }
            $this->checkSign($data);
            $begin = $data['begin'].'000000';//
            $end   = $data['end'].'000000';
            $map['trans_time'] = array('EGT',$begin);
            $map['trans_time'] = array('LT',$end);
            $maxSeq = M('pos_sellpay_trace')->where(array('code_type'=>901,'pos_id'=>$data['pos_id']))->where("`trans_time` >=".$begin." and `trans_time` <".$end)->order("pos_seq desc")->find();
             $this->log("sql语句：".M('pos_sellpay_trace')->getLastSql()); //json格式
            if (empty($maxSeq)) {
                $begin = $data['end'].'000000';    //如果前一天为空，则查询后一天数据
                $end   = date("YmdHis",strtotime($data['end'])+(24*60*60));
                $map['trans_time'] = array('EGT',$begin);
                $map['trans_time'] = array('LT',$end);
                $maxSeq = M('pos_sellpay_trace')->where(array('code_type'=>901,'pos_id'=>$data['pos_id']))->where("`trans_time` >=".$begin." and `trans_time` <".$end)->order("pos_seq desc")->find();
                $this->log("sql语句1：".M('pos_sellpay_trace')->getLastSql()); //json格式
                if(empty($maxSeq)){
                    exit(self::returnmsg(ErrorCode::$login_pos_null, '数据不存在'));
                }else{
                    exit(self::returnmsg(ErrorCode::$other, '查询前后一天成功', $maxSeq));
                }
                
            } else {
                exit(self::returnmsg(ErrorCode::$ok, '检查成功', $maxSeq));
            }
        } else {
            exit(self::returnmsg(ErrorCode::$illegal_request, '非法请求'));
        }
    }

    /**
     * 微信刷卡，京东刷卡付款
     */
    public function pay()
    {
        if (IS_POST) {
            $data = file_get_contents('php://input', 'r');
            $this->log("接收到APP支付请求数据：" . $data); //json格式
            $data = json_decode($data, true);
            if (empty($data)) {
                exit(self::returnmsg(ErrorCode::$data_error, '数据不合法'));
            }
            $data_sign  = $data['sign']; //原始sign
            $data_token = $data['login_token']; //原始token
            unset($data['sign']); //sign不参与签名
            $check_login_token = M('pos_posinfo')->where(array('pos_id' => $data['pos_id']))->find();

            if ($check_login_token['login_timeout'] < NOW_TIME) {
                exit(self::returnmsg(ErrorCode::$token_error, 'token过期'));
            }
            //$data['login_token'] = $check_login_token['login_token']; //生成签名的token取数据库
            $createSign = $this->_createSign($data, $check_login_token['login_token']); //生成签名
            if ($createSign !== $data_sign) { //对比签名
                exit(self::returnmsg(ErrorCode::$sign_error, '签名错误'));
            }
            //exit(self::returnmsg(ErrorCode::$ok,'接口调用通过'));
            //根据auth_code来判断调用何种支付类型，auth_code：13开头为微信支付，18为京东支付，28为支付宝支付
            $pay_type = substr($data['auth_code'], 0, 2); //截取条码开头两位数
            if ($pay_type == 13) {
                 $this->log("调用微信支付"); //json格式
                $this->wxpay($data);
            } elseif ($pay_type == 18) {
                $this->jdpay($data);
            } elseif ($pay_type == 28) {
                $this->zfbpay($data);
            } else {
                exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式'));
            }
        } else {
            exit(self::returnmsg(ErrorCode::$illegal_request, '非法请求'));
        }
    }

    /**
     * 微信条码支付，通过pos_id查找门店对应方式
     */
    private function wxpay($data = array())
    {
        $payconfig = $this->findPayconfg($data['pos_id'], $pay_type = 'wx');
        if ($payconfig === false) {
             $this->log("支付方式不存在："); //json格式
            exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式'));
        }
        $this->checkPosidSeq($data);   //检查序列号
        $this->log("微信支付开始："); //json格式
        $pos_info=M('pos_posinfo')->where(array('pos_id'=>$data['pos_id']))->find();
        $parameter = array(
            'appid' => $payconfig['appid'], //微信ID
            'sub_appid' => $payconfig['sub_appid'],
            'appsecret' => $payconfig['appsecret'], //微信密钥
            'mch_id' => $payconfig['mch_id'], //微信商户ID
            'sub_mch_id' => $payconfig['sub_mch_id'],
            'paykey' => $payconfig['paykey'] //微信商户密钥
        );
        $wxpay     = new \weixin_pay_card($parameter);
        $paydata      = array(
            'orderid' => 'wx'.$data['saledate']. $data['pos_id'] . $data['posseq'],
            'body' => '新上铁集团('.$pos_info['pos_name'].')--购物',
            'total_fee' => $data['je'],
            'auth_code' => $data['auth_code']
        );
        
        $res = $wxpay->micropay($paydata, $atta);
        $this->log("支付返回数据" . json_encode($res)); //json格式
        if ($res["return_code"] == "SUCCESS" && $res["result_code"] == "SUCCESS") {
            $this->log("微信支付成功：" . json_encode($res)); //json格式
            $trans_type = 'T'; //撤销
            $this->insertPayData($res,$data,$trans_type);     //插入支付成功数据
            exit(self::returnmsg(ErrorCode::$ok, '支付成功', $res));
        } else {

            $this->log("微信支付失败：" . json_encode($res)); //json格式
            $trans_type = 'T'; //撤销
            $this->insertWxOrderFail($res,$data,$trans_type );    //插入支付失败数据
            exit(self::returnmsg(ErrorCode::$wxpay_error, '支付失败', $res));
        }
    }
    /**
     * 京东条码支付
     */
    private function jdpay($data = array())
    {
        $payconfig = $this->findPayconfg($data['pos_id'], $pay_type = 'jd');
        if ($payconfig === false) {
            exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式'));
        }
        $this->checkPosidSeq($data);   //检查序列号
        $args['order_no']    = 'jd'.$data['saledate']. $data['pos_id'] . $data['posseq'];
        $args['merchant_no'] = $payconfig['sub_mch_id'];
        $args['amount']      = $data['je'] / 100;
        $args['seed']        = $data['auth_code'];
        $args['notify_url']  = $payconfig['notify_url'];
        $paykey              = $payconfig['paykey'];
        $object              = new \jd_paycode();
        $res                 = $object->execute($args, $paykey);
        if ($res['is_success'] == 'Y') {
            exit(self::returnmsg(ErrorCode::$ok, '支付成功', $res));
        } else {
            exit(self::returnmsg(ErrorCode::$jdpay_error, '支付失败', $res));
        }
        dump($res);
    }
    /**
     * 支付宝条码支付
     */
    private function zfbpay($data = array())
    {
        exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式'));
    }

    /**
     * 微信支付失败操作，撤销失败订单操作，用户需要输入密码、订单号重复、网络超时等失败订单，需要插入数据库
     * @param $res 微信返回支付失败数据，
     * @param $appdata APP端传输过来的调用支付数据，含金额，pos_id等数据
     * @param $trans_type string 交易类型，刷卡交易是为T，撤销或者退未C或R，这里只可能是C，交易失败订单不能发起退款
     */
    public function insertWxOrderFail($res=array(),$appdata=array(),$trans_type='')
    {   
        $out_trade_no = 'wx'.$appdata['saledate']. $appdata['pos_id'] . $appdata['posseq'];  //商户订单号
        $ischeck_insert = M('pos_sellpay_trace')->where(array('pos_id'=>$appdata['pos_id'],'pos_seq'=>$appdata['posseq']))->find();  //是否插入
        if(empty($ischeck_insert)){
            $max['server_maxposseq'] = $appdata['posseq'];
             M('pos_posinfo')->where(array('pos_id'=>$appdata['pos_id']))->save($max);  //更新终端最大流水号
            $pos_info = M('pos_posinfo')->where(array('pos_id'=>$appdata['pos_id']))->find();   //找到终端信息
            $dataadd['node_id'] = $pos_info['node_id'];   //集团id
            $dataadd['store_id'] = $pos_info['store_id']; // 门店id
            $dataadd['pos_id']  =  $appdata['pos_id'];  //终端编号
            $dataadd['pos_seq'] = $appdata['posseq'];   //终端流水号
            $dataadd['trans_type'] = $trans_type;  //交易类型，
            $paysign = 1;
            if($dataadd['trans_type'] == 'C'){
                $paysign = -1;
                $dataadd['org_posseq']    = $appdata['cancel_posseq'];     //源终端流水号
                $dataadd['out_trade_no']  = $appdata['out_trade_no'];       //商户订单号
                $dataadd['exchange_amt']    = $appdata['exchange_amt'];   //交易金额,原数据库金额
                $dataadd['fee_amt']    = $paysign*$appdata['fee_amt'];
                $dataadd['real_amt']    = $paysign * $appdata['real_amt'];  //实收金额，需减去手续费
            }else{
                $dataadd['out_trade_no']  =  $out_trade_no;       //商户订单号
                $dataadd['exchange_amt']    = $appdata['je']/100;   //交易金额,APP传过来为元
                $dataadd['fee_amt']    = round(($paysign*$dataadd['exchange_amt'] * 0.0035),2);  //业务手续费，保留2位小数
                $dataadd['real_amt']    = $paysign * ($dataadd['exchange_amt'] - $dataadd['fee_amt']);  //实收金额，需减去手续费

            }
            $dataadd['status']     = '6';  //失败状态
            $dataadd['cust_id']    = $appdata['custno'];   //会员id
            $dataadd['cust_name']    = '';
            $dataadd['cust_tel']    = '';
            $dataadd['cust_jf']    = '';
            $dataadd['is_canceled']    = 0;   //是否撤销
            
            $dataadd['trans_time']    = date("YmdHis");     //交易时间
            
            $dataadd['ret_code']    = '9998';       //返回码，数据库为此值，不明白其意思
            $dataadd['ret_desc']    = $res['err_code'].$res['err_code_des'].'成功';       //返回描述
            $dataadd['user_id']    = '00000000';
            $dataadd['user_name']    = '';
            $dataadd['code_type']    = 101;    //支付类型
            $dataadd['syn_seq']    = '';       //同步流水号
            $dataadd['ori_syn_seq']    = '';   //源同步流水号
            
            $dataadd['openid']    = '';       //买家用户号
            $dataadd['trade_no']    = '';       //微信订单号，此时为空
           
            $dataadd['frate']        = '0.006';     //手续费费率
            $dataadd['out_pos_seq']    = '';       //
            
            $dataadd['post_data']    = json_encode($res);       //返回数据json格式存入表中
            $dataadd['add_time']    = date("Y-m-d H:i:s",time());       //
            $this->log("微信支付失败或者撤销插入数据：".$paysign . json_encode($dataadd)); //json格式
            M('pos_sellpay_trace')->add($dataadd);
            if($trans_type == 'C'){       //如果是交易失败进行撤销，需要更新原失败订单状态与原始交易号；
                $where['pos_id'] = $appdata['pos_id'];      //pos_id
                $where['pos_seq'] = $appdata['cancel_posseq'];   //原始订单号，更新原始订单号撤销状态等
                $update['is_canceled'] =1;
                $update['cancel_pos_seq'] = $appdata['posseq'];
                M('pos_sellpay_trace')->where($where)->save($update);
            }
         }
    }

     /**
     * APP银联支付信息插入数据库
     *
     * // amount=000000000001&batchNo=001744&cardNo=6217002870007895889&date=1221&fsdate=1482308980&issue=%E5%BB%BA%E8%AE%BE%E9%93%B6%E8%A1%8C&merchantId=102310070110868&merchantName=%E4%B8%AD%E5%9B%BD%E9%93%B6%E8%81%94%E6%B5%8B%E8%AF%95%E5%95%86%E6%88%B7&pos_id=1000195407&referenceNo=162954322778&terminalId=31809001&time=162954&traceName=%E6%B6%88%E8%B4%B9&traceNo=000639&type=1&user_id=123&login_token=q459zejhzx 1f75ef7c1c3c6b9859399c4cc53a51bd
     * 1、pos_seq : batch_no加上trace_no
     * 2、C撤消R退货时：把原来的T的记录，is_canceled修改为1，cancel_pos_seq修改成现在的pos_seq
     * R退款时：时根据pos_seq等于参数oldReferenceNo的值
     * C撤消时：根据pos_seq等于参数batch_no拼上oldTrace的值
     * 遇到R和C时，把原来为T的交易的cancel_pos_seq修改成新的pos_seq
     * 3、real_amt在T和C时为负数
     * 4、trade_no和out_trade_no的值取自：reference_no
     */
    public function insertBankData($res=array())
    {
       
        $ischeck_insert = M('pos_sellpay_trace')->where(array('out_trade_no'=>$res['referenceNo']))->find();
        if(empty($ischeck_insert)){
            $max['server_maxposseq'] = $res['batchNo'].$res['traceNo'];
             M('pos_posinfo')->where(array('pos_id'=>$appdata['pos_id']))->save($max);  //更新终端最大流水号
            $pos_info = M('pos_posinfo')->where(array('pos_id'=>$res['pos_id']))->find();   //找到终端信息
            $dataadd['node_id'] = $pos_info['node_id'];   //集团id
            $dataadd['store_id'] = $pos_info['store_id']; // 门店id
            $dataadd['pos_id']  =  $res['pos_id'];  //终端编号
            $dataadd['pos_seq'] = $res['batchNo'].$res['traceNo'];   //终端流水号
            $dataadd['trans_type'] = $res['transType'];  //交易类型，
            $paysign = 1;
            if($dataadd['trans_type'] == 'C' || $dataadd['trans_type'] == 'R'){
                $paysign = -1;
                $dataadd['frate']    = 0.0038;
                $dataadd['exchange_amt']    =$res['amount']/100;   //交易金额
                $dataadd['fee_amt']    = $paysign * (round(($dataadd['frate'] * $res['amount']/100),2)); //$dataadd['frate'] * $res['amount']/100; //业务手续费（支付宝线下支付）
                $dataadd['real_amt']    = $paysign * ($dataadd['exchange_amt'] - (round(($dataadd['frate'] * $res['amount']/100),2))); //(round(($dataadd['exchange_amt'] * 0.0035),2))
                
            }else{
                $dataadd['frate']    = 0.0038;
                $dataadd['exchange_amt']    =$res['amount']/100;   //交易金额
                $dataadd['fee_amt']    = $paysign * (round(($dataadd['frate'] * $res['amount']/100),2));       //业务手续费（支付宝线下支付）
                $dataadd['real_amt']    = ($dataadd['exchange_amt'] - $dataadd['fee_amt']);      //
            }

            $dataadd['status']     = '0';
            $dataadd['cust_id']    = $res['cust_id'];   //会员卡号
            $dataadd['cust_name']    = '';
            $dataadd['cust_tel']    = '';
            $dataadd['cust_jf']    = ''; 
            $dataadd['is_canceled']    = 0;   //是否撤销
            $dataadd['cancel_pos_seq']  = ''; //撤销此交易的终端流水号
            //$dataadd['exchange_amt']    =$res['amount']/100;   //交易金额
            $dataadd['trans_time']    = date("Y",time()).$res['date'].$res['time'];     //交易时间
            $dataadd['org_posseq']    = '';     //源终端流水号
            $dataadd['ret_code']    = '';       //返回码
            $dataadd['ret_desc']    = '';       //返回描述
            $dataadd['user_id']    = '';
            $dataadd['user_name']    = $res['cardNo'];//银行卡号
            $dataadd['code_type']    = 901;    //支付类型
            $dataadd['syn_seq']    = '';       //同步流水号
            $dataadd['ori_syn_seq']    = '';   //源同步流水号
            //$dataadd['frate']    = 0.0038;
            //$dataadd['fee_amt']    = $paysign * $dataadd['frate'] * $res['amount']/100;       //业务手续费（支付宝线下支付）
            $dataadd['openid']    = $res['cardNo'];       //买家用户号
            $dataadd['trade_no']    = $res['referenceNo'];       //
            $dataadd['out_trade_no']    = $res['referenceNo'];       //
            $dataadd['out_pos_seq']    = '';       //
            //$dataadd['real_amt']    = $paysign * ($dataadd['exchange_amt'] - $dataadd['fee_amt']);      //
            $dataadd['post_data']    = '';       //
            $dataadd['add_time']    = date("Y-m-d H:i:s",time());       //
            $this->log("银联插入数据：" . json_encode($dataadd)); //json格式
            M('pos_sellpay_trace')->add($dataadd);

            $cust_id = $dataadd['cust_id'];

            if($dataadd['trans_type'] == 'C' || $dataadd['trans_type'] == 'R'){
                //R退款时：时根据tradeno等于参数oldReferenceNo的值
                //C撤消时：根据pos_seq等于参数batch_no拼上oldTrace的值

                $where['pos_id'] = $dataadd['pos_id'];
                 if($dataadd['trans_type'] == 'C'){
                        //array('pos_id'=>$dataadd['pos_id'],'pos_seq'=>$oldPosSeq,'trans_type'=>'T')
                    $where['pos_seq'] = $res['batchNo'].$res['oldTrace'];
                 }else{
                    $where['out_trade_no'] = $res['oldReferenceNo'];
                 }
                //一定要加上posid

                $update['is_canceled'] =1;
                $update['cancel_pos_seq'] = $dataadd['pos_seq'];

                M('pos_sellpay_trace')->where($where)->save($update);

                //找到原来的会员卡号，把积分扣下来
               $oldCust =  M('pos_sellpay_trace')->field('cust_id')->where($where)->find();
               if($oldCust){
                 $cust_id = $oldCust['cust_id'];
               }

            }
            //增加积分并发送模板消息
            if($cust_id){
                $para = array(
                    'node_id' => $pos_info['node_id'],//租户号
                    'card_number' => $cust_id ,//卡号
                    'card_phone' => '',//手机号
                    'openid' => '',//openid
                    'trans_time' => $dataadd['trans_time'],//发生日期
                    'trans_type' => $dataadd['trans_type']=='T' ? '消费' : '退款',//变动类型
                    'key' => $dataadd['pos_seq'],//记录一些值，如门店或终端号
                    'jf' => $paysign * $dataadd['exchange_amt'],//发生的积分
                );
                $this->_addJf($para);
            }
         }
    }


    /**
     * 微信支付订单信息插入数据库,或者撤销订单或退款
     */
    public function insertPayData($res=array(),$appdata=array(),$trans_type='')
    {
         $this->log("已支付订单，成功或撤销插入数据：" . json_encode($appdata)); //json格式

        $ischeck_insert = M('pos_sellpay_trace')->where(array('pos_id'=>$appdata['pos_id'],'pos_seq'=>$appdata['posseq']))->find();
        if(empty($ischeck_insert)){
             $max['server_maxposseq'] = $appdata['posseq'];
             M('pos_posinfo')->where(array('pos_id'=>$appdata['pos_id']))->save($max);  //更新终端最大流水号
            $pos_info = M('pos_posinfo')->where(array('pos_id'=>$appdata['pos_id']))->find();   //找到终端信息
            $dataadd['node_id'] = $pos_info['node_id'];   //集团id
            $dataadd['store_id'] = $pos_info['store_id']; // 门店id
            $dataadd['pos_id']  =  $appdata['pos_id'];  //终端编号
            $dataadd['pos_seq'] = $appdata['posseq'];   //终端流水号
            $dataadd['trans_type'] = $trans_type;  //交易类型，

            $paysign = 1;
            if($dataadd['trans_type'] == 'C' || $trans_type == 'R'){      //撤销订单
                $paysign = -1;
                $dataadd['org_posseq']    = $appdata['cancel_posseq'];     //源终端流水号
                $dataadd['exchange_amt']  = $appdata['exchange_amt'];   //交易金额
                $dataadd['trans_time']    = date("YmdHis");     //交易时间
                $dataadd['openid']        = $appdata['openid'];       //买家用户号
                $dataadd['trade_no']      = $appdata['trade_no'];       //
                $dataadd['out_trade_no']  = $appdata['out_trade_no'];       //
                $dataadd['fee_amt']    = $paysign*$appdata['fee_amt'];  //业务手续费，保留2位小数
                $dataadd['real_amt']    = $paysign*$appdata['real_amt']; //实收金额
                $dataadd['cust_id']    = $appdata['cust_id'];   //从原数据库中找出来的

            }else{     //正常支付订单

                $dataadd['cust_id']    = $appdata['custno'];   //会员id
                $dataadd['exchange_amt']    = $res['total_fee']/100;   //交易金额
                $dataadd['trans_time']    = $res['time_end'];     //交易时间
                $dataadd['openid']    = $res['sub_openid'];       //买家用户号
                $dataadd['trade_no']    = $res['transaction_id'];       //
                $dataadd['out_trade_no']    = $res['out_trade_no'];       //
                $dataadd['fee_amt']    = $paysign*(round(($dataadd['exchange_amt'] * 0.0035),2));  //业务手续费，保留2位小数
                $dataadd['real_amt']    = $paysign*(($dataadd['exchange_amt'])-$dataadd['fee_amt']); //实收金额
            }

            $dataadd['status']     = '0';
            $dataadd['cust_name']    = '';
            $dataadd['cust_tel']    = '';
            $dataadd['cust_jf']    = '';
            $dataadd['is_canceled']    = 0;   //是否撤销
            $dataadd['cancel_pos_seq']  = ''; //撤销此交易的终端流水号

            $dataadd['ret_code']    = 0;       //返回码支付成功
            $dataadd['ret_desc']    = $res['return_msg'].'交易成功';       //返回描述
            $dataadd['user_id']    = '00000000';
            $dataadd['user_name']    = '';
            $dataadd['code_type']    = 101;    //支付类型
            $dataadd['syn_seq']    = '';       //同步流水号
            $dataadd['ori_syn_seq']    = '';   //源同步流水号
            

            $dataadd['out_pos_seq']    = '';       //
            $dataadd['frate']        = '0.006';     //手续费费率
            
            $dataadd['post_data']    = json_encode($res);       //
            $dataadd['add_time']    = date("Y-m-d H:i:s",time());       //
            $this->log("微信支付插入数据：" . json_encode($dataadd)); //json格式
            M('pos_sellpay_trace')->add($dataadd);
 

            if($trans_type == 'C' || $trans_type == 'R'){       //如果是交易成功进行撤销，需要更新原成功订单状态与原始交易号；

                $where['pos_id'] = $appdata['pos_id'];      //pos_id
                $where['pos_seq'] = $appdata['cancel_posseq'];   //原始订单号，更新原始订单号撤销状态等
                $update['is_canceled'] =1;
                $update['cancel_pos_seq'] = $dataadd['pos_seq'];

                M('pos_sellpay_trace')->where($where)->save($update);
                $this->log("撤销更新");
            
            }


            //增加积分并发送模板消息
            $cust_id = $dataadd['cust_id'];

            $node = M('pos_node')->where(array('node_id'=>$pos_info['node_id']))->find();  
            $config = M('config')->where(array('name'=>'point_rate'))->find();
            if( empty($config['value']) || $config['value'] == ''){    //积分配置
                $config['value'] = 0;
            }
            if($cust_id){
              
                $para = array(
                    'node_id' => $pos_info['node_id'],//租户号
                    'token'=>$node['wx_id'],
                    'card_number' => $cust_id ,//卡号
                    'card_phone' => '',//手机号
                    'openid' => $res['sub_openid'],//openid
                    'trans_time' => $dataadd['trans_time'],//发生日期
                    'trans_type' => $dataadd['trans_type']=='T' ? '消费' : '退款',//变动类型
                    'key' => $dataadd['pos_seq'],//记录一些值，如门店或终端号
                    'jf' => $paysign * $dataadd['exchange_amt'] * $config['value'],//发生的积分
                );
            }else{
                $para = array(
                    'node_id' => $pos_info['node_id'],//租户号
                    'token'=>$node['wx_id'],
                    'card_number' => '' ,//卡号
                    'card_phone' => '',//手机号
                    'openid' => $res['sub_openid'],//openid
                    'trans_time' => $dataadd['trans_time'],//发生日期
                    'trans_type' => $dataadd['trans_type']=='T' ? '消费' : '退款',//变动类型
                    'key' => $dataadd['pos_seq'],//记录一些值，如门店或终端号
                    'jf' => $paysign * $dataadd['exchange_amt'] * $config['value'],//发生的积分
                );
            }

            //判断是否为会员
            $is_vipuser = M('users')->where(array('openid'=>$res['sub_openid']))->find();
            if(!empty($is_vipuser)){
                $this->log("start tempmsg:".json_encode($para));
                $this->_addJf($para);
            }
            
        }
    }


    /**
     * 插入错误的订单数据到数据库中，出现情况，用户刷卡需要输入密码，微信返回结果为支付失败，此时需要把返回失败订单插入数据库
     * 如果APP查询6次后，方便对此订单进行撤销
     */

    /**
     * 银联支付数据插入表中
     */
    public function insertUnionData()
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP银联请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        //有中文，单独处理签名
        $this->_createSignZH($data);
        //插入数据
        $this->insertBankData($data);

        exit(self::returnmsg(ErrorCode::$ok, '接口调用成功'));
    }

    /**
     * 更新输入密码的支付成功的订单
     */
    public function updatePayDate($res=array(),$data= array(),$status='')
    {   

        $this->log("更新查询订单"); //json格式
        if($status == 'ok'){
            $update['status'] = 0;
        }else{
            $update['status'] = 1;
        }
        
        $update['ret_code']=0;
        $update['ret_desc']=$res['trade_state_desc'].$res['trade_state_desc'];
        $update['openid'] = $res['sub_openid'];
        $update['post_data'] =json_encode($res);
        $update['trade_no'] = $res['transaction_id'];
        M('pos_sellpay_trace')->where(array('pos_id'=>$data['pos_id'],'pos_seq'=>$data['posseq'],'out_trade_no'=>$res['out_trade_no']))->save($update);
        
    }

    /**
     * 调用微信支付接口查询订单
     * @param find_type查询类型，1、普通查询：find，2、刷卡输入密码查询：find_micropay，3、find_cancel，
     */
    public function queryWxpay()
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP查询订单请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        $this->checkSign($data);

        $payconfig = $this->findPayconfg($data['pos_id'], $pay_type = 'wx');
        if ($payconfig === false) {
            exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式,查询失败'));
        }
        $parameter = array(
            'appid' => $payconfig['appid'], //微信ID
            'sub_appid' => $payconfig['sub_appid'],
            'appsecret' => $payconfig['appsecret'], //微信密钥
            'mch_id' => $payconfig['mch_id'], //微信商户ID
            'sub_mch_id' => $payconfig['sub_mch_id'],
            'paykey' => $payconfig['paykey'] //微信商户密钥
        );

        $orderid = array(
            'out_trade_no' => 'wx'.$data['saledate'].$data['pos_id'].$data['posseq'],
        );

        $queryorder     = new \weixin_pay_card($parameter);
        $res       = $queryorder->orderquery($orderid);
        if ($res["return_code"] == "SUCCESS" && $res["result_code"] == "SUCCESS") {
            $this->log("微信等待支付查询成功：" . json_encode($res)); //json格式
            
            if($data['find_type'] == 'find'){     //一般查询，不做操作

                exit(self::returnmsg(ErrorCode::$ok, '查询成功', $res));

            }elseif($data['find_type'] == 'find_micropay'){   //刷卡输入密码查询
                if($res['trade_state'] == 'SUCCESS'){         //查询支付成功后，插入数据库

                    $status = 'ok';
                    $this->updatePayDate($res,$data,$status);
                    exit(self::returnmsg(ErrorCode::$ok, 'userpaying查询成功，用户支付成功', $res));
                }else{    //用户中途取消支付

                    $status = 'fail';
                    $this->updatePayDate($res,$data,$status);
                    exit(self::returnmsg(ErrorCode::$ok, 'userpaying查询成功，用户取消支付', $res));
                }

            }elseif($data['find_type'] == 'find_cancel'){   //订单没有支付查询，一直没有支付，

                $updatetype = 'cancel';
                //$this->updatePayDate($orderid['out_trade_no'],$updatetype);
                exit(self::returnmsg(ErrorCode::$ok, 'cancel查询成功', $res));

            }else{
                exit(self::returnmsg(ErrorCode::$default_error, '请传入查询参数'));
            }

        } else {
            exit(self::returnmsg(ErrorCode::$wxpay_error, '查询失败!请重试', $res));
        }
    }

    /**
     * 订单撤销,撤销类型：cancel_type:userpaying_cancel  ;//刷卡后，要求用户输入密码，APP进行6次查询，如果6次查询还是失败，不管用户付款没，直接撤销该订单
     * 撤销序列号，需撤销订单的序列号
     */
    public function wxOrderCancel($data=array())
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP撤销订单请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        $this->checkSign($data);
        $this->checkPosidSeq($data);   //检查序列号
        $payconfig = $this->findPayconfg($data['pos_id'], $pay_type = 'wx');
        if ($payconfig === false) {
            exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式,查询失败'));
        }
        $parameter = array(
            'appid' => $payconfig['appid'], //微信ID
            'sub_appid' => $payconfig['sub_appid'],
            'appsecret' => $payconfig['appsecret'], //微信密钥
            'mch_id' => $payconfig['mch_id'], //微信商户ID
            'sub_mch_id' => $payconfig['sub_mch_id'],
            'paykey' => $payconfig['paykey'] //微信商户密钥
        );
        $password = $data['password'];      //撤销密码
        $orderid = array(
            'out_trade_no' => 'wx'.$data['saledate'].$data['pos_id'].$data['cancel_posseq'],   //需要撤销的订单号，还需要有一个发起请求的流水号posseq
        );
        if($data['cancel_type'] !== 'userpaying_cancel'){   //如果撤销类型为用户付款输入密码后撤销，则不需要密码，直接撤销该笔订单
            $ischeck_pwd = M('pos_posinfo')->where(array('pos_id'=>$data['pos_id'],'password'=>$password))->find();
            if(empty($password) || empty($ischeck_pwd)){
                exit(self::returnmsg(ErrorCode::$default_error, '撤销交易密码错误，请重新输入'));
            }
            $checkorder = M('pos_sellpay_trace')->where(array('out_trade_no'=>$orderid['out_trade_no'],'trans_type'=>'T','status'=>0,'is_canceled'=>0))->find();
            $this->log("申请撤销,sql语句：" . M('pos_sellpay_trace')->getLastSql()); //json格式
            if(empty($checkorder)){
                exit(self::returnmsg(ErrorCode::$default_error, '数据库该订单没有支付或不存在，不支持撤销'));
            }
        }else{    //如果是userpaying撤销，直接做撤销处理，不调用微信撤销
            exit(self::returnmsg(ErrorCode::$default_error, '交易超时，请确认银行卡是否扣款成功，联系管理员！'));
        }
        
        $queryorder     = new \weixin_pay_card($parameter);
        $res = $queryorder->reverse($orderid);
        if ($res["return_code"] == "SUCCESS" && $res["result_code"] == "SUCCESS") {
            $this->log("微信订单撤销成功：" . json_encode($res)); //json格式
            
            if($data['cancel_type'] == 'userpaying_cancel'){        //输入密码时撤销，用户并没有支付

                $trans_type = 'C'; //撤销
                $appdata = M('pos_sellpay_trace')->where(array('pos_id'=>$data['pos_id'],'out_trade_no'=>$orderid['out_trade_no'],'is_canceled'=>0))->find();
                $appdata['posseq'] = $data['posseq'];   //撤销流水号
                $appdata['cancel_posseq'] = $data['cancel_posseq']; //旧的流水号
                $res['sub_openid'] = $appdata['openid'];
                $this->insertWxOrderFail($res,$appdata,$trans_type);   //撤销支付不成功订单
                exit(self::returnmsg(ErrorCode::$ok, '撤销成功', $res));
            }else{

                $trans_type = 'C'; //撤销
                $appdata = M('pos_sellpay_trace')->where(array('pos_id'=>$data['pos_id'],'out_trade_no'=>$orderid['out_trade_no'],'is_canceled'=>0))->find();
                $appdata['posseq'] = $data['posseq'];//撤销流水号
                $appdata['cancel_posseq'] = $data['cancel_posseq']; //旧的流水号
                $res['sub_openid'] = $appdata['openid'];
                $this->insertPayData($res,$appdata,$trans_type);       //撤销支付成功订单
                exit(self::returnmsg(ErrorCode::$ok, '撤销成功', $res));

            }
        } else {
            $this->log("微信订单撤销失败：" . json_encode($res)); //json格式
            exit(self::returnmsg(ErrorCode::$default_error, '撤销失败!posseq:', $res));
        }
    }

    /**
     * 微信退款操作
     */
    public function wxOrderRefund($data=array())
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP撤销订单请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        $this->checkSign($data);
        $this->checkPosidSeq($data);   //检查序列号
        $payconfig = $this->findPayconfg($data['pos_id'], $pay_type = 'wx');
        if ($payconfig === false) {
            exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式,查询失败'));
        }
        $parameter = array(
            'appid' => $payconfig['appid'], //微信ID
            'sub_appid' => $payconfig['sub_appid'],
            'appsecret' => $payconfig['appsecret'], //微信密钥
            'mch_id' => $payconfig['mch_id'], //微信商户ID
            'sub_mch_id' => $payconfig['sub_mch_id'],
            'paykey' => $payconfig['paykey'] //微信商户密钥
        );

        $orderid = array(
            'out_trade_no' => 'wx'.$data['saledate'].$data['pos_id'].$data['posseq'],
        );
        $refund_id = 'wxtk'.$data['pos_id'].$data['posseq'];
        $checkorder = M('pos_sellpay_trace')->where(array('out_trade_no'=>$orderid['out_trade_no'],'trans_type'=>'T'))->find();
        if(empty($checkorder)){
            exit(self::returnmsg(ErrorCode::$default_error, '该订单没有支付，不支持退款'));
        }
        $queryorder     = new \weixin_pay_card($parameter);
        $res = $queryorder->refund($refund_id,$checkorder['exchange_amt'],$checkorder['exchange_amt'],$orderid);
        if ($res["return_code"] == "SUCCESS" && $res["result_code"] == "SUCCESS") {
            $this->log("微信订单退款成功：" . json_encode($res)); //json格式
            $updatetype = 'refund';
            $this->updatePayDate($orderid['out_trade_no'],$updatetype);
            exit(self::returnmsg(ErrorCode::$ok, '撤销成功', $res));
        } else {
            exit(self::returnmsg(ErrorCode::$default_error, '撤销失败!posseq:', $res));
        }
    }

    /**
     * 微信退款查询
     */
    public function wxOrderRefundQuery($data=array())
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP查询退款订单请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        $this->checkSign($data);
        $payconfig = $this->findPayconfg($data['pos_id'], $pay_type = 'wx');
        if ($payconfig === false) {
            exit(self::returnmsg(ErrorCode::$paytype_error, '不存在的支付方式,查询失败'));
        }
        $parameter = array(
            'appid' => $payconfig['appid'], //微信ID
            'sub_appid' => $payconfig['sub_appid'],
            'appsecret' => $payconfig['appsecret'], //微信密钥
            'mch_id' => $payconfig['mch_id'], //微信商户ID
            'sub_mch_id' => $payconfig['sub_mch_id'],
            'paykey' => $payconfig['paykey'] //微信商户密钥
        );

        $orderid = array(
            'out_trade_no' => 'wx'.$data['saledate'].$data['pos_id'].$data['posseq'],
        );

        $queryorder     = new \weixin_pay_card($parameter);
        $res       = $queryorder->refundquery($orderid);
        if ($res["return_code"] == "SUCCESS" && $res["result_code"] == "SUCCESS") {
             $this->log("微信退款查询成功：" . json_encode($res)); //json格式
            exit(self::returnmsg(ErrorCode::$ok, '查询成功', $res));
        } else {
            exit(self::returnmsg(ErrorCode::$default_error, '查询失败!posseq:', $res));
        }
    }

    /**
     * 得到会员信息接口
     */
    public function getVipUser()
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到查询会员请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        $cardno = $data['cust_tel'];
        $map['number|mobile'] = $data['cust_tel'];;
        $list = M('users')->where($map)->find();
        if(empty($list)){
            exit(self::returnmsg(ErrorCode::$default_error, '查询为空，请重试'));
        }
        $res = array(

            'cust_id'=>$list['number'],
            'cust_name'=>$list['nickname'],
            'cust_tel'=>$list['mobile'],
            'cust_jf'=>$list['pay_points'],
            'cust_grade'=>''
            );
        exit(self::returnmsg(ErrorCode::$ok, '查询成功', $res));
    }
     /**
     * App日结日结数据查询接口
     */
    public function queryDaySum()
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP日结请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        //$this->checkSign($data);
        //$data['saledate']='1479289394';
        $appdata = strtotime($data['saledate']);
        $yearmonthday =date("Ymd",$appdata);     //传过来的时间戳转换为年月日
        $yearmonth = date("Ym",$appdata);
        $pos_id = $data['pos_id'];

//当天汇总
        $daysum = M()->query("select sum(verify_amt + cancel_amt + revoke_amt) sdje,sum(verify_cnt) sdbs,
        sum(cancel_amt + revoke_amt) cxje,sum(cancel_cnt+ revoke_cnt) cxbs,0 hxsdje,0 hxsdbs,0 hxcxje, 0 hxcxbs 
        from tp_pos_sellpay_day
        where pos_id='{$pos_id}'
        and trans_date = '{$yearmonthday}'
        and from_type in ('101')");  //只显示微信金额

        $this->log("接收到APPqueryDaySum,sql语句：" . M()->getLastSql()); //json格式
        $res = array(
                'sdje'=>round($daysum[0]['sdje'],2), 
                'sdbs'=>$daysum[0]['sdbs'], 
                'cxje'=>$daysum[0]['cxje'], 
                'cxbs'=>$daysum[0]['cxbs'], 
                'hxsdje'=>$daysum[0]['hxsdje'], 
                'hxsdbs'=>$daysum[0]['hxsdbs'], 
                'hxcxje'=>$daysum[0]['hxcxje'], 
                'hxcxbs'=>$daysum[0]['hxcxbs'], 
               
            );
         exit(self::returnmsg(ErrorCode::$ok, '查询成功', $res));

    }
    /**
     * 查询数据库订单销售汇总查询接口
     */
    public function querySaleSum()
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP查询汇总订单请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        //$this->checkSign($data);
        //$data['saledate']='1479289394';
        $yearmonthday =date("Ymd",$data['saledate']);     //传过来的时间戳转换为年月日
        $yearmonth = date("Ym",$data['saledate'])."00";
        $pos_id = $data['pos_id'];
        
        // $paymode = $data['paymode'];//银联901,微信101，所有的ALL

        // if(!empty($paymode)){
        //     if($paymode=="101")//表示移动支付
        //         $map['from_type'] = array('neq','901');
        //     else
        //         $map['from_type'] = $paymode;
        // }
        $daysum = M()->query("select sum(verify_amt + cancel_amt + revoke_amt) sdje,
        sum(cancel_amt + revoke_amt) cxje,
     IFNULL(sum(CASE `from_type` WHEN '101' then verify_amt + cancel_amt + revoke_amt ELSE 0 end),0) je_101,
     IFNULL(sum(CASE `from_type` WHEN '901' then verify_amt + cancel_amt + revoke_amt ELSE 0 end),0) je_901,


     IFNULL(sum(CASE `from_type` WHEN '101' then cancel_amt + revoke_amt ELSE 0 end),0) cx_101,
     IFNULL(sum(CASE `from_type` WHEN '901' then cancel_amt + revoke_amt ELSE 0 end),0) cx_901

        from tp_pos_sellpay_day where `pos_id`='{$pos_id}' and `trans_date` >= '{$yearmonthday}' and from_type in ('101', '901')");   //当天汇总
         $this->log("接收到APPdaysum,sql语句：" . M()->getLastSql()); //json格式
        $monthsum = M()->query("select sum(verify_amt + cancel_amt + revoke_amt) sdje,
        sum(cancel_amt + revoke_amt) cxje,
     IFNULL(sum(CASE `from_type` WHEN '101' then verify_amt + cancel_amt + revoke_amt ELSE 0 end),0) je_101,
     IFNULL(sum(CASE `from_type` WHEN '901' then verify_amt + cancel_amt + revoke_amt ELSE 0 end),0) je_901,

     IFNULL(sum(CASE `from_type` WHEN '101' then cancel_amt + revoke_amt ELSE 0 end),0) cx_101,
     IFNULL(sum(CASE `from_type` WHEN '901' then cancel_amt + revoke_amt ELSE 0 end),0) cx_901

        from tp_pos_sellpay_day  where `pos_id`='{$pos_id}' and `trans_date`>= '{$yearmonth}' and from_type in ('101', '901')"); //当月汇总
        $this->log("接收到APPmonthsum,sql语句：" . M()->getLastSql()); //json格式
        $res = array(

            'thisday'=>array(
                'sdje'=>$daysum[0]['sdje'],
                'cxje'=>$daysum[0]['cxje'],
                'je_101'=>round(($daysum[0]['je_101']),2),
                'je_901'=>round(($daysum[0]['je_901']),2),
                'cx_101'=>$daysum[0]['cx_101'],
                'cx_901'=>$daysum[0]['cx_901'],
                ),
            'thismonth'=>array(
                'sdje'=>round(($monthsum[0]['sdje']),2),   //保留2位小数
                'cxje'=>$monthsum[0]['cxje'],
                'je_101'=>round(($monthsum[0]['je_101']),2),
                'je_901'=>round(($monthsum[0]['je_901']),2),
                'cx_101'=>$monthsum[0]['cx_101'],
                'cx_901'=>$monthsum[0]['cx_901'],
                ),
               
            );
         exit(self::returnmsg(ErrorCode::$ok, '查询成功', $res));
        if(!empty($daysum) || !empty($monthsum)){

            exit(self::returnmsg(ErrorCode::$default_error, '查询失败'));
         
        }else{

            exit(self::returnmsg(ErrorCode::$ok, '查询成功', $res));
        }
    }

    /**
     * 数据库查询交易明细数据查询接口
     */
    public function querySaleDetail()
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到APP查询订单明细请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        //$this->checkSign($data);
        $saledate = $data['saledate'];    // 日期,时间戳形式
        $paymode = $data['paymode'];     //支付方式 ，只支持移动支付101、银联901，京东没有
        $paystatus = (string)$data['paystatus'];  //支付状态  交易状态 0:成功 1:失败, 2.撤销
        $paytrans_type = $data['trans_type'];  //支付类型  交易类型 T-线下支付 R-支付退款 C-支付撤销
        $posseq =  $data['posseq'];       //序列号    
        $page_size = $data['page_size'];   //页面大小  默认为10
        $page_no = $data['page_no'];        //页面序号  默认为0

        $map = array();
        $map['pos_id'] = $data['pos_id'];
        if(!empty($posseq)){//根据流水号去查
            $map['pos_seq'] = $data['posseq'];
        }
        if(!empty($paymode)){
            if($paymode=="101")//表示移动支付
                $map['code_type'] = array('not in','901,100');//array('neq','901');
            else
                $map['code_type'] = $paymode;
        }

        // if(empty($paytrans_type)){
            
        //      exit(self::returnmsg(ErrorCode::$default_error, '缺少参数，交易类型'));

        // }elseif($paytrans_type == 'C'){
        //     $map['trans_type'] = array('in','R,C');

        // }else{

        //     $map['trans_type'] = $paytrans_type;
        // }

        if($paystatus === "0"){     //支付成功的订单
            $map['trans_type'] = 'T';
            $map['status'] = 0;
            if( $paymode == '901'){
                $map['is_canceled'] = array('in','1,0');   //银联支付成功显示交易成功与撤销成功的订单
            }else{
                $map['is_canceled'] = 0;
            }
            
        }elseif($paystatus === "1"){  //支付失败的的订单
            $map['trans_type'] = 'T';
            $map['status'] = 1;
        }elseif($paystatus === "2"){  //撤销的订单

            //$map['trans_type'] = "T";
            if( $paymode == '901'){
                $map['trans_type'] = array('in','C,R');     //银联查询撤销订单
            }else{
                $map['trans_type'] = "T";
                $map['status'] = 0;
                $map['is_canceled'] = 1;
            }
        }else{

        }

        if(empty($page_size)){

            $page_size =10;
        }

        if(empty($page_no)){

            $page_no = 0;
        }else{
            $page_no = 10*$page_no;
        }

        $list = M('pos_sellpay_trace')->field("code_type,CONCAT(substring(trans_time,1,4),'-',substring(trans_time,5,2),'-',substring(trans_time,7,2),' ',substring(trans_time,9,2),':',substring(trans_time,11,2),':',substring(trans_time,13,2)) add_time,pos_seq, exchange_amt as real_amt")->where($map)->order('id desc')->limit($page_no,$page_size)->select();
        $this->log("接收到APP查询订单明细请求数据sql语句：" . M('pos_sellpay_trace')->getLastSql()); //json格式
        if(empty($list)){

            exit(self::returnmsg(ErrorCode::$default_error, '查询失败',$list));
         
        }else{

            exit(self::returnmsg(ErrorCode::$ok, '查询成功', $list));
        }

    }

    /**
     * 签名检测
     */
    public function checkSign($data = array())
    {
        if (empty($data)) {
            exit(self::returnmsg(ErrorCode::$data_error, '数据不合法'));
        }
        $nowtime = time();
        $diftime     = abs(time() - $data['reqtime']);
        $this->log("APP时间与服务器时间对比：".time()."app时间戳：".$data['reqtime']);
        if($diftime>40){
            exit(self::returnmsg(ErrorCode::$request_timeout,'终端时间与支付平台时间不一致，请检查终端时间设置'));
        }
        $data_sign = $data['sign']; //原始sign
        unset($data['sign']); //sign不参与签名
        $check_login_token = M('pos_posinfo')->where(array('pos_id' => $data['pos_id']))->find();
        if ($check_login_token['login_timeout'] < NOW_TIME) {
            exit(self::returnmsg(ErrorCode::$token_error, 'token过期'));
        }
        //$data['login_token'] = $check_login_token['login_token']; //生成签名的token取数据库
        $createSign = $this->_createSign($data, $check_login_token['login_token']); //生成签名
        if ($createSign !== $data_sign) { //对比签名
            exit(self::returnmsg(ErrorCode::$sign_error, '签名错误'));
        }
    }

    /**
     * 刷卡支付扫码测试
     */
    public function scan()
    {
        $jssdk       = new JSSDK("wx7e4a1d43eae038d0", "0a0cc1eea55438f351655df4e0b263c2");
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage', $signPackage);
        $this->display();
    }
    /**
     * 通过posid查询门店对应的支付配置数据
     * @param pos_id 终端id
     * @param $pay_type 何种支付方式，目前有微信、京东支付,wx,jd
     * @return 微信或京东支付配置信息
     */
    public function findPayconfg($pos_id = '', $pay_type = '')
    {
        $storeconfig = M('view_pos_posinfo', null)->where(array('pos_id' => $pos_id))->find();
        $configinfo  = M('pos_paymode')->where(array('store_id' => $storeconfig['store_id'],'pay_config_type' => $pay_type))->find();
        if (empty($configinfo)) {
            return false;
        } else {
            return M('pos_payconfig')->where(array('id' => $configinfo['pay_config_id'],'pay_type' => $pay_type))->find();
        }
    }
    /**
     * 写入日志
     */
    private function log($data = '')
    {
        \Think\Log::write($data, 'INFO');
    }
    public function postdata()
    {
        G('begin');
        $login_token = 'q459zejhzx';
        $params      = array(
            "auth_code" => "130238107780545319",
            "pos_id" => "1000195407",
            "userid" => 1,
            "custno" => 'wx00152244',
            "posseq" => '00001' . rand(100, 100000),
            "paytype" => 'yd',
            "yfje" => 1,
            "je" => 1,
            "reqtime" => time()
        );
        $url         = 'http://api.skyatlas.cn/index.php/Home/Index/pay';
        ksort($params);
        $params['sign'] = $this->_createSign($params, $login_token);
        G('end');
        echo G('begin','end').'s';
        $res = $this->http($url, json_encode($params));
        dump($res);
        dump(json_decode($res, true));
    }
    private function http($url, $params = array(), $method = 'POST')
    {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                $opts[CURLOPT_URL]        = $url;
                $opts[CURLOPT_POST]       = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data   = curl_exec($ch);
        $err    = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);
        if ($err > 0) {
            $this->error = $errmsg;
            return false;
        } else {
            return $data;
        }
    }

    public function test()
    {
       
        $login_token = 'q459zejhzx';
        $params      = array(
            "pos_id" => "1000195407",
            "userid" => 1,
            "posseq" => '00001' . rand(100, 100000),
            'cancel_posseq'=>'000011130',
            'cancel_type' =>'userpaying_cancel',
            'password' =>'00446530',
            "je" => 1,
            "reqtime" => time()
        );
        $url         = 'http://api.skyatlas.cn/index.php/Home/Index/wxOrderCancel';
        $params['sign'] = $this->_createSign($params, $login_token);
        $res = $this->http($url, json_encode($params));
        dump($res);
        dump(json_decode($res, true));
        
    }

    public function test1()
    {
        
        $login_token = 'q459zejhzx';
        $params      = array(
            "pos_id" => "1000195407",
            "userid" => 1,
            "posseq" => 'wx10001954070000165',
            "je" => 1,
            "reqtime" => time()
        );
        $url         = 'http://api.skyatlas.cn/index.php/Home/Index/login';
        $params['sign'] = $this->_createSign($params, $login_token);
        dump($params);
        $res = $this->http($url, json_encode($params));
        dump($res);
        dump(json_decode($res, true));
    }

    /**
    测试调用存储过程
    */
    public function testjf()
    {
         
        $para = array(
            'node_id' => '1',//租户号
            'card_number' => '100000004',//卡号
            'card_phone' => '',//手机号
            'openid' => '',//openid
            'trans_time' => '20161229120101',//发生日期
            'trans_type' => '消费',//变动类型
            'key' => 'bbbbb',//记录一些值，如门店或终端号
            'jf' => 99,//发生的积分
        );
        $this->_addJf($para);
       
    }
    /**
     *增加积分并发送模板消息,不要在这里写echo和dump
    **/
    private function _addJf($para = array()){

        $this->log("发送积分模板信息：".json_encode($para));
        if(!$para['node_id'])
            return;
        if(!$para['card_number'] && !$para['card_phone'] && !$para['openid'])
            return;

         $sql = " call sp_sell_calc_jf('{$para['node_id']}','{$para['card_number']}','{$para['card_phone']}','{$para['openid']}','{$para['trans_time']}','{$para['trans_type']}','{$para['key']}',{$para['jf']})";
        //echo $sql;
        $result = M()->query($sql);

        if($result && $result[0]){

                $url = 'http://test.ctachina.net/data/weixin.php/Sendmsg/points';
            if($result[0]["@ret"] == 1 && $result[0]["@v_ischange"] == 1){
            //发送模板消息,返回成功，并且积分有变化时才发
                $trans_date = substr($para["trans_time"],0,4).'年'.substr($para["trans_time"],4,2).'月'.substr($para["trans_time"],6,2).'日 '.substr($para["trans_time"],8,2).':'.substr($para["trans_time"],10,2);
                $params      = array(
                    "node_id"=>$para['node_id'],
                    "is_cust"=>"Y",
                    "token" => $result[0]["@v_token"],
                    "openid" => $result[0]["@v_openid"],    
                    "points_jfye" => $result[0]["@v_jfye"], 
                    "points_jf" => $result[0]["p_jf"], 
                    "points_type" => $para["trans_type"], 
                    "points_date" => $trans_date, 
                    "reqtime" => time()
                );

                $res = $this->http($url, json_encode($params));
                //dump($res);
            }elseif ($result[0]["@ret"] == 100) {//没有找到卡号
                $params      = array(
                    "is_cust"=>"N",
                    "token" => $para['token'],
                    "openid" => $para["openid"],
                    "reqtime" => time()
                );

                $res = $this->http($url, json_encode($params));
            }
        }
    }

    public static function returnmsg($result_code = '', $result_des = '', $result_data = array())
    {
        $data = array(
            'result_code' => $result_code,
            'result_des' => $result_des,
            'result_data' => $result_data
        );
        \Think\Log::write("返回给APP数据：".json_encode($data), 'INFO');
        return json_encode($data);
    }
    /**
     * 获取登录后的token
     */
    public static function getToken()
    {
        return self::getRandChar(10);
    }
    public static function getRandChar($length) //生成随机数
    {
        $str    = null;
        $strPol = "23456789abcdefghijkmnpqrstuvwxyz";
        $max    = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
            
        }
        return $str;
    }
    /**
    *有中文时的签名
    */
    public static function _createSignZH($params = array(), $token = '')
    {
        ksort($params);
        \Think\Log::write("Sign urlencode前的请求数据：" . http_build_query($params) . '&login_token=' . $token);
        $pa = $params;
        //有可能有中文，所以要urlencode

        foreach($pa as $key => $value){
            if(empty($pa[$key])){//为空的值不能与签名
                unset($pa[$key]);
            }else{
                $pa[$key] = urldecode($value);
            }
        }

        \Think\Log::write("Sign前的请求数据：" . http_build_query($pa) . '&login_token=' . $token); //json格式
        \Think\Log::write("后台Sign值：" . md5(http_build_query($pa) . '&login_token=' . $token)); //json格式
        //$params['login_token'] = $token;
        return strtoupper(md5(http_build_query($pa) . '&login_token=' . $token));
    }

    public static function _createSign($params = array(), $token = '')
    {
        ksort($params);
        //$params['login_token'] = $token;
        return strtoupper(md5(http_build_query($params) . '&login_token=' . $token));
    }
    public function arraySort($params = array(), $token = '')
    {
        ksort($params);
        return http_build_query($params) . '&login_token=' . $token;
    }

      /**
     * 支付宝数据插入到数据库
     */
    public function insertZfuData($data=array())
    {
        $data = file_get_contents('php://input', 'r');
        $this->log("接收到支付宝插入订单请求数据：" . $data); //json格式
        $data = json_decode($data, true);
        $this->log("支付宝请求数组：" .json_encode($data['data'])); //json格式
        $this->zfb_checkSign($data['data']);
        $res = $data['data'];
        $pos_info = M('pos_posinfo')->where(array('pos_id'=>$res['pos_id']))->find();   //找到终端信息
        $pos_seq = $pos_info['server_maxposseq'];
        $dataadd['node_id']  =    $pos_info['node_id'];   //集团id
        $dataadd['store_id'] =   $pos_info['store_id']; // 门店id
        $dataadd['pos_id']   =    $res['pos_id'];  //终端编号
        $dataadd['pos_seq']  =    $pos_seq;   //终端流水号
        $paysign = 1;
        $is_order = M('pos_sellpay_trace')->where(array('trade_no'=>$res['trade_no'],'trans_type'=>'T'))->find();   //是否已经支付过
        if(empty($is_order)){   //没有支付过

            $paysign = 1;
            $dataadd['trans_type'] = 'T';
            $dataadd['real_amt']    = $paysign * $res['receipt_amount_ex_fee'];      // 实收金额
            $dataadd['fee_amt']    = $paysign * $res['alipay_fee'];       //业务手续费（支付宝线下支付）
            $dataadd['is_canceled']    = 0;   //是否撤销
            $dataadd['org_posseq']    = '';     //源终端流水号
            $dataadd['exchange_amt']    = $res['receipt_amount'];   //交易金额

        }else{              //已经支付过的，都算作退款，部分退款等
            if($res['refund_amount'] == 0){
              exit(self::returnmsg(ErrorCode::$data_error, '插入支付失败数据，该笔交易退款金额为0，不做插入'));  
            }
            $dataadd['exchange_amt']    = $res['refund_amount'];   //交易金额
            $paysign = -1;
            $dataadd['trans_type'] = 'R';
            $dataadd['real_amt']    = $paysign * $res['receipt_amount_ex_fee'];      // 实收金额
            $dataadd['fee_amt']    = $paysign * $res['alipay_fee'];       //业务手续费（支付宝线下支付）
            $dataadd['is_canceled'] = 1;  //撤销
            $old_order = M('pos_sellpay_trace')->where(array('trade_no'=>$res['trade_no'],'trans_type'=>'T'))->find();   //找到原始订单号
            $dataadd['org_posseq'] = $old_order['pos_seq'];   //源终端流水号

        }

        // $pos_info = M('pos_posinfo')->where(array('pos_id'=>$res['pos_id']))->find();   //找到终端信息
        // $pos_seq = $pos_info['server_maxposseq'];
        // $dataadd['node_id']  =    $pos_info['node_id'];   //集团id
        // $dataadd['store_id'] =   $pos_info['store_id']; // 门店id
        // $dataadd['pos_id']   =    $res['pos_id'];  //终端编号
        // $dataadd['pos_seq']  =    $pos_seq;   //终端流水号
        // $paysign = 1;
        // if($res['trade_status'] == 'TRADE_SUCCESS'){
        //     $dataadd['trans_type'] = 'T';
        //     $dataadd['real_amt']    = $paysign * $res['receipt_amount_ex_fee'];      // 实收金额
        //     $dataadd['fee_amt']    = $paysign * $res['alipay_fee'];       //业务手续费（支付宝线下支付）
        //     $dataadd['is_canceled']    = 0;   //是否撤销
        //     $dataadd['org_posseq']    = '';     //源终端流水号
        // }elseif($res['trade_status'] == 'TRADE_REFUND'){
        //     $paysign = -1;
        //     $dataadd['trans_type'] = 'R';
        //     $dataadd['real_amt']    = $paysign * $res['receipt_amount_ex_fee'];      // 实收金额
        //     $dataadd['fee_amt']    = $paysign * $res['alipay_fee'];       //业务手续费（支付宝线下支付）
        //     $dataadd['is_canceled'] = 1;  //撤销
        //     $old_order = M('pos_sellpay_trace')->where(array('trade_no'=>$res['trade_no'],'trans_type'=>'T'))->find();   //找到原始订单号
        //     $dataadd['org_posseq'] = $old_order['pos_seq'];   //源终端流水号
        // }
        
        $dataadd['status']     = '0';
        $dataadd['cust_id']    = '';   //会员idrefund_amount
        $dataadd['cust_name']    = '';
        $dataadd['cust_tel']    = '';
        $dataadd['cust_jf']    = '';
       
        $dataadd['trans_time']    = date("YmdHis",time());    //交易时间
        
        $dataadd['ret_code']    = '';       //返回码
        $dataadd['ret_desc']    = '';       //返回描述
        $dataadd['user_id']    = '00000000';
        $dataadd['user_name']    = $res['buyer_id'];
        $dataadd['code_type']    = 100;    //支付类型
        $dataadd['syn_seq']    = '';       //同步流水号
        $dataadd['ori_syn_seq']    = '';   //源同步流水号
        $dataadd['frate']    = ''; //费率
        
        $dataadd['coupon_fee'] = $res['coupon_amount'];    //支付宝红包
        $dataadd['openid']    = $res['buyer_email'];       //买家用户号
        $dataadd['trade_no']    = $res['trade_no'];       //
        $dataadd['out_trade_no']    = $res['out_trace_no'];       //
        $dataadd['out_pos_seq']    = '';       //
        
        $dataadd['post_data']    = json_encode($res);       //
        $dataadd['add_time']    = date("Y-m-d H:i:s",time());       //
        $this->log("支付宝入数据：" . json_encode($dataadd)); //json格式
        $insert = M('pos_sellpay_trace')->add($dataadd);
        if($insert){
            
            if(!empty($is_order)){
                 $saveold['cancel_pos_seq'] = $pos_seq;
                 M('pos_sellpay_trace')->where(array('trade_no'=>$res['trade_no'],'trans_type'=>'T'))->save($saveold);
            }
            $new_seq = M('pos_posinfo')->where(array('pos_id'=>$res['pos_id']))->find();   //找到终端信息
            $max_seq['server_maxposseq'] = $new_seq['server_maxposseq']+1; 
            M('pos_posinfo')->where(array('pos_id'=>$res['pos_id']))->save($max_seq);
            $this->sendZfbPayMessage($res);
            exit(self::returnmsg(ErrorCode::$ok, '成功'));
        }else{
            exit(self::returnmsg(ErrorCode::$data_error, '插入支付失败数据'));
        }
        
    }


    /**
     * 支付宝消费赠送积分或退款扣减积分
     */
    public function sendZfbPayMessage($data = array())
    {
        $user_id = $data['buyer_id'];//支付宝用户id
        $userinfo = M('users')->where(array('alipay_user_id'=>$user_id))->find();
        if(empty($userinfo)){  //没有会员

            $result['user_id'] = $data['buyer_id'];
            $result['buyer_email'] = $data['buyer_email'];
            $result['receipt_amount'] =$data['receipt_amount'];
            $url = 'http://test.ctachina.net/data/weixin.php/Sendmsg/sendNews';
            $this->log("发送注册模板信息data:".json_encode($result).$url);
            $res = $this->http($url, json_encode($result));
            $this->log("发送模板信息返回值:".($res));
            return false;
        }
        if($data['refund_amount'] > 0){
            $amount  = $data['refund_amount'];   //用户退款金额，扣减退款部分金额的积分
            $data['paysign'] = -1;
            $pay_type = '退款';
            $first = '你有一笔积分扣减，请查看';
        }else{
            $amount  = $data['receipt_amount'];   //用户支付金额
            $data['paysign'] = 1;
            $pay_type = '消费';
            $first = '你有一笔积分增加，请查看';
        }
        
        $config = M('config')->where(array('name'=>'point_rate'))->find();
        if( empty($config['value']) || $config['value'] == ''){    //积分配置
            $config['value'] = 0;
        }
        $pos_info = M('pos_posinfo')->where(array('pos_id'=>$data['pos_id']))->find();
        $node = M('pos_node')->where(array('node_id'=>$pos_info['node_id']))->find();
        
        $para = array(
            'node_id' => $pos_info['node_id'],//租户号
            'token'=>$node['wx_id'],
            'wxtoken'=>$node['wx_id'],
            'number' => $userinfo['number'],//卡号
            'user_id' => $data['buyer_id'],
            'nickname' =>$userinfo['nickname'],
            'trans_time' => $data['gmt_create'],//发生日期
            'key' => $data['pos_id'].'|'.$data['trade_no'],//记录一些值，如门店或终端号
            'paysign' =>$data['paysign'],
            'pay_type'=>$pay_type,
            'first' =>$first,
            'jf' => $data['paysign'] * $amount * $config['value'],//发生的积分
            'old_jf' => $userinfo['pay_points']  //老的积分
        );

        $res = $this->payZfbPoints($para);
        if($res){

            $url = 'http://test.ctachina.net/data/weixin.php/Sendmsg/template';
            $this->log("发送模板信息data:".json_encode($para).$url);
            $res = $this->http($url, json_encode($para));
            $this->log("发送模板信息返回值:".($res));
        }
    }

    /**
     * 积分增加或减少
     */
    public function payZfbPoints($data = array())
    {   

        $up['pay_points'] = $data['old_jf'] + ($data['jf']);
        $update = M('users')->where(array('number'=>$data['number']))->save($up);
        $add_log['openid'] = $data['user_id'];
        $add_log['oauth'] = 'alipay';//支付宝
        $add_log['card_id'] = $data['number'];
        $add_log['jf'] = $data['jf'];
        $add_log['jfye'] = $up['pay_points'];
        $add_log['key'] = $data['key'];
        $add_log['trans_time'] = date("YmdHis");
        $add_log['trans_type'] = $data['paysign'];
        $add_log['uid'] = $data['buyer_id'];
        $add_log['token'] = $data['token'];
        $add = M('wp_card_points_log',null)->add($add_log);
        if($update !== false ){

            return true;
        }else{
            return false;
        }
    }

    /**
     * 支付宝签名检测
     */
    public function zfb_checkSign($data = array())
    {   
        $key = 'testkey';
        if (empty($data)) {
            exit(self::returnmsg(ErrorCode::$data_error, '数据不合法'));
        }
        $data_sign = $data['sign']; //原始sign
        unset($data['sign']); //sign不参与签名
        unset($data['coupon']); //优惠券券不参与签名
        //dump($data);
        $createSign = $this->zfb_createSign($data,$key); //生成签名
        $this->log('生成的签名:'.$createSign);
        if ($createSign !== $data_sign) { //对比签名
            //exit(self::returnmsg(ErrorCode::$sign_error, '签名错误'));
        }
    }
    
    /**
     * 支付宝签名
     */
    public function zfb_createSign($data, $key = '')
    {   
       
        ksort($data);
        \Think\Log::write("Sign urlencode前的请求数据：" . http_build_query($data) . '&key=zfdjkauiuwei876');
        $pa = $data;
        //有可能有中文，所以要urlencode

        foreach($pa as $key => $value){
            
            $pa[$key] = urldecode($value);
        }

        \Think\Log::write("Sign前的请求数据：" . http_build_query($pa) . '&key=zfdjkauiuwei876'); //json格式
        \Think\Log::write("后台Sign值：" . md5(http_build_query($pa) . '&key=zfdjkauiuwei876')); //json格式
        //$params['login_token'] = $token;
        return strtoupper(md5(http_build_query($pa) . '&key=zfdjkauiuwei876'));
    }
}
/**
 * error code
 * 仅用作类内部使用，不用于官方API接口的errCode码
 */
class ErrorCode
{
    public static $ok = 1;
    public static $login_pos_null = 10001;
    public static $login_user_error = 10002;
    public static $login_error = 10003;
    public static $wxpay_error = 20001;
    public static $jdpay_error = 30001;
    public static $sign_error = 40001;
    public static $token_error = 40002;
    public static $paytype_error = 40003;
    public static $posseq_error = 40004;
    public static $pay_timeout = 50001;
    public static $data_error = 60001;
    public static $system_error = 90001;
    public static $default_error = 90002;
    public static $illegal_request = 90003;
    public static $request_timeout = 90004;
    public static $other = 90005;
    public static $errCode = array('1' => '返回成功', '10001' => 'pos机不存在', '10002' => '账号或密码错误', '10003' => '登录数据不合法', '20001' => '微信刷卡支付失败', '30001' => '京东付款码付款失败', '40001' => '签名效验失败', '40002' => 'token效验失败', '40003' => '不存在的支付方式，请检查','40004' => '终端序列号异常，请重试',  '50001' => '支付超时', '60001' => '数据不合法', '90001' => '系统错误，请联系管理员', '90002' => '其他错误', '90003' => '非法请求', '90004' => '请求超时','90005'=>'其他');
    public static function getErrText($err)
    {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        } else {
            return false;
        }
        ;
    }
}

