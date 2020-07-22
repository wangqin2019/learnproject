<?php

namespace app\api\model;
use think\Model;
use think\Db;

class ActivityOrderModel extends Model
{

    protected  $order="activity_order";
    /**
     * 根据搜索条件获取订单列表信息
     */
    public function getOrdersByWhere($map,$Nowpage, $limits)
    {
        return Db::name($this->order)->alias('o')->join('goods p','o.pid=p.id','left')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('o.id order_id,o.uid,o.fid,o.order_sn,o.pay_price,o.order_price,o.num,o.specs,o.flag,o.coupon_price,o.pid,o.order_status,p.name p_name,p.unit,p.images,p.activity_id,p.buy_type,m.realname,m.mobile,u.avatar,u.nickname,o.is_axs')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
    }
    /**
     * 美容师获取其下顾客订单列表信息
     */
    public function sellerGetOrders($map,$Nowpage, $limits)
    {
        return Db::name($this->order)->alias('o')->join('goods p','o.pid=p.id','left')->field('o.id order_id,o.order_sn,o.pay_price,o.num,o.specs,o.flag,o.coupon_price,p.name p_name,p.unit,p.images')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
    }

    /**
     * 根据id返回订单信息
     * @param $id
     */
    public function getOrderInfo($id)
    {
        return Db::name($this->order)->alias('o')->join('goods p','o.pid=p.id','left')->field('o.*,p.name p_name,p.unit,p.images,p.unit,p.g_draw_type,p.activity_id')->where('o.id',$id)->find();
    }

    /**
     * 根据搜索条件获取订单数量
     * @param $where
     */
    public function getOrderCount($where)
    {
        return Db::name($this->order)->alias('o')->join('goods p','o.pid=p.id','left')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->where($where)->count();
    }

    /**
     * 根据单号返回产品类型
     * @param $order_sn
     */

    public function getOrderGoodType($order_sn){
        $info=Db::name($this->order)->where('order_sn',$order_sn)->field('flag,pid')->find();
        if($info['flag']){
            $getGoodType=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where(['order_sn'=>$order_sn,'main_flag'=>1])->value('activity_id');
        }else{
            $getGoodType=Db::name('goods')->where('id',$info['pid'])->value('activity_id');
        }
        return $getGoodType;
    }


    public function getOneInfo($where,$field){
        return Db::name($this->order)->where($where)->field($field)->find();
    }


    /**
     * 数据修改
     * @param $where
     * @param $param
     */
    public function updateOrder($where,$param)
    {
        return Db::name($this->order)->where($where)->update($param);
    }

    /**
     * 返回订单分享信息
     * @param $map
     * @return array|false|\PDOStatement|string|Model
     */
    public function getOrderShareInfo($map,$pid=0)
    {
        $info=Db::name($this->order)->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('o.id order_id,o.uid,o.order_sn,o.pay_price,o.flag,o.pid,o.num,m.realname,m.mobile,u.avatar,u.nickname')->where($map)->find();
        if($info['flag']){
            $where['order_sn']=array('eq',$info['order_sn']);
            if($pid){
                $where['good_id']=array('eq',$pid);
            }else{
                $where['main_flag']=array('eq',1);
            }
            $info['goods']=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where($where)->field('name,unit,images,use_num,good_num,good_amount')->find();
            $info['goods']['price']=$info['goods']['good_amount'];
            $info['goods']['use_num']=$info['goods']['use_num']*$info['goods']['good_num'];
            unset($info['goods']['good_num'],$info['goods']['good_amount']);
        }else{
            $info['goods']=Db::name('goods')->where('id',$info['pid'])->field('name,unit,images,use_num')->find();
            $info['goods']['price']=$info['pay_price'];
            $info['goods']['use_num']=$info['goods']['use_num']*$info['num'];
        }
        unset($info['num'],$info['pid'],$info['flag'],$info['pay_price']);
        return $info;
    }


}