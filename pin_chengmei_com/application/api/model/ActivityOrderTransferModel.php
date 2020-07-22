<?php

namespace app\api\model;
use think\Model;
use think\Db;

class ActivityOrderTransferModel extends Model
{

    protected  $name="activity_order_transfer";

    /**
     * 根据搜索条件获取记录
     * @param $where
     * @param $field
     */
    public function getOneInfo($where,$field){
        return $this->where($where)->field($field)->find();
    }


    /**
     * 根据搜索条件获取同享数量
     * @param $where
     */
    public function getAllTransfer($where)
    {
        return $this->where($where)->count();
    }

    /**数据插入
     * @param $param
     * @return int|string
     */
    public function insertTransfer($param)
    {
        return $this->insert($param);
    }

    /**
     * 数据修改
     * @param $where
     * @param $param
     */
    public function updateTransfer($where,$param)
    {
        return $this->where($where)->update($param);
    }

    /**
     * 根据条件统计对字段求和
     */
    public function getSum($where,$filed)
    {
        return $this->where($where)->sum($filed);
    }


}