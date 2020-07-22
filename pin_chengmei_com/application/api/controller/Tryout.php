<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:试用
 */
class Tryout extends Base
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

    /**
     * 检查申请试用
     * @return \think\response\Json
     */
    public function tryout_check()
    {
        $pid=input('param.pid');
        $uid=input('param.uid');
        $is_tryout =Db::name('tryout_log')->where(['uid'=>$uid,'tryout_id'=>$pid])->count();
        if(!$is_tryout) {
            $code = 1;
            $data = '';
            $msg = '可以申领！';
        }else{
            $code = 0;
            $data = '';
            $msg = '您已经申请过了！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 申请试用
     * @return \think\response\Json
     */
    public function tryout_now()
    {
        $pid=input('param.pid');
        $uid=input('param.uid');
        $getNum=Db::name('tryout')->where('id',$pid)->value('tryout_num');
        $getTryoutNum=Db::name('tryout_log')->where('tryout_id',$pid)->count();
        if($getNum>0 && $getTryoutNum>=$getNum){
            $code = 0;
            $data = '';
            $msg = '申请已达最大限额！';
        }else{
            $is_tryout =Db::name('tryout_log')->where(['uid'=>$uid,'tryout_id'=>$pid])->count();
            if(!$is_tryout) {
                $arr = array('uid' => $uid, 'tryout_id' => $pid, 'insert_time' => time());
                $res=Db::name('tryout_log')->insert($arr);
                if($res){
//                    $arr1=array('uid'=>$uid,'title'=>'试用申请通知','content'=>"恭喜您获得了我们为您送出的圣诞礼物：".$drawInfo['prize_name']."，如有问题，请联系您所属美容师！");
//                    sendDrawQueue($arr1);
                    $code = 1;
                    $data = '';
                    $msg = '申请试用成功！';
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '申请试用失败！';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '您已经申请过了！';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

}