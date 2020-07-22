<?php

namespace app\api\controller;
use app\api\model\BranchModel;
use app\api\model\GoodsModel;
use app\api\model\MemberModel;
use app\api\model\PintuanModel;
use org\QRcode;
use think\Config;
use think\Controller;
use think\Db;

/**
 * swagger: 拼团
 */
class Tuan extends Base
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

    /**
     * 门店拼团列表
     * @return \think\response\Json
     */
    public function tuanList()
    {
        $storeid=input('param.storeid');
        $type=input('param.type',0);
        if($storeid!=''){
            //记录访问日志
            $logsData=parent::getInfoByToken(input('param.token'));
            if(is_array($logsData)){
                $logsData['action']=1;
                $logsData['remark']='访问拼团列表';
                $logsData['insert_time']=time();
                $this->logToRedis($logsData);
            }
            //获取所属门店拼团活动
            $tuan=new PintuanModel();
            $branch=new BranchModel();
            $count=$branch->getAllBranch(array('id'=>$storeid));
            if($count) {
                $map1['storeid'] = array('eq', $storeid);
                $map1['pt_status'] = array('eq', 1);
                $map1['pt_type'] = array('eq', $type);
                $list = $tuan->getTuanByWhere($map1, 1, 30);
                foreach ($list as $k => $v) {
                    $list[$k]['p_pic'] = $v['p_pic'];
                    $list[$k]['pt_cover'] = $v['pt_cover'];
                    $Analysis=parent::getTuanAnalysis($storeid,$v['id']);
                    $list[$k]['analysis'] = $Analysis;
                    unset($list[$k]['pt_num_max']);
                }
                if(count($list)){
                    $code = 1;
                    $data = $list;
                    $msg = '获取成功！';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '暂时没有分享活动！';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '门店不存在！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '门店id和活动类型必须！';
        }

        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 拼团详细信息
     * @return \think\response\Json
     */
    public function tuanInfo()
    {
        $tid=input('param.tid');
        //$sellerId=input('param.sellerid',0);
        if($tid!=''){
            $tuan=new PintuanModel();
            $info=$tuan->getOnePy($tid);
            if(is_array($info)) {
                //记录访问日志
                $logsData=parent::getInfoByToken(input('param.token'));
                if(is_array($logsData)){
                    $logsData['action']=2;
                    $logsData['remark']='浏览了:'.$info['p_name'];
                    $logsData['insert_time']=time();
                    $this->logToRedis($logsData);
                }
                // $info['share_uid']=$sellerId;
                $info['pay_price'] = $info['p_price']-($info['pt_buyer_max']*$info['buyer_price']);//计算发起人应付金额
                if($info['carousel_from_goods']){
                    $images=explode(',',$info['carousel_self']);
                }else{
                    $images=explode(',',$info['images']);
                }
                $info['images'] = $images;
                $info['buy_type'] = explode(',',$info['buy_type']);

                //如果文描有自定义设置 读取自定义内容
                if($info['content_from_goods']){
                    $content=$info['content_self'];
                }else{
                    $content=$info['content'];
                }
                $info['content']=$content;

                unset($info['content_from_goods']);
                unset($info['content_self']);
                unset($info['carousel_from_goods']);
                unset($info['carousel_self']);
                unset($info['is_custom']);
                //获取团购统计数据
                $Analysis=parent::getTuanAnalysis($info['storeid'],$tid);
                $info['analysis'] = $Analysis;
                unset($info['pt_num_max']);
                //根据赠品id获取赠品列表
                $gmap['id']=array('in',$info['prizeid']);
                $goods=new GoodsModel();
                $prizeList=$goods->getGoodsByWhere($gmap);
                $info['prizeList'] = $prizeList;
                $code = 1;
                $data = $info;
                $msg = '获取成功！';
            }else{
                $code = 0;
                $data = '';
                $msg = '请求分享活动不存在！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '分享id必须！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 拼团发起人点确认拼单页面
     */

    public function tuanConfirm(){
        $tuan_id=input('param.tid');//拼团id
        $buy_type=input('param.buy_type',1);//拼购购买为1 单独购买为2
        if($tuan_id!='') {
            $returnData = [];
            //根据拼团id 获取拼团信息
            $pt = new PintuanModel();
            $ptInfo = $pt->getOnePy($tuan_id);
            if(is_array($ptInfo) && count($ptInfo)) {
                $returnData['title'] = $ptInfo['title'];
                $returnData['p_name'] = $ptInfo['p_name'];
                $returnData['p_price'] = $ptInfo['p_price'];
                $returnData['p_pic'] = $ptInfo['p_pic'];
                $returnData['buy_type'] = $buy_type;
                $returnData['buyer_max'] = $ptInfo['pt_buyer_max'];
                if($buy_type==2){
                    $pay_price = $ptInfo['p_price'];//计算应该支付金额
                    $returnData['pay_price'] = $pay_price;
                }else{
                    $pay_price = $ptInfo['p_price'] - ($ptInfo['pt_buyer_max'] * $ptInfo['buyer_price']);//计算发起拼团人应该支付金额
                    $returnData['pay_price'] = $pay_price;
                }
                $returnData['fenqi'] = getBank($ptInfo['is_fenqi'], $pay_price, $ptInfo['fenqi']);
                $code = 1;
                $data = $returnData;
                $msg = '获取成功！';
            }else{
                $code = 0;
                $data = '';
                $msg = '分享活动不存在！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '分享id必须！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 拼团发起人提交拼团订单
     * @return \think\response\Json
     */
    public function tuanStart(){
        $create_uid=input('param.uid');//拼单发起人id
        $share_uid=input('param.sellerid');//分享拼单美容师id
        $tuan_id=input('param.tid');//拼团id
        $bank_id=input('param.bank');//支付方式
        $no_period=input('param.period',0);//分期期数
        $buy_type=input('param.buy_type',1);//拼购购买为1 单独购买为2
        //获取发起人所在门店id
        $createUidInfo=Db::table('ims_bj_shopn_member')->field('storeid,pid,staffid,id_regsource')->where('id',$create_uid)->find();
        $storeid=$createUidInfo['storeid'];
        //根据拼团id 获取拼团信息
        $pt=new PintuanModel();
        $ptInfo=$pt->getOnePy($tuan_id);
        //如果$share_uid为空 默认取直属美容师id 从别的美容师那里跳转进来 取分享美容师id
        if($create_uid==$share_uid){
            $share_uid=$createUidInfo['staffid'];
        }

        if($buy_type==1){
            $price=$pt->getPayPrice($tuan_id);//计算发起人应付金额
            $tuan_num=$ptInfo['pt_buyer_max'];//拼团人数
//         //判断是否超员
//         if($tuan_num > $ptInfo['pt_buyer_max']){
//            $code=0;
//            $data='';
//            $msg='拼团人数上限为'.$ptInfo['pt_buyer_max'].'人';
//            return parent::returnMsg($code,$data,$msg);
//         }
            //判断拼团状态
            if(!$ptInfo['pt_status']){
                $code=0;
                $data='';
                $msg='活动已结束';
                return parent::returnMsg($code,$data,$msg);
            }

            //判断是否已经加入过该团  判断支付状态
            $noPeyOrder=$pt->getPtInfoCount(['create_uid'=>$create_uid,'tuan_id'=>$tuan_id,'status'=>1,'order_type'=>1]);
            if($ptInfo['is_fenqi']==0 && !$noPeyOrder){
                $code=2;
                $data='';
                $msg='该分享您有未支付订单 去支付！';
                return parent::returnMsg($code,$data,$msg);
            }

            //判断用户是否发起过该团 且拼团未成功 未成功不允许再次发起
            $haveOrder=$pt->getPtInfo(['create_uid'=>$create_uid,'tuan_id'=>$tuan_id,'status'=>1,'order_type'=>1]);
            if(is_array($haveOrder) && count($haveOrder)){
                $code=0;
                $data='';
                $msg='该分享您已经发起但暂未成团，成团后才能继续发起！';
                return parent::returnMsg($code,$data,$msg);
            }

            //检测新用户参与的拼团是否成团，未成团不允许发起，成团后参与人才能发起
            if($createUidInfo['id_regsource']==7 && $buy_type==1){
                $info=$pt->getOrderByUser(['order.uid'=>$create_uid]);
                if(!$info){
                    $code=0;
                    $data='';
                    $msg='等待您参加的分享活动成功后，您才能发起分享';
                    return parent::returnMsg($code,$data,$msg);
                }
            }
            //判断成团数是否超过预设的最大成团数 超出给出提示 不允许拼团
            $hmap['storeid']=array('eq',$storeid);
            $hmap['tuan_id']=array('eq',$tuan_id);
            $hmap['status']=array('neq',5);//剔除失效订单
            $orderCount=$pt->getPtCont($hmap);
            if($orderCount>=$ptInfo['pt_num_max']){
                $code=0;
                $data='';
                $msg='该分享名额已满，请看看其他分享活动吧！';
                return parent::returnMsg($code,$data,$msg);
            }
            //判断是否已经加入过该团  一个用户只能参加一个398拼团 不允许二次拼团 3800 9800分期产品随意拼
            //2018-08-14规则改为随便拼 不限制数量
//          $orderHava=$pt->getPtCont(['create_uid'=>$create_uid,'tuan_id'=>$tuan_id]);
//          if($ptInfo['is_fenqi']==0 && $orderHava){
//            $code=0;
//            $data='';
//            $msg='您已经拼过该商品了，换个商品去拼吧！';
//            return parent::returnMsg($code,$data,$msg);
//        }
            $partner_pay=$ptInfo['buyer_price'];
        }else{
            //判断商品的最多购买限额 超出给出提示 不允许购买
            $hmap['storeid']=array('eq',$storeid);
            $hmap['tuan_id']=array('eq',$tuan_id);
            $hmap['status']=array('neq',5);//剔除失效订单
            $orderCount=$pt->getPtCont($hmap);
            if($orderCount>=$ptInfo['pt_num_max']){
                $code=0;
                $data='';
                $msg='该商品已售完，请看看其他商品吧！';
                return parent::returnMsg($code,$data,$msg);
            }
            //判断是否已经加入过该团  判断支付状态
            $noPeyOrder=$pt->getPtInfoCount(['create_uid'=>$create_uid,'tuan_id'=>$tuan_id,'status'=>1,'order_type'=>2]);
            if($ptInfo['is_fenqi']==0 && !$noPeyOrder){
                $code=2;
                $data='';
                $msg='该商品您有未支付订单 去支付！';
                return parent::returnMsg($code,$data,$msg);
            }
            $price=$ptInfo['p_price'];
            $tuan_num=1;
            $partner_pay=0;
        }

        $validityStart=time();
        $validityend=$validityStart+(intval($ptInfo['pt_time'])*60*60);

        //判断当前拼团用户所属门店是否与拼团门店一致
        if($storeid != $ptInfo['storeid']){
            $code=0;
            $data='';
            $msg='活动门店与您所在门店不匹配';
            return parent::returnMsg($code,$data,$msg);
        }

        //将拼团信息存储
        Db::startTrans();
        try{
            //获取支付工具名称
            $bankInfo=Db::name('bank')->where('id_bank',$bank_id)->find();
            $ordersn=date('YmdHis').rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
            $tuanData=array('create_uid'=>$create_uid,'storeid'=>$storeid,'share_uid'=>$share_uid,'tuan_id'=>$tuan_id,'tuan_num'=>$tuan_num,'order_sn'=>$ordersn,'begin_time'=>$validityStart,'end_time'=>$validityend,'insert_time'=>time(),'pay_name'=>$bankInfo['st_abbre_bankname'],'pay_id'=>$bankInfo['id_bank'],'is_fq'=>$bankInfo['is_period'],'pid'=>$ptInfo['pid'],'tuan_price'=>$ptInfo['p_price'],'initiator_pay'=>$price,'partner_pay'=>$partner_pay,'tuan_name'=>$ptInfo['p_name'],'order_type'=>$buy_type);
            $getId=Db::name('tuanList')->insertGetId($tuanData);
            //将主订单插入订单表
            $orderDate=array('order_sn'=>$ordersn,'uid'=>$create_uid,'pay_id'=>$bank_id,'pay_name'=>$bankInfo['st_abbre_bankname'],'no_period'=>$no_period,'pay_price'=>$price,'flag'=>0,'parent_order'=>$ordersn,'insert_time'=>time(),'tuan_id'=>$getId,'buy_good_ids'=>$ptInfo['pid']);
            Db::name('tuanOrder')->insert($orderDate);
            //如果是拼购 需要插入拼购子单
            if($buy_type==1) {
                //将拼购参与人订单插入订单表
                $insert_data = array();
                for ($i = 1; $i <= $tuan_num; $i++) {
                    $insert_data[$i]['order_sn'] = $ordersn . $i;
                    $insert_data[$i]['pay_id'] = '2';
                    $insert_data[$i]['pay_name'] = '微信';
                    $insert_data[$i]['pay_price'] = $ptInfo['buyer_price'];
                    $insert_data[$i]['flag'] = 1;
                    $insert_data[$i]['parent_order'] = $ordersn;
                    $insert_data[$i]['insert_time'] = time();
                    $insert_data[$i]['tuan_id'] = $getId;
                    $insert_data[$i]['buy_good_ids'] = $ptInfo['prizeid'];
                }
                Db::name('tuanOrder')->insertAll($insert_data);
            }
            Db::commit();
            $res['ordersn']=$ordersn;
            $res['attach']=$ordersn;
            $res['body']=$ptInfo['p_name'];
            $res['tid']=$tuan_id;
            $res['endtime']=$validityend;
            //如果是微信支付 拼单人需要支付应该支付货款  如果是分期 先插入订单  等朋友支付完成后在支付 因为分期支付不能退款
            if($bank_id==2){
                $res['flag']=1;
            }else{
                $res['flag']=0;
            }
            //记录访问日志
            $userToken=Db::table('ims_bj_shopn_member')->alias('mem')->where('mem.id',$create_uid)->join('wx_user u','mem.mobile=u.mobile','left')->value('u.token');
            $logsData=parent::getInfoByToken($userToken);
            if(is_array($logsData)){
                $logsData['action']=3;
                $logsData['remark']='下单成功:'.$ptInfo['p_name'];
                $logsData['insert_time']=time();
                $this->logToRedis($logsData);
            }
            $code=1;
            $data=$res;
            $msg='订单生成成功';
        }catch (\Exception $e){
            Db::rollback();
            $code=0;
            $data='';
            $msg='订单生成失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 拼团发起人下单成功后，返回拼团信息
     * @return \think\response\Json
     */
    public function tuanOrderInfo(){
        $ordersn=input('param.ordersn');//拼团单号
        $pt = new PintuanModel();
        $check=$pt->getPtCont(['order_sn'=>$ordersn]);
        if($ordersn!='' && $check) {
            $ptInfo = $pt->getBuyOrder($ordersn);
//            if($ptInfo) {
//                $map['order_sn']=array('eq',$ordersn);
//                $orderInfo = Db::name('tuan_list')->alias('order')->join(['ims_bj_shopn_member' => 'member'], 'member.id=order.create_uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->field('order.pid,order.create_uid,order.order_sn,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,bwk.title,depart.st_department')->where($map)->find();
//                $branchCheck=Db::name('activity_branch')->where('storeid',$orderInfo['storeid'])->count();
//                if($branchCheck) {
//                    $getActivityOrder = Db::name('activity_order')->where(['uid' => $orderInfo['create_uid'], 'pay_status' => 1])->count();
//                    if ($getActivityOrder == 2) {
//                        $activityInfo = Db::name('queen_day_config')->where('id', 1)->cache(60)->find();
//                        if ($activityInfo['activity_status']) {
//                            if (($activityInfo['begin_time'] < time() && $activityInfo['end_time'] > time())) {
//                                $productIds = array('23', '25', '40', '41');
//                                if ($ptInfo['order_type'] == 2 && in_array($orderInfo['pid'], $productIds)) {
//                                    $check = Db::name('ticket_user')->where(['type' => 5, 'source' => 1, 'mobile' => $orderInfo['mobile']])->count();
//                                    if (!$check) {
//                                        insertTicket(5, $orderInfo, '2020-01-01 00:00:00', 1, 1);//发电子抽奖券
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }
//            }
            $code = 1;
            $data = $ptInfo;
            $msg = '获取成功！';
        }else{
            $code = 0;
            $data = '';
            $msg = '订单不存在！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 新顾客查看参与拼团的详细信息
     * @return \think\response\Json
     */
    public function joinTuanInfo(){
        $tuanid=input('param.tuanid');
        if($tuanid!=''){
            try{
                $pt=new PintuanModel();
                $info=$pt->getJoinTuanInfo($tuanid);
                if(is_array($info) && count($info)){
                    //记录访问日志
                    $logsData=parent::getInfoByToken(input('param.token'));
                    if(is_array($logsData)){
                        $logsData['action']=2;
                        $logsData['remark']='浏览了:'.$info['p_name'];
                        $logsData['insert_time']=time();
                        $this->logToRedis($logsData);
                    }
                    $mem=new MemberModel();
                    $ownerInfo=$mem->getOneInfo(['member.id'=>$info['create_uid']]);
                    $info['p_price']=$info['tuan_price'];
                    $info['buyer_price']=$info['partner_pay'];
                    $info['nickname']=$ownerInfo['nickname'];
                    $info['avatar']=$ownerInfo['avatar'];
                    //根据赠品id获取赠品列表
                    $prizeids=Db::name('tuan_order')->where(['parent_order'=>$info['order_sn'],'flag'=>1])->limit(1)->value('buy_good_ids');
                    $gmap['id']=array('in',$prizeids);
                    $goods=new GoodsModel();
                    $prizeList=$goods->getGoodsByWhere($gmap);
                    $info['tuanid'] = $tuanid;
                    $info['prizeList'] = $prizeList;
                    $info['paid_member'] = $pt->getTuanPaidMember($info['order_sn']);
                    $info['paid_member'] = $pt->getTuanPaidMember($info['order_sn']);
                    $code=1;
                    $data=$info;
                    $msg='活动获取成功';
                }else{
                    $code=0;
                    $data='';
                    $msg='活动不存在';
                }
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='错误'.$e->getMessage();
            }
        }else{
            $code=0;
            $data='';
            $msg='分享id不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    public function joinTuanOrderInfo(){
        $tid=input('param.tuanid');
        if($tid!=''){
            $retrunData=[];
            try {
                $pt = new PintuanModel();
                $tuanInfo = $pt->getJoinTuanInfo($tid);
                if(is_array($tuanInfo) && count($tuanInfo)) {
                    $prizeids=Db::name('tuan_order')->where(['parent_order'=>$tuanInfo['order_sn'],'flag'=>1])->limit(1)->value('buy_good_ids');
                    $retrunData['id'] = $tid;
                    $retrunData['ordersn'] = $tuanInfo['order_sn'];
                    $retrunData['branch'] = $tuanInfo['title'];
                    $retrunData['buyer_price'] = $tuanInfo['partner_pay'];
                    $gmap['id'] = array('in', $prizeids);
                    $goods = new GoodsModel();
                    $prizeList = $goods->getGoodsByWhere($gmap);
                    $retrunData['prizeList'] = $prizeList;
                    $fenqi = getBankOnly(0, 2);
                    $retrunData['pay_method'] = $fenqi;
                    $retrunData['status'] = $pt->getOrderStatus($tuanInfo['status']);
                    $code = 1;
                    $data = $retrunData;
                    $msg = '详情获取成功';
                }else{
                    $code=0;
                    $data='';
                    $msg='拼购信息不存在';
                }
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='详情获取失败'.$e->getMessage();
            }

        }else{
            $code=0;
            $data='';
            $msg='id不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 参团者提交参团订单
     * @return \think\response\Json
     */
    public function joinOrderSubmit(){
        $ordersn=input('param.ordersn');
        $uid=input('param.uid');
        $pt=new PintuanModel();
        $mem=new MemberModel();
        //获取拼团用户id
        $buyerInfo=$mem->getOneInfo(['member.id'=>$uid]);
        //获取该拼团信息
        $tuanInfo=$pt->getPtInfo(['order_sn'=>$ordersn]);

        if(is_array($tuanInfo) &&  count($tuanInfo)) {

            //老顾客不能参加拼团 销售部提议修改 2018-9-19
            if($buyerInfo['id_regsource']!=7){
                return parent::returnMsg(0,'','您已是诚美VIP用户，尊享「美丽分享购」商品的发起权，请邀请您的好友一起来开启奢宠逆龄之旅！');
            }
            if($uid==$tuanInfo['create_uid']){
                return parent::returnMsg(0,'','不允许参加自己发起的分享活动');
            }
            //检测该参团人是否发起过拼购 如参加过不能在参与拼团
            $isJoin=$pt->getPtCont(['create_uid'=>$uid]);
            if($isJoin){
                return parent::returnMsg(0,'','您已发起过「美丽分享购」的拼购团，不能参与他人的拼购团哦！');
            }
            //398体验产品每种只能参加一次，其余产品共能参加一次 2018-08-14新增
            //398体验产品共能参加一次，其余产品共能参加一次 2018-09-07修改
            $tryout=Db::name('goods')->where('id',$tuanInfo['pid'])->value('is_tryout');
            if($tryout){//非398参团检测
                $tryoutList=Db::name('goods')->where(['is_tryout'=>$tryout,'goods_cate'=>3])->column('id');
                $map['order.uid']=array('eq',$uid);
                $map['order.flag']=array('eq',1);
                $map['order.pay_by_self']=array('eq',0);
                $map['order.order_status']=array('in','2,3');
                $map['order.pay_status']=array('eq',1);
                $map['list.pid']=array('in',implode(',',$tryoutList));
                $noTryout=Db::name('tuan_order')->alias('order')->join('tuan_list list','order.tuan_id=list.id')->where($map)->count();
                if($noTryout){
                    return parent::returnMsg(0,'','美丽分享购“超值特惠”商品，仅限参加一次');
                }
            }else{//398检测
                $tryoutList=Db::name('goods')->where(['is_tryout'=>0,'goods_cate'=>3])->column('id');
                $map['order.uid']=array('eq',$uid);
                $map['order.flag']=array('eq',1);
                $map['order.order_status']=array('in','2,3');
                $map['order.pay_status']=array('eq',1);
                $map['order.pay_by_self']=array('eq',0);
                //$map['list.pid']=array('eq',$tuanInfo['pid']);
                $map['list.pid']=array('in',implode(',',$tryoutList));
                $noTryout=Db::name('tuan_order')->alias('order')->join('tuan_list list','order.tuan_id=list.id')->where($map)->count();
                if($noTryout){
                    return parent::returnMsg(0,'','美丽分享购“超值体验”商品，仅限参加一次！');
                }
            }

            //若已参团常规商品的人不可再参与398元的集客商品拼购 2018-8-22新增
            if(!$tryout){
                $tryoutList=Db::name('goods')->where(['is_tryout'=>1,'goods_cate'=>3])->column('id');
                $map['order.uid']=array('eq',$uid);
                $map['order.flag']=array('eq',1);
                $map['order.pay_by_self']=array('eq',0);
                $map['order.order_status']=array('in','2,3');
                $map['order.pay_status']=array('eq',1);
                $map['list.pid']=array('in',implode(',',$tryoutList));
                $noTryout=Db::name('tuan_order')->alias('order')->join('tuan_list list','order.tuan_id=list.id')->where($map)->count();
                if($noTryout) {
                    return parent::returnMsg(0, '', '您已参加美丽分享购“超值特惠”活动，无法再参与美丽分享购“超值体验”活动');
                }
            }

            $prizeids=Db::name('tuan_order')->where(['parent_order'=>$ordersn,'flag'=>1])->limit(1)->value('buy_good_ids');
            //$getPrizeid = Db::name('tuan_info')->where('id', $tuanInfo['tuan_id'])->value('prizeid');
            $buyGoods = Db::name('goods')->where('id', 'in', $prizeids)->column('name');
            $buyGoodsName = implode(',', $buyGoods);
            //检测参团用户所属门店和该笔拼团所属门店是否一致
            if ($buyerInfo['storeid'] == $tuanInfo['storeid']) {
                //获取当前拼团状态
                $check = $this->checkOrder($ordersn);
                if ($check['code']) {
                    $map1['parent_order']=array('eq',$ordersn);
                    $map1['flag']=array('eq',1);
                    $map1['uid']=array('eq',$uid);
                    $map1['order_status']=array('in','2,3');
                    $map1['pay_status']=array('eq',1);
                    $isHave = Db::name('tuan_order')->where($map1)->count();
                    if (!$isHave) {
                        //分配拼团子单号
                        $sonSn = $pt->getSonOrderSn($ordersn, $uid);
                        if ($sonSn['code'] == 1) {
                            //返回订单信息 进行支付
                            $res['order_sn'] = $sonSn['data'];
                            $res['attach'] = $sonSn['data'];
                            $res['body'] = $buyGoodsName;
                            $code = 1;
                            $data = $res;
                            $msg = '子订单获取成功';
                            //记录访问日志
                            $userToken=Db::table('ims_bj_shopn_member')->alias('mem')->where('mem.id',$uid)->join('wx_user u','mem.mobile=u.mobile','left')->value('u.token');
                            $logsData=parent::getInfoByToken($userToken);
                            if(is_array($logsData)){
                                $logsData['action']=3;
                                $logsData['remark']='下单成功:'.$buyGoodsName;
                                $logsData['insert_time']=time();
                                $this->logToRedis($logsData);
                            }
                        } elseif ($sonSn['code'] == 2) {
                            $code = 0;
                            $data = '';
                            $msg = '一会再来看看吧，即将放出' . $sonSn['data'] . '个购买名额！';
                        } else {
                            $code = 0;
                            $data = '';
                            $msg = $sonSn['data'];
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '您已经参与过该活动';
                    }
                } else {
                    $code = 0;
                    $data = '';
                    $msg = $check['msg'];
                }
            } else {
                $code = 0;
                $data = '';
                $msg = '该活动不属于您所属美容院的活动';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '该活动已失效';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 拼团参与人下单成功后，返回拼团信息
     * @return \think\response\Json
     */
    public function joinOrderInfo(){
        $ordersn=input('param.ordersn');//拼团单号
        if($ordersn!='') {
            try {
                $pt = new PintuanModel();
                $info=$pt->getOrderInfo(['order_sn'=>$ordersn]);
                if(is_array($info) && count($info)) {
                    $tuanInfo = $pt->getJoinTuanInfo($info['tuan_id']);
                    $retrunData['ordersn'] = $info['order_sn'];
                    $retrunData['end_time'] = $tuanInfo['end_time'];
                    $retrunData['tuan_id'] = $info['tuan_id'];
                    $retrunData['pay_name'] = $info['pay_name'];
                    $retrunData['pay_time'] = $info['pay_time'];
                    $retrunData['time_diff'] = intval($info['pay_time']) - intval($info['insert_time']);
                    $retrunData['branch'] = $tuanInfo['title'];
                    $retrunData['buyer_price'] = $info['pay_price'];
                    $gmap['id'] = array('in', $info['buy_good_ids']);
                    $goods = new GoodsModel();
                    $prizeList = $goods->getGoodsByWhere($gmap);
                    $retrunData['prizeList'] = $prizeList;
                    $seller = new MemberModel();
                    $retrunData['sellerInfo'] = $seller->getOneInfo(['member.id' => $tuanInfo['share_uid']]);
                    $pay_percent=$pt->orderPayTime($info['process_time']);//获取参团支付耗时百分比
                    $retrunData['friendship'] = friendship($info['process_time'],$pay_percent);
                    $code = 1;
                    $data = $retrunData;
                    $msg = '订单获取成功';
                }else{
                    $code=0;
                    $data='';
                    $msg='订单不存在';
                }
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='错误：'.$e->getMessage();
            }
        }else{
            $code=0;
            $data='';
            $msg="订单号不允许为空";
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 检测该拼团订单是否还可以拼
     * @param $ordersn
     * @return mixed
     */
    public function checkOrder($ordersn){
        $pt=new PintuanModel();
        $info=$pt->getPtInfo(['order_sn'=>$ordersn]);
        //检测当前拼团是否过有效期
        if($info['end_time']>time()){
            if($info['status']==1){
                $tuanInfo=$pt->getOnePy($info['tuan_id']);
                if($tuanInfo['pt_status']){
                    $checkOrder=$pt->checkOrder($ordersn);
                    if($checkOrder){
                        $res['code']=1;
                        $res['msg']='可以支付';
                    }else{
                        $res['code']=0;
                        $res['msg']='该分享人数已满';
                    }
                }else{
                    $res['code']=0;
                    $res['msg']='分享活动已结束';
                }
            }else{
                $statustext='分享活动已结束';
                $res['code']=0;
                $res['msg']=$statustext;
            }
        }else{
            $res['code']=0;
            $res['msg']='分享活动已结束';
        }
        return $res;
    }


    //美容师查看分享出去的拼团进度
    public function  sellerTuan(){
        $sellerid=input('param.sellerid');
        $type=input('param.type');
        $tid=input('param.tid');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        //type为0全部  1正常拼购的订单 type为2 已完成订单
        if($type==1){
            $map['status']=array('eq',1);
        }elseif ($type==2){
            $map['status']=array('eq',2);
        }
        if($tid !=''){
            $map['tuan_id']=array('eq',$tid);
        }

        if($search_time1!='' && $search_time2!=''){
            $map['insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
        }

        $pt=new PintuanModel();
        $map['share_uid']=array('eq',$sellerid);
        $list=$pt->getPtList($map);
        if(is_array($list) && count($list)){
            $result=[];
            foreach ($list as $k=>$v){
                $result[$k]['order_sn']=$v['order_sn'];
                $result[$k]['end_time']=$v['end_time'];
                $tuanInfo=$pt->getJoinTuanInfo($v['id']);
                $result[$k]['tid']=$tuanInfo['tid'];
                $result[$k]['p_price']=$tuanInfo['tuan_price'];
                $result[$k]['p_name']=$tuanInfo['tuan_name'];
                $result[$k]['p_pic']=$tuanInfo['p_pic'];
                $result[$k]['realname']=$tuanInfo['realname'];
                $mem=new MemberModel();
                $ownerInfo=$mem->getOneInfo(['member.id'=>$v['create_uid']]);
                $result[$k]['nickname'] = $ownerInfo['nickname'];
                $result[$k]['avatar'] = $ownerInfo['avatar'];
                $working=$pt->checkOrder($v['order_sn']);//还有几单未支付
                $result[$k]['tuan_num']=$v['tuan_num'];
                $result[$k]['paid']=intval($v['tuan_num'])-intval($working);//还有几单未支付
                $result[$k]['waiting']=$working;
                $result[$k]['status']=$pt->getOrderStatus($v['status']);
                $result[$k]['order_type']=$v['order_type'];
                $result[$k]['pt_type']=Db::name('tuan_info')->where('id',$v['tuan_id'])->value('pt_type');
            }
            $code=1;
            $data=$result;
            $msg='订单获取成功';
        }else{
            $code=0;
            $data='';
            $msg='暂时没有订单';
        }
        return parent::returnMsg($code,$data,$msg);
    }



    //美容师查看分享出去的拼团进度详情
    public function  sellerTuanInfo(){
        $ordersn=input('param.ordersn');
        $pt=new PintuanModel();
        $map['order_sn']=array('eq',$ordersn);
        $retrunData=[];
        $ptinfo=$pt->getPtInfo($map);
        if(is_array($ptinfo) && count($ptinfo)) {
            $tuanInfo = $pt->getJoinTuanInfo($ptinfo['id']);
            $retrunData['realname'] = $tuanInfo['realname'];
            $mem = new MemberModel();
            $ownerInfo = $mem->getOneInfo(['member.id' => $ptinfo['create_uid']]);
            $retrunData['id'] = $ptinfo['id'];
            $retrunData['nickname'] = $ownerInfo['nickname'];
            $retrunData['avatar'] = $ownerInfo['avatar'];
            $retrunData['mobile'] = $ownerInfo['mobile'];
            $retrunData['end_time'] = $tuanInfo['end_time'];
            $retrunData['order_sn'] = $tuanInfo['order_sn'];
            $retrunData['order_type'] = $ptinfo['order_type'];
            $retrunData['pt_type']=Db::name('tuan_info')->where('id',$ptinfo['tuan_id'])->value('pt_type');
            $retrunData['tid'] = $tuanInfo['tid'];
            $retrunData['p_name'] = $tuanInfo['tuan_name'];
            $retrunData['p_price'] = $tuanInfo['tuan_price'];
            $retrunData['p_pic'] = $tuanInfo['p_pic'];
            $working = $pt->checkOrder($tuanInfo['order_sn']);//还有几单未支付
            $retrunData['tuan_num'] = $tuanInfo['tuan_num'];
            $retrunData['paid'] = intval($tuanInfo['tuan_num']) - intval($working);//已支付
            $retrunData['waiting'] = $working;
            $retrunData['status']=$pt->getOrderStatus($ptinfo['status']);
            $map1['parent_order'] = array('eq', $ordersn);
            $map1['pay_flag'] = array('eq', 2);
            $map1['order.flag'] = array('eq', 1);
            $orderList = $pt->getOrder($map1);
            $orderListArr = [];
            foreach ($orderList as $kk => $vv) {
                //根据赠品id获取赠品列表
                $gmap['id'] = array('in', $vv['buy_good_ids']);
                $goods = new GoodsModel();
                $prizeList = $goods->getGoodsByWhere($gmap);
                $prizeImages = [];
                foreach ($prizeList as $v) {
                    $prizeImages[] = $v['image'];
                }
                $userInfo = $mem->getOneInfo(['member.id' => $vv['uid']]);
                $orderListArr[$kk]['nickname'] = $userInfo['nickname'];
                $orderListArr[$kk]['avatar'] = $userInfo['avatar'];
                $orderListArr[$kk]['realname'] = $vv['realname'];
                $orderListArr[$kk]['order_sn'] = $vv['order_sn'];
                $orderListArr[$kk]['pay_price'] = $vv['pay_price'];
                $orderListArr[$kk]['prizeImages'] = $prizeImages;
                $orderListArr[$kk]['mobile'] =  $vv['mobile'];
            }
            $retrunData['orderList'] = $orderListArr;
            if (is_array($retrunData) && count($retrunData)) {
                $code = 1;
                $data = $retrunData;
                $msg = '订单详情获取成功';
            } else {
                $code = 0;
                $data = '';
                $msg = '详情获取失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '订单信息不存在';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 获取美容师下线拼团客户
     */
    public function  sellerCustomer(){
        $sellerId=input('param.sellerid');
        try {
            $returnData = [];
            $mem = new MemberModel();
            $ptCustomer = Db::name('tuan_list')->where('share_uid', $sellerId)->group('create_uid')->column('create_uid');
            if (is_array($ptCustomer) && count($ptCustomer)) {
                foreach ($ptCustomer as $k => $v) {
                    $owner = $mem->getOneInfo(['member.id' => $v]);
                    $returnData[$k]['id'] = $owner['id'];
                    $returnData[$k]['nickname'] = $owner['nickname'];
                    $returnData[$k]['avatar'] = $owner['avatar'];
                    $returnData[$k]['mobile'] = $owner['mobile'];
                    $ownerOrder = Db::name('tuan_list')->where(['create_uid' => $v])->column('order_sn');
                    $ownerOrder = implode(',', $ownerOrder);
                    $map['parent_order'] = array('in', $ownerOrder);
                    $map['pay_flag'] = array('eq', '2');
                    $map['flag'] = array('eq', '1');
                    $joinCustomer = Db::name('tuan_order')->where($map)->group('uid')->column('uid');
                    if(is_array($joinCustomer) && count($joinCustomer)){
                        foreach ($joinCustomer as $kk => $vv) {
                            $joinCustomerInfo = $mem->getOneInfo(['member.id' => $vv]);
                            $returnData[$k]['joinFriends'][$kk]['id'] = $joinCustomerInfo['id'];
                            $returnData[$k]['joinFriends'][$kk]['nickname'] = $joinCustomerInfo['nickname'];
                            $returnData[$k]['joinFriends'][$kk]['avatar'] = $joinCustomerInfo['avatar'];
                            $returnData[$k]['joinFriends'][$kk]['mobile'] = $joinCustomerInfo['mobile'];
                        }
                    }else{
                        $returnData[$k]['joinFriends']=[];
                    }
                }
            }
            $code = 1;
            $data = $returnData;
            $msg = '客人获取成功';
        }catch (\Exception $e){
            $code=0;
            $data='';
            $msg='客人获取失败'.$e->getMessage();
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 美容师当月业绩
     */
    public function achievement(){
        $sellerId=input('param.uid');
        $date=input('param.date');
        $begin=$date.'-01';
        $end=$date.'-31';
        if($date!='' && $sellerId!=''){
            //检测该用户是美容师还是店老板  美容师看自己的销售  店老板查看店内所有美容师的销量
            $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$sellerId)->field('isadmin,storeid')->find();
            if($sellerInfo['isadmin']){
                $map['l.storeid']=array('eq',$sellerInfo['storeid']);
            }else{
                $map['l.share_uid']=array('eq',$sellerId);
            }
            $map['o.order_status']=array('in','2,3');
            $map['o.pay_status']=array('eq',1);
            $map['l.status']=array('eq',2);
            $return=[];
            $order=Db::name('tuan_order')->alias('o')->join('tuan_list l','o.parent_order=l.order_sn','left')->join(['ims_bj_shopn_member' => 'm'],'m.id=o.uid','left')->where($map)->whereTime('o.pay_time', 'between', [$begin, $end])->field('m.id_regsource,o.pay_price')->select();
            if(is_array($order) && count($order)){
                $new_cus=0;//新客数量
                $old_cus=0;//老客数量
                $new_money=0;//新客带来收益
                $old_money=0;//老客带来收益
                foreach ($order as $k=>$v){
                    if($v['id_regsource']==7){
                        $new_cus++;
                        $new_money+=$v['pay_price'];
                    }else{
                        $old_cus++;
                        $old_money+=$v['pay_price'];
                    }
                }
                $money=number_format($new_money+$old_money,2,'.','');//总交易金额
                $return['money']=$money;
                $new_cus_percent=($new_money/$money)*100;//新客占比
                $old_cus_percent=($old_money/$money)*100;//老客占比
                $return['new_cus']=array('money'=>number_format($new_money,2),'percent'=>number_format($new_cus_percent,2,'.','').'%');
                $return['old_cus']=array('money'=>number_format($old_money,2),'percent'=>number_format($old_cus_percent,2,'.','').'%');
                $code=1;
                $data=$return;
                $msg='数据获取成功';
            }else{
                $code=1;
                $data=[];
                $msg='暂无数据';
            }
        }else{
            $code=0;
            $data='';
            $msg='请先选择查询月份';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 美容师榜单
     */
    public function sellerRanking(){
        $uId=input('param.uid');//店老板或者美容师id
        $type=input('param.type');//类型 1是店内美容师积分排行 2是办事处门店积分排行榜 3是全国门店积分排行榜
        if($uId!='' && $type!=''){
            //检测该用户是美容师还是店老板  美容师看自己的销售  店老板查看店内所有美容师的销量
            $userInfo=Db::table('ims_bj_shopn_member')->where('id',$uId)->field('isadmin,storeid')->find();
            $isadmin=0;
            if($userInfo['isadmin']){
                $isadmin=1;
            }
            $return=[];
            //店内美容师积分排行
            if($type==1){
                try {
                    $mydata = [];
                    $map1['m.storeid']=array('eq',$userInfo['storeid']);
                    $map1['m.code']=array('neq','');
                    $map1['m.isadmin']=array('eq',0);
                    $memberList = Db::table('ims_bj_shopn_member')->alias('m')->join('seller_score s', 'm.id=s.sellerid', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->where($map1)->field('m.id,m.realname,m.mobile,u.nickname,u.avatar,sum(s.score) score')->group('m.id')->order('score desc,id')->select();
                    $return['isadmin'] = $isadmin;
                    if (is_array($memberList) && count($memberList)) {
                        foreach ($memberList as $k => $v) {
                            $nickname='';
                            $avatar='';
                            if($v['nickname']==false){
                                $fans=Db::table('ims_fans')->field('nickname,avatar')->where('mobile',$v['mobile'])->find();
                                if (preg_match('/(http:\/\/)/i', $fans['avatar'])) {
                                    $avatar=$fans['avatar'];
                                }else{
                                    $avatar=config('qiniu.image_url').'/avatar.png';
                                }
                                $nickname=$fans['nickname'];
                            }
                            $memberList[$k]['nickname'] = $v['nickname']?$v['nickname']:$nickname;
                            $memberList[$k]['avatar'] = $v['avatar']?$v['avatar']:$avatar;
                            $memberList[$k]['rank'] = $k + 1;
                            if ($v['id'] == $uId) {
                                $v['rank'] = $k + 1;
                                $mydata = $v;
                            }
                        }
                    }
                    if($isadmin) {
                        $return['sellerData'] = [];
                    }else{
                        $return['sellerData'] = $mydata;
                    }
                    $return['sellerList'] = $memberList;
                    $code=1;
                    $data=$return;
                    $msg='数据获取成功';
                }catch (\Exception $e){
                    $code=0;
                    $data='';
                    $msg='错误'.$e->getMessage();
                }

            }elseif($type==2){
                //获取当前门店所属办事处
                $getDepartMent=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->field('d.st_department,d.id_department')->where('r.id_beauty',$userInfo['storeid'])->find();
                //获取当前办事处下的门店
                $getBranch=Db::table('sys_departbeauty_relation')->where('id_department',$getDepartMent['id_department'])->column('id_beauty');
                try {
                    $mydata = [];
                    $map1['b.id']=array('in',$getBranch);
                    $brancList = Db::table('ims_bwk_branch')->alias('b')->join('seller_score s', 's.storeid=b.id', 'left')->where($map1)->field('b.id,b.title,sum(s.score) score')->group('b.id')->order('score desc,id')->select();
                    if (is_array($brancList) && count($brancList)) {
                        foreach ($brancList as $k => $v) {
                            $brancList[$k]['rank'] = $k + 1;
                            if ($v['id'] == $userInfo['storeid']){
                                $v['rank'] = $k + 1;
                                $mydata = $v;
                            }
                        }
                    }
                    $return['branchData'] = $mydata;
                    $return['branchList'] = $brancList;
                    $code=1;
                    $data=$return;
                    $msg='数据获取成功';
                }catch (\Exception $e){
                    $code=0;
                    $data='';
                    $msg='错误'.$e->getMessage();
                }
            }elseif($type==3){
                try {
                    $mydata = [];
                    $brancList = Db::table('ims_bwk_branch')->alias('b')->join('seller_score s', 's.storeid=b.id', 'left')->field('b.id,b.title,sum(s.score) score')->group('b.id')->order('score desc,id')->select();
                    if (is_array($brancList) && count($brancList)) {
                        foreach ($brancList as $k => $v) {
                            $brancList[$k]['rank'] = $k + 1;
                            if ($v['id'] == $userInfo['storeid']){
                                $v['rank'] = $k + 1;
                                $mydata = $v;
                            }
                        }
                    }
                    $return['branchData'] = $mydata;
                    $return['branchList'] = $brancList;
                    $code=1;
                    $data=$return;
                    $msg='数据获取成功';
                }catch (\Exception $e){
                    $code=0;
                    $data='';
                    $msg='错误'.$e->getMessage();
                }
            }
        }else{
            $code=0;
            $data='';
            $msg='参数未填写完整';
        }
        return parent::returnMsg($code,$data,$msg);
    }




    //老顾客查看发起的拼团进度
    public function  myTuan(){
        $create_uid=input('param.uid');
        $type=input('param.type');
        $flag=input('param.flag',0);
        //type为0全部 1正常拼购的订单 type为2 已完成订单
        if($type==1){
            $map['status']=array('eq',1);
        }elseif ($type==2){
            $map['status']=array('eq',2);
        }
        $pt=new PintuanModel();
        $map['create_uid']=array('eq',$create_uid);
        $map['order_type']=array('eq',1);//新增购买类型判断
        $list=$pt->getPtList($map);
        if(is_array($list) && count($list)){
            $result=[];
            foreach ($list as $k=>$v){
                $tuanInfo=$pt->getJoinTuanInfo($v['id']);
                $result[$k]['id']=$v['id'];
                $result[$k]['tid']=$tuanInfo['tid'];
                $result[$k]['branch']=$tuanInfo['title'];
                $result[$k]['end_time']=$tuanInfo['end_time'];
                $result[$k]['order_sn']=$tuanInfo['order_sn'];
                $result[$k]['p_name']=$tuanInfo['tuan_name'];
                $result[$k]['p_price']=$tuanInfo['tuan_price'];
                $result[$k]['p_pic']=$tuanInfo['p_pic'];
                $result[$k]['tuan_num']=$tuanInfo['tuan_num'];
                $surplus=$pt->checkOrder($v['order_sn']);//还有几单未支付
                $result[$k]['surplus']=$surplus;//未成交订单数量
                $pay_price=$tuanInfo['initiator_pay'];//该拼团发起人支付金额
                //检测是否有凑单订单
                $pay_by_self=$pt->getPriceBySelfPay($v['order_sn']);
                $result[$k]['pay_money']=$pay_price+$pay_by_self;
                if($v['is_fq']){
                    $result[$k]['surplusmoney']=number_format(($surplus*$tuanInfo['partner_pay'])+$pay_price,2,'.','');//凑单付款价格
                    if($surplus<=$tuanInfo['last_num']){
                        if($v['is_fq'] && $surplus==0){
                            $result[$k]['pay_button']='支付';
                        }else{
                            $result[$k]['pay_button']='凑单支付';
                        }
                    }else{
                        $result[$k]['pay_button']='等待成团';
                    }
                }else{
                    $result[$k]['surplusmoney']=number_format($surplus*$tuanInfo['partner_pay'],2,'.','');//凑单付款价格
                    if($flag==1){
                        $result[$k]['pay_button']='支付';
                    }else{
                        if($surplus<=$tuanInfo['last_num']){
                            $result[$k]['pay_button']='凑单支付';
                        }else{
                            $result[$k]['pay_button']='等待成团';
                        }
                    }
                }
                $result[$k]['status']=$pt->getOrderStatus($v['status']);
                $result[$k]['paidmember']=$pt->getTuanPaidMember($v['order_sn']);
                $buyorder=Db::name('tuan_order')->field('pay_status,order_status')->where('order_sn',$v['order_sn'])->find();
                $result[$k]['pay_status']=$buyorder['pay_status'];
                $result[$k]['order_status']=$buyorder['order_status'];
            }
            $code=1;
            $data=$result;
            $msg='订单获取成功';
        }else{
            $code=0;
            $data='';
            $msg='暂时没有订单';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //老顾客查看单独购买订单
    public function  myTuanBySelf(){
        $create_uid=input('param.uid');
        $type=input('param.type');
        //$flag=input('param.flag',0);
        //type为0全部 1正常拼购的订单 type为2 已完成订单
        if($type==1){
            $map['status']=array('eq',1);
        }elseif ($type==2){
            $map['status']=array('eq',2);
        }
        $pt=new PintuanModel();
        $map['create_uid']=array('eq',$create_uid);
        $map['order_type']=array('eq',2);//新增购买类型判断
        $list=$pt->getPtList($map);
        if(is_array($list) && count($list)){
            $result=[];
            foreach ($list as $k=>$v){
                $tuanInfo=$pt->getJoinTuanInfo($v['id']);
                $result[$k]['id']=$v['id'];
                $result[$k]['tid']=$tuanInfo['tid'];
                $result[$k]['branch']=$tuanInfo['title'];
                $result[$k]['end_time']=$tuanInfo['end_time'];
                $result[$k]['order_sn']=$tuanInfo['order_sn'];
                $result[$k]['p_name']=$tuanInfo['tuan_name'];
                $result[$k]['p_price']=$tuanInfo['tuan_price'];
                $result[$k]['p_pic']=$tuanInfo['p_pic'];
                $result[$k]['order_type']=$v['order_type'];
                switch ($v['status']){
                    case 1:
                        $result[$k]['pay_button']='支付';
                        $result[$k]['status']='待支付';
                        break;
                    case 2:
                        $result[$k]['pay_button']='';
                        $result[$k]['status']='已支付';
                        break;
//                    case 3:
//                        $status='已失效';
//                        break;
//                    case 4:
//                        $status='已退款';
//                        break;
                    case 5:
                        $result[$k]['pay_button']='';
                        $result[$k]['status']='已失效';
                        break;
                }
                $buyorder=Db::name('tuan_order')->field('pay_status,order_status')->where('order_sn',$v['order_sn'])->find();
                $result[$k]['pay_status']=$buyorder['pay_status'];
                $result[$k]['order_status']=$buyorder['order_status'];
            }
            $code=1;
            $data=$result;
            $msg='订单获取成功';
        }else{
            $code=0;
            $data='';
            $msg='暂时没有订单';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //凑单及分期支付
    public function pariedInfo(){
        $ordersn=input('param.ordersn');
        $flag=input('param.flag');//1支付 2 凑单支付
        $pt=new PintuanModel();
        $map['order_sn']=array('eq',$ordersn);
        $retrunData=[];
        $ptinfo=$pt->getPtInfo($map);
        if(is_array($ptinfo) && count($ptinfo)) {
            try {
                $tuanInfo = $pt->getJoinTuanInfo($ptinfo['id']);
                $retrunData['tid'] = $tuanInfo['tid'];
                $retrunData['branch'] = $tuanInfo['title'];
                $retrunData['end_time'] = $tuanInfo['end_time'];
                $retrunData['order_sn'] = $tuanInfo['order_sn'];
                $retrunData['order_type'] = $ptinfo['order_type'];
                $retrunData['order_time'] = date('Y-m-d H:i:s', $ptinfo['insert_time']);
                $retrunData['p_name'] = $tuanInfo['p_name'];
                $retrunData['p_price'] = $tuanInfo['tuan_price'];
                $retrunData['p_pic'] = $tuanInfo['p_pic'];
                $retrunData['tuan_num'] = $tuanInfo['tuan_num'];
                $surplus = $pt->checkOrder($tuanInfo['order_sn']);//还有几单未支付
                $retrunData['surplus'] = $surplus;//未成交订单数量
                $retrunData['attach'] = $pt->surplusOrdersn($tuanInfo['order_sn']);//未支付凑单订单号
                $retrunData['body'] = $tuanInfo['p_name'];
                $pay_price=$tuanInfo['initiator_pay'];//该拼团发起人支付金额
                $retrunData['no_period'] = Db::name('tuan_order')->where('order_sn',$tuanInfo['order_sn'])->value('no_period');
                if ($ptinfo['is_fq']) {
                    $surplusmoney = number_format(($surplus * $tuanInfo['partner_pay']) + $pay_price,2,'.','');//凑单付款价格
                    $retrunData['zanzhu'] = number_format($tuanInfo['tuan_price'] - $surplusmoney-$pay_price,2,'.','');
                } else {
                    if($flag==1){
                        $surplusmoney=$pay_price;
                        $retrunData['attach'] = $tuanInfo['order_sn'];//待支付订单号
                        $retrunData['zanzhu'] = 0.00;
                    }else{
                        $surplusmoney = number_format($surplus * $tuanInfo['partner_pay'],2,'.','');//凑单付款价格
                        $retrunData['zanzhu'] = number_format($tuanInfo['tuan_price'] - $surplusmoney-$pay_price,2,'.','');
                    }
                }
                $retrunData['pay_money'] = $surplusmoney;
                $retrunData['paid_member'] = $pt->getTuanPaidMember($tuanInfo['order_sn']);
                $orderInfo = $pt->getOrderInfo(['order_sn' => $ordersn]);
                $fenqi = getBankOnly($ptinfo['is_fq'], $orderInfo['pay_id'], $surplusmoney);
                $retrunData['pay_method'] = $fenqi;
                $code=1;
                $data=$retrunData;
                $msg='获取成功';
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='错误'.$e->getMessage();
            }
        }else{
            $code=0;
            $data='';
            $msg='订单不存在';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //老顾客查看参团的拼团进度
    public function  myJoinTuan(){
        $create_uid=input('param.uid');
        $type=input('param.type');
        $returnData=[];
        //type为0全部 1正常的订单 type为2 已完成订单
        if($type==1){
            $map['list.status']=array('eq',1);
        }elseif ($type==2){
            $map['list.status']=array('eq',2);
        }
        $pt=new PintuanModel();
        $map['order.uid']=array('eq',$create_uid);
        $map['order.flag']=array('eq',1);
        $map['order.pay_status']=array('gt',0);
        $map['list.order_type']=array('eq',1);//新增购买类型判断
        $listInfo=Db::name('tuan_order')->alias('order')->field('order.pay_by_self,order.order_sn son_sn,order.uid,order.order_status,order.pay_status,order.pay_price,order.pay_flag,order.buy_good_ids,member.realname,list.*')->join('tuan_list list','order.tuan_id=list.id')->join(['ims_bj_shopn_member' => 'member'],'order.uid=member.id')->where($map)->order('orderid desc')->select();
        if(count($listInfo)){
            try {
                foreach ($listInfo as $k => $v) {
                    if($v['pay_by_self']==1){
                        unset($listInfo[$k]);
                    }else {
                        $returnData[$k]['order_sn'] = $v['son_sn'];
                        $returnData[$k]['end_time'] = $v['end_time'];
                        $returnData[$k]['pay_price'] = $v['pay_price'];
                        $goods = new GoodsModel();
                        $gmap['id'] = array('in', $v['buy_good_ids']);
                        $prizeList = $goods->getGoodsByWhere($gmap);
                        $returnData[$k]['prizeList'] = $prizeList;
                        $returnData[$k]['prizeCount'] = count($prizeList);
                        $mem = new MemberModel();
                        $ownerInfo = $mem->getOneInfo(['member.id' => $v['create_uid']]);
                        $returnData[$k]['tuanOwner'] = $ownerInfo['nickname'];
                        $returnData[$k]['avatar'] = $ownerInfo['avatar'];
                        $returnData[$k]['surplus'] = $pt->checkOrder($v['order_sn']);//还有几单未支付;
                        $returnData[$k]['status'] = $pt->getOrderStatus($v['status']);
                        $returnData[$k]['order_status'] = $v['order_status'];
                    }
                }
                if(is_array($returnData) && count($returnData)){
                    $code=1;
                    $data=$returnData;
                    $msg='订单获取成功';
                }else{
                    $code=0;
                    $data='';
                    $msg='暂时没有订单';
                }
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='订单获取失败'.$e->getMessage();
            }
        }else{
            $code=0;
            $data='';
            $msg='暂时没有订单';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 消息中心
     */
    public function myMessage(){
        $uid=input('param.uid');
        if($uid!=''){
            $list=Db::name('member_message')->where('uid',$uid)->field("id,title,content,FROM_UNIXTIME(insert_time,'%Y-%m-%d %H:%i:%s') date")->order('insert_time desc')->select();
            if(count($list)){
                $code=1;
                $data=$list;
                $msg='消息获取成功';
                foreach ($list as $k=>$v){
                    Db::name('member_message')->where('id',$v['id'])->update(['status'=>1]);
                }
            }else{
                $code=0;
                $data='';
                $msg='暂无消息';
            }
        }else{
            $code=0;
            $data='';
            $msg='uid不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 该用户有没有新消息 有新消息出现红点
     */
    public function messageCount(){
        $uid=input('param.uid');
        if($uid!=''){
            $count=Db::name('member_message')->where('uid',$uid)->where('status',0)->count();
            if($count){
                $code=1;
                $data=$count;
                $msg='有新消息';
            }else{
                $code=0;
                $data=$count;
                $msg='暂无消息';
            }
        }else{
            $code=0;
            $data='';
            $msg='uid不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**------------------------------店老板------------------------------------------**/


    public function boosTuanList(){
        $tid=input('param.tid');
        if($tid!=''){
            $returnData=[];
            $pt=new PintuanModel();
            $seller=new MemberModel();
            $info=$pt->getOnePy($tid);
            if(is_array($info) && count($info)){
                try {
                    $returnData['p_name'] = $info['p_name'];
                    $returnData['p_pic'] = $info['p_pic'];
                    $returnData['p_price'] = $info['p_price'];
                    $Analysis = parent::getTuanAnalysis($info['storeid'], $tid);
                    $returnData['analysis'] = $Analysis;
                    //获取其下美容师推销的用户拼团
                    $sellerList = $pt->SellerShareList(['storeid' => $info['storeid'],'tuan_id'=>$tid]);
                    foreach ($sellerList as $k => $v) {
                        $sellerInfo = $seller->getOneInfo(['member.id' => $v['share_uid']]);
                        if($sellerInfo['nickname']==false){
                            $fans=Db::table('ims_fans')->field('nickname,avatar')->where('mobile',$sellerInfo['mobile'])->find();
                            if (preg_match('/(http:\/\/)/i', $fans['avatar'])) {
                                $avatar=$fans['avatar'];
                            }else{
                                $avatar=config('qiniu.image_url').'/avatar.png';
                            }
                            $nickname=$fans['nickname'];
                        }
                        $sellerList[$k]['nickname'] = $sellerInfo['nickname']?$sellerInfo['nickname']:$nickname;
                        $sellerList[$k]['avatar'] = $sellerInfo['avatar']?$sellerInfo['avatar']:$avatar;
                        $returnData['sellerList'] = $sellerList;
                        $eveyInfo = $pt->SellerShareAnalysis(['share_uid' => $v['share_uid'],'tuan_id'=>$tid]);
                        if (is_array($eveyInfo) && count($eveyInfo)) {
                            $str = '';
                            foreach ($eveyInfo as $kk => $vv) {
                                if ($vv['status'] == 1) {
                                    $str .= ' 进行中' . $vv['count'];
                                }
                                if ($vv['status'] == 2) {
                                    $str .= ' 已成团' . $vv['count'];
                                }
                                if ($vv['status'] == 3) {
                                    $str .= ' 已失效' . $vv['count'];
                                }
                            }
                        }
                        $sellerList[$k]['sub'] = "总计" . $v['total'] . $str;
                        unset($sellerList[$k]['total']);
                    }
                    $returnData['sellerList'] = $sellerList;
                    $code=1;
                    $data=$returnData;
                    $msg='数据获取成功';
                }catch (\Exception $e){
                    $code=0;
                    $data='';
                    $msg='错误'.$e->getMessage();
                }
            }else{
                $code=0;
                $data='';
                $msg='拼团信息不存在';
            }
        }else{
            $code=0;
            $data='';
            $msg='tid不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*店老板查看当前门店成功订单*/
    public function BranchSuccessOrder(){
        $storeid=input('param.storeid');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        if($storeid!='') {
            try {
                if($search_time1!='' && $search_time2!=''){
                    $map['insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
                }
                $map['storeid'] = array('eq', $storeid);
                $map['status'] = array('eq', 2);
                $returnData = [];
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 10000;// 显示条数
                $count = Db::name('tuan_list')->field('create_uid,order_sn')->where($map)->order('id desc')->count();
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage > $allpage) {
                    $code = 0;
                    $data = '';
                    $msg = '没有数据了';
                    return parent::returnMsg($code, $data, $msg);
                }
                $list = Db::name('tuan_list')->field('create_uid,order_sn')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
                $member = new MemberModel();
                foreach ($list as $k => $v) {
                    $returnData[$k] = $member->getOneInfo(['member.id' => $v['create_uid']]);
                    $returnData[$k]['order_sn'] = $v['order_sn'];
                }
                $code=1;
                $data=$returnData;
                $msg='数据获取成功';
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='获取失败'.$e->getMessage();
            }

        }else{
            $code=0;
            $data='';
            $msg='门店id不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*店老板查看当前门店进行中订单*/
    public function BranchWorkingOrder(){
        $storeid=input('param.storeid');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        if($storeid!='') {
            try {
                if($search_time1!='' && $search_time2!=''){
                    $map['insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
                }
                $map['storeid'] = array('eq', $storeid);
                $map['status'] = array('eq', 1);
                $returnData = [];
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 10000;// 显示条数
                $count = Db::name('tuan_list')->field('create_uid,order_sn')->where($map)->order('id desc')->count();
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage > $allpage) {
                    $code = 0;
                    $data = '';
                    $msg = '没有数据了';
                    return parent::returnMsg($code, $data, $msg);
                }
                $list = Db::name('tuan_list')->field('create_uid,order_sn')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
                $member = new MemberModel();
                foreach ($list as $k => $v) {
                    $returnData[$k] = $member->getOneInfo(['member.id' => $v['create_uid']]);
                    $returnData[$k]['order_sn'] = $v['order_sn'];
                }
                $code=1;
                $data=$returnData;
                $msg='数据获取成功';
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='获取失败'.$e->getMessage();
            }

        }else{
            $code=0;
            $data='';
            $msg='门店id不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*店老板查看当前门店未成功订单*/
    public function BranchFailOrder(){
        $storeid=input('param.storeid');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        if($storeid!='') {
            try {
                if($search_time1!='' && $search_time2!=''){
                    $map['list.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
                }
                $map['list.storeid'] = array('eq', $storeid);
                $map['list.status'] = array('in', '5');
                $returnData = [];
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 10000;// 显示条数
                $count = Db::name('tuan_list')->alias('list')->field('list.create_uid,list.order_sn,info.p_name')->join('tuan_info info','list.tuan_id=info.id','left')->where($map)->order('list.id desc')->count();
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage > $allpage) {
                    $code = 0;
                    $data = '';
                    $msg = '没有数据了';
                    return parent::returnMsg($code, $data, $msg);
                }
                $list = Db::name('tuan_list')->alias('list')->field('list.create_uid,list.order_sn,list.begin_time,info.p_name')->join('tuan_info info','list.tuan_id=info.id','left')->where($map)->page($Nowpage, $limits)->order('list.id desc')->select();
                $member = new MemberModel();
                foreach ($list as $k => $v) {
                    $returnData[$k] = $member->getOneInfo(['member.id' => $v['create_uid']]);
                    $returnData[$k]['order_sn'] = $v['order_sn'];
                    $returnData[$k]['p_name'] = $v['p_name'];
                    $returnData[$k]['time'] =formatTime($v['begin_time']);
                }
                $code=1;
                $data=$returnData;
                $msg='数据获取成功';
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='获取失败'.$e->getMessage();
            }
        }else{
            $code=0;
            $data='';
            $msg='门店id不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }





    /**------------------------取货逻辑------------------------------------------**/

    //顾客获取取货信息
    public function pickUpCode(){
        $orderSn=input('param.order_sn');
        if($orderSn!='') {
            try {
                $mem = new MemberModel();
                $getParentSn = Db::name('tuan_order')->where('order_sn', $orderSn)->value('parent_order');
                if($getParentSn) {
                    $sellerId = Db::name('tuan_list')->where('order_sn', $getParentSn)->value('share_uid');
                    $sellerInfo = $mem->getOneInfo(['member.id' => $sellerId]);
                    if (!preg_match('/(https:\/\/)/i',  $sellerInfo['avatar'])) {
                        $sellerInfo['avatar']=config('qiniu.image_url').'/avatar.png';
                    }
                    $codeUrl = pickUpCode($orderSn);
                    if ($codeUrl) {
                        //$sellerInfo['codeUrl'] = config('web_site_url') . $codeUrl;
                        $sellerInfo['codeUrl'] =$codeUrl;
                    }
                    $code=1;
                    $data=$sellerInfo;
                    $msg='消息获取成功';
                }else{
                    $code=0;
                    $data='';
                    $msg='暂无该订单';
                }
            }catch (\Exception $e){
                $code=0;
                $data='';
                $msg='出错'.$e->getMessage();
            }
        }else{
            $code=0;
            $data='';
            $msg='订单号不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);

    }


    /**
     * 美容师确认收货
     */
   public function pickUpConfrim(){
        $orderSn=input('param.order_sn');
        $qrcode_type=input('param.qrcode_type',0);
        $good_id=input('param.good_id',0);
        if($orderSn!='') {
            try {
                switch ($qrcode_type){
                    case 1:
                        $getCodeInfo = Db::name('ticket_user')->field('status,type,ticket_code')->where('ticket_code', $orderSn)->find();
                        if ($getCodeInfo) {
                            if ($getCodeInfo['status'] ==2) {
                                return parent::returnMsg(0,'','请勿重复确认');
                            }elseif ($getCodeInfo['status'] ==3) {
                                return parent::returnMsg(0,'','该券已失效');
                            } else {
                                //核销的是月度券还是闺蜜券
                                if ($getCodeInfo['type']== 6) {
                                    $ticket_pic = config('queen_day_pic.4');
                                } elseif ($getCodeInfo['type'] == 7) {
                                    $ticket_pic = config('queen_day_pic.6');
                                }else{
                                    $ticket_pic='';
                                }
                                Db::name('ticket_user')->where('ticket_code', $orderSn)->update(['status' => 2, 'draw_pic'=>$ticket_pic,'update_time' => date('Y-m-d H:i:s')]);
                                return parent::returnMsg(1,'','确认成功');
                            }
                        } else {
                            return parent::returnMsg(1,'','订单号不存在');
                        }
                        break;
                    case 2:
                        $getOrderInfo = Db::name('activity_order')->where('order_sn', $orderSn)->find();
                        if ($getOrderInfo) {
                            if($getOrderInfo['flag']){
                                $info=Db::name('activity_order_info')->where(['order_sn'=>$orderSn,'good_id'=>$good_id])->find();
                                if ($info['pick_up'] == 1) {
                                    return parent::returnMsg(0,'','该产品已取货');
                                } else {
                                    Db::name('activity_order_info')->where(['order_sn'=>$getOrderInfo['order_sn'],'good_id'=>$good_id])->update(['pick_up' => 1,'update_time'=>time()]);
                                    //如果单品都取货完成 主订单标识已取货
                                    $infoCount=Db::name('activity_order_info')->where(['order_sn'=>$orderSn,'pick_up'=>0])->count();
                                    if($infoCount==0){
                                        Db::name('activity_order')->where('order_sn', $getOrderInfo['order_sn'])->update(['order_status' => 1]);
                                        Db::table('think_scores_record')->where('remark',$getOrderInfo['order_sn'])->update(['usable'=>1]);
                                    }
                                    return parent::returnMsg(1,'','确认成功');
                                }
                            }else{
                                if ($getOrderInfo['order_status'] == 1) {
                                    return parent::returnMsg(0,'','请勿重复确认');
                                } else {
                                    Db::name('activity_order')->where('order_sn', $getOrderInfo['order_sn'])->update(['order_status' => 1]);
                                    //核销完成 解冻积分
                                    Db::table('think_scores_record')->where('remark',$getOrderInfo['order_sn'])->update(['usable'=>1]);
                                    return parent::returnMsg(1,'','确认成功');
                                }
                            }
                        } else {
                            return parent::returnMsg(0,'','订单号不存在');
                        }
                        break;
                    case 3:
                        $getCodeInfo = Db::name('ticket_user')->field('type,status,type,ticket_code,par_value,draw_pic')->where('ticket_code', $orderSn)->find();
                        if ($getCodeInfo) {
                            if($getCodeInfo['status'] == -1 && $getCodeInfo['type'] != 20){
                                Db::name('ticket_user')->where('ticket_code', $orderSn)->update(['status' => 0,'draw_pic'=>config("transfer_ticket.cash_".$getCodeInfo['par_value']."_1"), 'update_time' => date('Y-m-d H:i:s')]);
                                return parent::returnMsg(1,'','激活成功');
                            }
                            if($getCodeInfo['status'] == 0){
                                if ($getCodeInfo['type']== 10) {
                                    $ticket_pic = config("transfer_ticket.cash_".$getCodeInfo['par_value']."_2");
                                }elseif($getCodeInfo['type']==20){
                                    $ticket_pic = $getCodeInfo['draw_pic'];
                                }else {
                                    $ticketImg = Db::name('draw_scene')->where('scene_prefix', $getCodeInfo['type'])->value('image2');
                                    $ticket_pic = $ticketImg;
                                }
                                Db::name('ticket_user')->where('ticket_code', $orderSn)->update(['status' => 2,'draw_pic'=>$ticket_pic,'update_time' => date('Y-m-d H:i:s')]);
                                return parent::returnMsg(1,'','确认成功');
                            }else{
                                return parent::returnMsg(0,'','请勿重复核销');
                            }
                        }else{
                            return parent::returnMsg(0,'','奖券不存在');
                        }
                        break;
                    case 4:
                        $getCodeInfo = Db::name('order_lucky')->field('order_sn,flag')->where('order_sn', $orderSn)->find();
                        if ($getCodeInfo) {
                            if($getCodeInfo['flag'] == 0){
                                Db::name('order_lucky')->where('order_sn', $orderSn)->update(['flag' => 1, 'update_time' => time()]);
                                return parent::returnMsg(1,'','确认成功');
                            }else{
                                return parent::returnMsg(0,'','请勿重复核销');
                            }
                        }else{
                            return parent::returnMsg(0,'','奖券不存在');
                        }
                        break;
                    case 5:
                        //查询订单
                        $getOrderInfo = Db::name('bargain_order')
                            ->where('order_sn', $orderSn)
                            ->find();
                        if (!empty($getOrderInfo)) {
                            if ($getOrderInfo['order_status'] == 1) {
                                return parent::returnMsg(0,'','请勿重复确认');
                            } else {
                                Db::name('bargain_order')->where('order_sn', $getOrderInfo['order_sn'])->update(['order_status' => 1]);
                                return parent::returnMsg(1,'','确认成功');
                            }
                        } else {
                            return parent::returnMsg(0,'','订单号不存在');
                        }
                        break;
                    case 6:
                        $getCode = Db::name('ticket_user')->where(['share_code'=>$orderSn])->field('type,mobile,status,type,ticket_code,order_sn,ticket_num')->count();
                        if($getCode){
                            $map['share_code']=array('eq',$orderSn);
                        }else{
                            $map['ticket_code']=array('eq',$orderSn);
                        }
                        if($good_id){
                            $map['goods_id']=array('eq',$good_id);
                        }
                        $getCodeInfo = Db::name('ticket_user')->field('type,mobile,status,type,ticket_code,order_sn,ticket_num')
                            ->where($map)
                            ->find();
                        if ($getCodeInfo) {
                            if($getCodeInfo['status'] == 2){
                                return parent::returnMsg(0,'','失败，该券已核销完毕');
                            }
                            if($getCodeInfo['type']==19){
                                Db::name('ticket_user')->where($map)->update(['status' => 2,'update_time' => date('Y-m-d H:i:s'),'remark'=>$getCodeInfo['mobile']]);
                                Db::name('activity_order_info')->where(['order_sn'=>$getCodeInfo['order_sn'],'good_id'=>$good_id])->update(['pick_up' => 1,'update_time'=>time()]);
                                Db::name('activity_order_sharing')->where(['order_sn'=>$getCodeInfo['order_sn'],'share_pid'=>$good_id])->update(['num' => 1,'update_time'=>time()]);
                            }else{
                                $map1['order_sn']=array('eq',$getCodeInfo['order_sn']);
                                $map1['ticket_sn']=array('eq',$getCodeInfo['ticket_code']);
                                $map1['mobile']=array('eq',$getCodeInfo['mobile']);
                                Db::name('activity_order_sharing')->where($map1)->setInc('num');
                                $getNum=Db::name('activity_order_sharing')->where(['order_sn'=>$getCodeInfo['order_sn'],'sharing_flag'=>1,'accept_flag'=>1])->sum('num');
                                if($getNum==$getCodeInfo['ticket_num']){
                                    Db::name('ticket_user')->where(['order_sn'=>$getCodeInfo['order_sn']])->update(['status' => 2,'update_time' => date('Y-m-d H:i:s')]);
                                    Db::name('activity_order_info')->where(['order_sn'=>$getCodeInfo['order_sn']])->update(['pick_up' => 1,'update_time'=>time()]);
                                }
                            }
                            $infoCount=Db::name('activity_order_info')->where(['order_sn'=>$getCodeInfo['order_sn'],'pick_up'=>0])->count();
                            if($infoCount==0){
                                Db::name('activity_order')->where('order_sn', $getCodeInfo['order_sn'])->update(['order_status' => 1]);
                            }
                            Db::table('think_scores_record')->where('remark',$getCodeInfo['order_sn'])->update(['usable'=>1]);
                            return parent::returnMsg(1,'','核销成功');
                        }else{
                            return parent::returnMsg(0,'','卡券不存在');
                        }
                        break;
                    default:
                        $getOrderInfo = Db::name('tuan_order')->where('order_sn', $orderSn)->find();
                        if ($getOrderInfo) {
                            if ($getOrderInfo['order_status'] == 3) {
                                return parent::returnMsg(0,'','请勿重复确认');
                            } else {
                                Db::name('tuan_order')->where('order_sn', $getOrderInfo['order_sn'])->update(['order_status' => 3, 'pick_up_time' => time()]);
                                //记录访问日志
                                $userToken = Db::table('ims_bj_shopn_member')->alias('mem')->where('mem.id', $getOrderInfo['uid'])->join('wx_user u', 'mem.mobile=u.mobile', 'left')->value('u.token');
                                $logsData = parent::getInfoByToken($userToken);
                                if (is_array($logsData)) {
                                    $logsData['action'] = 5;
                                    $logsData['remark'] = '门店取货成功';
                                    $logsData['insert_time'] = time();
                                    $this->logToRedis($logsData);
                                }
                                return parent::returnMsg(1,'','确认成功');
                            }
                        } else {
                            return parent::returnMsg(0,'','订单号不存在');
                        }
                }
            }catch (\Exception $e){
                return parent::returnMsg(0,'','出错'.$e->getMessage());
            }
        }else{
            return parent::returnMsg(0,'','订单号不允许为空');
        }
    }

    /**
     * 美容师确认收货产品详细
     */
    public function pickUpGoodsInfo(){
        $orderSn=input('param.order_sn');
        $uid=input('param.uid');
        if($orderSn!='' && $uid!='') {
            $order=explode('_',$orderSn);
            if(count($order)>1){
                $sellerStoreId = Db::table('ims_bj_shopn_member')->where('id',$uid)->value('storeid');
                switch ($order[0]){
                    case 'missshop':
                        $orderInfo = Db::name('activity_order')->alias('o')->where(['o.order_sn'=>$order[1]])
                            ->field('fid,storeid,order_sn,insert_time,pay_time,pay_price,num,order_status,specs,flag,pid')
                            ->find();
                        if (is_array($orderInfo) && count($orderInfo)) {
                            if($sellerStoreId==$orderInfo['storeid']) {
                                if($orderInfo['flag']){
                                    $info=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where(['info.order_sn'=>$order[1],'info.good_id'=>$order[2]])->field('info.*,g.name,g.image,g.images,g.unit')->find();
                                    if ($info['pick_up'] == 1) {
                                        return parent::returnMsg(0,'','该产品已取货');
                                    } else {
                                        $orderInfo['name'] = $info['name'].' '.$info['good_specs'];
                                        $orderInfo['insert_time'] = date('Y-m-d H:i:s', $info['insert_time']);
                                        $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
                                        $orderInfo['qrcode_type'] = 2;
                                        $orderInfo['images'] = strtok($info['images'], ',');
                                        $orderInfo['num'] = $info['good_num'];
                                        $orderInfo['specs'] = $info['good_specs'];
                                        $orderInfo['pay_price'] = $info['good_amount'];
                                        $orderInfo['image'] = $info['image'];
                                        $orderInfo['unit'] = $info['unit'];
                                        $orderInfo['good_id'] = $info['good_id'];
                                        unset($orderInfo['pid'],$orderInfo['flag']);
                                        return parent::returnMsg(1,$orderInfo,'获取成功');
                                    }
                                }else{
                                    if ($orderInfo['order_status'] == 1) {
                                        return parent::returnMsg(0,'','该订单已取货');
                                    } else {
                                        $goodsInfo=Db::name('goods')->where('id',$orderInfo['pid'])->field('name,image,images,unit')->find();
                                        $orderInfo['name'] = $goodsInfo['name'].' '.$orderInfo['specs'];
                                        $orderInfo['insert_time'] = date('Y-m-d H:i:s', $orderInfo['insert_time']);
                                        $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
                                        $orderInfo['qrcode_type'] = 2;
                                        $orderInfo['unit'] = $goodsInfo['unit'];
                                        $orderInfo['image'] = $goodsInfo['image'];
                                        $orderInfo['images'] = strtok($goodsInfo['images'], ',');
                                        $orderInfo['good_id'] = $orderInfo['pid'];
                                        unset($orderInfo['pid'],$orderInfo['flag']);
                                        return parent::returnMsg(1,$orderInfo,'获取成功');
                                    }
                                }
                            } else {
                                return parent::returnMsg(0,'','该订单您没有查看权限');
                            }
                        }else{
                            return parent::returnMsg(0,'','订单号不存在');
                        }
                        break;
                    case 'activate':
                        $orderInfo=Db::name('ticket_user')->where(['ticket_code'=>$order[1]])->field('type,ticket_code,draw_pic,par_value,status,mobile,storeid')->find();
                        if (is_array($orderInfo) && count($orderInfo)) {
                            if($sellerStoreId==$orderInfo['storeid']) {
                                if($orderInfo['type']==20){
                                    if ($orderInfo['status'] == 2) {
                                        return parent::returnMsg(0, '', '请勿重复核销');
                                    }
                                }else {
                                    if ($orderInfo['type'] == 10 && $orderInfo['status'] != -1) {
                                        return parent::returnMsg(0, '', '请勿重复激活');
                                    }
                                    if ($orderInfo['status'] == -1) {
                                        Db::name('ticket_user')->where('ticket_code', $order[1])->update(['status' => 0, 'draw_pic' => config("transfer_ticket.cash_" . $orderInfo['par_value'] . "_1"), 'update_time' => date('Y-m-d H:i:s')]);
                                        return parent::returnMsg(0, '', '激活成功');
                                    }
                                }
                                $orderInfo['qrcode_type'] = 3;
                                return parent::returnMsg(1, $orderInfo, '获取成功');
                            }else{
                                return parent::returnMsg(0, '', '您无权限处理');
                            }
                        } else {
                            return parent::returnMsg(0, '', '券不存在，请确认');
                        }
                        break;
                    case 'lucky':
                        $orderInfo=Db::name('order_lucky')->where(['order_sn'=>$order[1]])->field('flag,order_sn,lucky_name,uid,lucky_image')->find();
                        $buyStoreId=Db::table('ims_bj_shopn_member')->where('id',$orderInfo['uid'])->value('storeid');
                        if (is_array($orderInfo) && count($orderInfo)) {
                            if($sellerStoreId==$buyStoreId) {
                                if ($orderInfo['flag']) {
                                    return parent::returnMsg(0, '', '该码已核销');
                                } else {
                                    $orderInfo['qrcode_type'] = 4;
                                    return parent::returnMsg(1, $orderInfo, '获取成功');
                                }
                            }else{
                                return parent::returnMsg(0, '', '您无权限处理');
                            }
                        }else{
                            return parent::returnMsg(0,'','券不存在，请确认');
                        }
                        break;
                    case 'bargain':
                        $field = "o.order_sn,o.storeid,o.uid,o.fid,o.insert_time,o.pay_time,o.pay_price,o.num,o.order_status,o.specs,g.name,g.image,g.images,g.unit";
                        $orderInfo = Db::name('bargain_order')
                            ->alias('o')
                            ->join('goods g','o.goods_id=g.id','left')
                            ->where(['o.order_sn' => $order[1]])
                            ->field($field)
                            ->find();
                        //订单是否存在
                        if (!empty($orderInfo)) {
                            //统一门店可以核销
                            if($orderInfo['storeid'] == $sellerStoreId){
                                if ($orderInfo['order_status'] == 1) {
                                    return parent::returnMsg(0,'','该订单已取货');
                                } else {
                                    $orderInfo['name'] = $orderInfo['name'].' '.$orderInfo['specs'];
                                    $orderInfo['insert_time'] = date('Y-m-d H:i:s', $orderInfo['insert_time']);
                                    $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
                                    $orderInfo['qrcode_type'] = 5;
                                    $orderInfo['images'] = strtok($orderInfo['images'], ',');
                                    return parent::returnMsg(1,$orderInfo,'获取成功');
                                }
                            }else{
                                return parent::returnMsg(0,'','该订单您没有查看权限');
                            }

                          /*  //判断用户是否是订单所属美容师
                            if ($uid == $orderInfo['fid']) {
                                if ($orderInfo['order_status'] == 1) {
                                    return parent::returnMsg(0,'','该订单已取货');
                                } else {
                                    $orderInfo['name'] = $orderInfo['name'].' '.$orderInfo['specs'];
                                    $orderInfo['insert_time'] = date('Y-m-d H:i:s', $orderInfo['insert_time']);
                                    $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
                                    $orderInfo['qrcode_type'] = 5;
                                    $orderInfo['images'] = strtok($orderInfo['images'], ',');
                                    return parent::returnMsg(1,$orderInfo,'获取成功');
                                }
                            } else {
                                return parent::returnMsg(0,'','该订单您没有查看权限');
                            }*/
                        }else{
                            return parent::returnMsg(0,'','订单不存在或已失效，请确认');
                        }
                        break;
                    case 'sharing':
                        $map['ticket_code|share_code']=array('eq',$order[1]);
                        if(isset($order[2])){
                            $map['goods_id']=array('eq',$order[2]);
                        }
                        $orderInfo=Db::name('ticket_user')->where($map)->field('type,ticket_code,order_sn,status,mobile,storeid,ticket_num,goods_id good_id')->find();
                        if (is_array($orderInfo) && count($orderInfo)) {
                            $getNum=0;
                            if($sellerStoreId==$orderInfo['storeid']) {
                                if($orderInfo['status']==2){
                                    return parent::returnMsg(0, '', '失败，该券已核销完毕');
                                }
                                if($orderInfo['type']==18){
                                    $getNum=Db::name('activity_order_sharing')->where(['order_sn'=>$orderInfo['order_sn'],'sharing_flag'=>1,'accept_flag'=>1])->sum('num');
                                    if($getNum>$orderInfo['ticket_num']){
                                        return parent::returnMsg(0, '', '该券使用次数已用完');
                                    }
                                    $getInfo=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where(['order_sn'=>$orderInfo['order_sn'],'main_flag'=>1])->field('info.good_num,g.images,g.name')->find();
                                }else{
                                    $getInfo=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where(['order_sn'=>$orderInfo['order_sn'],'good_id'=>$orderInfo['good_id']])->field('info.good_num,g.images,g.name')->find();
                                }
                                $orderInfo['name'] =$getInfo['name']?$getInfo['name']:'';
                                $getPic=strtok($getInfo['images'], ',');
                                $orderInfo['images'] =$getPic?$getPic:'';
                                $orderInfo['use_num'] =$getNum?$getNum:0;
                                $orderInfo['good_num'] =$getInfo['good_num'];
                                $orderInfo['qrcode_type'] = 6;
                                $orderInfo['ticket_code'] =$order[1];
                                unset($orderInfo['status'],$orderInfo['storeid'],$orderInfo['order_sn']);
                                return parent::returnMsg(1, $orderInfo, '获取成功');
                            }else{
                                return parent::returnMsg(0, '', '您无权限处理');
                            }
                        } else {
                            return parent::returnMsg(0, '', '券不存在，请确认');
                        }

                        break;
                    default:
                        $codeInfo=Db::name('ticket_user')->where(['ticket_code'=>$order[1]])->field('branch,sign,mobile,ticket_code,status,insert_time,draw_pic,aead_time')->find();
                        if (is_array($codeInfo) && count($codeInfo)) {
                            $codeInfo['qrcode_type'] = 1;
                            $getSellerUid = Db::table('ims_bj_shopn_member')->where('mobile', $codeInfo['mobile'])->value('staffid');
                            if ($uid == $getSellerUid) {
                                if ($codeInfo['status'] == 2) {
                                    return parent::returnMsg(0,'','该码已使用');
                                } elseif ($codeInfo['status'] == 3) {
                                    return parent::returnMsg(0,'','该码已失效');
                                } else {
                                    $aeadTime = date('n月d日', $codeInfo['aead_time']);
                                    $beginTime = date('n月1日', $codeInfo['aead_time']);
                                    if ($codeInfo['status'] == 3) {
                                        $tips = '已失效';
                                    } elseif ($codeInfo['status'] == 1 || $codeInfo['status'] == 2) {
                                        $tips = '已使用';
                                    } else {
                                        $tips = '有效期：' . $beginTime . " ～ " . $aeadTime;
                                    }
                                    $codeInfo['aead_time'] = $tips;
                                    return parent::returnMsg(1,$codeInfo,'活动奖券信息获取成功');
                                }
                            } else {
                                return parent::returnMsg(0,'','该奖券您没有处理权限');
                            }
                        }else{
                            return parent::returnMsg(0,'','奖券号不存在');
                        }
                }
            }else {
                try {
                    $pt = new PintuanModel();
                    $getOrderInfo = Db::name('tuan_order')->where('order_sn', $orderSn)->find();
                    if ($getOrderInfo) {
                        $returnData = [];
                        $info = $pt->getJoinTuanInfo($getOrderInfo['tuan_id']);
                        if ($uid == $info['share_uid']) {
                            if ($getOrderInfo['flag'] == 0) {//主单
                                $mem = new MemberModel();
                                $buyInfo = $mem->getOneInfo(['member.id' => $info['create_uid']]);
                                $returnData['nickname'] = $buyInfo['nickname'];
                                $returnData['avatar'] = $buyInfo['avatar'];
                                $returnData['order_sn'] = $orderSn;
                                $pay_by_self = $pt->getPriceBySelfPay($info['order_sn']);
                                $returnData['pay_money'] = number_format($getOrderInfo['pay_price'] + $pay_by_self, 2, '.', '');
                                $goods['name'] = $info['p_name'];
                                $goods['price'] = $info['tuan_price'];
                                $goods['image'] = $info['p_pic'];
                                $goods['intro'] = $info['p_intro'];
                                $goods['unit'] = '';
                                $returnData['list'][] = $goods;
                            } else {//子单
                                $mem = new MemberModel();
                                $buyInfo = $mem->getOneInfo(['member.id' => $getOrderInfo['uid']]);
                                $returnData['nickname'] = $buyInfo['nickname'];
                                $returnData['avatar'] = $buyInfo['avatar'];
                                $returnData['order_sn'] = $orderSn;
                                $returnData['pay_money'] = $getOrderInfo['pay_price'];
                                $gmap['id'] = array('in', $getOrderInfo['buy_good_ids']);
                                $goods = new GoodsModel();
                                $prizeList = $goods->getGoodsByWhere($gmap);
                                $returnData['list'] = $prizeList;
                            }
                            $returnData['qrcode_type']=0;
                            $code = 1;
                            $data = $returnData;
                            $msg = '订单详细获取成功';
                        } else {
                            $code = 0;
                            $data = '';
                            $msg = '该订单您没有查看权限';
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '订单号不存在';
                    }
                } catch (\Exception $e) {
                    $code = 0;
                    $data = '';
                    $msg = '出错' . $e->getMessage();
                }
            }
        }else{
            $code=0;
            $data='';
            $msg='订单号不允许为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //用户是否在该订单中是否已支付
    public function checkOrderPay(){
        $uid=input('param.uid');
        $orderSn=input('param.ordersn');
        $pt=new PintuanModel();
        $info=$pt->getOrderInfo(['uid'=>$uid,'parent_order'=>$orderSn]);
        if($info['pay_status']==1){
            $code=1;
            $data=1;
            $msg='该用户已支付';
        }else{
            $code=0;
            $data=0;
            $msg='该用户未支付';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //配赠产品详细
    public function prizeInfo(){
        $id=input('param.id');
        if($id!=''){
            $pinfo=new GoodsModel();
            $info=$pinfo->getGoodsInfo($id);
            if(is_array($info)) {
                $info['images'] = explode(',',$info['images']);
                $code = 1;
                $data = $info;
                $msg = '获取成功！';
            }else{
                $code = 0;
                $data = '';
                $msg = '产品信息不存在！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '产品id必须！';
        }
        return parent::returnMsg($code,$data,$msg);
    }
}