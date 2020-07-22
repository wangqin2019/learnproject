<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class CouponModel extends Model
{
    protected  $name = 'new_year_coupon';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取代金券列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getCouponByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的代金券数量]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getAllCoupon($where)
    {
        return $this->where($where)->count();
    }


    /**
     * [insertRole 插入代金券信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertCoupon($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加代金券成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑代金券信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editCoupon($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑代金券成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据代金券id获取代金券信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOneCoupon($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delRole 删除代金券]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delCoupon($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除代金券成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}