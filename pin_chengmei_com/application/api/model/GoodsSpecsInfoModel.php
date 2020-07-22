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

class GoodsSpecsInfoModel extends Model
{
    protected  $table="goods_specs_info";

    public function getGoodsSpecsInfo($map,$order='id desc'){
       return Db::name($this->table)->where($map)->order($order)->select();
    }

    public function getStock($map,$field){
        return Db::name($this->table)->where($map)->field($field)->find();
    }

}