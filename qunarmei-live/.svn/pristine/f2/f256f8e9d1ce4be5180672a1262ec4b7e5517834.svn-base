<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/26
 * Time: 14:22
 */

namespace app\api\model;


use think\Model;

class Department extends Model
{
    // 办事处美容院关联数据表
    protected $table = 'sys_department';

    // 关联办事处
    public function departR()
    {
        return $this->hasOne('DepartbeautyRelation','id_department','id_department');
    }
}