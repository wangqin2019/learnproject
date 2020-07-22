<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/18
 * Time: 17:54
 */

namespace app\admin\service;


use think\Db;
use think\Log;

class BaseSer
{
    /**
     * 记录访问日志并记录到日志表
     * @param $user_id
     * @param $msg
     */
    public function writeLog($user_id,$msg)
    {
        $dt = date('Y-m-d H:i:s');
        // 记录日志文件
        Log::info($dt.'-'.$msg);

        // 记录日志到数据库
        $map['id'] = $user_id;
        $resu = Db::table('think_admin')->field('username')->where($map)->limit(1)->find();
        $data = [
            'admin_id' => $user_id,
            'admin_name' => $resu['username'],
            'description' => '用户【'.$resu['username'].'】'.$msg,
            'add_time' => time()
        ];
        Db::table('think_log')->insert($data);
    }
    /**
     * 异步执行耗时动作
     */
    // xxxxx
}