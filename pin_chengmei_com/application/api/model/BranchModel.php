<?php

namespace app\api\model;
use think\Model;
use think\Db;

class BranchModel extends Model
{

    protected  $table="ims_bwk_branch";
    /**
     * 根据搜索条件获取门店列表信息
     */
    public function getTuanByWhere($map, $Nowpage, $limits)
    {
        return Db::table($this->table)->field('p_name,p_pic,p_price,pt_num_max')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取门店数量
     * @param $where
     */
    public function getAllBranch($where)
    {
        return Db::table($this->table)->where($where)->count();
    }


    /**
     * 根据门店id获取信息
     * @param $id
     */
    public function getOneInfo($id)
    {
        return Db::table($this->table)->where('id', $id)->field('id,title,sign,location_p,location_c,location_a,address,lng,lat')->find();
    }


}