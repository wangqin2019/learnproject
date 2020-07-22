<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/3
 * Time: 11:43
 */

namespace app\api_public\service;
use tencent_cloud\TimChat;
use think\Db;

class LiveService
{
    /**
     * 获取直播间用户号码
     * @return string
     */
    public function mrsMobiles()
    {
        $mrs_mobile = [];
        // 1.查询在线直播间id
        $mapl['live_source'] = 1;
        $mapl['statu'] = 1;
        $res_live = Db::table('think_live')->where($mapl)->order('insert_time desc')->limit(1)->find();
        if($res_live){
            $chat_id = $res_live['chat_id'];
//            $chat_id = '1509447109';
            // 2.根据聊天室id查询聊天室用户
            $tentser = new TimChat();
            $res_mobile = $tentser->getChatMem($chat_id);
//            dump($res_mobile);die;
//             3.过滤非美容师用户
            if($res_mobile){
                $mobiles = [];
                foreach ($res_mobile as $v) {
                    $mobiles[] = $v['mobile'];
                }
                $mapm['mobile'] = ['in',$mobiles];
                $mapm1 = ' length(code) > 1 ';
//                dump($mapm);die;
                $res_mrs = Db::table('ims_bj_shopn_member')->where($mapm)->order('id desc')->select();
//                dump($res_mrs);die;
                if($res_mrs){
                    foreach ($res_mrs as $vr) {
                        $mrs_mobile[] = $vr['mobile'];
                    }
                }
            }
        }
        return $mrs_mobile;
    }
}