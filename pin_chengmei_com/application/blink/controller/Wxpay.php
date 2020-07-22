<?php
namespace app\blink\controller;
use think\Controller;
use think\Loader;
Loader::import('WxPay.WxPay',EXTEND_PATH,'.Api.php');
class Wxpay extends Controller
{
    function index()
    {
        $order="111224452414";
        $money=1;
        //     初始化值对象
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("点餐");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$order");
        //     费用应该是由小程序端传给服务端的，在用户下单时告知服务端应付金额，demo中取值是1，即1分钱
        $input->SetTotal_fee("$money");
        $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
        $input->SetTrade_type("JSAPI");
        //     由小程序端传给服务端
       // $input->SetOpenid(input('openid'));
        $input->SetOpenid('oiSgB5TEtkwX8hhBjgv4mqvAuRn8');
        //     向微信统一下单，并返回order，它是一个array数组
        $order = \WxPayApi::unifiedOrder($input);
        //     json化返回给小程序端
        header("Content-Type: application/json");
        echo json_encode($order);
    }
}


