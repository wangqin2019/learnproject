<?php
namespace app\live\controller;
use think\Db;

class Live extends Base
{

    public function getLive(){
        $flag=input('flag','now');
        if($flag=='history'){
            $roomId=input('roomId');
            $data=["action"=> "get_replay","room_id"=>$roomId,'start'=>0,'limit'=>10];
        }else{
            $data=['start'=>0,'limit'=>10];
        }
        $access_token=getAccessToken();
        if (!$access_token) return false;
        $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
        $result = http_post($url,json_encode($data));
        echo $result;
        die();
    }

    /*
     * 获取直播间列表
     */
    public function getLiveList(){
      $uid=input('param.uid');
      //拿出该用户的门店编码，角色
      $userInfo=Db::table('ims_bj_shopn_member')->where('id',$uid)->find();
      if($userInfo){
          $storeid=$userInfo['storeid'];
          $role='';
          if($userInfo['isadmin']){
              $role=1;//店老板
          }else{
              if($userInfo['id']==$userInfo['staffid'] || strlen($userInfo['code'])){
                  $role=2;//美容师
              }else{
                  $role=3;//顾客
              }
          }
      }
      $lists=Db::name('wechat_live')->where('live_show',1)->order('roomid desc')->select();
      if($lists){
          foreach ($lists as $k=>$v){
              $lists[$k]['goods']=json_decode($v['goods'],true);
              $allow=1;//默认都有资格观看
              //观看对象是部分门店，查看该用户是否在允许门店内
              if($v['live_object']){
                   if(!in_array($storeid,explode(',',$v['live_object_sign']))){
                       $allow=0;
                   }
              }
            //检测角色是否允许观看
              $roleArr=explode(',',$v['live_role']);
              if(!in_array($role,$roleArr)){
                  $allow=0;
              }
              if($allow==0){
                  unset($lists[$k]);
              }
              unset($lists[$k]['live_object'],$lists[$k]['live_object_sign'],$lists[$k]['live_role'],$lists[$k]['live_show'],$lists[$k]['create_time']);
          }
      }
      if($lists){
          $code = 1;
          $data=array_values($lists);
          $msg = '获取成功';
      }else{
          $code = 0;
          $data=[];
          $msg = '暂无直播列表';
      }
        return parent::returnMsg($code,$data,$msg);
    }

}
