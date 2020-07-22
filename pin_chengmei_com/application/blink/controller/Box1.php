<?php
/**
 * 盲盒活动及新年上上签
 */

namespace app\blink\controller;

use app\blink\model\BlinkOrderModel;
use think\Cache;
use think\Db;
use think\Exception;
use weixin\BlinkPay;
use app\blink\model\BlinkBoxGoodsModel;
use app\blink\model\BlinkBoxRecordModel;
use app\blink\model\BlinkCardImageModel;
use app\blink\model\BlinkCardModel;
use app\blink\model\BlinkCardRecordModel;
use app\blink\model\BlinkCouponRecordModel;
use app\blink\model\MemberModel;
use app\blink\model\GoodsModel;
use app\blink\model\BlinkOrderBoxModel;
use app\blink\model\BlinkBoxCouponUserModel;

/**
 * swagger: 盲盒鼠卡
 */
class Box1 extends Base
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
            return 1;
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
        if($this->config['end_time'] < time()){
            echo json_encode(array(
                'code' => 0,
                'data' => '',
                'msg'  => '活动已结束，结束时间：'.date('Y-m-d H:i:s',$this->config['end_time'])
            ));
            exit;
        }
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
            $rats = BlinkCardImageModel::getAllRatsList($ppp);
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
        $blink_box_goods = BlinkBoxGoodsModel::getBoxInAllGoods($param);
        if(!empty($blink_box_goods)){
            //商品数据
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
        $re = Db::table('ims_bwk_branch')->where('id',$storeid)->value('is_blink');
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
            $param['goods_cate'] = 11;
            $param['deputy_cate'] = 1;
            $param['stock'] = ['gt',0];//库存大于0
            $goods_list = GoodsModel::where($param)->column('id,name,image,xc_images,stock,activity_price,price');
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
            $map['type']   = ['=',0];
            $map['cid']    = ['=',1];
            $map['number'] = ['gt',0];
            $rats = BlinkCardImageModel::getAllRats($map);
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
        if(empty($mobile)){
            return $this->returnMsg(0,'','参数缺失');
        }
        try {
            $res = Db::name('blink_wx_user')->where('mobile',$mobile)->find();
            if(empty($res)){
                return $this->returnMsg(0,'','当前手机号的用户不存在');
            }
            Db::name('blink_wx_user')->where('mobile',$mobile)->update([
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
        $uid     = intval( input('param.uid', 0) );//门店
        if(empty($storeid)||empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        //检测门店是否开启活动
        $this->checkStore();

        $param['bg.type']       = 0;
        $param['g.goods_cate']  = 11;
        $param['g.deputy_cate'] = 1;
        $blink_box_goods = BlinkBoxGoodsModel::getBoxInAllGoods($param);
        // 随机选取9个产品
        if(!empty($blink_box_goods)){
            foreach ($blink_box_goods as $k=>$val){
                $images = $val['images'];
                if(!empty($images)){
                    $images = explode(',',$images);
                    $image  = $images['0'];
                }
                $blink_box_goods[$k]['price_image'] = $image;
            }
        }

        $count = count($blink_box_goods);
        if($count < 9){
            for($i=0;$i<9-$count;$i++){
                $goods_id          = $this->getGoods($blink_box_goods);
                $param['g.id']     = $goods_id;
                $blink_box_goods[] = BlinkBoxGoodsModel::getBoxInGoodsInfo($param);
            }
        }
        $data['list'] = $blink_box_goods;
        $data['info'] = Db::name('goods')
            ->field('id,name,image,price,activity_price')
            ->where('goods_cate',11)
            ->where('deputy_cate',0) //0盲盒产品 1 拆盒产品 2 清洁卡及礼包卡
            ->find();
        //检测用户是否有3个好友购买
        //1.查询清洁卡数量type=1
        $p['pid']  = $uid;
        $p['type'] = 1;
        $number = BlinkBoxCouponUserModel::getCurrentUserCouponsCount($p);

        //2查询当前用户下的子集
        $a['pid']           = $uid;
        $a['activity_flag'] = 9999;//新客
        $members = Db::table('ims_bj_shopn_member')->where($a)->column('id');
        if(!empty($members)){
            //3检测集合中的购买用户数
            $orders = Db::name('blink_order')->where('uid','in',$members)->count('DISTINCT uid');
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
                            'pid'          => 0,
                            'uid'          => $uid,
                            'ticket_code'  => $cardno,
                            'status'       => 0,
                            'type'         => 1,
                            'source'       => 2,
                            'goods_id'     => $this->config['share_goods'],
                            'price'        => $_price,
                            'qrcode'       => '',//使用时生成核销二维码
                            'share_status' => 0,
                            'insert_time'  => time(),
                            'update_time'  => time(),
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
        $storeid  = intval( input('param.storeid', 0) );//门店
        $goods_id = intval( input('param.goods_id', 0) );//商品id
        $fid      = intval( input('param.fid', 0) );//美容师id
        $uid      = intval( input('param.uid', 0) );//发起人id
        $num      = intval( input('param.num', 1) );//购买数量
        $price    = trim( input('param.price', '20.2') );//价格
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

        //检测用户手机号是否为空
        $mobile = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
        if(empty($mobile)){
            logs(date('Y-m-d H:i:s').' 用户没有手机号 参数'.json_encode(input('param.')),'nomobile');
            return parent::returnMsg(0,'','参数错误！！！');
        }
        //生成订单
        $order_sn = createOrderSn();
        $pay_price = $price * $num;
        $orders = [
            'storeid'     => $storeid,
            'uid'         => $uid,
            'goods_id'    => $goods_id,
            'fid'         => $fid,
            'order_sn'    => $order_sn,
            'num'         => $num,
            'status'      => 1,
            'pay_status'  => 0,//未支付
            'order_price' => $list['price'] * $num,
            'pay_price'   => $pay_price,
            'insert_time' => $time,//发起时间
            'close_time'  => $time + 86300,//超时时间
            'pick_code'   => pickUpCode('blinkbox_'.$order_sn)//订单二维码
        ];
        $order_id = Db::name('blink_order')->insert($orders,false,true);
        $market_price = $list['price'];
        $total_fee = $pay_price;//商品优惠价*数量

        //订单添加成功
        if(!empty($order_id)){
            $data                 = config('wx_blink_pay');
            $data['order_id']     = $order_id;
            $data['user_id']      = $uid;
            $data['attach']       = 'blink';
            $data['order_sn']     =  $order_sn;
            $data['num']          =  $num;
            $data['market_price'] = $market_price;//订单金额
            $data['price']        = $price;//待支付金额
            $data['total_fee']    = $total_fee;
            $data['body']         = $list['name'];
            logs(date('Y-m-d H:i:s') . "：2 " . json_encode($data), 'aaa');
            return parent::returnMsg(1,$data,'订单提交成功');
        }else{
            return parent::returnMsg(0,'','参数缺失，订单提交失败');
        }
    }
    //微信预支付
    public function wxPay(){
        $wxpay_config = config('wx_blink_pay');
        $appid        = $wxpay_config['appid'];
        $mch_id       = $wxpay_config['mch_id'];
        $key          = $wxpay_config['api_key'];
        //获取前台参数
        $token        = input('param.token');
        $buyUser      = Db::name('blink_wx_user')->where('token', $token)->find();
        $openid       = $buyUser['open_id'];//用户openID
        $body         = input('param.body');
        $user_id      = input('param.user_id');//用户id
        $out_trade_no = $mch_id. time().$user_id;
        $attach       = input('param.attach','blink');
        $total        = input('param.total_fee');
        $total_fee    = floatval($total*100);//价格转化为分x100
        $order_sn     = input('param.order_sn');//订单号
        $mobile       = $buyUser['mobile'];//用户手机
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
                'user_id'      => $user_id,
                'order_sn'     => $order_sn,
                'mobile'       => $mobile,
                'out_trade_no' => $out_trade_no,
                'status'       => 0,
                'attach'       => $attach,
                'pay_amount'   => $total,
                'prepay_id'    => $prepay_id,
                'log_time'     => date('Y-m-d H:i:s')
            );
            Db::name('pay_log')->insert($data);

            return parent::returnMsg(1,$return,'支付参数获取成功');
        }catch (\Exception $e){
            return parent::returnMsg(0,'','支付参数获取失败'.$e->getMessage());
        }
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
        $param['goods_cate']  = 11;
        $param['deputy_cate'] = 1;
        $param['stock']       = ['gt',0];//库存大于0
        $goods_list = GoodsModel::where($param)
            ->order('stock','desc')
            ->field('id,name,stock')
            ->select();
        if(empty($goods_list)){
            return parent::returnMsg(0,'','单品库存不足');
        }
        $goods = [];
        foreach ($goods_list as $k=>$val){
            $goods[$k]['goods_id'] = $val['id'];
            $goods[$k]['stock']    = $val['stock'];
            $map['user.uid']       = $uid;
            $map['user.goods_id']  = $val['id'];
            $map['user.type']      = 0;
            $goods[$k]['count']    = BlinkBoxCouponUserModel::getCurrentUserCouponsCount($map);
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
            $list  = BlinkOrderBoxModel::getUnremovedBlinkBoxList($param ,$page ,$limit);
            $total = BlinkOrderBoxModel::getUnremovedBlinkBoxCount($param);
        }else{//一拆盲盒 关联 盒中商品及鼠卡
            $param['ca.status'] = 1;
            $list  = BlinkOrderBoxModel::getRemovedBlinkBoxList($param ,$page ,$limit);
            $total = BlinkOrderBoxModel::getRemovedBlinkBoxCount($param);
        }
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
            $param['is_give'] = 0;//未赠送
            //检测数据是否存在
            $info = BlinkOrderBoxModel::getBlinkBox($param);
            if(empty($info)){
                return parent::returnMsg(0,'','盲盒不存在');
            }
            if($info['status'] == 1){
                return parent::returnMsg(0,'','该盲盒已拆开');
            }
            //拆盒
            Db::name('blink_order_box')->where($param)->update([
                'status'      => 1,
                'close_time'  => 0,
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
        try{
            //检测手机号使用者
            $storeInfo = MemberModel::getUserAccordToMobile($mobile);

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
            $info = BlinkOrderBoxModel::getBlinkBox($param);
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
                $param['id'] = $info['id'];//未使用
                //更新盒子记录
                Db::name('blink_order_box')
                    ->where($param)
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
        $boxs = BlinkOrderBoxModel::getBlinkBox($p);
        if(empty($boxs)){
            return parent::returnMsg(2,input('param.'),'当前行为已失效!');
        }
        //检测赠送记录是否存在
        $br['id'] = $boxs['give_id'];
        $info = BlinkBoxRecordModel::getBoxRecord($br);
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
        $member = MemberModel::getUserAccordToGiveUserID($info['give_uid']);
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
        $member['_mobile']     = $info['mobile'];
        $member['give_advice'] = $info['give_advice'];
        $data['member'] = $member;
        //查询该盲盒关联的商品
        $mapa['bob.blinkno'] = $info['blinkno'];
        $mapa['bob.uid']     = $info['give_uid'];
        if(!empty($blink_id)){
            $mapa['bob.id']     = $blink_id;
        }
        $goods = BlinkOrderBoxModel::getBlinkBoxGoodsInfo($mapa);
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
        try {
            //检测赠送记录是否存在
            $info = BlinkBoxRecordModel::getBoxRecord(['id'=>$give_id]);
            if(empty($info)){
                return parent::returnMsg(0,'','盲盒赠送记录不存在');
            }
            //检测接收人手机号 检测赠送人和接收人是否是同一人
            if($mobile != $info['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
            }
            //检测盒子赠送人门店
            $members = MemberModel::getUserAccordToGiveUserID($info['give_uid']);
            if(empty($members)){
                return parent::returnMsg(0,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user = MemberModel::getUserAccordToGiveUserID($uid);
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            $msg = '';
            if(in_array($current_user['sign'],$this->sign) && in_array($members['sign'],$this->sign)){
                $msg = ",接收人的引领人为{$current_user['pid']}";
            }else{
                if($storeid != $members['storeid']){
                    return parent::returnMsg(0,'','暂不支持跨门店操作哦！');
                }
            }
            //检测赠送人的盲盒是否被领取
            $bbr['blinkno'] = $blinkno;
            $bbr['uid']     = $info['give_uid'];
            $bbr['give_id'] = $give_id;
            $blink = BlinkOrderBoxModel::getBlinkBox($bbr);
            if(empty($blink)){
                return parent::returnMsg(0,'','当前盲盒已被领取！');
            }
            if($blink['is_give'] == 0 || $blink['is_give'] == 1){
                return parent::returnMsg(0,'','当前盲盒已被领取或未分享！');
            }
            //更新盒子记录从属于当前盒子赠送人盒子记录
            Db::name('blink_order_box')
                ->where($bbr)
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
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 0,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $blinkno,
                'desc'    => "{$blinkno} 被 {$current_user['title']}（{$current_user['sign']}）门店手机号为 {$mobile} 的用户接收".$msg,
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
            $members = MemberModel::getUserAccordToGiveUserID($info['give_uid']);
            if(empty($members)){
                return parent::returnMsg(2,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user = MemberModel::getUserAccordToGiveUserID($uid);
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
            $bbr['blinkno'] = $info['blinkno'];
            $bbr['uid']     = $info['give_uid'];
            $bbr['give_id'] = $give_id;
            $blink = BlinkOrderBoxModel::getBlinkBox($bbr);
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
                ->where($bbr)
                ->update([
                    //'give_id' => 0,
                    'close_time' => 0,
                    'is_give' => 0,
                    'update_time' => time(),
                ]);
            if($res){
                //添加分享日志
                $share = [
                    'uid'     => $info['give_uid'],//当前用户
                    'receive' => $uid,//接收用户
                    'type'    => 0,//记录类型 0 盲盒 1鼠卡 2卡券
                    'code'    => $info['blinkno'],
                    'desc'    => "{$info['blinkno']} 被 {$current_user['title']}（{$current_user['sign']}）门店手机号为 {$info['mobile']} 的用户拒绝 {$msg}，已回退",
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
        $rmap['cid'] = 1;
        $card = BlinkCardImageModel::getAllRatsList($rmap);

        if(!empty($card)){
            $param['is_give'] = 0;//未赠送
            $param['uid'] = $user_id;
            $param['is_compose'] = 0;//是否合成 0 未合成 1已合成
            foreach ($card as $k=>$val){
                $id = $val['id'];//卡片ID
                $param['thumb_id'] = $id;
                //查询当前用户是否有该卡片
                $count =  BlinkCardModel::getCurrentRatIdCount($param);

                $card[$k]['count'] = $count ?: 0;
                $card[$k]['cardno'] = BlinkCardModel::getRatCardNoAtRandom($param);
            }
        }
        $data['card'] = $card;
        //检测当前用户是否凑够五张鼠卡
        $ids_map['cid'] = 1;
        $ids_map['type'] = 0;//卡片类型 0普通鼠卡 1合成鼠卡
        $thumb_ids = BlinkCardImageModel::getAllRatsIDs($ids_map);//五张鼠卡
        $a['thumb_id'] = ['in',$thumb_ids];
        $a['uid'] = ['=',$user_id];
        $a['is_give'] = ['=',0];
        $a['is_compose'] = ['=',0];
        $is_compose = BlinkCardModel::getCurrentRatComposeCount($a);
        //能够合成
        $data['is_compose'] = $is_compose == 5 ? 1 : 0;

        //获取用户卡片总数量
        $thumb_ids[] = 6;
        $number = BlinkCardModel::getCurrentRatIdCount($a);
        $data['number'] = $number;
        return parent::returnMsg(1,$data,'鼠卡获取成功');
    }
    //赠送卡片
    public function give_card(){
        $cardno = intval( input('param.id', 0) );//鼠卡卡号
        $uid = intval( input('param.uid', 0) );//当前用户ID
        $remark = trim(input('param.remark',''));//好友赠言
        $mobile = trim(input('param.mobile',''));//接受人手机号
        if(empty($cardno) || empty($uid) || empty($mobile)){
            return parent::returnMsg(0,'','参数缺失!');
        }
        try{
            //检测手机号使用者
            $storeInfo =  MemberModel::getUserAccordToMobile($mobile);
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','您要赠送的用户不存在！');
            }
            if($storeInfo['id'] == $uid){
                return parent::returnMsg(0,'','不能赠送给自己');
            }
            //检测卡片数据是否存在  随机查询一条数据
            $param['uid'] = $uid;
            $param['cardno'] = $cardno;
            $param['is_compose'] = 0;//未合成卡
            $param['is_give'] = 0;//未赠送
            $info = BlinkCardModel::getCurrentCardInfo($param);

            if(empty($info)){
                return parent::returnMsg(0,'','当前鼠卡已合成或已赠送');
            }
            //生成卡片赠送记录
            $time = time();
            $give_id = Db::name('blink_card_give_record')->insert([
                'give_uid'    => $info['uid'],
                'blinkno'     => $info['blinkno'],
                'cardno'      => $info['cardno'],
                'pid'         => 0,
                'mobile'      => $mobile,
                'give_advice' => $remark,
                'create_time' => $time ,
                'update_time' => $time ,
                'close_time'  => $time + 86300,
            ],false,true);
            if(!empty($give_id)){
                //更新盒子中卡片记录
                $PPP['id']      = $info['id'];
                $PPP['uid']     = $uid;
                $PPP['blinkno'] = $info['blinkno'];
                Db::name('blink_order_box_card')
                    ->where($PPP)
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
                    'code'    => $cardno,
                    'desc'    => "{$cardno} 转赠给{$storeInfo['title']}（{$storeInfo['sign']}）门店手机号为 {$mobile}的用户",
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
        $card = BlinkCardModel::getCurrentCardInfo($param);
        if(empty($card)){
            return parent::returnMsg(2,'','当前行为已失效了!');
        }
        if($card['is_compose'] == 1){
            $data['click'] = 0;
        }
        //检测卡片赠送记录是否存在
        $info = BlinkCardRecordModel::getCardRecordInfo($card['give_id']);
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
        $member = MemberModel::getUserAccordToGiveUserID($info['give_uid']);
        $member['give_advice'] = $info['give_advice'];
        $member['_mobile']     = $info['mobile'];
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
        $P['cid'] = 1;
        $P['id']  = $card['thumb_id'];
        $cardInfo = BlinkCardImageModel::getRatInfo($P);
        $card['card_name']  = $cardInfo['name'];
        $card['card_thumb'] = $cardInfo['thumb'];
        $card['intro']      = $cardInfo['intro'];

        $data['member'] = $member;
        $data['info'] = $card;
        $data['card'] = $info;
        return parent::returnMsg(1,$data,'鼠卡数据查询成功');
    }
    //获取卡片
    public function set_accept_card(){
        $give_id = intval( input('param.give_id', 0) );//卡片赠送记录
        $blinkno = trim( input('param.blinkno', 0) );//鼠卡所属盒子编号
        $cardno = trim( input('param.cardno', 0) );//鼠卡编号
        $uid = intval( input('param.uid', 0) );//当前用户
        $storeid = intval( input('param.storeid', 0) );//当前用户门店
        $mobile =  input('param.mobile', '') ;//当前接收人手机号
        if(empty($give_id) || empty($cardno) || empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            //检测卡片赠送记录是否存在
            $info = BlinkCardRecordModel::getCardRecordInfo($give_id);
            if(empty($info)){
                return parent::returnMsg(0,'','鼠卡赠送记录不存在');
            }
            ////检测接收人手机号 检测赠送人和接收人是否是同一人
            if($mobile != $info['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
            }
            //检测鼠卡赠送人门店
            $storeInfo = MemberModel::getUserAccordToGiveUserID($info['give_uid']);
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user = MemberModel::getUserAccordToGiveUserID($uid);

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
            $aaa['uid']     = $info['give_uid'];
            $aaa['give_id'] = $give_id;
            $aaa['cardno']  = $cardno;
            $blink = BlinkCardModel::getCurrentCardInfo($aaa);
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
                ->where($aaa)
                ->update([
                    'is_give'     => 1,
                    'status'      => 0,
                    'update_time' => time(),
                    'close_time'  => 0,
                ]);
            //为接受人添加盲盒卡片记录
            Db::name('blink_order_box_card')->insert([
                'pid'          => $blink['id'],
                'uid'          => $uid,
                'type'         => 0,
                'is_compose'   => 0,
                'blinkno'      => $blink['blinkno'],
                'cardno'       => $blink['cardno'],
                //'qrcode'     => pickUpCode('blinkcard_'.$blink['cardno']),//核销卡片
                'qrcode'       => '',//使用时生成核销二维码
                'thumb_id'     => $blink['thumb_id'],
                'status'       => 0,
                'source'       => $blink['source'],
                'is_give'      => 0,
                'parent_owner' => $info['give_uid'],
                'create_time'  => time(),
                'update_time'  => time(),
            ]);

            //获取当前用户的昵称
            sendMessage($storeInfo['mobile'],['nickname'=>$current_user['nickname']],config('blink_sms_id'));
            //添加分享日志
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $cardno,
                'desc'    => "{$cardno} 被 {$current_user['title']}（{$current_user['sign']}）门店手机号为 {$mobile} 的用户接收".$msg,
            ];
            $this->setShareLogs($share);
            return parent::returnMsg(1,$current_user,'鼠卡接受成功');
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
            $info = BlinkCardRecordModel::getCardRecordInfo($give_id);
            if(empty($info)){
                return parent::returnMsg(2,'','鼠卡赠送记录已失效或已删除!');
            }
            if($info['give_uid'] == $uid){
                return parent::returnMsg(2,'','抱歉，它的主人不是您哦，暂不支持拒绝哦！！');
            }
            //检测赠送人门店
            $members = MemberModel::getUserAccordToGiveUserID($info['give_uid']);
            if(empty($members)){
                return parent::returnMsg(2,'','赠送人不存在或已删除！');
            }

            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user = MemberModel::getUserAccordToGiveUserID($uid);
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
            $p['uid']     = $info['give_uid'];
            $p['give_id'] = $give_id;
            $p['cardno']  = $info['cardno'];
            $blink = BlinkCardModel::getCurrentCardInfo($p);
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
                ->where($p)
                ->update([
                    //'give_id'   => 0,
                    'close_time'  => 0,
                    'is_give'     => 0,
                    'update_time' => time(),
                ]);
            //添加分享日志
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $info['cardno'],
                'desc'    => "{$info['cardno']} 被 {$current_user['title']}（{$current_user['sign']}）门店手机号为 {$info['mobile']} 的用户拒绝{$msg}，已回退",
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
            //获取卡片原始数据ID集合
            $map['cid']  = 1;
            $map['type'] = 0;//普通卡
            $images = BlinkCardImageModel::getAllRatsIDs($map);

            //获取当前用户卡片数据
            $where['uid']      = ['=',$uid];
            $where['thumb_id'] = ['in',$images];
            $where['is_give'] = ['=',0];//未赠送
            $where['is_compose'] = ['=',0];//未合成卡
            $ids = BlinkCardModel::getCurrentUserRatsGroupID($where);
            //可以合成
            if(count($ids) == 5){
                //1修改合成记录 卡片已合成
                Db::name('blink_order_box_card')
                    ->where('id','in',$ids)
                    ->update([
                        'is_compose'  => 1,
                        'update_time' => time(),
                    ]);
                //生成一条卡券记录
                $cardno = generate_promotion_code($uid,1,'',8)[0];
                //大礼包
                //4添加合成卡
                $b['cid']  = 1;
                $b['type'] = 1;
                $rat_id = BlinkCardImageModel::getRatID($b);
                Db::name('blink_order_box_card')->insert([
                    'uid'         => $uid,
                    'thumb_id'    => $rat_id,
                    'cardno'      => $cardno,
                    'is_compose'  => 0,
                    'is_give'     => 0,
                    'type'        => 1,
                    'qrcode'      => '',//pickUpCode('blinkcard_'.$cardno),
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
                            'uid'          => $uid,
                            'blinkno'      => $cardno,//合成卡无盲盒编号
                            'ticket_code'  => $_cardno,
                            'num'          => $val['num'],
                            'par_value'    => $val['activity_price'],
                            'qrcode'       => '',//使用时生成核销二维码
                            'goods_id'     => $val['goods_id'],
                            'price'        => 2020,
                            'parent_owner' => 0,//接收人ID
                            'type'         => 2,//礼包合成卡
                            'status'       => 0,
                            'source'       => 3,//来源 0拆盲盒 1好友赠送 2好友助理 3合成卡片
                            'share_status' => 0,
                            'insert_time'  => time(),
                            'update_time'  => time(),
                        ];
                        $j++;
                    }
                }
                if(!empty($insert)){
                    Db::name('blink_box_coupon_user')->insertAll($insert);
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
        $uid  = intval(input('param.uid',0));//登陆用户ID
        $page  = input('param.page',1);//分页
        if(empty($uid)){
            return parent::returnMsg(0,'','参数缺失');
        }
        $limit = 10;
        //获取我拆开的盲盒中的商品
        $map['user.uid'] = $uid;
        $list = BlinkBoxCouponUserModel::getCurrentUserCoupons($map ,$page ,$limit);

        $total = BlinkBoxCouponUserModel::getCurrentUserCouponsCount($map);
        $data = [
            'total' => $total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
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
            $storeInfo = MemberModel::getUserAccordToMobile($mobile);
            if(empty($storeInfo)){
                return parent::returnMsg(0,'','您要赠送的用户不存在！');
            }
            logs(date('Y-m-d H:i:s').' 1 '.json_encode($storeInfo),'ttt');
            if($storeInfo['id'] == $uid){
                return parent::returnMsg(0,'','不能赠送给自己');
            }
            //检测当前卡券是否存在 未分享
            $map['uid']          = $uid;
            $map['ticket_code']  = $ticket_code;
            $map['share_status'] = 0;//未分享
            $map['status']       = 0;//未使用
            $map['type']         = $type;
            $coupon = BlinkBoxCouponUserModel::getCoupon($map);
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
                $map1['uid']          = $uid;
                $map1['ticket_code']  = $ticket_code;
                $map1['type']         = $type;
                $map1['id']           = $coupon['id'];
                $res = Db::name('blink_box_coupon_user')
                    ->where($map1)
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
        }
        //查询赠送人卡片商品
        $p['ticket_code'] = $give_num;
        $p['uid'] = $give_userid;
        if(!empty($coupon_id)){
            $p['id'] = $coupon_id;
        }
        $coupon = BlinkBoxCouponUserModel::getCoupon($p);;
        if(empty($coupon)){
            return parent::returnMsg(2,'','当前卡券商品已删除');
        }

        //检测卡片赠送记录是否存在
        $mr['id'] = $coupon['give_id'];
        $info = BlinkCouponRecordModel::getCouponRecord($mr);
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

        $goods = GoodsModel::getGoodsInfoID($coupon['goods_id']);
        $coupon['goods_name']  = $goods['name'];
        $coupon['goods_image'] = $goods['image'];
        $coupon['intro']       = $goods['intro'];
        $data['info'] = $coupon;
        //赠送人信息
        $member = MemberModel::getUserAccordToGiveUserID($give_userid);
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
            $info = BlinkCouponRecordModel::getCouponRecord(['id'=>$give_id]);
            if(empty($info)){
                return parent::returnMsg(0,'','卡券商品赠送记录不存在');
            }
            //检测接收人手机号 检测赠送人和接收人是否是同一人
            if($mobile != $info['mobile'] || $info['give_uid'] == $uid){
                return parent::returnMsg(0,'','抱歉，它的主人不是您哦，暂不支持领取哦！');
            }
            //检测赠送人门店及信息
            $field = 'm.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign';
            $members = MemberModel::getUserAccordToGiveUserID($info['give_uid'],$field);
            if(empty($members)){
                return parent::returnMsg(0,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user = MemberModel::getUserAccordToGiveUserID($uid,$field);
            if(empty($current_user)){
                return parent::returnMsg(0,'','当前用户不存在或已删除！');
            }
            $msg = '';
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
            $map['ticket_code'] = $ticket_code;
            $map['uid']         = $info['give_uid'];
            $map['give_id']     = $give_id;
            $blink = BlinkBoxCouponUserModel::getCoupon($map);
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
                ->where($map)
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

            //获取当前用户的昵称
            sendMessage($members['mobile'],['nickname'=>$current_user['nickname']],config('blink_sms_id'));

            //添加分享日志
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 2,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $ticket_code,
                'desc'    => "{$ticket_code} 被 {$current_user['title']}（{$current_user['sign']}）门店手机号为 {$mobile} 的用户接收".$msg,
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
            $info = BlinkCouponRecordModel::getCouponRecord($give_id);
            if(empty($info)){
                return parent::returnMsg(2,'','当前行为已失效!');
            }
            //检测赠送人门店
            $field = 'm.id,m.storeid,m.mobile,m.staffid,m.pid,m.originfid,bwk.title,bwk.sign';
            $members = MemberModel::getUserAccordToGiveUserID($info['give_uid'],$field);

            if(empty($members)){
                return parent::returnMsg(2,'','赠送人不存在或已删除！');
            }
            //检测当前用户是否是1792门店 并判断赠送人是否是当前用户的引领人 是 可以接受
            $current_user = MemberModel::getUserAccordToGiveUserID($uid,$field);
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
            $cp['ticket_code'] = $info['code'];
            $cp['uid']         = $info['give_uid'];
            $cp['give_id']     = $give_id;
            $blink = BlinkBoxCouponUserModel::getCoupon($cp);
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
                ->where($cp)
                ->update([
                    //'give_id' => 0,
                    'share_time' => 0,
                    'share_status' => 0,
                    'update_time' => time(),
                ]);

            //添加分享日志
            $share = [
                'uid'     => $info['give_uid'],//当前用户
                'receive' => $uid,//接收用户
                'type'    => 1,//记录类型 0 盲盒 1鼠卡 2卡券
                'code'    => $info['code'],
                'desc'    => "{$info['code']} 被 {$current_user['title']}（{$current_user['sign']}）门店手机号为 {$info['mobile']} 的用户拒绝 {$msg}，已回退",
            ];
            $this->setShareLogs($share);
            return parent::returnMsg(2,'','当前卡券商品已返回到赠送人手中');
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
    //美容师业绩列表
    public function achievement(){
        $storeid  = intval( input('param.storeid', 0) );//门店
        $fid      = intval( input('param.fid', 0) );//美容师id
        $identity = intval( input('param.identity', 1) );//美容师id
        $page     = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        $limit = 10;
        if($identity == 1){
            //美容师
            //查询客户信息
            $map['m.storeid']    = $storeid;
            $map['m.staffid']    = $fid;
            $map['m.id']         = ['<>',$fid];
            $map1['m.originfid'] = $fid;
            $total = BlinkOrderModel::getAllBeautyCustomerCount($map,$map1);
            $list  = BlinkOrderModel::getAllBeautyCustomers($map,$map1,$page,$limit);
        }else{
            $fids = Db::table('ims_bj_shopn_member')
                ->where("code <> '' and id=staffid and storeid={$storeid} ")
                ->column('id');//检测美容师
            //查询客户信息
            $map['m.storeid']    = $storeid;
            $map['m.id']         = ['<>',$fid];
            $map1['m.originfid'] = ['in',$fids];

            $total = BlinkOrderModel::getAllBeautyCustomerCount($map,$map1);
            $list  = BlinkOrderModel::getAllBeautyCustomers($map,$map1,$page,$limit);
        }
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                if($storeid == 1792){
                    //查询当前用户引领人信息
                    $info = MemberModel::getUserAccordToGiveUserID($val['originfid']);
                    $origin = [
                        'id'       => !empty($info) ? $info['id'] : '',
                        'storeid'  => !empty($info) ? $info['storeid'] : '',
                        'code'     => !empty($info) ? $info['code'] : '',
                        'staffid'  => !empty($info) ? $info['staffid'] : '',
                        'realname' => !empty($info) ? ($info['nickname'] ?: $info['realname']) : '',
                        'mobile'   => !empty($info) ? $info['mobile'] : '',
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
        $storeid  = intval( input('param.storeid', 0) );//门店
        $fid      = intval( input('param.fid', 0) );//美容师id
        $identity = intval( input('param.identity', 1) );//是否是老板
        if(empty($storeid) || empty($fid)) {
            return parent::returnMsg(0,'','参数缺失');
        }
        try {
            if($identity == 1){
                //美容师
                //检测当前美容师下的用户
                $map['storeid']    = $storeid;
                $map['staffid']    = $fid;
                $map1['originfid'] = $fid;
                //正常的美容师下的客户
                $mids = Db::table('ims_bj_shopn_member')->where($map)->whereOr($map1)->column('id');//美容师下的用户 'id,storeid,realname,staffid'
            }else{
                //店老板
                //检测店老板下的美容师
                $fids = Db::table('ims_bj_shopn_member')->where("code <> '' and id=staffid and storeid={$storeid}")->column('id');
                $where['staffid'] = ['in',$fids];
                $where1['originfid'] = ['in',$fids];
                $mids = Db::table('ims_bj_shopn_member')->where($where)->whereOr($where1)->column('id');
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
        $uid     = intval( input('param.uid', 0) );//美容师id
        $page    = intval( input('param.page', 1) );//美容师id
        if(empty($storeid) || empty($uid)) {
            return parent::returnMsg(0,'','参数缺失');
        }

        //查询当前用户产品卡券 只查询 产品
        $member = MemberModel::getUserAccordToGiveUserID($uid);
        if(!empty($member)){
            if($member['storeid'] == 1792){
                $sss = Db::table('ims_bj_shopn_member')->field('realname,mobile')->where('id',$member['originfid'])->find();
            }else{
                $sss = Db::table('ims_bj_shopn_member')->field('realname,mobile')->where('id',$member['staffid'])->find();
            }
            $member['sellername']   = $sss['realname'];
            $member['sellermobile'] = $sss['mobile'];
            $member['nickname']     = $member['nickname'] ?: $member['realname'];
        }
        $limit = 10;
        $map['cu.uid'] = $uid;
        $map['cu.type'] = 0; //类型 1 清洁卡 2礼包卡 0一般商品
        $total = BlinkBoxCouponUserModel::getCurrentUserGroupCouponsCount($map);
        $list = BlinkBoxCouponUserModel::getCurrentUserGroupCoupons($map,$page,$limit);

        if(!empty($list)){
            foreach ($list as $k=>$val){
                $goods_id = $val['goods_id'];
                //产品总数
                $s['user.uid']      = $uid;
                $s['user.type']     = 0;
                $s['user.goods_id'] = $goods_id;
                $total = BlinkBoxCouponUserModel::getCurrentUserCouponsCount($s);
                $list[$k]['total'] = $total;
                //核销数
                $s['user.status'] = 1;
                $hexiao = BlinkBoxCouponUserModel::getCurrentUserCouponsCount($s);
                $list[$k]['hexiao'] = $hexiao;
                $list[$k]['nohexiao'] = $total - $hexiao;
            }
        }
        $data = [
            'total'        => $total,//总条数
            'per_page'     => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page'    => $total ? ceil($total / $limit) : 0,//最后一页
            'list'         => $list,
            'member'       => $member
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
        try {
            $time = time();
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
                if(!in_array($members['sign'],$this->sign) || !in_array($staffs['sign'],$this->sign)){
                    return parent::returnMsg(0,'','该卡券商品您没有查看权限');
                }
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
                if(!in_array($members['sign'],$this->sign) || !in_array($staffs['sign'],$this->sign)){
                    return parent::returnMsg(0,'','该卡券商品您没有查看权限');
                }

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
                    'pid'          => 0,
                    'uid'          => $uid,
                    'type'         => 0,
                    'is_compose'   => 0,
                    'blinkno'      => '',
                    'cardno'       => $cardno,
                    'qrcode'       => '',//使用时生成核销二维码
                    'thumb_id'     => $rand_id,
                    'status'       => 0,
                    'source'       => 1,
                    'is_give'      => 0,
                    'parent_owner' => 0,
                    'create_time'  => time(),
                    'update_time'  => time(),
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
}