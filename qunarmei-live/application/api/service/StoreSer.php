<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/24
 * Time: 9:15
 */

namespace app\api\service;

use think\Db;

/**
 * 门店服务类
 * Class StoreSer
 * @package app\api\service
 */
class StoreSer
{
    /*********************查询***********************/
    public function getStoreUsers($map)
    {
        $res = Db::table('ims_bwk_branch b')
            ->join(['ims_bj_shopn_member'=>'m'],['b.id = m.storeid'],'LEFT')
            ->field('b.id store_id,b.title,b.address,b.sign,m.mobile,m.id user_id,m.realname')
            ->where($map)->order('m.createtime desc')
            ->select();
        return $res;
    }
    /*********************查询***********************/
}