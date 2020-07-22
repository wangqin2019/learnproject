<?php
namespace app\blink\controller;
use app\blink\model\MemberModel;
use think\Controller;
use think\Db;
use weixinaes\wxBizDataCrypt;

/**
 * swagger: 用户中心
 */
class User extends Base
{
    //通过code获取用户openid及session_key及生成用户token
    public function getToken(){
        $appid = config('wx_blink_pay.appid');
        $appsecret = config('wx_blink_pay.appsecret');
        $js_code = input('param.code');
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$js_code."&grant_type=authorization_code";
        $mobile='';
        try {
            $info = httpGet($url);
            $info = json_decode($info, true);
            logs(date('Y-m-d H:i:s').':'.json_encode($info),'getToken');
            $userInfo = Db::name('blink_wx_user')->where('open_id', $info['openid'])->find();
            $token = createToken();
            if (is_array($userInfo) && count($userInfo)) {
                $time_out=strtotime("+1 days");
                Db::name('blink_wx_user')
                    ->where('open_id', $info['openid'])
                    ->update([
                        'session_key' => $info['session_key'],
                        'token'=>$token,
                        'time_out'=>$time_out
                    ]);
                $mobile=$userInfo['mobile'];
            } else {
                $time_out=strtotime("+1 days");
                Db::name('blink_wx_user')
                    ->insert([
                        'open_id' => $info['openid'],
                        'session_key' => $info['session_key'],
                        'token'=>$token,
                        'time_out'=>$time_out
                    ]);
            }
            $code = 1;
            $data = ['token'=>$token,'mobile'=>$mobile];
            $msg = '数据获取成功';
        }catch (\Exception $e){
            $code = 0;
            $data = ['wx'=>config('wx_blink_pay'),'url'=>$url];
            $msg = '数据获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }
    /**
     * 获取用户电话号码
     */
    public function getMobile(){
        $type = input('param.type',0);
        //type等于0检测在系统中是否存在 判断角色 不存在禁止登陆
        //type等于1 为通过美容师分享的拼购连接进入客户
        //type等于2 为通过老顾客分享的拼购连接进入客户
        //type等于3 为通过美容师分享的奖券连接进入客户
        //type等于4 为通过老顾客分享的奖券连接进入客户
        //type等于5 为通过老顾客分享的圣诞连接进入客户
        //type等于6 为通过老顾客分享的圣诞连接进入客户
        //type等于7 为通过美容师分享的新年活动连接进入客户
        //type等于8 为通过老顾客分享的新年活动连接进入客户
        //type等于9 为通过老顾客分享的盲盒活动连接进入客户
        $token = input('param.token');
        $mobile = input('param.mobile','');
        //检测通ken
        if(!parent::checkToken($token)){
            return parent::returnMsg(400,'','用户登陆信息过期，请重新登录');
        }
        if(strlen($mobile) != 11){
            $iv = input('param.iv');
            $encryptedData = input('param.encryptedData');
            if ($iv != '' || $encryptedData != '') {
                $getSession = Db::name('blink_wx_user')->where('token', $token)->value('session_key');
                if ($getSession) {
                    $aes = new wxBizDataCrypt(config('wx_blink_pay.appid'), $getSession);
                    $errCode = $aes->decryptData($encryptedData, $iv, $data1);
                    logs(date('Y-m-d H:i:s').' 85 :'.json_encode($data1),'getMobile');
                    if ($errCode == 0) {
                        $data1 = json_decode($data1, true);
                        Db::name('blink_wx_user')
                            ->where('token', $token)
                            ->update(['mobile' => $data1['phoneNumber']]);
                        $userMobile = $data1['phoneNumber'];
                    }else{
                        return parent::returnMsg(0,'','微信电话号码获取失败');
                    }
                } else {
                    return parent::returnMsg(0,'','数据获取失败');
                }
            } else {
                return parent::returnMsg(0,'','请检查参数,iv和encryptedData不能为空！');
            }
        }else{
            $userMobile = $mobile;
        }

        if(empty($userMobile)){
            return parent::returnMsg(0,'','数据获取失败');
        }
        //查询当前用户所属门店及信息
        $memberInfo = Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
            ->where('m.mobile',$userMobile)
            ->field('m.storeid,m.activity_flag,m.staffid,m.storeid,m.id,m.pid,m.code,m.id_regsource,m.isadmin,u.is_agree,bwk.sign,bwk.title')
            ->find();



        if(empty($memberInfo)){
            //新客
            if($type){
                $res['flag'] = $type;
            }else{
                $res['flag'] = -1;
            }
            $res['mobile'] = $userMobile;
            $res['auth'] = true;
            $res['desc'] = '未注册用户!';

            logs(date('Y-m-d H:i:s').' getMobile 用户不存在返回 :'.json_encode($res),'getMobile');
            return parent::returnMsg(1,$res,'未注册用户!');
        }else{
            if($type){
                $data['flag'] = $type;
            }else{
                $data['flag'] = 1;
            }
        }
        if($memberInfo['storeid'] == 1792){
            //检测当前用户的推广人是否是美容师
            $pp = $memberInfo['pid'] ?:$memberInfo['staffid'];
            /*$aaa = Db::table('ims_bj_shopn_member')->where('id',$memberInfo['pid'])->find();
            if($aaa['id'] == $aaa['staffid'] && strlen($aaa['code'])>0){
                $userMobile = $aaa['mobile'];
            }else{*/
            $userMobile = Db::table('ims_bj_shopn_member')->where('id',$pp)->value('mobile');
            /*}*/
        }
        //检测当前用户所在门店是否开通
        $res = Db::table('ims_bwk_branch')->alias('bwk')
            ->join(['pt_blink_box_store'=>'s'],'bwk.id=s.storeid')
            ->field('bwk.*,s.status as auth_status')
            ->where('bwk.id',$memberInfo['storeid'])
            ->find();
        if(!empty($res) && $res['is_blink'] == 1){
            $data['mobile'] = $userMobile;
            $data['uid'] = $memberInfo['id'];
            $data['activity_flag'] = $memberInfo['activity_flag'];
            $data['is_blink'] = $res['is_blink'];
            $data['is_agree'] = $memberInfo['is_agree'];//是否同意协议
            $data['status'] = $res['auth_status'];//使用人群
            //missshop顾客
            if(in_array($memberInfo['activity_flag'],[2200,8805,8806,8807,8808,8809,8810,8811,8812,8888])){
                if($res['auth_status'] == 2){
                    $data['auth'] = true;
                }else{
                    $data['auth'] = false;
                }
            }
            //非missshop顾客
            if(!in_array($memberInfo['activity_flag'],[2200,8805,8806,8807,8808,8809,8810,8811,8812,8888])){
                if($res['auth_status']==1){
                    $data['auth'] = true;
                }else{
                    $data['auth'] = false;
                }
            }

            if($res['auth_status']==1){
                $a = '非missshop顾客';
            }elseif($res['auth_status'] == 2){
                $a = 'missshop顾客';
            }elseif($res['auth_status'] == 3){
                $a = '全部顾客';
                $data['auth'] = true;
            }
            $data['auth_comment'] = $a;//使用人群
            return parent::returnMsg(1,$data,'数据获取成功');
        }else{
            $res['flag'] = -1;
            $res['mobile'] = $userMobile;
            $res['desc'] = '系统中有该用户 但所属门店没开通活动';
            logs(date('Y-m-d H:i:s').' 用户门店未开通 :'.json_encode($res),'getMobile0');
            return parent::returnMsg(1,$res,'所属门店没开通活动');
        }
    }
    /**
     * 维护信息
     */
    public function updateUserInfo() {
        $token=input('param.token');
        if(parent::checkToken($token)) {
            $type = input('param.type', 0);//接收进入渠道  9999
            $fid = input('param.fid', 0);//如果是新增 需将引导人id传入
            $nickName = input('param.nickName', '');
            $avatar = input('param.avatar', '');
            try{
                logs(date('Y-m-d H:i:s').' updateUserInfo参数 :'.json_encode(input('param.')),'updateUserInfo');
                $wxUserInfo = Db::name('blink_wx_user')->where('token', $token)->find();
                //如果wx_user表中已经存在过这个电话号码 即该电话有两条记录 就说明换过小程序id 要删除原来的记录
                $mobileCount = Db::name('blink_wx_user')->where('mobile',$wxUserInfo['mobile'])->count();
                if($mobileCount > 1){
                    Db::name('blink_wx_user')
                        ->where('mobile',$wxUserInfo['mobile'])
                        ->limit(1)
                        ->order('id asc')
                        ->delete();
                }
                Db::name('blink_wx_user')->where('token', $token)->update([
                    'nickname' => $nickName,
                    'avatar'   => $avatar
                ]);
                $memberInfo = Db::table('ims_bj_shopn_member')->where('mobile',$wxUserInfo['mobile'])->find();
                logs(date('Y-m-d H:i:s').' updateUserInfo 手机号用户信息 :'.json_encode($memberInfo),'updateUserInfo');
                //用户不存在需要注册建立关系
                if(empty($memberInfo)){
                    //检测邀请人是美容师还是老顾客
                    $uinfo = Db::table('ims_bj_shopn_member')
                        ->alias('m')
                        ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                        ->field('m.*,bwk.title,bwk.sign')
                        ->where('m.id',$fid)
                        ->find();
                    logs(date('Y-m-d H:i:s').' updateUserInfo 美容师信息 :'.json_encode($uinfo),'updateUserInfo');
                    logs(date('Y-m-d H:i:s').' 检测邀请人是美容师还是老顾客 :'.Db::table('ims_bj_shopn_member')->getLastSql(),'updateUserInfo');

                    if(strlen($uinfo['code']) > 1 && $uinfo['id'] == $uinfo['staffid']){//美容师
                        $sellerId = $fid;
                    }else{
                        $sellerId = $uinfo['staffid'];
                    }
                    $s = $uinfo['storeid'];
                    //检测当前用户的引领人所属门店 及 当前用户是否是内部员工
                    if($uinfo['sign'] == '666-666' || $uinfo['sign'] == '888-888' || $uinfo['sign'] == '000-000'){
                        //判断是否是内部员工 不是内部员工 进入的用户美容师强制为 148289   门店 1792
                        $f_mobile = self::$redis->exists('staff_' . $wxUserInfo['mobile']);
                        if(!$f_mobile){//新用户不是内部员工
                            $originfid = $sellerId;//引领人原始美容师
                            $sellerId = 148289;//虚拟门店美容师
                            $s = 1792;//门店
                        }else{
                            $originfid = $sellerId;
                        }
                    }else{
                        //虚拟门店中客户A分享进入的客户B,客户B的pid为客户A的ID 美容师为148289 原始美容师ID为A是原始美容师ID
                        if($s == 1792){
                            $originfid = $uinfo['originfid'];
                        }else{
                            $originfid = $sellerId;
                        }
                    }

                    //插入用户 创建层级关系
                    //先插入member表 再插入fans表
                    $memData = array(
                        'weid'          => 1,
                        'storeid'       => $s,
                        'pid'           => $fid,
                        'staffid'       => $sellerId,
                        'originfid'     => $originfid,//原始美容师 1792门店用
                        'realname'      => $nickName,
                        'mobile'        => $wxUserInfo['mobile'],
                        'createtime'    => time(),
                        'level'         => $uinfo['level']+1,
                        'fg_viprules'   => 1,
                        'fg_vipgoods'   => 1,
                        'id_regsource'  => 7,
                        'relation_bind' => 1,
                        'activity_flag' => $type
                    );
                    $getinsertId = Db::table('ims_bj_shopn_member')->insertGetId($memData);
                    logs(date('Y-m-d H:i:s').' updateUserInfo 新增用户 :'.json_encode($memData),'updateUserInfo');


                    //检测当前用户的引领人所属门店 及 当前用户是否是内部员工
                    if($uinfo['sign'] == '666-666' || $uinfo['sign'] == '888-888' || $uinfo['sign'] == '000-000'){
                        //判断是否是内部员工 不是内部员工 进入的用户美容师强制为 148289   门店 1792
                        $f_mobile = self::$redis->exists('staff_' . $wxUserInfo['mobile']);
                        if($f_mobile){//新用户是内部员工
                            //用户ID是美容师ID
                            Db::table('ims_bj_shopn_member')->where('id',$getinsertId)->update([
                                'staffid' => $getinsertId,
                                'code' => '99999999',
                            ]);
                        }
                    }

                    $fans = Db::table('ims_fans')->where('mobile',$wxUserInfo['mobile'])->find();
                    $fansData=array(
                        'weid'       => 1,
                        'createtime' => time(),
                        'realname'   => $nickName,
                        'nickname'   => $nickName,
                        'avatar'     => $avatar,
                        'id_member'  => $getinsertId
                    );
                    if($fans){
                        Db::table('ims_fans')->where('mobile',$wxUserInfo['mobile'])->update($fansData);
                    }else{
                        $fansData['mobile']=$wxUserInfo['mobile'];
                        Db::table('ims_fans')->insert($fansData);
                    }
                }

                $data = $this->getLoginUserInfo($wxUserInfo['mobile'],$type);
                $msg = '信息维护成功';
                return parent::returnMsg(1,$data,$msg);
            }catch (\Exception $e){
                return parent::returnMsg(0,'','错误'.$e->getMessage());
            }
        }else{
            return parent::returnMsg(400,'','用户登陆信息过期，请重新登录');
        }
    }

    public function getLoginUserInfo($mobile) {
        $field = "member.id,member.code,member.storeid,member.staffid,member.pid,member.originfid,member.realname";
        $field .= ",wu.nickname,wu.avatar,member.mobile,member.storeid,member.isadmin,member.id_regsource";
        $field .= ",branch.title,branch.sign,member.activity_flag,branch.is_blink,s.status";
        $field .= ",branch.address,branch.location_p,branch.location_c,branch.location_a";

        $field .= ",dep.id_department,dep.st_department,dep.st_address";

        $field .= ",wu.is_agree,store.status as auth_status";
        $user = Db::table('ims_bj_shopn_member')
            ->alias('member')
            ->field($field)
            ->join(['ims_bwk_branch' => 'branch'], 'member.storeid=branch.id')

            ->join(['sys_departbeauty_relation' => 'deprel'],'branch.id = deprel.id_beauty and branch.sign=deprel.id_sign','left')
            ->join(['sys_department' => 'dep'],'deprel.id_department = dep.id_department','left')

            ->join(['pt_blink_box_store'=>'store'],'branch.id=store.storeid')
            ->join('blink_wx_user wu', 'member.mobile=wu.mobile','left')
            ->join('blink_box_store s', 's.storeid=branch.id','left')
            ->where(array('member.mobile' => $mobile))
            ->find();

        $getManager=config('selectManager');
        $getManager=explode(',',$getManager);

        if ($user['isadmin']) {
            $user['role'] = '店老板';
        } elseif (strlen($user['code']) > 1 && $user['id'] == $user['staffid']) {
            $user['role'] = '美容师';
        } else {
            $user['role'] = '老顾客';
        }
        if(in_array($user['id'],$getManager)){
            $user['role1'] = '总部管理';
        }else{
            $user['role1'] = '';
        }
        $user['role2'] = 0;
        $user['live'] = 0;

        //missshop顾客
        if(in_array($user['activity_flag'],[2200,8805,8806,8807,8808,8809,8810,8811,8812,8888])){
            if($user['auth_status'] == 2){
                $user['auth'] = true;
            }else{
                $user['auth'] = false;
            }
        }
        //非missshop顾客
        if(!in_array($user['activity_flag'],[2200,8805,8806,8807,8808,8809,8810,8811,8812,8888])){
            if($user['auth_status']==1){
                $user['auth'] = true;
            }else{
                $user['auth'] = false;
            }
        }

        if($user['auth_status']==1){
            $a = '非missshop顾客';
        }elseif($user['auth_status'] == 2){
            $a = 'missshop顾客';
        }elseif($user['auth_status'] == 3){
            $a = '全部顾客';
            $user['auth'] = true;
        }
        $user['auth_comment'] = $a;

        $user['sellerId'] = $user['staffid'];
        //2020.3.3 门店下的非美容师用户  小程序显示电话改为 4006901188
        if($user['storeid'] == 1792){
            //检测当前用户的推广人是否是美容师 originfid
            $pp = $user['originfid'] ?: ($user['pid'] ?:$user['staffid']);
            $seller = Db::table('ims_bj_shopn_member')->where('id',$pp)->field('id,staffid,mobile')->find();
            $user['sellerMobile']  = '4006901188';
            $user['sellerMobile1'] = $seller['mobile'];
            $user['sellerId']      = $seller['id'];
        }else{
            $user['sellerMobile'] = Db::table('ims_bj_shopn_member')->where('id',$user['staffid'])->value('mobile');
            if($user['sign'] == '666-666' || $user['sign'] == '888-888'){
                if($user['id'] != $user['staffid']){
                    $user['sellerMobile1'] = $user['sellerMobile'];
                    $user['sellerMobile'] = '4006901188';
                }
            }
        }
        unset($user['pid']);
        unset($user['isadmin']);
        unset($user['staffid']);
        unset($user['id_regsource']);
        if(is_array($user) && count($user)){
            return $user;
        }else{
            return '';
        }
    }
    //登陆检测
    public function loginCheck(){
        $number=input('param.number');
        if(config('check_flag1')){
            if($number==config('check_text1')){
                $code = 0;
                $data = $this->getLoginUserInfo('15888888888',1);
                $msg = '审核用户！';
            }else{
                $code = 1;
                $data ='';
                $msg = '正常用户！!!';
            }
        }else{
            $code = 1;
            $data = '';
            $msg = '正常用户！!';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    //获取用户设备信息
    public function systemInfo(){
        try {
            $params=file_get_contents('php://input');
            $time=date('Y-m-d H:i:s');
            $insert=array('time'=>$time,'obj'=>$params);
            Db::name('error_system_info')->insert($insert);
            return parent::returnMsg(1,'','成功');
        }catch (\Exception $e){
            return parent::returnMsg(0,'','失败'.$e->getMessage());
        }
    }
    /**
     * 开启拼购之旅
     */

    public function openBlink(){
        $mobile=input('param.mobile');
        $userInfo=file_get_contents('php://input');
        $equipment = input('param.equipment/a');

        if($mobile!='') {
            $info = Db::name('blink_waiting_user')
                ->where('mobile', $mobile)
                ->count();
            if ($info) {
                $code = 0;
                $data = '';
                $msg = '申请已提交！';
            }else {
                $insertData = array(
                    'mobile' => $mobile,
                    'userInfo'=>$userInfo,
                    'equipment'=>$equipment ? json_encode($equipment): '',
                    'insert_time' => time()
                );
                Db::name('blink_waiting_user')->insert($insertData);
                $code = 1;
                $data = '';
                $msg = '申请已提交！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '电话号码必须填写！';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    //生成专属二维码
    public function getQrCode(){
        $scene = input('param.scene');
        $path = input('param.path');
        $width = input('param.width',400);
        $auto_color = input('param.auto_color',false);
        $line_color = input('param.line_color',array('r'=>'0','g'=>'0','b'=>'0'));
        $hyaline = input('param.is_hyaline',0);
        $is_hyaline = $hyaline ? true : false;
        $name = md5($scene.$path.$width.$auto_color.json_encode($line_color).$is_hyaline);
        $patch = 'qrcode/blink/'.date('Y-m-d').'/';
        if (!file_exists($patch)){
            mkdir($patch, 0755, true);
        }
        try {
            $fileName = $patch . $name . '.jpg';
            if (!file_exists($fileName)) {
                $res2 = getWXACodeUnlimitBLink($path, $scene, $width, $auto_color, $line_color, $is_hyaline);
                file_put_contents('./' . $fileName, $res2);
            }
            $data = config('web_site_url').$fileName;
            return parent::returnMsg(1,$data,'二维码获取成功');
        }catch (\Exception $e){
            return parent::returnMsg(0,'','二维码获取失败'.$e->getMessage());
        }
    }
}