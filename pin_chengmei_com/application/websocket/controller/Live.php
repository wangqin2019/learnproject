<?php
namespace app\websocket\controller;
use com\Gateway;
use think\Db;

class Live extends Base
{
    //用户绑定
    public function bind(){
        $mobile=input('param.mobile');
        $client_id=input('param.client_id');
        //$groupId=input('param.groupId','');
        Gateway::bindUid($client_id,$mobile);
        Gateway::joinGroup($client_id,'live');
        Gateway::setSession($client_id, ['live_mobile'=>$mobile,'live_stay'=>1]);
        $mobile11=Gateway::getUidByClientId($client_id);
        $msg=['scene'=>'initData','mobile'=>$mobile11];
        Gateway::sendToUid($mobile,json_encode($msg));
    }
    //获取初始数据
    public function get_live(){
        $mobile=input('param.mobile');
        $array=Db::name('live_url')->where('id',1)->find();
        if($array['flag']){
            $url = $array['live_url'];
        }else{
            $url = $array['preheat_url'];
        }
        $this->user_action($mobile,'in');//记录进入
        $msg = ['scene'=>'live','live_url' => $url];
        Gateway::sendToUid($mobile,json_encode($msg));
    }

    //记录用户进入离开动作
     public function user_action($mobile,$type){
        $arr=['live'=>'live','mobile'=>$mobile,'type'=>$type,'action_time'=>time()];
        self::$redis->lPush('live_user_action',json_encode($arr));
    }

    //用户离开
    public function leave(){
        $mobile=input('param.mobile');
        $this->user_action($mobile,'out');//记录离开
    }

    //redis数据入库
    public function user_pop(){
      $pop=self::$redis->rPop('live_user_action');
      $data=json_decode($pop,true);
      if ($data){
          $map['mobile']=array('eq',$data['mobile']);
          $map['item_name']=array('eq','live');
          $info=Db::name('user_stay')->where($map)->order('id desc')->find();
          if(!$info && $data['type']=='in'){
              Db::name('user_stay')->insert(['item_name'=>$data['live'],'mobile'=>$data['mobile'],'login_time'=>$data['action_time'],'leave_time'=>$data['action_time'],'stay_time'=>0]);
          }else{
              if($data['type']=='out'){
                  if($info['stay_time']==0){
                      $stay=$data['action_time']-$info['login_time'];
                      Db::name('user_stay')->where('id',$info['id'])->update(['leave_time'=>$data['action_time'],'stay_time'=>round($stay,2)]);
                  }
              }else{
                  Db::name('user_stay')->insert(['item_name'=>$data['live'],'mobile'=>$data['mobile'],'login_time'=>$data['action_time'],'leave_time'=>$data['action_time'],'stay_time'=>0]);
              }
          }
      }
    }

}