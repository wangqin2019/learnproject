<?php
namespace app\qmjk\controller;
use app\api\model\MemberModel;
use think\Db;
use weixinaes\wxBizDataCrypt;

/**
 * swagger: 全民集客报表
 */
class Report extends Base
{
   public function branch_report(){
       $list=Db::name('qmjk_branch')->column('id');
       if(is_array($list) && count($list)){
           foreach ($list as $k=>$v){
               //合作门店数量
               $union_total=Db::name('qmjk_union_relation')->where(['branch_id'=>$v,'step'=>2,'status'=>1])->count();
               //集客数量
               $customer_total=Db::name('qmjk_member')->where(['branch_id'=>$v,'type'=>3])->count();
               //有成交的人数
               $map['jkm.branch_id']=array('eq',$v);
               $order = $this->getOrder($map);
               //产生交易的集客人数
               $customer_pay_total=count($order);
               //产生交易的集客订单总额
               $customer_order_total=$this->getSum($order);
               //交易人数平均成交价钱
               if($customer_pay_total==0){
                   $order_avg=0;
               }else{
                   $order_avg=$customer_order_total/$customer_pay_total;
               }
               //集客人数转化率
               if($customer_total==0){
                   $conversion_rate=0;
               }else{
                   $conversion_rate=($customer_pay_total/$customer_total)*100;
               }
               $insertData=array('branch_id'=>$v,'union_total'=>$union_total,'customer_total'=>$customer_total,'customer_pay_total'=>$customer_pay_total,'customer_order_total'=>$customer_order_total,'order_avg'=>$order_avg,'conversion_rate'=>$conversion_rate,'report_date'=>date('Y-m-d'));
               $check=Db::name('qmjk_branch_report')->where('branch_id',$v)->count();
                if($check){
                    Db::name('qmjk_branch_report')->where('branch_id',$v)->update($insertData);
                }else{
                    Db::name('qmjk_branch_report')->insert($insertData);
                }
           }
       }
   }


    public function union_report(){
        $list=Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->field('r.branch_id,r.union_id,u.title')->where(['r.step'=>2,'r.status'=>1])->select();
        if(is_array($list) && count($list)){
           foreach ($list as $k=>$v){
                $week_customer=$this->getCustomer($v['branch_id'],$v['union_id'],'week');
                $month_customer=$this->getCustomer($v['branch_id'],$v['union_id'],'month');
                $year_customer=$this->getCustomer($v['branch_id'],$v['union_id'],'year');
                $total_customer=Db::name('qmjk_member')->where(['branch_id'=>$v['branch_id'],'union_id'=>$v['union_id'],'type'=>3])->count();
                $map['jkm.branch_id']=array('eq',$v['branch_id']);
                $map['jkm.union_id']=array('eq',$v['union_id']);
                $order = $this->getOrder($map);
                //产生交易的集客人数
                $customer_pay_total=count($order);
                //产生交易的集客订单总额
                $customer_order_total=$this->getSum($order);
                //交易人数平均成交价钱
                if($customer_pay_total==0){
                   $order_avg=0;
                }else{
                   $order_avg=$customer_order_total/$customer_pay_total;
                }
                //集客人数转化率
                if($total_customer==0){
                   $conversion_rate=0;
                }else{
                   $conversion_rate=($customer_pay_total/$total_customer)*100;
                }
               $insertData=array('title'=>$v['title'],'branch_id'=>$v['branch_id'],'union_id'=>$v['union_id'],'week_customer'=>$week_customer,'month_customer'=>$month_customer,'year_customer'=>$year_customer,'total_customer'=>$total_customer,'customer_pay_total'=>$customer_pay_total,'customer_order_total'=>$customer_order_total,'order_avg'=>$order_avg,'conversion_rate'=>$conversion_rate,'report_date'=>date('Y-m-d'));
               $check=Db::name('qmjk_union_report')->where(['branch_id'=>$v['branch_id'],'union_id'=>$v['union_id']])->count();
               if($check){
                   Db::name('qmjk_union_report')->where(['branch_id'=>$v['branch_id'],'union_id'=>$v['union_id']])->update($insertData);
               }else{
                   Db::name('qmjk_union_report')->insert($insertData);
               }
            }
        }

    }



    private function getOrder($where){
        $where['o.status']=array('in','1,2,3,7');
        $where['jkm.type']=array('eq',3);
       $order = Db::name('qmjk_member')->alias('jkm')->join(['ims_bj_shopn_member' => 'm'], 'jkm.mobile=m.mobile', 'left')->join(['ims_bj_shopn_order' => 'o'], 'm.id=o.uid', 'left')->where($where)->field('m.id,sum(o.price) price')->group('jkm.mobile')->select();
       return $order;
    }

    private function getCustomer($branch_id,$union_id,$date_flag){
       switch ($date_flag){
           case 'week':
               $count=Db::name('qmjk_member')->where(['branch_id'=>$branch_id,'union_id'=>$union_id,'type'=>3])->whereTime('insert_time', 'week')->count();
               break;
           case 'month':
               $count=Db::name('qmjk_member')->where(['branch_id'=>$branch_id,'union_id'=>$union_id,'type'=>3])->whereTime('insert_time', 'month')->count();
               break;
           case 'year':
               $count=Db::name('qmjk_member')->where(['branch_id'=>$branch_id,'union_id'=>$union_id,'type'=>3])->whereTime('insert_time', 'year')->count();
               break;
       }
       return $count?$count:0;
    }



   private function getSum($data){
       $total=0;
       if(is_array($data) && count($data)){
           foreach ($data as $k=>$v){
               $total+=$v['price'];
           }
       }
       return $total;
   }

}