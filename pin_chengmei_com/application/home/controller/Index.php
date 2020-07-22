<?php
namespace app\home\controller;
use com\Gateway;
use think\Db;
use think\Debug;

class Index extends Base
{



    public function open_year_bag(){
        set_time_limit(0);
        $map['m.isadmin'] = ['eq',1];
        $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->select();
        foreach ($lists as $kk => $vv) {
            $activitys=0;
            if(!empty($vv['join_tk'])){
                $exits = strstr($vv['join_tk'], '10');
                if($exits==false){
                    $activitys=$vv['join_tk'].',10';
                }
            }else{
                $activitys='10';
            }
            if($activitys){
                Db::table('ims_bj_shopn_member')->where('mobile',$vv['mobile'])->update(['activity_key'=>1]);
                Db::table('ims_bwk_branch')->where('id',$vv['id'])->update(['join_tk'=>$activitys]);
            }
        }
    }


    public function close_activity(){
        set_time_limit(0);
        $id=input('param.id');
        $map['m.isadmin'] = ['eq',1];
        $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->select();
        foreach ($lists as $kk => $vv) {
            if(!empty($vv['join_tk'])){
                $exits = strstr($vv['join_tk'], $id);
                if($exits){
                     $arr=explode(',',$vv['join_tk']);
                     $getKey=array_search($id,$arr);
                     unset($arr[$getKey]);
                     $activitys=implode(',',$arr);
                     Db::table('ims_bwk_branch')->where('id',$vv['id'])->update(['join_tk'=>$activitys]);
                }
            }
        }
    }

    public function activity_count(){
        set_time_limit(0);
        $id=input('param.id');
        $lists = Db::table('ims_bwk_branch')->alias('bwk')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('depart.st_department,bwk.title,bwk.sign,bwk.join_tk')->select();
        $data=[];
        foreach ($lists as $kk => $vv) {
            if(!empty($vv['join_tk'])){
                $exits = explode(',',$vv['join_tk']);
                if(in_array($id,$exits)){
                    $vv['join_tk']=config("activity.$id");
                    $data[]=$vv;
                }
            }
        }
        $filename = config("activity.$id")."活动开通门店列表".date('YmdHis');
        $header = array ('办事处','门店名称','门店编码','活动名称');
        $widths=array('30','30','30','30');
        if($data) {
            excelExport($filename, $header, $data, $widths);//生成数据
        }
    }


    public function scores(){
        $list=Db::name('temp_scores')->where('flag',0)->select();
        if($list){
            foreach ($list as $k=>$v){
                $count=Db::table('think_scores_record')->where('remark',$v['order_sn'])->count();
                if(!$count){
                    $score = $v['score'] * 0.1;
                    $scoreData = array('user_id' => $v['uid'], 'remark' => $v['order_sn'], 'scores' => floor($score), 'type' => 'missshop_transfer', 'msg' => '用户uid' . $v['uid'] . '下单，奖励' . $score . '分(补)', 'log_time' => date('Y-m-d H:i:s', time()));
                    $res=Db::table('think_scores_record')->insert($scoreData);
                    if($res) {
                        Db::name('temp_scores')->where('id', $v['id'])->update(['flag' => 1]);
                    }
                }
            }
        }
    }




    public function msg(){
        $arr=['name'=>'阿斯蒂芬','price'=>'50.01','url'=>'http://live.qunarmei.com/index/index/zhibo_down.html'];
        echo sendMessage('18818219125',$arr,108);
    }





    public function test1(){
        Db::table('ims_bj_shopn_member')->where('id',13)->delete();
    }


    public function index(){
        return $this->fetch();
    }

    public function bind(){
        $mobile=input('param.mobile');
        $client_id=input('param.client_id');
        //$groupId=input('param.groupId','');
        Gateway::bindUid($client_id,$mobile);
        Gateway::joinGroup($client_id,'live');
        Gateway::setSession($client_id, ['live_mobile'=>$mobile,'live_stay'=>1]);
        $mobile11=Gateway::getUidByClientId($client_id);
        $msg=['scene'=>'initData','data'=>$mobile11];
        Gateway::sendToUid($mobile,json_encode($msg));
    }

    public function get_live(){
        $mobile=input('param.mobile');
        $array=Db::name('live_url')->where('id',1)->find();
        if($array['flag']){
            $url = $array['live_url'];
        }else{
            $url = $array['preheat_url'];
        }
        $this->user_action1($mobile,'in');
        $msg = ['scene'=>'live','live_url' => $url];
        Gateway::sendToUid($mobile,json_encode($msg));
    }

//    public function send_live(){
//        $url=input('param.url');
//        $msg=['scene'=>'live','live_url'=>$url];
//        Gateway::sendToGroup('live', json_encode($msg));
//    }


    public function user_action1($mobile,$type){
        $arr=['live'=>'live','mobile'=>$mobile,'type'=>$type,'action_time'=>time()];
        self::$redis->lPush('live_user_action',json_encode($arr));
    }


    public function leave(){
        $mobile=input('param.mobile');
        $this->user_action1($mobile,'out');
    }


//    public function user_action(){
//        //$type=input('param.type');//记录类型 登录 登出
//        $num=rand(0,1);
//        $type=$num?'in':'out';
//        $mobile=input('param.mobile');
//        $arr=['mobile'=>$mobile,'type'=>$type,'action_time'=>time()];
//        self::$redis->lPush('live_user_action',json_encode($arr));
//    }


    public function user_pop(){
      $pop=self::$redis->rPop('live_user_action');
      $data=json_decode($pop,true);
      if ($data){
          $map['mobile']=array('eq',$data['mobile']);
          $map['item_name']=array('eq','live');
          $info=Db::name('user_stay')->where($map)->order('id desc')->find();
          if(!$info && $data['type']=='in'){
              Db::name('user_stay')->insert(['item_name'=>$data['live'],'mobile'=>$data['mobile'],'login_time'=>$data['action_time'],'leave_time'=>$data['action_time'],'stay_time'=>0]);
          }else{
              if($data['type']=='out'){
                  if($info['stay_time']==0){
                      $stay=$data['action_time']-$info['login_time'];
                      Db::name('user_stay')->where('id',$info['id'])->update(['leave_time'=>$data['action_time'],'stay_time'=>round($stay,2)]);
                  }
              }else{
                  Db::name('user_stay')->insert(['item_name'=>$data['live'],'mobile'=>$data['mobile'],'login_time'=>$data['action_time'],'leave_time'=>$data['action_time'],'stay_time'=>0]);
              }
          }
      }
    }




//    public function main()
//    {
//        Debug::remark('begin');
//        foreach ($this->useYield(1000) as $key => $val) {
//            echo "<pre>";
//            var_dump($key);
//            var_dump($val);
//        }
//        Debug::remark('end');
//        echo Debug::getRangeTime('begin','end').'s';
//    }
//
//    private function useYield($num)
//    {
//        for ($i = 1; $i <= $num; $i++) {
//            yield $i;
//        }
//    }




    /**
     * 美容师确认收货产品详细
     */
    public function pickUpGoodsInfo(){
//        $orderSn=input('param.order_sn');
//        $uid=input('param.uid');
//        if($orderSn!='' && $uid!='') {
//                    $orderInfo=Db::name('activity_order')->alias('o')->join('goods g','o.pid=g.id','left')->where(['o.order_sn'=>$orderSn])->field('o.fid,o.order_sn,o.insert_time,o.pay_time,o.pay_price,o.num,o.order_status,g.name,g.image')->find();
//                        if ($uid == $orderInfo['fid']) {
//                            if ($orderInfo['order_status'] == 1) {
//                                $code = 0;
//                                $data = '';
//                                $msg = '该订单已取货';
//                            } else {
//                                $orderInfo['insert_time'] = date('Y-m-d H:i:s', $orderInfo['insert_time']);
//                                $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
//                                $orderInfo['qrcode_type'] = 2;
//                                $code = 1;
//                                $data = $orderInfo;
//                                $msg = '获取成功';
//                            }
//                        } else {
//                            $code = 0;
//                            $data = '';
//                            $msg = '该订单您没有查看权限';
//                        }
//
//
//        }else{
//            $code=0;
//            $data='';
//            $msg='订单号不允许为空';
//        }
//        $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
//        return json($arr);
    }






















    public function upd(){
//        $map['o.pay_status']=array('eq',1);
//        $map['o.channel']=array('eq','missshop');
//        $map['m.isadmin']=array('eq',0);
//        $map['m.code']=array('eq','');
//        $map['activity_flag']=array('not in',['8805','8806']);
//        $info=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id','left')->where($map)->column('m.id');
//        foreach ($info as $k=>$v){
//                Db::table('ims_bj_shopn_member')->where('id',$v)->setField('activity_flag','8807');
//        }

    }


    public function total(){
        $info=Db::name('activity_order')->where(['channel'=>'missshop','pay_status'=>1,'scene'=>0])->field("count(id) as num1,sum(num) as num2,sum(pay_price) as num3")->find();
        echo "拓客截止到".date('Y-m-d H:i:s').'：<br/>'.'总单数：'.$info['num1'].'单，总盒数：'.$info['num2'].'盒，总支付金额：'.$info['num3'].'元';
        echo "<hr>";
        $info=Db::name('activity_order')->where(['channel'=>'missshop','pay_status'=>1,'scene'=>1])->field("count(id) as num1,sum(num) as num2,sum(pay_price) as num3")->find();
        echo "转客截止到".date('Y-m-d H:i:s').'：<br/>'.'总单数：'.$info['num1'].'单，总数量：'.$info['num2'].'盒/支，总支付金额：'.round($info['num3'],2).'元';
        echo "<hr>";
        die();
    }

    public function double11(){
        $info=Db::name('activity_order')->where(['channel'=>'missshop','pay_status'=>1,'scene'=>2])->field("count(id) as num1,sum(num) as num2,sum(pay_price) as num3")->find();
        echo "双十一订单截止到".date('Y-m-d H:i:s').'：<br/>'.'总单数：'.$info['num1'].'单，总盒数：'.$info['num2'].'盒，总支付金额：'.$info['num3'].'元';
        echo "<hr>";
        die();
    }



    public function admin(){
        $list=Db::table('ims_bj_shopn_member')->where(['isadmin'=>1,'staffid'=>0])->field('id,staffid')->select();
        if($list){
            foreach ($list as $k=>$v){
                if($v['staffid']==0){
                    Db::table('ims_bj_shopn_member')->where('id',$v['id'])->setField('staffid',$v['id']);
                }
            }
        }
    }




    public function action(){
//            $list=Db::name('temp827')->alias('t')->join(['ims_bwk_branch'=>'b'],'t.sign=b.sign','left')->where('t.flag',0)->field('t.id tid,b.id bid')->select();
//            print_r($list);
//            die();

//            if($list){
//                foreach ($list as $k=>$v){
//                    if($v['bid']) {
//                        Db::table('ims_bj_shopn_member')->where(array('storeid' => $v['bid'], 'isadmin' => 1))->setField(['activity_key' => 1]);
//                        Db::name('temp827')->where('id', $v['tid'])->setField(['flag' => 1]);
//                    }
//
//                }
//            }
    }





    public function send(){
        $mobile=Db::name('send38')->where('flag',0)->order('id desc')->limit(100)->select();
        if(is_array($mobile) && count($mobile)){
            try {
                foreach ($mobile as $k => $v) {
                    $arr['mobile']=$v['mobile'];
                    $arr['name']=$v['draw_sms'];
                    $arr['sms_id'] = $v['sms_id'];
                    \think\Queue::push( 'app\index\job\Send' , $arr,'testSend');
                    Db::name('send38')->where('id',$v['id'])->update(['flag'=>1]);
                }
            }catch (\Exception $e){
                file_put_contents('senlog',$e->getMessage(),FILE_APPEND);
            }
        }else{
            echo "完了";
        }
        die();
    }


    public function send_message(){
       // $sendCount='38女神节88福袋正在派送，更有限时活动2020抗疫法宝等您来参加，丽人堂入驻去哪美啊小程序，足不出户也可享受线上购物直邮到家的乐趣';
       // $list=Db::table('ims_bj_shopn_member')->where('storeid',68)->column('id');
      // foreach ($list as $k=>$v){
         // $arr1 = array('mail_param' => array('uid' => $v, 'title' => '38女神节活动通知', 'content' => $sendCount, 'insert_time' => time()), 'sms_param' => []);
           // sendCommQueue($arr1, 1);
      // }
    }


    public function tttt(){
        echo time();
    }





}