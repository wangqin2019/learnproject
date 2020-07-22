<?php

namespace app\blink\model;
use think\Model;
//盲盒订单表

class BlinkOrderModel extends Model
{
    protected $name = 'blink_order';

    public function getCreateTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getUpdateTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }
    public function getCloseTimeAttr($value){
        return $value?date('Y-m-d H:i:s',$value) :'';
    }

    /**
     * Commit: 获取美容师下的所有顾客数量
     * Function: getAllBeautyCustomerCount
     * @Param $map
     * @Param $map1
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 14:55:55
     * @Return int|string
     */
    public static function getAllBeautyCustomerCount($map,$map1){
        return self::alias('o')
            ->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id and o.pay_status=1','left')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->join(['ims_bj_shopn_member'=>'mm'],'m.staffid=mm.id','left')
            ->where($map)
            ->whereOr($map1)
            ->count('DISTINCT o.uid');
    }
    /**
     * Commit: 获取美容师下的所有顾客
     * Function: getAllBeautyCustomers
     * @Param $map
     * @Param $map1
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 14:55:55
     * @Return int|string
     */
    public static function getAllBeautyCustomers($map ,$map1 ,$page=1 ,$limit = 10){
        $field = "m.id,m.storeid,m.pid,m.staffid,m.originfid,m.realname,m.mobile,m.activity_flag";
        $field .= ",m.code,u.nickname,u.avatar";
        $field .= ",mm.realname as sellername,mm.mobile as sellermobile,mm.id as sellerid,mm.code sellercode,mm.staffid sellerstaffid";
        return self::alias('o')
            ->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id and o.pay_status=1','left')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->join(['ims_bj_shopn_member'=>'mm'],'m.staffid=mm.id','left')
            ->field($field)
            ->where($map)
            ->whereOr($map1)
            ->page($page,$limit)
            ->group('o.uid')
            ->select();
    }






}