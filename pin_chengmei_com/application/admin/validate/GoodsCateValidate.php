<?php

namespace app\admin\validate;

use think\Validate;

class GoodsCateValidate extends Validate
{
    protected $rule = [
       'name|产品分类名称'  => 'require',
       'orderby|排序'  	  => 'require',
    ];

}