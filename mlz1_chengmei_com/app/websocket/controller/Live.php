<?php
namespace app\websocket\controller;
use com\Gateway;
use think\Db;
use app\websocket\model\LiveUser;
class Live extends Base
{
    //用户绑定
    public function bind(){
        $mobile = input('param.mobile');// 用户号码
        $client_id = input('param.client_id');// gateway返回的唯一标识id
        $chat_id = input('param.chat_id','');// 聊天室群组id
        Gateway::bindUid($client_id,$mobile);// 绑定唯一标识id和用户号码
        Gateway::joinGroup($client_id,$chat_id);// 把用户加入群组
        Gateway::setSession($client_id, ['mobile'=>$mobile,'chat_id'=>$chat_id,'state'=>1]);// 存入数据到session中
        $mobile11=Gateway::getUidByClientId($client_id);// 通过唯一id反向获取用户号码
        $msg=['scene'=>'initData','mobile'=>$mobile11];// 下发数据到客户端
        // 查询数据是否存在
        $map['mobile'] = $mobile;
        $map['chat_id'] = $chat_id;
        $res = LiveUser::get($map);
        if ($res) {
          // 修改状态
          $data['state'] = 1;
          LiveUser::update($map,$data);
        }else{
          // 存入记录数据到数据库
          $data['mobile'] = $mobile;
          $dt = date('Y-m-d H:i:s');
          $data['register_socket_time'] = $dt;
          $data['chat_id'] = $chat_id;
          $data['state'] = 1;
          $data['create_time'] = $dt;
          $data['client_id'] = $client_id;
          LiveUser::create($data);
        }
        Gateway::sendToUid($mobile,json_encode($msg));
    }
    //获取群组聊天室在线人数
    public function get_chat_num(){
        $chat_id=input('param.chat_id');
        $cnt = Gateway::getClientIdCountByGroup($chat_id);
        $msg = ['type'=>'chat_num','cnt' => $cnt];
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