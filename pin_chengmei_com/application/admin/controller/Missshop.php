<?php

namespace app\admin\controller;
use app\admin\model\BankInterestrateModel;
use app\admin\model\BankModel;
use app\admin\model\BranchModel;
use app\admin\model\ChristmasModel;
use app\admin\model\CouponModel;
use app\admin\model\GoodsModel;
use app\admin\model\Node;
use app\admin\model\UserType;
use think\Db;
use think\Debug;
use think\Loader;

class Missshop extends Base
{

    //活动配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array=array('activity_status'=>$param['activity_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'show_time'=>strtotime($param['show_time']));
            Db::name('activity_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $a_config=Db::name('activity_config')->where('id',1)->find();
        $a_config['begin_time']=date('Y-m-d H:i:s',$a_config['begin_time']);
        $a_config['end_time']=date('Y-m-d H:i:s',$a_config['end_time']);
        $a_config['show_time']=date('Y-m-d H:i:s',$a_config['show_time']);
        $this->assign('a_config',$a_config);
        return $this->fetch();
    }


    /**
     * [店老板参与活动]
     */
    public function join(){
        header("Cache-control: private");
        $key = input('key');
        $export = input('export',0);
        $map=[];
        if($key && $key!=="")
        {
            $map['title|sign'] = ['like',"%" . $key . "%"];
        }
        $map['m.isadmin'] = ['eq',1];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export) {
            $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->select();
        }else{
            $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->page($Nowpage, $limits)->select();
        }
        foreach ($lists as $kk => $vv) {
            $activitys=[];
            if(!empty($vv['join_tk'])){
                $activitys=Db::name('activity_list')->where('id','in',$vv['join_tk'])->column('name');
            }
            $lists[$kk]['activitys']=implode('<br>',$activitys);
            $lists[$kk]['goods']=Db::name('goods')->where(['storeid'=>$vv['id'],'status'=>1,'goods_cate'=>4])->count();
            if(!session('get_mobile')){
                $lists[$kk]['mobile']=substr_replace($vv['mobile'], '****', 3, 4);
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['activity_key']=$v['activity_key']?'已参与':'未参加';
            }
            $filename = "密丝小铺参加门店列表".date('YmdHis');
            $header = array ('门店名称','门店编码','店老板名称','店老板电话','是否参加');
            $widths=array('30','10','10','10','5');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('count', $count);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    public function state(){
        $id=input('param.id');
        $status = Db::table('ims_bj_shopn_member')->where(array('storeid'=>$id,'isadmin'=>1))->value('activity_key');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::table('ims_bj_shopn_member')->where(array('storeid'=>$id,'isadmin'=>1))->setField(['activity_key'=>0]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已设置为未参与']);
        } else {
            $flag = Db::table('ims_bj_shopn_member')->where(array('storeid'=>$id,'isadmin'=>1))->setField(['activity_key'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已设置为已参与']);
        }
    }

    public function join_pg(){
        $id=input('param.id');
        $status = Db::table('ims_bwk_branch')->where(array('id'=>$id))->value('join_pg');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::table('ims_bwk_branch')->where(array('id'=>$id))->setField(['join_pg'=>0]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已设置为未开启']);
        } else {
            $flag = Db::table('ims_bwk_branch')->where(array('id'=>$id))->setField(['join_pg'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已设置为已开启']);
        }
    }


    public function join_tk(){
        $id=input('param.id');
        $status = Db::table('ims_bwk_branch')->where(array('id'=>$id))->value('join_tk');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::table('ims_bwk_branch')->where(array('id'=>$id))->setField(['join_tk'=>0]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已设置为未开启']);
        } else {
            $flag = Db::table('ims_bwk_branch')->where(array('id'=>$id))->setField(['join_tk'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已设置为已开启']);
        }
    }


    /**
     * [index 订单列表]
     */
    public function orders(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $key = input('key');
        $sale_uid = input('sale_uid');
        $pay_status = input('pay_status',1);
        $export = input('export',0);
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $scene = input("param.scene",88);
        $is_axs = input("param.is_axs",88);
        $start_id = input("param.start_id");
        $end_id = input("param.end_id");
        $map = [];
        if($key&&$key!=="")
        {
            $map['depart.st_department|bwk.sign|order.order_sn|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($sale_uid && $sale_uid!=="")
        {
            $map['order.fid'] = ['eq',$sale_uid];
        }
        if($pay_status!=88){
            if($pay_status)
            {
                $map['order.pay_status'] = ['eq',1];
            }else{
                $map['order.pay_status'] = ['eq',0];
            }
        }
        if($is_axs!=88){
            if($is_axs)
            {
                $map['order.is_axs'] = ['eq',1];
            }else{
                $map['order.is_axs'] = ['eq',0];
            }
        }
        if($scene!=88){
            $map['order.scene'] = ['eq',$scene];
        }
        if($search_time1!='' && $search_time2!=''){
            $map['order.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
        }
        if($start_id!='' && $end_id!=''){
            $map['order.id'] = array('between', [$start_id, $end_id]);
        }
        $map['order.channel'] = ['eq','missshop'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_order')->alias('order')->join('goods g','order.pid=g.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::name('activity_order')->alias('order')->join('activity_order_info info','order.order_sn=info.order_sn','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->join('order_lucky lucky','order.order_sn=lucky.order_sn','left')->field('order.*,order.flag order_flag,info.good_id,info.good_num,info.good_specs,info.pick_up,info.good_amount,info.main_flag,info.flag info_flag,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,lucky.flag,lucky.lucky_name,lucky.insert_time lucky_time1,lucky.update_time lucky_time2,info.good_specs_sku,bwk.receive_address,bwk.receive_consignee,bwk.receive_mobile,depart.st_department')->where($map)->order('order.id')->select();
        }else{
            $lists = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,order.flag order_flag,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->order('order.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$v['fid'])->field('mobile seller_tel,realname seller_name')->find();
            $lists[$k]['order_price']=$v['pay_price']?$v['pay_price']:0;
            $lists[$k]['sellername']=$sellerInfo['seller_name'];
            $lists[$k]['sellermobile']=$sellerInfo['seller_tel'];
            $lists[$k]['bsc_name']=$v['st_department'];
            $lists[$k]['cus_sign']=$v['sign'];
            $lists[$k]['cus_title']=$v['title'];
            $lists[$k]['st_department']=$v['st_department'];
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
            $lists[$k]['scene']=config("activity_list.".$v['scene']);
            if($v['order_flag']){
                $goods=[];
                $orderInfo=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where('info.order_sn',$v['order_sn'])->field('g.name,info.good_specs,info.good_amount,info.flag,info.good_num')->select();
                foreach ($orderInfo as $kk=>$vv){
                    $f=$vv['flag']?'赠送：':'';
                    $goods[]=($kk+1).'.'.$f.$vv['name'].$vv['good_specs'].' ×'.$vv['good_num'];
                }
                $lists[$k]['name']=implode('<br/>',$goods);
                $lists[$k]['goods_code']='';
            }else{
                $getInfo=Db::name('goods')->where('id',$v['pid'])->field('name,goods_code')->find();
                $lists[$k]['name']=$getInfo['name'].' '.$v['specs'];
                $lists[$k]['goods_code']=$getInfo['goods_code'];
            }
            $lists[$k]['promoter_tips']='';
            if($v['scene']==5){
                if(strstr($v['remark'],'该单有推广积分')){
                    $lists[$k]['promoter_flag']=1;
                }else{
                    $lists[$k]['promoter_flag']=1;
                }
                $lists[$k]['promoter_tips']=$v['remark'];
            }
            if(!session('get_mobile')){
                $lists[$k]['mobile']=substr_replace($v['mobile'], '****', 3, 4);
                $lists[$k]['sellermobile']=substr_replace($sellerInfo['seller_tel'], '****', 3, 4);
            }
            $aids=Db::name('activity_list')->where(['activity_status'=>1,'auto_erp'=>1])->column('id');
            if(in_array($v['scene'],$aids)) {
                if ($v['u8_flag'] == 1) {
                    $lists[$k]['u8_flag_text'] = '<span class="label label-success">插入成功</span>';
                    $lists[$k]['u8_flag_err'] = '';
                } elseif ($v['u8_flag'] == 2) {
                    $lists[$k]['u8_flag_text'] = '<span class="label label-warning">插入异常</span> <span class="label label-danger" onclick="insert_repeat(' . $v['id'] . ')" style="cursor: pointer" id="order_' . $v['id'] . '">重新插入</span>';
                    $lists[$k]['u8_flag_err'] = '<br/>异常原因：' . $v['u8_err'];
                } else {
                    $lists[$k]['u8_flag_text'] = '<span class="label label-default">等待插入</span>';
                    $lists[$k]['u8_flag_err'] = '';
                }
            }else{
                $lists[$k]['u8_flag_text'] = '<span class="label label-default">该订单类型无需插入</span>';
                $lists[$k]['u8_flag_err'] = '';
            }

        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['id']=$v['id'];
                $data[$k]['st_department']=$v['bsc_name'];
                $data[$k]['title']=$v['cus_title'];
                $data[$k]['sign']=$v['cus_sign'];
                $data[$k]['sellername']=$v['sellername'];
                $data[$k]['sellermobile']=$v['sellermobile'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['activity_flag']=$v['activity_flag'];
                $data[$k]['order_sn']=' '.$v['order_sn'];
                $data[$k]['pay_status']=$v['pay_status']?'已支付':'未支付';
                if($v['is_axs']){
                    $data[$k]['pick_type']='安心直邮';
                }else{
                    $data[$k]['pick_type']=$v['pick_type']?'到店取货':'现场取货';
                }
                $data[$k]['scene']=$v['scene'];
                if(!$v['order_flag']) {
                    $data[$k]['name'] = ' ' . $v['name'];
                    $data[$k]['belong'] ='';
                    $data[$k]['num'] = $v['num'];
                    $data[$k]['pay_price'] = $v['pay_price'];
                    $data[$k]['coupon_dsc']='';
                    $data[$k]['specs']=$v['goods_code'];
                    $data[$k]['order_status']=$v['pick_up']?'已取货':'未取货';
                }else{
                    $n=Db::name('goods')->where('id',$v['good_id'])->field('name,storeid,goods_code')->find();
                    if($n['storeid']){
                        $belongBranch=Db::table('ims_bwk_branch')->where('id',$n['storeid'])->value('title');
                    }else{
                        $belongBranch='';
                    }
                    $data[$k]['name'] = $v['info_flag']?'买赠：'.$n['name'].$v['good_specs']:$n['name'].$v['good_specs'];
                    if($v['info_flag']){
                        $data[$k]['belong'] = $n['storeid']?$belongBranch:'诚美总部';
                    }else{
                        $data[$k]['belong'] = '';
                    }
                    $data[$k]['num'] = $v['good_num'];

                    $data[$k]['pay_price'] = $v['info_flag']?0:$v['good_amount'];
                    $data[$k]['coupon_dsc']='';
                    $data[$k]['specs']=$v['good_specs_sku']?$v['good_specs_sku']:$n['goods_code'];

                    if($v['main_flag']){
                        if($v['coupon_price']) {
                            $data[$k]['pay_price'] = $v['good_amount'] - $v['coupon_price'];
                            $data[$k]['coupon_dsc'] = $v['coupon_price'] ? '该金额扣减抵用' . $v['coupon_price'] . '现金券一张' : '11';
                        }
                    }

                    $data[$k]['order_status']=$v['order_status']?'已取货':'未取货';
                }
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['pay_time']=$v['pay_time'];
                $data[$k]['transaction_id']='`'.$v['transaction_id'];
                $data[$k]['lucky_name']='';
                $data[$k]['flag']='';
                $data[$k]['lucky_time1']='';
                $data[$k]['lucky_time2']='';
                if($v['lucky_name']){
                    if(!$v['order_flag']) {
                        $data[$k]['lucky_name'] = $v['lucky_name'];
                        $data[$k]['flag'] = $v['flag'] ? '已领取' : '未领取';
                        $data[$k]['lucky_time1'] = date('Y-m-d H:i:s', $v['lucky_time1']);
                        $data[$k]['lucky_time2'] = $v['lucky_time2'] ? date('Y-m-d H:i:s', $v['lucky_time2']) : '';
                    }else{
                        if($v['main_flag']){
                            $data[$k]['lucky_name'] = $v['lucky_name'];
                            $data[$k]['flag'] = $v['flag'] ? '已领取' : '未领取';
                            $data[$k]['lucky_time1'] = date('Y-m-d H:i:s', $v['lucky_time1']);
                            $data[$k]['lucky_time2'] = $v['lucky_time2'] ? date('Y-m-d H:i:s', $v['lucky_time2']) : '';
                        }
                    }
                }
                //88福袋需要导入推广人姓名和电话
                $data[$k]['share_realname']='';
                $data[$k]['share_mobile']='';
                if($v['scene']==5){
                    if(strlen($v['share_uid'])){
                        $shareInfo=Db::table('ims_bj_shopn_member')->where('id',$v['share_uid'])->field('realname,mobile')->find();
                        if($shareInfo) {
                            $data[$k]['share_realname'] = $shareInfo['realname'];
                            $data[$k]['share_mobile'] = $shareInfo['mobile'];
                        }
                    }
                }
                //获取收货信息
                $data[$k]['d_consignee']='';
                $data[$k]['d_mobile']='';
                $data[$k]['d_address']='';
                if($v['is_axs']){
                    $delivery=Db::name('activity_order_address')->where('order_sn',trim($v['order_sn']))->find();
                    $data[$k]['d_consignee']=$delivery['consignee'];
                    $data[$k]['d_mobile']=$delivery['mobile'];
                    $data[$k]['d_address']=$this->getNameByParentId($delivery['province']).$this->getNameByParentId($delivery['city']).$this->getNameByParentId($delivery['district']).$this->getNameByParentId($delivery['street']).$delivery['address'];
                }else{
                    $data[$k]['d_consignee']=$v['receive_consignee'];
                    $data[$k]['d_mobile']=$v['receive_mobile'];
                    $data[$k]['d_address']=$v['receive_address'];
                }
                $goodId=$v['good_id']?$v['good_id']:$v['pid'];
                $data[$k]['goods_compose']=Db::name('goods')->where('id',$goodId)->value('is_compose');
                $data[$k]['info_flag']=$v['info_flag']?$v['info_flag']:0;
                $data[$k]['good_id']=$v['good_id']?$v['good_id']:$v['pid'];
            }
            //处理组合产品拆分
            $res=[];
            foreach ($data as $key=>$val){
                if($val['goods_compose'] && $val['info_flag']==0){
                    $compose=Db::name('compose')->where(['pid'=>$val['good_id'],'status'=>1])->value('cids');
                    if($compose){
                        $composeArr=explode(',',$compose);
                        foreach ($composeArr as $kk=>$vv){
                            $sonGoods=Db::name('goods')->where('id',$vv)->field('name,activity_price,goods_code')->find();
                            $val['name']=$sonGoods['name'];
                            $val['pay_price']=$sonGoods['activity_price'];
                            $val['specs']=$sonGoods['goods_code'];
                            unset($val['goods_compose'],$val['info_flag'],$val['good_id']);
                            $res[]=$val;
                        }
                    }
                }else{
                    unset($val['goods_compose'],$val['info_flag'],$val['good_id']);
                    $res[]=$val;
                }
            }
            $filename = "密丝小铺订单列表".date('YmdHis');
            $header = array ('订单Id','办事处','门店名称','门店编码','美容师名称','美容师电话','顾客姓名','顾客电话','顾客标识码','活动订单号','支付状态','取货方式','订单类型','购买产品','产品提供','购买数量','订单金额','抵扣信息','规格型号','取货状态','订单创建时间','订单支付时间','支付流水号','中奖奖品名称','奖品是否领取','奖品中奖时间','奖品领取时间','订单推广人','推广人电话','收货人','收货手机','收货地址');
            $widths=array('10','10','30','20','15','15','15','15','15','50','30','30','30','30','30','30','30','30','30','30','30','50','50','20','30','30','30','30','30','30','30','100');
            if($res) {
                if($export==1){
                    excelExport($filename, $header, $res, $widths);//生成数据
                }else{
                    export_data($res,$header,$filename);
                }
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('sale_uid', $sale_uid);
        $this->assign('pay_status', $pay_status);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        $this->assign('scene',$scene);
        $this->assign('is_axs',$is_axs);
        $this->assign('start_id',$start_id);
        $this->assign('end_id',$end_id);
        $seller=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.fid=m.id','left')->field('o.fid,m.realname')->where(['channel'=>'missshop','pay_status'=>1])->group('o.fid')->select();
        $this->assign('seller', $seller);
        //订单统计
        $order = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('sum(order.pay_price) pay_price,count(order.id) count,count(DISTINCT order.uid) number,count(DISTINCT order.storeid) storeNum')->where($map)->find();
        $this->assign('order',$order);

        $activityList=config('activity_list');
        $this->assign('activityList',$activityList);

        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [index 美容师推广列表]
     */
    public function rank(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $key = input('key');
        $export = input('export',0);
        $shareMan = input('param.share','fid');
        $scene = input('param.scene',88);
        $map = [];
        if($key&&$key!=="")
        {
            $map['bwk.title|bwk.sign|member.realname|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($scene!=88){
            $map['orders.scene'] = $scene;
        }
        $map['orders.pay_status'] = ['eq',1];
        $map['orders.channel'] = ['eq','missshop'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_order')->alias('orders')->join(['ims_bj_shopn_member'=>'member'],'orders.'.$shareMan.'=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('orders.id')->where($map)->count(' distinct orders.'.$shareMan);
        if($export){
            $lists = Db::name('activity_order')->alias('orders')->join(['ims_bj_shopn_member'=>'member'],'orders.'.$shareMan.'=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('orders.fid,orders.share_uid,orders.num,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,count(orders.id) count,sum(orders.num) num,sum(orders.pay_price) price')->where($map)->group('orders.'.$shareMan)->order('num desc')->select();
        }else{
            $lists = Db::name('activity_order')->alias('orders')->join(['ims_bj_shopn_member'=>'member'],'orders.'.$shareMan.'=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('orders.fid,orders.share_uid,orders.num,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign,count(orders.id) count,sum(orders.num) num,FORMAT(sum(orders.pay_price),2) price')->where($map)->page($Nowpage, $limits)->group('orders.'.$shareMan)->order('num desc')->select();
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['bsc_name']=Db::table('sys_departbeauty_relation')->alias('departbeauty')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('departbeauty.id_beauty',$v['storeid'])->value('depart.st_department');
            $lists[$k]['cus_title']=$v['title'];
            $lists[$k]['cus_sign']=$v['sign'];
            $lists[$k]['seller_name']=$v['realname'];
            $lists[$k]['seller_tel']=$v['mobile'];
            if(!session('get_mobile')){
                $lists[$k]['seller_tel']=substr_replace($v['mobile'], '****', 3, 4);
            }
            $m['o.fid']=array('eq',$v['fid']);
            $m['o.pay_status']=array('eq',1);
            $m['o.channel']=array('eq','missshop');
            $m['o.scene']=array('eq',0);
            $m['m.activity_flag']=array('eq','8806');
            $lists[$k]['cus_total']=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id')->where($m)->count();

        }
        $allpage = intval(ceil($count / $limits));
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['bsc_name']=$v['bsc_name'];
                $data[$k]['cus_title']=$v['cus_title'];
                $data[$k]['cus_sign']=$v['cus_sign'];
                $data[$k]['seller_name']=$v['seller_name'];
                $data[$k]['seller_tel']=$v['seller_tel'];
                $data[$k]['count']=$v['count'];
                $data[$k]['cus_total']=$v['cus_total'];
                $data[$k]['num']=$v['num'];
                $data[$k]['price']=$v['price'];
            }
            $filename = "密丝小铺推广排行列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','推广人名称','推广人电话','推广订单数','拓客数','销售盒数','订单总金额');
            $widths=array('10','30','20','15','15','15','15','15','15');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('share', $shareMan);
        $this->assign('scene', $scene);
        $activityList=config('activity_list');
        $this->assign('activityList',$activityList);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [index 办事处排名]
     */
    public function bscrank(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $key = input('key');
        $export = input('export',0);
        $id_department = input('param.id_department','');
        $scene = input('param.scene',88);
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $map = [];
        if($key && $key!=="")
        {
            $map['bwk.title|bwk.sign'] = ['like',"%" . $key . "%"];
        }
        $activity='8806';
        if($scene!=88){
            if($scene==1){
                $map['orders.scene'] = ['eq',1];
                $activity=['8805','8806','8808','8809'];
            }elseif($scene==2){
                $map['orders.scene'] = ['eq',2];
                $activity=['8810'];
            }elseif($scene==3){
                $map['orders.scene'] = ['eq',3];
                $activity=['8811','8813','8814','8815','8816'];
            }elseif($scene==4){
                $map['orders.scene'] = ['eq',4];
                $activity=['8812'];
            }elseif($scene==5){
                $map['orders.scene'] = ['eq',5];
                $activity=['8816'];
            }elseif($scene==6){
                $map['orders.scene'] = ['eq',6];
                $activity=['8818'];
            }elseif($scene==7){
                $map['orders.scene'] = ['eq',7];
                $activity=['8819'];
            }elseif($scene==100){
                $map['orders.pid'] = ['eq',47];
                $activity='8806';
            }elseif($scene==200){
                $map['orders.pid'] = ['eq',79];
                $activity='8809';
            }else{
                $map['orders.scene'] = ['eq',0];
                $activity=['8806','8809'];
            }
        }
        if($search_time1!='' && $search_time2!=''){
            if($export) {
                $map['orders.pay_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }else{
                $map['orders.pay_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }
        }
        $showBids = Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'], 'r.id_department=d.id_department', 'left')->where('d.id_department', $id_department)->column('id_beauty');
        if($id_department && $id_department!==""){
            $map['bwk.id'] = ['in',$showBids];
        }
        $map['orders.pay_status'] = ['eq',1];
        $map['orders.channel'] = ['eq','missshop'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_order')->alias('orders')->join(['ims_bwk_branch'=>'bwk'],'orders.storeid=bwk.id','left')->field('orders.id')->where($map)->count(' distinct orders.storeid');
        if($export){
            $lists = Db::name('activity_order')->alias('orders')->join(['ims_bwk_branch'=>'bwk'],'orders.storeid=bwk.id','left')->field('orders.fid,orders.share_uid,orders.num,bwk.id storeid,bwk.title,bwk.sign,count(orders.id) count,sum(orders.num) num,sum(orders.pay_price) price')->where($map)->group('orders.storeid')->order('num desc')->select();
        }else{
            $lists = Db::name('activity_order')->alias('orders')->join(['ims_bwk_branch'=>'bwk'],'orders.storeid=bwk.id','left')->field('orders.fid,orders.share_uid,orders.num,bwk.id storeid,bwk.title,bwk.sign,count(orders.id) count,sum(orders.num) num,FORMAT(sum(orders.pay_price),2) price')->where($map)->page($Nowpage, $limits)->group('orders.storeid')->order('num desc')->select();
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['bsc_name']=Db::table('sys_departbeauty_relation')->alias('departbeauty')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('departbeauty.id_beauty',$v['storeid'])->value('depart.st_department');
            $lists[$k]['cus_title']=$v['title'];
            $lists[$k]['cus_sign']=$v['sign'];
            $m['o.storeid']=array('eq',$v['storeid']);
            $m['o.pay_status']=array('eq',1);
            $m['o.channel']=array('eq','missshop');
            $m['o.scene']=array('eq',0);
            $m['m.activity_flag']=array('in',$activity);
            $cus_total=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member'=>'m'],'o.uid=m.id')->where($m)->field('o.id')->group('o.uid')->select();
            $lists[$k]['cus_total']=count($cus_total);
        }
        $allpage = intval(ceil($count / $limits));
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['bsc_name']=$v['bsc_name'];
                $data[$k]['cus_title']=$v['cus_title'];
                $data[$k]['cus_sign']=$v['cus_sign'];
                $data[$k]['count']=$v['count'];
                $data[$k]['cus_total']=$v['cus_total'];
                $data[$k]['num']=$v['num'];
                $data[$k]['price']=$v['price'];
            }
            $filename = "密丝小铺办事处排行列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','推广订单数','拓客数','销售盒数','订单总金额');
            $widths=array('10','30','20','15','15','15','15');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('id_department', $id_department);
        $this->assign('scene', $scene);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        $depart=Db::table('sys_department')->field('id_department,st_department')->where('id_department','not in',['000','001'])->select();
        $this->assign('depart', $depart);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function message(){
        if(request()->isAjax()){
            $param = input('post.');
            if($param['price']){
                $array=array('price'=>$param['price'],'send_time'=>time());
            }else{
                $array=array('price'=>$param['price'],'send_time'=>'');
            }

            Db::name('activity_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $a_config=Db::name('activity_config')->where('id',1)->find();
        $this->assign('a_config',$a_config);
        return $this->fetch();
    }



    public function message_action(){
        set_time_limit(0);
        $role=input('param.role',0);
        ini_set('memory_limit', '1024M');
        //获取参加拼购活动的门店
        $m['isadmin']=array('eq',1);
        $m['activity_key']=array('eq',1);
        $m['storeid']=array('not in','1,2');
        $getStore=Db::table('ims_bj_shopn_member')->where($m)->column('storeid');
        $map['storeid']=array('in',$getStore);
        if($role==1){
            $r_name='店老板';
            $map['isadmin']=array('eq',1);
        }elseif ($role==2){
            $r_name='美容师';
            $map[''] = ['exp', Db::raw('length(code)>1')];
        }else{
            $r_name='顾客';
            $map['activity_flag']=array('in','8805,8806,8808');
        }
        $dateSection=[];
        $a_config=Db::name('activity_config')->where('id',1)->find();
        if($a_config['price']){
            $startdate = date('Y-m-d',$a_config['send_time']);
            $enddate = date("Y-m-d",strtotime("+2 day"));
            $dateSection = $this->getDateFromRange($startdate,$enddate);
        }
        $list=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->join('wx_user u','m.mobile=u.mobile','left')->where($map)->field('b.title,b.sign,m.realname,m.mobile,m.last_login,u.time_out')->select();
        foreach ($list as $k=>$v){
            $appTime=date('Y-m-d',strtotime($v['last_login']));//app登陆时间
            $wxTime=date('Y-m-d',$v['time_out']);//微信小程序登录时间
            if($a_config['price']==1) {
                if (in_array($appTime, $dateSection) || in_array($wxTime, $dateSection)) {
                    $list[$k]['login'] = '已登录';
                } else {
                    $list[$k]['login'] = '未登录';
                }
            }else{
                $list[$k]['login'] = '';
            }
            if($v['time_out']){
                $xcx_login=date('Y-m-d H:i:s',$v['time_out']);//微信小程序登录时间
                $list[$k]['time_out']=date('Y-m-d H:i:s',strtotime("$xcx_login - 1 day"));
            }else{
                $list[$k]['time_out']='';
            }

        }
        $filename = $r_name."登陆核查表".date('YmdHis');
        $header = array ('门店编码','门店名称','用户名','手机号码','app登录时间','小程序登陆时间','是否登陆');
        $widths=array('20','30','20','20','20','20','15');
        if($list) {
            // excelExport($filename, $header, $list, $widths);//生成数据
            export_data($list,$header,$filename);
        }
        die();
    }

    //门店抽奖产品列表
    public function branch_goods(){
        header("Cache-control: private");
        $key = input('key');
        $storeid = input('storeid',0);
        $title = input('title','');
        $map = [];
        if($key&&$key!==""){
            $map['g.name'] = ['like',"%" . $key . "%"];
        }
        $map['g.goods_cate'] = ['eq',6];
        $map['g.storeid'] = ['in',[0,$storeid]];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 1000;// 获取总条数
        $count = Db::name('goods')->alias('g')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $ad = new GoodsModel();
        $lists = $ad->getAll($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists',$lists);
        $branch_draw=Db::name('activity_branch_draw')->where('storeid',$storeid)->value('draw_ids');
        $this->assign('branch_draw',$branch_draw);
        $cate=Db::name('goods_cate')->field('id,name')->select();
        $this->assign('cate',$cate);
        $this->assign('storeid',$storeid);
        $this->assign('title',$title);
        return $this->fetch();
    }

    //门店选择活动开关
    public function activity_goods(){
        if(request()->isAjax()){
            try {
                $join_tk='';
                $param = input('post.');
                if(!empty($param['join_tk'])){
                    $join_tk=implode(',',$param['join_tk']);
                }
                $flag = Db::table('ims_bwk_branch')->where(array('id'=>$param['storeid']))->update(['join_tk'=>$join_tk,'temp_ticket'=>$param['temp_ticket']]);
                return json(['code' => 1, 'data' => $flag['data'], 'msg' => '选择成功']);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '选择失败'.$e->getMessage()]);
            }
        }
        $storeid=input('param.storeid');
        $join_tk= Db::table('ims_bwk_branch')->where(array('id'=>$storeid))->field('join_tk,temp_ticket')->find();
        $this->assign('storeid',$storeid);
        $this->assign('join_tk',$join_tk['join_tk']);
        $this->assign('temp_ticket_val',$join_tk['temp_ticket']);
        $this->assign('temp_ticket_text',$join_tk['temp_ticket']?'无金额券':'50%代金券');
        //获取活动

        $activity=Db::name('activity_list')->where('activity_switch',1)->field('id,name')->select();
        $this->assign('activity',$activity);


        return $this->fetch();
    }

    //存储用户个性化抽奖产品
    public function is_lucky_goods(){
        $storeid=input('param.storeid');
        $ids=input('param.ids');
        try{
            $arr=['storeid'=>$storeid,'draw_ids'=>$ids];
            $check=Db::name('activity_branch_draw')->where('storeid',$storeid)->count();
            if(!$check){
                Db::name('activity_branch_draw')->insert($arr);
            }else{
                Db::name('activity_branch_draw')->where('storeid',$storeid)->update($arr);
            }
            return json(['code' => 1, 'data' => '', 'msg' =>'确认成功']);
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }



    //门店配赠推荐产品列表
    public function branch_give(){
        header("Cache-control: private");
        $key = input('key');
        $cate_id = input('cate_id');
        $storeid = input('storeid',0);
        $title = input('title','');
        $goods_id= input('goods_id',0);
        $map = [];
        if($key&&$key!==""){
            $map['g.name'] = ['like',"%" . $key . "%"];
        }
        $map['g.goods_cate'] = ['in',['4','9']];
        $map['g.storeid'] = ['eq',$storeid];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 1000;// 获取总条数
        $count = Db::name('goods')->alias('g')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $ad = new GoodsModel();
        $lists = $ad->getAll($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists',$lists);
        $branch_draw=Db::name('activity_branch_draw')->where('storeid',$storeid)->value('draw_ids');
        $this->assign('branch_draw',$branch_draw);
        $this->assign('storeid',$storeid);
        $this->assign('title',$title);
        //$this->assign('cate_id',$cate_id);
//        if($goods_id){
//            $this->assign('goods_id',$goods_id);
//            $branch_give=Db::name('goods')->where(['storeid'=>$storeid,'id'=>$goods_id])->value('given');
//            $this->assign('branch_give',$branch_give);
//        }
        return $this->fetch();
    }

    //批量上下架
    public function up_down(){
        $storeid=input('param.id');
        $check=Db::name('goods')->where(['storeid'=>$storeid,'status'=>1,'goods_cate'=>4])->count();
        if($check){
            Db::name('goods')->where(['storeid'=>$storeid,'goods_cate'=>4])->update(['status'=>0]);
            return json(['code' => 3, 'data' => '', 'msg' => '批量下架成功']);
        }else {
            $goodsList = Db::name('goods')->where(['goods_cate' => 4, 'pid' => 0, 'status' => 1])->select();
            try {
                foreach ($goodsList as $k => $v) {
                    $v['create_time'] = time();
                    $v['update_time'] = time();
                    $v['storeid'] = $storeid;
                    $v['stock'] = 999999;
                    $v['given'] = '';
                    $v['allow_buy_num1'] = 99999;
                    $v['pid'] = $v['id'];
                    $check = Db::name('goods')->where(['storeid' => $storeid, 'goods_cate' => 4,'pid' => $v['id']])->count();
                    if (!$check) {
                        unset($v['id']);
                        Db::name('goods')->insert($v);
                    }else{
                        Db::name('goods')->where(['storeid' => $storeid, 'goods_cate' => 4,'pid' => $v['id']])->update(['status'=>1]);
                    }
                }
                return json(['code' => 1, 'data' => '', 'msg' => '批量上架成功']);
            } catch (\Exception $e) {
                return json(['code' => 0, 'data' => '', 'msg' => '批量上架失败' . $e->getMessage()]);
            }
        }
    }


    /**
     * 获取指定日期段内每一天的日期
     * @param  Date  $startdate 开始日期
     * @param  Date  $enddate   结束日期
     * @return Array
     */
    public function getDateFromRange($startdate, $enddate){

        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);

        // 计算日期段内有多少天
        $days = ($etimestamp-$stimestamp)/86400+1;

        // 保存每天日期
        $date = array();

        for($i=0; $i<$days; $i++){
            $date[] = date('Y-m-d', $stimestamp+(86400*$i));
        }
        return $date;
    }


    public function luckybag(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $key = input('key');
        $export = input('export',0);
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $map = [];
        if($key&&$key!=="")
        {
            $map['bwk.title|bwk.sign|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($search_time1!='' && $search_time2!=''){
            $map['p.insert_time'] = array('between', [$search_time1. " 00:00:00" , $search_time2. " 23:59:59"]);
        }
        $map['p.type'] = ['eq','88福袋'];
        $map['orders.pay_status'] = ['eq',1];
        $map['orders.scene'] = ['eq',5];
        $map['orders.channel'] = ['eq','missshop'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('promoter')->alias('p')->join('activity_order orders','p.order_sn=orders.order_sn','left')->join(['ims_bj_shopn_member'=>'member'],'orders.share_uid=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('orders.id')->where($map)->count();
        if($export==1){
            $lists = Db::name('promoter')->alias('p')->join('activity_order orders','p.order_sn=orders.order_sn','left')->join(['ims_bj_shopn_member'=>'member'],'orders.share_uid=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('p.user_id,p.money,orders.uid,orders.share_uid,orders.num,orders.order_sn,orders.pay_price,orders.pay_time,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign')->where($map)->order('p.id desc')->select();
        }else{
            $lists = Db::name('promoter')->alias('p')->join('activity_order orders','p.order_sn=orders.order_sn','left')->join(['ims_bj_shopn_member'=>'member'],'orders.share_uid=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('p.user_id,p.money,orders.uid,orders.share_uid,orders.num,orders.order_sn,orders.pay_price,orders.pay_time,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign')->where($map)->page($Nowpage, $limits)->order('p.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $buy_info=Db::table('ims_bj_shopn_member')->where('id',$v['uid'])->field('mobile,realname')->find();
            $lists[$k]['bsc_name']=Db::table('sys_departbeauty_relation')->alias('departbeauty')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('departbeauty.id_beauty',$v['storeid'])->value('depart.st_department');
            $lists[$k]['cus_title']=$v['title'];
            $lists[$k]['cus_sign']=$v['sign'];
            $lists[$k]['seller_name']=$v['realname'];
            $lists[$k]['seller_tel']=$v['mobile'];
            $lists[$k]['money']=$v['money'];
            $lists[$k]['realname']=$buy_info['realname'];
            $lists[$k]['mobile']=$buy_info['mobile'];
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
            if(!session('get_mobile')){
                $lists[$k]['mobile']=substr_replace($buy_info['mobile'], '****', 3, 4);
                $lists[$k]['seller_tel']=substr_replace($v['mobile'], '****', 3, 4);
            }
        }
        $allpage = intval(ceil($count / $limits));
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['bsc_name'] = $v['bsc_name'];
                $data[$k]['cus_title'] = $v['cus_title'];
                $data[$k]['cus_sign'] = $v['cus_sign'];
                $data[$k]['seller_name'] = $v['seller_name'];
                $data[$k]['seller_tel'] = $v['seller_tel'];
                $data[$k]['money']=$v['money'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['order_sn']=''.$v['order_sn'];
                $data[$k]['num']=$v['num'];
                $data[$k]['pay_price']=$v['pay_price'];
                $data[$k]['pay_time'] = $v['pay_time'];
            }
            $filename = "春节88福袋推广排行列表" . date('YmdHis');
            $header = array('办事处', '门店名称', '门店编码', '推广人名称', '推广人电话','推广积分','购买人姓名','购买人电话','订单编号','订单数量','订单金额','推广日期');
            $widths = array('10', '30', '20', '15', '15', '15', '15', '15', '15', '15', '15');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function luckybag_exchage(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $key = input('key');
        $export = input('export',0);
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $map = [];
        if($key&&$key!=="")
        {
            $map['bwk.title|bwk.sign|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($search_time1!='' && $search_time2!=''){
            $map['p.insert_time'] = array('between', [$search_time1, $search_time2 ]);
        }
        $map['p.type'] = ['eq','88福袋'];
        $map['p.money'] = ['lt',0];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('promoter')->alias('p')->join(['ims_bj_shopn_member'=>'member'],'p.user_id=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('p.user_id,p.money,p.insert_time,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign')->where($map)->count();
        if($export==1){
            $lists = Db::name('promoter')->alias('p')->join(['ims_bj_shopn_member'=>'member'],'p.user_id=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('p.user_id,p.money,p.insert_time,p.action_uid,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign')->where($map)->order('p.id desc')->select();
        }else{
            $lists = Db::name('promoter')->alias('p')->join(['ims_bj_shopn_member'=>'member'],'p.user_id=member.id','left')->join(['ims_bwk_branch'=>'bwk'],'member.storeid=bwk.id','left')->field('p.user_id,p.money,p.insert_time,p.action_uid,member.storeid,member.realname,member.mobile,bwk.title,bwk.sign')->where($map)->order('p.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $action_info=Db::table('ims_bj_shopn_member')->where('id',$v['action_uid'])->field('mobile,realname')->find();
            $lists[$k]['bsc_name']=Db::table('sys_departbeauty_relation')->alias('departbeauty')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('departbeauty.id_beauty',$v['storeid'])->value('depart.st_department');
            $lists[$k]['cus_title'] = $v['title'];
            $lists[$k]['cus_sign'] = $v['sign'];
            $lists[$k]['seller_name'] = $v['realname'];
            $lists[$k]['seller_tel'] = $v['mobile'];
            $lists[$k]['money']=$v['money'];
            $lists[$k]['insert_time']=$v['insert_time'];
            $lists[$k]['realname']=$action_info['realname'];
            $lists[$k]['mobile']=$action_info['mobile'];
        }
        $allpage = intval(ceil($count / $limits));
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['bsc_name'] = $v['bsc_name'];
                $data[$k]['cus_title'] = $v['title'];
                $data[$k]['cus_sign'] = $v['sign'];
                $data[$k]['seller_name'] = $v['realname'];
                $data[$k]['seller_tel'] = $v['mobile'];
                $data[$k]['money']=$v['money'];
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
            }
            $filename = "春节88福袋推广兑换列表" . date('YmdHis');
            $header = array('办事处', '门店名称', '门店编码', '推广人名称', '推广人电话','兑换积分','兑换时间','处理人姓名','处理人电话');
            $widths = array('10', '30', '20', '15', '15', '15', '25', '15', '15');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function luckybag_info(){
        $uid=input('param.uid');
        $score['all'] =0;//推广中积分
        $score['used'] =0;//推广已使用积分
        $score['have'] =0;//推广剩余积分
        $score=Db::name('promoter')->where('user_id',$uid)->column('money');
        if($score){
            $aal_s=0;
            $use_s=0;
            foreach ($score as $v){
                if($v>0){
                    $aal_s+=$v;
                }else{
                    $use_s+=abs($v);
                }
            }
            $score['all'] =$aal_s;//推广中积分
            $score['used'] =$use_s;//推广已使用积分
            $score['have'] =$aal_s-$use_s;//推广已使用积分
        }
        return json(['code' => 1, 'data' => $score, 'msg' => '获取成功']);
    }



    /**
     * 活动列表
     */
    public function activity_list(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['name'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_list')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::name('activity_list')->where($map)->page($Nowpage, $limits)->order('id')->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [roleAdd 添加活动]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function activityAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $param['begin_time']=strtotime($param['begin_time']);
                $param['end_time']=strtotime($param['end_time']);
                unset($param['file']);
                $result =  Db::name('activity_list')->insert($param);
                if(false === $result){
                    $res= ['code' => -1, 'data' => '', 'msg' => '添加活动失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '添加活动成功'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑活动]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function activityEdit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $param['begin_time']=strtotime($param['begin_time']);
                $param['end_time']=strtotime($param['end_time']);
                unset($param['file']);
                $result = Db::name('activity_list')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护活动失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护活动成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $info= Db::name('activity_list')->where('id', $id)->find();
        $info['begin_time']=date('Y-m-d H:i:s',$info['begin_time']);
        $info['end_time']=date('Y-m-d H:i:s',$info['end_time']);
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * [roleEdit 批量操作活动]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function activityAction(){
        if(request()->isAjax()){
            $param = input('post.');
            $id=$param['sel_id'];
            switch ($param['activity_status']){
                case 1:
                    $this->activity_open($id,1);
                    break;
                case 2:
                    $this->activity_close($id,1);
                    break;
                case 3:
                    $this->activity_open($id,2);
                    break;
                case 4:
                    $this->activity_close($id,2);
                    break;
            }
            return json(['code' => 1, 'data' => '', 'msg' =>'成功']);
        }
        $lists =Db::name('activity_list')->field('id,name')->where('poster_cate',1)->select();
        $this->assign('lists',$lists);
        $sel_id = input('param.id');
        $this->assign('sel_id',$sel_id);
        return $this->fetch();
    }


    public function activity_open($id,$flag){
        set_time_limit(0);
        if($flag==2){
            if (!empty($_FILES)) {
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = request()->file('myfile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    $exclePath = $info->getSaveName();  //获取文件名
                    $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                    $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                    //array_shift($excel_array);  //删除标题;
                    $b_data=[];
                    foreach ($excel_array as $k=>$v){
                            $b_data[]=$v[0];
                    }
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }

            $map['b.sign'] = ['in',$b_data];
        }
        $map['m.isadmin'] = ['eq',1];
        $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->select();
        $i=0;
        Db::startTrans();
        foreach ($lists as $kk => $vv) {
            $activitys=0;
            if(!empty($vv['join_tk'])){
                $arr=explode(',',$vv['join_tk']);
                if(!in_array($id,$arr)){
                    $activitys=$vv['join_tk'].','.$id;
                }
            }else{
                $activitys=$id;
            }
            if($activitys){
                if(!$vv['activity_key']){
                    Db::table('ims_bj_shopn_member')->where('mobile',$vv['mobile'])->update(['activity_key'=>1]);
                }
                Db::table('ims_bwk_branch')->where('id',$vv['id'])->update(['join_tk'=>$activitys]);
            }
            if ($i % 500 == 0) {
                Db::commit();
                Db::startTrans();
            }
            $i++;
        }
        Db::commit();
    }


    public function activity_close($id,$flag){
        set_time_limit(0);
        if($flag==2){
            if (!empty($_FILES)) {
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = request()->file('myfile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    $exclePath = $info->getSaveName();  //获取文件名
                    $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                    $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                    //array_shift($excel_array);  //删除标题;
                    $b_data=[];
                    foreach ($excel_array as $k=>$v){
                        $b_data[]=$v[0];
                    }
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }

            $map['b.sign'] = ['in',$b_data];
        }
        $map['m.isadmin'] = ['eq',1];
        $lists = Db::table('ims_bwk_branch')->alias('b')->join(['ims_bj_shopn_member'=>'m'],'b.id=m.storeid')->where($map)->field('b.id,title,sign,realname,mobile,activity_key,join_pg,join_tk')->select();
        $i=0;
        Db::startTrans();
        foreach ($lists as $kk => $vv) {
            if(!empty($vv['join_tk'])){
                $arr=explode(',',$vv['join_tk']);
                $exits=array_search($id,$arr);
                if(($id==$vv['join_tk']) || $exits) {
                    unset($arr[$exits]);
                }
                $activitys=implode(',',$arr);
                Db::table('ims_bwk_branch')->where('id',$vv['id'])->update(['join_tk'=>$activitys]);
                if ($i % 500 == 0) {
                    Db::commit();
                    Db::startTrans();
                }
                $i++;
            }
        }
        Db::commit();
    }

    public function activityExports(){
        set_time_limit(0);
        $id=input('param.id');
        $info =Db::name('activity_list')->where('id',$id)->find();
        $lists = Db::table('ims_bwk_branch')->alias('bwk')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('depart.st_department,bwk.title,bwk.sign,bwk.join_tk')->select();
        $data=[];
        foreach ($lists as $kk => $vv) {
            if(!empty($vv['join_tk'])){
                $exits = explode(',',$vv['join_tk']);
                if(in_array($id,$exits)){
                    $vv['join_tk']=$info['name'];
                    $data[]=$vv;
                }
            }
        }
        $filename = $info['name']."活动开通门店列表".date('YmdHis');
        $header = array ('办事处','门店名称','门店编码','活动名称');
        $widths=array('30','30','30','30');
        if($data) {
            excelExport($filename, $header, $data, $widths);//生成数据
        }
    }



    /**
     * 安心送门店列表
     */
    public function axs_list(){
        $key = input('key');
        $id = input('id');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];
        }
        $map['activity_id'] = ['eq',$id];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('axs_branch')->alias('a')->join(['ims_bwk_branch' => 'b'],'a.store_id=b.id','left')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::name('axs_branch')->alias('a')->join(['ims_bwk_branch' => 'b'],'a.store_id=b.id','left')->join('activity_list l','a.activity_id=l.id','left')->where($map)->page($Nowpage, $limits)->field('a.*,b.title,b.sign,l.name')->order('a.id')->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('id', $id);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [roleAdd 添加安心送门店]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function axsAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $param['insert_time']=time();
                $result =  Db::name('axs_branch')->insert($param);
                if(false === $result){
                    $res= ['code' => -1, 'data' => '', 'msg' => '添加安心送门店失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '添加安心送门店成功'];
                }
            }catch( \Exception $e){
                $res= ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        //获取门店
        $branch=new BranchModel();
        $storeList=$branch->getAllBranch();
        $this->assign('storeList',$storeList);
        $id = input('param.id');
        $a_info=Db::name('activity_list')->field('id,name')->where('id',$id)->find();
        $this->assign('a_info',$a_info);
        return $this->fetch();
    }

    //门店选择安心送产品
    public function activity_axs(){
        if(request()->isAjax()){
            try {
                $param = input('post.');
                Db::name('axs_branch')->where(['store_id'=>$param['storeid']])->delete();
                if($param['goods_id']){
                    $insertData=[];
                    foreach ($param['goods_id'] as $k=>$v){
                        $activity_id=Db::name('goods')->where('id',$v)->value('activity_id');
                        $insertData[]=['store_id'=>$param['storeid'],'activity_id'=>$activity_id,'goods_id'=>$v,'insert_time'=>time()];
                    }
                    Db::name('axs_branch')->insertAll($insertData);
                }
                return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '配置失败'.$e->getMessage()]);
            }
        }
        $storeid=input('param.storeid');
        $axs_goods= Db::name('axs_branch')->where(array('store_id'=>$storeid))->column('goods_id');
        $this->assign('storeid',$storeid);
        $this->assign('axs_goods',$axs_goods);

        //读取活动列表
        $activity_list =Db::name('activity_list')->field('id,name')->where('poster_cate',1)->order('activity_orders')->select();
        foreach ($activity_list as $k=>$v){
            $goods_list=Db::name('goods')->where(['activity_id'=>$v['id']])->where('status',1)->field('id goods_id,name goods_name')->select();
            if($goods_list){
                $activity_list[$k]['goods']=$goods_list;
            }else{
                unset($activity_list[$k]);
            }
        }
        $this->assign('activityList',$activity_list);
        return $this->fetch();
    }
    //安心送列表
    public function axs(){
        header("Cache-control: private");
        ini_set('memory_limit', '-1');
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['bwk.sign|order.order_sn|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $map['channel'] = ['eq','missshop'];
        $map['is_axs'] = ['eq',1];
        $map['pay_status'] = ['eq',1];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_order')->where($map)->field("FROM_UNIXTIME(insert_time,'%Y-%m-%d') days")->group('days')->select();  //总数据
        $allpage = intval(ceil(count($count) / $limits));
        $lists = Db::name('activity_order')->where($map)->field("FROM_UNIXTIME(insert_time,'%Y-%m-%d') days,count(id) count,sum(pay_price) price,is_check")->order('days desc')->group('days')->page($Nowpage, $limits)->select(); ;

//        foreach ($lists as $k=>$v){
//            $map1['pay_status']=array('eq',1);
//            $map1['channel']=array('eq','missshop');
//            $map1['pay_time']=array('between', [strtotime($v['days'] . " 00:00:00"), strtotime($v['days'] . " 23:59:59")]);
//            $lists[$k]['list'] = Db::name('activity_order')->alias('order')->join(['ims_bwk_branch' => 'bwk'],'order.storeid=bwk.id','left')->field('count(order.id) count,sum(order.pay_price) price,order.storeid,bwk.title,bwk.sign,order.erp_sale_no')->where($map1)->group('order.storeid')->select();
//        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    //存储当天门店的erp关联销售单号
    public function save_erp_no(){
        set_time_limit(0);
        $n_date=input('param.n_date');
        $n_id=input('param.n_id');
        $text=input('param.text');
        if($n_date!='' && $n_id!='' && $text!=''){
            $map1['storeid']=array('eq',$n_id);
            $map1['pay_status']=array('eq',1);
            $map1['channel']=array('eq','missshop');
            $map1['pay_time']=array('between', [strtotime($n_date . " 00:00:00"), strtotime($n_date . " 23:59:59")]);
            Db::name('activity_order')->where($map1)->setField('erp_sale_no',$text);
            $data['code'] = 1;
            $data['msg'] = '操作成功';
            return json($data);
        }else{
            $data['code'] = 0;
            $data['msg'] = '操作失败';
            return json($data);
        }
    }
    //财务核对标记
    public function confirm_amount_action(){
        set_time_limit(0);
        $n_date=input('param.n_date');
        $flag=input('param.flag');
        if($n_date!=''){
            $map1['pay_status']=array('eq',1);
            $map1['channel']=array('eq','missshop');
            $map1['pay_time']=array('between', [strtotime($n_date . " 00:00:00"), strtotime($n_date . " 23:59:59")]);
            Db::name('activity_order')->where($map1)->setField('is_check',$flag);
            $data['code'] = 1;
            $data['msg'] = '操作成功';
            return json($data);
        }else{
            $data['code'] = 0;
            $data['msg'] = '操作失败';
            return json($data);
        }
    }

    //导出当天订单
    public function download_branch_order(){
        set_time_limit(0);
        $n_date=input('param.n_date');
        if($n_date!=''){
            $map['pay_status']=array('eq',1);
            $map['channel']=array('eq','missshop');
            $map['is_axs']=array('eq',1);
            $map['pay_time']=array('between', [strtotime($n_date . " 00:00:00"), strtotime($n_date . " 23:59:59")]);
            $lists = Db::name('activity_order')->alias('order')->join('activity_order_info info','order.order_sn=info.order_sn','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->field('order.*,order.flag order_flag,info.good_id,info.good_num,info.good_specs,info.pick_up,info.good_amount,info.main_flag,info.flag info_flag,member.realname,member.mobile,member.staffid,member.activity_flag,info.good_specs_sku')->where($map)->order('order.uid')->select();
            $data=array();
            foreach ($lists as $k => $v) {
                if(!session('get_mobile')){
                    $v['mobile']=substr_replace($v['mobile'], '****', 3, 4);
                }
                $sellerInfo=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'b.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('m.id',$v['fid'])->field('b.title cus_title,b.sign cus_sign,depart.st_department bsc_name,m.mobile seller_tel,m.realname seller_name')->find();
                $data[$k]['st_department']=$sellerInfo['bsc_name'];
                $data[$k]['cus_title']=$sellerInfo['cus_title'];
                $data[$k]['cus_sign']=$sellerInfo['cus_sign'];
                $data[$k]['sellername']=$sellerInfo['seller_name'];
                $data[$k]['sellermobile']=$sellerInfo['seller_tel'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['activity_flag']=$v['activity_flag'];
                $data[$k]['order_sn']=' '.$v['order_sn'];
                $data[$k]['pay_status']=$v['pay_status']?'已支付':'未支付';
                if($v['is_axs']){
                    $data[$k]['pick_type']='安心直邮';
                }else{
                    $data[$k]['pick_type']=$v['pick_type']?'到店取货':'现场取货';
                }
                $data[$k]['scene']=config("activity_list.".$v['scene']);
                if(!$v['order_flag']) {
                    $getName=Db::name('goods')->where('id',$v['pid'])->value('name');
                    $data[$k]['name']=$getName.' '.$v['specs'];
                    $data[$k]['belong'] ='';
                    $data[$k]['num'] = $v['num'];
                    $data[$k]['pay_price'] = $v['pay_price'];
                    $data[$k]['coupon_dsc']='';
                    $data[$k]['specs']='';
                    $data[$k]['order_status']=$v['pick_up']?'已取货':'未取货';
                }else{
                    $n=Db::name('goods')->where('id',$v['good_id'])->field('name,storeid')->find();
                    if($n['storeid']){
                        $belongBranch=Db::table('ims_bwk_branch')->where('id',$n['storeid'])->value('title');
                    }else{
                        $belongBranch='';
                    }
                    $data[$k]['name'] = $v['info_flag']?'买赠：'.$n['name'].$v['good_specs']:$n['name'].$v['good_specs'];
                    if($v['info_flag']){
                        $data[$k]['belong'] = $n['storeid']?$belongBranch:'诚美总部';
                    }else{
                        $data[$k]['belong'] = '';
                    }
                    $data[$k]['num'] = $v['good_num'];
                    $data[$k]['pay_price'] = $v['info_flag']?0:$v['good_amount'];
                    $data[$k]['coupon_dsc']='';
                    $data[$k]['specs']=$v['good_specs_sku'];
                    if($v['main_flag']){
                        if($v['coupon_price']) {
                            $data[$k]['pay_price'] = $v['good_amount'] - $v['coupon_price'];
                            $data[$k]['coupon_dsc'] = $v['coupon_price'] ? '该金额扣减抵用' . $v['coupon_price'] . '现金券一张' : '11';
                        }
                    }
                    $data[$k]['order_status']=$v['order_status']?'已取货':'未取货';
                }
                $data[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
                $data[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
                $data[$k]['transaction_id']='`'.$v['transaction_id'];
                //获取收货信息
                $delivery=Db::name('activity_order_address')->where('order_sn',trim($v['order_sn']))->find();
                $data[$k]['d_consignee']=$delivery['consignee'];
                $data[$k]['d_mobile']=$delivery['mobile'];
                $data[$k]['d_address']=$this->getNameByParentId($delivery['province']).$this->getNameByParentId($delivery['city']).$this->getNameByParentId($delivery['district']).$this->getNameByParentId($delivery['street']).$delivery['address'];
            }
            $filename = $n_date."安心送订单列表";
            $header = array ('办事处','门店名称','门店编码','美容师名称','美容师电话','顾客姓名','顾客电话','顾客标识码','活动订单号','支付状态','取货方式','订单类型','购买产品','产品提供','购买数量','订单金额','抵扣信息','规格型号','取货状态','订单创建时间','订单支付时间','支付流水号','收货人','收货手机','收货地址');
            $widths=array('10','30','20','15','15','15','15','15','50','30','30','30','30','30','30','30','30','30','30','30','50','50','20','30','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }else{
            $data['code'] = 0;
            $data['msg'] = '操作失败';
            return json($data);
        }
    }

    /**
     * Notes:物流单导入
     * User: HOUDJ
     * Date: 2020/5/7
     * Time: 11:17
     * @return mixed|\think\response\Json
     */
    public function deliveryImport(){
        if(request()->isAjax()){
            if (!empty($_FILES)) {
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = request()->file('myfile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    $exclePath = $info->getSaveName();  //获取文件名
                    $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                    $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                    array_shift($excel_array);  //删除标题;
                    $data=[];
                    foreach ($excel_array as $k=>$v){
                            $data['express_name'] = trim($v[1]);
                            $data['express_code'] = trim($v[2]);
                            $data['express_number'] = trim($v[3]);
                            Db::name('activity_order_address')->where('order_sn',trim($v[0]))->update($data);
                    }
                    $flag['code'] = 1;
                    $flag['data'] = '';
                    $flag['msg'] = '成功';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }
        }
        return $this->fetch();
    }

    public function getNameByParentId($id){
        return Db::table('sys_region')->where(['id'=>$id])->value('name');
    }

    /**
     * Notes:手动解决异常
     * User: HOUDJ
     * Date: 2020/5/6
     * Time: 17:40
     */
    public function abnormalResolve(){
        $id=input('param.id');
        Db::name('activity_order')->where('id',$id)->update(['u8_flag'=>0,'u8_err'=>'']);
        return json(['code' =>1, 'data' => '', 'msg' => '操作成功']);
    }



}
