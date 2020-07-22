<?php
namespace app\websocket\controller;
use think\worker\Server;
use think\Db;

class Push extends Server
{
    protected $socket = 'websocket://0.0.0.0:2348';
    protected $processes =1;

    public function _initialize()
    {
//        $this->processes=1;
    }
    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        // 客户端传递的是json数据
        $message_data = json_decode($data, true);
        if(!$message_data)
        {
            return ;
        }

        // 判断当前客户端是否已经验证,即是否设置了uid
        if(!isset($connection->uid)) {
            $arr = json_decode($data, true);
            // 把第一个包当做uid 具体看前台传值
            $connection->uid = $arr['uid'];
            /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
             * 实现针对特定uid推送数据
             */
            $this->worker->uidConnections[$connection->uid] = $connection;
        }

        $getScene=$message_data['scene'];
        if($getScene=='ping'){
            $arr=json_decode($data,true);
            $result = array('scene'=>'pone','type' => 'one');
            $this->sendMessageByUid($arr['uid'], json_encode($result));
        }else{
            //客户登陆上来 查询当前直播状态 然后返回
            if($arr['scene']=='live') {
                $array=Db::name('live_url')->where('id',1)->find();
                if($array['flag']){
                    $url = $array['live_url'];
                }else{
                    $url = $array['preheat_url'];
                }
                $result = array('scene'=>'live','live_url' => $url, 'type' => 'one');
                $this->sendMessageByUid($arr['uid'], json_encode($result));
            }
        }
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        return;
    }
    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        if(isset($connection->uid))
        {
            // 连接断开时删除映射
            unset($this->worker->uidConnections[$connection->uid]);
        }
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart()
    {
        // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
        $inner_text_worker = new \Workerman\Worker('Text://0.0.0.0:2349');
        $inner_text_worker->onMessage = function($connection, $buffer)
        {
            $data = json_decode($buffer, true);
            //type为all时推送给全部 否则根据uid推动给对应用户
            if($data['scene']=='draw'){
                $uid = 'uid1';
                // 通过workerman，向uid的页面推送数据
                $ret = $this->sendMessageByUid($uid, $buffer);
                // 返回推送结果
                $connection->send($ret ? 'ok' : 'fail');
            }else{
                if ($data['type'] == 'all') {
                    $ret = $this->broadcast($buffer);
                    // 返回推送结果
                    $connection->send($ret ? 'ok' : 'fail');
                } else {
                    $uid = $data['uid'];
                    // 通过workerman，向uid的页面推送数据
                    $ret = $this->sendMessageByUid($uid, $buffer);
                    // 返回推送结果
                    $connection->send($ret ? 'ok' : 'fail');
                }
            }
        };
        $inner_text_worker->listen();
    }

    // 向所有验证的用户推送数据
    function broadcast($message)
    {
        foreach($this->worker->uidConnections as $connection)
        {
            $connection->send($message);
        }
        return true;
    }

    // 针对uid推送数据
    function sendMessageByUid($uid, $message)
    {
        if(isset($this->worker->uidConnections[$uid]))
        {
            $connection = $this->worker->uidConnections[$uid];
            $connection->send($message);
            return true;
        }
        return false;
    }

}