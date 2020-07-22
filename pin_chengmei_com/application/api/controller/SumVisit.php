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
 * swagger: 数据分析-访问概况
 */
class SumVisit
{
    // 昨日访问概况
    public function visitYesterday($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $arr['dt'] = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $eve_day = date('Y-m-d',strtotime("-2 day"));// 前天日期
        // 昨日比例 = 昨天-前天/前天
        $res_v1 = Db::name('sum_visit_day')->field('storeid,visit_num,visit_new_num,log_time')->where("storeid=$storeid and log_time>='$eve_day' and log_time<='".$arr['dt']."'")->select();
        if($res_v1){
            $brr = ['yes_day_visit'=>0,'yes_day_visit_new'=>0,'eve_day_visit'=>0,'eve_day_visit_new'=>0];
            foreach ($res_v1 as $v1) {
                // 昨日数据
                if($v1['log_time'] == $arr['dt']){
                    $brr['yes_day_visit'] = $v1['visit_num'];
                    $brr['yes_day_visit_new'] = $v1['visit_new_num'];
                }else{
                    // 前日数据
                    $brr['eve_day_visit'] = $v1['visit_num'];
                    $brr['eve_day_visit_new'] = $v1['visit_new_num'];
                }
            }
            if(!empty($brr)){
                $yes_1['day_rate'] = 0;
                if($brr['eve_day_visit']){
                    $yes_1['day_rate'] = round(($brr['yes_day_visit']-$brr['eve_day_visit'])/$brr['eve_day_visit'],4);
                }
                $yes_1['day_new_rate'] = 0;
                if($brr['eve_day_visit_new']){
                    $yes_1['day_new_rate'] = round(($brr['yes_day_visit_new']-$brr['eve_day_visit_new'])/$brr['eve_day_visit_new'],4);
                }
                if(!empty($yes_1)){
                    // 更新昨日比率
                    $data_v['day_rate'] = $yes_1['day_rate'];
                    $data_v['day_new_rate'] = $yes_1['day_new_rate'];
                    if($data_v['day_rate'] || $data_v['day_rate']){
                        Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$arr['dt']."'")->update($data_v);
                    }
                }
            }
        }
        return $data_v;
    }
    // 上周访问概况
    public function visitWeek($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $dt3 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $dt1 = date('Y-m-d',strtotime('-2 monday', time()));// 上周一时间
        $dt2 = date('Y-m-d',strtotime('-1 sunday', time()));// 上周日时间
        // 上周比例 = 昨天-上周平均值/上周平均值
        $data_v = [];
        $res_v1 = Db::name('sum_visit_day')->field('storeid,visit_num,visit_new_num,log_time')->where("storeid=$storeid and log_time>='$dt1' and log_time<='".$dt2."'")->group('log_time')->select();
        if($res_v1){
            $cnt = count($res_v1);//有数据的天数
            $arr_week = ['visit_num'=>0,'visit_new_num'=>0,];
            foreach ($res_v1 as $v1) {
                $arr_week['visit_num']+=$v1['visit_num'];
                $arr_week['visit_new_num']+=$v1['visit_new_num'];
            }
            // 上周平均
            $arr_week['average_visit'] = $arr_week['visit_num']/$cnt;
            $arr_week['average_visit_new'] = $arr_week['visit_new_num']/$cnt;
            // 昨天的
            $res_yes = $this->yesDay($storeid);
            if($res_yes){
                // 更新上周比率
                $yes_1['week_rate'] = 0;
                if($arr_week['average_visit']){
                    $yes_1['week_rate'] = round(($res_yes['visit_num']-$arr_week['average_visit'])/$arr_week['average_visit'],4);
                }
                $yes_1['week_new_rate'] = 0;
                if($arr_week['average_visit_new']){
                    $yes_1['week_new_rate'] = round(($res_yes['visit_new_num']-$arr_week['average_visit_new'])/$arr_week['average_visit_new'],4);
                }
                $data_v['week_rate'] = $yes_1['week_rate'];
                $data_v['week_new_rate'] = $yes_1['week_new_rate'];
                if($data_v['week_rate'] || $data_v['week_new_rate']){
                    Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$dt3."'")->update($data_v);
                }
            }
        }
        return $data_v;
    }
    // 上月访问概况
    public function visitMonth($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $dt3 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $dt1 =  date('Y-m-d',strtotime(date('Y-m-01', strtotime('-1 month'))));// 上月一号时间
        $dt2 =  date('Y-m-d',strtotime(date('Y-m', time()) . '-01 00:00:00'));// 本月1号

        // 上月比例 = 昨天-上月平均值/上月平均值
        $res_v1 = Db::name('sum_visit_day')->field('storeid,visit_num,visit_new_num,log_time')->where("storeid=$storeid and log_time>='$dt1' and log_time<'".$dt2."'")->group('log_time')->select();
        if($res_v1){
            $cnt = count($res_v1);//有数据的天数
            $arr_week = ['visit_num'=>0,'visit_new_num'=>0,];
            foreach ($res_v1 as $v1) {
                $arr_week['visit_num']+=$v1['visit_num'];
                $arr_week['visit_new_num']+=$v1['visit_new_num'];
            }
            // 上月平均
            $arr_week['average_visit'] = $arr_week['visit_num']/$cnt;
            $arr_week['average_visit_new'] = $arr_week['visit_new_num']/$cnt;
            // 昨天的
            $res_yes = $this->yesDay($storeid);
            if($res_yes){
                // 更新上月比率
                $yes_1['week_rate'] = 0;
                if($arr_week['average_visit']){
                    $yes_1['week_rate'] = round(($res_yes['visit_num']-$arr_week['average_visit'])/$arr_week['average_visit'],4);
                }
                $yes_1['week_new_rate'] = 0;
                if($arr_week['average_visit_new']){
                    $yes_1['week_new_rate'] = round(($res_yes['visit_new_num']-$arr_week['average_visit_new'])/$arr_week['average_visit_new'],4);
                }
                $data_v['month_rate'] = $yes_1['week_rate'];
                $data_v['month_new_rate'] = $yes_1['week_new_rate'];
                if($data_v['month_rate'] || $data_v['month_new_rate']){
                    Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$dt3."'")->update($data_v);
                }

            }
        }
    }
    // 昨天访问数据
    public function yesDay($storeid=0){
        $dt1 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $res_v1 = Db::name('sum_visit_day')->field('storeid,visit_num,visit_new_num,log_time')->where("storeid=$storeid and log_time='$dt1'")->limit(1)->find();
        return $res_v1;

    }

    // 昨日成交统计
    public function dealYesterday($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $arr['dt'] = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $eve_day = date('Y-m-d',strtotime("-2 day"));// 前天日期
        // 昨日比例 = 昨天-前天/前天
        $res_v1 = Db::name('sum_visit_day')->field('storeid,sum_price,sum_ord_num,log_time')->where("storeid=$storeid and log_time>='$eve_day' and log_time<='".$arr['dt']."'")->order('log_time desc')->select();
        if($res_v1){
            $brr = ['yes_day_price'=>0,'yes_day_num'=>0,'eve_day_price'=>0,'eve_day_num'=>0];
            foreach ($res_v1 as $v1) {
                // 昨日数据
                if($v1['log_time'] == $arr['dt']){
                    $brr['yes_day_price'] = $v1['sum_price'];
                    $brr['yes_day_num'] = $v1['sum_ord_num'];
                }else{
                    // 前日数据
                    $brr['eve_day_price'] = $v1['sum_price'];
                    $brr['eve_day_num'] = $v1['sum_ord_num'];
                }
            }
            if(!empty($brr)){
                $yes_1['day_rate'] = 0;
                if($brr['eve_day_price']){
                    $yes_1['day_rate'] = round(($brr['yes_day_price']-$brr['eve_day_price'])/$brr['eve_day_price'],4);
                }
                $yes_1['day_new_rate'] = 0;
                if($brr['eve_day_num']){
                    $yes_1['day_new_rate'] = round(($brr['yes_day_num']-$brr['eve_day_num'])/$brr['eve_day_num'],4);
                }
                if(!empty($yes_1)){
                    // 更新昨日比率
                    $data_v['price_day_rate'] = $yes_1['day_rate'];
                    $data_v['num_day_rate'] = $yes_1['day_new_rate'];
                    if($data_v['price_day_rate'] || $data_v['num_day_rate']){
                        Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$arr['dt']."'")->update($data_v);
                    }

                }
            }
        }
        return $data_v;
    }
    // 上周访问概况
    public function dealWeek($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $data_v = [];
        $dt3 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $dt1 = date('Y-m-d',strtotime('-2 monday', time()));// 上周一时间
        $dt2 = date('Y-m-d',strtotime('-1 sunday', time()));// 上周日时间
        // 上周比例 = 昨天-上周平均值/上周平均值
        $res_v1 = Db::name('sum_visit_day')->field('storeid,sum_price,sum_ord_num,log_time')->where("storeid=$storeid and log_time>='$dt1' and log_time<='".$dt2."'")->group('log_time')->order('log_time desc')->select();
        if($res_v1){
            $cnt = count($res_v1);//有数据的天数
            $arr_week = ['sum_price'=>0,'sum_ord_num'=>0,];
            foreach ($res_v1 as $v1) {
                $arr_week['sum_price']+=$v1['sum_price'];
                $arr_week['sum_ord_num']+=$v1['sum_ord_num'];
            }
            // 上周平均
            $arr_week['average_price'] = $arr_week['sum_price']/$cnt;
            $arr_week['average_num'] = $arr_week['sum_ord_num']/$cnt;
            // 昨天的
            $res_yes = $this->yesCjDay($storeid);
            if($res_yes){
                // 更新上周比率
                $yes_1['week_rate'] = 0;
                if($arr_week['average_price']){
                    $yes_1['week_rate'] = round(($res_yes['sum_price']-$arr_week['average_price'])/$arr_week['average_price'],4);
                }
                $yes_1['week_new_rate'] = 0;
                if($arr_week['average_num']){
                    $yes_1['week_new_rate'] = round(($res_yes['sum_ord_num']-$arr_week['average_num'])/$arr_week['average_num'],4);
                }
                $data_v['price_week_rate'] = $yes_1['week_rate'];
                $data_v['num_week_rate'] = $yes_1['week_new_rate'];
                if($data_v['price_week_rate'] || $data_v['num_week_rate']){
                    Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$dt3."'")->update($data_v);
                }

            }
        }
        return $data_v;
    }
    // 上月访问概况
    public function dealMonth($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $data_v = [];
        $dt3 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $dt1 =  date('Y-m-d',strtotime(date('Y-m-01', strtotime('-1 month'))));// 上月一号时间
        $dt2 =  date('Y-m-d',strtotime(date('Y-m', time()) . '-01 00:00:00'));// 本月1号

        // 上月比例 = 昨天-上月平均值/上月平均值
        $res_v1 = Db::name('sum_visit_day')->field('storeid,sum_price,sum_ord_num,log_time')->where("storeid=$storeid and log_time>='$dt1' and log_time<'".$dt2."'")->group('log_time')->select();
        if($res_v1){
            $cnt = count($res_v1);//有数据的天数
            $arr_week = ['sum_price'=>0,'sum_ord_num'=>0,];
            foreach ($res_v1 as $v1) {
                $arr_week['sum_price']+=$v1['sum_price'];
                $arr_week['sum_ord_num']+=$v1['sum_ord_num'];
            }
            // 上月平均
            $arr_week['average_price'] = $arr_week['sum_price']/$cnt;
            $arr_week['average_num'] = $arr_week['sum_ord_num']/$cnt;
            // 昨天的
            $res_yes = $this->yesCjDay($storeid);
            if($res_yes){
                // 更新上月比率
                $yes_1['month_rate'] = 0;
                if($arr_week['average_price']){
                    $yes_1['month_rate'] = round(($res_yes['sum_price']-$arr_week['average_price'])/$arr_week['average_price'],4);
                }
                $yes_1['month_new_rate'] = 0;
                if($arr_week['average_num']){
                    $yes_1['month_new_rate'] = round(($res_yes['sum_ord_num']-$arr_week['average_num'])/$arr_week['average_num'],4);
                }
                $data_v['price_month_rate'] = $yes_1['month_rate'];
                $data_v['num_month_rate'] = $yes_1['month_new_rate'];
                if($data_v['price_month_rate'] || $data_v['num_month_rate']){
                    Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$dt3."'")->update($data_v);
                }

            }
        }
        return $data_v;
    }
    // 昨天成交数据
    public function yesCjDay($storeid=0){
        $dt1 = strtotime(date('Y-m-d',strtotime("-1 day")));// 昨天日期
        $dt2 = strtotime(date('Y-m-d'));//今天日期
        $data1 = [];
        // 如果storeid=0统计整的
        if($storeid == 0){
            // 昨天成交金额
            $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=1 and l.success_time>='".$dt1."' and l.success_time<'".$dt2."' ")->field('sum(ti.p_price) sum_price,l.storeid')->limit(1)->find();
            if($res) {
                $data1['sum_price'] = round($res['sum_price'], 2);// 成交金额
                $data1['storeid'] = $res['storeid'];
                // 统计昨天-今天成交单数
                $res_cj = Db::name('tuan_info ti')->join(['pt_tuan_list' => 'l'], 'l.tuan_id=ti.id', 'LEFT')->where("l.status=1 and l.success_time>='" . $dt1 . "' and l.success_time<'" . $dt2 . "' ")->field('count(l.id) cnt,l.storeid')->limit(1)->find();
                if ($res_cj) {
                    $data1['sum_ord_num'] = $res_cj['cnt'];
                }
            }
        }else{
            // 昨天成交金额
            $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=1 and l.success_time>='".$dt1."' and l.success_time<'".$dt2."' and l.storeid=$storeid ")->field('sum(ti.p_price) sum_price,l.storeid')->group('l.storeid')->order('l.storeid desc')->limit(1)->find();
            if($res) {
                $data1['sum_price'] = round($res['sum_price'], 2);// 成交金额
                $data1['storeid'] = $res['storeid'];
                // 统计昨天-今天成交单数
                $res_cj = Db::name('tuan_info ti')->join(['pt_tuan_list' => 'l'], 'l.tuan_id=ti.id', 'LEFT')->where("l.status=1 and l.success_time>='" . $dt1 . "' and l.success_time<'" . $dt2 . "' ")->where('l.storeid',$storeid)->field('count(l.id) cnt,l.storeid')->order('l.storeid desc')->group('l.storeid')->limit(1)->find();
                if ($res_cj) {
                    $data1['sum_ord_num'] = $res_cj['cnt'];
                }
            }
        }

        return $data1;
    }
    // 昨天新老顾客数据
    public function yesGkDay($storeid=0){
        $dt1 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $map['storeid'] = $storeid;
        $map['log_time'] = $dt1;
        $res = Db::name('sum_visit_day')->field('xgk_num,lgk_num')->where($map)->limit(1)->find();
        return $res;
    }
    // 昨日新老顾客统计
    public function gukeYesterday($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $data_v=[];
        $arr['dt'] = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $eve_day = date('Y-m-d',strtotime("-2 day"));// 前天日期
        // 昨日比例 = 昨天-前天/前天
        $res_v1 = Db::name('sum_visit_day')->field('storeid,xgk_num,lgk_num,log_time')->where("storeid=$storeid and log_time>='$eve_day' and log_time<='".$arr['dt']."'")->order('log_time desc')->select();
        if($res_v1){
            $brr = ['yes_day_xgk'=>0,'yes_day_lgk'=>0,'eve_day_xgk'=>0,'eve_day_lgk'=>0];
            foreach ($res_v1 as $v1) {
                // 昨日数据
                if($v1['log_time'] == $arr['dt']){
                    $brr['yes_day_xgk'] = $v1['xgk_num'];
                    $brr['yes_day_lgk'] = $v1['lgk_num'];
                }else{
                    // 前日数据
                    $brr['eve_day_xgk'] = $v1['xgk_num'];
                    $brr['eve_day_lgk'] = $v1['lgk_num'];
                }
            }
            if(!empty($brr)){
                $yes_1['day_rate'] = 0;
                if($brr['eve_day_xgk']){
                    $yes_1['day_rate'] = round(($brr['yes_day_xgk']-$brr['eve_day_xgk'])/$brr['eve_day_xgk'],4);
                }
                $yes_1['day_new_rate'] = 0;
                if($brr['eve_day_lgk']){
                    $yes_1['day_new_rate'] = round(($brr['yes_day_lgk']-$brr['eve_day_lgk'])/$brr['eve_day_lgk'],4);
                }
                if(!empty($yes_1)){
                    // 更新昨日比率
                    $data_v['xgk_day_rate'] = $yes_1['day_rate'];
                    $data_v['lgk_day_rate'] = $yes_1['day_new_rate'];
                    if($data_v['xgk_day_rate'] || $data_v['lgk_day_rate']){
                        Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$arr['dt']."'")->update($data_v);
                    }
                }
            }
        }
        return $data_v;
    }
    // 上周访问概况
    public function gukeWeek($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $data_v=[];
        $dt3 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $dt1 = date('Y-m-d',strtotime('-2 monday', time()));// 上周一时间
        $dt2 = date('Y-m-d',strtotime('-1 sunday', time()));// 上周日时间
        // 上周比例 = 昨天-上周平均值/上周平均值
        $res_v1 = Db::name('sum_visit_day')->field('storeid,xgk_num,lgk_num,log_time')->where("storeid=$storeid and log_time>='$dt1' and log_time<='".$dt2."'")->group('log_time')->order('log_time desc')->select();
        if($res_v1){
            $cnt = count($res_v1);//有数据的天数
            $arr_week = ['sum_xgk_num'=>0,'sum_lgk_num'=>0,];
            foreach ($res_v1 as $v1) {
                $arr_week['sum_xgk_num']+=$v1['xgk_num'];
                $arr_week['sum_lgk_num']+=$v1['lgk_num'];
            }
            // 上周平均
            $arr_week['average_xgk'] = $arr_week['sum_xgk_num']/$cnt;
            $arr_week['average_lgk'] = $arr_week['sum_lgk_num']/$cnt;
            // 昨天的
            $res_yes = $this->yesGkDay($storeid);
            if($res_yes){
                // 更新上周比率
                $yes_1['week_rate'] = 0;
                if($arr_week['average_xgk']){
                    $yes_1['week_rate'] = round(($res_yes['xgk_num']-$arr_week['average_xgk'])/$arr_week['average_xgk'],4);
                }
                $yes_1['week_new_rate'] = 0;
                if($arr_week['sum_lgk_num']){
                    $yes_1['week_new_rate'] = round(($res_yes['lgk_num']-$arr_week['sum_lgk_num'])/$arr_week['sum_lgk_num'],4);
                }
                $data_v['xgk_week_rate'] = $yes_1['week_rate'];
                $data_v['lgk_week_rate'] = $yes_1['week_new_rate'];
                if($data_v['xgk_week_rate'] || $data_v['lgk_week_rate']){
                    Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$dt3."'")->update($data_v);
                }

            }
        }
        return $data_v;
    }
    // 上月访问概况
    public function gukeMonth($storeid=0){
        $storeid1 = input('param.storeid');
        if($storeid1){
            $storeid = $storeid1;
        }
        $dt3 = date('Y-m-d',strtotime("-1 day"));// 昨天日期
        $dt1 =  date('Y-m-d',strtotime(date('Y-m-01', strtotime('-1 month'))));// 上月一号时间
        $dt2 =  date('Y-m-d',strtotime(date('Y-m', time()) . '-01 00:00:00'));// 本月1号

        // 上月比例 = 昨天-上月平均值/上月平均值
        $res_v1 = Db::name('sum_visit_day')->field('storeid,xgk_num,lgk_num,log_time')->where("storeid=$storeid and log_time>='$dt1' and log_time<'".$dt2."'")->group('log_time')->select();
        if($res_v1){
            $cnt = count($res_v1);//有数据的天数
            $arr_week = ['sum_xgk_num'=>0,'sum_lgk_num'=>0,];
            foreach ($res_v1 as $v1) {
                $arr_week['sum_xgk_num']+=$v1['lgk_num'];
                $arr_week['sum_lgk_num']+=$v1['lgk_num'];
            }
            // 上月平均
            $arr_week['average_xgk'] = $arr_week['sum_xgk_num']/$cnt;
            $arr_week['average_lgk'] = $arr_week['sum_lgk_num']/$cnt;
            // 昨天的
            $res_yes = $this->yesGkDay($storeid);
            if($res_yes){
                // 更新上月比率
                $yes_1['month_rate'] = 0;
                if($arr_week['average_xgk']){
                    $yes_1['month_rate'] = round(($res_yes['xgk_num']-$arr_week['average_xgk'])/$arr_week['average_xgk'],4);
                }
                $yes_1['month_new_rate'] = 0;
                if($arr_week['average_lgk']){
                    $yes_1['month_new_rate'] = round(($res_yes['lgk_num']-$arr_week['average_lgk'])/$arr_week['average_lgk'],4);
                }
                $data_v['xgk_month_rate'] = $yes_1['month_rate'];
                $data_v['lgk_month_rate'] = $yes_1['month_new_rate'];
                if($data_v['xgk_month_rate'] || $data_v['lgk_month_rate']){
                    Db::name('sum_visit_day')->where("storeid=$storeid and log_time='".$dt3."'")->update($data_v);
                }
            }
        }
    }
}