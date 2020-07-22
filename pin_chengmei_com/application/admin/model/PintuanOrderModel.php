<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class PintuanOrderModel extends Model
{
    protected $name = 'tuan_list';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    /**
     * 根据搜索条件获取拼团列表信息
     */
    public function getTuanByWhere($map, $Nowpage, $limits)
    {
        return $this->alias('list')->where($map)->field('list.*,branch.title,branch.sign,member.realname,member.staffid,info.p_name,info.p_price')->join(['ims_bwk_branch' => 'branch'],'list.storeid=branch.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=list.create_uid','left')->join('tuan_info info','info.id=list.tuan_id','left')->page($Nowpage, $limits)->order('list.success_time desc,list.insert_time desc')->select();
    }

    /**
     * 根据搜索条件获取拼团列表信息
     */
    public function getTuanPriceByWhere($map)
    {
        return $this->alias('list')->where($map)->join(['ims_bwk_branch' => 'branch'],'list.storeid=branch.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=list.create_uid','left')->join('tuan_info info','info.id=list.tuan_id','left')->sum('info.p_price');
    }

    /**
     * 根据搜索条件获取所有的拼团数量
     * @param $where
     */
//    public function getAllUsers($where)
//    {
//        return $this->where($where)->count();
//    }

    /**
     * 插入拼团信息
     * @param $param
     */
//    public function insertUser($param)
//    {
//        try{
//            $result = $this->validate('PintuanValidate')->allowField(true)->save($param);
//            if(false === $result){
//                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
//            }else{
//                writelog(session('uid'),session('username'),'拼团【'.$param['pt_name'].'】添加成功',1);
//                return ['code' => 1, 'data' => '', 'msg' => '添加拼团成功'];
//            }
//        }catch( PDOException $e){
//            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
//        }
//    }
//


    /**
     * 根据拼团id获取信息
     * @param $id
     */
    public function getOnePy($id)
    {
        return $this->alias('list')->field('list.*,branch.title,branch.sign,member.realname,g.name p_name')->join(['ims_bwk_branch' => 'branch'],'list.storeid=branch.id')->join(['ims_bj_shopn_member' => 'member'],'member.id=list.create_uid')->join('goods g','g.id=list.pid')->where('list.id', $id)->find();
    }

    //返回主订单下的订单列表
    public function orderList($orderSn){
        return Db::name('tuan_order')->alias('order')->field('order.order_sn,order.pay_price,order.pay_status,order.pay_time,order.pay_by_self,member.realname')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id','left')->where('order.parent_order',$orderSn)->select();
    }

    //返回主订单下的订单列表
    public function orderList1($orderSn){
        return Db::name('tuan_order')->alias('order')->field('order.uid,order.order_sn,order.pay_price,order.pay_status,order.pay_time,order.pay_by_self,member.realname,member.mobile,log.pay_check')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id','left')->join('pay_log log','order.transaction_id=log.transaction_id','left')->where('order.parent_order',$orderSn)->select();
    }

    /**
     * 删除拼团
     * @param $id
     */
    public function delPt($id)
    {
        try{

            $this->where('id', $id)->delete();
            writelog(session('uid'),session('username'),'删除拼团成功(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除拼团成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}