<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2019/11/1
 * Time: 10:06
 */

namespace app\api\model;


use think\Db;
use think\Model;

class GoodsSpecsItemModel extends Model
{
    protected  $table="goods_specs_item";

    public function goodsSpecsItem($map,$order='id'){
       return Db::name($this->table)->where($map)->order($order)->select();
    }
}