<?php
/**
 * Created by PhpStorm.
 * User: houdj
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\api\controller;
use think\Cache;
use think\Db;
use think\Debug;

/**
 * swagger: 统计数据分析-访问概况
 */
class ActivityAnalysis extends Base
{
    public static $missshop_2_expire = 60;//秒
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
      /* if($token==''){
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
       }*/
    }
    public function activity_index(){
        $sellerId=input('param.sellerid');// 美容师uid
        $storeid=input('param.storeid',0);// 0=>所有门店
        $map=[];
        $map3=[];
        if($storeid){
            $map['order.storeid']=array('eq',$storeid);
            $storeInfo=Db::table('ims_bwk_branch')->where('id',$storeid)->field('id,title,sign')->find();
            if($storeInfo['sign']=='000-000'){
                 $title=msubstr($storeInfo['title'],0,2,"utf-8",false);//截取前2位 去匹配管理门店
                 $getBids=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department'=>'d'],'r.id_department=d.id_department','left')->where('d.st_department',$title)->where('d.st_department',$title)->column('id_beauty');
                 if($getBids){
                     $map['order.storeid']=array('in',$getBids);
                 }
            }else{
                if($sellerId !=''){
                    $map3['order.fid']=array('eq',$sellerId);
                }
            }
        }else{
            $storeInfo=['id'=>0,'title'=>'所有门店'];
        }


        $map['channel']=array('eq','missshop');
        //待支付订单数量
//        $needPayNum=Db::name('activity_order')->alias('order')->where(['pay_status'=>1])->join(['ims_bj_shopn_member' => 'member'], 'order.uid=member.id', 'left')->where($map)->count();

        //门店增加趋势
        //获取活动进行日期
        $orderDate=Db::name('activity_order')->where(['pay_status'=>1,'channel'=>'missshop'])->field("FROM_UNIXTIME(insert_time,'%Y-%m-%d') days")->group('days')->select();
        $bs=[];
        $map4['bwk.sign']=array('not in',array('000-000','666-666','888-888'));
        $branchNum=Db::table('ims_bj_shopn_member')->alias('member')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->where(['member.isadmin'=>1,'member.activity_key'=>1])->where($map4)->count();
        $branchPayNum=0;
        if($orderDate){
            foreach ($orderDate as $k=>$v){
                $y=0;
                $map1['pay_status']=array('eq',1);
                $map1['channel']=array('eq','missshop');
                $branch_order=Db::name('activity_order')->where($map1)->whereTime('insert_time', 'between', [$v['days'].' 00:00:00', $v['days']." 23:59:59"])->group('storeid')->column('storeid');
                if($branch_order){
                    foreach ($branch_order as $vv){
                        if(!in_array($vv,$bs)){
                            $bs[]=$vv;
                            $y++;
                        }
                    }
                }
                $orderDate[$k]['days']=substr($v['days'],5);
                $orderDate[$k]['count']=$y;
                $branchPayNum+=$y;
            }
        }
        $branchData=['total'=>$branchNum,'payBranch'=>$branchPayNum,'list'=>$orderDate];

        //成交顾客增加趋势
        $orderDate1=Db::name('activity_order')->where(['pay_status'=>1])->alias('order')->where($map)->field("FROM_UNIXTIME(insert_time,'%Y-%m-%d') days")->group('days')->select();
        $userNum=0;
        if($orderDate1) {
            foreach ($orderDate1 as $kk => $vv) {
                $user = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member'=>'m'],'order.uid=m.id','left')->where(['order.pay_status' => 1])->where($map)->where($map3)->where('m.activity_flag','8806')->whereTime('insert_time', 'between', [$vv['days'] . ' 00:00:00', $vv['days'] . " 23:59:59"])->field("FROM_UNIXTIME(order.insert_time,'%Y-%m-%d') days")->group('uid')->select();
                $orderDate1[$kk]['days']=substr($vv['days'],5);
                $orderDate1[$kk]['count']=count($user);
                $userNum+=count($user);
            }
        }else{
            $orderDate1[0]['days']=date('m-d');
            $orderDate1[0]['count']=0;
        }
        $userData=['total'=>$userNum,'list'=>$orderDate1];
        //成交金额增加趋势
        $money=Db::name('activity_order')->alias('order')->where(['order.pay_status'=>1])->where($map)->where($map3)->field("FROM_UNIXTIME(order.insert_time,'%Y-%m-%d') days,sum(order.pay_price) count")->group('days')->select();
        $moneyNum=0;
        if($money){
            foreach ($money as $k=>$v){
                $money[$k]['days']=substr($v['days'],5);;
                $money[$k]['count']=round($v['count'],2);
                $moneyNum+=$v['count'];
            }
        }else{
            $money[0]['days']=date('m-d');
            $money[0]['count']=0;
        }
        $moneyData=['total'=>$moneyNum,'list'=>$money];
        $res=['store'=>$storeInfo,'needPayNum'=>0,'ticketNumb'=>0,'branchData'=>$branchData,'userData'=>$userData,'moneyData'=>$moneyData];
        return parent::returnMsg(1,$res,'获取成功');
    }

    /*
     * 活动门店列表
     */
    public function activity_branch(){
        $storeid=input('param.storeid',0);// 0=>所有门店
        if($storeid){
            $storeInfo=Db::table('ims_bwk_branch')->where('id',$storeid)->field('id,title,sign')->find();
        }else{
            $storeInfo=['id'=>0,'title'=>'所有门店'];
        }
        $Nowpage = input('param.page') ? input('param.page') : 1;
        $limits = 10;// 显示条数

        if($storeid) {
            $count = 1;
        }else{
            $count = Db::table('ims_bj_shopn_member')->where(['isadmin' => 1, 'activity_key' => 1])->count();
        }
        //$count = Db::name('activity_order')->alias('order')->where($map)->field('order.id')->join(['ims_bwk_branch' => 'bwk'], 'order.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->group('order.storeid')->select();
        $allpage = intval(ceil($count / $limits));
        if ($Nowpage >= $allpage) {
            $storeInfo['next_page_flag']=0;//是否有下一页
        }else{
            $storeInfo['next_page_flag']=1;
        }
        $map['isadmin']=array('eq',1);
        if($storeid){
            if($storeInfo['sign']=='000-000'){
                $title=msubstr($storeInfo['title'],0,2,"utf-8",false);//截取前2位 去匹配管理门店
                $getBids=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department'=>'d'],'r.id_department=d.id_department','left')->where('d.st_department',$title)->where('d.st_department',$title)->column('id_beauty');
                if($getBids){
                    $map['bwk.id']=array('in',$getBids);
                }
            }else{
                $map['bwk.id']=array('eq',$storeid);
            }
        }else{
            $map['activity_key']=array('eq',1);
            $map['sign']=array('not in',array('000-000','666-666','888-888'));
        }
        $subsql = Db::name('activity_order')->where(['pay_status'=>1,'channel'=>'missshop'])->field('storeid,sum(pay_price) count')->group('storeid')->buildSql();
        $lists = Db::table('ims_bj_shopn_member')->alias('member')->where($map)->field('bwk.id,count,bwk.title')->join([$subsql=> 'orders'], 'member.storeid = orders.storeid','left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->page($Nowpage, $limits)->order('count desc')->select();
        if(count($lists) && is_array($lists)){
            foreach ($lists as $k=>$v){
                $lists[$k]['storeid']=$v['id'];
                $lists[$k]['total_price']=$v['count']?$v['count']:0;
                $lists[$k]['st_department']=Db::table('sys_departbeauty_relation')->alias('departbeauty')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('departbeauty.id_beauty',$v['id'])->value('depart.st_department');
                unset($lists[$k]['id']);
                unset($lists[$k]['count']);
            }
        }
        if($storeInfo || $lists){
            $code = 1;
            $data = ['stores'=>$storeInfo,'branch_list'=>$lists];
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = [];
            $msg = '暂无数据';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 活动订单列表
     */
    public function activity_order(){
        $storeid=input('param.storeid',0);// 0=>所有门店
        $uid=input('param.uid');// 只看她
        $status=input('param.status','');// 订单状态
        $sellerId=input('param.sellerid');// 美容师uid
        $map=[];
        if($storeid){
            $map['order.storeid']=array('eq',$storeid);
            $storeInfo=Db::table('ims_bwk_branch')->where('id',$storeid)->field('id,title')->find();
        }else{
            $storeInfo=['id'=>0,'title'=>'所有门店'];
        }
        if($status !=''){
            $map['order.pay_status']=array('eq',$status);
        }
        if($uid !=''){
            $map['order.uid']=array('eq',$uid);
        }
        if($sellerId !=''){
            $map['member.staffid']=array('eq',$sellerId);
        }
        $map['order.channel']=array('eq','missshop');
        $Nowpage = input('param.page') ? input('param.page') : 1;
        $limits = 10;// 显示条数
        $count = Db::name('activity_order')->alias('order')->where($map)->field('order.id,order.order_sn,order.pay_time,order.insert_time,order.pay_price,order.storeid,bwk.title,bwk.sign')->join(['ims_bj_shopn_member' => 'member'], 'order.uid=member.id', 'left')->join(['ims_bwk_branch' => 'bwk'], 'order.storeid=bwk.id', 'left')->count();
        $allpage = intval(ceil($count / $limits));
        if ($Nowpage >= $allpage) {
            $storeInfo['next_page_flag']=0;//是否有下一页
        }else{
            $storeInfo['next_page_flag']=1;
        }
        //$map['member.activity_flag']=array('eq','8806');
        $lists = Db::name('activity_order')->alias('order')->where($map)->field('order.uid,order.insert_time,order.pay_price,bwk.title,member.mobile,member.staffid,user.nickname,user.avatar')->join(['ims_bj_shopn_member' => 'member'], 'order.uid=member.id', 'left')->join('wx_user user', 'member.mobile=user.mobile', 'left')->join(['ims_bwk_branch' => 'bwk'], 'order.storeid=bwk.id', 'left')->page($Nowpage, $limits)->order('order.id desc')->select();
        if($lists){
            foreach ($lists as $k=>$v){
                $lists[$k]['seller']=Db::table('ims_bj_shopn_member')->where('id',$v['staffid'])->value('realname');
                $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
                unset($lists[$k]['staffid']);
            }
        }
        if($storeInfo || $lists){
            $code = 1;
            $data = ['stores'=>$storeInfo,'order_list'=>$lists];
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = [];
            $msg = '暂无数据';
        }
        return parent::returnMsg($code,$data,$msg);
    }



    //门店列表
    public function storeList(){
        $storeid=input('param.storeid',0);// 0=>所有门店
        $map=[];
        if($storeid){
            $storeInfo=Db::table('ims_bwk_branch')->where('id',$storeid)->field('id,title,sign')->find();
            if($storeInfo['sign']=='000-000'){
                $title=msubstr($storeInfo['title'],0,2,"utf-8",false);//截取前2位 去匹配管理门店
                $getBids=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department'=>'d'],'r.id_department=d.id_department','left')->where('d.st_department',$title)->where('d.st_department',$title)->column('id_beauty');
                if($getBids){
                    $map['id']=array('in',$getBids);
                }
            }
        }
        $list=Db::table('ims_bwk_branch')->where($map)->field('id storeid,title,sign')->select();
        if($list){
            $code = 1;
            $data =$list;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '获取失败';
        }

        return parent::returnMsg($code,$data,$msg);
    }


    //销量排行
    public function missRanking(){
        $uId=input('param.uid');//店老板或者美容师id
        $type=input('param.type');//类型 1是店内美容师积分排行 2是办事处门店积分排行榜 3是全国门店积分排行榜
        if($uId!='' && $type!=''){
            $map=[];
            $m=[];
            //获取排行榜展示截至日期
            $endTime=Db::name('activity_config')->where('id',1)->value('show_time');
            if($endTime){
                $map['o.pay_time'] = array('elt', $endTime);
                $m['o.pay_time'] = array('elt', $endTime);
            }
            $map['o.scene'] = array('eq', 0);
            $m['o.scene'] = array('eq', 0);
            $getManager=config('selectManager');
            $getManager=explode(',',$getManager);
            $showBids=[];//各角色查看到的美容师所属门店
            $bscBids=[];//各角色查看到的美容师所属门店
            $bscName='';
            if(!in_array($uId,$getManager)) {
                //检测该用户是美容师还是店老板  美容师看自己的销售  店老板查看店内所有美容师的销量
                $userInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch' => 'b'], 'm.storeid=b.id')->where('m.id', $uId)->field('m.isadmin,b.title,m.storeid,b.sign')->find();
                if ($userInfo['sign'] == '000-000') {
                    $title = msubstr($userInfo['title'], 0, 2, "utf-8", false);//截取前2位 去匹配管理门店
                    $showBids = Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'], 'r.id_department=d.id_department', 'left')->where('d.st_department', $title)->column('id_beauty');
                    $bscBids=$showBids;
                    $bscName=$userInfo['title'];
                } else {
                    $showBids[] = $userInfo['storeid'];
                    //获取当前门店所属办事处
                    $getDepartMent=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$userInfo['storeid'])->field('d.id_department,d.st_department')->find();
                    $bscName=$getDepartMent['st_department'].'办事处';
                    //获取当前办事处下的门店
                    $bscBids=Db::table('sys_departbeauty_relation')->where('id_department',$getDepartMent['id_department'])->column('id_beauty');
                }
            }
            //店内美容师积分排行
            if($type==1){
                    if($showBids){
                        $map['o.storeid']=array('in',$showBids);
                    }
                    $map['o.pay_status']=array('eq',1);
                    $map['o.channel'] = array('eq', 'missshop');
                    $Nowpage = input('param.page') ? input('param.page') : 1;
                    $limits = 20;// 显示条数
                    $count = Db::name('activity_order')->alias('o')->field('uid')->where($map)->count('distinct fid');
                    //$count =$limits;
                    $allpage = intval(ceil($count / $limits));
                    if ($Nowpage >= $allpage) {
                        $info['next_page_flag']=0;//是否有下一页
                    }else{
                        $info['next_page_flag']=1;
                    }
                    $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.fid=m.id','left')->join(['ims_bwk_branch' => 'b'],'m.storeid=b.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('o.storeid,b.title,b.sign,m.realname,m.mobile,u.nickname,u.avatar,count(o.id) count,sum(o.pay_price) total_price,sum(o.num) total_goods,o.fid')->where($map)->page($Nowpage, $limits)->group('o.fid')->order('total_goods desc')->select();
                    if($list){
                        foreach ($list as $k=>$v){
                            $m['o.fid']=array('eq',$v['fid']);
                            $m['o.pay_status']=array('eq',1);
                            $m['o.channel']=array('eq','missshop');
                            $m['m.activity_flag']=array('eq','8806');
                            $list[$k]['cus_total']=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id')->where($m)->count();
                        }
                    }

                    $info['list']=$list;
                    if(count($list) && is_array($list)){
                        $code = 1;
                        $data = $info;
                        $msg = '获取成功';
                    }else{
                        $code = 0;
                        $data = '';
                        $msg = '暂无数据';
                    }
            }elseif($type==2){
                if($bscBids){
                    $map['o.storeid']=array('in',$bscBids);
                }
                $map['o.pay_status']=array('eq',1);
                $map['o.channel'] = array('eq', 'missshop');
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 100;// 显示条数
                //$count = Db::name('activity_order')->alias('o')->field('uid')->where($map)->count('distinct storeid');
                $count = $limits;
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage >= $allpage) {
                    $info['next_page_flag']=0;//是否有下一页
                }else{
                    $info['next_page_flag']=1;
                }
                $info['bsc_name']=$bscName;

                if(Cache::get('rankBsc'.$uId)){
                    $list = Cache::get('rankBsc'.$uId);
                }else{
                    $list = Db::name('activity_order')->alias('o')->join(['ims_bwk_branch' => 'b'],'o.storeid=b.id','left')->field('o.storeid,b.title,b.sign,count(o.id) count,sum(o.pay_price) total_price,sum(o.num) total_goods')->where($map)->page($Nowpage, $limits)->group('o.storeid')->order('total_goods desc')->select();
                    if($list){
                        foreach ($list as $k=>$v){
                            $m['o.storeid']=array('eq',$v['storeid']);
                            $m['o.pay_status']=array('eq',1);
                            $m['o.channel']=array('eq','missshop');
                            $m['m.activity_flag']=array('eq','8806');
                            $list[$k]['cus_total']=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id')->where($m)->count();
                        }
                    }
                    Cache::set('rankBsc'.$uId,$list,0);
                }

                $info['list']=$list;
                if(count($list) && is_array($list)){
                    $code = 1;
                    $data = $info;
                    $msg = '获取成功';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '暂无数据';
                }
            }elseif($type==3){
                $map['o.pay_status']=array('eq',1);
                $map['o.channel'] = array('eq', 'missshop');
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 100;// 显示条数
                //$count = Db::name('activity_order')->alias('o')->field('uid')->where($map)->count('distinct storeid');
                $count = $limits;
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage >= $allpage) {
                    $info['next_page_flag']=0;//是否有下一页
                }else{
                    $info['next_page_flag']=1;
                }
                if(Cache::get('rankAll')){
                    $list = Cache::get('rankAll');
                }else{
                    $list = Db::name('activity_order')->alias('o')->join(['ims_bwk_branch' => 'b'],'o.storeid=b.id','left')->field('o.storeid,b.title,b.sign,count(o.id) count,sum(o.pay_price) total_price,sum(o.num) total_goods')->where($map)->page($Nowpage, $limits)->group('o.storeid')->order('total_goods desc')->select();
                    if($list){
                        foreach ($list as $k=>$v){
                            $m['o.storeid']=array('eq',$v['storeid']);
                            $m['o.pay_status']=array('eq',1);
                            $m['o.channel']=array('eq','missshop');
                            $m['m.activity_flag']=array('eq','8806');
                            $list[$k]['cus_total']=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id')->where($m)->count();

                        }
                    }
                    Cache::set('rankAll',$list,0);
                }
                $info['list']=$list;
                if(count($list) && is_array($list)){
                    $code = 1;
                    $data = $info;
                    $msg = '获取成功';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '暂无数据';
                }
            }
        }else{
            $code=0;
            $data='';
            $msg='参数未填写完整';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //门店排行
    public function branchRanking(){
        $storeid=input('param.storeid');//店id
        if($storeid!=''){
            $map=[];
            $m=[];
            //获取排行榜展示截至日期
            $endTime=Db::name('activity_config')->where('id',1)->value('show_time');
            if($endTime){
                $map['o.pay_time'] = array('elt', $endTime);
                $m['o.pay_time'] = array('elt', $endTime);
            }
            $map['o.scene'] = array('eq', 0);
            $m['o.scene'] = array('eq', 0);
            //店内美容师积分排行
            $map['o.storeid']=array('eq',$storeid);
            $map['o.pay_status']=array('eq',1);
            $map['o.channel'] = array('eq', 'missshop');
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 20;// 显示条数
            //$count = Db::name('activity_order')->alias('o')->field('uid')->where($map)->count('distinct fid');
            $count = $limits;
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'],'o.fid=m.id','left')->join(['ims_bwk_branch' => 'b'],'m.storeid=b.id','left')->join('wx_user u','m.mobile=u.mobile','left')->field('o.storeid,b.title,b.sign,m.realname,m.mobile,u.nickname,u.avatar,count(o.id) count,sum(o.pay_price) total_price,sum(o.num) total_goods,o.fid')->where($map)->page($Nowpage, $limits)->group('o.fid')->order('total_goods desc')->select();
            if($list){
                foreach ($list as $k=>$v){
                    $m['o.fid']=array('eq',$v['fid']);
                    $m['o.pay_status']=array('eq',1);
                    $m['o.channel']=array('eq','missshop');
                    $m['m.activity_flag']=array('eq','8806');
                    $list[$k]['cus_total']=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id')->where($m)->count();
                }
            }
            $info['list']=$list;
            if(count($list) && is_array($list)){
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无数据';
            }
        }else{
            $code=0;
            $data='';
            $msg='参数未填写完整';
        }
        return parent::returnMsg($code,$data,$msg);
    }


	//查询全国排行修改数据库
    public function get_national()
    {
        set_time_limit(0);
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $begin = $activity_config['begin_time'];//活动开始时间
        $end = 1572364800;//$activity_config['end_time'];//活动结束时间
        //查询门店
        $store_list = Db::table('ims_bwk_branch')->select();
        foreach ($store_list as $k=>$value){
            $storeid = $value['id'];//门店id
            $list = $this->cccc($storeid,$begin,$end);
            if(!empty($list)){
                foreach ($list as $kk=>$val){
                    $storeid = $val['storeid'];
                    $map = [];
                    $map['storeid'] = $storeid;

                    $data = array(
                        'storeid' => $storeid,
                        'title' => $val['title'],
                        'sign' => $val['sign'],
                        'transfer_price' => $val['transfer_price'],
                        'price' => $val['pay_price'],
                        'transfer' => $val['transfer'],
                        'toker' => $val['toker'],
                        'share_num' => $val['share_num'],
                        'grade' => $val['grade'],
                        'create_time' => time(),
                    );
                    Db::name('activity_order_rank')->insert($data,false,true);
                }
            }else{
				$data = array(
                        'storeid' => $value['id'],
                        'title' => $value['title'],
                        'sign' => $value['sign'],
                        'transfer_price' => 0,
                        'price' => 0,
                        'transfer' => 0,
                        'toker' => 0,
                        'share_num' => 0,
                        'grade' => 0,
                        'create_time' => time(),
                    );
                    Db::name('activity_order_rank')->insert($data,false,true);
            }
            unset($data);
        }

        return true;
    }
    function cccc($storeid,$begin,$end){
        //查询订单数据并排序 店铺及用户排序
        $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
        $field_where .= ",( CASE WHEN o.pay_price THEN o.pay_price ELSE 0 END ) pay_price";
        $field_where .= ",(CASE WHEN o.pid = 47 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a47";
        $field_where .= ",(CASE WHEN o.pid = 79 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a79";
        //$field_where .= ",( CASE WHEN o.scene = 1 THEN 1 ELSE 0 END ) transfer ";
        $map['o.channel'] = 'missshop';
        $map['o.pay_status'] = 1;
        $map['o.storeid'] = $storeid;
        $map['o.pay_time'] = ['between',[$begin,$end]];
        $order_where = Db::name('activity_order')
            ->alias('o')
            ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
            ->field($field_where)
            ->where($map)
            ->order('o.storeid asc,o.uid asc')
            ->buildSql();
        //以用户分组
        $field_uid = "a.storeid,a.uid,a.fid,GROUP_CONCAT(a.pid) pid,a.title,a.sign";
        $field_uid .= ",CAST(SUM( BINARY ( ( CASE WHEN a.scene = 1 THEN a.pay_price ELSE 0 END ))) as decimal(10,2)) transfer_price";
        $field_uid .= ",CAST(SUM( BINARY ( a.pay_price)) as decimal(10,2)) pay_price";
        $field_uid .= ",SUM(a.a47) a47,SUM(a.a79) a79";
        $field_uid .= ",greatest(SUM(a.a47),SUM(a.a79)) toker";
        $field_uid .= ",( CASE WHEN COUNT( a.uid ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
        $field_uid .= ",sum( CASE WHEN a.scene = 1 THEN 1 ELSE 0 END ) transfer ";

        $order_uid = Db::table($order_where . 'a')
            ->field($field_uid)
            ->group('a.uid')
            ->order('a.storeid asc,a.uid asc')
            ->buildSql();

        //以门店分组
        $filed_store = "b.storeid,b.title,b.sign";
        $filed_store .= ",SUM(BINARY(b.pay_price)) pay_price";
        $filed_store .= ",SUM(BINARY(b.transfer_price)) transfer_price";
        $filed_store .= ",SUM(b.first_transfer) first_transfer";
        $filed_store .= ",SUM( b.toker) toker";
        $filed_store .= ",SUM( b.transfer) transfer";
        $order_store = Db::table($order_uid . 'b')
            ->field($filed_store)
            ->group('b.storeid')
            ->order('b.storeid asc')
            ->buildSql();
        //获取用户分享数据 10.30之前
        $share_sql = Db::name('activity_share')
                ->field(' storeid,uid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->group('uid')
                ->buildSql();
        $share = Db::table($share_sql . 'sss')
            ->field(' sss.storeid,count(sss.uid) share_num')
            ->group('sss.storeid')
            ->buildSql();
        //计算分数
        $field = "c.storeid,c.title,c.sign,c.pay_price,c.transfer_price,c.first_transfer,c.toker,c.transfer";
        $field .= ",IFNULL( d.share_num, 0 ) share_num";
        $field .= ",( ( c.transfer - c.first_transfer ) * 3 ) transfer_grade";
        $field .= ",( c.first_transfer * 5 ) first_transfer_grade";
        $field .= ",( c.toker * 2 ) toker_grade";
        $field .= ",( floor( c.pay_price / 200 ) ) seller_grade";
        $field .= ",(c.toker * 2 + ( c.transfer - c.first_transfer ) * 3 + c.first_transfer * 5 + floor( c.pay_price / 200 ) + IFNULL( d.share_num, 0 )) grade";
        $field .= ",(CASE WHEN (c.transfer_price >= 100000 AND c.toker >= 200 AND c.transfer >= 100 ) THEN 1 ELSE 0 END ) flag ";//标识
        $list = Db::table( $order_store . 'c')
            ->field($field)
            ->join([$share => 'd'],'c.storeid = d.storeid','left')
            ->select();
        return $list;
    }



    //查询门店下美容师排行并修改数据 10.30之前的数据
    public function get_beautician() {
        set_time_limit(0);
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $begin = $activity_config['begin_time'];//活动开始时间
        $end = 1572364800;//$activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间

        //查询门店
        $store_list = Db::table('ims_bwk_branch')->select();
        foreach ($store_list as $key=>$value){
            $storeid = $value['id'];//门店
            //查询门店下的美容师
            $member_list = Db::table('ims_bj_shopn_member')
            	->alias('m')
                ->field('m.id,m.staffid,m.realname,m.mobile,u.nickname,u.avatar')
                ->join(['pt_wx_user' => 'u'],'m.mobile=u.mobile','left')
                ->where('m.storeid','=',$storeid)
                ->where('m.isadmin','=',0)
                ->where('m.id=m.staffid')
                ->where('m.code <> \'\'')
                ->select();
            if(!empty($member_list)){
                foreach ($member_list as $kk=>$val){
                    $fid = $val['id'];//美容师id
                    $list = $this->bbbb($storeid,$fid,$begin,$end);
                    if(empty($list)){
                        $data = array(
                            'storeid' => $storeid,
                            'title' => $value['title'],
                            'sign' => $value['sign'],
                                'fid' =>  $val['id'] ,
                            'mobile' => $val['mobile']?:'',
                            'nickname' => $val['nickname']?:'',
                            'realname' => $val['realname']?:'',
                            'avatar' => $val['avatar']?:'',
                            'transfer_price' => 0,
                            'price' => 0,
                            'transfer' => 0,
                            'toker' => 0,
                            'share_num' => 0,
                            'grade' => 0,
                            'create_time' => time(),
                        );
                        Db::name('activity_order_fid_rank')->insert($data,false,true);
                    }else{
                        foreach ($list as $kkk => $v) {
                            $data = [
                                'storeid' => $storeid,
                                'title' => $v['title'],
                                'sign' => $v['sign'],
                                'fid' => $v['fid'] ?: $val['id'] ,
                                'realname' => ($v['realname']?:$val['realname']) ?: '',
                                'mobile' => ($v['mobile']?:$val['mobile']) ?: '',
                                'nickname' => ($v['nickname']?:$val['nickname']) ?: '',
                                'avatar' => ($v['avatar']?:$val['avatar']) ?: '',
                                'transfer_price' => $v['transfer_price'],
                                'price' => $v['pay_price'],
                                'transfer' => $v['transfer']?:0,
                                'toker' => $v['toker']?:0,
                                'share_num' => $v['share_num']?:0,
                                'grade' => $v['grade']?:0,
                                'create_time' => time(),
                            ];
                            Db::name('activity_order_fid_rank')->insert($data,false,true);
                        }
                    }
                }
            }
        }
        return true;
    }
    function bbbb($storeid,$fid,$begin,$end){
        //查询订单数据并排序 店铺及用户排序
        $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
        $field_where .= ",( CASE WHEN o.pay_price THEN o.pay_price ELSE 0 END ) pay_price";
        $field_where .= ",(CASE WHEN o.pid = 47 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a47";
        $field_where .= ",(CASE WHEN o.pid = 79 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a79";
        $field_where .= ",m.realname,m.mobile,user.nickname,user.avatar";
        $map['o.channel'] = 'missshop';
        $map['o.pay_status'] = 1;
        $map['o.storeid'] = $storeid;
        $map['o.fid'] = $fid;
        $map['o.pay_time'] = ['between',[$begin,$end]];
        $order_where = Db::name('activity_order')
            ->alias('o')
            ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
            ->join(['ims_bj_shopn_member' => 'm'],'o.fid=m.staffid and m.code != \'\'','left')
            ->join(['pt_wx_user' => 'user'],'m.mobile=user.mobile  ','left')
            ->field($field_where)
            ->where($map)
            ->order('o.storeid asc,o.uid asc')
            ->buildSql();
        //以用户分组
        $field_uid = "a.storeid,a.uid,a.fid,GROUP_CONCAT(a.pid) pid,a.title,a.sign";
        $field_uid .= ",CAST(SUM( BINARY ( a.pay_price)) as decimal(10,2)) pay_price";
        $field_uid .= ",CAST(SUM( BINARY ( ( CASE WHEN a.scene = 1 THEN a.pay_price ELSE 0 END ))) as decimal(10,2)) transfer_price";
        $field_uid .= ",SUM(a.a47) a47,SUM(a.a79) a79";
        $field_uid .= ",greatest(SUM(a.a47),SUM(a.a79)) toker";
        $field_uid .= ",( CASE WHEN COUNT( a.uid ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
        $field_uid .= ",sum( CASE WHEN a.scene = 1 THEN 1 ELSE 0 END ) transfer";
        $field_uid .= ",a.realname,a.mobile,a.nickname,a.avatar";
        $order_uid = Db::table($order_where . 'a')
            ->field($field_uid)
            ->group('a.uid')
            ->order('a.storeid asc,a.uid asc')
            ->buildSql();

        //以美容师分组
        $filed_fid = "f.storeid,f.title,f.sign,f.fid,f.realname,f.mobile,f.nickname,f.avatar";
        $filed_fid .= ",GROUP_CONCAT(f.uid) uid";
        $filed_fid .= ",SUM( BINARY ( f.pay_price ) ) pay_price";
        $filed_fid .= ",SUM( BINARY ( f.transfer_price ) ) transfer_price";
        $filed_fid .= ",SUM( f.first_transfer) first_transfer";
        $filed_fid .= ",SUM( f.transfer) transfer";
        $filed_fid .= ",SUM( f.toker ) toker";

        $order_fid = Db::table($order_uid . 'f')
            ->field($filed_fid)
            ->group('f.fid')
            ->buildSql();

        //获取用户分享数据
        $share_sql = Db::name('activity_share')
            ->field(' storeid,uid,fid')
            ->where('item','=','miss_tuoke')
            ->where('storeid','<>','')
            ->group('uid')
            ->buildSql();
        $share = Db::table($share_sql . 'sss')
            ->field(' sss.storeid,sss.fid,count(sss.fid) share_num')
            ->group('sss.fid')
            ->buildSql();
        //计算分数
        $field = "c.storeid,c.title,c.sign,c.fid,c.pay_price,c.transfer_price,c.realname,c.mobile,c.nickname,c.avatar";
        $field .= ",IFNULL( d.share_num, 0 ) share_num,c.toker,c.transfer";
        $field .= ",( ( c.transfer - c.first_transfer ) * 3 ) transfer_grade";
        $field .= ",( c.first_transfer * 5 ) first_transfer_grade";
        $field .= ",( c.toker * 2 ) toker_grade";
        $field .= ",( floor( c.pay_price / 200 ) ) seller_grade";
        $field .= ",(c.toker * 2 + ( c.transfer - c.first_transfer ) * 3 + c.first_transfer * 5 + floor( c.pay_price / 200 ) + IFNULL( d.share_num, 0 )) grade";

        $list = Db::table( $order_fid . 'c')
            ->field($field)
            ->join([$share => 'd'],'c.fid = d.fid','left')
            ->order('grade desc')
            ->select();
        return $list;
    }






    /**
     * Commit: 全国排名或某一个门店排名
     * Function: national_store_rank
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-11 14:20:34
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function national_rank1() {
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = $activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('national_rank_'.$pageindex)){
            $list = Cache::get('national_rank_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
            $field_where .= ",( CASE WHEN o.pay_price THEN o.pay_price ELSE 0 END ) pay_price";
            $field_where .= ",(CASE WHEN o.pid = 47 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a47";
            $field_where .= ",(CASE WHEN o.pid = 79 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a79";
            //$field_where .= ",( CASE WHEN o.scene = 1 THEN 1 ELSE 0 END ) transfer ";
            $map['o.channel'] = 'missshop';
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->order('o.storeid asc,o.uid asc')
                ->buildSql();
            //以用户分组
            $field_uid = "a.storeid,a.uid,a.fid,GROUP_CONCAT(a.pid) pid,a.title,a.sign";
            $field_uid .= ",CAST(SUM( BINARY ( ( CASE WHEN a.scene = 1 THEN a.pay_price ELSE 0 END ))) as decimal(10,2)) transfer_price";
            $field_uid .= ",CAST(SUM( BINARY ( a.pay_price)) as decimal(10,2)) pay_price";
            $field_uid .= ",SUM(a.a47) a47,SUM(a.a79) a79";
            $field_uid .= ",greatest(SUM(a.a47),SUM(a.a79)) toker";
            $field_uid .= ",( CASE WHEN COUNT( a.uid ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
            $field_uid .= ",sum( CASE WHEN a.scene = 1 THEN 1 ELSE 0 END ) transfer ";




            //$field_uid .= ",( CASE WHEN count( a.transfer ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
            //$field_uid .= ",sum(  a.transfer ) transfer";

            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();

            //以门店分组
            $filed_store = "b.storeid,b.title,b.sign";
            $filed_store .= ",SUM(BINARY(b.pay_price)) pay_price";
            $filed_store .= ",SUM(BINARY(b.transfer_price)) transfer_price";
            $filed_store .= ",SUM(b.first_transfer) first_transfer";
            $filed_store .= ",SUM( b.toker) toker";
            $filed_store .= ",SUM( b.transfer) transfer";
            $order_store = Db::table($order_uid . 'b')
                ->field($filed_store)
                ->group('b.storeid')
                ->order('b.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field('( CASE WHEN count( uid ) THEN 1 ELSE 0 END ) share_num, storeid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->group('storeid')
                ->buildSql();
            //计算分数
            $field = "c.storeid,c.title,c.sign,c.pay_price,c.transfer_price,c.first_transfer,c.toker,c.transfer";
            $field .= ",IFNULL( d.share_num, 0 ) share_num";
            $field .= ",( ( c.transfer - c.first_transfer ) * 3 ) transfer_grade";
            $field .= ",( c.first_transfer * 5 ) first_transfer_grade";
            $field .= ",( c.toker * 2 ) toker_grade";
            $field .= ",( floor( c.pay_price / 200 ) ) seller_grade";
            $field .= ",(c.toker * 2 + ( c.transfer - c.first_transfer ) * 3 + c.first_transfer * 5 + floor( c.pay_price / 200 ) + IFNULL( d.share_num, 0 )) grade";
            $field .= ",(CASE WHEN (c.transfer_price >= 100000 AND c.toker >= 200 AND c.transfer >= 100 ) THEN 1 ELSE 0 END ) flag ";//标识
            $list = Db::table( $order_store . 'c')
                ->field($field)
                ->join([$share => 'd'],'c.storeid = d.storeid','left')
                ->order('grade desc')
                ->page($pageindex,$pagesize)
                ->select();
            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('national_rank_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }
	public function national_rank() {
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = 1572364800;//$activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        if(time() >= $stop){
        	$end = $stop;
        }
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('national_rank_'.$pageindex)){
            $list = Cache::get('national_rank_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid, store.title,store.sign ";
            $map['o.channel'] = 'missshop' ;
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->where('o.uid<>o.fid')
                ->buildSql();
            //查询门店
            $stores = Db::name('activity_order')
                ->where('channel','=','missshop')
                ->where('pay_status','=',1)
                ->where('pay_time','between',[1569859200,$end])
                ->group('storeid')
                ->column('storeid');
            //以用户分组
            #获取九月份拓客记录信息
            $map9['channel'] = 'missshop';
            $map9['pay_status'] = 1;
            $map9['scene'] = 0;
            $map9['pay_time'] = ['<',$begin];
            $toker9 = Db::name('activity_order')
                ->field('(case when scene=0 then 1 else 0 end) toker9')
                ->where($map9)
                ->where('uid<>fid')
                ->where('uid = a.uid')
                ->group('uid')
                ->buildSql();

            $field_uid = "a.storeid,a.uid,a.fid,a.title,a.sign";
            $field_uid .= ",GROUP_CONCAT(DISTINCT a.scene ORDER BY a.scene desc) tt";#去重后标识
            $field_uid .= ",IFNULL({$toker9},0) toker9";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();
            //查询转客拓客用户数量
            $field_tt = "bb.storeid,bb.uid,bb.fid,bb.title,bb.sign,bb.tt";
            $field_tt .= ",( case when bb.tt = '1,0' then 1 else 0 end) transfer_part";#拓转客
            $field_tt .= ",( case when bb.tt = '0' then 1 else 0 end) toker_part";#拓客一部分
            $field_tt .= ",( case when bb.tt = '1' then 1 else 0 end) first_transfer";#首次转客
            $field_tt .= ",( case when (bb.tt = '0' or bb.tt = '1,0') then 1 else 0 end) toker_transfer";
            $field_tt .= ",bb.toker9";
            $order_tt = Db::table($order_uid. ' bb')
                ->field($field_tt)
                ->buildSql();

            //拓客转客真实数量
            $cc = "cc.storeid,cc.uid,cc.fid,cc.title,cc.sign,cc.tt,cc.toker9";
            //首次转客
            $cc .= ",( case when cc.first_transfer>0 ";
            $cc .= " then cc.first_transfer- cc.toker9 ";
            $cc .= "  else cc.first_transfer end ) first_transfer";
            //不包含首次转客
            $cc .= ",( case when cc.first_transfer>0";
            $cc .= " then ( cc.transfer_part+ cc.toker9 )";
            $cc .= "  else cc.transfer_part end ) transfer_part";
            //真实拓客
            $cc .= ",( case when ( cc.transfer_part + cc.toker_part) > 0 ";
            $cc .= "   then ( cc.transfer_part + cc.toker_part -cc.toker9 ) ";
            $cc .= "   else ( cc.transfer_part + cc.toker_part) end ) toker";
            $order_cc = Db::table($order_tt. 'cc')
                ->field($cc)
                ->buildSql();

            $dd = "dd.storeid,dd.uid,dd.fid,dd.title,dd.sign,dd.toker9";
            $dd .= ",dd.toker,dd.first_transfer,dd.transfer_part";
            $dd .= ",(dd.transfer_part + dd.first_transfer) transfer";

            $order_dd = Db::table($order_cc . 'dd')
                ->field($dd)
                ->buildSql();
            //以门店分组
            $filed_store = "d.storeid,d.title,d.sign";
            $filed_store .= ",SUM( d.transfer ) transfer";#转客总数量
            $filed_store .= ",SUM( d.first_transfer ) first_transfer";#首次转客
            $filed_store .= ",SUM( d.transfer_part ) transfer_part";#不包含首次转客
            $filed_store .= ",SUM( d.toker ) toker";#真实拓客

            $pmap['channel'] = 'missshop';
            $pmap['pay_status'] = 1;
            $pmap['pay_time'] = ['BETWEEN', [$begin,$end]];
            //门店总金额
            $price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$price}), 0) price";#门店全部金额
            //门店转客总金额
            $transfer_price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('scene','=',1)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$transfer_price}), 0) transfer_price";#门店转客金额
            $order_store = Db::table($order_dd . 'd')
                ->field($filed_store)
                ->group('d.storeid')
                ->order('d.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field(' storeid,uid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->where('insert_time','in',[$begin,$end])
                ->group('uid')
                ->buildSql();
            $share_sql = Db::table($share . 'sss')
                ->field(' sss.storeid,count(sss.uid) share_num')
                ->group('sss.storeid')
                ->buildSql();
            //计算分数
            $field = "abc.storeid,abc.title,abc.sign";
            $field .= ",abc.transfer_price,abc.price";
            $field .= ",abc.transfer";#转客总数
            $field .= ",abc.toker ";#拓客总数
            $field .= ",IFNULL( share.share_num, 0 ) share_num ";#分享数量
            $field .= ",(abc.toker * 2 + abc.transfer_part * 3 + abc.first_transfer * 5 + floor( abc.price / 200 ) + IFNULL( share.share_num, 0 )) grade ";#总分数
            $new_sql = Db::table( $order_store . 'abc' )
                ->field($field)
                ->join([$share_sql => 'share'],'abc.storeid = share.storeid','left')
                ->order('grade desc')
                ->buildSql();

            //查询10.30之前的数据
            $old = "storeid,sign,title,price,transfer_price,transfer,toker,share_num,grade";
            $old_sql = Db::name('activity_order_rank')
                ->field($old)
                ->where('storeid','in',$stores)
                ->buildSql();
            //合并
            $merge_field = "l.storeid,l.title,l.sign";
            $merge_field .= ",(IFNULL(l.price,0) + IFNULL(n.price,0)) price";
            $merge_field .= ",(IFNULL(l.transfer_price,0) + IFNULL(n.transfer_price,0)) transfer_price";
            $merge_field .= ",(IFNULL(l.transfer,0) + IFNULL(n.transfer,0)) transfer";
            $merge_field .= ",(IFNULL(l.toker,0) + IFNULL(n.toker,0)) toker";
            $merge_field .= ",(IFNULL(l.share_num,0) + IFNULL(n.share_num,0)) share_num";
            $merge_field .= ",(IFNULL(l.grade,0) + IFNULL(n.grade,0)) grade";

            $sql = Db::table($old_sql . 'l')
                ->field($merge_field)
                ->join( [$new_sql => 'n'] ,'l.storeid=n.storeid','left')
                ->buildSql();
            $xxx = "xxx.storeid,xxx.title,xxx.sign,xxx.transfer,xxx.toker,xxx.share_num,xxx.grade";
            $xxx .= ",xxx.price,xxx.transfer_price";	
            $xxx .= ",(CASE WHEN (xxx.transfer_price >= 100000 AND xxx.toker >= 200 AND xxx.transfer >= 100) THEN 1 ELSE 0 END) flag";
            $list = Db::table($sql . 'xxx')
                ->field($xxx)
                ->order('xxx.grade desc')
                ->page($pageindex,$pagesize)
                ->select();
            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('national_rank_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }
    public function national_rank2() {
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = 1572364800;//$activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('national_rank_'.$pageindex)){
            $list = Cache::get('national_rank_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid, store.title,store.sign ";
            $map['o.channel'] = 'missshop' ;
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->where('o.uid<>o.fid')
                ->buildSql();
            //以用户分组
            #获取九月份拓客记录信息
            $map9['channel'] = 'missshop';
            $map9['pay_status'] = 1;
            $map9['scene'] = 0;
            $map9['pay_time'] = ['<',$begin];
            $toker9 = Db::name('activity_order')
                ->field('(case when scene=0 then 1 else 0 end) toker9')
                ->where($map9)
                ->where('uid<>fid')
                ->where('uid = a.uid')
                ->group('uid')
                ->buildSql();

            $field_uid = "a.storeid,a.uid,a.fid,a.title,a.sign";
            $field_uid .= ",GROUP_CONCAT(DISTINCT a.scene ORDER BY a.scene desc) tt";#去重后标识
            $field_uid .= ",IFNULL({$toker9},0) toker9";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();
            //查询转客拓客用户数量
            $field_tt = "bb.storeid,bb.uid,bb.fid,bb.title,bb.sign,bb.tt";
            $field_tt .= ",( case when bb.tt = '1,0' then 1 else 0 end) transfer_part";#拓转客
            $field_tt .= ",( case when bb.tt = '0' then 1 else 0 end) toker_part";#拓客一部分
            $field_tt .= ",( case when bb.tt = '1' then 1 else 0 end) first_transfer";#首次转客
            $field_tt .= ",( case when (bb.tt = '0' or bb.tt = '1,0') then 1 else 0 end) toker_transfer";
            $field_tt .= ",bb.toker9";
            $order_tt = Db::table($order_uid. ' bb')
                ->field($field_tt)
                ->buildSql();

            //拓客转客真实数量
            $cc = "cc.storeid,cc.uid,cc.fid,cc.title,cc.sign,cc.tt,cc.toker9";
            //首次转客
            $cc .= ",( case when cc.first_transfer>0 ";
            $cc .= " then cc.first_transfer- cc.toker9 ";
            $cc .= "  else cc.first_transfer end ) first_transfer";
            //不包含首次转客
            $cc .= ",( case when cc.first_transfer>0";
            $cc .= " then ( cc.transfer_part+ cc.toker9 )";
            $cc .= "  else cc.transfer_part end ) transfer_part";
            //真实拓客
            $cc .= ",( case when ( cc.transfer_part + cc.toker_part) > 0 ";
            $cc .= "   then ( cc.transfer_part + cc.toker_part -cc.toker9 ) ";
            $cc .= "   else ( cc.transfer_part + cc.toker_part) end ) toker";
            $order_cc = Db::table($order_tt. 'cc')
                ->field($cc)
                ->buildSql();

            $dd = "dd.storeid,dd.uid,dd.fid,dd.title,dd.sign,dd.toker9";
            $dd .= ",dd.toker,dd.first_transfer,dd.transfer_part";
            $dd .= ",(dd.transfer_part + dd.first_transfer) transfer";

            $order_dd = Db::table($order_cc . 'dd')
                ->field($dd)
                ->buildSql();
            //以门店分组
            $filed_store = "d.storeid,d.title,d.sign";
            $filed_store .= ",SUM( d.transfer ) transfer";#转客总数量
            $filed_store .= ",SUM( d.first_transfer ) first_transfer";#首次转客
            $filed_store .= ",SUM( d.transfer_part ) transfer_part";#不包含首次转客
            $filed_store .= ",SUM( d.toker ) toker";#真实拓客

            $pmap['channel'] = 'missshop';
            $pmap['pay_status'] = 1;
            $pmap['pay_time'] = ['BETWEEN', [$begin,$end]];
            //门店总金额
            $price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$price}), 0) price";#门店全部金额
            //门店转客总金额
            $transfer_price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('scene','=',1)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$transfer_price}), 0) transfer_price";#门店转客金额
            $order_store = Db::table($order_dd . 'd')
                ->field($filed_store)
                ->group('d.storeid')
                ->order('d.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field(' storeid,uid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->group('uid')
                ->buildSql();
            $share_sql = Db::table($share . 'sss')
                ->field(' sss.storeid,count(sss.uid) share_num')
                ->group('sss.storeid')
                ->buildSql();
            //计算分数
            $field = "abc.storeid,abc.title,abc.sign";
            $field .= ",abc.transfer_price,abc.price";
            $field .= ",abc.transfer";#转客总数
            #$field .= ",abc.first_transfer";#首次转客数
            #$field .= ",abc.transfer_part";
            $field .= ",abc.toker ";#拓客总数
            $field .= ",IFNULL( share.share_num, 0 ) share_num ";#分享数量
            $field .= ",(abc.toker * 2 + abc.transfer_part * 3 + abc.first_transfer * 5 + floor( abc.price / 200 ) + IFNULL( share.share_num, 0 )) grade ";#总分数
            //$field .= ",(CASE WHEN (abc.transfer_price >= 100000 AND abc.toker >= 200 AND abc.transfer >= 100 )
            // THEN 1 ELSE 0 END ) flag";//标识
            $new_sql = Db::table( $order_store . 'abc' )
                ->field($field)
                ->join([$share_sql => 'share'],'abc.storeid = share.storeid','left')
                ->order('grade desc')
                ->buildSql();

            //查询10.30之前的数据
            $old = "storeid,sign,title,price,transfer_price,transfer,toker,share_num,grade";
            $old_sql = Db::name('activity_order_rank')
                ->field($old)
                ->buildSql();
            //合并
            $merge_field = "l.storeid,l.title,l.sign";
            $merge_field .= ",(IFNULL(l.price,0) + IFNULL(n.price,0)) price";
            $merge_field .= ",(IFNULL(l.transfer_price,0) + IFNULL(n.transfer_price,0)) transfer_price";
            $merge_field .= ",(IFNULL(l.transfer,0) + IFNULL(n.transfer,0)) transfer";
            $merge_field .= ",(IFNULL(l.toker,0) + IFNULL(n.toker,0)) toker";
            $merge_field .= ",(IFNULL(l.share_num,0) + IFNULL(n.share_num,0)) share_num";
            $merge_field .= ",(IFNULL(l.grade,0) + IFNULL(n.grade,0)) grade";

            $sql = Db::table($old_sql . 'l')
                ->field($merge_field)
                ->join( [$new_sql => 'n'] ,'l.storeid=n.storeid','left')
                ->buildSql();
            $xxx = "xxx.storeid,xxx.title,xxx.sign,xxx.price,xxx.transfer_price,xxx.transfer,xxx.toker,xxx.share_num,xxx.grade";
            $xxx .= ",(CASE WHEN (xxx.transfer_price >= 100000 AND xxx.toker >= 200 AND xxx.transfer >= 100) THEN 1 ELSE 0 END) flag";
            $list = Db::table($sql . 'xxx')
                ->field($xxx)
                ->order('xxx.grade desc')
                ->page($pageindex,$pagesize)
                ->select();

            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('national_rank_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }



    /**
     * Commit: 门店下美容师排行
     * Function: beautician_rank
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-11 15:29:29
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function beautician_rank1()
    {
        $storeid = input('param.storeid');//店id
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($storeid) || empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = $activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('beautician_rank_'.$storeid.'_'.$pageindex)){
            $list = Cache::get('beautician_rank_'.$storeid.'_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
            $field_where .= ",( CASE WHEN o.pay_price THEN o.pay_price ELSE 0 END ) pay_price";
            $field_where .= ",(CASE WHEN o.pid = 47 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a47";
            $field_where .= ",(CASE WHEN o.pid = 79 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a79";
            $field_where .= ",m.realname,m.mobile,user.nickname,user.avatar";
            $map['o.channel'] = 'missshop';
            $map['o.pay_status'] = 1;
            $map['o.storeid'] = $storeid;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->join(['ims_bj_shopn_member' => 'm'],'o.fid=m.staffid and m.code != \'\'','left')
                ->join(['pt_wx_user' => 'user'],'m.mobile=user.mobile  ','left')
                ->field($field_where)
                ->where($map)
                ->order('o.storeid asc,o.uid asc')
                ->buildSql();
            //以用户分组
            $field_uid = "a.storeid,a.uid,a.fid,GROUP_CONCAT(a.pid) pid,a.title,a.sign";
            $field_uid .= ",CAST(SUM( BINARY ( a.pay_price)) as decimal(10,2)) pay_price";
            $field_uid .= ",CAST(SUM( BINARY ( ( CASE WHEN a.scene = 1 THEN a.pay_price ELSE 0 END ))) as decimal(10,2)) transfer_price";
            $field_uid .= ",SUM(a.a47) a47,SUM(a.a79) a79";
            $field_uid .= ",greatest(SUM(a.a47),SUM(a.a79)) toker";
            $field_uid .= ",( CASE WHEN COUNT( a.uid ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
            $field_uid .= ",sum( CASE WHEN a.scene = 1 THEN 1 ELSE 0 END ) transfer";
            $field_uid .= ",a.realname,a.mobile,a.nickname,a.avatar";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();

            //以美容师分组
            $filed_fid = "f.storeid,f.title,f.sign,f.fid,f.realname,f.mobile,f.nickname,f.avatar";
            $filed_fid .= ",GROUP_CONCAT(f.uid) uid";
            $filed_fid .= ",SUM( BINARY ( f.pay_price ) ) pay_price";
            $filed_fid .= ",SUM( BINARY ( f.transfer_price ) ) transfer_price";
            $filed_fid .= ",SUM( f.first_transfer) first_transfer";
            $filed_fid .= ",SUM( f.transfer) transfer";
            $filed_fid .= ",SUM( f.toker ) toker";

            $order_fid = Db::table($order_uid . 'f')
                ->field($filed_fid)
                ->group('f.fid')
                ->buildSql();

            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field('( CASE WHEN count( uid ) THEN 1 ELSE 0 END ) share_num, storeid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->group('storeid')
                ->buildSql();
            //计算分数
            $field = "c.storeid,c.title,c.sign,c.fid,c.pay_price,c.transfer_price,c.realname,c.mobile,c.nickname,c.avatar";
            $field .= ",IFNULL( d.share_num, 0 ) share_num";
            $field .= ",( ( c.transfer - c.first_transfer ) * 3 ) transfer_grade";
            $field .= ",( c.first_transfer * 5 ) first_transfer_grade";
            $field .= ",( c.toker * 2 ) toker_grade";
            $field .= ",( floor( c.pay_price / 200 ) ) seller_grade";
            $field .= ",(c.toker * 2 + ( c.transfer - c.first_transfer ) * 3 + c.first_transfer * 5 + floor( c.pay_price / 200 ) + IFNULL( d.share_num, 0 )) grade";

            $list = Db::table( $order_fid . 'c')
                ->field($field)
                ->join([$share => 'd'],'c.fid = d.fid','left')
                ->order('grade desc')
                ->page($pageindex,$pagesize)
                ->select();
            if(time() >= $stop){
                self::$missshop_2_expire = 0;
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('beautician_rank_'.$storeid.'_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }
    public function beautician_rank() {
        $storeid = input('param.storeid');//店id
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($storeid) || empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = 1572364800;//$activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        if(time() >= $stop){
        	$end = $stop;
        }
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('beautician_rank_'.$storeid.'_'.$pageindex)){
            $list = Cache::get('beautician_rank_'.$storeid.'_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid, store.title,store.sign ";
            $map['o.channel'] = 'missshop' ;
            $map['o.pay_status'] = 1;
            $map['o.storeid'] = $storeid;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->where('o.uid<>o.fid')
                ->buildSql();

        	//查询门店下的美容师
        	$fid_list = Db::name('activity_order')
	        	->where('channel','=','missshop')
	        	->where('pay_status','=',1)
	        	->where('storeid','=',$storeid)
	        	->where('pay_time','between',[1569859200,$end])
	        	->column('fid');
            //以用户分组
            #获取九月份拓客记录信息
            $map9['channel'] = 'missshop';
            $map9['pay_status'] = 1;
            $map9['scene'] = 0;
            $map9['pay_time'] = ['<',$begin];
            $toker9 = Db::name('activity_order')
                ->field('(case when scene=0 then 1 else 0 end) toker9')
                ->where($map9)
                ->where('uid<>fid')
                ->where('uid = a.uid')
                ->group('uid')
                ->buildSql();

            $field_uid = "a.storeid,a.uid,a.fid,a.title,a.sign";
            $field_uid .= ",GROUP_CONCAT(DISTINCT a.scene ORDER BY a.scene desc) tt";#去重后标识
            $field_uid .= ",IFNULL({$toker9},0) toker9";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->buildSql();
            //查询转客拓客用户数量
            $field_tt = "bb.storeid,bb.uid,bb.fid,bb.title,bb.sign,bb.tt";
            $field_tt .= ",( case when bb.tt = '1,0' then 1 else 0 end) transfer_part";#拓转客
            $field_tt .= ",( case when bb.tt = '0' then 1 else 0 end) toker_part";#拓客一部分
            $field_tt .= ",( case when bb.tt = '1' then 1 else 0 end) first_transfer";#首次转客
            $field_tt .= ",( case when (bb.tt = '0' or bb.tt = '1,0') then 1 else 0 end) toker_transfer";
            $field_tt .= ",bb.toker9";
            $order_tt = Db::table($order_uid. ' bb')
                ->field($field_tt)
                ->buildSql();

            //拓客转客真实数量
            $cc = "cc.storeid,cc.uid,cc.fid,cc.title,cc.sign,cc.tt,cc.toker9";
            //首次转客
            $cc .= ",( case when cc.first_transfer>0 ";
            $cc .= " then cc.first_transfer- cc.toker9 ";
            $cc .= "  else cc.first_transfer end ) first_transfer";
            //不包含首次转客
            $cc .= ",( case when cc.first_transfer>0";
            $cc .= " then ( cc.transfer_part+ cc.toker9 )";
            $cc .= "  else cc.transfer_part end ) transfer_part";
            //真实拓客
            $cc .= ",( case when ( cc.transfer_part + cc.toker_part) > 0 ";
            $cc .= "   then ( cc.transfer_part + cc.toker_part -cc.toker9 ) ";
            $cc .= "   else ( cc.transfer_part + cc.toker_part) end ) toker";
            $order_cc = Db::table($order_tt. 'cc')
                ->field($cc)
                ->buildSql();

            $dd = "dd.storeid,dd.uid,dd.fid,dd.title,dd.sign,dd.toker9";
            $dd .= ",dd.toker,dd.first_transfer,dd.transfer_part";
            $dd .= ",(dd.transfer_part + dd.first_transfer) transfer";

            $order_dd = Db::table($order_cc . 'dd')
                ->field($dd)
                ->buildSql();
            //以门店分组
            $filed_store = "d.storeid,d.title,d.sign,m.realname,m.mobile,user.nickname,user.avatar,d.fid";
            $filed_store .= ",SUM( d.transfer ) transfer ";#转客总数量
            $filed_store .= ",SUM( d.first_transfer ) first_transfer";#首次转客
            $filed_store .= ",SUM( d.transfer_part ) transfer_part";#不包含首次转客
            $filed_store .= ",SUM( d.toker ) toker";#真实拓客

            $pmap['channel'] = 'missshop';
            $pmap['pay_status'] = 1;
            $pmap['pay_time'] = ['BETWEEN', [$begin,$end]];
            //门店总金额
            $price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('fid = d.fid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$price}), 0) price";#门店全部金额
            //门店转客总金额
            $transfer_price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('scene','=',1)
                ->where('fid = d.fid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$transfer_price}), 0) transfer_price";#门店转客金额
            $order_store = Db::table($order_dd . 'd')
                ->field($filed_store)
                ->join(['ims_bj_shopn_member'=>'m'],'d.fid=m.staffid and m.code != \'\'','left')
                ->join(['pt_wx_user'=>'user'],'m.mobile=user.mobile','left')
                ->group('d.fid')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field(' storeid,uid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->where('insert_time','in',[$begin,$end])
                ->group('uid')
                ->buildSql();
            $share_sql = Db::table($share . 'sss')
                ->field(' sss.storeid,sss.fid,count(sss.fid) share_num')
                ->group('sss.fid')
                ->buildSql();
            //计算分数
            $field = "abc.storeid,abc.title,abc.sign";
            $field .= ",abc.fid,abc.realname,abc.mobile,abc.nickname,abc.avatar";
            $field .= ",abc.transfer_price,abc.price ";
            $field .= ",abc.transfer ";#转客总数
            $field .= ",abc.first_transfer ";#首次转客数
            $field .= ",abc.transfer_part ";
            $field .= ",abc.toker ";#拓客总数
            $field .= ",IFNULL( share.share_num, 0 ) share_num ";#分享数量
            $field .= ",(abc.toker * 2 + abc.transfer_part * 3 + abc.first_transfer * 5 + floor( abc.price / 200 ) + IFNULL( share.share_num, 0 )) grade ";#总分数

            //查询10.30之后的数据
            $new_sql = Db::table( $order_store . 'abc' )
                ->field($field)
                ->join([$share_sql => 'share'],'abc.fid = share.fid','left')
                ->order('grade desc')
                ->buildSql();

            //查询10.30之前的数据
            $old = "storeid,sign,title,price,transfer_price,transfer,toker,share_num,grade";
            $old .= ",fid,mobile,nickname,realname,avatar";
            $old_sql = Db::name('activity_order_fid_rank')
                ->field($old)
                ->where('storeid','=',$storeid)
                ->where('fid','in',$fid_list)
                ->buildSql();

            //合并
            $merge_field = "l.storeid,l.title,l.sign";
            $merge_field .= ",l.fid,l.mobile,l.nickname,l.realname,l.avatar";
            $merge_field .= ",(IFNULL(l.price,0) + IFNULL(n.price,0)) price";
            $merge_field .= ",(IFNULL(l.transfer_price,0) + IFNULL(n.transfer_price,0)) transfer_price";
            $merge_field .= ",(IFNULL(l.transfer,0) + IFNULL(n.transfer,0)) transfer";
            $merge_field .= ",(IFNULL(l.toker,0) + IFNULL(n.toker,0)) toker";
            $merge_field .= ",(IFNULL(l.share_num,0) + IFNULL(n.share_num,0)) share_num";
            $merge_field .= ",(IFNULL(l.grade,0) + IFNULL(n.grade,0)) grade ";

            $sql = Db::table($old_sql . 'l')
                ->field($merge_field)
                ->join( [$new_sql => 'n'] ,'l.fid=n.fid','left')
                ->buildSql();
            $xxx = "xxx.storeid,xxx.title,xxx.sign,xxx.fid,xxx.mobile,xxx.nickname,xxx.realname,xxx.avatar";
            $xxx .= ",xxx.price,xxx.transfer_price,xxx.transfer,xxx.toker,xxx.share_num,xxx.grade";
            $xxx .= ",(CASE WHEN (xxx.transfer_price >= 100000 AND xxx.toker >= 200 AND xxx.transfer >= 100) THEN 1 ELSE 0 END) flag";
            $list = Db::table($sql . 'xxx')
                ->field($xxx)
                ->order('xxx.grade desc')
                ->page($pageindex,$pagesize)
                ->select();
            if(time() >= $stop){
                self::$missshop_2_expire = 0;
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('beautician_rank_'.$storeid.'_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }



    /**
     * Commit: 办事处下美容店排行
     * Function: agency_rank
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-12 09:41:39
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agency_rank1(){
        $storeid = input('param.storeid');//店id
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($storeid) || empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        //获取门店所属办事处
        $agency = DB::table('sys_departbeauty_relation')
            ->where('id_beauty','=',$storeid)
            ->value('id_department');
        if(empty($agency)){
            $code = 0;
            $msg = '暂无此办事处';
            return parent::returnMsg($code,$data,$msg);
        }
        //查询办事处下的所有门店
        $store_list = DB::table('sys_departbeauty_relation')
            ->where('id_department','=',$agency)
            ->column('id_beauty');
        if(empty($store_list)){
            $store_list[] = $storeid;
        }
        $begin = $activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('agency_rank_'.$agency.'_'.$pageindex)){
            $list = Cache::get('agency_rank_'.$agency.'_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else{
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
            $field_where .= ",( CASE WHEN o.pay_price THEN o.pay_price ELSE 0 END ) pay_price";
            $field_where .= ",(CASE WHEN o.pid = 47 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a47";
            $field_where .= ",(CASE WHEN o.pid = 79 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a79";
            $map['o.channel'] = 'missshop';
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->order('o.storeid asc,o.uid asc')
                ->buildSql();
            //以用户分组
            $field_uid = "a.storeid,a.uid,a.fid,GROUP_CONCAT(a.pid) pid,a.title,a.sign";
            $field_uid .= ",CAST(SUM( BINARY ( ( CASE WHEN a.scene = 1 THEN a.pay_price ELSE 0 END ))) as decimal(10,2)) transfer_price";
            $field_uid .= ",CAST(SUM( BINARY ( a.pay_price )) as decimal(10,2)) pay_price";
            $field_uid .= ",SUM(a.a47) a47,SUM(a.a79) a79";
            $field_uid .= ",greatest(SUM(a.a47),SUM(a.a79)) toker";
            $field_uid .= ",( CASE WHEN COUNT( a.uid ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
            $field_uid .= ",sum( CASE WHEN a.scene = 1 THEN 1 ELSE 0 END ) transfer";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();

            //以门店分组
            $filed_store = "b.storeid,b.title,b.sign";
            $filed_store .= ",SUM(BINARY(b.pay_price)) pay_price";
            $filed_store .= ",SUM(BINARY(b.transfer_price)) transfer_price";
            $filed_store .= ",SUM(b.first_transfer) first_transfer";
            $filed_store .= ",SUM( b.toker) toker";
            $filed_store .= ",SUM( b.transfer) transfer";
            $order_store = Db::table($order_uid . 'b')
                ->field($filed_store)
                ->where('b.storeid','in',$store_list)
                ->group('b.storeid')
                ->order('b.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field('( CASE WHEN count( uid ) THEN 1 ELSE 0 END ) share_num, storeid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->group('storeid')
                ->buildSql();
            //计算分数
            $field = "c.storeid,c.title,c.sign,c.pay_price,c.transfer_price,c.first_transfer,c.toker,c.transfer";
            $field .= ",IFNULL( d.share_num, 0 ) share_num";
            $field .= ",( ( c.transfer - c.first_transfer ) * 3 ) transfer_grade";
            $field .= ",( c.first_transfer * 5 ) first_transfer_grade";
            $field .= ",( c.toker * 2 ) toker_grade";
            $field .= ",( floor( c.pay_price / 200 ) ) seller_grade";
            $field .= ",(c.toker * 2 + ( c.transfer - c.first_transfer ) * 3 + c.first_transfer * 5 + floor( c.pay_price / 200 ) + IFNULL( d.share_num, 0 )) grade";
            $field .= ",(CASE WHEN (c.transfer_price >= 100000 AND c.toker >= 200 AND c.transfer >= 100 ) THEN 1 ELSE 0 END ) flag ";//标识
            $list = Db::table( $order_store . 'c')
                ->field($field)
                ->join([$share => 'd'],'c.storeid = d.storeid','left')
                ->order('grade desc')
                ->page($pageindex,$pagesize)
                ->select();
            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('agency_rank_'.$agency.'_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }

    public function agency_rank(){
        $storeid = input('param.storeid');//店id
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($storeid) || empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        //获取门店所属办事处
        $agency = DB::table('sys_departbeauty_relation')
            ->where('id_beauty','=',$storeid)
            ->value('id_department');
        if(empty($agency)){
            $code = 0;
            $msg = '暂无此办事处';
            return parent::returnMsg($code,$data,$msg);
        }
        //查询办事处下的所有门店
        $store_list = DB::table('sys_departbeauty_relation')
            ->where('id_department','=',$agency)
            ->column('id_beauty');
        if(empty($store_list)){
            $store_list[] = $storeid;
        }
        $begin = 1572364800;//$activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        if(time() >= $stop){
        	$end = $stop;
        }
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('agency_rank_'.$agency.'_'.$pageindex)){
            $list = Cache::get('agency_rank_'.$agency.'_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else{
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
            $map['o.channel'] = 'missshop';
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $map['o.storeid'] = ['in',$store_list];//办事处下的所有门店
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->where('o.uid<>o.fid')
                ->buildSql();

            //查询办事处下的门店
            $stores = Db::name('activity_order')
                ->where('channel','=','missshop')
                ->where('pay_status','=',1)
                ->where('storeid','in',$store_list)
                ->where('pay_time','between',[1569859200,$end])
                ->group('storeid')
                ->column('storeid');
            //以用户分组
            #获取九月份拓客记录信息
            $map9['channel'] = 'missshop';
            $map9['pay_status'] = 1;
            $map9['scene'] = 0;
            $map9['pay_time'] = ['<',$begin];
            $toker9 = Db::name('activity_order')
                ->field('(case when scene=0 then 1 else 0 end) toker9')
                ->where($map9)
                ->where('uid<>fid')
                ->where('uid = a.uid')
                ->group('uid')
                ->buildSql();

            $field_uid = "a.storeid,a.uid,a.fid,a.title,a.sign";
            $field_uid .= ",GROUP_CONCAT(DISTINCT a.scene ORDER BY a.scene desc) tt";#去重后标识
            $field_uid .= ",IFNULL({$toker9},0) toker9";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();
            //查询转客拓客用户数量
            $field_tt = "bb.storeid,bb.uid,bb.fid,bb.title,bb.sign,bb.tt";
            $field_tt .= ",( case when bb.tt = '1,0' then 1 else 0 end) transfer_part";#拓转客
            $field_tt .= ",( case when bb.tt = '0' then 1 else 0 end) toker_part";#拓客一部分
            $field_tt .= ",( case when bb.tt = '1' then 1 else 0 end) first_transfer";#首次转客
            $field_tt .= ",( case when (bb.tt = '0' or bb.tt = '1,0') then 1 else 0 end) toker_transfer";
            $field_tt .= ",bb.toker9";
            $order_tt = Db::table($order_uid. ' bb')
                ->field($field_tt)
                ->buildSql();

            //拓客转客真实数量
            $cc = "cc.storeid,cc.uid,cc.fid,cc.title,cc.sign,cc.tt,cc.toker9";
            //首次转客
            $cc .= ",( case when cc.first_transfer>0 ";
            $cc .= " then cc.first_transfer- cc.toker9 ";
            $cc .= "  else cc.first_transfer end ) first_transfer";
            //不包含首次转客
            $cc .= ",( case when cc.first_transfer>0";
            $cc .= " then ( cc.transfer_part+ cc.toker9 )";
            $cc .= "  else cc.transfer_part end ) transfer_part";
            //真实拓客
            $cc .= ",( case when ( cc.transfer_part + cc.toker_part) > 0 ";
            $cc .= "   then ( cc.transfer_part + cc.toker_part -cc.toker9 ) ";
            $cc .= "   else ( cc.transfer_part + cc.toker_part) end ) toker";
            $order_cc = Db::table($order_tt. 'cc')
                ->field($cc)
                ->buildSql();

            $dd = "dd.storeid,dd.uid,dd.fid,dd.title,dd.sign,dd.toker9";
            $dd .= ",dd.toker,dd.first_transfer,dd.transfer_part";
            $dd .= ",(dd.transfer_part + dd.first_transfer) transfer";

            $order_dd = Db::table($order_cc . 'dd')
                ->field($dd)
                ->buildSql();
            //以门店分组
            $filed_store = "d.storeid,d.title,d.sign";
            $filed_store .= ",SUM( d.transfer ) transfer";#转客总数量
            $filed_store .= ",SUM( d.first_transfer ) first_transfer";#首次转客
            $filed_store .= ",SUM( d.transfer_part ) transfer_part";#不包含首次转客
            $filed_store .= ",SUM( d.toker ) toker";#真实拓客

            $pmap['channel'] = 'missshop';
            $pmap['pay_status'] = 1;
            $pmap['pay_time'] = ['BETWEEN', [$begin,$end]];
            //门店总金额
            $price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$price}), 0) price";#门店全部金额
            //门店转客总金额
            $transfer_price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('scene','=',1)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$transfer_price}), 0) transfer_price";#门店转客金额
            $order_store = Db::table($order_dd . 'd')
                ->field($filed_store)
                ->group('d.storeid')
                ->order('d.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field(' storeid,uid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->where('insert_time','in',[$begin,$end])
                ->group('uid')
                ->buildSql();
            $share_sql = Db::table($share . 'sss')
                ->field(' sss.storeid,count(sss.uid) share_num')
                ->group('sss.storeid')
                ->buildSql();
            //计算分数
            $field = "abc.storeid,abc.title,abc.sign";
            $field .= ",abc.transfer_price,abc.price";
            $field .= ",abc.transfer";#转客总数
            //$field .= ",abc.first_transfer";#首次转客数
            //$field .= ",abc.transfer_part";
            $field .= ",abc.toker ";#拓客总数
            $field .= ",IFNULL( share.share_num, 0 ) share_num ";#分享数量
            $field .= ",(abc.toker * 2 + abc.transfer_part * 3 + abc.first_transfer * 5 + floor( abc.price / 200 ) + IFNULL( share.share_num, 0 )) grade ";#总分数
           //$field .= ",(CASE WHEN (abc.transfer_price >= 100000 AND abc.toker >= 200 AND abc.transfer >= 100 )
            // THEN 1 ELSE 0 END ) flag";//标识
            $new_sql = Db::table( $order_store . 'abc' )
                ->field($field)
                ->join([$share_sql => 'share'],'abc.storeid = share.storeid','left')
                ->buildSql();

            //查询10.30之前的数据
            $old = "storeid,sign,title,price,transfer_price,transfer,toker,share_num,grade";
            $old_sql = Db::name('activity_order_rank')
                ->field($old)
                ->where('storeid','in',$stores)
                ->buildSql();
            //合并
            $merge_field = "l.storeid,l.title,l.sign";
            $merge_field .= ",(IFNULL(l.price,0) + IFNULL(n.price,0)) price";
            $merge_field .= ",(IFNULL(l.transfer_price,0) + IFNULL(n.transfer_price,0)) transfer_price";
            $merge_field .= ",(IFNULL(l.transfer,0) + IFNULL(n.transfer,0)) transfer";
            $merge_field .= ",(IFNULL(l.toker,0) + IFNULL(n.toker,0)) toker";
            $merge_field .= ",(IFNULL(l.share_num,0) + IFNULL(n.share_num,0)) share_num";
            $merge_field .= ",(IFNULL(l.grade,0) + IFNULL(n.grade,0)) grade";
            $sql = Db::table($old_sql . 'l')
                ->field($merge_field)
                ->join( [$new_sql => 'n'] ,'l.storeid=n.storeid','left')
                ->buildSql();

            $xxx = "xxx.storeid,xxx.title,xxx.sign,xxx.price,xxx.transfer_price,xxx.transfer,xxx.toker,xxx.share_num,xxx.grade";
            $xxx .= ",(CASE WHEN (xxx.transfer_price >= 100000 AND xxx.toker >= 200 AND xxx.transfer >= 100) THEN 1 ELSE 0 END) flag";
            $list = Db::table($sql . 'xxx')
                ->field($xxx)
                ->order('xxx.grade desc')
                ->page($pageindex,$pagesize)
                ->select();
            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('agency_rank_'.$agency.'_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }



    /**
     * Commit: 门店入围奖排行
     * Function: win_bid_rank
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-14 11:38:42
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function win_bid_rank1()
    {
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = $activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('win_bid_rank_'.$pageindex)){
            $list = Cache::get('win_bid_rank_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign";
            $field_where .= ",( CASE WHEN o.pay_price THEN o.pay_price ELSE 0 END ) pay_price";
            $field_where .= ",(CASE WHEN o.pid = 47 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a47";
            $field_where .= ",(CASE WHEN o.pid = 79 AND o.scene = 0 AND o.uid <> o.fid THEN 1 ELSE 0 END )  a79";
            $map['o.channel'] = 'missshop';
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->order('o.storeid asc,o.uid asc')
                ->buildSql();
            //以用户分组
            $field_uid = "a.storeid,a.uid,a.fid,GROUP_CONCAT(a.pid) pid,a.title,a.sign";
            $field_uid .= ",CAST(SUM( BINARY ( a.pay_price)) as decimal(10,2)) pay_price";
            $field_uid .= ",CAST(SUM( BINARY ( ( CASE WHEN a.scene = 1 THEN a.pay_price ELSE 0 END ))) as decimal(10,2)) transfer_price";
            $field_uid .= ",SUM(a.a47) a47,SUM(a.a79) a79";
            $field_uid .= ",greatest(SUM(a.a47),SUM(a.a79)) toker";
            $field_uid .= ",( CASE WHEN COUNT( a.uid ) >= 1 AND a.scene = 1 THEN 1 ELSE 0 END ) first_transfer";
            $field_uid .= ",sum( CASE WHEN a.scene = 1 THEN 1 ELSE 0 END ) transfer";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();

            //以门店分组
            $filed_store = "b.storeid,b.title,b.sign";
            $filed_store .= ",SUM(BINARY(b.pay_price)) pay_price";
            $filed_store .= ",SUM(BINARY(b.transfer_price)) transfer_price";
            $filed_store .= ",SUM(b.first_transfer) first_transfer";
            $filed_store .= ",SUM( b.toker) toker";
            $filed_store .= ",SUM( b.transfer) transfer";
            $order_store = Db::table($order_uid . 'b')
                ->field($filed_store)
                ->group('b.storeid')
                ->order('b.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field('( CASE WHEN count( uid ) THEN 1 ELSE 0 END ) share_num, storeid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->group('storeid')
                ->buildSql();
            //计算分数
            $field = "c.storeid,c.title,c.sign,c.pay_price,c.transfer_price,c.first_transfer,c.toker,c.transfer";
            $field .= ",IFNULL( d.share_num, 0 ) share_num";
            $field .= ",( ( c.transfer - c.first_transfer ) * 3 ) transfer_grade";
            $field .= ",( c.first_transfer * 5 ) first_transfer_grade";
            $field .= ",( c.toker * 2 ) toker_grade";
            $field .= ",( floor( c.pay_price / 200 ) ) seller_grade";
            $field .= ",(c.toker * 2 + ( c.transfer - c.first_transfer ) * 3 + c.first_transfer * 5 + floor( c.pay_price / 200 ) + IFNULL( d.share_num, 0 )) grade";
            $field .= ",(CASE WHEN (c.transfer_price >= 100000 AND c.toker >= 200 AND c.transfer >= 100 ) THEN 1 ELSE 0 END ) flag ";//标识
            $list = Db::table( $order_store . 'c')
                ->field($field)
                ->join([$share => 'd'],'c.storeid = d.storeid','left')
                ->where('c.pay_price','>=',20000)
                ->where('c.toker','>=',100)
                ->where('c.transfer','>=',20)
                ->order('grade desc')
                ->page($pageindex,$pagesize)
                ->select();

            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('win_bid_rank_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }

    public function win_bid_rank()
    {
        //活动配置信息
        $activity_config = Db::name('activity_config')->where('id',2)->find();
        $code = 1;
        $data = [];
        $msg = '数据请求成功';

        if(empty($activity_config)){
            $code = 0;
            $msg = '参数未填写完整';
            return parent::returnMsg($code,$data,$msg);
        }
        $begin = 1572364800;//$activity_config['begin_time'];//活动开始时间
        $end = $activity_config['end_time'];//活动结束时间
        $stop = $activity_config['show_time'];//排行榜静止时间
        if(time() >= $stop){
        	$end = $stop;
        }
        $pageindex = intval(input('param.page'))?:1;//当前页
        $pagesize = config('list_rows')?:15;//每页条数

        if(Cache::get('win_bid_rank_'.$pageindex)){
            $list = Cache::get('win_bid_rank_'.$pageindex);
            $info['row'] = count($list);
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = $list;
        }else {
            //查询订单数据并排序 店铺及用户排序
            $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid,store.title,store.sign ";
            $map['o.channel'] = 'missshop';
            $map['o.pay_status'] = 1;
            $map['o.pay_time'] = ['between',[$begin,$end]];
            $order_where = Db::name('activity_order')
                ->alias('o')
                ->join(['ims_bwk_branch' => 'store'],'o.storeid = store.id','left')
                ->field($field_where)
                ->where($map)
                ->where('o.uid<>o.fid')
                ->buildSql();

            //查询门店
            $stores = Db::name('activity_order')
                ->where('channel','=','missshop')
                ->where('pay_status','=',1)
                ->where('pay_time','between',[1569859200,$end])
                ->group('storeid')
                ->column('storeid');

            //以用户分组
            #获取九月份拓客记录信息
            $map9['channel'] = 'missshop';
            $map9['pay_status'] = 1;
            $map9['scene'] = 0;
            $map9['pay_time'] = ['<',$begin];
            $toker9 = Db::name('activity_order')
                ->field('(case when scene=0 then 1 else 0 end) toker9')
                ->where($map9)
                ->where('uid<>fid')
                ->where('uid = a.uid')
                ->group('uid')
                ->buildSql();

            $field_uid = "a.storeid,a.uid,a.fid,a.title,a.sign";
            $field_uid .= ",GROUP_CONCAT(DISTINCT a.scene ORDER BY a.scene desc) tt";#去重后标识
            $field_uid .= ",IFNULL({$toker9},0) toker9";
            $order_uid = Db::table($order_where . 'a')
                ->field($field_uid)
                ->group('a.uid')
                ->order('a.storeid asc,a.uid asc')
                ->buildSql();
            //查询转客拓客用户数量
            $field_tt = "bb.storeid,bb.uid,bb.fid,bb.title,bb.sign,bb.tt";
            $field_tt .= ",( case when bb.tt = '1,0' then 1 else 0 end) transfer_part";#拓转客
            $field_tt .= ",( case when bb.tt = '0' then 1 else 0 end) toker_part";#拓客一部分
            $field_tt .= ",( case when bb.tt = '1' then 1 else 0 end) first_transfer";#首次转客
            $field_tt .= ",( case when (bb.tt = '0' or bb.tt = '1,0') then 1 else 0 end) toker_transfer";
            $field_tt .= ",bb.toker9";
            $order_tt = Db::table($order_uid. ' bb')
                ->field($field_tt)
                ->buildSql();

            //拓客转客真实数量
            $cc = "cc.storeid,cc.uid,cc.fid,cc.title,cc.sign,cc.tt,cc.toker9";
            //首次转客
            $cc .= ",( case when cc.first_transfer>0 ";
            $cc .= " then cc.first_transfer- cc.toker9 ";
            $cc .= "  else cc.first_transfer end ) first_transfer";
            //不包含首次转客
            $cc .= ",( case when cc.first_transfer>0";
            $cc .= " then ( cc.transfer_part+ cc.toker9 )";
            $cc .= "  else cc.transfer_part end ) transfer_part";
            //真实拓客
            $cc .= ",( case when ( cc.transfer_part + cc.toker_part) > 0 ";
            $cc .= "   then ( cc.transfer_part + cc.toker_part - cc.toker9 ) ";
            $cc .= "   else ( cc.transfer_part + cc.toker_part) end ) toker";
            $order_cc = Db::table($order_tt. 'cc')
                ->field($cc)
                ->buildSql();

            $dd = "dd.storeid,dd.uid,dd.fid,dd.title,dd.sign,dd.toker9";
            $dd .= ",dd.toker,dd.first_transfer,dd.transfer_part";
            $dd .= ",(dd.transfer_part + dd.first_transfer) transfer";

            $order_dd = Db::table($order_cc . 'dd')
                ->field($dd)
                ->buildSql();
            //以门店分组
            $filed_store = "d.storeid,d.title,d.sign";
            $filed_store .= ",SUM( d.transfer ) transfer";#转客总数量
            $filed_store .= ",SUM( d.first_transfer ) first_transfer";#首次转客
            $filed_store .= ",SUM( d.transfer_part ) transfer_part";#不包含首次转客
            $filed_store .= ",SUM( d.toker ) toker";#真实拓客

            $pmap['channel'] = 'missshop';
            $pmap['pay_status'] = 1;
            $pmap['pay_time'] = ['BETWEEN', [$begin,$end]];
            //门店总金额
            $price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$price}), 0) price";#门店全部金额
            //门店转客总金额
            $transfer_price = Db::name('activity_order')
                ->field('sum(pay_price)')
                ->where($pmap)
                ->where('scene','=',1)
                ->where('storeid = d.storeid')
                ->buildSql();
            $filed_store .= ",IFNULL(({$transfer_price}), 0) transfer_price";#门店转客金额
            $order_store = Db::table($order_dd . 'd')
                ->field($filed_store)
                ->group('d.storeid')
                ->order('d.storeid asc')
                ->buildSql();
            //获取用户分享数据
            $share = Db::name('activity_share')
                ->field(' storeid,uid,fid')
                ->where('item','=','miss_tuoke')
                ->where('storeid','<>','')
                ->where('insert_time','in',[$begin,$end])
                ->group('uid')
                ->buildSql();
            $share_sql = Db::table($share . 'sss')
                ->field(' sss.storeid,count(sss.uid) share_num')
                ->group('sss.storeid')
                ->buildSql();
            //计算分数
            $field = "abc.storeid,abc.title,abc.sign";
            $field .= ",abc.transfer_price,abc.price";
            $field .= ",abc.transfer";#转客总数
            $field .= ",abc.toker ";#拓客总数
            $field .= ",IFNULL( share.share_num, 0 ) share_num ";#分享数量
            $field .= ",(abc.toker * 2 + abc.transfer_part * 3 + abc.first_transfer * 5 + floor( abc.price / 200 ) + IFNULL( share.share_num, 0 )) grade ";#总分数
            $new_sql = Db::table( $order_store . 'abc' )
                ->field($field)
                ->join([$share_sql => 'share'],'abc.storeid = share.storeid','left')
                ->buildSql();
            //查询10.30之前的数据
            $old = "storeid,sign,title,price,transfer_price,transfer,toker,share_num,grade";
            $old_sql = Db::name('activity_order_rank')
                ->field($old)
                ->where('storeid','in',$stores)
                ->buildSql();

            //合并
            $merge_field = "l.storeid,l.title,l.sign";
            $merge_field .= ",(IFNULL(l.price,0) + IFNULL(n.price,0)) price";
            $merge_field .= ",(IFNULL(l.transfer_price,0) + IFNULL(n.transfer_price,0)) transfer_price";
            $merge_field .= ",(IFNULL(l.transfer,0) + IFNULL(n.transfer,0)) transfer";
            $merge_field .= ",(IFNULL(l.toker,0) + IFNULL(n.toker,0)) toker";
            $merge_field .= ",(IFNULL(l.share_num,0) + IFNULL(n.share_num,0)) share_num";
            $merge_field .= ",(IFNULL(l.grade,0) + IFNULL(n.grade,0)) grade";
            $sql = Db::table($old_sql . 'l')
                ->field($merge_field)
                ->join( [$new_sql => 'n'] ,'l.storeid=n.storeid','left')
                ->buildSql();

            $xxx = "xxx.storeid,xxx.title,xxx.sign,xxx.price,xxx.transfer_price,xxx.transfer,xxx.toker,xxx.share_num,xxx.grade";
            $where['xxx.transfer_price'] = [ '>=' , 20000 ];
            $where['xxx.toker'] = ['>=',100];
            $where['xxx.transfer'] = ['>=',20];
            $list = Db::table($sql . 'xxx')
                ->field($xxx)
                ->where($where)
                ->order('xxx.grade desc')
                ->page($pageindex,$pagesize)
                ->select();

            if(time() >= $stop){
                self::$missshop_2_expire = 0;//缓存时间
            }
            if(empty($list)){
                $code = 0;
                $msg = '暂无数据';
            }
            //排行榜分数
            $info['row'] = !empty($list)?count($list):0;
            $info['size'] = $pagesize;
            $info['page'] = $pageindex;
            $data['info'] = $info;
            $data['list'] = !empty($list)?$list:[];
            Cache::set('win_bid_rank_'.$pageindex,$list,self::$missshop_2_expire);
        }
        return parent::returnMsg($code,$data,$msg);
    }












}