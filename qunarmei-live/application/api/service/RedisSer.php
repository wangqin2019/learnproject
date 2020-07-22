<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/20
 * Time: 16:36
 */

namespace app\api\service;


use think\cache\driver\Redis;

class RedisSer
{
    protected $redisIns ;

    public function __construct()
    {
        $this->redisIns = new Redis();
    }
    /**
     * 数据加入redis队列
     * @param $key
     * @param $val
     */
    public function pushQueue($key,$val)
    {
        $this->redisIns->LPush($key,$val);
    }

    /**
     * 获取redis队列数据
     * @param $key
     * @param int $limit
     */
    public function pullQueue($key,$limit=1000)
    {
        $rest = [];
        for($i = 0 ; $i < $limit; $i++){
            $res = $this->redisIns->rPop($key) ;
            if($res){
                $rest[] = $res;
            }else{
                break;
            }
        }
        return $rest;
    }
}