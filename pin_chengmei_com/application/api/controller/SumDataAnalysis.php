<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\api\controller;
use think\Db;
/**
 * swagger: 统计数据分析-访问概况
 */
class SumDataAnalysis extends SumHour
{
    protected $code = 0;
    protected $data = [];
    protected $msg = '暂无数据';

    //访问数据统计
    public function visitData(){
        //请求参数,门店id
        $storeid=input('param.storeid',0);// 0=>所有门店
        // 初始化数据
        $rest = [];$arr1=[];
        $arr2=['visit_num'=>0,'day_rate'=>'-','day_rate_flag'=>0,'week_rate'=>'-','week_rate_flag'=>0,'month_rate'=>'-','month_rate_flag'=>0,'visit_new_num'=>0,'day_new_rate'=>'-','day_new_rate_flag'=>0,'week_new_rate'=>'-','week_new_rate_flag'=>0,'month_new_rate'=>'-','month_new_rate_flag'=>0];
        $arr1 = ['dt'=>date("Y-m-d",strtotime("-1 day"))];// 昨日日期
        $res1 = Db::table('ims_bwk_branch')->field('id,title')->where('id',$storeid)->limit(1)->find();
        if($res1){
            $arr1['title'] = $res1['title'];
        }else{
            $arr1['title'] = '所有门店';
        }
        $rest['stores'] = $arr1;
        // 访问人数,新访问人数数据
        $res2 = Db::name('sum_visit_day')->field('id,storeid,visit_num,visit_new_num,day_rate,day_new_rate,week_rate,week_new_rate,month_rate,month_new_rate')->where("storeid=$storeid and log_time='".$arr1['dt']."'")->limit(1)->find();
        if($res2){
            $arr2['visit_num'] = $res2['visit_num'];
            $arr2['day_rate'] = $res2['day_rate'];
            $arr2['day_rate_flag'] = 1;
            $arr2['week_rate'] = $res2['week_rate'];
            $arr2['week_rate_flag'] = 1;
            $arr2['month_rate'] = $res2['month_rate'];
            $arr2['month_rate_flag'] = 1;
            $arr2['visit_new_num'] = $res2['visit_new_num'];
            $arr2['day_new_rate'] = $res2['day_new_rate'];
            $arr2['day_new_rate_flag'] = 1;
            $arr2['week_new_rate'] = $res2['week_new_rate'];
            $arr2['week_new_rate_flag'] = 1;
            $arr2['month_new_rate'] = $res2['month_new_rate'];
            $arr2['month_new_rate_flag'] = 1;
            if($arr2['day_rate']<0){
                $arr2['day_rate_flag'] = 0;
            }
            $arr2['day_rate'] = (abs($arr2['day_rate']*100)).'%';
            if($arr2['week_rate']<0){
                $arr2['week_rate_flag'] = 0;
            }
            $arr2['week_rate'] = (abs($arr2['week_rate']*100)).'%';
            if($arr2['month_rate']<0){
                $arr2['month_rate_flag'] = 0;
            }
            $arr2['month_rate'] = (abs($arr2['month_rate']*100)).'%';
            if($arr2['day_new_rate']<0){
                $arr2['day_new_rate_flag'] = 0;
            }
            $arr2['day_new_rate'] = (abs($arr2['day_new_rate']*100)).'%';
            if($arr2['week_new_rate']<0){
                $arr2['week_new_rate_flag'] = 0;
            }
            $arr2['week_new_rate'] = (abs($arr2['week_new_rate']*100)).'%';
            if($arr2['month_new_rate']<0){
                $arr2['month_new_rate_flag'] = 0;
            }
            $arr2['month_new_rate'] = (abs($arr2['month_new_rate']*100)).'%';
        }
        $rest['visits'] = $arr2;
        $this->data = $rest;
        if($arr2 || $arr1){
            $this->code = 1;
            $this->msg = '获取数据成功';
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
    // 趋势图
    public function trendImg(){
        //请求参数,门店id
        $storeid=input('param.storeid',0);// 0=>所有门店
        $dt = input('param.dt',1);// 默认今天 1=>今天,-1=>昨天,7=>前7天
        // 初始化数据
        $rest = [];$arr1=[];$arr2=[];
        // 今日和昨日
        if($dt == 1 || $dt == -1){
            $dt = $dt==1?date('Y-m-d'):date("Y-m-d",strtotime("-1 day"));
            $res = Db::name('sum_visit_hour')->field('storeid,hours,visit_num,visit_new_num,log_time')->where("storeid=$storeid and log_time='".$dt."' and hours>=0")->group('hours')->order('hours asc')->select();
            $arr3['dt'] = ['00:00'=>0,'01:00'=>0,'02:00'=>0,'03:00'=>0,'04:00'=>0,'05:00'=>0,'06:00'=>0,'07:00'=>0,'08:00'=>0,'09:00'=>0,'10:00'=>0,'11:00'=>0,'12:00'=>0,'13:00'=>0,'14:00'=>0,'15:00'=>0,'16:00'=>0,'17:00'=>0,'18:00'=>0,'19:00'=>0,'20:00'=>0,'21:00'=>0,'22:00'=>0,'23:00'=>0,'24:00'=>0];
            if($res){
                foreach($res as $v){
                    $arr1['dt'] = sprintf("%02d", $v['hours']).':00';
                    $arr3['dt'][$arr1['dt']] = $v['visit_num'];
                }
                foreach ($arr3['dt'] as $k3=>$v3) {
                    $arr2_1['dt'] = $k3;
                    $arr2_1['cnt'] = $v3;
                    $arr2[] = $arr2_1;
                }
            }

        }else{
            // 按7日排序
            $dt = date("Y-m-d",strtotime("-7 day"));
            $res = Db::name('sum_visit_day')->field('storeid,visit_num,visit_new_num,log_time')->where("storeid=$storeid and log_time>='".$dt."'")->group('log_time')->order('log_time asc')->select();
            $arr3_1 = [$dt,date("Y-m-d",strtotime("-6 day")),date("Y-m-d",strtotime("-5 day")),date("Y-m-d",strtotime("-4 day")),date("Y-m-d",strtotime("-3 day")),date("Y-m-d",strtotime("-2 day")),date("Y-m-d",strtotime("-1 day"))];
            $arr3_2 = [0,0,0,0,0,0,0];
            if($res){
                foreach($res as $v){
                    foreach ($arr3_1 as $k3=>$v3_1) {
                        if($v['log_time'] == $v3_1){
                            $arr3_2[$k3] = $v['visit_num'];
                        }
                    }
                }
                $arr3_3 = array_combine($arr3_1,$arr3_2);
                foreach ($arr3_3 as $k3=>$v3_3) {
                    $arr2_3['dt'] = $k3;
                    $arr2_3['cnt'] = $v3_3;
                    $arr2[] = $arr2_3;
                }
            }
        }
        $rest = $arr2;
        if($rest){
            $this->code = 1;
            $this->data = $rest;
            $this->msg = '趋势图获取成功';
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
    // 门店列表
    public function storeList(){
        // 参与活动门店
        $res_canyu = Db::name('tuan_info')->field('DISTINCT storeid')->select();
        if($res_canyu){
            foreach ($res_canyu as $v) {
                $storeids[] = $v['storeid'];
            }
            $res = Db::table('ims_bwk_branch')->field('id storeid,title,sign')->where('id','in',$storeids)->order('id desc')->select();
            if($res){
                $data = ['storeid'=>0,'title'=>'所有门店','sign'=>''];
                $res[] = $data;
                $this->code = 1;
                $this->msg = '获取门店列表成功';
                $this->data = $res;
            }
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
}