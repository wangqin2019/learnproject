<?php

namespace app\blink\model;
use think\Model;
use think\Db;
//用户盲盒赠送记录

class BlinkBoxRecordModel extends Model
{
    protected $name = 'blink_box_give_record';

    /**
     * Commit: 根据条件获取盲盒记录数据
     * Function: getBoxRecord
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:39:52
     * @Return array
     */
    public static function getBoxRecord($map){
        return self::where($map)
            ->find()
            ->toArray();
    }

















}