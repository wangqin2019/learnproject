<?php

namespace app\api\controller;
use app\api\model\PintuanModel;
use think\Controller;
use think\Db;
use My\RedisPackage;

class Base extends Controller
{
    protected static $redis;
    public function __construct()
    {
        parent::__construct();
        self::$redis=RedisPackage::getInstance();
    }
    public function _initialize()
    {
        $config = cache('db_config_data');

        if(!$config){            
            $config = load_config();                          
            cache('db_config_data',$config);
        }
        config($config);
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
        $res=Db::name('wx_user')->where('token',$token)->find();
        if(is_array($res) && count($res)){
            if(time()-$res['time_out']>0){
                return 0;
            }else{
                $time_out=strtotime("+1 days");
                Db::name('wx_user')->where('token', $token)->update(['time_out' => $time_out]);
                return 1;
            }
        }else{
            return 0;
        }
    }

    //将访客动作存入redis队列
    public function logToRedis($data){
        self::$redis->LPush('userAction',json_encode($data));
    }

    //根据token 获取顾客用户信息
    public function getInfoByToken($token){
        $res=Db::name('wx_user')->where('token',$token)->find();
        if(is_array($res) && count($res)){
            $memberInfo=Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.storeid,member.mobile,member.pid,member.isadmin,member.staffid,member.id_regsource,b.title')->join(['ims_bwk_branch' => 'b'],'member.storeid=b.id')->where('member.mobile',$res['mobile'])->find();
            if($memberInfo['isadmin']==0 && ($memberInfo['pid']==$memberInfo['staffid'])){
                $memberInfo['name']=$res['nickname'];
                $memberInfo['avatar']=$res['avatar'];
                $first=Db::name('data_logs')->where(['uid'=>$memberInfo['id'],'action'=>0])->count();
                $memberInfo['first_login']=$first?0:1;
                $memberInfo['sellername']=Db::table('ims_bj_shopn_member')->where('id',$memberInfo['pid'])->value('realname');
                return $memberInfo;
            }else{
              return 0;
            }
        }else{
            return 0;
        }
    }
}