<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/4/9
 * Time: 18:53
 */

namespace app\admin\controller;
use think\Exception;

/**
 * Class SerNotice 消息通知服务
 * @package app\admin\controller
 */
class SerNotice
{
    // 站内信通知测试url
    protected $urlPush = 'http://test.api.app.qunarmei.com/qunamei/pushmessage';
    // 本地发送短信测试url
    protected $urlSms = 'http://sms.qunarmei.com/sms.php';
    /**
     * 站内信通知
     * @param $type 消息类型 1订单消息2会员注册3邀约码4普通文本5富文本
     * @param int $pushtype alias单人,tag标签,all全部
     * @param string $target 目标人号码
     * @param string $content
     */
    public function sendJpush($type=5,$pushtype='alias',$target='',$content='')
    {
        try{
            $data['type'] = $type;
            $data['pushtype'] = $pushtype;
            $data['target'] = $target;
            $data['content'] = $content;
            $res = curl_post($this->urlPush,$data);
        }catch(Exception $e){
            $res = $e->getMessage();
        }
        return $res;
    }

    /**
     * 发送短信通知
     * @param $mobile
     * @param $template_id
     * @return mixed|string
     */
    public function sendSms($mobile,$template_id)
    {
        try{
            $str = "mobile=".$mobile."&name=qunarmeiApp&pwd=qunarmeiApp&template=".$template_id."&type=1";
            $key = md5($str);
            $url = $this->urlSms.'?'.$str.'&key='.$key;
            $res = curl_get($url);
        }catch(Exception $e){
            $res = $e->getMessage();
        }
//       echo '<pre>';print_r($res);die;
        return $res;
    }
}