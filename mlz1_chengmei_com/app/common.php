<?php
// 应用公共文件
use think\facade\Db;
use think\facade\Filesystem;

if(!function_exists('isMobile')){
    function isMobile($mobile) {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }
}
if(!function_exists('isPWD')){
    /**
     * @commit: 验证密码 数字 字母组合
     * @function: isPWD
     * @param $value
     * @param int $minLen 6
     * @param int $maxLen 10
     * @return bool|int
     * @author by stars<1014916675@qq.com>
     * @CreateTime 2017-09-22 18:04
     */
    function isPWD($value,$minLen = 6,$maxLen = 10){
        $match='/^[\\~!@#$%^&*()-_=+|{},.?\/:;\'\"\d\w]{'.$minLen.','.$maxLen.'}$/';
        $v = trim($value);
        if(empty($v)) return false;
        return preg_match($match,$v);
    }
}
if(!function_exists('sendMessage')) {
    /**
     * 发送创蓝短消息
     * @param $sendArr
     * @param $modelId
     * @return mixed
     */
    function sendMessage($mobile, $sendArr, $modelId)
    {
        $code = json_encode($sendArr);
        $send['name'] = config('sms.cl_sms_user');
        $send['pwd'] = config('sms.cl_sms_pwd');
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
        $curl =curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}
if(!function_exists('validateIdCard')){
    /**
     * @commit:
     * @function: validateIdCard
     * @param $idCard 身份证
     * @return bool
     * @author by stars<1014916675@qq.com>
     * @CreateTime 2017-09-24 14:05
     * @descript
     * 身份证15位编码规则：dddddd yymmdd xx p
     * dddddd：6位地区编码
     * yymmdd: 出生年(两位年)月日，如：910215
     * xx: 顺序编码，系统产生，无法确定
     * p: 性别，奇数为男，偶数为女
     *
     * 身份证18位编码规则：dddddd yyyymmdd xxx y
     * dddddd：6位地区编码
     * yyyymmdd: 出生年(四位年)月日，如：19910215
     * xxx：顺序编码，系统产生，无法确定，奇数为男，偶数为女
     * y: 校验码，该位数值可通过前17位计算获得
     *
     * 前17位号码加权因子为 Wi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2 ]
     * 验证位 Y = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ]
     * 如果验证码恰好是10，为了保证身份证是十八位，那么第十八位将用X来代替
     * 校验位计算公式：Y_P = mod( ∑(Ai×Wi),11 )
     * i为身份证号码1...17 位; Y_P为校验码Y所在校验码数组位置
     */
    function validateIdCard($idCard){
        //15位和18位身份证号码的正则表达式
        $regIdCard='/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/';

        //如果通过该验证，说明身份证格式正确，但准确性还需计算
        if(preg_match($regIdCard,$idCard)){
            if(strlen($idCard) == 18){
                $idCardWi=array( 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2 ); //将前17位加权因子保存在数组里
                $idCardY=array( 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ); //这是除以11后，可能产生的11位余数、验证码，也保存成数组
                $idCardWiSum=0; //用来保存前17位各自乖以加权因子后的总和
                for($i=0;$i<17;$i++){
                    $idCardWiSum+=substr($idCard,$i,1)*$idCardWi[$i];
                }
                $idCardMod=$idCardWiSum%11;//计算出校验码所在数组的位置
                $idCardLast=substr($idCard,17);//得到最后一位身份证号码
                //如果等于2，则说明校验码是10，身份证号码最后一位应该是X
                if($idCardMod==2){
                    if($idCardLast=="X" || $idCardLast=="x"){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    //用计算出的验证码与最后一位身份证号码匹配，如果一致，说明通过，否则是无效的身份证号码
                    if($idCardLast == $idCardY[$idCardMod]){
                        return true;
                    }else{
                        return false;
                    }
                }
            }
        }else{
            return false;
        }
    }
}
if(!function_exists('detect_sensitive_word')){
    /**
     * Commit: 检测关键词是否替换
     * Function: filter_sensitive_word
     * @Param $word 关键词
     * @Param bool $tips 是否替换
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-03 15:56:33
     * @Return bool|mixed|string
     */
    function filter_sensitive_word($word,$tips = false){
        $keywords = Db::name('cm_sensitiveword')->cache(true,86400)->column('name');
        if(empty($keywords)){
            return true;
        }
        //$word = strip_tags($word);

        //检测是否含有关键词
        foreach ($keywords as $k=>$val){
            $blacklist="/{$val}/i";
            if(preg_match($blacklist, $word)){
                if($tips){
                    $word =  str_replace("{$val}",'***',$word);
                }
            }
        }
        return $word;
    }
}
if(!function_exists('wechatShare')){
    /**
     * Commit: 微信分享
     * Function: wechatShare
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 17:45:51
     * @Return array
     */
    function wechatShare(){
        ini_set("user_agent","Mozilla/4.0 (compatible; MSIE 5.00; Windows 98)");
        $appid     = config('wechat.appId');
        $appsceret = config('wechat.appSecret');
        $wechat    = new \starsutil\WeChatJSSDK($appid, $appsceret);

        return $wechat->getSignPackage();
    }
}
if(!function_exists('stat_data_range')){
    /**
     * Commit: 获取一段时间内的日期集合
     * Function: stat_date_range
     * @Param $start
     * @Param $end
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-18 11:38:06
     * @Return array
     */
    function stat_date_range($start, $end) {
        $result = array();
        if (empty($start) || empty($end)) {
            return $result;
        }
        $start = strtotime($start);
        $end = strtotime($end);
        $i = 0;
        while(strtotime(end($result)) < $end) {
            $result[] = date('Y-m-d', $start + $i * 86400);
            $i++;
        }
        return $result;
    }
}
if(!function_exists('getImageUrlInContent')){
    /**
     * Commit: 获取字符串中的图片地址
     * Function: getImageUrlInContent
     * @Param string $string
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-18 13:30:24
     * @Return array|string
     */
    function getImageUrlInContent($string = ''){
        if(empty($string)){
            return [];
        }
        preg_match_all ('/<img.*src=[\'"](.*\.(?:png|jpg|jpeg|jpe|gif).*)[\'"].*\/?>/iU', $string, $match);
        preg_match_all ('/url\([\'|\"](.*\.(?:png|jpg|jpeg|jpe|gif).*)[\'|\"]\)/iU', $string, $match1);
        if(empty($match)) return [];
        $images = [];
        if(!empty($match['1'])){
            $img_match = $match['1'];
            if(!empty($match2['1'])){
                $img_match = array_unique(array_merge($img_match, $match2['1']));
            }
            foreach ($img_match as $img){
                if (
                    (strexists ($img, 'http://') || strexists ($img, 'https://')) &&
                    !strexists ($img, 'mmbiz.qlogo.cn') &&
                    !strexists ($img, 'mmbiz.qpic.cn') &&
                    strexists ($img, 'statics.xiumi.us')
                ) {
                    $images[] = $img;
                }
            }
        }
        return $images;
    }
}
if(!function_exists('getContentFirstPicture')){
    /**
     * Commit: 获取内容中第一张图片
     * Function: getContentFirstPicture
     * @Param string $string
     * @Param bool $flag 是否上传七牛
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-18 13:30:24
     * @Return array|string
     */
    function getContentFirstPicture($string = '',$flag = true){
        if(empty($string)){
            return false;
        }
        preg_match_all ('/<img.*src=[\'"](.*\.(?:png|jpg|jpeg|jpe|gif).*)[\'"].*\/?>/iU', $string, $match);
        if(empty($match)) return false;
        if(!empty($match['1'])){
            return $match['1']['0'];
        }
        return '';
    }
}
if(!function_exists('send_dignding_link_message')){
    /**
     * 发送钉钉消息 链接消息
     * @param $mobiles 	手机号集合 ['1','2','3',......]
     * @param $message 消息内容
     * @param $title 消息内容 消息标题
     * @param $messageUrl 消息内容 消息点击链接地址
     * @param $picUrl 消息内容 图片媒体文件链接
     * @return bool
     */
    function send_dignding_link_message($mobiles,$message,$title,$messageUrl,$picUrl) {
        $ini = config('ini.dtalk');
        $postUrl = $ini['ding_send_message_url'];
        if(empty($mobiles)){
            $result = ['msg'=>'error','obj'=>'手机号不能为空','status'=>'0'];
        }else{
            $message = $message ?: "您有新的“原创内容”文章需要审核，请尽快进行相关操作！";
            if(!is_array($mobiles) && is_string($mobiles)){
                $mobiles = [$mobiles];
            }
            $sendData['mobiles'] = $mobiles;
            $sendData['type'] = 2;
            $sendData['content'] = $message;
            $sendData['messageUrl'] = $messageUrl;
            $sendData['picUrl'] = $picUrl;
            $sendData['title'] = $title;
            $result = curlPost($postUrl,($sendData),true);
        }

        dtalk_log($postUrl,$sendData,$result);

        return $result;
    }
}
if(!function_exists('dtalk_log')){
    /**
     * @commit: 添加钉钉日志
     * @function: dtalk_log
     * @param $url  请求地址
     * @param $send 参数
     * @param $data 返回值
     * @author: stars<1014916675@qq.com>
     * @createTime ct
     */
    function dtalk_log($url,$send,$data){
        $ini = config('ini.dtalk');
        $filename = './dtalk/'. date('Ymd').'/'.$ini['dtalk_log'];
        if(!is_dir($filename)) {
            mkdirs(dirname($filename));
        }
        $content = "\n---------------------------------------------\n ";
        $content .= '| '.date('Y-m-d H:i:s') . "  | ";
        $content .= "\n---------------------------------------------\n ";
        $content .= " | 请求链接:\n------------\n";
        $content .= $url." \n------------\n";
        $content .= " | 参数:\n------------\n";
        $content .= var_export($send, true)."  \n------------\n";
        $content .= " | 返回值:\n------------\n";
        $content .= var_export($data, true)."  \n \n".PHP_EOL;
        file_put_contents($filename,$content,FILE_APPEND);
    }
}
if(!function_exists('curlPost')) {
    /**
     * commit:
     * function: curlPost
     * @param $url 地址
     * @param $postFields 参数
     * @param bool $json 提交方式  json   form
     * @return mixed|string
     * user: stars<1014916675@qq.com>
     */
    function curlPost($url, $postFields, $json = false)
    {
        if ($json) {
            $postFields = json_encode($postFields);
            $application = 'Content-Type: application/json; charset=utf-8';
        } else {
            $postFields = http_build_query($postFields);
            $application = 'Content-Type: application/x-www-form-urlencoded;charset=utf-8';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $application
            )
        );
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //若果报错 name lookup timed out 报错时添加这一行代码
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($ch);
        if (false == $ret) {
            $result = curl_error($ch);
        } else {
            $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = "请求状态 " . $rsp . " " . curl_error($ch);
            } else {
                $result = json_decode($ret, true);
            }
        }
        curl_close($ch);
        return $result;

    }
}
if(!function_exists('sendMessage')) {
    /**
     * Commit: 发送短信
     * Function: sendMessage
     * @Param $mobile 手机号
     * @Param $sendArr 模板中的变量 ['seller_name'=>$seller_name,'number'=>$room_num]
     * @Param $modelId 模板id
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-11 11:48:26
     * @Return bool|string
     */
    function sendMessage($mobile, $sendArr = [], $modelId)
    {
        load()->func('sms');
        $ini = config('ini.sms');
        $code = json_encode($sendArr);
        $send['name'] = $ini['cl_sms_user'];
        $send['pwd'] = $ini['cl_sms_pwd'];
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
        $info = httpGet1($url);

        sms_log($url, $send, $info);

        return $info;
    }
}
if(!function_exists('sms_log')) {
    function sms_log($url, $send, $data)
    {
        $ini = config('ini.sms');
        $filename = './sms/' . date('Ymd') . '/' . $ini['sms_log'];
        load()->func('file');
        if(!is_dir($filename)) {
            mkdirs(dirname($filename));
        }
        $content = "\n---------------------------------------------\n";
        $content .= '| ' . date('Y-m-d H:i:s') . "  |";
        $content .= "\n---------------------------------------------\n";
        $content .= " | 请求链接:\n------------\n";
        $content .= $url . " \n------------\n";
        $content .= " | 参数:\n------------\n";
        $content .= var_export($send, true) . "  \n------------\n";
        $content .= " | 返回值:\n------------\n";
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        $content .= var_export($data, true) . "  \n \n" . PHP_EOL;
        file_put_contents($filename, $content, FILE_APPEND);
    }
}
if(!function_exists('httpGet1')) {
    function httpGet1($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}
if(!function_exists('getID3')){
    function getID3($filepath){
        require_once('../extend/getid3/getid3.php');
        $getID3 = new \getID3;
        $file = $getID3->analyze($filepath);
        return $file ? $file : '';
    }
}
if(!function_exists('mkdirs')) {
    /**
     * 递归创建目录树
     * @param string $path 目录树
     * @return bool
     */
    function mkdirs($path)
    {
        if (!is_dir($path)) {
            mkdirs(dirname($path));
            mkdir($path);
        }
        return is_dir($path);
    }
}
if(!function_exists('getCheckerNumber')) {
    /**
     * commit: 获取审核人的总人数
     * function: getCheckerNumber
     * @param int $type
     * @return int
     * user: stars<1014916675@qq.com>
     */
    function getCheckerNumber($type = 0, $data = [])
    {
        if(empty($data)){
            $data = Db::name('cm_article_config_auditor')
                ->field('level,is_way,flag,type,count(level) count')
                ->where('type', '=', $type)
                ->group('level')
                ->select();
        }

        if (empty($data)) return 0;

        $i = 0;
        foreach ($data as $k => $v) {
            if ($v['is_way'] == 1) {//会签
                $i += $v['count'];
            } else {
                $i += 1;
            }
        }
        return $i;
    }
}

function changeTimeType($seconds){
    if ($seconds>3600){
        $hours = intval($seconds/3600);
        $time = $hours.":".gmstrftime('%M:%S', $seconds);
    }else{
        $time = gmstrftime('%M:%S', $seconds);
    }
    return $time;
}
function strexists($string, $find) {
    return !(strpos($string, $find) === FALSE);
}
/**
 * Commit: 获取七牛token
 * Function: getQiNiuToken
 * Author: stars<1014916675@qq.com>
 * DateTime: 2019-12-18 14:05:41
 * @Return string
 */
function getQiNiuToken(){
    $bucket = Filesystem::getDiskConfig('qiniu','bucket');
    $accessKey = Filesystem::getDiskConfig('qiniu','accessKey');
    $secretKey = Filesystem::getDiskConfig('qiniu','secretKey');
    $qiniuAuth = new \Qiniu\Auth($accessKey,$secretKey);
    $upToken = $qiniuAuth->uploadToken($bucket);
    return $upToken;
}
/**
 * Commit: 七牛获取远程图片
 * Function: qiNiuUploadFile
 * Author: stars<1014916675@qq.com>
 * DateTime: 2019-12-18 14:05:41
 * @Return string
 */
function qiNiuUploadFile($file = '',$dir = 'article'){
    if(empty($file)){
        return '';
    }
    $bucket    = Filesystem::getDiskConfig('qiniu','bucket');
    $accessKey = Filesystem::getDiskConfig('qiniu','accessKey');
    $secretKey = Filesystem::getDiskConfig('qiniu','secretKey');
    $domain = Filesystem::getDiskConfig('qiniu','domain');
    $qiniuAuth = new \Qiniu\Auth($accessKey,$secretKey);
    $config = new \Qiniu\Config();
    $bucketManager = new \Qiniu\Storage\BucketManager($qiniuAuth);
    $filename = $dir.'/'.date('Ymd').'/'.date('YmdHis').random_int(1000, 9999999999).'.jpg';
    $item = $bucketManager->fetch($file, $bucket, $filename);
    if($item !== null){
        return $domain.'/'.$filename;
    }else{
        return $item;
    }
}
//外链图片需要上传七牛时判断的字符
function needToConvertCharacter(){
    return [
        '.baidu.',
        '.mmbiz.',
        '.qpic.',
        '.weixin.',
        '.qq.',
        '.xiumi.',
        '.sohucs.',
    ];
}




