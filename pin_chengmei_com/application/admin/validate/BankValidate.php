<?php

namespace app\admin\validate;
use think\Validate;

class BankValidate extends Validate
{
    protected $rule = [
        ['st_abbre_bankname', 'unique:bank', '银行名称已经存在'],
        ['st_full_bankname', 'unique:bank', '银行名称已经存在']
    ];

}