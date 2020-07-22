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

//活动发券操作2
function sendTicket($uid,$ticketType,$ticketImg='',$source=0,$prefix='activate_',$order_sn='',$num=1,$pid=''){
    try {
        $uidInfo = \think\Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.mobile,member.storeid,bwk.title,bwk.sign,depart.st_department')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->where('member.id', $uid)->find();
        $ticket_code = time() . $uid .$ticketType. rand(11, 99);
        $ticketList['depart'] = $uidInfo['st_department'];
        $ticketList['branch'] = $uidInfo['title'];
        $ticketList['sign'] = $uidInfo['sign'];
        $ticketList['mobile'] = $uidInfo['mobile'];
        $ticketList['storeid'] = $uidInfo['storeid'];
        $ticketList['insert_time'] = date('Y-m-d H:i:s');
        $ticketList['update_time'] = date('Y-m-d H:i:s');
        $ticketList['par_value'] = 0;
        $ticketList['status'] = 0;
        $ticketList['ticket_code'] = $ticket_code;
        $ticketList['type'] = $ticketType;
        $ticketList['source'] = $source;
        $ticketList['draw_pic'] = $ticketImg;
        $ticketList['order_sn'] = $order_sn;
        $ticketList['ticket_num'] = $num;
        if($pid){
            $codeCon=$prefix.$ticket_code.'_'.$pid;
            $ticketList['goods_id'] = $pid;
        }else{
            $codeCon=$prefix.$ticket_code;
        }
        $ticketList['qrcode'] = pickUpCode($codeCon);
        \think\Db::name('ticket_user')->insert($ticketList);
        //记录日志
        sendQueue($ticket_code, $ticket_code . '分配给' . $uidInfo['st_department'] . $uidInfo['title'] . $uidInfo['sign'] . '下的' . $uidInfo['mobile']);
        return true;
    }catch (\Exception $e){
        return false;
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


//根据经纬度返回地址信息
function getAddress($location){
    $gdUrl = "https://restapi.amap.com/v3/geocode/regeo?output=json&location=".$location."&key=".config('map_key');
    $gdSend = httpGet($gdUrl);
    $gdSend = json_decode($gdSend,true);
    $data=[];
    if($gdSend['status'] && $gdSend['info']=='OK'){
        $d=$gdSend['regeocode'];
        if(!is_array($d['formatted_address'])){
            $data['gps_address']=$d['formatted_address'];
            $data['country']=isset($d['addressComponent']['country'])?$d['addressComponent']['country']:'';
            $data['province']=isset($d['addressComponent']['province'])?$d['addressComponent']['province']:'';
            $data['district']=isset($d['addressComponent']['district'])?$d['addressComponent']['district']:'';
            $data['township']=isset($d['addressComponent']['township'])?$d['addressComponent']['township']:'';
            if(isset($d['addressComponent']['streetNumber']['street'])){
                $data['street']=is_array($d['addressComponent']['streetNumber']['street'])?'':$d['addressComponent']['streetNumber']['street'];
            }
            if(isset($d['addressComponent']['streetNumber']['location'])){
                $data['location']=is_array($d['addressComponent']['streetNumber']['location'])?'':$d['addressComponent']['streetNumber']['location'];
            }
        }
    }
    return $data;
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
