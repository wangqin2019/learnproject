<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BargainOrderModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'bargain_order';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    public function getIsTypeAttr($value){
        return $value ? '活动商品订单' : '奖励商品订单';
    }
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
    public function getPickTypeAttr($value){
        return $value ? '<div><span class="label label-info">到店取货</span></div>' : '<div><span class="label label-success">现场取货</span></div>';
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
            ->where($map)
            ->count();
    }

    /**
     * Commit: 获取订单列表及关联信息
     * Function: getOrderLists
     * @param $map
     * @param $nowpage
     * @param $limit
     * @param bool $flag
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 15:29:51
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderLists($map,$nowpage,$limit,$flag = true){
        $field = "order.id,order.storeid,order.uid,order.goods_id,order.fid,order.order_sn,order.pay_status,order.order_type";
        $field .= ",order.order_status,order.order_price,order.pay_price,order.insert_time,order.pay_time,order.is_type,order.order_type order_type1";
        $field .= ",order.transaction_id,order.out_trade_no,order.num,order.pick_type";
        $field .= ",IFNULL(bwk.title,'--') title,bwk.sign";
        $field .= ",g.name,g.bargain_number";
        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag";
        $field .= ",IFNULL(m.realname,'--') sellername";
        $field .= ",IFNULL(m.mobile,'--') sellermobile";
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
        $list = $model->order('order.id desc')->select();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $order_id = $val['id'];
                $promoter_id = $val['uid'];
                $goods_id = $val['goods_id'];
                $where = [];
                $where['order_id'] = $order_id;
                $where['goods_id'] = $goods_id;
                $_count = Db::name('bargain_record')->where($where)->count();//总数
                $where['status'] = 1;
                $where['promote_uid'] = $promoter_id;
                $number = Db::name('bargain_record')->where($where)->count();

                $order_type = $val['order_type1'];
                $bargain_number = $_count ?: ($val['bargain_number'] ?: self::get_config()['number']);
                $list[$key]['discount'] = $order_type == 1 ? $number . '/' . $bargain_number : '--';
                if(!session('get_mobile')){
                    $list[$key]['mobile']=substr_replace($val['mobile'], '****', 3, 4);
                }
            }
        }
        return $list;
    }
    public static function get_config($storeid = 0){
        return Db::name('bargain_config')->where('id',1)->find();
    }

    /**
     * Commit: 获取美容师信息
     * Function: getOrderBeautician
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-01 13:41:26
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getOrderBeautician(){
        return Db::name('bargain_order')
            ->alias('o')
            ->join(['ims_bj_shopn_member'=>'m'],'o.fid=m.id','left')
            ->field('o.fid,m.realname')
            ->where(['pay_status'=>1])
            ->group('o.fid')
            ->select();
    }

    /**
     * Commit: 获取成交金额
     * Function: getPayDeal
     * @Param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-01 14:01:40
     * @Return float|int
     */
    public function getPayDeal($map){
        $map['pay_status'] = 1;
        return $this->alias('order')
            ->join(['ims_bwk_branch' => 'bwk'],'order.storeid=bwk.id','left')
            ->where($map)
            ->sum('pay_price');
    }

    /**
     * Commit: 获取进行中的金额
     * Function: getUnderWayDeal
     * @Param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-01 14:07:15
     * @Return float|int
     */
    public function getUnderWayDeal($map){
        $duration = Db::name('bargain_config')->where('id',1)->value('duration') ?: 24;
        $time = time() - 3600 * $duration;
        $map['insert_time'] = ['>=',$time];
        $map['pay_status'] = 0;
        return $this->alias('order')
            ->join(['ims_bwk_branch' => 'bwk'],'order.storeid=bwk.id','left')
            ->where($map)
            ->sum('pay_price');
    }
























}