<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class WechatPlatformModel extends Model
{
    protected  $name = 'wechat_platform';
    protected $resultSetType = 'collection';

    /**
     * [getRoleByWhere 根据条件获取列表信息]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function getDataByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select()->toArray();
    }


    /**
     * [getRoleByWhere 根据条件获取所有的列表合计]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function getAll($where)
    {
        return $this->where($where)->count();
    }

    /**
     * [getRoleByWhere 根据条件获取所有的列表]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function getAllByWhere($where=[])
    {
        return $this->where($where)->field('id,app_name')->select()->toArray();
    }

    /**
     * [getRoleByWhere 根据条件获取信息]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function getOne($where)
    {
        return $this->where($where)->find();
    }

    /**
     * [insertRole 插入小程序信息]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function insertData($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加小程序成功'];
            }
        }catch( \PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑小程序信息]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function editData($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑小程序成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * [editRole 删除小程序信息]
     * @author [侯典敬] [45103507@qq.com]
     */
    public function delData($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除小程序成功'];
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}