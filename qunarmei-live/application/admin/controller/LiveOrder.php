<?php

namespace app\admin\controller;
use think\Db;
header('Access-Control-Allow-Origin: *');
/* 直播订单详情
 * */
class LiveOrder extends Base
{
    // 定义活动订单类型
    protected $orderTypeval = [
        1 => '普通订单商品',
        2 => '315活动门票商品',
        3 => '315活动直播商品',
        4 => '327活动门票商品',
        5 => '327活动直播商品',
        6 => '329活动门票商品',
        7 => '329活动直播商品',
        8 => '329活动之后直播商品',
        9 => '412活动门票商品',
        10 => '412活动直播商品',
        11 => '412之后活动门票商品',
        12 => '412之后活动直播商品',
    ];
    //服务器报表路径
    private $url = 'http://live.qunarmei.com/csv/';
    /**
     * 功能: 订单列表
     * 请求: key 建议搜索
     * 返回:
     */
    public function index(){
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        // echo "1111live_order_index";die;
        $order_type = input('order_type',0);
        $goods_title = input('goods_title');
        $key = input('key');
        $map = ''; $map1='';$ordersn_arr = [];// 当前订单数组
        $content = input('content')==''?'':input('content');
        $st_source = input('st_source')==''?'':input('st_source');
        $ordersn = input('ordersn');
        $signs = input('signs');
        if( $content && $content!=="")
        {
            // echo "<pre>";print_r($content);die;
            if($content == 5)
            {
                $map = " and ord.content like '%套盒%' ";
            }else
            {
                $map = " and (ord.content like '%内衣%' or ord.content like '%调整带%' or ord.content like '%文胸%' or ord.content like '%长筒袜%')";
            }

        }
        if($st_source)
        {
            // $map .= " and ord.st_source='$st_source' "  ;
            if($st_source == 'app')
            {
                $map .= " and ord.st_source in ('android','ios') "  ;
            }else
            { 
                $map .= " and ord.st_source='wxc' "  ;
            }
        }
        if($ordersn){
            $map .= " and ord.ordersn='{$ordersn}'";
        }
        if ($goods_title) {
            $map .= " and g.title like '%".$goods_title."%'";
        }
        if($signs){
            $map .= " and ibb.sign like '%".$signs."%'";
        }
        if ($order_type) {
            // 315活动门票商品
            if ($order_type == 2) {
                $map .= " and g.pcate=31 and g.ticket_type=1 and ord.payTime>=UNIX_TIMESTAMP('2020-03-13') and ord.payTime<UNIX_TIMESTAMP('2020-03-19')";
            }elseif ($order_type == 3) {
                // 315活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=0 and ord.payTime>=UNIX_TIMESTAMP('2020-03-13') and ord.payTime<UNIX_TIMESTAMP('2020-03-19')";
            }elseif ($order_type == 4) {
                // 327活动门票商品
                $map .= " and g.pcate=31 and g.ticket_type=1 and ord.payTime>=UNIX_TIMESTAMP('2020-03-25') and ord.payTime<UNIX_TIMESTAMP('2020-03-28')";
            }elseif ($order_type == 5) {
                // 327活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=0 and ord.payTime>=UNIX_TIMESTAMP('2020-03-25') and ord.payTime<UNIX_TIMESTAMP('2020-03-28')";
            }elseif ($order_type == 6) {
                // 329活动门票商品
                $map .= " and g.pcate=31 and g.ticket_type=1 and ord.payTime>=UNIX_TIMESTAMP('2020-03-28') and ord.payTime<UNIX_TIMESTAMP('2020-03-31')";
            }elseif ($order_type == 7) {
                // 329活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=0 and ord.payTime>=UNIX_TIMESTAMP('2020-03-28') and ord.payTime<UNIX_TIMESTAMP('2020-03-31')";
            }elseif ($order_type == 8) {
                // 329活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=0 and ord.payTime>=UNIX_TIMESTAMP('2020-03-31') ";
            }elseif ($order_type == 9) {
                // 412活动门票商品
                $map .= " and g.pcate=31 and g.ticket_type=2 and ord.payTime>=UNIX_TIMESTAMP('2020-04-10') and ord.payTime<UNIX_TIMESTAMP('2020-04-15 ";
            }elseif ($order_type == 10) {
                // 412活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=0 and ord.payTime>=UNIX_TIMESTAMP('2020-04-10') and ord.payTime<UNIX_TIMESTAMP('2020-04-15') ";
            }elseif ($order_type == 11) {
                // 412之后活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=2 and ord.payTime>=UNIX_TIMESTAMP('2020-04-15') ";
            }elseif ($order_type == 12) {
                // 412之后活动直播商品
                $map .= " and g.pcate=31 and g.ticket_type=0 and ord.payTime>=UNIX_TIMESTAMP('2020-04-15') ";
            }elseif ($order_type == 1) {
                // 普通订单商品
                $map .= " and g.pcate!=31 and g.pcate!=32";
            }
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数

        $dt2 = strtotime(date('Y-m-d',time()));$dt1 = $dt2-3600*24;
        //搜索支付时间
        if(input('dt1') && input('dt2'))
        {
            $dt1 = strtotime(input('dt1'));
            $dt2 = strtotime(input('dt2'));
        }
        $count = Db::table('ims_bj_shopn_order ord')
            ->join(['ims_bj_shopn_order_goods'=>'ordg'],['ord.id=ordg.orderid'],'LEFT')
            ->join(['ims_bwk_branch'=>'ibb'],['ord.storeid=ibb.id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'mem'],['mem.id=ord.uid'],'LEFT')
            ->join(['sys_bank_interestrate'=>'sbi'],['sbi.id_interestrate=ord.id_interestrate'],'LEFT')
            ->join(['sys_bank'=>'sb'],['sbi.id_bank = sb.id_bank'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['g.id = ordg.goodsid'],'LEFT')
            ->where('ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.$map )
            ->group('ord.id')
            ->count();//计算总页面
        // $count = Db::table('ims_bj_shopn_order ord,ims_bj_shopn_order_goods ordg,ims_bwk_branch ibb,ims_bj_shopn_member mem,sys_bank_interestrate sbi,sys_bank sb')->where('ord.id=ordg.orderid and ibb.id=mem.storeid and ord.uid=mem.id and ord.storeid=ibb.id and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.id_interestrate=sbi.id_interestrate and sbi.id_bank=sb.id_bank  '.$map )->field('ord.*,mem.realname,ibb.title,ibb.sign,ibb.sign,ibb.location_p,ibb.address,mem.mobile')->group('ord.id')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
//        print_r($count);
        $lists = Db::table('ims_bj_shopn_order ord')
            ->join(['ims_bj_shopn_order_goods'=>'ordg'],['ord.id=ordg.orderid'],'LEFT')
            ->join(['ims_bwk_branch'=>'ibb'],['ord.storeid=ibb.id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'mem'],['mem.id=ord.uid'],'LEFT')
            ->join(['sys_bank_interestrate'=>'sbi'],['sbi.id_interestrate=ord.id_interestrate'],'LEFT')
            ->join(['sys_bank'=>'sb'],['sbi.id_bank = sb.id_bank'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['g.id = ordg.goodsid'],'LEFT')
            ->where('ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.$map )
            ->limit($pre,$limits)
            ->field('ord.id_interestrate,ord.ums_instalment,ord.ordersn,ord.id as orderid,ibb.title,ibb.sign,ibb.location_p,ibb.address,mem.realname,mem.mobile,ord.content,ord.price,ord.createtime,ord.payTime,sb.id_bank,sb.st_abbre_bankname bkname,sbi.no_period,ord.st_source,ord.id_interestrate')
            ->group('ord.id')
            ->select();
        $lists1 = Db::table('ims_bj_shopn_order ord')
            ->join(['ims_bj_shopn_order_goods'=>'ordg'],['ord.id=ordg.orderid'],'LEFT')
            ->join(['ims_bwk_branch'=>'ibb'],['ord.storeid=ibb.id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'mem'],['mem.id=ord.uid'],'LEFT')
            ->join(['sys_bank_interestrate'=>'sbi'],['sbi.id_interestrate=ord.id_interestrate'],'LEFT')
            ->join(['sys_bank'=>'sb'],['sbi.id_bank = sb.id_bank'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['g.id = ordg.goodsid'],'LEFT')
            ->where('ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.$map )
            ->field('ord.id_interestrate,ord.ums_instalment,ord.ordersn,ord.ordersn,ord.id as orderid,ibb.title,ibb.sign,ibb.location_p,ibb.address,mem.realname,mem.mobile,ord.content,ord.price,ord.createtime,ord.payTime,sb.id_bank,sb.st_abbre_bankname bkname,sbi.no_period,ord.st_source,ord.id_interestrate,g.pcate')
            ->group('ord.id')
            ->select();
        // $lists = Db::table('ims_bj_shopn_order ord,ims_bj_shopn_order_goods ordg,ims_bwk_branch ibb,ims_bj_shopn_member mem,sys_bank_interestrate sbi,sys_bank sb')->where('ord.id=ordg.orderid and ibb.id=mem.storeid and ord.uid=mem.id and ord.storeid=ibb.id and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.id_interestrate=sbi.id_interestrate and sbi.id_bank=sb.id_bank '.$map )->limit($pre,$limits)->field('ord.ordersn,ord.id as orderid,ibb.title,ibb.sign,ibb.location_p,ibb.address,mem.realname,mem.mobile,ord.content,ord.price,ord.createtime,ord.payTime,sb.st_abbre_bankname bkname,sbi.no_period,ord.st_source')->order('ord.payTime desc')->group('ord.id')->select();
//                print_r($lists);
        // start Modify by wangqin 2018-03-20
        $sum_data = array('sum_price'=>0,'zb_fenqi'=>0,'avg_price'=>0,'zb_app'=>0,'zb_ny'=>0,'ny_sum_order'=>0,'zb_fenqi_price'=>0,'zb_app_price'=>0);
        // 添加相关统计及占比
        // $lists1 = Db::table('ims_bj_shopn_order ord,ims_bwk_branch ibb,ims_bj_shopn_member mem,sys_bank_interestrate sbi,sys_bank sb,ims_bj_shopn_order_goods ordg')->join(['ims_bj_shopn_goods'=>'gd'],'gd.id=ordg.goodsid','LEFT')->where('ord.id=ordg.orderid and ibb.id=mem.storeid and ord.uid=mem.id and ord.storeid=ibb.id and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.id_interestrate=sbi.id_interestrate and sbi.id_bank=sb.id_bank '.$map )->field('ord.ordersn,ord.id as orderid,ibb.title,ibb.sign,ibb.location_p,ibb.address,mem.realname,mem.mobile,ord.content,ord.price,ord.createtime,ord.payTime,sb.st_abbre_bankname bkname,sbi.no_period,ord.st_source,ord.id_interestrate,gd.pcate')->group('ord.id') ->select();
        $sum_price=0;  //总金额
        $zb_fenqi=0;  //分期占比 %
        $avg_price=0;  //平均成交总金额
        $zb_app=0;  //App支付占比
        $zb_ny=0;  //内衣总金额占比
        $ny_sum_order=0;  //内衣订单占比
        $fq = 0;$app_num=0;$ny_num=0;$ny_price=0; $j=0;$zb_fenqi_price=0;$fq_price=0;$app_price=0;$zb_app_price=0;
        // end Modify by wangqin 2018-03-20
        $sum_data = array('sum_price'=>0,'zb_fenqi'=>'0%','avg_price'=>0,'zb_app'=>'0%','zb_ny'=>'0%','ny_sum_order'=>'0%','zb_fenqi_price'=>'0%','zb_app_price'=>'0%','sum_ord_num'=>0);
        if($lists)
        {
            $i=1;
            //分页时,显示每页的id
            if($Nowpage>1)
            {
                $i = ($Nowpage-1)*$limits+1;
            }
            foreach($lists as &$v)
            {
                $v['erp_status'] = 0;
                $v['erp_status_val'] = '未导入Erp';
                $ordersn_arr[] = $v['ordersn'];
                if($flag_rule){
                    $v['mobile'] = $mobrule->replaceMobile($v['mobile']);
                }
                // 查询订单类型
                $mapact['g.pcate'] = 31;
                $mapact['og.orderid'] = $v['orderid'];
                $resact = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.ticket_type,g.pcate,g.live_flag')->where($mapact)->limit(1)->find();
                $v['order_type'] = '普通订单商品';
                if ($resact) {
                    $v['order_type'] = $resact['ticket_type']?'315活动门票商品':'315活动直播商品';
                }
                $v['payTime'] = date('Y-m-d H:i:s',$v['payTime']);
                 if ($order_type) {
                    $v['order_type'] = $this->orderTypeval[$order_type];
                }elseif($resact){
                     $v['order_type'] = $this->orderTypeValue($v['payTime'],$resact['ticket_type']);
                 }
                // 查询门店对应办事处
                $mapsys['r.id_sign'] = $v['sign'];
                $ressys = Db::table('sys_departbeauty_relation r')->join(['sys_department'=>'d'],['r.id_department=d.id_department'],'LEFT')->field('d.st_department')->where($mapsys)->limit(1)->find();
                $v['bsc'] = $ressys?$ressys['st_department']:'';
                if($v['id_bank'] == 10){
                    $v['no_period'] = $v['ums_instalment']==0?1:$v['ums_instalment'];
                }
                $v['id'] = $i;
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);

                $v['realname'] = $v['realname']==''?'':$v['realname'];
                $v['address'] = str_replace(',','|',$v['address']);
                // 根据商品数量竖线分割
                $mapp['orderid'] = $v['orderid'];
                $res_g = Db::table('ims_bj_shopn_order_goods og')->field('content,total')->where($mapp)->select();
                if($res_g){
                    $content1 = '';
                    foreach($res_g as $vg){
                        if($vg['total']>1){
                            for($n=0;$n<$vg['total'];$n++){
                                $content1 .= $vg['content'].'|';
                            }
                        }else{
                            $content1 .= $vg['content'].'|';
                        }
                    }
                    if($content1){
                        $content1 = rtrim($content1,'|');
                    }
                    $v['content'] = $content1;
                }
                $v['no_period'] = $v['no_period']==0?1:$v['no_period'];
                // $v['content'] = str_replace(',','|',$v['content']);
                $i++;
            }
            // start Modify by wangqin 2018-03-20
            foreach($lists1 as $k1=>$v1)
            {
                if($flag_rule){
                    $lists1[$k1]['mobile'] = $mobrule->replaceMobile($v1['mobile']);
                }
                
                if($v1['id_bank'] == 10){
                    $v1['no_period'] = $v1['ums_instalment']==0?1:$v1['ums_instalment'];
                    $lists1[$k1]['no_period'] = $v1['ums_instalment'];
                }
                $sum_price+=$v1['price'];
                if($v1['no_period']>1)
                {
                    $fq++;
                    $fq_price+=$v1['price'];
                }
                // app订单
                if($v1['st_source']=='android' || $v1['st_source']=='ios')
                {
                    $app_num++;
                    $app_price+=$v1['price'];
                }
                // 内衣
                if($v1['pcate']==17)
                {
                    $ny_num++;
                    $ny_price += $v1['price'];
                }
                $j++;
            }
            if($j){
                $zb_fenqi = $fq/$j;
                $avg_price = $sum_price/$j;
                $zb_app = $app_num/$j;
                $ny_sum_order = $ny_num/$j;
            }
            if($sum_price){
                $zb_fenqi_price = $fq_price/$sum_price;
                $zb_app_price = $app_price/$sum_price;
                $zb_ny = $ny_price/$sum_price;
            }
            $sum_data = array('sum_price'=>$sum_price,'zb_fenqi'=>((round($zb_fenqi,3)*100).'%'),'avg_price'=>round($avg_price),'zb_app'=>(round($zb_app,3)*100).'%','zb_ny'=>(round($zb_ny,3)*100).'%','ny_sum_order'=>(round($ny_sum_order,3)*100).'%','zb_fenqi_price'=>(round($zb_fenqi_price,3)*100).'%','zb_app_price'=>(round($zb_app_price,3)*100).'%','sum_ord_num'=>$j);

            // end Modify by wangqin 2018-03-20
        }
        // 查询erp导入结果
        if ($ordersn_arr) {
            $map_erp['ordersn'] = ['in',$ordersn_arr];
            $res_erp = Db::table('ims_bj_shopn_order_insert_erp')->where($map_erp)->select();
            if ($res_erp) {
                foreach ($res_erp as $vr) {
                    foreach ($lists as $k=>$vl) {
                        if ($vr['ordersn'] == $vl['ordersn']) {
                            $lists[$k]['erp_status'] = $vr['status'];
                            $lists[$k]['erp_status_val'] = $vr['reason'];
                        }
                    }
                }
            }
        }
        //导出xls报表
        $export = input('export',0);
        if($export){
            $data=array();
            foreach ($lists1 as $k2 => $v2) {
                // 查询订单类型
                $mapact1['g.pcate'] = 31;
                $mapact1['og.orderid'] = $v2['orderid'];
                $resact1 = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.ticket_type,g.pcate,g.live_flag')->where($mapact1)->limit(1)->find();
                $data[$k2]['order_type'] = '普通订单商品';
                if ($resact1) {
                    $data[$k2]['order_type'] = $resact1['ticket_type']?'315活动门票商品':'315活动直播商品';
                }
                $payTime = date('Y-m-d H:i:s',$v2['payTime']);

                if ($order_type) {
                    $data[$k2]['order_type'] = $this->orderTypeval[$order_type];
                }else{
                    $data[$k2]['order_type'] = $this->orderTypeValue($payTime,$resact1['ticket_type']);
                }
                // 查询门店对应办事处
                $mapsys['r.id_sign'] = $v2['sign'];
                $ressys1 = Db::table('sys_departbeauty_relation r')->join(['sys_department'=>'d'],['r.id_department=d.id_department'],'LEFT')->field('d.st_department')->where($mapsys)->limit(1)->find();
                $data[$k2]['bsc'] = $ressys1?$ressys1['st_department']:'';
                $data[$k2]['ordersn'] = "\t".$v2['ordersn'];
                $data[$k2]['title'] = $v2['title'];
                $data[$k2]['sign'] = $v2['sign'];
                $data[$k2]['location_p'] = $v2['location_p'];
                $data[$k2]['address'] = $v2['address'];
                $data[$k2]['realname'] = $v2['realname'];
                $data[$k2]['mobile'] = $v2['mobile'];
                $data[$k2]['content'] = $v2['content'];
                // 根据商品数量竖线分割
                $mapg['orderid'] = $v2['orderid'];
                $res_g = Db::table('ims_bj_shopn_order_goods og')->field('content,total')->where($mapg)->select();
                if($res_g){
                    $content = '';
                    foreach($res_g as $vg){
                        if($vg['total']>1){
                            for($i=0;$i<$vg['total'];$i++){
                                $content .= $vg['content'].'|';
                            }
                        }else{
                            $content .= $vg['content'].'|';
                        }
                    }
                    if($content){
                        $content = rtrim($content,'|');
                    }
                    $data[$k2]['content'] = $content;
                }
                $data[$k2]['price'] = $v2['price'];
                $data[$k2]['createtime'] = date('Y-m-d H:i:s',$v2['createtime']);
                $data[$k2]['payTime'] = $payTime;
                $data[$k2]['bkname'] = $v2['bkname'];
                $data[$k2]['no_period'] = $v2['no_period']==0?1:$v2['no_period'];
                $data[$k2]['st_source'] = $v2['st_source'];
            }
            $filename = "去哪美日订单".date('YmdHis');
            $header = array ('订单类型','办事处','订单编号','门店名称','门店编号','省份','地址','用户名','手机号','商品名称','价格','下单时间   ','付款时间','支付类型','分期数','订单来源');
            $widths=array('10','10','20','10','10','10','10','10','10','10','10','10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('signs', $signs);
        $this->assign('ordersn', $ordersn);
        $this->assign('sum_data', $sum_data);
        $this->assign('dt1', date('Y-m-d',$dt1));
        $this->assign('dt2', date('Y-m-d',$dt2));
        $this->assign('st_source', $st_source);
        $this->assign('content', $content);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $lists);
        $this->assign('goods_title', $goods_title);
        $this->assign('order_type', $order_type);
//        print_r($lists);

        //翻页
        if(input('get.page'))
        {
            return json($lists);
        }
        
        return $this->fetch();
    }

    // start Modify by wangqin 2017-12-19 增加出货记录查询及报表导出
    public function shipping_records(){

        $key = input('key');
        $map = ''; $map1='';
        $content = input('content')==''?'':input('content');
        $st_source = input('st_source')==''?'':input('st_source');
        $sign = input('sign')==''?'':input('sign');
        if($content!=="")
        {
            if($content == 5)
            {
                $map = " and ord.content like '%套盒%' ";
            }else
            {
                $map = " and (ord.content like '%内衣%' or ord.content like '%调整带%' or ord.content like '%文胸%')";
            }

        }
        if($st_source)
        {
            $map .= " and ord.st_source='$st_source' "  ;
        }
        if($sign)
        {
            $map .= " and ibb.sign='$sign' "  ;
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数

        $dt2 = time();$dt1 = $dt2-3600*24;
        //搜索支付时间
        if(input('dt1') && input('dt2'))
        {
            $dt1 = strtotime(input('dt1'));
            $dt2 = strtotime(input('dt2'));
        }
        $count = Db::table('ims_bj_shopn_order ord,ims_bwk_branch ibb')->where('ibb.id=ord.storeid and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.status not in (-1,0) '.$map )->field('ord.*')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
//        print_r($count);

        if(input('report') == 1)
        {
            $limits = $count;
        }

        $lists = Db::table('ims_bj_shopn_order ord,ims_bwk_branch ibb')->where('ibb.id=ord.storeid and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.status not in (-1,0) '.$map )->limit($pre,$limits)->field('ord.*')->select();
//                print_r($lists);
        if($lists)
        {
            $i=1;
            //分页时,显示每页的id
            if($Nowpage>1)
            {
                $i = ($Nowpage-1)*$limits+1;
            }
            foreach($lists as &$v)
            {
//                $v['id'] = $i;
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $v['payTime'] = date('Y-m-d H:i:s',$v['payTime']);
                $v['content'] = str_replace(',','|',$v['content']);
                $v['qunarmei_pay_parameter'] = str_replace(',','|',$v['qunarmei_pay_parameter']);
                $i++;
            }
        }

        //导出报表
//        echo 'report:'. input('get.report');
        if(input('report') == 1)
        {
            #id	weid	storeid	uid	pid	staffid	from_user	ordersn	price	content	status	sendtype	paytype	transid	goodstype	remark	addressid	expresscom	expresssn	express	goodsprice	dispatchprice	dispatch	createtime	shareid	is_qunarmei_pay	qunarmei_pay_parameter	closetime	payTime

            $headerArr = array('订单id','weid','storeid','uid','pid','staffid','from_user','ordersn','price','content','status','sendtype','paytype','transid','goodstype','remark','addressid','expresscom','expresssn','express','goodsprice','dispatchprice','dispatch','createtime','shareid','is_qunarmei_pay','qunarmei_pay_parameter','closetime','payTime','id_interestrate','st_source','order_del','order_flag','timeout');
            $name = 'shipping_records';
            foreach($lists as &$v1)
            {
                $v1['ordersn'] = str_replace('"', '""', "\t".$v1['ordersn']);
                $v1['transid'] = str_replace('"', '""', "\t".$v1['transid']);
            }
            $res = reportCsv($headerArr,$lists,$name);
            // $url = 'http://localhost:81/csv/';
            //服务器
            $url = 'http://live.qunarmei.com/csv/';
            $res = $url.$res;
            //浏览器下载
            return $res;
        }

        $this->assign('dt1', date('Y-m-d',$dt1));
        $this->assign('dt2', date('Y-m-d',$dt2));
        $this->assign('st_source', $st_source);
        $this->assign('content', $content);
        $this->assign('sign', $sign);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $lists);
//        print_r($lists);

        //翻页
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    // end Modify by wangqin 2017-12-19

    // start Modify by wangqin 2018-01-23 增加门店每日订单统计
    public function store_order_statistics(){

        $title = input('title');
        $map = '';

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数

        $dt2 = time();$dt1 = $dt2-3600*24; $lists = array();
        //搜索支付时间
        if(input('dt1') && input('dt2'))
        {
            $dt1 = strtotime(input('dt1'));
            $dt2 = strtotime(input('dt2'));
        }
        if($title)
        {
            $map = " and ibb.title rlike '$title' ";
        }
        $count = Db::table('ims_bj_shopn_order ord,ims_bwk_branch ibb')->where('ibb.id=ord.storeid and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.status not in (-1,0) '.$map )->field('ord.storeid')->group('ord.storeid')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
//        print_r($count);

        if(input('report') == 1)
        {
            $limits = $count;
        }

        $list2 = $lists1 = Db::table('ims_bj_shopn_order ord,ims_bwk_branch ibb')->where('ibb.id=ord.storeid and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.status not in (-1,0)'.$map )->order('ord.storeid asc')->group('ord.storeid')->field('ord.*,ibb.title')->select();
        if($list2)
        {
            foreach($list2 as $v2)
            {
                $i=1;
                //分页时,显示每页的id
                if($Nowpage>1)
                {
                    $i = ($Nowpage-1)*$limits+1;
                }
                $lists1 = Db::table('ims_bj_shopn_order ord,ims_bwk_branch ibb')->where('ibb.id=ord.storeid and ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.' and ord.status not in (-1,0) and ibb.id='.$v2['storeid'].$map )->order('ord.storeid asc')->field('ord.*,ibb.title')->select();
                //初始化
                $fenqi_sum_price=0; $fei_fenqi_sum_price=0;$sum_price_398=0;
                if($lists1)
                {
                    foreach($lists1 as $v)
                    {
                        if($v['id_interestrate']>0 && $v['id_interestrate']<6 )
                        {
                            //分期总价
                            $fenqi_sum_price += $v['price'];
                        }else
                        {
                            if($v['price']!=398)
                            {
                                //非分期总价
                                $fei_fenqi_sum_price  += $v['price'];
                            }else
                            {
                                //398总价
                                $sum_price_398 += $v['price'];;
                            }
                        }

                    }
                    $data_csv = array(
                        'id'=>$i,
                        'title'=>$v2 ['title'],//门店
                        'fenqi_sum_price'=>$fenqi_sum_price,//分期总价
                        'fei_fenqi_sum_price'=>$fei_fenqi_sum_price,//非分期总价
                        'sum_price_398'=>$sum_price_398,//398总价
                    );
                    $lists[] = $data_csv;
                    $i++;
                }

            }
        }

        if(input('report') == 1)
        {
            $headerArr = array('记录ID','门店名称','分期总价','非分期总价(除398)','398');
            $name = 'store_order_statistics';
            $res = reportCsv($headerArr,$lists,$name);
            $res = $this->url.$res;
            //浏览器下载
            return $res;
        }
        $this->assign('dt1', date('Y-m-d',$dt1));
        $this->assign('dt2', date('Y-m-d',$dt2));
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('title', $title);
        $this->assign('lists', $lists);
        //翻页
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    // end Modify by wangqin 2017-12-19
    //
    // start Modify by wangqin 2018-03-01 增加每月398返利订单统计
    public function fanli(){

        $title = input('title');
        $map = ''; $data=array();

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数

        $dt2 = strtotime(date('Y-m-01', time()));$dt1 = strtotime(date('Y-m-01', strtotime('-1 month'))); $lists = array();
        //搜索支付时间
        if(input('dt1') && input('dt2'))
        {
            $dt1 = strtotime(input('dt1'));
            $dt2 = strtotime(input('dt2'));
        }

        $lists = Db::query("SELECT branch.id,branch.location_p '门店省份',branch.title '门店名称',branch.sign '门店编号',ifnull(member.realname,(SELECT realname from ims_bj_shopn_member where storeid=comm.storeid and isadmin=1)) '返利用户',ifnull(member.mobile,(SELECT mobile from ims_bj_shopn_member where storeid=comm.storeid and isadmin=1)) '返利用户电话',CONCAT('\'',comm.ordersn ) '产生返利订单号',goods.title '产品名称',goods.marketprice '产品价格',comm.commission '返利金额',FROM_UNIXTIME(comm.paytime,'%Y-%m-%d %T') '订单支付时间' FROM ims_bj_shopn_commission comm LEFT JOIN ims_bwk_branch branch ON comm.storeid = branch.id LEFT JOIN ims_bj_shopn_member AS member ON comm.uid = member.id LEFT JOIN ims_bj_shopn_goods AS goods ON comm.goodsid = goods.id WHERE comm.`status` IN (1, 2, 3, 7) and comm.paytime>=$dt1 and comm.paytime<$dt2 and comm.commission>0 and branch.sign<>'666-666' ORDER BY branch.title,comm.ordersn,comm.commission");//计算总页面

        if($lists)
        {
            foreach($lists as $k=>&$v)
           {
                   $v['id'] = $k+1;
                   $v['产生返利订单号']  = ltrim($v['产生返利订单号'],"'");
                   #订单号导出文本字符串
                   $v['产生返利订单号'] = str_replace('"', '""', "\t".$v['产生返利订单号']);
                   $data[] = $v;
           }
        }
        if(input('report') == 1)
        {

            $headerArr = array('ID','门店省份','门店名称','门店编号','返利用户','返利用户电话','产生返利订单号','产品名称','产品价格','返利金额','订单支付时间');
            $name = '398fanli';
            $res = reportCsv($headerArr,$data,$name);
            $url = 'http://live.qunarmei.com/csv/';
            $res = $url.$res;
            //浏览器下载
            return $res;
        }
        $this->assign('dt1', date('Y-m-d',$dt1));
        $this->assign('dt2', date('Y-m-d',$dt2));
        $this->assign('title', $title);
        $this->assign('lists', $data);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', 1); //总页数
        //翻页
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    // end Modify by wangqin 2018-03-01
    // 订单详情数据
    public function orderdetail0525(){
        set_time_limit(0);// 超时30s执行
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        $id_begin = input('id_begin');
        $id_end = input('id_end');

        $order_type = input('order_type',0);
        $ordersn = input('ordersn');
        $dt1 = input('dt1',date("Y-m-d",strtotime("-1 day")));
        $dt2 = input('dt2',date("Y-m-d"));
        $key = input('key');
        $dt11 = $dt1==''?'':strtotime($dt1);
        $dt22 = $dt2==''?'':strtotime($dt2);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $map11 = '';
        if ($dt11) {
            $map11 = ' o.payTime>='.$dt11.' and o.payTime<='.$dt22;
        }

        // id区间导出
        if($id_begin && $id_end){
            $map11 = ' o.id>='.$id_begin.' and o.id<='.$id_end;
        }
        // var_dump(input());die;
        $limits = 50;
        $report = input('report');
        if ($report) {
            $limits = 9999;
        }
        $map = [];
        if($ordersn){
            $map['o.ordersn'] = ['like','%'.$ordersn.'%'];
        }
        if($key){
            $map['b.sign'] = $key;
        }
        // 查询订单
        // $map['o.id'] = 300144;
        $map22 = null;
        if ($order_type) {
            $map22['g.pcate'] = 31;
            $map22['g.ticket_type'] = 0;
            // 315活动门票商品
            if ($order_type == 2) {
                $map22['g.ticket_type'] = 1;
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-13'),strtotime('2020-03-19')]];
            }elseif ($order_type == 3) {
                // 315活动直播商品
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-13'),strtotime('2020-03-19')]];
            }elseif ($order_type == 4) {
                // 327活动门票商品
                $map22['g.ticket_type'] = 1;
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-25'),strtotime('2020-03-28')]];
            }elseif ($order_type == 5) {
                // 327活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-25'),strtotime('2020-03-28')]];
            }elseif ($order_type == 6) {
                // 329活动门票商品
                $map22['g.ticket_type'] = 1;
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-28'),strtotime('2020-03-31')]];
            }elseif ($order_type == 7) {
                // 329活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-28'),strtotime('2020-03-31')]];
            }elseif ($order_type == 9) {
                // 412活动门票商品
                // 支付日期
                $map22['g.ticket_type'] = 2;
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-10'),strtotime('2020-04-15')]];
            }elseif ($order_type == 10) {
                // 412活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-10'),strtotime('2020-04-15')]];
            }elseif ($order_type == 11) {
                // 412之后活动门票商品
                // 支付日期
                $map22['g.ticket_type'] = 2;
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-15'),strtotime('2020-12-31')]];
            }elseif ($order_type == 12) {
                // 412之后活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-15'),strtotime('2020-12-31')]];
            }elseif ($order_type == 1) {
                // 普通订单商品
                $map22['g.pcate'] = ['<',31];
                unset($map22['g.ticket_type']);
            }
        }
        $count = Db::table('ims_bj_shopn_order o')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')->join(['ims_bj_shopn_order_goods'=>'og'],'og.orderid=o.id','LEFT')->join(['ims_bj_shopn_goods'=>'g'],'og.goodsid=g.id','LEFT')->field('o.*,b.title,b.sign,m.realname,m.mobile')->where($map)->where($map11)->where($map22)->group('o.id')->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $res = Db::table('ims_bj_shopn_order o')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')->join(['ims_bj_shopn_order_goods'=>'og'],'og.orderid=o.id','LEFT')->join(['ims_bj_shopn_goods'=>'g'],'og.goodsid=g.id','LEFT')->field('o.*,b.receive_address,b.receive_consignee,b.receive_mobile,b.title,b.sign,m.realname,m.mobile')->limit($pre,$limits)->where($map)->where($map11)->where($map22)->group('o.id')->select();
        if ($res) {
            $orderids = [];
            $zi_ids = [];
            $id_interestrates = []; 
            $storeids = [];
            foreach ($res as $k => $v) {
                if($flag_rule){
                    $res[$k]['mobile'] = $mobrule->replaceMobile($res[$k]['mobile']);
                }
                $storeids[] = $v['storeid'];
                $id_interestrates[] = $v['id_interestrate'];
                $orderids[] = $v['id'];
                $res[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $res[$k]['payTime'] = $v['payTime']==''?'':date('Y-m-d H:i:s',$v['payTime']);
                $res[$k]['store_name'] = $v['title'];
                $res[$k]['store_sign'] = $v['sign'];
                $res[$k]['bsc'] = '';
                $res[$k]['bank'] = '';
                $res[$k]['fenqi'] = '';
                $res[$k]['buy_goods'] = '';
                // 包含子商品
                $res[$k]['buy_goods_zi'] = '';
                $res[$k]['isaxs'] = $v['addressid']>0?'是':'否';// 有安心送地址
                $res[$k]['sh_name'] = $v['receive_consignee']==null?'':$v['receive_consignee'];
                $res[$k]['sh_mobile'] = $v['receive_mobile']==null?'':$v['receive_mobile'];
                $res[$k]['sh_address'] = $v['receive_address']==null?'':$v['receive_address'];
                $res[$k]['express_number'] = '';
//                $res[$k]['mobile'] = substr_replace($res[$k]['mobile'], '****', 3, 4);
                //查询订单-用户-门店信息
            $mapog['og.orderid'] = ['in',$v['id']];
            $resg = Db::table('ims_bj_shopn_order_goods og')->field('idGoodsExtend,og.content title,og.goodsid id,og.total,og.goodsid,og.content')->where($mapog)->select();
            if ($resg) {
                $k1 = 1;
                $zi_ids = [];
                $res[$k]['buy_goods'] = '';
                $k2 = 1;
                foreach ($resg as $ksg => $vsg) {
                    $title = $vsg['content']==null?$vsg['title']:$vsg['content'];
                    $vsg['title'] = $title;
                    $res[$k]['buy_goods'] .= $k1.'.'.$vsg['title'].';';
                    // 查询商品属性
                    if ($vsg['idGoodsExtend']) {
                        $gdpro = $this->getGoodsPro($vsg['idGoodsExtend']);
                        $res[$k]['buy_goods'] .= $gdpro;
                        $res[$k]['buy_goods_zi'] .=  $k2.'.'.$vsg['title'].';'.$gdpro.'数量:'.$vsg['total'].';<br/>';
                        $k2++;
                    }
                    $zi_ids[] = $vsg['goodsid'];
                    $res[$k]['buy_goods'] .= '数量:'.$vsg['total'].';<br/>';
                    $k1++;
                }
                // 包含子商品
                $mapzi['pid'] = ['in',$zi_ids];
                $reszi = Db::table('ims_bj_shopn_goods_zi zi')->where($mapzi)->order('type asc')->order('pid desc')->select();
                if ($reszi) {
                    // $k2 = 1;
                    foreach ($reszi as $kz => $vz) {
                        foreach ($resg as $ksg => $vsg) {
                            if ($vz['pid'] == $vsg['id']) {
                                $res[$k]['buy_goods_zi'] .= $k2.'.';
                                if($vz['type'] == 2){
                                    $res[$k]['buy_goods_zi'] .= '赠品:';
                                }else{
                                    $res[$k]['buy_goods_zi'] .= '子商品:';
                                }
                                $res[$k]['buy_goods_zi'] .= $vz['title'].'-规格:'.$vz['spec'].'-数量:'.$vz['num']*$vsg['total'].'-编码:'.$vz['code'].';<br/>';
                                $k2++;
                            }
                        }
                        // $res[$k]['buy_goods_zi'] .= $vz['title'].'-规格:'.$vz['spec'].'-数量:'.$vz['num']*$vsg['total'].'-编码:'.$vz['code'].';<br/>';
                        // $k2++;
                    }
                }
            }
            }

            // echo'<pre>';print_r($res);die;
            //查询商品属性-数量-编码,包含子商品-数量-编码
            
            // 查询支付银行和分期
            $mapi['i.id_interestrate'] = ['in',$id_interestrates];
            $resi = Db::table('sys_bank s')->join(['sys_bank_interestrate'=>'i'],'s.id_bank=i.id_bank','LEFT')->field('s.*,i.no_period,i.id_interestrate')->where($mapi)->select();
            if ($resi) {
                foreach ($resi as $ki => $vi) {
                    foreach ($res as $k => $v){
                        if ($v['id_interestrate'] == $vi['id_interestrate']) {
                            $res[$k]['bank'] = $vi['st_abbre_bankname'];
                            $res[$k]['fenqi'] = $vi['no_period'];
                            if ($vi['id_bank'] == 10) {
                                $res[$k]['fenqi'] = $v['ums_instalment'];
                            }
                        }
                    }
                }
            }
            // 查询所属市场
            $mapst['r.id_beauty'] = ['in',$storeids];
            $resst = Db::table('sys_departbeauty_relation r')->join(['sys_department'=>'s'],'s.id_department=r.id_department','LEFT')->where($mapst)->select();
            if ($resst) {
                foreach ($resst as $kst => $vst) {
                    foreach ($res as $k => $v){
                        if ($vst['id_beauty'] == $v['storeid']) {
                            $res[$k]['bsc'] = $vst['st_department'];
                        }
                    }
                }
            }
            // 查询收货人信息及物流信息
            $mapa['a.order_id'] = ['in',$orderids];
            $resa = Db::table('ims_bj_shopn_order_address a')->where($mapa)->select();
            if ($resa) {
                foreach ($resa as $ka => $va) {
                    foreach ($res as $k => $v){
                        if ($va['order_id'] == $v['id']) {
                            $res[$k]['sh_name'] = $va['consignee'];
                            $res[$k]['sh_mobile'] = $va['mobile'];
                            $res[$k]['sh_address'] = $va['country'].$va['province'].$va['city'].$va['city'].$v['district'].$v['street'].$v['address'];
                            $res[$k]['express_number'] = $va['express_number'];
                        }
                    }
                }
            }
        }

        

        // 根据订单商品拆分
        // 购买商品及属性编码 
        $gd_arr1 = [];

        if ($res) {
            foreach ($res as $k1 => $v1) {
                // 根据套盒商品拆分成单品订单
                $mapc['og.orderid'] = $v1['id'];
//                $resog = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.title,g.id,og.total,g.marketprice,og.idGoodsExtend,og.price,og.sale_price')->where($mapc)->select();
                $resog = Db::table('ims_bj_shopn_order_goods og')->field('og.content title,og.goodsid id,og.total,og.idGoodsExtend,og.price,og.sale_price')->where($mapc)->select();
                $price1 = '';
                if ($resog) {
                    foreach ($resog as $kog => $vog) {
                        // erp售出价格与实际价格不一致时
                        $res[$k1]['dp_sale_price'] = 0;
                        if($vog['sale_price'] >= 0.01){
                            $vog['price'] = $vog['sale_price'];
                            $res[$k1]['dp_sale_price'] = $vog['sale_price'];
                        }

                        $gd = $vog['title'];
                        if ($vog['idGoodsExtend'])  {
                            $gd_pros = $this->getNyCode($vog['idGoodsExtend']);
                            $res[$k1]['goods'] = $gd;
                            $res[$k1]['spec'] = $gd_pros['pros'];
                            $res[$k1]['goods_num'] = $vog['total'];
                            // $res[$k1]['price'] = $vog['marketprice'] * $res[$k1]['goods_num'] ;
                            $res[$k1]['goods_code'] = $gd_pros['code']; 
                            if($price1 == ''){
                                $price1 = $v1['price'];
                            }else{
                                $price1 = '/';
                            }
                            $res[$k1]['price_g'] = $price1;
                            $res[$k1]['price_h'] = $vog['price']*$vog['total'];
                            $gd_arr1[] = $res[$k1];
                            
                        }else{
                            // 查询是否有子商品
                            $mapzi['pid'] = $vog['id'];
                            $reszi = Db::table('ims_bj_shopn_goods_zi zi')->where($mapzi)->order('type asc')->select();
                            if ($reszi) {
                                foreach ($reszi as $kzi => $vzi) {
                                    $gd = $vzi['title'];
                                    $res[$k1]['goods'] = $gd;
                                    if ($vzi['type'] == 2) {
                                        $gd = '买赠:'.$gd;
                                    }
                                    $res[$k1]['goods'] = $gd;
                                    $res[$k1]['spec'] = $vzi['spec'];
                                    $res[$k1]['goods_num'] = $vog['total'] * $vzi['num'];
                                    // $res[$k1]['price'] = $vzi['price'] * $res[$k1]['goods_num'] ;
                                    $res[$k1]['goods_code'] = $vzi['code'];
                                    if($price1 == ''){
                                        $price1 = $v1['price'];
                                    }else{
                                        $price1 = '/';
                                    }
                                    $res[$k1]['price_g'] = $price1;
                                    $res[$k1]['price_h'] = $vog['price']*$vog['total'];
                                    $gd_arr1[] = $res[$k1];
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($res) {
            foreach ($res as $k => $v) {
                // 查询订单类型
                $mapact1['g.pcate'] = 31;
                $mapact1['og.orderid'] = $v['id'];
                $resact1 = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.ticket_type,g.pcate,g.live_flag')->where($mapact1)->limit(1)->find();
                $res[$k]['order_type'] = '普通订单商品';
                if ($resact1) {
                    $res[$k]['order_type'] = $resact1['ticket_type']?'315活动门票商品':'315活动直播商品';
                }
                if ($order_type) {
                    $res[$k]['order_type'] = $this->orderTypeval[$order_type];
                }elseif($resact1){
                    // order_type默认为空时,根据支付时间区分活动
                    $res[$k]['order_type'] = $this->orderTypeValue($v['payTime'],$resact1['ticket_type']);
                }
            }
        }
        // echo "gd_arr1:<pre>";print_r($gd_arr1);die;
        //翻页
        if(input('get.page')){
            return json($res);
        }

        //导出xls报表
        $type = input('type');
        $export = input('report',0);
        if($export){
            $data = [];
            foreach ($gd_arr1 as $k => $v) {
                $data1['orderid'] = 'APP' . $v['id'];
                // 查询订单类型
                $mapact2['g.pcate'] = 31;
                $mapact2['og.orderid'] = $v['id'];
                $resact2 = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods' => 'g'], ['og.goodsid=g.id'], 'LEFT')->field('g.ticket_type,g.pcate,g.live_flag')->where($mapact2)->limit(1)->find();
                $data1['order_type'] = '普通订单商品';
                if ($resact2) {
                    $data1['order_type'] = $resact2['ticket_type'] ? '315活动门票商品' : '315活动直播商品';
                }
                if ($order_type) {
                    $data1['order_type'] = $this->orderTypeval[$order_type];
                } elseif($resact2) {
                    // order_type默认为空时,根据支付时间区分活动
                    $data1['order_type'] = $this->orderTypeValue($v['payTime'], $resact2['ticket_type']);
                }
                $data1['bsc'] = $v['bsc'];
                $data1['store_name'] = $v['store_name'];
                $data1['store_sign'] = $v['store_sign'];
                $data1['ordersn'] = "\t" . $v['ordersn'];
                $data1['realname'] = $v['realname'];
                $data1['mobile'] = $v['mobile'];
                $data1['price'] = $v['price_h'];
                $data1['discount_price'] = $data1['price'] * 0.38;
                $data1['dp_sale_price'] = $v['dp_sale_price'];
                // 销售导出订单,手机号中间4位替换*
                if ($export == 1) {
                    $data1['mobile'] = substr_replace($data1['mobile'], '****', 3, 4);
                }
                $data1['goods'] = $v['goods'];
                $data1['spec'] = $v['spec'];
                $data1['goods_num'] = $v['goods_num'];
                $data1['goods_code'] = $v['goods_code'];
                $data1['createtime'] = $v['createtime'];
                $data1['payTime'] = $v['payTime'];
                $data1['bank'] = $v['bank'];
                $data1['fenqi'] = $v['fenqi'] == 0 ? 1 : $v['fenqi'];
                $data1['st_source'] = $v['st_source'];
                if ($type == 2) {
                    $data1['buy_goods'] = '"' . str_replace(array(',', '&nbsp;', '<br>', '<br/>', '<br />'), array('，', ' ', PHP_EOL, PHP_EOL, PHP_EOL), $data1['buy_goods']) . '"';
                    $data1['buy_goods_zi'] = '"' . str_replace(array(',', '&nbsp;', '<br>', '<br/>', '<br />'), array('，', ' ', PHP_EOL, PHP_EOL, PHP_EOL), $data1['buy_goods_zi']) . '"';
                }
                // 增加顾客订单备注和自定义备注导出
                $data1['remark'] = '';
                $data1['own_remark'] = '';
                if ($v['price_g'] != '/') {
                    $data1['remark'] = $v['remark'];
                    $data1['own_remark'] = $v['own_remark'];
                }

                // 收货人信息
                $data1['isaxs'] = $v['isaxs'];
                $data1['sh_name'] = $v['sh_name'];
                $data1['sh_mobile'] = $v['sh_mobile'];
                $data1['sh_address'] = $v['sh_address'];
                $data1['express_number'] = $v['express_number'];
                // 获取订单直播间
                $data1['live_name'] = '';
                $map_order['id'] = $v['id'];
                $res_order = Db::table('ims_bj_shopn_order')->field('id,chat_id,chat_type')->where($map_order)->limit(1)->find();
                // 根据类型查询直播间名称
                if ($res_order && $res_order['chat_id']) {
                    // 小程序直播
                    if ($res_order['chat_type'] == 1) {
                        $map_live1['roomid'] = $res_order['chat_id'];
                        $res_live = Db::table('think_wechat_live')->field('id,name')->where($map_live1)->limit(1)->find();
                        $data1['live_name'] = $res_live['name'];
                    } else {
                        // app直播
                        $map_live['chat_id'] = $res_order['chat_id'];
                        $res_live = Db::table('think_live')->field('id,title')->where($map_live)->limit(1)->find();
                        $data1['live_name'] = $res_live['title'];
                    }
                }
                // 重置数组索引为数字
//                $data1 = array_values($data1);

                // 获取订单直播间
                $data1['live_name'] = '';
                $map_order['id'] = $v['id'];
                $res_order = Db::table('ims_bj_shopn_order')->field('id,chat_id,chat_type')->where($map_order)->limit(1)->find();
                // 根据类型查询直播间名称
                if ($res_order && $res_order['chat_id']) {
                    // 小程序直播
                    if ($res_order['chat_type'] == 1) {
                        $map_live['roomid'] = $res_order['chat_id'];
                        $res_live = Db::table('think_wechat_live')->field('id,name')->where($map_live)->limit(1)->find();
                        $data1['live_name'] = $res_live['name'];
                    } else {
                        // app直播
                        $map_live['chat_id'] = $res_order['chat_id'];
                        $res_live = Db::table('think_live')->field('id,title')->where($map_live)->limit(1)->find();
                        $data1['live_name'] = $res_live['title'];
                    }
                }
                $data[] = $data1;
            }
            $filename = "去哪美日订单".date('YmdHis');
            $header = array ('订单ID','订单类型','市场','门店名称','门店编号','订单编号','用户名','用户电话','订单金额','折扣后价格','商品实际售出单价','购买商品','商品规格','商品数量','商品编码','下单时间','支付时间','付款银行','分期数','订单来源','顾客备注','客服开单自定义备注','是否安心送','收货人名称','收货人号码','收货人地址','物流单号','直播间名称');
            $widths=array('10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10');
            // var_dump($data);die;
            if($data) {
                if ($type == 2) {
                    $filename = "app_order".date('YmdHis');
                    $rescsv = reportCsv($header, $data, $filename);//生成数据
                    $url = 'http://live.qunarmei.com/csv/';
                    $rescsv = $url.$rescsv;
                    return $rescsv;
                }else{
                    excelExport($filename, $header, $data, $widths);//生成数据
//                    csv_export($data,$header,$filename);die;
                }
            }
            die();
        }
        // var_dump($res);die;
        $this->assign('ordersn', $ordersn);
        $this->assign('id_begin', $id_begin);
        $this->assign('id_end', $id_end);
        $this->assign('dt1', $dt1);
        $this->assign('dt2', $dt2);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $res);
        $this->assign('order_type', $order_type);
        return $this->fetch();
    }
    /**
     * 查询选择的商品属性
     */
    public function getGoodsPro($pro)
    {
        // ["192x1","240x1","215x1"]
        $pro1 = json_decode($pro,true);
        // var_dump($pro1);die;
        $proids = [];
        $pros = '';
        foreach ($pro1 as $k => $v) {
            $pro2 = explode('x',$v);
            $proids[] = $pro2[0];
        }
        // 查询对应商品和对应属性
        // var_dump($proids);die;
        $map['id'] = ['in',$proids];
        $res = Db::table('ims_bj_shopn_goods_extend')->where($map)->select();
        if ($res) {
            foreach ($res as $k => $v) {
                $pros .= '-'.$v['color'].'-'.$v['size'].';编码:'.$v['inventory_code'].';';
            }
        }
        // var_dump($pros);die;
        return $pros;
    }
    /**
     * 获取内衣产品 ims_bj_shopn_goods_extend 产品的产品编码
     * @param  [type] $pro [description]
     * @return [type]      [description]
     */
    public function getNyCode($pro)
    {
        $pros = [];
        $pro1 = json_decode($pro,true);
        foreach ($pro1 as $k => $v) {
            $pro2 = explode('x',$v);
            $map['id'] = $pro2[0];
            $res = Db::table('ims_bj_shopn_goods_extend')->where($map)->limit(1)->find();
            if ($res) {
                $pros['code'] = $res['inventory_code'];
                $pros['pros'] = $res['name'].'-'.$res['color'].'-'.$res['size'];
            }
        }
        return $pros;
    }
    /**
     * 订单修改
     * @param  [int] $id [订单id]
     * @return [json]
     */
    public function ordedit($id)
    {
        // 查询用户订单号
        $map['id'] = $id;
        $res = Db::table('ims_bj_shopn_order')->where($map)->limit(1)->find();
        // 提交修改保存信息
        if(request()->isPost()){
            $param = input('post.');
            $data['own_remark'] = $param['own_remark'];
            $mapu['id'] = $param['id'];
            Db::table('ims_bj_shopn_order')->where($mapu)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }
        $this->assign('list',$res);
        return $this->fetch();
    }

    /**
     * 根据支付时间判断订单类型
     * $payTime:支付日期;$ticket_type:门票类型,0:直播商品,1:门票
     */
    public function orderTypeValue($payTime,$ticket_type)
    {
        $order_type1 = 1;
        if($payTime >='2020-03-13' && $payTime <='2020-03-19') {
            if($ticket_type){
                $order_type1 = 2;
            }else{
                $order_type1 = 3;
            }
        }elseif($payTime >='2020-03-25' && $payTime <='2020-03-28'){
            if($ticket_type){
                $order_type1 = 4;
            }else{
                $order_type1 = 5;
            }
        }elseif($payTime >='2020-03-28' && $payTime <='2020-03-31'){
            if($ticket_type){
                $order_type1 = 6;
            }else{
                $order_type1 = 7;
            }
        }elseif($payTime >='2020-04-10' && $payTime <='2020-04-15'){
            if($ticket_type){
                $order_type1 = 9;
            }else{
                $order_type1 = 10;
            }
        }elseif($payTime >='2020-04-15'){
            if($ticket_type){
                $order_type1 = 11;
            }else{
                $order_type1 = 12;
            }
        }
        $res = $this->orderTypeval[$order_type1];
        return $res;
    }
    // 订单详情数据
    public function orderdetail(){
        set_time_limit(0);// 超时30s执行
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        $id_begin = input('id_begin');
        $id_end = input('id_end');

        $order_type = input('order_type',0);
        $ordersn = input('ordersn');
        $dt1 = input('dt1',date("Y-m-d",strtotime("-1 day")));
        $dt2 = input('dt2',date("Y-m-d"));
        $key = input('key');
        $dt11 = $dt1==''?'':strtotime($dt1);
        $dt22 = $dt2==''?'':strtotime($dt2);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $map11 = '';
        if ($dt11) {
            $map11 = ' o.payTime>='.$dt11.' and o.payTime<='.$dt22;
        }

        // id区间导出
        if($id_begin && $id_end){
            $map11 = ' o.id>='.$id_begin.' and o.id<='.$id_end;
        }
        // var_dump(input());die;
        $limits = 50;
        $report = input('report');
        if ($report) {
            $limits = 9999;
        }
        $map = [];
        if($ordersn){
            $map['o.ordersn'] = ['like','%'.$ordersn.'%'];
        }
        if($key){
            $map['b.sign'] = $key;
        }
        // 查询订单
        // $map['o.id'] = 300144;
        $map22 = null;
        if ($order_type) {
            $map22['g.pcate'] = 31;
            $map22['g.ticket_type'] = 0;
            // 315活动门票商品
            if ($order_type == 2) {
                $map22['g.ticket_type'] = 1;
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-13'),strtotime('2020-03-19')]];
            }elseif ($order_type == 3) {
                // 315活动直播商品
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-13'),strtotime('2020-03-19')]];
            }elseif ($order_type == 4) {
                // 327活动门票商品
                $map22['g.ticket_type'] = 1;
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-25'),strtotime('2020-03-28')]];
            }elseif ($order_type == 5) {
                // 327活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-25'),strtotime('2020-03-28')]];
            }elseif ($order_type == 6) {
                // 329活动门票商品
                $map22['g.ticket_type'] = 1;
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-28'),strtotime('2020-03-31')]];
            }elseif ($order_type == 7) {
                // 329活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-03-28'),strtotime('2020-03-31')]];
            }elseif ($order_type == 9) {
                // 412活动门票商品
                // 支付日期
                $map22['g.ticket_type'] = 2;
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-10'),strtotime('2020-04-15')]];
            }elseif ($order_type == 10) {
                // 412活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-10'),strtotime('2020-04-15')]];
            }elseif ($order_type == 11) {
                // 412之后活动门票商品
                // 支付日期
                $map22['g.ticket_type'] = 2;
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-15'),strtotime('2020-12-31')]];
            }elseif ($order_type == 12) {
                // 412之后活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-15'),strtotime('2020-12-31')]];
            }elseif ($order_type == 1) {
                // 普通订单商品
                $map22['g.pcate'] = ['<',31];
                unset($map22['g.ticket_type']);
            }
        }
        $count = Db::table('ims_bj_shopn_order o')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')->join(['ims_bj_shopn_order_goods'=>'og'],'og.orderid=o.id','LEFT')->join(['ims_bj_shopn_goods'=>'g'],'og.goodsid=g.id','LEFT')->field('o.*,b.title,b.sign,m.realname,m.mobile')->where($map)->where($map11)->where($map22)->group('o.id')->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $res = Db::table('ims_bj_shopn_order o')
        ->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')
        ->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')
        ->join(['ims_bj_shopn_order_goods'=>'og'],'og.orderid=o.id','LEFT')
        ->join(['ims_bj_shopn_goods'=>'g'],'og.goodsid=g.id','LEFT')->field('o.*,b.receive_address,b.receive_consignee,b.receive_mobile,b.title,b.sign,m.realname,m.mobile')->limit($pre,$limits)->where($map)->where($map11)->where($map22)->group('o.id')->select();
        if ($res) {
            $orderids = [];
            $zi_ids = [];
            $id_interestrates = []; 
            $storeids = [];
            foreach ($res as $k => $v) {
                if($flag_rule){
                    $res[$k]['mobile'] = $mobrule->replaceMobile($res[$k]['mobile']);
                }
                $storeids[] = $v['storeid'];
                $id_interestrates[] = $v['id_interestrate'];
                $orderids[] = $v['id'];
                $res[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $res[$k]['payTime'] = $v['payTime']==''?'':date('Y-m-d H:i:s',$v['payTime']);
                $res[$k]['store_name'] = $v['title'];
                $res[$k]['store_sign'] = $v['sign'];
                $res[$k]['bsc'] = '';
                $res[$k]['bank'] = '';
                $res[$k]['fenqi'] = '';
                $res[$k]['buy_goods'] = '';
                // 包含子商品
                $res[$k]['buy_goods_zi'] = '';
                $res[$k]['isaxs'] = $v['addressid']>0?'是':'否';// 有安心送地址
                $res[$k]['sh_name'] = $v['receive_consignee']==null?'':$v['receive_consignee'];
                $res[$k]['sh_mobile'] = $v['receive_mobile']==null?'':$v['receive_mobile'];
                $res[$k]['sh_address'] = $v['receive_address']==null?'':$v['receive_address'];
                $res[$k]['express_number'] = '';
            }
            // 查询支付银行和分期
            $mapi['i.id_interestrate'] = ['in',$id_interestrates];
            $resi = Db::table('sys_bank s')->join(['sys_bank_interestrate'=>'i'],'s.id_bank=i.id_bank','LEFT')->field('s.*,i.no_period,i.id_interestrate')->where($mapi)->select();
            if ($resi) {
                foreach ($resi as $ki => $vi) {
                    foreach ($res as $k => $v){
                        if ($v['id_interestrate'] == $vi['id_interestrate']) {
                            $res[$k]['bank'] = $vi['st_abbre_bankname'];
                            $res[$k]['fenqi'] = $vi['no_period'];
                            if ($vi['id_bank'] == 10) {
                                $res[$k]['fenqi'] = $v['ums_instalment'];
                            }
                        }
                    }
                }
            }
            // 查询所属市场
            $mapst['r.id_beauty'] = ['in',$storeids];
            $resst = Db::table('sys_departbeauty_relation r')->join(['sys_department'=>'s'],'s.id_department=r.id_department','LEFT')->where($mapst)->select();
            if ($resst) {
                foreach ($resst as $kst => $vst) {
                    foreach ($res as $k => $v){
                        if ($vst['id_beauty'] == $v['storeid']) {
                            $res[$k]['bsc'] = $vst['st_department'];
                        }
                    }
                }
            }
            // 查询收货人信息及物流信息
            $mapa['a.order_id'] = ['in',$orderids];
            $resa = Db::table('ims_bj_shopn_order_address a')->where($mapa)->select();
            if ($resa) {
                foreach ($resa as $ka => $va) {
                    foreach ($res as $k => $v){
                        if ($va['order_id'] == $v['id']) {
                            $res[$k]['sh_name'] = $va['consignee'];
                            $res[$k]['sh_mobile'] = $va['mobile'];
                            $res[$k]['sh_address'] = $va['country'].$va['province'].$va['city'].$va['city'].$v['district'].$v['street'].$v['address'];
                            $res[$k]['express_number'] = $va['express_number'];
                        }
                    }
                }
            }
        }

        
        if ($res) {
            foreach ($res as $k => $v) {
                // 查询订单类型
                $mapact1['g.pcate'] = 31;
                $mapact1['og.orderid'] = $v['id'];
                $resact1 = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.ticket_type,g.pcate,g.live_flag,g.sub_account')->where($mapact1)->limit(1)->find();
                $res[$k]['order_type'] = '普通订单商品';
                if ($resact1) {
                    $res[$k]['order_type'] = $resact1['ticket_type']?'315活动门票商品':'315活动直播商品';
                }
                if ($order_type) {
                    $res[$k]['order_type'] = $this->orderTypeval[$order_type];
                }elseif($resact1){
                    // order_type默认为空时,根据支付时间区分活动
                    $res[$k]['order_type'] = $this->orderTypeValue($v['payTime'],$resact1['ticket_type']);
                }
                // 消费券订单
                if ($v['ticket_id']) {
                  $res[$k]['order_type'] = '消费券商品';
                }
                $res[$k]['sub_account'] = $resact1['sub_account'];
            }
        }
        // 根据订单商品拆分
        // 购买商品及属性编码 
        $gd_arr1 = [];

        if ($res) {
            foreach ($res as $k1 => $v1) {
                // 根据套盒商品拆分成单品订单
                $mapc['og.orderid'] = $v1['id'];
                $resog = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('og.content title,og.goodsid id,og.total,og.idGoodsExtend,og.price,og.sale_price,og.is_gift,g.sub_account,g.goods_num')->where($mapc)->select();
                $price1 = '';
                if ($resog) {
                    foreach ($resog as $kog => $vog) {
                        $res[$k1]['sub_account'] = $vog['sub_account'];
                        $res[$k1]['buy_goods'] = $vog['title'];
                        $res[$k1]['buy_goods_zi'] = $vog['title'];
                        // erp售出价格与实际价格不一致时
                        $res[$k1]['dp_sale_price'] = 0;
                        if($vog['sale_price'] >= 0.01){
                            $vog['price'] = $vog['sale_price'];
                            $res[$k1]['dp_sale_price'] = $vog['sale_price'];
                        }

                        $gd = $vog['title'];
                        $res[$k1]['price_h'] = $vog['price']*$vog['total'];
                        if ($vog['goods_num'] > 1) {
                            $res[$k1]['dp_sale_price'] = round(($vog['price'] / $vog['goods_num']),4);
                        }
                        // 赠品
                        if($vog['is_gift']){
                            $res[$k1]['price_h'] = 0;
                        }
                        if ($vog['idGoodsExtend'])  {
                            $gd_pros = $this->getNyCode($vog['idGoodsExtend']);
                            $res[$k1]['goods'] = $gd;
                            $res[$k1]['spec'] = $gd_pros['pros'];
                            $res[$k1]['goods_num'] = $vog['total'];
                            // $res[$k1]['price'] = $vog['marketprice'] * $res[$k1]['goods_num'] ;
                            $res[$k1]['goods_code'] = $gd_pros['code']; 
                            if($price1 == ''){
                                $price1 = $v1['price'];
                            }else{
                                $price1 = '/';
                            }
                            $res[$k1]['price_g'] = $price1;
                            $gd_arr1[] = $res[$k1];
                            
                        }else{
                            // 查询是否有子商品
                            $mapzi['pid'] = $vog['id'];
                            $reszi = Db::table('ims_bj_shopn_goods_zi zi')->where($mapzi)->order('type asc')->select();
                            if ($reszi) {
                                foreach ($reszi as $kzi => $vzi) {
                                    $gd = $vzi['title'];
                                    
                                    if ($vzi['type'] == 2) {
                                        $vzi['price'] = 0;
                                        $res[$k1]['buy_goods_zi'] = '买赠:'.$res[$k1]['buy_goods_zi'];
                                    }
                                    // 非赠品才查子商品
                                    if($vog['is_gift'] == 0){
                                        $res[$k1]['goods'] = $gd;
                                        $res[$k1]['buy_goods_zi'] = $gd;
                                    }
                                    $res[$k1]['spec'] = $vzi['spec'];
                                    $res[$k1]['goods_num'] = $vog['total'] * $vzi['num'];
                                    // $res[$k1]['price'] = $vzi['price'] * $res[$k1]['goods_num'] ;
                                    $res[$k1]['goods_code'] = $vzi['code'];
                                    if($price1 == ''){
                                        $price1 = $v1['price'];
                                    }else{
                                        $price1 = '/';
                                    }
                                    $res[$k1]['price_g'] = $price1;
                                    if ($vzi['pid'] != 1978523) {
                                      $res[$k1]['price_h'] = $vzi['price'] * $res[$k1]['goods_num'];
                                    }
                                    // 赠品
                                    if($vog['is_gift']){
                                        $res[$k1]['price_h'] = 0;
                                    }
                                    $gd_arr1[] = $res[$k1];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $res = $gd_arr1;
        // echo 'gd_arr1';dump($gd_arr1);die;
        // echo "gd_arr1:<pre>";print_r($gd_arr1);die;
        //导出xls报表
        $type = input('type');
        $export = input('report',0);
        if($export){
            $header = array('订单ID','订单类型','市场','门店名称','门店编号','订单编号','用户名','用户电话','订单金额','折扣后价格','商品实际售出单价','购买商品','商品规格','商品数量','商品编码','下单时间','支付时间','付款银行','分期数','订单来源','顾客备注','客服开单自定义备注','是否安心送','收货人名称','收货人号码','收货人地址','物流单号','子账户');
            $data1[0] = $header;
            foreach ($res as $v) {
                $data = [];
                $data[] = 'APP' . $v['id'];
                $data[] = $v['order_type'];
                $data[] = $v['bsc'];
                $data[] = $v['store_name'];
                $data[] = $v['store_sign'];
                $data[] = "\t" . $v['ordersn'];
                $data[] = $v['realname']; 
                $data[] = $v['mobile'];
                $data[] = $v['price_h'];
                $data[] = $v['price'] * 0.38;
                $data[] = $v['dp_sale_price'];
                $data[] = $v['buy_goods_zi'];
                $data[] = $v['spec'];
                $data[] = $v['goods_num'];
                $data[] = $v['goods_code'];
                $data[] = $v['createtime'];
                $data[] = $v['payTime'];
                $data[] = $v['bank'];
                $data[] = $v['fenqi'] == 0 ? 1 : $v['fenqi'];
                $data[] = $v['st_source'];
                $data[] = $v['remark'];
                $data[] = $v['own_remark'];
                $data[] = $v['isaxs'];
                $data[] = $v['sh_name'];
                $data[] = $v['sh_mobile'];
                $data[] = $v['sh_address'];
                $data[] = $v['express_number'];
                $data[] = $v['sub_account'];
                $data1[] = $data;
            }
            $datav['data'] = $data1;// 具体数据
            $datav['msg'] = 'app日订单详情'.date('YmdHis');// 数据表名称
            echo json_encode($datav,JSON_UNESCAPED_UNICODE);die;
        }
        //翻页
        if(input('get.page')){
            return json($res);
        }
        // var_dump($res);die;
        $this->assign('ordersn', $ordersn);
        $this->assign('id_begin', $id_begin);
        $this->assign('id_end', $id_end);
        $this->assign('dt1', $dt1);
        $this->assign('dt2', $dt2);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $res);
        $this->assign('order_type', $order_type);
        return $this->fetch();
    }
    // 重新导入订单进入erp
    public function import_erp()
    {
        $ordersn = input('ordersn');
        $arr['code'] = 0;
        $arr['msg'] = '订单导入Erp失败';
        try{
            $map['ordersn'] = $ordersn;
            $res = Db::table('ims_bj_shopn_order_insert_erp')->where($map)->limit(1)->find();
            if ($res) {
                $url = config('app_url_java').'qunamei/erporder';
                $data['id'] = $res['id'];
                $arr['data'] = curl_post_https($url,$data);
                $arr['code'] = 1;
                $arr['msg'] = '手动导入中,稍后查询导入结果';
            }
        }catch(Exception $e){
            $arr['msg'] = '审核失败-'.$e->getMessage();
        }
        return json($arr);
    }
}