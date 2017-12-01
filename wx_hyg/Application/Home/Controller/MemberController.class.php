<?php
/**
 * Created by nuchect
 * E-mail: nuchect@qq.com
 * Date: 2017/11/14
 * Time: 17:43
 */

namespace Home\Controller;

class MemberController extends  MobileController {
    public $selectUser = 'http://218.87.88.2:8083/jjlsws/DepositCustomWeiChatSrv';
    public $editIdcard = 'http://218.87.88.2:8086/WeiChat/CustomModify';
    public $editLevel = 'http://218.87.88.2:8083/jjlsws/CooperationSrv';
    public $key = 'test';
    public $openid = '';
    
    protected $userinfo = null;
    
    // 会员等级
    public $level = [
        // 保证金deposit 返利券vouchers 消费金额amount 宣传publicity
        11 => ['id' => 11, 'class' => 'bg1', 'name' => '普通会员', 'expire' => 1, 'deposit' => 5000, 'vouchers' => 1000, 'amount' => 2000, 'publicity' => 2],
        12 => ['id' => 12, 'class' => 'bg2', 'name' => '贵宾会员', 'expire' => 1, 'deposit' => 10000, 'vouchers' => 2000, 'amount' => 4000, 'publicity' => 2],
        13 => ['id' => 12, 'class' => 'bg2', 'name' => '贵宾会员', 'expire' => 1, 'deposit' => 20000, 'vouchers' => 4000, 'amount' => 8000, 'publicity' => 2],
        14 => ['id' => 14, 'class' => 'bg3', 'name' => '金卡会员', 'expire' => 1, 'deposit' => 50000, 'vouchers' => 10000, 'amount' => 50000, 'publicity' => 2],
        15 => ['id' => 15, 'class' => 'bg4', 'name' => '铂金会员', 'expire' => 1, 'deposit' => 50000, 'vouchers' => 10000, 'amount' => 100000, 'publicity' => 2],
        16 => ['id' => 16, 'class' => 'bg5', 'name' => '钻石会员', 'expire' => 1, 'deposit' => 50000, 'vouchers' => 10000, 'amount' => 150000, 'publicity' => 2]
    ];
    public $now_level = [
        11 => 0,
        12 => 1,
        13 => 2,
        14 => 3,
        15 => 4,
        16 => 5,
    ];
    
    // TODO
    public function _initialize(){
        $member_config = C('MEMBER_CONFIG');
        $this->selectUser = $member_config['selectUser'];
        $this->editIdcard = $member_config['editIdcard'];
        $this->editLevel = $member_config['editLevel'];
        $this->key = $member_config['key'];
        
//        $this->openid = 'oUBPqjhOAJqyCnvf5n4WOL4JfTyw';
//        $_SESSION['openid'] = $this->openid;
        
        parent::_initialize();
        $this->openid = $_SESSION['openid'];
        $this->updateUser();
    }
    
    public function index(){
        if(IS_POST){
            $level = I('post.level');
            $agree = I('post.agree');
            if(!$level){
                $this->error('请选择会员等级');
            }
            if(!$agree){
                $this->error('请阅读并确认页面下方的条款');
            }
            if($this->userinfo['p_ref_cardtype'] > $level){
                $this->error('暂不支持降级办理');
            }
            session('member_level', $this->level[$level]);
            $this->success('签约成功', U('Member/idcard'));
        }else{
            $this->assign('level', $this->level);
            $this->assign('now_level', 0);
            $this->assign('step', 1);
            $this->display();
        }
    }
    
    public function idcard(){
        if(IS_POST){
            $username = I('post.username');
            $idcard = I('post.idcard');
            if(!$username || !$idcard){
                $this->error('请填写真实信息');
            }
            if(!preg_match('/^\d{17}[0-9Xx]$/', $idcard)){
                $this->error('身份证格式错误');
            }
            // 接口修改
            $res = $this->editUser($idcard, $username);
            if(isset($res) && $res['refcode'] == 0){
                $this->success('修改成功', U('Member/pay'));
            }else{
                $this->success('身份信息修改失败');
            }
        }else{
            if(!session('member_level')){
                redirect(U('Member/index'));
            }
            $this->assign('username', $this->userinfo['p_ref_name']);
            $this->assign('idcard', $this->userinfo['p_ref_pushcode']);
            $this->assign('step', 2);
            $this->display();
        }
    }
    
    /**
     * 添加协议
     * @return int|mixed
     * E-mail: nuchect@qq.com
     */
    public function addAgreement(){
        $agreement_data = session('member_level');
        $agreement_data['level'] = $agreement_data['id'];
        unset($agreement_data['id']);
        $agreement_data['wid'] = $this->userinfo['p_ref_wid'];
        $agreement_data['openid'] = $this->userinfo['p_ref_weixinid'];
        $agreement_data['create_time'] = time();
        $agreement_data['update_time'] = time();
        $agreement_data['start_time'] = time();
        $agreement_data['end_time'] = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y') + 1);
        $id = M('MemberAgreement')->add($agreement_data);
        if($id){
            $number = date('ymd') . sprintf('%06s', $id);
            M('MemberAgreement')->save(['id' => $id, 'number' => $number]);
            return $id;
        }else{
            return 0;
        }
    }
    
    public function pay(){
        if(IS_AJAX){
            $obj = M('MemberAgreement');
            $info = $obj->where([
                'wid' => $this->userinfo['p_ref_wid'],
                'status' => 0
            ])->save(['status' => -1]);
 
            $id = $this->addAgreement();
            if($id){
//                $this->success('生成协议成功', U('Pay/index', ['orderid' => 'member' . $obj->getFieldById($id, 'number')]));
                $this->success('生成协议成功', U('Member/lists', ['orderid' => 'member' . $obj->getFieldById($id, 'number')]));
            }else{
                $this->error('网络错误');
            }
        }else{
            if(!$member_level = session('member_level')){
                redirect(U('Member/index'));
            }
            session('pay_type', 'member');
            $this->assign('deposit', $member_level['deposit']);
            $this->assign('step', 3);
            $this->display();
        }
    }
    
    public function lists(){
        $lists = M('MemberAgreement')->where([
            'status' => ['egt', 0],
            'wid' => $this->userinfo['p_ref_wid']
        ])->select();
        foreach($lists as $k => $v){
            switch ($v['status']){
                case 0:
                    $str = '待支付';break;
                case 1:
                    $str = '协议中';break;
                case 2:
                    $str = '已退款';break;
                default:
                    $str = '';
            }
            if($v['status'] == 1 && time() > $v['end_time']){
                $str = '已到期';
            }
            $lists[$k]['status_str'] = $str;
            $lists[$k]['end_time'] = $v['end_time'] - 24*3600;
        }
        $this->assign('lists', $lists);
        $this->display();
    }
    
    public function result(){
        $id = I('id');
        $obj = M('MemberAgreement');
        $this->info = $obj->alias('ma')
            ->field('ma.*,mp.id as pay_id')
            ->join('left join __MEMBER_PAYMENT__ as mp on ma.number = mp.out_trade_no')
            ->where(['ma.id' => $id])
            ->find();
        $this->display();
    }
    
    public function agreement(){
        $id = I('id');
        $obj = M('MemberAgreement');
        $info = $obj->alias('ma')
            ->field('ma.*,mp.id as pay_id')
            ->join('left join __MEMBER_PAYMENT__ as mp on ma.number = mp.out_trade_no')
            ->where(['ma.id' => $id])
            ->find();
        if($info){
            $info['start_date'] = date('Y年m月d日', $info['start_time']);
            $info['end_date'] = date('Y年m月d日', $info['end_time'] - 24*3600);
            $this->assign('info', $info);
            $this->assign('user', $this->userinfo);
        }
        $this->display();
    }
    
    /**
     * 更新用户信息
     * E-mail: nuchect@qq.com
     */
    public function updateUser(){
        // 获取当前用户
        $userinfo = $this->getUser();
        if($userinfo && isset($userinfo['refcode']) && $userinfo['refcode'] == 0){
            session('member', $userinfo);
            $this->userinfo = $userinfo;
        }else{
//            $this->error('用户不存在');
            header('location:' . C('TO_REGIST'));
        }
    }
    
    /**
     * 获取用户信息
     * @return mixed
     * E-mail: nuchect@qq.com
     */
    public function getUser(){
        $params = array(
            "id" => $this->openid,
            "timestamp" => date("Y-m-d H:i:s"),
            "type" => 3,
        );
        $params['sign'] = $this->_createSign($params, $this->key);
        $res = $this->httpRequest($this->selectUser,$params);
//        echo $res;die;
        return json_decode($res, true);
    }
    
    /**
     * 修改会员身份信息
     * @param $idcard
     * @param $username
     * @return mixed
     * E-mail: nuchect@qq.com
     */
    public function editUser($idcard, $username){
        $params = array(
            "wid" => $this->openid,
            "timestamp" => date("Y-m-d H:i:s"),
            "cardno" => $username,
            "mobile" => $this->userinfo['p_ref_mobile'],
            "id" => $idcard
        );
        $params['sign'] = $this->_createSign($params, $this->key);
        $res = $this->httpRequest($this->editIdcard,$params);
//        print_r($params);var_dump($res);die;
        return json_decode($res, true);
    }
    
    public function httpRequest($url, $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            $data = is_array($data) ? urldecode(http_build_query($data)) : urldecode($data);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
    
    
    /**
     * 修改等级
     * @param $level
     * @return mixed
     * E-mail: nuchect@qq.com
     */
    public function editLevel($level){
        $params = array(
            "id" => $this->openid,
            "timestamp" => date("Y-m-d H:i:s"),
            "type" => 3,
            'plan' => $level,
            "seqno" => '20171122143251', // 支付订单号
            'flq' => $this->level[$level]['vouchers'],
            'je' => $this->level[$level]['deposit'],
        );
        $params['sign'] = $this->_createSign($params, $this->key);
        $res = $this->httpRequest($this->editLevel,$params);
//        echo $res;die;
        return json_decode($res, true);
    }
    
    public static function _createSign($params = array(), $key = ''){
        $str = '';
        ksort($params);
        foreach ($params as $k => $v) {
            $str.= $k.$v;
        }
//        echo $str . PHP_EOL . PHP_EOL;
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
        \Think\Log::write(print_r($opts, true), '接口请求');
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
    private static function _getRandomStr($lenght = ''){
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
        return substr(str_shuffle($str_pol), 0, $lenght);
    }
}