<?php
namespace app\api\controller;
use app\api\model\MemberModel;
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
        $appid=config('wx_pay.appid');
        $appsecret=config('wx_pay.appsecret');
        $js_code=input('param.code');
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$js_code."&grant_type=authorization_code";
        $mobile='';
        try {
            $info = httpGet($url);
            $info = json_decode($info, true);
            //logs(date('Y-m-d H:i:s').':'.json_encode($info),'getToken');
            $userInfo = Db::name('wx_user')->where('open_id', $info['openid'])->find();
            $token=createToken();
            if (is_array($userInfo) && count($userInfo)) {
                $time_out=strtotime("+1 days");
                Db::name('wx_user')->where('open_id', $info['openid'])->update(['session_key' => $info['session_key'],'token'=>$token,'time_out'=>$time_out]);
                $mobile=$userInfo['mobile'];
            } else {
                $time_out=strtotime("+1 days");
                Db::name('wx_user')->insert(['open_id' => $info['openid'], 'session_key' => $info['session_key'],'token'=>$token,'time_out'=>$time_out]);
            }
            $code = 1;
            $data = ['token'=>$token,'mobile'=>$mobile];
            $msg = '数据获取成功';
        }catch (\Exception $e){
            $code = 0;
            $data = [];
            $msg = '数据获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 获取用户电话号码
     */
    public function getMobile(){
        $type=input('param.type',0);
        //type等于0检测在系统中是否存在 判断角色 不存在禁止登陆
        //type等于1 为通过美容师分享的拼购连接进入客户
        //type等于2 为通过老顾客分享的拼购连接进入客户
        //type等于3 为通过美容师分享的奖券连接进入客户
        //type等于4 为通过老顾客分享的奖券连接进入客户
        //type等于5 为通过老顾客分享的圣诞连接进入客户
        //type等于6 为通过老顾客分享的圣诞连接进入客户
        //type等于7 为通过美容师分享的新年活动连接进入客户
        //type等于8 为通过老顾客分享的新年活动连接进入客户
        $token=input('param.token');
        $mobile=input('param.mobile');
        if(parent::checkToken($token)) {
            if(strlen($mobile)==11){
                $userMobile=$mobile;
            }else{
                $iv = input('param.iv');
                $encryptedData = input('param.encryptedData');
                if ($iv != '' || $encryptedData != '') {
                    $getSession = Db::name('wx_user')->where('token', $token)->value('session_key');
                    if ($getSession) {
                        $aes = new wxBizDataCrypt(config('wx_pay.appid'), $getSession);
                        $errCode = $aes->decryptData($encryptedData, $iv, $data1);
                        //logs(date('Y-m-d H:i:s').':'.json_encode($data1),'getMobile');
                        if ($errCode == 0) {
                            $data1 = json_decode($data1, true);
                            Db::name('wx_user')->where('token', $token)->update(['mobile' => $data1['phoneNumber']]);
                            $userMobile=$data1['phoneNumber'];
                        }else{
                            $code = 0;
                            $data = '';
                            $msg = '微信电话号码获取失败';
                            return parent::returnMsg($code,$data,$msg);
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '数据获取失败';
                        return parent::returnMsg($code,$data,$msg);
                    }
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '请检查参数,iv和encryptedData不能为空！';
                    return parent::returnMsg($code,$data,$msg);
                }
            }
            $flag = $this->checkCurrenUser($type, $userMobile);
            if ($flag){
                if($flag==200){
                    $res['flag'] = 0;
                }else{
//                    $memberInfo=Db::table('ims_bj_shopn_member')->where('mobile',$userMobile)->count();
//                    if(!$memberInfo){
//                        $res['flag'] = -1;
//                    }else{
                        $res['flag'] = $flag;
//                    }
                }
            }else{
                $res['flag'] = -1;
            }
            $res['mobile'] = $userMobile;
            $code = 1;
            $data = $res;
            $msg = '数据获取成功';
        }else{
            $code = 400;
            $data = '';
            $msg = '用户登陆信息过期，请重新登录';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    /**
     * 维护信息
     */
    public function updateUserInfo() {
        $token=input('param.token');
        if(parent::checkToken($token)) {
            $type = input('param.type', 0);//接收进入渠道
            $fid = input('param.fid', 0);//如果是新增 需将引导人id传入
            $nickName = input('param.nickName', '');
            $avatar = input('param.avatar', '');
            $roomid = input('param.roomid', 0);
            //$avatarUrl=$avatarUrl?str_ireplace('\\','',$avatarUrl):'';
            try{
                $wxUserInfo= Db::name('wx_user')->where('token', $token)->find();
                //如果wx_user表中已经存在过这个电话号码 即该电话有两条记录 就说明换过小程序id 要删除原来的记录
                $mobileCount=Db::name('wx_user')->where('mobile',$wxUserInfo['mobile'])->count();
                if($mobileCount>1){
                    Db::name('wx_user')->where('mobile',$wxUserInfo['mobile'])->limit(1)->order('id asc')->delete();
                }
                if($nickName=='' && $avatar==''){
                    $nickName=$wxUserInfo['nickname']?$wxUserInfo['nickname']:'';
                    $avatar=$wxUserInfo['avatar']?$wxUserInfo['avatar']:'';
                }
                Db::name('wx_user')->where('token', $token)->update(['nickname'=>$nickName,'avatar'=>$avatar]);
                $memberInfo=Db::table('ims_bj_shopn_member')->where('mobile',$wxUserInfo['mobile'])->find();
                //用户不存在需要注册建立关系
                // if(($type==1 || $type==2) && !count($memberInfo));
                if(!count($memberInfo)){
                    if($fid) {
                        //检测邀请人是美容师还是老顾客
                        $uinfo = Db::table('ims_bj_shopn_member')->where('id', $fid)->find();
                        if (strlen($uinfo['code']) > 1 && $uinfo['id'] == $uinfo['staffid']) {
                            $sellerId = $fid;
                        } else {
                            $sellerId = $uinfo['staffid'];
                        }
                        $tempSellerId = $sellerId;
                        //如果是上级门店是1550  missshop门店 且flag=8805 强制规定其美容师绑定为62957
                        if ($uinfo['storeid'] == 1550 || $type == 8805) {
                            $sellerId = 62957;
                            $s = 1550;
                        } else {
                            $s = $uinfo['storeid'];
                        }
                        //如果邀请人是店老板 返回错误
                        if ($uinfo['isadmin']) {
                            $code = 0;
                            $data = '';
                            $msg = '错误，请通过正确的用户或美容师的分享进入';
                            return parent::returnMsg($code, $data, $msg);
                        }
                        //插入用户 创建层级关系
                        //先插入member表 再插入fans表
                        $memData = array('weid' => 1, 'storeid' => $s, 'pid' => $fid, 'staffid' => $sellerId, 'realname' => $nickName, 'mobile' => $wxUserInfo['mobile'], 'createtime' => time(), 'level' => $uinfo['level'] + 1, 'fg_viprules' => 1, 'fg_vipgoods' => 1, 'id_regsource' => 7, 'relation_bind' => 1, 'activity_flag' => $type, 'register_roomid' => $roomid);
                        $getinsertId = Db::table('ims_bj_shopn_member')->insertGetId($memData);
                        $fans = Db::table('ims_fans')->where('mobile', $wxUserInfo['mobile'])->find();
                        $fansData = array('weid' => 1, 'createtime' => time(), 'realname' => $nickName, 'nickname' => $nickName, 'avatar' => $avatar, 'id_member' => $getinsertId);
                        if ($fans) {
                            Db::table('ims_fans')->where('mobile', $wxUserInfo['mobile'])->update($fansData);
                        } else {
                            $fansData['mobile'] = $wxUserInfo['mobile'];
                            Db::table('ims_fans')->insert($fansData);
                        }
                        //如果新增加顾客店铺id为1550，且flag=8805 要与引导美容师建立关系 之后的业绩算引导人的
                        if ($uinfo['storeid'] == 1550 || $type == 8805) {
                            $insertd['seller_id'] = $tempSellerId;
                            $insertd['cus_id'] = $getinsertId;
                            Db::name('pk_customer')->insert($insertd);
                        }
                    }else{
                        $code = 0;
                        $data = '';
                        $msg = '非法注册';
                        return parent::returnMsg($code,$data,$msg);
                    }
                }else{
                    if($fid<>"") {
                        if($memberInfo['isadmin']==1 || ($memberInfo['id']==$memberInfo['staffid'] && strlen($memberInfo['code']) > 1)) {
                            //如果是美容师或者店老板 不处理
                        }else{
                            //顾客更新美容师归属：顾客首次点击分享美容师的分享连接，自动将自己归属于该美容师下 如果之前属于美容师A 但拼购他第一次点的是美容师B的分享连接 那么将层级关系由A改为B
                            if ($memberInfo['relation_bind'] == 0) {
                                if ($memberInfo['staffid'] <> $fid) {
                                    $uinfo = Db::table('ims_bj_shopn_member')->where('id', $fid)->find();
                                    if($memberInfo['storeid']==$uinfo['storeid']) {//只有同门店才能挂靠
                                        if (strlen($uinfo['code']) > 1 && $uinfo['id'] == $uinfo['staffid']) {
                                            $sellerId = $fid;
                                            $upData = array('relation_bind' => 1, 'pid' => $sellerId, 'staffid' => $sellerId);
                                            Db::table('ims_bj_shopn_member')->where('mobile', $memberInfo['mobile'])->update($upData);
                                            Db::table('ims_bj_shopn_order')->where('uid', $memberInfo['id'])->update(['staffid' => $sellerId, 'pid' => $sellerId]);
                                        }
                                    }

                                }
                            }
                            //检测当前用户是否是美容师身份的顾客，如果是 修改成顾客身份
                            //该有后台手工调整 甘小娟修改 2018-09-08
//                            if ($memberInfo['is_seller'] == 0 && ($memberInfo['id'] == $memberInfo['staffid'])) {
//                                $upData = array('code' => '', 'relation_bind' => 1, 'pid' => $fid, 'staffid' => $fid);
//                                Db::table('ims_bj_shopn_member')->where('mobile', $memberInfo['mobile'])->update($upData);
//                                Db::table('ims_bj_shopn_order')->where('uid', $memberInfo['id'])->update(['staffid' => $fid, 'pid' => $fid]);
//                            }
                            logs(date('Y-m-d H:i:s')."用户id为".$memberInfo['id']."的上级美容师通过拼购连接由".$memberInfo['staffid']."改变为".$fid,"customerSeller");
                        }
                    }
                }
                //记录访客登陆
                $logsData=$this->getInfoByToken($token);
                if(is_array($logsData)) {
                    $logsData['action'] = 0;
                    $logsData['remark']='登陆';
                    $logsData['insert_time']=time();
                    $this->logToRedis($logsData);
                }
                $code = 1;
                $data = $this->getLoginUserInfo($wxUserInfo['mobile'],$type);
                $msg = '信息维护成功';
            }catch (\Exception $e){
                $code = 0;
                $data = '';
                $msg = '错误'.$e->getMessage();
            }
        }else{
            $code = 400;
            $data = '';
            $msg = '用户登陆信息过期，请重新登录';
        }
        return parent::returnMsg($code,$data,$msg);
    }



    /**
     * get: id
     * path: getUserInfo
     * method: getUserInfo
     * param: id - {int} 用户id
     */
//	public function getUserInfo($mobile) {
//		if ($mobile != '') {
//			$user = Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.pid,member.realname,member.mobile,member.realname,member.storeid,member.isadmin,branch.title,branch.sign')->join(['ims_bwk_branch' => 'branch'],'member.storeid=branch.id')->where(array('member.mobile'=>$mobile))->find();
//			if ($user) {
//				$code = 1;
//				if($user['isadmin']){
//                    $user['role']='店老板';
//                }elseif($user['pid']==0 && $user['isadmin']==0){
//                    $user['role']='美容师';
//                }else{
//                    $user['role']='顾客';
//                }
//                unset($user['pid']);
//                unset($user['isadmin']);
//				$data = $user;
//				$msg = '获取用户信息成功';
//			} else {
//                $code = 0;
//                $data = '';
//                $msg = '用户不存在';
//			}
//		} else {
//            $code = 0;
//            $data = '';
//            $msg = '参数错误';
//		}
//		return parent::returnMsg($code,$data,$msg);
//	}


    public function getLoginUserInfo($mobile,$type) {
        $user = Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.code,member.staffid,member.pid,member.realname,wu.nickname,wu.avatar,member.mobile,member.storeid,member.isadmin,member.id_regsource,branch.title,branch.sign,member.activity_flag,branch.join_pg,branch.join_tk,branch.is_bargain,branch.is_blink,branch.address')->join(['ims_bwk_branch' => 'branch'], 'member.storeid=branch.id')->join('wx_user wu', 'member.mobile=wu.mobile','left')->where(array('member.mobile' => $mobile))->find();
        $getManager=config('selectManager');
        $getManager=explode(',',$getManager);
//        if ($user['id_regsource'] == 7) {
//            if (strlen($user['code']) > 1 && $user['id'] == $user['staffid']){
//                $user['role'] = '美容师';
//            }else{
//                //抽奖券进来的顾客都把它当作老顾客对待
//                if ($type == 1 || $type == 3 || $type == 4 || $type == 5 || $type == 6 || $type == 7 || $type == 8 || $user['activity_flag']==3 || $user['activity_flag']==4 || $user['activity_flag']==5 || $user['activity_flag']==6 || $user['activity_flag']==7 || $user['activity_flag']==8 || (strlen($type)==4) || (strlen($user['activity_flag'])==4)) {
//                    //if ($type == 1 || $type == 3 || $type == 4 || $type == 5 || $type == 6 || $type == 7 || $type == 8 || $user['activity_flag']==3 || $user['activity_flag']==4 || $user['activity_flag']==5 || $user['activity_flag']==6 || $user['activity_flag']==7 || $user['activity_flag']==8) {
//                    $user['role'] = '老顾客';
//                } else {
//                    //如果是7 则属于小程序注册的新顾客 要检测他是否支付成功过
//                    $map['member.mobile'] = array('eq', $mobile);
//                    //                $map['order.order_status']=array('in','2,3');
//                    //                $map['order.pay_status']=array('eq',1);
//                    $checkOrder = Db::name('tuan_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'], 'order.uid=member.id')->where($map)->order('id desc')->find();
//                    if ($checkOrder) {
//                        $user['role'] = '老顾客';
//                    } else {
//                        $user['role'] = '新顾客';
//                    }
//                }
//            }
//        } else {
        if ($user['isadmin']) {
            $user['role'] = '店老板';
        } elseif (strlen($user['code']) > 1 && $user['id'] == $user['staffid']) {
            $user['role'] = '美容师';
        } else {
            $user['role'] = '老顾客';
        }
//        }
        if(in_array($user['id'],$getManager)){
            $user['role1'] = '总部管理';
        }else{
            $user['role1'] = '';
        }

        //21天训练营打开是否加推广二维码
        if($user['role']=='店老板'){
            $user['role2'] = 0;
        }else{
            $getIds=Db::table('ims_bwk_branch')->where('sign','in','000-000,888-888,666-666')->column('id');
            $bmap['mobile']=array('eq',$mobile);
            $bmap['storeid']=array('in',$getIds);
            $bscMemer=Db::table('ims_bj_shopn_member')->where($bmap)->find();
            if($bscMemer){
                $user['role2'] = 0;
            }else{
                $user['role2'] = 1;
            }
        }
        //直播观看权限
//        $allowStoreId=array('000-000','888-888','666-666');
//        if($user['role1'] == '总部管理' || $user['isadmin'] == 1 || in_array($user['sign'],$allowStoreId)){
//            $user['live'] = 1;
//        }else{
//            $getLiveCheck=Db::table('think_appoint_list')->where('mobile',$mobile)->find();
//            if(count($getLiveCheck) && $getLiveCheck['type']==1){
//                $user['live'] = 1;
//            }else{
//                $user['live'] = 0;
//            }
//        }
        $user['live'] = 0;

        //是否参加2019PK赛扫街的权限,签到的美容师
//        $joinPk=Db::name('pk_reg')->where('uid',$user['id'])->count();
//        if($joinPk){
//            $user['miss_flag']=1;
//        }else{
//            $getJoinBranch=Db::name('new_year_config')->where('id',2)->value('branch_list');
//            if($getJoinBranch){
//                $joinBranchArr=explode(',',$getJoinBranch);
//                if(in_array($user['storeid'],$joinBranchArr)){
//                    $user['miss_flag']=1;
//                }else{
//                    $user['miss_flag']=0;
//                }
//            }else{
//                $user['miss_flag']=0;
//            }
//        }
//        if($user['role']=='老顾客' || $user['role']=='新顾客'){
//            $checkPKOrder=Db::name('pk_order')->where('uid',$user['id'])->count();
//            if($checkPKOrder){
//                $user['miss_flag']=1;
//            }else{
//                $user['miss_flag']=0;
//            }
//        }
        //$user['miss_flag']=0;
        //2019PK赛标识结束


        //密丝小铺产品允许老客购买标识
        $allow_branch=['75','79'];
        if(in_array($user['storeid'],$allow_branch)){
            $check=Db::name('tuan_order')->where(['uid'=>$user['id'],'pay_status'=>1,'flag'=>1])->count();
            if($check){
                $user['miss_flag']=1;
            }else{
                $user['miss_flag']=0;
            }
        }else{
            $user['miss_flag']=0;
        }

        //21天减脂训练营打卡标识
        $training = Db::name('training_member')->where('uid', $user['id'])->order('id')->value('insert_time');
        if ($training) {
            $joinDay = count_days($training, time());
            if ($joinDay > 22) {
                $training_flag = 0;
            } else {
                $training_flag = 1;
            }
        } else {
            $training_flag = 0;
        }
        $user['xly_flag'] = $training_flag;


        //活动开关
        $getActicityKey=Db::table('ims_bj_shopn_member')->where(['isadmin'=>1,'storeid'=>$user['storeid']])->value('activity_key');
        $getActicityKey=$getActicityKey?$getActicityKey:0;
        $user['activityKey'] = $getActicityKey;
        $user['sellerId']=$user['staffid'];
        $user['sellerMobile']=Db::table('ims_bj_shopn_member')->where('id',$user['staffid'])->value('mobile');

        //是否填写用户画像
        $user['portrait']= Db::table('ims_bj_shopn_member_extend')->where('mobile', $mobile)->count();

        //用户是否通过了美容师审核
        $double_user= Db::name('double12_user')->where(['uid'=>$user['id'],'pid'=>0])->count();
        if($double_user){
            $user['double12Role']='体验官';
        }else{
            $user['double12Role']='非体验官';
        }

        //是否弹出密丝小铺大礼包
//        $gift = Db::name('ticket_user')->where(['mobile' => $mobile, 'type' => 17])->count();
//        if ($gift) {
//            $user['miss_gift']=1;
//        }else{
//            $user['miss_gift']=0;
//        }
        $user['miss_gift']=1;
        $activity=$this->activity_role($getActicityKey,$user);
        $user['activity']=$activity;

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
    //活动权限
    public function activity_role($getActicityKey,$user){
        $activity=[];
        $activity['mainKey']['currentStatus']=$getActicityKey?'open':'close';
        $haveBuy=0;
        if($user['role']=='店老板' || $user['role']=='美容师') {
            $c = Db::name('activity_order')->where(['channel' => 'missshop', 'pay_status' => 1, 'fid' => $user['id']])->count();
            if($activity || $c){
                $haveBuy=1;
            }
        }else{
            //if($user_flag = array('8805', '8806', '8807', '8808', '8809', '8810','8811','2200')){
                $c= Db::name('activity_order')->where(['channel' => 'missshop', 'pay_status' => 1, 'uid' => $user['id']])->count();
                if($c){
                    $haveBuy=1;
                }
           // }
        }
        if($user['activity_flag']=='2200'){
            $activity['mainKey']['historyStatus'] = 'open';
        }else{
            $activity['mainKey']['historyStatus'] = $haveBuy ? 'open' : 'close';
        }


        $activity['missshopKey']=[];
        $activity['missshopKey']['allowKey']=false;
        $activity['missshopKey']['mask']=false;
        $activity['missshopKey']['cleansingWater']=false;
        $activity['missshopKey']['transfer']=false;
        $activity['otherKey']=[];
        $activity['otherKey']['substitute']['allowKey']=false;//代餐开关
        $activity['otherKey']['wuzhenjiaoyuan']['allowKey']=false;//无针胶原小颜术开关
        $activity['otherKey']['tongyanshu']['allowKey']=false;//明星童颜术开关
        $activity['otherKey']['toupijiance']['allowKey']=false;//头皮检测开关
        $activity['otherKey']['xingti']['allowKey']=false;//形体开关
        $activity['otherKey']['fudai']['allowKey']=false;//2020福袋开关
        $activity['otherKey']['virus']['allowKey']=false;//疫情关怀开关
        $activity['otherKey']['spring']['allowKey']=false;//约惠春天开关
        $activity['otherKey']['88card']['allowKey']=false;//88礼券
        $activity['otherKey']['live']['allowKey']=false;//直播banner
        $activity['otherKey']['training']['allowKey']=false;//21减脂训练营
        $activity['otherKey']['liuyiba']['allowKey']=false;//618活动
        $activity['otherKey']['substitute']['userKey']=$user['double12Role'];
        $activity['otherKey']['substitutemeal']['allowKey']=false;
        if($getActicityKey) {
            if($user['role']=='店老板' || $user['role']=='美容师'){
                $activity['missshopKey']['allowKey'] = true;
                if (strlen($user['join_tk'])) {
                    $join_tk_arr = explode(',', $user['join_tk']);
                    foreach ($join_tk_arr as $k => $v) {
                        switch ($v) {
                            case 1:
                                $activity['missshopKey']['mask'] = true;
                                break;
                            case 2:
                                $activity['missshopKey']['cleansingWater'] = true;
                                break;
                            case 3:
                                $activity['missshopKey']['transfer'] = true;
                                break;
                            case 5:
                                $activity['otherKey']['substitute']['allowKey'] = true;
                                break;
                            case 6:
                                $activity['otherKey']['wuzhenjiaoyuan']['allowKey'] = true;
                                break;
                            case 7:
                                $activity['otherKey']['tongyanshu']['allowKey'] = true;
                                break;
                            case 8:
                                $activity['otherKey']['toupijiance']['allowKey'] = true;
                                break;
                            case 9:
                                $activity['otherKey']['xingti']['allowKey'] = true;
                                break;
                            case 10:
                                $activity['otherKey']['fudai']['allowKey'] = true;
                                break;
                            case 11:
                                $activity['otherKey']['virus']['allowKey'] = true;
                                break;
                            case 12:
                                $activity['otherKey']['spring']['allowKey'] = true;
                                break;
                            case 16:
                                $activity['otherKey']['live']['allowKey'] = true;
                                break;
                            case 18:
                                $activity['otherKey']['substitutemeal']['allowKey'] = true;
                                break;
                            case 19:
                                $activity['otherKey']['training']['allowKey'] = true;
                                break;
                            case 20:
                                $activity['otherKey']['liuyiba']['allowKey'] = true;
                                break;
                        }
                    }
                }
            }else{
                $activity['missshopKey']['allowKey'] = true;
                if (strlen($user['join_tk'])) {
                    $join_tk_arr = explode(',', $user['join_tk']);
                    $user_allow=$this->is_allow($user['activity_flag']);
                    $activity['missshopKey']['allowKey'] = $user_allow;
                    foreach ($join_tk_arr as $k => $v) {
                        switch ($v) {
                            case 1:
                                $activity['missshopKey']['mask'] = $user_allow;
                                break;
                            case 2:
                                $activity['missshopKey']['cleansingWater'] = $user_allow;
                                break;
                            case 3:
                                if ($user['activity_flag'] != '8807') {
                                    $activity['missshopKey']['transfer'] = $user_allow;
                                }
                                break;
                            case 5:
                                $activity['otherKey']['substitute']['allowKey'] = true;
                                break;
                            case 6:
                                $activity['otherKey']['wuzhenjiaoyuan']['allowKey'] = true;
                                break;
                            case 7:
                                $activity['otherKey']['tongyanshu']['allowKey'] = true;
                                break;
                            case 8:
                                $activity['otherKey']['toupijiance']['allowKey'] = true;
                                break;
                            case 9:
                                $activity['otherKey']['xingti']['allowKey'] = true;
                                break;
                            case 10:
                                $activity['otherKey']['fudai']['allowKey'] = true;
                                break;
                            case 11:
                                $activity['otherKey']['virus']['allowKey'] = true;
                                break;
                            case 12:
                                $activity['otherKey']['spring']['allowKey'] = true;
                                break;
                            case 17:
                                $activity['otherKey']['88card']['allowKey'] = true;
                                break;
                            case 16:
                                $activity['otherKey']['live']['allowKey'] = true;
                                break;
                            case 18:
                                $activity['otherKey']['substitutemeal']['allowKey'] = true;
                                break;
                            case 19:
                                $activity['otherKey']['training']['allowKey'] = true;
                                break;
                            case 20:
                                $activity['otherKey']['liuyiba']['allowKey'] = true;
                                break;
                        }
                    }
                }
            }
        }
        return $activity;
    }

    public function is_allow($u_flag){
        $user_flag = array('8805', '8806', '8807', '8808', '8809', '8810','8811','8812','2200','8818','8819','8821','8822','8823');
        if (in_array($u_flag, $user_flag)) {
            return true;
        }else{
            return false;
        }
    }


    public function checkCurrenUser($type,$mobile){
        $mem=new MemberModel();
        $user=$mem->getInfoByMobile($mobile);
        if($type){
            $code=$type;
        }else{
            if(is_array($user) && count($user)){
//                if($user['isadmin']==1 || ($user['id']==$user['staffid'] && strlen($user['code']) > 1)) {
//                    $code = 200;
//                }else{
//                    if($user['id_regsource']==7){
//                        //if ($user['activity_flag']==3 || $user['activity_flag']==4 || $user['activity_flag']==5 || $user['activity_flag']==6 || $user['activity_flag']==7 || $user['activity_flag']==8) {
//                        if ($user['activity_flag']==3 || $user['activity_flag']==4 || $user['activity_flag']==5 || $user['activity_flag']==6 || $user['activity_flag']==7 || $user['activity_flag']==8 || (strlen($user['activity_flag'])==4)) {
//                            $code = 200;//正常存在用户 允许登陆
//                        }else {
//                            $map1['uid'] = array('eq', $user['id']);
//                            //                    $map1['order_status']=array('in','2,3');
//                            //                    $map1['pay_status']=array('eq',1);
//                            $checkOrder = Db::name('tuan_order')->where($map1)->count();
//                            if ($checkOrder) {
//                                $code = 200;//正常存在用户 允许登陆
//                            } else {
//                                $code = 0;
//                            }
//                        }
//                    }else{
//                        $code = 200;//正常存在用户 允许登陆
//                    }
//                }
                $code = 200;//正常存在用户 允许登陆
            }else{
                $code = 0;
            }
        }
        return $code;
    }


    /**
     * 存储用户画像
     */
    public function member_portrait(){
        $mobile=input('param.mobile');
        $sex=input('param.sex');
        $age_group=input('param.age_group');
        $birthday=input('param.birthday');
        $lon=input('param.lon');//经度
        $lat=input('param.lat');//纬度
        $wx_address=input('param.wx_address');
        $location=$lon.','.$lat;
        $interest=input('param.interest');
        $info = Db::table('ims_bj_shopn_member_extend')->where('mobile', $mobile)->count();
        if (!$info) {
            $getMid = Db::table('ims_bj_shopn_member')->where('mobile', $mobile)->value('id');
            $insertData = array('mid'=>$getMid,'mobile' => $mobile,'sex'=>$sex,'age_group'=>$age_group,'birthday'=>$birthday,'interest'=>$interest,'wx_address'=>$wx_address,'location'=>$location,'insert_time' => time());
//            if($lon!='' && $lat!=''){
//                $gps_Address=getAddress($lon,$lat);
//                if($gps_Address){
//                    $insertData=array_merge($insertData,$gps_Address);
//                }
//            }
            Db::table('ims_bj_shopn_member_extend')->insert($insertData);
            $code = 1;
            $data = '';
            $msg = '已提交成功！';
        }else{
            $code = 0;
            $data = '';
            $msg = '已存在！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 开启拼购之旅
     */
    public function openTuanPower(){
        $mobile=input('param.mobile');
        $userInfo=file_get_contents('php://input');
        if($mobile!='') {
            $info = Db::name('waiting_user')->where('mobile', $mobile)->count();
            if ($info) {
                $code = 0;
                $data = '';
                $msg = '申请已提交！';
            }else {
                $insertData = array('mobile' => $mobile, 'userInfo'=>$userInfo,'insert_time' => time());
                Db::name('waiting_user')->insert($insertData);
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


    //登陆检测
    public function loginCheck(){
        $number=input('param.number');
        if(config('check_flag')){
            if($number==config('check_text')){
                $code = 0;
                $data = $this->getLoginUserInfo('15888888888',1);
                $msg = '审核用户！';
            }else{
                $code = 1;
                $data = '';
                $msg = '正常用户！';
            }
        }else{
            $code = 1;
            $data = '';
            $msg = '正常用户！';
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

    //生成专属二维码
    public function getQrCode(){
        $scene=input('param.scene');
        $path = input('param.path');
        $width = input('param.width',400);
        $auto_color = input('param.auto_color',false);
        $line_color = input('param.line_color',array('r'=>'0','g'=>'0','b'=>'0'));
        $hyaline = input('param.is_hyaline',0);
        $is_hyaline=$hyaline?true:false;
		$aa=$scene.'-'.$path.'-'.$width.'-'.$auto_color.'-'.json_encode($line_color).'-'.$is_hyaline;
		logs(date('Y-m-d H:i:s').':'.json_encode($aa),'qrcodehdj');
        $name=md5($scene.$path.$width.$auto_color.json_encode($line_color).$is_hyaline);
        $patch = 'qrcode/xcx/';
        if (!file_exists($patch)){
            mkdir($patch, 0755, true);
        }
        try {
            $fileName = $patch . $name . '.jpg';
            if (!file_exists($fileName)) {
                $res2 = getWXACodeUnlimit($path, $scene, $width, $auto_color, $line_color, $is_hyaline);
                file_put_contents('./' . $fileName, $res2);
            }
            $code=1;
            $data=config('web_site_url').$fileName;
            $msg='二维码获取成功';
        }catch (\Exception $e){
            $code=0;
            $data='';
            $msg='二维码获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //检测用户画像是否填写
//    public function check_portrait(){
//        $mobile=input('param.mobile');
//        if($mobile!='') {
//            //是否领取过大礼包
//            $gift = Db::name('ticket_user')->where(['mobile' => $mobile, 'type' => 17])->count();
//            //是否填写过用户画像
//            $portrait = Db::table('ims_bj_shopn_member_extend')->where('mobile', $mobile)->count();
//            if ($portrait || $gift) {
//                $code = 0;
//                $data = '';
//                $msg = '已填写用户画像';
//            } else {
//                $code = 1;
//                $data = '';
//                $msg = '填写画像并发送大礼包';
//            }
//        }else{
//            $code = 0;
//            $data = '';
//            $msg = '参数错误';
//        }
//        return parent::returnMsg($code,$data,$msg);
//    }

    //直播受众
    public function live_audience(){
        $audience= Db::name('live_url')->where('id',1)->find();
        if($audience) {
            $code = 1;
            $data = $audience['audience'];
            $msg = '获取成功';
        } else {
            $code = 0;
            $data = '';
            $msg = '错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

//    public function activity_list(){
//        $mobile=input('param.mobile');
//        $storeid=input('param.storeid');
//        $is_bargain=input('param.is_bargain');
//        $userInfo = $this->getLoginUserInfo($mobile,1);
//        if($userInfo){
//            $userInfo=$userInfo['activity'];
//        }
//        $list=[];
//        $list[0]['name']='missshop碳酸面膜';
//        $list[0]['bs']='mask';
//        $list[0]['type']='8806';
//        $list[1]['name']='missshop免洗卸妆水';
//        $list[1]['bs']='cleansingWater';
//        $list[1]['type']='8809';
//        $list[2]['name']='心肌福利社';
//        $list[2]['bs']='transfer';
//        $list[2]['type']='8808';
//        $list[3]['name']='抗疫四大法宝';
//        $list[3]['bs']='virus';
//        $list[3]['type']='8818';
//        $list[4]['name']='春节88福袋';
//        $list[4]['bs']='fudai';
//        $list[4]['type']='8817';
//        $list[5]['name']='明星童颜术';
//        $list[5]['bs']='tongyanshu';
//        $list[5]['type']='8815';
//        $list[6]['name']='无针线雕小颜术';
//        $list[6]['bs']='wuzhenjiaoyuan';
//        $list[6]['type']='8813';
//        $list[7]['name']='拯救我的发际线';
//        $list[7]['bs']='toupijiance';
//        $list[7]['type']='8814';
//        $list[8]['name']='寻找美魔女';
//        $list[8]['bs']='xingti';
//        $list[8]['type']='8816';
//        $list[9]['name']='营养代餐';
//        $list[9]['bs']='hongwei';
//        $list[9]['type']='8812';
//        $list[10]['name']='拼人品';
//        $list[10]['bs']='pinrenpin';
//        $list[10]['type']='2200';
//
//        foreach ($list as $k=>$v){
//            if($v['bs']=='mask' || $v['bs']=='cleansingWater' || $v['bs']=='transfer'){
//                if($userInfo['missshopKey']['allowKey']) {
//                    if (!$userInfo['missshopKey']['mask']) {
//                        unset($list[$k]);
//                    }
//                    if (!$userInfo['missshopKey']['cleansingWater']) {
//                        unset($list[$k]);
//                    }
//                    if (!$userInfo['missshopKey']['transfer']) {
//                        unset($list[$k]);
//                    }
//                }else{
//                    unset($list[$k]);
//                }
//            }
//            if ($v['bs']=='virus') {
//                if ($v['bs'] == 'virus' && !$userInfo['otherKey']['virus']['allowKey']) {
//                    unset($list[$k]);
//                }
//            }
//            if ($v['bs']=='fudai') {
//                if ($v['bs'] == 'fudai' && !$userInfo['otherKey']['fudai']['allowKey']) {
//                    unset($list[$k]);
//                }
//            }
//            if ($v['bs']=='tongyanshu'){
//                if(!$userInfo['otherKey']['tongyanshu']['allowKey']) {
//                    unset($list[$k]);
//                }else{
//                    $list[$k]['bs']='taipan';
//                }
//            }
//            if ($v['bs']=='wuzhenjiaoyuan'){
//                if(!$userInfo['otherKey']['wuzhenjiaoyuan']['allowKey']) {
//                    unset($list[$k]);
//                }else{
//                    $list[$k]['bs']='xiaoyan';
//                }
//            }
//            if ($v['bs']=='toupijiance'){
//                if(!$userInfo['otherKey']['toupijiance']['allowKey']) {
//                    unset($list[$k]);
//                }else{
//                    $list[$k]['bs']='toupi';
//                }
//            }
//            if ($v['bs']=='xingti'){
//                if(!$userInfo['otherKey']['xingti']['allowKey']) {
//                    unset($list[$k]);
//                }else{
//                    $list[$k]['bs']='neiyi';
//                }
//            }
//            if ($v['bs']=='hongwei'){
//                if($storeid!='1071') {
//                    unset($list[$k]);
//                }
//            }
//            if ($v['bs']=='pinrenpin'){
//                if(!$is_bargain) {
//                    unset($list[$k]);
//                }
//            }
//        }
//        $list=array_values($list);
//        if($list){
//            return parent::returnMsg(1,$list,'获取成功');
//        }else{
//            return parent::returnMsg(0,$list,'暂无参与活动');
//        }
//    }


    public function activity_list_new(){
        $mobile=input('param.mobile');
        $storeid=input('param.storeid');
        $is_bargain=input('param.is_bargain',0);
        $userInfo = $this->getLoginUserInfo($mobile,1);
        if($userInfo){
            $userInfo=$userInfo['activity'];
        }
        $list=Db::name('activity_list')->where(['activity_status'=>1,'is_used'=>1])->field('name,bs,type,poster_cate,activity_poster')->order('activity_orders')->select();
        $branch=[];
        foreach ($list as $k=>$v){
            if($v['poster_cate']) {
                if ($v['bs'] == 'mask' || $v['bs'] == 'cleansingWater' || $v['bs'] == 'transfer') {
                    if ($userInfo['missshopKey']['allowKey']) {
                        if (!$userInfo['missshopKey']['mask']) {
                            unset($list[$k]);
                        }
                        if (!$userInfo['missshopKey']['cleansingWater']) {
                            unset($list[$k]);
                        }
                        if (!$userInfo['missshopKey']['transfer']) {
                            unset($list[$k]);
                        }
                    } else {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'virus') {
                    if ($v['bs'] == 'virus' && !$userInfo['otherKey']['virus']['allowKey']) {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'spring') {
                    if ($v['bs'] == 'spring' && !$userInfo['otherKey']['spring']['allowKey']) {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'fudai') {
                    if ($v['bs'] == 'fudai' && !$userInfo['otherKey']['fudai']['allowKey']) {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'tongyanshu') {
                    if (!$userInfo['otherKey']['tongyanshu']['allowKey']) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['bs'] = 'taipan';
                    }
                }
                if ($v['bs'] == 'wuzhenjiaoyuan') {
                    if (!$userInfo['otherKey']['wuzhenjiaoyuan']['allowKey']) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['bs'] = 'xiaoyan';
                    }
                }
                if ($v['bs'] == 'toupijiance') {
                    if (!$userInfo['otherKey']['toupijiance']['allowKey']) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['bs'] = 'toupi';
                    }
                }
                if ($v['bs'] == 'xingti') {
                    if (!$userInfo['otherKey']['xingti']['allowKey']) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['bs'] = 'neiyi';
                    }
                }
                if ($v['bs'] == '88card') {
                    if (!$userInfo['otherKey']['88card']['allowKey']) {
                        unset($list[$k]);
                    } else {
                        $list[$k]['bs'] = '88card';
                    }
                }
                if ($v['bs'] == 'hongwei') {
                    if ($storeid != '1071') {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'pinrenpin') {
                    if (!$is_bargain) {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'substitute5') {
                    if ($v['bs'] == 'substitute5' && !$userInfo['otherKey']['substitutemeal']['allowKey']) {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'training') {
                    if ($v['bs'] == 'training' && !$userInfo['otherKey']['training']['allowKey']) {
                        unset($list[$k]);
                    }
                }
                if ($v['bs'] == 'liuyiba') {
                    if ($v['bs'] == 'liuyiba' && !$userInfo['otherKey']['liuyiba']['allowKey']) {
                        unset($list[$k]);
                    }
                }}else{
                unset($list[$k]);
                $branch=$v;
            }
        }
        $list=array_values($list);
        $res['branch']=$branch;
        $res['list']=$list;
        return parent::returnMsg(1,$res,'获取成功');
    }

    /*
     * 用户直播间登陆，通过openid返回用户信息
     */
    public function liveUserInfo(){
        $openId=input('param.openid');
        $roomId=input('param.roomid');
        if($openId !='' && $roomId !=''){
            $getINfo=Db::name('wx_user')->where('open_id|mobile',$openId)->field('open_id,mobile,token,time_out')->find();
            $log=['openid'=>$getINfo['open_id']?$getINfo['open_id']:$openId,'roomid'=>$roomId,'insert_time'=>date('Y-m-d H:i:s')];
            auto_worker('live_room_user',$log,'log');
            if($getINfo){
                if($getINfo['time_out']<time()){
                    $time_out = strtotime("+1 days");
                    Db::name('wx_user')->where('token', $getINfo['token'])->update(['time_out' => $time_out]);
                }
                $userInfo=Db::table('ims_bj_shopn_member')->alias('m')->join('wx_user u','m.mobile=u.mobile')->where('m.mobile',$getINfo['mobile'])->field('m.id,m.storeid,m.isadmin,m.code,m.staffid,m.mobile,u.token')->find();
                if($userInfo){
                    $storeid=$userInfo['storeid'];
                    if($userInfo['isadmin']){
                        $role=1;//店老板
                        $roleText='店老板';//店老板
                    }else{
                        if($userInfo['id']==$userInfo['staffid'] || strlen($userInfo['code'])){
                            $role=2;//美容师
                            $roleText='美容师';//美容师
                        }else{
                            $role=3;//顾客
                            $roleText='顾客';//顾客
                        }
                    }
                    $roomInfo=Db::name('wechat_live')->where(['roomid'=>$roomId])->find();
                    if($roomInfo){
                        $allow=1;//默认都有资格观看
                        //观看对象是部分门店，查看该用户是否在允许门店内
                        if($roomInfo['live_object']){
                            if(!in_array($storeid,explode(',',$roomInfo['live_object_sign']))){
                                $allow=0;
                            }
                        }
                        //检测角色是否允许观看
                        $roleArr=explode(',',$roomInfo['live_role']);
                        if(!in_array($role,$roleArr)){
                            $allow=0;
                        }
                        $code = 1;
                        $data = ['uid'=>$userInfo['id'],'mobile'=>$userInfo['mobile'],'token'=>$getINfo['token'],'live'=>$allow,'role'=>$roleText];
                        $msg = '获取成功';
                    }else{
                        $code = 0;
                        $data = '';
                        $msg = '无此直播间id';
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '无此用户';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '无此用户';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

}