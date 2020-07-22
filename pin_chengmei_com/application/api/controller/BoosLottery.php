<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:老板抽奖
 */
class BoosLottery extends Base
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


    public function newTicketList()
    {
        $mobile=input('param.mobile');
        $type=input('param.type');
        $status=input('param.status');
        if($mobile!=''){
            $arr['ticket.mobile']=array('eq',$mobile);
            if($type!='') {
                $arr['ticket.type'] = array('eq', $type);
            }else{
//                $arr['ticket.type']=array('neq','9');//剔除
                $arr['ticket.type']=array('not in',['9','23','24','27']);//剔除
            }
            if($status==2){
                $arr['ticket.status']=array('in','2,3');
            }else{
                $arr['ticket.status']=array('in','-1,0,1');
            }
            $getTicket=Db::name('ticket_user')->alias('ticket')
                ->field('ticket.id,s.scene_name,ticket_code,order_sn,type,par_value,status,flag,draw_rank,draw_name,s.image3 draw_pic,ticket.qrcode,ticket.aead_time,ticket_num,remark,goods_id,ticket.draw_pic draw_pic1,ticket.insert_time,share_status')->join('pt_draw_scene s','ticket.type=s.scene_prefix','left')
                ->where($arr)
                ->order('ticket.insert_time desc')
                ->select();
            if(count($getTicket) && is_array($getTicket)){
                foreach ($getTicket as $k=>$v){
                    $getTicket[$k]['qrcode'] =$v['qrcode'];
                    $getTicket[$k]['draw_rank'] =$v['draw_rank']?$v['draw_rank']:'';
                    $getTicket[$k]['draw_name'] =$v['draw_name']?$v['draw_name']:'';
                    $getTicket[$k]['draw_pic'] =$v['draw_pic']?$v['draw_pic']:'';
                    $getTicket[$k]['order_sn'] =$v['order_sn']?$v['order_sn']:'';
                    $aeadTime=date('n月d日',$v['aead_time']);
                    $beginTime=date('n月1日',$v['aead_time']);
                    if($v['status']==3){
                        $tips='已失效';
                    }elseif($v['status']==1 || $v['status']==2){
                        $tips='已使用';
                    }else{
                        if($v['type']==28){
                            $insert_time=strtotime($v['insert_time']);
                            $tips='有效期：'.date('n月d日',$insert_time)." ～ ".date("n月d日",strtotime("+1 month",$insert_time));;
                        }else{
                            $tips='有效期：'.$beginTime." ～ ".$aeadTime;
                        }
                    }
                    $good_num=0;
                    $share_flag=1;
                    if($v['type']==18 ||$v['type']==19){
                        if($v['type']==18){
                            $getInfo=Db::name('activity_order_info')->alias('info')
                                ->join('goods g','info.good_id=g.id','left')
                                ->where(['order_sn'=>$v['order_sn'],'main_flag'=>1])
                                ->field('info.good_num,g.images,g.name')
                                ->find();
                        }else{
                            $getInfo=Db::name('activity_order_info')->alias('info')
                                ->join('goods g','info.good_id=g.id','left')
                                ->where(['order_sn'=>$v['order_sn'],'good_id'=>$v['goods_id']])
                                ->field('info.good_num,g.images,g.name')
                                ->find();
                            $share_flag=2;
                        }
                        $getTicket[$k]['scene_name'] =$getInfo['name']?$getInfo['name']:'';
                        $getPic=strtok($getInfo['images'], ',');
                        $getTicket[$k]['draw_pic'] =$getPic?$getPic:'';
                        $good_num =$getInfo['good_num'];
                    }
                    //宏伟门店个性化产品 || 618
                    if($v['type']==20 || $v['type']==28){
                        $getTicket[$k]['scene_name'] =$v['draw_name']?$v['draw_name']:$v['scene_name'];
                        $getTicket[$k]['draw_pic'] = $v['draw_pic1'];
                        if($v['qrcode']==false){
                            $codeUrl =pickUpCode('activate_'.$v['ticket_code']);
                            $getTicket[$k]['qrcode'] = $codeUrl;
                            Db::name('ticket_user')
                                ->where('id', $v['id'])
                                ->update(['qrcode' => $codeUrl]);
                        }
                    }
                    $getTicket[$k]['other']['good_num'] =$good_num;
                    $getTicket[$k]['other']['par_value'] =$v['par_value'];
                    $getTicket[$k]['other']['remark'] =$v['remark']?$v['remark']:'';
                    $getTicket[$k]['other']['aead_time'] =$tips;
                    $getTicket[$k]['other']['ticket_num'] =$v['ticket_num'];
                    $getTicket[$k]['other']['ticket_use']=Db::name('activity_order_sharing')->where(['ticket_sn'=>$v['ticket_code'],'accept_flag'=>1,'sharing_flag'=>$share_flag])->sum('num');
                    unset($getTicket[$k]['par_value'],$getTicket[$k]['aead_time'],$getTicket[$k]['ticket_num'],$getTicket[$k]['remark'],$getTicket[$k]['draw_pic1']);
                    if($v['type']==22){
                        if($v['status']==0){
                            $getTicket[$k]['other']['remark'] ='券号:'.$v['ticket_code'];
                        }
                        if($v['status']==2 && $v['flag']==0 ){
                            $getTicket[$k]['other']['remark'] ='未中奖';
                        }
                        if($v['flag']){
                            $getTicket[$k]['other']['remark'] ='已中奖:'.$v['draw_name'];
                        }
                    }
                }
                $code = 1;
                $data = $getTicket;
                $msg = '获取卡券成功！';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂时没有卡券！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //分享券
    public function shareTicket(){
        $ticket=input('param.ticket');
        if($ticket =='') {
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }else{
            $ticketInfo = Db::name('ticket_user')->alias('t')
                ->join(['ims_bj_shopn_member' => 'm'], 't.mobile=m.mobile', 'left')
                ->field('share_status,parent_owner,m.id,m.realname,m.mobile')
                ->where(['ticket_code' => $ticket])
                ->find();
            if ($ticketInfo) {
                if($ticketInfo['share_status']>=1){
                    $code = 0;
                    $data = '';
                    $msg = '卡券已分享，等待好友接收！';
                }else{
                    $res=Db::name('ticket_user')
                        ->where(['ticket_code' => $ticket])
                        ->update(['share_status' => 1,'update_time'=>date('Y-m-d H:i:s'), 'share_time' => time()]);
                    if($res) {
                        $arr=array('uid'=>$ticketInfo['id'],'title'=>'卡券分享通知','content'=>"您把券号为".$ticket."的卡券，分享给了您的好友，目前好友暂未接收，如超过24小时好友未接收，卡券还自动退还至您的卡券包，请知晓！");
                        sendDrawQueue($arr);
                        sendQueue($ticket, $ticket . '由用户'.$ticketInfo['realname'].$ticketInfo['mobile'].'分享给了微信好友 等待朋友接收卡券' );
                    }
                    $code = 1;
                    $data = '';
                    $msg = '卡券分享成功 等待好友领取！';
                }
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //顾客接收美容师发来的卡券
    public function customerGetTicket(){
        $userid=input('param.userid');
        $ticket=input('param.ticket');
        if($userid=='' || $ticket =='') {
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }else {
            $ticketInfo = Db::name('ticket_user')->alias('t')
                ->join(['ims_bj_shopn_member' => 'm'], 't.mobile=m.mobile', 'left')
                ->field('share_status,parent_owner,m.id,m.realname,m.mobile,m.storeid')
                ->where(['ticket_code' => $ticket])
                ->find();
            $userInfo=Db::table('ims_bj_shopn_member')
                ->field('realname,mobile,storeid,isadmin,code')
                ->where('id',$userid)
                ->find();
            if ($ticketInfo) {
                if($userInfo['storeid']==$ticketInfo['storeid']) {
                    //禁止店老板或者美容师领取
                    if (strlen($userInfo['code']) || $userInfo['isadmin']) {
                        $code = 0;
                        $data = '';
                        $msg = '该券只允许终端顾客领取哦';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    //禁止自己领取
                    if ($ticketInfo['mobile'] == $userInfo['mobile']) {
                        $code = 0;
                        $data = '';
                        $msg = '自己不能领取自己发送的券哦';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    //判断该券是否被领取
                    if ($ticketInfo['share_status'] == 2) {
                        $code = 0;
                        $data = '';
                        $msg = '您下手晚了，该卡券已被人领走啦！';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    //判断该券是否超过24小时被收回
                    if ($ticketInfo['share_status'] == 0 ) {
                        $code = 0;
                        $data = '';
                        $msg = '您下手晚了，该卡券已超过24小时未领取，已失效！';
                        return parent::returnMsg($code, $data, $msg);
                    }
                    //接受卡券
                    Db::startTrans();
                    try {
                        $parents = $ticketInfo['parent_owner'] . ',' . $userid;
                        //将该卡券状态置为以分享并被接收
                        Db::name('ticket_user')
                            ->where(['ticket_code' => $ticket])
                            ->update(['mobile'=>$userInfo['mobile'],'share_status' => 2, 'share_time' => time(),'update_time'=>date('Y-m-d H:i:s'), 'remark' => '','parent_owner' => $parents]);
                        $arr=array('uid'=>$userid,'title'=>'卡券分享通知','content'=>"您接收了好友发给您的1张护理券，卡券号码为：".$ticket."，请至我的卡券中查看！");
                        sendDrawQueue($arr);
                        sendQueue($ticket, $ticket . '由用户' . $ticketInfo['mobile'] . '分享的卡券，已被' . $userInfo['mobile'] . '接收');
                        $arr=array('uid'=>$ticketInfo['id'],'title'=>'卡券分享通知','content'=>"您分享的卡券号为".$ticket."的奖卡券，已被用户".$userInfo['realname'].$userInfo['mobile']."成功领取，请知晓！");
                        sendDrawQueue($arr);
                        Db::commit();
                        $code = 1;
                        $data = '';
                        $msg = '卡券领取成功！';
                    } catch (\Exception $e) {
                        Db::rollback();
                        $code = 0;
                        $data = '';
                        $msg = '卡券领取失败！';
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '奖券不能跨店分享哦';
                }
            }else{
                $code = 0;
                $data = '';
                $msg = '卡券不存在！';
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }



    //回滚美容师顾客发出未领的券
    public function ticket_roolback(){
        $curTime=time();
        $map['status']=array('eq',0);
        $map['share_status']=array('eq',1);
        $map['type']=array('eq',28);
        $shareTickets=Db::name('ticket_user')
            ->field('id,share_time,ticket_code,mobile,share_status')
            ->where($map)
            ->limit(50)
            ->select();
        if(count($shareTickets)){
            foreach ($shareTickets as $k=>$v){
                $getUserId=Db::table('ims_bj_shopn_member')->where('mobile',$v['mobile'])->value('id');
                if(($curTime-$v['share_time'])>=86400){
                    if($v['share_status']==1){
                        $res=Db::name('ticket_user')
                            ->where(['id' => $v['id']])
                            ->update(['share_status' => 0,'update_time'=>date('Y-m-d H:i:s'), 'share_time' => '']);
                        if ($res) {
                            $arr=array('uid'=>$getUserId,'title'=>'卡券分享通知','content'=>"您分享的卡券号为".$v['ticket_code']."的卡券，因超过24小时未被领取，成功退回，请知晓！");
                            sendDrawQueue($arr);
                            sendQueue($v['ticket_code'], $v['ticket_code'] . '由用户' . $v['mobile'] . '分享的卡券，超过24小时未被人领取，状态回滚');
                        }
                    }
                }
            }
        }
    }

    //为分享类型的券 效期一周前发送短信提醒
    public function ticket_tips(){
        $curTime=time();
        $map['t.status']=array('eq',0);
        $map['t.share_status']=array('eq',0);
        $map['t.remark']=array('eq','可转赠券');
        $map['t.type']=array('eq',28);
        $map['t.flag']=array('eq',0);
        $shareTickets = Db::name('ticket_user')->alias('t')
            ->join(['ims_bj_shopn_member' => 'm'], 't.mobile=m.mobile', 'left')
            ->field('t.id,share_time,t.ticket_code,t.mobile,t.share_status,t.insert_time,m.id mid')->where($map)
            ->limit(50)
            ->select();
        if(count($shareTickets)){
            foreach ($shareTickets as $k=>$v){
                $end_date=strtotime("+1 month",strtotime($v['insert_time']));
                $send_date=strtotime("-7 days",$end_date);
                if($curTime>$send_date){
                    Db::name('ticket_user')->where('id',$v['id'])->update(['flag'=>10]);
                    $znx=array('uid'=>$v['mid'],'title'=>'卡券分享通知','content'=>"您有一张闺蜜券即将过期！避免失效请尽快去卡券包进行分享好友一起体验胎盘蛋白抗衰尊享护理!",'insert_time' => time());
                    $arr1 = array('mail_param' => $znx, 'sms_param' => array('smsId' => 126, 'param' => array('mobile' => $v['mobile'])));
                    sendCommQueue($arr1, 0);
                }
            }
        }
    }

	
}