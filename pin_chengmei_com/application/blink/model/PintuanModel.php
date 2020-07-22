<?php

namespace app\blink\model;
use think\Model;
use think\Db;

class PintuanModel extends Model
{
    protected $tuanInfo = 'tuan_info';
    protected $tuanList = 'tuan_list';
    protected $tuanOrder = 'tuan_order';

    /**--------------------------tuan_info操作开始------------------------------------------*/


    /**
     * 根据搜索条件获取拼团列表信息
     */
    public function getTuanByWhere($map, $Nowpage, $limits)
    {
        return Db::name($this->tuanInfo)->field('id,p_name,pid,p_pic,pt_cover,pt_type,p_price,pt_num_max')->where($map)->page($Nowpage, $limits)->order('order_by desc')->select();
    }

    /**
     * 根据搜索条件获取所有的拼团数量
     * @param $where
     */
    public function getAllTuan($where)
    {
        return Db::name($this->tuanInfo)->where($where)->count();
    }

    //计算拼单发起人需支付金额
    public function getPayPrice($tuanId){
        $info=$this->getOnePy($tuanId);
        $price=$info['p_price']-($info['pt_buyer_max']*$info['buyer_price']);
        return number_format($price,2,'.','');
    }


    /**
     * 根据拼团id获取信息
     * @param $id
     */
    public function getOnePy($id)
    {
        return Db::name($this->tuanInfo)->alias('info')->join('goods g','info.pid=g.id')->join(['ims_bwk_branch' => 'b'],'info.storeid=b.id')->field('info.*,g.id pid,g.images,g.is_fenqi,g.fenqi,g.content,g.unit,g.buy_type,b.title,b.sign,b.address')->where('info.id', $id)->find();
    }

    /**--------------------------tuan_info操作结束------------------------------------------*/


    /**--------------------------tuan_list操作开始------------------------------------------*/

    /**
     * 根据条件获已拼团的拼团数量
     * @param $where
     */
    public function getPtCont($where)
    {
        return Db::name($this->tuanList)->where($where)->count();
    }


    /**
     * @param $where
     */
    public function getPtInfoCount($where)
    {
        $info=Db::name($this->tuanList)->where($where)->find();
        if(!$info){
            return true;
        }else{
            $order=Db::name($this->tuanOrder)->where('order_sn',$info['order_sn'])->find();
            if($order['order_status']==1 || $order['pay_status']==0){
                return false;
            }else{
                return true;
            }
        }

    }



    /**
     * 根据条件获已拼团的拼团信息
     * @param $where
     */
    public function getPtInfo($where)
    {
        return Db::name($this->tuanList)->where($where)->find();
    }


    public function getPtInfo1($where)
    {
        return Db::name($this->tuanList)->where($where)->find();
    }



    /**
     * 根据条件获已拼团的拼团列表
     * @param $where
     */
    public function getPtList($where)
    {
        return Db::name($this->tuanList)->where($where)->order('id desc')->select();
    }


    /**
     * 根据tuanid获拼团人发我的拼团信息
     * @param $tid
     */
    public function getJoinTuanInfo($tuanid){
        return Db::name($this->tuanList)->alias('tl')->join('pt_tuan_info info','tl.tuan_id=info.id','left')->join(['ims_bj_shopn_member' => 'member'],'tl.create_uid=member.id','left')->join(['ims_bwk_branch' => 'b'],'tl.storeid=b.id','left')->field('member.realname,member.mobile,tl.end_time,tl.order_sn,tl.tuan_id tid,tl.status,tl.tuan_num,tl.create_uid,tl.share_uid,tl.tuan_price,tl.initiator_pay,tl.partner_pay,tl.tuan_name,info.p_name,info.p_price,info.p_pic,info.p_intro,info.pt_intro,info.pt_rule1,info.buyer_price,info.prizeid,info.last_num,b.title')->where('tl.id',$tuanid)->find();
    }

    //根据美容师 查看拼团下线用户
    public function getTuanBySeller($sellerId){
        $list= $this->getPtList(['share_uid'=>$sellerId]);
        $result=[];
        $mem=new MemberModel();
        foreach ($list as $k=>$v){
            $owner=$mem->getOneInfo($v['create_uid']);
            $result[$k]['tuanOwner'] =$owner['realname'];
            $result[$k]['avatar'] =$owner['avatar'];
            $result[$k]['insert_time'] =date('Y-m-d',$v['insert_time']);
            $map['order.parent_order']=array('eq',$v['order_sn']);
            $map['order.order_status']=array('in','2,3');
            $map['order.pay_status']=array('eq','1');
            $result[$k]['list']=Db::name($this->tuanOrder)->alias('order')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id')->join(['ims_fans' => 'fans'],'member.id=fans.id_member')->field('member.realname,fans.avatar')->where($map)->select();
        }
        return $result;
    }

    public function SellerShareList($where)
    {
        return Db::name($this->tuanList)->where($where)->field('share_uid,count(id) total')->group('share_uid')->select();
    }

    public function SellerShareAnalysis($where)
    {
        return Db::name($this->tuanList)->where($where)->field('status,count(id) count')->group('status')->order('status')->select();
    }


    //返回用户订单信息
    public function getOrderByUser($map){
        $list=Db::name($this->tuanList)->alias('list')->field('list.status')->join('tuan_order order','order.parent_order=list.order_sn')->where($map)->column('list.status');
        if(!$list){
            return true;
        }else {
            if (in_array('2', $list) || in_array('3', $list) || in_array('4', $list) || in_array('5', $list)) {
                return true;
            } else {
                return false;
            }
        }
    }




    /**--------------------------tuan_list操作结束------------------------------------------*/


    /**--------------------------tuan_order操作开始------------------------------------------*/

    //检测该订单号下是否还有未支付的订单
    public function checkOrder($ordersn){
        $map['parent_order']=array('eq',$ordersn);
        $map['flag']=array('eq',1);
        $map['pay_status']=array('eq',0);
        $map['order_status']=array('eq',1);
        return Db::name($this->tuanOrder)->where($map)->count();
    }


    //返回该订单号下未支付订单 凑单一起支付
    public function surplusOrdersn($ordersn){
        $map['parent_order']=array('eq',$ordersn);
        $map['flag']=array('eq',1);
        $map['pay_status']=array('eq',0);
        $map['order_status']=array('eq',1);
        $orders=Db::name($this->tuanOrder)->where($map)->column('order_sn');
        return implode(',',$orders);
    }





    //根据单号 返回未支付的子订单编号
    public function getSonOrderSn($ordersn,$uid){
       $check=$this->checkOrder($ordersn);
       $res=[];
       if($check){
            try {
                $map['parent_order'] = array('eq', $ordersn);
                $map['flag'] = array('eq', 1);
                //判断该用户是否有未支付订单 有未支付订单直接返回未支付订单号
                $isHave = Db::name($this->tuanOrder)->where($map)->where(['pay_flag'=>1,'uid'=>$uid,'pay_status'=>0])->value('order_sn');
                if($isHave){
                    $res['code'] = 1;
                    $res['data'] = $isHave;
                }else{
                    //获取未拼购的订单号给该用户
                    $sn = Db::name($this->tuanOrder)->where($map)->where('pay_flag',0)->order('orderid')->value('order_sn');
                    if ($sn) {
                        Db::name($this->tuanOrder)->where('order_sn', $sn)->update(['pay_flag' => 1, 'pay_flag_time' => time(),'uid'=>$uid]);
                        $res['code'] = 1;
                        $res['data'] = $sn;
                    }else{
                        //如果拼购名额已被用完，检测是否有即将开放的名额 pay_flag为1 及支付锁定订单 5分钟解除锁定 允许别的用户购买
                        $hava_no_pay = Db::name($this->tuanOrder)->where($map)->where('pay_flag',1)->count();
                        $res['code'] = 2;
                        $res['data'] = $hava_no_pay;
                    }
                }

            }catch (\Exception $e){
                $res['code'] = 0;
                $res['data'] = $e->getMessage();
            }

       }else{
           $res['code']=0;
           $res['data']='拼团人数已满';
       }
       return $res;
    }


    //返回订单信息
    public function getOrder($map){
        return  Db::name($this->tuanOrder)->alias('order')->field('order.order_sn,order.uid,order.order_status,order.pay_status,order.pay_price,order.pay_flag,order.buy_good_ids,member.realname,member.mobile')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id')->where($map)->order('orderid')->select();
    }




    //返回单条订单商品信息
    public function getBuyOrder($ordersn){
        $map['order_sn']=array('eq',$ordersn);
        return Db::name($this->tuanList)->alias('list')->field('list.id,list.order_sn,list.tuan_num,list.end_time,list.tuan_id,list.order_type,member.realname,u.nickname,u.avatar,info.p_name,info.p_pic,info.p_price')->join('tuan_info info','list.tuan_id=info.id','left')->join(['ims_bj_shopn_member' => 'member'],'list.create_uid=member.id','left')->join('wx_user u','member.mobile=u.mobile','left')->where($map)->find();
    }

    //获取团付款用户
    public function getTuanPaidMember($ordersn){
        $map['order.parent_order']=array('eq',$ordersn);
        //$map['order.order_status']=array('in','2,3,4');
        //$map['order.pay_status']=array('gt',0);
        $map['order.pay_flag']=array('eq',2);
        $map['order.flag']=array('eq',1);
        return Db::name($this->tuanOrder)->alias('order')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id','left')->join('wx_user u','member.mobile=u.mobile','left')->field('member.id,member.mobile,u.nickname,u.avatar,order.pay_by_self,order.pay_price,order.order_sn,order.parent_order')->where($map)->order('pay_time asc')->select();
    }


    //根据付款用户获取购买的产品信息
    public function getBuyOrderInfo($ordersn,$uid){
        $map['order.parent_order']=array('eq',$ordersn);
        $map['member.id']=array('eq',$uid);
        return Db::name($this->tuanOrder)->alias('order')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id','left')->join('wx_user u','member.mobile=u.mobile','left')->field('member.id,u.nickname,u.avatar,member.mobile,order.pay_by_self,order.pay_price,order.order_sn,order.parent_order')->where($map)->select();
    }


    //获取订单信息
    public function getOrderInfo($map){
        return Db::name($this->tuanOrder)->where($map)->find();
    }

    //计算拼单发起人凑单金额
    public function getPriceBySelfPay($order_sn){
        $map['parent_order']=array('eq',$order_sn);
        //$map['order_status']=array('in','2,3');
        //$map['pay_status']=array('eq',1);
        $map['pay_flag']=array('eq',2);
        $map['flag']=array('eq',1);
        $map['pay_by_self']=array('eq',1);
        $price= Db::name($this->tuanOrder)->where($map)->sum('pay_price');
        return number_format($price,2,'.','');
    }

    //获取发起人支付金额（发起人支付金额+凑单金额） 2018-09-20修改
    public function fqrPay($orderSn,$uid){
        $map['parent_order']=array('eq',$orderSn);
        $map['pay_flag']=array('eq',2);
        $map['uid']=array('eq',$uid);
        $price= Db::name($this->tuanOrder)->where($map)->sum('pay_price');
        return number_format($price,2,'.','');
    }

    //获取参团人的支付数量及支付平均耗时
    public function orderPayTime($time){
        $map['order.flag']=array('eq',1);
        $map['order.pay_by_self']=array('eq',0);
        $map['list.order_type']=array('eq',1);
        $count=Db::name($this->tuanOrder)->alias('order')->join('pt_tuan_list list','order.parent_order=list.order_sn','left')->where($map)->count();//总数
        $gtSelf=Db::name($this->tuanOrder)->alias('order')->join('pt_tuan_list list','order.parent_order=list.order_sn','left')->where($map)->where('order.process_time','egt',$time)->count();//大于等于自己的
        return ($gtSelf/($count-1))*100;
    }
    /**--------------------------tuan_order操作结束------------------------------------------*/

    /*------订单状态----*/

    public function getOrderStatus($status){
        switch ($status){
            case 1:
                $status='进行中';
                break;
            case 2:
                $status='已成团';
                break;
            case 3:
                $status='已失效';
                break;
            case 4:
                $status='已退款';
                break;
            case 5:
                $status='已失效';
                break;
        }
        return $status;
    }





}