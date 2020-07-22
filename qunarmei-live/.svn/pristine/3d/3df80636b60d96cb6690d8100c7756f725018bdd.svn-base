<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/20
 * Time: 16:34
 */

namespace app\api\service;
use think\Db;


/**
 * 第三方回调相关处理服务
 */
class CallBackSer
{
    /**
     * 记录用户进出聊天室时间
     * @param $chat_id
     * @param $mobiles
     * @param $type
     */
    public function addWatchLiveLog($chat_id,$mobiles,$type,$see_type=1)
    {
        // 进入redis队列
        $redisSer = new RedisSer();
        $key = 'seeLive_'.$chat_id;
        foreach ($mobiles as $v) {
            $val = [
                'chat_id' => $chat_id,
                'mobile' => $v,
                'type' => $type,
                'see_type' => $see_type,
                'create_time' => time(),
                'status' => ''
            ];
            // 查询当前直播状态
            $map['chat_id'] = $chat_id;
            $res = Db::table('think_live')->where($map)->limit(1)->find();
            if($res){
                $val['status'] = $res['statu'];
            }
            $val = json_encode($val);
            $redisSer->pushQueue($key,$val);
        }
    }
}