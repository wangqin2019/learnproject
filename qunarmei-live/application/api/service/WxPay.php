<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/6/4
 * Time: 10:19
 */

namespace app\api\service;

/*
 * 微信支付接口
 *
 * */

class WxPay
{
    //下单
    public function getPrePayOrder($body, $out_trade_no, $total_fee){
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $notify_url = config('text.qnm_wxcall_url');

        $onoce_str = $this->createNoncestr();

        $data["appid"] = config('text.qnm_wx_appid');
        $data["body"] = $body;
        $data["mch_id"] = config('text.qnm_mch_id');
//        $data["mch_id"] = config('wx_pay.qnm_mch_id');
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["sign_type"] = "MD5";
        $data["spbill_create_ip"] = $this->get_client_ip();
        $data["total_fee"] = $total_fee;
        $data["trade_type"] = "APP";
        $sign = $this->getSign($data);
        $data["sign"] = $sign;
//        print_r($data);die;
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);
        //将微信返回的结果xml转成数组
        $response = $this->xmlToArray($response);
//        echo '<pre>';print_r($response);die;
        //客户端需要的二次签名
        $arr1['appid'] = config('text.qnm_wx_appid');
        $arr1['noncestr'] = $response['nonce_str'];
        $arr1['package'] = 'Sign=WXPay';
        $arr1['partnerid'] = config('text.qnm_mch_id');
//        $arr1['partnerid'] = config('wx_pay.qnm_mch_id');
        $arr1['prepayid'] = $response['prepay_id'];
        $arr1['timestamp'] = (string)time();
        $response['timestamp'] = $arr1['timestamp'];
        $response['sign_client'] = $this->getSign($arr1);
        $arr1['packageing'] = 'Sign=WXPay';
        $arr1['sign'] = $response['sign_client'];
        //返回数据
        return $arr1;
    }

    // 生成签名
    public function getSign($Obj){
        foreach ($Obj as $k => $v){
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".config('text.qnm_wx_key');
//        $String = $String."&key=".config('wx_pay.qnm_api_key');
//        echo "【string2】".$String."</br>";die;
        //签名步骤三：MD5加密
        $String = md5($String);
//        $String = hash_hmac("sha256",$String,config('wx_pay.api_key'));
//        echo "【string3】 ".$String."</br>";die;
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }


    /**
     *  作用：产生随机字符串，不长于32位
     */
    public function createNoncestr( $length = 32 ){
//        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }


    //数组转xml
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


    /**
     *  作用：将xml转为array
     */
    public function xmlToArray($xml){
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }


    /**
     *  作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml,$url,$second=30){
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果

        if($data){
            curl_close($ch);
            return $data;
        }else{
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            curl_close($ch);
            return false;
        }
    }


    /*
    获取当前服务器的IP
    */
    public function get_client_ip(){
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        // 测试
        $cip = '1.1.1.1';
        return $cip;
    }


    /**
     *  作用：格式化参数，签名过程需要使用
     */
    public function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if($urlencode){
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0){
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
}