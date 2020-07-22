<?php

namespace app\live\controller;
use think\Db;
/**
 * swagger: 计划任务
 */
class Execute extends Base
{
    //定时获取直播回放
    public function get_live_replay(){
        set_time_limit(0);
        $map['live_status'] = ['eq', '103'];
        $map['live_replay'] = ['eq', 0];
        $list=Db::name('wechat_live')->where($map)->field('id,roomid')->select();
        if($list){
            $insertData=[];
            $updateIds=[];
            foreach ($list as $k=>$v){
                $data=["action"=> "get_replay","room_id"=>$v['roomid'],'start'=>0,'limit'=>50];
                $access_token=getAccessToken();
                if (!$access_token) return false;
                $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
                $result = http_post($url,json_encode($data));
                $resultArr=json_decode($result,true);
                if($resultArr['errcode']==0 && count($resultArr['live_replay'])){
                    foreach ($resultArr['live_replay'] as $kk=>$vv){
                        $type=$kk?1:0;
                        $create_time=strtotime($vv['create_time']);
                        $expire_time=strtotime($vv['expire_time']);
                        $insertData[]=['roomid'=>$v['roomid'],'create_time'=>date('Y-m-d H:i:s',$create_time),'expire_time'=>date('Y-m-d H:i:s',$expire_time),'media_url'=>$vv['media_url'],'type'=>$type,'insert_time'=>date('Y-m-d H:i:s')];
                    }
                    $updateIds[]=$v['roomid'];
                }
            }
            if($list){
                Db::name('wechat_live_replay')->insertAll($insertData);
                Db::name('wechat_live')->where('roomid','in',$updateIds)->update(['live_replay'=>1]);
            }
        }
    }

        //定时刷新最近100个直播
        public function RefreshLive(){
            set_time_limit(0);
            $data=['start'=>0,'limit'=>50];
            $access_token=getAccessToken();
            if (!$access_token) return false;
            $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
            $result = http_post($url,json_encode($data));
            $result=json_decode($result,true);
            if($result['errcode']==0){
                foreach ($result['room_info'] as $k=>$v){
                    $v['goods']=json_encode($v['goods']);
                    $v['start_time']=date('Y-m-d H:i:s',$v['start_time']);
                    $v['end_time']=date('Y-m-d H:i:s',$v['end_time']);
                    $check=Db::name('wechat_live')->where('roomid',$v['roomid'])->count();
                    if($check){
                        Db::name('wechat_live')->where('roomid', $v['roomid'])->update($v);
                    }else{
                        $v['create_time']=date('Y-m-d H:i:s');
                        $v['buy_begin'] = strtotime($v['start_time']);
                        $v['buy_end'] = strtotime($v['end_time']);
                        Db::name('wechat_live')->insert($v);
                    }
                }
            }
    }

}