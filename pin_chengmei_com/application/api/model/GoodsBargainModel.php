<?php

namespace app\api\model;
use think\Model;
use think\Db;

class GoodsBargainModel extends Model
{

    protected  $goods="goods_bargain";

    /**
     * Commit: 获取当前活动产品关联的奖励产品
     * Function: getRewardGoodsList
     * @param $map goods_cate=8
     *
     * $map['gb.pid'] = $goods_id;
     * $map['gb.storeid'] = $storeid;
     * $map['gb.storeid'] = $storeid;
     * $map['g.is_bargain'] = 1;
     * $map['g.status'] = 1;
     *
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 16:05:07
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRewardGoodsList($map)
    {
        return Db::name('goods_bargain')
            ->alias('gb')
            ->field('g.id,g.name,g.price,g.unit,g.intro,g.image,g.is_bargain,g.activity_price,g.stock,gb.goods_id,g.buy_type,g.image,g.images,g.xc_images,g.goods_sub,g.bargain_number')
            ->join(['pt_goods' => 'g'],'gb.goods_id = g.id','left')
            ->where($map)
            ->select();
    }

    /**
     * Commit: 获取当前用户点击的当前门店下的活动产品信息
     * Function: getActivityGoodsInfo
     * @param $map
     *
     * $map['gb.goods_id'] = $goods_id;
     * $map['gb.storeid'] = $storeid;
     *
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 16:59:54
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getActivityGoodsInfo($map)
    {
        $field = "gb.goods_id,gb.pid,g.bargain_number";
        $field .= ",g.id,g.name,g.price,g.activity_price,g.unit,g.intro,g.goods_cate,g.image,g.images,g.allow_buy_num1,g.xc_images";
        $field .= ",g.images,g.score,g.buy_type,g.allow_buy_num,g.stock,g.stock_limit,g.video,g.storeid,g.type,g.pid,g.content";

        $list = Db::name('goods_bargain')
            ->alias('gb')
            ->join(['pt_goods' => 'g'],'g.id=gb.goods_id','left')
            ->field($field)
            ->where($map)
            ->find();
        if(!empty($list)){
            $images = $list['images'];
            $video = $list['video'];
            $picShow = [];
            if(!empty($images)){
                $images = explode(',',$images);
                foreach ($images as $k=>$val){
                    $picShow[$k]['type'] = 0;
                    $picShow[$k]['link'] = $val;
                }
            }
            if(!empty($video)){
                $video1['type'] = 1;
                $video1['link'] = $video;
                array_unshift($picShow,$video1);
            }
            $list['picShow'] = $picShow;
            unset($list['images']);
        }
        return $list;
    }

    /**
     * commit: 获取门店下的所有活动商品
     * function: getStoreGoodsList
     * @param $map
     * $map['gb.storeid'] = $storeid;
     * $map['gb.pid'] = 0;
     * $map['g.is_bargain'] = 1;
     * $map['g.status'] = 1;
     * author: stars<1014916675@qq.com>
     * dateTime: 2019-10-21 17:13:33
     * @return array
     */
    public function getStoreGoodsList($map,$page = 1,$limit = 15){
        $filed = "g.image,g.images,g.xc_images,g.name,g.price,g.goods_cate,g.storeid sid";
        $filed .= ",g.activity_price,gb.goods_id,gb.storeid,g.bargain_number";
        $list = Db::name('goods_bargain')
            ->alias('gb')
            ->field($filed)
            ->join(['pt_goods' => 'g'],'g.id=gb.goods_id','left')
            ->where($map)
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $goods_id = $val['goods_id'];
                //获取当前商品中的用户参与人数
                $list[$key]['partake_num'] = Db::name('bargain_record')
                    ->where('goods_id','=',$goods_id)
                    ->where('status','=',1)
                    ->count('distinct uid');
            }
        }
        return $list;
    }

}