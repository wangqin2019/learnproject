<?php

namespace app\admin\controller;
use think\Db;

/* 市场订单详情
 * */
class Market extends Base
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
        9 => '412活动门票商品',
        10 => '412活动直播商品',
        11 => '412之后活动门票商品',
        12 => '412之后活动直播商品',
    ];
    //服务器报表路径
    private $url = 'http://live.qunarmei.com/csv/';

    // 订单详情数据
    public function orderdetail(){
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
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-10'),strtotime('2020-05-31')]];
            }elseif ($order_type == 10) {
                // 412活动直播商品
                // 支付日期
                $map22['o.payTime'] = ['between time',[strtotime('2020-04-10'),strtotime('2020-05-31')]];
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
        $res = Db::table('ims_bj_shopn_order o')->join(['ims_bj_shopn_member'=>'m'],'m.id=o.uid','LEFT')->join(['ims_bwk_branch'=>'b'],'b.id=o.storeid','LEFT')->join(['ims_bj_shopn_order_goods'=>'og'],'og.orderid=o.id','LEFT')->join(['ims_bj_shopn_goods'=>'g'],'og.goodsid=g.id','LEFT')->field('o.*,b.title,b.sign,m.realname,m.mobile')->limit($pre,$limits)->where($map)->where($map11)->where($map22)->group('o.id')->select();
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
                // 包含子商品
                $res[$k]['buy_goods_zi'] = '';
                $res[$k]['isaxs'] = $v['addressid']>0?'是':'否';// 有安心送地址
                $res[$k]['sh_name'] = '';
                $res[$k]['sh_mobile'] = '';
                $res[$k]['sh_address'] = '';
                $res[$k]['express_number'] = '';
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
                $resog = Db::table('ims_bj_shopn_order_goods og')->field('og.content title,og.goodsid id,og.total,og.idGoodsExtend,og.price,og.sale_price')->where($mapc)->select();
                $price1 = '';
                if ($resog) {
                    foreach ($resog as $kog => $vog) {
                        // erp售出价格与实际价格不一致时
                        if($vog['sale_price']){
                            $vog['price'] = $vog['sale_price'];
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
        $liveord = new LiveOrder();
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
                    $res[$k]['order_type'] = $liveord->orderTypeValue($v['payTime'],$resact1['ticket_type']);
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
                $data1['orderid'] = 'APP'.$v['id'];
                // 查询订单类型
                $mapact2['g.pcate'] = 31;
                $mapact2['og.orderid'] = $v['id'];
                $resact2 = Db::table('ims_bj_shopn_order_goods og')->join(['ims_bj_shopn_goods'=>'g'],['og.goodsid=g.id'],'LEFT')->field('g.ticket_type,g.pcate,g.live_flag')->where($mapact2)->limit(1)->find();
                $data1['order_type'] = '普通订单商品';
                if ($resact2) {
                    $data1['order_type'] = $resact2['ticket_type']?'315活动门票商品':'315活动直播商品';
                }
                if ($order_type) {
                    $data1['order_type'] = $this->orderTypeval[$order_type];
                }elseif($resact2){
                    $data1['order_type'] = $liveord->orderTypeValue($v['payTime'],$resact2['ticket_type']);
                }
                $data1['bsc'] = $v['bsc'];
                $data1['store_name'] = $v['store_name'];
                $data1['store_sign'] = $v['store_sign'];
                $data1['ordersn'] = "\t".$v['ordersn'];
                $data1['price'] = $v['price_h'];
                if ($export == 2) {
                    $data1['price'] = $v['price_g'];
                }
                $data1['goods'] = $v['goods'];
                $data1['spec'] = $v['spec'];
                $data1['goods_num'] = $v['goods_num'];
                $data1['goods_code'] = $v['goods_code'];
                $data1['createtime'] = $v['createtime'];
                $data1['payTime'] = $v['payTime'];
                $data1['bank'] = $v['bank'];
                $data1['fenqi'] = $v['fenqi']==0?1:$v['fenqi'];
                $data1['st_source'] = $v['st_source'];
                if ($type == 2) {
                    $data1['buy_goods'] = '"'.str_replace(array(',','&nbsp;','<br>','<br/>','<br />'),array('，',' ',PHP_EOL,PHP_EOL,PHP_EOL),$data1['buy_goods']).'"';
                    $data1['buy_goods_zi'] = '"'.str_replace(array(',','&nbsp;','<br>','<br/>','<br />'),array('，',' ',PHP_EOL,PHP_EOL,PHP_EOL),$data1['buy_goods_zi']).'"';
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
                $data[] = $data1;
            }
            $filename = "去哪美日订单".date('YmdHis');
            $header = array ('订单ID','订单类型','市场','门店名称','门店编号','订单编号','订单金额','购买商品','商品规格','商品数量','商品编码','下单时间','支付时间','付款银行','分期数','订单来源','顾客备注','客服开单自定义备注','是否安心送');
            $widths=array('10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10','10');
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
                }
            }
            die();
        }
        // var_dump($res);die;
        $this->assign('ordersn', $ordersn);
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
}