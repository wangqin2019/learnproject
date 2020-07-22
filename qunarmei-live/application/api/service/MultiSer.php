<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/5/14
 * Time: 10:03
 */

namespace app\api\service;

use app\api\model\Branch;
use app\api\model\TxAppoint;

/**
 * 多表数据联合查询服务
 * Class SingleSer
 * @package app\api\service
 */
class MultiSer
{
    /**
     * 预约项目订单关联门店项目
     * @param $map [查询条件]
     * @return
     */
    public function TxAppointBwkItem($map)
    {
        $res = TxAppoint::alias('a')
            ->join(['store_bwk_item'=>'b'],['a.service_id=b.id'],'LEFT')
            ->where($map)
            ->field('a.id,b.id item_id,b.item_name,b.item_price,a.appoint_num,a.id_interestrate,a.appoint_sn,a.user_id,a.mobile')
            ->select()
        ;
        return $res;
    }
}