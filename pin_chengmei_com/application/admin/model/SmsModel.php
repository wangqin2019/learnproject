<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class SmsModel extends Model
{
    protected  $name = 'sms_template';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * [getRoleByWhere 根据条件获取短信模版列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getSmsByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('sms_to')->select();
    }



    /**
     * [getRoleByWhere 根据条件获取所有的短信模版数量]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getAllSms($where)
    {
        return $this->where($where)->count();
    }



    /**
     * [insertRole 插入短信模版信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertSms($param)
    {
        try{
            $check=$this->where(['sms_scene'=>$param['sms_scene'],'sms_to'=>$param['sms_to']])->count();
            if(!$check) {
                $result = $this->allowField(true)->save($param);
                if (false === $result) {
                    return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                } else {
                    return ['code' => 1, 'data' => '', 'msg' => '添加短信模版成功'];
                }
            }else{
                return ['code' => -1, 'data' => '', 'msg' => '场景已存在，请勿重复添加'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * [editRole 编辑短信模版信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editSms($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑短信模版成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * [getOneRole 根据短信模版id获取短信模版信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOneSms($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * [delRole 删除短信模版]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delSms($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除短信模版成功'];
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}