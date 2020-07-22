<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:37
 */
namespace app\api\validate;

class OtoEducation
{
    // 定义各个方法的验证规则
    public static $func = [
        'sendotocard'   =>  [
            'mobile' => 'require',
            'store_id' => 'require',
        ],
        'otoaccount'   =>  [
            'user_id' => 'require',
        ],
        'getotorecord'   =>  [
            'oto_user' => 'require',
        ],
        'getclassify'   =>  [

        ],
        'getsilkbag'   =>  [
            'cla_id' => 'require',
            'type_id' => 'require',
            'oto_user' => 'require',
            'begin_time' => 'require',
            'end_time' => 'require'
        ],
        'getqalist'   =>  [

        ],
        'payscancm' => [
            'user_id' => 'require',
            'store_id' => 'require',
        ],
    ];
}