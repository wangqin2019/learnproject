<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkShareLogsModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'blink_share_logs';

    public function getTypeAttr($value){
        $status = [
            0 => '盲盒',
            1 => '鼠卡',
            2 => '卡券',
        ];
        return $status[$value];
    }


}