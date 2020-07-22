<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:36
 */

namespace app\neibu\service;
use app\api\service\BaseSer;
//整合腾讯云通信扩展
use tencent_cloud\TimChat;
use think\Db;
/**
 * java相关接口服务类
 */
class JavaService extends BaseSer
{
    /**
     * 获取腾讯聊天室人数
     * @param  [string] $chat_id [聊天室id]
     * @return
     */
    public function getChatCnt($chat_id)
    {
        $chat_ids = explode(',', $chat_id);
        $data = [];
        $this->code = 1;
        $this->msg = '暂无数据';
        $tentser = new TimChat();
        $reschat = $tentser->getChatCntList($chat_ids);
        if ($reschat) {
            foreach ($reschat as $k => $v) {
                $data1['chat_id'] = $v['chat_id'];
                $data1['chat_num'] = $v['chat_num'];
                $data[] = $data1;
            }
        }
        if ($data) {
            $this->msg = '获取成功';
            $this->data = $data;
        }
        return $this->returnArr();
    }
}