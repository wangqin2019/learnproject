<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/23
 * Time: 17:28
 */

namespace app\api\service;

use think\Db;

class GoodsSer
{
    /**
     * 查询商品信息
     * @param array $map 查询条件
     * @return array
     */
    public function getGoodss($map)
    {
        $res = Db::table('ims_bj_shopn_goods')->where($map)->select();
        return $res;
    }
    /**
     * 查询购物车商品信息
     * @param array $map 查询条件
     * @return array
     */
    public function getCarGoods($map)
    {
        $res = Db::table('ims_bj_shopn_car c')
            ->join(['ims_bj_shopn_goods'=>'g'],['c.id_goods=g.id'],'LEFT')
            ->field('g.id,g.pcate')
            ->where($map)
            ->select();
        return $res;
    }
    /**
     * 查询商品信息
     * @param array $map 查询条件
     * @return array
     */
    public function getGoods($map)
    {
        $res = Db::table('ims_bj_shopn_goods')->where($map)->limit(1)->find();
        return $res;
    }
}