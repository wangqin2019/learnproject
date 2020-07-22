<?php
namespace app\websocket\controller;
use \GatewayWorker\Lib\Gateway;
class WsSock{
   
    public function helloAction () {
        $uid = $_GET['uid'];
        session('uid', $uid);

        $view = new View;
        return $view->fetch();
    }

    public function BindClientIdAction () {
        
        $client_id = $_POST['client_id'];
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';

        $bindUid = session('uid');
        // 假设用户已经登录，用户uid和群组id在session中
        // client_id与uid绑定
        Gateway::bindUid($client_id, $bindUid);
        // 加入某个群组（可调用多次加入多个群组）
        // Gateway::joinGroup($client_id, $group_id);
    }

    public function AjaxSendMessageAction () {
        $message = '你下的订单有误';
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';

        GateWay::sendToAll($message);
    }
}