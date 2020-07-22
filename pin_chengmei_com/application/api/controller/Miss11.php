<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:missshop双十一活动
 */
class Miss1 extends Base
{
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
        if($token==''){
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

    //检测活动
    public function check_activity(){
        $activityInfo=Db::name('activity_config')->where('id',3)->cache(86400)->find();
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

    //产品信息
    public function goodsInfo(){
        $pid=input('param.pid');
        if($pid){
            $info=Db::name('goods')->where(['id'=>$pid,'goods_cate'=>10])->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content')->find();
            if($info && $info['status']){
                $this->setCacheString('ms_good'.$info['id'],$info['stock']);//将产品库存记录到redis
                $info['stock']=$this->getCacheString('ms_good'.$info['id']);
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
                $activityInfo=Db::name('activity_config')->where('id',3)->cache(86400)->find();
                $info['begin_time']=$activityInfo['begin_time'];
                $info['end_time']=$activityInfo['end_time'];
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '获取失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 创建订单
     *
     * */
	public function createOrder(){
        $this->check_activity();
        $pid=input('param.pid');
        $num = input('param.num', 1);//购买数量
        $stock=$this->getCacheString('ms_good'.$pid);
        if($stock>=$num) {
            $uid = input('param.uid');
            if($uid) {
                $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,status,stock,activity_price,allow_buy_num,stock_limit,allow_buy_num1')->find();
                $fid = input('param.fid', $uid);//引导分享uid
                $storeid = input('param.storeid');
                $getPayPrice = $goodsInfo['activity_price'] * $num;
                $getUserOrder = $this->hashGet('missshop', $uid . '_' . $pid);
                if ($getUserOrder) {
                    $getOrderCount = $getUserOrder;
                } else {
                    $map['uid'] = array('eq', $uid);
                    $map['pid'] = array('eq', $pid);
                    $map['pay_status'] = array('eq',1);
                    $map['channel'] = array('eq', 'missshop');
                    $getOrderCount = Db::name('activity_order')->where($map)->count();
                }
                if (($getOrderCount < $goodsInfo['allow_buy_num']) || $goodsInfo['allow_buy_num'] == 0) {
                    //获取购买者的上级uid
                    $fidInfo = Db::table('ims_bj_shopn_member')->where('id', $uid)->field('storeid,staffid')->find();
                    $sellerId = $fidInfo['staffid'];
                    $storeid = $fidInfo['storeid'];
                    //密丝小铺门店用户重新归属
                    if($storeid==1550){
                        $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
                        Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
                        $sellerId = $fid;
                        $storeid = $getFidBid;
                    }
                    if ($sellerId == 27291) {
                        $code = 0;
                        $data = '';
                        $msg = '分享码错误';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    $stock1=$this->getCacheString('ms_good'.$pid);
                    if($stock1>=$num) {
                        $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                        $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'pay_price' => $getPayPrice, 'scene' => 2, 'pick_type' => 1, 'channel' => 'missshop', 'insert_time' => time(), 'pid' => $pid);
                        Db::name('activity_order')->insert($arr);
                        $res['order_sn'] = $ordersn;
                        $res['attach'] = 'missshop';
                        $res['total_fee'] = $getPayPrice;
                        $res['user_id'] = $uid;
                        $res['buy_type'] = 3;
                        $res['body'] = $goodsInfo['name'];
                        $code = 1;
                        $data = $res;
                        $msg = '订单已生成，去付款！';
                    }else{
                        $code = 0;
                        $data = '';
                        $msg = '失败，产品库存还剩'.$stock.'个';
                    }
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '您已经达到购买上线，单人仅限' . $goodsInfo['allow_buy_num'] . '次！';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '错误，请重新登陆';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '失败，产品库存还剩'.$stock.'个';
        }
        return parent::returnMsg($code,$data,$msg);
    }

}