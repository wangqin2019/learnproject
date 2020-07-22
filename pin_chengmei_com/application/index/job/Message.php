<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2018/8/9
 * Time: 16:20
 */

namespace app\index\job;
use app\api\model\PintuanModel;
use think\Controller;
use think\Db;
use think\queue\job;

class Message extends Controller
{
    public function _initialize()
    {
        $config = cache('db_config_data');

        if(!$config){
            $config = load_config();
            cache('db_config_data',$config);
        }
        config($config);
    }
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data){
        // 如有必要,可以根据业务需求和数据库中的最新数据,判断该任务是否仍有必要执行.
//        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
//        if(!$isJobStillNeedToBeDone){
//            $job->delete();
//            return;
//        }

        $isJobDone = $this->doMessageJob($data);

        if ($isJobDone) {
            //如果任务执行成功， 记得删除任务
            $job->delete();
            print(date('Y-m-d H:i:s')."：message发送记录：".json_encode($data)." send ok\n");
        }else{
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                //print("<warn>Hello Job has been retried more than 3 times!"."</warn>\n");
                $job->delete();
                // 也可以重新发布这个任务
                //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
                //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
            }
        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function doMessageJob($data) {
        // 根据消息中的数据进行实际的业务处理...
            $scene=$data['scene'];
            if($scene=='draw'){
                unset($data['scene']);
                Db::name('member_message')->insert($data);
            }elseif($scene=='comm'){
                unset($data['scene']);
                if($data['flag']==1){
                    Db::name('member_message')->insert($data['mail_param']);
                }elseif ($data['flag']==2){
                    $this->sendMsgByChuanglan($data['sms_param']['param'],$data['sms_param']['smsId']);
                }else{
					$this->sendMsgByChuanglan($data['sms_param']['param'],$data['sms_param']['smsId']);
                    Db::name('member_message')->insert($data['mail_param']);
                }
            }else {
                $joinUid = $data['joinUid'];
                $typeArr = explode(',', $data['type']);
                $roleArr = explode(',', $data['role']);

                //获取该拼团信息
                $pt = new PintuanModel();
                $ptInfo = $pt->getPtInfo(['id' => $data['tid']]);
                $tinfo = $pt->getJoinTuanInfo($data['tid']);
                $shareInfo = Db::table('ims_bj_shopn_member')->field('realname,mobile')->where(['id' => $ptInfo['share_uid']])->find();
                $tinfo['seller'] = $shareInfo['realname'];

                //获取店老板id
                $boosinfo = Db::table('ims_bj_shopn_member')->field('id,mobile')->where(['storeid' => $ptInfo['storeid'], 'isadmin' => 1])->find();
                if (in_array('1', $roleArr)) {
                    $this->sendMessage('1', $boosinfo['id'], $boosinfo['mobile'], $typeArr, $scene, $tinfo);
                }
                //获取美容师id
                if (in_array('2', $roleArr)) {
                    $sellerId = $ptInfo['share_uid'];
                    $mobile = $shareInfo['mobile'];
                    $this->sendMessage('2', $sellerId, $mobile, $typeArr, $scene, $tinfo);
                }
                //获取拼团发起人id
                if (in_array('3', $roleArr)) {
                    $ownerId = $ptInfo['create_uid'];
                    $mobile = $tinfo['mobile'];
                    $this->sendMessage('3', $ownerId, $mobile, $typeArr, $scene, $tinfo);
                }
                //获取参团人id
                if (in_array('4', $roleArr)) {
                    if ($joinUid == '') {
                        $joinMember = $pt->getTuanPaidMember($ptInfo['order_sn']);
                        $this->sendMessage('4', $joinMember, '', $typeArr, $scene, $tinfo);
                    } else {
                        $joinMember = $pt->getBuyOrderInfo($ptInfo['order_sn'], $joinUid);
                        $this->sendMessage('4', $joinMember, '', $typeArr, $scene, $tinfo);
                    }
                }
            }
            return true;
    }

    public function sendMessage($roleId,$uid,$mobile,$typeArr,$scene,$tinfo){
            //给店老板发站内信
            switch ($roleId){
                case 1:
                    //默认店老板只接收拼团成功的站内信
                    if ($scene==3){
                            $getInfo = $this->getMessageContent(3, 1);
                            if ($getInfo['code']) {
                                //由*seller*（美容师）分享出的“*activity*”美丽分享购活动，有订单已成功！
                                $seller = $tinfo['seller'];
                                $p_name = $tinfo['p_name'];
                                if (in_array('1', $typeArr)) {//短信息
                                    $arr = ['activity' => $p_name, 'seller' => $seller];
                                    $this->sendMessageToUser($mobile, $getInfo['data']['sms_id'],$arr);
                                }
                                if (in_array('2', $typeArr)) {//站内信
                                    $txt = $getInfo['data']['sms_content'];
                                    $arr1 = array("*seller*", "*activity*");
                                    $arr2 = array($seller, $p_name);
                                    $str = str_replace($arr1, $arr2, $txt);
                                    $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                    Db::name('member_message')->insert($insertData);
                                }
                            }
                    }
                    break;
                case 2:
                    //默认美容师只接收拼团发起和成功的站内信
                    if($scene==1){
                        $getInfo=$this->getMessageContent(1,2);
                        if($getInfo['code']){
//                          //您分享的“*activity*”美丽分享购，您的客人*name*发起成功，请在我的“拼购进度”查看详情！
                            $p_name=$tinfo['p_name'];
                            $name=$tinfo['realname'];
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['activity'=>$p_name,'name'=>$name];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*activity*","*name*");
                                $arr2 = array($p_name,$name);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }elseif ($scene==3){
                        $getInfo=$this->getMessageContent(3,2);
                        if($getInfo['code']){
//                          //您分享的“*activity*”美丽分享购，现已有新订单*ordersn*成功，请在我的“拼购进度”查看详情！
                            $p_name=$tinfo['p_name'];
                            $order_sn=$tinfo['order_sn'];
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['activity'=>$p_name,'order_sn'=>$order_sn];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*activity*","*order_sn*");
                                $arr2 = array($p_name,$order_sn);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }elseif ($scene==4){
                        $getInfo=$this->getMessageContent(4,2);
                        if($getInfo['code']){
                            //您分享的美丽分享购订单*ordersn*，因在规定时间内未达到分享数量失败，请在我的“拼购进度”查看详情！
                            $order_sn=$tinfo['order_sn'];
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['order_sn'=>$order_sn];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*order_sn*");
                                $arr2 = array($order_sn);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }
                    break;
                case 3:
                    //默认拼团人只接收拼团发起参与和成功的站内信
                    if($scene==1){
                        $getInfo=$this->getMessageContent(1,3);
                        if($getInfo['code']){
                            //您发起的“*activity*”美丽分享购，现已发起成功啦！赶快召唤身边的小伙伴来参与！
                            $p_name=$tinfo['p_name'];
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['activity'=>$p_name];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*activity*");
                                $arr2 = array($p_name);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }elseif ($scene==2){
                        $getInfo=$this->getMessageContent(2,3);
                        if($getInfo['code']){
                            //您有好友参与了您发起的美丽分享购订单*order_sn*，距离订单成功还差*num*人，请再接再厉！
                            $order_sn=$tinfo['order_sn'];
                            //获取当前订单未成团数量
                            $pt=new PintuanModel();
                            $num=$pt->checkOrder($order_sn);
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['order_sn'=>$order_sn,'num'=>$num];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*order_sn*","*num*");
                                $arr2 = array($order_sn,$num);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }elseif ($scene==3){
                            $getInfo = $this->getMessageContent(3, 3);
                            if ($getInfo['code']) {
                                //您发起的美丽分享购订单*order_sn*，现已达到分享数量要求。恭喜您，订单已成功！请在我的“我发起的”查看详情！
                                $order_sn = $tinfo['order_sn'];
                                if (in_array('1', $typeArr)) {//短信息
                                    $arr = ['order_sn' => $order_sn];
                                    $this->sendMessageToUser($mobile, $getInfo['data']['sms_id'], $arr);
                                }
                                if (in_array('2', $typeArr)) {//站内信
                                    $txt = $getInfo['data']['sms_content'];
                                    $arr1 = array("*order_sn*");
                                    $arr2 = array($order_sn);
                                    $str = str_replace($arr1, $arr2, $txt);
                                    $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                    Db::name('member_message')->insert($insertData);
                                }
                            }
                    }elseif ($scene==4){
                        $getInfo=$this->getMessageContent(4,3);
                        if($getInfo['code']){
                            //您发起的美丽分享购订单*order_sn*，因在规定时间未达到分享数量要求，未能成功。订单支付款项将在1-3个工作日为您退回，请注意查收！
                            $order_sn=$tinfo['order_sn'];
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['order_sn'=>$order_sn];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*order_sn*");
                                $arr2 = array($order_sn);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }elseif ($scene==5){
                        $getInfo=$this->getMessageContent(5,3);
                        if($getInfo['code']){
                            //你好，您发起的美丽分享购订单*order_sn*退款已成功受理，您所支付的款项将在1-3个工作日原路返还，请注意查收！
                            $order_sn=$tinfo['order_sn'];
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['order_sn'=>$order_sn];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*order_sn*");
                                $arr2 = array($order_sn);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }elseif ($scene==6){
                        $getInfo=$this->getMessageContent(6,3);
                        if($getInfo['code']){
                            //您参与的美丽分享购订单*order_sn*，距离失效时间越来越近了。再有*num*位好友加入即可成功！快去召唤你的小伙伴加入吧！
                            $order_sn=$tinfo['order_sn'];
                            //获取当前订单未成团数量
                            $pt=new PintuanModel();
                            $num=$pt->checkOrder($order_sn);
                            if(in_array('1',$typeArr)) {//短信息
                                $arr=['order_sn'=>$order_sn,'num'=>$num];
                                $this->sendMessageToUser($mobile,$getInfo['data']['sms_id'],$arr);
                            }
                            if(in_array('2',$typeArr)) {//站内信
                                $txt=$getInfo['data']['sms_content'];
                                $arr1 = array("*order_sn*","*num*");
                                $arr2 = array($order_sn,$num);
                                $str =str_replace($arr1,$arr2,$txt);
                                $insertData = array('uid' => $uid, 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                Db::name('member_message')->insert($insertData);
                            }
                        }
                    }
                    break;
                case 4:
                    //默认参团人只接收拼团参与和成功的站内信
                    foreach ($uid as $k=>$v){
                        if ($scene==2){
                            $getInfo=$this->getMessageContent(2,4);
                            if($getInfo['code']){
                                //欢迎您参与美丽分享购活动，您已参与成功，距离订单成功还差*num*人，你可以在我的“我参与的”查看进度！
                                //获取当前订单未成团数量
                                $pt=new PintuanModel();
                                $num=$pt->checkOrder($v['parent_order']);
                                if(!$v['pay_by_self']) {
                                    if (in_array('1', $typeArr)) {//短信息
                                        $arr = ['num' => $num];
                                        $this->sendMessageToUser($v['mobile'], $getInfo['data']['sms_id'],$arr);
                                    }
                                }
                                if(in_array('2',$typeArr)) {//站内信
                                    if($v['pay_by_self']){
                                        if($v['pay_price']){
                                            $insertData=array('uid'=>$v['id'],'title'=>'凑单成功提醒','content'=>'您给自己发起的订单完成了凑单','insert_time'=>time());
                                            Db::name('member_message')->insert($insertData);
                                        }
                                    }else{
                                        $txt=$getInfo['data']['sms_content'];
                                        $arr1 = array("*num*");
                                        $arr2 = array($num);
                                        $str =str_replace($arr1,$arr2,$txt);
                                        $insertData = array('uid' => $v['id'], 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                        Db::name('member_message')->insert($insertData);
                                    }
                                }
                            }
                        }elseif ($scene==3){
                            if(!$v['pay_by_self']) {
                                $getInfo=$this->getMessageContent(3,4);
                                if($getInfo['code']){
                                    //您参与的美丽分享购订单*order_sn*，订单已成功！请在我的“我参与的”查看详情！
                                    $order_sn=$v['order_sn'];
                                    //获取当前订单未成团数量
                                    if(in_array('1',$typeArr)) {//短信息
                                        $arr=['order_sn'=>$order_sn];
                                        $this->sendMessageToUser($v['mobile'],$getInfo['data']['sms_id'],$arr);
                                    }
                                    if(in_array('2',$typeArr)) {//站内信
                                        $txt=$getInfo['data']['sms_content'];
                                        $arr1 = array("*order_sn*");
                                        $arr2 = array($order_sn);
                                        $str =str_replace($arr1,$arr2,$txt);
                                        $insertData = array('uid' => $v['id'], 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                        Db::name('member_message')->insert($insertData);
                                    }
                                }
                            }
                        }elseif ($scene==4){
                            if(!$v['pay_by_self']) {
                                $getInfo=$this->getMessageContent(4,4);
                                if($getInfo['code']){
                                    //您参与的美丽分享购订单*order_sn*，因在规定时间未达到分享数量要求，未能成功。订单支付款项将在1-3个工作日为您退回，请注意查收！
                                    $order_sn=$v['order_sn'];
                                    //获取当前订单未成团数量
                                    if(in_array('1',$typeArr)) {//短信息
                                        $arr=['order_sn'=>$order_sn];
                                        $this->sendMessageToUser($v['mobile'],$getInfo['data']['sms_id'],$arr);
                                    }
                                    if(in_array('2',$typeArr)) {//站内信
                                        $txt=$getInfo['data']['sms_content'];
                                        $arr1 = array("*order_sn*");
                                        $arr2 = array($order_sn);
                                        $str =str_replace($arr1,$arr2,$txt);
                                        $insertData = array('uid' => $v['id'], 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                        Db::name('member_message')->insert($insertData);
                                    }
                                }
                            }
                        }
                        elseif ($scene==5){
                            if(!$v['pay_by_self']) {
                                $getInfo=$this->getMessageContent(5,4);
                                if($getInfo['code']){
                                    //您参与的美丽分享购订单*order_sn*退款已成功受理，您所支付的款项将在1-3个工作日原路返还，请注意查收！
                                    $order_sn=$v['order_sn'];
                                    //获取当前订单未成团数量
                                    if(in_array('1',$typeArr)) {//短信息
                                        $arr=['order_sn'=>$order_sn];
                                        $this->sendMessageToUser($v['mobile'],$getInfo['data']['sms_id'],$arr);
                                    }
                                    if(in_array('2',$typeArr)) {//站内信
                                        $txt=$getInfo['data']['sms_content'];
                                        $arr1 = array("*order_sn*");
                                        $arr2 = array($order_sn);
                                        $str =str_replace($arr1,$arr2,$txt);
                                        $insertData = array('uid' => $v['id'], 'title' => $getInfo['data']['sms_title'], 'content' => $str, 'insert_time' => time());
                                        Db::name('member_message')->insert($insertData);
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
    }
    public function getMessageContent($scene,$sendTo){
        $map['sms_scene']=array('eq',$scene);
        $map['sms_to']=array('eq',$sendTo);
        $info=Db::name('sms_template')->where($map)->find();
        if(is_array($info) && count($info)){
            if($info['status']==1){
                $res['code']=1;
                $res['data']=$info;
            }else{
                $res['code']=0;
                $res['data']='';
            }
        }else{
            $res['code']=0;
            $res['data']='';
        }
        return $res;
    }

    //给用户发送事件短信
    public function sendMessageToUser($mobile,$tplId,$content){
        sendMsg($mobile,$tplId,$content);
    }
	
	
	    /**
     * 发送创蓝短消息
     * @param $sendArr
     * @param $modelId
     * @return mixed
     */
    public function sendMsgByChuanglan($sendArr,$modelId){
        $code=json_encode($sendArr);
        $send['name'] =config('cl_sms_user');
        $send['pwd'] = config('cl_sms_pwd');
        $send['mobile'] = $sendArr['mobile'];
        $send['type'] = 1; //模板类型
        $send['template'] = $modelId; //模板id
        $send['code'] = $code;
        $send['code2'] = $sendArr['mobile'];
        $str = '';
        ksort($send);
        foreach ($send as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = substr($str, 0, -1);

        $key = md5($str);
        $str .= '&key=' . $key;
        $send['key'] = $key;
        $url = 'http://sms.qunarmei.com/sms.php?' . $str;
        $info=$this->curl_get($url);
        return $info;
    }

    public function curl_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }



}