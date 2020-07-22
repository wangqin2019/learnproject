<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkboxImageModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'blink_box_card_image';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    public function getTypeAttr($value){
        return $value ? '合成卡' : '普通卡';
    }
    public function getUpdateTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getCreateTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
















}