<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/28
 * Time: 13:55
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\QnmPayService;

/**
 * 支付相关处理类
 * Class QnmPay
 * @package app\api\controller\v4
 */
class QnmPay extends Common
{
    /**
     * 是否能购买
     * @param  int $goods_id 商品id
     * @return array
     */
    public function is_pay()
    {
        $arr['goods_id'] = input('goods_id',0);
        $res = [];
        $scoreser = new QnmPayService();
        $res = $scoreser->isPay($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}