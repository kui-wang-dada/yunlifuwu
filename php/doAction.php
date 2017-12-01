<?php
header('content-type:text/html;charset=utf8');
require_once 'functions/mysql.func.php';
require_once 'functions/common.func.php';
require_once 'config/config.php';
require_once 'swiftmailer-master/lib/swift_required.php';

$act=$_REQUEST['act'];
$username=$_REQUEST['username'];
$password=md5($_POST['password']);
$link=connect2($config);
$table='student3';
switch($act){
    case 'reg':
        mysqli_autocommit($link,false);
        $email=$_POST['email'];
        $regTime=time();
        $token=md5($username.$password.$regTime);
        $token_exptime=$regTime+24*3600;
        $data=compact('username','password','email','regTime','token','token_exptime');
        $res=insert($link,$data,$table);

        $transport=Swift_SmtpTransport::newInstance('smtp.qq.com',587);

        $transport->setUsername('872505550@qq.com');
        $transport->setPassword('1991125012**');
        $mailer=Swift_Mailer::newInstance($transport);
        $message=Swift_Message::newInstance();
        $message->setFrom(array('872505550@qq.com'));
        $message->setTo($email);
        $message->setSubject('注册账号激活邮件');

        $activeStr="?act=active&username={$username}&token={$token}";
        $url="http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].$activeStr;

        $urlEncode=urlencode($url);

        $emailBody=<<<EOF
        欢迎{$username}使用账号激活功能
	    请点击链接激活账号：
	    <a href='{$url}' target='_blank'>{$urlEncode}</a> <br />
	    (该链接在24小时内有效)
	    如果上面不是链接形式，请将地址复制到您的浏览器(例如IE)的地址栏再访问。
EOF;
        $message->setBody($emailBody,"text/html",'utf-8');
        try{
            $res1=$mailer->send($message);
            var_dump($res);
            if($res && $res1){
                mysqli_commit($link);
                mysqli_autocommit($link,TRUE);
                alertMes("注册成功，立即激活使用",'index.php');
            }else{
                mysqli_rollback($link);
                alertMes("注册失败，重新注册",'index.php');
            }
        }catch(Swift_ConnectionException $e){
            echo '123';
            die('邮件服务器错误：').$e->getMessage();
        }
        break;

    case 'active':
        $token=$_GET['token'];
        $username=mysqli_real_escape_string($link,$username);
        $query="SELECT id,token_exptime FROM {$table} WHERE username='{$username}'";
        $user=fetchOne($link,$query);
        if($user){
            $now=time();
            $token_exptime=$user['token_exptime'];
            if($now>$token_exptime){
                delete($link,$table,"username='{$username}'");
                alertMes("激活码过期，请重新注册",'index.php');
            }else{
                $data=array('status'=>1);
                $res=update($link,$data,$table,"username='{$username}'");
                if($res){
                    alertMes('激活成功，立即登录','index.php');
                }else{
                    alertMes('激活失败，重新激活','index.php');
                }
            }
        }else{
            alertMes('激活失败，没有找到要激活的用户！！！','index.php');
        }
        break;

    case 'checkUser':
        $username=mysqli_real_escape_string($link, $username);
        $query="SELECT id FROM {$table} WHERE username='{$username}'";
        $row=fetchOne($link, $query);
        if($row){
            echo 1;
        }else{
            echo 0;
        }
        break;

    case 'login':
        $username=addslashes($username);
        $query="SELECT id,status FROM {$table} WHERE username='{$username}' AND password='{$password}'";
        $row=fetchOne($link,$query);
        alertMes("登陆成功，跳转到首页",'student/layout-index.php');
//        if($row){
//            if($row['status']==0){
//                alertMes("请先到邮箱激活再来登录",'index.php');
//            }else{
//                alertMes("登陆成功，跳转到首页",'student/layout-index.php');
//            }
//        }else{
//            alertMes("用户名或密码错误，重新登录",'index.php');
//        }
        break;
    default:
        die('非法操作');
        break;
}

