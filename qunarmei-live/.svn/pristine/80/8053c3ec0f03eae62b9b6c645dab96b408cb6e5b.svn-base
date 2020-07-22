<?php

namespace app\api_public\controller;
use my\RedisPackage;
use think\Controller;


class Base extends Controller
{
    protected static $redis;
    public function __construct()
     {
         parent::__construct();
             self::$redis=RedisPackage::getInstance();
     }
    public function _initialize()
    {
        $config = cache('db_config_data');
        if(!$config){
            $config = load_config();
            cache('db_config_data',$config);
        }
        config($config);
    }
}