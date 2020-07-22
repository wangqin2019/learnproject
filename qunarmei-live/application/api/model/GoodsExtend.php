<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/7/7
 * Time: 10:43
 */

namespace app\api\model;


use think\Model;

class GoodsExtend extends Model
{
	// 商品扩展表，记录商品属性
	protected $table = 'ims_bj_shopn_goods_extend';
}