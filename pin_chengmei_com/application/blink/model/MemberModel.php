<?php

namespace app\blink\model;
use think\Model;
use think\Db;

class MemberModel extends Model
{
    protected $table = 'ims_bj_shopn_member';

    /**
     * 获取用户信息
     * @param $id
     */
    public function getOneInfo($map)
    {
        return Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.realname,u.nickname,member.mobile,u.avatar,member.storeid,member.id_regsource,branch.title,branch.address')->join(['ims_bwk_branch' => 'branch'],'member.storeid=branch.id','left')->join('wx_user u','member.mobile=u.mobile','left')->where($map)->find();
    }


    //根据电话获取用户信息
    public function getInfoByMobile($mobile)
    {
        return Db::table('ims_bj_shopn_member')->where('mobile',$mobile)->find();
    }

    /**
     * Commit: 根据手机号获取用户数据（含门店信息）
     * Function: getUserAccordToMobile
     * @Param string $mobile
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:31:33
     * @Return array
     */
    public static function getUserAccordToMobile($mobile = '',$field = 'm.id,bwk.title,bwk.sign'){
        return self::alias('m')
            ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
            ->field($field)
            ->where('m.mobile',$mobile)
            ->find()->toArray();
    }
    /**
     * Commit: 根据赠送人用户ID获取用户数据（含门店信息）
     * Function: getUserAccordToGiveUserID
     * @Param string $mobile
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-17 10:31:33
     * @Return array
     */
    public static function getUserAccordToGiveUserID($give_userid = '',$field = 'm.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign,u.nickname,u.avatar,m.code'){
        return self::alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
            ->field($field)
            ->where('m.id',$give_userid)
            ->find()
            ->toArray();
    }

}