<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/6/4
 * Time: 10:19
 */

namespace app\api\service;
use think\Exception;
/*
 * 支付宝支付接口
 *
 * */
require_once (EXTEND_PATH.'alipay03/AopSdk.php');
class AliPay
{

    public function unifiedorderTest($orderId, $subject,$body,$pre_price,$expire){

        $mygoods['partner'] = '"'.config('text.qnm_aliapy_partner').'"';
        $mygoods['seller_id'] = '"'.config('text.qnm_alipay_sellerid').'"';
        $mygoods['out_trade_no'] = '"'.$orderId.'"';
        $mygoods['subject'] = '"'.$subject.'"';
        $mygoods['body'] = '"'.$body.'"';
        $mygoods['total_fee'] = '"'.$pre_price.'"';
        $mygoods['notify_url'] = '"'.config('text.qnm_aliacall_url').'"';
        $mygoods['service'] = "\"mobile.securitypay.pay\"";
        $mygoods['payment_type'] = "\"1\"";
        $mygoods['_input_charset'] = "\"utf-8\"";
        $mygoods['it_b_pay'] = "\"30m\"";

        // 数组拼接成字符串
        $mystr = $this->createLinkstring($mygoods);
        //生成最终签名信息
        $sign = $this->rsaSign($mystr,'/home/canmay/www/test.qunarmeic.com/public/key/alia_prikey.pem');
        $sign = urlencode($sign);
        //生成最终签名信息
        $rest = $mystr.'&sign="'.$sign.'"&sign_type="RSA"';
        return $rest;
    }
    /**
     *生成APP支付订单信息
     * @param string $orderId 商品订单ID
     * @param string $subject 支付商品的标题
     * @param string $body 支付商品描述
     * @param float $pre_price 商品总支付金额
     * @param int $expire 支付交易时间
     * @return bool|string 返回支付宝签名后订单信息，否则返回false
     */
    public function unifiedorder($orderId, $subject,$body,$pre_price,$expire){
        try{
            $orderId = '20000155832040269466';
            $subject = '紧致明眸の初体验';
            $body = '紧致明眸の初体验';
            $pre_price = '0.01';
            $expire = '30m';

            $aop = new \AopClient();
//            $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
//            $aop->appId = config('text.qnm_aliapy_partner');
//            $aop->rsaPrivateKey = config('text.qnm_alipay_privatekey');
//            $aop->format = "json";
//            $aop->charset = "UTF-8";
//            $aop->signType = "RSA";
//            $aop->alipayrsaPublicKey = config('text.qnm_alipay_publickey');
            //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
//            $request = new \AlipayTradeAppPayRequest();
            //SDK已经封装掉了公共参数，这里只需要传入业务参数
//            $bizcontent = "{\"body\":\"{$body}\"," //支付商品描述
//                . "\"subject\":\"{$subject}\"," //支付商品的标题
//                . "\"out_trade_no\":\"{$orderId}\"," //商户网站唯一订单号
//                . "\"timeout_express\":\"{$expire}m\"," //该笔订单允许的最晚付款时间，逾期将关闭交易
//                . "\"total_amount\":\"{$pre_price}\"," //订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
//                . "\"product_code\":\"QUICK_MSECURITY_PAY\""
////                . "}";
//            echo '<pre>';print_r($bizcontent);die;
//
//            $request->setNotifyUrl(config('text.qnm_aliacall_url'));
//            $request->setNotifyUrl("http://test.api.app.qunarmei.com/qunamei/alipaynotice");
//            $request->setBizContent($bizcontent);
            //这里和普通的接口调用不同，使用的是sdkExecute

            /**支付宝新版本接口接入**/
//            $response = $aop->sdkExecute($request);
//            echo '<pre>response:';print_r($response);die;

//            echo '<pre>response:';print_r($response);die;
//            echo '<pre>response';print_r($response);die;
            // partner=2088421360665476&seller_id=it@qunarmei.com&out_trade_no=订单号&subject=商品名称&body=商品名称&total_fee=总费用&notify_url=回调url&service=mobile.securitypay.pay&payment_type=1&_input_charset=utf-8&it_b_pay=
//            $response1 = 'partner="'.config('text.qnm_aliapy_partner').'"&seller_id="'.config('text.qnm_alipay_sellerid').'"&out_trade_no="'.$orderId.'"&subject="'.$body.'"&body="'.$body.'"&total_fee="'.$pre_price.'"&notify_url="'.config('text.qnm_aliacall_url').'"&service="mobile.securitypay.pay"&payment_type="1"&_input_charset="utf-8"&it_b_pay="30m"';
//            if($response){
//                parse_str($response,$res_arr);
//                $response1 .= '&sign="'.$res_arr['sign'].'"&sign_type="RSA"';
//            }
//            return $response1;


            //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
//            return htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
            // 支付宝老版本支付文档-组装返回参数
//            $alia_pay_conf = config('alia_pay');

            /**支付宝老版本接口接入**/
            $mygoods['partner'] = '"'.config('text.qnm_aliapy_partner').'"';
            $mygoods['seller_id'] = '"'.config('text.qnm_alipay_sellerid').'"';
            $mygoods['out_trade_no'] = '"'.$orderId.'"';
            $mygoods['subject'] = '"'.$subject.'"';
            $mygoods['body'] = '"'.$body.'"';
            $mygoods['total_fee'] = '"'.$pre_price.'"';
//            $mygoods['notify_url'] = '"'.config('text.qnm_aliacall_url').'"';
            $mygoods['notify_url'] = "\"http://test.api.app.qunarmei.com/qunamei/alipaynotice\"";
            $mygoods['service'] = "\"mobile.securitypay.pay\"";
            $mygoods['payment_type'] = "\"1\"";
            $mygoods['_input_charset'] = "\"utf-8\"";
            $mygoods['it_b_pay'] = "\"30m\"";
//            $rest = 'partner="'.$alia_pay_conf['partner'].'"&seller_id="'.$alia_pay_conf['seller_id'].'"&out_trade_no="'.$orderId.'"&subject="'.$subject.'"&body="'.$body.'"&total_fee="'.$pre_price.'"&notify_url="'.$alia_pay_conf['notify_url'].'"&service="'.$alia_pay_conf['service'].'"&payment_type="1"'.'&_input_charset="utf-8"'.'&it_b_pay="'.$alia_pay_conf['it_b_pay'].'"';
            //
            //排序
            $mygoods = $this->argSort($mygoods);
            //拼接
            $mystr = $this->createLinkstring($mygoods);
            //签名
//            $pri_key = config('text.qnm_alipay_privatekey');
//            echo '<pre>';print_r($mystr);print_r($pri_key);die;
            $sign = $this->rsaSign($mystr,'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\application\api\service\alia_prikey.pem');
//            $mystr = $this->createLinkstring($mygoods);

            //生成最终签名信息
            $rest = $mystr.'&sign="'.$sign.'"&sign_type="RSA"';
            echo '<pre>sign:';print_r($sign);echo '<pre>rest:';print_r($rest);die;
            //partner="2088101568358171"&seller_id="xxx@alipay.com"&out_trade_no="0819145412-6177"&subject="测试"&body="测试测试"&total_fee="0.01"&notify_url="http://notify.msp.hk/notify.htm"&service="mobile.securitypay.pay"&payment_type="1"&_input_charset="utf-8"&it_b_pay="30m"&sign="lBBK%2F0w5LOajrMrji7DUgEqNjIhQbidR13GovA5r3TgIbNqv231yC1NksLdw%2Ba3JnfHXoXuet6XNNHtn7VE%2BeCoRO1O%2BR1KugLrQEZMtG5jmJIe2pbjm%2F3kb%2FuGkpG%2BwYQYI51%2BhA3YBbvZHVQBYveBqK%2Bh8mUyb7GM1HxWs9k4%3D"&sign_type="RSA"


            // partner=\"2088421360665476\"&seller_id=\"it@qunarmei.com\"&out_trade_no=\"20000155832040269466\"&subject=\"紧致明眸の初体验\"&body=\"紧致明眸の初体验\"&total_fee=\"0.01\"&notify_url=\"http://test.api.app.qunarmei.com/qunamei/alipaynotice\"&service=\"mobile.securitypay.pay\"&payment_type=\"1\"&_input_charset=\"utf-8\"&it_b_pay=\"30m\"&sign=\"CnjPT25Vv%2Bbv2odWnsQRZ2e42rbwTkEtthMnEI7nBg5R%2B7z8eG8J%2BOcwdIi%2FYY%2B7BzD7ibgZsCWE%2BfSfmRRnU6yGMjiKjHmnXoHIDcYa3cY%2FgUog%2FThf%2BVnF79P0dh2YjvmuPBgozk3DMFV3nAIJEPjdpxmHA%2BChvtjyYjKZD4U%3D\"&sign_type=\"RSA\"
            return $rest;
        }catch (\Exception $e){
            return false;
        }

    }

    /*************************需要使用到的方法*******************************/
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    private function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * return 签名结果
     */
    private function rsaSign($data,$private_key_path) {
        $priKey = file_get_contents($private_key_path);
//        echo '<pre>priKey:';print_r($priKey);die;
        $res = openssl_get_privatekey($priKey);
//        echo '<pre>res:';print_r($res);die;
        openssl_sign($data, $sign, $res);
        echo '<pre>sign:';print_r($sign);die;
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    private function getPrikey($priKey)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        return $res;
    }

    /**RSA验签
     * $data待签名数据
     * $sign需要验签的签名
     * 验签用支付宝公钥
     * return 验签是否通过 bool值
     */
    private function verify($data, $sign)  {
        //读取支付宝公钥文件
        $pubKey = file_get_contents('key/alipay_public_key.pem');

        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);

        //释放资源
        openssl_free_key($res);

        //返回资源是否成功
        return $result;
    }
}