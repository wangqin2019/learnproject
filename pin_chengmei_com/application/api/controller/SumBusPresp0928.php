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
 * swagger: 统计数据分析-业务透视
 */
class SumBusPresp extends SumHour
{
    protected $msg = '暂无数据';
    // 业务透视-门店
    public function storeRank(){
        // 请求数据
        $storeid = input('param.storeid',0);// 0=>所有门店
        // 初始化
        $arr1['dt'] = date('Y-m-d');
        $hour = date('H');
        // 如果当前时间刚好是整点,则自动减1
//        $hour = strtotime(date('Y-m-d H:i'))<strtotime(date('Y-m-d 00:02'))?($hour-1):$hour;
        $arr1['hours'] = $hour-1;// 上一个小时统计数据
        $res_dt = $this->updSpeHour($arr1['hours']);
        if($res_dt){
            $arr1['dt'] = $res_dt['dt1'];
            $arr1['hours'] = $res_dt['hour1'];
        }

            // 其他门店能看到门店排名
            $mapt['h.storeid'] = ['neq',0];
            $mapt['h.sum_all_price'] = ['>',0];
//            $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('h.storeid,h.log_time,h.hours,h.sum_all_price,b.title')->where("h.log_time='".$arr1['dt']."' and h.hours=".$arr1['hours'])->where($mapt)->group('h.storeid')->order('sum_all_price desc')->limit(20)->select();
        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'LEFT')->join(['sys_department'=>'sd'],['sd.id_department=sdr.id_department'],'LEFT')->field('h.storeid,h.log_time,h.hours,h.sum_all_price,b.title,sd.st_department')->where("h.log_time='".$arr1['dt']."' and h.flag=1")->where($mapt)->group('h.storeid')->order('sum_all_price desc')->limit(20)->select();
        if($res){
            $arr3 = ['rank_id'=>0,'sum_price'=>0,'title'=>0,'storeid'=>$storeid,'agency_name'=>''];
            // 排名列表[名次,金额,门店名称]
            $arr2['rank_id'] = 0;$rest2=[];
            foreach ($res as $v) {
                $arr2['rank_id']++;
                $arr2['sum_price'] = round($v['sum_all_price'],2);
                $arr2['title'] = $v['title'];
                $arr2['storeid'] = $v['storeid'];
                $arr2['agency_name'] = $v['st_department'].'办事处';
                $rest2[] = $arr2;
                // 当前门店排名 (门店名称,金额,名次,总榜名)
                if($v['storeid'] == $storeid){
                    $arr3['rank_id'] = $arr2['rank_id'];
                    $arr3['sum_price'] = round($v['sum_all_price'],2);
                    $arr3['title'] = $v['title'];
                    $arr3['agency_name'] = $v['st_department'].'办事处';
                }
            }
//            print_r($rest2);print_r($arr3);die;
            // 查询对应门店
            if($storeid && empty($arr3['title'])){
                $res_title1 = Db::table('ims_bwk_branch')->field('title')->where('id',$storeid)->limit(1)->find();
                if($res_title1){
                    $arr3['title'] = $res_title1['title'];
                }
            }

            $arr3['general'] = count($rest2);
            if($arr2){
                $this->code = 1;
                $this->msg = '获取成功';
                $this->data['stores'] = $arr3;
                $this->data['rank_list'] = $rest2;
            }
        }
        // 办事处下对应的参与门店,每小时统计一次,诚美能看
        if($storeid == 0){
            // 参与门店
            $res1 = Db::name('sum_agency_hour')->field('agency_id,agency_name,store_num')->where("log_time='".$arr1['dt']."' and hours=".$arr1['hours'])->select();
            if($res1){
                $arr2_2 = [];$arr2=[];
                // 参与门店,日期,[办事处名称,门店数]
                $rest['stores'] = 0;
                $rest['dt'] = $arr1['dt'];
                foreach($res1 as $v1){
                    $rest['stores'] += $v1['store_num'];
                    $arr2['agency_name'] = $v1['agency_name'];
                    $arr2['store_num'] = $v1['store_num'];
                    $rest1[] = $arr2;
                }
                $this->data['agency_list'] = $rest1;
//                $res2 = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('h.storeid,h.log_time,h.hours,h.sum_all_price,b.title')->where("h.log_time='".$arr1['dt']."' and h.hours=".$arr1['hours'])->where('h.storeid',0)->group('h.storeid')->order('sum_all_price desc')->limit(20)->limit(1)->find();
                $res2 = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('h.storeid,h.log_time,h.hours,h.sum_all_price,b.title')->where("h.log_time='".$arr1['dt']."' and h.flag=1")->where('h.storeid',0)->group('h.storeid')->order('sum_all_price desc')->limit(20)->limit(1)->find();
                if($res2){
                    $arr2_2['stores'] = $rest['stores'];
                    $arr2_2['dt'] = $arr1['dt'];
                }
                $this->data['stores'] = $arr2_2;
//                $this->code = 1;
//                $this->msg = '获取成功';
//                $this->data = ['stores'=>$rest,'agency_list'=>$rest1];
            }
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }

    // 业务透视-成交金额
    public function sumPrice(){
        // 请求数据
        $storeid = input('param.storeid',0);
        // 初始化参数
        $h = date('H');
        // 如果当前时间刚好是整点,则自动减1
//        $h = strtotime($dt11)<strtotime(date('Y-m-d 00:02'))?($h-1):$h;
        $h1 = $h - 1;// 前1个小时
        $dt = date('Y-m-d');// 当前日期
        $res_dt = $this->updSpeHour($h);
        if($res_dt){
            $dt = $res_dt['dt1'];
            $h1 = $res_dt['hour1'];
        }
        // 门店名称,日期,总成交额,进行中订单金额,成交单数
//        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('sum_all_price,sum_ing_price,sum_ord_num,b.title')->where('h.storeid='.$storeid.' and h.hours='.$h1.' and h.log_time="'.$dt.'" ')->limit(1)->find();
//        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('sum_all_price,sum_ing_price,sum_ord_num,b.title')->where('h.storeid='.$storeid.' and h.flag=1 and h.log_time="'.$dt.'" ')->limit(1)->find();
        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('sum_all_price,sum_ing_price,sum_ord_num,b.title,buy_alone,buy_share,buy_alone_num,buy_share_num')->where('h.storeid='.$storeid.' and h.flag=1 and h.log_time="'.$dt.'" ')->limit(1)->find();
        if($res){
            $arr1['title'] = $res['title'];
            $arr1['dt'] = $dt;
            $arr1['sum_all_price'] = $res['sum_all_price'];
            $arr1['sum_ing_price'] = $res['sum_ing_price'];
            $arr1['sum_ord_num'] = $res['sum_ord_num'];
            $arr1['buy_alone'] = $res['buy_alone'];
            $arr1['buy_share'] = $res['buy_share'];
            $arr1['buy_alone_num'] = $res['buy_alone_num'];
            $arr1['buy_share_num'] = $res['buy_share_num'];
            if($storeid == 0){
                $arr1['title'] = '所有门店';
            }
            //比率 {成交昨日比率,成交昨日上升标记,成交上周比率,成交上周上升标记,成交上月比率,成交上月上升标记},{单数昨日比率,单数昨日上升标记,单数上周比率,单数上周上升标记,单数上月比率,单数上月上升标记}

            $arr2=['sum_price'=>$arr1['sum_all_price'],'price_day_rate'=>'-','price_day_rate_flag'=>0,'price_week_rate'=>'-','price_week_rate_flag'=>0,'price_month_rate'=>'-','price_month_rate_flag'=>0,'sum_ord_num'=>$arr1['sum_ord_num'],'num_day_rate'=>'-','num_day_rate_flag'=>0,'num_week_rate'=>'-','num_week_rate_flag'=>0,'num_month_rate'=>'-','num_month_rate_flag'=>0];
            $arr1_1 = ['dt'=>date("Y-m-d",strtotime("-1 day"))];// 昨日日期

            // 成交金额,成交单数
//            $res2 = Db::name('sum_visit_hour')->field('id,storeid,day_price sum_price,day_num sum_ord_num,day_price_rate price_day_rate,day_num_rate num_day_rate,week_price_rate price_week_rate,week_num_rate num_week_rate,month_price_rate price_month_rate,month_num_rate num_month_rate')->where("storeid=$storeid and log_time='".$dt."' and hours=$h1")->limit(1)->find();
            $res2 = Db::name('sum_visit_hour')->field('id,storeid,day_price sum_price,day_num sum_ord_num,day_price_rate price_day_rate,day_num_rate num_day_rate,week_price_rate price_week_rate,week_num_rate num_week_rate,month_price_rate price_month_rate,month_num_rate num_month_rate,buy_alone_day,buy_share_day,buy_alone_num_day,buy_share_num_day')->where("storeid=$storeid and log_time='".$dt."' and flag=1")->limit(1)->find();
            if($res2){
                $arr2['buy_alone_day'] = $res2['buy_alone_day'];
                $arr2['buy_share_day'] = $res2['buy_share_day'];
                $arr2['buy_alone_num_day'] = $res2['buy_alone_num_day'];
                $arr2['buy_share_num_day'] = $res2['buy_share_num_day'];
                $arr2['sum_price'] = $res2['sum_price'];
                $arr2['price_day_rate'] = $res2['price_day_rate'];
                $arr2['price_day_rate_flag'] = 1;
                $arr2['price_week_rate'] = $res2['price_week_rate'];
                $arr2['price_week_rate_flag'] = 1;
                $arr2['price_month_rate'] = $res2['price_month_rate'];
                $arr2['price_month_rate_flag'] = 1;
                $arr2['sum_ord_num'] = $res2['sum_ord_num'];
                $arr2['num_day_rate'] = $res2['num_day_rate'];
                $arr2['num_day_rate_flag'] = 1;
                $arr2['num_week_rate'] = $res2['num_week_rate'];
                $arr2['num_week_rate_flag'] = 1;
                $arr2['num_month_rate'] = $res2['num_month_rate'];
                $arr2['num_month_rate_flag'] = 1;
                if($arr2['price_day_rate']<0){
                    $arr2['price_day_rate_flag'] = 0;
                }
                $arr2['price_day_rate'] = (abs($arr2['price_day_rate']*100)).'%';
                if($arr2['price_week_rate']<0){
                    $arr2['price_week_rate_flag'] = 0;
                }
                $arr2['price_week_rate'] = (abs($arr2['price_week_rate']*100)).'%';
                if($arr2['price_month_rate']<0){
                    $arr2['price_month_rate_flag'] = 0;
                }
                $arr2['price_month_rate'] = (abs($arr2['price_month_rate']*100)).'%';
                if($arr2['num_day_rate']<0){
                    $arr2['num_day_rate_flag'] = 0;
                }
                $arr2['num_day_rate'] = (abs($arr2['num_day_rate']*100)).'%';
                if($arr2['num_week_rate']<0){
                    $arr2['num_week_rate_flag'] = 0;
                }
                $arr2['num_week_rate'] = (abs($arr2['num_week_rate']*100)).'%';
                if($arr2['num_month_rate']<0){
                    $arr2['num_month_rate_flag'] = 0;
                }
                $arr2['num_month_rate'] = (abs($arr2['num_month_rate']*100)).'%';
            }
            $rest['deal'] = $arr2;
            $this->code = 1;
//            unset($arr1['sum_ord_num']);
            $this->data = ['stores'=>$arr1,'deal_rate'=>$rest['deal']];
            $this->msg = '获取成功';
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
    // 业务透视-成交趋势、订单失效趋势
    public function dealTrend(){
        // 请求参数
        $storeid = input('param.storeid',0);// 门店id
        // 初始化参数
        $dt = date("Y-m-d",strtotime("-7 day"));
        $week_dt = [$dt,date("Y-m-d",strtotime("-6 day")),date("Y-m-d",strtotime("-5 day")),date("Y-m-d",strtotime("-4 day")),date("Y-m-d",strtotime("-3 day")),date("Y-m-d",strtotime("-2 day")),date("Y-m-d",strtotime("-1 day"))];
        $data_dt = [0,0,0,0,0,0,0];
        $data_dt1 = [0,0,0,0,0,0,0];
        $data_dt2 = [0,0,0,0,0,0,0];
        $data_dt3 = [0,0,0,0,0,0,0];
        $data_dt4 = [0,0,0,0,0,0,0];
        $data_dt5 = [0,0,0,0,0,0,0];
        $data_dt6 = [0,0,0,0,0,0,0];
        // 成交金额趋势,每日失效单数,每日成交单数
        $map['log_time'] = ['>=',$dt];
        $map['storeid'] = ['eq',$storeid];
        $res = Db::name('sum_visit_day')->field('sum_price,sum_ord_num,sum_ord_invalid_num sum_invalid_num,log_time,buy_alone_price_day,buy_alone_num_day,buy_share_price_day,buy_share_num_day')->where($map)->order('log_time asc')->select();
        if($res){
            $arr2 = [];$arr2_1 = [];$arr_sx=[];$arr4_1=[];$arr5_1=[];$arr6_1=[];$arr7_1=[];$arr8_1=[];
            foreach ($res as $v) {
                foreach ($week_dt as $k=>$v_dt) {
                    if($v['log_time'] == $v_dt){
                        $data_dt[$k] = $v['sum_price'];
                        $data_dt1[$k] = $v['sum_ord_num'];
                        $data_dt2[$k] = $v['sum_invalid_num'];
                        $data_dt3[$k] = $v['buy_alone_price_day'];
                        $data_dt4[$k] = $v['buy_alone_num_day'];
                        $data_dt5[$k] = $v['buy_share_price_day'];
                        $data_dt6[$k] = $v['buy_share_num_day'];
                    }
                }
            }
            $arr1 = array_combine($week_dt,$data_dt);
            $arr3 = array_combine($week_dt,$data_dt1);
            $arr4 = array_combine($week_dt,$data_dt2);
            $arr5 = array_combine($week_dt,$data_dt3);
            $arr6 = array_combine($week_dt,$data_dt4);
            $arr7 = array_combine($week_dt,$data_dt5);
            $arr8 = array_combine($week_dt,$data_dt6);
            foreach ($arr1 as $k=>$v1) {
                $arr1_1['dt'] = $k;
                $arr1_1['cnt'] = $v1;
                $arr2[] = $arr1_1;
            }
            foreach ($arr3 as $k=>$v3) {
                $arr1_2['dt'] = $k;
                $arr1_2['cnt'] = $v3;
                $arr2_1[] = $arr1_2;
            }
            foreach ($arr4 as $k4=>$v4) {
                $arr1_4['dt'] = $k4;
                $arr1_4['cnt'] = $v4;
                $arr4_1[] = $arr1_4;
            }
            foreach ($arr5 as $k5=>$v5) {
                $arr1_5['dt'] = $k5;
                $arr1_5['cnt'] = $v5;
                $arr5_1[] = $arr1_5;
            }
            foreach ($arr6 as $k6=>$v6) {
                $arr1_6['dt'] = $k6;
                $arr1_6['cnt'] = $v6;
                $arr6_1[] = $arr1_6;
            }
            foreach ($arr7 as $k7=>$v7) {
                $arr1_7['dt'] = $k7;
                $arr1_7['cnt'] = $v7;
                $arr7_1[] = $arr1_7;
            }
            foreach ($arr8 as $k8=>$v8) {
                $arr1_8['dt'] = $k8;
                $arr1_8['cnt'] = $v8;
                $arr8_1[] = $arr1_8;
            }
            // 每天门店失效订单Top5 排名,门店名称,总失效单数
//            $mapt['d.log_time'] = ['eq',date("Y-m-d",strtotime("-1 day"))];
//            $mapt['d.storeid'] = ['neq',0];
//            $res_sx = Db::name('sum_visit_day d')->join(['ims_bwk_branch'=>'b'],['b.id=d.storeid'],'LEFT')->field('b.title,sum(sum_ord_invalid_all_num) invalid_num,log_time,d.storeid')->where($mapt)->group('d.storeid')->order('sum_ord_invalid_all_num desc')->limit(5)->select();
            $mapt['log_time'] = date('Y-m-d');
            $mapt['sum_invalid_num'] = ['>',0];
            $mapt['storeid'] = ['neq',0];
            $res_sx = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,sum(sum_invalid_num) invalid_num,log_time,h.storeid')->where($mapt)->group('h.storeid')->order('sum_invalid_num desc')->limit(5)->select();
            if($res_sx){
                foreach ($res_sx as $k=>$v_sx) {
                    $arr_sx1['rank_id'] = $k+1;
                    $arr_sx1['title'] = $v_sx['title'];
                    $arr_sx1['invalid_num'] = $v_sx['invalid_num'];
                    $arr_sx[] = $arr_sx1;
                }
            }

            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = ['deal'=>$arr2,'deal_num'=>$arr2_1,'invalid'=>$arr4_1,'invalid_rank'=>$arr_sx,'buy_alone_price_day'=>$arr5_1,'buy_alone_num_day'=>$arr6_1,'buy_share_price_day'=>$arr7_1,'buy_share_num_day'=>$arr8_1];
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
    // 业务透视-美容师
    public function mrsSum(){
        // 请求参数
        $storeid = input('param.storeid',0);// 门店id
        // 初始化参数
        $dt = date('Y-m-d');// 今日
        $dt1 = date('Y-m-d',strtotime("-1 day"));// 昨日
        $hour = date('H');
        // 如果当前时间刚好是整点,则自动减1
//        $hour = strtotime(date('Y-m-d H:i'))<strtotime(date('Y-m-d 00:02'))?($hour-1):$hour;
        $hours = $hour-1;
        $res_dt = $this->updSpeHour($hour);
        if($res_dt){
            $dt = $res_dt['dt1'];
            $hours = $res_dt['hour1'];
        }
        $rest=['beautician'=>(object)[],'guke_rate'=>'','guke_trend'=>[],'beautician_rank'=>[]];
        // 当前门店,日期,门店美容师人数,活动美容师人数,
        $map1['h.log_time'] = $dt;
//        $map1['h.hours'] = $hours;
//        $res1 = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,h.beautician_num,h.beautician_active_num')->where('h.storeid',$storeid)->where($map1)->limit(1)->find();
        $map1['h.flag'] = 1;
        $res1 = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,h.beautician_num,h.beautician_active_num')->where('h.storeid',$storeid)->where($map1)->limit(1)->find();
        if($res1){
            $arr1['title'] = $res1['title'];
            $arr1['dt'] = $dt;
            $arr1['beautician_num'] = $res1['beautician_num'];
            $arr1['beautician_active_num'] = $res1['beautician_active_num'];
            $rest['beautician'] = $arr1;
        }
        if($storeid==0){
            $rest['beautician']['title'] = '所有门店';
        }
        // {老顾客人数,昨日比,上周比,上月比},{新顾客人数,昨日比,上周比,上月比}
        $map['storeid'] = $storeid;
        $map['log_time'] = $dt;
        $map['flag'] = 1;
//        $res2 = Db::name('sum_visit_day d')->field('xgk_num,lgk_num,xgk_day_rate,lgk_day_rate,xgk_week_rate,lgk_week_rate,xgk_month_rate,lgk_month_rate')->where($map)->limit(1)->find();
        $res2 = Db::name('sum_visit_hour d')->field('xgk_num,lgk_num,day_xgk_rate xgk_day_rate,day_lgk_rate lgk_day_rate,week_xgk_rate xgk_week_rate,week_lgk_rate lgk_week_rate,month_xgk_rate xgk_month_rate,month_lgk_rate lgk_month_rate')->where($map)->limit(1)->find();
        $arr2=['xgk_num'=>0,'xgk_day_rate'=>'-','xgk_day_rate_flag'=>0,'xgk_week_rate'=>'-','xgk_week_rate_flag'=>0,'xgk_month_rate'=>'-','xgk_month_rate_flag'=>0,'lgk_num'=>0,'lgk_day_rate'=>'-','lgk_day_rate_flag'=>0,'lgk_week_rate'=>'-','lgk_week_rate_flag'=>0,'lgk_month_rate'=>'-','lgk_month_rate_flag'=>0];
        if($res2){
            $arr2['xgk_num'] = $res2['xgk_num'];
            $arr2['xgk_day_rate'] = $res2['xgk_day_rate'];
            $arr2['xgk_day_rate_flag'] = 1;
            $arr2['xgk_week_rate'] = $res2['xgk_week_rate'];
            $arr2['xgk_week_rate_flag'] = 1;
            $arr2['xgk_month_rate'] = $res2['xgk_month_rate'];
            $arr2['xgk_month_rate_flag'] = 1;
            $arr2['lgk_num'] = $res2['lgk_num'];
            $arr2['lgk_day_rate'] = $res2['lgk_day_rate'];
            $arr2['lgk_day_rate_flag'] = 1;
            $arr2['lgk_week_rate'] = $res2['lgk_week_rate'];
            $arr2['lgk_week_rate_flag'] = 1;
            $arr2['lgk_month_rate'] = $res2['lgk_month_rate'];
            $arr2['lgk_month_rate_flag'] = 1;
            if($arr2['xgk_day_rate']<0){
                $arr2['xgk_day_rate_flag'] = 0;
            }
            $arr2['xgk_day_rate'] = (abs($arr2['xgk_day_rate']*100)).'%';
            if($arr2['xgk_week_rate']<0){
                $arr2['xgk_week_rate_flag'] = 0;
            }
            $arr2['xgk_week_rate'] = (abs($arr2['xgk_week_rate']*100)).'%';
            if($arr2['xgk_month_rate']<0){
                $arr2['xgk_month_rate_flag'] = 0;
            }
            $arr2['xgk_month_rate'] = (abs($arr2['xgk_month_rate']*100)).'%';
            if($arr2['lgk_day_rate']<0){
                $arr2['lgk_day_rate_flag'] = 0;
            }
            $arr2['lgk_day_rate'] = (abs($arr2['lgk_day_rate']*100)).'%';
            if($arr2['lgk_week_rate']<0){
                $arr2['lgk_week_rate_flag'] = 0;
            }
            $arr2['lgk_week_rate'] = (abs($arr2['lgk_week_rate']*100)).'%';
            if($arr2['lgk_month_rate']<0){
                $arr2['lgk_month_rate_flag'] = 0;
            }
            $arr2['lgk_month_rate'] = (abs($arr2['lgk_month_rate']*100)).'%';
            $rest['guke_rate'] = $arr2;
        }
        // 新顾客增长趋势,前7日
        $dt3 = date("Y-m-d",strtotime("-7 day"));
        $map3['storeid'] = $storeid;
        $map3['log_time'] = ['>=',$dt3];
        $arr3_1 = [$dt3,date("Y-m-d",strtotime("-6 day")),date("Y-m-d",strtotime("-5 day")),date("Y-m-d",strtotime("-4 day")),date("Y-m-d",strtotime("-3 day")),date("Y-m-d",strtotime("-2 day")),date("Y-m-d",strtotime("-1 day"))];
        $arr3_2 = [0,0,0,0,0,0,0];
        $arr4_2 = [0,0,0,0,0,0,0];
        $arr5_2 = [0,0,0,0,0,0,0];
        $res3 = Db::name('sum_visit_day')->field('xgk_num,log_time,buy_alone_xgk_day,buy_share_xgk_day')->where($map3)->select();
        if($res3){
            foreach($res3 as $v3){
                foreach ($arr3_1 as $k3=>$v3_1) {
                    if($v3['log_time'] == $v3_1){
                        $arr3_2[$k3] = $v3['xgk_num'];
                        $arr4_2[$k3] = $v3['buy_alone_xgk_day'];
                        $arr5_2[$k3] = $v3['buy_share_xgk_day'];
                    }
                }
            }
            $arr_qs=[];
            $arr3_3 = array_combine($arr3_1,$arr3_2);
            if(!empty($arr3_3)){
                foreach ($arr3_3 as $k=>$v) {
                    $arr3['dt'] = $k;
                    $arr3['cnt'] = $v;
                    $arr_qs[] = $arr3;
                }
            }
            $arr_qs1=[];
            $arr4_3 = array_combine($arr3_1,$arr4_2);
            if(!empty($arr4_3)){
                foreach ($arr4_3 as $k=>$v) {
                    $arr4['dt'] = $k;
                    $arr4['cnt'] = $v;
                    $arr_qs1[] = $arr4;
                }
            }
            $arr_qs2=[];
            $arr5_3 = array_combine($arr3_1,$arr5_2);
            if(!empty($arr5_3)){
                foreach ($arr5_3 as $k=>$v) {
                    $arr5['dt'] = $k;
                    $arr5['cnt'] = $v;
                    $arr_qs2[] = $arr5;
                }
            }
            $rest['guke_trend'] = $arr_qs;
            $rest['buy_alone_xgk_day'] = $arr_qs1;
            $rest['buy_share_xgk_day'] = $arr_qs2;
        }
        // 美容师成交金额排行
        // 名次,名称,金额,门店,老顾客人数,新顾客人数,头像
        $map4['h.log_time'] = $dt;
        $map4['h.flag'] = 1;
//        $map4['h.hours'] = $hours;
        $res4 = Db::name('sum_mrs_price_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->join(['ims_bj_shopn_member'=>'m'],['m.id=h.uid'],'LEFT')->join(['ims_fans'=>'f'],['m.id=f.id_member'],'LEFT')->join(['pt_wx_user'=>'wu'],['wu.mobile=m.mobile'],'LEFT')->field('m.realname uname,h.sum_all_price sum_price,b.title,h.customer_old,h.customer_new,f.avatar,wu.avatar avatar2,h.storeid')->where($map4)->group('h.uid')->order('sum_price desc')->select();
        if($res4){
            $arr_4=[];
            foreach ($res4 as $k=>$v4) {
                if($v4['storeid']==0){
                    continue;
                }
                $arr4_1['rank_id'] = $k+1;
                $arr4_1['uname'] = $v4['uname'];
                $arr4_1['sum_price'] = $v4['sum_price'];
                $arr4_1['title'] = $v4['title'];
                $arr4_1['customer_old'] = $v4['customer_old'];
                $arr4_1['customer_new'] = $v4['customer_new'];
                $arr4_1['avatar'] = $v4['avatar']==null?$v4['avatar2']:$v4['avatar'];
                $arr_4[] = $arr4_1;
            }
            $rest['beautician_rank'] = $arr_4;
        }
        if(!empty($rest)){
            $this->code = 1;
            $this->data = $rest;
            $this->msg = '获取成功';
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
    // 业务透视-活动额度
    // $dt1=>昨日
    public function actLimit(){
        // 请求参数
        $storeid = input('param.storeid',0);// 门店id
        $rest = ['rate'=>'','trend'=>[],'rank'=>[]];
        // 初始化参数
        $dt = date('Y-m-d');// 今日
        $dt1 = date('Y-m-d',strtotime("-7 day"));// 7天前
        $h = date('H');
        // 如果当前时间刚好是整点,则自动减1
//        $h = strtotime(date('Y-m-d H:i'))<strtotime(date('Y-m-d 00:02'))?($h-1):$h;
        $h1 = $h - 1;// 前1个小时
        $res_dt = $this->updSpeHour($h);
        if($res_dt){
            $dt = $res_dt['dt1'];
            $h1 = $res_dt['hour1'];
        }
        $map['h.log_time'] = $dt ;
//        $map['h.hours'] = $h1 ;
        $map['h.storeid'] = $storeid ;
        //门店名称,活动额度完成比,日期,活动商品数,活动商品总额度,已完成额度,进行中额度
//        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,h.rate_limit,goods_num,goods_sum_limit,over_limit,ing_limit')->where($map)->limit(1)->find();
        $map['h.flag'] = 1 ;
        $res = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,h.rate_limit,goods_num,goods_sum_limit,over_limit,ing_limit')->where($map)->limit(1)->find();
        if($res){
            $arr1['title'] = $res['title'];
            $arr1['rate_limit'] = ($res['rate_limit']*100).'%';
            $arr1['dt'] = $dt;
            $arr1['act_goods'] = $res['goods_num'];
            $arr1['act_goods_limit'] = $res['goods_sum_limit'];
            $arr1['over_limit'] = $res['over_limit'];
            $arr1['ing_limit'] = $res['ing_limit'];
            $rest['rate'] = $arr1;
        }
        if($storeid == 0){
            $rest['rate']['title'] = '所有门店';
        }
        // 前7日趋势图
        $map1['log_time'] = ['>=',$dt1];
        $map1['storeid'] = $storeid;
        $arr3_1 = [$dt1,date("Y-m-d",strtotime("-6 day")),date("Y-m-d",strtotime("-5 day")),date("Y-m-d",strtotime("-4 day")),date("Y-m-d",strtotime("-3 day")),date("Y-m-d",strtotime("-2 day")),date("Y-m-d",strtotime("-1 day"))];
        $arr3_2 = [0,0,0,0,0,0,0];
        $res1 = Db::name('sum_visit_day d')->field('storeid,limit_rate,log_time')->where($map1)->select();
        if($res1){
            $arr2_1=[];
            foreach ($res1 as $v1) {
                foreach ($arr3_1 as $k3_1=>$v3_1) {
                    if($v1['log_time'] == $v3_1){
                        $arr3_2[$k3_1] = ($v1['limit_rate']*100);
                    }
                }
            }
            $arr_qs = [];
            $arr3_3 = array_combine($arr3_1,$arr3_2);
            if(!empty($arr3_3)){
                foreach ($arr3_3 as $k=>$v) {
                    $arr3['xdt'] = $k;
                    $arr3['yrate'] = $v;
                    $arr_qs[] = $arr3;
                }
            }
            $rest['trend'] = $arr_qs;
        }
        // 门店额度完成排名
        // 名次,门店名称,活动总额度,比率
        $map2['log_time'] = $dt;
//        $map2['hours'] = $h1;
        $map2['h.storeid'] = ['>',0];
//        $res3 = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('storeid,goods_sum_limit,rate_limit,b.title')->where($map2)->order('rate_limit desc')->limit(20)->select();
        $map2['flag'] = 1;
        $map2['rate_limit'] = ['>',0];
        $res3 = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('storeid,goods_sum_limit,rate_limit,b.title')->where($map2)->order('rate_limit desc')->select();
//        print_r($res3);die;
        if($res3){
            $arr4_1=[];
            foreach ($res3 as $k3=>$v3) {
                $arr4['rank_id'] = $k3+1;
                $arr4['title'] = $v3['title'];
                $arr4['act_sum_limit'] = $v3['goods_sum_limit'];
                $arr4['rate_limit'] = ($v3['rate_limit']*100).'%';
                $arr4_1[] = $arr4;
            }
            $rest['rank'] = $arr4_1;
        }
        if(!empty($rest)){
            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = $rest;
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }

    // 历史失效订单排名/当日失效订单排名
    public function invalidRank(){
        // 请求参数
        $storeid = input('param.storeid',0);// 门店id
        $type = input('param.type',1);// 类型 1=>当日,-1=>历史
        // 初始化参数
        $dt = date('Y-m-d');
        $dt1 = date('Y-m-d',strtotime("-1 day"));// 昨天;
        // 当日失效排名
        $mapt['log_time'] = $dt;
        $mapt['sum_invalid_num'] = ['>',0];
        $mapt['storeid'] = ['neq',0];
        $mapt['flag'] = 1;
        if($type == 1){
            $res_sx = Db::name('sum_visit_hour h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,sum_invalid_num invalid_num,log_time,h.storeid')->where($mapt)->group('h.storeid')->order('sum_invalid_num desc')->limit(5)->select();
            if($res_sx){
                foreach ($res_sx as $k=>$v_sx) {
                    $arr_sx1['rank_id'] = $k+1;
                    $arr_sx1['title'] = $v_sx['title'];
                    $arr_sx1['invalid_num'] = $v_sx['invalid_num'];
                    $arr_sx[] = $arr_sx1;
                }
            }
        }else{
            unset($mapt['sum_invalid_num']);unset($mapt['flag']);
            $mapt['sum_ord_invalid_all_num'] = ['>',0];
            $mapt['log_time'] = $dt1;
            $res_sx = Db::name('sum_visit_day h')->join(['ims_bwk_branch'=>'b'],['b.id=h.storeid'],'LEFT')->field('b.title,sum_ord_invalid_all_num invalid_num,log_time,h.storeid')->where($mapt)->group('h.storeid')->order('sum_ord_invalid_all_num desc')->limit(5)->select();
            if($res_sx){
                foreach ($res_sx as $k=>$v_sx) {
                    $arr_sx1['rank_id'] = $k+1;
                    $arr_sx1['title'] = $v_sx['title'];
                    $arr_sx1['invalid_num'] = $v_sx['invalid_num'];
                    $arr_sx[] = $arr_sx1;
                }
            }
        }
        if(!empty($arr_sx)){
            $this->code = 1;
            $this->data = $arr_sx;
            $this->msg = '获取成功';
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
}