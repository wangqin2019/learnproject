<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2020/1/15
 * Time: 13:33
 * Description:
 */

namespace app\admin\controller;

use think\Db;

class Test extends Base
{
    //获取当前用户及其下级用户 成交单数
    public function getCurrentNextUserOrder(){
        $uid = input('get.uid/a');
        var_dump($uid);
        //查询当前用户及下级
        $field = "m.id,m.pid,m.staffid,m.storeid,m.realname,m.mobile";
        $field .= ",u.nickname,u.avatar";
        $field .= ",bwk.title,bwk.sign";
        $field .= ",mm.realname p_realname,mm.mobile p_mobile";
        //$field .= ",bwk1.title sellertitle,bwk1.sign sellersign";
        $field .= ",count(o.uid) count";
        $members = Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->field($field)
            ->join(['pt_blink_wx_user'=>'u'],'u.mobile=m.mobile','left')
            ->join(['ims_bwk_branch'=>'bwk'],'bwk.id=m.storeid','left')
            ->join(['ims_bj_shopn_member'=>'mm'],'mm.id=m.pid','left')
            //->join(['ims_bwk_branch'=>'bwk1'],'bwk1.id=mm.storeid','left')
            ->join(['pt_blink_order'=>'o'],'m.id=o.uid','left')
            ->where('m.id','in',$uid)
            ->whereOr('m.pid','in',$uid)
            ->group('m.id')
            ->select();
        var_dump($members);
    }

    public function getcoupon(){
        set_time_limit(0);
        ini_set("memory_limit", "1024M");


            $list = Db::name('blink_box_coupon_user')
                ->where('status','=',0)
                ->where('share_status','=',0)
                ->where('pid','=',0)
                ->where('id','>=',1)
                ->where('id','<',2000)
                ->field('id,ticket_code,uid,pid')
                ->select();
            if(!empty($list)){
                foreach ($list as $k=>$val){
                    $re = Db::name('blink_box_coupon_user')
                        ->where('id',$val['id'])
                        ->update([
                            'qrcode' => pickUpCode('blinkcoupon_'.$val['ticket_code'].'_'.$val['id']),
                            'update_time' => time()
                        ]);
                }
            }
            echo count($list);

    }
    public function getcoupon1(){
        echo pickUpCode('blinkcoupon_15199299312870_27977');
    }
}