<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:37
 */
namespace app\api\validate;

class AppVer
{
    // 定义代餐档案各个方法的验证规则
    public static $func = [
        'aboutqunarmei'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'mycardlist'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
    ];
}