<?php

namespace app\api\controller;
use think\Controller;
use think\Db;
use weixin\WeixinPay;

/**
 * swagger: 支付回调
 */
class Notify extends Base
{


    public function test(){
        //发送模版消息
        //$open_id="oiSgB5TEtkwX8hhBjgv4mqvAuRn8";
        //$data['keyword1'] = array('value'=>'201821231231','color'=>'#000');
        //$data['keyword2'] = array('value'=>date('Y-m-d H:i'),'color'=>'#000');
        //$data['keyword3'] = array('value'=>'测试模版消息','color'=>'#000');
        //$data['keyword4'] = array('value'=>100,'color'=>'#000');
        //send_weapp_msg($open_id,'3XH3Iu9GlVDOXOclMhRJRrTzDBU8pydaV7bvc6IIVXY','','wx08141343250531ef39988f3b0972023312',$data,'keyword4.DATA');
        //发送短消息

    }



    /*
 * 去哪美支付回调
 *
 * */
    public function qunarmeiNotify(){
        // 接收回调参数
        $data = input('post.');
        file_put_contents('qnm_huidiao.txt',json_encode($data));
//        write_log(['msg'=>'订单号为'.$data['orderID'].'-订单回调开始']);
//        write_log(['msg'=>'订单号为'.$data['orderID'].'-回调数据:'.json_encode($data,JSON_UNESCAPED_UNICODE)]);
//        if($data['stateCode']==2){
//
//            $pay = new Pay();
//            //$arr=>[transid=>交易流水号,order_no=>订单号,pay_price=>支付金额]
//            $arr = ['transid'=>$data['orderNo'],'order_no'=>$data['orderID'],'pay_price'=>$data['payAmount']];
//            $pay->paySuc($arr);
//            write_log(['msg'=>'订单号为'.$data['orderID'].'-订单回调结速-处理成功']);
//        }
    }



}