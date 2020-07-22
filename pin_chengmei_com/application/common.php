<?php
use think\Db;
use Aliyun\Core\Config;  
use Aliyun\Core\Profile\DefaultProfile;  
use Aliyun\Core\DefaultAcsClient;  
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use phpmailer\PHPMailer;
/**
 * 字符串截取，支持中文和其他编码
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
	if (function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif (function_exists('iconv_substr')) {
		$slice = iconv_substr($str, $start, $length, $charset);
		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("", array_slice($match[0], $start, $length));
	}
	return $suffix ? $slice . '...' : $slice;
}



/**
 * 读取配置
 * @return array 
 */
function load_config(){
    $list = Db::name('config')->select();
    $config = [];
    foreach ($list as $k => $v) {
        $config[trim($v['name'])]=$v['value'];
    }
    return $config;
}


/**
* 验证手机号是否正确
* @author honfei
* @param number $mobile
*/
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}


/** 
 * 阿里云云通信发送短息 
 * @param string $mobile    接收手机号 
 * @param string $tplCode   短信模板ID
 * @param array  $tplParam  短信内容
 * @return array 
 */  
function sendMsg($mobile,$tplCode,$tplParam){  
    if( empty($mobile) || empty($tplCode) ) return array('Message'=>'缺少参数','Code'=>'Error');  
    if(!isMobile($mobile)) return array('Message'=>'无效的手机号','Code'=>'Error');
    require_once EXTEND_PATH.'/aliyunsms/vendor/autoload.php';
    Config::load();             //加载区域结点配置   
    $accessKeyId = config('sms_user');
    $accessKeySecret = config('sms_pwd');
    if( empty($accessKeyId) || empty($accessKeySecret) ) return array('Message'=>'请先在后台配置appkey和appsecret','Code'=>'Error');
    $templateParam = $tplParam; //模板变量替换
	$signName = config('sms_sign');
    //短信模板ID 
    $templateCode = $tplCode;   
    //短信API产品名（短信产品名固定，无需修改）  
    $product = "Dysmsapi";  
    //短信API产品域名（接口地址固定，无需修改）  
    $domain = "dysmsapi.aliyuncs.com";  
    //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）  
    $region = "cn-hangzhou";     
    // 初始化用户Profile实例  
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);  
    // 增加服务结点  
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);  
    // 初始化AcsClient用于发起请求  
    $acsClient= new DefaultAcsClient($profile);  
    // 初始化SendSmsRequest实例用于设置发送短信的参数  
    $request = new SendSmsRequest();  
    // 必填，设置雉短信接收号码  
    $request->setPhoneNumbers($mobile);  
    // 必填，设置签名名称  
    $request->setSignName($signName);  
    // 必填，设置模板CODE  
    $request->setTemplateCode($templateCode);  
    // 可选，设置模板参数     
    if($templateParam) {
        $request->setTemplateParam(json_encode($templateParam));
    }
    //发起访问请求  
    $acsResponse = $acsClient->getAcsResponse($request);   
    //返回请求结果  
    $result = json_decode(json_encode($acsResponse),true); 

    return $result;  
}  



//生成网址的二维码 返回图片地址
function Qrcode($token, $url, $size = 8){
    $md5 = md5($token);
    $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
    $patch = 'qrcode/' . $dir;
    if (!file_exists($patch)){
        mkdir($patch, 0755, true);
    }
    $file = 'qrcode/' . $dir . $md5 . '.png';
    $fileName =  $file;
    if (!file_exists($fileName)) {
        $level = 'L';
        $data = $url;
        \org\QRcode::png($data, $fileName, $level, $size, 2, true);
    }
    return $file;
}


//生成取货二维码 返回图片地址
function pickUpCode($url, $size = 10){
    $dir = date('Ymd'). '/';
    $patch = 'qrcode/' . $dir;
    if (!file_exists($patch)){
        mkdir($patch, 0755, true);
    }
    $file = 'qrcode/' . $dir . $url . '.png';
    $fileName =  $file;
    if (!file_exists($fileName)) {
        $level = 'H';
        $data = $url;
        \org\QRcode::png($data, $fileName, $level, $size, 2, true);
    }
    $logo = 'static/logo.png';//准备好的logo图片
    $QR=$file;
    if ($logo !== FALSE) {
        $QR = imagecreatefromstring(file_get_contents($QR));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width/$logo_qr_width;
        $logo_qr_height = $logo_height/$scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
            $logo_qr_height, $logo_width, $logo_height);
    }
    imagepng($QR, $file);
    $qiniuFile=qiniu_upload($file);
    unlink($file);
    return  config('qiniu.image_url').'/'.$qiniuFile;
}


function qiniu_upload($filePath){
    $config =config('qiniu');
    $auth = new \Qiniu\Auth($config['ak'], $config['sk']);
    // 生成上传 Token
    $token = $auth->uploadToken($config['bucket']);
    $pathinfo = pathinfo($filePath);
    $ext = $pathinfo['extension'];
    $key = date('Y').'/'.date('m').'/'.date('d').'/'.substr(md5($filePath),0,5).date('YmdHis').mt_rand(0,9999).'.'.$ext;
    // 初始化UploadManager类
    $uploadMgr = new \Qiniu\Storage\UploadManager();
    list($ret,$err) = $uploadMgr->putFile($token,$key,$filePath);
    if($err !== null){
        return null;
    }else{
        return $key;
    }
}

//生成取货二维码 返回图片地址
//function pickUpCode123($url, $size = 10){
//    $dir = date('Ymd'). '/';
//    $patch = 'qrcode/' . $dir;
//    if (!file_exists($patch)){
//        mkdir($patch, 0755, true);
//    }
//    $file = 'qrcode/' . $dir . $url . '.png';
//    $fileName =  $file;
//    if (!file_exists($fileName)) {
//        $level = 'H';
//        $data = $url;
//        \org\QRcode::png($data, $fileName, $level, $size, 2, true);
//    }
//    $logo = 'static/logo.png';//准备好的logo图片
//    $QR=$file;
//     if ($logo !== FALSE) {
//       $QR = imagecreatefromstring(file_get_contents($QR));
//       $logo = imagecreatefromstring(file_get_contents($logo));
//       $QR_width = imagesx($QR);//二维码图片宽度
//       $QR_height = imagesy($QR);//二维码图片高度
//       $logo_width = imagesx($logo);//logo图片宽度
//       $logo_height = imagesy($logo);//logo图片高度
//       $logo_qr_width = $QR_width / 5;
//       $scale = $logo_width/$logo_qr_width;
//       $logo_qr_height = $logo_height/$scale;
//       $from_width = ($QR_width - $logo_qr_width) / 2;
//       //重新组合图片并调整大小
//       imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
//       $logo_qr_height, $logo_width, $logo_height);
//     }
//     imagepng($QR, $file);
//    return $file;
//}



/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name) {
    $result = false;
    if(is_dir($dir_name)){
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}



//时间格式化1
function formatTime($time) {
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int) ($t / (86400 * 30));
    if ($mon >= 1) {
        return '一个月前';
    }
    $day = (int) ($t / 86400);
    if ($day >= 1) {
        return $day . '天前';
    }
    $h = (int) ($t / 3600);
    if ($h >= 1) {
        return $h . '小时前';
    }
    $min = (int) ($t / 60);
    if ($min >= 1) {
        return $min . '分钟前';
    }
    return '刚刚';
}


//时间格式化2
function pincheTime($time) {
     $today  =  strtotime(date('Y-m-d')); //今天零点
      $here   =  (int)(($time - $today)/86400) ; 
      if($here==1){
          return '明天';  
      }
      if($here==2) {
          return '后天';  
      }
      if($here>=3 && $here<7){
          return $here.'天后';  
      }
      if($here>=7 && $here<30){
          return '一周后';  
      }
      if($here>=30 && $here<365){
          return '一个月后';  
      }
      if($here>=365){
          $r = (int)($here/365).'年后'; 
          return   $r;
      }
     return '今天';
}

//将秒转成天时分
 function dataFormat($time){
     $d = floor($time / (3600*24));
     $h = floor(($time % (3600*24)) / 3600);
     $m = floor((($time % (3600*24)) % 3600) / 60);
     if($d>'0'){
         return $d.'天'.$h.'小时'.$m.'分钟';
     }else{
         if($h!='0'){
             return $h.'小时'.$m.'分钟';
         }else{
             return $m.'分钟';
         }
     }
 }

 //计算友情等级
 function friendship($payTime,$pay_percent){
        $hour=floor(($payTime % (3600*24)) / 3600);
        $pay=dataFormat($payTime);
        if($hour<1){
            $str="您花了".$pay."帮好友完成美丽分享，超过全球".round($pay_percent,2)."%的好友，获得【大神好友】的称号！";
        }elseif ($hour>1 && $hour<=5){
            $str="您花了".$pay."帮好友完成美丽分享，超过全球".round($pay_percent,2)."%的好友，获得【地表最强好友】的称号！";
        }elseif ($hour>5 && $hour<=10){
            $str="您花了".$pay."帮好友完成美丽分享，超过全球".round($pay_percent,2)."%的好友，获得【铁杆好友】的称号！";
        }else{
            $str="您花了".$pay."帮好友完成美丽分享，超过全球".round($pay_percent,2)."%的好友，获得【助攻好友】的称号！";
        }
        return $str;
 }


/**
 * 计算两个时间戳之差
 * @param $begin_time
 * @param $end_time
 */
function timeDiff( $begin_time, $end_time ){
    $timeDiff = $end_time - $begin_time;
    if($timeDiff<0){
        $str = '0天0时0分0秒';
    }else{
        $days = intval( $timeDiff / 86400 );
        $remain = $timeDiff % 86400;
        $hours = intval( $remain / 3600 );
        $remain = $remain % 3600;
        $mins = intval( $remain / 60 );
        $secs = $remain % 60;
        $str = $days.'天'.$hours.'小时'.$mins."分".$secs.'秒';
    }

    return $str;
}

function getRandomString($len, $chars=null){
    if (is_null($chars)){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }  
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
        $str .= $chars[mt_rand(0, $lc)];  
    }
    return $str;
}


function random_str($length){
    //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
    $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
 
    $str = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++)
    {
        $rand = mt_rand(0, $arr_len-1);
        $str.=$arr[$rand];
    }
 
    return $str;
}

/**
 * 发送创蓝短消息
 * @param $sendArr
 * @param $modelId
 * @return mixed
 */
function sendMessage($mobile,$sendArr,$modelId){
    $code=json_encode($sendArr);
    $send['name'] =config('cl_sms_user');
    $send['pwd'] = config('cl_sms_pwd');
    $send['mobile'] = $mobile;
    $send['type'] = 1; //模板类型
    $send['template'] = $modelId; //模板id
    $send['code'] = $code;
    $str = '';
    ksort($send);
    foreach ($send as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    $str = substr($str, 0, -1);
    $key = md5($str);
    $str .= '&key=' . $key;
    $send['key'] = $key;
    $url = 'http://sms.qunarmei.com/sms.php?' . $str;
    $info=httpGet($url);
    return $info;
}

//输出日志
function logs($log,$name='default'){
    $logpath ="./logs/" .$name."/". date('Y-m-d') . "/".$name.".txt";
    if(!is_dir($logpath))
    {
        mkdirs(dirname($logpath));
    }
    file_put_contents($logpath,$log.PHP_EOL,FILE_APPEND);
}
/**
 * 递归创建目录树
 * @param string $path 目录树
 * @return bool
 */
function mkdirs($path) {
    if(!is_dir($path)) {
        mkdirs(dirname($path));
        mkdir($path);
    }
    return is_dir($path);
}

//将微信下载对账单转化成数组
function deal_WeChat_response($response){
    $result   = array();
    $response = str_replace(","," ",$response);
    $response = explode(PHP_EOL, $response);
    foreach ($response as $key=>$val){
        if(strpos($val, '`') !== false){
            $data = explode('`', $val);
            array_shift($data); // 删除第一个元素并下标从0开始
            if(count($data) == 24 || count($data) == 27){ // 处理账单数据
                $result['bill'][] = array(
                    'pay_time'             => $data[0], // 支付时间
                    'APP_ID'               => $data[1], // app_id
                    'MCH_ID'               => $data[2], // 商户id
                    'IMEI'                 => $data[4], // 设备号
                    'order_sn_wx'          => $data[5], // 微信订单号
                    'order_sn_sh'          => $data[6], // 商户订单号
                    'user_tag'             => $data[7], // 用户标识
                    'pay_type'             => $data[8], // 交易类型
                    'pay_status'           => $data[9], // 交易状态
                    'bank'                 => $data[10], // 付款银行
                    'money_type'           => $data[11], // 货币种类
                    'total_amount'         => $data[12], // 总金额
                    'coupon_amount'        => $data[13], // 代金券或立减优惠金额
                    'refund_number_wx'     => $data[14], // 微信退款单号
                    'refund_number_sh'     => $data[15], // 商户退款单号
                    'refund_amount'        => $data[16], // 退款金额
                    'coupon_refund_amount' => $data[17], // 代金券或立减优惠退款金额
                    'refund_type'          => $data[18], // 退款类型
                    'refund_status'        => $data[19], // 退款状态
                    'goods_name'           => $data[20], // 商品名称
                    'service_charge'       => $data[22], // 手续费
                    'rate'                 => $data[23], // 费率
                );
            }
            if(count($data) == 5 || count($data) == 7){ // 统计数据
                $result['summary'] = array(
                    'order_num'       => $data[0],    // 总交易单数
                    'turnover'        => $data[1],    // 总交易额
                    'refund_turnover' => $data[2],    // 总退款金额
                    'coupon_turnover' => $data[3],    // 总代金券或立减优惠退款金额
                    'rate_turnover'   => $data[4],    // 手续费总金额
                );
            }
        }
    }
    return $result;
}

//将奖券变化插入队列
function sendQueue($code,$desc,$flag=0){
    $arr['ticket_code']=$code;
    $arr['desc']=$desc;
    $arr['status']=$flag;
    $arr['insert_time']=date('Y-m-d H:i:s');
    \think\Queue::push('app\index\job\TicketLog', $arr, 'ticketLog');
}


//将奖券变化通知插入队列
function sendDrawQueue($arr){
    $arr['scene']='draw';
    $arr['insert_time']=time();
    \think\Queue::push( 'app\index\job\Message' , $arr,'message');
}

/**通用通知
 * @param $arr //短信内容
 * @param int $flag //通知标识 1 为 发站内信  2 为发短信  0为发站内性和短信
 */
function sendCommQueue($arr,$flag=1){
    $arr['scene']='comm';
    $arr['flag']=$flag;
    \think\Queue::push( 'app\index\job\Message' , $arr,'message');
}

//自动队列任务
function auto_worker($table_name,$insert_data,$scene){
    $arr['table_name']=$table_name;
    $arr['insert_data']=$insert_data;
    $arr['scene']=$scene;
    \think\Queue::push( 'app\index\job\MyQueue' , $arr,'my_queue');
}

//发站内信
function sendMessageToUser($data){
    Db::name('member_message')->insert($data);
}

function trimall($str){
    $qian=array(" ","　","\t","\n","\r");
    return str_replace($qian, '', $str);
}


function getNumber($tel){
    preg_match_all('/\d+/',$tel,$arr);
    $arr = join('',$arr[0]);
    return $arr;
}

/**
 * 将秒转换为 分:秒
 * s int 秒数
 */
function s_to_hs($s=0){
    //计算分钟
    //算法：将秒数除以60，然后下舍入，既得到分钟数
    $h    =    floor($s/60);
    //计算秒
    //算法：取得秒%60的余数，既得到秒数
    $s    =    $s%60;
    //如果只有一位数，前面增加一个0
    $h    =    (strlen($h)==1)?'0'.$h:$h;
    $s    =    (strlen($s)==1)?'0'.$s:$s;
    return $h.':'.$s;
}
/**
 * @commit: 生成订单号 28+标识符
 * @function: createOrderSn
 * @Param string $flag 平台标识符 或 支付渠道标识符
 * @Return string 30位
 * @author by stars<1014916675@qq.com>
 */
function createOrderSn($flag=''){
    $microtime = microtime(true);
    $flag_id_main = date('YmdHis').substr($microtime,(strpos($microtime,'.')+1)). mt_rand(100000000,999999999) ;
    //订单号码主体长度
    $flag_id_len = strlen($flag_id_main);
    $flag_id_sum = 0;
    for($i=0; $i<$flag_id_len; $i++){
        $flag_id_sum += (int)(substr($flag_id_main,$i,1));
    }
    //唯一号码（YYYYMMDDHHIISSNNNNNNNNCC）
    $flag_id = $flag_id_main . str_pad((100 - $flag_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
    if(strlen($flag) > 2){
        $flag = substr($flag,0,2);
    }
    return $flag .$flag_id;
}

/**
 * 获取access_token
 * @return string
 */
function getAccessToken()
{
    //判断是否过了缓存期
    $expire_time = config('web_expires');
    if($expire_time > time()){
        return  config('access_token');
    }
    $appid=config('wx_pay.appid');
    $appsecret=config('wx_pay.appsecret');
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
    $return = httpGet($url);
    if ($return === false) {
        return false;
    }
    $return=json_decode($return,true);
    $web_expires = time() + 7000; // 提前200秒过期
    Db::name('config')->where('name','web_expires')->setField('value', $web_expires);
    Db::name('config')->where('name','access_token')->setField('value', $return['access_token']);
    cache('db_config_data',null);
    return $return['access_token'];
}

//get方式获取
function httpGet($url) {
    $curl =curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}

/**
 * POST 请求
 * @param string $url
 * @param array $param
 * @param boolean $post_file 是否文件上传
 * @return string content
 */
function http_post($url,$param=[],$post_file=false,$arr_header = null){
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    if (is_string($param) || $post_file) {
        $strPOST = $param;
    } else {
        $aPOST = array();
        foreach($param as $key=>$val){
            $aPOST[] = $key."=".urlencode($val);
        }
        $strPOST =  join("&", $aPOST);
    }
    if(!empty($arr_header)){
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $arr_header);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_POST,true);
    curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}


/*
 * 检测用户是否有权限进入观看该直播间
 */
function check_user_live($mobile,$roomId)
{
    $allow = 1;//默认都有资格观看
    $userInfo = Db::table('ims_bj_shopn_member')->alias('m')->join('wx_user u', 'm.mobile=u.mobile')->where('m.mobile', $mobile)->field('m.id,m.storeid,m.isadmin,m.code,m.staffid,m.mobile,u.token')->find();
    if ($userInfo) {
        $storeid = $userInfo['storeid'];
        if ($userInfo['isadmin']) {
            $role = 1;//店老板
        }else {
            if ($userInfo['id'] == $userInfo['staffid'] || strlen($userInfo['code'])) {
                $role = 2;//美容师
            } else {
                $role = 3;//顾客
            }
        }
        $roomInfo = Db::name('wechat_live')->where(['roomid' => $roomId, 'live_show' => 1])->find();
        if ($roomInfo) {
            //观看对象是部分门店，查看该用户是否在允许门店内
            if ($roomInfo['live_object']) {
                if (!in_array($storeid, explode(',', $roomInfo['live_object_sign']))) {
                    $allow = 0;
                }
            }
            //检测角色是否允许观看
            $roleArr = explode(',', $roomInfo['live_role']);
            if (!in_array($role, $roleArr)) {
                $allow = 0;
            }
        }else{
            $allow = 0;
        }
    }else{
        $allow = 0;
    }
    return $allow;
}

//网上经典的计算中奖概率方法
function comm_getRand($proArr) {
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
 * 统邮件发送函数
 */
function send_mail($tomail, $name, $subject = '', $body = '', $attachment = null,$flag=0) {
    $mail=new PHPMailer();
    $mail->CharSet = 'UTF-8';           //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();                    // 设定使用SMTP服务
    $mail->SMTPDebug = 0;               // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
    $mail->SMTPAuth = true;             // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';          // 使用安全协议
    $mail->Host = "smtp.qq.com"; // SMTP 服务器
    $mail->Port = 465;                  // SMTP服务器的端口号
    $mail->Username = "451035207@qq.com";    // SMTP服务器用户名
    $mail->Password = "qszmgfvretnwbjca";     // SMTP服务器密码
    $mail->SetFrom('451035207@qq.com', 'houdianjing');
    $replyEmail = '';                   //留空则为发件人EMAIL
    $replyName = '';                    //回复名称（留空则为发件人名称）
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    if($flag==0){
        $mail->AddAddress($tomail, $name);
    }else{
        $maillist=explode(',',$tomail);
        foreach ($maillist as $val){
            $mail->AddAddress($val, $name);
        }
    }
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}


/**

 * excel表格导出
 * @param string $fileName 文件名称
 * @param array $headArr 表头名称
 * @param array $data 要导出的数据
 * @author static7  */
function comm_excelExport($fileName = '', $headArr = [], $data = [], $widths=[],$flag=0) {
    $fileName = iconv("UTF-8", "GB2312//IGNORE", @$fileName);
    $fileName .=".xlsx";
    \think\Loader::import('PHPExcel.PHPExcel');
    \think\Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->getProperties();
    $ordA = ord('A'); //65
    $key2 = ord("@"); //64
    foreach ($headArr as $v) {
        if($ordA > ord("Z"))
        {
            $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
        }else{
            $colum = chr($ordA++);
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
    }
    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
    foreach ($data as $key => $rows) { // 行写入
        $ordA = ord('A');//重新从A开始
        $key2 = ord("@"); //64
        foreach ($rows as $keyName => $value) { // 列写入
            if($ordA > ord("Z"))
            {
                $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
            }else{
                $colum = chr($ordA++);
            }
            $objActSheet->setCellValue($colum . $column, $value);
        }
        $column++;
    }
    //表格宽度
    if(count($widths)){
        $ordA = ord('A');//重新从A开始
        $key2 = ord("@"); //64
        foreach ($widths as  $value) { // 列写入
            if($ordA > ord("Z"))
            {
                $colum = chr(ord("A")).chr(++$key2);//超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
            }else{
                $colum = chr($ordA++);
            }
            $objActSheet->getColumnDimension($colum)->setWidth($value);
        }
    }
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    if($flag){
        $objWriter->save('./ExcelReport/'.$fileName);
    }else{
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
        header('Content-Disposition: attachment;filename="'.$fileName.'"');//告诉浏览器将输出文件的名称(文件下载)
        header('Cache-Control: max-age=0');//禁止缓存
        $objWriter->save('php://output');
    }
}