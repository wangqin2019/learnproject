<?php

namespace app\websocket\controller;
use app\websocket\model\LiveUser;
/**
 * 处理日常任务
 */
class Job extends Base
{
    /**
     * 获取当前Gateway群组在线人数
     * @param [string] $chat_id [群组id,多个,分割]
     * @return [string] [json数据]
     */
    public function getChatNum()
    {
        $chat_id = input('chat_id');

        $work = new Worker();
        $res = $work->getChatCnt($chat_id);
        if ($res) {
            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = $res;
        }
        return $this->returnMsg();
    }
    /**
     * 同步redis数据到mysql
     * @return [type] [description]
     */
    public function redisToMysql()
    {
        $arr['code'] = 0;
        $arr['msg'] = '暂无websocket数据需要同步数据库';
        $arr['data'] = [];

    	$key = 'live_chat_*';
        // var_dump($key);
    	$keys = self::$redis->getKeys($key);
        // 获取key开头的所有数据
        if ($keys) {
            $rest = [];
            foreach ($keys as $k => $v) {
                $rest[] = self::$redis->get($v);
                // 删除缓存
                self::$redis->delAll($v);
            }
            // 插入到mysql,删除对应的缓存
            $live_user_mod = new LiveUser();
            $data = $live_user_mod->saveAll($rest);
            $arr['data'] = $data->id;
            $arr['code'] = 1;
            $arr['msg'] = 'redis数据同步mysql成功';
        }
        return json($arr);
    }
}