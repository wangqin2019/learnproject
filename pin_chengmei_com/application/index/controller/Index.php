<?php
namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Log;

class Index extends Controller
{




    public function index(){
        Log::write('pay-wechat', 'sdfsdfsdf',Log::DEBUG);

    }


    public function test_send(){
//        $ordersn='2020032109513056817854';
//        $orderInfo=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id','left')->where('o.order_sn',$ordersn)->join('activity_order_address a','o.order_sn=a.order_sn','left','left')->field('m.mobile,o.fid,o.storeid,m.realname,a.*')->find();
//        //给购买人发送短信
//        $arr1 = array('mail_param' => array(), 'sms_param' => array('smsId' => 119, 'param' => array('mobile' => $orderInfo['mobile'], 'order_sn' => $ordersn,'express_name' => $orderInfo['express_name'],'express_number' => $orderInfo['express_number'])));
//        sendCommQueue($arr1, 2);
//        //给所属美容师发送短信
//        //尊敬的*role*，您的会员*name*在去哪美购买的订单*order_sn*已发货，*express_name*包裹*express_number*。
//        $sellerMobile=Db::table('ims_bj_shopn_member')->where('id',$orderInfo['fid'])->value('mobile');
//        if($sellerMobile) {
//            $arr2 = array('mail_param' => array(), 'sms_param' => array('smsId' => 122, 'param' => array('mobile' => $sellerMobile, 'role' => '美容师', 'name' => $orderInfo['realname'], 'order_sn' => $ordersn, 'express_name' => $orderInfo['express_name'], 'express_number' => $orderInfo['express_number'])));
//            sendCommQueue($arr2, 2);
//        }
//        //给门店老板发送短信
//        $bossMobile=Db::table('ims_bj_shopn_member')->where(['storeid'=>$orderInfo['storeid'],'isadmin'=>1])->value('mobile');
//        if($bossMobile) {
//            $arr3 = array('mail_param' => array(), 'sms_param' => array('smsId' => 122, 'param' => array('mobile' => $bossMobile, 'role' => '店老板', 'name' => $orderInfo['realname'], 'order_sn' => $ordersn, 'express_name' => $orderInfo['express_name'], 'express_number' => $orderInfo['express_number'])));
//            sendCommQueue($arr3, 2);
//        }
    }

    public function test(){
       auto_worker('live_room_user',['openid'=>'11111111111111','roomid'=>2,'insert_time'=>date('Y-m-d H:i:s')],'log');
    }



    public function update_address(){
        set_time_limit(0);
        $list=Db::name('temp_414')->where('flag',0)->limit(100)->order('storeid')->select();
       if(count($list)){
           foreach ($list as $k=>$v){
               Db::table('ims_bwk_branch')->where('id',$v['storeid'])->update(['receive_address'=>$v['address'],'receive_consignee'=>$v['name'],'receive_mobile'=>$v['mobile']]);
               Db::name('temp_414')->where('id',$v['id'])->update(['flag'=>1]);
           }
       }else{
           echo '已处理完成';
       }
    }

    //418内定抽奖
    public function send_draw(){
        $num=input('param.num');
        $list=Db::name('temp418')->where('num',$num)->field('num,code,name')->select();
        if(count($list)){
            foreach ($list as $v){
                $data = array('flag' => 1, 'update_time' => date('Y-m-d H:i:s'), 'draw_rank' => '第'.$v['num'].'轮', 'draw_name' => $v['name'], 'draw_pic' => 'http://ml.chengmei.com/jp1_0416.png');
                Db::name('ticket_user')->where('ticket_code',$v['code'])->update($data);
            }
        }else{
            echo '当场没有奖品可抽';
            exit;
        }

    }



    public function test_mail(){
            send_mail('451035207@qq.com','代餐发货报告','代餐发货报告','代餐发货报告已生成，请查看附件','',0);
        die();
    }




    public function aaa(){
        $flag=input('param.flag');
        $res=[];
        $lists = Db::table('ims_bwk_branch_20200316')->field('id,sign,join_pg,join_tk')->select();
        foreach ($lists as $kk => $vv) {
            if(!empty($vv['join_tk'])){
                $arr=explode(',',$vv['join_tk']);
                if(in_array($flag,$arr)){
                    $res[]['sign']=$vv['sign'];
                }
            }
        }

        $filename = $flag;
        $header = array ('门店编码');
        $widths=array('20');
        if($res) {
            comm_excelExport($filename, $header, $res, $widths);//生成数据
        }
        //echo $str;
    }


    public function bbb(){
        set_time_limit(0);
        $list=Db::name('temp0512')->where('flag',0)->limit(200)->select();
        if(count($list)){
            foreach ($list as $k=>$v){
                Db::table('ims_bwk_branch')->where('id',$v['sid'])->update(['join_tk'=>$v['tk']]);
                Db::name('temp0512')->where('id',$v['id'])->update(['flag'=>1]);
            }
        }else{
            echo '已处理完成';
        }
    }




    public function ccc(){
        $map['m.isadmin'] = ['eq',1];
        $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->select();
        $i=0;
        Db::startTrans();
        foreach ($lists as $kk => $vv) {
            if(!empty($vv['join_tk'])){
                if(!$vv['activity_key']){
                    Db::table('ims_bj_shopn_member')->where('mobile',$vv['mobile'])->update(['activity_key'=>1]);
                }
            }
            if ($i % 500 == 0) {
                Db::commit();
                Db::startTrans();
            }
            $i++;
        }
        Db::commit();

    }









	

}
