<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/6/4
 * Time: 10:19
 */

namespace app\api\controller;

/*
 * 去哪美支付接口
 *
 * */
use think\Controller;

class QunarmeiPay extends Controller
{

    /*
     * 支付参数整合
     * @param  [string] $arr => [bank_id=>银行id,order_no=>订单编号,no_period=>分期数,sum_price=>支付总价,gd_title=>商品名称,sum_num=>商品数量,'mobile'=>手机号]
     * */
    public function qunarmeiPayParameter($arr)
    {
        // 获取自定义支付参数
        $pay_notice_url = config('qunarmei_pay.pay_notice_url');// 支付回调url
        $partner_id = config('qunarmei_pay.partner_id');// 支付商户ID
        $return_url = config('qunarmei_pay.return_url');// 商户回调地址

        $no_period = 1;$payType = 'BANK_B2C';$submit_time = date('YmdHis');

        $sum_price = $arr['sum_price']*100;// 元转换成分
        $order_no = $arr['order_no'];$serial_id = $order_no==''?'dw_'.date('YmdHis'):'dw_'.$order_no;
        $order_details = $order_no.','.$sum_price.',诚则至美,'.mb_substr($arr['gd_title'],0,14,'utf-8').','.$arr['sum_num'];
        $mobile = $arr['mobile'];

        $org_code = config('org_code');
        $orgCode = $org_code[$arr['bank_id']];
//        "version=" + version + "&serialID=" + serialID + "&submitTime=" + submitTime
//        + "&failureTime=" + (StringUtils.isEmpty(failureTime) ? "":failureTime) + "&customerIP=" +(StringUtils.isEmpty(customerIP) ? "":customerIP)
//        + "&orderDetails=" + orderDetails + "&totalAmount=" + totalAmount
//        + "&type=" + type + "&buyerMarked=" + (StringUtils.isEmpty(buyerMarked) ? "":buyerMarked) + "&payType="
//        + payType + "&orgCode=" + orgCode + "&currencyCode=" + currencyCode
//        + "&directFlag=" + directFlag + "&borrowingMarked=" + borrowingMarked
//        + "&couponFlag=" + couponFlag + "&platformID=" + (StringUtils.isEmpty(platformID) ? "":platformID)
//        + "&returnUrl=" + returnUrl + "&noticeUrl=" + noticeUrl
//        + "&partnerID=" + partnerID + "&remark=" + (StringUtils.isEmpty(remark) ? "":remark)
//        + "&charset=" + charset + "&signType=" + signType
//        + "&installmentTimes=" + installmentTimes + "&pkey=" + key;
        $post_arr = ['version'=>'2.6','serialID'=>$serial_id,'submitTime'=>$submit_time,'failureTime'=>'','customerIP'=>'','orderDetails'=>$order_details,'totalAmount'=>$sum_price,'type'=>'1000','buyerMarked'=>$mobile,'payType'=>$payType,'orgCode'=>$orgCode,'currencyCode'=>'1','directFlag'=>'1','borrowingMarked'=>'0','couponFlag'=>'1','platformID'=>'','returnUrl'=>$return_url,'noticeUrl'=>$pay_notice_url,'partnerID'=>$partner_id,'remark'=>'','charset'=>'1','signType'=>'2','installmentTimes'=>$no_period];
        // 获取签名
        $post_arr['signMsg'] = $this->setSignMsg($post_arr);
        return $post_arr;
    }

    /*
     * 生成支付签名
     * $post_arr
     * */
    private function setSignMsg($post_arr){
        $signMsg = '';
        if($post_arr){
            $str = '';
            unset($post_arr['signMsg']);
            foreach ($post_arr as $k => $v) {
                $str .= $k . '=' . $v . '&';
            }
            $md5_key = config('qunarmei_pay.md5_key');
            $str .= 'pkey='.$md5_key;
            $signMsg = md5($str);
        }
        return $signMsg;
    }
}