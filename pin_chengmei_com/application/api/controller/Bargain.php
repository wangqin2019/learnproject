<?php
/**
 * Created by PhpStorm.
 * User: houdj
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\api\controller;
use think\Cache;
use think\Db;
use algor\Bargain as algor_bargain;
use app\api\model\BargainRecordModel;
use app\api\model\BargainOrderModel;
use app\api\model\GoodsBargainModel;
use think\Exception;
use weixin\WeixinPay;

/**
 * swagger: 砍价接口
 */
class Bargain extends Base
{
    public static $missshop_2_expire = 100;//毫秒
    public $config = [];
    protected static $return_content = [
        '今天的你颜值爆表，人品爆棚，你的超人气为她拼下了{PRICE}元',
        '小姐姐的人品真的长在我心里了，始于颜值，忠于人品讲的就是你啦！恭喜成功为她助力{PRICE}元',
        '颜值与人品兼备，恭喜你的拼团和拼命，为她省下{PRICE}元，只有你最美！',
        '高颜值超人气仙女启动魔法棒，成功拿下{PRICE}元，为您攒了一大笔钱哦！',
        '你确实是自带吸引力高人品和超颜值，为她助力{PRICE}元，所有的好运都应你的人品爆发！',
    ];
    protected static $promoter_content = [
        '都说你人品好，快来帮我拼吧！','帮我拼人品，心“肌”福利一起享！',
        '帮我点一下好不好？你人品最好啦！',
        '嗨！验证人品的时候到了，快来帮我点一下啦，爱你哟！',
        '你需要我时，我会立马出现，但现在我很需要你帮我点一下哟！'
    ];
    protected static $_goods = [
        85 => [
            'id' => 92,
            'goods_id' => 55,
        ],
        91 => [
            'id' => 93,
            'goods_id' => 54,
        ],
        122 => [
            'id' => 92,
            'goods_id' => 55,
        ],
        124 => [
            'id' => 92,
            'goods_id' => 55,
        ],
        126 => [
            'id' => 92,
            'goods_id' => 55,
        ],
        128 => [
            'id' => 92,
            'goods_id' => 55,
        ],
        130 => [
            'id' => 92,
            'goods_id' => 55,
        ],
        132 => [
            'id' => 92,
            'goods_id' => 55,
        ],
    ];//一对一奖励商品

    public function _initialize() {
        parent::_initialize();
        $token = input('param.token','');
        $this->config = Db::name('bargain_config')->where('id',1)->find();
        if(empty($this->config) || empty($this->config['activity_status'])){
            echo json_encode(array('code'=>400,'data'=>'','msg'=>'活动未开启'));
            exit;
        }
        if($token == ''){
            return true;
            $code = 400;
            $data = '';
            $msg = '非法请求';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }else{
            if(!parent::checkToken($token)) {
                $code = 400;
                $data = '';
                $msg = '用户登陆信息过期，请重新登录！';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }else{
                return true;
            }
        }
    }
    //判断是否是转客
    public function transfer(){
        $uid = intval( input('param.uid', 0) );//当前用户id
        $flag = Db::table('ims_bj_shopn_member')
            ->where('uid','=',$uid)
            ->where('activity_flag','in',['8808'])
            ->value('activity_flag');
        if(empty($flag)){
            echo json_encode(array('code'=>400,'data'=>'','msg'=>'您不是转客用户，不能参加拼人品活动。'));
            exit;
        }
        return true;
    }
    /**
     * Commit: 判断门店是否开通砍价活动
     * Function: checkStore
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-22 14:59:44
     * @Return bool|\think\response\Json
     */
    public function checkStore($storeid = 0){
        $storeid = intval( input('param.storeid', 0) ) ?: $storeid;//门店
        $re = Db::table('ims_bwk_branch')
            ->where('id',$storeid)
            ->field('is_bargain,bargain_plan')
            ->find();
        return $re;
    }

    /**
     * commit: 检测门店参与的活动
     * function: checkStorePlan
     * @param int $storeid
     * author: stars<1014916675@qq.com>
     * dateTime: 2019-12-12 13:44:45
     * @return array
     */
    public function checkStorePlan($storeid = 0){
        $storeid = intval( input('param.storeid', 0) ) ?: $storeid;//门店
        $plan =  Db::table('ims_bwk_branch')
            ->where('id',$storeid)
            ->where('is_bargain',1)
            ->value('bargain_plan');
        $plans = config('bargainPlan');//拼人品活动方案
        $data = [];
        $bargain_plan_arr = $plan ? explode(',', $plan) : '';
        foreach ($plans as $k=>$val){
            if(empty($plan)){
                $data[$val['activity']] = false;
            }else{
                if(!empty($bargain_plan_arr)){
                    if (in_array($k, $bargain_plan_arr)) {
                        $data[$val['activity']] = true;
                    }else{
                        $data[$val['activity']] = false;
                    }
                }else{
                    $data[$val['activity']] = false;
                }
            }
        }
        return $data;
    }
    //检测用户所在门店的权限
    public function checkAuth(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $re = Db::table('ims_bwk_branch')->where('id',$storeid)->find();
        $activity = [];
        //拼人品活动是否开启
        if($re['is_bargain']){
            $activity['bargain']['main'] = true;
            $bargain_plan = $re['bargain_plan'];//活动方案
            if(!empty($bargain_plan)){
                $bargain_plan_arr = explode(',', $bargain_plan);
                foreach ($bargain_plan_arr as $k => $v) {
                    if (array_key_exists($v, config('bargain'))) {
                        switch ($v) {
                            case 1:
                                $activity['bargain']['main'] = false;
                                $activity['bargain']['bargainKey']['main'] = true;
                                $activity['bargain']['bargainKey']['main_comment'] = config('bargain.'.$v);
                                break;
                            case 2:
                                $activity['bargain']['main'] = true;
                                $activity['bargain']['bargainKey']['special'] = true;
                                $activity['bargain']['bargainKey']['special_comment'] = config('bargain.'.$v);
                                break;
                        }
                    }
                }
            }
        }else{
            $activity['bargain']['main'] = false;
        }
        return json(['code'=>1,'data'=>$activity,'msg'=>'']);
    }

    /**
     * Commit: 门店权限验证 plan基础活动
     * [
     *  'plan'=>'拼人品主活动',
     *  'planA'=>'个性化门店活动',
     *  'planB'=>'2019年终裂变活动'
     * ]
     * Function: checkBargainAuth
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-12 13:18:11
     * @Return \think\response\Json
     */
    public function checkBargainAuth(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $re = Db::table('ims_bwk_branch')->where('id',$storeid)->find();
        $plans = config('bargainPlan');//拼人品活动方案
        $activity = [];
        $is_bargain = !empty($re) ? $re['is_bargain'] : 0;
        $bargain_plan = !empty($re) ? $re['bargain_plan'] : '';//该门店启用的方案

        $bargain_plan_arr = $bargain_plan ? explode(',', $bargain_plan) : '';

        foreach ($plans as $k=>$val){
            if(!empty($is_bargain)){
                if(!empty($bargain_plan_arr)){
                    if (in_array($k, $bargain_plan_arr)) {
                        $activity['bargainPlan'][$val['activity']] = true;
                    }else{
                        $activity['bargainPlan'][$val['activity']] = false;
                    }
                }else{
                    $activity['bargainPlan'][$val['activity']] = false;
                }
            }else{
                $activity['bargainPlan'][$val['activity']] = false;
            }
            $activity['bargainPlan'][$val['activity'].'Comment'] = $val['name'];
            $activity['bargainPlan'][$val['activity'].'Flag'] = $val['activity'];
        }
        return json(['code'=>1,'data'=>$activity,'msg'=>'门店活动权限验证']);
    }


    /**
     * Commit: 获取用户所属门店参与砍价活动的商品 商品列表
     * Function: goods_list
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 17:57:50
     * @Return \think\response\Json
     */
    public function goods_list() {
        $storeid = intval( input('param.storeid', 0) );//门店
        $page = intval( input('param.page', 1) );//分页
        if(empty($storeid)){
            return parent::returnMsg(0,'','门店未参与活动或已关闭');
        }
        //查询关联产品及奖励
        $map['gb.storeid']   = $storeid;//门店id
        $map['gb.pid']       = 0;//类型 活动商品
        $map['g.is_bargain'] = 1;//商品是否参加砍价活动
        $map['g.status']     = 1;//商品是否上架
        $goodsBargainModel   = new GoodsBargainModel();
        $list = $goodsBargainModel->getStoreGoodsList($map,$page);
        $info['list']        = $list;
        $info['bargainPlan']        = $this->checkStorePlan($storeid);

        if(!empty($list)){
            $code = 1;
            $data = $info;
            $msg  = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg  = '暂无数据';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    /**
     * Commit: 获取商品信息及关联奖励产品 商品详情
     * Function: goods
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 18:57:31
     * @Return \think\response\Json
     */
    public function goods() {
        //$this->transfer();
        $storeid = intval( input('param.storeid', 0) );//门店
        if(empty($storeid)){
            return parent::returnMsg(0,'','门店未参与活动或已关闭');
        }

        $goods_id = intval( input('param.goods_id', 0) );//商品id
        if(empty($goods_id)){
            return parent::returnMsg(0,'','商品未参与活动或已下架');
        }

        //砍价商品
        $map['gb.storeid'] = $storeid;
        $map['g.status'] = 1;//商品是否上下架
        $map['gb.pid'] = 0;
        $map['gb.goods_id'] = $goods_id;
        $goodsBargain = new GoodsBargainModel();
        $list = $goodsBargain->getActivityGoodsInfo($map);
        if(empty($list)){
            $code = 0;
            $data = '';
            $msg = '商品不存在或已下架';
            return parent::returnMsg($code,$data,$msg);
        }
        $info['list'] = $list;
        //判断是否是个性化门店
        $res = $this->checkStore($storeid);

        if(!empty($res) && !empty($res['bargain_plan']) && strpos($res['bargain_plan'],',2')!==false){
            $info['special'] = 2;//个性化
            if(empty($list['storeid'])){
                $info['special'] = 1;
            }
        }else if(!empty($res) && !empty($res['bargain_plan']) && strpos($res['bargain_plan'],'1')!==false){
            $info['special'] = 1;//个性化
        }else{
            $info['special'] = 0;
        }

        $storePlan = $this->checkStorePlan($storeid);//获取门店参与的方案
        if(empty($list['storeid'])){
            $storePlan['planA'] = false;
            $storePlan['planB'] = false;
        }
        $info['storePlan'] = $storePlan;
        $code = 1;
        $data = $info;
        $msg  = '获取成功';
        return parent::returnMsg($code,$data,$msg);
    }
    /**
     * Commit: 砍价发起人发起分享时生成订单及预生成参与人砍价记录 发起砍价预生成订单
     * Function: share_goods
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 09:52:01
     * @Return \think\response\Json
     */
    public function share_goods() {
        //判断当前用户所属门店是否参与活动
        try{
            $storeid = intval( input('param.storeid', 0) );//门店
            $goods_id = intval( input('param.goods_id', 0) );//商品id
            $fid = intval( input('param.fid', 0) );//美容师id
            $uid = intval( input('param.uid', 0) );//发起人id
            $num = intval( input('param.num', 1) );//购买数量
            $_count = intval( input('param.count', 0) );//参与人数
            //查询活动商品信息
            $GoodsBargainModel = new GoodsBargainModel();
            $map['gb.goods_id'] = $goods_id;
            $map['gb.storeid'] = $storeid;
            $list = $GoodsBargainModel->getActivityGoodsInfo($map);
            $time = time();
            //生成订单
            $order_sn = createOrderSn();
            $orders['storeid'] = $storeid;
            $orders['uid'] = $uid;
            $orders['goods_id'] = $goods_id;
            $orders['fid'] = $fid;
            $orders['order_sn'] = $order_sn;
            $orders['num'] = $num;
            $orders['order_type'] = 1;//订单类型  1我发起 2直接购买 3奖励购买 0我参与的
            $orders['is_type'] = 1;//活动订单
            $orders['is_purchase'] = 1;//砍价购买
            $orders['pay_status'] = 0;//未支付
            $orders['order_price'] = $list['price'] * $num;//未支付
            $orders['pay_price'] = $list['activity_price'] * $num;//未支付
            $orders['insert_time'] = $time-5;//发起时间
            $orders['pick_code'] = pickUpCode('bargain_'.$order_sn);;//订单二维码
            $orders['close_time'] = $orders['insert_time'] + $this->config['duration'] * 3600;//订单失效时间
            $plan = $this->checkStorePlan($storeid);
            if($plan['planA'] == true ){
                $orders['flag'] = 1;
            }else if($plan['planB'] == true){
                $orders['flag'] = 2;
            }
            $order_id = Db::name('bargain_order')->insert($orders,false,true);
            //订单添加成功
            if(!empty($order_id)){
                $number = $_count ?: ($list['bargain_number'] ?: $this->config['number']);
                $total = ($list['price'] - $list['activity_price']) * $num;
                $first = $this->config['first_reba'];
                $next = $this->config['next_reba'];
                $arr = self::make_price($number,$total,$first,$next);
                //预添加参与记录
                $record = [];
                $return_content = self::$return_content;
                foreach ($arr as $k=>$v){
                    $record[$k]['price'] = $v;//砍价金额
                    $record[$k]['order_id'] = $order_id;//发起人订单id
                    $record[$k]['goods_id'] = $goods_id;//商品id
                    $record[$k]['promote_uid'] = $uid;//发起人uid
                    $record[$k]['status'] = 0;//未启用
                    $record[$k]['create_time'] = $time;//发起人时间
                    shuffle($return_content);
                    $content = $return_content[0];
                    if(strpos($content,'{PRICE}') !== false){
                        $content = str_replace('{PRICE}',$v,$content);
                    }
                    $record[$k]['content'] = $content;//弹框文案
                }
                Db::name('bargain_record')->insertAll($record);
                $code = 1;
                $data = config('wx_pay');
                $data['order_id'] = $order_id;
                $data['user_id'] = $uid;
                $data['attach'] = 'bargain';
                $data['buy_type'] =  1;
                $data['order_sn'] =  $order_sn;
                $data['num'] =  $num;
                $data['market_price'] = $list['price'];//未支付
                $data['price'] =  $list['activity_price'];
                $data['total_fee'] = $data['price'] * $num;
                $data['body'] = $list['intro'] ?: $list['name'];
                $msg = '订单提交成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '参数缺失，订单提交失败';
            }
            return parent::returnMsg($code,$data,$msg);
        }catch (Exception $e){
            return parent::returnMsg(0,'',$e->getMessage());
        }
    }
    /**
     * Commit: 直接购买生成订单 直接购买活动商品0  购买砍价后获取的优惠商品1
     * Function: direct_buy_order
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-22 16:27:27
     * @Return \think\response\Json
     */
    public function direct_buy_order() {
        //判断当前用户所属门店是否参与活动
        $storeid = intval( input('param.storeid', 0) );//门店
        $order_id = intval( input('param.order_id', 0) );//订单id
        $type = intval( input('param.type', 0) );//类型 奖励产品 0直接购买  或 1优惠购买

        $goods_id = intval( input('param.goods_id', 55) );//商品id
        $fid = intval( input('param.fid', 0) );//美容师id
        $uid = intval( input('param.uid', 0) );//发起人id
        $num = intval( input('param.num', 1) );//购买数量
        if(empty($order_id)){
            //查询活动商品信息
            $GoodsBargainModel = new GoodsBargainModel();
            $map['gb.goods_id'] = $goods_id;
            $map['gb.storeid'] = $storeid;
            $list = $GoodsBargainModel->getActivityGoodsInfo($map);
            $time = time();
            //生成订单
            $order_sn = createOrderSn();
            $orders['storeid'] = $storeid;
            $orders['uid'] = $uid;
            $orders['goods_id'] = $goods_id;
            $orders['fid'] = $fid;
            $orders['order_sn'] = $order_sn;
            $orders['num'] = $num;
            $orders['status'] = 2;//已完成订单
            $orders['order_type'] = $type == 1 ? 3 : 2;//订单类型  1我发起 2直接购买 3奖励购买 0我参与的
            $orders['is_type'] = 0;//奖励订单
            $orders['is_purchase'] = $type;//直接购买
            $orders['pay_status'] = 0;//未支付
            $orders['order_price'] = $list['price'] * $num;//未支付
            $orders['pay_price'] = ($type ? $list['activity_price'] : $list['price']) * $num;//支付金额
            $orders['insert_time'] = $time;//发起时间
            $orders['pick_code'] = pickUpCode('bargain_'.$order_sn);;//订单二维码
            $order_id = Db::name('bargain_order')->insert($orders,false,true);
            $market_price = $list['price'];
            $price = $type ? $list['activity_price'] : $list['price'];
            $total_fee = $price * $num;//商品优惠价*数量
        }else{
            //查询订单信息
            $list = Db::name('bargain_order')
                ->alias('o')
                ->field('o.*,g.intro,g.name')
                ->join('goods g','o.goods_id=g.id','left')
                ->where('o.id','=',$order_id)
                ->find();

            if($list['pay_status'] == 1){
                return parent::returnMsg('0',$list,'该订单已支付！');
            }
            $market_price = $list['order_price'];
            $price = $list['pay_price'];
            $total_fee = $list['pay_price'];
            $order_sn = $list['order_sn'];
            $uid = $uid ?: $list['uid'];
            $num = $num ?: $list['num'];
        }

        //订单添加成功
        if(!empty($order_id)){
            $code = 1;
            $data = config('wx_pay');
            $data['order_id'] = $order_id;
            $data['user_id'] = $uid;
            $data['attach'] = 'bargain';
            $data['buy_type'] =  $type;
            $data['order_sn'] =  $order_sn;
            $data['num'] =  $num;
            $data['market_price'] = $market_price;//订单金额
            $data['price'] = $price;//待支付金额
            $data['total_fee'] = $total_fee;
            $data['body'] = $list['intro'] ?: $list['name'];
            $msg = '订单提交成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数缺失，订单提交失败';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    /**
     * Commit: 拼人品详情数据 及 参与人记录   砍价详情
     * Function: bargain_info
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 13:33:11
     * @Return \think\response\Json
     */
    public function bargain_info()
    {
        $order_id = intval( input('param.order_id', 0) );//订单id
        $_count = intval( input('param.count', 0) );//订单id

        $storeid = intval( input('param.storeid', 0) );//门店
        $uid = intval( input('param.uid', 0) );//发起人id
        //用户发起
        $BargainOrderModel = new BargainOrderModel();

        //根据订单查询发起或参与信息
        #$map['o.storeid'] = $storeid;
        #$map['o.uid'] = $uid;
        $map['o.id'] = $order_id;//订单编号
        $info = $BargainOrderModel->promoteUidOrderInfo($map);
        if(empty($info)){
            return parent::returnMsg(0,'','订单已删除或不存在');
        }
        if(empty($storeid)){
            $storeid = $info['storeid'];
        }

        //查询多少人参与及详情
        $BargainRecordModel = new BargainRecordModel();
        $where['order_id'] = $info['order_id'];
        $where['goods_id'] = $info['goods_id'];
        $where['promote_uid'] = $info['uid'];//发起人uid
        $where['status'] = 1;
        $record = $BargainRecordModel->partakeRecordList($where);
        $sum = $BargainRecordModel->orderSum($where);
        $info['reba'] = $sum ? round(($sum/$info['bargain_price']) * 100,2) . '%' :0;
        $info['spell_price'] = $sum; //已砍金额
        $info['bargain_price'] = $info['order_price'] - $info['pay_price']; //已砍金额
        $info['lack_price'] = $info['bargain_price'] - $sum;//未砍金额
        $info['expire_time'] = $this->config['duration'] * 3600 + $info['insert_time'];
        $info['spell_time'] = time() > $info['expire_time'] ?0: ($info['expire_time']- time()) ;
        $info['spell_format'] = $info['spell_time'] ? $this->getTime($info['spell_time']) : '';
        $info['duration'] = $this->config['duration'];
        if($info['expire_time'] < time()){
            $info['flag'] = 0;//过期
        }else{
            $info['flag'] = 1;
        }

        $data['info'] = $info;
        $data['record'] = $record;//砍价记录
        $data['count'] = $BargainRecordModel->recordCount($where);//当前活动参与人数量
        $data['number'] = $_count ?: $info['bargain_number'];//商品配置参与人数量
        $data['config_number'] = $this->config['number'];//默认配置参与人数量
        //是否能买
        if($info['pay_status'] == 1){
            $data['again_buy'] = false;
        }else{
            $data['again_buy'] = $data['number'] == $data['count'] ? true : false;
        }
        //判断是否是个性化门店
        $res = $this->checkStore($storeid);

        if(!empty($res) && !empty($res['bargain_plan']) && strpos($res['bargain_plan'],',2')!==false){
            $data['special'] = 2;//个性化
            if(empty($info['sid'])){
                $data['special'] = 1;
            }
        }else if(!empty($res) && !empty($res['bargain_plan']) && strpos($res['bargain_plan'],'1')!==false){
            $data['special'] = 1;//个性化
        }else{
            $data['special'] = 0;
        }
        $storePlan = $this->checkStorePlan($storeid);//获取门店参与的方案
        if(empty($info['sid'])){
            $storePlan['planA'] = false;
            $storePlan['planB'] = false;
        }
        $data['storePlan'] = $storePlan;
        return parent::returnMsg(1,$data,'数据请求成功');
    }
    /**
     * Commit: 获取用户发起和参与的砍价活动列表 我的砍价及我的参与列表
     * Function: bargain_list
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:49:09
     * @Return \think\response\Json
     */
    public function bargain_list() {
        $storeid = intval( input('param.storeid', 0) );//门店
        $uid = intval( input('param.uid', 0) );//发起人id
        $start = input('param.start','');//开始时间
        $end = input('param.end','');//结束时间
        $status = intval( input('param.type', 1) );//订单类型 1进行中 2已完成 3已失效

        if(empty($storeid) || empty($uid)) {
            return parent::returnMsg(0,'','参数缺失');
        }

        $where = [];
        $where['o.status'] = $status ?: 3;
        $where['o.uid'] = $uid;
        $where['o.storeid'] = $storeid;
        if(!empty($end) && !empty($start)){
            $start = strtotime($start);
            $end = strtotime($end.' 23:59:59');
            $where['o.insert_time'] = [ 'between' , [ $start , $end ]];
        }
        if(!empty($start)){
            $start = strtotime($start);
            $where['o.insert_time'] = [ '<=' , $start ];
        }
        if(!empty($end)){
            $end = strtotime($end.' 23:59:59');
            $where['o.insert_time'] = [ '>=' , $end ];
        }
        //获取当前用户所参与或发起的砍价
        $pagesize = config('list_rows')?:15;//每页条数
        $page = intval(input('param.page', 1));//分页

        $BargainOrderModel = new BargainOrderModel();
        if($status == 1){
            $where['o.order_type'] = ['<>',3];
            //进行中
            $list = $BargainOrderModel->getUnderWayOrderList($where,$page,$pagesize);
        }else if($status == 2){
            $where['o.pay_status'] = 1;
            //翼支付
            $list = $BargainOrderModel->getPaymentOrder($storeid,$uid,$page,$pagesize);
        }else{
            $where['o.pay_status'] = 0;
            //已失效
            $list = $BargainOrderModel->getFailureOrderList($where,$page,$pagesize);
        }
        $data['info'] = array(
            'row' => !empty($list) ? count($list) : 0,
            'size' => $pagesize,
            'page' => $page,
        );
        $data['list'] = $list;
        if(!empty($list)){
            $code = 1;
            $msg = '请求成功';
        }else{
            $code = 0;
            $msg = '暂无数据';
        }

        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * Commit: 获取美容师下属的砍价活动列表
     * Function: bargain_fid_list
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 11:49:09
     * @Return array
     */
    public function bargain_fid_list() {
        $storeid = intval( input('param.storeid', 0) );//门店
        $fid = intval( input('param.fid', 0) );//美容师id
        $start = input('param.start','');//开始时间
        $end = input('param.end','');//结束时间

        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }

        $where = [];
        $where['o.storeid'] = $storeid;
        //根据fid查询是否是店老板
        $abc = Db::table('ims_bj_shopn_member')
            ->where('isadmin','=',1)
            ->where('staffid','=',$fid)
            ->value('storeid');
        if(!empty($abc)){
            $fid_list =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                //->where('m.pid','=',0)
                ->where('m.code','<>','\'\'')
                ->where('m.staffid = m.id')
                ->where('m.storeid','=',$abc)
                ->column('id');
            $where['o.fid'] = ['in',$fid_list];
        }else{
            $where['o.fid'] = $fid;
        }
        if(!empty($end) && !empty($start)){
            $start = strtotime($start);
            $end = strtotime($end.' 23:59:59');
            $where['o.insert_time'] = [ 'between' , [ $start , $end ]];
        }
        if(!empty($start)){
            $start = strtotime($start);
            $where['o.insert_time'] = [ '<=' , $start ];
        }
        if(!empty($end)){
            $end = strtotime($end.' 23:59:59');
            $where['o.insert_time'] = [ '>=' , $end ];
        }
        //获取当前用户所参与或发起的砍价
        $pagesize = config('list_rows')?:15;//每页条数
        $page = intval(input('param.page', 1));//分页

        $BargainOrderModel = new BargainOrderModel();

        $list = $BargainOrderModel->getBeauticianOrderList($where,$page,$pagesize);
        $data['info'] = array(
            'row' => !empty($list) ? count($list) : 0,
            'size' => $pagesize,
            'page' => $page,
        );
        $data['list'] = $list;
        if(!empty($list)){
            $code = 1;
            $msg = '请求成功';
        }else{
            $code = 0;
            $msg = '暂无数据';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    /**
     * Commit: 绑好有详情页 好友帮忙砍价之后奖励产品只能购买一次
     * Function: batgain_help_info
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-17 15:38:15
     * @Return \think\response\Json
     */
    public function batgain_help_info(){
        $storeid = intval( input('param.storeid', 0) );//好友门店
        $uid = intval( input('param.uid', 0) );//好友id
        $order_id = intval( input('param.order_id', 0) );//发起人订单id
        $_count = intval( input('param.count', 0) );//发起人订单id
        //$this->transfer();
        $BargainOrderModel = new BargainOrderModel();
        $BargainRecordModel = new BargainRecordModel();
        //根据订单号获取信息
        $map['o.id'] = $order_id;
        $info = $BargainOrderModel->promoteUidOrderInfo($map);
        if(empty($info)){
            return parent::returnMsg(0,'','订单已删除或不存在');
        }
        $goods_id = $info['goods_id'];//订单商品
        $promote_uid = $info['uid'];//发起订单用户uid
        //查询参与砍价金额之和
        $where['order_id'] = $info['order_id'];
        $where['goods_id'] = $goods_id;
        $where['promote_uid'] = $promote_uid;
        $where['status'] = 1;
        $sum = $BargainRecordModel->orderSum($where);
        $info['reba'] = $sum ? round(($sum/$info['bargain_price']) * 100,2) . '%' :0;
        $info['spell_price'] = $sum; //已砍金额
        $info['lack_price'] = $info['bargain_price'] - $sum;//未砍金额

        $info['expire_time'] = $this->config['duration'] * 3600 + $info['insert_time'];
        $info['spell_time'] = time() > $info['expire_time'] ?0: ($info['expire_time']- time()) ;
        $info['spell_format'] = $info['spell_time'] ? $this->getTime($info['spell_time']) : '';
        $info['duration'] = $this->config['duration'];
        if($info['expire_time'] < time()){
            $info['flag'] = 0;//过期
        }else{
            $info['flag'] = 1;
        }
        $data['info'] = $info;

        //查询奖励产品信息
        /*$goods = Db::name('goods')
            ->field('id,name,price,unit,intro,image,activity_price,stock')
            ->where('id','=',55)
            ->find();
        $data['goods'][] = $goods;*/
        $r['gb.pid'] = $goods_id;
        $r['gb.storeid'] = $storeid;
        $GoodsBargainModel = new GoodsBargainModel();
        $data['goods'] = $GoodsBargainModel->getRewardGoodsList($r);;
        //发起人信息
        $promoter = Db::table('ims_bj_shopn_member')
            ->alias('member')
            ->join(['pt_wx_user'=>'user'],'member.mobile=user.mobile','left')
            ->field('member.id as uid,member.realname,member.mobile,user.nickname,user.avatar')
            ->where('member.id','=',$promote_uid)
            ->find();
        $ccccc = self::$promoter_content;
        shuffle($ccccc);
        $promoter['content'] = $ccccc['0'];
        $data['promoter'] = $promoter;

        $bargain_number = $_count ?: $info['bargain_number'];
        //判断当前用户和订单用户是否是同一人
        if($promote_uid == $uid){
            $data['is_bargain'] = false;
            $data['is_buy'] = false;//禁止购买
            $data['is_share'] = false;//禁止分享
            $data['is_promoter'] = true;//是否是从本身的分享链接进入
        }else{
            //查询用户是否购买过当前奖励产品
            //1.判断当前用户所属门店是否开启活动
            $is_bargain = Db::table('ims_bwk_branch')->where('id',$storeid)->value('is_bargain');

            //查询是否已满
            $abb['order_id'] = $order_id;
            $abb['goods_id'] = $goods_id;
            $abb['promote_uid'] = $promote_uid;
            $abb['status'] = 1;
            $aabb = Db::name('bargain_record')->where($abb)->count();

            if($aabb == $bargain_number){
                $data['is_bargain'] = false;//是否能砍价
                $is_bargain1 = true;
            }else{
                //查询当前用户是否已经帮忙砍价
                $getUIDHasPartakeBargain['order_id'] = $order_id;
                $getUIDHasPartakeBargain['goods_id'] = $goods_id;
                $getUIDHasPartakeBargain['promote_uid'] = $promote_uid;
                $getUIDHasPartakeBargain['uid'] = $uid;
                $is_bargain1 = $BargainRecordModel->getUIDHasPartakeBargain($getUIDHasPartakeBargain);//是否能砍
                $data['is_bargain'] = $is_bargain1;//是否能砍价
            }

            if(empty($is_bargain)){
                $data['is_buy'] = false;//禁止购买
                $data['is_share'] = false;//禁止分享
            }else{
                if($is_bargain1){//能砍价
                    $data['is_buy'] = false;//禁止购买
                    $data['is_share'] = false;//禁止分享
                }else{//不能砍价 是否已经购买过
                    $a['uid'] = $uid;
                    $a['storeid'] = $info['storeid'];
                    $a['pay_status'] = 1;
                    //查询该活动商品下的奖励产品
                    $reward_id = Db::name('goods_bargain')
                        ->where('pid','=',$info['goods_id'])
                        ->where('storeid','=',$info['storeid'])
                        ->value('goods_id');
                    $a['goods_id'] = $reward_id ?: 92;//奖励产品id  目前就一个固定
                    $res = $BargainOrderModel->getUIDRewardGoodsOrder($a);//是否已购买过
                    if(empty($res)){
                        $data['is_buy'] = true;//购买
                    }else{
                        $data['is_buy'] = false;//禁止购买
                    }
                    $data['is_share'] = true;//分享
                }
            }
            $data['is_promoter'] = false;//是否是从本身的分享链接进入
        }

        $data['count'] = $BargainRecordModel->recordCount($where);//当前活动参与人数量
        $data['number'] = $bargain_number;//商品配置参与人数量
        $data['config_number'] = $this->config['number'];//默认配置参与人数量
        $data['spell_price'] = $info['spell_price'];//已砍金额
        $data['lack_price'] = $info['lack_price'];//未砍金额
        //判断是否是个性化门店
        $res = $this->checkStore($storeid);


        if(!empty($res) && !empty($res['bargain_plan']) && strpos($res['bargain_plan'],',2')!==false){
            $data['special'] = 2;//个性化
            if(empty($info['sid'])){
                $data['special'] = 1;
            }
        }else if(!empty($res) && !empty($res['bargain_plan']) && strpos($res['bargain_plan'],'1')!==false){
            $data['special'] = 1;//个性化
        }else{
            $data['special'] = 0;
        }

        $storePlan = $this->checkStorePlan($storeid);//获取门店参与的方案
        if(empty($info['sid'])){
            $storePlan['planA'] = false;
            $storePlan['planB'] = false;
        }
        $data['storePlan'] = $storePlan;
        return parent::returnMsg(1,$data,'数据请求成功');
    }
    /**
     * Commit: 添加参与人砍价信息
     * Function: bargain_add
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-16 14:06:28
     * @Return \think\response\Json
     */
    public function bargain_add()
    {
        $uid = intval( input('param.uid', 0) );//用户id
        $order_id = intval( input('param.order_id', 0) );//订单id
        $storeid = intval( input('param.storeid', 0) );//好友门店
        if(empty($order_id) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        if(empty($storeid)){
            $storeid = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('storeid');
        }
        //$this->transfer();
        $BargainRecordModel = new BargainRecordModel();
        $BargainOrderModel = new BargainOrderModel();
        //查询订单是否超时
        /*$field = "id as order_id,uid,fid,storeid,goods_id,order_sn,order_price,pay_price,insert_time,(order_price - pay_price) bargain_price,num";
        $order = Db::name('bargain_order')
            ->field($field)
            ->where('id', '=', $order_id)
            ->where('insert_time','>=',time() - 86400)
            ->find();*/
        $map['o.id'] = $order_id;
        $map['o.insert_time'] = ['>=',time() - $this->config['duration'] * 3600];
        $order = $BargainOrderModel->promoteUidOrderInfo($map);
        if(empty($order)){
            return parent::returnMsg(0,'','本次分享活动已超时失效');
        }
        $order['name'] = Db::name('goods')->where('id',$order['goods_id'])->value('name');
        $promote_uid = $order['uid'];//发起人id
        $goods_id = $order['goods_id'];//商品id


        //查询是否还有剩余金额并随机获取一个预生成记录
        $list = Db::name('bargain_record')
            ->where('status','=',0)
            ->where('order_id','=',$order_id)
            ->where('promote_uid','=',$promote_uid)
            ->where('goods_id','=',$goods_id)
            ->order('id asc')
            ->find();

        $id = $list['id'];
        $content = $list['content'];
        if(empty($list)){
            $data['is_bargain'] = false;
            return parent::returnMsg(0,$data,'本次活动已圆满结束');
        }
        try {
            //组装数据
            $data['order_id'] = $order_id;
            $data['uid'] = $uid;
            $data['promote_uid'] = $promote_uid;
            $data['goods_id'] = $goods_id;
            $data['nickname'] = trim(input('param.nickname',''));
            $data['avatar'] = trim(input('param.avatar',''));
            $data['status'] = 1;
            $data['partake_time'] = time();


            //查询是否已砍

            $abc = Db::name('bargain_record')
                ->where('uid','=',$uid)
                ->where('order_id','=',$order_id)
                ->where('promote_uid','=',$promote_uid)
                ->where('goods_id','=',$goods_id)
                ->find();
            if(!empty($abc)){
                $is_bargain1 = false;
                $id = $abc['id'];
                $content = $abc['content'];
            }else{
                $getUIDHasPartakeBargain['order_id'] = $order_id;
                $getUIDHasPartakeBargain['goods_id'] = $goods_id;
                $getUIDHasPartakeBargain['promote_uid'] = $promote_uid;
                $getUIDHasPartakeBargain['uid'] = $uid;
                $where1['id'] = $id;
                $res = Db::name('bargain_record')->where($where1)->update($data);//修改砍价数据

                $is_bargain1 = !empty($res) ? false :true; //$BargainRecordModel->getUIDHasPartakeBargain
                //($getUIDHasPartakeBargain); //判断是否能砍
            }

            $datas['is_bargain'] = $is_bargain1;


            //获取砍价信息
            $BargainOrderModel = new BargainOrderModel();
            $BargainRecordModel = new BargainRecordModel();
            $where['order_id'] = $order_id;
            $where['goods_id'] = $goods_id;
            $where['promote_uid'] = $promote_uid;//发起人uid
            $where['status'] = 1;
            $sum = $BargainRecordModel->orderSum($where);
            $order['bargain_price'] = $order['order_price'] - $order['pay_price']; //总共需砍价金额
            $order['reba'] = $sum ? round(($sum/($order['bargain_price'])) * 100,2) . '%' :0;
            $order['spell_price'] = $sum; //已砍金额
            $order['lack_price'] = $order['order_price'] - $order['pay_price'] - $sum;//未砍金额

            $order['expire_time'] = $this->config['duration'] * 3600 + $order['insert_time'];
            $order['spell_time'] = time() > $order['expire_time'] ?0: ($order['expire_time']- time()) ;
            $order['spell_format'] = $order['spell_time'] ? $this->getTime($order['spell_time']) : '';
            $order['duration'] = $this->config['duration'];
            if($order['expire_time'] < time()){
                $order['flag'] = 0;//过期
            }else{
                $order['flag'] = 1;
            }
            $order['content'] = $content;
            $datas['info'] = $order;

            //查询用户是否购买过当前奖励产品
            //1.判断当前用户所属门店是否开启活动
            $is_bargain = Db::table('ims_bwk_branch')
                ->where('id',$storeid)
                ->value('is_bargain');
            if(empty($is_bargain)){
                $datas['is_buy'] = false;//禁止购买
                $datas['is_share'] = false;//禁止购买
            }else{
                $a['id'] = $order_id;
                $a['storeid'] = $order['storeid'];
                $a['pay_status'] = 1;
                //查询该活动商品下的奖励产品
                $reward_id = Db::name('goods_bargain')
                    ->where('pid','=',$order['goods_id'])
                    ->where('storeid','=',$order['storeid'])
                    ->value('goods_id');
                $a['goods_id'] = $reward_id ?: 55;//奖励产品id  目前就一个固定

                $res = $BargainOrderModel->getUIDRewardGoodsOrder($a);//是否已购买过
                if($is_bargain1){
                    $datas['is_buy'] = false;//禁止购买
                    $datas['is_share'] = false;//禁止分享
                }else{
                    if(empty($res)){
                        $datas['is_buy'] = true;//购买
                    }else{//买过
                        $datas['is_buy'] = false;//禁止购买
                    }
                    $datas['is_share'] = true;//分享
                }
            }
            return parent::returnMsg(1,$datas,'拼人品帮忙拼成功');
        }catch (\Exception $e){
            return parent::returnMsg(0,$e->getMessage(),'拼人品帮忙拼失败');
        }
    }
    /**
     * Commit: 添加分享记录
     * Function: bargain_share
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-22 10:38:18
     * @Return \think\response\Json
     */
    public function bargain_share(){
        $order_id = intval( input('param.order_id', 0) );//用户id
        $uid = intval( input('param.uid', 0) );//用户id
        $mobile = trim( input('param.mobile', '') );//用户手机号

        $storeid = intval( input('param.storeid', 0) );//门店id
        $fid = intval( input('param.fid', 0) );//美容师id
        $goods_id = intval( input('param.goods_id', 0) );//美容师id
        //查询订单信息
        $order = Db::name('bargain_order')
            ->where('id','=',$order_id)
            ->field('uid,fid,goods_id,storeid')
            ->find();

        $map['id'] = $uid;
        $mobile = $mobile ?: Db::table('ims_bj_shopn_member')->where($map)->value('mobile');
        $data = array(
            'storeid' => $storeid?:$order['storeid'],
            'order_id' => $order_id,
            'uid' => $uid,
            'fid' => $fid?:$order['fid'],
            'goods_id' => $goods_id?:$order['goods_id'],
            'mobile' => $mobile,
            'insert_time' => time(),
        );
        Db::name('bargain_share')->insert($data);
        return parent::returnMsg(1,'','分享添加成功');
    }
    //微信预支付
    public function wxPay(){
        $wxpay_config = config('wx_pay');
        $appid = $wxpay_config['appid'];
        $mch_id = $wxpay_config['mch_id'];
        $key = $wxpay_config['api_key'];
        //获取前台参数
        $token = input('param.token');
        $buyUser = Db::name('wx_user')->where('token', $token)->find();
        $openid = $buyUser['open_id'];//用户openID
        $body = input('param.body');
        $user_id = input('param.user_id');//用户id
        $out_trade_no = $mch_id. time().$user_id;
        $attach = input('param.attach','bargain');
        $total = input('param.total_fee');
        $total_fee = floatval($total*100);//价格转化为分x100
        $order_sn = input('param.order_sn');//订单号
        $buyType = input('param.buy_type',0);//0是正常购买 1是活动支付 砍价支付
        $mobile = $buyUser['mobile'];//用户手机
        try {
            if($buyType == 1){
                //查询当前订单是否超时
                $order_info = Db::name('bargain_order')
                    ->field('insert_time,pay_status,pay_time')
                    ->where('order_sn', $order_sn)
                    ->find();
                $order_begin_time = $order_info['insert_time'];//订单添加时间
                if($order_info['pay_time'] && $order_info['pay_status'] == 1){
                    return parent::returnMsg(0,'','订单已支付');
                }
                if(empty($order_begin_time)){
                    return parent::returnMsg(0,'','订单不存在或已失效');
                }
                $pay_end_time = time() + 86400;
                if($pay_end_time <= time()){
                    return parent::returnMsg(0,'','订单已超时，下单时间：'.date('Y-m-d H:i:s',$order_begin_time).'，有效时长：'
                        .$this->config['duration'].'h');
                }
                $return['order_sn'] = $order_sn;
            }else{
                $pay_end_time = time()+7200;
            }
            $weixinpay = new WeixinPay(
                $appid,
                $openid,
                $mch_id,
                $key,
                $out_trade_no,
                $body,
                $total_fee,
                $attach,
                date('YmdHis', $order_begin_time),
                date('YmdHis', $pay_end_time)
            );
            $return = $weixinpay->pay();
            //记录欲支付请求
            logs(date('Y-m-d H:i:s')."：".json_encode($return),'prepay');
            $prepay_id = substr($return['package'],10);
            // 记录支付日志
            $data = array(
                'user_id' => $user_id,
                'order_sn' => $order_sn,
                'mobile' => $mobile,
                'out_trade_no' => $out_trade_no,
                'status' => 0,
                'attach' => $attach,
                'pay_amount' => $total,
                'prepay_id' => $prepay_id,
                'log_time' => date('Y-m-d H:i:s')
            );
            Db::name('pay_log')->insert($data);

            $code = 1;
            $data = $return;
            $msg = '支付参数获取成功';
        }catch (\Exception $e){
            $code = 0;
            $data = '';
            $msg = '支付参数获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }






















    /**
     * Commit: 生成金额数组
     * Function: make_price
     * @param $number 人数
     * @param $total 总金额
     * @param $first 第一次砍价比例 最小
     * @param $other 其他砍价比例 最小
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 19:25:00
     * @return array
     */
    public static function make_price($number,$total,$first,$other){
        $model = new algor_bargain();
        $res = $model->only_first_divide($number,$total,$first,$other);
        return $res;
    }

    //设置时间秒转日期格式
    function getTime($time){
        $str = '';
        $f = array(
            '31536000' => '年',
            '2592000' => '月',
            '604800' => '周',
            '86400' => '天',
            '3600' => '时',
            '60' => '分',
            '1' => '秒'
        );
        foreach ($f as $k => $v) {
            if (0 != $c = floor($time / (int)$k)) {
                $str .= $c . $v ;
            }
            $time = $time % $k;
        }
        return $str;
    }


    //批量设置砍价产品
    public function setBargainGoodsList() {
        //获取开启砍价的门店结合
        $storeList = Db::table('ims_bwk_branch')
            ->field('id,title,is_bargain,bargain_plan')
            ->where('is_bargain','=',1)
            ->select();

        //获取砍价商品
        /* $bargainGoodsList = Db::name('goods')
             ->field('id,name,is_bargain')
             ->where('goods_cate','=',7)
             ->where('is_bargain','=',1)
             ->select();*/

        $goodsList = [];
        $i = 0;
        $reward = [ 92 ];
        $goods_id =  169 ;
        echo count($storeList);
        foreach ($storeList as $k=>$val){
            $storeid = $val['id'];//门店id
            $time = time();
            //添加活动产品
            if($storeid == 2 || strpos($val['bargain_plan'],',2') === false){
                $data = [];
                $data['storeid'] = $storeid;
                $data['pid'] = 0;
                $data['goods_id'] = $goods_id;
                $data['create_time'] = $time;
                $goodsList[$i] = $data;
                $i++;


                //奖励产品
                $goodsList[$i]['storeid'] = $storeid;
                $goodsList[$i]['pid'] = $goods_id;
                $goodsList[$i]['goods_id'] = 92;
                $goodsList[$i]['create_time'] = $time;
                $i++;
            }
        }
        Db::name('goods_bargain')->insertAll($goodsList);
        var_dump($goodsList);
        exit;
        foreach ($storeList as $key=>$val){
            $storeid = $val['id'];//门店id
            //if($storeid == 2) continue;
            $time = time();
            foreach ($bargainGoodsList as $kk=>$vv){
                $goods_id = $vv['id'];//商品id
                //活动产品
                $data = [];
                $data['storeid'] = $storeid;
                $data['pid'] = 0;
                $data['goods_id'] = $goods_id;
                $data['create_time'] = $time;
                // Db::name('goods_bargain')->insert($data);
                $goodsList[$i] = $data;
                $i++;

                //goods_id=92 -> 55 小颜术
                //奖励产品
                foreach ($reward as $kkk=>$vvv){
                    $data1 = [];
                    $data1['storeid'] = $storeid;
                    $data1['pid'] = $goods_id;
                    $data1['goods_id'] = $vvv;
                    $data1['create_time'] = $time;
                    //Db::name('goods_bargain')->insert($data1);
                    $goodsList[$i]['storeid'] = $storeid;
                    $goodsList[$i]['pid'] = $goods_id;
                    $goodsList[$i]['goods_id'] = $vvv;
                    $goodsList[$i]['create_time'] = $time;
                    $i++;
                }
            }
        }
        //Db::name('goods_bargain')->insertAll($goodsList);
        echo '<pre>';
        print_r($goodsList);exit;
    }


}