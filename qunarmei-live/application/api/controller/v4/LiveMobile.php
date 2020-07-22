<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:41
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\LiveMobileService;

header('Access-Control-Allow-Origin:*');
/**
 * 手机端直播相关接口
 */
class LiveMobile extends Common
{
    // 服务类
    protected $liveSer;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->liveSer = new LiveMobileService();
    }
    /**
     * 直播订阅/取消
     * @param string $live_id 直播间id
     * @param string $user_id 用户id
     * @param string $type 操作,1:订阅,0:取消
     * @return string json
     */
    public function live_signup()
    {
        $live_id = input('live_id');
        $user_id = input('user_id');
        $type = input('type',1);
        $res = $this->liveSer->liveSignup($live_id,$user_id,$type);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 直播是否订阅
     * @param string $live_id 直播间id
     * @param string $user_id 用户id
     * @return string json
     */
    public function is_live_sign()
    {
        $live_id = input('live_id');
        $user_id = input('user_id');
        $res = $this->liveSer->liveSign($live_id,$user_id);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 直播详情
     * @param string $live_id 直播间id
     * @param string $user_id 用户id
     * @return string json
     */
    public function live_detail()
    {
        $live_id = input('live_id');
        $user_id = input('user_id');
        $res = $this->liveSer->liveDetail($live_id,$user_id);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 直播当前人数
     * @param string $chat_id 聊天室id
     */
    public function live_numbers()
    {
        $chat_id = input('chat_id');
        $res = $this->liveSer->liveNumbers($chat_id);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 直播信息人数调整
     * @param int $chat_id 聊天室id
     * @param int $minute 多少分钟
     * @param int $nums 达到多少人
     */
    public function live_numbers_adjust()
    {
        $chat_id = input('chat_id');
        $minute = input('minute');
        $nums = input('nums');
        $res = $this->liveSer->liveNumbersAdjustment($chat_id,$minute,$nums);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 主播关闭直播间后的直播信息
     * @param int $live_id 直播间id
     */
    public function live_end()
    {
        $live_id = input('live_id');
        $res = $this->liveSer->liveEnd($live_id);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 主播主动关闭记录时长
     * @param int $live_id 直播间id
     * @param string $length 时长
     */
    public function close_live()
    {
        $live_id = input('live_id');
        $length = input('length');
        $res = $this->liveSer->closeLive($live_id,$length);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 断流续播
     * @param string $mobile 主播号码
     */
    public function continue_live()
    {
        $mobile = input('mobile');
        $res = $this->liveSer->continueLive($mobile);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 是否有可续接的流直播
     * @param string $mobile 主播号码
     */
    public function whether_continue()
    {
        $mobile = input('mobile');
        $res = $this->liveSer->whetherContinue($mobile);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}