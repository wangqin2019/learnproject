<?php

namespace app\api\controller;
use app\api\model\ActivityOrderModel;
use app\api\model\GoodsModel;
use app\api\model\GoodsModModel;
use app\api\model\GoodsSpecsModel;
use think\Controller;
use think\Db;

/**
 * desc:年终裂变活动
 */
class YearFission extends Base
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

//     public function activity_status(){
//         $activityInfo=Db::name('activity_config')->where('id',6)->find();
//         if($activityInfo['activity_status']==1){
//             $code = 1;
//             $data = '';
//             $msg = '活动可以显示';
//         }else{
//             $code = 0;
//             $data = '';
//             $msg = '活动已结束！';
//         }
//         return parent::returnMsg($code,$data,$msg);
//     }

    //检测活动
    public function check_activity($id){
        $activityInfo=Db::name('activity_config')->where('id',$id)->find();
        if($activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动已结束！';
            return parent::returnMsg($code,$data,$msg);
        }
        if ($activityInfo['begin_time'] > time()) {
            $code = 0;
            $data = '';
            $msg = '活动将于' . date('Y年m月d日 H时i分s秒', $activityInfo['begin_time']) . '开启，请等待！';
            return parent::returnMsg($code,$data,$msg);
        }
        if($activityInfo['end_time'] < time() ){
            $code = 0;
            $data = '';
            $msg = '活动已结束';
            return parent::returnMsg($code,$data,$msg);
        }
        return true;
    }

    /**
     * 八大裂变活动产品
     * 无针胶原小颜术体验项目 good_type=5	  明星童颜术体验项目 good_type=6  头皮体验项目 good_type=7  形体项目 good_type=8
     */
    public function goods_list(){
        $check=0;
        $uid=input('param.uid');
        $storeid=input('param.storeid',0);
        $good_type=input('param.good_type');
        $map['goods_cate']=array('eq',4);
        $good_type=$good_type+1;
        $map['activity_id']=array('eq',$good_type);
        $map['status']=array('eq',1);
        $goods=new GoodsModel();
        if($storeid){
            $map['storeid']=array('eq',$storeid);
            $check=$goods->getAllBranch($map);
        }
        if(!$check){
            $storeid=0;
        }

        if($good_type ==6){
            $activity_flag='8813';
        }
        if($good_type ==7){
            $activity_flag='8814';
        }
        if($good_type ==8){
            $activity_flag='8815';
        }

        $map['storeid']=array('eq',$storeid);
        $goodsData=$goods->getActivityGoods($map,'orderby');
        if(count($goodsData)){
            $order=new ActivityOrderModel();
            foreach ($goodsData as $k=>$v){
                $showInfo = [];
                if($good_type<8){
                    $where['o.storeid']=array('eq',input('param.storeid'));
                    $where['o.pid']=array('eq',$v['id']);
                    $where['m.pid']=array('eq',$uid);
                    $where['m.activity_flag']=array('eq',$activity_flag);
                    $where['m.id']=array('neq',$uid);
                    $list=$order->getOrdersByWhere($where,1,3);
                    if (count($list)) {
                        foreach ($list as $kk => $vv) {
                            $showInfo[$kk]['nickname'] = $vv['nickname'];
                            $showInfo[$kk]['avatar'] = $vv['avatar'];
                        }
                    }
                }
                $goodsData[$k]['share_list'] = $showInfo;
                $getUserOrder =Db::name('double12_user')->where(['uid'=>$uid,'pid'=>$good_type])->count();
                $goodsData[$k]['show'] = $getUserOrder ? 1 : 0;

            }
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


    //产品信息
    public function goods_info(){
        $pid=input('param.pid');
        if($pid){
            $info=Db::name('goods')->where(['id'=>$pid])->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content,model_id')->cache(86400)->find();
            if($info && $info['status']){
                if($info && $info['model_id']){
                    $goods_model=new GoodsModModel();
                    $goods_specs=new GoodsSpecsModel();
                    $specs=$goods_model->getModelValue($info['model_id'],'model_specs');
                    $map['id']=array('in',$specs);
                    $map['specs_status']=array('eq',1);
                    $info['specs']=$goods_specs->goodsSpecs1($pid);
                }else{
                    $info['specs']=[];
                }
                if(!parent::getCacheString('ms_good'.$info['id'])) {
                    parent::setCacheString('ms_good' . $info['id'], $info['stock']);//将产品库存记录到redis
                }
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
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '页面暂不可用';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //获取所选规格价格库存信息
    public function get_good_specs(){
        $pid=input('pid');
        $specs=input('specs');
        if($specs!='') {
            $getInfo = Db::name('goods_specs_info')->where(['goods_id' => $pid, 'key' => $specs])->cache(86400)->find();
            if ($getInfo) {
                $check_stock = parent::getCacheString('ms_good' . $pid . $getInfo['sku']);
                if (!$check_stock) {
                    parent::setCacheString('ms_good' . $pid . $getInfo['sku'], $getInfo['store_count']);//将产品库存记录到redis
                    $check_stock = parent::getCacheString('ms_good' . $pid . $getInfo['sku']);
                }
                if ($check_stock) {
                    $getInfo['store_count'] = $check_stock;
                    $code = 1;
                    $data = $getInfo;
                    $msg = '获取成功';
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '当前组合暂无库存';
                }
            }
        }else{
            $code = 1;
            $data = '';
            $msg = '无参数返回';
        }

        return parent::returnMsg($code,$data,$msg);
    }



    /*
     *下单
     */
	public function createOrder(){
        //$this->check_activity(6);
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
                if (($getOrderCount < $goodsInfo['allow_buy_num']) || $goodsInfo['allow_buy_num'] == 0) {
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
                $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'], 'o.uid=m.id', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->join('goods p', 'o.pid=p.id', 'left')->field('uid,fid,o.pid,pay_status,order_status,pay_price,order_price,order_sn,m.realname,m.mobile,u.avatar,u.nickname,num,pick_code,pick_type,o.specs,p.name p_name,p.unit,p.images p_images,o.step,p.activity_id,o.is_axs')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
                if (count($list) && is_array($list)) {
                    foreach ($list as $k => $v) {
                        $list[$k]['allow_draw'] = 0;
                        $list[$k]['lucky_flag'] = 0;
                        $list[$k]['lucky_draw'] = '';
                        $list[$k]['p_name'] = $v['p_name'];
                        $list[$k]['pay_status'] = $v['pay_status'] ? '已支付' : '未付款';
                        $list[$k]['order_status'] = $v['order_status'] ? '已收货' : '未收货';
                        $list[$k]['order_price'] = $v['pay_price'] ? $v['pay_price'] : 0;
                        if($v['activity_id']==5){
                            $v['activity_id']=0;
                            $list[$k]['activity_id']=0;
                        }
                        if($v['activity_id']==9){
                            $list[$k]['ticket_confirm'] = Db::name('double12_user')->where(['uid'=>$v['uid'],'pid'=> $v['activity_id'],'order_sn'=>$v['order_sn']])->count();
                        }else{
                            $list[$k]['ticket_confirm'] = Db::name('double12_user')->where(['uid'=>$v['uid'],'pid'=> $v['activity_id']])->count();
                        }
                        $list[$k]['g_good_type']=$list[$k]['activity_id'];
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

    //点击激活奖券
    public function activation_ticket(){
        $good_type=input('param.good_type',0);
        $uid=input('param.uid');
        $order_sn=input('param.order_sn');
        if($good_type==5){
            $good_type=0;
        }
        $map['uid']=array('eq',$uid);
        $map['pid']=array('eq',$good_type);
        if($good_type==9){
            $map['order_sn']=array('eq',$order_sn);
        }
        $count=Db::name('double12_user')->where($map)->count();
        if(!$count){
            if($good_type==9) {
                //激活奖券 只有形体产品活动才会激活奖券
                Db::name('ticket_user')->where('order_sn', $order_sn)->update(['status' => 0]);
                Db::name('double12_user')->insert(['uid'=>$uid,'pid'=>$good_type,'order_sn'=>$order_sn]);
            }else{
                Db::name('double12_user')->insert(['uid'=>$uid,'pid'=>$good_type]);
            }

        }
        return parent::returnMsg(1,'','已激活');
    }
}