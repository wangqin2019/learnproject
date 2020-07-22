<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkboxConfigModel extends Model
{
    protected $name = 'blink_box_config';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    public function getStartTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getEndTimeAttr($value)
    {
        return $value?date('Y-m-d H:i:s',$value):'未支付';
    }
}