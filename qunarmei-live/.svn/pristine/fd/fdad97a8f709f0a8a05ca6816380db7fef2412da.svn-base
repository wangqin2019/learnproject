<?php

namespace app\neibu\controller;
use think\Controller;
use app\neibu\service\GoodService;
/**
 * 商品相关处理
 */
class Goods extends Base
{

    
    public function add_live_goods()
    {     
        $sign = input('sign');
        $gdser = new GoodService();
        $res = $gdser->addLiveGoods($sign);
        // var_dump($res);die;
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }


}