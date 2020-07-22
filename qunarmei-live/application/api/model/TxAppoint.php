<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/3/14
 * Time: 17:10
 */

namespace app\api\model;


use think\Model;

class TxAppoint extends Model
{
    protected $table = 'store_tx_appoint';
    // 返回create_time原始数据，不进行时间戳转换
    public function getCreateTimeAttr($time)
    {
        return $time;
    }
}