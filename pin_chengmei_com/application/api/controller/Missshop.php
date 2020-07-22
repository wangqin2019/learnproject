<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:missshop销货活动
 */
class Missshop extends Base
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
        $activityInfo=Db::name('new_year_config')->where('id',2)->cache(60)->find();
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

    /**
     * 查看用户是否已经参与过活动
     * @return \think\response\Json
     */
    public function check_user()
    {
        $uid=input('param.uid');
        if($uid!=''){
            $map['uid']=array('eq',$uid);
            $info = Db::name('pk_order')->field('uid,order_status,pay_status,order_price,order_sn,sales_uid fid,num')->where($map)->find();
            if(count($info) && is_array($info)){
                if($info['pay_status']){
                    if($info['order_status']==0){
                        $code = 1;
                        $data = array('flag'=>2,'info'=>$info);
                        $msg = '订单已付款，未收货！';
                    }else {
                        $code = 1;
                        $data = array('flag' => 1, 'info' => $info);
                        $msg = '已参加过活动！';
                    }
                }else{
                    $this->check_activity();
                    $code = 1;
                    $data = array('flag'=>3,'info'=>$info);
                    $msg = '订单已生成，但未付款，去付款！';
                }
            }else{
                $this->check_activity();
                $payPrice=Db::name('new_year_config')->where('id',2)->value('price');
                $code = 1;
                $data = array('flag'=>4,'info'=>['price'=>$payPrice]);
                $msg = '没参与过，可继续';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 创建订单
     *
     * */
    public function createOrder(){
        $this->check_activity();
        $payPrice=Db::name('new_year_config')->where('id',2)->value('price');
        $uid = input('param.uid');
        $fid = input('param.fid');//通过扫街二维码进来的uid
        $num = input('param.num',1);//购买数量
        $getPayPrice=$payPrice*$num;
        $storeid = input('param.storeid');
        $map['uid'] = array('eq', $uid);
        $info = Db::name('pk_order')->field('uid,pay_status,order_sn,num')->where($map)->find();
        if (count($info) && is_array($info)) {
            if ($info['pay_status']) {
                $code = 0;
                $data = $info;
                $msg = '您已经购买过了，单人仅限一次！';
            } else {
                $res['order_sn'] = $info['order_sn'];
                $res['attach'] = 'activity';
                $res['total_fee'] = $getPayPrice;
                $res['user_id'] = $uid;
                $res['buy_type'] = 2;
                $res['body'] = '2019PK赛密丝小铺扫街活动';
                $code = 1;
                $data = $res;
                $msg = '订单已生成，去付款！';
            }
        } else {
            try {
                $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . $uid;
                $arr = array('uid' => $uid, 'storeid'=>$storeid, 'sales_uid'=>$fid,'num' => $num,'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'insert_time' => time());
                Db::name('pk_order')->insert($arr);
                $res['order_sn'] = $ordersn;
                $res['attach'] = 'activity';
                $res['total_fee'] = $getPayPrice;
                $res['user_id'] = $uid;
                $res['buy_type'] = 2;
                $res['body'] = '2019PK赛密丝小铺扫街活动';
                $code = 1;
                $data = $res;
                $msg = '订单已生成，去付款！';
            } catch (\Exception $e) {
                $code = 0;
                $data = '';
                $msg = '订单生成失败';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //用户确认订单
    public function confirmOrder(){
        $ordersn=input('param.ordersn');
        if($ordersn!=''){
            Db::name('pk_order')->where('order_sn',$ordersn)->update(['order_status'=>1]);
            $code = 1;
            $data = '';
            $msg = '收货成功！';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

//    public function send(){
//        sendMessage('15821881959',[],104);
//    }


    //美容师下顾客购买列表
    public function sale_order(){
        $uid=input('param.uid');
        if($uid!=''){
            $map['sales_uid']=array('eq',$uid);
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 1000;// 显示条数
            $count = Db::name('pk_order')->field('uid,pay_status,order_status,order_price,order_sn,num')->where($map)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = Db::name('pk_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('uid,pay_status,order_status,pay_price order_price,order_sn,u.avatar,u.nickname,num')->where($map)->page($Nowpage, $limits)->select();
            if(count($list) && is_array($list)){
                foreach ($list as $k=>$v){
                    $list[$k]['pay_status']=$v['pay_status']?'已支付':'未付款';
                    $list[$k]['order_status']=$v['order_status']?'已收货':'未收货';
                    $list[$k]['order_price']=$v['order_price']?$v['order_price']:0;
                }
                $info['list']=$list;
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




}