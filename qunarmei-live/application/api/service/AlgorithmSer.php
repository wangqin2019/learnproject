<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/23
 * Time: 10:28
 */

namespace app\api\service;

/**
 * 算法运算类
 */
class AlgorithmSer
{
    /**
     * 线性算法计算k , y = kx + m
     * @param array $a [x=0,y=初始值(当前直播间人数)]
     * @param array $b [x=计划值,y=目标值]
     */
    public function linear($a , $b)
    {
        $k = ($b[1] - $a[1])/$b[0] ;
        return $k;
    }

}