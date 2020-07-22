<?php
namespace app\live\controller;
use think\Controller;
use think\Db;
use weixinaes\wxBizDataCrypt;

/**
 * swagger: 用户中心
 */
class User extends Base
{
    private static $appId='wxb7dca4d4a8f9b78d';
    private static $appSecret='8a4b2bcde9e3a9f6081da0936f75f247';

    //通过code获取用户openid及session_key及生成用户token
    public function getToken(){
        $appid=self::$appId;
        $appsecret=self::$appSecret;
        $js_code=input('param.code');
        $nickname=input('param.nickName','');
        $avatar=input('param.avatarUrl','');
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appsecret."&js_code=".$js_code."&grant_type=authorization_code";
        $mobile='';
        try {
            $info = httpGet($url);
            $info = json_decode($info, true);
            //logs(date('Y-m-d H:i:s').':'.json_encode($info),'getToken');
            $userInfo = Db::name('wx_live_user')->where('open_id', $info['openid'])->find();
            $token=createToken();
            if (is_array($userInfo) && count($userInfo)) {
                $time_out=strtotime("+1 days");
                Db::name('wx_live_user')->where('open_id', $info['openid'])->update(['session_key' => $info['session_key'],'token'=>$token,'nickname'=>$nickname,'avatar'=>$avatar,'time_out'=>$time_out]);
                $mobile=$userInfo['mobile'];
            } else {
                $time_out=strtotime("+1 days");
                Db::name('wx_live_user')->insert(['open_id' => $info['openid'], 'session_key' => $info['session_key'],'token'=>$token,'nickname'=>$nickname,'avatar'=>$avatar,'time_out'=>$time_out]);
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
        $token=input('param.token');
        if(parent::checkToken($token)) {
            $iv = input('param.iv');
            $encryptedData = input('param.encryptedData');
            if ($iv != '' || $encryptedData != '') {
                $getSession = Db::name('wx_live_user')->where('token', $token)->value('session_key');
                if ($getSession) {
                    $aes = new wxBizDataCrypt(self::$appId, $getSession);
                    $errCode = $aes->decryptData($encryptedData, $iv, $data1);
                    if ($errCode == 0) {
                        $data1 = json_decode($data1, true);
                        Db::name('wx_live_user')->where('token', $token)->update(['mobile' => $data1['phoneNumber']]);
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
	
	    //检测315开关
    public function checkActFlag(){
        $mobile=input('param.mobile');
        $allow=['15888888888'];
        if($mobile){
            if(in_array($mobile,$allow)){
                $code = 1;
                $data = 0;
                $msg = '获取成功';
            }else{
                $info=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.mobile',$mobile)->field('m.id,b.act_flag315')->find();
                if($info){
                    $code = 1;
                    $data = $info['act_flag315'];
                    $msg = '获取成功';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '用户不存在';
                }
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '号码不许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

}