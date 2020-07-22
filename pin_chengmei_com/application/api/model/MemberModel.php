<?php

namespace app\api\model;
use think\Model;
use think\Db;

class MemberModel extends Model
{


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


    public function getInfoByField($map,$field){
        return Db::table('ims_bj_shopn_member')->where($map)->field($field)->find();
    }




}