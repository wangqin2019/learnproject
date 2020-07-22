<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/10
 * Time: 16:15
 */

namespace app\admin\service;
use think\Db;

/**
 * 门店服务类
 */
class BranchSer
{
    /**
     * 删除门店
     * @param array $map 查询条件
     * @return
     */
    public function delBranch($map)
    {
        $res = Db::table('ims_bwk_branch')
            ->where($map)
            ->delete();
        return $res;
    }
    /**
     * 添加门店
     * @param array $data
     * @return
     */
    public function addBranch($data)
    {
        $res = Db::table('ims_bwk_branch')
            ->insertGetId($data);
        return $res;
    }
    /**
     * 添加门店邀请码
     * @param int $store_id 门店id
     * @return int|string
     */
    public function addInvitecode($store_id)
    {
        $res = $this->getInvitecode($store_id);
        if($res < 1){
            $code = $this->makeCode($store_id);
            $data = [
                'weid' => 1,
                'storeid' => $store_id,
                'codes' => $code,
                'numbers' => 30,
                'createtime' => time()
            ];
            $res = Db::table('ims_bj_shopn_invitecode')
                ->insertGetId($data);
        }
        return $res;
    }

    /**
     * 生成邀请码
     * @param int $store_id 门店id
     * @return mixed|string
     */
    private function makeCode($store_id)
    {
        $code = '[{"isused":0,"code":"betm3i1686"},{"isused":0,"code":"micqec1686"},{"isused":0,"code":"jon8g81686"},{"isused":0,"code":"oexz7z1686"},{"isused":0,"code":"4rfgnw1686"},{"isused":0,"code":"j89lkh1686"},{"isused":0,"code":"05yqhw1686"},{"isused":0,"code":"azw4s41686"},{"isused":0,"code":"3x9qp91686"},{"isused":0,"code":"h8wss71686"},{"isused":0,"code":"09xytg1686"},{"isused":0,"code":"vuwrfu1686"},{"isused":0,"code":"vybh9b1686"},{"isused":0,"code":"gu0im41686"},{"isused":0,"code":"ojdaa01686"},{"isused":0,"code":"hpn8e41686"},{"isused":0,"code":"tuew3m1686"},{"isused":0,"code":"zcpr3x1686"},{"isused":0,"code":"mif77t1686"},{"isused":0,"code":"f2mpnw1686"},{"isused":0,"code":"6clz5n1686"},{"isused":0,"code":"v7x7d61686"},{"isused":0,"code":"544d8e1686"},{"isused":0,"code":"uywx3m1686"},{"isused":0,"code":"pzez1i1686"},{"isused":0,"code":"2l6tso1686"},{"isused":0,"code":"tbf1tc1686"},{"isused":0,"code":"hwtz2v1686"},{"isused":0,"code":"p63uy91686"},{"isused":0,"code":"4obpsu1686"},{"isused":0,"code":"o85brn1686"},{"isused":0,"code":"mpjlwy1686"},{"isused":0,"code":"ckftbx1686"},{"isused":0,"code":"8d3rnd1686"},{"isused":0,"code":"mq7tkr1686"},{"isused":0,"code":"8kg67v1686"},{"isused":0,"code":"gobhl01686"},{"isused":0,"code":"eemo5r1686"},{"isused":0,"code":"2yh3d51686"},{"isused":0,"code":"v54h571686"}]
';
        $code = str_replace('1686',$store_id,$code);
        return $code ;
    }

    /**
     * 查询门店邀请码
     * @param int $store_id 门店id
     * @return int|string
     */
    private function getInvitecode($store_id)
    {
        $map['storeid'] = $store_id;
        $res = Db::table('ims_bj_shopn_invitecode')
            ->where($map)
            ->count() ;
        return $res;
    }
}