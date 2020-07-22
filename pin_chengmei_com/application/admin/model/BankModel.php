<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BankModel extends Model
{
    protected  $name = 'bank';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取银行列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getBankByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('no_displayorder')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的银行数量]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getAllBank($where)
    {
        return $this->where($where)->count();
    }



    /**
     * [insertRole 插入银行信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertBank($param)
    {
        try{
            $result =  $this->validate('BankValidate')->allowField(true)->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加银行成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑银行信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editBank($param)
    {
        try{
            $result =  $this->validate('BankValidate')->allowField(true)->save($param, ['id_bank' => $param['id_bank']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑银行成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据银行id获取银行信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOneBank($id)
    {
        return $this->where('id_bank', $id)->find();
    }



    /**
     * [delRole 删除银行]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delBank($id)
    {
        try{
            $this->where('id_bank', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除银行成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



}