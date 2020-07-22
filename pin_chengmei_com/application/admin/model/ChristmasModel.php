<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class ChristmasModel extends Model
{
    protected  $name = 'christmas_prize';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取奖品列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getPrizeByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的奖品数量]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getAllPrize($where)
    {
        return $this->where($where)->count();
    }


    /**
     * [insertRole 插入奖品信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertPrize($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加奖品成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑奖品信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editPrize($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑奖品成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据奖品id获取奖品信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOnePrize($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delRole 删除奖品]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delPrize($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除奖品成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}