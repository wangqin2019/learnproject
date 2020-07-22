<?php

namespace app\api\model;
use think\Model;
use think\Db;

class ActivityOrderSharingModel extends Model
{

    protected  $name="activity_order_sharing";



    public function getAll($order_sn){
        $where['order_sn']=array('eq',$order_sn);
        $where['sharing_flag']=array('eq',1);
        $where['accept_flag']=array('eq',1);
        return $this->where($where)->alias('s')->join(['ims_bj_shopn_member' => 'm'],'s.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('m.mobile,m.realname,u.nickname,u.avatar,s.num,s.accept_time')->where($where)->select();
    }
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
    public function getAllSharing($where)
    {
        return $this->where($where)->count();
    }

    /**数据插入
     * @param $param
     * @return int|string
     */
    public function insertSharing($param)
    {
        return $this->insert($param);
    }

    /**
     * 数据修改
     * @param $where
     * @param $param
     */
    public function updateSharing($where,$param)
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