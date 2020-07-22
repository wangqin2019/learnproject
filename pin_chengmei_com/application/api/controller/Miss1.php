<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:missshop活动
 */
class Miss1 extends Base
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

    //产品信息
    public function goodsInfo(){
        $pid=input('param.pid');
        if($pid){
            $info=Db::name('goods')->where(['id'=>$pid,'goods_cate'=>4])->field('id,name,unit,image,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1')->cache(86400)->find();
            if($info && $info['status']){
                $this->setCacheString('ms_good'.$info['id'],$info['stock']);//将产品库存记录到redis
                $info['stock']=$this->getCacheString('ms_good'.$info['id']);
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

    //检测用户购买数量
    public function checkUser(){
        $uid = input('param.uid');
        $pid=input('param.pid');
        $stock=$this->getCacheString('ms_good'.$pid);
        if($stock>0) {
            $getUserOrder=$this->hashGet('missshop',$uid.'_'.$pid);
            if($getUserOrder){
                $getOrderCount = $getUserOrder;
            }else{
                $getOrderCount = Db::name('activity_order')->where(['uid' => $uid, 'pid' => $pid, 'pay_status' => 1, 'channel' => 'missshop'])->count();
            }
            if ($getOrderCount) {
                $info = Db::name('activity_order')->where(['uid' => $uid, 'pid' => $pid, 'pay_status' => 1, 'channel' => 'missshop', 'order_status' => 0])->field('order_sn,order_status,pick_type')->find();
                if ($info && $info['order_status'] == 0 && $info['pick_type'] == 0) {
                    $code = 2;
                    $data = $info;
                    $msg = '有未收货订单';
                    return parent::returnMsg($code, $data, $msg);
                }
            }
            $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,status,stock,activity_price,allow_buy_num,stock_limit,allow_buy_num1')->find();
            if (($getOrderCount < $goodsInfo['allow_buy_num']) || $goodsInfo['allow_buy_num'] == 0) {
                $code = 1;
                $data = '';
                $msg = '允许购买';
            } else {
                $code = 0;
                $data = '';
                $msg = '不允许购买';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '该产品已售完';
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
        if($stock>$num) {
            $uid = input('param.uid');
            if($uid) {
                $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,status,stock,activity_price,allow_buy_num,stock_limit,allow_buy_num1')->cache(86400)->find();
                $fid = input('param.fid', $uid);//引导分享uid
                $storeid = input('param.storeid');
                $getPayPrice = $goodsInfo['activity_price'] * $num;
                $getUserOrder = $this->hashGet('missshop', $uid . '_' . $pid);
                if ($getUserOrder) {
                    $getOrderCount = $getUserOrder;
                } else {
                    $map['uid'] = array('eq', $uid);
                    $map['pid'] = array('eq', $pid);
                    $map['pay_status'] = array('eq', 1);
                    $map['channel'] = array('eq', 'missshop');
                    $getOrderCount = Db::name('activity_order')->where($map)->count();
                }
                if (($getOrderCount < $goodsInfo['allow_buy_num']) || $goodsInfo['allow_buy_num'] == 0) {
                    //获取购买者的上级uid
                    $fidInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.join_tk')->find();
                    //推广人和所属美容师师是同一人
                    if ($fid == $fidInfo['staffid']) {
                        $sellerId = $fidInfo['staffid'];
                        $storeid = $fidInfo['storeid'];
                    } else {
                        if ($fidInfo['isadmin'] == 1 || strlen($fidInfo['code']) > 1) {
                            $sellerId = $fidInfo['staffid'];
                            $storeid = $fidInfo['storeid'];
                        } else {
                            if ($fidInfo['activity_flag'] == 8805 || $fidInfo['activity_flag'] == 8806  || $fidInfo['activity_flag'] == 8808 || $fidInfo['activity_flag'] == 8809) {
                                $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
                                //检测当前用户标识，如果是8805 8806 8808 8809，没有下过单，将用户三级关系绑定到当前fid
                                $haveBuy=Db::name('activity_order')->where(['channel'=>'missshop','pay_status'=>1,'uid'=>$uid])->count();
                                if(!$haveBuy){
                                    Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
                                    $sellerId = $fid;
                                    $storeid = $getFidBid;
                                }else{
                                    $sellerId = $fidInfo['staffid'];
                                    $storeid = $fidInfo['storeid'];
                                }
                            }else{
                                $sellerId = $fidInfo['staffid'];
                                $storeid = $fidInfo['storeid'];
                            }
                            //密丝小铺门店用户重新归属
                            if($storeid==1550){
                                Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
                                $sellerId = $fid;
                                $storeid = $getFidBid;
                            }
                        }
                    }
                    if ($sellerId == 27291) {
                        $code = 0;
                        $data = '';
                        $msg = '分享码错误';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    if(strlen($fidInfo['join_tk'])){
                        $join_tk_arr=explode(',',$fidInfo['join_tk']);
                        if($pid==47){
                            if(!in_array(1,$join_tk_arr)){
                                $code = 0;
                                $data = '';
                                $msg = '您所在门店没有开通活动，请联系所属美容师';
                                return parent::returnMsg($code, $data, $msg);
                            }
                        }
                        if($pid==79){
                            if(!in_array(2,$join_tk_arr)){
                                $code = 0;
                                $data = '';
                                $msg = '您所在门店没有开通活动，请联系所属美容师';
                                return parent::returnMsg($code, $data, $msg);
                            }
                        }
                    }else{
                        $code = 0;
                        $data = '';
                        $msg = '您所在门店没有开通活动，请联系所属美容师';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                    $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'pay_price' => $getPayPrice, 'channel' => 'missshop', 'insert_time' => time(), 'pid' => $pid);
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
            $msg = '该产品库存不足';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //用户确认订单
    public function confirmOrder(){
        $ordersn=input('param.ordersn');
        $type=input('param.type',0);
        if($ordersn!=''){
            if($type){
                $codeUrl = pickUpCode('missshop_'.$ordersn);
                if ($codeUrl) {
                    $branchInfo['codeUrl'] = $codeUrl;
                }else{
                    $branchInfo['codeUrl'] = '';
                }
                Db::name('activity_order')->where('order_sn',$ordersn)->update(['pick_type'=>1,'pick_code'=>$branchInfo['codeUrl']]);
            }else{
                Db::name('activity_order')->where('order_sn',$ordersn)->update(['order_status'=>1]);

            }
            $code = 1;
            $data = '';
            $msg = '确认成功！';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //购买列表
    public function orderList(){
        $uid=input('param.uid');
        if($uid!=''){
            $map['uid']=array('eq',$uid);
            $map['pay_status']=array('eq',1);
            $map['channel'] = array('eq', 'missshop');
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 1000;// 显示条数
            $count = Db::name('activity_order')->where($map)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->join('goods p','o.pid=p.id','left')->field('uid,fid,o.pid,pay_status,order_status,pay_price,order_price,order_sn,u.avatar,u.nickname,num,pick_code,pick_type,o.specs,p.name p_name,p.unit,m.isadmin,m.code')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
            if(count($list) && is_array($list)){
                foreach ($list as $k=>$v){
                    if($v['pid']==47 || $v['pid']==79){
                        $list[$k]['allow_draw']=0;
                        $list[$k]['lucky_flag']=0;
                        $list[$k]['lucky_draw']='';
                    }else{
                        if($v['isadmin']==1 || strlen($v['code'])>1){
                            $list[$k]['allow_draw']=0;
                            $list[$k]['lucky_flag']=0;
                            $list[$k]['lucky_draw']='';
                        }else{
                            $list[$k]['allow_draw']=1;
                            $lucky=Db::name('order_lucky')->where('order_sn',$v['order_sn'])->field('lucky_name,lucky_image,flag,qrcode')->find();
                            if($lucky){
                                $lucky['flag']=$lucky['flag']?'已领取':'未领取';
                                $list[$k]['lucky_flag']=1;
                                $list[$k]['lucky_draw']=$lucky;
                            }else{
                                $list[$k]['lucky_flag']=0;
                                $list[$k]['lucky_draw']='';
                            }
                        }
                    }
                    $list[$k]['p_name']=$v['p_name'].' '.$v['specs'];
                    $list[$k]['pay_status']=$v['pay_status']?'已支付':'未付款';
                    $list[$k]['order_status']=$v['order_status']?'已收货':'未收货';
                    $list[$k]['order_price']=$v['pay_price']?$v['pay_price']:0;
                    $branchInfo=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id',$v['fid'])->field('m.realname,m.mobile,b.title,b.sign,b.address')->find();
                    $list[$k]['pick_info']=$branchInfo;
                    if($v['pick_type']==1 && $v['pick_code']==''){
                        $codeUrl = pickUpCode('missshop_'.$v['order_sn']);
                        if ($codeUrl) {
                            $list[$k]['pick_code'] = $codeUrl;
                            Db::name('activity_order')->where('order_sn',$v['order_sn'])->update(['pick_code'=>$codeUrl]);
                        }else{
                            $list[$k]['pick_code']= '';
                        }
                    }
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


    //美容师销售统计
    public function saleOrder(){
        $map['o.pay_status']=array('eq',1);
        $map['o.channel'] = array('eq', 'missshop');
        $Nowpage = input('param.page') ? input('param.page') : 1;
        $limits = 10;// 显示条数
        $count = Db::name('activity_order')->alias('o')->field('uid')->where($map)->count('distinct fid');
        $allpage = intval(ceil($count / $limits));
        if ($Nowpage >= $allpage) {
            $info['next_page_flag']=0;//是否有下一页
        }else{
            $info['next_page_flag']=1;
        }
        $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.fid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('m.realname,m.mobile,u.nickname,u.avatar,count(o.id) count,sum(o.pay_price) total_price,sum(o.num) total_goods')->where($map)->page($Nowpage, $limits)->group('o.fid')->order('total_goods desc')->select();
        if(count($list) && is_array($list)){
            $info['list']=$list;
            $code = 1;
            $data = $info;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂无订单';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //美容师下顾客购买列表
    public function SellerSale(){
        $uid=input('param.uid');
        if($uid!=''){
            $map['fid']=array('eq',$uid);
            $map['pay_status']=array('eq',1);
            $map['channel'] = array('eq', 'missshop');
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count = Db::name('activity_order')->where($map)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.uid=m.id','left')->join('wx_user u','m.mobile=u.mobile','left')->join('goods p','o.pid=p.id','left')->field('uid,fid,o.pid,pay_status,order_status,pay_price,order_price,order_sn,m.realname,m.mobile,u.avatar,u.nickname,num,pick_code,pick_type,o.specs,p.name p_name,p.unit')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
            if(count($list) && is_array($list)){
                foreach ($list as $k=>$v){
                    if($v['pid']==47 || $v['pid']==79){
                        $list[$k]['allow_draw']=0;
                        $list[$k]['lucky_flag']=0;
                        $list[$k]['lucky_draw']='';
                    }else{
                        $list[$k]['allow_draw']=1;
                        $lucky=Db::name('order_lucky')->where('order_sn',$v['order_sn'])->field('lucky_name,lucky_image,flag,qrcode')->find();
                        if($lucky){
                            $lucky['flag']=$lucky['flag']?'已领取':'未领取';
                            $list[$k]['lucky_flag']=1;
                            $list[$k]['lucky_draw']=$lucky;
                        }else{
                            $list[$k]['lucky_flag']=0;
                            $list[$k]['lucky_draw']='';
                        }
                    }
                    $list[$k]['p_name']=$v['p_name'].' '.$v['specs'];
                    $list[$k]['pay_status']=$v['pay_status']?'已支付':'未付款';
                    $list[$k]['order_status']=$v['order_status']?'已收货':'未收货';
                    $list[$k]['order_price']=$v['pay_price']?$v['pay_price']:0;
                    $branchInfo=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id',$v['fid'])->field('m.realname,m.mobile,b.title,b.sign,b.address')->find();
                    $list[$k]['pick_info']=$branchInfo;
                    if($v['pick_type']==1 && $v['pick_code']==''){
                        $codeUrl = pickUpCode('missshop_'.$v['order_sn']);
                        if ($codeUrl) {
                            $list[$k]['pick_code'] =  $codeUrl;
                        }else{
                            $list[$k]['pick_code']= '';
                        }
                    }
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


    //missshop活动产品列表
    public function goods_list(){
        $map['goods_cate']=array('eq',4);
        $map['id']=array('not in',['47','79']);
        $map['status']=array('eq',1);
        $list=Db::name('goods')->where($map)->field('id,name,image,price,activity_price')->order('orderby')->select();
        if($list){
            $code = 1;
            $data = $list;
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
        $info=Db::name('goods')->field('id,name,images,xc_images,price,activity_price,model_id,content,status,stock,video,allow_buy_num,stock_limit,allow_buy_num1')->where('id',$id)->find();
        if($info && $info['model_id']){
            $specs=Db::name('goods_model')->where('id',$info['model_id'])->value('model_specs');
            $map['id']=array('in',$specs);
            $map['specs_status']=array('eq',1);
            $info['specs']=Db::name('goods_specs')->where($map)->field('specs_name,specs_item')->order('specs_order')->select();
        }else{
            $info['specs']=[];
        }
        if($info){
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
            $this->setCacheString('ms_good'.$info['id'],$info['stock']);//将产品库存记录到redis
            $info['stock']=$this->getCacheString('ms_good'.$info['id']);
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



    /*
     * 创建missshop订单
     *
     * */
    public function create_order(){
        $this->check_activity();
        $pid=input('param.pid');
        $num = input('param.num', 1);//购买数量
        $specs = input('param.specs','');//规格
        $ticket_id = input('param.ticket_id',0);//现金券
        $stock=$this->getCacheString('ms_good'.$pid);
        if($ticket_id){
            $cash_code=$this->saddSismember('cash_code',$ticket_id);
            if($cash_code) {
                return parent::returnMsg(0,'','现金券已使用');
            }
        }
        if($stock>$num) {
            $uid = input('param.uid');
            if($uid){
                $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,status,stock,activity_price,allow_buy_num,stock_limit,allow_buy_num1')->cache(86400)->find();
                $fid = input('param.fid',$uid);//引导分享uid
                $getPayPrice = $goodsInfo['activity_price'] * $num;
                $getUserOrder=$this->hashGet('missshop',$uid.'_'.$pid);
                if($getUserOrder){
                    $getOrderCount = $getUserOrder;
                }else{
                    $map['uid'] = array('eq', $uid);
                    $map['pid'] = array('eq', $pid);
                    $map['pay_status'] = array('eq', 1);
                    $map['channel'] = array('eq', 'missshop');
                    $getOrderCount = Db::name('activity_order')->where($map)->count();
                }
                if (($getOrderCount < $goodsInfo['allow_buy_num']) || $goodsInfo['allow_buy_num'] == 0) {
                    //获取购买者的上级uid
                    //$fidInfo = Db::table('ims_bj_shopn_member')->where('id', $uid)->field('code,isadmin,storeid,staffid,activity_flag')->find();
                    $fidInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.join_tk')->find();
                    $sellerId = $fidInfo['staffid'];
                    $storeid = $fidInfo['storeid'];
                    //密丝小铺门店用户重新归属
                    if($storeid==1550){
                        $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
                        Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
                        $sellerId = $fid;
                        $storeid = $getFidBid;
                    }
                    if($sellerId==27291){
                        $code = 0;
                        $data = '';
                        $msg = '分享码错误';
                        return parent::returnMsg($code,$data,$msg);
                    }
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
                    if($ticket_id){
                        $cashInfo = Db::name('ticket_user')->where('id', $ticket_id)->field('status,par_value')->find();
                        if ($cashInfo) {
                            if($cashInfo['status']){
                                $code = 0;
                                $data = '';
                                $msg = '现金券已过期';
                                return parent::returnMsg($code,$data,$msg);
                            }
                            $cash_value = $cashInfo['par_value'];
                        }else{
                            $code = 0;
                            $data = '';
                            $msg = '现金券错误';
                            return parent::returnMsg($code,$data,$msg);
                        }
                    }else{
                        $cash_value = 0;
                    }
                    $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                    $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'pay_price' => $getPayPrice - $cash_value, 'coupon_price' => $cash_value, 'coupon_id' => $ticket_id, 'channel' => 'missshop', 'pick_type' => 1, 'insert_time' => time(), 'pid' => $pid, 'scene' => 1, 'specs' => $specs);
                    Db::name('activity_order')->insert($arr);
                    $res['order_sn'] = $ordersn;
                    $res['attach'] = 'missshop';
                    $res['total_fee'] = $getPayPrice - $cash_value;
                    $res['user_id'] = $uid;
                    $res['buy_type'] = 3;
                    $res['body'] = $goodsInfo['name'];
                    $code = 1;
                    $data = $res;
                    $msg = '订单已生成，去付款！';
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
            $msg = '该产品库存不足';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //打开红包 发放奖券
    public function grant_ticket(){
        $mobile=input('param.mobile');
        if($mobile!='') {
            $check=parent::saddSismember('user_ticket',$mobile);
            if (!$check) {
                try{
                    parent::saddset('user_ticket',$mobile);
                    $getUid=Db::table('ims_bj_shopn_member')->where('mobile',$mobile)->value('id');
                    $tickets = ['11','12','13','14','15','16','17'];
                    foreach ($tickets as $k=>$v){
                        $ticketImg=Db::name('draw_scene')->where('scene_prefix',$v)->value('image1');
                        sendTicket($getUid,$v,$ticketImg);
                    }
                    $arr = array('uid' => $getUid, 'title' => '心"肌"大礼包发放通知', 'content' => '系统给你发放了心"肌"大礼包(指定活动体验券，专业皮肤检测券)，请至我的卡券中查看');
                    sendDrawQueue($arr);
                    $code = 1;
                    $data = '';
                    $msg = '卡券已放进卡包';
                }catch (\Exception $e){
                    $code = 0;
                    $data = '';
                    $msg = '参数错误'.$e->getMessage();
                }
            }else{
                $code = 1;
                $data = '';
                $msg = '卡券已放进卡包';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }



    //获取当前已激活的代金券
    public function my_cash_ticket(){
        $mobile=input('param.mobile');
        $price=input('param.price',0);
        if($mobile!='') {
            if($price){
                $map['price']=array('elt',$price);
            }
            $map['mobile']=array('eq',$mobile);
            $map['type']=array('eq',10);
            $map['status']=array('eq',0);
            $list = Db::name('ticket_user')->where($map)->field('id ticket_id,ticket_code,par_value,price,remark')->select();
            if ($list) {
                $code = 1;
                $data = $list;
                $msg = '现金券获取成功';
            } else {
                $code = 0;
                $data = [];
                $msg = '暂无可用现金券';
            }
        }else{
            $code = 0;
            $data = [];
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //通过拓客活动注册 但未产生购买的用户
    public function no_buyer()
    {
        $uid = input('param.uid');
        if($uid!=''){
            $buyerIds = Db::name('activity_order')->where(['channel' => 'missshop', 'pay_status' => 1, 'scene' => 1, 'fid' => $uid])->column('uid');
            $map['m.staffid'] = array('eq', $uid);
            $map['m.activity_flag'] = array('eq', 8808);
            if ($buyerIds) {
                $map['m.id'] = array('not in', $buyerIds);
            }
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count =Db::table('ims_bj_shopn_member')->alias('m')->where($map)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = Db::table('ims_bj_shopn_member')->alias('m')->where($map)->field('m.id,m.realname,m.mobile,m.createtime,u.avatar,u.nickname')->join('wx_user u','m.mobile=u.mobile','left')->page($Nowpage, $limits)->select();
            if ($list) {
                foreach ($list as $k=>$v){
                    if($v['avatar']==''){
                        $list[$k]['avatar']=config('qiniu.image_url').'/avatar.png';
                        $list[$k]['nickname']=$v['realname'];
                    }
                    $list[$k]['createtime']=date('Y-m-d H:i:s',$v['createtime']);
                }
                $info['list']=$list;
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            } else {
                $code = 0;
                $data = '';
                $msg = '暂无数据';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //分享记录
    public function activity_share(){
        $uid=input('param.uid');
        $mobile=input('param.mobile');
        $fid=input('param.fid');
        $storeid=input('param.storeid');
        $item=input('param.item');
        if($uid!='' && $mobile!=''){
            $insert=array('uid'=>$uid,'mobile'=>$mobile,'storeid'=>$storeid,'fid'=>$fid,'item'=>$item,'insert_time'=>time());
            Db::name('activity_share')->insert($insert);
            $code = 1;
            $data = '';
            $msg = '成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

}