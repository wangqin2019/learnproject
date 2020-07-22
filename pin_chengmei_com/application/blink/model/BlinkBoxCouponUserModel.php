<?php

namespace app\blink\model;
use think\Model;
use think\Db;
//用户卡券商品表（卡券和商品）

class BlinkBoxCouponUserModel extends Model
{
    protected $name = 'blink_box_coupon_user';

    public function getInsertTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getUpdateTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getShareTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getSourceStrAttr($value,$data){
        $source = $data['source'];
        $arr = [0=>'盲盒',1=>'好友赠送',2=>'好友助力',3=>'合成赠送'];
        return $arr[$source];
    }
    public function getSourceAttr($value){
        $arr = [0=>'盲盒',1=>'好友赠送',2=>'好友助力',3=>'合成赠送'];
        return array_key_exists($value,$arr) ? $arr[$value] : "其他";
    }

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
            ->field('user.*,g.name as goods_name,g.image as image,g.images,g.intro,g.activity_price')
            ->join(['pt_goods'=>'g'],'user.goods_id=g.id','left')
            ->where($map)
            ->order('insert_time','desc')
            ->page($page,$limit)
            ->select();
    }
    public static function getCurrentUserGroupCoupons($map,$page = 1,$limit = 10){
        return self::alias('cu')
            ->field('cu.price,cu.goods_id,cu.uid,g.id,g.name,g.image,g.activity_price,g.intro')
            ->join(['pt_goods'=>'g'],'user.goods_id=g.id','left')
            ->where($map)
            ->order('insert_time','desc')
            ->page($page,$limit)
            ->group('cu.goods_id')
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
        return self::alias('user')->where($map)->count() ?: 0;
    }
    public static function getCurrentUserGroupCouponsCount($map){
        return self::alias('cu')
            ->join(['pt_goods'=>'g'],'user.goods_id=g.id','left')
            ->where($map)
            ->count('DISTINCT cu.goods_id') ?: 0;
    }

    /**
     * Commit: 根据条件获取优惠券数据
     * Function: getCoupon
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:39:52
     * @Return array
     */
    public static function getCoupon($map){
        return self::where($map)
            ->order('insert_time','desc')
            ->find()->toArray();
    }

















}