<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/8/7
 * Time: 10:21
 */
namespace app\api\controller\v2;
//活动类
use app\api\controller\Base;
use app\api\model\ActivitiesMod;
use app\api\service\ActiviteSer;
use think\Log;
class Activities extends Base
{
    private $code = 0;
    private $data = [];
    private $msg = '暂无数据';

    /*
     * 功能:登录有奖活动 => 改为1.18直播活动预热,每天登陆弹窗显示不同活动预热图片
     * 请求:user_id=>用户id,device_type=>设备类型
     * 返回:json
     * */
    public function loginActivities()
    {
        $arr['user_id'] = input('user_id','');
        $arr['device_type'] = input('device_type','');

        $arr['dt'] = date('Y-m-d H:i:s');// 当前系统时间
        $actser = new ActiviteSer();
        $rest = $actser->homePopImg($arr['user_id']);
        if(!empty($rest)){
            $this->code = 1;
            $this->data = $rest;
            $this->msg = '获取成功';
        }else{
            $this->code = 0;
            $this->data = (object)$this->data;
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    /*
     * 功能:登录有奖活动
     * 请求:user_id=>用户id,device_type=>设备类型
     * 返回:json
     * */
    public function loginActivities0114()
    {
        $arr['user_id'] = input('user_id','');
        $arr['device_type'] = input('device_type','');

        $actmod = new ActivitiesMod();
        $arr['dt'] = date('Y-m-d H:i:s');
        $rest = $actmod->selAct($arr);
        if($rest){
            $this->code = 1;
            $this->data = $rest;
            $this->msg = '登录领奖成功';
            // 发短信通知
            $arr1['user_id'] = $arr['user_id'];
            $arr1['prize_id'] = $rest['prize_id'];
            $res = $actmod->sendSms($arr1);
        }else{
            $this->data = (object)$this->data;
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
	/**
     * 首页弹窗广告版本2
     * @param int $user_id 用户id
     * @param string $device_type 设备号
     * @return string
     */
    public function loginActivitiesV2()
    {
        // 初始化参数
        $this->code = 0;
        $this->data = (object)[];
        $this->msg = '获取失败';
        // 获取请求参数
        $arr['user_id'] = input('user_id','');
        $arr['device_type'] = input('device_type','');
        $arr['appVersion'] = input('appVersion',0);
        if(!($arr['user_id'] && $arr['device_type'])){
            $this->msg = '请求参数不全';
            return $this->returnMsg($this->code,$this->data,$this->msg);
        }

        $arr['dt'] = date('Y-m-d H:i:s');// 当前系统时间
        $actser = new ActiviteSer();
        $rest = $actser->homePopImgV2($arr['user_id'],$arr['appVersion']);
        if($rest){
            $this->code = 1;
            $this->data = $rest;
            $this->msg = '获取成功';
        }
        $str = json_encode($this->data);
        Log::info('loginActivitiesV2下发数据:'.$str);
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
}