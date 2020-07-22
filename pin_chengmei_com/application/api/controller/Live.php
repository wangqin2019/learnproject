<?php
namespace app\api\controller;
use think\Db;
use com\Gateway;

class Live extends Base
{
    //用户绑定
    public function bind(){
        header('Access-Control-Allow-Origin:*');
        $mobile=input('param.mobile');
        $client_id=input('param.client_id');
        //$groupId=input('param.groupId','');
        Gateway::bindUid($client_id,$mobile);
        Gateway::joinGroup($client_id,'live');
        Gateway::setSession($client_id, ['live_mobile'=>$mobile,'live_stay'=>1]);
       // $mobile11=Gateway::getUidByClientId($client_id);
        $msg=['scene'=>'initData','data'=>'ok'];
        Gateway::sendToUid($mobile,json_encode($msg));
    }
    //获取初始数据
    public function get_live(){
        $mobile=input('param.mobile');
        $array=Db::name('live_url')->where('id',1)->find();
        if($array['flag']){
            $url = $array['live_url'];
        }else{
            $url = $array['preheat_url'];
        }
        $this->user_action($mobile,'in');//记录进入
        $msg = ['scene'=>'live','live_url' => $url];
        Gateway::sendToUid($mobile,json_encode($msg));
    }

    //记录用户进入离开动作
    public function user_action($mobile,$type){
        $arr=['live'=>'live','mobile'=>$mobile,'type'=>$type,'action_time'=>time()];
        self::$redis->lPush('live_user_action',json_encode($arr));
    }

    //用户离开
    public function leave(){
        $mobile=input('param.mobile');
        $this->user_action($mobile,'out');//记录离开
    }

    //redis数据入库
    public function user_pop(){
        $num = 500;// 每次默认取出500条
        for($i=0;$i<$num;$i++){
            $data_redis = self::$redis->rPop('live_user_action');
            if($data_redis){
                $data[] = json_decode($data_redis,true);
            }
        }
        if(!empty($data)){
            foreach($data as $v){
                $map['mobile']=array('eq',$v['mobile']);
                $map['item_name']=array('eq','live');
                $info=Db::name('user_stay')->where($map)->order('id desc')->find();
                if(!$info && $v['type']=='in'){
                    $role=$this->get_role($v['mobile']);
                    Db::name('user_stay')->insert(['item_name'=>$v['live'],'mobile'=>$v['mobile'],'login_time'=>$v['action_time'],'leave_time'=>$v['action_time'],'role'=>$role,'stay_time'=>0]);
                }else{
                    if($v['type']=='out'){
                        if($info['stay_time']==0){
                            $stay=$v['action_time']-$info['login_time'];
                            Db::name('user_stay')->where('id',$info['id'])->update(['leave_time'=>$v['action_time'],'stay_time'=>round($stay,2)]);
                        }
                    }else{
                        $role=$this->get_role($v['mobile']);
                        Db::name('user_stay')->insert(['item_name'=>$v['live'],'mobile'=>$v['mobile'],'login_time'=>$v['action_time'],'leave_time'=>$v['action_time'],'role'=>$role,'stay_time'=>0]);
                    }
                }
            }
        }
    }
    //登陆用户角色
    public function get_role($mobile){
        $getSeller=Db::table('ims_bj_shopn_member')->where('mobile',$mobile)->field('code,isadmin')->find();
        if($getSeller['isadmin']){
            return 2;
        }elseif(strlen($getSeller['code'])){
            return 1;
        }else{
            return 0;
        }
    }


}
