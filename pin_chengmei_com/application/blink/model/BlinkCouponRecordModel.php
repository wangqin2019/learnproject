<?php

namespace app\blink\model;
use think\Model;
use think\Db;
//用户卡券商品赠送记录

class BlinkCouponRecordModel extends Model
{
    protected $name = 'blink_coupon_give_record';


    /**
     * Commit: 获取当前用户的优惠券商品
     * Function: getCurrentUserCoupons
     * @Param $map
     * @Param int $page
     * @Param int $limit
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 09:58:16
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getCurrentUserCoupons($map,$page = 1,$limit = 10){
        return self::alias('user')
            ->field('user.*,g.name as goods_name,g.image as image,g.images,g.intro')
            ->join(['pt_goods'=>'g'],'user.goods_id=g.id','left')
            ->where($map)
            ->order('insert_time','desc')
            ->page($page,$limit)
            ->select();
    }
    /**
     * Commit: 获取当前用户的优惠券商品数量
     * Function: getCurrentUserCouponsCount
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 09:58:16
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getCurrentUserCouponsCount($map){
        return self::alias('user')->where($map)->count();
    }

    /**
     * Commit: 根据条件获取优惠券数据
     * Function: getCoupon
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:39:52
     * @Return array
     */
    public static function getCouponRecord($map){
        return self::where($map)
            ->find()
            ->toArray();
    }

















}