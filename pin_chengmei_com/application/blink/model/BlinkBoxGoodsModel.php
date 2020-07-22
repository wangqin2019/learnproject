<?php

namespace app\blink\model;
use think\Model;
//用户盲盒中商品表

class BlinkBoxGoodsModel extends Model
{
    protected $name = 'blink_box_goods';

    /**
     * Commit: 根据条件获取盲盒中的所有商品数据
     * Function: getBoxInAllGoods
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:39:52
     * @Return array
     */
    public static function getBoxInAllGoods($map){
        return self::alias('bg')
            ->join(['pt_goods'=>'g'],'bg.goods_id=g.id','left')
            ->field('bg.goods_id,g.id,bg.type,g.name,g.stock,g.image,g.xc_images,g.images,g.activity_price,g.price')
            ->where($map)
            ->select();
    }
    public static function getBoxInGoodsInfo($map){
        return self::alias('bg')
            ->join(['pt_goods'=>'g'],'bg.goods_id=g.id','left')
            ->field('bg.goods_id,g.id,bg.type,g.name,g.stock,g.image,g.xc_images,g.images,g.activity_price,g.price')
            ->where($map)
            ->find();
    }
















}