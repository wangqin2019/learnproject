<?php

namespace app\blink\model;
use think\Model;
use think\Db;

class BranchSellerSaleSumModel extends Model
{

    protected  $table="branch_seller_sale_sum";


    /**
     * 获取美容师销售额趋势
     * @param $role
     * @param $storeid
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getSellerData($role,$storeid){
        $map=[];
        if($role==0){
            $map['s.storeid']=$storeid;
        }
        return Db::name($this->table)->where($map)->alias('s')->join(['ims_bj_shopn_member'=>'m'],'s.sid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('m.id sellerid,m.realname,m.mobile,u.nickname,u.avatar,sum(s.total) total')->group('sid')->order('total desc')->select();
    }

    /**
     * 获取前三十天的日期
     * @return array
     */
    public function getDay(){
        $current_day=date('Y-m-d',strtotime("-1 day"));
        $history_day=date("Y-m-d",strtotime("-31 day"));
        return [$history_day,$current_day];
    }



}