<?php
namespace app\websocket\controller;

use think\facade\Cache;
use My\RedisPackage;
class RedisSer
{
	// protected static $redis;
 //    public function __construct()
 //    {
 //        parent::__construct();
 //        self::$redis=RedisPackage::getInstance();
 //    }
	
	/**
	 * 设置redis数据
	 * @param [string] $key [键名]
	 */
    public function set($key , $val , $expire = 86400){
        Cache::store('redis')->set( $key , $val , $expire);
        // Cache::set( $key , $val , $expire);
    }

    public function get($key){
        $res = Cache::store('redis')->get($key);
        // $res = Cache::get($key);
        return $res;
    }
    
}