<?php

namespace app\api\model;
use think\Model;
use think\Db;

class BargainOrderModel extends Model
{
    protected $name = 'bargain_order';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * Commit: 获取参与人参与的订单信息
     * Function: partakeOrderInfo
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:17:37
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function partakeOrderInfo($map)
    {
        return $this->field('storeid,order_sn,order_price,pay_price,insert_time')->where($map)->find();
    }
    /**
     * Commit: 获取参与人参与的订单列表
     * Function: promoteUidOrderListInfo
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:17:37
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function promoteUidOrderListInfo($map)
    {
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number";
        $field .= ",o.transaction_id,out_trade_no,o.is_type,g.name";
        return Db::name('bargain_order')
            ->alias('o')
            ->field($field)
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->where($map)
            ->select();;
    }
    /**
     * Commit: 获取参与人参与的订单信息
     * Function: partakeUidOrderInfo
     * @param $map
     *
     * #$map['o.storeid'] = $storeid;
     * #$map['o.uid'] = $uid;
     * $map['o.id'] = $order_id;
     *
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:17:37
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function promoteUidOrderInfo($map)
    {
        $field = "o.id as order_id,o.uid,o.fid,o.storeid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.pay_status,o.insert_time,g.name,g.bargain_number";
        $field .= ",(o.order_price - o.pay_price) bargain_price,o.num,g.image,g.images,g.storeid sid";
        $list = Db::name('bargain_order')
            ->alias('o')
            ->field($field)
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->where($map)
            ->find();
        if(!empty($list)){
            $images = $list['images'];
            if(!empty($images)){
                $images = explode(',',$images);
                $square = $images[0];
            }else{
                $square = $list['image'];
            }

            $list['square'] = $square;
        }
        return $list;
    }
    /**
     * Commit: 查询参与人是否购买过当前奖励产品
     * Function: getRewardGoodsOrder
     * @param $map
     *
     * $a['id'] = $order_id;
     * $a['storeid'] = $storeid;
     * $a['goods_id'] = 55;//奖励产品id  目前就一个固定
     * $a['pay_status'] = 1;
     *
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-18 10:40:32
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUIDRewardGoodsOrder($map){
        $res = Db::name('bargain_order')
            ->where($map)
            ->find();
        return $res;
    }
    /**
     * Commit: 查询参与人是否能够购买当前奖励产品 能true 不能false
     * Function: getUIDHasBuyRewardGoodsOrder
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-25 14:56:47
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUIDHasBuyRewardGoodsOrder($map){
        $res = Db::name('bargain_order')
            ->where($map)
            ->find();
        return !empty($res) ? false : true;
    }

    /**
     * Commit: 获取正在进行中的发起订单及参与订单 未支付 在有效期内
     * Function: getUnderWayOrder
     * @param $storeid 门店id
     * @param $uid 当前用户id
     * @param int $duration 订单有效市场 H
     * @param $page 当前分页
     * @param $limit 每页条数
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-18 13:36:48
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function getUnderWayOrder($storeid,$uid,$duration = 24,$page = 1,$limit = 15){
        $time = time() - $duration * 3600;
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.is_type";
        //活动进行中的发起订单sql
        $promote = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field.',1 flag,o.order_type')
            ->where('o.storeid','=',$storeid)
            ->where('o.uid','=',$uid)
            ->where('o.pay_status','=',0)
            ->where('o.order_type','<>',3)
            ->where('o.insert_time','>=',$time)
            ->buildSql();

        //获取参与人的订单 order_type=0
        $partake = Db::name('bargain_record')
            ->alias('br')
            ->field($field.',0 flag, 0 as order_type')
            ->join(['pt_bargain_order'=>'o'],'br.order_id=o.id','left')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->where('br.uid','=',$uid)
            ->where('br.status','=',1)
            ->where('o.insert_time','>=',$time)
            ->buildSql();

        $sql = "select abc.*,(abc.order_price - abc.pay_price) bargain_price from ({$promote} UNION ( {$partake} ) ) abc";
        $sql .= " ORDER BY abc.flag desc,abc.insert_time desc ";
        $sql .= " limit ".($page - 1) * $limit . "," . $limit;
        $list = Db::query($sql);
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where['order_id'] = $val['order_id'];//订单id
                $where['goods_id'] = $val['goods_id'];//订单id
                $where['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where);
                $list[$key]['spell_price'] = $spell_price ?:0;
                $list[$key]['lack_price'] = round($bargain_price - $spell_price,2);
                $list[$key]['reba'] = $bargain_price ? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
            }
        }

        return $list;
    }
    /**
     * Commit: 获取正在进行中的发起订单及参与订单 未支付 在有效期内
     * Function: getFailureOrderList
     * @Param $where
     * @Param int $page
     * @Param int $limit
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-22 13:11:28
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getUnderWayOrderList($where,$page = 1,$limit = 15){
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number,o.status,o.close_time";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.is_type";
        //活动进行中的发起订单sql
        $promote = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field.',1 flag,o.order_type')
            ->where($where)
            ->buildSql();

        //获取参与人的订单 order_type=0
        $uid = $where['o.uid'];
        unset($where['o.uid']);
        $where['br.uid'] = $uid;
        $partake = Db::name('bargain_record')
            ->alias('br')
            ->field($field.',0 flag, 0 as order_type')
            ->join(['pt_bargain_order'=>'o'],'br.order_id=o.id','left')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->where($where)
            ->buildSql();

        $sql = "select abc.*,(abc.order_price - abc.pay_price) bargain_price from ({$promote} UNION ( {$partake} ) ) abc";
        $sql .= " ORDER BY abc.insert_time desc ";//abc.flag desc,
        $sql .= " limit ".($page - 1) * $limit . "," . $limit;
        $list = Db::query($sql);
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where1['order_id'] = $val['order_id'];//订单id
                $where1['goods_id'] = $val['goods_id'];//订单id
                $where1['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where1);
                $list[$key]['spell_price'] = $spell_price ?:0;
                $list[$key]['lack_price'] = round($bargain_price - $spell_price,2);
                $list[$key]['reba'] = $bargain_price ? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
            }
        }

        return $list;
    }

    /**
     * Commit: 获取已过期的发起订单及奖励订单 未支付 不在有效期内
     * Function: getFailureOrder
     * @param $storeid 门店id
     * @param $uid 当前用户id
     * @param int $duration 订单有效市场 H
     * @param $page 当前分页
     * @param $limit 每页条数
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-18 13:58:09
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function getFailureOrder($storeid,$uid,$duration = 24,$page = 1,$limit = 15){
        $time = time() - $duration * 3600;
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.order_type,o.is_type";
        //活动进行中的发起订单sql
        $list = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field.',1 flag')
            ->where('o.storeid','=',$storeid)
            ->where('o.uid','=',$uid)
            ->where('o.pay_status','=',0)
            ->where('o.insert_time','<=',$time)
            ->order('o.insert_time',' desc')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where['order_id'] = $val['order_id'];//订单id
                $where['goods_id'] = $val['goods_id'];//订单id
                $where['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where);
                $list[$key]['spell_price'] = $spell_price;
                $list[$key]['lack_price'] = round($bargain_price  - $spell_price,2);
                $list[$key]['reba'] = $bargain_price? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
            }
        }
        return $list;
    }
    /**
     * Commit: 获取已过期的发起订单及奖励订单 未支付 失效订单
     * Function: getFailureOrderList
     * @Param $where
     * @Param int $page
     * @Param int $limit
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-22 13:11:28
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getFailureOrderList($where,$page = 1,$limit = 15){
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number,o.status,o.close_time";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.order_type,o.is_type";
        //活动进行中的发起订单sql
        $list = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field.',1 flag')
            ->where($where)
            ->order('o.insert_time',' desc')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where1['order_id'] = $val['order_id'];//订单id
                $where1['goods_id'] = $val['goods_id'];//订单id
                $where1['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where1);
                $list[$key]['spell_price'] = $spell_price;
                $list[$key]['lack_price'] = round($bargain_price  - $spell_price,2);
                $list[$key]['reba'] = $bargain_price? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
            }
        }
        return $list;
    }

    /**
     * Commit: 获取已支付的发起订单及奖励订单 翼支付
     * Function: getPaymentOrder
     * @param $storeid 门店id
     * @param $uid 当前用户id
     * @param $page 当前分页
     * @param $limit 每页条数
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-18 13:58:09
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function getPaymentOrder($storeid,$uid,$page = 1,$limit = 15){
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.order_type,o.is_type";
        //活动进行中的发起订单sql
        $list = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field.',1 flag')
            ->where('o.storeid','=',$storeid)
            ->where('o.uid','=',$uid)
            ->where('o.pay_status','=',1)
            ->order('o.insert_time',' desc')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where['order_id'] = $val['order_id'];//订单id
                $where['goods_id'] = $val['goods_id'];//订单id
                $where['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where);
                $list[$key]['spell_price'] = $spell_price;
                $lack_price = round($list[$key]['bargain_price'] - $spell_price,2);
                $list[$key]['lack_price'] = $lack_price;
                $list[$key]['reba'] = $bargain_price? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
            }
        }
        return $list;
    }
    /**
     * Commit: 获取已支付的发起订单及奖励订单 翼支付
     * Function: getFailureOrderList
     * @Param $where
     * @Param int $page
     * @Param int $limit
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-22 13:11:28
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getPaymentOrderList($where,$page = 1,$limit = 15){
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number,o.close_time";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.order_type,o.is_type";
        //活动进行中的发起订单sql
        $list = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field.',1 flag')
            ->where($where)
            ->order('o.insert_time',' desc')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where1['order_id'] = $val['order_id'];//订单id
                $where1['goods_id'] = $val['goods_id'];//订单id
                $where1['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where1);
                $list[$key]['spell_price'] = $spell_price;
                $lack_price = round($list[$key]['bargain_price'] - $spell_price,2);
                $list[$key]['lack_price'] = $lack_price;
                $list[$key]['reba'] = $bargain_price? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
            }
        }
        return $list;
    }

    /**
     * Commit: 获取美容师（下属）订单列表
     * Function: getBeauticianOrderList
     * @Param $where
     * @Param int $page
     * @Param int $limit
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-22 15:45:04
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getBeauticianOrderList($where,$page = 1,$limit = 15){
        $field = "o.id as order_id,o.storeid,o.uid,o.fid,o.goods_id,o.order_sn,o.order_price,o.pay_price,o.insert_time,o.pay_status,o.pay_time,g.bargain_number,o.status,o.close_time";
        $field .= ",o.transaction_id,out_trade_no,g.name,g.image,g.images,o.pick_code,o.num,o.is_purchase,o.order_type,o.is_type";
        //活动进行中的发起订单sql
        $list = $this->alias('o')
            ->join(['pt_goods'=>'g'],'o.goods_id=g.id','left')
            ->field($field)
            ->where($where)
            ->order('o.insert_time','desc')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            $BargainRecordModel = new BargainRecordModel();
            foreach ($list as $key=>$val){
                $where1['order_id'] = $val['order_id'];//订单id
                $where1['goods_id'] = $val['goods_id'];//订单id
                $where1['status'] = 1;
                $bargain_price = $val['order_price'] - $val['pay_price'];
                $list[$key]['bargain_price'] = $bargain_price;
                $spell_price = $BargainRecordModel->orderSum($where1);
                $list[$key]['spell_price'] = $spell_price;
                $list[$key]['lack_price'] = round($bargain_price  - $spell_price,2);
                $list[$key]['reba'] = $bargain_price? round(($spell_price/$bargain_price) * 100 ,2).'%':0;

                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $square = $images[0];
                }else{
                    $square = $val['image'];
                }

                $list[$key]['square'] = $square;
                //获取当前订单发起人信息
                $list[$key]['userInfo'] = Db::table('ims_bj_shopn_member')
                    ->alias('member')
                    ->join(['pt_wx_user'=>'user'],'member.mobile=user.mobile','left')
                    ->field('member.id as uid,member.realname,member.mobile,user.nickname,user.avatar')
                    ->where('member.id','=',$val['uid'])
                    ->find();
            }
        }
        return $list;
    }
}