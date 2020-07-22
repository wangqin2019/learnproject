<?php
/*
 *Author: stars
 *CreateTime: 2020/2/24 11:06
 *Description: 其他配置信息
*/

return [
    //scrm后台审核人分组配置
    'scrm_admin'    => [
        'check_group'           => 6, //审核权限组id
        'check_level'           => 2, //审核级别 共几级审核人 默认两级 最后一级为法务审核
        'check_number'          => 5, //每级最多人数
        'legal_group'           => 7, //法务权限组

        'creator_number'        => 5, //创作审核人最多几人

        'start'                 => '8:00:00', //审核提醒开始时间
        'end'                   => '20:00:00', //审核提醒结束时间
    ],
    'scrm_message' => [
        'msg_check'             => "您有新的“原创内容”文章需要审核，请尽快进行相关操作！",//审核提醒信息
        'msg_sms_id'            => 109,//短信接口id
    ],
    'dtalk'        => [
        'dtalk_log'             => 'dtalk.log',//日志
        'ding_send_message_url' => 'http://dingding.chengmei.com/dingding/message.shtml',//钉钉接口url
        'sms_url'               => 'http://sms.qunarmei.com/sms.php',//短信接口地址 , 服务器地址=>加8080端口
        'dtalk_staff_url_test'  => 'http://wshpc.chengmei.com:8888/scrm/user.shtml',//办事处及员工
        'dtalk_staff_url'       => 'http://dingding.chengmei.com/scrm/user.shtml',//办事处及员工
    ],
    'sms'          => [
        'cl_sms_user'           => 'huangwei',
        'cl_sms_pwd'            => 'admin',
        'sms_log'               => "sms.log",
    ],
];