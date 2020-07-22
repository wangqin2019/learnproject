<?php

namespace app\blink\model;
use think\Model;
use think\Db;
//用户鼠卡赠送记录

class BlinkCardRecordModel extends Model
{
    protected $name = 'blink_card_give_record';

    /**
     * Commit: 根据条件获取鼠卡记录数据
     * Function: getCardRecord
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:39:52
     * @Return array
     */
    public static function getCardRecord($map){
        return self::where($map)
            ->find()
            ->toArray();
    }
    /**
     * Commit: 根据条件获取鼠卡记录数据
     * Function: getCardRecordInfo
     * @Param $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:39:52
     * @Return array
     */
    public static function getCardRecordInfo($id){
        return self::where('id',$id)
            ->find()
            ->toArray();
    }

















}