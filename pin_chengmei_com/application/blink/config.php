<?php

return [
    'default_return_type'	=> 'json',
    // 默认时区
    'default_timezone'       => 'PRC',
    // +----------------------------------------------------------------------
    // | redis设置
    // +----------------------------------------------------------------------
    'redis'       => [
        //'host'    => 'r-uf653c8dfadcb3c4.redis.rds.aliyuncs.com',// redis 主机地址
        'host'    => '127.0.0.1',// redis 主机地址
        'port'    => 6379,// redis 端口
        'password'    => 'Canmay2015',// redis 密码
        'prefix'    => '',// redis 名称前缀
        'timeout'    => 0,// 超时时间
        'select'    => 20,// 库
        'expire'    => 0,// redis 保存时间
        'persistent'    => false,
    ],
    'wx_blink_pay' => [
        'appid' => 'wx52ffe914bb40b1fd',    /*微信开放平台上的应用id*/
        'mch_id' => "1248782701",   /*微信申请成功之后邮件中的商户id*/
        'api_key' => "GifdUuhcf2mvuccHQbdvSK8b6ILbMTDQ",    /*在微信商户平台上自己设定的api密钥 32位*/
        'appsecret' => "909f8bd653b38c840283bdc35a6d70f4",    /*在微信商户平台上自己设定的api密钥 32位*/
    ],
    'blink_sms_id' => 114,

];