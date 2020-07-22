<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:37
 */
namespace app\api\validate;

class Archives
{
    // 定义各个方法的验证规则
    public static $func = [
        'archiveslist'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'archivesunderwear'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'archivesunderwearupd'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'archivesunderwearsel'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'measure'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'measureadd'   =>  [
            'user_id' => 'require',
            'store_id' => 'require',
            'figure_id' => 'require',
            'form_state_id' => 'require',
            'bb' => 'require',
            'right_bb' => 'require',
            'left_bb' => 'require'
        ],
        'occuplist'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'cardlist'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'incomelist'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'propertylist'   =>  [
            'proid'=>'require'
        ],
        'shapescore' => [
            'user_id' => 'require',
            'store_id' => 'require',
            'record_id' => 'require'
        ],
        'contrastdata' => [
            'record_id1' => 'require',
            'record_id2' => 'require'
        ],
        'measurelist' => [
            'user_id' => 'require',
        ],
        'customerunderfilesdel' => [
            'record_id' => 'require',
            'user_id' => 'require',
        ],
        'customercode' => [
            'user_id' => 'require',
        ],
        'recommendsize' => [
            'record_id' => 'require',
        ],
    ];
}