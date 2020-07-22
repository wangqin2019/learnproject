<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:37
 */
namespace app\api\validate;

class Submeal
{
    // 定义代餐档案各个方法的验证规则
    public static $func = [
        'dinnerfiles'   =>  [
            'user_id' => 'require',
            'store_id' => 'require',
            'dt' => 'require'
        ],
        'mealdetails'   =>  [
            'user_id' => 'require',
            'store_id' => 'require',
            'record_id' => 'require'
        ],
        'mealcompare'   =>  [
            'dt1' => 'require',
            'dt2' => 'require',
            'user_id' => 'require'
        ],
        'mealuserinfo'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'mealuserinfoupd'   =>  [
            'user_id' => 'require',
            'weight' => 'number',
            'height' => 'number'
        ],
        'usermealsel'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'usermealupd'   =>  [
            'user_id' => 'require',
            'store_id' => 'require',
            'weight' => 'require|number',
            'waist' => 'require|number',
            'hipline' => 'require|number'
        ],
        'beauticianmealsel'  =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'beauticianmealupd'  =>  [
            'user_id' => 'require',
            'store_id' => 'require',
            'waist' => 'require|number',
            'hipline' => 'require|number'
        ],
    ];

}