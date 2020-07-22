<?php

namespace app\websocket\controller;
// use My\RedisPackage;

class Index extends Base
{
    public function index()
    {
    	$key = 'live_chat_123456_15921324162';
    	echo "redis<pre>";var_dump(self::$redis->get($key));
        // echo phpinfo();
    }
    
}