<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * desc:老板抽奖
 */
class Christmas extends Base
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
     * 查看用户是否已经中奖
     * @return \think\response\Json
     */
    public function check_prize()
    {
        $uid=input('param.uid');
        if($uid!=''){
            $map['log.uid']=array('eq',$uid);
            $info = Db::name('christmas_prize_log')->alias('log')->join('christmas_prize prize','log.prize_id=prize.id','left')->field('log.id,log.insert_time,prize.prize_name,prize.prize_price,prize.prize_pic,prize_price')->where($map)->find();
            if(count($info) && is_array($info)){
                $code = 1;
                $data = $info;
                $msg = '获取抽奖信息成功！';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂时没有中奖！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 摇一摇抽奖
     * @return \think\response\Json
     */
    public function lottery_draw()
    {
        $uid=input('param.uid');
        $is_prize =Db::name('christmas_prize_log')->alias('log')->join('christmas_prize prize','log.prize_id=prize.id','left')->field('log.id,log.insert_time,prize.prize_name,prize.prize_price,prize.prize_pic,prize_price')->where('log.uid',$uid)->find();
        if(!$is_prize) {
            $map['prize_cate'] = array('neq', "美容师专享");
            $map['prize_status'] = array('eq', 1);
            $map['prize_num'] = array('gt', 0);
            $list = Db::name('christmas_prize')->field('id,prize_name,prize_num')->where($map)->select();
            foreach ($list as $key => $val) {
                $arr[$val['id']] = $val['prize_num'];
            }
            $prize_id = $this->getRand($arr); //根据概率获取奖品id
            $drawInfo = Db::name('christmas_prize')->field('id,prize_name,prize_num,prize_pic,prize_cate,prize_price')->where('id', $prize_id)->find();
            if (count($list) && is_array($list)) {
                if ($drawInfo['prize_cate'] != "仪器护理") {
                    Db::name('christmas_prize')->where('id', $drawInfo['id'])->setDec('prize_num');
                }
                $arr = array('uid' => $uid, 'prize_id' => $prize_id, 'insert_time' => time());
                Db::name('christmas_prize_log')->insert($arr);
				$arr1=array('uid'=>$uid,'title'=>'圣诞中奖通知','content'=>"恭喜您获得了我们为您送出的圣诞礼物：".$drawInfo['prize_name']."，如有问题，请联系您所属美容师！");
                sendDrawQueue($arr1);
                $code = 1;
                $data = $drawInfo;
                $msg = '奖品抽取成功！';
            } else {
                $code = 0;
                $data = '';
                $msg = '奖品抽取失败！';
            }
        }else{
            $code = 0;
            $data = $is_prize;
            $msg = '您已经抽过奖了！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 美容师奖励
     * @return \think\response\Json
     */
    public function seller_prize()
    {
        $map['prize_cate']=array('eq',"美容师专享");
        $map['prize_status']=array('eq',1);
        $info = Db::name('christmas_prize')->field('prize_name,prize_pic,prize_price')->where($map)->select();
        if(count($info) && is_array($info)){
            $code = 1;
            $data = $info;
            $msg = '获取奖励信息成功！';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂时没有奖励信息！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /**
     * 美容师排行
     * @return \think\response\Json
     */
    public function seller_rank()
    {
        $sellerId=input('param.sellerid');
        $res=array();
        $map1['activity_flag']=array('in','5,6');
        $customerCount=Db::table('ims_bj_shopn_member')->where(['staffid'=>$sellerId,'id_regsource'=>7])->where($map1)->count();
        $getSellerInfo2=Db::table('ims_bj_shopn_member')->alias('member')->field('member.realname,member.mobile,u.avatar')->join('wx_user u','member.mobile=u.mobile','left')->where('member.id',$sellerId)->find();
        $selfRank['staffid']=$sellerId;
        $selfRank['count']=0;
        $selfRank['avatar']=$getSellerInfo2['avatar']?$getSellerInfo2['avatar']:config('qiniu.image_url').'/avatar.png';
        $selfRank['rank']=0;
        $selfRank['seller_name']=$getSellerInfo2['realname']?$getSellerInfo2['realname']:$getSellerInfo2['mobile'];

        $customerList=Db::table('ims_bj_shopn_member')->alias('member')->join('wx_user u','member.mobile=u.mobile','left')->field('member.staffid,count(member.id) as count,u.avatar')->where(['member.id_regsource'=>7])->where($map1)->group('member.staffid')->order('count desc')->select();
        if(count($customerList) && is_array($customerList)){
            $rank=1;
            foreach ($customerList as $key=>$val){
                $customerList[$key]['rank']=$rank++;
                $getSellerInfo=Db::table('ims_bj_shopn_member')->alias('member')->field('member.realname,member.mobile,u.avatar')->join('wx_user u','member.mobile=u.mobile','left')->where('member.id',$val['staffid'])->find();
                $customerList[$key]['seller_name']=$getSellerInfo['realname']?$getSellerInfo['realname']:$getSellerInfo['mobile'];
                $customerList[$key]['avatar']=$getSellerInfo['avatar']?$getSellerInfo['avatar']:config('qiniu.image_url').'/avatar.png';
                if($sellerId==$val['staffid']){
                    $selfRank['count']=$val['count'];
                    $selfRank['rank']=$rank-1;
                }
            }
            $res['count']=$customerCount;
            $res['selfRank']=$selfRank;
            $res['data']=$customerList;
        }
        if(count($res) && is_array($res)){
            $code = 1;
            $data = $res;
            $msg = '获取排名成功！';
        }else{

            $res['count']=0;
            $res['selfRank']=$selfRank;
            $res['data']=[];
            $code = 1;
            $data = $res;
            $msg = '暂时没有排名！';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //网上经典的计算中奖概率方法
    function getRand($proArr) {
        $data = '';
        $proSum = array_sum($proArr); //概率数组的总概率精度
        foreach ($proArr as $k => $v) { //概率数组循环
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $data = $k;
                break;
            } else {
                $proSum -= $v;
            }
        }
        unset($proArr);
        return $data;
    }
}