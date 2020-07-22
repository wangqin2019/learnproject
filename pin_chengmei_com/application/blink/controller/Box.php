<?php
/**
 * Created by PhpStorm.
 * User: houdj
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\blink\controller;

use app\blink\model\MemberModel;
use app\blink\model\PintuanModel;
use app\blink\model\GoodsModel;
use app\blink\model\BlinkOrderBoxModel;
use app\blink\model\BlinkBoxCouponUserModel;
use think\Cache;
use think\Db;
use think\Exception;
use weixin\BlinkPay;
use weixin\WeixinPay;
use algor\Particle;

/**
 * swagger: 盲盒鼠卡
 */
class Box extends Base
{
    public static $missshop_2_expire = 100;//毫秒
    //配置
    public $config = [];
    //鼠卡
    public $rats = [];
    public $sign = [
        '666-666',
        '888-888',
        '000-000',
        '998-998',
    ];

    public function _initialize() {
        parent::_initialize();
        $token = input('param.token','');
        $this->checkConfig();
        $blink_box_card_number = $this->getCacheString('blink_box_card_number');
        //获取商品
        if(empty($blink_box_card_number) ){
            $this->setConfig();
            $this->setGoods();//设置商品缓存
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
    //检测配置信息
    public function checkConfig(){
        $this->config = Db::name('blink_box_config')->where('id', 1)->find();

        if(empty($this->config) || empty($this->config['status'])){
            echo json_encode(array('code'=>0,'data'=>'','msg'=>'活动未开启'));
            exit;
        }
        if($this->config['start_time'] > time()){
            echo json_encode(array(
                'code' => 0,
                'data' => '',
                'msg'  => '活动未开始，开始时间：'.date('Y-m-d H:i:s',$this->config['start_time'])
            ));
            exit;
        }
        /*if($this->config['end_time'] < time()){
            echo json_encode(array(
                'code' => 0,
                'data' => '',
                'msg'  => '活动已结束，结束时间：'.date('Y-m-d H:i:s',$this->config['end_time'])
            ));
            exit;
        }*/
    }
    public function setConfig(){
        //检测活动配置
        if(empty($this->config)) {
            $this->config = Db::name('blink_box_config')->where('id', 1)->find();
            //卡片剩余数量
            $this->setCacheString('blink_box_card_number',intval($this->config['number']));
            if($this->config['number'] <= 0){
                echo json_encode(array('code'=>0,'data'=>'','msg'=>'活动已结束！'));
                exit;
            }
            //设置盒子库存
            $this->setCacheString('blink_box_number',intval($this->config['box_number']));
            //设置盒子分享人数
            $this->setCacheString('blink_box_share_number',intval($this->config['share_number']));
        }
        //检测鼠卡配置信息
        if(empty($this->rats)){
            //获取所有鼠卡信息
            $ppp['cid'] = 1;
            $ppp['id'] = ['lt',6];
            $rats = Db::name('blink_box_card_image')->where($ppp)->select();
            foreach ($rats as $k=>$val){
                //设置每一种鼠卡数量
                if($val['type'] == 1){//合成鼠卡
                    $this->setCacheString('blink_compose_rats_'.$val['id'],intval($val['number']));
                }else{//普通鼠卡
                    $this->setCacheString('blink_default_rats_'.$val['id'],intval($val['number']));
                }
            }
            $this->rats = $rats;
        }
        //设置内部员工
        $staff = Db::name('blink_staff')->field('name,mobile')->select();
        if(!empty($staff)){
            foreach ($staff as $k=>$val){
                $this->setCacheString('staff_'.$val['mobile'],trim($val['mobile']));
            }
        }
    }
    //设置盲盒中产品信息
    public function setGoods(){
        $param['bg.type'] = 0;
        $param['g.stock'] = ['egt',0];
        $blink_box_goods = Db::name('blink_box_goods')
            ->alias('bg')
            ->where($param)
            ->join(['pt_goods'=>'g'],'bg.goods_id=g.id','left')
            ->field('bg.goods_id,g.id,bg.type,g.name,g.stock')
            ->select();
        if(!empty($blink_box_goods)){
            //商品数据
            $this->setCacheString('blink_box_goods',serialize($blink_box_goods));
            foreach ($blink_box_goods as $k=>$v){
                //设置每种产品的库存
                $this->setCacheString('blink_box_goods_stock_'.$v['goods_id'],intval($v['stock']));
            }
            //产品个数
            $this->setCacheString('blink_box_goods_number',9);
        }
    }
    /**
     * Commit: 判断门店是否开通活动
     * Function: checkStore
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-22 14:59:44
     * @Return bool|\think\response\Json
     */
    public function checkStore($storeid = 0){
        $storeid = intval( input('param.storeid', 0) ) ?: $storeid;//门店
        $re = Db::table('ims_bwk_branch')
            ->where('id',$storeid)
            ->value('is_blink');
        if(empty($re)){
            echo json_encode(array('code'=>0,'data'=>'','msg'=>'当前门店未开通活动'));
            exit;
        }
        return true;
    }

    /**
     * Commit: 获取商品列表中的一个
     * Function: getGoods
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-25 08:58:34
     * @Return int|string
     */
    public function getGoods($goods_list = []){
        if(empty($goods_list)){
            /*$goods_list = $this->getCacheString('blink_box_goods');
            $goods_list = unserialize($goods_list);*/
            $param['goods_cate'] = 11;
            $param['deputy_cate'] = 1;
            $param['stock'] = ['gt',0];//库存大于0
            $goods_list = GoodsModel::where($param)
                ->column('id,name,image,xc_images,stock,activity_price,price');
        }

        $arr = [];
        foreach ($goods_list as $key => $val) {
            $arr[$val['id']] = $val['stock'];
        }
        $goods_id = getRand($arr);
        return $goods_id;
    }
    /**
     * Commit: 随机获取一张鼠卡
     * Function: getRats
     * @Param array $rats
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-02 10:38:53
     * @Return int|string
     */
    public function getRats($rats = []){
        if(empty($rats)){
            $rats = Db::name('blink_box_card_image')
                ->where('type','=',0)
                ->where('cid','=',1)
                ->where('number','gt',0)
                ->column('id,number,name','id');
        }
        $arr = [];
        foreach ($rats as $key => $val) {
            $arr[$val['id']] = $val['number'];
        }
        return getRand($arr);
    }
    //是否同意协议
    public function setagree(){
        $mobile = trim( input('param.mobile', 0) );
        $is_agree = trim( input('param.is_agree', 0) );
        if(empty($mobile)){
            return $this->returnMsg(0,'','参数缺失');
        }
        try {
            $res = Db::name('blink_wx_user')
                ->where('mobile',$mobile)
                ->find();
            if(empty($res)){
                return $this->returnMsg(0,'','当前手机号的用户不存在');
            }
            Db::name('blink_wx_user')
                ->where('mobile',$mobile)
                ->update([
                    'is_agree' => 1
                ]);
            return $this->returnMsg(1,'','当前手机号的用户已同意规则协议');
        }catch (Exception $e){
            return $this->returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }
    //首页
    public function index(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $uid = intval( input('param.uid', 0) );//门店
        if(empty($storeid)||empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        //检测门店是否开启活动
        $this->checkStore();

        $param['bg.type'] = 0;
        $param['g.goods_cate'] = 11;
        $param['g.deputy_cate'] = 1;
        $blink_box_goods = Db::name('blink_box_goods')
            ->alias('bg')
            ->where($param)
            ->join(['pt_goods'=>'g'],'bg.goods_id=g.id','left')
            ->field('bg.goods_id,g.id,bg.type,g.name,g.stock,g.image,g.xc_images,g.images,g.activity_price,g.price')
            ->select();
        // 随机选取9个产品
        if(!empty($blink_box_goods)){
            foreach ($blink_box_goods as $k=>$val){
                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $image = $images['0'];
                }
                $blink_box_goods[$k]['price_image'] = $image;
            }
        }

        $count = count($blink_box_goods);
        if($count < 9){
            for($i=0;$i<9-$count;$i++){
                $goods_id = $this->getGoods($blink_box_goods);
                $param['g.id'] = $goods_id;
                $blink_box_goods[] = Db::name('blink_box_goods')
                    ->alias('bg')
                    ->where($param)
                    ->join(['pt_goods'=>'g'],'bg.goods_id=g.id','left')
                    ->field('bg.goods_id,g.id,bg.type,g.name,g.stock,g.image,g.xc_images,g.images,g.activity_price,g.price')
                    ->find();
            }
        }
        $data['list'] = $blink_box_goods;
        $data['info'] = $list = Db::name('goods')
            ->field('id,name,image,price,activity_price')
            ->where('goods_cate',11)
            ->where('deputy_cate',0) //0盲盒产品 1 拆盒产品 2 清洁卡及礼包卡
            ->find();
        //检测用户是否有3个好友购买
        //1.查询清洁卡数量type=1
        $number = Db::name('blink_box_coupon_user')
            ->where('type',1)
            ->where('uid',$uid)
            ->count();
        //2查询当前用户下的子集
        $members = Db::table('ims_bj_shopn_member')
            ->where('pid',$uid)
            ->where('activity_flag',9999)//新客
            ->column('id');
        if(!empty($members)){
            //3检测集合中的购买用户数
            $orders = Db::name('blink_order')
                ->where('uid','in',$members)
                ->count('DISTINCT uid');
            if(!empty($orders)){
                // 几张清洁券
                $aaa = $count ? floor($orders / $this->config['share_number']) : 0;
                if($aaa <= $number){
                    $data['alter'] = 0;
                }else{
                    $bb = $aaa - $number;//剩余未生成的卡券
                    $insert = [];
                    //清洁卡价格
                    $_price = Db::name('goods')->where('id',$this->config['share_goods'])->value('activity_price');
                    for($i=0;$i<$bb;$i++){
                        //用户添加清洁卡卡券
                        $cardno = generate_promotion_code(1,1,'',8)['0'];
                        $insert[] = [
                            'pid' => 0,
                            'uid' => $uid,
                            'ticket_code' => $cardno,
                            'status' => 0,
                            'type' => 1,
                            'source' => 2,
                            'goods_id' => $this->config['share_goods'],
                            'price' => $_price,
                            //'qrcode' => pickUpCode('blinkcoupon_'.$cardno),//核销卡券
                            'qrcode' => '',//使用时生成核销二维码
                            'share_status' => 0,
                            'insert_time' => time(),
                            'update_time' => time(),
                        ];
                    }
                    //用户添加卡券
                    Db::name('blink_box_coupon_user')->insertAll($insert);
                    $data['alter'] = 1;
                }
            }else{
                $data['alter'] = 0;
            }
        }else{
            $data['alter'] = 0;
        }
        //检测新年上上签是否开启
        $data['newyearsign'] = $this->checkNewYear();
        return parent::returnMsg(1,$data,'盲盒查询成功');
    }
    //检测新年上上签是否开启
    public function checkNewYear($storeid = 0){
        $info = Cache::get('new_year_sign');
        if(empty($info)){
            $info = Db::name('blink_box_config')->where('id', 2)->find();
            if(empty($info)){
                return 0;
            }
            Cache::set('new_year_sign',$info,3600);
        }
        if($info['start_time'] <= time() && $info['end_time'] >= time()){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * Commit: 直接购买生成订单
     * Function: direct_buy
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-22 16:27:27
     * @Return \think\response\Json
     */
    public function direct_buy() {
        //判断当前用户所属门店是否参与活动
        $storeid = intval( input('param.storeid', 0) );//门店
        $goods_id = intval( input('param.goods_id', 0) );//商品id
        $fid = intval( input('param.fid', 0) );//美容师id
        $uid = intval( input('param.uid', 0) );//发起人id
        $num = intval( input('param.num', 1) );//购买数量
        $price = trim( input('param.price', '20.2') );//价格
        if(empty($goods_id) || empty($uid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        if(empty($storeid) || empty($fid) || empty($num)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        //检测盒子是否超卖
        $blink_box_number = Db::name('blink_box_config')->where('id', 1)->value('box_number');;
        if($blink_box_number <= 0 ){
            return parent::returnMsg(0,'','活动已结束!!');
        }
        if($blink_box_number < $num){
            return parent::returnMsg(0,'','库存不足!!');
        }
        //查询活动商品信息
        $time = time();
        $list = Db::name('goods')->where('id',$goods_id)->find();
        /*//检测当前用户的门店是否是1792  是  取介绍人所属的美容师
        $member = Db::table('ims_bj_shopn_member')
            ->field('id,mobile,staffid,originfid')
            ->where('id',$uid)
            ->find();
        if($storeid == 1792 || $member['stoteid'] == 1792){
            $fid = $member['originfid'];
        }*/
        //检测用户手机号是否为空
        $mobile = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
        if(empty($mobile)){
            logs(date('Y-m-d H:i:s').' 用户没有手机号 参数'.json_encode(input('param.')),'nomobile');
            return parent::returnMsg(0,'','参数错误！！！');
        }
        //生成订单
        $order_sn = createOrderSn();
        $orders['storeid'] = $storeid;
        $orders['uid'] = $uid;
        $orders['goods_id'] = $goods_id;
        $orders['fid'] = $fid;
        $orders['order_sn'] = $order_sn;
        $orders['num'] = $num;
        $orders['status'] = 1;//进行中
        $orders['pay_status'] = 0;//未支付
        $orders['order_price'] = $list['price'] * $num;
        $orders['pay_price'] = $price * $num;//支付金额
        $orders['insert_time'] = $time;//发起时间
        $orders['close_time'] = $time + 86300;//发起时间
        $orders['pick_code'] = pickUpCode('blinkbox_'.$order_sn);;//订单二维码
        $order_id = Db::name('blink_order')->insert($orders,false,true);
        $market_price = $list['price'];
        $total_fee = $price * $num;//商品优惠价*数量

        //订单添加成功
        if(!empty($order_id)){
            $code = 1;
            $data = config('wx_blink_pay');
            $data['order_id'] = $order_id;
            $data['user_id'] = $uid;
            $data['attach'] = 'blink';
            $data['order_sn'] =  $order_sn;
            $data['num'] =  $num;
            $data['market_price'] = $market_price;//订单金额
            $data['price'] = $price;//待支付金额
            $data['total_fee'] = $total_fee;
            $data['body'] = $list['name'];
            logs(date('Y-m-d H:i:s') . "：2 " . json_encode($data), 'aaa');
            $msg = '订单提交成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数缺失，订单提交失败';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    //微信预支付
    public function wxPay(){
        $wxpay_config = config('wx_blink_pay');
        $appid = $wxpay_config['appid'];
        $mch_id = $wxpay_config['mch_id'];
        $key = $wxpay_config['api_key'];
        //获取前台参数
        $token = input('param.token');
        $buyUser = Db::name('blink_wx_user')->where('token', $token)->find();
        $openid = $buyUser['open_id'];//用户openID
        $body = input('param.body');
        $user_id = input('param.user_id');//用户id
        $out_trade_no = $mch_id. time().$user_id;
        $attach = input('param.attach','blink');
        $total = input('param.total_fee');
        $total_fee = floatval($total*100);//价格转化为分x100
        $order_sn = input('param.order_sn');//订单号
        $mobile = $buyUser['mobile'];//用户手机
        if(empty($mobile)){
            return parent::returnMsg(0,'','参数错误！！！');
        }
        try {
            //查询当前订单是否超时
            $order_info = Db::name('blink_order')
                ->field('insert_time,pay_status,pay_time,close_time')
                ->where('order_sn', $order_sn)
                ->find();
            if(empty($order_info)){
                return parent::returnMsg(0,'','订单已超时');
            }
            $close_time = $order_info['close_time'] ?: $order_info['insert_time'] + 7100;
            if($close_time < time()){
                return parent::returnMsg(0,'','订单已超时');
            }
            $order_begin_time = $order_info['insert_time'];//订单添加时间
            if($order_info['pay_time'] && $order_info['pay_status'] == 1){
                return parent::returnMsg(0,'','订单已支付');
            }
            if(empty($order_begin_time)){
                return parent::returnMsg(0,$order_info,'订单不存在或已失效');
            }
            $pay_end_time = $order_begin_time + 7200;
            if($pay_end_time <= time()){
                return parent::returnMsg(0,'','订单已超时');
            }
            $return['order_sn'] = $order_sn;

            $weixinpay = new BlinkPay(
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

            logs(date('Y-m-d H:i:s')." 与支付 ：".json_encode($return),'prepayBlink');
            //记录欲支付请求
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

            return parent::returnMsg(1,$return,'支付参数获取成功');
        }catch (\Exception $e){
            return parent::returnMsg(0,'','支付参数获取失败'.$e->getMessage());
        }
    }
    public function getCurrentUserInfo($uid = 0){
        return Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->where('m.id',$uid)
            ->field('m.id,m.realname,m.mobile,u.nickname')
            ->find();
    }

    /**
     * Commit: 获取当前用户所得盲盒商品中最少的一个商品ID
     * Function: get_goods_reba_id
     * @Param int $uid
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-06 17:09:59
     * @Return mixed
     */
    public function get_goods_reba_id($uid= 0){
        //获取当前用户已出现的商品
        $param['goods_cate'] = 11;
        $param['deputy_cate'] = 1;
        $param['stock'] = ['gt',0];//库存大于0
        $goods_list = GoodsModel::where($param)
            ->order('stock','desc')
            ->field('id,name,stock')->select();
        if(empty($goods_list)){
            return parent::returnMsg(0,'','单品库存不足');
        }
        $goods = [];
        foreach ($goods_list as $k=>$val){
            $goods[$k]['goods_id'] = $val['id'];
            $goods[$k]['stock'] = $val['stock'];
            $goods[$k]['count'] = Db::name('blink_box_coupon_user')
                ->where('uid',$uid)
                ->where('goods_id',$val['id'])
                ->where('type',0)
                ->count();
        }
        $last_names = array_column($goods,'count');
        array_multisort($last_names,SORT_DESC,$goods);
        //获取最后一个
        $end = end($goods);
        return $end['goods_id'];
    }
    //---------------------------------盒子----------------------------------------------------
    /**
     * Commit:  个人中心 -- 我的盒柜
     * Function: blink
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 18:57:31
     * @Return \think\response\Json
     */
    public function blink(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $user_id = intval( input('param.uid', 0) );//登陆用户
        if(empty($user_id) || empty($storeid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        $status = intval( input('param.status', 0) );//是否拆盒
        $page = intval( input('param.page', 1) );//分页

        //查询盲盒数据
        $param['ca.uid'] = $user_id;
        $param['ca.is_pay'] = 1;//翼支付

        $limit = 10;
        if($status == 0){//未拆盒子记录
            $param['ca.status'] = 0;
            $list = BlinkOrderBoxModel::alias('ca')
                ->join(['pt_goods'=>'g'],'ca.goods_id=g.id','left')
                ->field('ca.*,g.name,g.image as goods_image,g.xc_images as goods_thumb,g.intro')
                ->where($param)
                ->order(['status'=>'asc','ca.create_time'=>'desc'])
                ->page($page,$limit)
                ->select();
        }else{//一拆盲盒 关联 盒中商品及鼠卡
            $param['ca.status'] = 1;
            $list = BlinkOrderBoxModel::alias('ca')
                ->join(['pt_goods'=>'g'],'ca.goods_id=g.id','left') //盲盒关联商品
                ->join(['pt_blink_box_coupon_user'=>'cu'],'cu.blinkno=ca.blinkno and cu.uid=ca.uid','left')//盲盒中的商品
                ->join(['pt_goods'=>'gg'],'cu.goods_id=gg.id','left')

                ->join(['pt_blink_order_box_card'=>'card'],'ca.blinkno=card.blinkno and ca.uid=card.uid','left')
                ->join(['pt_blink_box_card_image'=>'image'],'card.thumb_id=image.id','left')
                ->field('ca.*,cu.thumb_id,g.name,g.image as goods_image,g.intro,g.xc_images as goods_thumb,gg.name goods_name,gg.image,g.xc_images,image.thumb as card_thumb,image.name as card_name')
                ->where($param)
                ->order('ca.create_time','desc')
                ->page($page,$limit)
                ->select();
        }

        $total = Db::name('blink_order_box')->alias('ca')->where($param)->count();
        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'list' => $list
        ];
        if(!empty($list)){
            return parent::returnMsg(1,$data,'盲盒查询成功!');
        }else{
            return parent::returnMsg(1,$data,'暂无盲盒数据');
        }
    }
    //拆盒
    public function take_blink(){
        $blinkno = trim( input('param.blinkno', 0) );//盒子编号
        $order_id = intval( input('param.order_id', 0) );//盒子编号
        $uid = intval( input('param.userid', 0) );//盒子编号
        if(empty($blinkno) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try{
            //拆盒
            $param['blinkno'] = $blinkno;
            //$param['order_id'] = $order_id;
            $param['uid'] = $uid;
            $param['status'] = 0;//未拆
            //检测数据是否存在
            $info = Db::name('blink_order_box')->where($param)->find();
            if(empty($info)){
                return parent::returnMsg(0,'','盲盒不存在');
            }
            if($info['status'] == 1){
                return parent::returnMsg(0,'','该盲盒已拆开');
            }
            //拆盒
            Db::name('blink_order_box')->where($param)->update([
                'status'=>1,
                'close_time' => 0,
                'update_time' => time()
            ]);
            //随机查询一条鼠卡图片ID
            $rat_thumb_id = $this->getRats();
            $cardno = generate_promotion_code($uid,1,'',8)['0'];
            Db::name('blink_order_box_card')->insert([
                'blinkno' => $blinkno,
                'uid' => $info['uid'],
                'cardno' => $cardno,
                //'qrcode' => pickUpCode('blinkcard_'.$cardno),//核销卡片
                'qrcode' => '',//使用时生成核销二维码
                'thumb_id' => $rat_thumb_id,
                'status' => 0,
                'create_time' => time(),
                'update_time' => time(),
                'close_time' => 0
            ]);
            //卡片数量减1
            $this->setDec('blink_box_card_number',1);
            //对应鼠卡减1
            if($this->getCacheString('blink_default_rats_'.$rat_thumb_id)){
                $this->setDec('blink_default_rats_'.$rat_thumb_id,1);
            }
            Db::name('blink_box_config')->where('id',1)->setDec('number',1);

            //获取盲盒中卡数据
            $data['card'] = Db::name('blink_box_card_image')
                ->field('thumb as card_thumb,name as card_name')
                ->where('id',$rat_thumb_id)
                ->where('cid',1)
                ->find();
            $data['card']['cardno'] = $cardno;

            //生成商品记录 以卡券记录形式展示
            $goods_id = $this->get_goods_reba_id($uid);//$this->getGoods();//随机获取商品ID
            $_price = Db::name('goods')->where('id',$goods_id)->value('activity_price');
            $goodsno = generate_promotion_code($goods_id,1,'',8)[0];
            //返回卡券商品ID
            $res = Db::name('blink_box_coupon_user')->insert([
                'blinkno' => $blinkno,
                'uid' => $info['uid'],
                'goods_id' => $goods_id,
                'price' => $_price,
                'par_value' => $_price,
                'ticket_code' => $goodsno,
                //'qrcode' => pickUpCode('blinkcoupon_'.$goodsno),//核销商品
                'qrcode' => '',//使用时生成核销二维码
                'type' => 0,//一般商品
                'source' => 0,//来源 0拆盲盒 1好友赠送 2好友助理 3合成卡片
                'status' => 0,//未赠送
                'share_status' => 0,//未赠送
                'insert_time' => time(),
                'update_time' => time(),
            ],false,true);
            /*if($res){
                Db::name('blink_box_coupon_user')->where('id',$res)->update([
                    'qrcode' => pickUpCode('blinkcoupon_'.$goodsno.'_'.$res),
                    'update_time' => time()
                ]);
            }*/
            //商品库存减1
            if($this->getCacheString('blink_box_goods_stock_'.$goods_id)){
                $this->setDec('blink_box_goods_stock_'.$goods_id,1);
            }
            Db::name('goods')->where('id',$goods_id)->setDec('stock',1);
            //盲盒中商品数据
            $data['goods'] = Db::name('goods')
                ->where('id',$goods_id)
                ->field('name,image,xc_images')
                ->find();

            //盲盒数据
            $data['info'] = $info;
            return parent::returnMsg(1,$data,'盲盒拆开成功');
        }catch (Exception $e){
            return parent::returnMsg(0,'','数据操作失败：'.$e->getMessage());
        }
    }
    //赠送盒子
    public function give_blink(){
        $blinkno = trim( input('param.blinkno', 0) );//盒子编号
        $order_id = intval( input('param.order_id', 0) );//盒子编号
        $uid = intval( input('param.uid', 0) );//盒子编号
        $remark = trim(input('param.remark',''));//好友赠言
        $mobile = trim(input('param.mobile',''));//接受人手机号
        if(empty($blinkno) || empty($uid) || empty($mobile)){
            return parent::returnMsg(0,'','参数缺失');
        }
        logs(date('Y-m-d').' give_blink参数 :'.json_encode(input('param.')),'ttt1');
        try{
            //检测手机号使用者
            $storeInfo = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->field('m.id,bwk.title,bwk.sign')
                ->where('m.mobile',$mobile)
                ->find();
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','您要赠送的用户不存在！');
            }
            if($storeInfo['id'] == $uid){
                return parent::returnMsg(0,'','不能赠送给自己');
            }
            $param['blinkno'] = $blinkno;
            $param['uid'] = $uid;
            $param['is_give'] = 0;//未分享
            $param['status'] = 0;//未使用
            //检测盒子数据是否存在
            $info = Db::name('blink_order_box')->where($param)->find();
            if(empty($info)){
                return parent::returnMsg(0,'','盲盒不存在或已拆开或已赠送');
            }
            if($info['status'] == 1 || $info['is_give'] == 1 ){
                return parent::returnMsg(0,'','该盲盒已拆开或已赠送');
            }
            //生成赠送记录
            $time = time();
            $give_id = Db::name('blink_box_give_record')->insert([
                'give_uid' => $info['uid'],
                'blinkno' => $info['blinkno'],
                'pid' => 0,
                'mobile' => $mobile,
                'give_advice' => $remark,
                'create_time' => $time ,
                'update_time' => $time ,
                'close_time' => $time + 86400,
            ],false,true);
            if(!empty($give_id)){
                //更新盒子记录
                Db::name('blink_order_box')
                    ->where('order_id',$order_id)
                    ->where('uid',$uid)
                    ->where('blinkno',$blinkno)
                    ->where('id',$info['id'])
                    ->update([
                        'is_give' => 2,
                        'give_id' => $give_id,
                        'close_time' => $time + 86300,
                    ]);

                //添加分享日志
                $share = [
                    'uid'     => $uid,//当前用户
                    'receive' => $storeInfo['id'],//接收用户
                    'type'    => 0,//记录类型 0 盲盒 1鼠卡 2卡券
                    'code'    => $blinkno,
                    'desc'    => "{$blinkno} 转赠给{$storeInfo['title']}（{$storeInfo['sign']}）门店手机号为 {$mobile}的用户",
                ];
                $this->setShareLogs($share);
                return parent::returnMsg(1,['give_id'=>$give_id,'param'=>input('param.')],'赠送盒子成功');
            }else{
                return parent::returnMsg(0,'','赠送盒子失败');
            }
        }catch (Exception $e){
            return parent::returnMsg(0,'','数据操作失败：'.$e->getMessage());
        }
    }
    //接收盒子页面
    //give_id=1
    public function accept_blink(){
        $give_num = trim( input('param.give_num', 0) );//盒子编号
        $blink_id = trim( input('param.blink_id', 0) );//盒子id
        $give_userid = intval( input('param.give_userid', 0) );//盒子赠送人
        $uid = intval( input('param.uid', 0) );//当前用户ID
        if(empty($give_num) || empty($give_userid)|| empty($uid)){
            return parent::returnMsg(2,'','参数缺失1');
        }
        logs(date('Y-m-d').' accept_blink参数 :'.json_encode(input('param.')),'ttt1');
        //不能是自己
        if($uid == $give_userid){
            $data['click'] = 0;
            //return parent::returnMsg(2,'','您不能领取自己的盒子!');
        }
        //检测赠送人盒子是否已领取
        $p['blinkno'] = $give_num;
        $p['uid']     = $give_userid;
        if(!empty($blink_id)){
            $p['id']     = $blink_id;
        }
        $boxs = Db::name('blink_order_box')
            ->where($p)
            ->order('create_time','desc')
            ->find();

        if(empty($boxs)){
            return parent::returnMsg(2,input('param.'),'当前行为已失效!');
        }
        //检测赠送记录是否存在
        $info = Db::name('blink_box_give_record')->where('id',$boxs['give_id'])->find();
        if(empty($info)){
            return parent::returnMsg(2,input('param.'),'当前行为已失效!');
        }
        if($info['close_time'] < time()){
            return parent::returnMsg(2,'','当前行为已失效');
        }
        //检测当前盒子是否已拆
        if($boxs['status'] == 1){
            $data['click'] = 0;
            //return parent::returnMsg(2,'','当前盲盒已被拆开!');
        }
        if($boxs['is_give'] == 1 || $boxs['is_give'] == 0){
            $data['click'] = 0;
            //检测是否是接受人进入
            $UUU = Db::table('ims_bj_shopn_member')->where('mobile',$info['mobile'])->value('id');
            if($UUU == $uid){
                return parent::returnMsg(2,'','当前盲盒已被领取或未分享!!');
            }
            //return parent::returnMsg(2,'','当前盲盒已被领取!');
        }
        //赠送人信息
        $member = Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->field('u.nickname,u.avatar,m.storeid,m.staffid,m.mobile,m.realname')
            ->where('m.id',$info['give_uid'])
            ->find();
        //不能赠送给自己
        if($member['mobile'] == $info['mobile']){
            $data['click'] = 0;
            //return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
        }
        //检测当前用户是否是接收人
        $mobile = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
        if($mobile == $info['mobile']){
            $data['click'] = 1;
        }else{
            $data['click'] = 0;
        }
        $member['_mobile'] = $info['mobile'];
        $member['give_advice'] = $info['give_advice'];
        $data['member'] = $member;
        //查询该盲盒关联的商品
        $mapa['bob.blinkno'] = $info['blinkno'];
        $mapa['bob.uid']     = $info['give_uid'];
        if(!empty($blink_id)){
            $mapa['bob.id']     = $blink_id;
        }
        $goods = BlinkOrderBoxModel::alias('bob')
            ->join(['pt_goods'=>'g'],'bob.goods_id=g.id','left')
            ->where($mapa)
            ->field('bob.*,g.name goods_name,g.image goods_image,g.intro')
            ->order('bob.create_time','desc')
            ->find();
        $data['map'] = $mapa;
        $data['info'] = $goods;
        return parent::returnMsg(1,$data,'盲盒数据查询成功');
    }
    //获取赠送的盒子
    public function set_accept_blink(){
        $give_id = intval( input('param.give_id', 0) );//盒子赠送记录
        $blinkno = trim( input('param.blinkno', 0) );//盒子编号
        $uid = intval( input('param.uid', 0) );//当前用户
        $storeid = trim( input('param.storeid', 0) );//当前用户
        $mobile = trim( input('param.mobile', 0) );//当前用户
        if(empty($give_id) || empty($blinkno) || empty($uid) || empty($storeid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        logs('set_accept_blink 参数： '.json_encode(input('param.')),'ttt1');
        try {
            //检测赠送记录是否存在
            $info = Db::name('blink_box_give_record')->where('id',$give_id)->find();
            if(empty($info)){
                return parent::returnMsg(0,'','盲盒赠送记录不存在');
            }
            //检测接收人手机号 检测赠送人和接收人是否是同一人
            if($mobile != $info['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
            }
            //检测盒子赠送人门店
            $members = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$info['give_uid'])
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign')
                ->find();
            if(empty($members)){
                return parent::returnMsg(0,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$uid)
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,u.nickname,bwk.title,bwk.sign')
                ->find();
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            $msg = '';
            //
            if(in_array($current_user['sign'],$this->sign) && in_array($members['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $members['storeid']){
                    return parent::returnMsg(0,'','暂不支持跨门店操作哦！');
                }
            }
            //检测赠送人的盲盒是否被领取
            $blink = Db::name('blink_order_box')
                ->where('blinkno',$blinkno)
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->find();
            logs(json_encode($blink),'ttt1');
            if(empty($blink)){
                return parent::returnMsg(0,'','当前盲盒已被领取！');
            }
            if($blink['is_give'] == 0 || $blink['is_give'] == 1){
                return parent::returnMsg(0,'','当前盲盒已被领取或未分享！');
            }
            //更新盒子记录从属于当前盒子赠送人盒子记录
            Db::name('blink_order_box')
                ->where('blinkno',$blinkno)
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->update([
                    'is_give' => 1,
                    'status' => 0,
                    'update_time' => time(),
                    'close_time' => 0,
                ]);
            //为接受人添加盲盒记录
            Db::name('blink_order_box')->insert([
                'pid'          => $blink['id'],
                'order_id'     => $blink['order_id'],
                'uid'          => $uid,
                'blinkno'      => $blinkno,
                'price'        => $blink['price'],
                'goods_id'     => $blink['goods_id'],
                'status'       => 0,
                'source'       => 1,
                'is_give'      => 0,
                'is_pay'       => 1,
                'parent_owner' => $info['give_uid'],
                'create_time'  => time(),
                'update_time'  => time(),
            ]);
            //给赠送人发送一条短信 $member['mobile'] sendMessage($mobile,['code'=>$code],$sms_id);
            sendMessage($members['mobile'],['nickname'=>$current_user['nickname']],config('blink_sms_id'));

            //添加分享日志
            $store = Db::table('ims_bwk_branch')->where('id',$storeid)->field('title,sign')->find();
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 0,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $blinkno,
                'desc'    => "{$blinkno} 被 {$store['title']}（{$store['sign']}）门店手机号为 {$mobile} 的用户接收".$msg,
            ];
            $this->setShareLogs($share);

            return parent::returnMsg(1,'','好友赠送的盲盒接受成功');
        }catch (Exception $e){
            return parent::returnMsg(0,'','接收盲盒操作失败：'.$e->getMessage());
        }
    }
    //拒绝接受赠送
    public function set_reject_blink(){
        $give_id = intval( input('param.give_id', 0) );//盒子赠送记录
        $storeid = intval( input('param.storeid', 0) );//盒子赠送记录
        $uid = intval( input('param.uid', 0) );//盒子赠送记录
        if(empty($give_id) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            //检测赠送记录是否超市
            $info = Db::name('blink_box_give_record')->where('id',$give_id)->find();
            if(empty($info)){
                return parent::returnMsg(2,'','当前行为已失效!');
            }
            //检测赠送人门店
            $members = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$info['give_uid'])
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign')
                ->find();
            if(empty($members)){
                return parent::returnMsg(2,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$uid)
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,u.nickname,bwk.title,bwk.sign')
                ->find();
            if(empty($current_user)){
                return parent::returnMsg(2,'','当前用户不存在或已删除！');
            }
            $msg = '';
            if(in_array($current_user['sign'],$this->sign) && in_array($members['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $members['storeid']){
                    return parent::returnMsg(2,'','暂不支持跨门店操作哦！');
                }
            }
            //检测当前用户手机号是否匹配  检测是否赠给自己
            $mobile = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
            if($info['mobile'] != $mobile || $info['give_uid'] == $uid){
                return parent::returnMsg(2,[$members,$info],'抱歉，它的主人不是您哦，暂不支持拒绝哦！！');
            }
            //检测赠送人的盲盒是否被领取
            $blink = Db::name('blink_order_box')
                ->where('blinkno',$info['blinkno'])
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->find();
            if(empty($blink)){
                return parent::returnMsg(2,'','当前盲盒已被领取！');
            }
            if($blink['is_give'] == 1 || $blink['is_give'] == 0){
                return parent::returnMsg(2,'','当前盲盒已被领取或未分享！');
            }
            if($blink['status'] == 1){
                return parent::returnMsg(2,'','当前盲盒已被拆开！');
            }
            //更新盒子记录
            $res = Db::name('blink_order_box')
                ->where('blinkno',$info['blinkno'])
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->update([
                    //'give_id' => 0,
                    'close_time' => 0,
                    'is_give' => 0,
                    'update_time' => time(),
                ]);
            if($res){
                //添加分享日志
                $store = Db::table('ims_bwk_branch')->where('id',$storeid)->field('title,sign')->find();
                $share = [
                    'uid'     => $info['give_uid'],//当前用户
                    'receive' => $uid,//接收用户
                    'type'    => 0,//记录类型 0 盲盒 1鼠卡 2卡券
                    'code'    => $info['blinkno'],
                    'desc'    => "{$info['blinkno']} 被 {$store['title']}（{$store['sign']}）门店手机号为 {$info['mobile']} 的用户拒绝 {$msg}，已回退",
                ];
                $this->setShareLogs($share);
                return parent::returnMsg(2,'','盲盒已返回到赠送人手中');
            }else{
                return parent::returnMsg(0,'','操作失败');
            }
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }
    //---------------------------------------鼠卡---------------------------------------------
    /**
     * Commit:  我的卡片
     * Function: card
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 18:57:31
     * @Return \think\response\Json
     */
    public function card(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $user_id = intval( input('param.uid', 0) );//登陆用户
        if(empty($storeid) || empty($user_id)){
            return parent::returnMsg(0,'','参数缺失');
        }
        $this->checkStore($storeid);

        //获取原始卡片
        $card = Db::name('blink_box_card_image')
            ->where('cid',1)
            ->order(['type'=>'desc','id'=>'asc'])
            ->select();
        if(!empty($card)){
            $param['is_give'] = 0;//未赠送
            $param['uid'] = $user_id;
            $param['is_compose'] = 0;//是否合成 0 未合成 1已合成
            foreach ($card as $k=>$val){
                $id = $val['id'];//卡片ID
                $param['thumb_id'] = $id;
                //查询当前用户是否有该卡片
                $count = Db::name('blink_order_box_card')
                    ->where($param)
                    ->count();
                $card[$k]['count'] = $count ?: 0;
                $card[$k]['cardno'] = Db::name('blink_order_box_card')
                    ->where($param)
                    ->orderRaw('rand()')
                    ->value('cardno') ?: '';
            }
        }
        $data['card'] = $card;
        //检测当前用户是否凑够五张鼠卡
        $thumb_ids = Db::name('blink_box_card_image')
            ->where('cid',1)
            ->where('type',0)//卡片类型 0普通鼠卡 1合成鼠卡
            ->column('id');//五张鼠卡
        $is_compose = Db::name('blink_order_box_card')
            ->where('thumb_id','in',$thumb_ids)
            ->where('uid','=',$user_id)
            ->where('is_give','=',0)
            ->where('is_compose','=',0)
            ->count('DISTINCT thumb_id');
        //获取用户卡片总数量
        $thumb_ids[] = Db::name('blink_box_card_image')
            ->where('cid',1)
            ->where('type',1)->value('id');
        $number = Db::name('blink_order_box_card')
            ->where('thumb_id','in',$thumb_ids)
            ->where('uid','=',$user_id)
            ->where('is_give','=',0)
            ->where('is_compose','=',0)
            ->count();
        $data['number'] = $number;
        if($is_compose == 5){//能够合成
            $data['is_compose'] = 1;
        }else{
            $data['is_compose'] = 0;
        }
        return parent::returnMsg(1,$data,'鼠卡获取成功');
    }
    //赠送卡片
    public function give_card(){
        $thumb_id = intval( input('param.id', 0) );//卡片ID
        $uid = intval( input('param.uid', 0) );//当前用户ID
        $remark = trim(input('param.remark',''));//好友赠言
        $mobile = trim(input('param.mobile',''));//接受人手机号
        if(empty($thumb_id) || empty($uid) || empty($mobile)){
            return parent::returnMsg(0,'','参数缺失!');
        }
        try{
            //检测手机号使用者
            $storeInfo = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->field('m.id,bwk.title,bwk.sign')
                ->where('m.mobile',$mobile)
                ->find();
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','您要赠送的用户不存在！');
            }
            if($storeInfo['id'] == $uid){
                return parent::returnMsg(0,'','不能赠送给自己');
            }
            $param['uid'] = $uid;
            $param['cardno'] = $thumb_id;
            $param['is_compose'] = 0;//未合成卡
            $param['is_give'] = 0;//未赠送
            //检测卡片数据是否存在  随机查询一条数据
            $info = Db::name('blink_order_box_card')
                ->where($param)
                ->order('create_time','desc')
                ->find();
            if(empty($info)){
                return parent::returnMsg(0,'','当前鼠卡已合成或已赠送');
            }
            //生成卡片赠送记录
            $time = time();
            $give_id = Db::name('blink_card_give_record')->insert([
                'give_uid' => $info['uid'],
                'blinkno' => $info['blinkno'],
                'cardno' => $info['cardno'],
                'pid' => 0,
                'mobile' => $mobile,
                'give_advice' => $remark,
                'create_time' => $time ,
                'update_time' => $time ,
                'close_time' => $time + 86300,
            ],false,true);
            if(!empty($give_id)){
                //更新盒子中卡片记录
                Db::name('blink_order_box_card')
                    ->where('uid',$uid)
                    ->where('blinkno',$info['blinkno'])
                    ->where('id',$info['id'])
                    ->update([
                        'is_give' => 2,
                        'give_id' => $give_id,
                        'close_time' => $time + 86300,
                    ]);
                //添加分享日志
                $share = [
                    'uid'     => $uid,//当前用户
                    'receive' => $storeInfo['id'],//接收用户
                    'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                    'code'    => $thumb_id,
                    'desc'    => "{$thumb_id} 转赠给{$storeInfo['title']}（{$storeInfo['sign']}）门店手机号为 {$mobile}的用户",
                ];
                $this->setShareLogs($share);
                return parent::returnMsg(1,['give_id'=>$give_id],'赠送盒子成功');
            }else{
                return parent::returnMsg(0,'','赠送盒子失败');
            }
        }catch (Exception $e){
            return parent::returnMsg(0,'','数据操作失败：'.$e->getMessage());
        }
    }
    //接收卡片页面
    //give_id=1
    public function accept_card(){
        $give_num = trim( input('param.give_num', 0) );//鼠卡编号
        $give_userid = intval( input('param.give_userid', 0) );//盒子赠送人
        $uid = intval( input('param.uid', 0) );//当前用户ID
        logs(date('Y-m-d H:i:s').' accept_card 参数 :'.json_encode(input('param.')),'accept');
        if(empty($give_num) || empty($give_userid) || empty($uid)){
            return parent::returnMsg(2,'','参数缺失');
        }
        //是否是同一人
        if($uid == $give_userid){
            $data['click'] = 0;
            //return parent::returnMsg(2,'','您不能领取自己的盒子!');
        }
        //检测卡片赠送人当前卡片是否存在
        $param['cardno'] = $give_num;
        $param['uid'] = $give_userid;
        $param['is_compose'] = 0;//未合成卡
        $param['is_give'] = 2;//未赠送
        //检测卡片数据是否存在
        $card = Db::name('blink_order_box_card')
            ->where($param)
            ->order('create_time','desc')
            ->find();
        logs(date('Y-m-d H:i:s').' 检测鼠卡赠送人的卡片 :'.json_encode($card),'accept');
        if(empty($card)){
            return parent::returnMsg(2,'','当前行为已失效了!');
        }
        if($card['is_compose'] == 1){
            $data['click'] = 0;
        }
        //检测卡片赠送记录是否存在
        $info = Db::name('blink_card_give_record')->where('id',$card['give_id'])->find();
        logs(date('Y-m-d H:i:s').' 鼠卡赠送人的分享记录 :'.json_encode($info),'accept');
        if(empty($info)){
            return parent::returnMsg(2,'','当前行为已失效!!');
        }
        if($info['close_time'] < time()){
            $data['click'] = 0;
            //return parent::returnMsg(2,'','当前行为已失效');
        }
        if($card['is_give'] == 0){
            $data['click'] = 0;
            //检测是否是接受人进入
            $UUU = Db::table('ims_bj_shopn_member')->where('mobile',$info['mobile'])->value('id');
            if($UUU == $uid){
                return parent::returnMsg(2,'','当前鼠卡未发起赠送或已回退给赠送人');
            }
        }
        if($card['is_give'] == 1){
            //检测是否是接受人进入
            $UUU = Db::table('ims_bj_shopn_member')->where('mobile',$info['mobile'])->value('id');
            if($UUU == $uid){
                return parent::returnMsg(2,'','当前鼠卡已被领取!');
            }
            return parent::returnMsg(2,'','当前鼠卡已被领取!');
        }
        //赠送人信息
        $member = Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->field('u.nickname,u.avatar,m.storeid,m.staffid,m.mobile,m.realname')
            ->where('m.id',$info['give_uid'])
            ->find();

        $member['give_advice'] = $info['give_advice'];
        $member['_mobile'] = $info['mobile'];
        //不能赠送给自己
        if($member['mobile'] == $info['mobile']){
            $data['click'] = 0;
            //return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
        }
        //检测当前用户是否是接收人
        $mobile = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
        if($mobile == $info['mobile']){
            $data['click'] = 1;
        }else{
            $data['click'] = 0;
        }

        //判断当前卡片是否领取
        $cardInfo = Db::name('blink_box_card_image')->where('cid',1)->where('id',$card['thumb_id'])->find();
        $card['card_name'] = $cardInfo['name'];
        $card['card_thumb'] = $cardInfo['thumb'];
        $card['intro'] = $cardInfo['intro'];
        $data['member'] = $member;
        $data['info'] = $card;
        $data['card'] = $info;
        return parent::returnMsg(1,$data,'鼠卡数据查询成功');
    }
    //获取卡片
    public function set_accept_card(){
        $give_id = intval( input('param.give_id', 0) );//卡片赠送记录
        $blinkno = trim( input('param.blinkno', 0) );//卡片编号
        $cardno = trim( input('param.cardno', 0) );//卡片编号
        $uid = intval( input('param.uid', 0) );//当前用户
        $storeid = intval( input('param.storeid', 0) );//当前用户门店
        $mobile =  input('param.mobile', '') ;//当前接收人手机号
        if(empty($give_id) || empty($cardno) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            //检测卡片赠送记录是否存在
            $info = Db::name('blink_card_give_record')->where('id',$give_id)->find();
            if(empty($info)){
                return parent::returnMsg(0,'','鼠卡赠送记录不存在');
            }
            ////检测接收人手机号 检测赠送人和接收人是否是同一人
            if($mobile != $info['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
            }
            //检测鼠卡赠送人门店
            $storeInfo = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$info['give_uid'])
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign')
                ->find();
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$uid)
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,u.nickname,bwk.title,bwk.sign')
                ->find();
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            $msg = '';

            if(in_array($current_user['sign'],$this->sign) && in_array($storeInfo['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $storeInfo['storeid']){
                    return parent::returnMsg(0,'','暂不支持跨门店操作哦！');
                }
            }
            //不能赠送给自己
            if($info['mobile'] == $storeInfo['mobile']){
                return parent::returnMsg(1,'','抱歉，不能赠送给自己，暂不支持领取哦！！');
            }
            //检测赠送人卡片是否被领取
            $blink = Db::name('blink_order_box_card')
                ->where('cardno',$cardno)
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->order('create_time','desc')
                ->find();
            if(empty($blink)){
                return parent::returnMsg(0,'','当前鼠卡已被领取！');
            }
            if($blink['is_compose'] == 1){
                return parent::returnMsg(0,'','当前鼠卡已合成');
            }
            if($blink['is_give'] == 0){
                return parent::returnMsg(0,'','当前鼠卡未发起赠送');
            }
            if($blink['is_give'] == 1){
                return parent::returnMsg(0,'','当前鼠卡已领取');
            }
            //更改赠送人卡片记录
            Db::name('blink_order_box_card')
                ->where('cardno',$cardno)
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->update([
                    'is_give' => 1,
                    'status' => 0,
                    'update_time' => time(),
                    'close_time' => 0,
                ]);
            //为接受人添加盲盒卡片记录
            Db::name('blink_order_box_card')->insert([
                'pid' => $blink['id'],
                'uid' => $uid,
                'type' => 0,
                'is_compose' => 0,
                'blinkno' => $blink['blinkno'],
                'cardno' => $blink['cardno'],
                //'qrcode' => pickUpCode('blinkcard_'.$blink['cardno']),//核销卡片
                'qrcode' => '',//使用时生成核销二维码
                'thumb_id' => $blink['thumb_id'],
                'status' => 0,
                'source' => $blink['source'],
                'is_give' => 0,
                'parent_owner' => $info['give_uid'],
                'create_time' => time(),
                'update_time' => time(),
            ]);

            //获取当前用户的昵称
            $cur_member = $this->getCurrentUserInfo($uid);
            sendMessage($storeInfo['mobile'],['nickname'=>$current_user['nickname']],config('blink_sms_id'));
            //添加分享日志
            $store = Db::table('ims_bwk_branch')->where('id',$storeid)->field('title,sign')->find();
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $cardno,
                'desc'    => "{$cardno} 被 {$store['title']}（{$store['sign']}）门店手机号为 {$mobile} 的用户接收".$msg,
            ];
            $this->setShareLogs($share);
            return parent::returnMsg(1,$cur_member,'鼠卡接受成功');
        }catch (Exception $e){
            return parent::returnMsg(0,'','鼠卡操作失败：'.$e->getMessage());
        }
    }
    //拒绝接受赠送
    public function set_reject_card(){
        $give_id = intval( input('param.give_id', 0) );//盒子赠送记录
        $storeid = intval( input('param.storeid', 0) );//盒子赠送记录
        $uid = intval( input('param.uid', 0) );//盒子赠送记录
        if(empty($give_id) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失！');
        }
        try {
            //检测赠送记录是否超市
            $info = Db::name('blink_card_give_record')->where('id',$give_id)->find();
            if(empty($info)){
                return parent::returnMsg(2,'','鼠卡赠送记录已失效或已删除!');
            }
            if($info['give_uid'] == $uid){
                return parent::returnMsg(2,'','抱歉，它的主人不是您哦，暂不支持拒绝哦！！');
            }
            //检测赠送人门店
            $members = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$info['give_uid'])
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign')
                ->find();
            if(empty($members)){
                return parent::returnMsg(2,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$uid)
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,u.nickname,bwk.title,bwk.sign')
                ->find();
            if(empty($current_user)){
                return parent::returnMsg(2,'','当前用户不存在或已删除！');
            }
            $msg = '';
            if(in_array($current_user['sign'],$this->sign) && in_array($members['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $members['storeid'] || $current_user['storeid'] != $members['storeid']){
                    return parent::returnMsg(2,'','暂不支持跨门店操作哦！');
                }
            }

            //不能赠送给自己
            if($info['mobile'] == $members['mobile']){
                return parent::returnMsg(2,$members,'抱歉，不能赠送给自己，暂不支持拒绝哦！！');
            }
            //检测赠送人卡片是否被领取
            $blink = Db::name('blink_order_box_card')
                ->where('cardno',$info['cardno'])
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->order('create_time','desc')
                ->find();
            if(empty($blink)){
                return parent::returnMsg(2,'','当前鼠卡已被领取！');
            }
            if($blink['is_compose'] == 1){
                return parent::returnMsg(2,'','当前鼠卡已合成');
            }
            if($blink['is_give'] == 0){
                return parent::returnMsg(2,'','当前鼠卡未发起赠送');
            }
            if($blink['is_give'] == 1){
                return parent::returnMsg(2,'','当前鼠卡已领取');
            }
            //更新盒子中卡片记录
            Db::name('blink_order_box_card')
                ->where('blinkno',$info['blinkno'])
                ->where('cardno',$info['cardno'])
                ->where('give_id',$give_id)
                ->update([
                    //'give_id' => 0,
                    'close_time' => 0,
                    'is_give' => 0,
                    'update_time' => time(),
                ]);
            //添加分享日志
            $store = Db::table('ims_bwk_branch')->where('id',$storeid)->field('title,sign')->find();
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $info['cardno'],
                'desc'    => "{$info['cardno']} 被 {$store['title']}（{$store['sign']}）门店手机号为 {$info['mobile']} 的用户拒绝{$msg}，已回退",
            ];
            $this->setShareLogs($share);
            return parent::returnMsg(2,'','鼠卡已返回到赠送人手中');
        }catch (Exception $e){
            return parent::returnMsg(0,'','鼠卡操作失败：'.$e->getMessage());
        }
    }
    //合成卡片 转化为大礼包卡券
    public function make_card(){
        $uid = intval( input('param.uid', 0) );//当前用户
        $storeid = intval( input('param.storeid', 0) );//当前用户
        if(empty($storeid) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            debug('begin');
            //获取卡片原始数据
            $images = Db::name('blink_box_card_image')
                ->where('cid',1)
                ->where('type',0)//普通卡
                ->column('id');

            //获取当前用户卡片数据
            $list = Db::name('blink_order_box_card')
                ->where('thumb_id','in',$images)
                ->where('uid','=',$uid)
                ->where('is_give','=',0)//未赠送
                ->where('is_compose','=',0)//未合成卡
                ->group('thumb_id')
                ->column('id');
            //可以合成
            if(count($list) == 5){
                //1修改合成记录 卡片已合成
                Db::name('blink_order_box_card')
                    ->where('id','in',$list)
                    ->update([
                        'is_compose' => 1,
                        'update_time' => time(),
                    ]);
                logs(date('Y-m-d H:i:s').' 合成鼠卡：'.Db::name('blink_order_box_card')->getLastSql(),'make_card');
                //生成一条卡券记录
                $cardno = generate_promotion_code($uid,1,'',8)[0];
                //大礼包
                //4添加合成卡
                $thumb_compose = Db::name('blink_box_card_image')->where('cid',1)->where('type',1)->value('id');
                Db::name('blink_order_box_card')->insert([
                    'uid' => $uid,
                    'thumb_id' => $thumb_compose,
                    'cardno' => $cardno,
                    'is_compose' => 0,
                    'is_give' => 0,
                    'type' => 1,
                    'qrcode' => '',//pickUpCode('blinkcard_'.$cardno),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                //3添加卡券商品记录 大礼包包含5张现金卡片
                //3.1获取礼包卡中的商品
                $p['bbg.cid'] = 1;
                $p['bbg.type'] = 2;
                $compose_goods = Db::name('blink_box_goods')
                    ->alias('bbg')
                    ->join(['pt_goods'=>'g'],'bbg.goods_id=g.id','left')
                    ->field('bbg.goods_id,bbg.num,g.name,g.activity_price')
                    ->where($p)
                    ->select();

                $insert = [];
                $j = 0;
                $ccc = [];
                foreach ($compose_goods as $k=>$val){
                    $num = $val['num'];
                    for($i=0;$i<$num;$i++){
                        $_cardno = $cardno . ( 1 + $j);
                        $ccc[$j] = $_cardno;
                        $insert[$j] = [
                            'uid' => $uid,
                            'blinkno' => $cardno,//合成卡无盲盒编号
                            'ticket_code' => $_cardno,
                            'num' => $val['num'],
                            'par_value' => $val['activity_price'],
                            //'qrcode' => pickUpCode('blinkcompose_'.$_cardno),//核销合成卡片
                            'qrcode' => '',//使用时生成核销二维码
                            'goods_id' => $val['goods_id'],
                            'price' => 2020,
                            'parent_owner' => 0,//接收人ID
                            'type' => 2,//礼包合成卡
                            'status' => 0,
                            'source' => 3,//来源 0拆盲盒 1好友赠送 2好友助理 3合成卡片
                            'share_status' => 0,
                            'insert_time' => time(),
                            'update_time' => time(),
                        ];
                        $j++;
                    }
                }
                if(!empty($insert)){
                    Db::name('blink_box_coupon_user')->insertAll($insert);
                    /*if(!empty($ccc)){
                        foreach ($ccc as $v){
                            $r = Db::name('blink_box_coupon_user')
                                ->where('ticket_code',$v)
                                ->where('uid',$uid)->find();
                            if($r){
                                Db::name('blink_box_coupon_user')
                                    ->where('ticket_code',$v)
                                    ->where('uid',$uid)
                                    ->where('id',$r['id'])
                                    ->update([
                                        'qrcode' => pickUpCode('blinkcompose_'.$v.'_'.$r['id']),//核销合成卡片
                                        'update_time' => time(),
                                    ]);
                            }

                        }
                    }*/
                }
                debug('end');

                logs(date('Y-m-d H:i:s').' 合成商品总耗时：'.debug('begin','end',8).'s '.debug('begin','end','m'),'make_card');
                return parent::returnMsg(1,'','成功兑换2020元新年开运大盲盒，请前往我的优惠券查看！');
            }else{
                return parent::returnMsg(1,'','合成卡片条件不成立');
            }
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }

    //----------------------------------卡券商品-------------------------------------------------
    /**
     * Commit: 我的卡券
     * Function: coupon
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-26 13:20:29
     * @Return \think\response\Json
     */
    public function coupon(){
        $uid  = input('param.uid',0);//登陆用户ID
        $page  = input('param.page',1);//登陆用户ID
        if(empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        $limit = 10;
        //获取我拆开的盲盒中的商品
        //$list = Db::name('blink_box_coupon_user')
        $list = BlinkBoxCouponUserModel::alias('user')
            ->field('user.*,g.name as goods_name,g.image as image,g.images,g.intro')
            ->join(['pt_goods'=>'g'],'user.goods_id=g.id','left')
            ->where('user.uid',$uid)
            ->order('insert_time','desc')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            /*foreach ($list as $k=>$val){
                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $image = $images['0'];
                }else{
                    $image = $val['image1'];
                }
                $list[$k]['image'] = $image;
            }*/
        }
        $total = BlinkBoxCouponUserModel::alias('user')->where('user.uid',$uid)->count();
        //检测当前用户是否能够批量申请
        $apply = BlinkBoxCouponUserModel::alias('user')
            ->where('user.type',0)
            ->where('user.status',0)
            ->where('user.is_deliver',0)
            ->where('user.share_status',0)
            ->where('user.uid',$uid)
            ->count(); 
        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'is_apply' => $apply ? 1 : 0,
            'list' => $list
        ];
        return parent::returnMsg(1,$data,'卡券数据获取成功');
    }
    //赠送给好友
    public function give_coupon(){
        $ticket_code = trim( input('param.code', 0) );//券号
        $uid = intval( input('param.uid', 0) );//盒子编号
        $mobile = input('param.mobile', '') ;//接收人手机号
        $give_advice = trim( input('param.give_advice', 0) );//好友赠言
        $type = intval( input('param.type', 0) );//类型 1 清洁卡 2礼包卡 0一般商品
        if(empty($ticket_code) || empty($mobile) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            logs(date('Y-m-d H:i:s').' 0 '.json_encode(input('param.')),'ttt');
            //检测手机号使用者
            $storeInfo = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->field('m.id,bwk.title,bwk.sign')
                ->where('m.mobile',$mobile)
                ->find();
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','您要赠送的用户不存在！');
            }
            logs(date('Y-m-d H:i:s').' 1 '.json_encode($storeInfo),'ttt');
            if($storeInfo['id'] == $uid){
                return parent::returnMsg(0,'','不能赠送给自己');
            }
            //检测当前卡券是否存在 未分享
            $coupon = Db::name('blink_box_coupon_user')
                ->where('ticket_code',$ticket_code)
                ->where('uid',$uid)
                ->where('share_status',0) //未分享
                ->where('status',0) //未使用
                ->where('type',$type)
                ->order('insert_time','desc')
                ->find();
            logs(date('Y-m-d H:i:s').' 2 '.json_encode($coupon),'ttt');
            if(empty($coupon)){
                return parent::returnMsg(0,'','当前卡券或商品不存在！');
            }
            if($coupon['status'] == 1){
                return parent::returnMsg(0,'','当前卡券或商品已使用!');
            }
            if($coupon['is_deliver'] == 1){
                return parent::returnMsg(0,'','当前卡券或商品已核销!');
            }
            if($coupon['share_status'] != 0){
                return parent::returnMsg(0,'','当前卡券赠送中或商品已赠送');
            }
            //添加卡券商品赠送记录 返回ID
            $give_id = Db::name('blink_coupon_give_record')->insert([
                'give_uid' => $uid,
                'code' => $ticket_code,
                'pid' => 0,
                'mobile' => $mobile,
                'give_advice' => $give_advice,
                'create_time' => time(),
                'update_time' => time(),
                'close_time' => time()+86300,
            ],false,true);

            logs(date('Y-m-d H:i:s').' 3 '.$give_id,'ttt');
            if($give_id){
                //更新用户卡券商品记录
                $res = Db::name('blink_box_coupon_user')
                    ->where('ticket_code',$ticket_code)
                    ->where('uid',$uid)
                    ->where('type',$type)
                    ->where('id',$coupon['id'])
                    ->update([
                        'share_status' => 2,//赠送中
                        'share_time' => time(),//赠送中
                        'give_id' => $give_id,//赠送中
                    ]);
                logs(date('Y-m-d H:i:s').' 4 '.$res,'ttt');
                //添加分享日志
                $share = [
                    'uid'     => $uid,//当前用户
                    'receive' => $storeInfo['id'],//接收用户
                    'type'    => 2,//记录类型 0 盲盒 1鼠卡 2卡券
                    'code'    => $ticket_code,
                    'desc'    => "{$ticket_code} 转赠给{$storeInfo['title']}（{$storeInfo['sign']}）门店手机号为 {$mobile}的用户",
                ];
                $this->setShareLogs($share);
                return parent::returnMsg(1,['give_id'=>$give_id],'已赠送给好友，等待接受');
            }else{
                return parent::returnMsg(0,'','赠送失败');
            }
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }
    //接收卡券商品页面
    //give_id=1
    public function accept_coupon(){
        $give_num = trim( input('param.give_num', 0) );//卡券商品编号
        $coupon_id = trim( input('param.coupon_id', 0) );//卡券id
        $give_userid = intval( input('param.give_userid', 0) );//盒子赠送人
        $uid = intval( input('param.uid', 0) );//当前用户ID
        if(empty($give_num) || empty($uid)|| empty($give_userid)){
            return parent::returnMsg(0,input('param.'),'参数缺失');
        }
        if($uid == $give_userid){
            $data['click'] = 0;
            //return parent::returnMsg(2,'','您不能领取自己的盒子!');
        }
        //查询赠送人卡片商品
        $p['ticket_code'] = $give_num;
        $p['uid'] = $give_userid;
        if(!empty($coupon_id)){
            $p['id'] = $coupon_id;
        }
        $coupon = Db::name('blink_box_coupon_user')
            ->where($p)
            ->order('insert_time','desc')
            ->find();
        if(empty($coupon)){
            return parent::returnMsg(2,'','当前卡券商品已删除');
        }

        //检测卡片赠送记录是否存在
        $info = Db::name('blink_coupon_give_record')->where('id',$coupon['give_id'])->find();
        if(empty($info)){
            return parent::returnMsg(2,'','当前行为已失效!');
        }
        if($info['close_time'] < time()){
            $data['click'] = 0;
            //return parent::returnMsg(2,'','当前行为已失效');
        }
        //判断是否被领取
        if($coupon['status'] == 1){
            $data['click'] = 0;
            return parent::returnMsg(2,$coupon,'当前卡券或商品已使用!');
        }
        if($coupon['share_status'] == 0){
            $data['click'] = 0;
            //检测是否是接受人进入
            $UUU = Db::table('ims_bj_shopn_member')->where('mobile',$info['mobile'])->value('id');
            if($UUU == $uid){
                return parent::returnMsg(2,'','当前卡券或商品未发起赠送或已回退给赠送人');
            }
        }
        if($coupon['share_status'] == 1){
            $data['click'] = 0;
            //检测是否是接受人进入
            $UUU = Db::table('ims_bj_shopn_member')->where('mobile',$info['mobile'])->value('id');
            if($UUU == $uid){
                return parent::returnMsg(2,'','当前卡券或商品已领取!!');
            }
        }
        //检测当前用户是否是接收人
        $mobile = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
        if($mobile == $info['mobile']){
            $data['click'] = 1;
        }else{
            $data['click'] = 0;
        }

        $goods = Db::name('goods')->where('id',$coupon['goods_id'])->find();
        $coupon['goods_name'] = $goods['name'];
        $coupon['goods_image'] = $goods['image'];
        $coupon['intro'] = $goods['intro'];
        $data['info'] = $coupon;
        //赠送人信息
        $member = Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->field('u.nickname,u.avatar,m.storeid,m.staffid,m.mobile,m.realname')
            ->where('m.id',$give_userid)
            ->find();
        if($member['mobile'] == $info['mobile']){
            $data['click'] = 0;
            //return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
        }
        $member['give_advice'] = $info['give_advice'];
        $member['_mobile'] = $info['mobile'];
        $data['member'] = $member;
        $data['record'] = $info;
        return parent::returnMsg(1,$data,'卡券商品数据查询成功');
    }
    //好友接收卡券商品
    public function set_accept_coupon(){
        $give_id = intval( input('param.give_id', 0) );//卡券商品赠送记录
        $ticket_code = trim( input('param.blinkno', 0) );//卡券商品
        $uid = intval( input('param.uid', 0) );//当前用户
        $storeid = intval( input('param.storeid', 0) );//当前用户门店
        $mobile = trim( input('param.mobile', 0) );//当前用户
        if(empty($give_id) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            //检测卡片赠送记录是否存在
            $info = Db::name('blink_coupon_give_record')->where('id',$give_id)->find();
            if(empty($info)){
                return parent::returnMsg(0,'','卡券商品赠送记录不存在');
            }
            //检测接收人手机号 检测赠送人和接收人是否是同一人
            if($mobile != $info['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
            }
            //检测赠送人门店及信息
            $members = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$info['give_uid'])
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign')
                ->find();
            if(empty($members)){
                return parent::returnMsg(0,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$uid)
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,u.nickname,bwk.title,bwk.sign')
                ->find();
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            $msg = '';
            //
            if(in_array($current_user['sign'],$this->sign) && in_array($members['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $members['storeid'] || $members['storeid'] != $current_user['storeid']){
                    return parent::returnMsg(0,'','暂不支持跨门店操作哦！');
                }
            }
            //不能分享给自身
            if($info['mobile'] == $members['mobile']){
                return parent::returnMsg(0,'','抱歉，不能分享给自己，暂不支持领取哦！！');
            }
            //检测卡券商品是否被领取
            $blink = Db::name('blink_box_coupon_user')
                ->where('ticket_code',$ticket_code)
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->order('insert_time','desc')
                ->find();
            if(empty($blink)){
                return parent::returnMsg(0,'','当前卡券商品不存在或已删除！');
            }
            if($blink['status'] == 1){
                return parent::returnMsg(0,'','当前卡券或商品已使用');
            }
            if($blink['share_status'] == 0){
                return parent::returnMsg(0,'','当前卡券或商品未发起赠送');
            }
            if($blink['share_status'] == 1){
                return parent::returnMsg(0,'','当前卡券或商品已领取');
            }
            //更改赠送人卡券商品记录
            Db::name('blink_box_coupon_user')
                ->where('ticket_code',$ticket_code)
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->update([
                    'share_status' => 1,
                    'status' => 0,
                    'update_time' => time(),
                ]);
            //为接受人添加卡券商品记录
            $res = Db::name('blink_box_coupon_user')->insert([
                'pid' => $blink['id'],
                'uid' => $uid,
                'blinkno' => $blink['blinkno'],
                'par_value' => $blink['par_value'],
                'ticket_code' => $blink['ticket_code'],
                //'qrcode' => pickUpCode('blinkcoupon_'.$blink['ticket_code'].'_'.$blink['id']),//核销卡片
                'qrcode' => '',//使用时生成核销二维码
                'goods_id' => $blink['goods_id'],
                'price' => $blink['price'],
                'parent_owner' => $blink['uid'],//上一位拥有者
                'type' => $blink['type'],
                'status' => 0,
                'source' => 1,
                'share_status' => 0,
                'insert_time' => time(),
                'update_time' => time(),
            ],false,true);
            /*if(!empty($res)){
                Db::name('blink_box_coupon_user')->where('id',$res)->update([
                    'update_time' => time(),
                    'qrcode' => pickUpCode('blinkcoupon_'.$blink['ticket_code'].'_'.$res)
                ]);
            }*/

            //获取当前用户的昵称
            sendMessage($members['mobile'],['nickname'=>$current_user['nickname']],config('blink_sms_id'));

            //添加分享日志
            $store = Db::table('ims_bwk_branch')->where('id',$storeid)->field('title,sign')->find();
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 2,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $ticket_code,
                'desc'    => "{$ticket_code} 被 {$store['title']}（{$store['sign']}）门店手机号为 {$mobile} 的用户接收".$msg,
            ];
            $this->setShareLogs($share);

            return parent::returnMsg(1,'','当前卡券或商品接受成功');
        }catch (Exception $e){
            return parent::returnMsg(0,'','卡券或商品操作失败：'.$e->getMessage());
        }
    }
    //拒绝接受赠送的卡券商品
    public function set_reject_coupon(){
        $give_id = intval( input('param.give_id', 0) );//盒子赠送记录
        $uid = intval( input('param.uid', 0) );//当前用户ID
        $storeid = intval( input('param.storeid', 0) );//当前用户门店
        if(empty($give_id) ||empty($uid)){
            return parent::returnMsg(0,'','参数缺失！');
        }
        try {
            //检测卡券商品赠送记录是否超市
            $info = Db::name('blink_coupon_give_record')->where('id',$give_id)->find();
            if(empty($info)){
                return parent::returnMsg(2,'','当前行为已失效!');
            }
            //检测赠送人门店
            $members = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$info['give_uid'])
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign')
                ->find();
            if(empty($members)){
                return parent::returnMsg(2,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user =  Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->where('m.id',$uid)
                ->field('m.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,u.nickname,bwk.title,bwk.sign')
                ->find();
            if(empty($current_user)){
                return parent::returnMsg(2,'','当前用户不存在或已删除！');
            }
            $msg = '';
            if(in_array($current_user['sign'],$this->sign) && in_array($members['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $members['storeid'] || $members['storeid'] != $current_user['storeid']){
                    return parent::returnMsg(2,'','暂不支持跨门店操作哦！');
                }
            }
            //不能分享给自身
            if($info['mobile'] == $members['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(2,'','抱歉，它的主人不是您哦，暂不支持拒绝哦！！');
            }
            //获取当前用户数据
            if($current_user['mobile'] != $info['mobile']){
                return parent::returnMsg(2,'','抱歉，它的主人不是您哦，暂不支持拒绝哦！！');
            }

            //检测卡券商品是否被领取
            $blink = Db::name('blink_box_coupon_user')
                ->where('ticket_code',$info['code'])
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->order('insert_time','desc')
                ->find();
            if(empty($blink)){
                return parent::returnMsg(2,'','当前卡券商品已被领取！');
            }
            if($blink['status'] == 1){
                return parent::returnMsg(2,'','当前卡券或商品已使用');
            }
            if($blink['share_status'] == 0){
                return parent::returnMsg(2,'','当前卡券或商品未发起赠送');
            }
            if($blink['share_status'] == 1){
                return parent::returnMsg(2,'','当前卡券或商品已领取');
            }
            //更新卡券商品
            Db::name('blink_box_coupon_user')
                ->where('ticket_code',$info['code'])
                ->where('uid',$info['give_uid'])
                ->where('give_id',$give_id)
                ->update([
                    //'give_id' => 0,
                    'share_time' => 0,
                    'share_status' => 0,
                    'update_time' => time(),
                ]);

            //添加分享日志
            $store = Db::table('ims_bwk_branch')->where('id',$storeid)->field('title,sign')->find();
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $info['code'],
                'desc'    => "{$info['code']} 被 {$store['title']}（{$store['sign']}）门店手机号为 {$info['mobile']} 的用户拒绝 {$msg}，已回退",
            ];
            $this->setShareLogs($share);
            return parent::returnMsg(2,'','当前卡券商品已返回到赠送人手中');
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }
    //用户申请发货
    public function apply(){
        $uid = intval( input('param.uid', 0) );//当前用户ID
        $id = intval( input('param.id', 0) );//当前卡券ID
        if(empty($uid) ||empty($id)){
            return parent::returnMsg(0,'','参数缺失！');
        }
        try {
            //检测卡券商品信息
            $info = Db::name('blink_box_coupon_user')
                ->where('id',$id)
                ->find();
            if(empty($info)){
                return parent::returnMsg(0,'','当前商品不存在或已发货!');
            }
            if($info['status'] == 1){
                return parent::returnMsg(0,'','当前商品已核销!');
            }
            if($info['is_deliver'] == 1){
                return parent::returnMsg(0,'','当前商品已发货!');
            }
            if($info['is_deliver'] == 2){
                return parent::returnMsg(10,'','当前商品发货中!');
            }
            if($info['share_status'] == 2){
                return parent::returnMsg(0,'','当前卡券或商品已发起赠送');
            }
            if($info['share_status'] == 1){
                return parent::returnMsg(2,'','当前卡券或商品已领取');
            }
            //更新发货状态
            //更新卡券商品
            $res = Db::name('blink_box_coupon_user')
                ->where('id',$id)
                ->where('uid',$uid)
                ->update([
                    'is_deliver' => 2,
                    'is_apply' => 1,
                    'remark' => "用户自己申请发货，申请时间".date('Y-m-d H:i:s'),
                    'aead_time' => time(),
                ]);
            return parent::returnMsg(1,'','当前用户申请成功，请耐心等候！');
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }
    //用户批量申请
    public function batchapply(){
        $uid = intval( input('param.uid', 0) );//当前用户ID
        if(empty($uid)){
            return parent::returnMsg(0,'','参数缺失！');
        }
        try {
            //检测卡券商品信息
            $list = Db::name('blink_box_coupon_user')
                ->where('uid',$uid)
                ->where('type',0)//九大商品
                ->where('status',0)//未核销
                ->where('is_deliver',0)//未发货
                ->where('share_status',0)//未分享
                ->select();
            if(empty($list)){
                return parent::returnMsg(0,'','当前用户商品已核销或发货中!');
            }
            //需要申请发货的ID集合
            $ids = [];
            foreach($list as $k=>$val){
                $ids[] = $val['id'];
            }
            if(empty($ids)){
                return parent::returnMsg(0,'','当前用户商品已核销或发货中!');
            }
           
            //更新发货状态
            //更新卡券商品
            $res = Db::name('blink_box_coupon_user')
                ->where('id','in',$ids)
                ->where('uid','=',$uid)
                ->update([
                    'is_deliver' => 2,
                    'is_apply' => 1,
                    'remark' => "用户自己批量申请发货，申请时间".date('Y-m-d H:i:s'),
                    'aead_time' => time(),
                ]);
            return parent::returnMsg(1,'','当前用户批量申请成功，请耐心等候！');
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }
    /**
     * Commit: 添加分享流转记录
     * Function: setShareLogs
     * @Param $data 添加记录数据
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-03 09:36:10
     */
    public function setShareLogs($data){
        return $data ? Db::name('blink_share_logs')->insert($data) : 0;
    }
    //----------------------美容师业绩---------------------------------------------
    //美容师下的顾客
    /**
     * Commit: 获取美容师下属的活动商品及卡券列表
     * Function: coupon_fid_list
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-08 09:21:30
     */
    public function coupon_fid_blink_list() {
        $storeid = intval( input('param.storeid', 0) );//门店
        $fid = intval( input('param.fid', 0) );//美容师id
        $page = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        //判断门店
        $this->checkStore($storeid);
        //检测当前美容师下的用户
        $map['storeid'] = $storeid;
        $map['staffid'] = $fid;
        $map1['originfid'] = $fid;
        //正常的美容师下的客户
        $mids = Db::table('ims_bj_shopn_member')
            ->where($map)
            ->whereOr($map1)
            ->column('id');//美容师下的用户

        //查询用户盲盒数据
        $limit = 10;
        $total =  Db::name('blink_order_box')
            ->alias('box')
            ->join(['pt_goods'=>'g'],'box.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member'=>'m'],'box.uid=m.id','left')//查询用户
            ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member'=>'mem'],'mem.id=m.staffid','left')//查询用户所属美容师
            ->where('uid','in',$mids)
            ->count();
        $field = "box.*";
        $list = Db::name('blink_order_box')
            ->alias('box')
            ->field('box.*,g.name,m.mobile,m.staffid,m.originfid,m.realname,m.storeid,m.pid as share_id,bwk.title,bwk.sign,mem.id as sellerid,mem.realname as sellername,mem.mobile as sellermobile')
            ->join(['pt_goods'=>'g'],'box.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member'=>'m'],'box.uid=m.id','left')//查询用户
            ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member'=>'mem'],'mem.id=m.staffid','left')//查询用户所属美容师
            ->where('uid','in',$mids)
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人信息
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('m')
                        ->field('m.id,m.storeid,m.staffid,m.realname,m.mobile,bwk.title,bwk.sign')
                        ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
                        ->where('m.id',$val['originfid'])
                        ->find();
                    $list[$k]['origin'] = [
                        'origin_fid'     => $info['id'],//原始（发货）美容师ID
                        'origin_name'    => $info['realname'],//原始（发货）美容师昵称
                        'origin_mobile'  => $info['realname'],//原始（发货）美容师手机号
                        'origin_storeid' => $info['storeid'],//原始（发货）美容师手所属门店编号
                        'origin_title'   => $info['title'],//原始（发货）美容师手所属门店
                        'origin_sign'    => $info['sign'],//原始（发货）美容师手所属门店编号
                    ];
                }else{
                    $list[$k]['origin'] = [
                        'origin_fid'     => $val['staffid'],//原始（发货）美容师ID
                        'origin_name'    => $val['sellername'],//原始（发货）美容师昵称
                        'origin_mobile'  => $val['sellermobile'],//原始（发货）美容师手机号
                        'origin_storeid' => $val['storeid'],//原始（发货）美容师手所属门店编号
                        'origin_title'   => $val['title'],//原始（发货）美容师手所属门店
                        'origin_sign'    => $val['sign'],//原始（发货）美容师手所属门店编号
                    ];
                }
            }
        }
        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'list' => $list
        ];
        return parent::returnMsg(1,$data,'美容师下属盲盒数据查询成功');
    }
    //商品
    public function coupon_fid_coupon_list() {
        $storeid = intval( input('param.storeid', 0) );//门店
        $fid = intval( input('param.fid', 0) );//美容师id
        $page = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        //判断门店
        $this->checkStore($storeid);
        //检测当前美容师下的用户
        $map['storeid'] = $storeid;
        $map['staffid'] = $fid;
        $map1['originfid'] = $fid;
        //正常的美容师下的客户
        $mids = Db::table('ims_bj_shopn_member')
            ->where($map)
            ->whereOr($map1)
            ->column('id');//美容师下的用户

        //查询用户盲盒数据
        $limit = 10;
        $total =  Db::name('blink_box_coupon_user')
            ->alias('cu')
            ->join(['pt_goods'=>'g'],'cu.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member'=>'m'],'cu.uid=m.id','left')//查询用户
            ->join(['ims_bj_shopn_member'=>'mem'],'mem.id=m.staffid','left')//查询用户所属美容师
            ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
            ->where('cu.uid','in',$mids)
            ->count();

        $list = Db::name('blink_box_coupon_user')
            ->alias('cu')
            ->field('cu.*,m.storeid,g.name,m.mobile,m.realname,m.staffid,m.originfid,m.storeid,m.pid as share_id,bwk.title,bwk.sign,mem.id as sellerid,mem.realname as sellername,mem.mobile as sellermobile')
            ->join(['pt_goods'=>'g'],'cu.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member'=>'m'],'cu.uid=m.id','left')//查询用户
            ->join(['ims_bj_shopn_member'=>'mem'],'mem.id=m.staffid','left')//查询用户所属美容师
            ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
            ->where('cu.uid','in',$mids)
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人信息
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('m')
                        ->field('m.id,m.storeid,m.staffid,m.realname,m.mobile,bwk.title,bwk.sign')
                        ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
                        ->where('m.id',$val['originfid'])
                        ->find();
                    $list[$k]['origin'] = [
                        'origin_fid'     => $info['id'],//原始（发货）美容师ID
                        'origin_name'    => $info['realname'],//原始（发货）美容师昵称
                        'origin_mobile'  => $info['realname'],//原始（发货）美容师手机号
                        'origin_storeid' => $info['storeid'],//原始（发货）美容师手所属门店编号
                        'origin_title'   => $info['title'],//原始（发货）美容师手所属门店
                        'origin_sign'    => $info['sign'],//原始（发货）美容师手所属门店编号
                    ];
                }else{
                    $list[$k]['origin'] = [
                        'origin_fid'     => $val['staffid'],//原始（发货）美容师ID
                        'origin_name'    => $val['sellername'],//原始（发货）美容师昵称
                        'origin_mobile'  => $val['sellermobile'],//原始（发货）美容师手机号
                        'origin_storeid' => $val['storeid'],//原始（发货）美容师手所属门店编号
                        'origin_title'   => $val['title'],//原始（发货）美容师手所属门店
                        'origin_sign'    => $val['sign'],//原始（发货）美容师手所属门店编号
                    ];
                }
            }
        }
        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'list' => $list
        ];
        return parent::returnMsg(1,$data,'美容师下属商品数据查询成功');
    }
    public function coupon_fid_order_list(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $fid = intval( input('param.fid', 0) );//美容师id
        $page = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        //判断门店
        $this->checkStore($storeid);
        //检测当前美容师下的用户
        $map['storeid'] = $storeid;
        $map['staffid'] = $fid;
        $map1['originfid'] = $fid;
        //正常的美容师下的客户
        $mids = Db::table('ims_bj_shopn_member')
            ->where($map)
            ->whereOr($map1)
            ->column('id');//美容师下的用户

        //查询用户盲盒数据
        $limit = 10;
        $total =  Db::name('blink_order')
            ->alias('order')
            ->join(['pt_goods'=>'g'],'order.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member'=>'m'],'order.uid=m.id','left')//查询用户
            ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member'=>'mem'],'mem.id=m.staffid','left')//查询用户所属美容师
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->where('order.uid','in',$mids)
            ->where('order.pay_status','=',1)
            ->count();
        $field = "order.id,order.storeid,order.uid,order.order_sn,order.goods_id,order.fid,order.pay_status";
        $field .= ",order.pay_price,order.pay_time,order.insert_time,order.num";
        $field .= ",g.name,g.activity_price,g.image";
        $field .= ",m.mobile,m.staffid,m.originfid,m.realname,m.storeid,m.pid as share_pid";//当前用户信息
        $field .= ",mem.id as sellerid,mem.realname as sellername,mem.mobile as sellermobile";//当前用户所属美容师信息
        $field .= ",bwk.title,bwk.sign";//当前用户门店信息
        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";//当前用户门店所属市场
        $list = Db::name('blink_order')
            ->alias('order')
            ->join(['pt_goods'=>'g'],'order.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member'=>'m'],'order.uid=m.id','left')//查询用户
            ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
            ->join(['ims_bj_shopn_member'=>'mem'],'mem.id=m.staffid','left')//查询用户所属美容师
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->where('order.uid','in',$mids)
            ->field($field)
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人信息
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('m')
                        ->field('m.id,m.storeid,m.staffid,m.realname,m.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->join(['ims_bwk_branch' => 'bwk'],'m.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('m.id',$val['originfid'])
                        ->find();
                    $list[$k]['origin'] = [
                        'origin_fid'        => $info['id'],//原始（发货）美容师ID
                        'origin_name'       => $info['realname'],//原始（发货）美容师昵称
                        'origin_mobile'     => $info['realname'],//原始（发货）美容师手机号
                        'origin_storeid'    => $info['storeid'],//原始（发货）美容师手所属门店编号
                        'origin_title'      => $info['title'],//原始（发货）美容师手所属门店
                        'origin_sign'       => $info['sign'],//原始（发货）美容师手所属门店编号
                        'origin_department' => $val['pertain_department_name'],//原始（发货）美容师手所属门店所
                    ];
                }else{
                    $list[$k]['origin'] = [
                        'origin_fid'        => $val['staffid'],//原始（发货）美容师ID
                        'origin_name'       => $val['sellername'],//原始（发货）美容师昵称
                        'origin_mobile'     => $val['sellermobile'],//原始（发货）美容师手机号
                        'origin_storeid'    => $val['storeid'],//原始（发货）美容师手所属门店编号
                        'origin_title'      => $val['title'],//原始（发货）美容师手所属门店
                        'origin_sign'       => $val['sign'],//原始（发货）美容师手所属门店编号
                        'origin_department' => $val['pertain_department_name'],//原始（发货）美容师手所属门店所属市场
                    ];
                }
            }
        }
        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'list' => $list
        ];
        return parent::returnMsg(1,$data,'美容师下属订单数据查询成功');
    }

    //美容师业绩列表
    public function achievement(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $fid = intval( input('param.fid', 0) );//美容师id
        $identity = intval( input('param.identity', 1) );//美容师id
        $page = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }

        if($identity == 1){
            //美容师
            //查询客户信息
            $map['m.storeid'] = $storeid;
            $map['m.staffid'] = $fid;
            $map['m.id'] = ['<>',$fid];
            $map1['m.originfid'] = $fid;
            $field = "m.id,m.storeid,m.pid,m.staffid,m.originfid,m.realname,m.mobile,m.activity_flag";
            $field .= ",m.code,u.nickname,u.avatar";
            $field .= ",mm.realname as sellername,mm.mobile as sellermobile,mm.id as sellerid,mm.code sellercode,mm.staffid sellerstaffid";
            $limit = 10;
            $total = Db::name('blink_order')->alias('o')
                ->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id and o.pay_status=1','left')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bj_shopn_member'=>'mm'],'m.staffid=mm.id','left')
                ->where($map)
                ->whereOr($map1)
                ->count('DISTINCT o.uid');
            $list = Db::name('blink_order')->alias('o')
                ->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id and o.pay_status=1','left')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bj_shopn_member'=>'mm'],'m.staffid=mm.id','left')
                ->field($field)
                ->where($map)
                ->whereOr($map1)
                ->page($page,$limit)
                ->group('o.uid')
                ->select();
        }else{
            $mapp['storeid'] = $storeid;
            $fids = Db::table('ims_bj_shopn_member')
                ->where($mapp)
                ->where("code <> '' and id=staffid")
                ->column('id');//检测美容师
            //查询客户信息
            $map['m.storeid'] = $storeid;
            $map['m.id'] = ['<>',$fid];
            $map1['m.originfid'] = ['in',$fids];
            $field = "m.id,m.storeid,m.pid,m.staffid,m.originfid,m.realname,m.mobile,m.activity_flag";
            $field .= ",m.code,u.nickname,u.avatar";
            $field .= ",mm.realname as sellername,mm.mobile as sellermobile,mm.id as sellerid,mm.code sellercode,mm.staffid sellerstaffid";
            $limit = 10;
            $total = Db::name('blink_order')->alias('o')
                ->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id and o.pay_status=1','left')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bj_shopn_member'=>'mm'],'m.staffid=mm.id','left')
                ->where($map)
                ->whereOr($map1)
                ->count('DISTINCT o.uid');
            $list = Db::name('blink_order')->alias('o')
                ->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id and o.pay_status=1','left')
                ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                ->join(['ims_bj_shopn_member'=>'mm'],'m.staffid=mm.id','left')
                ->field($field)
                ->where($map)
                ->whereOr($map1)
                ->page($page,$limit)
                ->group('o.uid')
                ->select();
        }


        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人信息
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('m')
                        ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
                        ->field('m.id,m.storeid,m.staffid,m.realname,m.mobile,m.code,u.nickname,u.avatar')
                        ->where('m.id',$val['originfid'])
                        ->find();
                    $origin = [
                        'id'       => $info['id'],
                        'storeid'  => $info['storeid'],
                        'code'     => $info['code'],
                        'staffid'  => $info['staffid'],
                        'realname' => $info['nickname'] ?: $info['realname'],
                        'mobile'   => $info['mobile'],
                    ];
                }else{
                    $realname = Db::name('blink_wx_user')->where('mobile',$val['sellermobile'])->value('nickname');
                    $origin = [
                        'id'       => $val['sellerid'],
                        'storeid'  => $val['storeid'],
                        'code'     => $val['code'],
                        'staffid'  => $val['sellerstaffid'],
                        'realname' => $realname ?: $val['sellername'],
                        'mobile'   => $val['sellermobile'],
                    ];
                }
                $list[$k]['origin'] = $origin;
            }
        }

        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'list' => $list
        ];
        return parent::returnMsg(1,$data,'数据查询成功');
    }
    //美容师业绩
    public function beauty(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $fid = intval( input('param.fid', 0) );//美容师id
        $identity = intval( input('param.identity', 1) );//是否是老板
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            if($identity == 1){
                //美容师
                //检测当前美容师下的用户
                $map['storeid'] = $storeid;
                $map['staffid'] = $fid;
                $map1['originfid'] = $fid;
                //正常的美容师下的客户
                $mids = Db::table('ims_bj_shopn_member')
                    ->where($map)
                    ->whereOr($map1)
                    ->column('id');//美容师下的用户 'id,storeid,realname,staffid'
            }else{
                //店老板
                //检测店老板下的美容师
                $map['storeid'] = $storeid;
                $fids = Db::table('ims_bj_shopn_member')
                    ->where($map)
                    ->where("code <> '' and id=staffid")
                    ->column('id');
                $where['staffid'] = ['in',$fids];
                $where1['originfid'] = ['in',$fids];
                $mids = Db::table('ims_bj_shopn_member')
                    ->where($where)
                    ->whereOr($where1)
                    ->column('id');
            }

            if(!empty($mids)){
                array_push($mids,$fid);
            }else{
                $mids = [$fid];
            }
            //查询交易金额
            $info = Db::name('blink_order')
                ->field('IFNULL(sum(pay_price),0) price,IFNULL(sum(num),0) count,count(DISTINCT uid) number')
                ->where('pay_status','=',1)
                ->where('pay_time','>',0)
                ->where('uid','in',$mids)
                ->find();
            if(empty($info)){
                $info['price'] = 0;
                $info['count'] = 0;
                $info['number'] = 0;
            }
            return parent::returnMsg(1,$info,'查询成功');
        }catch (Exception $e){
            return parent::returnMsg(0,'','操作出错：'.$e->getMessage());
        }
    }
    //某一个用户详情
    public function achinfo(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $uid = intval( input('param.uid', 0) );//美容师id
        $page = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($uid)) {
            return parent::returnMsg(0,'','参数缺失');
        }

        //查询当前用户产品卡券 只查询 产品
        $member = Db::table('ims_bj_shopn_member')
            ->alias('m')
            ->join(['pt_blink_wx_user'=>'u'],'m.mobile=u.mobile','left')
            ->where('m.id',$uid)
            ->field('m.id,m.staffid,m.originfid,m.storeid,m.mobile,m.realname,u.nickname,u.avatar')
            ->find();
        if(!empty($member)){
            if($member['storeid'] == 1792){
                $sss = Db::table('ims_bj_shopn_member')
                    ->field('realname,mobile')
                    ->where('id',$member['originfid'])
                    ->find();
            }else{
                $sss = Db::table('ims_bj_shopn_member')
                    ->field('realname,mobile')
                    ->where('id',$member['staffid'])
                    ->find();
            }
            $member['sellername'] = $sss['realname'];
            $member['sellermobile'] = $sss['mobile'];
            $member['nickname'] = $member['nickname'] ?: $member['realname'];
        }

        $map['cu.uid'] = $uid;
        $map['cu.type'] = 0; //类型 1 清洁卡 2礼包卡 0一般商品
        $total = Db::name('blink_box_coupon_user')
            ->alias('cu')
            ->join(['pt_goods'=>'g'],'cu.goods_id=g.id','left')
            ->where($map)
            ->count('DISTINCT cu.goods_id');
        $limit = 10;
        $field = "cu.price,cu.goods_id,cu.uid";
        $field .= ",g.id,g.name,g.image,g.activity_price,g.intro";
        $list = Db::name('blink_box_coupon_user')
            ->alias('cu')
            ->join(['pt_goods'=>'g'],'cu.goods_id=g.id','left')
            ->field($field)
            ->where($map)
            ->group('cu.goods_id')
            ->page($page,$limit)
            ->select();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $goods_id = $val['goods_id'];
                //产品总数
                $total = Db::name('blink_box_coupon_user')
                    ->where('type',0)
                    ->where('uid',$uid)
                    ->where('goods_id',$goods_id)
                    ->count() ?: 0;
                $list[$k]['total'] = $total;
                //核销数
                $hexiao = Db::name('blink_box_coupon_user')
                    ->where('type',0)
                    ->where('uid',$uid)
                    ->where('goods_id',$goods_id)
                    ->where('status',1)
                    ->count() ?: 0;
                $list[$k]['hexiao'] = $hexiao;
                $list[$k]['nohexiao'] = $total - $hexiao;
            }
        }

        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'list' => $list,
            'member' => $member
        ];
        return parent::returnMsg(1,$data,'数据查询成功');
    }
    //--------------------------------------核销-----------------------------------------------
    /**
     * 美容师确认收货
     */
    public function pickUpConfrim(){
        $ticket_code = input('param.ticket_code');
        $qrcode_type = input('param.qrcode_type',0);
        $coupon_id = input('param.coupon_id',0);
        if(empty($ticket_code)){
            return parent::returnMsg(0,'','卡券号不允许为空');
        }
        $time = time();
        try {
            switch ($qrcode_type){
                case 2:
                    //核销2020大礼包 19张100元 1张120元
                    //检测卡券商品数据
                    $map['ticket_code'] = $ticket_code;
                    $map['share_status'] = 0;
                    $map['status'] = 0;
                    if($coupon_id == 0){
                        $map['pid'] = $coupon_id;
                    }else{
                        $map['id'] = $coupon_id;
                    }
                    $getCodeInfo = Db::name('blink_box_coupon_user')
                        ->where($map)
                        ->order('insert_time','desc')
                        ->find();
                    if(empty($getCodeInfo)){
                        return parent::returnMsg(1,'','卡券号已核销或已分享');
                    }
                    if ($getCodeInfo['status'] == 1) {
                        return parent::returnMsg(0,'','请勿重复确认');
                    }

                    Db::name('blink_box_coupon_user')
                        ->where($map)
                        ->update([
                            'status'        => 1,
                            'is_deliver'    => 1,
                            'remark'        => $getCodeInfo['remark'].' '.'门店核销，已使用',
                            'update_time'   => $time,
                            'instrume_time' => $time
                        ]);
                    return parent::returnMsg(1,'','确认成功');
                    break;
                case 1:
                    $map['ticket_code'] = $ticket_code;
                    $map['share_status'] = 0;
                    $map['status'] = 0;
                    if($coupon_id == 0){
                        $map['pid'] = $coupon_id;
                    }else{
                        $map['id'] = $coupon_id;
                    }
                    //检测卡券商品数据
                    $getCodeInfo = Db::name('blink_box_coupon_user')
                        ->where($map)
                        ->order('insert_time','desc')
                        ->find();
                    if(empty($getCodeInfo)){
                        return parent::returnMsg(1,'','卡券号已核销或已分享');
                    }
                    if ($getCodeInfo['status'] == 1) {
                        return parent::returnMsg(0,'','请勿重复确认');
                    }
                    if ($getCodeInfo['is_deliver'] == 3) {
                        return parent::returnMsg(0,'','该券已发货');
                    }

                    Db::name('blink_box_coupon_user')
                        ->where($map)
                        ->update([
                            'status'        => 1,
                            'is_deliver'    => 1,
                            'remark'        => $getCodeInfo['remark'].' '.'门店核销，已取货',
                            'update_time'   => $time,
                            'instrume_time' => $time
                        ]);
                    return parent::returnMsg(1,'','确认成功');
                    break;
                default:
            }
        }catch (\Exception $e){
            return parent::returnMsg(0,'','出错'.$e->getMessage());
        }
    }

    /**
     * 美容师确认收货产品详细
     */
    public function pickUpGoodsInfo(){
        $orderSn = trim(input('param.order_sn'));
        $uid = trim(input('param.uid'));
        if(empty($uid) || empty($orderSn)){
            return parent::returnMsg(0,'','订单号不允许为空');
        }
        $order = explode('_',$orderSn);

        if(empty($order)){
            return parent::returnMsg(0,'','订单号不存在');
        }
        //检测用户所属门店
        $sellerStoreId = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('storeid');
        switch ($order[0]){
            case 'blinkcompose':
                //核销2020大礼包 19张100元 1张120元
                //检测卡券商品是否存在
                $coupon = Db::name('blink_box_coupon_user')
                    ->where('ticket_code',$order[1])
                    ->where('share_status',0)
                    ->where('status',0)
                    ->order('insert_time','desc')
                    ->find();
                if(empty($coupon)){
                    return parent::returnMsg(0,'','卡券已核销或已分享，请确认');
                }
                if($coupon['source'] != 3){
                    return parent::returnMsg(0,'','卡券商品类型错误，请确认');
                }
                //检测卡券所属用户的门店
                $members = Db::table('ims_bj_shopn_member')
                    ->alias('m')
                    ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                    ->field('m.id,m.pid,m.staffid,m.storeid,m.mobile,bwk.title,bwk.sign')
                    ->where('m.id',$coupon['uid'])
                    ->find();
                //检测美容师门店信息
                $staffs = Db::table('ims_bj_shopn_member')
                    ->alias('m')
                    ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                    ->field('m.id,m.pid,m.staffid,m.storeid,m.mobile,bwk.title,bwk.sign')
                    ->where('m.id',$uid)
                    ->find();
                //同一门店可以核销
                if($members['storeid'] != $staffs['storeid']){
                    //跨门店 检测是否是总部
                    if(!in_array($members['sign'],$this->sign) || !in_array($staffs['sign'],$this->sign)){
                        return parent::returnMsg(0,'','该卡券商品您没有查看权限');
                    }
                }
                /*if(!in_array($members['sign'],$this->sign) || !in_array($staffs['sign'],$this->sign)){
                    return parent::returnMsg(0,'','该卡券商品您没有查看权限');
                }*/
                if($coupon['share_status'] == 1 || $coupon['share_status'] == 2){
                    return parent::returnMsg(0,'','该卡券商品已分享');
                }
                if($coupon['status'] == 1){
                    return parent::returnMsg(0,'','该卡券商品已使用');
                }
                if($coupon['is_deliver'] == 1){
                    return parent::returnMsg(0,'','该卡券商品已核销');
                }
                //获取卡券对应商品
                $goods = Db::name('goods')->where('id',$coupon['goods_id'])->find();

                $orderInfo['blinkno'] = $coupon['blinkno'];
                $orderInfo['ticket_code'] = $coupon['ticket_code'];
                $orderInfo['thumb_id'] = $coupon['thumb_id'];
                $orderInfo['uid'] = $coupon['uid'];
                $orderInfo['coupon_id'] = !empty($order[2]) ? $order[2] : 0;
                $orderInfo['pid'] = $coupon['pid'];
                $orderInfo['type'] = $coupon['type'];
                $orderInfo['name'] = $goods['name'];
                $orderInfo['insert_time'] = date('Y-m-d H:i:s', $coupon['insert_time']);
                $orderInfo['qrcode_type'] = 2;
                $orderInfo['image'] = $goods['image'];
                $orderInfo['xc_images'] = $goods['xc_images'];
                return parent::returnMsg(1,$orderInfo,'获取成功');
                break;
            case 'blinkcoupon':
            default:
                //检测卡券商品是否存在
                $coupon = Db::name('blink_box_coupon_user')
                    ->where('ticket_code',$order[1])
                    ->where('share_status',0)//分享
                    ->where('status',0)//核销
                    ->order('insert_time','desc')
                    ->find();
                if(empty($coupon)){
                    return parent::returnMsg(0,'','卡券商品已核销或已分享，请确认');
                }
                //检测卡券所属用户的门店
                $members = Db::table('ims_bj_shopn_member')
                    ->alias('m')
                    ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                    ->field('m.id,m.pid,m.staffid,m.storeid,m.mobile,bwk.title,bwk.sign')
                    ->where('m.id',$coupon['uid'])
                    ->find();
                //检测美容师门店信息
                $staffs = Db::table('ims_bj_shopn_member')
                    ->alias('m')
                    ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                    ->field('m.id,m.pid,m.staffid,m.storeid,m.mobile,bwk.title,bwk.sign')
                    ->where('m.id',$uid)
                    ->find();
                //同一门店可以核销
                if($members['storeid'] != $staffs['storeid']){
                    //跨门店 检测是否是总部
                    if(!in_array($members['sign'],$this->sign) || !in_array($staffs['sign'],$this->sign)){
                        return parent::returnMsg(0,'','该卡券商品您没有查看权限');
                    }
                }
                /*if(!in_array($members['sign'],$this->sign) || !in_array($staffs['sign'],$this->sign)){
                    return parent::returnMsg(0,'','该卡券商品您没有查看权限');
                }*/

                if($coupon['status'] == 1){
                    return parent::returnMsg(0,'','该卡券商品已使用');
                }
                if($coupon['share_status'] == 1 || $coupon['share_status'] == 2){
                    return parent::returnMsg(0,'','该卡券商品已分享');
                }
                if($coupon['is_deliver'] == 1){
                    return parent::returnMsg(0,'','该卡券商品已核销');
                }
                //获取卡券对应商品
                $goods = Db::name('goods')->where('id',$coupon['goods_id'])->find();

                $orderInfo['blinkno'] = $coupon['blinkno'];
                $orderInfo['ticket_code'] = $coupon['ticket_code'];
                $orderInfo['thumb_id'] = $coupon['thumb_id'];
                $orderInfo['uid'] = $coupon['uid'];
                $orderInfo['coupon_id'] = !empty($order[2]) ? $order[2] : 0;
                $orderInfo['pid'] = $coupon['pid'];
                $orderInfo['type'] = $coupon['type'];
                $orderInfo['name'] = $goods['name'];
                $orderInfo['insert_time'] = date('Y-m-d H:i:s', $coupon['insert_time']);
                $orderInfo['qrcode_type'] = 1;
                $orderInfo['image'] = $goods['image'];
                $orderInfo['xc_images'] = $goods['xc_images'];
                return parent::returnMsg(1,$orderInfo,'获取成功');
                break;
        }
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


    //-------------------------新年上上签-----------------------------------------
    //摇一摇获取鼠卡及素材
    public function newyear(){
        $storeid = intval( input('param.storeid', 0) );//门店
        $uid = intval( input('param.uid', 0) );//美容师id
        if(empty($storeid) || empty($uid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        $today = date('Y-m-d');

        try {
            //检测当前用户当天是否获取到鼠卡
            $newyser = Db::name('blink_newyear_rats')
                ->where('refdate',$today)
                ->where('uid',$uid)
                ->find();
            if(empty($newyser)){
                //获取随机内容对应的鼠卡
                $rand_id = mt_rand(1,4);
                //添加记录
                $re = Db::name('blink_newyear_rats')->insert([
                    'uid'         => $uid,
                    'refdate'     => $today,
                    'rat_id'      => $rand_id,
                    'create_time' => time(),
                ]);
                //添加鼠卡
                $cardno = generate_promotion_code($uid,1,'',10)['0'];
                $insert = [
                    'pid' => 0,
                    'uid' => $uid,
                    'type' => 0,
                    'is_compose' => 0,
                    'blinkno' => '',
                    'cardno' => $cardno,
                    //'qrcode' => pickUpCode('blinkcard_'.$cardno),//核销卡片
                    'qrcode' => '',//使用时生成核销二维码
                    'thumb_id' => $rand_id,
                    'status' => 0,
                    'source' => 1,
                    'is_give' => 0,
                    'parent_owner' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                $res = Db::name('blink_order_box_card')->insert($insert);
                //获取随机内容
                $content = Db::name('blink_newyear_content')
                    ->where('rat_id',$rand_id)
                    ->where('status',0)
                    ->find();
                $is_win = 1;
            }else{
                /*//查询获取到的鼠卡
                $rats = Db::name('blink_box_card_image')
                    ->where('id',$newyser['rat_id'])
                    ->find();*/
                //获取随机内容
                $content = Db::name('blink_newyear_content')
                    ->where('status',1)
                    ->orderRaw('rand()')
                    ->find();
                $is_win = 0;
            }
            if(empty($content)){
                return parent::returnMsg(0,'','新年上上签不存在！');
            }
            $data['content'] = $content;
            //$data['rats'] = $rats;
            $data['is_win'] = $is_win;
            return parent::returnMsg(1,$data,'摇一摇成功');
        }catch (Exception $e){
            return parent::returnMsg(0,'','新年上上签出错：'.$e->getMessage());
        }
    }


    //--------------生成qrcode---------------
    public function qrcode(){
        $uid = input('param.uid',0);
        $id = input('param.id',0);
        if(empty($uid) || empty($id)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            //检测卡券是否存在
            $map['uid'] = $uid;
            $map['id'] = $id;
            $map['status'] = 0;
            $map['share_status'] = 0;
            $coupon = Db::name('blink_box_coupon_user')
                ->where($map)
                ->find();
            if(empty($coupon)){
                return parent::returnMsg(0,'','卡券不存在或已删除');
            }
            if(!empty($coupon['qrcode'])){
                $data['qrcode'] = $coupon['qrcode'];
                return parent::returnMsg(1,$data,'获取成功');
            }
            if($coupon['type'] == 2){
                $pick = pickUpCode('blinkcompose_'.$coupon['ticket_code'].'_'.$id);
            }else{
                $pick = pickUpCode('blinkcoupon_'.$coupon['ticket_code'].'_'.$id);
            }
            //更新
            $res = Db::name('blink_box_coupon_user')
                ->where($map)
                ->update([
                    'update_time' => time(),
                    'qrcode' => $pick,
                ]);
            $data['qrcode'] = $pick;
            if($res){
                return parent::returnMsg(1,$data,'获取成功');
            }else{
                return parent::returnMsg(0,'','网络错误');
            }

        }catch (Exception $e){
            return parent::returnMsg(0,'','操作失败：'.$e->getMessage());
        }
    }


    //-----------------其他测试-----------------------------
    public function makedata(){
        $uid = input('param.uid','');//用户ID
        $mobile = input('param.mobile','');//用户ID
        $num = input('param.num',1);
        $len = input('param.len',8);
        if($mobile){
            $uid = Db::table('ims_bj_shopn_member')->where('mobile',$mobile)->value('id');
        }
        //查询当前用户是否知否 1.10
        $pay_log = Db::name('pay_log')
            ->where('attach','=','blink')
            ->where('status','=','1')
            ->where('user_id','=',$uid)
            ->where('log_time','>=','2020-01-10 00:00:00')
            ->where('log_time','<=','2020-01-10 23:59:59')
            ->field('user_id,order_sn,out_trade_no,transaction_id,log_time,pay_amount')
            ->select();
        if($pay_log){
            $insert = [];
            $box = [];
            foreach ($pay_log as $k=>$val){
                $order_sn = $val['order_sn'];
                $order = Db::name('blink_order')
                    ->where('uid',$uid)
                    ->where('order_sn',$order_sn)
                    ->find();
                if(!empty($order)){
                    continue;
                }
                //查询用户门店
                $member = Db::table('ims_bj_shopn_member')
                    ->where('id',$uid)
                    ->field('id,staffid,originfid,storeid')
                    ->find();
                if($member['storeid'] == 1792){
                    $member['staffid'] = Db::table('ims_bj_shopn_member')
                        ->where('originfid',$uid)
                        ->value('id');
                }
                $pay_amount = $val['pay_amount'];
                $number = $pay_amount/ 20.2;
                //1.生成订单
                $insert[] = [
                    'storeid'        => $member['storeid'],
                    'fid'            => $member['staffid'],
                    'uid'            => $uid,
                    'goods_id'       => 188,
                    'order_price'    => $pay_amount * $number,
                    'pay_price'      => $pay_amount,
                    'pay_status'     => 1,
                    'num'            => $number,
                    'out_trade_no'   => $val['out_trade_no'],
                    'transaction_id' => $val['transaction_id'],
                    'insert_time'    => strtotime($val['log_time']),
                    'pay_time'       => strtotime($val['log_time']) +10,
                    'close_time'     => strtotime($val['log_time']) +7200,
                ];
                //2.生成盒子
                if($number == 1){
                    $box[] = [
                        'uid'         => $uid,
                        'blinkno'     => generate_promotion_code($uid,1,'',$len)['0'],
                        'goods_id'    => 188,
                        'price'       => 20.2,
                        'is_pay'      => 1,
                        'create_time' => strtotime($val['log_time']),
                        'update_time' => strtotime($val['log_time']),
                    ];
                }else{
                    for($i=0;$i<$number;$i++){
                        $box[] = [
                            'uid'         => $uid,
                            'blinkno'     => generate_promotion_code($uid,1,'',$len)['0'],
                            'goods_id'    => 188,
                            'price'       => 20.2,
                            'is_pay'      => 1,
                            'create_time' => strtotime($val['log_time']),
                            'update_time' => strtotime($val['log_time']),
                        ];
                    }
                }
            }
            $data['pay_log'] = $pay_log;
            $data['order'] = $insert;
            $data['box'] = $box;
        }
        $code = generate_promotion_code($uid,$num,'',$len);
        $coupon = [];
        foreach ($code as $v){
            $coupon[] = [
                'code'   => $v,
                'qrcode' => pickUpCode('blinkcoupon_'.$v)
            ];
        }
        $data['coupon'] = $coupon;

        return parent::returnMsg(1,$data,'生成序列号及二维码');
    }




    //批量处理未拆盲盒   27329-63  17253-46 17357-10 154624-9 158202-9  31243-9
    public function batch_take_blink(){
        set_time_limit(0);
        //获取未拆盲盒
        $param['status']  = 0;//未拆
        $param['is_give'] = 0;//未赠送
        $param['create_time'] = ['>=',1583855999];//未赠送
        //检测数据是否存在
        $lists = Db::name('blink_order_box')->where($param)->select();
        //var_dump($lists);exit;
        if(empty($lists)){
            return parent::returnMsg(0,'','盲盒不存在');
        }
        var_dump(count($lists));
        foreach ($lists as $k=>$val){
            if(in_array($val['uid'],[27329,17253,17357,154624,158202,31243,81360])){
                continue;
            }
            //检测是否已拆
            $param['id'] = $val['id'];
            $res = Db::name('blink_order_box')->where($param)->find();
            if(empty($res)){
                continue;
            }
            $date = 1585618619;//time();
            try {
                $box_data = [
                    'status'      => 1,
                    'close_time'  => 0,
                    'update_time' => $date
                ];
                //盲盒设置已拆
                Db::name('blink_order_box')->where($param)->update($box_data);
                echo '盲盒ID：'.$val['id'].PHP_EOL;
                //随机查询一条鼠卡图片ID
                $rat_thumb_id = $this->getRats();
                //生成一张鼠卡
                $cardno = generate_promotion_code($val['uid'], 1, '', 8)['0'];
                $ca_id = Db::name('blink_order_box_card')->insert([
                    'blinkno'     => $val['blinkno'],
                    'uid'         => $val['uid'],
                    'cardno'      => $cardno,
                    'qrcode'      => '',//使用时生成核销二维码
                    'thumb_id'    => $rat_thumb_id,
                    'status'      => 0,
                    'create_time' => $date,
                    'update_time' => $date,
                    'close_time'  => 0
                ], false, true);
                echo '鼠卡ID：'.$ca_id.PHP_EOL;
                //卡片数量减1
                $this->setDec('blink_box_card_number', 1);
                //对应鼠卡减1
                if ($this->getCacheString('blink_default_rats_' . $rat_thumb_id)) {
                    $this->setDec('blink_default_rats_' . $rat_thumb_id, 1);
                }
                Db::name('blink_box_config')->where('id', 1)->setDec('number', 1);

                //生成商品记录 以卡券记录形式展示
                $goods_id = $this->get_goods_reba_id($val['uid']);//$this->getGoods();//随机获取商品ID
                $_price = Db::name('goods')->where('id', $goods_id)->value('activity_price');
                $ticket_code = generate_promotion_code($goods_id, 1, '', 8)[0];
                //返回卡券商品ID
                $res = Db::name('blink_box_coupon_user')->insert([
                    'blinkno'      => $val['blinkno'],
                    'uid'          => $val['uid'],
                    'goods_id'     => $goods_id,
                    'price'        => $_price,
                    'par_value'    => $_price,
                    'ticket_code'  => $ticket_code,
                    'qrcode'       => '',//使用时生成核销二维码
                    'type'         => 0,//一般商品
                    'source'       => 0,//来源 0拆盲盒 1好友赠送 2好友助理 3合成卡片
                    'status'       => 0,//未核销
                    'is_batch1'    => 10,
                    'remark'       => '后台批量自动拆盲盒',//未赠送
                    'share_status' => 0,//未赠送
                    'insert_time'  => $date,
                    'update_time'  => $date,
                ], false, true);
                echo '优惠券商品ID：'.$res.PHP_EOL;
                echo '<hr/>';
                //商品库存减1
                if ($this->getCacheString('blink_box_goods_stock_' . $goods_id)) {
                    $this->setDec('blink_box_goods_stock_' . $goods_id, 1);
                }
                Db::name('goods')->where('id', $goods_id)->setDec('stock', 1);
            }catch (\Exception $e) {
                echo $e->getMessage().PHP_EOL;
            }
        }
    }
    public function batch_take_blink1(){
        set_time_limit(0);
        //获取未拆盲盒
        $param['status']  = 0;//未拆
        $param['is_give'] = 0;//未赠送
        //以用户分组查询是否存在未拆盲盒
        $lists = Db::name('blink_order_box')
            ->where($param)
            ->field('uid,count(uid) count,status,is_give')
            ->group('uid')
            ->order('count','desc')
            ->select();
        var_dump($lists);

        $param1['goods_cate'] = 11;
        $param1['deputy_cate'] = 1;
        $param1['stock'] = ['gt',0];//库存大于0
        $goods_list1 = GoodsModel::where($param1)
            ->order('stock','desc')
            ->column('id');
        var_dump($goods_list1);
        if(empty($lists)){
            return parent::returnMsg(0,'','盲盒不存在');
        }
        $date = 1585618620;//time();
        foreach ($lists as $k=>$val){
            //如果count大于9 9大产品一次添加
            if($val['count'] <= 8){
                continue;
            }
            //生成数组
            $arr = [];
            for($i=0; $i<$val['count']; $i++){
                if($i>=9){
                    $a = $i % 9;
                    $arr[] = $goods_list1[$a];
                }else{
                    $arr[] = $goods_list1[$i];
                }
            }
            if(empty($arr)){
                continue;
            }
            var_dump($val['count'],$arr);
            //检测是否已拆
            $param['uid'] = $val['uid'];
            $res = Db::name('blink_order_box')->where($param)->select();
            if(empty($res)){
                continue;
            }


            foreach ($res as $kk=>$vv){
                //盲盒设置已拆
                $param['id'] = $vv['id'];
                Db::name('blink_order_box')->where($param)->update([
                    'status'      => 1,
                    'close_time'  => 0,
                    'update_time' => $date
                ]);
                echo '盲盒ID：'.$vv['id'].PHP_EOL;

                //随机查询一条鼠卡图片ID
                $rat_thumb_id = $this->getRats();
                //生成一张鼠卡
                $cardno = generate_promotion_code($vv['uid'], 1, '', 8)['0'];
                $ca_id = Db::name('blink_order_box_card')->insert([
                    'blinkno'     => $vv['blinkno'],
                    'uid'         => $vv['uid'],
                    'cardno'      => $cardno,
                    'qrcode'      => '',//使用时生成核销二维码
                    'thumb_id'    => $rat_thumb_id,
                    'status'      => 0,
                    'create_time' => $date,
                    'update_time' => $date,
                    'close_time'  => 0
                ], false, true);
                echo '鼠卡ID：'.$ca_id.PHP_EOL;
                //卡片数量减1
                $this->setDec('blink_box_card_number', 1);
                //对应鼠卡减1
                if ($this->getCacheString('blink_default_rats_' . $rat_thumb_id)) {
                    $this->setDec('blink_default_rats_' . $rat_thumb_id, 1);
                }
                Db::name('blink_box_config')->where('id', 1)->setDec('number', 1);


                //生成商品记录 以卡券记录形式展示
                $goods_id = $arr[$kk];//$this->getGoods();//随机获取商品ID
                $_price = Db::name('goods')->where('id', $goods_id)->value('activity_price');
                $ticket_code = generate_promotion_code($goods_id, 1, '', 8)[0];

                //返回卡券商品ID
                $res = Db::name('blink_box_coupon_user')->insert([
                    'blinkno'      => $vv['blinkno'],
                    'uid'          => $vv['uid'],
                    'goods_id'     => $goods_id,
                    'price'        => $_price,
                    'par_value'    => $_price,
                    'ticket_code'  => $ticket_code,
                    'qrcode'       => '',//使用时生成核销二维码
                    'type'         => 0,//一般商品
                    'source'       => 0,//来源 0拆盲盒 1好友赠送 2好友助理 3合成卡片
                    'status'       => 0,//未核销
                    'is_batch1'    => 10,
                    'remark'       => '后台批量自动拆盲盒',//未赠送
                    'share_status' => 0,//未赠送
                    'insert_time'  => $date,
                    'update_time'  => $date,
                ], false, true);
                echo '优惠券商品ID：'.$res.PHP_EOL;
                echo '<hr/>';
                //商品库存减1
                if ($this->getCacheString('blink_box_goods_stock_' . $goods_id)) {
                    $this->setDec('blink_box_goods_stock_' . $goods_id, 1);
                }
                Db::name('goods')->where('id', $goods_id)->setDec('stock', 1);
            }
        }
    }
}