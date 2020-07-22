<?php

namespace app\blink\controller;
use app\blink\model\PintuanModel;
use think\Controller;
use think\Db;
use My\RedisPackage;

class Base extends Controller
{
    protected static $redis;
    public function __construct()
    {
        parent::__construct();
        self::$redis= RedisPackage::getInstance();
    }
    public function _initialize()
    {
        $config = cache('db_config_data');

        if(!$config){
            $config = load_config();
            cache('db_config_data',$config);
        }
        config($config);
        self::$redis= RedisPackage::getInstance();
    }

    /**
     * 同一返回方法
     * @param string $code
     * @param array $data
     * @param string $msg
     * @return \think\response\Json
     */
    public function returnMsg($code='1',$data=array(),$msg='获取成功')
    {
        $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($arr);
    }


    public function getTuanAnalysis($storeId,$tuanId){
        $arr=[];
        $pt=new PintuanModel();
        $ptInfo=$pt->getOnePy($tuanId);
        $working=$pt->getPtCont(['storeid'=>$storeId,'tuan_id'=>$tuanId,'status'=>1]);//进行中
        $arr['total']=$ptInfo['pt_num_max'];//总数
        $arr['working']=$working;
        $complete=$pt->getPtCont(['storeid'=>$storeId,'tuan_id'=>$tuanId,'status'=>2]);
        $shixiao=$pt->getPtCont(['storeid'=>$storeId,'tuan_id'=>$tuanId,'status'=>3]);
        $tuikuan=$pt->getPtCont(['storeid'=>$storeId,'tuan_id'=>$tuanId,'status'=>4]);
        $arr['complete']=$complete+$shixiao+$tuikuan;
        $surplus=intval($ptInfo['pt_num_max'])-intval($complete)-intval($shixiao)-intval($tuikuan)-intval($working);
        $arr['surplus']= $surplus;
        return $arr;
    }


    //检测token
    public function checkToken($token)
    {
        $res = Db::name('blink_wx_user')->where('token',$token)->find();
        if(empty($res)) {
            return 0;
        }
        if(time()-$res['time_out'] > 0){
            return 0;
        }
        $time_out = strtotime("+1 days");
        Db::name('blink_wx_user')->where('token', $token)->update(['time_out' => $time_out]);
        return 1;
    }

    //将访客动作存入redis队列
    public function logToRedis($data){
        self::$redis->LPush('userAction',json_encode($data));
    }

    //根据token 获取顾客用户信息
    public function getInfoByToken($token){
        $res=Db::name('wx_user')->where('token',$token)->find();
        if(is_array($res) && count($res)){
            $memberInfo=Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.storeid,member.mobile,member.pid,member.code,member.isadmin,member.staffid,member.id_regsource,b.title')->join(['ims_bwk_branch' => 'b'],'member.storeid=b.id')->where('member.mobile',$res['mobile'])->find();
            if($memberInfo['isadmin']==1){
                return 0;
            }elseif($memberInfo['id']==$memberInfo['staffid'] && strlen($memberInfo['code']) > 2){
                return 0;
            }else{
                $memberInfo['name']=$res['nickname'];
                $memberInfo['avatar']=$res['avatar'];
                $first=Db::name('data_logs')->where(['uid'=>$memberInfo['id'],'action'=>0])->count();
                $memberInfo['first_login']=$first?0:1;
                $memberInfo['sellername']=Db::table('ims_bj_shopn_member')->where('id',$memberInfo['staffid'])->value('realname');
                return $memberInfo;
            }
        }else{
            return 0;
        }
    }

    //将访客动作取出redis队列
    public function logOutRedis($key='userAction'){
        $res = self::$redis->rPop($key);
        return $res;
    }

    //缓存相关

    //设置
    protected function setCacheString($key,$val){
        if(self::$redis->exists($key) == 0){
            self::$redis->set($key,$val);
        }
    }
    //获取
    protected function getCacheString($key){
        return self::$redis->get($key);
    }

    //递减
    protected function setDec($key,$num){
        return self::$redis->DECRBY($key,$num);
    }
    //hash设置
    protected function hashSet($name,$field,$val){
        return self::$redis->HSET($name,$field,$val);
    }
    //hash读取
    protected function hashGet($name,$field){
        return self::$redis->HGET($name,$field);
    }
    //set设置
    protected function saddSet($name,$field){
        return self::$redis->SADD($name,$field);
    }

    //判断在集合中是否存在
    protected function saddSismember($name,$field){
        return self::$redis->SISMEMBER($name,$field);
    }

    //hash设置字段增减量
    protected function hincrby($name,$field,$val){
        return self::$redis->HINCRBY($name,$field,$val);
    }

}