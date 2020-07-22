<?php

namespace app\qmjk\controller;
use org\QiniuUpload;
use think\Controller;
use think\Db;

/**
 * desc: 异业联盟/全民集客
 */
class TradeUnion extends Base
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

    /*
     * 门店注册
     */
    public function branchReg(){
        $mobile=input('param.mobile');
        $name=input('param.name');
        $title=input('param.title');
        $address=input('param.address');
        $logo=input('param.logo');
        if($mobile!='' || $name!='' || $title!='' || $address!=''){
            $map['mobile']=array('eq',$mobile);
            $map['title']=array('eq',$title);
            $check=Db::name('qmjk_branch')->where('mobile',$mobile)->whereOr('title',$title)->find();
            if($check){
                $code = 0;
                $data = '';
                if($check['mobile']==$mobile){
                    $msg = '手机号码已经存在！';
                }else{
                    $msg = '门店名称已经存在';
                }
            }else{
                $insertData=array('mobile'=>$mobile,'name'=>$name,'title'=>$title,'logo'=>$logo,'address'=>$address,'insert_time'=>time());
                $insertId=Db::name('qmjk_branch')->insertGetId($insertData);
                if($insertId){
                    $memberData=array('branch_id'=>$insertId,'name'=>$name,'mobile'=>$mobile,'role'=>1,'type'=>1,'insert_time'=>time());
                    Db::name('qmjk_member')->insert($memberData);
                    $code = 1;
                    $data = '';
                    $msg = '注册成功，请等待平台审核';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '注册失败，请重试！';
                }
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 加盟商注册
     */
    public function unionReg(){
        $mobile=input('param.mobile');
        $name=input('param.name');
        $title=input('param.title');
        $address=input('param.address');
        $pay_code=input('param.pay_code');//收款码
        //$b_id=input('param.bid');//美容院id
        $u_id=input('param.fid');//美容院推广员uid
        if($mobile!='' || $name!='' || $title!='' || $address!='' || $pay_code!='' || $u_id!=''){
            //根据推广人uid获取关联美容院id
            $b_id=Db::name('qmjk_member')->where('id',$u_id)->value('branch_id');
            //检测推广美容院状态是否正常
            $branchStatus=Db::name('qmjk_branch')->where('id',$b_id)->value('status');
            if($branchStatus==1) {
                //检测自己是否注册过联盟商门店
                $check = Db::name('qmjk_union')->where('mobile',$mobile)->find();
                if($check){
                    $map['branch_id'] = array('eq', $b_id);
                    $map['union_id'] = array('eq', $check['id']);
                    $check1 = Db::name('qmjk_union_relation')->where($map)->find();
                    if($check1) {
                        $code = 0;
                        $data = '';
                        if ($check['step'] == 0) {
                            $msg = '联盟申请已提交 等待美容院审核！';
                        } else {
                            $msg = '您已经是该美容院联盟了 不允许重复申请';
                        }
                    }else{
                        $relationData = array('union_id' => $check['id'], 'branch_id' => $b_id,'fid'=>$u_id,'insert_time'=>time());
                        Db::name('qmjk_union_relation')->insert($relationData);
                        //发站内信 带着门店id和联盟商id
                        $message['type']=1;
                        $message['senduid']=Db::name('qmjk_member')->where(['mobile'=>$mobile,'union_id'=>$check['id']])->value('id');
                        $bInfo=Db::name('qmjk_member')->where(['branch_id'=>$b_id,'type'=>1,'role'=>1])->field('id,mobile')->find();
                        $message['getuid']=$bInfo['id'];
                        $message['params']='bid='.$b_id.'&union_id='.$check['id'];
                        $message['title']='加盟申请审核';
                        $message['content']='有新的加盟商-'.$check['title'].'给您发起了一条加盟申请，请尽快处理！'.'';
                        $this->send_message($message,1,$bInfo['mobile'],['title'=>$title],98);
                        $code = 1;
                        $data = '';
                        $msg = '注册成功，请等待商家确认';
                    }
                }else{
                    $insertData = array('mobile' => $mobile, 'name' => $name, 'title' => $title, 'address' => $address, 'insert_time' => time(), 'bid' => $b_id, 'uid' => $u_id, 'pay_code' => $pay_code);
                    $newId=Db::name('qmjk_union')->insertGetId($insertData);
                    $memberData = array('union_id' => $newId,'name' => $name, 'mobile' => $mobile, 'type' => 2, 'insert_time' => time());
                    Db::name('qmjk_member')->insert($memberData);
                    $relationData=array('union_id'=>$newId,'branch_id'=>$b_id,'fid'=>$u_id,'insert_time'=>time());
                    Db::name('qmjk_union_relation')->insert($relationData);
                    //发站内信 带着门店id和联盟商id
                    $message['type']=1;
                    $message['senduid']=Db::name('qmjk_member')->where(['mobile'=>$mobile,'union_id'=>$newId])->value('id');
                    $bInfo=Db::name('qmjk_member')->where(['branch_id'=>$b_id,'type'=>1,'role'=>1])->field('id,mobile')->find();
                    $message['getuid']=$bInfo['id'];
                    $message['params']='bid='.$b_id.'&union_id='.$newId;
                    $message['title']='加盟申请审核';
                    $message['content']='有新的加盟商-'.$title.'给您发起了一条加盟申请，请点击处理！';
                    $this->send_message($message,1,$bInfo['mobile'],['title'=>$title],98);
                    $code = 1;
                    $data = '';
                    $msg = '注册成功，请等待商家确认';
                    }
            }else{
                $code = 0;
                $data = '';
                $msg = '注册失败，扫码二维码已失效！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 获取联盟商信息
     */
    public function getUnionInfo(){
        $bid=input('param.bid');
        $unionId=input('param.union_id');
        if($unionId!='') {
            $unionInfo = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union union', 'r.union_id=union.id', 'left')->join('qmjk_wx_user user', 'union.mobile=user.mobile', 'left')->field('union.id,r.step,union.name,union.mobile,union.title,union.address,union.pay_code,r.pay_role,user.nickname,user.avatar')->where(['r.union_id'=>$unionId,'r.branch_id'=>$bid])->find();
            if(strlen($unionInfo['pay_role'])){
                $unionInfo['pay_role']=json_decode($unionInfo['pay_role'],true);
            }else{
                $unionInfo['pay_role']=array("roleType"=>"","firstPrice"=>"","otherPrice"=>"","payDay"=>"");
            }
            $code = 1;
            $data = $unionInfo;
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 美容院配置支付条款
     */
    public function updatePayRole(){
        $bid=input('param.bid');
        $unionId=input('param.union_id');
        $roleType=input('param.role_type');
        $firstPrice=input('param.first_price');//首单
        $otherPrice=input('param.other_price','');//次单
        $payDay=input('param.pay_day');
        $branchName=Db::name('qmjk_branch')->where('id',$bid)->value('title');
        if($unionId!='' || $roleType!='' || $firstPrice!='' || $payDay!='') {
            $info=Db::name('qmjk_union_relation')->where(['branch_id'=>$bid,'union_id'=>$unionId])->find();
            if($info['step'] && $info['flag']){
                $code = 0;
                $data = '';
                $msg = '该消息已处理过，不允许编辑！';
            }else{
                $param['roleType']=$roleType;
                $param['firstPrice']=$firstPrice;
                $param['otherPrice']=$otherPrice;
                $param['payDay']=$payDay;
                Db::name('qmjk_union_relation')->where(['branch_id'=>$bid,'union_id'=>$unionId])->update(['step'=>1,'pay_role'=>json_encode($param)]);
                //发站内信 带着门店id和联盟商id
                $message['type']=2;
                $message['senduid']=Db::name('qmjk_member')->where(['branch_id'=>$bid,'type'=>1,'role'=>1])->value('id');
                $getInfo=Db::name('qmjk_member')->where(['type'=>2,'union_id'=>$unionId])->field('id,mobile')->find();
                $message['getuid']=$getInfo['id'];
                $message['params']='bid='.$bid.'&union_id='.$unionId;
                $message['title']='加盟申请通过提醒';
                $message['content']=$branchName.'通过了您的加盟申请，并给你定制了加盟结算条款，请点击查看确认！';
                $this->send_message($message,1,$getInfo['mobile'],['title'=>$branchName],99);
                $code = 1;
                $data = '';
                $msg = '确认成功';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 联盟商确定支付条款
     */
    public function confirmPayRole(){
        $bid=input('param.bid');
        $unionId=input('param.union_id');
        if($unionId!='') {
            Db::name('qmjk_union_relation')->where(['union_id'=>$unionId,'branch_id'=>$bid])->update(['step'=>2]);
            $code = 1;
            $data = '';
            $msg = '确认成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 门店下联盟商列表
     */
    public function unionLists(){
        $uid=input('param.uid','');//当前用户id
        $bid=input('param.bid');
        if($bid!='') {
            if($uid!=''){
                $getUidRole=Db::name('qmjk_member')->where('id',$uid)->value('role');
                if($getUidRole==0){
                    $map['r.fid']=array('eq',$getUidRole);
                }
            }
            $map['r.branch_id']=array('eq',$bid);
            $map['union.status']=array('eq',1);
            $map['r.step']=array('eq',2);
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union union','r.union_id=union.id','left')->where($map)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $unionList = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union union','r.union_id=union.id','left')->join('qmjk_wx_user user', 'union.mobile=user.mobile', 'left')->field('r.pay_role,union.id,union.name,union.mobile,union.title,union.address,user.nickname,user.avatar')->where($map)->page($Nowpage, $limits)->select();
            if (is_array($unionList) && count($unionList)) {
                foreach ($unionList as $k => $v) {
                    $unionList[$k]['all_person'] = Db::name('qmjk_member')->where(['branch_id' => $bid, 'union_id' => $v['id'], 'type' => 3])->count();
                    $unionList[$k]['week_person'] = Db::name('qmjk_member')->where(['branch_id' => $bid, 'union_id' => $v['id'], 'type' => 3])->whereTime('insert_time', 'week')->count();
                    $unionList[$k]['pay_role']=json_decode($v['pay_role'],true);
                }
            }
            $info['list']=$unionList;
            $code = 1;
            $data = $info;
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 联盟商下顾客支付订单列表
     */
    public function unionPayOrder(){
        $bid=input('param.bid');
        $unionId=input('param.union_id');
        if($unionId!='') {
            $unionInfo = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union union','r.union_id=union.id','left')->where(['branch_id'=>$bid,'union_id'=>$unionId])->field('union.title,r.pay_role,r.step,union.pay_code')->find();
            if($unionInfo['step']==2) {
                $total=0;
                $pay_role = json_decode($unionInfo['pay_role'], true);
                if($pay_role['roleType']==1){
                    $members = Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user jku', 'm.mobile=jku.mobile', 'left')->where(['m.branch_id' => $bid, 'm.union_id' => $unionId, 'm.type' => 3])->field('m.id,m.id uid,m.insert_time time,m.name,jku.nickname,jku.avatar')->select();
                    if (is_array($members) && count($members)){
                        foreach ($members as $k=>$v){
                            $members[$k]['price'] = 0;
                            $members[$k]['time'] = date('Y-m-d H:i:s', $v['time']);
                            $members[$k]['refunds_money'] = $this->get_refunds_money($pay_role, $k, $v);
                            $total += $members[$k]['refunds_money'];
                        }
                    }
                    $getOrder=$members;
                }else{
                    $getMemberId = Db::name('qmjk_member')->where(['branch_id' => $bid, 'union_id' => $unionId, 'type' => 3])->column('mobile');
                    $map['m.mobile'] = array('in', $getMemberId);
                    $map['order.status'] = array('in', '1,2,3,7');
                    $map['order.qmjk_pay'] = array('eq', 0);
                    $getOrder = Db::table('ims_bj_shopn_order')->alias('order')->join(['ims_bj_shopn_member' => 'm'], 'order.uid=m.id', 'left')->join('qmjk_member jkm', 'm.mobile=jkm.mobile', 'left')->join('qmjk_wx_user jku', 'jkm.mobile=jku.mobile', 'left')->where($map)->field('order.id,order.price,order.payTime time,m.id uid,jkm.name,jku.nickname,jku.avatar')->select();
                    if (is_array($getOrder) && count($getOrder)) {
                        foreach ($getOrder as $k => $v) {
                            $getOrder[$k]['time'] = date('Y-m-d H:i:s', $v['time']);
                            $getOrder[$k]['refunds_money'] = $this->get_refunds_money($pay_role, $k, $v);
                            $total += $getOrder[$k]['refunds_money'];
                        }
                    }
                }
                $code = 1;
                $data = ['union'=>array('title'=>$unionInfo['title'],'roleType'=>$pay_role['roleType'],'payDay'=>$pay_role['payDay'],'pay_code'=>$unionInfo['pay_code'],'need_pay'=>$total),'orders'=>$getOrder];
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '交易条款双方未确定！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 确认提交结算打款
     */
    public function unionPaySubmit(){
        $bid=input('param.bid');
        $unionId=input('param.union_id');
        $ids=input('param.ids');
        if($bid !='' || $unionId!='' || $ids!='') {
            $unionInfo = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union union','r.union_id=union.id','left')->where(['branch_id'=>$bid,'union_id'=>$unionId])->field('union.title,r.pay_role,r.step,union.pay_code')->find();
            $pay_role = json_decode($unionInfo['pay_role'], true);
            if(strlen($ids)){
                $idsArr=explode('-',$ids);
            }else{
                $idsArr='';
            }
            if(is_array($idsArr) && count($idsArr)){
                $pay_month=date('n',time());
                $pay_number=date('YmdHis').$bid.$unionId.rand(111,999);
                $check=Db::name('qmjk_order_pay')->where(['bid'=>$bid,'union_id'=>$unionId,'pay_month'=>$pay_month])->count();
                if(!$check) {
                    $payMoney=0;
                    foreach ($idsArr as $key => $val) {
                        if($pay_role['roleType']==1){
                            $pay_money = $this->get_refunds_money($pay_role, $key, []);
                            $insertData = array('pay_number'=>$pay_number,'bid' => $bid, 'union_id' => $unionId, 'uid' => $val, 'order_id' => $val,'pay_type' => $pay_role['roleType'], 'order_sn' => '', 'pay_month' => $pay_month, 'pay_money' => $pay_money, 'insert_time' => time());
                            $res = Db::name('qmjk_order_pay')->insert($insertData);
                            if ($res) {
                                Db::name('qmjk_member')->where('id', $val)->update(['flag' => 1]);
                            }
                        }else {
                            $orderInfo = Db::table('ims_bj_shopn_order')->where('id', $val)->field('uid,ordersn,price')->find();
                            $pay_money = $this->get_refunds_money($pay_role, $key, $orderInfo);
                            $insertData = array('pay_number'=>$pay_number,'bid' => $bid, 'union_id' => $unionId, 'uid' => $orderInfo['uid'], 'order_id' => $val,'pay_type' => $pay_role['roleType'], 'order_sn' => $orderInfo['ordersn'], 'pay_month' => $pay_month, 'pay_money' => $pay_money, 'insert_time' => time());
                            $res = Db::name('qmjk_order_pay')->insert($insertData);
                            if ($res) {
                                Db::table('ims_bj_shopn_order')->where('id', $val)->update(['qmjk_pay' => 1]);
                            }
                        }
                        $payMoney+=$pay_money;
                    }
                    //发送上传凭证的通知
                    $uName=Db::name('qmjk_union')->where('id',$unionId)->value('title');
                    $bInfo=Db::name('qmjk_member')->where(['branch_id'=>$bid,'type'=>1,'role'=>1])->field('id,mobile')->find();
                    $message['type']=3;
                    $message['senduid']=$bInfo['id'];
                    $message['getuid']=$bInfo['id'];
                    $message['params']='pay_number='.$pay_number;
                    $message['title']='上传支付凭证通知';
                    $message['content']='您给'.$uName.'生成了一笔集客结算费用，请尽快支付，支付后，点击此处上传支付凭证！';
                    $this->send_message($message,1,$bInfo['mobile'],['title'=>$uName],100);

                    $code =1;
                    $data = $pay_number;
                    $msg = '支付记录已生成，请尽快付款';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '当月您已经进行过结算操作，下个月再来处理哦';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无需要结算的订单';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }
    /*
     *项联盟商已支付佣金列表
     */
    public function unionPayLog(){
        $bid=input('param.bid');
        $unionId=input('param.union_id');
        if($unionId!='' || $bid!='') {
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count = Db::name('qmjk_order_pay')->where(['bid'=>$bid,'union_id'=>$unionId])->group('pay_month')->select();
            $count= count($count);
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list = Db::name('qmjk_order_pay')->where(['bid'=>$bid,'union_id'=>$unionId])->field('pay_number,pay_month,insert_time,sum(pay_money) pay_money,status,pay_evidence')->group('pay_month')->page($Nowpage, $limits)->select();
            if(is_array($list) && count($list)){
                foreach ($list as $k=>$v){
                    $list[$k]['insert_time']=date('Y-m-d',$v['insert_time']);
                    $list[$k]['pay_money']=round($v['pay_money'],2);
                    $list[$k]['pay_status']=$v['status']?'已到帐':'未确认';
                    $list[$k]['evidence']=$v['pay_evidence']?'凭证已上传':'凭证未上传';
                }
            }
            $info['list']=$list;
            $code = 1;
            $data = $info;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 店老板取消联盟商合作
     */
    public function cancelCooperate(){
        $branch_id=input('param.branch_id');
        $union_id=input('param.union_id');
        if($branch_id!='' && $union_id!=''){
            $getConfigMoney=Db::name('qmjk_order_pay')->where(['bid'=>$branch_id,'union_id'=>$union_id,'status'=>array('neq','2')])->sum('pay_money');
            $getWjsMoney=$this->get_money($branch_id,$union_id);
            $waitMoney=$getConfigMoney+$getWjsMoney;
            if($waitMoney){
                $code = 0;
                $data = '';
                $msg = '联盟商集客费用未结清，不允许取消';
            }else{
                Db::name('qmjk_union_relation')->where(['branch_id'=>$branch_id,'union_id'=>$union_id])->update(['status'=>0]);
                $bName=Db::name('qmjk_branch')->where('id',$branch_id)->value('title');
                $message['type']=0;
                $message['senduid']=Db::name('qmjk_member')->where(['branch_id'=>$branch_id,'type'=>1,'role'=>1])->value('id');
                $message['getuid']=Db::name('qmjk_member')->where(['branch_id'=>$branch_id,'union_id'=>$union_id,'type'=>2])->value('id');
                $message['params']='';
                $message['title']="合作取消通知";
                $message['content']=$bName.'取消了与您的联盟合作！';
                $this->send_message($message);
                $code = 1;
                $data = '';
                $msg = '合作取消成功';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 门店上传支付凭证
     */
    public function branchPayEvidence(){
        $pay_number=input('param.pay_number');
        $pay_evidence=input('param.pay_evidence');
        if($pay_number!=''){
            $res=Db::name('qmjk_order_pay')->where('pay_number',$pay_number)->update(['status'=>1,'pay_evidence'=>$pay_evidence]);
            if($res){
                //发送上传凭证的通知
                $payInfo=Db::name('qmjk_order_pay')->where('pay_number',$pay_number)->limit(1)->field('bid,union_id')->find();
                $payMoney=Db::name('qmjk_order_pay')->where('pay_number',$pay_number)->sum('pay_money');
                $payMoney=round($payMoney,2);
                $bName=Db::name('qmjk_branch')->where('id',$payInfo['bid'])->value('title');
                $message['type']=4;
                $message['senduid']=Db::name('qmjk_member')->where(['branch_id'=>$payInfo['bid'],'type'=>1,'role'=>1])->value('id');
                $getInfo=Db::name('qmjk_member')->where(['union_id'=>$payInfo['union_id'],'type'=>2])->field('id,mobile')->find();
                $message['getuid']=$getInfo['id'];
                $message['params']='pay_number='.$pay_number;
                $message['title']='集客费用结算通知';
                $message['content']=$bName.'给您结算集客费用.'.$payMoney.'元，请及时前往确认！';
                $this->send_message($message,1,$getInfo['mobile'],['title'=>$bName,'money'=>$payMoney],101);
                $code = 1;
                $data = '';
                $msg = '支付凭证上传成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '凭证上传错误，请重试';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 门店全部美容师列表
     */
    public function branchSellerList(){
        $bid=input('param.bid');
        if($bid!=''){
            $getStoreid=Db::name('qmjk_branch')->alias('b')->join(['ims_bwk_branch bb'],'b.sign=bb.sign','left')->where('b.id',$bid)->value('bb.id');
            $map['m.storeid']=array('eq',$getStoreid);
            $list=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_fans f'],'m.mobile=f.mobile','left')->field('m.id,m.staffid,m.code,m.realname,m.mobile,f.avatar')->where($map)->select();
            $seller=[];
            foreach ($list as $key=>$val){
                if(strlen($val['code'])>1 && $val['id']=$val['staffid']){
                    unset($val['code']);
                    unset($val['staffid']);
                    $seller[]=$val;
                }
            }
           if(is_array($seller) && count($seller)){
               $code = 1;
               $data = $seller;
               $msg = '获取成功';
           }else{
               $code = 0;
               $data = '';
               $msg = '暂无美容师';
           }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //提交选择美容师
    public function branchSellerSelect(){
        $bid=input('param.bid');
        if($bid!='') {
            $sellerIds = input('param.sellerIds');
            //先删掉原来设置的美容师 再将新增的美容师增加
            $sellerIdsToArr = explode('-', $sellerIds);
            if (is_array($sellerIdsToArr) && count($sellerIdsToArr)) {
                $check=Db::name('qmjk_branch_seller')->where('bid', $bid)->count();
                if($check){
                    Db::name('qmjk_branch_seller')->where('bid', $bid)->delete();
                }
                $insert = [];
                foreach ($sellerIdsToArr as $k => $v) {
                    $insert[$k]['seller_id'] = $v;
                    $insert[$k]['bid'] = $bid;
                }
                Db::name('qmjk_branch_seller')->insertAll($insert);
                $code = 1;
                $data = '';
                $msg = '美容师选择成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '请选择美容师后提交';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 集客选中美容师列表
    */
    public function branchSeller(){
        $bid=input('param.bid');
        if($bid!=''){
            $map['bid']=array('eq',$bid);
            $list=Db::name('qmjk_branch_seller')->alias('s')->join(['ims_bj_shopn_member m'],'s.seller_id=m.id','left')->join(['ims_fans f'],'m.mobile=f.mobile','left')->where($map)->field('s.seller_id id,m.realname,m.mobile,f.avatar')->select();
            if(is_array($list) && count($list)){
                $code = 1;
                $data = $list;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无美容师';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 添加小二
     */

    public function addWaiter(){
        $bid=input('param.bid');
        $name=input('param.name');
        $mobile=input('param.mobile');
        if($bid!='' && $name !='' && $mobile!=''){
            $check=Db::name('qmjk_member')->where(['branch_id'=>$bid,'mobile'=>$mobile])->find();
            $bInfo=Db::name('qmjk_branch')->where('id',$bid)->field('title,name')->find();
            if($check){
                if($bid==$check['branch_id']) {
                    if($check['role'] == 1){
                        $code = 0;
                        $data = '';
                        $msg = '添加失败，不能将店铺管理指定为小二';
                    }else{
                        if ($check['union_id'] == 0 && $check['role'] == 0) {
                            $code = 0;
                            $data = '';
                            $msg = '添加失败，用户已存在';
                        } else {
                            Db::name('qmjk_member')->where(['branch_id'=>$bid,'mobile'=>$mobile])->update(['union_id' => 0, 'role' => 0, 'type' => 1, 'status' => 1]);
                            $this->send_message('',1,$mobile,['name'=>$bInfo['name'],'title'=>$bInfo['title']],102);
                            $code = 1;
                            $data = '';
                            $msg = '添加成功';
                        }
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '添加失败，用户已存在，但不在您的门店中';
                }
            }else{
                $insert=array('branch_id'=>$bid,'name'=>$name,'mobile'=>$mobile,'union_id' => 0, 'role' => 0, 'type' => 1, 'status' => 1,'insert_time'=>time());
                Db::name('qmjk_member')->insert($insert);
                $this->send_message('',1,$mobile,['name'=>$bInfo['name'],'title'=>$bInfo['title']],102);
                $code = 1;
                $data = '';
                $msg = '添加成功';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 小二管理
     */
    public function WaiterManage(){
        $bid=input('param.bid');
        $type=input('param.type','chengjiao');
        $order=input('param.order','desc');
        $condition=array('type'=>$type,'order'=>$order);
        if($bid!=''){
            $uids=[];
            $waiter=Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user u', 'm.mobile=u.mobile', 'left')->field('m.id,m.name,m.mobile,u.avatar,m.role')->where(['m.branch_id'=>$bid,'m.union_id'=>0,'m.type'=>1,'m.status'=>1])->select();
            $map['r.branch_id'] = array('eq', $bid);
            $map['m.type'] = array('eq', 3);
            if($type=='chengjiao') {
                $map['o.status'] = array('in', '1,2,3,7');
                $list = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_member m', 'r.union_id=m.union_id', 'left')->join(['ims_bj_shopn_member member'], 'm.mobile=member.mobile', 'left')->join(['ims_bj_shopn_order o'], 'member.id=o.uid', 'left')->field('r.union_id,r.fid,sum(o.price) total')->where($map)->group('r.fid')->order("total $order")->select();
                if (is_array($list) && count($list)) {
                    foreach ($list as $k => $v) {
                        $uids[]=$v['fid'];
                        $info = Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user u', 'm.mobile=u.mobile', 'left')->where('m.id', $v['fid'])->field('m.name,m.mobile,u.avatar,m.role')->find();
                        $list[$k]['name'] = $info['name'];
                        $list[$k]['mobile'] = $info['mobile'];
                        $list[$k]['avatar'] = $info['avatar'];
                        $list[$k]['role'] = $info['role']?'老板':'小二';
                        $list[$k]['customer'] = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_member m', 'r.union_id=m.union_id', 'left')->where(['r.branch_id' => $bid, 'r.fid' => $v['fid'],'m.branch_id' => $bid, 'm.type' => 3])->count('m.id');
                    }
                }
            }elseif($type=='guke') {
                $list=Db::name('qmjk_union_relation')->alias('r')->join('qmjk_member m','r.union_id=m.union_id','left')->group('r.fid')->field('r.fid,count(m.id) customer')->where($map)->where('m.branch_id',$bid)->order("customer $order")->select();
                if (is_array($list) && count($list)) {
                    foreach ($list as $k => $v) {
                        $uids[]=$v['fid'];
                        $info = Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user u', 'm.mobile=u.mobile', 'left')->where('m.id', $v['fid'])->field('m.name,m.mobile,u.avatar,m.role')->find();
                        $list[$k]['name'] = $info['name'];
                        $list[$k]['mobile'] = $info['mobile'];
                        $list[$k]['avatar'] = $info['avatar'];
                        $list[$k]['role'] = $info['role']?'老板':'小二';
                        $map['o.status'] = array('in', '1,2,3,7');
                        $map['r.fid'] = array('eq', $v['fid']);
                        $list[$k]['total'] = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_member m', 'r.union_id=m.union_id', 'left')->join(['ims_bj_shopn_member member'], 'm.mobile=member.mobile', 'left')->join(['ims_bj_shopn_order o'], 'member.id=o.uid', 'left')->where($map)->sum('o.price');
                    }
                }
            }
            $waiters=[];
            foreach ($waiter as $kk=>$vv){
                if(in_array($vv['id'],$uids)){
                    unset($waiter[$kk]);
                }else{
                    $waiters[$kk]['fid']=$vv['id'];
                    $waiters[$kk]['customer']=0;
                    $waiters[$kk]['name']=$vv['name'];
                    $waiters[$kk]['mobile']=$vv['mobile'];
                    $waiters[$kk]['avatar']=$vv['avatar'];
                    $waiters[$kk]['total']=0;
                    $waiters[$kk]['role'] = $vv['role']?'老板':'小二';
                }
            }
            if($order=='desc'){
                $lists=array_merge($list,$waiters);
            }else{
                $lists=array_merge($waiters,$list);
            }
            $code = 1;
            $data = ['condition'=>$condition,'lists'=>$lists];
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 小二管理的门店
     */

    public function waiterManageUnion(){
        $fid=input('param.fid');
        if($fid!=''){
            $info=Db::name('qmjk_member')->where('id',$fid)->field('branch_id,name')->find();
            $map['r.branch_id'] = array('eq', $info['branch_id']);
            $map['r.fid'] = array('eq', $fid);
            $list = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u', 'r.union_id=u.id', 'left')->join('qmjk_member m', 'u.mobile=m.mobile', 'left')->join('qmjk_wx_user wu', 'm.mobile=wu.mobile', 'left')->where($map)->field('r.union_id,u.title,u.address,u.name,u.mobile,wu.nickname,wu.avatar')->select();
            if (is_array($list) && count($list)) {
                foreach ($list as $k => $v) {
                    $list[$k]['customer'] = Db::name('qmjk_member')->where(['branch_id' => $info['branch_id'], 'union_id' => $v['union_id']])->count();
                    $map['o.status'] = array('in', '1,2,3,7');
                    $map['r.branch_id'] = array('eq', $info['branch_id']);
                    $map['r.fid'] = array('eq', $fid);
                    $map['r.union_id'] = array('eq', $v['union_id']);
                    $map['m.type'] = array('eq', 3);
                    $list[$k]['total'] = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_member m', 'r.union_id=m.union_id', 'left')->join(['ims_bj_shopn_member member'], 'm.mobile=member.mobile', 'left')->join(['ims_bj_shopn_order o'], 'member.id=o.uid', 'left')->where($map)->sum('o.price');
                }
            }
            $info['count']=count($list);
            $info['list']=$list;
            $code = 1;
            $data = $info;
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 本店下小二列表
     */
    public function waiterList(){
        $branch_id=input('param.branch_id');
        if($branch_id!=''){
            $waiter=Db::name('qmjk_member')->field('id,name')->where(['branch_id'=>$branch_id,'union_id'=>0,'type'=>1,'status'=>1])->select();
            $code = 1;
            $data = $waiter;
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 将小二管理的联盟商给别的小二
     */

    public function transferUnion(){
        $branch_id=input('param.branch_id');
        $waiter_id=input('param.waiter_id');
        $union_ids=input('param.union_ids');
        if($branch_id!='' && $waiter_id!=''){
            if($union_ids!='') {
                $unionIdsArr = explode('-', $union_ids);
                foreach ($unionIdsArr as $k=>$v){
                    Db::name('qmjk_union_relation')->where(['branch_id'=>$branch_id,'union_id'=>$v])->update(['fid'=>$waiter_id]);
                }
                $code = 1;
                $data = '';
                $msg = '联盟商转让成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '请至少选择一个联盟门店';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 移除小二 移除前先判断下面有没有联盟商
     */

    public function removeWaiter(){
        $fid=input('param.fid');
        if($fid!=''){
            $bid=Db::name('qmjk_member')->where('id',$fid)->value('branch_id');
            $list=Db::name('qmjk_union_relation')->where(['branch_id'=>$bid,'fid'=>$fid])->count();
            if($list){
                $code = 0;
                $data = '';
                $msg = '请先将其下负责的联盟商转让给别的小二再来删除';
            }else{
                Db::name('qmjk_member')->where('id',$fid)->delete();
                $code = 1;
                $data = '';
                $msg = '小二移除成功';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 联盟商管理主页
     */
    public function unionIndex(){
        $uid=input('param.id');
        if($uid!=''){
            $historyMoney=0;
            $getWaitMoney=0;
            $getUnionId=Db::name('qmjk_member')->where('id',$uid)->value('union_id');
            $list=Db::name('qmjk_union_relation')->alias('r')->join('qmjk_branch b','r.branch_id=b.id','left')->join('qmjk_member m','m.id=r.fid','left')->join('qmjk_wx_user wu','m.mobile=wu.mobile','left')->where(['r.union_id'=>$getUnionId,'r.step'=>2,'r.status'=>1])->field('r.branch_id,r.union_id,b.title,b.address,b.name,b.mobile,wu.nickname,wu.avatar')->select();
            if(is_array($list) && count($list)){
                foreach ($list as $k=>$v){
                    $list[$k]['history_pay']=Db::name('qmjk_order_pay')->where(['bid'=>$v['branch_id'],'union_id'=>$v['union_id'],'status'=>2])->sum('pay_money');
                    $list[$k]['history_member']=Db::name('qmjk_member')->where(['branch_id'=>$v['branch_id'],'union_id'=>$v['union_id'],'type'=>3])->count();
                    $getConfigMoney=Db::name('qmjk_order_pay')->where(['bid'=>$v['branch_id'],'union_id'=>$v['union_id'],'status'=>array('neq','2')])->sum('pay_money');
                    $getWjsMoney=$this->get_money($v['branch_id'],$v['union_id']);
                    $list[$k]['waitMoney']=round($getConfigMoney+$getWjsMoney,2);
                    $historyMoney+=$list[$k]['history_pay'];
                    $getWaitMoney+=$list[$k]['waitMoney'];
                }
            }
            $code = 1;
            $data = ['historyMoney'=>round($historyMoney,2),'getWaitMoney'=>round($getWaitMoney,2),'list'=>$list];
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 联盟商信息
     */
    public function unionInfo(){
        $mobile=input('param.mobile');
        if($mobile!=''){
            $branchInfo=Db::name('qmjk_union')->alias('u')->join('qmjk_member m','u.id=m.union_id','left')->where('m.mobile',$mobile)->field('u.name,u.mobile,u.title,u.address,u.pay_code')->find();
            if($branchInfo){
                $code = 1;
                $data = $branchInfo;
                $msg = '数据获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无数据';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 联盟商更新收款码
     */
    public function updatePayCode(){
        $uid=input('param.id');
        $pay_code=input('param.pay_code');
        if($uid!='' && $pay_code!=''){
            $getUnionId=Db::name('qmjk_member')->where('id',$uid)->value('union_id');
            $update=Db::name('qmjk_union')->where('id',$getUnionId)->update(['pay_code'=>$pay_code]);
            if($update){
                $code = 1;
                $data = '';
                $msg = '收款码更新成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '收款码更新失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 联盟商查看美容院付款凭证
     */
    public function checkPayInfo(){
        $pay_number=input('param.pay_number');
        if($pay_number!=''){
            $info=Db::name('qmjk_order_pay')->where('pay_number',$pay_number)->field('pay_number,pay_evidence,status')->limit(1)->find();
            if($info){
                $code = 1;
                $data = $info;
                $msg = '数据获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '数据获取失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }




    /*
     * 联盟商集客报告
     */
    public function unionReport(){
        $uid=input('param.id');
        $getSixMonth=$this->to_sex_month();//获取前6个月月份
        $getSixDay=$this->to_sex_day();//获取前6天日期
        if($uid!=''){
            $historyMoney=0;
            $historyMember=0;
            $uids=[];
            $getUnionId=Db::name('qmjk_member')->where('id',$uid)->value('union_id');
            $list=Db::name('qmjk_union_relation')->where(['union_id'=>$getUnionId])->field('branch_id,union_id')->select();
            if(is_array($list) && count($list)){
                foreach ($list as $k=>$v){
                    $list[$k]['history_pay']=Db::name('qmjk_order_pay')->where(['bid'=>$v['branch_id'],'union_id'=>$v['union_id'],'status'=>2])->sum('pay_money');
                    $list[$k]['history_member']=Db::name('qmjk_member')->where(['branch_id'=>$v['branch_id'],'union_id'=>$v['union_id'],'type'=>3])->count();
                    $historyMoney+=$list[$k]['history_pay'];
                    $historyMember+=$list[$k]['history_member'];
                    $uids[]=$v['union_id'];
                }
            }
            $money=[];
            $member=[];
            //集客收入月趋势
            if(is_array($uids) && count($uids)){
                $map['union_id']=array('in',$uids);
                $map['status']=array('eq',2);
                foreach ($getSixMonth as $key=>$val){
                    $map['insert_time'] = ['between',[strtotime($val.'-01 00:00:00'),strtotime($val.'-31 23:59:59')]];
                    $data=Db::name('qmjk_order_pay')->where($map)->field('pay_month,sum(pay_money) money')->group('pay_month')->find();
                    if(is_array($data)){
                        $money[]=array('pay_month'=>date('m',strtotime($val)),'money'=>$data['money']);
                    }else{
                        $money[]=array('pay_month'=>date('m',strtotime($val)),'money'=>0);
                    }
                }
                //集客人数周趋势
                $map1['union_id']=array('in',$uids);
                $map1['type']=array('eq',3);
                foreach ($getSixDay as $kk=>$vv){
                    $map1['insert_time'] = ['between',[strtotime($vv.' 00:00:00'),strtotime($vv.' 23:59:59')]];
                    $data1=Db::name('qmjk_member')->where($map1)->field("FROM_UNIXTIME(insert_time,'%m-%d') days,count(id) count")->group('days')->find();
                    if(is_array($data1)){
                        $member[]=array('days'=>date('m-d',strtotime($vv)),'count'=>$data1['count']);
                    }else{
                        $member[]=array('days'=>date('m-d',strtotime($vv)),'count'=>0);
                    }
                }
            }

            $code = 1;
            $data = ['historyMoney'=>round($historyMoney,2),'historyMember'=>round($historyMember,2),'money'=>$money,'member'=>$member];
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 联盟商确认门店付款
     */
    public function unionPayConfirm(){
        $pay_number=input('param.pay_number');
        if($pay_number!=''){
            $res=Db::name('qmjk_order_pay')->where('pay_number',$pay_number)->update(['status'=>2]);
            if($res){
                $code = 1;
                $data = '';
                $msg = '结算金额收款确认成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '确认失败，请重试';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 消费和列表
     */
    public function ordersUser(){
        $uid=input('param.uid','');//当前用户id 店老板显示所有 小二显示自己管辖
        $bid=input('param.bid');
        $status=input('param.status');
        $union_id=input('param.union_id',0);
        if($bid){
            if($uid!=''){
                $getUidRole=Db::name('qmjk_member')->where('id',$uid)->value('role');
                if($getUidRole==0){
                    $getManageUnnion=Db::name('qmjk_union_relation')->where('fid',$uid)->column('union_id');
                    $map['jkm.union']=array('in',$getManageUnnion);
                }
            }
            $map['un.status'] = array('eq', 1);
            if($status) {
                $map['o.status'] = array('in', '1,2,3,7');
            }else{
                $map['o.status'] = array('not in', '-1,1,2,3,4,5,6,7');
            }
            if($union_id){
                $map['jkm.union_id']=array('eq',$union_id);
            }
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $map['jkm.branch_id'] = array('eq', $bid);
            $map['jkm.type'] = array('eq', 3);
            $count = Db::name('qmjk_member')->alias('jkm')->join('qmjk_union un','jkm.union_id=un.id','left')->join(['ims_bj_shopn_member' => 'm'], 'jkm.mobile=m.mobile', 'left')->join('qmjk_wx_user u','jkm.mobile=u.mobile','left')->join(['ims_bj_shopn_order' => 'o'], 'm.id=o.uid', 'left')->where($map)->group('o.uid')->field('jkm.id')->select();
            $allpage = intval(ceil(count($count) / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $info['count']=count($count);
            $list = Db::name('qmjk_member')->alias('jkm')->join('qmjk_union un','jkm.union_id=un.id','left')->join(['ims_bj_shopn_member' => 'm'], 'jkm.mobile=m.mobile', 'left')->join('qmjk_wx_user u','jkm.mobile=u.mobile','left')->join(['ims_bj_shopn_order' => 'o'], 'm.id=o.uid', 'left')->where($map)->field('m.id,un.title,jkm.name,jkm.mobile,u.nickname,u.avatar,count(o.id) orderTotal')->page($Nowpage, $limits)->select();
            if(!count($count)){
                $list=[];
            }else{
                if(!$status){
                    foreach ($list as $k=>$v){
                        $list[$k]['orderTotal']=0;
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
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //获取店内的联盟商
    public function allUnion(){
        $uid=input('param.uid','');//当前用户id 店老板显示所有 小二显示自己管辖
        $bid=input('param.bid');
        $union_id=input('param.union_id');
        if($uid!=''){
            $getUidRole=Db::name('qmjk_member')->where('id',$uid)->value('role');
            if($getUidRole==0){
                $map['r.fid']=array('eq',$uid);
            }
        }
        $map['union.status']=array('eq',1);
        $map['r.branch_id']=array('eq',$bid);
        $map['r.step']=array('eq',2);
        if($union_id!=''){
            $map['r.union_id']=array('eq',$union_id);
        }
        $list = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union union','r.union_id=union.id','left')->field('union.id,union.title')->where($map)->select();
        if(is_array($list) && count($list)){
            $code = 1;
            $data = $list;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂无数据';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 订单详情
     */
    public function payOrderList(){
        $uid=input('param.uid');
        if($uid!=''){
            $map['uid']=array('eq',$uid);
            $map['status']=array('in','1,2,3,7');
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count=Db::table('ims_bj_shopn_order')->where($map)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list=Db::table('ims_bj_shopn_order')->where($map)->field('id,ordersn,price,createtime,payTime')->page($Nowpage, $limits)->select();
            if(is_array($list) && count($list)){
                foreach ($list as $k=>$v){
                    $list[$k]['son']=Db::table('ims_bj_shopn_order_goods')->alias('og')->join(['ims_bj_shopn_goods' => 'g'], 'og.goodsid=g.id', 'left')->where('orderid',$v['id'])->field('g.thumb,g.title,og.price,og.total')->select();

                }
                $info['list']=$list;
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无数据';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //用户扫描联盟商二维码注册
    public function customerReg(){
        $mobile=input('param.mobile');
        $name=input('param.name');
        $age=input('param.age');
        $project=input('param.project');
        $bid=input('param.bid');
        $u_id=input('param.fid');//联盟商推广老板id
        if($mobile!='' || $name!='' || $age!='' || $project!='' || $u_id!='' || $bid!=''){
            //获取推广联盟商id
            $getUnionInfo=Db::name('qmjk_union')->alias('u')->join('qmjk_member m','u.id=m.union_id','left')->where('m.id',$u_id)->field('u.id,u.status')->find();
            //获取推广美容院信息
            $getInfo=Db::name('qmjk_branch')->field('id,sign,status')->where('id',$bid)->find();

            if($getUnionInfo['status']==0 || $getInfo['status']==2 || $getInfo['status']==0){
                $code = 0;
                $data = '';
                $msg = '错误，门店二维码已失效';
            }else {
                $map['mobile'] = array('eq', $mobile);
                $check = Db::name('qmjk_member')->where($map)->find();
                $check1 = Db::table('ims_bj_shopn_member')->where($map)->find();
                //如果是已存在电话号码 不允许加入
                if ($check || $check1) {
                    $code = 0;
                    $data = '';
                    $msg = '已存在用户，不允许重复加入';
                } else {
                    //获取店老板指定的美容师
                    $getSeller = Db::name('qmjk_branch_seller')->where('bid', $bid)->column('seller_id');
                    //根据美容院编码获取在系统中的门店id
                    $getStoreId =Db::table('ims_bwk_branch')->where('sign',$getInfo['sign'])->value('id');
                    //门店设置了美容师 则随机取一位 没有设置 那去他门店中随机拿一位
                    if(is_array($getSeller) && count($getSeller)){
                        $getRand=array_rand($getSeller);
                        $sellerId=$getSeller[$getRand];
                    }else{
                        $sellerId=Db::table('ims_bj_shopn_member')->where(['storeid'=>1])->where('','exp','LENGTH(code) >4 ')->order('id desc')->value('id');
                    }
                    Db::startTrans();
                    try{
                        $memData = array('weid' => 1, 'storeid' =>$getStoreId, 'pid' => $sellerId, 'staffid' => $sellerId, 'realname' => $name, 'mobile' => $mobile, 'createtime' => time(),'activity_flag'=>6666);
                        Db::table('ims_bj_shopn_member')->insertGetId($memData);
                        $memberData = array('branch_id' => $bid, 'union_id' => $getUnionInfo['id'], 'name' => $name, 'mobile' => $mobile,'age' => $age,'status' =>1,'project' => $project, 'type' => 3, 'insert_time' => time());
                        Db::name('qmjk_member')->insert($memberData);
                        Db::commit();
                        $code = 1;
                        $data = '';
                        $msg = '注册成功';
                    } catch (\Exception $e) {
                        Db::rollback();
                        $code = 0;
                        $data = '';
                        $msg = '注册失败，请重试！'.$e->getMessage();
                    }
                }
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 查看所有门店
     */
    public function getAllBranch(){
        $key=input('param.key');
        $map=[];
        if($key!=''){
            $map['title|address|sign']=array('like','%'.$key.'%');
        }
        $Nowpage = input('param.page') ? input('param.page') : 1;
        $limits = 10;// 显示条数
        $count =Db::name('qmjk_branch')->field('id,title,address')->count();
        $allpage = intval(ceil($count / $limits));
        if ($Nowpage >= $allpage) {
            $info['next_page_flag']=0;//是否有下一页
        }else{
            $info['next_page_flag']=1;
        }
        $list=Db::name('qmjk_branch')->where($map)->field('id,title,address')->page($Nowpage, $limits)->select();
        if(is_array($list) && count($list)){
            foreach ($list as $k=>$v){
                $list[$k]['union_num']=Db::name('qmjk_union_relation')->where('branch_id',$v['id'])->count();
                $list[$k]['member_num']=Db::name('qmjk_member')->where(['branch_id'=>$v['id'],'type'=>3])->count();
            }
            $info['list']=$list;
            $code =1;
            $data = $info;
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂无数据';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //用户上传
    public function upload(){
        try{
            // 返回qiniu上的文件名
            $image = QiniuUpload::image();
        }catch(\Exception $e) {
            echo json_encode(['status' => 0, 'message' => $e->getMessage()]);
        }
        if($image){
            $data = [
                'status' => 1,
                'message' => 'OK',
                'data' => config('qiniu.image_url').'/'.$image,
            ];
            echo json_encode($data);
            exit;
        }else {
            echo json_encode(['status' => 0, 'message' => '上传失败']);
        }
    }



    /*
     * 获取联盟商未结算金额
     */
    public function get_money($bid,$unionId){
        $unionInfo = Db::name('qmjk_union_relation')->where(['branch_id'=>$bid,'union_id'=>$unionId])->find();
        $total=0;
        $pay_role = json_decode($unionInfo['pay_role'], true);
        if(is_array($pay_role) && count($pay_role)) {
            if ($pay_role['roleType'] == 1) {
                $members = Db::name('qmjk_member')->alias('m')->where(['m.branch_id' => $bid, 'm.union_id' => $unionId, 'm.type' => 3])->field('m.id,m.id uid,m.insert_time time,m.name')->select();
                if (is_array($members) && count($members)) {
                    foreach ($members as $k => $v) {
                        $total += $this->get_refunds_money($pay_role, $k, $v);
                    }
                }
            } else {
                $getMemberId = Db::name('qmjk_member')->where(['branch_id' => $bid, 'union_id' => $unionId, 'type' => 3])->column('mobile');
                $map['m.mobile'] = array('in', $getMemberId);
                $map['order.status'] = array('in', '1,2,3,7');
                $map['order.qmjk_pay'] = array('eq', 0);
                $getOrder = Db::table('ims_bj_shopn_order')->alias('order')->join(['ims_bj_shopn_member' => 'm'], 'order.uid=m.id', 'left')->join('qmjk_member jkm', 'm.mobile=jkm.mobile', 'left')->where($map)->field('order.id,order.price,m.id uid')->select();
                if (is_array($getOrder) && count($getOrder)) {
                    foreach ($getOrder as $k => $v) {
                        $total += $this->get_refunds_money($pay_role, $k, $v);
                    }
                }
            }
            return $total;
        }else{
            return 0;
        }
    }



    /*
     * 获取返利金额
     */
    public function get_refunds_money($role,$key,$order){
        switch ($role['roleType']){
            case 1:
                //固定返款金额
                $money=$role['firstPrice']?$role['firstPrice']:0;
                break;
            case 2:
                //按订单首单次单计算价格
                if($key==0) {
                    $getFirstOrder = Db::name('qmjk_order_pay')->where('uid', $order['uid'])->count();
                    if ($getFirstOrder) {
                        $money = $role['otherPrice'] ? $role['otherPrice'] : 0;
                    } else {
                        $money = $role['firstPrice'] ? $role['firstPrice'] : 0;
                    }
                }else{
                    $money = $role['otherPrice'] ? $role['otherPrice'] : 0;
                }
                break;
            case 3:
                //按订单折扣
                $money=$order['price']*($role['firstPrice']/100);
                break;
            default:
                $money=0;
        }
        return round($money,2);
    }

    /*
     * 个人中心消息列表
     */
    public function messageList(){
        $id=input('param.id');
        if($id!=''){
            $Nowpage = input('param.page') ? input('param.page') : 1;
            $limits = 10;// 显示条数
            $count = Db::name('qmjk_message')->where('getuid',$id)->count();
            $allpage = intval(ceil($count / $limits));
            if ($Nowpage >= $allpage) {
                $info['next_page_flag']=0;//是否有下一页
            }else{
                $info['next_page_flag']=1;
            }
            $list =Db::name('qmjk_message')->where('getuid',$id)->page($Nowpage, $limits)->select();
            if (is_array($list) && count($list)) {
                foreach ($list as $k => $v) {
                    $list[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
                }
                $info['list']=$list;
                $code = 1;
                $data = $info;
                $msg = '数据获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无消息';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 美容院联盟报告
     */
    public function branchReport(){
        $branch_id=input('param.bid');
        if($branch_id!=''){
            $branchInfo=Db::name('qmjk_branch_report')->where('branch_id',$branch_id)->find();
            if(!$branchInfo){
                $branchInfo=array('id'=>'','branch_id'=>intval($branch_id),'union_total'=>0,'customer_total'=>0,'customer_pay_total'=>0,'customer_order_total'=>0,'order_avg'=>0,'conversion_rate'=>0,'report_date'=>date('Y-m-d',strtotime("-1 day")));
            }
            $getSixDay=$this->to_sex_day();//获取前6天日期
            //加盟商加入七日数据
            $map['branch_id']=array('in',$branch_id);
            foreach ($getSixDay as $kk=>$vv){
                $map['insert_time'] = ['between',[strtotime($vv.' 00:00:00'),strtotime($vv.' 23:59:59')]];
                $data1=Db::name('qmjk_union_relation')->where($map)->field("FROM_UNIXTIME(insert_time,'%m-%d') days,count(id) count")->group('days')->find();
                if(is_array($data1)){
                    $unions[]=array('days'=>date('m-d',strtotime($vv)),'count'=>$data1['count']);
                }else{
                    $unions[]=array('days'=>date('m-d',strtotime($vv)),'count'=>0);
                }
            }
            //集客人数七日数据
            $map1['branch_id']=array('in',$branch_id);
            $map1['type']=array('eq',3);
            foreach ($getSixDay as $kk=>$vv){
                $map1['insert_time'] = ['between',[strtotime($vv.' 00:00:00'),strtotime($vv.' 23:59:59')]];
                $data1=Db::name('qmjk_member')->where($map1)->field("FROM_UNIXTIME(insert_time,'%m-%d') days,count(id) count")->group('days')->find();
                if(is_array($data1)){
                    $member[]=array('days'=>date('m-d',strtotime($vv)),'count'=>$data1['count']);
                }else{
                    $member[]=array('days'=>date('m-d',strtotime($vv)),'count'=>0);
                }
            }
            //获取门店下联盟商列表 按周
            $unionList=[];
            $week=Db::name('qmjk_union_report')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['branch_id'=>$branch_id])->field('u.title,r.branch_id,r.union_id,r.week_customer count')->order('count desc')->select();
            $unionList['week']=$week;
            //获取门店下联盟商列表 按月
            $month=Db::name('qmjk_union_report')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['branch_id'=>$branch_id])->field('u.title,r.branch_id,r.union_id,r.month_customer count')->order('count desc')->select();
            $unionList['month']=$month;
            //获取门店下联盟商列表 按年
            $year=Db::name('qmjk_union_report')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['branch_id'=>$branch_id])->field('u.title,r.branch_id,r.union_id,r.year_customer count')->order('count desc')->select();
            $unionList['year']=$year;

            $code = 1;
            $data = ['branchInfo'=>$branchInfo,'unionInfo'=>$unions,'memberInfo'=>$member,'unionRank'=>$unionList];
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 门店下联盟商列表报告
     */
    public function branchUnionReport(){
        $branch_id=input('param.branch_id');
        $order=input('param.order',1);
        if($branch_id!=''){
            $branchInfo=Db::name('qmjk_branch_report')->where('branch_id',$branch_id)->find();
            if(!$branchInfo){
                $branchInfo=array('id'=>'','branch_id'=>intval($branch_id),'union_total'=>0,'customer_total'=>0,'customer_pay_total'=>0,'customer_order_total'=>0,'order_avg'=>0,'conversion_rate'=>0,'report_date'=>date('Y-m-d',strtotime("-1 day")));
            }
            if($order==1){
                $unionRank=Db::name('qmjk_union_report')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['branch_id'=>$branch_id])->field('u.id,u.title,r.conversion_rate,r.order_avg')->order('conversion_rate desc')->select();
            }else{
                $unionRank=Db::name('qmjk_union_report')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['branch_id'=>$branch_id])->field('u.id,u.title,r.conversion_rate,r.order_avg')->order('order_avg desc')->select();
            }
            $branchInfo['union_count']=count($unionRank);
            $branchInfo['union']=$unionRank;
            $code = 1;
            $data = $branchInfo;
            $msg = '数据获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 联盟商消费记录报告
     */
    public function unionOrderReport(){
        $branch_id=input('param.branch_id');
        $union_id=input('param.union_id');
        if($branch_id!='' && $union_id!=''){
            $unionName=Db::name('qmjk_union')->where('id',$union_id)->value('title');
            if ($unionName) {
                $unionInfo = Db::name('qmjk_union_report')->where(['branch_id' => $branch_id, 'union_id' => $union_id])->field('conversion_rate')->find();
                $where['jkm.branch_id'] = array('eq', $branch_id);
                $where['jkm.union_id'] = array('eq', $union_id);
                $where['o.status'] = array('in', '1,2,3,7');
                $where['jkm.type'] = array('eq', 3);
                $order = Db::name('qmjk_member')->alias('jkm')->join(['ims_bj_shopn_member' => 'm'], 'jkm.mobile=m.mobile', 'left')->join(['ims_fans f'], 'm.mobile=f.mobile', 'left')->join(['ims_bj_shopn_order' => 'o'], 'm.id=o.uid', 'left')->where($where)->field('m.id,f.avatar,f.nickname,o.price,o.createtime')->select();
                if (is_array($order) && count($order)) {
                    $member = [];
                    $total = 0;
                    foreach ($order as $k => $v) {
                        if (!in_array($v['id'], $member)) {
                            $member[] = $v['id'];
                        }
                        $total += $v['price'];
                        $order[$k]['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
                    }
                }
                $unionInfo['member_count'] = count($member);
                $unionInfo['pay_total'] = $total;
                $unionInfo['pay_avg'] = $total / count($member);
                $unionInfo['title'] = $unionName;
                $unionInfo['list'] = $order;
                $code = 1;
                $data = $unionInfo;
                $msg = '数据获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '没有该联盟商信息';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
     * 联盟商集客人数报告
     */
    public function unionCustomerReport(){
        $branch_id=input('param.branch_id');
        $union_id=input('param.union_id');
        $date=input('param.date');
        if($branch_id!='' && $union_id!=''){
            $unionInfo=Db::name('qmjk_union')->where(['id'=>$union_id])->field('title')->find();
            if($unionInfo) {
                switch ($date) {
                    case 'week':
                        $customer = Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user user', 'm.mobile=user.mobile', 'left')->where(['m.branch_id' => $branch_id, 'm.union_id' => $union_id, 'm.type' => 3])->whereTime('m.insert_time', 'week')->field('user.avatar,user.nickname,insert_time')->order('insert_time desc')->select();
                        break;
                    case 'month':
                        $customer = Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user user', 'm.mobile=user.mobile', 'left')->where(['m.branch_id' => $branch_id, 'm.union_id' => $union_id, 'm.type' => 3])->whereTime('m.insert_time', 'month')->field('user.avatar,user.nickname,insert_time')->order('insert_time desc')->select();
                        break;
                    case 'year':
                        $customer = Db::name('qmjk_member')->alias('m')->join('qmjk_wx_user user', 'm.mobile=user.mobile', 'left')->where(['m.branch_id' => $branch_id, 'm.union_id' => $union_id, 'm.type' => 3])->whereTime('m.insert_time', 'year')->field('user.avatar,user.nickname,insert_time')->order('insert_time desc')->select();
                        break;
                }
                if (is_array($customer) && count($customer)) {
                    foreach ($customer as $k => $v) {
                        $customer[$k]['insert_time'] = date('Y-m-d H:i:s', $v['insert_time']);
                    }
                }
                $unionInfo['member_count'] = count($customer);
                $unionInfo['data'] = $date;
                $unionInfo['list'] = $customer;
                $code = 1;
                $data = $unionInfo;
                $msg = '数据获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '没有该联盟商信息';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '数据提交不完整！请检查';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    public function  send_message($data,$type=0,$mobile='',$senArr=[],$modelId=0){
        if(is_array($data)){
            $data['insert_time']=time();
            Db::name('qmjk_message')->insert($data);
        }
        if($type){
            sendMessage($mobile,$senArr,$modelId);
        }
    }


    //获取前6个月月份
    public  function to_sex_month(){
        $today = input('param.today') ? input('param.today') : date("Y-m-d");
        $arr = array();
        $old_time = strtotime('-6 month',strtotime($today));
        for($i = 0;$i <= 6; ++$i){
            $t = strtotime("+$i month",$old_time);
            $arr[]=date('Y-m',$t);
        }
        return $arr;
    }

    //获取前6个天日期
    public  function to_sex_day(){
        $today = input('param.today') ? input('param.today') : date("Y-m-d");
        $arr = array();
        $old_time = strtotime('-6 day',strtotime($today));
        for($i = 0;$i <= 6; ++$i){
            $t = strtotime("+$i day",$old_time);
            $arr[]=date('Y-m-d',$t);
        }
        return $arr;
    }

}