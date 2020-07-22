<?php
namespace app\qmjk\controller;
use app\api\model\MemberModel;
use think\Db;
use weixinaes\wxBizDataCrypt;

/**
 * swagger: 用户中心
 */
class User extends Base
{

    //通过code获取用户openid及session_key及生成用户token
    public function getToken(){
        $appid=$this->jkConfig['appId'];
        $appsecret=$this->jkConfig['appSecret'];
        $js_code=input('param.code');
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$js_code."&grant_type=authorization_code";
        $mobile='';
        try {
            $info = httpGet($url);
            $info = json_decode($info, true);
            if($info['openid']) {
                $userInfo = Db::name('qmjk_wx_user')->where('open_id', $info['openid'])->find();
                $token = createToken();
                if (is_array($userInfo) && count($userInfo)) {
                    $time_out = strtotime("+1 days");
                    Db::name('qmjk_wx_user')->where('open_id', $info['openid'])->update(['session_key' => $info['session_key'], 'token' => $token, 'time_out' => $time_out]);
                    $mobile = $userInfo['mobile'];
                } else {
                    $time_out = strtotime("+1 days");
                    Db::name('qmjk_wx_user')->insert(['open_id' => $info['openid'], 'session_key' => $info['session_key'], 'token' => $token, 'time_out' => $time_out]);
                }
                $code = 1;
                $data = ['token' => $token, 'mobile' => $mobile];
                $msg = '数据获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '数据获取失败';
            }
        }catch (\Exception $e){
            $code = 0;
            $data = '';
            $msg = '数据获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 获取用户电话号码
     */
    public function getMobile(){
            $type = input('param.type', 0);//搜索进入未注册用户为0。扫描诚美门店注册码type为1 扫描美容院集客码为2 扫描异业门店集客码类型为3
            $fid = input('param.fid', 0);//如果是新增 需将引导人id传入
            $token = input('param.token');
            $mobile = input('param.mobile');
            $nickName = input('param.nickName');
            $avatar = input('param.avatar', '');
            if (parent::checkToken($token)) {
                if (strlen($mobile) == 11) {
                    $userMobile = $mobile;
                    $getType = Db::name('qmjk_member')->where('mobile', $mobile)->field('type')->find();
                    if ($getType) {
                        $type = $getType['type'];
                    }
                } else {
                    $iv = input('param.iv');
                    $encryptedData = input('param.encryptedData');
                    if ($iv != '' || $encryptedData != '') {
                        $getSession = Db::name('qmjk_wx_user')->where('token', $token)->value('session_key');
                        if ($getSession) {
                            $aes = new wxBizDataCrypt($this->jkConfig['appId'], $getSession);
                            $errCode = $aes->decryptData($encryptedData, $iv, $data1);
                            if ($errCode == 0) {
                                $data1 = json_decode($data1, true);
                                if ($data1['phoneNumber'] != '') {
                                    Db::name('qmjk_wx_user')->where('token', $token)->update(['mobile' => $data1['phoneNumber'], 'nickname' => $nickName, 'avatar' => $avatar]);
                                    $userMobile = $data1['phoneNumber'];
                                } else {
                                    $code = 0;
                                    $data = '';
                                    $msg = '手机号码获取失败';
                                    return parent::returnMsg($code, $data, $msg);
                                }
                            } else {
                                $code = 0;
                                $data = '';
                                $msg = '手机号码获取失败';
                                return parent::returnMsg($code, $data, $msg);
                            }
                        } else {
                            $code = 0;
                            $data = '';
                            $msg = '数据获取失败';
                            return parent::returnMsg($code, $data, $msg);
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '请检查参数,iv和encryptedData不能为空！';
                        return parent::returnMsg($code, $data, $msg);
                    }
                }
                $res['type'] = $type;
                $res['fid'] = $fid;
                $res['mobile'] = $userMobile;
                $code = 1;
                $data = $res;
                $msg = '数据获取成功';
            } else {
                $code = 400;
                $data = '';
                $msg = '用户登陆信息过期，请重新登录';
            }
            return parent::returnMsg($code, $data, $msg);
    }

    /**
     * 不同角色跳转
     */
    public function jumpPath() {
        $token=input('param.token');
        $type = input('param.type', 0);//接收进入渠道
        $fid = input('param.fid', 0);//如果是新增 需将引导人id传入
        $mobile = input('param.mobile');
        if(parent::checkToken($token)) {
        try {
            $superAdmin=$this->jkConfig['superAdmin'];
            $superAdminArr=explode('#',$superAdmin);
            //如果qmjk_wx_user表中已经存在过这个电话号码 即该电话有两条记录 就说明换过小程序id 要删除原来的记录
            $mobileCount = Db::name('qmjk_wx_user')->where('mobile', $mobile)->count();
            if ($mobileCount > 1) {
                Db::name('qmjk_wx_user')->where('mobile', $mobile)->limit(1)->order('id asc')->delete();
            }
            if(in_array($mobile,$superAdminArr)){
                $getUser = $this->get_qmjk_user($mobile);
                if(!is_array($getUser)){
                    $getUser['id']=null;
                    $getUser['branch_id']=null;
                    $getUser['union_id']=null;
                    $getUser['name']=null;
                    $getUser['mobile']=$mobile;
                    $getUser['type']=4;
                    $getUser['status']=1;
                }
                $getUser['identity'] = '平台管理员';
                $getUser['role'] = '';
                $res['flag'] = 1;
                $res['info'] = $getUser;
                $res['type'] = 4;
                $res['tips'] = 'flag=1 and type=4 可跳转到美容院列表';
            }else {
                $getType = Db::name('qmjk_member')->where('mobile', $mobile)->field('type')->find();
                if ($getType) {
                    $type = $getType['type'];
                }
                switch ($type) {
                    case 0:
                        //直接跳转到门店注册页面
                        $getUser = $this->get_qmjk_user($mobile);
                        if (is_array($getUser) && count($getUser)) {
                            if ($getUser['bstatus'] == 0) {
                                $code = 0;
                                $data = '';
                                $msg = '您的门店注册申请系统审核中！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            if ($getUser['bstatus'] == 2) {
                                $code = 0;
                                $data = '';
                                $msg = '当前账号关联门店已被冻结！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            if ($getUser['status'] == 0) {
                                $code = 0;
                                $data = '';
                                $msg = '您的账号已被冻结！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            $getUser['identity'] = '美容院';
                            $getUser['role'] = $getUser['role'] ? '店老板' : '店小二';
                            $res['flag'] = 1;
                            $res['info'] = $getUser;
                            $res['type'] = $getUser['type'];
                            $res['tips'] = 'flag=1 and type=1 可跳转到美容院管理页面';
                        } else {
                            $res['flag'] = -1;
                            $res['info'] = '';
                            $res['type'] = $type;
                            $res['tips'] = 'flag=-1 and type=0 可跳转到美容院注册的页面';
                        }
                        break;
                    case 1:
                        //去查询是否已经注册门店 未注册跳转注册 已注册 进入门店主页
                        $getUser = $this->get_qmjk_user($mobile);
                        if (is_array($getUser) && count($getUser)) {
                            if ($getUser['bstatus'] == 0) {
                                $code = 0;
                                $data = '';
                                $msg = '您的门店注册申请系统审核中！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            if ($getUser['bstatus'] == 2) {
                                $code = 0;
                                $data = '';
                                $msg = '当前账号关联门店已被冻结！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            if ($getUser['status'] == 0) {
                                $code = 0;
                                $data = '';
                                $msg = '您的账号已被冻结！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            $getUser['identity'] = '美容院';
                            $getUser['role'] = $getUser['role'] ? '店老板' : '店小二';
                            $res['flag'] = 1;
                            $res['info'] = $getUser;
                            $res['type'] = $getUser['type'];
                            $res['tips'] = 'flag=1 and type=1 可跳转到美容院管理页面';
                        } else {
                            $getUser1 = $this->get_qmjk_user3($mobile);
                            if (is_array($getUser1) && count($getUser1)) {
                                if($getUser1['role']==0 && $getUser1['type']==1){
                                    $getUser1['identity'] = '美容院';
                                    $getUser1['role'] = $getUser1['role'] ? '店老板' : '店小二';
                                    $res['flag'] = 1;
                                    $res['info'] = $getUser1;
                                    $res['type'] = $getUser1['type'];
                                    $res['tips'] = 'flag=1 and type=1 可跳转到美容院管理页面';
                                }else{
                                    $res['flag'] = -1;
                                    $res['info'] = '';
                                    $res['type'] = $type;
                                    $res['tips'] = 'flag=-1 and type=0 可跳转到美容院注册的页面';
                                }
                            }else{
                                $res['flag'] = -1;
                                $res['info'] = '';
                                $res['type'] = $type;
                                $res['tips'] = 'flag=-1 and type=0 可跳转到美容院注册的页面';
                            }

                        }
                        break;
                    case 2:
                        //扫码进入联盟商主页
                        $getUser = $this->get_qmjk_user1($mobile);
                        if (is_array($getUser) && count($getUser)) {
                            if($fid){
                                //获取该fid对应的美容院
                                $getSid=Db::name('qmjk_member')->where('id',$fid)->value('branch_id');
                                //获取关联的美容院
                                $getJoin=Db::name('qmjk_union_relation')->where('union_id',$getUser['union_id'])->column('branch_id');
                                if(in_array($getSid,$getJoin)){
                                    $code = 0;
                                    $data = '';
                                    $msg = '你已经注册过该美容院了';
                                    return parent::returnMsg($code,$data,$msg);
                                }else{
                                    $getUser['identity'] = '联盟商';
                                    $getUser['role'] = '';
                                    $res['flag'] = -3;
                                    $res['info'] = $getUser;
                                    $res['type'] = $getUser['type'];
                                    $res['tips'] = 'flag=-3 and type=2 可跳转到联盟商二次注册页面';
                                }
                            }else{
                                $getUser['identity'] = '联盟商';
                                $getUser['role'] = '';
                                $res['flag'] = 2;
                                $res['info'] = $getUser;
                                $res['type'] = $getUser['type'];
                                $res['tips'] = 'flag=2 and type=2 可跳转到联盟商管理的页面';
                            }
                        }else{
                            $res['flag'] = -2;
                            $res['info'] = '';
                            $res['type'] = $type;
                            $res['tips'] = 'flag=-2 and type=2 可跳转到联盟商注册的页面';
                        }
                        break;
                    case 3:
                        //扫码进行关联 如果有过关联 提示
                        $getUser = $this->get_qmjk_user2($mobile);
                        if (is_array($getUser) && count($getUser)) {
                            if ($getUser['status'] == 2) {
                                $code = 0;
                                $data = '';
                                $msg = '您的账号已被冻结！';
                                return parent::returnMsg($code, $data, $msg);
                            }
                            $getUser['identity'] = '顾客';
                            $getUser['role'] = '';
                            $res['flag'] = 3;
                            $res['info'] = $getUser;
                            $res['type'] = $getUser['type'];
                            $res['tips'] = 'flag=3 and type=3 可跳转到顾客管理页面';
                        } else {
                            $res['flag'] = -3;
                            $res['info'] = '';
                            $res['type'] = $type;
                            $res['tips'] = 'flag=-3 and type=3 可跳转到顾客注册的页面';
                        }
                        break;
                }

            }

            $res['fid']=$fid;
            $code=1;
            $data=$res;
            $msg='信息获取成功';
        }catch (\Exception $e){
            $code=0;
            $data='';
            $msg='错误'.$e->getMessage();
        }
        }else{
            $code = 400;
            $data = '';
            $msg = '用户登陆信息过期，请重新登录';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 根据电话号码获取全民集客用户信息
     */
    function get_qmjk_user($mobile){
        $getUser=Db::name('qmjk_member')->alias('m')->join('qmjk_branch b','m.mobile=b.mobile')->where('m.mobile',$mobile)->field('m.id,m.branch_id,m.union_id,m.name,m.mobile,m.role,m.type,m.status,b.status bstatus')->find();
        return $getUser;
    }


    function get_qmjk_user1($mobile){
        $getUser=Db::name('qmjk_member')->alias('m')->join('qmjk_union u','m.mobile=u.mobile')->where('m.mobile',$mobile)->field('m.id,m.branch_id,m.union_id,m.name,m.mobile,m.role,m.type,m.status,u.status bstatus')->find();
        return $getUser;
    }
    function get_qmjk_user2($mobile){
        $getUser=Db::name('qmjk_member')->alias('m')->join('qmjk_union u','m.mobile=u.mobile','left')->where('m.mobile',$mobile)->field('m.id,m.branch_id,m.union_id,m.name,m.mobile,m.role,m.type,m.status,u.status bstatus')->find();
        return $getUser;
    }
    function get_qmjk_user3($mobile){
        $getUser=Db::name('qmjk_member')->alias('m')->join('qmjk_branch b','m.mobile=b.mobile','left')->where('m.mobile',$mobile)->field('m.id,m.branch_id,m.union_id,m.name,m.mobile,m.role,m.type,m.status,b.status bstatus')->find();
        return $getUser;
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
        $name=md5($scene.$path.$width.$auto_color.json_encode($line_color).$is_hyaline);
        $patch = 'qrcode/qmjk/';
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

}