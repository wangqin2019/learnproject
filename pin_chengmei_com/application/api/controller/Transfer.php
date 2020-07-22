<?php

namespace app\api\controller;
use app\api\model\ActivityOrderInfoModel;
use app\api\model\ActivityOrderModel;
use app\api\model\ActivityOrderSharingModel;
use app\api\model\ActivityOrderTransferModel;
use app\api\model\CartModel;
use app\api\model\GoodsModel;
use app\api\model\GoodsModModel;
use app\api\model\GoodsSpecsModel;
use app\api\model\TicketUserModel;
use think\Controller;
use think\Db;

/**
 * desc:转客活动
 */
class Transfer extends Base
{
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
//        if($token==''){
//            $code = 400;
//            $data = '';
//            $msg = '非法请求';
//            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
//            exit;
//        }else{
//            if(!parent::checkToken($token)) {
//                $code = 400;
//                $data = '';
//                $msg = '用户登陆信息过期，请重新登录！';
//                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
//                exit;
//            }else{
//                return true;
//            }
//        }
    }

    //检测活动
    public function check_activity(){
        $activityInfo=Db::name('activity_config')->where('id',1)->cache(86400)->find();
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

    //missshop活动产品列表  --分组
    public function goods_list(){
        $check=0;
        $storeid=input('param.storeid',0);
        $map['goods_cate']=array('eq',4);
        $map['activity_id']=array('eq',3);
        $map['status']=array('eq',1);
        $goods=new GoodsModel();
        if($storeid){
            $map['storeid']=array('eq',$storeid);
            $check=$goods->getAllBranch($map);
        }
        if(!$check){
            $storeid=0;
        }
        $map['storeid']=array('eq',$storeid);
        $map['deputy_cate'] = ['exp', Db::raw('is not null')];
        $getDeputy=$goods->getGoodsColumn($map,'deputy_cate');
        $goodsData=[];
        $cate=['yxj','tmj','tp','xt'];
        foreach ($getDeputy as $k=>$v) {
            $goodsData[$k]['category']=$cate[$k];
            $map['deputy_cate'] = array('eq', $v);
            $goodsData[$k]['list'] = $goods->getActivityGoods($map,'orderby');
        }
        if($goodsData){
            $code = 1;
            $data = $goodsData;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂无数据！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //missshop活动产品详情
    public function goods_info(){
        $id=input('param.pid');
        $storeid=input('param.storeid',0);
        $goods=new GoodsModel();
        $info=$goods->getGoods($id);
        if($info && $info['model_id']){
            $goods_model=new GoodsModModel();
            $goods_specs=new GoodsSpecsModel();
            $specs=$goods_model->getModelValue($info['model_id'],'model_specs');
            $map['id']=array('in',$specs);
            $map['specs_status']=array('eq',1);
            $info['specs']=$goods_specs->getAllGoodsSpecs($map,'specs_order desc');
        }else{
            $info['specs']=[];
        }
        if($info){
            //详情轮播图展示 视频+图片 如包含视频放在首位
            $picShow=[];
            if(strlen($info['images'])){
                $img=explode(',',$info['images']);
                foreach ($img as $k=>$v){
                    $imgs[$k]['type']=0;
                    $imgs[$k]['link']=$v;
                }
                $picShow=$imgs;
            }
            if(strlen($info['video'])){
                $video['type']=1;
                $video['link']=$info['video'];
                array_unshift($picShow,$video);
            }
            $info['picShow']=$picShow;
            //买赠 门店自己配置了用自己配置 自己没配置 显示总部的
            $info['goods_given']=$this->get_given($info['given'],$info['pid']);
            //连带推荐 门店自己配置了用自己的 自己没有 用总部的
            $info['recommend']=$this->get_recommend($id,$info['pid'],$storeid?$storeid:$info['storeid']);
            //第一次访问 将该产品库存存在redis 之后直接取redis里面的
            $info['stock']=$this->get_stock($id,$info['stock']);
            $info['buy_type']=$this->goods_type($storeid?$storeid:$info['storeid'],$info['buy_type']);
            unset($info['given']);
            unset($info['pid']);
            unset($info['video']);
            unset($info['model_id']);
            unset($info['status']);
            $code = 1;
            $data = $info;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂无数据！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //将欲购买信息插入临时购物车
    public function add_temp_cart(){
        $ids=input('param.ids');
        $uid=input('param.uid');
        $main_id=input('param.main_id');
        if($ids && $uid && $main_id){
            try{
                $cart=new CartModel();
                $transaction_num=$cart->addCart($uid,$ids,$main_id);
                if($transaction_num){
                    $code = 1;
                    $data = $transaction_num;
                    $msg = '添加成功';
                }else{
                    $code = 0;
                    $data = $transaction_num;
                    $msg = '添加失败';
                }
            }catch (\Exception $e){
                $code = 0;
                $data = '';
                $msg = '插入失败'.$e->getMessage();
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //将产品从临时购物车中取出来
    public function get_cart_goods(){
        $uid=input('param.uid');
        $transaction_num=input('param.transaction_num');
        if($uid) {
            $cart = new CartModel();
            $cart_good = $cart->cartGoods($uid, $transaction_num);
            if ($cart_good) {
                foreach ($cart_good as $k => $v) {
                    if ($v['model_id']) {
                        $goods_model = new GoodsModModel();
                        $goods_specs = new GoodsSpecsModel();
                        $specs = $goods_model->getModelValue($v['model_id'], 'model_specs');
                        $map['id'] = array('in', $specs);
                        $map['specs_status'] = array('eq', 1);
                        $cart_good[$k]['specs'] = $goods_specs->getAllGoodsSpecs($map, 'specs_order desc');
                    } else {
                        $cart_good[$k]['specs'] = [];
                    }
                    $cart_good[$k]['given'] = $info['goods_given'] = $this->get_given($v['given'], $v['pid']);
                    unset($cart_good[$k]['model_id']);
                    unset($cart_good[$k]['pid']);
                    unset($cart_good[$k]['insert_time']);
                    if($v['main_flag']){
                        $cart_good[$k]['activity_price']=$v['activity_price'];
                    }else{
                        $cart_good[$k]['activity_price']=$v['recommend_price'];
                    }
                    unset($cart_good[$k]['recommend_price']);
                }
                $code = 1;
                $data = $cart_good;
                $msg = '获取成功';
            } else {
                $code = 0;
                $data = '';
                $msg = '您没有添加任何产品';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '缺少参数';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 删除购物车中的某一个产品
     * @return \think\response\Json
     */
    public function del_cart_goods(){
        $uid=input('param.uid');
        $good_id=input('param.good_id');
        $transaction_num=input('param.transaction_num');
        if($uid && $good_id && $transaction_num){
            $cart=new CartModel();
            $del=$cart->cartGoodsDel($uid,$good_id,$transaction_num);
            if($del){
                $code = 1;
                $data = '';
                $msg = '移除成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '移除失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '缺少参数';
        }

        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 更新购物车中的某一个产品的数量及规格
     * @return \think\response\Json
     */
    public function update_cart_goods(){
        $uid=input('param.uid');
        $good_id=input('param.good_id');
        $transaction_num=input('param.transaction_num');
        if($uid && $good_id && $transaction_num) {
            $num = input('param.num', 1);
            $specs = input('param.specs');
            $cart = new CartModel();
            $res = $cart->cartGoodsUpdate($uid, $good_id, $num, $specs, $transaction_num);
            if ($res) {
                $code = 1;
                $data = '';
                $msg = '更新成功';
            } else {
                $code = 0;
                $data = '';
                $msg = '更新失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '缺少参数';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //购买
    public function buy(){
        $this->check_activity();
        $uid=input('param.uid');
        $transaction_num=input('param.transaction_num');
        if($uid && $transaction_num) {
            $ticket_id = input('param.ticket_id', 0);//现金券
            $fid = input('param.fid', $uid);//引导分享uid
            $cart = new CartModel();
            $cart_goods = $cart->cartGoods($uid, $transaction_num);
            if(count($cart_goods)) {
                $stock = $this->check_goods_stock($cart_goods);
                if ($stock['code']) {
                    //判断现金券
                    $cash_value = 0;
                    if ($ticket_id) {
                        $cash_code = parent::saddSismember('cash_code', $ticket_id);
                        if ($cash_code) {
                            return parent::returnMsg(0, '', '现金券已使用');
                        } else {
                            $cashInfo = Db::name('ticket_user')->where('id', $ticket_id)->field('status,par_value')->find();
                            if ($cashInfo) {
                                if ($cashInfo['status']) {
                                    $code = 0;
                                    $data = '';
                                    $msg = '现金券已过期';
                                    return parent::returnMsg($code, $data, $msg);
                                }
                                $cash_value = $cashInfo['par_value'];
                            }
                        }
                    }
                    Db::startTrans();
                    try {
                        //检测购买权限及返回美容师及门店id
                        $check_buyer = $this->check_buyer($uid, $fid);
                        $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                        $total_price = 0;
                        $total_goods = 0;
                        $goods_name = [];
                        $orderInfo = [];
                        $given_info = [];
                        foreach ($cart_goods as $k => $v) {
                            //检测当前产品是否超过允许购买次数
                            if ($v['allow_buy_num']) {
                                if($v['storeid']){
                                    $buy_pid=$v['pid'];
                                }else{
                                    $buy_pid=$v['good_id'];
                                }
                                $check_buy=$this->check_goods_limit($uid, $buy_pid, $v['allow_buy_num']);
                                if(!$check_buy){
                                    $code = 0;
                                    $data = '';
                                    $msg = $v['name'].'不允许购买，每人仅限'.$v['allow_buy_num'].'次';
                                    return parent::returnMsg($code,$data,$msg);
                                }
                            }
                            if($v['main_flag']){
                                $goods_price=$v['activity_price'];
                            }else{
                                $goods_price=$v['recommend_price'];
                            }
                            $amount = $goods_price * $v['good_num'];
                            $orderInfo[] = ['order_sn' => $ordersn, 'good_id' => $v['good_id'], 'good_num' => $v['good_num'], 'good_price' => $goods_price, 'good_amount' => $amount, 'main_flag' => $v['main_flag'], 'good_specs' => $v['good_param'], 'source' => 0, 'flag' => 0, 'insert_time' => time()];
                            $total_price += $amount;
                            $total_goods += $v['good_num'];
                            $goods_name[] = $v['name'];
                            //如果有买赠 附加上去
                            $given=$this->get_given($v['given'], $v['pid']);
                            if (count($given)) {
                                foreach ($given as $kk => $vv) {
                                    $given_info[] = ['order_sn' => $ordersn, 'good_id' => $vv['id'], 'good_num' => 1, 'good_price' => 0, 'good_amount' => 0, 'main_flag' => 0, 'good_specs' => '', 'source' => $v['good_id'], 'flag' => 1, 'insert_time' => time()];
                                }
                            }
                        }
                        //合并产品订单和配赠
                        $orderInfo = array_merge($orderInfo, $given_info);
                        $arr = array('uid' => $uid, 'storeid' => $check_buyer['storeid'], 'fid' => $check_buyer['sellerid'], 'share_uid' => $fid, 'num' => $total_goods, 'order_sn' => $ordersn, 'order_price' => $total_price, 'pay_price' => $total_price - $cash_value, 'coupon_price' => $cash_value, 'coupon_id' => $ticket_id, 'channel' => 'missshop', 'pick_type' => 1, 'insert_time' => time(), 'pid' => '', 'scene' => 1, 'flag' => 1, 'specs' => '');
                        Db::name('activity_order')->insert($arr);
                        Db::name('activity_order_info')->insertAll($orderInfo);
                        $cart->cartGoodsDel($uid, '', $transaction_num);
                        Db::commit();
                        $o_info=new ActivityOrderInfoModel();
                        $getDraw=$o_info->getGoodsDraw($ordersn);
                        $res['order_sn'] = $ordersn;
                        $res['attach'] = 'missshop';
                        $res['total_fee'] = $total_price - $cash_value;
                        $res['user_id'] = $uid;
                        $res['buy_type'] = 3;
                        $res['body'] = implode(' + ', $goods_name);
                        $res['draw'] = $getDraw;
                        $code = 1;
                        $data = $res;
                        $msg = '订单已生成，去付款！';
                    } catch (\Exception $e) {
                        Db::rollback();
                        $code = 0;
                        $data = '';
                        $msg = '订单生成失败！' . $e->getMessage();
                    }
                } else {
                    $code = 0;
                    $data = '';
                    $msg = implode('+', $stock['data']) . '库存不足';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '订单已过期，请返回重新选择';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '错误，请返回重试';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 顾客购买列表
     * @return \think\response\Json
     */
    public function order_list(){
        $uid=input('param.uid');
        $userInfo=Db::table('ims_bj_shopn_member')->field('id,staffid,code,isadmin,storeid')->where('id',$uid)->find();
        if($uid!='' || $userInfo){
//            if($userInfo['isadmin']){
            $map['uid']=array('eq',$uid);
//            }elseif (($userInfo['id']==$userInfo['staffid']) || strlen($userInfo['code'])>1){
//                $map['fid']=array('eq',$uid);
//            }else{
//                $map['uid']=array('eq',$uid);
//            }
            $order=new ActivityOrderModel();
            $map['pay_status']=array('eq',1);
            $map['channel'] = array('eq', 'missshop');
            $map['scene'] = array('in', '0,1,2');
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count = $order->getOrderCount($map);
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = $order->getOrdersByWhere($map,$Nowpage, $limits);
            if(count($list) && is_array($list)){
                $data_list=[];
                $orderInfo=new ActivityOrderInfoModel();
                $shareFlag=0;
                foreach ($list as $k=>$v){
                    $goodsInfo=[];
                    if($v['flag']){
                        $getInfo=$orderInfo->getOrderInfoByWhere(['order_sn'=>$v['order_sn']]);
                        if(count($getInfo)){
                            foreach ($getInfo as $kk=>$vv){
                                $goodsInfo[$kk]['p_name']=$vv['p_name'];
                                $goodsInfo[$kk]['p_unit']=$vv['unit'];
                                $goodsInfo[$kk]['p_images']=strtok($vv['images'], ',');
                                $goodsInfo[$kk]['good_id']=$vv['good_id'];
                                $goodsInfo[$kk]['good_specs']=$vv['good_specs'];
                                $goodsInfo[$kk]['good_num']=$vv['good_num'];
                                $goodsInfo[$kk]['good_price']=$vv['good_price'];
                                $goodsInfo[$kk]['good_amount']=$vv['good_amount'];
                                $goodsInfo[$kk]['use_num']=$vv['use_num'];
                                $goodsInfo[$kk]['buy_type']=$this->goods_type($userInfo['storeid'],$vv['buy_type'],$v['order_sn']);
                                $goodsInfo[$kk]['is_sharing']=$vv['is_sharing'];
                                if($goodsInfo[$kk]['buy_type']){
                                    $shareFlag=$goodsInfo[$kk]['buy_type'];
                                }
                            }
                        }
                    }else{
                        $goodsInfo[0]['p_name']=$v['p_name'];
                        $goodsInfo[0]['p_unit']=$v['unit'];
                        $goodsInfo[0]['p_images']=strtok($v['images'], ',');
                        $goodsInfo[0]['good_specs']=$v['specs'];
                        $goodsInfo[0]['good_id']=$v['pid'];
                        $goodsInfo[0]['good_num']=$v['num'];
                        $goodsInfo[0]['good_price']=$v['order_price']/$v['num'];
                        $goodsInfo[0]['good_amount']=$v['pay_price'];
                        $goodsInfo[0]['use_num']=1;
                        $goodsInfo[0]['buy_type']=0;
                        $goodsInfo[0]['is_sharing']=0;
                    }
                    unset($v['num'],$v['specs'],$v['p_name'],$v['unit'],$v['unit'],$v['images'],$v['flag'],$v['buy_type']);
                    $data_list[$k]=$v;
                    $data_list[$k]['son_list']=$goodsInfo;
                    $data_list[$k]['share_list']=[];
                    if($shareFlag==1){
                        $share=new ActivityOrderSharingModel();
                        $share_list=$share->getAll($v['order_sn']);
                        $data_list[$k]['share_list']=$share_list;
                    }
                }
                $info['list']=$data_list;
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无订单';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }



    public function order_info(){
        $order_id=input('param.order_id');
        if($order_id!=''){
            $data_info=[];
            $order=new ActivityOrderModel();
            $map['order_id']=array('eq',$order_id);
            $order_info =$order->getOrderInfo($order_id);
            $data_info['order_sn']=$order_info['order_sn'];
            $data_info['pay_time']=date('Y-m-d H:i:s',$order_info['pay_time']);
            $data_info['pick_type']=$order_info['pick_type']?'门店取货':'现场收货';
            $data_info['pay_status']=$order_info['pay_status']?'已支付':'未付款';
            $data_info['order_price']=$order_info['order_price']?$order_info['order_price']:0;
            $data_info['coupon_price']=$order_info['coupon_price']?$order_info['coupon_price']:0;
            $data_info['pay_price']=$order_info['pay_price']?$order_info['pay_price']:0;
            $data_info['is_axs']=$order_info['is_axs'];
            //产品是否允许抽奖
            $getOrder=new ActivityOrderModel();
            $orderGoodType=$getOrder->getOrderGoodType($order_info['order_sn']);
            //拓客产品不允许抽奖
            if($orderGoodType==0){
                $data_info['allow_draw']=0;
                $data_info['lucky_flag']=0;
                $data_info['lucky_draw']='';
            }else{
                //美容师和店老板不允许抽奖
                if($order_info['uid']==$order_info['fid']){
                    $data_info['allow_draw']=0;
                    $data_info['lucky_flag']=0;
                    $data_info['lucky_draw']='';
                }else{
                    $data_info['allow_draw']=1;
                    $lucky=Db::name('order_lucky')->where('order_sn',$order_info['order_sn'])->field('lucky_name,lucky_image,flag,qrcode')->find();
                    if($lucky){
                        $lucky['flag']=$lucky['flag']?'已领取':'未领取';
                        $data_info['lucky_flag']=1;
                        $data_info['lucky_draw']=$lucky;
                    }else{
                        $data_info['lucky_flag']=0;
                        $data_info['lucky_draw']='';
                    }
                }
            }
            $shareFlag=0;
            if($order_info['flag']){
                $orderInfo=new ActivityOrderInfoModel();
                $getInfo=$orderInfo->getOrderInfoByWhere(['order_sn'=>$order_info['order_sn']]);
                if(count($getInfo)){
                    foreach ($getInfo as $kk=>$vv){
                        $getInfo[$kk]['p_name']=$vv['p_name'];
                        $getInfo[$kk]['p_images']=strtok($vv['images'], ',');
                        $getInfo[$kk]['p_unit']=$vv['unit'];
                        $getInfo[$kk]['p_specs']=$vv['good_specs'];
                        $getInfo[$kk]['main_flag'] = $vv['main_flag']?'主单':'次单';
                        $getInfo[$kk]['flag'] = $vv['flag']?'买赠':'单品';
                        $getInfo[$kk]['pick_up'] = $vv['pick_up']?'已取货':'未取货';
                        $getInfo[$kk]['pick_code'] = $vv['pick_code'];
                        if($vv['pick_code']==''){
                            $codeUrl = pickUpCode('missshop_'.$order_info['order_sn'].'_'.$vv['good_id']);
                            if ($codeUrl) {
                                $getInfo[$kk]['pick_code'] = $codeUrl;
                                Db::name('activity_order_info')->where(['order_sn'=>$order_info['order_sn'],'good_id'=>$vv['good_id']])->update(['pick_code'=>$codeUrl]);
                            }
                        }
                        if($vv['main_flag']){
                            $main_flag=$vv['g_draw_type'];
                        }
                        $getInfo[$kk]['use_num']=$vv['use_num'];
                        $getInfo[$kk]['buy_type']=$this->goods_type($order_info['storeid'],$vv['buy_type'],$order_info['order_sn']);
                        $getInfo[$kk]['is_sharing']=$vv['is_sharing'];
                        if($getInfo[$kk]['buy_type']){
                            $shareFlag=$getInfo[$kk]['buy_type'];
                        }
                        unset($getInfo[$kk]['images']);
                        unset($getInfo[$kk]['unit']);
                        unset($getInfo[$kk]['good_specs']);
                    }
                }
                //$data_info['draw']=$main_flag;
                $data_info['goods_list']=$getInfo;
            }else{
                $goods['good_id']=$order_info['pid'];
                $goods['good_num']=$order_info['num'];
                $goods['good_price']=$order_info['order_price']/$order_info['num'];
                $goods['good_amount']=$order_info['pay_price'];
                $goods['main_flag']='主单';
                $goods['flag']='单品';
                $goods['pick_up']=$order_info['order_status']?'已取货':'未取货';
                $goods['p_name']=$order_info['p_name'];
                $goods['p_images']=strtok($order_info['images'], ',');
                $goods['p_unit']=$order_info['unit'];
                $goods['p_specs']=$order_info['specs'];
                $goods['pick_code']=$order_info['pick_code'];
                $main_flag=$order_info['g_draw_type'];
                if($order_info['pick_type']==1 && $order_info['pick_code']==''){
                    $codeUrl = pickUpCode('missshop_'.$order_info['order_sn']);
                    if ($codeUrl) {
                        $goods['pick_code'] = $codeUrl;
                        Db::name('activity_order')->where('order_sn',$order_info['order_sn'])->update(['pick_code'=>$codeUrl]);
                    }
                }
                $goods['use_num']=1;
                $goods['buy_type']=0;
                $goods['is_sharing']=0;
                $data_info['goods_list'][]=$goods;
            }
            $data_info['share_list']=[];
            $data_info['share_use_num']=0;
            if($shareFlag==1){
                $share=new ActivityOrderSharingModel();
                $share_list=$share->getAll($order_info['order_sn']);
                $data_info['share_list']=$share_list;
                $data_info['share_use_num']=$share->getSum(['order_sn' => $order_info['order_sn'],'sharing_flag' => 1], 'num');;
            }
            //判断该订单抽奖形式
            if($order_info['uid']==$order_info['fid']){
                $data_info['draw']=0;
            }else{
                $check=Db::name('activity_branch_draw')->where('storeid',$order_info['storeid'])->count();
                if($check){
                    if($main_flag==0){
                        $data_info['draw']=0;
                    }else{
                        $data_info['draw']=1;
                    }
                }else{
                    $data_info['draw']=$main_flag;
                }
            }
            $branchInfo=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id',$order_info['fid'])->field('m.realname,m.mobile,b.title,b.sign,b.address')->find();
            $data_info['pick_info']=$branchInfo;

            if(count($data_info) && is_array($data_info)){
                $code = 1;
                $data = $data_info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无订单';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 美容师下购买列表
     * @return \think\response\Json
     */
    public function customer_order_list(){
        $uid=input('param.uid');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $userInfo=Db::table('ims_bj_shopn_member')->field('id,staffid,code,isadmin')->where('id',$uid)->find();
        if($uid!='' || $userInfo){
            if($userInfo['isadmin']){
                $map['uid']=array('eq',$uid);
            }elseif (($userInfo['id']==$userInfo['staffid']) || strlen($userInfo['code'])>1){
                $map['fid']=array('eq',$uid);
            }else{
                $map['uid']=array('eq',$uid);
            }
            if($search_time1!='' && $search_time2!=''){
                $map['o.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }
            $order=new ActivityOrderModel();
            $map['pay_status']=array('eq',1);
            $map['channel'] = array('eq', 'missshop');
            $map['scene'] = array('in', '0,1,2');
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count = $order->getOrderCount($map);
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = $order->getOrdersByWhere($map,$Nowpage, $limits);
            if(count($list) && is_array($list)){
                $data_list=[];
                $orderInfo=new ActivityOrderInfoModel();
                foreach ($list as $k=>$v){
                    //产品是否允许抽奖
                    $getOrder=new ActivityOrderModel();
                    $orderGoodType=$getOrder->getOrderGoodType($v['order_sn']);
                    //拓客产品不允许抽奖
                    if($orderGoodType==0){
                        $v['allow_draw']=0;
                        $v['lucky_flag']=0;
                        $v['lucky_draw']='';
                    }else{
                        //美容师和店老板不允许抽奖
                        if($v['uid']==$v['fid']){
                            $v['allow_draw']=0;
                            $v['lucky_flag']=0;
                            $v['lucky_draw']='';
                        }else{
                            $v['allow_draw']=1;
                            $lucky=Db::name('order_lucky')->where('order_sn',$v['order_sn'])->field('lucky_name,lucky_image,flag,qrcode')->find();
                            if($lucky){
                                $lucky['flag']=$lucky['flag']?'已领取':'未领取';
                                $v['lucky_flag']=1;
                                $v['lucky_draw']=$lucky;
                            }else{
                                $v['lucky_flag']=0;
                                $v['lucky_draw']='';
                            }
                        }
                    }
                    $goodsInfo=[];
                    if($v['flag']){
                        $getInfo=$orderInfo->getOrderInfoByWhere(['order_sn'=>$v['order_sn']]);
                        if(count($getInfo)){
                            foreach ($getInfo as $kk=>$vv){
                                $goodsInfo[$kk]['p_name']=$vv['p_name'];
                                $goodsInfo[$kk]['p_unit']=$vv['unit'];
                                $goodsInfo[$kk]['p_images']=strtok($vv['images'], ',');
                                $goodsInfo[$kk]['good_specs']=$vv['good_specs'];
                                $goodsInfo[$kk]['good_num']=$vv['good_num'];
                                $goodsInfo[$kk]['good_price']=$vv['good_price'];
                                $goodsInfo[$kk]['good_amount']=$vv['good_amount'];
                                $goodsInfo[$kk]['pick_up']=$vv['pick_up'];
                            }
                        }
                    }else{
                        $goodsInfo[0]['p_name']=$v['p_name'];
                        $goodsInfo[0]['p_unit']=$v['unit'];
                        $goodsInfo[0]['p_images']=strtok($v['images'], ',');
                        $goodsInfo[0]['good_specs']=$v['specs'];
                        $goodsInfo[0]['good_num']=$v['num'];
                        $goodsInfo[0]['good_price']=$v['order_price']/$v['num'];
                        $goodsInfo[0]['good_amount']=$v['pay_price'];
                        $goodsInfo[0]['pick_up']=$v['order_status'];
                    }
                    unset($v['num'],$v['specs'],$v['p_name'],$v['unit'],$v['unit'],$v['images'],$v['flag']);
                    $data_list[$k]=$v;
                    $data_list[$k]['son_list']=$goodsInfo;
                }
                $info['list']=$data_list;
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无订单';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }



    //发送同享邀请
    public function send_sharing(){
        $order_sn=input('param.ordersn');
        $mobile=input('param.mobile');
        $message=input('param.message');
        $sharing=new ActivityOrderSharingModel();
        $ticket=new TicketUserModel();
        $ticket_info=$ticket->getOneInfo($order_sn);
        $checkUser=$sharing->getAllSharing(['order_sn'=>$order_sn,'ticket_sn'=>$ticket_info['ticket_code'],'mobile'=>$mobile,'sharing_flag'=>1]);
        if(!$checkUser) {
            $data = ['order_sn' => $order_sn, 'ticket_sn' => $ticket_info['ticket_code'], 'mobile' => $mobile, 'buyer_flag' => 0, 'message'=>$message,'insert_time' => time()];
            $sharing->insertSharing($data);
            $code = 1;
            $data = '';
            $msg = '发送邀请成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '您已经邀请过该好友了！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //查看朋友同享信息

    public function sharing_info(){
        $order_sn=input('param.ordersn');
        $mobile=input('param.mobile');
        $pid=input('param.pid',0);//如果是赠送 需要传pid
        $type=input('param.type',1);//同享是1 赠送是2
        $order=new ActivityOrderModel();
        $order_info=$order->getOrderShareInfo(['o.order_sn'=>$order_sn],$pid);
        if($order_info){
            $sharing=new ActivityOrderSharingModel();
            if($type==2){
                $map['share_pid']=array('eq',$pid);
            }
            $map['order_sn']=array('eq',$order_sn);
            $map['mobile']=array('eq',$mobile);
            $map['sharing_flag']=array('eq',$type);
            $msg=$sharing->getOneInfo($map,'message');
            $order_info['message']=$msg['message'];
        }
        return parent::returnMsg(1,$order_info,'获取成功');
    }

    //接受产品同享操作
    public function accept_sharing(){
        $order_sn=input('param.ordersn');
        $uid=input('param.uid');
        $storeid=input('param.storeid');
        $mobile=input('param.mobile');
        $ticket=new TicketUserModel();
        $ticket_info=$ticket->getOneInfo($order_sn);
        if($order_sn!='' && $mobile!='' && $ticket_info && $storeid!=''){
            if($uid==$ticket_info['uid']){
                $code = 0;
                $data = '';
                $msg = '自己不能同享自己的订单哦！';
            }else{
                if($storeid==$ticket_info['storeid']) {
                    $sharing = new ActivityOrderSharingModel();
                    $getUse = $sharing->getSum(['order_sn' => $order_sn, 'ticket_sn' => $ticket_info['ticket_code'], 'accept_flag' => 1,'sharing_flag' => 1], 'accept_flag');
                    if ($getUse < $ticket_info['ticket_num']) {
                        $check = $sharing->getAllSharing(['order_sn' => $order_sn, 'ticket_sn' => $ticket_info['ticket_code'], 'mobile' => $mobile, 'sharing_flag' => 1]);
                        if ($check) {
                            $checkUser = $sharing->getAllSharing(['order_sn' => $order_sn, 'ticket_sn' => $ticket_info['ticket_code'], 'mobile' => $mobile, 'sharing_flag' => 1, 'accept_flag' => 0]);
                            if ($checkUser) {
                                Db::startTrans();
                                try {
                                    //改变分享记录
                                    $data = ['uid' => $uid, 'accept_flag' => 1, 'accept_time' => time()];
                                    $sharing->updateSharing(['order_sn' => $order_sn, 'ticket_sn' => $ticket_info['ticket_code'], 'sharing_flag' => 1, 'mobile' => $mobile], $data);
                                    //生成卡券
                                    $ticket->copyTicket($uid,$mobile, $ticket_info['ticket_code']);
                                    Db::commit();
                                    //需要给发起人发送通知短信 待做
                                    $code = 1;
                                    $data = '';
                                    $msg = '接收成功，已在您的卡包中生成同享卡';
                                } catch (\Exception $e) {
                                    Db::rollback();
                                    $code = 0;
                                    $data = '';
                                    $msg = '同享券接收失败' . $e->getMessage();
                                }
                            } else {
                                $code = 0;
                                $data = '';
                                $msg = '你已经拥有这张同享卡了哦！';
                            }
                        } else {
                            $code = 0;
                            $data = '';
                            $msg = '该卡为指定用户同享卡，领取失败！';
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '您来晚啦，该同享卡额度已被领取完毕';
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '您与赠送人不属于同一家美容院，接收失败！';
                }
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //赠送订单
    public function send_order(){
        $order_sn=input('param.ordersn');
        $pid=input('param.pid');
        $mobile=input('param.mobile');
        $message=input('param.message');
        $transfer=new ActivityOrderSharingModel();
        $checkUser=$transfer->getOneInfo(['order_sn'=>$order_sn,'mobile'=>$mobile,'sharing_flag'=>2],'insert_time,accept_flag');
        if(!$checkUser) {
            $data = ['order_sn' => $order_sn, 'mobile' => $mobile, 'message'=>$message,'sharing_flag'=>2,'share_pid'=>$pid, 'insert_time' => time()];
            $transfer->insertSharing($data);
            $orderInfo=new ActivityOrderInfoModel();
            $data = ['is_sharing' => 1,'sharing_time' => time()];
            $orderInfo->updateData(['order_sn'=>$order_sn,'good_id'=>$pid],$data);
            $code = 1;
            $data = '';
            $msg = '发送邀请成功';
        }else{
            if($checkUser['accept_flag']){
                $code = 0;
                $data = '';
                $msg = '该好友已经接受过您的赠送了';
            }else{
                $code = 0;
                $data = '';
                $msg = '快联系好友去接受邀请吧！';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //接收产品赠送
    public function accept_order(){
        $order_sn=input('param.ordersn');
        $pid=input('param.pid');
        $uid=input('param.uid');
        $storeid=input('param.storeid');
        $mobile=input('param.mobile');
        $order=new ActivityOrderModel();
        $order_info=$order->getOneInfo(['order_sn'=>$order_sn],'uid,storeid');
        if($order_sn!='' && $mobile!='' && $order_info && $pid !='' && $storeid!=''){
            if($uid==$order_info['uid']){
                $code = 0;
                $data = '';
                $msg = '自己不能接收自己的赠送产品哦！';
            }else {
                if ($storeid == $order_info['storeid']) {
                    $transfer = new ActivityOrderSharingModel();
                    $getUse = $transfer->getSum(['order_sn' => $order_sn, 'share_pid'=>$pid, 'accept_flag' => 1, 'sharing_flag' => 2], 'accept_flag');
                    if (!$getUse) {
                        $check = $transfer->getAllSharing(['order_sn' => $order_sn,'share_pid'=>$pid, 'mobile' => $mobile, 'sharing_flag' => 2]);
                        if ($check) {
                            $checkUser = $transfer->getAllSharing(['order_sn' => $order_sn,'share_pid'=>$pid, 'mobile' => $mobile, 'accept_flag' => 0, 'sharing_flag' => 2]);
                            if ($checkUser) {
                                $ticket = new TicketUserModel();
                                $ticket_check = $ticket->getCount(['order_sn' => $order_sn,'goods_id'=>$pid, 'type' => 19]);
                                if (!$ticket_check) {
                                    Db::startTrans();
                                    try {
//                                        $tInfo=$transfer->getOneInfo(['order_sn'=>$order_sn,'mobile'=>$mobile,'sharing_flag'=>2,'accept_flag'=>0],'share_pid');
                                        $ticketInfo = Db::name('draw_scene')->where('scene_prefix', 19)->field('scene_name,image1')->find();
                                        $send = sendTicket($uid, 19, $ticketInfo['image1'], 0, 'sharing_', $order_sn, 1,$pid);
                                        if ($send) {
                                            $ticket = $ticket->getInfoByWhere(['order_sn' => $order_sn, 'goods_id'=>$pid,'type' => 19], 'ticket_code,mobile');
                                            $data = ['uid' => $uid, 'accept_flag' => 1, 'ticket_sn' => $ticket['ticket_code'], 'accept_time' => time()];
                                            $transfer->updateSharing(['order_sn' => $order_sn, 'mobile' => $mobile, 'share_pid'=>$pid,'sharing_flag' => 2], $data);

                                            $orderInfo=new ActivityOrderInfoModel();
                                            $data = ['is_sharing' => 2,'sharing_time' => time()];
                                            $orderInfo->updateData(['order_sn'=>$order_sn,'good_id'=>$pid],$data);
                                        }
                                        Db::commit();
                                        //需要给发起人发送通知短信 待做
                                        $code = 1;
                                        $data = '';
                                        $msg = '接收成功，已在您的卡包中生成领取卡券';
                                    }catch (\Exception $e){
                                        Db::rollback();
                                        $code = 0;
                                        $data = '';
                                        $msg = '赠送券接收失败' . $e->getMessage();
                                    }
                                }
                            } else {
                                $code = 0;
                                $data = '';
                                $msg = '你已经领取过该订单了哦！';
                            }
                        } else {
                            $code = 0;
                            $data = '';
                            $msg = '该订单为指定用户专享，领取失败！';
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '您来晚啦，该订单已被领取';
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '您与赠送人不属于同一家美容院，接收失败！';
                }
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 检测当前用户是否时候还能购买
     * @param $uid
     * @param $pid
     * @param $allow_buy_num
     */
    public function check_goods_limit($uid,$pid,$allow_buy_num){
        $getUserOrder=parent::hashGet('missshop',$uid.'_'.$pid);
        if(!$getUserOrder){
            $getUserOrder=Db::name('activity_order_info')->alias('info')->join('activity_order o','info.order_sn=o.order_sn','left')->where(['o.uid'=>$uid,'info.good_id'=>$pid,'o.pay_status'=>1])->count();
        }
        if ($getUserOrder >= $allow_buy_num) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * 检测当前购买者的购买权限 有权限返回所属美容师和门店id
     * @param $uid
     * @param $fid
     * @return array|\think\response\Json
     */
    public function check_buyer($uid,$fid){
        //判断用户门店以及是否有权限购买
        $fidInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.join_tk,b.sign')->find();
        $sellerId = $fidInfo['staffid'];
        $storeid = $fidInfo['storeid'];
        //密丝小铺门店用户重新归属
        if($storeid==1550){
            $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
            Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
            $sellerId = $fid;
            $storeid = $getFidBid;
        }
        if($fidInfo['sign']=='000-000'){
            $code = 0;
            $data = '';
            $msg = '您为办事处人员，无活动商品购买权限！';
            return parent::returnMsg($code, $data, $msg);
        }
        //密丝小铺门店用户禁止下单
        if($storeid==1550){
            $code = 0;
            $data = '';
            $msg = '请联系您的所属美容师，再进行活动商品购买';
            return parent::returnMsg($code, $data, $msg);
        }
        //是否是错误的分享美容师
        if($sellerId==27291){
            $code = 0;
            $data = '';
            $msg = '分享码错误';
            return parent::returnMsg($code,$data,$msg);
        }
        //所在门店是否开通了活动
        if(strlen($fidInfo['join_tk'])){
            $join_tk_arr=explode(',',$fidInfo['join_tk']);
            if(!in_array(3,$join_tk_arr)){
                $code = 0;
                $data = '';
                $msg = '您所在门店没有开通活动，请联系所属美容师';
                return parent::returnMsg($code, $data, $msg);
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '您所在门店没有开通活动，请联系所属美容师';
            return parent::returnMsg($code, $data, $msg);
        }
        return ['sellerid'=>$sellerId,'storeid'=>$storeid];
    }


    /**
     * 检测购物车内下单产品库存
     * @param $data
     * @return array
     */
    public function check_goods_stock($data){
        $i=0;//初始至为0 假设都有库存
        $goods=[];
        foreach ($data as $k=>$v){
            $stock=parent::hashGet('activity_stock', 'goods_'.$v['good_id']);
            if(!$stock || $stock<$v['good_num']){
                $i++;
                $goods[]=$v['good_id'];
            }
        }
        if($i){
            $map['id']=array('in',$goods);
            $good= new GoodsModel();
            $info=$good->getGoodsColumn($map,'name');
            return ['code'=>0,'data'=>$info,'msg'=>'库存不足'];
        }else{
            return ['code'=>1,'data'=>'','msg'=>'库存允许'];
        }
    }


    //记录产品库存
    public function get_stock($pid,$stock){
        $getStock = parent::hashGet('activity_stock', 'goods_'.$pid );
        if (!$getStock) {
            parent::hashSet('activity_stock', 'goods_' . $pid, $stock);
            $getStock=parent::hashGet('activity_stock', 'goods_'.$pid );
        }
        return $getStock;
    }

    //获取配赠
    public function get_given($given,$pid){
        $goods_given=[];
        if(strlen($given)){
            $m['status']=array('eq',1);
            $m['id']=array('in',$given);
            $goods_given=Db::name('goods')->where($m)->field('id,name,images,stock')->select();
        }else{
            $getGiven=Db::name('goods')->where('id',$pid)->value('given');
            if($getGiven){
                $m['status']=array('eq',1);
                $m['id']=array('in',$getGiven);
                $m['stock']=array('gt',0);
                $goods_given=Db::name('goods')->where($m)->field('id,name,images,stock')->select();
            }
        }
        //将库存存入缓存
        if($goods_given){
            foreach ($goods_given as $k=>$v){
                $goods_given[$k]['stock']=$this->get_stock($v['id'],$v['stock']);
            }
        }
        return $goods_given;
    }
    //获取推荐
    public function get_recommend($id,$pid,$storeid){
        $recommendCheck=Db::name('activity_goods_recommend')->where(['gid'=>$id,'storeid'=>$storeid])->count();
        if($recommendCheck){
            $m_storeid=$storeid;
            $m_pid=$id;
        }else{
            $m_storeid=0;
            $m_pid=$pid?$pid:$id;
        }
        $recommend=[];
        $getRecommend=Db::name('activity_goods_recommend')->where(['gid'=>$m_pid,'storeid'=>$m_storeid])->column('recommend_ids');
        if($getRecommend){
            foreach ($getRecommend as $k=>$v){
                $m['status']=array('eq',1);
                $m['id']=array('in',$v);
                $m['stock']=array('gt',0);
                $recommend=Db::name('goods')->where($m)->field('id,name,images,price,recommend_price,unit,stock')->select();
            }
        }
        //将库存存入缓存
        if($recommend){
            foreach ($recommend as $k=>$v){
                $recommend[$k]['stock']=$this->get_stock($v['id'],$v['stock']);
            }
        }
        return $recommend;
    }

    /**
     * 产品是否允许同享买送 同享1 买送2 同享买送3 其他0
     * @param $buy_type
     * @return int
     */
    public function goods_type($storeid,$buy_type,$order_sn=''){
        if($order_sn!=''){
            $getInsert=substr($order_sn,0,8);
            if(strtotime($getInsert)<1607673943){
                return 0;
            }
        }
        $get_join=Db::table('ims_bwk_branch')->where('id',$storeid)->value('join_tk');
        $join_tk_arr = explode(',', $get_join);
        if (in_array(4, $join_tk_arr)) {
            $tx = strpos($buy_type, '3');
            $ms = strpos($buy_type, '4');
            if ($tx !== false && $ms !== false) {
                return 3;
            } elseif ($tx !== false) {
                return 1;
            } elseif ($ms !== false) {
                return 2;
            } else {
                return 0;
            }
        }else{
            return 0;
        }
    }


}