<?php

namespace app\blink\controller;
use app\blink\model\TicketUserModel;
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
        $buyUser = Db::name('blink_wx_user')->where('token', $token)->find();
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
            logs(date('Y-m-d H:i:s')."：".json_encode($attr),'blinkpayFail');
            $result = false;
        }else {
            $total_fee = number_format($attr['total_fee']/100,2,'.','');
            $out_trade_no = $attr['out_trade_no'];
            $attach = $attr['attach'];
            if($attach == 'blink'){
                //记录支付成功返回
                $logInfo = Db::name('pay_log')
                    ->field('id,mobile,status,order_sn,prepay_id')
                    ->where(['out_trade_no' => $out_trade_no])
                    ->limit(1)
                    ->order('log_time desc')
                    ->find();
                if ($logInfo) {
                    $payTime = time();
                    $data1 = array(
                        'transaction_id' => $attr['transaction_id'],
                        'status' => 1,
                        'upd_time' => date('Y-m-d H:i:s', $payTime)
                    );

                    Db::name('pay_log')->where('id', $logInfo['id'])->update($data1);
                    $data6 = array(
                        'transaction_id' => $attr['transaction_id'],
                        'out_trade_no'=>$out_trade_no,
                        'pay_status' => 1,
                        'status' => 2,
                        'pay_price' => $total_fee,
                        'pay_time' => time()
                    );
                    Db::name('blink_order')->where('order_sn', $logInfo['order_sn'])->update($data6);

                    //订单商品减库存
                    $this->buyEndAction($logInfo['order_sn']);
                    /*//1.根据订单减盒子库存
                    $orders = Db::name('blink_order')
                        ->where('order_sn', $logInfo['order_sn'])
                        ->find();
                    $this->setDec('blink_box_number',intval($orders['num']));
                    //2.生成盒子记录
                    $blinks = generate_promotion_code($orders['uid'],$orders['num'],'',8);
                    $insert = [];
                    for($i=0;$i<$orders['num'];$i++){
                        $insert[] = [
                            'order_id' => $orders['id'],
                            'uid' => $orders['uid'],
                            'blinkno' => $blinks[$i],
                            'goods_id' => $orders['goods_id'],
                            'price' => $orders['pay_price']/$orders['num'],
                            'status' => 0,
                            'is_give' => 0,//为赠送
                            'is_pay' => 1,//未支付
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                    }
                    if(!empty($insert)){
                        logs(date('Y-m-d H:i:s') . "：3" . json_encode($insert), 'aaa');
                        Db::name('blink_order_box')->insertAll($insert);
                        //3.配置汇总盒子数量减库存
                        Db::name('blink_box_config')->where('id',1)->setDec('box_number',intval($orders['num']));
                    }
                    unset($insert);*/
                    $result = true;
                }
            }else{
                $result = false;
            }
        }
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;exit;
    }


    /*
     * 下单后事项处理
     */
    public function buyEndAction($ordersn){
        //1.根据订单减盒子库存
        $orders = Db::name('blink_order')
            ->where('order_sn', $ordersn)
            ->find();
        if(!empty($orders)){
            //检测是否已存在盒子
            if(Db::name('blink_order_box')->where('order_id',$orders['id'])->count()){
                logs(date('Y-m-d H:i:s') . "：bbbbbbbbbbbbbbbbbbb " , 'aaa');
                return '';
            }
            //2.生成盒子记录
            $blinks = generate_promotion_code($orders['uid'],$orders['num'],'',8);
            $insert = [];
            for($i=0;$i<$orders['num'];$i++){
                $insert[] = [
                    'order_id' => $orders['id'],
                    'uid' => $orders['uid'],
                    'blinkno' => $blinks[$i],
                    'goods_id' => $orders['goods_id'],
                    'price' => $orders['pay_price']/$orders['num'],
                    'status' => 0,
                    'is_give' => 0,//未赠送
                    'is_pay' => 1,//未支付
                    'create_time' => time(),
                    'update_time' => time()
                ];
            }

            //所属美容师添加积分  1792
            $price = floor($orders['pay_price']);

            if(!empty($price)){
                $fid = $orders['fid'];
                $_msg = "下属uid{$orders['uid']}用户下单，奖励美容师{$price}分";
                //检测当前用户是否属于 1792
                if($orders['storeid'] == 1792){
                    //检测当前用户的引领人的美容师 originfid
                    /*$pid = Db::table('ims_bj_shopn_member')
                        ->where('id',$orders['uid'])
                        ->value('pid');
                    $_ifno = Db::table('ims_bj_shopn_member')
                        ->where('id',$pid)
                        ->find();
                    if($_ifno['id'] == $_ifno['staffid'] && strlen($_ifno['code']) > 0){
                        $fid = $pid;
                    }else{
                        $fid = $_ifno['staffid'];
                    }*/
                    $info = Db::table('ims_bj_shopn_member')
                        ->where('id',$orders['uid'])
                        ->find();
                    $fid = $info['originfid'] ?: $info['staffid'];
                    $_msg = "下属uid{$orders['uid']}用户下单,所属美容师为{$info['staffid']},引领人{$info['pid']}的美容师为{$fid}，奖励美容师{$price}分";
                }
                $scores = [
                    'user_id'  => $fid,//美容师
                    'type'     => 'blink',//标识
                    'scores'   => $price,//积分
                    'msg'      => $_msg,
                    'remark'   => $ordersn,
                    'log_time' => date('Y-m-d H:i:s')
                ];
                $s_id = Db::table('think_scores_record')->insert($scores,false,true);
                logs(date('Y-m-d H:i:s') . "{$s_id}： " . json_encode($scores), 'blink_score');
            }

            if(!empty($insert)){
                logs(date('Y-m-d H:i:s') . "：3 " . json_encode($insert), 'aaa');
                Db::name('blink_order_box')->insertAll($insert);
                unset($insert);
                //3.配置汇总盒子数量减库存
                Db::name('blink_box_config')->where('id',1)->setDec('box_number',intval($orders['num']));
                //盒子库存减$orders['num']
                $this->setDec('blink_box_number',intval($orders['num']));
            }
        }
    }

}