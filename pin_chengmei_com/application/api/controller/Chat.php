<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * swagger: 支付回调
 */
class Chat extends Base
{
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
        if($token==''){
            $code = 400;
            $data = '';
            $msg = '非法请求';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }else{
            if(!parent::checkToken($token)) {
                $code = 400;
                $data = '';
                $msg = '用户登陆信息过期，请重新登录！';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }else{
                return true;
            }
        }
    }

    public function reg(){
        $getId=Db::table('think_live')->where('statu',1)->value('chat_id');
        $mobile=input('param.mobile');
        $url="http://live.qunarmei.com/api/live/getSig?type=0&mobile=".$mobile;
        $res=httpGet($url);
        $json=json_decode($res,true);
//        $json['roomId']=$getId?$getId:0;
        $json['roomId']=0;
        return $json;
    }
}