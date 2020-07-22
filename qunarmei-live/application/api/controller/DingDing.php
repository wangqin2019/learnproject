<?php
namespace app\api\controller;
use think\Db;
/**
* 钉钉服务相关类
*/
class DingDing
{   
    /**
     * 查询消息配置
     * @return array
     */
    public function getNoticeConf()
    {
        $mobiles = [];
        $map['type'] = 1;
        $map['delete_time'] = 0;
        $res2 = Db::table('think_notice_conf')->where($map)->limit(1)->find();
        if ($res2 && $res2['mobiles']) {
            $mobiles = explode(',',$res2['mobiles']);
        }
        return $mobiles;
    }
    /*
     * 功能:发送钉钉消息接口
     * 请求:$arr1=>[mobile=>手机号码,type=>消息类型，1 text消息、 2 link消息,content=>消息内容,title=>消息标题]
     * 返回:json请求结果
     * */
    function sendMsg($arr1=null){
        // 测试数据
//        $arr1=['mobile'=>'15921324164','title'=>'店务App里您有新的订单待处理','content'=>(date('Y-m-d H:i:s').'请登录店务App处理您管理门店下新的订单申请')];
        // 接口地址
        $dingding_url = config('url.dingding_url').'dingding/message.shtml';

        $data['mobiles'] = [$arr1['mobile']];
        $data['type'] = isset($arr1['type'])?$arr1['type']:1;
        $data['title'] = $arr1['title'];
        $data['content'] = $arr1['content'].' ('.date('Y-m-d H:i:s').')';
        $data1 = json_encode($data,JSON_UNESCAPED_UNICODE);
        $rest = curl_post_json(['url'=>$dingding_url,'data'=>$data1]);
        return $rest;
    }
}