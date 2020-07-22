<?php

namespace app\api\model;
use think\Model;
use think\Db;

class ActivityOrderInfoModel extends Model
{

    protected  $order_info="activity_order_info";
    /**
     * 根据搜索条件获取订单列表信息
     */
    public function getOrderInfoByWhere($map)
    {
        return Db::name($this->order_info)->alias('info')->join('goods p','info.good_id=p.id','left')->field('info.good_id,info.good_num,info.good_price,info.good_amount,info.good_specs,info.main_flag,info.flag,info.pick_up,info.pick_code,info.is_sharing,p.name p_name,p.unit,p.images,p.g_draw_type,p.buy_type,p.use_num')->where($map)->order('info.main_flag desc,info.flag')->select();
    }

    /**
     * 根据订单号返回订单是否可以抽奖 已经抽什么奖,如果门店个性化了抽奖，统一采用翻牌子抽奖，
     * @param $map
     */
    public function getGoodsDraw($order_sn)
    {
        $orderInfo=Db::name('activity_order')->where('order_sn',$order_sn)->field('uid,fid')->find();
        if($orderInfo['uid']==$orderInfo['fid']){
            return 0;
        }else {
            $map['info.order_sn'] = array('eq', $order_sn);
            $map['info.main_flag'] = array('eq', 1);
            $info = Db::name($this->order_info)->alias('info')->join('activity_order o', 'info.order_sn=o.order_sn', 'left')->join('goods g', 'info.good_id=g.id')->where($map)->field('o.storeid,g_draw_type')->find();
            if ($info) {
                $check = Db::name('activity_branch_draw')->where('storeid', $info['storeid'])->count();
                if ($check) {
                    if ($info['g_draw_type'] == 0) {
                        return 0;
                    } else {
                        return 1;
                    }
                } else {
                    return $info['g_draw_type'];
                }
            } else {
                return 0;
            }
        }
    }

    /**
     * 数据修改
     * @param $where
     * @param $param
     */
    public function updateData($where,$param)
    {
        return Db::name($this->order_info)->where($where)->update($param);
    }


}