<?php

namespace app\api\controller;
use org\QiniuUpload;
use think\Controller;
use think\Db;

/**
 * desc:减脂训练营活动
 */
class Training extends Base
{
    protected $activityInfo = [];
    protected $getDay = 0;
    public function _initialize() {
        parent::_initialize();
        $this->activityInfo=Db::name('training_config')->where('id',1)->cache(60)->find();
        $this->getDay = count_days($this->activityInfo['begin_time'], time());//获取活动进行天数
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
    public function check_activity($uid=0){
        if($this->activityInfo['activity_status']==0){
            $code = 0;
            $data = '';
            $msg = '活动未开启！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
        if($this->activityInfo['begin_time'] > time() ){
            $code = 0;
            $data = '';
            $msg = '入营通道将于'.date('Y年m月d日 H时i分s秒',$this->activityInfo['begin_time']).'开启，请等待！';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }
        $xlyMap['uid']=array('eq',$uid);
        $xlyMember=Db::name('training_measure')->where($xlyMap)->field('insert_time')->order('insert_time asc')->find();
        if(count($xlyMember) && is_array($xlyMember)){
            if($xlyMember['insert_time'] > $this->activityInfo['end_time']){
                $code = 0;
                $data = '';
                $msg = '您来晚啦，训练营的通道已经关闭了！';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }
        }else{
            $getUserInfo=Db::table('ims_bj_shopn_member')->where('id',$uid)->field('id,code,mobile')->find();
            //$bscMemer=Db::table('sys_department_member')->where('u_mobile',$getUserInfo['mobile'])->count();
            //if(strlen($getUserInfo['code'])>1){
             //   $msg1="您先测量您的体脂数据 再来参与哦！";
            //}else{
                $msg1='请先联系您所属的美容师，再来参与哦！';
            //}
            $code = 0;
            $data = '';
            $msg = $msg1;
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;

        }
        return true;
    }

    /*---------------------------------------------------美容师部分开始----------------------------------------------------------*/
    //获取当前美容师下顾客打卡状态
    public function attendance(){
        $uid=input('param.uid');
        if($uid !='') {
            try {
                $res = [];
                $list = Db::name('training_measure')->alias('m')->join('training_member tm', 'm.uid=tm.uid', 'left')->join(['ims_bj_shopn_member' => 'member'], 'member.id=m.uid', 'left')->field('m.uid,member.realname,member.mobile,count(tm.uid) count')->group('m.uid')->where('m.seller_id', $uid)->order('count')->select();
                if (count($list) && is_array($list)) {
                    foreach ($list as $k => $v) {
                        $map['uid'] = array('eq', $v['uid']);
                        $userInfo = Db::name('training_member')->where($map)->field('insert_time')->order('insert_time asc')->find();
                        if($userInfo){
                            $joinDay=count_days($userInfo['insert_time'], time());//获取自己打卡天数
                        }else{
                            $joinDay=1;//获取自己打卡天数
                        }
                        $list[$k]['join_day'] = $joinDay;
                        if($joinDay>21){
                            $list[$k]['daka'] = '已结业';
                        }else{
                            $isHave = Db::name('training_member')->where($map)->whereTime('insert_time', 'today')->find();
                            $list[$k]['daka'] = $isHave ? '已打卡' : '未打卡';
                        }
                    }
                }
                $posterKey = array_rand(config('seller_poster'));
                $res['comm'] = array(
                    'current_day' => $this->getDay,
                    'poster_bg' => config('seller_poster.' . $posterKey),
                    'send_status'=>self::$redis->sismember('xly_send_msg'.date('Ymd'),$uid)//检查当天是否发送过短信
                );
                $res['list'] = $list;
                $code = 1;
                $data = $res;
                $msg = '获取成功！';
            } catch (\Exception $e) {
                $code = 0;
                $data = '';
                $msg = '获取失败！'.$e->getMessage();
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 美容师下顾客列表
     */
    public function get_members(){
        $uid=input('param.uid');
        if($uid !='') {
            try {
                //$getUserInfo=Db::table('ims_bj_shopn_member')->where('id',$uid)->field('id,code,mobile')->find();
               // $bscMemer=Db::table('sys_department_member')->where('u_mobile',$getUserInfo['mobile'])->count();
                //if($bscMemer){
                //    $map['member.id']=array('eq',$uid);
               // }else{
                    $map['member.staffid']=array('eq',$uid);
               // }
                $list = Db::table('ims_bj_shopn_member')->alias('member')->join(['ims_fans' => 'fans'], 'member.id=fans.id_member', 'left')->join('wx_user u','member.mobile=u.mobile','left')->field('member.id,member.realname,member.mobile,fans.avatar pic,u.avatar')->where($map)->select();
                if(count($list) && is_array($list)){
                    foreach ($list as $k=>$v){
                        if($v['avatar']==''){
                            $list[$k]['avatar']=$v['pic']?$v['pic']:'http://appc.qunarmei.com/img_logo1.png';
                        }
                        unset($list[$k]['pic']);
                    }
                }
                $code = 1;
                $data = $list;
                $msg = '获取成功！';
            } catch (\Exception $e) {
                $code = 0;
                $data = '';
                $msg = '获取失败！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //用户测量数据
    public function measure_data(){
        $uid=input('param.uid');
        if($uid !='') {
            try {
                $res=[];
                $measure=[];
                $res['userInfo']=Db::table('ims_bj_shopn_member')->field('id,realname,mobile')->where('id',$uid)->find();
                $getBackDay=explode(',',$this->activityInfo['back_day']);
                if(count($getBackDay) && is_array($getBackDay)){
                    foreach ($getBackDay as $k=>$v){
                        $info=Db::name('training_measure')->where(['uid'=>$uid,'record_day'=>$v])->find();
                        unset($info['id']);
                        unset($info['seller_id']);
                        unset($info['uid']);
                        unset($info['insert_time']);
                        $measure[$v]['flag']=$info?1:0;
                        $measure[$v]['info']=$info?$info:[];
                    }
                    $res['measure']=$measure;
                    $code = 1;
                    $data = $res;
                    $msg = '获取成功！';
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '后台未配置到店日期！';
                }
            } catch (\Exception $e) {
                $code = 0;
                $data = '';
                $msg = '获取失败！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
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

    //测量数据提交
    public function measure_data_submit(){
        $uid=input('param.uid');
        $record_day=input('param.record_day');
        if($uid=='' || $record_day==''){
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }else {
            $check = Db::name('training_measure')->where(['uid' => $uid, 'record_day' => $record_day])->count();
            try {
                $seller_id = input('param.seller_id', '');
                $age = input('param.age', 0);
                $weight = input('param.weight', 0);
                $bust = input('param.bust', 0);
                $low_bust = input('param.low_bust', 0);
                $waist = input('param.waist', 0);
                $hips = input('param.hips', 0);
                $left_thigh = input('param.left_thigh', 0);
                $right_thigh = input('param.right_thigh', 0);
                $crus = input('param.crus', 0);
                $ankle = input('param.ankle', 0);
                $pic1 = input('param.pic1', '');
                $pic2 = input('param.pic2', '');
                $pic3 = input('param.pic3', '');
                $body_fat = input('param.body_fat', 0);
                $weight_index = input('param.weight_index', '');
                $underclothes1 = input('param.underclothes1', '');
                $underclothes2 = input('param.underclothes2', '');
                $substitute = input('param.substitute', '');
                $insertData = array(
                    'seller_id' => $seller_id,
                    'uid' => $uid,
                    'record_day' => $record_day,
                    'age' => $age,
                    'weight' => $weight,
                    'bust' => $bust,
                    'low_bust' => $low_bust,
                    'waist' => $waist,
                    'hips' => $hips,
                    'left_thigh' => $left_thigh,
                    'right_thigh' => $right_thigh,
                    'crus' => $crus,
                    'ankle' => $ankle,
                    'pic1' => $pic1,
                    'pic2' => $pic2,
                    'pic3' => $pic3,
                    'body_fat' => $body_fat,
                    'weight_index' => $weight_index,
                    'underclothes1' => $underclothes1,
                    'underclothes2' => $underclothes2,
                    'substitute' => $substitute,
                    'insert_time' => time()
                );
                if ($check) {
                    Db::name('training_measure')->where(['uid' => $uid, 'record_day' => $record_day])->update($insertData);
                    $tips = '信息维护成功';
                } else {
                    Db::name('training_measure')->insert($insertData);
                    $tips = '信息提交成功';
                }
                $code = 1;
                $data = '';
                $msg = $tips;
            } catch (\Exception $e) {
                $code = 0;
                $data = '';
                $msg = '提交失败！' . $e->getMessage();
            }
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //发送提醒信息 站内信和短信
    public function send_warn(){
        $uid=input('param.uid');
        if($uid !='') {
            //$getUserInfo=Db::table('ims_bj_shopn_member')->where('id',$uid)->field('id,code,mobile')->find();
//            $bscMemer=Db::table('sys_department_member')->where('u_mobile',$getUserInfo['mobile'])->count();
//            if($bscMemer){
//                $code = 1;
//                $data = '';
//                $msg = '发送成功';
//            }else{
                $list = Db::name('training_measure')->alias('m')->join('training_member tm', 'm.uid=tm.uid', 'left')->join(['ims_bj_shopn_member' => 'member'], 'member.id=m.uid', 'left')->field('m.uid,member.realname,member.mobile,count(tm.uid) count')->group('m.uid')->where('m.seller_id', $uid)->order('count')->select();
                if (count($list) && is_array($list)) {
                    try {
                        $check = self::$redis->sismember('xly_send_msg' . date('Ymd'), $uid);
                        if ($check) {
                            $code = 1;
                            $data = '';
                            $msg = '今天您已经提醒过了！';
                        } else {
                            foreach ($list as $k => $v) {
                                $map['uid'] = array('eq', $v['uid']);
                                $userInfo = Db::name('training_member')->where($map)->field('insert_time')->order('insert_time')->find();
                                if ($userInfo) {
                                    $joinDay = count_days($userInfo['insert_time'], time());//获取自己打卡天数
                                } else {
                                    $joinDay = 1;//获取自己打卡天数
                                }
                                if ($joinDay < 22) {
                                    $sendTips = config("send_text." . intval($joinDay - 1));
                                    $sendCount = "减脂小秘书提醒您：这是您加入训练营的第" . $joinDay . "天，别忘了去打卡哦！#" . $sendTips . "#";
                                    $isHave = Db::name('training_member')->where($map)->whereTime('insert_time', 'today')->find();
                                    if (!$isHave) {
                                        //未打卡用户发送站内信息和短信
                                        $arr1 = array('mail_param' => array('uid' => $v['uid'], 'title' => '减脂小秘书提醒', 'content' => $sendCount, 'insert_time' => time()), 'sms_param' => array('smsId' => 80, 'param' => array('mobile' => $v['mobile'], 'day' => $joinDay, 'tips' => $sendTips)));
                                        sendCommQueue($arr1, 0);
                                    }
                                }
                            }
                            self::$redis->sadd('xly_send_msg' . date('Ymd'), $uid);//发送成功后 应该不让其在此发送
                            $code = 1;
                            $data = '';
                            $msg = '发送成功！';
                        }
                    } catch (\Exception $e) {
                        $code = 0;
                        $data = '';
                        $msg = '失败！' . $e->getMessage();
                    }
                }else{
                    $code = 0;
                    $data = '';
                    $msg = '您没有顾客参与该活动';
                }
//            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /*---------------------------------------------------美容师部分结束----------------------------------------------------------*/


    /*---------------------------------------------------顾客部分开始----------------------------------------------------------*/
    //用户登陆数据
    public function user_record(){
        $uid=input('param.uid');
        $this->check_activity($uid);
        if($uid !=''){
            $firstJoinDay=Db::name('training_member')->where('uid',$uid)->order('id')->value('insert_time');
            if($firstJoinDay){
                $joinDay=count_days($firstJoinDay, time());//获取自己打卡天数
                if($joinDay>21){
                    $joinDay=21;
                }
            }else{
                $joinDay=1;//获取自己打卡天数
            }
            $res['day']=$joinDay;
            $res['measure_data']=Db::name('training_measure')->where('uid',$uid)->order('id desc')->find();
            $tips1Key = array_rand(config('tips1'));
            $res['tips1']=config('tips1.'.$tips1Key);
            $tips2Key = array_rand(config('tips2'));
            $res['tips2']=config('tips2.'.$tips2Key);
            if($joinDay==7 ||$joinDay==14 ||$joinDay==21){
                $res['toBranchDay']=1;
            }else{
                $res['toBranchDay']=0;
            }
            if($joinDay==1 ||$joinDay==8 ||$joinDay==15){
                $res['measureDataShow']=1;
            }else{
                $res['measureDataShow']=0;
            }
            $getCurrent=Db::name('training_member') ->where(['uid'=>$uid,'day'=>$joinDay])->field('weight')->find();
            if(count($getCurrent) && is_array($getCurrent)){
                $res['is_daka']=1;
                $res['daka_weight']=$getCurrent['weight'];
            }else{
                $res['is_daka']=0;
                $res['daka_weight']='';
            }
            $code = 1;
            $data = $res;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //记录每日打卡体重
    public function record_submit(){
        $uid=input('param.uid');
        $this->check_activity($uid);
        $weight=input('param.weight');
        if($uid !='' && $weight !=''){
            try {
                $firstJoinDay=Db::name('training_member')->where('uid',$uid)->order('id')->value('insert_time');
                if($firstJoinDay){
                    $joinDay=count_days($firstJoinDay, time());//获取自己打卡天数
                    if($joinDay>21){
                        $joinDay=21;
                    }
                }else{
                    $joinDay=1;//获取自己打卡天数
                }
                $check = Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->count();
                if ($check) {
                    Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->update(['weight' => $weight]);
                    $continued=Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->value('continued');
                } else {
                    $pre_day=Db::name('training_member')->where(['uid'=>$uid])->field('day,continued')->order('id desc')->find();
                    $day=$pre_day['day']?$pre_day['day']:0;
                    $continued=$pre_day['continued']?$pre_day['continued']:0;
                    $insert = array('uid' => $uid, 'day' => $joinDay, 'weight' => $weight, 'insert_time' => time());
                    Db::name('training_member')->insert($insert);
                    if($day==0 || ($day==($joinDay-1))){
                        $continued=$continued+1;
                        Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->update(['continued' => $continued]);
                    }else{
                        Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->update(['continued' => 1]);
                        $continued=1;
                    }
                    //打卡奖励开始
                    //打卡7天 奖励代餐一包
                    if($continued==7){
                        $checkAward=Db::name('training_member')->where(['uid'=>$uid,'award'=>1])->count();
                        if(!$checkAward){
                            Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->update(['award' => 1]);
                        }
                    }
                    //打卡21天 奖励代餐二包和长筒袜一条
                    if($continued==21){
                        Db::name('training_member')->where(['uid' => $uid, 'day' => $joinDay])->update(['award' => 2]);
                    }
                    //打卡奖励结束
                }

                if($continued<7){
                    $str='恭喜您！连续打卡'.$continued.'天，距离免费代餐只剩'.(7-$continued).'天';
                }elseif($continued==7){
                    $str='恭喜您！连续打卡'.$continued.'天，请联络您的专属美容顾问领取免费代餐。';
                }elseif($continued==21){
                    $str='恭喜您！连续打卡'.$continued.'天，请联络您的专属美容顾问领取大礼！';
                }else{
                    $str='恭喜您！连续打卡'.$continued.'天，免费代餐和夜间长筒袜只剩'.(21-$continued).'天';
                }

                $code = 1;
                $data = $str;
                $msg = '提交成功';
            }catch (\Exception $e){
                $code = 0;
                $data = '';
                $msg = '提交失败';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //显示对比数据
    public function user_record_info(){
        $uid=input('param.uid');
        $this->check_activity($uid);
        if($uid !=''){
            $firstJoinDay=Db::name('training_member')->where('uid',$uid)->order('id')->value('insert_time');
            if($firstJoinDay){
                $joinDay=count_days($firstJoinDay, time());//获取自己打卡天数
                if($joinDay>21){
                    $joinDay=21;
                }
            }else{
                $joinDay=1;//获取自己打卡天数
            }
            $res['day']=$joinDay;
            //获取用户上两次的体重
            $getWeight=Db::name('training_member')->where(['uid'=>$uid])->field('day,weight,insert_time')->order('insert_time desc')->limit(2)->select();
            $weightData=[];
            foreach ($getWeight as $k=>$v){
                $weightData[$k]['days']=date('Y-m-d',$v['insert_time']);
                $weightData[$k]['weight']=$v['weight'];
            }
            asort($weightData);
            $weightData=array_values($weightData);
            if(count($weightData)==1){
                $preWeight=$weightData[0]['weight'];
                $lastWeight=$weightData[0]['weight'];
            }elseif (count($weightData)==2){
                $preWeight=$weightData[0]['weight'];
                $lastWeight=$weightData[1]['weight'];
            }else{
                $preWeight=0;
                $lastWeight=0;
            }
            $diffWeight=$preWeight-$lastWeight;
            $diffWeight=sprintf("%.1f",$diffWeight);
            $res['diff']=abs($diffWeight);
            $res['weightData']=$weightData;
            if($diffWeight==0){
                $pics3Key = array_rand(config('suggest_pics3'));
                $res['measure_tips']=config('tips5.2');
                $res['customer_pic']=config('suggest_pics3.'.$pics3Key);
                $res['flag']='相等';
            }else{
                switch ($diffWeight){
                    case $diffWeight>=0.5:
                        $pics1Key = array_rand(config('suggest_pics1'));
                        $res['measure_tips']=config('tips5.0');
                        $res['customer_pic']=config('suggest_pics1.'.$pics1Key);
                        $res['flag']='轻了';
                        break;
                    case $diffWeight >0 && $diffWeight< 0.5:
                        $pics2Key = array_rand(config('suggest_pics2'));
                        $res['measure_tips']=config('tips5.1');
                        $res['customer_pic']=config('suggest_pics2.'.$pics2Key);
                        $res['flag']='轻了';
                        break;
                    case $diffWeight <0 && $diffWeight >= -0.5:
                        $pics4Key = array_rand(config('suggest_pics4'));
                        $res['measure_tips']=config('tips5.3');
                        $res['customer_pic']=config('suggest_pics4.'.$pics4Key);
                        $res['flag']='重了';
                        break;
                    default:
                        $pics5Key = array_rand(config('suggest_pics5'));
                        $res['measure_tips']=config('tips5.4');
                        $res['customer_pic']=config('suggest_pics5.'.$pics5Key);
                        $res['flag']='重了';
                }
            }

            //计算第一次和最后一次的体重变化
            $firstData=Db::name('training_measure')->where(['uid'=>$uid])->field('weight,body_fat')->order('id')->limit(1)->find();
            $endData=Db::name('training_measure')->where(['uid'=>$uid])->field('weight,body_fat')->order('id desc')->limit(1)->find();
            $weight_diff=round($firstData['weight']-$endData['weight'],2);
            if($weight_diff>=0){
                $res['weight_diff']=abs($weight_diff);
                $res['weight_change']='减轻';
            }else{
                $res['weight_diff']=abs($weight_diff);
                $res['weight_change']='增加';
            }

            $fat_diff=round($firstData['body_fat']-$endData['body_fat'],2);
            if($weight_diff>=0){
                $res['fat_diff']=abs($fat_diff);
                $res['fat_change']='降低';
            }else{
                $res['fat_diff']=abs($fat_diff);
                $res['fat_change']='增高';
            }
            $res['beat_num']=rand('500','9999');
            $getBackDay=explode(',',$this->activityInfo['back_day']);
            if(count($getBackDay) && is_array($getBackDay)){
                foreach ($getBackDay as $k=>$v){
                    $info=Db::name('training_measure')->where(['uid'=>$uid,'record_day'=>$v])->field('bust,low_bust,waist,hips,left_thigh,right_thigh,crus,ankle')->find();
                    $infoData=[];
                    $infoData[]=$info['bust']?$info['bust']:0;
                    $infoData[]=$info['low_bust']?$info['low_bust']:0;
                    $infoData[]=$info['waist']?$info['waist']:0;
                    $infoData[]=$info['hips']?$info['hips']:0;
                    $infoData[]=$info['left_thigh']?$info['left_thigh']:0;
                    $infoData[]=$info['right_thigh']?$info['right_thigh']:0;
                    $infoData[]=$info['crus']?$info['crus']:0;
                    $infoData[]=$info['ankle']?$info['ankle']:0;
                    $measure[$v]=$infoData;
                }
                $res['measure']=$measure;
            }
            $code = 1;
            $data = $res;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

}