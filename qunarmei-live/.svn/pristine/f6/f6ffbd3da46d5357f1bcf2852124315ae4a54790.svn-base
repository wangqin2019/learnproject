<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:37
 */
namespace app\api\validate;

class Health
{
    // 定义代餐档案各个方法的验证规则
    public static $func = [
        'healthcheck'   =>  [
            'user_id' => 'require',
            'store_id' => 'require',
            'sex' => 'require|number',
            'birthday' => 'require',
            'weight' => 'require|number|gt:0',
            'height' => 'require|number|gt:0',
            'waist' => 'require|number|gt:0',
            'hipline' => 'require|number|gt:0'
        ],

    ];
}