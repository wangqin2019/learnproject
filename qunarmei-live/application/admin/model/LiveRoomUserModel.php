<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class LiveRoomUserModel extends Model
{
    protected  $name = 'live_room_user';
    protected $resultSetType = 'collection';
    /**
     * [getLiveByWhere 根据条件获取直播间用户列表信息]
     */
    public function getLiveByWhere($map,$platform_id)
    {
        if($platform_id==1){
            $list=$this->alias('log')->join(['ims_bj_shopn_member'=>'m'],'log.openid=m.wx_open_id','left')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'b.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->field('depart.st_department,b.title,b.sign,m.id,m.mobile,m.realname,m.isadmin,m.code,m.staffid,log.insert_time,log.openid')->order('log.insert_time desc')->select()->toArray();
        }else{
            $list=$this->alias('log')->join('wx_user u','log.openid=u.open_id','left')->join(['ims_bj_shopn_member'=>'m'],'u.mobile=m.mobile','left')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'b.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->field('depart.st_department,b.title,b.sign,m.id,m.mobile,m.realname,m.isadmin,m.code,m.staffid,log.insert_time,log.openid')->order('log.insert_time desc')->select()->toArray();
        }
        if($list){
            foreach ($list as $k=>$v){
                if($v['mobile']){
                    if($v['isadmin']){
                        $list[$k]['role']='店老板';//店老板
                    }else{
                        if($v['id']==$v['staffid'] || strlen($v['code'])){
                            $list[$k]['role']='美容师';//美容师
                        }else{
                            $list[$k]['role']='顾客';//顾客
                        }
                    }
                }else{
                    $list[$k]['role']='未注册顾客';//顾客
                }
            }
        }
        return $list;
    }
    /**
     * [getLiveByWhere 微商城根据条件获取直播间用户列表信息]
     */
    public function getLiveByWhere1($map)
    {
        $list=$this->alias('log')->join(['ims_bj_shopn_member'=>'m'],'log.openid=m.wx_open_id','left')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'b.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->field('depart.st_department,b.title,b.sign,m.id,m.mobile,m.realname,m.isadmin,m.code,m.staffid,log.insert_time,log.openid')->order('log.insert_time desc')->select()->toArray();
        if($list){
            foreach ($list as $k=>$v){
                if($v['mobile']){
                    if($v['isadmin']){
                        $list[$k]['role']='店老板';//店老板
                    }else{
                        if($v['id']==$v['staffid'] || strlen($v['code'])){
                            $list[$k]['role']='美容师';//美容师
                        }else{
                            $list[$k]['role']='顾客';//顾客
                        }
                    }
                }else{
                    $list[$k]['role']='未注册顾客';//顾客
                }
            }
        }
        return $list;
    }
}