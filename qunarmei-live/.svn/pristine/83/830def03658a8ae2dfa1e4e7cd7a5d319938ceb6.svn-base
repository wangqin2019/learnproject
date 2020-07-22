<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/10
 * Time: 10:07
 */

namespace app\api\service;

use think\Db;
/**
 * 订单服务
 */
class OrderSer
{
    
	/**
     * 获取订单信息
     * @param array $map 查询条件
     * @return array
     */
    public function getOrd($map)
    {
        $res = Db::table('ims_bj_shopn_order o')->where($map)->limit(1)->find();
        return $res;
    }
	/**
     * 修改订单信息
     * @param array $data 订单数据
     * @param array $map 查询条件
     * @return int|string
     */
    public function editOrd($data,$map)
    {
        $res = Db::table('ims_bj_shopn_order o')->where($map)->update($data);
        return $res;
    }
	/**
     * 获取单个订单详情
     * @param array $map 查询条件
     */
    public function getOrderDetail($map)
    {
        $map1 = [];
        $scoreSer = new ScoreSer();
        $goods_ids = array_merge($scoreSer->missshop_id,$scoreSer->missshop_double_id);
        if($goods_ids){
            $map1['g.id'] = ['in',$goods_ids];
        }
        $res = Db::table('ims_bj_shopn_order o')
            ->join(['ims_bj_shopn_order_goods' => 'og'],['og.orderid = o.id '],'LEFT')
            ->join(['ims_bj_shopn_goods' => 'g'],['og.goodsid = g.id '],'LEFT')
            ->field('o.uid user_id,o.id,o.ordersn,o.price sum_price,og.price,og.total,g.id goods_id,g.title,g.marketprice')
            ->where('o.payTime','>',0)
            ->where($map)
            ->where($map1)
            ->select()
        ;
        return $res;
    }
}