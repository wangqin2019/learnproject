<?php

namespace app\websocket\controller;
use app\home\model\BaseModel;
use think\Controller;
use My\RedisPackage;

class Base extends Controller
{
    protected static $redis;
    public function __construct()
    {
        parent::__construct();
        self::$redis=RedisPackage::getInstance();
    }
}