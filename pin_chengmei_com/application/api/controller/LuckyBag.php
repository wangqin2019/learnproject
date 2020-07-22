<?php

namespace app\api\controller;
use app\api\model\GoodsModModel;
use app\api\model\GoodsSpecsModel;
use think\Controller;
use think\Db;

/**
 * desc:春节福袋
 */
class LuckyBag extends Base
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
    public function check_activity($id){
        $activityInfo=Db::name('activity_config')->where('id',$id)->cache(86400)->find();
        if($activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动已结束！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
        if ($activityInfo['begin_time'] > time()) {
            $code = 0;
            $data = '';
            $msg = '活动将于' . date('Y年m月d日 H时i分s秒', $activityInfo['begin_time']) . '开启，请等待！';
            echo json_encode(array('code' =>$code,'data' => $data,'msg' => $msg));
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

    //福袋时间节点
    public function lucky_bag_date(){
        $id=input('param.id','7');
        $activityInfo=Db::name('activity_config')->where('id',$id)->cache(86400)->find();
        if($activityInfo){
            $current_time=time();
            $end_time_tips=date("Y-m-d H:i:s",strtotime("+".$activityInfo['price']." day"));
            if($activityInfo['begin_time'] > $current_time){
                $activityInfo['tips']='预告中';
            }else{
                $activityInfo['tips']='进行中';
            }
            if(strtotime($end_time_tips) > $activityInfo['end_time']){
                $activityInfo['tips']='倒计时';
            }
            if($activityInfo['end_time'] < $current_time){
                $activityInfo['tips']='已结束';
            }
            unset($activityInfo['show_time'],$activityInfo['branch_list'],$activityInfo['price'],$activityInfo['boos_status'],$activityInfo['send_time']);
            return parent::returnMsg(1,$activityInfo,'获取成功');
        }else{
            return parent::returnMsg(0,'','错误');
        }
    }

    //产品信息
    public function goodsInfo(){
        $pid=input('param.pid');
        $mobile=input('param.mobile');
        if($pid){
            $info=Db::name('goods')->where(['id'=>$pid,'goods_cate'=>12])->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content,model_id')->cache(86400)->find();
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
                $info['agreement']=Db::name('wx_user')->where('mobile',$mobile)->value('agreement');
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
     *  下单
     *
     * */
	public function createOrder(){
        $this->check_activity(7);
        $pid=input('param.pid');
        $sku=input('param.sku');
        $num = input('param.num', 1);//购买数量
        $specs = input('param.specs','');//规格
        $stock=parent::getCacheString('ms_good'.$pid.$sku);
        if($stock>=$num) {
            $uid = input('param.uid');
            if($uid) {
                $goodsInfo = Db::name('goods')->where('id', $pid)->field('id,name,unit,image,images,xc_images,video,status,price,activity_price,stock,allow_buy_num,stock_limit,allow_buy_num1,content,model_id')->cache(86400)->find();
                $fid = input('param.fid','');//引导分享uid
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
                    //获取购买者的信息
                    $buyerInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.sign,b.join_tk')->find();
                    $sellerId = $buyerInfo['staffid'];
                    $storeid = $buyerInfo['storeid'];
                    //密丝小铺门店用户重新归属
                    $getFidInfo = Db::table('ims_bj_shopn_member')->where('id', $fid)->field('storeid,code,isadmin,mobile')->find();
                    if($storeid==1550){
                        Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidInfo['storeid'], 'pid' => $fid, 'staffid' => $fid]);
                    }
                    if($buyerInfo['sign']=='000-000'){
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
                    //如果当前购买者门店没有开通88福袋，提示不能购买
                    if(strlen($buyerInfo['join_tk'])){
                        $join_tk_arr=explode(',',$buyerInfo['join_tk']);
                        if(!in_array(10,$join_tk_arr)){
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

                    if ($sellerId == 27291) {
                        $code = 0;
                        $data = '';
                        $msg = '分享码错误';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    /**
                     * 无佣金情况
                     * 1.自己购买，佣金无效
                     * 2.美容师推广 佣金无效
                     * 3.不再同一家店的用户推广 佣金无效
                     */
                    if(($uid==$fid) || (strlen($getFidInfo['code'])) || ($getFidInfo['isadmin']) || ($storeid != $getFidInfo['storeid'])){
                        $fid='';
                        if($uid==$fid){
                            $remark='该单无推广积分，自行下单，引导人号码为：'.$getFidInfo['mobile'];
                        }elseif (strlen($getFidInfo['code'])){
                            $remark='该单无推广积分，美容师推广，引导人号码为：'.$getFidInfo['mobile'];
                        }elseif ($getFidInfo['isadmin']){
                            $remark='该单无推广积分，店老板推广，引导人号码为：'.$getFidInfo['mobile'];
                        }elseif ($storeid != $getFidInfo['storeid']){
                            $remark='该单无推广积分，其他门店用户推广，引导人号码为：'.$getFidInfo['mobile'];
                        }else{
                            $remark='';
                        }
                    }else{
                        $remark='该单有推广积分，引导人号码为：'.$getFidInfo['mobile'];
                    }

                    $stock1=parent::getCacheString('ms_good'.$pid.$sku);
                    if($stock1>=$num) {
                        $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                        $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num, 'order_sn' => $ordersn, 'order_price' => $getPayPrice, 'pay_price' => $getPayPrice, 'scene' => 5, 'pick_type' => 1,'specs'=>$specs,'sku'=>$sku, 'channel' => 'missshop', 'insert_time' => time(), 'pid' => $pid,'remark'=>$remark);
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
                $map['scene'] = array('eq', 5);
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
                        $branchInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch' => 'b'], 'm.storeid=b.id', 'left')->where('m.id', $v['fid'])->field('m.realname,m.mobile,b.title,b.sign,b.address')->find();
                        $list[$k]['pick_info'] = $branchInfo;
                        $list[$k]['pick_code'] = '';
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

    /*
     * 我的推广
     */
    public function my_promoter(){
        $uid=input('param.uid');
        if($uid!='') {
            $userInfo = Db::table('ims_bj_shopn_member')->field('id,storeid,staffid,code,isadmin')->where('id', $uid)->find();
            if ($userInfo) {
                if ($userInfo['isadmin']) {
                    $map['p.storeid'] = array('eq', $userInfo['storeid']);
                } elseif (($userInfo['id'] == $userInfo['staffid']) || strlen($userInfo['code']) > 1) {
                    $map['p.fid'] = array('eq', $uid);
                } else {
                    $map['p.user_id'] = array('eq', $uid);
                }
                $map['p.type'] = array('eq', '88福袋');
                //当前用户名下积分
                $info['score']['all'] =0;//推广中积分
                $info['score']['used'] =0;//推广已使用积分
                $info['score']['have'] =0;//推广剩余积分
                $score=Db::name('promoter')->alias('p')->where($map)->column('money');
                if($score){
                    $aal_s=0;
                    $use_s=0;
                    foreach ($score as $v){
                        if($v>0){
                            $aal_s+=$v;
                        }else{
                            $use_s+=abs($v);
                        }
                    }
                    $info['score']['all'] =$aal_s;//推广中积分
                    $info['score']['used'] =$use_s;//推广已使用积分
                    $info['score']['have'] =$aal_s-$use_s;//推广已使用积分
                }
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 10;// 显示条数
                $count = Db::name('promoter')->alias('p')->join('activity_order o','p.order_sn=o.order_sn','left')->join(['ims_bj_shopn_member' => 'm'], 'o.uid=m.id', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->where($map)->distinct('p.user_id')->count();
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage >= $allpage) {
                    $info['next_page_flag'] = 0;//是否有下一页
                } else {
                    $info['next_page_flag'] = 1;
                }
                $list = Db::name('promoter')->alias('p')->join('activity_order o','p.order_sn=o.order_sn','left')->join(['ims_bj_shopn_member' => 'm'], 'o.share_uid=m.id', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->field('p.user_id,u.nickname,m.mobile,u.avatar,count(o.id) order_nums,sum(o.pay_price) money_total,sum(p.money) score_total')->where($map)->page($Nowpage, $limits)->group('p.user_id')->order('score_total desc')->select();
                if (count($list) && is_array($list)) {
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

    /**
     * 推广用户详情
     */
    public function promoter_info(){
        $uid=input('param.uid');
        if($uid!='') {
            $userInfo=Db::table('ims_bj_shopn_member')->alias('m')->join('wx_user u', 'm.mobile=u.mobile', 'left')->field('u.nickname,m.mobile,u.avatar')->where('m.id',$uid)->find();
            $map['p.user_id'] = array('eq', $uid);
            $map['p.type'] = array('eq', '88福袋');
            $map['p.money'] = array('gt', 0);
            $list = Db::name('promoter')->alias('p')->join('activity_order o','p.order_sn=o.order_sn','left')->join(['ims_bj_shopn_member' => 'm'], 'o.uid=m.id', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->join('goods g', 'o.pid=g.id', 'left')->where($map)->field('g.name,g.images,g.activity_price,o.num,o.order_sn,o.pay_price,o.num,p.money,u.nickname,m.mobile,u.avatar')->order('p.id desc')->select();
            if (count($list) && is_array($list)) {
                $info['user'] = $userInfo;
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
        return parent::returnMsg($code,$data,$msg);
    }

    //生成核销码
    public function create_code(){
      $uid=input("param.uid");
      if($uid!='') {
          $get_code=Db::name('wx_user')->alias('u')->join(['ims_bj_shopn_member m'],'u.mobile=m.mobile','left')->field('u.id,u.promoter_code')->where('m.id',$uid)->find();
          if(empty($get_code['promoter_code'])){
              $p_code=pickUpCode('promoter_'.$uid);
              Db::name('wx_user')->where('id',$get_code['id'])->update(['promoter_code'=>$p_code]);
          }else{
              $p_code=$get_code['promoter_code'];
          }
          $code = 1;
          $data = $p_code;
          $msg = '获取成功！';
      }else{
          $code = 0;
          $data = '';
          $msg = '参数错误！';
      }
      return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 同意协议
     */
    public function agreement(){
        $uid=input('param.uid');
        if($uid!=''){
            $get_uid=Db::name('wx_user')->alias('u')->join(['ims_bj_shopn_member m'],'u.mobile=m.mobile','left')->where('m.id',$uid)->value('u.id');
            Db::name('wx_user')->where('id',$get_uid)->update(['agreement'=>1]);
            $code = 1;
            $data = '';
            $msg = '设置成功！';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

}