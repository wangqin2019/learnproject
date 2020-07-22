<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/1/8
 * Time: 15:14
 */

namespace app\api\model;

use think\Model;
class Fans extends Model
{
    // 定义数据表名称
    protected $table = 'ims_fans';
    /*
     * 定义关联模型
     * */
    public function user()
    {
        // 属于主表模型,本表关联字段id,主表关联id
        return $this->belongsTo('Test','id_member','id');
    }
    /*
     * 定义关联查询方法
     * */
    public static function getUserById($id)
    {
        // 关联方法
//        return self::with('fans')->select($id); // 多条记录
        $map['id_member'] = $id;
        return self::with('user')->where($map)->find(); // 单条记录
    }
}