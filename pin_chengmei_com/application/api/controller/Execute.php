<?php

namespace app\api\controller;
use think\Db;
use think\Debug;
use think\Queue;
use weixin\WeixinAccount;
use weixin\WeixinRefund;

/**
 * swagger: 计划任务
 */
class Execute extends Base
{

    //计划任务 关闭失效订单 5分钟执行一次  检测是否有退款短信需要发送信息
    public function closeOrderAndSendMsg(){
        set_time_limit(0);
       //订单有效期检查 订单失效status置为3
       $orderIds=Db::name('tuan_list')->field('id,create_uid,order_sn,end_time-unix_timestamp(now()) surplusTime')->where(['status'=>1,'order_type'=>1])->select();
       if(is_array($orderIds) && count($orderIds)){
           foreach ($orderIds as $kk=>$vv){
               //结束时间一到 将status设置为3
                if($vv['surplusTime']<=0){
                   $update=Db::name('tuan_list')->where('id',$vv['id'])->update(['status'=>3,'close_time'=>time()]);
                   if($update){
                       //将拼团失效的信息发给 美容师 拼团发起人 参团人，短消息及站内信
                           $messageData['type']='1,2';//type 1 为短息 2 为站内信
                           $messageData['role']='2,3,4';//role 角色 1 店老板 2 美容师 3 拼团发起人 4 拼团参与人
                           $messageData['tid']=$vv['id'];//tid 当前拼团id
                           $messageData['scene']=4;//scene 场景 1 拼团发起成功 2 拼团参与成功  3 拼团完成 4 拼团失败通知 5 退款通知  6 团即将失效通知
                           $messageData['joinUid']='';
                           Queue::push( 'app\index\job\Message' , $messageData,'message');

                           //失效订单自动退款开始 若果后台打开自动退款的话
                           $refundSet=config('auto_refund');
                           if($refundSet) {
                               $map['order.parent_order'] = array('eq', $vv['order_sn']);
                               $map['order.pay_status'] = array('eq', 1);
                               $map['order.order_status'] = array('eq', 2);
                               $map['order.pay_price'] = array('gt', 0);
                               $map['log.status'] = 1;
                               $orderList = Db::name('tuan_order')->alias('order')->field('order.order_sn,order.uid,order.transaction_id,order.parent_order,order.pay_by_self,log.pay_amount,log.out_trade_no,log.attach')->join('pay_log log', 'order.transaction_id=log.transaction_id', 'left')->where($map)->select();
                               if (is_array($orderList) && count($orderList)) {
                                   $successNum = 0;
                                   $successRes = [];
                                   $errNum = 0;
                                   $errRes = [];
                                   foreach ($orderList as $k => $v) {
                                       if ($v['pay_amount']) {
                                           $refundData = [
                                               'appid' => config('wx_pay.appid'), //应用id
                                               'mchid' => config('wx_pay.mch_id'), //商户号id
                                               'api_key' => config('wx_pay.api_key'), //支付key
                                               'transaction_id' => $v['transaction_id'], //微信交易号
                                               'out_refund_no' => date('YmdHis') . time() . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9), //退款单号
                                               'total_fee' => floatval($v['pay_amount'] * 100), //原订单金额
                                               'refund_fee' => floatval($v['pay_amount'] * 100), //退款金额
                                               'refund_text' => '拼购未成团退款' //退款描述
                                           ];
                                           $refund = new WeixinRefund($refundData);
                                           $res = $refund->orderRefund();
                                           $resArr = $refund->XmlToArr($res);
                                           if ($resArr['return_code'] == "SUCCESS") {
                                               if ($resArr['result_code'] == "SUCCESS") {
                                                   $successNum++;
                                                   $successRes[] = $resArr;
                                                   //退款成功 修改日志表记录
                                                   $data1 = array('refund_amont' => $resArr['refund_fee'] / 100, 'status' => 2, 'refund_time' => date('Y-m-d H:i:s'), 'refund_id' => $resArr['refund_id'], 'refund_err' => '');
                                                   Db::name('pay_log')->where('transaction_id', $v['transaction_id'])->update($data1);
                                                   //退款成功 修改订单表记录
                                                   Db::name('tuan_order')->where('transaction_id', $v['transaction_id'])->update(['order_status' => 4, 'pay_status' => 2, 'return_time' => time(), 'return_sms_flag' => 1]);
                                                   //退款成功 记录日志
                                                   logs(date('Y-m-d H:i:s') . "自动退款：" . json_encode($resArr), 'refundOk');
                                               } else {
                                                   $errNum++;
                                                   $errRes[] = $resArr;
                                                   //退款失败 修改表记录
                                                   $data1 = array('refund_time' => date('Y-m-d H:i:s'), 'refund_err' => $resArr['err_code_des']);
                                                   Db::name('pay_log')->where('transaction_id', $v['transaction_id'])->update($data1);
                                                   //退款失败 记录日志
                                                   logs(date('Y-m-d H:i:s') . "自动退款：" . json_encode($resArr), 'refundFail');
                                               }
                                           } else {
                                               //退款失败 记录日志
                                               logs(date('Y-m-d H:i:s') . "自动退款：" . json_encode($resArr), 'refundFail');
                                           }
                                       }
                                   }
                                   if (count($orderList) == $successNum) {
                                       //退款成功 修改主订单记录
                                       Db::name('tuan_list')->where('order_sn', $vv['order_sn'])->update(['status' => 4,'refund_time'=>time()]);
                                       logs(date('Y-m-d H:i:s') . "：" . $vv['order_sn'] . "自动退款成功", 'autoRefund');
                                   } else {
                                       logs(date('Y-m-d H:i:s') . "：" . $vv['order_sn'] . "自动退款失败，详见refundFail.txt", 'autoRefund');
                                   }
                               } else {
                                   logs(date('Y-m-d H:i:s') . "：" . $vv['order_sn'] . "自动退款失败，主订单下暂无退款子订单", 'autoRefund');
                               }
                           }
                           //失效订单自动退款结束
                   }
                    $userToken=Db::table('ims_bj_shopn_member')->alias('mem')->where('mem.id',$vv['create_uid'])->join('wx_user u','mem.mobile=u.mobile','left')->value('u.token');
                    //记录访问日志
                    $logsData=parent::getInfoByToken($userToken);
                    if(is_array($logsData)){
                        $logsData['action']=6;
                        $logsData['remark']='订单失效';
                        $logsData['insert_time']=time();
                        $this->logToRedis($logsData);
                    }
                }else{
                    $hour = floor(($vv['surplusTime'] % (3600*24)) / 3600);
                    $hour=floor($hour);
                    $notice=config('order_notice');
                    $noticeArr=explode(',',$notice);
                    if(in_array($hour,$noticeArr)){
                        //检测该时段是否已经通知过
                        $check=Db::name('invalid_notice')->where(['listid'=>$vv['id'],'hour'=>$hour])->count();
                        if(!$check){
                            Db::name('invalid_notice')->insert(['listid'=>$vv['id'],'hour'=>$hour,'insert_time'=>date('Y-m-d H:i:s')]);
                            $messageData['type']='1,2';//type 1 为短息 2 为站内信
                            $messageData['role']='3';//role 角色 1 店老板 2 美容师 3 拼团发起人 4 拼团参与人
                            $messageData['tid']=$vv['id'];//tid 当前拼团id
                            $messageData['scene']=6;//scene 场景 1 拼团发起成功 2 拼团参与成功  3 拼团完成 4 拼团失败通知 5 退款通知  6 团即将失效通知
                            $messageData['joinUid']='';
                            Queue::push( 'app\index\job\Message' , $messageData,'message');
                        }
                    }
                }
           }
       }
        //检测是否有退款短信需要发送 发送成功将return_sms_flag设置为2
        $orderList=Db::name('tuan_order')->field('orderid,tuan_id,uid,flag,pay_by_self')->where('return_sms_flag',1)->select();
        if(is_array($orderList) && count($orderList)){
            foreach ($orderList as $k=>$v){
                Db::name('tuan_order')->where('orderid',$v['orderid'])->update(['return_sms_flag'=>2]);
                if(!$v['pay_by_self']){
                    //将拼退款信息发给 拼团发起人 参团人，短消息及站内信
                        $messageData['type']='1,2';//type 1 为短息 2 为站内信
                        if($v['flag']){
                            $messageData['role']='4';//role 角色 1 店老板 2 美容师 3 拼团发起人 4 拼团参与人
                        }else{
                            $messageData['role']='3';
                        }
                        $messageData['tid']=$v['tuan_id'];//tid 当前拼团id
                        $messageData['scene']=5;//scene 场景 1 拼团发起成功 2 拼团参与成功  3 拼团完成 4 拼团失败通知 5 退款通知  6 团即将失效通知
                        $messageData['joinUid']=$v['uid'];
                        Queue::push( 'app\index\job\Message' , $messageData,'message');
                }
            }
        }
    }


    //定时将前一天的微信对账单下载到本地
    public function accountDownload(){
        $date['appid'] = config('wx_pay.appid'); //应用id
        $date['mchid'] = config('wx_pay.mch_id'); //商户号id
        $date['api_key'] = config('wx_pay.api_key'); //支付key
        $date['bill_date']=date("Ymd",strtotime("-1 day"));
        //$date['bill_date']=input('param.day');
        $refund=new WeixinAccount($date);
        $res = $refund->checkAccount();
        $list=deal_WeChat_response($res);
        $check=Db::name('wx_account')->where('account_date',$date['bill_date'])->count();
        if(!$check) {
                if (is_array($list) && count($list)) {
                    $insertData = [];
                    foreach ($list['bill'] as $k => $v) {
                        $insertData[$k]['account_date'] = $date['bill_date'];
                        $insertData[$k]['pay_time'] = $v['pay_time'];
                        $insertData[$k]['order_sn_wx'] = $v['order_sn_wx'];
                        $insertData[$k]['order_sn_sh'] = $v['order_sn_sh'];
                        $insertData[$k]['pay_type'] = $v['pay_type'];
                        $insertData[$k]['pay_status'] = $v['pay_status'];
                        $insertData[$k]['bank'] = $v['bank'];
                        $insertData[$k]['money_type'] = $v['money_type'];
                        $insertData[$k]['total_amount'] = trim($v['total_amount']);
                        $insertData[$k]['coupon_amount'] = trim($v['coupon_amount']);
                        $insertData[$k]['refund_number_wx'] = $v['refund_number_wx'];
                        $insertData[$k]['refund_number_sh'] = $v['refund_number_sh'];
                        $insertData[$k]['refund_amount'] = trim($v['refund_amount']);
                        $insertData[$k]['coupon_refund_amount'] = trim($v['coupon_refund_amount']);
                        $insertData[$k]['refund_type'] = $v['refund_type'];
                        $insertData[$k]['refund_status'] = $v['refund_status'];
                        $insertData[$k]['goods_name'] = $v['goods_name'];
                        $insertData[$k]['service_charge'] = trim($v['service_charge']);
                        $insertData[$k]['rate'] = $v['rate'];
                        $insertData[$k]['insert_time'] = date('Y-m-d H:i:s');
                    }
                    Db::name('wx_account_info')->insertAll($insertData);
                    $insertData2 = array('account_date' => $date['bill_date'], 'order_num' => $list['summary']['order_num'], 'turnover' => trim($list['summary']['turnover']), 'refund_turnover' => trim($list['summary']['refund_turnover']), 'coupon_turnover' => trim($list['summary']['coupon_turnover']), 'rate_turnover' => trim($list['summary']['rate_turnover']), 'insert_time' => date('Y-m-d H:i:s'));
                    Db::name('wx_account')->insert($insertData2);
                } else {
                    $insertData2 = array('account_date' => $date['bill_date'], 'insert_time' => date('Y-m-d H:i:s'));
                    Db::name('wx_account')->insert($insertData2);
                }
        }
    }

    //微信对账
    public function account_check(){
       $date=date("Ymd",strtotime("-1 day"));
       //$date=input('param.day');
       $account=Db::name('wx_account')->where('account_date',$date)->find();
       if($account['order_num']){
           $map1['account_date']=array('eq',$date);
           $map1['is_check']=array('eq',0);
           $map1['total_amount']=array('neq',58);
           $infoList=Db::name('wx_account_info')->field('id,order_sn_wx,pay_status,total_amount,refund_amount,refund_number_wx')->where($map1)->limit(100)->select();
           if(count($infoList)) {
               foreach ($infoList as $k => $v) {
                   if (trim($v['pay_status']) == 'SUCCESS') {
                       //去支付pay_log表中匹配
                       $logs = Db::name('pay_log')->where('transaction_id', $v['order_sn_wx'])->field('id,pay_amount')->find();
                       if (is_array($logs) && ($logs['pay_amount'] == $v['total_amount'])) {
                           Db::name('wx_account_info')->where('id', $v['id'])->update(['is_check' => 1]);
                           Db::name('pay_log')->where('id', $logs['id'])->update(['pay_check' => 1]);
                       }
                   } elseif (trim($v['pay_status']) == 'REFUND') {
                       //去支付pay_log表中匹配
                       $logs = Db::name('pay_log')->where('refound_id', $v['refund_number_wx'])->field('id,refund_amont')->find();
                       if (is_array($logs) && ($logs['refund_amount'] == $v['refund_amont'])) {
                           Db::name('wx_account_info')->where('id', $v['id'])->update(['is_check' => 1]);
                           Db::name('pay_log')->where('id', $logs['id'])->update(['refund_check' => 1]);
                       }
                   }
               }
           }
       }
    }

    //定时根据经纬度逆解析地址
    public function member_portrait_address(){
        $map['province'] = ['exp', Db::raw('is null')];
        $map['location'] = ['exp', Db::raw('is not null')];
        $map['flag'] = ['eq', 0];
        $list=Db::table('ims_bj_shopn_member_extend')->where($map)->field('id,location')->limit(10)->select();
        if($list){
            foreach ($list as $k=>$v){
                $addressData=getAddress($v['location']);
                if($addressData){
                    unset($addressData['location']);
                    Db::table('ims_bj_shopn_member_extend')->where('id',$v['id'])->update($addressData);
                }else{
                    Db::table('ims_bj_shopn_member_extend')->where('id',$v['id'])->update(['flag'=>1]);
                }
            }
        }
    }


    //分享买送超过24小时回滚
    public function share_rollback(){
        $curTime=time();
        $map['accept_flag']=array('eq',0);
        $map['sharing_flag']=array('eq','2');
        $shareLog=Db::name('activity_order_sharing')->field('id,order_sn,share_pid,insert_time')->where($map)->limit(100)->select();
        if(count($shareLog) && is_array($shareLog)){
            foreach ($shareLog as $k=>$v){
                if(($curTime-$v['insert_time'])>=86400){
                    $res=Db::name('activity_order_sharing')->where(['id' => $v['id']])->delete();
                    if ($res) {
                        Db::name('activity_order_info')->where(['order_sn' => $v['order_sn'],'good_id'=>$v['share_pid']])->update(['is_sharing'=>0]);
//                        如果需要发短信通知赠送人，再次发信息
//                        $getUserId=Db::table('ims_bj_shopn_member')->where('mobile',$v['mobile'])->value('id');
//                        $arr=array('uid'=>$getUserId,'title'=>'奖券分享通知','content'=>"您分享的奖券号为".$v['ticket_code']."的奖券，因超过1小时未被领取，成功退回，请知晓！");
//                        sendDrawQueue($arr);
//                        sendQueue($v['ticket_code'], $v['ticket_code'] . '由美容师' . $v['mobile'] . '分享的奖券，超过1小时未被人领取，状态回滚');
                    }
                }
            }
        }
    }

    /**
     * 阳光普照
     */
    public function draw_sun(){
        set_time_limit(0);
        $end=parent::getCacheString('draw_end_flag66');
        if($end=='end'){
            $num = 1000;// 每次默认取出1000条
            Debug::remark('begin');
            Db::startTrans();
            try {
                for ($i = 0; $i < $num; $i++) {
                    $ticket = parent::getMembers('draw66codeList-1', 1);
                    $map['stock'] = array('gt', 0);
                    $list = Db::name('draw_temp_goods')->field('id,name,stock')->where($map)->select();
                    if($list) {
                        foreach ($list as $key => $val) {
                            $arr[$val['id']] = $val['stock'];
                        }
                        $coupon_id = $this->getRand($arr); //根据概率获取奖品id
                        $draw = Db::name('draw_temp_goods')->find($coupon_id);
                        $data = array('status' => 1, 'flag' => 1, 'update_time' => date('Y-m-d H:i:s'), 'draw_rank' => '女王专属礼', 'draw_name' => $draw['name']);
                        $map0['ticket_code'] = array('eq', $ticket);
                        $res = Db::name('ticket_user')->where($map0)->update($data);
                        if ($res) {
                            Db::name('draw_temp_goods')->where('id', $coupon_id)->setDec('stock');
                        }
                        if ($i % 500 == 0) {
                            Db::commit();
                            Db::startTrans();
                        }
                    }
                }
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
            }
            Debug::remark('end');
            echo Debug::getRangeTime('begin','end').'s';
        }
    }

    //定时获取订单物流信息
   public function get_delivery_info(){
        set_time_limit(0);
        $map['channel'] = ['eq', 'missshop'];
        $map['pay_status'] = ['eq', 1];
        $map['is_axs'] = ['eq', 1];
        $map['express_number'] = ['eq', ''];
        $list=Db::name('activity_order')->alias('o')->join('activity_order_address a','o.order_sn=a.order_sn','left')->where($map)->limit(10)->column('o.order_sn');
        if($list){
            $orders=implode(',',$list);
            $delivery='http://erpapi2.chengmei.com:7779/web/axs.php?orderno='.$orders;
            $delivery_get=json_decode(file_get_contents($delivery),true);
            foreach ($delivery_get['data'] as $k=>$v){
                if(strlen($v['express_number'])){
                    $addressData['express_name'] = trim($v['express_name']);
                    $addressData['express_code'] = trim($v['express_code']);
                    $addressData['express_number'] = trim($v['express_number']);
                    $addressData['update_time'] = date('Y-m-d H:i:s');
                    $res=Db::name('activity_order_address')->where('order_sn',$v['order_sn'])->update($addressData);
                    if($res){
                        $orderInfo=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id','left')->where('o.order_sn',$v['order_sn'])->field('m.mobile,o.fid,o.storeid,m.realname')->find();
                        //给购买人发送短信
                        $arr1 = array('mail_param' => array(), 'sms_param' => array('smsId' => 119, 'param' => array('mobile' => $orderInfo['mobile'], 'order_sn' => $v['order_sn'],'express_name' => $v['express_name'],'express_number' => $v['express_number'])));
                        sendCommQueue($arr1, 2);
                        //给所属美容师发送短信
                        //尊敬的*role*，您的会员*name*在去哪美购买的订单*order_sn*已发货，*express_name*包裹*express_number*。
                        $sellerMobile=Db::table('ims_bj_shopn_member')->where('id',$orderInfo['fid'])->value('mobile');
                        if($sellerMobile) {
                            $arr2 = array('mail_param' => array(), 'sms_param' => array('smsId' => 122, 'param' => array('mobile' => $sellerMobile, 'role' => '美容师', 'name' => $orderInfo['realname'], 'order_sn' => $v['order_sn'], 'express_name' => $v['express_name'], 'express_number' => $v['express_number'])));
                            sendCommQueue($arr2, 2);
                        }
                        //给门店老板发送短信
                        $bossMobile=Db::table('ims_bj_shopn_member')->where(['storeid'=>$orderInfo['storeid'],'isadmin'=>1])->value('mobile');
                        if($bossMobile) {
                            $arr3 = array('mail_param' => array(), 'sms_param' => array('smsId' => 122, 'param' => array('mobile' => $bossMobile, 'role' => '店老板', 'name' => $orderInfo['realname'], 'order_sn' => $v['order_sn'], 'express_name' => $v['express_name'], 'express_number' => $v['express_number'])));
                            sendCommQueue($arr3, 2);
                        }
                    }
                }
            }
        }
    }
	
	    //门店开通安心送产品
    public function open_axs(){
        $storeid=input('param.storeid');
        if(!$storeid){
            return json(['code' => 0, 'data' => '', 'msg' => '门店不允许为空']);
        }
        try {
            //读取活动列表
            $map['poster_cate']=array('eq',1);
            $map['id']=array('in',['11','12']);
            $activity_list =Db::name('activity_list')->field('id,name')->where($map)->order('activity_orders')->select();
            foreach ($activity_list as $k=>$v){
                $goods_list=Db::name('goods')->where(['activity_id'=>$v['id']])->where('status',1)->column('id');
                if($goods_list){
                    $activity_list[$k]['goods']=$goods_list;
                }else{
                    unset($activity_list[$k]);
                }
            }
            Db::name('axs_branch')->where(['store_id'=>$storeid])->delete();
            if($activity_list){
                $insertData=[];
                foreach ($activity_list as $k=>$v){
                    foreach ($v['goods'] as $kk=>$vv){
                        $insertData[]=['store_id'=>$storeid,'activity_id'=>$v['id'],'goods_id'=>$vv,'insert_time'=>time()];
                    }
                }
                Db::name('axs_branch')->insertAll($insertData);
            }
                return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }catch (\Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' => '配置失败'.$e->getMessage()]);
        }
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


    /*
         * 定时发送订单数据
         */
    public function sendOrdersByEmail(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $date = input('param.date',date("Y-m-d", strtotime("-1 day")));
        $map = [];
        $map['order.pay_status'] = ['eq',1];
        $map['order.pay_time'] = array('between', [strtotime($date . " 00:00:00"), strtotime($date . " 23:59:59")]);
        $map['order.channel'] = ['eq','missshop'];
        $lists = Db::name('activity_order')->alias('order')->join('activity_order_info info','order.order_sn=info.order_sn','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->join('order_lucky lucky','order.order_sn=lucky.order_sn','left')->field('order.*,order.flag order_flag,info.good_id,info.good_num,info.good_specs,info.pick_up,info.good_amount,info.main_flag,info.flag info_flag,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,lucky.flag,lucky.lucky_name,lucky.insert_time lucky_time1,lucky.update_time lucky_time2,info.good_specs_sku,bwk.receive_address,bwk.receive_consignee,bwk.receive_mobile,depart.st_department')->where($map)->order('order.id')->select();
        foreach ($lists as $k=>$v){
            $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$v['fid'])->field('mobile seller_tel,realname seller_name')->find();
            $lists[$k]['order_price']=$v['pay_price']?$v['pay_price']:0;
            $lists[$k]['sellername']=$sellerInfo['seller_name'];
            $lists[$k]['sellermobile']=$sellerInfo['seller_tel'];
            $lists[$k]['bsc_name']=$v['st_department'];
            $lists[$k]['cus_sign']=$v['sign'];
            $lists[$k]['cus_title']=$v['title'];
            $lists[$k]['st_department']=$v['st_department'];
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
            $lists[$k]['scene']=config("activity_list.".$v['scene']);
            if($v['order_flag']){
                $goods=[];
                $orderInfo=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where('info.order_sn',$v['order_sn'])->field('g.name,info.good_specs,info.good_amount,info.flag,info.good_num')->select();
                foreach ($orderInfo as $kk=>$vv){
                    $f=$vv['flag']?'赠送：':'';
                    $goods[]=($kk+1).'.'.$f.$vv['name'].$vv['good_specs'].' ×'.$vv['good_num'];
                }
                $lists[$k]['name']=implode('<br/>',$goods);
                $lists[$k]['goods_code']='';
            }else{
                $getInfo=Db::name('goods')->where('id',$v['pid'])->field('name,goods_code')->find();
                $lists[$k]['name']=$getInfo['name'].' '.$v['specs'];
                $lists[$k]['goods_code']=$getInfo['goods_code'];
            }
            $lists[$k]['promoter_tips']='';
            if($v['scene']==5){
                if(strstr($v['remark'],'该单有推广积分')){
                    $lists[$k]['promoter_flag']=1;
                }else{
                    $lists[$k]['promoter_flag']=1;
                }
                $lists[$k]['promoter_tips']=$v['remark'];
            }
//            if(!session('get_mobile')){
//                $lists[$k]['mobile']=substr_replace($v['mobile'], '****', 3, 4);
//                $lists[$k]['sellermobile']=substr_replace($sellerInfo['seller_tel'], '****', 3, 4);
//            }
        }
        //导出
        if($lists){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['id']=$v['id'];
                $data[$k]['st_department']=$v['bsc_name'];
                $data[$k]['title']=$v['cus_title'];
                $data[$k]['sign']=$v['cus_sign'];
                $data[$k]['sellername']=$v['sellername'];
                $data[$k]['sellermobile']=$v['sellermobile'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['activity_flag']=$v['activity_flag'];
                $data[$k]['order_sn']=' '.$v['order_sn'];
                $data[$k]['pay_status']=$v['pay_status']?'已支付':'未支付';
                if($v['is_axs']){
                    $data[$k]['pick_type']='安心直邮';
                }else{
                    $data[$k]['pick_type']=$v['pick_type']?'到店取货':'现场取货';
                }
                $data[$k]['scene']=$v['scene'];
                if(!$v['order_flag']) {
                    $data[$k]['name'] = ' ' . $v['name'];
                    $data[$k]['belong'] ='';
                    $data[$k]['num'] = $v['num'];
                    $data[$k]['pay_price'] = $v['pay_price'];
                    $data[$k]['coupon_dsc']='';
                    $data[$k]['specs']=$v['goods_code'];
                    $data[$k]['order_status']=$v['pick_up']?'已取货':'未取货';
                }else{
                    $n=Db::name('goods')->where('id',$v['good_id'])->field('name,storeid,goods_code')->find();
                    if($n['storeid']){
                        $belongBranch=Db::table('ims_bwk_branch')->where('id',$n['storeid'])->value('title');
                    }else{
                        $belongBranch='';
                    }
                    $data[$k]['name'] = $v['info_flag']?'买赠：'.$n['name'].$v['good_specs']:$n['name'].$v['good_specs'];
                    if($v['info_flag']){
                        $data[$k]['belong'] = $n['storeid']?$belongBranch:'诚美总部';
                    }else{
                        $data[$k]['belong'] = '';
                    }
                    $data[$k]['num'] = $v['good_num'];

                    $data[$k]['pay_price'] = $v['info_flag']?0:$v['good_amount'];
                    $data[$k]['coupon_dsc']='';
                    $data[$k]['specs']=$v['good_specs_sku']?$v['good_specs_sku']:$n['goods_code'];

                    if($v['main_flag']){
                        if($v['coupon_price']) {
                            $data[$k]['pay_price'] = $v['good_amount'] - $v['coupon_price'];
                            $data[$k]['coupon_dsc'] = $v['coupon_price'] ? '该金额扣减抵用' . $v['coupon_price'] . '现金券一张' : '11';
                        }
                    }

                    $data[$k]['order_status']=$v['order_status']?'已取货':'未取货';
                }
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['pay_time']=$v['pay_time'];
                $data[$k]['transaction_id']='`'.$v['transaction_id'];
                $data[$k]['lucky_name']='';
                $data[$k]['flag']='';
                $data[$k]['lucky_time1']='';
                $data[$k]['lucky_time2']='';
                if($v['lucky_name']){
                    if(!$v['order_flag']) {
                        $data[$k]['lucky_name'] = $v['lucky_name'];
                        $data[$k]['flag'] = $v['flag'] ? '已领取' : '未领取';
                        $data[$k]['lucky_time1'] = date('Y-m-d H:i:s', $v['lucky_time1']);
                        $data[$k]['lucky_time2'] = $v['lucky_time2'] ? date('Y-m-d H:i:s', $v['lucky_time2']) : '';
                    }else{
                        if($v['main_flag']){
                            $data[$k]['lucky_name'] = $v['lucky_name'];
                            $data[$k]['flag'] = $v['flag'] ? '已领取' : '未领取';
                            $data[$k]['lucky_time1'] = date('Y-m-d H:i:s', $v['lucky_time1']);
                            $data[$k]['lucky_time2'] = $v['lucky_time2'] ? date('Y-m-d H:i:s', $v['lucky_time2']) : '';
                        }
                    }
                }
                //88福袋需要导入推广人姓名和电话
                $data[$k]['share_realname']='';
                $data[$k]['share_mobile']='';
                if($v['scene']==5){
                    if(strlen($v['share_uid'])){
                        $shareInfo=Db::table('ims_bj_shopn_member')->where('id',$v['share_uid'])->field('realname,mobile')->find();
                        if($shareInfo) {
                            $data[$k]['share_realname'] = $shareInfo['realname'];
                            $data[$k]['share_mobile'] = $shareInfo['mobile'];
                        }
                    }
                }
                //获取收货信息
                $data[$k]['d_consignee']='';
                $data[$k]['d_mobile']='';
                $data[$k]['d_address']='';
                if($v['is_axs']){
                    $delivery=Db::name('activity_order_address')->where('order_sn',trim($v['order_sn']))->find();
                    $data[$k]['d_consignee']=$delivery['consignee'];
                    $data[$k]['d_mobile']=$delivery['mobile'];
                    $data[$k]['d_address']=$this->getNameByParentId($delivery['province']).$this->getNameByParentId($delivery['city']).$this->getNameByParentId($delivery['district']).$this->getNameByParentId($delivery['street']).$delivery['address'];
                }else{
                    $data[$k]['d_consignee']=$v['receive_consignee'];
                    $data[$k]['d_mobile']=$v['receive_mobile'];
                    $data[$k]['d_address']=$v['receive_address'];
                }
                $goodId=$v['good_id']?$v['good_id']:$v['pid'];
                $data[$k]['goods_compose']=Db::name('goods')->where('id',$goodId)->value('is_compose');
                $data[$k]['info_flag']=$v['info_flag']?$v['info_flag']:0;
                $data[$k]['good_id']=$v['good_id']?$v['good_id']:$v['pid'];
            }
            //处理组合产品拆分
            $res=[];
            foreach ($data as $key=>$val){
                if($val['goods_compose'] && $val['info_flag']==0){
                    $compose=Db::name('compose')->where(['pid'=>$val['good_id'],'status'=>1])->value('cids');
                    if($compose){
                        $composeArr=explode(',',$compose);
                        foreach ($composeArr as $kk=>$vv){
                            $sonGoods=Db::name('goods')->where('id',$vv)->field('name,activity_price,goods_code')->find();
                            $val['name']=$sonGoods['name'];
                            $val['pay_price']=$sonGoods['activity_price'];
                            $val['specs']=$sonGoods['goods_code'];
                            unset($val['goods_compose'],$val['info_flag'],$val['good_id']);
                            $res[]=$val;
                        }
                    }
                }else{
                    unset($val['goods_compose'],$val['info_flag'],$val['good_id']);
                    $res[]=$val;
                }
            }

            $filename = date('YmdHis');
            $header = array ('订单Id','办事处','门店名称','门店编码','美容师名称','美容师电话','顾客姓名','顾客电话','顾客标识码','活动订单号','支付状态','取货方式','订单类型','购买产品','产品提供','购买数量','订单金额','抵扣信息','规格型号','取货状态','订单创建时间','订单支付时间','支付流水号','中奖奖品名称','奖品是否领取','奖品中奖时间','奖品领取时间','订单推广人','推广人电话','收货人','收货手机','收货地址');
            $widths=array('10','10','30','20','15','15','15','15','15','50','30','30','30','30','30','30','30','30','30','30','30','50','50','20','30','30','30','30','30','30','30','100');
            $path=ROOT_PATH.'/public/ExcelReport/'.$filename.'.xlsx';
            $fname= iconv("UTF-8", "GB2312//IGNORE", @$path);
            if($res) {
                comm_excelExport($filename, $header, $res, $widths,1);//生成数据
                $fu=array($fname);
                send_mail('xuyuanyuan@chengmei.com,451035207@qq.com','去哪美啊顾客订单',$date.'去哪美啊平台订单数据',$date.'去哪美啊平台订单数据，请查看附件',$fu,1);
            }
        }
    }

    public function getNameByParentId($id){
        return Db::table('sys_region')->where(['id'=>$id])->value('name');
    }

}