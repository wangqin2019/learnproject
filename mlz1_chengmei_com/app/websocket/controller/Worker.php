<?php
namespace app\websocket\controller;

use GatewayWorker\Lib\Gateway;
use app\websocket\model\LiveUser;
use think\facade\Db;
class Worker
{
    /**
     * 获取多个群组在线人数
     * @param  [string] $chat_ids [多个,分割]
     * @return [type]           [description]
     */
    public function getChatCnt($chat_ids)
    {
        $rest = [];
        $chatis1 = explode(',', $chat_ids);
        foreach ($chatis1 as $v) {
            $res = Gateway::getClientIdCountByGroup($v);
            $res1['chat_id'] = $v;
            $res1['num'] = $res;
            $rest[] = $res1;
        }
        return $rest;
    }
	/**连接时触发
     * @param $client_id
     * @param $data
     */
    public static function onWebSocketConnect($client_id,$data){
    	$msg['scene'] = 'connect_success';
    	$msg['client_id'] = $client_id;
    	$rest = json_encode($msg);
        // 直接带用户信息绑定到群组
        // Gateway::sendToCurrentClient($rest);
        // 获取用户get请求参数
        $arr = $data['get'];
        // var_export($arr);
        // 绑定bind方法
        Gateway::bindUid($client_id,$arr['mobile']);// 绑定唯一标识id和用户号码
        Gateway::joinGroup($client_id,$arr['chat_id']);// 把用户加入群组
        Gateway::setSession($client_id, ['mobile'=>$arr['mobile'],'chat_id'=>$arr['chat_id'],'state'=>1]);// 存入数据到session中
        $mobile11=Gateway::getUidByClientId($client_id);// 通过唯一id反向获取用户号码
        $msg=['scene'=>'initData','client_id'=>$client_id,'mobile'=>$mobile11];// 下发数据到客户端
        // 查询数据是否存在
        $map['mobile'] = $arr['mobile'];
        $map['chat_id'] = $arr['chat_id'];
        // $resl = LiveUser::get($map);
        $resl = Db::table('scrm_live_user')->where($map)->limit(1)->find();
        if ($resl) {
          // 修改状态
          $data1['state'] = 1;
          // LiveUser::update($map,$data1);
          Db::table('scrm_live_user')->where($map)->update($data1);
        }else{
          // 存入记录数据到数据库
          $data1['mobile'] = $arr['mobile'];
          $dt = date('Y-m-d H:i:s');
          $data1['register_socket_time'] = $dt;
          $data1['chat_id'] = $arr['chat_id'];
          $data1['state'] = 1;
          $data1['create_time'] = $dt;
          $data1['client_id'] = $client_id;
          // LiveUser::create($data1);
          Db::table('scrm_live_user')->insert($data1);
        }
        
        // $rediser = new RedisSer();
        // // 查询redis数据是否存在
        // $key = 'live_chat_'.$arr['chat_id'].'_'.$arr['mobile'];
        // $res = $rediser->get($key);
        // if ($res) {
        // 	$res1 = json_decode($res,true);
        // 	if ($res1['state'] == 0) {
        // 		$res1['state'] = 1;
        // 		$val = json_encode($res1);
        // 		$this->set($key,$val,86400);// 默认保存1天,同步到数据库后删除
        // 	}
        // }else{
        // 	$dt = date('Y-m-d H:i:s');
        // 	$val1['mobile'] = $arr['mobile'];
        // 	$val1['register_socket_time'] = $dt;
        // 	$val1['chat_id'] = $arr['chat_id'];
        // 	$val1['client_id'] = $client_id;
        // 	$val1['state'] = 1;
        // 	$val1['create_time'] = $dt;
        // 	$val = json_encode($val1);
        // 	// 添加
        // 	$rediser->set($key,$val,86400);// 默认保存1天,同步到数据库后删除
        // }
        Gateway::sendToCurrentClient(json_encode($msg));
    }


    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public static function onMessage($client_id,$data){
        Gateway::sendToCurrentClient($data);
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public static function onClose($client_id){
        // 修改mobile对应的状态和时间
        $data['last_heartbeat_time'] = date('Y-m-d H:i:s');
        $data['state'] = 0;
        $chat_id = $_SESSION['chat_id'];
        // $map['client_id'] = $client_id;
        $map['chat_id'] = $chat_id;
        $map['mobile'] = $_SESSION['mobile'];
        LiveUser::update($data,$map);
        // $rest = json_encode($data);
        // var_export($_SESSION);
        $rest['client_id'] = $client_id;
        $rest['scene'] = 'client_close';
        $rest['cnt'] = Gateway::getClientIdCountByGroup($chat_id);
        // 更新用户状态为0
        // $key = 'live_chat_'.$chat_id.'_'.$map['mobile'];
        // $rediser = new RedisSer();
        // $res1 = $rediser->get($key);
        // if ($res1) {
        // 	$res1 = json_decode($res1,true);
        // 	// 更新状态和最后1次心跳时间
        // 	$res1['last_heartbeat_time'] = $data['last_heartbeat_time'];
        // 	$res1['state'] = 0;
        // 	$val = json_encode($res1);
        // 	$rediser->set($key,$val,86400);// 默认保存1天,同步到数据库后删除
        // }
        $rest = json_encode($rest);
        // Gateway::sendToAll($rest);
    }
}