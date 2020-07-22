<?php

namespace app\blink\model;
use think\Model;
use think\Db;
//订单盲盒表

class BlinkOrderBoxModel extends Model
{
    protected $name = 'blink_order_box';

    public function getCreateTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getUpdateTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getCloseTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }

    /**
     * Commit: 获取用户未拆盲盒列表
     * Function: getUnremovedBlinkBoxList
     * @Param $map
     * @Param int $page
     * @Param int $limit
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 11:08:33
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getUnremovedBlinkBoxList($map ,$page = 1 ,$limit = 10){
        return self::alias('ca')
            ->join(['pt_goods'=>'g'],'ca.goods_id=g.id','left')
            ->field('ca.*,g.name,g.image as goods_image,g.xc_images as goods_thumb,g.intro')
            ->where($map)
            ->order(['ca.status'=>'asc','ca.create_time'=>'desc'])
            ->page($page,$limit)
            ->select();
    }
    /**
     * Commit: 获取用户未拆盲盒数量
     * Function: getUnremovedBlinkBoxCount
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 11:08:33
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getUnremovedBlinkBoxCount($map){
        return self::alias('ca')
            ->join(['pt_goods'=>'g'],'ca.goods_id=g.id','left')
            ->where($map)
            ->count();
    }


    /**
     * Commit: 获取用户已拆盲盒列表
     * Function: getRemovedBlinkBoxList
     * @Param $map
     * @Param int $page
     * @Param int $limit
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 11:08:33
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getRemovedBlinkBoxList($map ,$page = 1 ,$limit = 10){
        return self::alias('ca')
            ->join(['pt_goods'=>'g'],'ca.goods_id=g.id','left') //盲盒关联商品
            ->join(['pt_blink_box_coupon_user'=>'cu'],'cu.blinkno=ca.blinkno and cu.uid=ca.uid','left')//盲盒中的商品
            ->join(['pt_goods'=>'gg'],'cu.goods_id=gg.id','left')

            ->join(['pt_blink_order_box_card'=>'card'],'ca.blinkno=card.blinkno and ca.uid=card.uid','left')
            ->join(['pt_blink_box_card_image'=>'image'],'card.thumb_id=image.id','left')
            ->field('ca.*,cu.thumb_id,g.name,g.image as goods_image,g.intro,g.xc_images as goods_thumb,gg.name goods_name,gg.image,g.xc_images,image.thumb as card_thumb,image.name as card_name')
            ->where($map)
            ->order(['ca.status'=>'asc','ca.create_time'=>'desc'])
            ->page($page,$limit)
            ->select();
    }
    /**
     * Commit: 获取用户已拆盲盒数量
     * Function: getRemovedBlinkBoxCount
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 11:08:33
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getRemovedBlinkBoxCount($map){
        return self::alias('ca')
            ->join(['pt_goods'=>'g'],'ca.goods_id=g.id','left') //盲盒关联商品
            ->join(['pt_blink_box_coupon_user'=>'cu'],'cu.blinkno=ca.blinkno and cu.uid=ca.uid','left')//盲盒中的商品
            ->join(['pt_goods'=>'gg'],'cu.goods_id=gg.id','left')

            ->join(['pt_blink_order_box_card'=>'card'],'ca.blinkno=card.blinkno and ca.uid=card.uid','left')
            ->join(['pt_blink_box_card_image'=>'image'],'card.thumb_id=image.id','left')
            ->where($map)
            ->count();
    }

    /**
     * Commit:根据条件获取盲盒数据
     * Function: getBlinkBox
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 11:20:25
     * @Return array|false|\PDOStatement|string|Model
     */
    public static function getBlinkBox($map){
        return self::where($map)->order('create_time','desc')->find()->toArray();
    }

    public static function getBlinkBoxGoodsInfo($map){
        return self::alias('bob')
            ->join(['pt_goods'=>'g'],'bob.goods_id=g.id','left')
            ->where($map)
            ->field('bob.*,g.name goods_name,g.image goods_image,g.intro')
            ->order('bob.create_time','desc')
            ->find()->toArray();
    }
















}