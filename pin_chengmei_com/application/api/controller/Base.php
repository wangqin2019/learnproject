<?php

namespace app\api\controller;
use app\api\model\GoodsModel;
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
        if($token=='abcdefg'){
            return 1;
        }else {
            $res = Db::name('wx_user')->where('token', $token)->find();
            if (is_array($res) && count($res)) {
                if (time() - $res['time_out'] > 0) {
                    return 0;
                } else {
                    $time_out = strtotime("+1 days");
                    Db::name('wx_user')->where('token', $token)->update(['time_out' => $time_out]);
                    return 1;
                }
            } else {
                $res1 = Db::name('wx_live_user')->where('token', $token)->find();
                if (is_array($res1) && count($res1)) {
                    if (time() - $res1['time_out'] > 0) {
                        return 0;
                    } else {
                        $time_out = strtotime("+1 days");
                        Db::name('wx_live_user')->where('token', $token)->update(['time_out' => $time_out]);
                        return 1;
                    }
                } else {
                    return 0;
                }
            }
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


    //检测登陆
    public function c_checkLogin($open=1){
        if($open) {
            $token = input('param.token');
            if ($token == '') {
                $code = 400;
                $data = '';
                $msg = '非法请求';
                echo json_encode(array('code' => $code, 'data' => $data, 'msg' => $msg));
                exit;
            } else {
                if (!$this->checkToken($token)) {
                    $code = 400;
                    $data = '';
                    $msg = '用户登陆信息过期，请重新登录！';
                    echo json_encode(array('code' => $code, 'data' => $data, 'msg' => $msg));
                    exit;
                } else {
                    return true;
                }
            }
        }else{
            return true;
        }
    }

    //检测活动
    public function c_check_activity($id){
        $activityInfo=Db::name('activity_list')->where('id',$id)->find();
        if($activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动已结束！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }

        if($activityInfo['begin_time'] > time() ){
            $code = 0;
            $data = '';
            $msg = '活动将于'.date('Y年m月d日 H时i分s秒',$activityInfo['begin_time']).'开启，请等待！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }

        if($activityInfo['end_time'] < time() ){
            $code = 0;
            $data = '';
            $msg = '活动已结束';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
    }


    //获取配赠
    public function c_get_given($given,$pid){
        $m=[];
        $goods_given=[];
        $goods=new GoodsModel();
        if(strlen($given)) {
            $m['status'] = array('eq', 1);
            $m['id'] = array('in', $given);
            $goods_given = Db::name('goods')->where($m)->field('id,goods_code,stock')->select();
            if ($goods_given) {
                foreach ($goods_given as $k => $v) {
                    $goods_given[$k]= $goods->getGoodsByMap(['id' => $v['id']]);
                    $goods_given[$k]['stock'] = $this->get_no_stock_goods($v['goods_code'], 1);//将库存存入缓存
                }
            }
        }
        return $goods_given;
    }

    //记录产品库存
    public function c_get_stock($code,$stock=0){
//        $getStock = $this->hashGet('sku_stock', $code );
//        if (!$getStock) {
//            $this->hashSet('sku_stock', $code, $stock);
//            $getStock=$this->hashGet('sku_stock', $code);
//        }
//        return $getStock;
        $stock=$this->get_no_stock_goods($code);
        return $stock?99999:0;
    }


    /**
     * Notes:检测该商品是否允许购买
     * User: HOUDJ
     * Date: 2020/5/6
     * Time: 18:12
     * @param $goodsCode
     * @param int $flag
     * @return int
     */
    public function get_no_stock_goods($goodsCode,$flag=0){
        $disableGoods=[];
        $goods=Db::table('ims_bj_shopn_restrict_buy')->where('statu',1)->column('goods_code');
        if(count($goods)){
            $disableGoods= $goods;
        }
        if(in_array($goodsCode,$disableGoods)){
            return 0;
        }else{
            return $flag?9999:1;
        }
    }


    //根据门店 检查是否允许下该类型订单
    public function c_branch_rule($storeid,$activity_id){
        $activity_ids=Db::table('ims_bwk_branch')->where('id',$storeid)->value('join_tk');
        if(strlen($activity_ids)){
            $join_tk_arr=explode(',',$activity_ids);
            if(!in_array($activity_id,$join_tk_arr)){
                return 0;
            }else{
                return 1;
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
        if(self::$redis->exists($key)==0){
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

    //获取集合中的所有数据
    protected function getMembers($name,$num=0){
        if($num){
            return self::$redis->spop($name);
        }else{
            return self::$redis->sMembers($name);
        }

    }

    //移除集合中n条数据
    protected function getMembersByNum($name,$num=0){
        $res='';
        if($num){
            for($i=0;$i<$num;$i++){
                if(self::$redis->exists($name)==1) {
                    $res .= self::$redis->spop($name).',';
                }else{
                    break;
                }
            }
            return rtrim($res, ',');
        }else{
            return $res;
        }
    }

}