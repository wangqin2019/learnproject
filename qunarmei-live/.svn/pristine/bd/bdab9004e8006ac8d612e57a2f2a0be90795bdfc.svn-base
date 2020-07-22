<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/24
 * Time: 15:35
 */

namespace app\admin\controller;
use think\Db;
/**
 * 电话号码权限控制,中间4位显示为*
 */
class MobileRule
{
    // 没有完整号码权限的用户
    public $no_mobile_uid = [52,20];

    /**
     * 是否显示完整号码权限
     * @param $uid
     * @return int
     */
    public function checkRule($uid)
    {
        $flag = 0;
        if(in_array($uid,$this->no_mobile_uid)){
            $flag = 1;
        }
        return $flag;
    }
    /**
     * 电话号码中间4位替换为*
     * @param $uid
     * @return int
     */
    public function replaceMobile($mobile)
    {
        $mobile = substr_replace($mobile, '****', 3, 4);;
        return $mobile;
    }

    /**
     * 通过后台用户id获取办事处下面门店信息
     * @return array
     */
    public function getAdminBranch($uid)
    {
        $storeids = [];
        $map_bsc['b.admin_id'] = $uid;
        $res_bsc = Db::table('think_admin_ban b')->join(['sys_department'=>'d'],['b.bsc=d.id_department'],'left')->join(['sys_departbeauty_relation'=>'r'],['r.id_department=d.id_department'],'left')->field('r.id_beauty')->where($map_bsc)->select();
        if($res_bsc){
            foreach ($res_bsc as $vb) {
                $storeids[] = $vb['id_beauty'];
            }
        }
        return $storeids;
    }
}