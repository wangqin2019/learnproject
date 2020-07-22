<?php

namespace app\index\controller;
use think\Controller;
use think\Db;
use My\RedisPackage;

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

    /**
     * 同一返回方法
     * @param string $code
     * @param array $data
     * @param string $msg
     * @return \think\response\Json
     */
    public function returnMsg($code='1',$data=array(),$msg='获取成功')
    {
        $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($arr);
    }

    //缓存相关

    //设置
    protected function setCacheString($key,$val){
        if(self::$redis->exists($key)==0){
            self::$redis->set($key,$val);
        }
    }
    //获取
    protected function getCacheString($key){
        return self::$redis->get($key);
    }

    //递减
    protected function setDec($key,$num){
        return self::$redis->DECRBY($key,$num);
    }
    //hash设置
    protected function hashSet($name,$field,$val){
        return self::$redis->HSET($name,$field,$val);
    }
    //hash读取
    protected function hashGet($name,$field){
        return self::$redis->HGET($name,$field);
    }
	//set设置
    protected function saddSet($name,$field){
        return self::$redis->SADD($name,$field);
    }

    //判断在集合中是否存在
    protected function saddSismember($name,$field){
        return self::$redis->SISMEMBER($name,$field);
    }

    //hash设置字段增减量
    protected function hincrby($name,$field,$val){
        return self::$redis->HINCRBY($name,$field,$val);
    }

}