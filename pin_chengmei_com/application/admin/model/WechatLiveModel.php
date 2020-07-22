<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class WechatLiveModel extends Model
{
    protected  $name = 'wechat_live';

    // 开启自动写入时间戳字段
   // protected $autoWriteTimestamp = true;
    protected $autoWriteTimestamp = 'datetime';

    /**
     * [getRoleByWhere 根据条件获取直播间列表信息]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getLiveByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('roomid desc')->select();
    }

    public function getLastRoomId(){
        return $this->max('roomid');
    }



    /**
     * [getRoleByWhere 根据条件获取所有的直播列表]
     * @author [田建龙] [864491238@qq.com]
     */
    public function getLiveAll($where)
    {
        return $this->where($where)->count();
    }


    /**
     * [insertRole 插入直播间信息]
     * @author [田建龙] [864491238@qq.com]
     */    
    public function insertLive($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){               
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加直播间成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editRole 编辑直播间信息]
     * @author [田建龙] [864491238@qq.com]
     */  
    public function editLive($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneRole 根据条件获取直播间信息]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function getOneLive($map)
    {
        return $this->where($map)->find();
    }



    /**
     * [delRole 删除直播间]
     * @author [田建龙] [864491238@qq.com]
     */ 
    public function delLive($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除直播间成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    


}