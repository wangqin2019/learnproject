<?php

namespace app\admin\controller;
use think\Db;

/* 安心送订单详情
 * */
class Order extends Base
{
    //服务器报表路径
    private $url = 'http://live.qunarmei.com/csv/';
    /**
     * 功能: 订单列表
     * 请求: key 建议搜索
     * 返回:
     */
    public function index(){
        $orderSer = new LiveOrder();
        $key = input('key');
        $ordersn = input('ordersn');
        $map = ''; $map1='';
        $data22 = '';
        if($ordersn){
            $map .= " and ord.ordersn='{$ordersn}'";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数

        $dt2 = strtotime(date('Y-m-d',time()));$dt1 = $dt2-3600*24;
        //搜索支付时间
        if(input('dt1') && input('dt2')){
            $dt1 = strtotime(input('dt1'));
            $dt2 = strtotime(input('dt2'));
        }
        $map .= ' and ord.anxinsong=1 ';
        $count = Db::table('ims_bj_shopn_order ord')
            ->join(['ims_bj_shopn_order_goods'=>'ordg'],['ord.id=ordg.orderid'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'mem'],['mem.id=ord.uid'],'LEFT')
            ->where('ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.$map )
            ->group('ord.id')
            ->count();//计算总页面
       
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::table('ims_bj_shopn_order ord')->join(['ims_bj_shopn_member'=>'mem'],['mem.id=ord.uid'],'LEFT')->join(['ims_bwk_branch'=>'b'],['b.id=ord.storeid'],'LEFT')->where('ord.payTime>='.$dt1.' and ord.payTime<'.$dt2.$map )->limit($pre,$limits)->field('ord.*,mem.pid,mem.realname,mem.mobile,mem.activity_flag,b.sign,b.title,b.address')->group('ord.id')->select();  
        $id_interestrates = [];
        $store_ids = [];
        $order_ids = [];
        $pids = [];
        if ($lists) {
            foreach ($lists as $k => $v) {
                $id_interestrates[] = $v['id_interestrate'];
                $store_ids[] = $v['storeid'];
                $order_ids[] = $v['id'];
                $pids[] = $v['pid'];
                $lists[$k]['bsc'] = '';
                $lists[$k]['store_name'] = $v['title'];
                $lists[$k]['sign'] = $v['sign'];
                $list[$k]['pname'] = '';
                $list[$k]['pmobile'] = '';
                $lists[$k]['realname'] = $v['realname'];
                $lists[$k]['mobile'] = $v['mobile'];
                $lists[$k]['activity_flag'] = $v['activity_flag'];
                $lists[$k]['ordersn'] = $v['ordersn'];
                $lists[$k]['status'] = '已支付';
                $lists[$k]['sh_type'] = $v['anxinsong']==1?'送货上门':'到店取货';
                $lists[$k]['order_type'] = '安心送';
                $lists[$k]['goods'] = '';//商品名称 + 商品属性
                $lists[$k]['goods_tg'] = '';// 默认为空,买赠为 诚美总部
                $lists[$k]['goods_num'] = 1;// 购买数量
                $lists[$k]['price'] = '';// 订单金额
                $lists[$k]['discount'] = '';// 抵扣信息
                $lists[$k]['goods_code'] = '';// 规格型号 产品编码
                $lists[$k]['qh_status'] = '未取货';// 取货状态
                $lists[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);// 订单创建时间
                $lists[$k]['payTime'] = $v['payTime']==null?'':date('Y-m-d H:i:s',$v['payTime']);// 订单支付时间
                $lists[$k]['transid'] = $v['transid'];// 支付流水号
                $lists[$k]['pl'] = '';// 商品分类 
                $lists[$k]['sh_name'] = $v['realname'];// 收获人名字
                $lists[$k]['sh_mobile'] = $v['mobile'];// 收获人电话
                $lists[$k]['sh_address'] = $v['address'];// 收获人地址
            }
        }
        // 所属办事处
        if ($store_ids){
            $mapst['b.id'] = ['in',$store_ids];
            $res_store = Db::table('ims_bwk_branch b')->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty = b.id'],'LEFT')->join(['sys_department'=>'sd'],['sd.id_department = sdr.id_department'],'LEFT')->field('b.id,b.sign,b.title,sd.st_department,b.address')->where($mapst)->select();
            // var_dump(777);die;
            if ($res_store){
                foreach ($res_store as $ks => $vs) {
                    foreach ($lists as $k => $v) {
                        if($vs['id'] == $v['storeid']){
                            $lists[$k]['bsc'] = $vs['st_department'];
                        }
                    }
                }
            }
        }
        // 上级美容师
        if ($pids) {
            $mappid['id'] = ['in',$pids];
            $respid = Db::table('ims_bj_shopn_member')->where($mappid)->select();
            if($respid){
                foreach ($respid as $kp => $vp) {
                    foreach ($lists as $k => $v) {
                        if ($v['pid'] == $vp['id']) {
                            $lists[$k]['pname'] = $vp['realname'];
                            $lists[$k]['pmobile'] = $vp['mobile'];
                        }
                    }
                }
            }

        }
        // 收货人信息
        if ($order_ids) {
            $mapord['order_id'] = ['in',$order_ids];
            $resord = Db::table('ims_bj_shopn_order_address')->where($mapord)->select();
            if ($resord) {
                foreach ($resord as $ko => $vo) {
                    foreach ($lists as $k => $v) {
                        if ($v['id'] == $vo['order_id']) {
                            $lists[$k]['sh_name'] = $vo['consignee'];// 收获人名字
                            $lists[$k]['sh_mobile'] = $vo['mobile'];// 收获人电话
                            $lists[$k]['sh_address'] = $vo['address'];// 收获人地址
                        }
                    }
                }
            }

        }
        // 购买商品及属性编码 
        // 根据订单商品拆分
        $gd_arr1 = [];
        if ($lists) {
            $liveser = new LiveOrder();
            $res = $lists;
            foreach ($res as $k1 => $v1) {
                // 根据套盒商品拆分成单品订单
                $mapc['og.orderid'] = $v1['id'];
                $resog = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.title,g.id,og.total,g.marketprice,og.idGoodsExtend,og.price')->where($mapc)->select();
                $price1 = '';
                if ($resog) {
                    foreach ($resog as $kog => $vog) {
                        $gd = $vog['title'];
                        if ($vog['idGoodsExtend'])  {
                            $gd_pros = $liveser->getNyCode($vog['idGoodsExtend']);
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
        //导出xls报表
        $export = input('report',0);
        if($export){
            $data = [];
            foreach ($gd_arr1 as $k2 => $v2) {
                $data1['id'] = 'APP'.$v2['id'];
                $data1['bsc'] = $v2['bsc'];
                $data1['store_name'] = $v2['store_name'];
                $data1['sign'] = $v2['sign'];
                $data1['pname'] = $v2['pname'];
                $data1['pmobile'] = $v2['pmobile'];
                $data1['realname'] = $v2['realname'];
                $data1['mobile'] = $v2['mobile'];
                $data1['activity_flag'] = $v2['activity_flag'];
                $data1['ordersn'] = "\t".$v2['ordersn'];
                $data1['status'] = $v2['status'];
                $data1['sh_type'] = $v2['sh_type'];
                $data1['order_type'] = $v2['order_type'];
                $data1['goods'] = $v2['goods'];
                $data1['goods_tg'] = $v2['goods_tg'];
                $data1['goods_num'] = $v2['goods_num'];
                $data1['price'] = $v2['price'];
                $data1['discount'] = $v2['discount'];
                $data1['goods_code'] = $v2['goods_code'];
                $data1['qh_status'] = $v2['qh_status'];
                $data1['createtime'] = $v2['createtime'];
                $data1['payTime'] = $v2['payTime'];
                $data1['transid'] = $v2['transid'];
                $data1['pl'] = $v2['pl'];
                $data1['sh_name'] = $v2['sh_name'];
                $data1['sh_mobile'] = $v2['sh_mobile'];
                $data1['sh_address'] = $v2['sh_address'];
                $data[] = $data1;
            }
        //     // var_dump($data);die;
            $filename = "去哪美日订单".date('YmdHis');
            $header = array ('订单ID','办事处','门店名称','门店编码','美容师名称','美容师电话','顾客姓名','顾客电话','顾客标识码','活动订单号','支付状态','取货方式','订单类型','购买产品','产品提供','购买数量','订单金额','抵扣信息','规格型号','取货状态','订单创建时间','订单支付时间','支付流水号','品类','收货人','联系方式','收货地址');
            $widths=array('10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10'); 
        //     // var_dump($data);die;
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('ordersn', $ordersn);
        $this->assign('dt1', date('Y-m-d',$dt1));
        $this->assign('dt2', date('Y-m-d',$dt2));
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $gd_arr1);
        //翻页
        if(input('get.page')){
            return json($gd_arr1);
        }
        return $this->fetch();
    }

    // erp单号回填页面
    public function huitian()
    {
        $order_ids = input('order_ids');
        $order_ids = rtrim($order_ids,',');
        // 根据订单查询门店信息
        $map = ' o.id in ('.$order_ids.')';
        $res = Db::table('ims_bj_shopn_order o')->join(['ims_bwk_branch'=>'b'],['o.storeid=b.id'],'LEFT')->field('b.title,b.sign')->where($map)->limit(1)->find();

        $this->assign('list', $res);
        $this->assign('order_ids', $order_ids);
        return $this->fetch();
    }
    // erp单号回填页面修改
    public function huitian_update()
    {
        $order_ids = input('order_ids');
        $erp_ordersn = input('erp_ordersn');
        if ($erp_ordersn) {
            $map = ' id in ('.$order_ids.')';
            $data['erp_ordersn'] = $erp_ordersn;
            $res = Db::table('ims_bj_shopn_order')->where($map)->update($data);
            $arr = [
                'code' => 1,
                'msg' => '修改成功'
            ];
            
        }else{
            $arr = [
                'code' => 0,
                'msg' => 'erp单号不能为空'
            ];
        }
        return json($arr);
    }
    /**
     * 安心送订单
     * @return [type] [description]
     */
    public function axsOrder()
    {
        /*每日按门店统计订单:
            门店名称,门店编号,当日订单数,当日订单额,查看(Erp单号回填,查看导出明细-个人订单)
        */
        $dt1 = strtotime('2020-01-01');
        $dt2 = strtotime('2020-01-02');
        $key = input('key');
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;
        // $dt1 = input('dt1',strtotime("yesterday"));
        // $dt2 = input('dt2',strtotime(date("Y-m-d",time())));
        $count = Db::table('ims_bj_shopn_order o')->join(['ims_bwk_branch'=>'b'],['b.id=o.storeid'],'LEFT')->where('o.payTime >= '.$dt1.' and o.payTime<'.$dt2)->field('count(o.id) cnt,sum(o.price) price,o.storeid,b.title,b.sign')->group('o.storeid')->count();
        $allpage = intval(ceil($count / $limits));
        $reso = Db::table('ims_bj_shopn_order o')->join(['ims_bwk_branch'=>'b'],['b.id=o.storeid'],'LEFT')->where('o.payTime >= '.$dt1.' and o.payTime<'.$dt2)->field('count(o.id) cnt,sum(o.price) price,o.storeid,o.erp_ordersn,b.title,b.sign')->page($Nowpage,$limits)->group('o.storeid,o.erp_ordersn')->select();
        $arr = [];
        if ($reso) {
            foreach ($reso as $k => $v) {
                $arr1['id'] = $k+1;
                $arr1['title'] = $v['title'];
                $arr1['sign'] = $v['sign'];
                $arr1['day_num'] = $v['cnt'];
                $arr1['day_price'] = $v['price'];
                $arr1['erp_sn'] = $v['erp_ordersn'];
                $res = 'storeid='.$v['storeid'].'&dt1='.$dt1.'&dt2='.$dt2;
                $arr1['res'] = $res;
                $arr[] = $arr1;
            }
        }
        // 导出每日整个所有订单
        $report = input('report');
        if ($report == 1) {
            $this->axsOrderDetail();

        }
        //翻页
        if(input('get.page')){
            return json($arr);
        }
        // echo '<pre>';print_r($arr);die;
        $this->assign('dt1', $dt1);
        $this->assign('dt2', $dt2);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('list', $arr);
        return $this->fetch();
    }
    // 安心送订单erp单号回填页面
    public function axs_huitian()
    {
        $storeid = input('storeid');// 门店id
        $dt1 = input('dt1');
        $dt2 = input('dt2');
        $res = 'storeid='.$storeid.'&dt1='.$dt1.'&dt2='.$dt2;
        $map['id'] = $storeid;
        $resl = Db::table('ims_bwk_branch')->where($map)->limit(1)->find();
        $this->assign('res', $res);
        $this->assign('list', $resl);
        return $this->fetch();
    }
    // erp单号回填页面修改
    public function axs_huitian_update()
    {
        $storeid = input('storeid');// 门店id
        $dt1 = input('dt1');
        $dt2 = input('dt2');
        $erp_ordersn = input('erp_ordersn');
        if ($erp_ordersn) {
            $map = ' payTime >= '.$dt1.' and payTime<'.$dt2;
            $map1['storeid'] = $storeid;
            $data['erp_ordersn'] = $erp_ordersn;
            $res = Db::table('ims_bj_shopn_order')->where($map)->where($map1)->update($data);
            $arr = [
                'code' => 1,
                'msg' => '修改成功'
            ];
            
        }else{
            $arr = [
                'code' => 0,
                'msg' => 'erp单号不能为空'
            ];
        }
        return json($arr);
    }
    /**
     * 安心送订单明细列表
     * @return [type] [description]
     */
    public function axsOrderDetail()
    {
        // echo "<pre>";print_r($_POST);die;
        // 查询是不是上传文件
        if ($_POST) {
            if ($_POST['submit']) {
                $this->excelImport();
            }
        }
        
        $orderSer = new LiveOrder();
        $ordersn = input('ordersn');
        $dt11 = input('dt11');
        $dt12 = input('dt12');
        $dt1 = input('dt1');
        $dt2 = input('dt2');
        if ($dt11 && $dt12) {
            $dt1 = strtotime($dt11);
            $dt2 = strtotime($dt12);
        }
        $storeid = input('storeid');
        $key = input('key');
        $Nowpage = input('get.page') ? input('get.page'):1;
        $res1 = 'storeid='.$storeid.'&dt1='.$dt1.'&dt2='.$dt2;
        $map11 = '';
        if ($dt1) {
            $map11 = ' o.payTime>='.$dt1.' and o.payTime<='.$dt2;
        }
        $map = [];
        if($ordersn){
            $map['o.ordersn'] = ['like','%'.$ordersn.'%'];
        }
        if($key){
            $map['b.sign'] = $key;
        }
        // var_dump(input());die;
        $limits = 50;
        // 查询订单
        // $map['o.id'] = 300144;
        if ($storeid) {
            $map['o.storeid'] = $storeid;
        }
        $count = Db::table('ims_bj_shopn_order o')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')->field('o.*,b.title,b.sign,m.realname,m.mobile')->where($map)->where($map11)->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $res = Db::table('ims_bj_shopn_order o')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')->field('o.*,b.title,b.sign,m.realname,m.mobile')->limit($pre,$limits)->where($map)->where($map11)->select();
        // var_dump($res);die;
        if ($res) {
            $orderids = [];
            $zi_ids = [];
            $id_interestrates = []; 
            $storeids = [];
            foreach ($res as $k => $v) {
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
                $res[$k]['sh_name'] = '';
                $res[$k]['sh_address'] = '';
                // 包含子商品
                $res[$k]['buy_goods_zi'] = '';
            }
            //查询订单-用户-门店信息
            $mapog['og.orderid'] = ['in',$orderids];
            $resg = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],'g.id=og.goodsid','LEFT')->field('idGoodsExtend,g.title,og.total,og.goodsid')->where($mapog)->select();
            if ($resg) {
                $k1 = 1;
                foreach ($resg as $ksg => $vsg) {
                    $res[$k]['buy_goods'] .= $k1.'.'.$vsg['title'].';';
                    // 查询商品属性
                    if ($vsg['idGoodsExtend']) {
                        $res[$k]['buy_goods'] .= $orderSer->getGoodsPro($vsg['idGoodsExtend']);
                    }
                    $zi_ids[] = $vsg['goodsid'];
                    $res[$k]['buy_goods'] .= '数量:'.$vsg['total'].';<br/>';
                    $k1++;
                }
                // 包含子商品
                $mapzi['pid'] = ['in',$zi_ids];
                $reszi = Db::table('ims_bj_shopn_goods_zi zi')->where($mapzi)->order('type asc')->select();
                if ($reszi) {
                    $k2 = 1;
                    foreach ($reszi as $kz => $vz) {
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
        }
        //翻页
        if(input('get.page')){
            return json($res);
        }
        //导出xls报表
        $type = input('type');
        $export = input('report',0);
        if($export){
            $data = [];
            foreach ($res as $k => $v) {
                $data1['bsc'] = $v['bsc'];
                $data1['store_name'] = $v['store_name'];
                $data1['store_sign'] = $v['store_sign'];
                $data1['ordersn'] = "\t".$v['ordersn'];
                $data1['realname'] = $v['realname'];
                $data1['mobile'] = $v['mobile'];
                $data1['price'] = $v['price'];
                $data1['buy_goods'] = $v['buy_goods'];
                $data1['buy_goods_zi'] = $v['buy_goods_zi'];

                $data1['sh_name'] = $v['sh_name'];
                $data1['sh_address'] = $v['sh_address'];
                $data1['erp_ordersn'] = $v['erp_ordersn'];
                $data1['kuaidi_sn'] = $v['kuaidi_sn'];

                $data1['createtime'] = $v['createtime'];
                $data1['payTime'] = $v['payTime'];
                $data1['bank'] = $v['bank'];
                $data1['fenqi'] = $v['fenqi']==0?1:$v['fenqi'];
                $data1['st_source'] = $v['st_source'];
                if ($type == 2) {
                    $data1['buy_goods'] = '"'.str_replace(array(',','&nbsp;','<br>','<br/>','<br />'),array('，',' ',PHP_EOL,PHP_EOL,PHP_EOL),$data1['buy_goods']).'"';
                    $data1['buy_goods_zi'] = '"'.str_replace(array(',','&nbsp;','<br>','<br/>','<br />'),array('，',' ',PHP_EOL,PHP_EOL,PHP_EOL),$data1['buy_goods_zi']).'"';
                }
                $data[] = $data1;
            }
            $filename = "去哪美日订单".date('YmdHis');
            $header = array ('市场','门店名称','门店编号','订单编号','用户名','用户电话','订单金额','购买商品','包含子商品','收货人名称','收货人地址','Erp单号','物流快递单号','下单时间','支付时间','付款银行','分期数','订单来源');
            $widths=array('10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10');
            // var_dump($data);die;
            if($data) {
                
                if ($type == 2) {
                    $rescsv = reportCsv($header, $data, $filename);//生成数据
                    $url = 'http://www.testlive.com:9091/csv/';
                    $rescsv = $url.$rescsv;
                    return $rescsv;
                }else{
                    excelExport($filename, $header, $data, $widths);//生成数据
                }
            }
            die();
        }
        // var_dump($res);die;
        $this->assign('ordersn', $ordersn);
        $this->assign('storeid', $storeid);
        $this->assign('dt1', date('Y-m-d',$dt1));
        $this->assign('dt2', date('Y-m-d',$dt2));
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $res);
        $this->assign('res', $res1);
        return $this->fetch();
    }

    /**
     * excel导入数据读取
     */
    public function excelImport()
    {
        
        $res11 = exceladd();
        // echo "res11:<pre>";print_r($res11);die; 
        foreach ($res11 as $k => $v) {
            $map1['ordersn'] = trim($v[3]);
            $arr1['kuaidi_sn'] = $v[12];// 快递单号,更新到数据库
            // 更新到数据库
            if ($arr1['kuaidi_sn']) {
                 // 更新完成跳转到刷新首页
                 $resupd = Db::table('ims_bj_shopn_order')->where($map1)->update($arr1);
             } 
        }
        //重定向浏览器 
        $res = input('res');
        $url = 'http://www.testlive.com:9091/admin/order/axsorderdetail.html?'.$res;
        header('Location:'.$url);
        // $arr['code'] = 1;
        // $arr['msg'] = '物流信息更新成功';
        // return json($arr);
    }
}