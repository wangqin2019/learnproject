<?php

namespace app\admin\validate;

use think\Validate;

class GoodsValidate extends Validate
{
    protected $rule = [
       'name|产品名称'  => 'require',
       'orderby|排序'  => 'require',

    ];

}