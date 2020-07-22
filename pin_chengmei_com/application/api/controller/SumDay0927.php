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
 * swagger: 统计按日数据
 */
set_time_limit(0);
class SumDay extends SumHour
{
    // 统计每天的访问用户数据到统计表
    public function visitDay(){

        $rest = [];//每天所有门店访问和新访问数据
        $sum_visit_num = 0;$sum_visit_new_num = 0;$sum_xgk_num=0;$sum_lgk_num=0;
        $dt1 = strtotime(date("Y-m-d",strtotime("-1 day")));//昨天
        $dt2 = strtotime(date("Y-m-d"));// 今天
//        $dt1 = strtotime(date("Y-m-d",strtotime("-2 day")));//前天
//        $dt2 = strtotime(date("Y-m-d",strtotime("-1 day")));// 昨天
        $log_time_dt1 = date("Y-m-d",$dt1);
        // 每天统计插入一条参与门店记录
        $this->ChuDay($dt1);

//        // 更新按日成交金额,成交单数
        $this->dealDay($dt1,$dt2);
        $sumvisit = new SumVisit();
//        $res1 = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->select();
        // 统计昨天访问人数
        $this->getVisit($dt1,$dt2);
        // 每日更新活动总额度和已完成额度
        $this->overLimitTrend($dt1,$dt2);
        // 统计失效情况
        $this->invalidDay(date("Y-m-d",strtotime("-1 day")),date("Y-m-d"));
        $res1 = $this->actStore();
        if($res1){
            foreach($res1 as $v1){

                $sumvisit->visitYesterday($v1['storeid']);// 昨日数据比率统计
                $sumvisit->visitWeek($v1['storeid']);//上周数据比率统计
                $sumvisit->visitMonth($v1['storeid']);//上周数据比率统计

                $sumvisit->dealYesterday($v1['storeid']);//昨日成交金额比率统计
                $sumvisit->dealWeek($v1['storeid']);//上周成交金额比率统计
                $sumvisit->dealMonth($v1['storeid']);//上月成交金额比率统计

                // 统计昨天老顾客和新顾客
//                $res_gk = $this->xlgkDay($dt1,$dt2,$v1['storeid']);
                $res_gk = $this->xlGuke($dt1,$dt2,$v1['storeid']);
                $arr_gk = [];
                if($res_gk){
                    $arr_gk['xgk_num'] = $res_gk['customer_new'];
                    $arr_gk['lgk_num'] = $res_gk['customer_old'];
                    $sum_xgk_num += $arr_gk['xgk_num'];
                    $sum_lgk_num += $arr_gk['lgk_num'];
                }
                if($arr_gk['xgk_num'] || $arr_gk['lgk_num']){
                    $res_upd = Db::name('sum_visit_day')->where("storeid=".$v1['storeid']." and log_time='".$log_time_dt1."'")->update($arr_gk);
                }

                // 更新每日新老顾客比率
//                $sumvisit->gukeYesterday($v1['storeid']);
//                $sumvisit->gukeWeek($v1['storeid']);
//                $sumvisit->gukeMonth($v1['storeid']);
            }
            // 统计所有门店统计数据
            $sumvisit->visitYesterday(0);// 昨日数据比率统计-所有门店
            $sumvisit->visitWeek(0);//上周数据比率统计-所有门店
            $sumvisit->visitMonth(0);//上周数据比率统计-所有门店

            $sumvisit->dealYesterday(0);//昨日成交金额比率统计-所有门店
            $sumvisit->dealWeek(0);//上周成交金额比率统计-所有门店
            $sumvisit->dealMonth(0);//上月成交金额比率统计-所有门店

//            $sumvisit->gukeYesterday(0);//昨日新老顾客比率统计-所有门店
//            $sumvisit->gukeWeek(0);//上周新老顾客比率统计-所有门店
//            $sumvisit->gukeMonth(0);//上月新老顾客比率统计-所有门店
        }


        // 更改总门店访问人数统计
//        $arr2['visit_num'] = $sum_visit_num;
//        $arr2['visit_new_num'] = $sum_visit_new_num;
        $arr2['xgk_num'] = $sum_xgk_num;
        $arr2['lgk_num'] = $sum_lgk_num;
        $res_upd = Db::name('sum_visit_day')->where("storeid=0 and log_time='".$log_time_dt1."'")->update($arr2);
        // 更新所有顾客统计比例
        $res1[] = ['storeid'=>0];
        foreach ($res1 as $v1) {
            $sumvisit->gukeYesterday($v1['storeid']);
            $sumvisit->gukeWeek($v1['storeid']);
            $sumvisit->gukeMonth($v1['storeid']);
        }


        $this->code = 1;
        $this->msg = '每天数据更新成功';
        // 记录日志
        parent::write_log(['msg'=>'每天定时任务-'.json_encode($this->data,JSON_UNESCAPED_UNICODE)]);
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    public function visitDay1()
    {

        $rest = [];//每天所有门店访问和新访问数据
        $sum_visit_num = 0;
        $sum_visit_new_num = 0;
        $sum_xgk_num = 0;
        $sum_lgk_num = 0;
        $dt1 = strtotime(date("Y-m-d", strtotime("-1 day")));//昨天
        $dt2 = strtotime(date("Y-m-d"));// 今天
        $dt3 = strtotime(date("Y-m-d", strtotime("-2 day")));//前天
//        $dt2 = strtotime(date("Y-m-d",strtotime("-1 day")));// 昨天
        $log_time_dt1 = date("Y-m-d", $dt1);
        $log_time_dt3 = date("Y-m-d", $dt3);
        // 每天统计插入一条参与门店记录
//        $this->ChuDay($dt1);

//        $map_t['insert_time'] = ['>=',$dt1];
//        $map['insert_time'] = ['<',$dt2];
//        $map['action'] = ['eq',0];
//        $map['first_login'] = ['eq',1];
////        // 统计昨天新访问人数
//        $res = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->select();
//        if($res){
//            foreach($res as $v){
//                $arr['visit_new_num'] = $v['cnt'];
//                $sum_visit_new_num += $arr['visit_new_num'];
//                $res_upd = Db::name('sum_visit_day')->where("storeid=".$v['storeid']." and log_time='".$log_time_dt1."'")->update($arr);
//            }
//        }
//        // 统计昨天访问人数
////        unset($map['first_login']);
//        $sumvisit = new SumVisit();
//        $res1 = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->select();
//          // 更新按日成交金额,成交单数
//        $dt1 = strtotime('2018-09-10');
//        $dt2 = strtotime('2018-09-11');
//        $res1 = $this->actStore();
//        if ($res1) {
//            foreach ($res1 as $v1) {
//                $res_gk = $this->xlGuke($dt1, $dt2, $v1['storeid']);
//                $arr_gk = [];
//                if ($res_gk) {
//                    $arr_gk['xgk_num'] = $res_gk['customer_new'];
//                    $arr_gk['lgk_num'] = $res_gk['customer_old'];
//                    $sum_xgk_num += $arr_gk['xgk_num'];
//                    $sum_lgk_num += $arr_gk['lgk_num'];
//                }
//                if ($arr_gk['xgk_num'] || $arr_gk['lgk_num']) {
//                    $res_upd = Db::name('sum_visit_day')->where("storeid=" . $v1['storeid'] . " and log_time='" . $log_time_dt1 . "'")->update($arr_gk);
//                }
////                $sumvisit->gukeYesterday($v1['storeid']);
////                $sumvisit->gukeWeek($v1['storeid']);
////                $sumvisit->gukeMonth($v1['storeid']);
//            }
//        }
        // 更新总的新老顾客
//        $arr2['xgk_num'] = $sum_xgk_num;
//        $arr2['lgk_num'] = $sum_lgk_num;
//        $res_upd = Db::name('sum_visit_day')->where("storeid=0 and log_time='".$log_time_dt1."'")->update($arr2);
//        $res1[] = ['storeid'=>0];
//        foreach ($res1 as $v1) {
//            $sumvisit->gukeYesterday($v1['storeid']);
//            $sumvisit->gukeWeek($v1['storeid']);
//            $sumvisit->gukeMonth($v1['storeid']);
//        }
        // 统计昨天访问人数
        $this->getVisit($dt1,$dt2);
        print_r(1);die;

        $this->code = 1;
        $this->msg = '每天数据更新成功';
        // 记录日志
        parent::write_log(['msg'=>'每天定时任务-'.json_encode($this->data,JSON_UNESCAPED_UNICODE)]);
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }

    // 每天统计插入一条参与门店记录
    public function ChuDay($dt1){
        // 每天统计插入一条参与门店记录
        $data_day = [];
        $res_canyu = Db::name('tuan_info')->field('DISTINCT storeid')->select();
        if($res_canyu){
            // 删除每日记录
            $dt1 = date('Y-m-d',$dt1);
            $del_day = Db::name('sum_visit_day')->where('log_time',$dt1)->delete();
            // 删除每日记录
            foreach ($res_canyu as $v_canyu) {
                $data_canyu['storeid'] = $v_canyu['storeid'];
                $data_canyu['log_time'] = $dt1;
                $data_day[] = $data_canyu;
            }
            $data_day[] = array('storeid'=>0,'log_time'=>$dt1);//所有门店汇总
            $res_day = Db::name('sum_visit_day')->insertAll($data_day);
        }
    }
    // 统计按日成交金额,成交单数
    public function dealDay($dt1,$dt2){
        $dt11 = input('dt1');$dt21 = input('dt2');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt21==null?$dt2:$dt21;
//        $dt1 = strtotime(date("Y-m-d",strtotime("-1 day")));//昨天
//        $dt2 = strtotime(date("Y-m-d"));// 今天
        $dt = date('Y-m-d',$dt1);$arr1=[];
        $res_upd = 0;$sum_ord_num = 0;$sum_price = 0;
        // 统计昨天-今天成交金额
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time>='".$dt1."' and l.success_time<'".$dt2."' ")->field('sum(ti.p_price) sum_price,l.storeid')->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $data1 = [];$storeids=[];$data2=[];$arr1=[];
            foreach($res as $v){
                $data1['sum_price'] = round($v['sum_price'],2);// 成交金额
                $data1['storeid'] = $v['storeid'];
                $storeids[] = $v['storeid'];//已有成交的门店集合
                $data2[] = $data1;
            }
            // 统计昨天-今天成交单数
            $res_cj = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time>='".$dt1."' and l.success_time<'".$dt2."'")->where('l.storeid','in',$storeids)->field('count(l.id) cnt,l.storeid')->order('l.storeid desc')->group('l.storeid')->select();
            if($res_cj){
                foreach ($res_cj as $k=>$v_cj) {
                    if($v_cj['storeid'] == $data2[$k]['storeid']){
                        $data2[$k]['sum_num'] = $v_cj['cnt'];
                    }
                }
            }
            // 更新成交金额和单数入每日统计表
            foreach ($data2 as $v2) {
                $arr1['sum_ord_num'] = $v2['sum_num'];//成交单数
                $arr1['sum_price'] = $v2['sum_price'];//成交金额
                $res_upd = Db::name('sum_visit_day')->where("storeid=".$v2['storeid']." and log_time='".$dt."'")->update($arr1);
                $sum_ord_num += $arr1['sum_ord_num'];
                $sum_price += $arr1['sum_price'];
            }
            // 更新'所有门店'的成交金额和订单
            $arr2['sum_ord_num'] = $sum_ord_num;
            $arr2['sum_price'] = $sum_price;
            $res_upd = Db::name('sum_visit_day')->where("storeid=0 and log_time='".$dt."'")->update($arr2);
        }
        return $arr1;
    }
    // 统计按日每个门店总的失效订单数,每日失效订单数
    public function invalidDay($dt1,$dt2){
        $dt11 = input('dt1');$dt12 = input('dt2');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt12==null?$dt2:$dt12;
        $dt3 = strtotime($dt1);
        $dt4 = strtotime($dt2);
        $data2 = [];
        // 统计昨天-今天每个门店总失效订单数
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("(l.status=3 or l.status=4) ")->field('count(l.id) cnt,l.storeid')->group('l.storeid')->order('l.storeid desc')->select();
        // 统计昨天-今天每个门店每日失效订单数
        $res1 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("(l.status=3 or l.status=4) and l.close_time>=$dt3 and  l.close_time<=$dt4")->field('count(l.id) cnt,l.storeid')->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $data1 = [];$data2=[];$data2_1=[];
            foreach($res as $v){
                $data1['invalid_num'] = $v['cnt'];// 总失效订单数
                $data1['storeid'] = $v['storeid'];
                $data2[] = $data1;
            }
            // 更新总失效订单数入每日统计表
            $sum_invalid_num = 0;$invalid_num = 0;
            foreach ($data2 as $v2) {
                $arr1['sum_ord_invalid_all_num'] = $v2['invalid_num'];//总失效订单数
                $res_upd = Db::name('sum_visit_day')->where("storeid=".$v2['storeid']." and log_time='".$dt1."'")->update($arr1);
                $sum_invalid_num += $arr1['sum_ord_invalid_all_num'];
            }
            if($res1){
                foreach($res1 as $v1){
                    $data1_1['invalid_num'] = $v1['cnt'];// 总失效订单数
                    $data1_1['storeid'] = $v1['storeid'];
                    $data2_1[] = $data1_1;
                }
                // 更新失效订单数入每日统计表
                foreach ($data2_1 as $v2_1) {
                    $arr1_1['sum_ord_invalid_num'] = $v2_1['invalid_num'];//总失效订单数
                    $res_upd = Db::name('sum_visit_day')->where("storeid=".$v2_1['storeid']." and log_time='".$dt1."'")->update($arr1_1);
                    $invalid_num += $arr1_1['sum_ord_invalid_num'];
                }
            }
            // 更新'所有门店'的成交金额和订单
            $arr2['sum_ord_invalid_num'] = $invalid_num;
            $arr2['sum_ord_invalid_all_num'] = $sum_invalid_num;
            $res_upd = Db::name('sum_visit_day')->where("storeid=0 and log_time='".$dt1."'")->update($arr2);
        }
        return $data2;
    }
    // 按日统计新老顾客人数
    // $dt1=>昨天,$dt2=>今天
    public function xlgkDay($dt1=null,$dt2=null,$storeid1=null){
        $dt11 = input('dt1');$dt12 = input('dt2');$storeid12 = input('storeid');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt11==null?$dt2:$dt12;
        $storeid = $storeid1==null?$storeid12:$storeid1;
        // 新老顾客统计
        $map['action'] = ['in',[1,2]];
        $map['insert_time'] = ['<',$dt2];
        $map['storeid'] = ['eq',$storeid];
        $map1['insert_time'] = ['>=',$dt1];
        $map2['insert_time'] = ['<',$dt1];
        $map2['action'] = ['in',[1,2]];
        $map2['storeid'] = ['eq',$storeid];
        // 昨日到今日顾客
        $arr1=[];$arr2=[];$rest=[];
        $res1 = Db::name('data_logs')->field('id,uid,insert_time,storeid')->where($map)->where($map1)->group('uid,storeid')->select();
        if($res1){
            foreach ($res1 as $v1) {
                $arr1[] = $v1['uid'];
            }
        }
        // 昨日之前总顾客
        $res2 = Db::name('data_logs')->field('id,uid,insert_time,storeid')->where($map2)->group('uid,storeid')->select();
        if($res2){
            foreach ($res2 as $v2) {
                $arr2[] = $v2['uid'];
            }
        }
        //求差集,在arr1中但不在arr2中
        $gk_1 = array_diff($arr1,$arr2);//新顾客
        $customer_new = count($gk_1);
        //求交集,即在arr1中也在arr2中,键值保留arr1数组中的键值不变
        $gk_2 = array_intersect($arr1,$arr2);//新顾客
        $customer_old = count($gk_2);
        $rest = ['customer_old'=>$customer_old,'customer_new'=>$customer_new];
        return $rest;
    }
    // 统计昨天额度完成比趋势
    // $dt1=>昨天日期时间戳,$dt2=>今天日期时间戳
    public function overLimitTrend($dt1,$dt2){
        //统计昨天完成额度和总额度
        $arr1=[];
        $map['l.status'] = 2;
        $map['l.success_time'] = ['>=',$dt1];
        $map1['l.success_time'] = ['<',$dt2];
        $dt3 = date('Y-m-d',$dt1);
        // 总额度
        $res1 = Db::name('tuan_info ti')->field('sum(pt_num_max) pt_num_max,storeid')->group('storeid')->select();
        if($res1){
            foreach ($res1 as $v1) {
                $arr1_1['storeid'] = $v1['storeid'];
                $arr1_1['sum_limit'] = $v1['pt_num_max'];
                $arr1[] = $arr1_1;
            }
        }
        // 已完成额度
        $res2 = Db::name('tuan_list l')->where($map)->where($map1)->field('count(id) cnt,storeid')->group('l.storeid')->select();
        if($res2){
            foreach ($res2 as $v2) {
                $arr2_2['storeid'] = $v2['storeid'];
                $arr2_2['over_limit'] = $v2['cnt'];
                $arr2[] = $arr2_2;
            }
        }
        $arr3=['over_limit'=>0,'sum_limit'=>0];
        if(!empty($arr2)){
            foreach ($arr2 as $k=>$v) {
                foreach($arr1 as $v1){
                    if($v['storeid'] == $v1['storeid']){
                        $cha = $v['over_limit'];
                        $arr3['over_limit'] += $v['over_limit'];
                        $arr3['sum_limit'] += $v1['sum_limit'];
                        $arr_ed['limit_rate'] = round($cha/$v1['sum_limit'],4);
                        if($arr_ed['limit_rate']){
                            $res_upd = Db::name('sum_visit_day')->where("storeid=".$v['storeid']." and log_time='".$dt3."'")->update($arr_ed);
                        }

                    }
                }
            }
            // 更新所有门店
            $arr_ed1['limit_rate'] = round($arr3['over_limit']/$arr3['sum_limit'],4);
            Db::name('sum_visit_day')->where("storeid=0 and log_time='".$dt3."'")->update($arr_ed1);
        }
    }
    // 统计昨天访问人数和总人数
    // $dt1=>昨天日期时间戳,$dt2=>今天日期时间戳
    public function getVisit($dt1=null,$dt2=null){
        // 新访问人数
        $dt = date('Y-m-d',$dt1);
        $map_t['insert_time'] = ['>=',$dt1];
        $map['insert_time'] = ['<',$dt2];
        $map['action'] = 0;
        $map['first_login'] = 1;
        $arr1 = [];
        $res = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->select();
        if($res){
            foreach ($res as $v) {
                $arr1_1['storeid'] = $v['storeid'];
                $arr1_1['visit_new_num'] = $v['cnt'];
                $arr1[] = $arr1_1;
            }
        }
        // 总人数
        unset($map['first_login']);
        $res1 = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->select();
        if($res1){
            foreach ($res1 as $v1) {
                $arr2_1['storeid'] = $v1['storeid'];
                $arr2_1['visit_num'] = $v1['cnt'];
                $arr2[] = $arr2_1;
            }
        }
        if(!empty($arr2)){
            $crr2['visit_new_num'] = 0;
            $crr2['visit_num'] = 0;
//            print_r($arr2);die;
            foreach ($arr2 as $v2) {
                $crr1['visit_new_num'] = 0;
                $crr1['visit_num'] = $v2['visit_num'];
                foreach($arr1 as $v1){
                    if($v2['storeid'] == $v1['storeid']){
                        $crr1['visit_new_num'] = $v1['visit_new_num'];
                        $crr2['visit_new_num'] += $crr1['visit_new_num'];
                    }
                }
                Db::name('sum_visit_day')->where("storeid=".$v2['storeid']." and log_time='".$dt."'")->update($crr1);
                $crr2['visit_num'] += $crr1['visit_num'];
            }
            Db::name('sum_visit_day')->where("storeid=0 and log_time='".$dt."'")->update($crr2);
        }
        return $crr2;
    }
    // 每日统计新老顾客
    // 新顾客 = 通过以前注册的老顾客,通过拼购注册的是新顾客
    // $dt1=>昨天日期时间戳,$dt2=>今天日期时间戳,$storeid=>门店id
    public function xlGuke($dt1=null,$dt2=null,$storeid=null){
        $map11['l.status'] = 2;
        $map11['l.storeid'] = $storeid;
        $map11['o.pay_time'] = ['>=',$dt1];
        $map12['o.pay_time'] = ['<',$dt2];
        $where11 = ' o.order_sn!=o.parent_order ';
        $res11 = Db::name('tuan_list l')->join(['pt_tuan_order'=>'o'],'l.order_sn=o.parent_order','LEFT')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->field('count(o.uid) cnt,m.id_regsource,l.storeid')->where($map11)->where($map12)->where($where11)->group('m.id_regsource')->select();
        $customer_old = 0;$customer_new=0;
        if($res11){
            foreach ($res11 as $v11) {
                if($v11['id_regsource'] == 7){
                    $customer_new = $v11['cnt'];
                }else{
                    $customer_old = $v11['cnt'];
                }
            }
        }
        $rest = ['customer_old'=>$customer_old,'customer_new'=>$customer_new];
        return $rest;
    }
}