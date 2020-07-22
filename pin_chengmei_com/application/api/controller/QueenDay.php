<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:女王节活动
 */
class QueenDay extends Base
{
//    public function _initialize() {
//        parent::_initialize();
//        $token = input('param.token');
//        if($token==''){
//            $code = 400;
//            $data = '';
//            $msg = '非法请求';
//            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
//            exit;
//        }else{
//            if(!parent::checkToken($token)) {
//                $code = 400;
//                $data = '';
//                $msg = '用户登陆信息过期，请重新登录！';
//                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
//                exit;
//            }else{
//                return true;
//            }
//        }
//    }

    //检测活动
    public function activity_status(){
        $storeid=input('param.storeid');
        if($storeid!='') {
            $activityInfo = Db::name('queen_day_config')->where('id', 1)->cache(60)->find();
            if ($activityInfo['activity_status'] == 0) {
                $code = 0;
                $data = '';
                $msg = '活动未开始 不显示切换按钮！';
            } else {
                $allow = Db::name('activity_branch')->where('storeid', $storeid)->count();
                if ($allow) {
                    $code = 1;
                    $data = '';
                    $msg = '可以显示切换按钮了！';
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '不在参加活动列表 不显示切换按钮！';
                }

            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //检测活动
    public function check_activity($storeId,$number=0){
        $activityInfo=Db::name('queen_day_config')->where('id',1)->cache(60)->find();
        if($activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动未开始！';
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
            $msg = '活动已结束，如您已参与活动，请至您所属门店！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
        //检测所在门店是否可以参加活动
        $activityBranch=Db::name('activity_branch')->where('storeid',$storeId)->find();
        if(!count($activityBranch)){
            $code = 0;
            $data = '';
            $msg = '您所在门店未参加该活动，请联系您的美容师！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }else{
            $num=$this->branch_ticket_action($storeId);
            if(($num+$number)>$activityBranch['limit_num']){
                $code = 0;
                $data = '';
                $msg = '门店奖券额度已用完，请联系所属美容师';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }
        }
        return true;
    }

    //改变活动参加状态
    public function activitySwitch(){
        $uid=input('param.uid');
        $switchFlag=input('param.switchFlag');
        try {
            Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['activity_key' => $switchFlag]);
			Db::name('activity_switch_log')->insert(['uid'=>$uid,'flag'=>$switchFlag,'insert_time'=>time(),'remark'=>'38女王节活动']);
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
    public function check_user_status()
    {
        $uid=input('param.uid');
        $storeid=input('param.storeid');
        if($uid!='' && $storeid!=''){
			//获取活动详情图
            $getPic=Db::name('activity_branch')->where('storeid',$storeid)->value('pic');
            $map['uid']=array('eq',$uid);
            $count = Db::name('activity_order')->where($map)->count();
            if($count){
                $map1['pay_status']=array('eq',1);
                $count1 = Db::name('activity_order')->where($map)->where($map1)->count();
                $info=Db::name('activity_order')->where($map)->field('uid,order_sn,pay_status,order_price,step')->order('id desc')->find();
                if($count1==2){
                    $code = 1;
                    $data = array('flag'=>4,'status'=>'','pic'=>$getPic,'info'=>[]);
                    $msg = '已参加过活动';
                }else{
                    $this->check_activity($storeid);
                    if($info['pay_status']){
                        $res['step']=2;
                        $res['uid']=$uid;
                        $res['storeid']=$storeid;
                        $code = 1;
                        $data = array('flag'=>2,'status'=>'upgradePay','pic'=>$getPic,'info'=>$res);
                        $msg = '已参加过活动，可以继续升级！';
                    }else{
                        $res['order_sn'] = $info['order_sn'];
                        $res['attach'] = 'activity';
                        $res['total_fee'] = $info['order_price'];
                        $res['user_id'] = $uid;
                        $res['buy_type'] = 2;
                        $res['body'] = '参加诚美女王驾到 为爱加冕活动';
                        if($info['step']==1){
                            $status='firstPay';
                        }else{
                            $status='upgradePay';
                        }
                        $code = 1;
                        $data = array('flag'=>3,'status'=>$status,'pic'=>$getPic,'info'=>$res);
                        $msg = '订单已生成，但未付款，去付款！';
                    }
                }
            }else{
                $res['step']=1;
                $res['uid']=$uid;
                $res['storeid']=$storeid;
                $code = 1;
                $data = array('flag'=>1,'status'=>'firstPay','pic'=>$getPic,'info'=>$res);
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
        $uid = input('param.uid');
        $storeid = input('param.storeid');
        $step = input('param.step')?input('param.step'):1;
        try {
            $getPrice = Db::name('queen_day_config')->where('id', 1)->field('price,price1')->find();
            if ($step == 2) {
                $check = Db::name('activity_order')->field('uid,pay_status,order_sn')->where(['uid'=>$uid, 'step' => 1])->find();
                if ($check['pay_status']) {
                    $getPayPrice = $getPrice['price1'];
                } else {
                    $getPayPrice = $getPrice['price'];
                }
            } else {
                $getPayPrice = $getPrice['price'];
            }
            //检测
            $map['uid'] = array('eq', $uid);
            $map['order_price'] = array('eq', $getPayPrice);
            $info = Db::name('activity_order')->field('uid,pay_status,order_sn')->where($map)->find();
            if (count($info) && is_array($info)) {
                if ($info['pay_status']) {
                    $code = 0;
                    $data = $info;
                    $msg = '您的' . $getPayPrice . '元订单已支付，请勿重复操作！';
                } else {
                    $this->check_activity($storeid);
                    $res['order_sn'] = $info['order_sn'];
                    $res['attach'] = 'activity';
                    $res['total_fee'] = $getPayPrice;
                    $res['user_id'] = $uid;
                    $res['buy_type'] = 2;
                    $res['body'] = '参加诚美女王驾到 为爱加冕活动';
                    $code = 1;
                    $data = $res;
                    $msg = '订单已生成，去付款！';
                }
            } else {
                try {
                    if ($this->check_activity($storeid,$step)) {
                        $this->branch_ticket_action($storeid, 1, $step);//将数量占位
                    }
                    $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . $uid;
                    $arr = array('uid' => $uid, 'storeid' => $storeid, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'step' => $step, 'insert_time' => time());
                    Db::name('activity_order')->insert($arr);
                    $res['order_sn'] = $ordersn;
                    $res['attach'] = 'activity';
                    $res['total_fee'] = $getPayPrice;
                    $res['user_id'] = $uid;
                    $res['buy_type'] = 2;
                    $res['body'] = '参加诚美女王驾到 为爱加冕活动';
                    $code = 1;
                    $data = $res;
                    $msg = '订单已生成，去付款！';
                } catch (\Exception $e) {
                    $code = 0;
                    $data = '';
                    $msg = '订单生成失败';
                }
            }
        }catch (\Exception $e){
            $code = 0;
            $data = '';
            $msg = '失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //检查门店是否还有券可发放  每个门店的券有数量限制
    public function branch_ticket_action($storeId,$flag=0,$num=0){
        if($flag==1){
            self::$redis->INCRBY('activity_branch'.$storeId,$num);
        }elseif($flag==2){
            self::$redis->DECRBY('activity_branch'.$storeId,$num);
        }else{
            if($storeId){
                return self::$redis->get('activity_branch'.$storeId);
            }else{
                return 0;
            }
        }
    }

    //释放占用订单
    public function check_activity_order(){
        $pay_aead_time = Db::name('queen_day_config')->where('id', 1)->cache(60)->value('pay_aead_time');
        $list=Db::name('activity_order')->field('id,storeid,insert_time,step')->where('pay_status',0)->select();
        if(count($list) && is_array($list)){
            foreach ($list as $k=>$v){
                $date=time()-$v['insert_time'];
                if($date>$pay_aead_time){
                    $this->branch_ticket_action($v['storeid'],2,$v['step']);
                    Db::name('activity_order')->delete($v['id']);
                }
            }
        }
    }


    /**
     * 支付成功后 给客户发券
     */
    public function sendTicket(){
        $order_sn=input('param.order_sn');
        if($order_sn!=''){
            $map['order_sn']=array('eq',$order_sn);
            $map['pay_status']=array('eq',1);
            $orderInfo = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'], 'member.id=order.uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->field('order.uid,order.step,order.order_sn,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,bwk.title,depart.st_department')->where($map)->find();
            if(count($orderInfo) && is_array($orderInfo)){
				$getTicketType=Db::name('activity_branch')->where('storeid',$orderInfo['storeid'])->field('ticket')->find();
                $getLastDay=date('Y-m-d', mktime(0, 0, 0,date('m')+1,1)-1);//获取当月最后一天的日期
				if($getTicketType && $getTicketType['ticket']==0){
					if($orderInfo['step']==1){
						$check88=Db::name('ticket_user')->where(['mobile'=>$orderInfo['mobile'],'type'=>6])->find();
						if(!$check88){
							insertTicket(6,$orderInfo,$getLastDay,1);//发月度券
						}
						$check99=Db::name('ticket_user')->where(['mobile'=>$orderInfo['mobile'],'type'=>7])->find();
						if(!$check99){
							insertTicket(7,$orderInfo,$getLastDay,1);//发闺蜜券
						}
					}
				}
                insertTicket(5,$orderInfo,$getLastDay,$orderInfo['step']);//发电子抽奖券
                $code = 1;
                $data = '';
                $msg = '发券成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '订单不存在或未支付';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 计划任务 月初发券及将过期券失效
     */
    public function ticketManage(){
        $getFirstDay=date('Y-m-d', mktime(0, 0, 0, date('m'), 1));
        $getLastDay=date('Y-m-d', mktime(0, 0, 0,date('m')+1,1)-1);
        if(date('Y-m-d')==$getFirstDay) {
            if (time() < '1577894400') {//在2020年1月2日之前执行
                Db::startTrans();
                try {
                    //删券
                    $map['aead_time'] = array('lt', strtotime($getFirstDay));
                    $map['status'] = array('eq', 0);
                    Db::name('ticket_user')->where($map)->where('type', 6)->delete();
                    Db::name('ticket_user')->where($map)->where('type', 7)->delete();
//                    Db::name('ticket_user')->where($map)->where('type', 6)->update(['status' => 3, 'draw_pic' => config('queen_day_pic.4')]);
//                    Db::name('ticket_user')->where($map)->where('type', 7)->update(['status' => 3, 'draw_pic' => config('queen_day_pic.6')]);
                    //发券
//                    if (time() < '1577808000') {//在2020年1月1日之前执行
//                        $users = Db::name('activity_order')->alias('order')->join('activity_branch ab', 'order.storeid=ab.storeid', 'left')->join(['ims_bj_shopn_member' => 'member'], 'member.id=order.uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->field('member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,bwk.title,depart.st_department')->where(['order.pay_status'=>1,'order.channel'=>'queenday','ab.ticket'=>0])->group('order.uid')->select();
//                        if (count($users)) {
//                            foreach ($users as $k => $v) {
//                                $getTicketType=Db::name('activity_branch')->where('storeid',$v['storeid'])->field('ticket')->find();
//                                if($getTicketType && $getTicketType['ticket']==0){
//                                    insertTicket(6, $v, $getLastDay, 1);//发月度券
//                                    //insertTicket(7, $v, $getLastDay, 1);//发闺蜜券
//                                }
//                            }
//                        }
//                    }
                    Db::commit();
                    return date('Y年m月') . "女王节奖券已管理完成";
                }catch(\Exception $e){
                    Db::rollback();
                    return date('Y年m月') . "女王节奖券出现错误：" . $e->getMessage();
                }
            }
        }
    }





}