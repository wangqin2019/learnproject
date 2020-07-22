<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/23
 * Time: 17:42
 */

namespace app\api\service;
use think\cache\driver\Redis;
/**
 * 服务基类
 */
class BaseSer
{
    // 下发统一数据
    protected $code = 0;//状态码
    protected $msg = '暂无数据';// 提示信息
    protected $data = [];// 返回数据

    /**
     * 截取指定两个字符之间的字符串
     * @param $begin string 开始字符串
     * @param $end string 结束字符串
     * @param $str string 整个字符串
     * @return string
     */
    public function cut($begin,$end,$str)
    {
        $b = mb_strpos($str,$begin) + mb_strlen($begin);
        $e = mb_strpos($str,$end) - $b;
        return mb_substr($str,$b,$e);
    }
    /**
     * 存入redis
     * @return array
     */
    public function setRedisData($key,$data,$expire=30)
    {
        $redisser = new Redis();
        $redisser->set($key,$data,$expire);
    }
    /**
     * 获取redis数据
     * @return array
     */
    public function getRedisData($key)
    {
        $redisser = new Redis();
        $data = $redisser->get($key);
        return $data;
    }
    /**
     * 返回数组
     * @return array
     */
    public function returnArr()
    {
        $arr = [
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => $this->data,
        ];
        return $arr;
    }
}