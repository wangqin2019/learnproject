<?php

namespace app\neibu\controller;
use think\Controller;
use app\neibu\service\JavaService;
/**
 * 外部java相关接口调用
 */
class Java extends Base
{
	/**
	 * 获取腾讯聊天室人数
	 * @param  [string] $chat_id [聊天室id,多个,分割]
	 * @return
	 */
    public function get_chat_cnt()
    {
        $chat_id = input('chat_id');
        $javaser = new JavaService();
        $res = $javaser->getChatCnt($chat_id);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}