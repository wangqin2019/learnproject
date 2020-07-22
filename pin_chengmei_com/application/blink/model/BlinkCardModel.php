<?php

namespace app\blink\model;
use think\Model;
//用户鼠卡记录表

class BlinkCardModel extends Model
{
    protected $name = 'blink_order_box_card';
    protected $pk = 'id';

    /**
     * Commit: 获取盲盒活动鼠卡数据
     * Function: getAllRatsList
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:12:09
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public static function getAllRatsList($map){
        return self::where($map)->order(['type'=>'desc','id'=>'asc'])->select();
    }
    public static function getCurrentUserRatsGroupID($map){
        return self::where($map)->group('thumb_id')->column('id');
    }
    /**
     * Commit: 根据条件获取当前鼠卡的数量
     * Function: getCurrentRatIdCount
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:19:46
     * @Return int|string
     */
    public static function getCurrentRatIdCount($map){
        return self::where($map)->count();
    }

    /**
     * Commit: 获取当前用户合成鼠卡的数量
     * Function: getCurrentRatComposeCount
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:26:55
     * @Return int|string
     */
    public static function getCurrentRatComposeCount($map){
        return self::where($map)->count('DISTINCT thumb_id');
    }

    /**
     * Commit: 根据条件随机获取一张鼠卡的编号
     * Function: getRatCardNoAtRandom
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:22:32
     * @Return mixed
     */
    public static function getRatCardNoAtRandom($map){
        return self::where($map)->orderRaw('rand()')->value('cardno') ?: '';
    }

    /**
     * Commit: 获取当前用户的某一张鼠卡数据
     * Function: getCurrentCardInfo
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:35:17
     * @Return array|false|\PDOStatement|string|Model
     */
    public static function getCurrentCardInfo($map){
        return self::where($map)->order('create_time','desc')->find();
    }
}