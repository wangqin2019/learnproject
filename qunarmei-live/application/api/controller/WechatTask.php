<?php

namespace app\api\controller;
use think\Controller;
use think\Db;
/**
 * swagger: 微信小程序直播计划任务
 */
class WechatTask extends Controller
{
    //定时获取直播回放
    public function get_live_replay(){
        set_time_limit(0);
        $map['live_status'] = ['eq', '103'];
        $map['live_replay'] = ['eq', 0];
        $list=Db::name('wechat_live')->where($map)->field('id,roomid,platform_id')->select();
        if($list){
            $insertData=[];
            foreach ($list as $k=>$v){
                $data=["action"=> "get_replay","room_id"=>$v['roomid'],'start'=>0,'limit'=>50];
                $access_token=getAccessToken($v['platform_id']);
                if (!$access_token) return false;
                $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
                $result = comm_http_post($url,json_encode($data));
                $resultArr=json_decode($result,true);
                if($resultArr['errcode']==0 && count($resultArr['live_replay'])){
                    foreach ($resultArr['live_replay'] as $kk=>$vv){
                        $type=$kk?1:0;
                        $create_time=strtotime($vv['create_time']);
                        $expire_time=strtotime($vv['expire_time']);
                        $insertData[]=['platform_id'=>$v['platform_id'],'roomid'=>$v['roomid'],'create_time'=>date('Y-m-d H:i:s',$create_time),'expire_time'=>date('Y-m-d H:i:s',$expire_time),'media_url'=>$vv['media_url'],'type'=>$type,'insert_time'=>date('Y-m-d H:i:s')];
                    }
                    Db::name('wechat_live')->where(['roomid'=>$v['roomid'],'platform_id'=>$v['platform_id']])->update(['live_replay'=>1]);
                }
            }
            if($insertData){
                Db::name('wechat_live_replay')->insertAll($insertData);
            }
        }
        return true;
    }

        //定时刷新最近50个直播
        public function RefreshLive(){
            set_time_limit(0);
            $this->hide_live();
            $platform=Db::name('wechat_platform')->where(['app_status'=>1])->column('id');
            if(count($platform)){
                foreach ($platform as $key=>$val){
                    $data=['start'=>0,'limit'=>50];
                    $access_token=getAccessToken($val);
                    if (!$access_token) return false;
                    $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
                    $result = comm_http_post($url,json_encode($data));
                    $result=json_decode($result,true);
                    if($result['errcode']==0){
                        foreach ($result['room_info'] as $k=>$v){
                            $v['goods']=json_encode($v['goods']);
                            $v['start_time']=date('Y-m-d H:i:s',$v['start_time']);
                            $v['end_time']=date('Y-m-d H:i:s',$v['end_time']);
                            $check=Db::name('wechat_live')->where(['roomid'=>$v['roomid'],'platform_id'=>$val])->count();
                            if($check){
                                Db::name('wechat_live')->where(['roomid'=>$v['roomid'],'platform_id'=>$val])->update($v);
                            }else{
                                $v['platform_id']=$val;
                                $v['create_time']=date('Y-m-d H:i:s');
                                $v['buy_begin'] = strtotime($v['start_time']);
                                $v['buy_end'] = strtotime($v['end_time']);
                                Db::name('wechat_live')->insert($v);
                            }
                        }
                    }
                }
            }
            $this->hide_live();
            return true;
    }

    /**将超过直播显示时间的直播隐藏掉
     * Notes:
     * User: HOUDJ
     * Date: 2020/5/6
     * Time: 9:43
     */
    public function hide_live(){
        $map['live_status']=['eq','101'];
        $map['live_show']=['eq',1];
        $list=Db::name('wechat_live')->where($map)->whereNotNull('hide_time')->field('id,hide_time')->select();
        if(count($list)){
            foreach ($list as $k=>$v){
                if(time()> $v['hide_time']){
                    Db::name('wechat_live')->where('id',$v['id'])->update(['live_show'=>0]);
                }
            }
        }
    }

}