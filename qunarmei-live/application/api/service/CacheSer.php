<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/12
 * Time: 11:29
 */

namespace app\api\service;

use think\Cache;

/**
 * 缓存服务类
 */
class CacheSer
{
    /**
     * 获取缓存数据
     * @param string $key 键值
     * @return array
     */
    public function getCache($key)
    {
        $res = Cache::get($key);
        if($res){
            $res = json_decode($res,true);
        }
        return $res;
    }

    /**
     * 设置缓存数据
     * @param string $key 键值
     * @param array $val 数组
     * @param int $expire_time 缓存时间,默认10分钟
     * @return bool
     */
    public function setCache($key,$val,$expire_time=600)
    {
        if(is_array($val)){
            $val = json_encode($val,JSON_UNESCAPED_UNICODE);
        }
        $res = Cache::set($key,$val,$expire_time);
        return $res;
    }
}