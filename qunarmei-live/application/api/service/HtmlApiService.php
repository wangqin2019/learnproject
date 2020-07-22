<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:36
 */

namespace app\api\service;

use think\Db;
use think\Log;
class HtmlApiService extends BaseSer
{
    public function sendSms1($mobile,$id_temp,$arr)
    {
        // 请求数据
        $str1 = "mobile=".$mobile."&name=qunarmeiApp&pwd=qunarmeiApp&template=".$id_temp."&type=1";
        if($arr && is_array($arr)){
            $arr = json_encode($arr);
        }
        $str1 = 'code='.$arr.'&'.$str1;
        $key = md5($str1);
        $data = $str1.'&key='.$key;
        // 接口地址
        $url = config('url')['sms_url'].'?'.$data;
        $rest = file_get_contents($url);
        Log::info('短信url:'.$url.'-发送结果:'.$rest);
    }
    /**
     * 验证码校验
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @param string $live_id 直播间id
     * @return array
     */
    public function codeCheck($mobile,$code,$live_id)
    {
        $this->msg = '验证码不正确';
        $this->code = 0;
        $key = 'appc_livecode'.$mobile;
        $codeF = $this->getRedisData($key);
        if($codeF == $code){
            $this->code = 1;
            $this->msg = '验证码正确';
            // 根据直播间id查询直播间信息
            $map['id'] = $live_id;
            $res_live = Db::table('think_live')->where($map)->limit(1)->find();
            if($res_live){
                $callser = new CallBackSer();
                $callser->addWatchLiveLog($res_live['chat_id'],[$mobile],1,2);
            }

        }elseif(empty($codeF)){
            $this->msg = '验证码已过期';
        }
        return $this->returnArr();
    }
    /**
     * 手机号验证
     * @param string $mobile 手机号
     * @return array
     */
    public function mobileCheck($mobile,$live_id)
    {
        $userser = new User();
        $map['mobile'] = $mobile;
        $res = $userser->getUser($map);
        $this->code = 1;
        $this->data['is_register'] = 0;
        $this->msg = '用户未注册';
        if($res){
            $this->msg = '用户已注册';
            $this->data['is_register'] = 1;
            // 根据直播间id查询直播间信息
            $map1['id'] = $live_id;
            $res_live = Db::table('think_live')->where($map1)->limit(1)->find();
            if($res_live){
                $callser = new CallBackSer();
                $callser->addWatchLiveLog($res_live['chat_id'],[$mobile],1,2);
            }
        }else{
            // 下发验证码
            $code = rand(100000,999999);
            // 验证码存入redis
            $this->data['code'] = $code;
            $key = 'appc_livecode'.$mobile;
            $this->setRedisData($key,$code,60);
            // 下发短信验证码
            $this->sendSms1($mobile,1,$code);
        }
        return $this->returnArr();
    }
}