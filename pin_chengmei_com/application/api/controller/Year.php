<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:老板抽奖
 */
class Year extends Base
{
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
        if($token==''){
            $code = 400;
            $data = '';
            $msg = '非法请求';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }else{
            if(!parent::checkToken($token)) {
                $code = 400;
                $data = '';
                $msg = '用户登陆信息过期，请重新登录！';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }else{
                return true;
            }
        }
    }

    //检测活动
    public function check_activity(){
        $activityInfo=Db::name('new_year_config')->where('id',1)->cache(60)->find();
        if($activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动已结束！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
        if($activityInfo['begin_time'] > time() ){
            $code = 0;
            $data = '';
            $msg = '活动将于'.date('Y年m月d日 H时i分s秒',$activityInfo['begin_time']).'开启，请等待！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }

        if($activityInfo['end_time'] < time() ){
            $code = 0;
            $data = '';
            $msg = '活动已结束，如您已参与，请于2019年3月31日前到指定门店领取礼品';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
    }

    //改变活动参加状态
    public function activitySwitch(){
        $uid=input('param.uid');
        $switchFlag=input('param.switchFlag');
        try {
            Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['activity_key' => $switchFlag]);
			Db::name('new_year_boss_log')->insert(['uid'=>$uid,'flag'=>$switchFlag,'insert_time'=>time()]);
            $code = 1;
            $data = '';
            $msg = '修改成功！';
        }catch (\Exception $e){
            $code = 0;
            $data = '';
            $msg = '修改失败！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 查看用户是否已经参与过活动
     * @return \think\response\Json
     */
    public function check_coupon()
    {
        $uid=input('param.uid');
        if($uid!=''){
            $map['uid']=array('eq',$uid);
            $info = Db::name('new_year_coupon_order')->field('uid,pay_status,coupon_price,order_sn')->where($map)->find();
            if(count($info) && is_array($info)){
                if($info['pay_status']){
                    if($info['coupon_price']){
                        $code = 1;
                        $data = array('flag'=>1,'info'=>$info);
                        $msg = '已参加过活动！';
                    }else{
                        $code = 1;
                        $data = array('flag'=>2,'info'=>$info);
                        $msg = '已支付 但未获取代金券！';
                    }
                }else{
                    $this->check_activity();
                    $code = 1;
                    $data = array('flag'=>3,'info'=>$info);
                    $msg = '订单已生成，但未付款，去付款！';
                }
            }else{
                $this->check_activity();
                $code = 1;
                $data = array('flag'=>4,'info'=>[]);
                $msg = '没参与过，可继续';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 创建订单
     *
     * */
    public function createOrder(){
        $this->check_activity();
        $getPayPrice=Db::name('new_year_config')->where('id',1)->value('price');
        $getOrderCount=Db::name('new_year_coupon_order')->field('id')->where('pay_status',1)->whereTime('insert_time', 'today')->count();
        if($getOrderCount>=2000){
            $code = 0;
            $data = '';
            $msg = '今天福袋抢完啦，明日请赶早哦!';
            return parent::returnMsg($code,$data,$msg);
        }
        $uid = input('param.uid');
        $storeid = input('param.storeid');
        $map['uid'] = array('eq', $uid);
        $info = Db::name('new_year_coupon_order')->field('uid,pay_status,coupon_price,order_sn')->where($map)->find();
        if (count($info) && is_array($info)) {
            if ($info['pay_status']) {
                $code = 0;
                $data = $info;
                $msg = '您已经参加过该活动了！';
            } else {
                $res['order_sn'] = $info['order_sn'];
                $res['attach'] = 'activity';
                $res['total_fee'] = $getPayPrice;
                $res['user_id'] = $uid;
                $res['buy_type'] = 2;
                $res['body'] = '参加诚美耀福年活动';
                $code = 1;
                $data = $res;
                $msg = '订单已生成，去付款！';
            }
        } else {
            try {
                $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . $uid;
                $arr = array('uid' => $uid, 'storeid'=>$storeid, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'insert_time' => time());
                Db::name('new_year_coupon_order')->insert($arr);
                $res['order_sn'] = $ordersn;
                $res['attach'] = 'activity';
                $res['total_fee'] = $getPayPrice;
                $res['user_id'] = $uid;
                $res['buy_type'] = 2;
                $res['body'] = '参加诚美耀福年活动';
                $code = 1;
                $data = $res;
                $msg = '订单已生成，去付款！';
            } catch (\Exception $e) {
                $code = 0;
                $data = '';
                $msg = '订单生成失败';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 摇一摇获取代金券
     * @return \think\response\Json
     */
    public function rock_coupon()
    {
        //$this->check_activity();
        $uid=input('param.uid');
        $order_sn=input('param.order_sn');
        $coupon =Db::name('new_year_coupon_order')->where('uid',$uid)->field('uid,pay_status,order_sn,coupon_price')->find();
        if($coupon && $coupon['coupon_price'] && $coupon['pay_status']) {
            $code = 0;
            $data = $coupon;
            $msg = '您已经摇过了！';
        }else{
            $map['coupon_status'] = array('eq', 1);
            $map['coupon_num'] = array('gt', 0);
            $list = Db::name('new_year_coupon')->field('id,coupon_name,coupon_num')->where($map)->select();
            foreach ($list as $key => $val) {
                $arr[$val['id']] = $val['coupon_num'];
            }
            $coupon_id = $this->getRand($arr); //根据概率获取奖品id
            $couponInfo = Db::name('new_year_coupon')->field('id,coupon_name,coupon_price')->where('id', $coupon_id)->find();
            if (count($couponInfo) && is_array($couponInfo)) {
                $map1['uid']=array('eq',$uid);
                $map1['pay_status']=array('eq',1);
                $map1['coupon_price']=array('neq',0);
                $check=Db::name('new_year_coupon_order')->where($map1)->find();
                if($check) {
                    $code = 0;
                    $data = '';
                    $msg = '您已经摇过了！';
                }else{
                    try {
                        Db::name('new_year_coupon')->where('id', $couponInfo['id'])->setDec('coupon_num');
                        Db::name('new_year_coupon_order')->where('order_sn', $order_sn)->update(['coupon_price' => $couponInfo['coupon_price']]);
                        $pic = "https://pin.qunarmei.com/static/xcx/coupon" . $couponInfo['coupon_price'] . ".jpg";
                        $userInfo = Db::table('ims_bj_shopn_member')->alias('member')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->field('member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,bwk.title,depart.st_department')->where('member.id', $uid)->find();
                        //将奖券插入卡券表
                        $check1 = Db::name('ticket_user')->where(['status' => 0, 'ticket_code' => '000000', 'mobile' => $userInfo['mobile']])->count();
                        if (!$check1) {
                            $ticket['depart'] = $userInfo['st_department'];
                            $ticket['storeid'] = $userInfo['storeid'];
                            $ticket['branch'] = $userInfo['title'];
                            $ticket['sign'] = $userInfo['sign'];
                            $ticket['mobile'] = $userInfo['mobile'];
                            $ticket['ticket_code'] = '000000';
                            $ticket['type'] = 3;
                            $ticket['par_value'] = 0;
                            $ticket['insert_time'] = date('Y-m-d H:i:s');
                            $ticket['update_time'] = date('Y-m-d H:i:s');
                            $ticket['draw_pic'] = $pic;
                            Db::name('ticket_user')->insert($ticket);
                        }
                        $code = 1;
                        $data = $couponInfo;
                        $msg = '代金券获取成功！';
                    }catch (\Exception $e){
                        $code = 0;
                        $data = '';
                        $msg = '错误 '.$e->getMessage();
                    }
                }
            } else {
                $code = 0;
                $data = '';
                $msg = '代金券获取失败！';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 手动分配代金券
     * @return \think\response\Json
     */
    public function rock_coupon_by_hand()
    {
        $this->check_activity();
        $coupon =Db::name('new_year_coupon_order')->where(['pay_status'=>1,'coupon_price'=>0])->field('uid,pay_status,order_sn,coupon_price')->select();
        try {
            if(count($coupon)) {
                foreach ($coupon as $k=>$v) {
                    $pic = "https://pin.qunarmei.com/static/xcx/coupon300.jpg";
                    $userInfo = Db::table('ims_bj_shopn_member')->alias('member')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->field('member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,bwk.title,depart.st_department')->where('member.id', $v['uid'])->find();
                    //将奖券插入卡券表
                    $check1 = Db::name('ticket_user')->where(['status' => 0, 'ticket_code' => '000000', 'mobile' => $userInfo['mobile']])->count();
                    if (!$check1) {
                        $ticket['depart'] = $userInfo['st_department'];
                        $ticket['storeid'] = $userInfo['storeid'];
                        $ticket['branch'] = $userInfo['title'];
                        $ticket['sign'] = $userInfo['sign'];
                        $ticket['mobile'] = $userInfo['mobile'];
                        $ticket['ticket_code'] = '000000';
                        $ticket['type'] = 3;
                        $ticket['par_value'] = 0;
                        $ticket['insert_time'] = date('Y-m-d H:i:s');
                        $ticket['update_time'] = date('Y-m-d H:i:s');
                        $ticket['draw_pic'] = $pic;
                        Db::name('ticket_user')->insert($ticket);
                        Db::name('new_year_coupon')->where('id', 1)->setDec('coupon_num');
                        Db::name('new_year_coupon_order')->where('order_sn', $v['order_sn'])->update(['coupon_price' => 300]);
                    }
                }
            }
            $code = 1;
            $data = '';
            $msg = '手动发送完成';
        }catch (\Exception $e){
            $code = 0;
            $data = '';
            $msg = '错误 '.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }



    //网上经典的计算中奖概率方法
    function getRand($proArr) {
        $data = '';
        $proSum = array_sum($proArr); //概率数组的总概率精度
        foreach ($proArr as $k => $v) { //概率数组循环
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $data = $k;
                break;
            } else {
                $proSum -= $v;
            }
        }
        unset($proArr);
        return $data;
    }
}