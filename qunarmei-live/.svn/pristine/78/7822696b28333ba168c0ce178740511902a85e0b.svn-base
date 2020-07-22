<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/28
 * Time: 10:50
 */

namespace app\api\service;


use think\Db;

class ActSer
{
    /**
     * 获取ims_bj_shopn_act_switch数据
     * @param array $map 查询条件
     */
    public function getActSwitchs($map)
    {
        $res = Db::table('ims_bj_shopn_act_switch')->where($map)->select();
        return $res;
    }
}