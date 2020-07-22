<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/24
 * Time: 14:15
 */

namespace app\api\model;

// app购物车模型
use think\Model;

class Car extends Model
{
    // 购物车模型分类
    protected $table = 'ims_bj_shopn_car';

    // 每条购物车记录属于1个商品
    public function goods()
    {
        return $this->belongsTo('BjGoods','id_goods','id');
    }
}