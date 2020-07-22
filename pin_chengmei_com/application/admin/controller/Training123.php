<?php

namespace app\admin\controller;
use app\admin\model\BankInterestrateModel;
use app\admin\model\BankModel;
use app\admin\model\BranchModel;
use app\admin\model\ChristmasModel;
use app\admin\model\CouponModel;
use app\admin\model\Node;
use app\admin\model\UserType;
use think\Db;
use think\Loader;

class Training123 extends Base
{

    //减脂训练营活动配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array=array('activity_status'=>$param['activity_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'back_day'=>$param['back_day']);
            Db::name('training_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $config=Db::name('training_config')->where('id',1)->find();
        $config['begin_time']=date('Y-m-d H:i:s',$config['begin_time']);
        $config['end_time']=date('Y-m-d H:i:s',$config['end_time']);
        $this->assign('config',$config);
        return $this->fetch();
    }

    /**
     * [index 减脂用户列表]
     */
    public function lists(){
        set_time_limit(0);
        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['member.mobile|member.realname'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('training_member')->alias('l')->join(['ims_bj_shopn_member' => 'member'],'member.id=l.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->group('l.uid')->field('l.id')->select();  //总数据
        $allpage = intval(ceil(count($count) / $limits));
        if($export){
            $lists = Db::name('training_member')->alias('l')->join(['ims_bj_shopn_member' => 'member'],'member.id=l.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('l.*,member.id mid,member.realname,member.mobile,member.staffid,member.isadmin,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->order('l.uid,l.day,l.insert_time')->select();
        }else{
            $lists = Db::name('training_member')->alias('l')->join(['ims_bj_shopn_member' => 'member'],'member.id=l.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('l.*,count(l.uid) num,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->group('l.uid')->order('l.id desc')->select();
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {

                $firstJoinDay=Db::name('training_member')->where(['uid'=>$v['uid']])->order('id')->value('insert_time');
                $end='';
                if($firstJoinDay) {
                    $joinDay = $this->count_days($firstJoinDay, time());//获取自己打卡天数
                    if($joinDay>21){
                        $end='已结营';
                    }
                }

                if($joinDay==21) {
                    $getCurrent = Db::name('training_member')->where(['uid' => $v['uid'], 'day' => $joinDay])->field('weight')->find();
                    if (count($getCurrent) && is_array($getCurrent)) {
                        $end = '已结营';
                    }
                }


                $num=Db::name('training_member')->where(['uid'=>$v['uid'],'day'=>1])->count();
                if($num>1){
                    $again='存在多次打卡';
                }else{
                    $again='第一轮打卡';
                }


                if($v['award']==1){
                    $award='连续打卡7天奖励';
                }elseif($v['award']==2){
                    $award='连续打卡21天奖励';
                }else{
                    $award='无';
                }
                if($v['isadmin']==1){
                    $role='店老板';
                }elseif ($v['mid']==$v['staffid']){
                    if($v['sign']=='000-000'){
                        $role='讲师';
                    }else{
                        $role='美容师';
                    }
                }else{
                    $role='顾客';
                }
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$v['staffid'])->field('mobile,realname')->find();
                $data[$k]['sellername']=$sellerInfo['realname'];
                $data[$k]['sellermobile']=$sellerInfo['mobile'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['role']=$role;
                $data[$k]['day']=$v['day'];
                $data[$k]['weight']=$v['weight'];
                $data[$k]['award']=$award;
                $data[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
                $data[$k]['is_end']=$end;
                $data[$k]['again']=$again;

            }
            print_r($data);
            die();
            $filename = "打卡用户列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话','用户角色','打卡计数','打卡体重(Kg)','打卡奖励','打卡时间','是否结营','多轮打卡');
            $widths=array('10','30','10','15','15','15','15','10','10','15','25','25','20');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function member_list_info(){
        $getBackDay=Db::name('training_config')->where('id',1)->value('back_day');
        $getBackDay=explode(',',$getBackDay);
        $uid = input('uid');
        $map['l.uid'] = ['eq',$uid];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 200;// 获取总条数
        $count = Db::name('training_member')->alias('l')->join(['ims_bj_shopn_member' => 'member'],'member.id=l.uid','left')->where($map)->field('l.id')->count();  //总数据
        $allpage = intval(ceil(count($count) / $limits));
        $lists = Db::name('training_member')->alias('l')->join(['ims_bj_shopn_member' => 'member'],'member.id=l.uid','left')->field('l.*,member.realname,member.mobile')->where($map)->page($Nowpage, $limits)->order('l.id')->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['nums']=$k+1;
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            if($getBackDay){
                if(in_array($v['day'],$getBackDay)){
                    $lists[$k]['bs']=1;
                    $getData=Db::name('training_measure')->where(['record_day'=>$v['day'],'uid'=>$v['uid']])->find();
                    $imgs='';
                    if($getData['pic1']){
                        $imgs.="<img src='".$getData['pic1']."' width='100'>";
                    }
                    if($getData['pic2']){
                        $imgs.="<img src='".$getData['pic2']."' width='100'>";
                    }
                    if($getData['pic3']){
                        $imgs.="<img src='".$getData['pic3']."' width='100'>";
                    }
                    $str="<div class='panel panel-success'>";
                    $str.="<div class='panel-heading'>第".$getData['record_day']."天测量数据</div>";
                    $str.=" <div class='panel-body'>";
                    $str.="<table class='table table-bordered table-hover'>";
                    $str.="<tr><td width='25%'>测量日期：".date('Y-m-d H:i:s',$getData['insert_time'])."</td><td width='25%'>年龄：".$getData['age']."岁</td><td width='25%'>体重：".$getData['weight']."Kg</td><td width='25%'>胸围：".$getData['bust']."cm</td></tr>";
                    $str.="<tr><td width='25%'>下胸围：".$getData['low_bust']."cm</td><td width='25%'>腰围：".$getData['waist']."cm</td><td width='25%'>臀围：".$getData['hips']."cm</td><td width='25%'>左大腿围：".$getData['left_thigh']."cm</td></tr>";
                    $str.="<tr><td width='25%'>右大腿围：".$getData['right_thigh']."cm</td><td width='25%'>小腿肚：".$getData['crus']."cm</td><td width='25%'>脚踝：".$getData['ankle']."cm</td><td width='25%'>体指率：".$getData['body_fat']."%</td></tr>";
                    $str.="<tr><td width='25%'>体重指数：".$getData['weight_index']."</td><td width='25%'>日间内衣型号：".$getData['underclothes1']."</td><td width='25%'>夜间内衣型号：".$getData['underclothes2']."</td><td width='25%'>营养代餐：".$getData['substitute']."</td></tr>";
                    $str.="<tr><td colspan='4'>图片列表：".$imgs."</td></tr>";
                    $str.="</table></div></div>";
                    $lists[$k]['info']=$str;
                }else{
                    $lists[$k]['bs']=0;
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('uid', $uid); //总页数
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    //两个日期只差
    public function count_days($a,$b){
        $a_dt = getdate($a);
        $b_dt = getdate($b);
        $a_new = mktime(12, 0, 0, $a_dt['mon'], $a_dt['mday'], $a_dt['year']);
        $b_new = mktime(12, 0, 0, $b_dt['mon'], $b_dt['mday'], $b_dt['year']);
        return (round(abs($a_new-$b_new)/86400))+1;
    }

}
