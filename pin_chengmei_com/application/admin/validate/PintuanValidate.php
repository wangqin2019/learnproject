<?php

namespace app\admin\validate;

use think\Validate;

class PintuanValidate extends Validate
{
    protected $rule = [
        ['pt_name', 'require', '拼团名称不允许为空'],
        ['storeid', 'require', '拼团活动门店不允许为空'],
        ['pid', 'require', '拼团产品名称不允许为空'],
        ['p_name', 'require', '拼团产品名称不允许为空'],
        ['p_price', 'require', '拼团产品价格不允许为空'],
        ['pt_num_max', 'require', '拼团最大数量不允许为空'],
        ['pt_buyer_max', 'require', '拼团最多参与人不允许为空'],
        ['buyer_price', 'require', '参与拼团人支付金额不允许为空'],
        ['pt_time', 'require', '拼团效期不允许为空'],
    ];
}