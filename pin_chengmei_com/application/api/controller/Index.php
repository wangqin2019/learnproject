<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Index extends Controller
{
    public function index()
    {

        $getMainOrderSn="20180815094236370520";

        $getScore=Db::name('goods')->alias('g')->join('tuan_list list','g.id=list.pid','left')->where('list.order_sn',$getMainOrderSn)->value('g.score');
        //获取当前单所属门店
        $getOrder=Db::name('tuan_list')->field('share_uid,storeid')->where('order_sn',$getMainOrderSn)->find();
        //给美容师增加积分,先获取当前单有几个新顾客 新顾客判断标注：member表id_regsource为7 且只支付过1单
        $orderList=Db::name('tuan_order')->field('uid')->where(['parent_order'=>$getMainOrderSn,'flag'=>1])->select();
        $num=0;
        $memId=[];
        foreach ($orderList as $k=>$v){
            $map1['member.id_regsource']=array('eq',7);
            $map1['order.order_status']=array('eq',2);
            $map1['order.pay_status']=array('eq',1);
            $map1['order.uid']=array('eq',$v['uid']);
            $orderCount=Db::name('tuan_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id','left')->where($map1)->count();
            if($orderCount==1){
                $memId[]=$v['uid'];
                $num++;
            }
        }
        $have=Db::name('seller_score')->where('order_sn',$getMainOrderSn)->count();
        if(!$have){
            $scoreToal=$num*$getScore;
            $scoreData=array('sellerid'=>$getOrder['share_uid'],'storeid'=>$getOrder['storeid'],'order_sn'=>$getMainOrderSn,'memberid'=>implode(',',$memId),'score'=>$scoreToal,'insert_time'=>time());
            Db::name('seller_score')->insert($scoreData);
        }
    }
	
	
	
	    public function updateMember(){
        $getMembers=Db::table('ims_bj_shopn_member')->field('id,staffid,isadmin,mobile')->where(['isadmin'=>1,'staffid'=>0])->select();
        if(count($getMembers) && is_array($getMembers)){
            foreach ($getMembers as $k=>$v){
                $have=Db::table('ims_fans')->where('mobile',$v['mobile'])->count();
                if(!$have){
                    $fansData=array('weid'=>1,'createtime'=>time(),'realname'=>'','nickname'=>'','avatar'=>'','mobile'=>$v['mobile'],'id_member'=>$v['id']);
                    Db::table('ims_fans')->insert($fansData);
                }
                if($v['staffid']==0){
                    Db::table('ims_bj_shopn_member')->where('id',$v['id'])->update(['staffid'=>$v['id']]);
                }
            }
        }
    }
	
}
