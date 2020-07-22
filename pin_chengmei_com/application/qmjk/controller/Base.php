<?php

namespace app\qmjk\controller;
use think\Controller;
use think\Db;
use My\RedisPackage;

class Base extends Controller
{
    protected static $redis;
    protected $jkConfig;
    public function __construct()
    {
        parent::__construct();
        self::$redis=RedisPackage::getInstance();
        $this->jkConfig=Db::name('qmjk_config')->where('id',1)->find();
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


    //检测token
    public function checkToken($token)
    {
        $res=Db::name('qmjk_wx_user')->where('token',$token)->find();
        if(is_array($res) && count($res)){
            if(time()-$res['time_out']>0){
                return 0;
            }else{
                $time_out=strtotime("+1 days");
                Db::name('qmjk_wx_user')->where('token', $token)->update(['time_out' => $time_out]);
                return 1;
            }
        }else{
            return 0;
        }
    }


    //根据token 获取顾客用户信息
    public function getInfoByToken($token){
        $res=Db::name('qmjk_wx_user')->where('token',$token)->find();
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
}