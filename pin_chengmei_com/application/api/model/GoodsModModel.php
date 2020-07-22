<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2019/11/1
 * Time: 10:04
 */

namespace app\api\model;


use think\Db;
use think\Model;

class GoodsModModel extends Model
{
    protected  $table="goods_model";

    /**
     * 获取产品模型表中的某一个字段
     * @param $id
     * @param $key
     * @return mixed
     */
    public function getModelValue($id,$key)
    {
        return Db::name('goods_model')->where('id',$id)->value($key);
    }
}