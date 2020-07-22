<?php

namespace app\blink\model;
use think\Model;
//鼠卡表

class BlinkCardImageModel extends Model
{
    protected $name = 'blink_box_card_image';
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

    /**
     * Commit: 获取盲盒活动中五张鼠卡的ID集合
     * Function: getAllRatsIDs
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:24:35
     * @Return array
     */
    public static function getAllRatsIDs($map){
        return self::where($map)->column('id');
    }
    /**
     * Commit: 获取盲盒活动中某一张鼠卡信息
     * Function: getRatInfo
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:24:35
     * @Return array
     */
    public static function getRatInfo($map){
        return self::where($map)->find();
    }
    public static function getRatID($map){
        return self::where($map)->value('id');
    }

    /**
     * Commit: 获取盲盒活动中五张鼠卡的集合
     * Function: getAllRats
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 13:24:35
     * @Return array
     */
    public static function getAllRats($map){
        return self::where($map)->column('id,number,name','id');
    }
}