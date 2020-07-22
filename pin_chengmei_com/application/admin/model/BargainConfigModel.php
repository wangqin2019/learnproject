<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BargainConfigModel extends Model
{
    protected $name = 'bargain_config';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    public function getBeginTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getEndTimeAttr($value)
    {
        return $value?date('Y-m-d H:i:s',$value):'未支付';
    }
    public function getShowTimeAttr($value)
    {
        return $value?date('Y-m-d H:i:s',$value):'未支付';
    }
    /*public function getActivityStatusAttr($value)
    {
        return $value?'开始':'关闭';
    }
    public function getBootsStatusAttr($value)
    {
        return $value?'开':'关';
    }*/
}