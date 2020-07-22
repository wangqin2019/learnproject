<?php

namespace app\api\controller;
use think\Controller;
use think\Db;
header('Access-Control-Allow-Origin:*');
/**
 * desc:h5页面调用
 */
class H5 extends Base
{
    //获取抽奖产品,如果门店自己有个性化抽奖产品，就用门店设置的，如没有，就用公司的
    public function lucky_goods(){
        $storeid=input('param.storeid',0);
        $getDrawIds='';
        if($storeid){
            $getDrawIds=Db::name('activity_branch_draw')->where('storeid',$storeid)->value('draw_ids');
        }
        $type=input('param.type',0);
        $map['goods_cate']=array('eq',6);
        $map['status']=array('eq',1);
        if(strlen($getDrawIds)){
            $map['id']=array('in',$getDrawIds);
        }else{
            $map['is_tryout']=array('eq',$type);
            $map['storeid']=array('eq',0);
        }
        $list=Db::name('goods')->where($map)->field('id,name,image')->select();
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

    //抽奖
    public function get_lucky()
    {
        $uid=input('param.uid');
        $type=input('param.type',0);
        $order_sn=input('param.order_sn');
        if($uid!='' && $order_sn!='') {
            $check = Db::name('order_lucky')->where('order_sn', $order_sn)->count();
            if ($check) {
                $code = 0;
                $data = '';
                $msg = '该订单号已经参与过抽奖！';
                return parent::returnMsg($code,$data,$msg);
            } else {
                $orderInfo=Db::name('activity_order')->where('order_sn',$order_sn)->find();
                if($orderInfo) {
                    $getDrawIds = Db::name('activity_branch_draw')->where('storeid', $orderInfo['storeid'])->value('draw_ids');
                    //个性化抽奖 门店有配置用门店 无配置 用总部配置
                    if (strlen($getDrawIds)) {
                        $map['goods_cate'] = array('eq', 6);
                        $map['status'] = array('eq', 1);
                        $map['stock'] = array('gt', 0);
                        $map['id'] = array('in', $getDrawIds);
                        $list = Db::name('goods')->field('id,name,stock')->where($map)->select();
                        foreach ($list as $key => $val) {
                            $arr[$val['id']] = $val['stock'];
                        }
                        $lucky_id = getRand($arr); //根据概率获取奖品id
                    } else {
                        if ($orderInfo['flag']) {
                            //新修改订单格式
                            $getGoods = Db::name('activity_order_info')->alias('info')->join('goods g', 'info.good_id=g.id', 'left')->where(['info.order_sn' => $order_sn, 'info.main_flag' => 1])->field('g_draw_type,good_id')->find();
                            $draw_type = $getGoods['g_draw_type'];
                            $draw_goods_id = $getGoods['good_id'];
                        } else {
                            $draw_type = Db::name('goods')->where('id', $orderInfo['pid'])->value('g_draw_type');
                            $draw_goods_id = $orderInfo['pid'];
                        }
                        if ($draw_type == 2) {
                            $storeid = $orderInfo['storeid'];
                            $orderCount = Db::name('activity_order')->where(['storeid' => $storeid, 'pay_status' => 1, 'pid' => 78])->order('id')->count();
                            $lucky_id = $this->get_lucky_goods(78, $storeid, $orderCount);
                        } else {
                            $map['goods_cate'] = array('eq', 6);
                            $map['status'] = array('eq', 1);
                            $map['stock'] = array('gt', 0);
                            $map['is_tryout'] = array('eq', 0);
                            $map['storeid'] = array('eq', 0);
                            $list = Db::name('goods')->field('id,name,stock')->where($map)->select();
                            foreach ($list as $key => $val) {
                                $arr[$val['id']] = $val['stock'];
                            }
                            $lucky_id = getRand($arr); //根据概率获取奖品id
                        }
                    }
                    $luckyInfo = Db::name('goods')->field('id,name,image,type,ticket_id')->where('id', $lucky_id)->find();
                    if (count($luckyInfo) && is_array($luckyInfo)) {
                        try {
                            $check1 = Db::name('order_lucky')->where('order_sn', $order_sn)->count();
                            if (!$check1) {
                                $qrcode = pickUpCode('lucky_' . $order_sn);
                                $insert = array('uid' => $uid, 'order_sn' => $order_sn, 'lucky_name' => $luckyInfo['name'], 'lucky_image' => $luckyInfo['image'], 'qrcode' => $qrcode, 'insert_time' => time());
                                Db::name('order_lucky')->insert($insert);
                                Db::name('goods')->where('id', $luckyInfo['id'])->setDec('stock');
                                //如果抽中的礼券 要发送至用户卡包
//                            if($luckyInfo['type']==1 && $luckyInfo['ticket_id']>0){
//                                $ticketInfo=Db::name('draw_scene')->where('scene_prefix',$luckyInfo['ticket_id'])->field('scene_name,image1')->find();
//                                $send=sendTicket($uid,$luckyInfo['ticket_id'],$ticketInfo['image1']);
//                                if($send){
//                                    //发站内信
//                                    $content="恭喜您获得".$ticketInfo['scene_name']."，请至我的卡券中查看！";
//                                    $arr = array('uid' => $uid, 'title' => '幸运抽奖通知', 'content' => $content);
//                                    sendDrawQueue($arr);
//                                }
//                            }else{
                                if ($lucky_id != 80) {
                                    $content = "恭喜您获得" . $luckyInfo['name'] . "，请至订单列表中查看，联系所属美容师领取";
                                    $arr = array('uid' => $uid, 'title' => '幸运抽奖通知', 'content' => $content);
                                    sendDrawQueue($arr);
                                }
//                            }
                                $code = 1;
                                $data = $luckyInfo;
                                $msg = '抽奖成功！';
                            } else {
                                $code = 0;
                                $data = '';
                                $msg = '该订单号已经参与过抽奖！';
                            }
                        } catch (\Exception $e) {
                            $code = 0;
                            $data = '';
                            $msg = '错误 ' . $e->getMessage();
                        }
                    } else {
                        $code = 0;
                        $data = '';
                        $msg = '抽奖产品获取失败！';
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '单号错误！';
                }
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //获取中奖号码
    public function get_lucky_goods($pid,$storeid,$count){
        //订单量等于1 或者每百单的第一单生成幸运订单号
        if($count==1 || $count%100==0) {
            $endNum = $count == 1 ? 100 - 1 : ($count + 100) - 1;
            $getLuckNum = $this->NoRand($count, $endNum, 10);
            if ($getLuckNum) {
                $luckNums = implode(',', $getLuckNum);
                parent::hashSet('underwear_lucky', $storeid, $luckNums);
                $luckData = ['storeid' => $storeid, 'pid' => $pid, 'order_between' => $count . '-' . $endNum, 'lucky_num' => $luckNums, 'insert_time' => time()];
                Db::name('underwear_lucky')->insert($luckData);
            }
        }

        $getLuckNum = parent::hashGet('underwear_lucky', $storeid);
        if ($getLuckNum) {
            $luckNum = $getLuckNum;
        } else {
            $map['storeid'] = array('eq', $storeid);
            $map['pid'] = array('eq', $pid);
            $luckNum = Db::name('underwear_lucky')->where($map)->order('id desc')->value('lucky_num');
            if($luckNum){
                parent::hashSet('underwear_lucky', $storeid, $luckNum);
            }
        }
        if($luckNum){
            $luckNumArr=explode(',',$luckNum);
            if(in_array($count,$luckNumArr)){
                $getIndex=array_search($count,$luckNumArr);
                switch ($getIndex){
                    case $getIndex<7:
                        return 82;
                        break;
                    case $getIndex==7:
                        return 81;
                        break;
                    case $getIndex==8:
                        return 83;
                        break;
                    case $getIndex==9:
                        return 84;
                        break;
                    default:
                        return 80;
                }
            }else{
               return 80;
            }
        }else{
            return 80;
        }
    }


    //随机中奖号码
    public function NoRand($begin=0,$end=20,$limit=10){
        $rand_array=range($begin,$end);
        shuffle($rand_array);//调用现成的数组随机排列函数
        return array_slice($rand_array,0,$limit);//截取前$limit个
    }



    //查看我的印花
    public function my_flower(){
        $uid=input('param.uid');
        //如果印花数量等于3枚 转化成双清券
        $cardCount=Db::name('card_upgrade')->where(['uid'=>$uid,'flag'=>0,'type'=>1])->count();
        $uidInfo=Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.mobile,member.storeid,bwk.title,bwk.sign,depart.st_department')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->where('member.id', $uid)->find();
        if($cardCount>=3){
            $flowerNum=floor($cardCount/3);
            for ($i=0;$i<$flowerNum;$i++){
               //如果印花数量等于3枚 转化成双清券
                $cardInfo=Db::name('card_upgrade')->where(['uid'=>$uid,'flag'=>0,'type'=>1])->limit(3)->order('id')->column('id');
                $ticket_code=time() .$uid. rand(11,99);
                $ticketList['depart'] = $uidInfo['st_department'];
                $ticketList['branch'] = $uidInfo['title'];
                $ticketList['sign'] = $uidInfo['sign'];
                $ticketList['mobile'] = $uidInfo['mobile'];
                $ticketList['storeid'] = $uidInfo['storeid'];
                $ticketList['insert_time'] = date('Y-m-d H:i:s');
                $ticketList['update_time'] = date('Y-m-d H:i:s');
                $ticketList['par_value'] = 0;
                $ticketList['status'] = 0;
                $ticketList['ticket_code'] =$ticket_code ;
                $ticketList['type'] = 12;
                $ticketList['source'] = 1;
                $ticketList['draw_pic'] = config("transfer_ticket.shuangqing_0");
                $ticketList['qrcode'] = pickUpCode('activate_' .$ticket_code);
                $insert=Db::name('ticket_user')->insert($ticketList);
                if($insert){
                    //更改印花状态
                    Db::name('card_upgrade')->where('id','in',$cardInfo)->update(['flag'=>1,'compose'=>$ticket_code,'update_time'=>time()]);
                    //发券
                    $content="恭喜，系统已自动帮您合成3张印花券并生成价值198元深层清洁护理券一张，请至我的卡券中查看！";
                    $arr = array('uid' => $uidInfo['id'], 'title' => '卡券合成通知', 'content' => $content);
                    sendDrawQueue($arr);
                    //记录日志
                    sendQueue($ticket_code,$ticket_code.'分配给'.$uidInfo['st_department'].$uidInfo['title'].$uidInfo['sign'].'下的'.$uidInfo['mobile']);
                    //app消息推送
                    //$this->app_message($uidInfo['mobile'],$content);
                }
            }
        }
        $ticketNum=Db::name('ticket_user')->where(['mobile'=>$uidInfo['mobile'],'type'=>12,'source'=>1])->count();
        $cardCount=Db::name('card_upgrade')->where(['uid'=>$uid,'flag'=>0,'type'=>1])->count();
        return parent::returnMsg(1,['ticketNum'=>$ticketNum,'cardCount'=>$cardCount],'获取成功');
    }

    //app极光推送
//    public function app_message($mobile,$content){
//        if($mobile){
//            $data=array('type'=>4,'pushtype'=>'alias','target'=>$mobile,'content'=>$content);
//            $url="https://api-app.qunarmei.com/qunamei/pushmessage";
//            http_post($url,$data);
//        }
//    }



}