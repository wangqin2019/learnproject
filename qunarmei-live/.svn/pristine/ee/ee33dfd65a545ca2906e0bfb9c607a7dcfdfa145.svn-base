<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/10
 * Time: 16:01
 */

namespace app\admin\service;

/*
**高德地图
*/
class GouldMap
{
    protected $key = '065fe85d9b2bfde0dcd1b205aa4ec282';

    /**
     * 通过地址获取经纬度
     * @return string
     */
    public function addressTolng($address)
    {
        $res = [];
        $url='http://restapi.amap.com/v3/geocode/geo?address='.$address.'&key='.$this->key;
        $result = file_get_contents($url);
        if($result){
            $result = json_decode($result,true);
            if(!empty($result['count'])){
//                $res = explode(',',$result['geocodes']['0']['location']);
                $res = $result;
            }
        }
        return $res;
    }
}