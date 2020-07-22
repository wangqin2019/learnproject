<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/5/14
 * Time: 17:15
 */

namespace app\api\controller\v4;


use app\api\service\StoreItem;
use app\api\service\WxPay;

class CallBack
{
    /**
     * 去哪美支付回调
     */
    public function qnmPayReturn(){
        $arr1 = ['code'=>0,'data'=>[],'msg'=>'回调失败'];
        // 接收回调参数
        $data = input('post.');
        write_log('订单号为'.$data['orderID'].'-订单回调开始','qnmpay');
        write_log('订单号为'.$data['orderID'].'-回调数据:'.json_encode($data,JSON_UNESCAPED_UNICODE),'qnmpay');
        if($data['stateCode']==2){
            //$arr=>[transid=>交易流水号,order_no=>订单号,pay_price=>支付金额]
            $arr = ['appoint_sn'=>$data['orderID'],'transaction_id'=>$data['orderNo'],'pay_price'=>$data['payAmount']];
            $ser = new StoreItem();
            $res = $ser->callPaySuc($arr);
            if($res == 1){
                $arr1['msg'] = '回调成功';
                $arr1['code'] = 1;
            }elseif($res == -1){
                $arr1['msg'] = '已回调过,重复回调';
            }
            write_log('订单号为'.$data['orderID'].'-订单回调结速-处理成功','qnmpay');
        }
        return_msg($arr1);
    }

    /*
     * 微信支付回调
     * */
    public function wxPayReturn(){
        $postXml = null;
        if ($postXml == null) {
            $postXml = file_get_contents("php://input");
        }

        if ($postXml == null) {
            $postXml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
        }

        if (empty($postXml) || $postXml == null || $postXml == '') {
            //阻止微信接口反复回调接口  文档地址 https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_7&index=7，下面这句非常重要!!!
            $str_arr = ['return_code'=>'SUCCESS','return_msg'=>'OK'];
            $wxpay = new WxPay();
            $str = $wxpay->arrayToXml($str_arr);
            echo $str;
            exit('Notify 非法回调');
        }
        /*****************微信回调返回数据样例*******************
        $post = '<xml>
        <return_code><![CDATA[SUCCESS]]></return_code>
        <return_msg><![CDATA[OK]]></return_msg>
        <appid><![CDATA[wx2421b1c4370ec43b]]></appid>
        <mch_id><![CDATA[10000100]]></mch_id>
        <nonce_str><![CDATA[IITRi8Iabbblz1Jc]]></nonce_str>
        <sign><![CDATA[7921E432F65EB8ED0CE9755F0E86D72F]]></sign>
        <result_code><![CDATA[SUCCESS]]></result_code>
        <prepay_id><![CDATA[wx201411101639507cbf6ffd8b0779950874]]></prepay_id>
        <trade_type><![CDATA[APP]]></trade_type>
        <out_trade_no><![CDATA[19000201905091012370]]></out_trade_no>
        <transaction_id><![CDATA[4200000134201806265483331660]]></transaction_id>
        <total_fee><![CDATA[399]]></total_fee>
        </xml>';
        ************************************/
//        echo '<pre>';print_r($postXml);die;
        $wxpay = new WxPay();
        $attr = $wxpay->xmlToArray($postXml);
        $arr1 = ['code'=>0,'data'=>[],'msg'=>'回调失败'];
        if ($attr['result_code'] != 'SUCCESS' || $attr['return_code'] != 'SUCCESS') {
            write_log('微信支付失败信息:'.$attr['return_msg'].'-'.$attr['err_code'].'-'.$attr['err_code_des'],'wxpay');
            exit('fail');
        }else{
            write_log('订单号为'.$attr['out_trade_no'].'-订单回调开始','wxpay');
            write_log('订单号为'.$attr['out_trade_no'].'-回调数据:'.json_encode($attr,JSON_UNESCAPED_UNICODE),'wxpay');
            // 成功业务逻辑处理
            $arr = ['appoint_sn'=>$attr['out_trade_no'],'transaction_id'=>$attr['transaction_id'],'pay_price'=>($attr['total_fee']/100)];
            $ser = new StoreItem();
            $res = $ser->callPaySuc($arr);
            if($res == 1){
                $arr1['msg'] = '回调成功';
                $arr1['code'] = 1;
            }elseif($res == -1){
                $arr1['msg'] = '已回调过,重复回调';
            }
            write_log('订单号为'.$attr['out_trade_no'].'-订单回调结速-处理成功','wxpay');
        }
        //阻止微信接口反复回调接口  文档地址 https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_7&index=7，下面这句非常重要!!!
//        $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        $str_arr = ['return_code'=>'SUCCESS','return_msg'=>'OK'];
        $str = $wxpay->arrayToXml($str_arr);
        echo $str;exit('回调成功');
    }
    /*
     * 支付宝支付回调
     * */
    public function aliaPayReturn(){
//        $postData = $GLOBALS["HTTP_RAW_POST_DATA"]; //接收支付宝参数
        $postData = input('post.');
//        echo '<pre>postData:';print_r($postData);die;
        // 没有支付成功回调
        if (empty($postData)) {
            return false;
        }
        write_log('订单号为'.$postData['out_trade_no'].'-订单回调开始','aliapay');
        write_log('订单号为'.$postData['out_trade_no'].'-回调数据:'.json_encode($postData,JSON_UNESCAPED_UNICODE),'aliapay');
        $arr1 = ['code'=>0,'data'=>[],'msg'=>'回调失败'];
        if ($postData['trade_status'] != 'TRADE_SUCCESS') {
            write_log('支付宝支付失败信息','aliapay');
            return 'fail';
        }else{
            // 成功业务逻辑处理
            $arr = ['appoint_sn'=>$postData['out_trade_no'],'transaction_id'=>$postData['trade_no'],'pay_price'=>$postData['price'],'pay_time'=>$postData['gmt_payment']];
            $ser = new StoreItem();
            $res = $ser->callPaySuc($arr);
            if($res == 1){
                $arr1['msg'] = '回调成功';
                $arr1['code'] = 1;
            }elseif($res == -1){
                $arr1['msg'] = '已回调过,重复回调';
            }
            write_log('订单号为'.$postData['out_trade_no'].'-订单回调结速-处理成功','aliapay');
        }
        return 'success';
    }
}