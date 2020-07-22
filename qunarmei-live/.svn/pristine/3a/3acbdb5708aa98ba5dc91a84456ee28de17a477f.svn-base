<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/4
 * Time: 10:23
 */

namespace app\api\controller\v4;

use app\api\controller\SerNotice;
use app\api\controller\v3\Common;
use app\api\controller\Wx;
use app\api\model\OtoMod;
use think\Exception;
use think\Request;
use think\Cache;
use app\api\validate\OtoEducation as OtoEducationValidate;
/*
 * App脑力教育相关功能API
 * */
header('Access-Control-Allow-Origin:*');
class OtoEducation extends Common
{

    /*
     * 功能: 初始化方法
     * 请求:
     * */
    public  function _initialize()
    {
        $this->dt = time();
        // 统一处理数据验证
        $request = Request::instance();
        $arr = $request->param();
        $action = $request->action();
        // 记录请求日志
        $data_req = $_REQUEST;
        // 将请求数组拼接成url地址参数
        $msg_req = '请求数据:'.http_build_query($data_req , '' , '&');
        parent::writeLog($msg_req);
        $result = $this->validate($arr,OtoEducationValidate::$func[$action]);
        if(true !== $result){
            $this->rest['msg'] = '请求参数错误:'.$result;
            parent::returnMsgError($this->rest['msg']);
        }
    }

    /**
     * 发送OTO学习卡
     * @param int $mobile 手机号
     * @param int $store_id 门店id
     * @param int $orderid 订单id
     * @param int $goods_id 商品id
     * @return
     *
     */
    public function sendOtoCard()
    {
        $mobile = input('mobile');
        $store_id = input('store_id');
        $orderid = input('orderid');
        $goods_id = input('goods_id');
        // 1.有没有卡
        $otomod = new OtoMod();
        $map['mobile'] = $mobile;
        $map['type'] = 9;
        $res_oto = $otomod->getTicketUser($map);
        if($res_oto){
            $this->rest['msg'] = '已拥有OTO学习卡';
        }else{
            $mapb['m.mobile'] = $mobile;
            $res_bwk = $otomod->getUserBranch($mapb);
            if($res_bwk){
                $data['depart'] = $res_bwk['st_department'];
                $data['storeid'] = $store_id;
                $data['branch'] = $res_bwk['title'];
                $data['sign'] = $res_bwk['sign'];
                $data['mobile'] = $mobile;
                $data['type'] = 9;
                $data['user_id'] = $res_bwk['id'];
                $data['orderid'] = $orderid;
                $data['goods_id'] = $goods_id;
                $data['insert_time'] = date('Y-m-d H:i:s');
                $otomod->insTicket($data);
//                // 购买成功,推送站内信通知 + 短信通知
//                // 推送站内信通知
                $sernotice = new SerNotice();
                $sernotice->sendJpush(4,'alias',$mobile,'赠送的OTO脑力学习卡课程优惠,请进入个人中心我的卡券查收!');
                // 推送短信通知
                $msg = $sernotice->sendSms($mobile,81);
                $this->rest['msg'] = 'OTO学习卡发送成功';
            }
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 账号展示
     * 请求: int $user_id=>用户id
     * */
    public function otoAccount ()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        // 用户名,头像,下载地址,账号,密码
        $otoMod = new OtoMod();
        $map['m.id'] = $arr['user_id'];
        $rest = $otoMod->getOtoMem($map);
        if(!empty($rest)){
            $res['user_name'] = $rest['realname'];
            $res['head_img'] = $rest['avatar']==''?config('head_img'):$rest['avatar'];
            $res['down_url'] = config('url.oto_down_url');
            $res['oto_user'] = $rest['oto_user'];
            $res['oto_pwd'] = $rest['oto_pwd'];
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 学霸计划战绩榜
     * @param $oto_user => 账号
     * @param $page => 当前页,默认第1页
     * @param $page => 当前页,默认第1页
     * @return string
     */
    public function getOtoRecord()
    {
        // 请求参数
        $arr['oto_user'] = input('oto_user','');
        $arr['type'] = input('type',0);

        $rest = [];
        $otomod = new OtoMod();
        $map['o.oto_user'] = $arr['oto_user'];
        $map['r.type'] = $arr['type'];
        // 1.自己排名数据
        $res_own = $otomod->getRecordList($map);
        // 个人金币数,个人排名数
        if($res_own){
            $res1['coin_num'] = $res_own[0]['coin_num'];
            $res1['ranking'] = $res_own[0]['ranking'];
            $res1['word_num'] = $res_own[0]['word_num'];
            $res1['online_time'] = $res_own[0]['online_time'];
            $res1['clearance_num'] = $res_own[0]['clearance_num'];
            $rest['own_data'] = $res1;
        }
        // 排行榜列表
        $map1['r.type'] = $arr['type'];
        $res_list = $otomod->getRecordList($map1);
        if($res_list){
            // 排名,用户名,金币数,初次登录时间,最后登录时间,单词总数,在线时长,通关总数
            $res3 = [];

            foreach ($res_list as $k=>$v) {
                if($v['oto_user'] == $arr['oto_user']){
                    $rest['own_data']['ranking'] = $k+1;
                }
                $res2['ranking'] = $k+1;
                $res2['user_name'] = $v['user_name'];
                $res2['coin_num'] = $v['coin_num'];
                $res2['first_login_time'] = $v['first_login_time']==''?'':date('Y-m-d H:i:s',$v['first_login_time']);
                $res2['last_login_time'] = $v['last_login_time']==''?'':date('Y-m-d H:i:s',$v['last_login_time']);
                $res2['word_num'] = $v['word_num'];
                $res2['online_time'] = $v['online_time'];
                $res2['clearance_num'] = $v['clearance_num'];
                $res3[] = $res2;
            }
//            echo '<pre>';print_r($res3);die;
            if($res3){
                $rest['rank_list'] = $res3;
            }
        }
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 获取分类和学习类型
     * 请求: 分类和学习类型
     * */
    public function getClassify()
    {
        // 请求参数
        $rest = [];

        // 分类
//        $res1 = [
//            [
//                'cla_id' => 1,
//                'cla_name' => 'AI单词'
//            ],
//            [
//                'cla_id' => 2,
//                'cla_name' => '快乐魔方'
//            ],
//        ];
        // 类型
//        $res2 = [
//            [
//                'type_id' => 1,
//                'type_name' => '人教版小学PEP'
//            ],
//            [
//                'type_id' => 2,
//                'type_name' => '人教版初中go for it'
//            ],
//        ];

        $res1 = Cache::get('oto_queryTypes_classfy');
        $res2 = Cache::get('oto_queryTypes_types');

        $rest['classfy'] = $res1;
        $rest['types'] = $res2;
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /*
     * 功能: 锦囊-查询
     * 请求: int $cla_id 分类id,int $type_id 类型id,string $oto_user oto账号,string $begin_time 开始时间,string $end_time 结束时间
     * */
    public function getSilkBag()
    {
        // 请求参数
        $arr['cla_id'] = input('cla_id','');
        $arr['type_id'] = input('type_id',0);
        $arr['oto_user'] = input('oto_user','');
        $arr['begin_time'] = input('begin_time',0);
        $arr['end_time'] = input('end_time','');

        $rest = [];
        $comoto = new \app\api_public\controller\OtoEducation();
        $rest = $comoto->queryTips($arr);
//        echo '<pre>';print_r($rest);die;
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = [];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: Q&A列表
     * 请求: sting $key=>关键字查询
     * */
    public function getQaList()
    {
        // 请求参数
        $arr['key'] = input('key','');

        $otomod = new OtoMod();
        $map = null;
        if($arr['key']){
            $map['question|answer'] = ['like','%'.$arr['key'].'%'];
        }
        $res = $otomod->getQas($map);
        if($res){
            foreach ($res as $v) {
                $res1['question'] = $v['question'];
                $res1['answer'] = $v['answer'];
                $rest[] = $res1;
            }
        }
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = [];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 诚美扫码微信支付
     */
    public function payScanCm()
    {
        // 请求数据
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');

        $wx_pay = new Wx();
        $arr['title'] = 'oto脑力学习教育卡';
        $arr['orderNo'] = '';// 生成订单号
        $arr['fee'] = 168*100;// 168元
        try{
            $getRand=rand(10000,99999);//5位随机数
            /*生成订单号*/
            $len=strlen($arr['store_id']);
            $zero = '';
            for($i=$len; $i<5; $i++){
                $zero .= "0";
            }
            $ordersn = $arr['store_id'].$zero.time().$getRand;// 组合订单编号 共20位
            $arr['orderNo'] = $ordersn;
            $res = $wx_pay->getScanPay($arr['title'],$arr['orderNo'],$arr['fee']);
            if($res){
                $this->rest['data']['url'] = $res;
            }else{
                $this->rest['data'] = (object)[];
                $this->rest['msg'] = '获取失败';
            }
        }catch(Exception $e){
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = $e->getMessage();
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}