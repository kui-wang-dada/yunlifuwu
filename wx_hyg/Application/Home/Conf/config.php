<?php
return array(
	'TMPL_L_DELIM'=>'<{',//左定界符
	'TMPL_R_DELIM'=>'}>',//右定界符
	'SHOW_PAGE_TRACE'=>false,
	'DB_TYPE' => 'mysql', //数据库类型
    'DB_HOST' => 'rm-bp1phqh632307bnszo.mysql.rds.aliyuncs.com', //数据库主机
    'DB_NAME' => 'lqb', //数据库名称
    'DB_USER' => 'wx01', //数据库用户名
    'DB_PWD' => 'wx01@YunLi', //数据库密码
    'DB_PORT' => '3306', //数据库端口
    'DB_PREFIX' => 'tp_', //数据库前缀
    'DB_CHARSET'=> 'utf8', // 字符集
    'DB_DEBUG'  => 'false', // 数据库调试模式 开启后可以记录SQL日志

    // 支付配置
    'PAY_CONFIG' => array(
        'appid'     =>'wx7e4a1d43eae038d0',
        'sub_appid' =>'wx6a5ca61373bac69a',
        'appsecret' =>'473585551f8fdd6df915ace4b5cdccc7',
        'mch_id'    =>'1270723401',
        'sub_mch_id'=>'1273625701',
        'payKey'    =>'789435BD82cce97cf9ae0b83BAD76C4b',
    ),
    // 会员购接口
    'MEMBER_CONFIG' => [
        'key' => 'test', // 加密key
        'selectUser' => 'http://218.87.88.2:8083/jjlsws/DepositCustomWeiChatSrv', // 会员查询
        'editIdcard' => 'http://218.87.88.2:8086/WeiChat/CustomModify', // 身份信息编辑
        'editLevel' => 'http://218.87.88.2:8083/jjlsws/CooperationSrv', // 会员等级修改
    ],
    // 注册地址
    'TO_REGIST' => 'http://www.lianshengit.com/Wei/index.php?s=/addon/Card/Wap/index/token/gh_05cd3368541d.html',
    //零钱包title数组及底部菜单
    'MY_MENU' => array(
        'index'     => '我的电子购物卡',
        'payDetail' => '资金明细',
        'qrcode'    => '我的二维码',
    ),
    //支付方式
    'PAY_TYPE' => array(
        'WX' => ['title'=>'微信支付', 'pic'=>'icon_weixin.png'],
    ),
);
