<?php
/**
 * @param int $length
 * @return string
 *
 * 生成唯一字符串
 */
function createToken()
{
    $str = md5(uniqid(md5(microtime(true)),true));  //生成一个不会重复的字符串
    $str = sha1($str);  //加密
    return $str;
}

//xml转换成数组
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $val = json_decode(json_encode($xmlstring), true);
    return $val;
}

/**
 * 获取access_token
 * @return string
 */
function getAccessToken1()
{
    //判断是否过了缓存期
    $expire_time = config('web_expires1');
    if($expire_time > time()){
        return  config('access_token1');
    }
    $appid = config('wx_blink_pay.appid');
    $appsecret = config('wx_blink_pay.appsecret');
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
    logs($url,'abc');
    $return = httpGet($url);
    if ($return === false) {
        return false;
    }
    $return=json_decode($return,true);
    $web_expires = time() + 7000; // 提前200秒过期
    \think\Db::name('config')->where('name','web_expires1')->setField('value', $web_expires);
    \think\Db::name('config')->where('name','access_token1')->setField('value', $return['access_token']);
    cache('db_config_data',null);
    return $return['access_token'];
}


//发送模版消息
function sendTemplateMessage($data){
    $access_token=getAccessToken();
    if (!$access_token) return false;
    $url="https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
    $result = http_post($url,json_encode($data));
    if($result){
        $json = json_decode($result,true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        }
        return $json;
    }
    return false;
}


// 获取带参数的二维码
// 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制。
function getWXACodeUnlimitBLink($path='',$scene,$width=430,$auto_color,$line_color,$is_hyaline){
    $access_token = getAccessToken1();
    if (empty($access_token)||empty($path)) {
        return 'error';
    }
    //$url = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$access_token}";
    $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
    $data = array();
    $data['path'] = $path;
    $data['scene'] = $scene;
    $data['auto_color'] = $auto_color;
    $data['line_color'] = $line_color;
    $data['is_hyaline'] = $is_hyaline;
    //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
    $data['width'] = $width;
    //二维码的宽度，默认为 430px
    $json = http_post($url,json_encode($data));
    return $json;
}


//两个日期只差
function count_days($a,$b){
    $a_dt = getdate($a);
    $b_dt = getdate($b);
    $a_new = mktime(12, 0, 0, $a_dt['mon'], $a_dt['mday'], $a_dt['year']);
    $b_new = mktime(12, 0, 0, $b_dt['mon'], $b_dt['mday'], $b_dt['year']);
    return (round(abs($a_new-$b_new)/86400))+1;
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

/**
 * @param int $user_defined  客户自定义开头
 * @param int $no_of_codes 定义一个int类型的参数 用来确定生成多少个优惠码
 * @param array $exclude_codes_array 定义一个exclude_codes_array类型的数组
 * @param int $code_length  定义一个code_length的参数来确定优惠码的长度
 * @return array//返回数组
 */
function generate_promotion_code($user_defined,$no_of_codes,$exclude_codes_array='',$code_length = 4)
{
    $characters = "0123456789";

    $promotion_codes = array();//这个数组用来接收生成的优惠码
    for($j = 0 ; $j < $no_of_codes; $j++)
    {
        $code = "";
        $code .= $user_defined;
        for ($i = 0; $i < $code_length; $i++)
        {
            $code .= $characters[mt_rand(0, strlen($characters)-1)];
        }
        //如果生成的4位随机数不再我们定义的$promotion_codes函数里面
        if(!in_array($code,$promotion_codes))
        {
            if(is_array($exclude_codes_array))//
            {
                if(!in_array($code,$exclude_codes_array))//排除已经使用的优惠码
                {
                    $promotion_codes[$j] = $code;//将生成的新优惠码赋值给promotion_codes数组
                }
                else
                {
                    $j--;
                }
            }
            else
            {
                $promotion_codes[$j] = $code;//将优惠码赋值给数组
            }
        }
        else
        {
            $j--;
        }
    }
    return $promotion_codes;
}


function httpPost($url,$data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //设置头部信息
    /*$headers = array('Content-Type:application/json; charset=utf-8','Content-Length: '.strlen($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);*/
    //执行请求
    $output = curl_exec($ch);
    $output = json_decode($output,JSON_UNESCAPED_UNICODE);

    curl_close($ch);
    return $output;
}