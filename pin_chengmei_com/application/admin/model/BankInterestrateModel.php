<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BankInterestrateModel extends Model
{
    protected  $name = 'bank_interestrate';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取分期列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getFenqiByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->alias('i')->join('bank b','i.id_bank=b.id_bank','left')->page($Nowpage, $limits)->order('i.orderby')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的分期数量]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getAllFenqi($where)
    {
        return $this->alias('i')->where($where)->count();
    }



    /**
     * [insertRole 插入分期信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertFenqi($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加分期成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑分期信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editFenqi($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑分期成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据分期id获取分期信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOneFenqi($id)
    {
        return $this->alias('i')->where('i.id', $id)->join('bank b','i.id_bank=b.id_bank','left')->find();
    }



    /**
     * [delRole 删除分期]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delFenqi($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除分期成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



}