<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkOrderModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'blink_order';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getPayTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getInsertTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getPayStatusAttr($value){
        return $value ? '<div><span class="label label-info">已支付</span></div>' : '<div><span class="label label-success">未支付</span></div>';
    }
    public function getOrderStatusAttr($value){
        return $value ? '<div><span class="label label-info">已取货</span></div>' : '<div><span class="label label-success">未取货</span></div>';
    }
    public function getOrderTypeAttr($value){
        $status = [
            0 => '参与',
            1 => '发起购买',
            2 => '直接购买',
            3 => '奖励购买',
        ];
        return $status[$value];
    }
    /**
     * Commit: 订单数量
     * Function: getOrderCount
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 10:58:02
     * @return int|string
     */
    public function getOrderCount($map)
    {
        return $this
            ->alias('order')
            ->join('pt_goods g','order.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member' => 'm'],'m.id=order.fid','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->where($map)
            ->count();
    }
    /**
     * Commit: 获取订单列表及关联信息
     * Function: getOrderLists
     * @Param $map
     * @Param $nowpage
     * @Param $limit
     * @Param bool $flag
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 15:29:51
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getOrderLists($map,$nowpage,$limit,$flag = true){
        $field = "order.id,order.storeid,order.uid,order.goods_id,order.fid,order.order_sn,order.pay_status";
        $field .= ",order.order_status,order.order_price,order.pay_price,order.insert_time,order.pay_time";
        $field .= ",order.transaction_id,order.out_trade_no,order.num,order.pick_type";
        $field .= ",bwk.title,bwk.sign";
        $field .= ",g.name,g.activity_price";
        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag,member.pid,member.originfid,member.id as mid,member.pid";

        $field .= ",IFNULL(m.realname,'--') sellername";
        $field .= ",IFNULL(m.mobile,'--') sellermobile,member.staffid,m.code,m.storeid as sellerstoreid";

        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";
        $model = $this
            ->alias('order')
            ->join('pt_goods g','order.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member' => 'm'],'m.id=order.fid','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map);
        if($flag){
            $model = $model ->page($nowpage, $limit);
        }
        $list = $model->order('order.id desc')->select()->toArray();

        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $info['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $info['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = '';//原始（发货）美容师ID
                    $list[$k]['origin_code']    = '';//原始（发货）美容师ID
                    $list[$k]['origin_name']    = '';//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = '';//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = '';//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = '';//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_storeid'] = '';//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = '';//原始（发货）美容师手所属办事处
                }
                if(!session('get_mobile')){
                    $list[$k]['mobile']=substr_replace($val['mobile'], '****', 3, 4);
                }
            }
        }
        return $list;
    }
    public function getExportOrderLists($map){
        $field = "order.id,order.storeid,order.uid,order.goods_id,order.fid,order.order_sn,order.pay_status";
        $field .= ",order.order_status,order.order_price,order.pay_price,order.insert_time,order.pay_time";
        $field .= ",order.transaction_id,order.out_trade_no,order.num,order.pick_type";
        $field .= ",bwk.title,bwk.sign";
        $field .= ",g.name,g.activity_price";

        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag,member.pid,member.originfid,member.pid";

        $field .= ",IFNULL(m.realname,'--') sellername,m.storeid as sellerstoreid";
        $field .= ",IFNULL(m.mobile,'--') sellermobile,m.code";

        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";
        $model = $this
            ->alias('order')
            ->join('pt_goods g','order.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member' => 'm'],'m.id=order.fid','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map);
        $list = $model->order('order.id desc')->select()->toArray();

        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $info['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $info['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = $val['staffid'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $val['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $val['sellername'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $val['sellermobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = $val['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $val['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_storeid'] = $val['sellerstoreid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $val['pertain_department_name'];//原始（发货）美容师手所属办事处
                }
            }
        }
        return $list;
    }
    /**
     * Commit: 获取美容师信息
     * Function: getOrderBeautician
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-01 13:41:26
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getOrderBeautician(){
        return $this
            ->alias('o')
            ->join(['ims_bj_shopn_member'=>'m'],'o.fid=m.id','left')
            ->field('o.fid,m.realname')
            ->where(['pay_status'=>1])
            ->group('o.fid')
            ->select();
    }

}