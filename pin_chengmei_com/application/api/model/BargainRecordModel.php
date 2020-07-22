<?php

namespace app\api\model;
use think\Model;
use think\Db;

class BargainRecordModel extends Model
{
    protected $name = 'bargain_record';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * Commit: 获取单个发起订单的砍价总金额
     * Function: orderSum
     * @param $map
     *
     * $where['order_id'] = $info['order_id'];
     * $where['goods_id'] = $goods_id;
     * $where['promote_uid'] = $promote_uid;
     * $where['status'] = 1;
     *
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:12:36
     * @return float|int
     */
    public function orderSum($map)
    {
        return $this->where($map)->sum('price'); //已砍价格
    }
    /**
     * Commit: 获取参与人参与的订单列表
     * Function: partakeUidOrderListInfo
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:17:37
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function partakeUidOrderListInfo($map)
    {
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time";
        $field .= ",o.transaction_id,out_trade_no,o.is_type,g.name";
        return Db::name('bargain_record')
            ->alias('br')
            ->field($field)
            ->join(['pt_bargain_order'=>'o'],'br.order_id=o.id','left')
            ->join(['pt_goods'=>'g'],'br.goods_id=g.id','left')
            ->where($map)
            ->select();
    }
    /**
     * Commit: 获取参与人参与的订单信息
     * Function: partakeUidOrderInfo
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:17:37
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function partakeUidOrderInfo($map)
    {
        return Db::name('bargain_record')
            ->alias('br')
            ->field('br.order_id,g.name,br.goods_id')
            ->join(['pt_goods'=>'g'],'br.goods_id=g.id','left')
            ->where($map)
            ->find();
    }
    /**
     * Commit: 获取发起人下属的参与人记录
     * Function: partakeRecordList
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 13:30:10
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function partakeRecordList($map){
        return Db::name('bargain_record')
            ->where($map)
            ->select();
    }
    /**
     * Commit: 获取当前活动参与人数量
     * Function: recordCount
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 16:42:40
     * @return int|string
     */
    public function recordCount($map)
    {
        return Db::name('bargain_record')
            ->where($map)
            ->count();
    }

    /**
     * Commit: 获取当前用户是否能够参与砍价 能true 不能false
     * Function: getUIDHasPartakeBargain
     * @param $map
     *
     * ->where('uid','=',$uid)
     * ->where('order_id','=',$order_id)
     * ->where('goods_id','=',$goods_id)
     * ->where('promote_uid','=',$promote_uid)
     *
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 16:44:35
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUIDHasPartakeBargain($map)
    {
        $res = Db::name('bargain_record')
            ->where($map)
            ->find();
        return !empty($res) ?  false : true ;
    }
}