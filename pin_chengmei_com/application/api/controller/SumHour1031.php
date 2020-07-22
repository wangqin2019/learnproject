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
 * swagger: 统计按小时数据
 */
set_time_limit(0);
class SumHour1031
{
    protected $code = 0;
    protected $data = [];
    protected $msg = '暂无数据';
    // 统计每小时的访问用户数据到统计表
    public function visitHour(){
        $res_upd = null;$sum_visit_num = 0;
        // 每天统计插入一条参与门店记录
        $dt1 = date("Y-m-d");// 今天
        $hour1 = date('H');//当前小时
        $hour2 = $hour1-1;//上一个小时
        // 处理0点特殊时间数据
        $res_dt = $this->updSpeHour($hour1);
        if($res_dt){
            $dt1 = $res_dt['dt1'];
            $hour2 = $res_dt['hour1'];
        }
        $this->ChuHour($dt1,$hour2);
        $dt3 = strtotime(date("Y-m-d H:00:00"));//当前小时
        $dt4 = $dt3-3600;//上一小时时间戳
//        $dt4 = strtotime(date("Y-m-d $hour2:00:00"));//当前小时前1个小时
        // 每小时更新门店美容师总人数和发起活动美容师人数
        $this->beauticianSum($dt1,date('Y-m-d H:i:s',$dt3),$hour2);
        // 每小时更新门店新老顾客访问人数
        $this->gkVisit($dt4,$dt3,$dt1,$hour2);
//        // 每小时更新门店成交金额
        $res_price = $this->storeOrdPrice($dt4,$dt3,$hour2);
//        // 每小时更新门店成交总金额
        $res_all_price = $this->storeSumPrice($dt1,$hour2,$dt3);
        // 每小时更新门店进行中的订单金额
        $this->storeSumIngPrice($dt1,date('Y-m-d H:i:s',$dt3),$hour2);
        // 统计美容师成交金额排名
        $dt5 = date("Y-m-d",strtotime("-1 day"));
        $this->sumMrsPrice($dt1,date('Y-m-d H:i:s',$dt3),$hour2,$dt5);

        // 统计每小时分享购和单独购总的成交金额和订单数量
        $this->buySumPrice($dt1,$hour2,$dt3);
        // 统计每小时分享购和单独购当日成交金额和订单数量
        $dt11 = strtotime($dt1);
        $this->buyDayPrice($dt11,$dt3,$hour2);

        // 统计累计失效订单数
        $this->sumInvalidNum($dt1,$hour2);
        // 统计商品额度
        $res_limit = $this->goodsAllLimit();
        $arr_cy1=['goods_num'=>0,'goods_sum_limit'=>0,'ing_limit'=>0,'over_limit'=>0,'rate_limit'=>0];
        if($res_limit){
            foreach ($res_limit as $vl) {
                $arr_cy['goods_num'] = $vl['gd_num'];
                $arr_cy['goods_sum_limit'] = $vl['gd_sum_limit'];
                $arr_cy['ing_limit'] = $vl['gd_ing_limit'];
                $arr_cy['over_limit'] = $vl['gd_over_limit'];
                $arr_cy['rate_limit'] = $vl['rate_limit'];
                $arr_cy1['goods_num'] += $arr_cy['goods_num'];
                $arr_cy1['goods_sum_limit'] += $arr_cy['goods_sum_limit'];
                $arr_cy1['ing_limit'] += $arr_cy['ing_limit'];
                $arr_cy1['over_limit'] += $arr_cy['over_limit'];
                Db::name('sum_visit_hour')->where("storeid=".$vl['storeid']." and log_time='".$dt1."' and hours=".$hour2)->update($arr_cy);
            }
            $arr_cy1['rate_limit'] = round($arr_cy1['over_limit']/$arr_cy1['goods_sum_limit'],4);
            $res_upd = Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$dt1."' and hours=".$hour2)->update($arr_cy1);
        }
//        // 每小时更新办事处下面有多少门店参与
        $this->agencyStore($dt1,$hour2);

        // 每小时更新每天成交单数和成交金额
        $this->getCjDay(strtotime($dt1),$hour2);//
        // 每小时更新每天成交单数和成交金额与上周/月的比率
        $this->getCjRate($dt5,$dt1,$hour2);
        // 每小时统计门店新老顾客人数
        $res = $this->getGkDay(strtotime($dt1),$hour2);
        // 每小时统计新老顾客比率
        $this->getGkRate($dt5,$dt1,$hour2);

        $this->code = 1;
        $this->msg = '每小时数据更新成功';
        // 记录日志
        $this->write_log(['msg'=>'每小时定时任务-'.json_encode($this->data,JSON_UNESCAPED_UNICODE)]);
        // 所有数据完成,更新所有统计数据的标志位
        $this->updDataFlag($dt1,$hour2);
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    public function visitHour1(){
        $res_upd = null;$sum_visit_num = 0;
        // 每天统计插入一条参与门店记录
        $dt1 = date("Y-m-d");// 今天
        $hour1 = date('H');//当前小时
        $hour2 = $hour1-1;//上一个小时
        $dt3 = strtotime(date("Y-m-d H:00:00"));//当前小时
        $dt5 = date("Y-m-d",strtotime("-1 day"));
        // 每小时统计门店新老顾客人数
        // $dt1=>今天日期,$hour1=>上一小时,$dh=>今天日期当前小时
//        $this->buySumPrice($dt1,$hour2,$dt3);
        // $dt1=>今天日期时间戳,$dt2=>今天日期当前小时时间戳,$hour1=>上一小时
        // $dt11 = strtotime($dt1);
        // $this->buyDayPrice($dt11,$dt3,$hour2);
        $this->updDataFlag($dt1,$hour2);
        print_r(1);die;
        $this->code = 1;
        $this->msg = '每小时数据更新成功';
        // 记录日志
        $this->write_log(['msg'=>'每小时定时任务-'.json_encode($this->data,JSON_UNESCAPED_UNICODE)]);
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }

    // 每小时统计插入一条参与门店记录
    public function ChuHour($dt1,$hour1){
        // 每天统计插入一条参与门店记录
        $data_day = [];
        $res_canyu = Db::name('tuan_info')->field('DISTINCT storeid')->select();
        if($res_canyu){
            // 删除每日记录
            $del_day = Db::name('sum_visit_hour')->where("log_time='$dt1' and hours='$hour1'")->delete();
            // 删除每日记录
            foreach ($res_canyu as $v_canyu) {
                $data_canyu['storeid'] = $v_canyu['storeid'];
                $data_canyu['log_time'] = $dt1;
                $data_canyu['hours'] = $hour1;
                // 每小时一条记录
                $data_day[] = $data_canyu;
            }
            $data_day[] = array('storeid'=>0,'log_time'=>$dt1,'hours'=>$hour1);//所有门店汇总
            $res_day = Db::name('sum_visit_hour')->insertAll($data_day);
        }
    }

    // 每小时统计插入一条参与门店记录
    public function returnMsg($code,$data,$msg){
        $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($arr);
    }
    // 每小时统计门店成交金额
    public function storeOrdPrice($dt1=null,$dt2=null,$hour1=null){
        $dt11 = input('dt1');$dt12 = input('dt2');$hour12 = input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt12==null?$dt2:$dt12;
        $hour1 = $hour12==null?$hour1:$hour12;
        $sum_price = 0;$res2 = null;$dt = date('Y-m-d',$dt1);
//        $res = Db::name('tuan_order o')->join(['pt_tuan_list'=>'l'],'o.parent_order=l.order_sn','LEFT')->where("l.status=2 and o.pay_status=1 and o.pay_time>=$dt1 and o.pay_time<=$dt2")->field('sum(o.pay_price) pay_price,l.storeid')->group('l.storeid')->select();
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dt2 and l.success_time>=$dt1 ")->field('sum(ti.p_price) pay_price,l.storeid')->group('l.storeid')->select();
        if($res){
            foreach($res as $v){
                $data1['sum_order_price'] = round($v['pay_price'],2);
                $res2 = Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='$dt' and hours=$hour1")->update($data1);
                $sum_price += $data1['sum_order_price'];
            }
            // 更新所有门店
            $data2['sum_order_price'] = $sum_price ;
            $res2 = Db::name('sum_visit_hour')->where("storeid=0 and log_time='$dt' and hours=$hour1")->update($data2);
        }
        return $res2;
    }
    // 每小时门店成交总金额,总单数
    // $dt1=>今天日期,$hour1=>上一小时,$dh=>今天日期当前小时时间戳
    public function storeSumPrice($dt1=null,$hour1=null,$dh=null){
        $dt11 = input('dt1');$hour11 = input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $hour1 = $hour11==null?$hour1:$hour11;
        $sum_price = 0;$res2 = null;
//        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('h.storeid,h.log_time,h.hours,sum(sum_order_price) ord_price,b.title')->where("(h.log_time='".$dt1."' and h.hours<=".$hour1.") or (h.log_time<'".$dt1."')")->group('h.storeid')->order('ord_price desc')->select();
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dh")->field('sum(ti.p_price) pay_price,l.storeid')->group('l.storeid')->select();
        if($res){
            foreach($res as $v){
                $data1['sum_all_price'] = round($v['pay_price'],2);
                $res2 = Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='$dt1' and hours=$hour1")->update($data1);
                $sum_price += $data1['sum_all_price'];
            }
            // 更新所有门店
            $data2['sum_all_price'] = $sum_price ;
            $res2 = Db::name('sum_visit_hour')->where("storeid=0 and log_time='$dt1' and hours=$hour1")->update($data2);
        }
        // 门店每小时成交总单数
        $res1 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dh")->field('count(l.id) cnt,l.storeid')->group('l.storeid')->select();
        if($res1){
            $sum_ord_num = 0;
            foreach($res1 as $v1){
                $data_1['sum_ord_num'] = round($v1['cnt'],2);
                $res2 = Db::name('sum_visit_hour')->where("storeid=".$v1['storeid']." and log_time='$dt1' and hours=$hour1")->update($data_1);
                $sum_ord_num += $data_1['sum_ord_num'];
            }
            // 更新所有门店
            $data_2['sum_ord_num'] = $sum_ord_num ;
            $res2 = Db::name('sum_visit_hour')->where("storeid=0 and log_time='$dt1' and hours=$hour1")->update($data_2);
        }
        return $res2;
    }
    // 每小时更新办事处下面有多少门店参与
    // '2018-09-11', 12
    public function agencyStore($dt=null,$hours=null){
        // 请求参数
        $dt1 = input('dt');$hours1 = input('hours');
        $dt = $dt1==null?$dt:$dt1;
        $hours = $hours1==null?$hours:$hours1;
        //初始化参数
        $res_age = null;$storeids = [];
        // 参与门店id
        $res_cy = Db::name('tuan_info')->field('DISTINCT storeid')->select();
        if($res_cy){
            foreach ($res_cy as $v_cy) {
                $storeids[] = $v_cy['storeid'];
            }
            // 参与门店关联对应的办事处
//            $res_agency = Db::table('sys_department sd')->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_department=sd.id_department'],'LEFT')->where('sdr.id_beauty','in',$storeids)->field('count(sdr.id_beauty) store_num,sd.id_department,sd.st_department')->group('sd.id_department')->fetchSql(true)->select();
            // 原生查询
            $storeids = implode(',',$storeids);
            $res_agency = "SELECT count(sdr.id_beauty) store_num,sd.id_department,sd.st_department FROM `sys_department` `sd` LEFT JOIN `sys_departbeauty_relation` `sdr` ON `sdr`.`id_department`=`sd`.`id_department` WHERE  `sdr`.`id_beauty` IN ($storeids) GROUP BY `sd`.`id_department`";
            $res_agency = Db::query($res_agency);// 执行原生查询
//            print_r($res_agency);die;
            if($res_agency){
                $arr1 = null;
                foreach($res_agency as $v_agecny){
                    $arr['agency_id'] = $v_agecny['id_department'];
                    $arr['store_num'] = $v_agecny['store_num'];
                    $arr['agency_name'] = $v_agecny['st_department'].'办事处';
                    $arr['hours'] = $hours;
                    $arr['log_time'] = $dt;
                    $arr1[] = $arr;
                }
                if($arr1){
                    // 先删除,后插入
                    Db::name('sum_agency_hour')->where("log_time='$dt' and hours=$hours")->delete();
                    $res_age = Db::name('sum_agency_hour')->insertAll($arr1);
                }
            }

        }
        return $res_age;
    }
    // 每小时门店进行中的订单金额
    // $dt1=>当前日期天,$dt2=>当前天小时时间,$hour1=>前一个小时时间对应的数字 -> dt1=2018-09-07&hour1=12&dt2=2018-09-07 12:00:00
    public function storeSumIngPrice($dt1=null,$dt2=null,$hour1=null){
        $dt11 = input('dt1');$dt12 = input('dt2');$hour12 = input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt12==null?$dt2:$dt12;
        $hour1 = $hour12==null?$hour1:$hour12;
        $sum_price = 0;$res2 = null;
        $dt3_1 = $dt1;//当前日期
        $dt3_2 = strtotime($dt2);//当前小时对应时间戳
        // 总累计
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=1 ")->field('sum(ti.p_price) sum_price,l.storeid')->group('l.storeid')->select();
        if($res){
            foreach($res as $v){
                $data1['sum_ing_price'] = round($v['sum_price'],2);
                $res2 = Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='$dt3_1' and hours=$hour1")->update($data1);
                $sum_price += $data1['sum_ing_price'];
            }
            // 更新所有门店
            $data2['sum_ing_price'] = $sum_price ;
            $res2 = Db::name('sum_visit_hour')->where("storeid=0 and log_time='$dt3_1' and hours=$hour1")->update($data2);
        }
        return $res2;
    }
    // 每小时统计美容师总人数,发起活动美容师人数 => $dt1=>当前日期天,$dt2=>当前天小时时间,$hour1=>前一个小时时间对应的数字->dt1=2018-09-07&hour1=12&dt2=2018-09-07 13:00:00
    public function beauticianSum($dt1=null,$dt2=null,$hour1=null){
        $dt11 = input('dt1');$dt12 = input('dt2');$hour12 = input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt12==null?$dt2:$dt12;
        $hour1 = $hour12==null?$hour1:$hour12;
        // 参与的门店
        $dt3 = strtotime($dt2);
        $storeids = [];$arr1=[];$sum_num=0;$arr2=[];$whereT=['dt1'=>$dt1,'hours'=>$hour1];
        $res_cy = Db::name('tuan_info')->field('DISTINCT storeid')->select();
        if($res_cy){
            foreach ($res_cy as $v_cy) {
                $storeids[] = $v_cy['storeid'];
            }
        }
        $rest['storeids'] = $storeids;//参与的所有门店
        // 每个门店的总美容师人数
        $map['storeid'] = ['in',$storeids];
        $res = Db::table('ims_bj_shopn_member')->field('count(id) cnt,storeid')->where('length(code)>0 and staffid=id')->where($map)->group('storeid')->select();
        if($res){
            foreach ($res as $v) {
                $arr1_1['storeid'] = $v['storeid'];
                $arr1_1['cnt'] = $v['cnt'];
                $arr1[] = $arr1_1;
            }
        }
        $rest['storeid_beautician_num'] = $arr1;//参与门店每个门店的美容师人数
        // 发起活动美容师人数
        $res1 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.begin_time<'".$dt3."' ")->field('count(DISTINCT share_uid) cnts,l.storeid')->group('l.storeid')->select();
        if($res1){
            foreach ($res1 as $v1) {
                $arr2_1['storeid'] = $v1['storeid'];
                $arr2_1['cnt'] = $v1['cnts'];
                $arr2[] = $arr2_1;
            }
        }
        $rest['storeid_beautician_active_num'] = $arr2;//参与门店每个门店发起活动的美容师人数

        if(!empty($arr1))
        {
            $sum_num = 0;$map1=[];$data_0=[];
            foreach ($arr1 as $v1) {
                $map1['storeid'] = $v1['storeid'];
                $map1['log_time'] = $whereT['dt1'];
                $map1['hours'] = $whereT['hours'];
                $data_1['beautician_num'] = $v1['cnt'];
                Db::name('sum_visit_hour')->where($map1)->update($data_1);
                $sum_num += $data_1['beautician_num'];
            }
            unset($map1['storeid']);
            $data_0['beautician_num'] = $sum_num;
            Db::name('sum_visit_hour')->where('storeid',0)->where($map1)->update($data_0);
        }
        if(!empty($arr2))
        {
            $sum_act_num = 0;$map2=[];$data_0=[];
            foreach ($arr2 as $v2) {
                $map2['storeid'] = $v2['storeid'];
                $map2['log_time'] = $whereT['dt1'];
                $map2['hours'] = $whereT['hours'];
                $data_2['beautician_active_num'] = $v2['cnt'];
                Db::name('sum_visit_hour')->where($map2)->update($data_2);
                $sum_act_num += $data_2['beautician_active_num'];
            }
            unset($map2['storeid']);
            $data_0['beautician_active_num'] = $sum_act_num;
            Db::name('sum_visit_hour')->where('storeid',0)->where($map2)->update($data_0);
        }
        return $rest;
    }
    // 更新统计数据到小时数据表里
    // $whereT=>[dt1=>当天时间,hours=>上个小时],$data=>['storeid'=>门店id,cnt=>统计数据],$type=>类型
    private function updHour($whereT,$data,$type='beautician_num'){
        $sum_num = 0;$map=[];
        foreach ($data as $v) {
            $arr[$type] = $v['cnt'];
            $map['storeid'] = $v['storeid'];
            $map['log_time'] = $whereT['dt1'];
            $map['hours'] = $whereT['hours'];
            // 更新每家门店数据
            $res2 = Db::name('sum_visit_hour')->where($map)->update($arr);
            $sum_num += $arr[$type];
        }
        // 更新所有门店数据
        $arr1[$type] = $sum_num ;
        unset($map['storeid']);
        $res2 = Db::name('sum_visit_hour')->where('storeid',0)->where($map)->update($arr1);
    }
    // 统计美容师成交金额排名
    // $dt1=>当天日期,$dt2=>今天当前小时,$hour1=>上一小时,$dt3=>昨日日期
    public function sumMrsPrice($dt1=null,$dt2=null,$hour1=null,$dt3=null){
        $dt11 = input('dt1');$dt12 = input('dt2');$hour12 = input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt12==null?$dt2:$dt12;
        $hour1 = $hour12==null?$hour1:$hour12;
        $dt2_1 = strtotime($dt2);
        // 美容师成交金额
        $arr1=[];$whereT=['log_time'=>$dt1,'hours'=>$hour1];
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<'".$dt2_1."' ")->field('sum(ti.p_price) sum_price,l.storeid,l.share_uid')->group('l.storeid,l.share_uid')->select();
        if($res){
            foreach ($res as $v) {
                $arr1_1['storeid'] = $v['storeid'];
                $arr1_1['uid'] = $v['share_uid'];
                $arr1_1['sum_all_price'] = round($v['sum_price'],2);
                $arr1_1['hours'] = $hour1;
                $arr1_1['log_time'] = $dt1;
                // 新顾客 = 昨日到今日的顾客和昨日之前的顾客的差集
                $res1 = $this->sumGuke($v['storeid'],$v['share_uid']);
                if($res1){
                    $arr1_1['customer_old'] = $res1['customer_old'];
                    $arr1_1['customer_new'] = $res1['customer_new'];
                }
                $arr1[] = $arr1_1;
            }
        }
        $this->updHourMrs($whereT,$arr1);
        return $arr1;

    }
    // 更新统计数据到小时数据表里
    // $whereT=>[查询条件 log_time=>日期,hours=>小时]
    private function updHourMrs($whereT,$data,$type='ins'){
        if($type='ins'){
            //先删除
            $res1 = Db::name('sum_mrs_price_hour')->where($whereT)->delete();
            //再插入
            $res = Db::name('sum_mrs_price_hour')->insertAll($data);
        }else{
            //修改
            $res = Db::name('sum_mrs_price_hour')->where($whereT)->update($data);
        }
    }
    // 统计新老顾客
    // 新顾客 = 通过以前注册的老顾客,通过拼购注册的是新顾客
    // $dt1=>昨日日期,$dt2=>今日日期,$dt3=>今日当前小时
    public function sumGuke($storeid=null,$sellerid=null){
//    public function sumGuke($dt1,$dt2,$dt3,$storeid=null,$sellerid=null){
//        $dt1 = strtotime($dt1);
//        $dt2 = strtotime($dt2);
//        $dt3 = strtotime($dt3);
//        $map['action'] = ['in',[1,2]];
//        $map['insert_time'] = ['<',$dt3];
//        $map['storeid'] = ['eq',$storeid];
//        $map1['insert_time'] = ['>=',$dt2];
//        $map2['insert_time'] = ['<',$dt1];
//        $map2['action'] = ['in',[1,2]];
//        $map2['storeid'] = ['eq',$storeid];
//        // 今日0点到现在顾客
//        $map['sellerid'] = $sellerid;
//        $map2['sellerid'] = $sellerid;
//        $arr1=[];$arr2=[];$rest=[];
//        $res1 = Db::name('data_logs')->field('id,uid,insert_time,storeid')->where($map)->where($map1)->group('uid,storeid')->select();
//        if($res1){
//            foreach ($res1 as $v1) {
//                $arr1[] = $v1['uid'];
//            }
//        }
//        // 今天之前总顾客
//        $res2 = Db::name('data_logs')->field('id,uid,insert_time,storeid')->where($map2)->group('uid,storeid')->select();
//        if($res2){
//            foreach ($res2 as $v2) {
//                $arr2[] = $v2['uid'];
//            }
//        }
//        //求差集,在arr1中但不在arr2中
//        $gk_1 = array_diff($arr1,$arr2);//新顾客
//        $customer_new = count($gk_1);
//        //求交集,即在arr1中也在arr2中,键值保留arr1数组中的键值不变
//        $gk_2 = array_intersect($arr1,$arr2);//新顾客
//        $customer_old = count($gk_2);
        $map11['l.status'] = 2;
        $map11['l.storeid'] = $storeid;
        $map11['l.share_uid'] = $sellerid;
        $where11 = ' o.order_sn!=o.parent_order ';
        $res11 = Db::name('tuan_list l')->join(['pt_tuan_order'=>'o'],'l.order_sn=o.parent_order','LEFT')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->field('o.uid,o.order_sn,m.id_regsource')->where($map11)->where($where11)->group('o.uid')->select();
        $customer_old = 0;$customer_new=0;
        if($res11){
            foreach ($res11 as $v11) {
                if($v11['id_regsource'] == 7){
                    $customer_new++;
                }else{
                    $customer_old++;
                }
            }
        }

        $rest = ['customer_old'=>$customer_old,'customer_new'=>$customer_new];
        return $rest;
    }
    // 每小时 统计活动商品,活动商品总额度,已完成额度,进行中的额度,活动完成额度比
    public function goodsLimit($storeid=null){

        $arr1['gd_num'] = 0;
        $arr1['gd_sum_limit'] = 0;
        $arr1['gd_ing_limit'] = 0;
        $arr1['gd_over_limit'] = 0;
        $arr1['rate_limit'] = 0;
        $arr1['storeid'] = $storeid;
        // 统计统计活动商品,活动商品总额度
        $res = Db::name('tuan_info')->field('storeid,pt_num_max')->where('storeid',$storeid)->select();
        if($res){

            foreach ($res as $v) {
                $arr1['gd_num']+=1;
                $arr1['gd_sum_limit']+=$v['pt_num_max'];
            }
        }
        // 统计已完成额度,进行中的额度,活动完成额度比
        $map1['l.storeid'] = $storeid;
        $res1 = Db::name('tuan_list l')->where("l.status=1 or l.status=2")->field('count(id) cnt,l.status')->where($map1)->select();
        if($res1){
            foreach ($res1 as $v1) {
                if($v1['status']==1){
                    $arr1['gd_ing_limit']+=$v1['cnt'];
                }elseif($v1['status']==2){
                    $arr1['gd_over_limit']+=$v1['cnt'];
                }
            }
        }
        $arr1['rate_limit'] = round($arr1['gd_over_limit']/$arr1['gd_sum_limit'],4);
        return $arr1;
    }

    // 每小时 统计活动商品,活动商品总额度,已完成额度,进行中的额度,活动完成额度比
    public function goodsAllLimit(){

        $arr1['gd_num'] = 0;
        $arr1['gd_sum_limit'] = 0;
        $arr1['gd_ing_limit'] = 0;
        $arr1['gd_over_limit'] = 0;
        $arr1['rate_limit'] = 0;
        $arr1['storeid'] = 0;
        $crr = [];
        // 统计统计活动商品,活动商品总额度
        $brr2=[];$brr3=[];$brr4=[];
        $res = Db::name('tuan_info')->field('storeid,sum(pt_num_max) pt_num_max')->group('storeid')->order('storeid desc')->select();
        if($res){
            foreach ($res as $v) {
                $arr1_1['storeid'] = $v['storeid'];
                $arr1_1['pt_num_max'] = $v['pt_num_max'];
                $brr1[] = $arr1_1;
            }
        }
        // 统计统计活动商品,活动商品总数量
        $res = Db::name('tuan_info')->field('storeid,count(id) num')->group('storeid')->order('storeid desc')->select();
        if($res){
            foreach ($res as $v) {
                $arr2_1['storeid'] = $v['storeid'];
                $arr2_1['num'] = $v['num'];
                $brr2[] = $arr2_1;
            }
        }
        // 统计已完成额度,进行中的额度,活动完成额度比
        $res2 = Db::name('tuan_list l')->where("l.status=1")->field('count(id) cnt,l.status,storeid')->group('storeid')->order('storeid desc')->select();
        $res3 = Db::name('tuan_list l')->where("l.status=2")->field('count(id) cnt,l.status,storeid')->group('storeid')->order('storeid desc')->select();

        if($res2){
            foreach ($res2 as $v2) {
                $arr3_1['storeid'] = $v2['storeid'];
                $arr3_1['gd_ing_limit'] = $v2['cnt'];
                $brr3[] = $arr3_1;
            }
        }
        if($res3){
            foreach ($res3 as $v3) {
                $arr4_1['storeid'] = $v3['storeid'];
                $arr4_1['gd_over_limit'] = $v3['cnt'];
                $brr4[] = $arr4_1;
            }
        }
        if(!empty($brr1)){
            foreach ($brr1 as $k1=>$v1) {
                $crr1 = ['gd_over_limit'=>0,'gd_ing_limit'=>0];
                $crr1['storeid'] = $v1['storeid'];
                $crr1['gd_sum_limit'] = $v1['pt_num_max'];
                $crr1['gd_num'] = $brr2[$k1]['num'];
                foreach ($brr3 as $v3) {
                    if($v3['storeid'] == $v1['storeid']){
                        $crr1['gd_ing_limit'] = $v3['gd_ing_limit'];
                    }
                }
                foreach ($brr4 as $v4) {
                    if($v4['storeid'] == $v1['storeid']){
                        $crr1['gd_over_limit'] = $v4['gd_over_limit'];
                    }
                }
                $crr1['rate_limit'] = round($crr1['gd_over_limit']/$crr1['gd_sum_limit'],4);
                $crr[] = $crr1;
            }
        }
        return $crr;
    }

    // 新老访问人数按小时统计
    // $dt4=>上一小时时间戳,$dt3=>当前小时时间戳,$dt1=>今天,$hour2=>当前小时
    public function gkVisit($dt4=null,$dt3=null,$dt1=null,$hour2=null){
        $dt4_1 = input('dt4');$dt3_1 = input('dt3');$dt1_1 = input('dt1');$hour2_1 = input('hour2');
        $dt4 = $dt4_1==null?$dt4:$dt4_1;
        $dt3 = $dt3_1==null?$dt3:$dt3_1;
        $dt1 = $dt1_1==null?$dt1:$dt1_1;
        $hour2 = $hour2_1==null?$hour2:$hour2_1;
        // 今天统计每小时总访问人数
        $map_t['insert_time'] = ['>=',$dt4];
        $map['insert_time'] = ['<',$dt3];
        $map['action'] = 0;
        $res = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->order('storeid asc')->select();
        if($res){
            $sum_visit_num = 0;
            foreach($res as $v){
                $arr['visit_num'] = $v['cnt'];
                // 更新每小时统计人数
                $res_upd = Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='".$dt1."' and hours=".$hour2)->update($arr);
                $sum_visit_num += $arr['visit_num'];

            }
            // 更新每小时所有门店总人数
            $arr1['visit_num'] = $sum_visit_num;
            $res_upd = Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$dt1."' and hours=".$hour2)->update($arr1);
        }
        // 统计新访问人数
        $map['first_login'] = 1;
        $res = Db::name('data_logs')->field('storeid,count(DISTINCT uid) cnt,first_login')->where($map_t)->where($map)->group('storeid')->order('storeid asc')->select();
        if($res){
            $sum_visit_num = 0;
            foreach($res as $v){
                $arr['visit_new_num'] = $v['cnt'];
                $res_upd = Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='".$dt1."' and hours=".$hour2)->update($arr);
                $sum_visit_num += $arr['visit_new_num'];
            }
            // 更新每小时所有门店总人数
            $arr1['visit_new_num'] = $sum_visit_num;
            $res_upd = Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$dt1."' and hours=".$hour2)->update($arr1);
        }
        return 1;
    }
    // 每小时统计当日成交金额和成交单数 及与上周平均比较
    // $dt1=>今天日期时间戳,$hour1=>上一小时数字
    public function getCjDay($dt1=null,$hour1=null){
        $dt11=input('dt1');$hour11=input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $hour1 = $hour11==null?$hour1:$hour11;
        $dt3 = date('Y-m-d',$dt1);
        // 统计今日当前成交金额
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time>=$dt1 ")->field('sum(ti.p_price) pay_price,l.storeid')->group('l.storeid')->select();
        $brr1 = ['day_price'=>0,'day_num'=>0];
        foreach ($res as $v) {
            $arr['day_price'] = $v['pay_price'];
            if($arr['day_price']){
                Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='".$dt3."' and hours=".$hour1)->update($arr);
            }
            $brr1['day_price'] += $arr['day_price'];
        }
        // 统计今日当前成交单数
        $res1 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time>=$dt1 ")->field('count(l.id) cnt,l.storeid')->group('l.storeid')->select();
        foreach ($res1 as $v1) {
            $arr1['day_num'] = $v1['cnt'];
            if($arr1['day_num']){
                Db::name('sum_visit_hour')->where("storeid=".$v1['storeid']." and log_time='".$dt3."' and hours=".$hour1)->update($arr1);
            }
            $brr1['day_num'] += $arr1['day_num'];
        }
        // 统计所有门店数据
        Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$dt3."' and hours=".$hour1)->update($brr1);
        return $brr1;
    }

    // 每小时统计当日成交金额和成交单数 及与上周平均比较比率
    // $dt1=>昨天日期,$dt2=>今天日期,$hour=>上一小时
    public function getCjRate($dt1=null,$dt2=null,$hour=null){
        $dt11=input('dt1');$dt21=input('dt2');$hour11=input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt21==null?$dt2:$dt21;
        $hour = $hour11==null?$hour:$hour11;
        // 统计昨日的
        $brr = [];
        $res1 = Db::name('sum_visit_day d')->where('log_time',$dt1)->field('sum_price,sum_ord_num,storeid')->group('storeid')->select();
        // 获取今日的
        $res2 = Db::name('sum_visit_hour')->field('day_price,day_num,storeid')->where("log_time='$dt2' and hours=$hour")->group('storeid')->select();
        if($res1){
            foreach ($res1 as $v1) {
                $brr1 = ['storeid'=>0,'day_price_rate'=>0,'day_num_rate'=>0];
                foreach ($res2 as $v2) {
                    if($v1['storeid'] == $v2['storeid']){
                        $brr1['storeid'] = $v2['storeid'];
                        if($v1['sum_price']>0){
                            $brr1['day_price_rate'] = round(($v2['day_price']-$v1['sum_price'])/$v1['sum_price'],4);
                        }
                        if($v1['sum_ord_num']>0){
                            $brr1['day_num_rate'] = round(($v2['day_num']-$v1['sum_ord_num'])/$v1['sum_ord_num'],4);
                        }
                        if(!empty($brr1)){
                            $brr[] = $brr1;
                        }
                    }
                }
            }
        }
        if(!empty($brr)){
            foreach ($brr as $vb) {
                $data_vb['day_price_rate'] = $vb['day_price_rate'];
                $data_vb['day_num_rate'] = $vb['day_num_rate'];
                if($data_vb['day_price_rate'] || $data_vb['day_num_rate']){
                    Db::name('sum_visit_hour')->where("log_time='$dt2' and hours=$hour and storeid=".$vb['storeid'])->update($data_vb);
                }
            }
        }
        // 上周统计数据
        $dt3 = date('Y-m-d',strtotime('-2 monday', time()));// 上周一时间
        $dt4 = date('Y-m-d',strtotime('-1 sunday', time()));// 上周日时间
        $map1['log_time'] =['>=',$dt3];
        $map2['log_time'] =['<',$dt4];
        $res_w = Db::name('sum_visit_day d')->where($map1)->where($map2)->field('sum(sum_price) sum_price,sum(sum_ord_num) sum_ord_num,storeid')->group('storeid')->select();
        if($res_w){
            $brr = [];
            foreach ($res_w as $vw) {
                $brr1 = ['storeid'=>0,'week_price_rate'=>0,'week_num_rate'=>0];
                foreach ($res2 as $v2) {
                    if($vw['storeid'] == $v2['storeid']){
                        $brr1['storeid'] = $v2['storeid'];
                        $sum_price = $vw['sum_price']/7;
                        $sum_num = $vw['sum_ord_num']/7;
                        if($sum_price){
                            $brr1['week_price_rate'] = round(($v2['day_price']-$sum_price)/$sum_price,4);
                        }
                        if($sum_num>0){
                            $brr1['week_num_rate'] = round(($v2['day_num']-$sum_num)/$sum_num,4);
                        }
                        if(!empty($brr1)){
                            $brr[] = $brr1;
                        }
                    }
                }
            }
        }
        if(!empty($brr)){
            foreach ($brr as $vb) {
                $data_sz['week_price_rate'] = $vb['week_price_rate'];
                $data_sz['week_num_rate'] = $vb['week_num_rate'];
                if($data_sz['week_price_rate'] || $data_sz['week_num_rate']){
                    Db::name('sum_visit_hour')->where("log_time='$dt2' and hours=$hour and storeid=".$vb['storeid'])->update($data_sz);
                }

            }
        }
        // 上月统计数据
        $dt3 =  date('Y-m-d',strtotime(date('Y-m-01', strtotime('-1 month'))));// 上月一号时间
        $dt4 =  date('Y-m-d',strtotime(date('Y-m', time()) . '-01 00:00:00'));// 本月1号
        $map1['log_time'] =['>=',$dt3];
        $map2['log_time'] =['<',$dt4];
        $res_m = Db::name('sum_visit_day d')->where($map1)->where($map2)->field('sum(sum_price) sum_price,sum(sum_ord_num) sum_ord_num,storeid')->group('storeid')->select();
        if($res_m){
            $brr = [];
            foreach ($res_m as $vm) {
                $brr1 = ['storeid'=>0,'month_price_rate'=>0,'month_num_rate'=>0];
                foreach ($res2 as $v2) {
                    if($vm['storeid'] == $v2['storeid']){
                        $brr1['storeid'] = $v2['storeid'];
                        $sum_price = $vm['sum_price']/30;
                        $sum_num = $vm['sum_ord_num']/30;
                        if($sum_price){
                            $brr1['month_price_rate'] = round(($v2['day_price']-$sum_price)/$sum_price,4);
                        }
                        if($sum_num>0){
                            $brr1['month_num_rate'] = round(($v2['day_num']-$sum_num)/$sum_num,4);
                        }
                        if(!empty($brr1)){
                            $brr[] = $brr1;
                        }
                    }
                }
            }
        }
        if(!empty($brr)){
            foreach ($brr as $vb) {
                $data_sm['month_price_rate'] = $vb['month_price_rate'];
                $data_sm['month_num_rate'] = $vb['month_num_rate'];
                if($data_sm['month_price_rate'] || $data_sm['month_num_rate']){
                    Db::name('sum_visit_hour')->where("log_time='$dt2' and hours=$hour and storeid=".$vb['storeid'])->update($data_sm);
                }
            }
        }
    }
    // 每小时累计订单失效数
    // $dt1=>今天日期,$hour1=>上一小时数字
    public function sumInvalidNum($dt1=null,$hour1=null){
        $dt11=input('dt1');$hour11=input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $hour1 = $hour11==null?$hour1:$hour11;
        // 每日失效订单数
        $dt2 = strtotime($dt1);//今日时间戳
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("(l.status=3 or l.status=4) and l.close_time>=$dt2")->field('count(l.id) cnt,l.storeid')->group('l.storeid')->select();
        $brr1 = ['sum_invalid_num'=>0];
        foreach ($res as $v) {
            $arr['sum_invalid_num'] = $v['cnt'];
            if($arr['sum_invalid_num']){
                Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='".$dt1."' and hours=".$hour1)->update($arr);
            }
            $brr1['sum_invalid_num'] += $arr['sum_invalid_num'];
        }
        // 统计所有门店数据
        Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$dt1."' and hours=".$hour1)->update($brr1);
        return $brr1;
    }
    // 更新所有数据完成的标志位
    public function updDataFlag($dt1=null,$hour1=null){
        if($dt1 && $hour1){
            // 把其余的都改为flag=0,最新小时统计数据改为flag=1
            $data_f['flag'] = 0;
            Db::name('sum_visit_hour')->where('flag',1)->update($data_f);
            // 当前统计小时改为1
            $data_flag['flag'] = 1;
            Db::name('sum_visit_hour')->where("log_time='$dt1' and hours=$hour1")->update($data_flag);
            // 美容师成交金额标记修改
            Db::name('sum_mrs_price_hour')->where('flag',1)->update($data_f);
            Db::name('sum_mrs_price_hour')->where("log_time='$dt1' and hours=$hour1")->update($data_flag);
        }
        return 1;
    }
    // 处理0点特殊时间,根据当前时间返回上一小时和日期
    public function updSpeHour($hour1=null){
        // 0点时,返回昨天23点和昨天日期
        $arr = [];
        if($hour1 == 0){
            $arr['dt1'] = date("Y-m-d",strtotime("-1 day"));//昨天日期
            $arr['hour1'] = 23;
        }else{
            //返回上一小时和今天日期
            $arr['dt1'] = date("Y-m-d");//昨天日期
            $arr['hour1'] = $hour1-1;
        }
        return $arr;
    }
    // 已参与活动的门店
    public function actStore(){
        $res = Db::name('tuan_info')->field('DISTINCT storeid')->select();
        return $res;
    }

    // 记录定时器请求下发日志
    public function write_log($arr){
    // 默认记录支付日志
    $arr['path'] = isset($arr['path'])==''?'redislog':$arr['path'];
    $arr['name'] = isset($arr['name'])==''?'redislog':$arr['name'];
    $logpath = RUNTIME_PATH.'/'.$arr['path'].'/'.$arr['name'].'_'.date('Y-m-d').'.txt';
    // 存在直接末尾追加写入
    file_put_contents($logpath,'时间:'.date('Y-m-d H:i:s').'--'.var_export($arr['msg'],true).PHP_EOL,FILE_APPEND);
    }
    // 每小时门店新顾客和老顾客人数
    // $dt1=>今日时间戳,$hour1=>上一小时
    public function getGkDay($dt1=null,$hour1=null){
        $dt11=input('dt1');$hour11=input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $hour1 = $hour11==null?$hour1:$hour11;
        $dt3 = date('Y-m-d',$dt1);//日期
        $map11['l.status'] = 2;
        $map11['m.id_regsource'] = 7;
        $map11['o.pay_time'] = ['>=',$dt1];
        $where11 = ' o.order_sn!=o.parent_order ';
        $brr1=['xgk_num'=>0,'lgk_num'=>0];
        // 统计今日新顾客
        $res = Db::name('tuan_list l')->join(['pt_tuan_order'=>'o'],'l.order_sn=o.parent_order','LEFT')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->field('count(o.uid) cnt,m.id_regsource,l.storeid')->where($map11)->where($where11)->group('l.storeid')->select();
        if($res){
            foreach ($res as $v) {
                $arr['xgk_num'] = $v['cnt'];
                Db::name('sum_visit_hour')->where("storeid=".$v['storeid']." and log_time='".$dt3."' and hours=".$hour1)->update($arr);
                $brr1['xgk_num'] += $arr['xgk_num'];
            }
        }
        // 统计今日老顾客
        $map11['m.id_regsource'] = ['neq',7];
        $res1 = Db::name('tuan_list l')->join(['pt_tuan_order'=>'o'],'l.order_sn=o.parent_order','LEFT')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->field('count(o.uid) cnt,o.order_sn,m.id_regsource,l.storeid')->where($map11)->where($where11)->group('o.uid')->select();
        foreach ($res1 as $v1) {
            $arr1['lgk_num'] = $v1['cnt'];
            Db::name('sum_visit_hour')->where("storeid=".$v1['storeid']." and log_time='".$dt3."' and hours=".$hour1)->update($arr1);
            $brr1['lgk_num'] += $arr1['lgk_num'];
        }
        // 统计所有门店数据
        Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$dt3."' and hours=".$hour1)->update($brr1);
        return $brr1;
    }
    // 每小时统计新老顾客数 及与上周平均比较比率
    // $dt1=>昨天日期,$dt2=>今天日期,$hour=>上一小时
    public function getGkRate($dt1=null,$dt2=null,$hour=null){
        $dt11=input('dt1');$dt21=input('dt2');$hour11=input('hour1');
        $dt1 = $dt11==null?$dt1:$dt11;
        $dt2 = $dt21==null?$dt2:$dt21;
        $hour = $hour11==null?$hour:$hour11;
        // 统计昨日的
        $brr = [];
        $res1 = Db::name('sum_visit_day d')->where('log_time',$dt1)->field('xgk_num,lgk_num,storeid')->group('storeid')->select();
        // 获取今日的
        $res2 = Db::name('sum_visit_hour')->field('xgk_num,lgk_num,storeid')->where("log_time='$dt2' and hours=$hour")->group('storeid')->select();
        if($res1){
            foreach ($res1 as $v1) {
                $brr1 = ['storeid'=>0,'day_xgk_rate'=>0,'day_lgk_rate'=>0];
                foreach ($res2 as $v2) {
                    if($v1['storeid'] == $v2['storeid']){
                        $brr1['storeid'] = $v2['storeid'];
                        if($v1['xgk_num']>0){
                            $brr1['day_xgk_rate'] = round(($v2['xgk_num']-$v1['xgk_num'])/$v1['xgk_num'],4);
                        }
                        if($v1['lgk_num']>0){
                            $brr1['day_lgk_rate'] = round(($v2['lgk_num']-$v1['lgk_num'])/$v1['lgk_num'],4);
                        }
                        if(!empty($brr1)){
                            $brr[] = $brr1;
                        }
                    }
                }
            }
        }
        if(!empty($brr)){
            foreach ($brr as $vb) {
                $data_vb['day_xgk_rate'] = $vb['day_xgk_rate'];
                $data_vb['day_lgk_rate'] = $vb['day_lgk_rate'];
                if($data_vb['day_lgk_rate'] || $data_vb['day_lgk_rate']){
                    Db::name('sum_visit_hour')->where("log_time='$dt2' and hours=$hour and storeid=".$vb['storeid'])->update($data_vb);
                }
            }
        }
        // 上周统计数据
        $dt3 = date('Y-m-d',strtotime('-2 monday', time()));// 上周一时间
        $dt4 = date('Y-m-d',strtotime('-1 sunday', time()));// 上周日时间
        $map1['log_time'] =['>=',$dt3];
        $map2['log_time'] =['<',$dt4];
        $res_w = Db::name('sum_visit_day d')->where($map1)->where($map2)->field('sum(xgk_num) xgk_num,sum(lgk_num) lgk_num,storeid')->group('storeid')->select();
        if($res_w){
            $brr = [];
            foreach ($res_w as $vw) {
                $brr1 = ['storeid'=>0,'week_xgk_rate'=>0,'week_lgk_rate'=>0];
                foreach ($res2 as $v2) {
                    if($vw['storeid'] == $v2['storeid']){
                        $brr1['storeid'] = $v2['storeid'];
                        $sum_xgk = $vw['xgk_num']/7;
                        $sum_lgk = $vw['lgk_num']/7;
                        if($sum_xgk){
                            $brr1['week_xgk_rate'] = round(($v2['xgk_num']-$sum_xgk)/$sum_xgk,4);
                        }
                        if($sum_lgk>0){
                            $brr1['week_lgk_rate'] = round(($v2['lgk_num']-$sum_lgk)/$sum_lgk,4);
                        }
                        if(!empty($brr1)){
                            $brr[] = $brr1;
                        }
                    }
                }
            }
        }
        if(!empty($brr)){
            foreach ($brr as $vb) {
                $data_sz['week_xgk_rate'] = $vb['week_xgk_rate'];
                $data_sz['week_lgk_rate'] = $vb['week_lgk_rate'];
                if($data_sz['week_xgk_rate'] || $data_sz['week_lgk_rate']){
                    Db::name('sum_visit_hour')->where("log_time='$dt2' and hours=$hour and storeid=".$vb['storeid'])->update($data_sz);
                }

            }
        }
        // 上月统计数据
        $dt3 =  date('Y-m-d',strtotime(date('Y-m-01', strtotime('-1 month'))));// 上月一号时间
        $dt4 =  date('Y-m-d',strtotime(date('Y-m', time()) . '-01 00:00:00'));// 本月1号
        $map1['log_time'] =['>=',$dt3];
        $map2['log_time'] =['<',$dt4];
        $res_m = Db::name('sum_visit_day d')->where($map1)->where($map2)->field('sum(xgk_num) xgk_num,sum(lgk_num) lgk_num,storeid')->group('storeid')->select();
        if($res_m){
            $brr = [];
            foreach ($res_m as $vm) {
                $brr1 = ['storeid'=>0,'month_xgk_rate'=>0,'month_lgk_rate'=>0];
                foreach ($res2 as $v2) {
                    if($vm['storeid'] == $v2['storeid']){
                        $brr1['storeid'] = $v2['storeid'];
                        $sum_price = $vm['xgk_num']/30;
                        $sum_num = $vm['lgk_num']/30;
                        if($sum_price){
                            $brr1['month_xgk_rate'] = round(($v2['xgk_num']-$sum_price)/$sum_price,4);
                        }
                        if($sum_num>0){
                            $brr1['month_lgk_rate'] = round(($v2['lgk_num']-$sum_num)/$sum_num,4);
                        }
                        if(!empty($brr1)){
                            $brr[] = $brr1;
                        }
                    }
                }
            }
        }
        if(!empty($brr)){
            foreach ($brr as $vb) {
                $data_sm['month_xgk_rate'] = $vb['month_xgk_rate'];
                $data_sm['month_lgk_rate'] = $vb['month_lgk_rate'];
                if($data_sm['month_xgk_rate'] || $data_sm['month_lgk_rate']){
                    Db::name('sum_visit_hour')->where("log_time='$dt2' and hours=$hour and storeid=".$vb['storeid'])->update($data_sm);
                }
            }
        }
    }

    // 每小时统计 单独购总成交金额、总订单数量和分享购总成交金额、总订单数量
    // $dt1=>今天日期,$hour1=>上一小时,$dh=>今天日期当前小时
    public function buySumPrice($dt1=null,$hour1=null,$dh=null){
        $store_ids = [];$data2['buy_share']=0;
        // 分享购总成交金额
        $map['order_type'] = 1;
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dh ")->field('sum(l.tuan_price) pay_price,l.storeid,l.order_type')->where($map)->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $arr1['dt1'] = $dt1;
            $arr1['hour1'] = $hour1;
            foreach ($res as $v) {
                $arr1['storeid'] = $v['storeid'];
                $data1['buy_share'] = $v['pay_price'];
                // 修改每个门店分享购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_share'] += $data1['buy_share'];
            }
            // 修改总门店分享购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        // 单独购总成交金额
        $map['order_type'] = 2;
        $data2=[];
        $res1 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dh ")->field('sum(l.tuan_price) pay_price,l.storeid,l.order_type')->where($map)->group('l.storeid')->order('l.storeid desc')->select();
        if($res1){
            $data2['buy_alone']=0;
            $arr1['dt1'] = $dt1;
            $arr1['hour1'] = $hour1;
            foreach ($res1 as $v1) {
                $arr1['storeid'] = $v1['storeid'];
                $data1['buy_alone'] = $v1['pay_price'];
                // 修改每个门店分享购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_alone'] += $data1['buy_alone'];
            }
            // 修改总门店分享购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        // 分享购总成交单数
        $map['order_type'] = 1;
        $data1=[];$data2=[];
        $res2 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dh ")->field('count(l.id) cnt,l.storeid,l.order_type')->where($map)->group('l.storeid')->order('l.storeid desc')->select();
        if($res2){
            $data2['buy_share_num']=0;
            $arr1['dt1'] = $dt1;
            $arr1['hour1'] = $hour1;
            foreach ($res2 as $v2) {
                $arr1['storeid'] = $v2['storeid'];
                $data1['buy_share_num'] = $v2['cnt'];
                // 修改每个门店分享购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_share_num'] += $data1['buy_share_num'];
            }
            // 修改总门店分享购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        // 单独购总成交单数
        $map['order_type'] = 2;
        $data1=[];$data2=[];
        $res2 = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2 and l.success_time<=$dh ")->field('count(l.id) cnt,l.storeid,l.order_type')->where($map)->group('l.storeid')->order('l.storeid desc')->select();
        if($res2){
            $data2['buy_alone_num']=0;
            $arr1['dt1'] = $dt1;
            $arr1['hour1'] = $hour1;
            foreach ($res2 as $v2) {
                $arr1['storeid'] = $v2['storeid'];
                $data1['buy_alone_num'] = $v2['cnt'];
                // 修改每个门店分享购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_alone_num'] += $data1['buy_alone_num'];
            }
            // 修改总门店分享购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        return 1;
    }

    // 每小时统计当日单独购金额及单数和分享购金额及单数
    // $dt1=>今天日期时间戳,$dt2=>今天日期当前小时时间戳,$hour1=>上一小时
    public function buyDayPrice($dt1,$dt2,$hour1){
        $data2['buy_share_day'] = 0;$data1=[];
        $dt11 = date('Y-m-d',$dt1);//转化成日期
        // 分享购当日成交金额
        $map['order_type'] = 1;
        $map['l.success_time'] = ['>=',$dt1];
        $map1['l.success_time'] = ['<',$dt2];
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2")->field('sum(l.tuan_price) pay_price,l.storeid,l.order_type')->where($map)->where($map1)->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $arr1['dt1'] = $dt11;
            $arr1['hour1'] = $hour1;
            foreach ($res as $v) {
                $arr1['storeid'] = $v['storeid'];
                $data1['buy_share_day'] = $v['pay_price'];
                // 修改每个门店分享购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_share_day'] += $data1['buy_share_day'];
            }
            // 修改总门店分享购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        // 单独购当日成交金额
        $map['order_type'] = 2;
        $data2 = [];$data1=[];
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2")->field('sum(l.tuan_price) pay_price,l.storeid,l.order_type')->where($map)->where($map1)->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $data2['buy_alone_day'] = 0;
            $arr1['dt1'] = $dt11;
            $arr1['hour1'] = $hour1;
            foreach ($res as $v) {
                $arr1['storeid'] = $v['storeid'];
                $data1['buy_alone_day'] = $v['pay_price'];
                // 修改每个门店单独购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_alone_day'] += $data1['buy_alone_day'];
            }
            // 修改总门店单独购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        // 分享购当日成交单数
        $data2 = [];$data1=[];
        $map['order_type'] = 1;
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2")->field('count(l.id) cnt,l.storeid,l.order_type')->where($map)->where($map1)->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $data2['buy_share_num_day'] = 0;
            $arr1['dt1'] = $dt11;
            $arr1['hour1'] = $hour1;
            foreach ($res as $v) {
                $arr1['storeid'] = $v['storeid'];
                $data1['buy_share_num_day'] = $v['cnt'];
                // 修改每个门店分享购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_share_num_day'] += $data1['buy_share_num_day'];
            }
            // 修改总门店分享购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        // 单独购当日成交单数
        $data2 = [];$data1=[];
        $map['order_type'] = 2;
        $res = Db::name('tuan_info ti')->join(['pt_tuan_list'=>'l'],'l.tuan_id=ti.id','LEFT')->where("l.status=2")->field('count(l.id) cnt,l.storeid,l.order_type')->where($map)->where($map1)->group('l.storeid')->order('l.storeid desc')->select();
        if($res){
            $data2['buy_alone_num_day'] = 0;
            $arr1['dt1'] = $dt11;
            $arr1['hour1'] = $hour1;
            foreach ($res as $v) {
                $arr1['storeid'] = $v['storeid'];
                $data1['buy_alone_num_day'] = $v['cnt'];
                // 修改每个门店单独购总成交额
                $this->upd_hour_data($arr1,$data1);
                $data2['buy_alone_num_day'] += $data1['buy_alone_num_day'];
            }
            // 修改总门店单独购总成交额
            $this->upd_hour_data_all($arr1,$data2);
        }
        return 1;
    }

    // 修改每小时门店数据
    // $arr1=>storeid[门店id],dt1=>今日日期,hour1=>上一小时; $data1=>修改数据
    private function upd_hour_data($arr1,$data1){
        $res = Db::name('sum_visit_hour')->where("storeid=".$arr1['storeid']." and log_time='".$arr1['dt1']."' and hours=".$arr1['hour1'])->update($data1);
        return $res;
    }
    // 修改每小时总门店数据
    // $arr1=>storeid[门店id],dt1=>今日日期,hour1=>上一小时; $data1=>修改数据
    private function upd_hour_data_all($arr1,$data1){
        $res = Db::name('sum_visit_hour')->where("storeid=0 and log_time='".$arr1['dt1']."' and hours=".$arr1['hour1'])->update($data1);
        return $res;
    }
}