<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class TryoutModel extends Model
{
    protected  $name = 'tryout';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取试用列表信息]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function getTryoutByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的试用数量]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function getAllTryout($where)
    {
        return $this->where($where)->count();
    }


    /**
     * [insertRole 插入试用信息]
     * @author [侯典敬] [451035207@qq.com]
     */    
    public function insertTryout($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加试用成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑试用信息]
     * @author [侯典敬] [451035207@qq.com]
     */  
    public function editTryout($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑试用成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据试用id获取试用信息]
     * @author [侯典敬] [451035207@qq.com]
     */ 
    public function getOneTryout($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delRole 删除试用]
     * @author [侯典敬] [451035207@qq.com]
     */ 
    public function delTryout($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除试用成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}