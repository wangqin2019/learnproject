<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/1/8
 * Time: 15:27
 */

namespace app\api\model;

use think\Model;
class Branch extends Model
{
    // 定义数据表
    protected $table = 'ims_bwk_branch';

    // 关联办事处
    public function departR()
    {
        return $this->hasOne('DepartbeautyRelation','id_beauty','id');
    }
    /*
     * 定义关联ims_bj_shopn_member表
     * */
    public function user()
    {
        // 主表模型,从表id,主表关联id
        return $this->hasOne('Test','id','boss_id');// 一对一关联
    }
    /*
     * 定义关联ims_bj_shopn_member查询方法
     * */
    public static function getUserById($id)
    {
        // 关联方法
//        return self::with('fans')->select($id); // 多条记录
        $map['boss_id'] = $id;
        return self::with('user')->where($map)->select(); // 多条记录
    }
}