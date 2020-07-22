<?php
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
 function http_post($url,$param,$post_file=false){
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


/**
 * @param int $flag
 * @param int $price
 * @param int $max_num
 * @param int $pay_money
 * @param string $fenqi_mun
 * 返回支付工具列表
 */
function getBank($flag=0,$only_pay=0,$fenqi_mun=''){
    //是否分期 总额 最多拼团人数 拼团人支付金额
    if($flag){
        $fenqiIds=explode(',',$fenqi_mun);
        $fenqi=\think\Db::name('bank')->where(['id_status'=>1])->field('id_bank,st_abbre_bankname,st_bnkpic1,is_period')->order('no_displayorder')->select();
        foreach ($fenqi as $k=>$v){
            if($v['is_period']){
                $sonlist=\think\Db::name('bank_interestrate')->where('id_bank',$v['id_bank'])->field('id,no_period')->order('orderby')->select();
                foreach ($sonlist as $kk=>$vv){
                    if(!in_array($vv['id'],$fenqiIds)){
                        unset($sonlist[$kk]);
                    }else{
                        $pay=number_format($only_pay/$vv['no_period'], 2,'.','');
                        $sonlist[$kk]['text']="分".$vv['no_period']."期(0手续费)";
                        $sonlist[$kk]['money']="￥".$pay."/期";
                    }
                }
                if(count($sonlist)==0){
                    unset($fenqi[$k]);
                }else{
                    $fenqi[$k]['period']= $sonlist;
                }
            }
        }
    }else{
        $fenqi=\think\Db::name('bank')->where(['id_bank'=>2,'id_status'=>1])->field('id_bank,st_abbre_bankname,st_bnkpic1,is_period')->select();
    }
    return array_values($fenqi);
}

//传入单个分期银行
function getBankOnly($flag=0,$bankId=0,$only_pay=0){
    //是否分期 总额 最多拼团人数 拼团人支付金额
    if($flag){
        $fenqi=\think\Db::name('bank')->where(['id_bank'=>$bankId])->field('id_bank,st_abbre_bankname,st_bnkpic1')->find();
        $sonlist=\think\Db::name('bank_interestrate')->where('id_bank',$bankId)->field('id,no_period')->order('orderby')->select();
            foreach ($sonlist as $kk=>$vv){
                $pay=number_format($only_pay/$vv['no_period'], 2,'.','');
                $sonlist[$kk]['text']="分".$vv['no_period']."期(0手续费)";
                $sonlist[$kk]['money']="￥".$pay."/期";
            }
        $fenqi['paried']= $sonlist;
    }else{
        $fenqi=\think\Db::name('bank')->where(['id_bank'=>2,'id_status'=>1])->field('id_bank,st_abbre_bankname,st_bnkpic1,is_period')->find();
    }
    return $fenqi;
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
function getAccessToken()
{
    //判断是否过了缓存期
    $expire_time = config('qmjk_web_expires');
    if($expire_time > time()){
        return  config('qmjk_access_token');
    }
    $appid=config('wx_qmjk.appid');
    $appsecret=config('wx_qmjk.appsecret');
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
    $return = httpGet($url);
    if ($return === false) {
        return false;
    }
    $return=json_decode($return,true);
    $web_expires = time() + 7000; // 提前200秒过期
    \think\Db::name('config')->where('name','qmjk_web_expires')->setField('value', $web_expires);
    \think\Db::name('config')->where('name','qmjk_access_token')->setField('value', $return['access_token']);
    cache('db_config_data',null);
    return $return['access_token'];
}

/**
 *
 * @param $touser
 * @param $template_id
 * @param $page
 * @param $form_id
 * @param $data
 * @param $emphasis_keyword
 * @return Ambigous <boolean, multitype:>
 * 小程序发送模板消息
 */
function send_weapp_msg($touser,$template_id,$page,$form_id,$data,$emphasis_keyword=NULL) {
    $send['touser'] = $touser;
    $send['template_id'] = $template_id;
    $send['page'] = $page;
    $send['form_id'] = $form_id;
    if($emphasis_keyword) {
        $send['emphasis_keyword'] = $emphasis_keyword;
    }
    $send['data'] = $data;
    $res = sendTemplateMessage($send);
//    if(!$res) {
//        return false;
//    }
    return $res;
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

/*
 * 发券
 */
function insertTicket($ticketType,$userInfo,$getLastDay,$step,$source=0){
    $map['type'] = array('eq', $ticketType);
    $map['flag'] = array('eq', 0);
    $getTicket = \think\Db::name('ticket')->where($map)->field('id,ticket')->order('id')->limit($step)->select();
    if (count($getTicket) && is_array($getTicket)) {
        //将奖券插入卡券表
        $ticketList = array();
        $ticketIds = array();
        foreach ($getTicket as $kk => $vv) {
            $ticketList[$kk]['depart'] = $userInfo['st_department'];
            $ticketList[$kk]['branch'] = $userInfo['title'];
            $ticketList[$kk]['sign'] = $userInfo['sign'];
            $ticketList[$kk]['mobile'] = $userInfo['mobile'];
            $ticketList[$kk]['par_value'] = 0;
            $ticketList[$kk]['insert_time'] = date('Y-m-d H:i:s');
            $ticketList[$kk]['update_time'] = date('Y-m-d H:i:s');
            $ticketList[$kk]['ticket_code'] = $vv['ticket'];
            $ticketList[$kk]['type'] = $ticketType;
            $ticketList[$kk]['storeid'] = $userInfo['storeid'];
            if ($ticketType == 6) {
                $ticket_pic = config('queen_day_pic.3');
            } elseif ($ticketType == 7) {
                $ticket_pic = config('queen_day_pic.5');
            }else{
                $ticket_pic = config('queen_day_pic.0');
            }
            $ticketList[$kk]['aead_time'] = strtotime($getLastDay);
            $ticketList[$kk]['draw_pic'] = $ticket_pic;
            $ticketList[$kk]['source'] = $source;
            $ticketList[$kk]['qrcode'] = pickUpCode('lottery_'.$vv['ticket']);
            $ticketIds[] = $vv['id'];
            //记录日志
            sendQueue($vv['ticket'], $vv['ticket'] . '分配给' . $userInfo['storeid'] . $userInfo['sign'] . '下的' . $userInfo['mobile']);
        }
        \think\Db::name('ticket_user')->insertAll($ticketList);
        \think\Db::name('ticket')->where('id', 'in', $ticketIds)->update(['flag' => 1]);
    }
}

function https_request($url,$data = null){
    if(function_exists('curl_init')){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }else{
        return false;
    }
}

// 获取带参数的二维码
// 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制。
function getWXACodeUnlimit($path='',$scene,$width=430,$auto_color,$line_color,$is_hyaline){
    $access_token=getAccessToken();
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




