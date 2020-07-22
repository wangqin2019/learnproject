<?php

namespace app\api\controller;
use app\api\model\TicketUserModel;
use think\Controller;
use think\Db;
use think\Log;
use think\Queue;
use weixin\WeixinPay;

/**
 * swagger: 支付
 */
class Pay extends Base
{
    //微信预支付
    public function wxPay(){
        $appid=config('wx_pay.appid');
        $mch_id=config('wx_pay.mch_id');
        $key=config('wx_pay.api_key');
        //获取前台参数
        $token=input('param.token');
        $buyUser = Db::name('wx_user')->where('token', $token)->find();
        $openid=$buyUser['open_id'];
        $body=input('param.body');
		$user_id=input('param.user_id');//用户id
		$out_trade_no = $mch_id. time().$user_id;
        $attach = input('param.attach');
        $total = input('param.total_fee');
        $total_fee = floatval($total*100);//价格转化为分x100
        $order_sn=input('param.order_sn');//订单号
        $buyType=input('param.buy_type',0);//0是正常购买 1 是凑单支付 2 是活动支付
        $mobile=$buyUser['mobile'];//用户手机
        try {
            if($buyType==1){
                Db::name('tuan_order')->where('order_sn','in' ,$attach)->update(['pay_flag' => 1, 'pay_flag_time' => time(),'uid'=>$user_id,'pay_by_self'=>1]);
            }
            if($buyType==2){
                $order_begin_time = Db::name('pk_order')->where('order_sn', $order_sn)->value('insert_time');
                $pay_end_time=time()+86400;
                $weixinpay = new WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee, $attach, date('YmdHis', $order_begin_time), date('YmdHis', $pay_end_time));
                $return['order_sn'] = $order_sn;
            }else{
                $pay_end_time=time()+7200;
                $weixinpay = new WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee, $attach,date('YmdHis',time()),date('YmdHis',$pay_end_time));
            }
            $return = $weixinpay->pay();
            //记录欲支付请求
            logs(date('Y-m-d H:i:s')."：".json_encode($return),'prepay');
            $prepay_id=substr($return['package'],10);
            // 记录支付日志
            $data = array('user_id'=>$user_id,'order_sn'=>$order_sn,'mobile'=>$mobile,'out_trade_no'=>$out_trade_no,'status'=>0,'attach'=>$attach,'pay_amount'=>$total,'prepay_id'=>$prepay_id,'log_time'=>date('Y-m-d H:i:s'));
            Db::name('pay_log')->insert($data);

            $code = 1;
            $data = $return;
            $msg = '支付参数获取成功';
        }catch (\Exception $e){
            $code = 0;
            $data = '';
            $msg = '支付参数获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 支付回调
     * @return bool
     */
    function notify(){
        $result = false;
        $postXml = $GLOBALS["HTTP_RAW_POST_DATA"]; //接收微信参数
        if (empty($postXml)) {
            return false;
        }
        $attr = xmlToArray($postXml);
        if ($attr['result_code'] != 'SUCCESS' || $attr['return_code'] != 'SUCCESS') {
            //记录支付失败返回
            logs(date('Y-m-d H:i:s')."：".json_encode($attr),'payFail');
            $result = false;
        }else {
            $total_fee = number_format($attr['total_fee']/100,2,'.','');
            $out_trade_no = $attr['out_trade_no'];
            $attach = $attr['attach'];
            if($attach=='activity'){
                //记录支付成功返回
                logs(date('Y-m-d H:i:s') . "：" . json_encode($attr), 'activityPayOk');
                $logInfo = Db::name('pay_log')->field('id,mobile,status,order_sn,prepay_id')->where(['out_trade_no' => $out_trade_no])->limit(1)->order('log_time desc')->find();
                if ($logInfo) {
                    $payTime = time();
                    $data1 = array('transaction_id' => $attr['transaction_id'], 'status' => 1, 'upd_time' => date('Y-m-d H:i:s', $payTime));
                    Db::name('pay_log')->where('id', $logInfo['id'])->update($data1);
                    $data6 = array('transaction_id' => $attr['transaction_id'],'out_trade_no'=>$out_trade_no,'pay_status' => 1, 'pay_price' => $total_fee, 'pay_time' => time());
                    Db::name('pk_order')->where('order_sn', $logInfo['order_sn'])->update($data6);
                    $result = true;
                    sendMessage($logInfo['mobile'],[],104);
                }
            }elseif($attach=='missshop'){
                //记录支付成功返回
                logs(date('Y-m-d H:i:s') . "：" . json_encode($attr), 'missshopPayOk');
                $logInfo = Db::name('pay_log')->field('id,mobile,status,order_sn,prepay_id')->where(['out_trade_no' => $out_trade_no])->limit(1)->order('log_time desc')->find();
                if ($logInfo) {
                    $payTime = time();
                    $data1 = array('transaction_id' => $attr['transaction_id'], 'status' => 1, 'upd_time' => date('Y-m-d H:i:s', $payTime));
                    Db::name('pay_log')->where('id', $logInfo['id'])->update($data1);
                    $data6 = array('transaction_id' => $attr['transaction_id'],'out_trade_no'=>$out_trade_no,'pay_status' => 1, 'pay_price' => $total_fee, 'pay_time' => time());
                    Db::name('activity_order')->where('order_sn', $logInfo['order_sn'])->update($data6);
                    $this->buyEndAction($logInfo['order_sn']);//处理积分及库存事项
                    $result = true;
                    //sendMessage($logInfo['mobile'],[],104);
                }
            }
//            elseif($attach == 'bargain'){//砍价活动
                //记录支付成功返回
//                logs(date('Y-m-d H:i:s') . "：" . json_encode($attr), 'bargainPayOk');
//                $logInfo = Db::name('pay_log')
//                    ->field('id,mobile,status,order_sn,prepay_id')
//                    ->where(['out_trade_no' => $out_trade_no])
//                    ->limit(1)
//                    ->order('log_time desc')
//                    ->find();
//                if ($logInfo) {
//                    //更新支付记录
//                    $payTime = time();
//                    $data1 = array(
//                        'transaction_id' => $attr['transaction_id'],
//                        'status' => 1,
//                        'upd_time' => date('Y-m-d H:i:s', $payTime)
//                    );
//                    Db::name('pay_log')->where('id', $logInfo['id'])->update($data1);
//                    //修改砍价订单记录
//                    $data6 = array(
//                        'transaction_id' => $attr['transaction_id'],
//                        'out_trade_no'=>$out_trade_no,
//                        'pay_status' => 1,
//                        'status' => 2,
//                        'pay_price' => $total_fee,
//                        'pay_time' => time()
//                    );
//                    Db::name('bargain_order')->where('order_sn', $logInfo['order_sn'])->update($data6);
//                    $result = true;
//                    //sendMessage($logInfo['mobile'],[],104);
//                }
//            }else {
                //记录支付成功返回
//                logs(date('Y-m-d H:i:s')."：".json_encode($attr),'payOk');
//                $attachArr = explode(',', $attach);//将attach转成数组
//                // 支付成功,修改订单状态和时间
//                $logInfo = Db::name('pay_log')->field('id,mobile,status,order_sn,prepay_id')->where(['out_trade_no' => $out_trade_no])->limit(1)->order('log_time desc')->find();
//                if ($logInfo) {
//                    if ($logInfo['status'] == 0) {
//                        //记录访问日志
//                        $userToken = Db::name('wx_user')->where('mobile', $logInfo['mobile'])->value('token');
//                        $logsData = parent::getInfoByToken($userToken);
//                        if (is_array($logsData)) {
//                            $logsData['action'] = 4;
//                            $logsData['remark'] = '完成了货品支付';
//                            $logsData['insert_time'] = time();
//                            $this->logToRedis($logsData);
//                        }
//                        $result = $attr;
//                        // 修改日志表记录
//                        $payTime = time();
//                        $data1 = array('transaction_id' => $attr['transaction_id'], 'status' => 1, 'upd_time' => date('Y-m-d H:i:s', $payTime));
//                        Db::name('pay_log')->where('id', $logInfo['id'])->update($data1);
//                        //获取当前订单的开始时间
//                        $beginTime = Db::name('tuan_order')->where('order_sn', $logInfo['order_sn'])->value('insert_time');
//                        // 修改订单表记录
//                        $data2 = array('order_status' => 2, 'pay_status' => 1, 'transaction_id' => $attr['transaction_id'], 'pay_time' => $payTime, 'pay_flag' => 2, 'pay_flag_time' => $payTime, 'process_time' => $payTime - $beginTime);
//                        $map['order_sn'] = array('in', $attachArr);
//                        Db::name('tuan_order')->where($map)->update($data2);
//
//                        //如果支付的是凑单订单 将取一单改变支付金额 另外1单金额改为为0
//                        foreach ($attachArr as $kk => $vv) {
//                            $getPayType = Db::name('tuan_order')->where(['order_sn' => $vv])->value('pay_by_self');
//                            if ($getPayType) {
//                                if ($kk == 0) {
//                                    Db::name('tuan_order')->where(['order_sn' => $vv])->update(['pay_price' => $total_fee]);
//                                } else {
//                                    Db::name('tuan_order')->where(['order_sn' => $vv])->update(['pay_price' => 0]);
//                                }
//                            }
//                        }
//                        // 获取主订单号
//                        $getMainOrderSn = Db::name('tuan_order')->where('order_sn', $logInfo['order_sn'])->limit(1)->value('parent_order');
//                        // 修改主订单状态
//                        $checkOrderStatus = Db::name('tuan_order')->where(['parent_order' => $getMainOrderSn, 'order_status' => 1, 'pay_status' => 0])->count();
//                        //如果查不到了未支付状态的了 把主订单状态修改一下
//                        if (!$checkOrderStatus) {
//                            //获取当前单所属门店
//                            $getOrder = Db::name('tuan_list')->field('id,share_uid,storeid,begin_time,order_type')->where('order_sn', $getMainOrderSn)->find();
//                            $diffTime = $payTime - $getOrder['begin_time'];
//                            Db::name('tuan_list')->where('order_sn', $getMainOrderSn)->update(['status' => 2, 'success_time' => $payTime, 'process_time' => $diffTime]);
//                            if ($getOrder['order_type'] == 1) {
//                                //获取拉取新人美容师所得积分
//                                $new_customer_reward = config('new_customer_reward');
//                                //获取当前拼团商品成团后美容师奖励积分
//                                $getScore = Db::name('goods')->alias('g')->join('tuan_list list', 'g.id=list.pid', 'left')->where('list.order_sn', $getMainOrderSn)->value('g.score');
//                                //给美容师增加积分,先获取当前单有几个新顾客 新顾客判断标注：member表id_regsource为7 且只支付过1单
//                                $orderList = Db::name('tuan_order')->field('uid')->where(['parent_order' => $getMainOrderSn, 'flag' => 1])->select();
//                                $num = 0;
//                                $memId = [];
//                                foreach ($orderList as $k => $v) {
//                                    $map1['member.id_regsource'] = array('eq', 7);
//                                    //$map1['order.order_status']=array('in','2,3');
//                                    $map1['order.pay_status'] = array('eq', 1);
//                                    $map1['order.uid'] = array('eq', $v['uid']);
//                                    $orderCount = Db::name('tuan_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'], 'order.uid=member.id', 'left')->where($map1)->count();
//                                    if ($orderCount == 1) {
//                                        $memId[] = $v['uid'];
//                                        $num++;
//                                    }
//                                }
//                                $have = Db::name('seller_score')->where('order_sn', $getMainOrderSn)->count();
//                                if (!$have) {
//                                    $scoreTotal = ($num * $new_customer_reward) + $getScore;//新人积分+拼购产品积分
//                                    $scoreData = array('sellerid' => $getOrder['share_uid'], 'storeid' => $getOrder['storeid'], 'order_sn' => $getMainOrderSn, 'memberid' => implode(',', $memId), 'score' => $scoreTotal, 'insert_time' => time());
//                                    //Db::name('seller_score')->insert($scoreData);
//                                }
//                                //将成团信息发给店老板 美容师 拼团发起人 参团人，短消息及站内信
//                                $messageData['type'] = '1,2';//$type 1 为短息 2 为站内信
//                                $messageData['role'] = '1,2,3,4';//$role 角色 1 店老板 2 美容师 3 拼团发起人 4 拼团参与人
//                                $messageData['tid'] = $getOrder['id'];//$tid 当前拼团id
//                                $messageData['scene'] = 3;//1 拼团发起成功 2 拼团参与成功  3 拼团完成 4 拼团失败退款通知
//                                $messageData['joinUid'] = '';
//                                Queue::push('app\index\job\Message', $messageData, 'message');
//                            } else {
//                                //获取当前拼团商品成团后美容师奖励积分
//                                $getScore = Db::name('goods')->alias('g')->join('tuan_list list', 'g.id=list.pid', 'left')->where('list.order_sn', $getMainOrderSn)->value('g.score');
//                                $have = Db::name('seller_score')->where('order_sn', $getMainOrderSn)->count();
//                                if (!$have) {
//                                    $scoreTotal = $getScore;//新人积分+拼购产品积分
//                                    $scoreData = array('sellerid' => $getOrder['share_uid'], 'storeid' => $getOrder['storeid'], 'order_sn' => $getMainOrderSn, 'memberid' => '', 'score' => $scoreTotal, 'insert_time' => time());
//                                    //Db::name('seller_score')->insert($scoreData);
//                                }
//                            }
//                        } else {
//                            //判断该单是拼团发起还是参与
//                            $orderType = Db::name('tuan_order')->field('flag,tuan_id,uid')->where('order_sn', $logInfo['order_sn'])->find();
//                            if ($orderType['flag']) {
//                                //参团支付 发送信息给参团人和拼单发起人
//                                $messageData1['type'] = '1,2';//$type 1 为短息 2 为站内信
//                                $messageData1['role'] = '3,4';//$role 角色 1 店老板 2 美容师 3 拼团发起人 4 拼团参与人
//                                $messageData1['tid'] = $orderType['tuan_id'];//$tid 当前拼团id
//                                $messageData1['scene'] = 2;//1 拼团发起成功 2 拼团参与成功  3 拼团完成 4 拼团失败退款通知
//                                $messageData1['joinUid'] = $orderType['uid'];
//                                Queue::push('app\index\job\Message', $messageData1, 'message');
//                            } else {
//                                //拼单发起 发送给拼团发起人及美容师
//                                $messageData2['type'] = '1,2';//$type 1 为短息 2 为站内信
//                                $messageData2['role'] = '2,3';//$role 角色 1 店老板 2 美容师 3 拼团发起人 4 拼团参与人
//                                $messageData2['tid'] = $orderType['tuan_id'];//$tid 当前拼团id
//                                $messageData2['scene'] = 1;//1 拼团发起成功 2 拼团参与成功  3 拼团完成 4 拼团失败退款通知
//                                $messageData2['joinUid'] = '';
//                                Queue::push('app\index\job\Message', $messageData2, 'message');
//                            }
//                        }
////                    $data['keyword1'] = array('value'=>$logInfo['order_sn'],'color'=>'#000');
////                    $data['keyword2'] = array('value'=>date('Y-m-d H:i'),'color'=>'#000');
////                    $data['keyword3'] = array('value'=>$body,'color'=>'#000');
////                    $data['keyword4'] = array('value'=>$total_fee,'color'=>'#000');
//                    send_weapp_msg($open_id,'3XH3Iu9GlVDOXOclMhRJRrTzDBU8pydaV7bvc6IIVXY','',$logInfo['prepay_id'],$data,'keyword4.DATA');
////
//                    } else {
//                        $result = false;
//                    }
//                }
//            }
        }
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
        return $result;
        // echo $this->returnMsg($this->code,$this->data,$this->msg);
    }


    /*
     * missshop下单后事项处理
     */
    public function buyEndAction($ordersn){
        $orderInfo=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'member'],'o.uid=member.id','left')->join('goods g','o.pid=g.id','left')->field('o.id,o.storeid,o.uid,o.fid,o.share_uid,o.num,o.pid,o.coupon_id,o.pay_price,o.coupon_price,o.order_status,o.flag,o.scene,o.sku,g.id gid,g.stock,g.score,g.score_object,g.score_double,g.activity_id,g.type,g.ticket_id,g.use_num,g.buy_type,member.activity_flag,member.pid mpid,g.activity_price')->where('order_sn',$ordersn)->find();
        if($orderInfo){
            $count=Db::table('think_scores_record')->where('remark',$ordersn)->count();
            if(!$count) {
                //如果是同享单 需生成同享券，同享产品需要去卡券中使用
                if ($orderInfo['flag']) {
                    //获取该订单下的所有产品，减库存
                    $buyGoods = Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where(['info.order_sn' => $ordersn])->field('info.good_id,info.good_num,g.type,g.ticket_id,g.use_num,g.activity_id,g.buy_type,info.main_flag,good_specs_sku')->select();
                    if ($buyGoods) {
                        $getGoodType=0;
                        foreach ($buyGoods as $k => $v) {
                            //记录购买
                            parent::hincrby('missshop', $orderInfo['uid'] . '_' . $v['good_id'], 1);
                            //Db::name('goods')->where('id', $v['good_id'])->setDec('stock', $v['good_num']);
                            if($orderInfo['flag']==2){
                                $getGoodType=11;
//                                parent::setDec('ms_good' . $v['good_id'].$v['good_specs_sku'], $v['good_num']);
//                                parent::hincrby('activity_stock', 'goods_' . $v['good_id'], -$v['good_num']);
//                                Db::name('goods_specs_info')->where(['goods_id'=>$v['good_id'],'sku'=>$v['good_specs_sku']])->setDec('store_count',$v['good_num']);
                            }elseif($orderInfo['flag']==4 || $orderInfo['flag']==3 || $orderInfo['flag']==5){
                                if($v['main_flag']){
                                    $getGoodType=$v['activity_id'];
                                }
//                                parent::hincrby('sku_stock', $v['good_specs_sku'], -$v['good_num']);
//                                $chcek=Db::name('goods_specs_info')->where(['goods_id'=>$v['good_id'],'sku'=>$v['good_specs_sku']])->count();
//                                if($chcek) {
//                                    Db::name('goods_specs_info')->where(['goods_id' => $v['good_id'], 'sku' => $v['good_specs_sku']])->setDec('store_count', $v['good_num']);
//                                }
                            }else{
//                                parent::hincrby('activity_stock', 'goods_' . $v['good_id'], -$v['good_num']);
                                //记录主单产品类型，根据类型奖励积分
                                if($v['main_flag']){
                                    $getGoodType=$v['activity_id'];
                                }
                                //如果该主单产品是同享产品，需要生成同享券
                                if(strpos($v['buy_type'],'3') !==false){
                                    $this->send_share_ticket($ordersn,$orderInfo['uid'],$v['ticket_id'],($v['good_num']*$v['use_num']));
                                }
                            }
                        }
                    }
                } else {
                    if($orderInfo['sku']){
                        //记录购买 //减库存
                        parent::hincrby('missshop', $orderInfo['uid'] . '_' . $orderInfo['pid'], $orderInfo['num']);
//                        parent::setDec('ms_good' . $orderInfo['pid'].$orderInfo['sku'], $orderInfo['num']);
//                        Db::name('goods')->where('id', $orderInfo['pid'])->setDec('stock', $orderInfo['num']);
//                        Db::name('goods_specs_info')->where(['goods_id'=>$orderInfo['pid'],'sku'=>$orderInfo['sku']])->setDec('store_count', $orderInfo['num']);
                    }else {
                        //获取该订单下的所有产品，减库存
                        //记录购买
                        parent::hincrby('missshop', $orderInfo['uid'] . '_' . $orderInfo['pid'], 1);
//                        parent::setDec('ms_good' . $orderInfo['pid'], $orderInfo['num']);
//                        Db::name('goods')->where('id', $orderInfo['pid'])->setDec('stock', $orderInfo['num']);
                    }
                    //记录主单产品类型，根据类型奖励积分
                    $getGoodType = $orderInfo['activity_id'];
                }

                //增加积分
                //1.2拓客 按金额计等比积分  5代餐 小于9盒送等比积分 超出无积分 （13宏伟代餐 11抗议 12 春天 16 17直播产品 无积分） 走if逻辑  其他金额的百分之十积分
                if ($getGoodType==1 || $getGoodType==2 || $getGoodType==5 || $getGoodType==13 || $getGoodType==11 || $getGoodType==12 || $getGoodType==16 || $getGoodType==17 || $getGoodType==20) {
                    $giveScore=true;
                    //代餐如果超过9盒，不赠送积分
                    if($getGoodType==5){
                        if($orderInfo['pid']==135) {
                            $getCount = parent::hashGet('missshop', $orderInfo['uid'] . '_135');
                            if ($getCount > 9) {
                                $giveScore = false;
                            }
                        }
                    }
                    //宏伟定制3380产品不赠送积分
                    if($getGoodType==13 && $orderInfo['pid']==174){
                        $giveScore = false;
                    }
                    //新年疫情关怀活动/约惠春天活动/直播无积分
                     if($getGoodType==11 || $getGoodType==12 || $getGoodType==16 || $getGoodType==17 || $getGoodType==20){
                        $giveScore = false;
                    }

                    if($giveScore){
                        //如果是现场收货，不冻结积分 其他只有美容师核销了订单才解冻积分
                        //$unable= $orderInfo['order_status']?1:0;
                        $unable= 1;
                        //增加美容师积分
                        $score = $orderInfo['num'] * $orderInfo['score'];
                        $scoreData = array('user_id' => $orderInfo['fid'], 'remark' => $ordersn, 'scores' => intval($score), 'type' => 'missshop', 'msg' => '下属uid' . $orderInfo['uid'] . '用户下单，奖励美容师' . $score . '分','usable'=>$unable, 'log_time' => date('Y-m-d H:i:s', time()));
                        Db::table('think_scores_record')->insert($scoreData);
                    }
                }else{
                    //增加顾客积分 2019-11-13修改不限制积分 美容师和店老板买也会有积分
//                    if ($orderInfo['uid'] != $orderInfo['fid']) {
                        $score = $orderInfo['pay_price'] * 0.1;
                        $scoreData = array('user_id' => $orderInfo['fid'], 'remark' => $ordersn, 'scores' => floor($score), 'type' => 'missshop_transfer', 'msg' => '用户uid' . $orderInfo['uid'] . '下单，奖励' . $score . '分','usable'=>1, 'log_time' => date('Y-m-d H:i:s', time()));
                        Db::table('think_scores_record')->insert($scoreData);
//                    }
                }

                //将使用的现金券设置为已使用
                if($orderInfo['coupon_id']){
                    parent::saddset('cash_code',$orderInfo['coupon_id']);
                    $ticket_pic = config("transfer_ticket.cash_".$orderInfo['coupon_price']."_2");
                    Db::name('ticket_user')->where('id',$orderInfo['coupon_id'])->update(['status'=>2,'draw_pic'=>$ticket_pic,'update_time' => date('Y-m-d H:i:s')]);
                }

                if($orderInfo['scene'] ==0 || $orderInfo['scene'] == 1) {
                    //如果购买人是8808新客 且是第一次购买 且是顾客推荐的(不能是美容师或者老板)
                    if ($orderInfo['activity_flag'] == '8808') {
                        $this->card_upgrade($orderInfo['uid'], $orderInfo['mpid'], $ordersn);
                    }
                    //第一次购买 发送皮肤检测券 2019-11-21王玮老师说停止发送
//                    $map2['uid']=array('eq',$orderInfo['uid']);
//                    $map2['pay_status']=array('eq',1);
//                    $map2['channel']=array('eq','missshop');
//                    $map2['scene']=array('in',[0,1]);
//                    $buyNumCount = Db::name('activity_order')->where($map2)->count();
//                    if ($buyNumCount == 1 && $orderInfo['uid'] != $orderInfo['fid']) {
//                        $ticketInfo = Db::name('draw_scene')->where('scene_prefix', 11)->field('scene_name,image1')->find();
//                        $send = sendTicket($orderInfo['uid'], 11, $ticketInfo['image1']);
//                        if ($send) {
//                            $content = "恭喜，您获得" . $ticketInfo['scene_name'] . "一张，请至我的卡券中查看！";
//                            $arr = array('uid' => $orderInfo['uid'], 'title' => '首单奖励通知', 'content' => $content);
//                            sendDrawQueue($arr);
//                        }
//                    }
                }
                //宏伟门店定制 活动结束可删除
                if($orderInfo['scene'] ==4){
                    $ticket=new TicketUserModel();
                    $ticket_image='https://pgimg1.qunarmei.com/hwdjq.jpg';
                    //根据活动 要自定义奖券名称 分新老顾客
                    if($orderInfo['pid']==173){
                        $ticket_name='199元现金消费券';
                        $ticket_remark='';
                        $ticket_name1='1000元内衣代金券';
                        $ticket_remark1='内衣消费达2000元及以上可抵用';
                        if($orderInfo['activity_flag']=='8812'){
                            $ticket_remark='或店内指定项目3次体验';
                        }
                        $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name,$ticket_image,-1,$ticket_remark);
                        $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name1,$ticket_image,-1,$ticket_remark1);
                    }elseif($orderInfo['pid']==174){
                        $ticket_name='3380元现金消费券';
                        $ticket_remark='或店内指定项目12次/疗程';
                        $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name,$ticket_image,-1,$ticket_remark);
                    }
                }
                //处理三大体验项目3好友裂变是否完成
//                if(($getGoodType ==6 || $getGoodType ==7 || $getGoodType ==8) && ($orderInfo['activity_flag'] =='8813' || $orderInfo['activity_flag'] =='8814' || $orderInfo['activity_flag'] =='8815')){
//                    if($getGoodType ==6){
//                        $activity_flag='8813';
//                    }
//                    if($getGoodType ==7){
//                        $activity_flag='8814';
//                    }
//                    if($getGoodType ==8){
//                        $activity_flag='8815';
//                    }
//                    //检查引导用户是否购买过当前产品 如果购买过且拉了3个新客 将订单置为可购买1元产品
//                    $where['o.storeid']=array('eq',$orderInfo['storeid']);
//                    $where['o.pid']=array('eq',$orderInfo['pid']);
//                    $where['m.pid']=array('eq', $orderInfo['mpid']);
//                    $where['m.activity_flag']=array('eq',$activity_flag);
//                    $where['m.id']=array('neq', $orderInfo['mpid']);
//                    $count= Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id')->where($where)->count();
//                    if($count==3){
//                        //3个新客  将引导人的改为资格通过
//                        $count2=Db::name('double12_user')->where(['uid'=>$orderInfo['mpid'],'pid'=>$getGoodType])->count();
//                        if(!$count2) {
//                            Db::name('double12_user')->insert(['uid' => $orderInfo['mpid'], 'pid' => $getGoodType]);
//                        }
//                    }
//                }

                //处理形体奖券发放 该券可按门店需求在后台配置
//                if($getGoodType ==9){
//                    //获取门店指定券类型
//                    $temp_ticket=Db::table('ims_bwk_branch')->where('id',$orderInfo['storeid'])->value('temp_ticket');
//                    $ticket=new TicketUserModel();
//                    if ($temp_ticket) {
//                        $ticket_image = 'https://pgimg1.qunarmei.com/nzxfq.jpg';
//                        $ticket_name = '门店指定金额消费券';
//                        $ticket_remark = '';
//                    } else {
//                        $ticket_image = 'https://pgimg1.qunarmei.com/nzxfq.jpg';
//                        $ticket_name = intval($orderInfo['activity_price']/2).'元内衣换购抵用券';
//                        $ticket_remark = '';
//                    }
//                    for ($i=0;$i<$orderInfo['num'];$i++) {
//                        $ticket->insertTicket($orderInfo['uid'], 20, 'activate_', $ordersn, $ticket_name, $ticket_image, -1, $ticket_remark,$i);
//                    }
//                }

                //2020春节福袋
//                if($getGoodType ==10){
//                    //如果当前购买推广人连接不为空，会给链接推广者返单件产品的28元（推广者：本单购买通过谁的推广连接进来 谁就是推广人）
//                    if($orderInfo['share_uid']){
//                        $sellerId=Db::table('ims_bj_shopn_member')->where('id',$orderInfo['share_uid'])->value('staffid');
//                        $score = $orderInfo['num'] * 28;
//                        $scoreData = array('user_id' => $orderInfo['share_uid'], 'fid'=>$sellerId,'goods_id'=>$orderInfo['pid'],'storeid'=>$orderInfo['storeid'],'order_sn'=>$ordersn,'remark' => '推广uid' . $orderInfo['uid'] . '用户下单奖励' . $score . '分', 'money' => intval($score), 'type' => '88福袋', 'insert_time' => date('Y-m-d H:i:s', time()));
//                        Db::name('promoter')->insert($scoreData);
//                    }
//                    $ticket=new TicketUserModel();
//                    //购买88福袋，产品将已券的形式发放到卡券包
//                    //1张DGR胎盘蛋白赋活精华129元+RF紧致微雕导入698元券
//                    $ticket_image='https://pgimg1.qunarmei.com/year_yt.jpg';
//                    $ticket_name='DGR胎盘蛋白赋活精华129元';
//                    $ticket_remark='RF紧致微雕导入698元';
//                    $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name,$ticket_image,0,$ticket_remark);
//                    //2张开门红现金礼券600元+1张开门红现金礼券680元券
//                    $ticket_image1='https://pgimg1.qunarmei.com/year_cash600.jpg';
//                    $ticket_image2='https://pgimg1.qunarmei.com/year_cash680.jpg';
//                    $ticket_name1='开门红现金礼券';
//                    $ticket_remark1='';
//                    $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name1.'600元',$ticket_image1,0,$ticket_remark1);
//                    $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name1.'600元',$ticket_image1,0,$ticket_remark1);
//                    $ticket->insertTicket($orderInfo['uid'],20,'activate_',$ordersn,$ticket_name1.'680元',$ticket_image2,0,$ticket_remark1);
//                }

                //618胎盘蛋白赋活精华附加券发放
                if($getGoodType ==20){
                    $ticket=new TicketUserModel();
                        $ticketInfo = Db::name('draw_scene')->where('scene_prefix',28)->field('image0,image1')->find();
                        $ticket_name = '胎盘童颜术护理1次';
                    for ($i=0;$i<2;$i++) {
                        $ticket_image=$ticketInfo['image'.$i];
                        $ticket_remark = $i?'可转赠券':'';
                        $ticket->insertTicket($orderInfo['uid'], 28, 'activate_', $ordersn, $ticket_name, $ticket_image, 0, $ticket_remark,$i);
                    }
                }
            }
        }
    }



    //印花券合成
    public function card_upgrade($uid,$share_uid,$order_sn){
        //获取分享人信息 分享人不能是美容师和店老板
        $fidInfo=Db::table('ims_bj_shopn_member')->field('code,isadmin')->where('id', $share_uid)->find();
        if($fidInfo['isadmin']==0 || strlen($fidInfo['code'])<1) {
            //检测当前购买人购买次数  只有第一次订单时候才能给推广人奖励印花
            $map['uid']=array('eq',$uid);
            $map['pay_status']=array('eq',1);
            $map['channel']=array('eq','missshop');
            $map['scene']=array('in',[0,1]);
            $buyNum = Db::name('activity_order')->where($map)->count();
            if ($buyNum == 1) {
                //插入印花
                $cardData=array('uid'=>$share_uid,'order_sn'=>$order_sn,'type'=>1,'source'=>1,'insert_time'=>time());
                Db::name('card_upgrade')->insert($cardData);
            }
        }
    }

    //发送同享
    public function send_share_ticket($order_sn,$uid,$ticket_id,$num){
        $ticketInfo = Db::name('draw_scene')->where('scene_prefix', $ticket_id)->field('scene_name,image1')->find();
        $send = sendTicket($uid, $ticket_id, $ticketInfo['image1'],0,'sharing_', $order_sn, $num);
        if($send){
            $ticket=Db::name('ticket_user')->where('order_sn',$order_sn)->field('ticket_code,mobile')->find();
            $data=['order_sn'=>$order_sn,'ticket_sn'=>$ticket['ticket_code'],'uid'=>$uid,'mobile'=>$ticket['mobile'],'buyer_flag'=>1,'accept_flag'=>1,'accept_time'=>time(),'insert_time'=>time()];
            Db::name('activity_order_sharing')->insert($data);
        }
    }

}