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

class Pks extends Base
{

    //活动配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array=array('activity_status'=>$param['activity_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'price'=>$param['price']);
            Db::name('new_year_config')->where('id',2)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $a_config=Db::name('new_year_config')->where('id',2)->find();
        $a_config['begin_time']=date('Y-m-d H:i:s',$a_config['begin_time']);
        $a_config['end_time']=date('Y-m-d H:i:s',$a_config['end_time']);
        $this->assign('a_config',$a_config);
        if(strlen($a_config['branch_list'])){
            $branch=Db::table('ims_bwk_branch')->where('id','in',$a_config['branch_list'])->field('id,title,sign')->select();
        }else{
            $branch=[];
        }
        $this->assign('branch',$branch);
        return $this->fetch();
    }


    /*
     * 导入
     */
    public function import(){
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
                    $errData=[];
                    foreach ($excel_array as $k=>$v){
                        $bid=Db::table('ims_bwk_branch')->where('sign',$v[0])->value('id');
                        if($bid){
                            $data[] = $bid;
                        }else{
                            $errData[]=$v[0];
                        }
                    }
                    if(count($errData)>0){
                        $flag['code'] = 0;
                        $flag['data'] = implode(',',$errData);
                        $flag['msg'] = '部分活动门店不存在';
                    }else{
                        Db::name('new_year_config')->where('id',2)->update(['branch_list'=>implode(',',$data)]);
                        $flag['code'] = 1;
                        $flag['data'] = '';
                        $flag['msg'] = '成功';
                    }
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }
        }
    }



    /**
     * [reg 签到]
     */
    public function reg(){
        header("Cache-control: private");
        $key = input('key');
        $export = input('export',0);
        $room_num = input('room_num',0);
        $map=[];
        if($key && $key!=="")
        {
            $map['bsc_name|cus_sign|cus_title|seller_name|seller_tel|room_num'] = ['like',"%" . $key . "%"];
        }
        if($room_num && $room_num!=0){
            if($room_num==1){
                $map['room_num'] = ['exp', Db::raw('is null')];
            }else{
                $map['room_num'] = ['exp', Db::raw('is not null')];
            }
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数
        $count = Db::name('pk_reg')->where($map)->count();  //总数据
        $price1 = Db::name('pk_reg')->where($map)->where('money','neq','-1')->sum('money');  //价格
        $price2 = Db::name('pk_reg')->where($map)->where('money','eq','-1')->sum('other_money');  //价格
        $price=$price1+$price2;
        $allpage = intval(ceil($count / $limits));
        if($export) {
            $lists = Db::name('pk_reg')->where($map)->order('insert_time desc')->select();
        }else{
            $lists = Db::name('pk_reg')->where($map)->page($Nowpage, $limits)->order('insert_time desc')->select();
        }
        foreach ($lists as $k => $v) {
            $lists[$k]['room_num']= $v['room_num']?$v['room_num']:"未分配";
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['bsc_name']=$v['bsc_name'];
                $data[$k]['cus_sign']=$v['cus_sign'];
                $data[$k]['cus_title']=$v['cus_title'];
                $data[$k]['seller_name']=$v['seller_name'];
                $data[$k]['seller_sex']=$v['seller_sex'];
                $data[$k]['seller_tel']=$v['seller_tel'];
                $data[$k]['room_num']=$v['room_num'];
                if($v['money']==-1){
                    $data[$k]['money']=$v['other_money'];
                }else{
                    $data[$k]['money']=$v['money'];
                }
                $data[$k]['back_date']=$v['back_date'];
                $data[$k]['back_station']=$v['back_station'];
                $data[$k]['insert_time']=$v['insert_time'];
            }
            $filename = "2019美容师pk签到信息".date('YmdHis');
            $header = array ('办事处','门店编码','门店名称','美容师姓名','美容师性别','美容师电话','入住房间号','预缴费用','返程时间','返程车站','签到时间');
            $widths=array('10','10','20','10','5','20','15','10','25','20','25');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('count', $count);
        $this->assign('price', $price);
        $this->assign('room_num', $room_num);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    public function edit_reg(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::name('pk_reg')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '签到编辑失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '签到编辑成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $info= Db::name('pk_reg')->where('id', $id)->find();
        $this->assign('info',$info);
        $bsc=Db::name('pk_store')->group('bsc_name')->select();
        $this->assign('bsc',$bsc);
        return $this->fetch();
    }


    public function del(){
        $id = input('param.id');
        try{
            Db::name('pk_reg')->where('id',$id)->delete();
            $res= ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( \PDOException $e){
            $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json($res);
    }


    public function room(){
        if(request()->isAjax()){
            $id = input('param.id');
            $seller_tel = input('param.seller_tel');
            $seller_name = input('param.seller_name');
            $room_num = input('param.room_num');
            $is_message = input('param.is_message');
            if(strlen($room_num)){
                $res=Db::name('pk_reg')->where('id',$id)->update(['room_num'=>$room_num]);
                if($res){
                    if($is_message=='on'){
                        $send=['seller_name'=>$seller_name,'number'=>$room_num];
                        sendMessage($seller_tel,$send,103);
                    }
                }
                return json(['code' =>1, 'data' => '', 'msg' => '房间分配成功！']);
            }else{
                return json(['code' => 0, 'data' => '', 'msg' =>'房间号不允许为空']);
            }
        }
        $uid=input('param.uid');
        $info=Db::name('pk_reg')->where('id',$uid)->field('id,seller_name,seller_tel,cus_sign,cus_title,room_num')->find();
        $this->assign('uinfo',$info);
        return $this->fetch();
    }

    public function money(){
        if(request()->isAjax()){
            $id = input('param.id');
            $money = input('param.money');
            $other_money = input('param.other_money');
            if($money==-1){
                $m=$other_money;
            }else{
                $m=0;
            }
            Db::name('pk_reg')->where('id',$id)->update(['money'=>$money,'other_money'=>$m]);
            return json(['code' =>1, 'data' => '', 'msg' => '预缴登记成功！']);
        }
        $uid=input('param.uid');
        $info=Db::name('pk_reg')->where('id',$uid)->field('id,seller_name,seller_tel,cus_sign,cus_title,money,other_money')->find();
        $this->assign('uinfo',$info);
        return $this->fetch();
    }


    /**
     * [index 订单列表]
     */
    public function orders(){
        header("Cache-control: private");
        $key = input('key');
        $sale_uid = input('sale_uid');
        $pay_status = input('pay_status',88);
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['order.order_sn|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($sale_uid && $sale_uid!=="")
        {
            $map['order.sales_uid'] = ['eq',$sale_uid];
        }
        if($pay_status!=88){
            if($pay_status)
            {
                $map['order.pay_status'] = ['eq',1];
            }else{
                $map['order.pay_status'] = ['eq',0];
            }
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('pk_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::name('pk_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->order('order.id desc')->select();
        }else{
            $lists = Db::name('pk_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->order('order.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $sellerInfo=Db::name('pk_reg')->where('uid',$v['sales_uid'])->field('bsc_name,cus_sign,cus_title,seller_tel,seller_name')->find();
            if(!$sellerInfo || strlen($sellerInfo['cus_sign']<7)){
                $sellerInfo=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'b.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('m.id',$v['sales_uid'])->field('b.title cus_title,b.sign cus_sign,depart.st_department bsc_name,m.mobile seller_tel,m.realname seller_name')->find();
            }
            $lists[$k]['order_price']=$v['pay_price']?$v['pay_price']:0;
            $lists[$k]['sellername']=$sellerInfo['seller_name'];
            $lists[$k]['sellermobile']=$sellerInfo['seller_tel'];
            $lists[$k]['bsc_name']=$sellerInfo['bsc_name'];
            $lists[$k]['cus_sign']=$sellerInfo['cus_sign'];
            $lists[$k]['cus_title']=$sellerInfo['cus_title'];
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['st_department']=$v['bsc_name'];
                $data[$k]['title']=$v['cus_title'];
                $data[$k]['sign']=$v['cus_sign'];
//                $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$v['sales_uid'])->field('mobile,realname')->find();
                $data[$k]['sellername']=$v['sellername'];
                $data[$k]['sellermobile']=$v['sellermobile'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['order_sn']=' '.$v['order_sn'];
                $data[$k]['pay_status']=$v['pay_status']?'已支付':'未支付';
                $data[$k]['pay_price']=$v['pay_price'];
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['pay_time']=$v['pay_time'];
                $data[$k]['transaction_id']=' '.$v['transaction_id'];
                $data[$k]['out_trade_no']=' '.$v['out_trade_no'];
            }
            $filename = "2019扫街活动订单列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','美容师名称','美容师电话','顾客姓名','顾客电话','活动订单号','支付状态','订单金额','订单创建时间','订单支付时间','支付流水号','商户订单号');
            $widths=array('10','30','20','15','15','15','15','30','30','30','30','30','30','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('sale_uid', $sale_uid);
        $this->assign('pay_status', $pay_status);
        $seller=Db::name('pk_reg')->alias('reg')->join('pk_order o','reg.uid=o.sales_uid')->field('reg.uid,reg.seller_name')->group('reg.uid')->select();
        $this->assign('seller', $seller);
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
        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['r.bsc_name|r.cus_sign|r.cus_title|r.seller_name|r.seller_tel'] = ['like',"%" . $key . "%"];
        }
        $map['orders.pay_status'] = ['eq',1];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('pk_order')->alias('orders')->join('pk_reg r','orders.sales_uid=r.uid','left')->field('orders.*,r.*,count(orders.id) count,FORMAT(sum(orders.pay_price),2) price')->where($map)->group('orders.sales_uid')->order('orders.id desc')->select();
        if($export){
            $lists = Db::name('pk_order')->alias('orders')->join('pk_reg r','orders.sales_uid=r.uid','left')->field('orders.*,r.*,count(orders.id) count,FORMAT(sum(orders.pay_price),2) price')->where($map)->group('orders.sales_uid')->order('orders.id desc')->select();
        }else{
            $lists = Db::name('pk_order')->alias('orders')->join('pk_reg r','orders.sales_uid=r.uid','left')->field('orders.*,r.*,count(orders.id) count,FORMAT(sum(orders.pay_price),2) price')->where($map)->page($Nowpage, $limits)->group('orders.sales_uid')->order('count desc')->select();
        }

        foreach ($lists as $k=>$v){
            if($v['seller_name']=='' && $v['seller_tel']==''){
                $info=Db::table('ims_bj_shopn_member')->alias('member')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('member.id',$v['sales_uid'])->field('depart.st_department,bwk.title,bwk.sign,member.realname,member.mobile')->find();
                $lists[$k]['bsc_name']=$info['st_department'];
                $lists[$k]['cus_title']=$info['title'];
                $lists[$k]['cus_sign']=$info['sign'];
                $lists[$k]['seller_name']=$info['realname'];
                $lists[$k]['seller_tel']=$info['mobile'];
            }
        }
        $allpage = intval(ceil(count($count) / $limits));
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
                $data[$k]['price']=$v['price'];
            }
            $filename = "2019美容师扫街排行列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','美容师名称','美容师电话','推广订单数','订单总金额');
            $widths=array('10','30','20','15','15','15','15');
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





}
