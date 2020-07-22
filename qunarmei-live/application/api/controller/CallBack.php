<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/20
 * Time: 10:18
 */

namespace app\api\controller;
use app\api\service\CallBackSer;
use app\api\service\TicketSer;
use think\Log;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
/**
 * 第三方回调相关处理
 */
class CallBack
{
    // 腾讯云appid
    protected $sdk_appid = '1400047679';

    public function tentCloud()
    {
        // 获取请求数据
        $arr['sdk_appid'] = input('SdkAppid');
        $arr['callback_command'] = input('CallbackCommand');
        $arr['content_type'] = input('contenttype');
        $arr['client_ip'] = input('ClientIP');
        $arr['optplatform'] = input('OptPlatform');


        if($this->sdk_appid == $arr['sdk_appid']) {
            $mobiles = [];
            $chat_id = '';
            $arr['data'] = file_get_contents('php://input');
            if ($arr['data']) {
                $arr['data'] = json_decode($arr['data'], true);
                
                $chat_id = $arr['data']['GroupId'];
            }
            $callbackSer = new CallBackSer();
            if ($arr['callback_command'] == 'Group.CallbackAfterNewMemberJoin') {
                // 入群回调
                $members = $arr['data']['NewMemberList'];
                foreach ($members as $v) {
                    $mobiles[] = $v['Member_Account'];
                }
                $callbackSer->addWatchLiveLog($chat_id,$mobiles, 1);
            } elseif ($arr['callback_command'] == 'Group.CallbackAfterMemberExit') {
                // 出群回调
                $members = $arr['data']['ExitMemberList'];
                foreach ($members as $v) {
                    $mobiles[] = $v['Member_Account'];
                }
                $callbackSer->addWatchLiveLog($chat_id,$mobiles, 2);
            }
        }

        $data['ActionStatus'] = 'OK';
        $data['ErrorInfo'] = '';
        $data['ErrorCode'] = 0;
        return json($data);
    }

    /**
     * 内部方法异步执行的回调上传图片到七牛
     * @return string
     */
    public function ybUpimg()
    {
        set_time_limit(0);
        $str = input('str');
        if($str){
            $arr = json_decode($str,true);
            $tickSer = new TicketSer();
            foreach ($arr as $v) {
                $img_url = $tickSer->makeEwm($v['code'],$v['img']);
            }
        }
        Log::info('ybUpimg-'.date('Y-m-d H:i:s').'-str:'.$str);
        return '图片上传七牛异步执行成功!';
    }
}