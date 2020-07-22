<?php

namespace app\api\controller;
use app\api\model\GoodsModModel;
use app\api\model\GoodsSpecsModel;
use think\Controller;
use think\Db;

/**
 * desc:missshop双十二活动
 */
class Miss12 extends Base
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
    public function check_activity($id,$uid=0){
//        $flag=true;
        $activityInfo=Db::name('activity_config')->where('id',$id)->cache(86400)->find();
//        if($uid){
//            $user=Db::name('double12_user')->where('uid',$uid)->count();
//            if($user){
//                $flag=false;
//            }
//        }
        if($activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动已结束！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
//        if($flag && $id==5) {
            if ($activityInfo['begin_time'] > time()) {
                $code = 0;
                $data = '';
                $msg = '活动将于' . date('Y年m月d日 H时i分s秒', $activityInfo['begin_time']) . '开启，请等待！';
                echo json_encode(array('code' => $code, 'data' => $data, 'msg' => $msg));
                exit;
            }
//        }
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
        $uid=input('param.uid');
        if($pid && $uid){
            $info=Db::name('goods')->where(['id'=>$pid,'goods_cate'=>4])->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content,model_id')->cache(86400)->find();
            if($info && $info['status']){
                if($info && $info['model_id']){
                    $goods_model=new GoodsModModel();
                    $goods_specs=new GoodsSpecsModel();
                    $specs=$goods_model->getModelValue($info['model_id'],'model_specs');
                    $map['id']=array('in',$specs);
                    $map['specs_status']=array('eq',1);
                    $info['specs']=$goods_specs->goodsSpecs($pid,$map,'specs_order desc');
                    if(is_array($info['specs']) && count($info['specs'])){
                        foreach ($info['specs']['list'] as $k=>$v){
                           parent::setCacheString('ms_good'.$info['id'].$v['sku'],$v['store_count']);//将产品库存记录到redis
                           $info['specs']['list'][$k]['store_count']=parent::getCacheString('ms_good'.$info['id'].$v['sku']);
                        }
                    }
                }else{
                    $info['specs']=[];
                }
                parent::setCacheString('ms_good'.$info['id'],$info['stock']);//将产品库存记录到redis
                $info['stock']=parent::getCacheString('ms_good'.$info['id']);
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

                $getUserOrder = parent::hashGet('missshop', $uid . '_' . $pid);
                if ($getUserOrder) {
                    $getOrderCount = $getUserOrder;
                } else {
                    $map=[];
                    $map['uid'] = array('eq', $uid);
                    $map['pid'] = array('eq', $pid);
                    $map['pay_status'] = array('eq',1);
                    $map['channel'] = array('eq', 'missshop');
                    $getOrderCount = Db::name('activity_order')->where($map)->sum('num');
                }

                $info['user_buy_num']=$getOrderCount;

                $checkUser=Db::name('double12_user')->where(['uid'=>$uid,'pid'=>0])->count();
                if($checkUser){
                    $info['double12Role']='体验官';
                    $surplus_num=9-$getOrderCount;
                    $info['buy_surplus']=$surplus_num>0?$surplus_num:0;
                    $info['activity_price']=$surplus_num==0?$info['price']:$info['activity_price'];
                }else{
                    $info['double12Role']='非体验官';
                    $surplus_num=1-$getOrderCount;
                    $info['buy_surplus']=$surplus_num>0?$surplus_num:0;
                    $info['activity_price']=$surplus_num<=0?$info['price']:$info['activity_price'];
                }
                if($pid==134){
                    $info['buy_surplus']=0;
                    $info['activity_price']=$info['activity_price'];
                }

//                $activity_yr=Db::name('activity_config')->where('id',4)->cache(86400)->find();
//                $info['yr_begin_time']=$activity_yr['begin_time'];
//                $info['yr_end_time']=$activity_yr['end_time'];
//                $activity_zs=Db::name('activity_config')->where('id',5)->cache(86400)->find();
//                $info['zs_begin_time']=$activity_zs['begin_time'];
//                $info['zs_end_time']=$activity_zs['end_time'];

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
     *  预热阶段下单
     *
     * */
	public function createOrder(){
        $this->check_activity(4);
        $pid=input('param.pid');
        $sku=input('param.sku');
        $num = input('param.num', 1);//购买数量
        $specs = input('param.specs','');//规格
        $stock=parent::getCacheString('ms_good'.$pid.$sku);
        if($stock>=$num) {
            $uid = input('param.uid');
            if($uid) {
                $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content,model_id')->cache(86400)->find();
                $fid = input('param.fid', $uid);//引导分享uid
                $getPayPrice = $goodsInfo['activity_price'] * $num;
                $getUserOrder = parent::hashGet('missshop', $uid . '_' . $pid);
                if ($getUserOrder) {
                    $getOrderCount = $getUserOrder;
                } else {
                    $map['uid'] = array('eq', $uid);
                    $map['pid'] = array('eq', $pid);
                    $map['pay_status'] = array('eq',1);
                    $map['channel'] = array('eq', 'missshop');
                    $getOrderCount = Db::name('activity_order')->where($map)->count();
                }
                if (($getOrderCount < $goodsInfo['allow_buy_num'])) {
                    //获取购买者的上级uid
                    $fidInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.sign,b.join_tk')->find();
                    $sellerId = $fidInfo['staffid'];
                    $storeid = $fidInfo['storeid'];
                    //密丝小铺门店用户重新归属
                    if($storeid==1550){
                        $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
                        Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
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
                    if ($sellerId == 27291) {
                        $code = 0;
                        $data = '';
                        $msg = '分享码错误';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    $stock1=parent::getCacheString('ms_good'.$pid.$sku);
                    if($stock1>=$num) {
                        $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                        $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'pay_price' => $getPayPrice, 'scene' => 3, 'pick_type' => 1,'specs'=>$specs,'sku'=>$sku, 'channel' => 'missshop', 'insert_time' => time(), 'pid' => $pid);
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
                        $stock1=$stock1?$stock1:0;
                        $code = 0;
                        $data = '';
                        $msg = '失败，'.$specs.'库存还剩'.$stock1.'个';
                    }
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '您已经达到购买上限，每人仅限' . $goodsInfo['allow_buy_num'] . '次！';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '错误，请重新登陆';
            }
        }else{
            $stock=$stock?$stock:0;
            $code = 0;
            $data = '';
            $msg = '失败，'.$specs.'库存还剩'.$stock.'个';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //顾客购买列表 美容师看顾客 顾客店老板看自己
    public function orderList(){
        $uid=input('param.uid');
        if($uid!='') {
            $userInfo = Db::table('ims_bj_shopn_member')->field('id,staffid,code,isadmin')->where('id', $uid)->find();
            if ($userInfo) {
                if ($userInfo['isadmin']) {
                    $map['uid'] = array('eq', $uid);
                } elseif (($userInfo['id'] == $userInfo['staffid']) || strlen($userInfo['code']) > 1) {
                    $map['fid'] = array('eq', $uid);
                } else {
                    $map['uid'] = array('eq', $uid);
                }
                $map['scene'] = array('eq', 3);
                $map['pay_status'] = array('eq', 1);
                $map['channel'] = array('eq', 'missshop');
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 10;// 显示条数
                $count = Db::name('activity_order')->where($map)->count();
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage >= $allpage) {
                    $info['next_page_flag'] = 0;//是否有下一页
                } else {
                    $info['next_page_flag'] = 1;
                }
                $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'], 'o.uid=m.id', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->join('goods p', 'o.pid=p.id', 'left')->field('uid,fid,o.pid,pay_status,order_status,pay_price,order_price,order_sn,m.realname,m.mobile,u.avatar,u.nickname,num,pick_code,pick_type,o.specs,p.name p_name,p.unit,p.images p_images,o.is_axs')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
                if (count($list) && is_array($list)) {
                    foreach ($list as $k => $v) {
                        $list[$k]['allow_draw'] = 0;
                        $list[$k]['lucky_flag'] = 0;
                        $list[$k]['lucky_draw'] = '';
                        $list[$k]['p_name'] = $v['p_name'] . ' ' . $v['specs'];
                        $list[$k]['pay_status'] = $v['pay_status'] ? '已支付' : '未付款';
                        $list[$k]['order_status'] = $v['order_status'] ? '已收货' : '未收货';
                        $list[$k]['order_price'] = $v['pay_price'] ? $v['pay_price'] : 0;
                        $list[$k]['double12_user'] = Db::name('double12_user')->where(['uid'=>$v['uid'],'pid'=>0])->count();
                        $branchInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch' => 'b'], 'm.storeid=b.id', 'left')->where('m.id', $v['fid'])->field('m.realname,m.mobile,b.title,b.sign,b.address')->find();
                        $list[$k]['pick_info'] = $branchInfo;
                        if ($v['pick_type'] == 1 && $v['pick_code'] == '') {
                            $codeUrl = pickUpCode('missshop_' . $v['order_sn']);
                            if ($codeUrl) {
                                $list[$k]['pick_code'] = $codeUrl;
                                Db::name('activity_order')->where('order_sn', $v['order_sn'])->update(['pick_code' => $codeUrl]);
                            } else {
                                $list[$k]['pick_code'] = '';
                            }
                        }
                    }
                    $info['list'] = $list;
                    $code = 1;
                    $data = $info;
                    $msg = '获取成功';
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '暂无订单';
                }
            } else {
                $code = 0;
                $data = '';
                $msg = '参数错误！';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //允许该用户参加双十二活动
    public function double12User(){
        $uid=input('param.uid');
        $count=Db::name('double12_user')->where(['uid'=>$uid,'pid'=>0])->count();
        if(!$count){
            Db::name('double12_user')->insert(['uid'=>$uid]);
        }
        return parent::returnMsg(1,'','已通过');
    }


    /**
     * 正式阶段下单
     * @return \think\response\Json
     */
    public function createOrder1(){
        $uid = input('param.uid');
        $this->check_activity(4,$uid);
        $pid=input('param.pid');
        $sku=input('param.sku');
        $num = input('param.num', 3);//购买数量
        $specs = input('param.specs','');//规格
        $stock=parent::getCacheString('ms_good'.$pid.$sku);
        if($stock>=$num) {
            if($uid) {
                //获取客户允许购买盒数
                $buyCount=Db::name('double12_user')->where(['uid'=>$uid,'pid'=>0])->count();
                if($buyCount){
                    $allowBuy=9;
                }else{
                    $allowBuy=0;
                }
                $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content,model_id')->cache(86400)->find();
                $fid = input('param.fid', $uid);//引导分享uid

                $getUserOrder = parent::hashGet('missshop', $uid . '_' . $pid);
                if ($getUserOrder) {
                    $getOrderCount = $getUserOrder;
                } else {
                    $map['uid'] = array('eq', $uid);
                    $map['pid'] = array('eq', $pid);
                    $map['pay_status'] = array('eq',1);
                    $map['channel'] = array('eq', 'missshop');
                    $getOrderCount = Db::name('activity_order')->where($map)->sum('num');
                }

                //购买量小于9 优惠价格购买  大于9 原价购买
                if($getOrderCount<$allowBuy){
                    if (($getOrderCount+$num)>$allowBuy) {
                        $code = 0;
                        $data = '';
                        $msg = '单人仅限优惠购买'.$allowBuy.'盒！你已经购买'.$getOrderCount.'盒';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    $getPayPrice = $goodsInfo['activity_price'] * $num;
                }else{
                    $getPayPrice = $goodsInfo['price'] * $num;
                }

                //获取购买者的上级uid
                $fidInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch' => 'b'], 'm.storeid=b.id', 'left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.sign,b.join_tk')->find();
                $sellerId = $fidInfo['staffid'];
                $storeid = $fidInfo['storeid'];
                //密丝小铺门店用户重新归属
                if ($storeid == 1550) {
                    $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
                    Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
                }
                if ($fidInfo['sign'] == '000-000') {
                    $code = 0;
                    $data = '';
                    $msg = '您为办事处人员，无活动商品购买权限！';
                    return parent::returnMsg($code, $data, $msg);
                }
                //密丝小铺门店用户禁止下单
                if ($storeid == 1550) {
                    $code = 0;
                    $data = '';
                    $msg = '请联系您的所属美容师，再进行活动商品购买';
                    return parent::returnMsg($code, $data, $msg);
                }
                if ($sellerId == 27291) {
                    $code = 0;
                    $data = '';
                    $msg = '分享码错误';
                    return parent::returnMsg($code, $data, $msg);
                }
                $stock1 = parent::getCacheString('ms_good' . $pid . $sku);
                if ($stock1 >= $num) {
                    $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                    $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'pay_price' => $getPayPrice, 'scene' => 3, 'pick_type' => 1, 'specs' => $specs, 'sku' => $sku, 'channel' => 'missshop', 'insert_time' => time(), 'pid' => $pid);
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
                    $stock1=$stock1?$stock1:0;
                    $code = 0;
                    $data = '';
                    $msg = '失败，' . $specs . '库存还剩' .$stock1 . '个';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '错误，请重新登陆';
            }
        }else{
            $stock=$stock?$stock:0;
            $code = 0;
            $data = '';
            $msg = '失败，'.$specs.'库存还剩'.$stock.'个';
        }
        return parent::returnMsg($code,$data,$msg);
    }




}