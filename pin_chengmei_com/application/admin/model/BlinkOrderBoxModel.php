<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkOrderBoxModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'blink_order_box';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getCreateTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getStatusAttr($value){
        return $value ? '<div><span class="label label-danger">已拆</span></div>' : '<div><span class="label label-success">未拆</span></div>';
    }
    public function getIsGiveAttr($value){
        if($value == 1){
            return '<div><span class="label label-danger">已赠送</span></div>';
        }elseif($value == 2){
            return '<div><span class="label label-success">赠送中</span></div>';
        }else{
            return '<div><span class="label label-default">未赠送</span></div>';
        }
    }
    public function getBoxCount($map){
        return $this->alias('box')->where($map)->count();
    }
    public function getBoxLists($map,$Nowpage = 1,$limits = 10){
        return $this->alias('box')
            ->join(['pt_goods'=>'g'],'box.goods_id=g.id','left')
            ->field('box.*,box.status as status1,box.is_give as is_give1,g.name,g.activity_price,g.image')
            ->where($map)
            ->page($Nowpage,$limits)
            ->select()->toArray();
    }
    public function getCurrentOrderUserInfo($order_id = 0){
        return Db::name('blink_order')
            ->alias('order')
            ->join(['ims_bj_shopn_member'=>'m'],'order.uid=m.id','left')
            ->field('order.order_sn,m.realname')
            ->where('order.id',$order_id)
            ->find();
    }

}