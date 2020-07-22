<?php

namespace app\admin\validate;

use think\Validate;

class BargainValidate extends Validate
{
    protected $rule = [
        ['bargain_name', 'require', '拼人品名称不允许为空'],
        ['storeid', 'require', '拼人品活动门店不允许为空'],
        ['pid', 'require', '拼人品产品名称不允许为空'],
        ['name', 'require', '拼人品产品名称不允许为空'],
        ['market_price', 'require', '拼人品产品价格不允许为空'],
        ['num_max', 'require', '拼人品最大数量不允许为空'],
        ['buyer_max', 'require', '拼人品最多参与人不允许为空'],
        ['price', 'require', '参与拼人品人支付金额不允许为空'],
        ['pt_time', 'require', '拼人品效期不允许为空'],
    ];
}