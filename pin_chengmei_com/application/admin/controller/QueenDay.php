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

class QueenDay extends Base
{

    //女王节活动配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            if(time() < strtotime($param['begin_time'])){
                $array=array('activity_status'=>$param['activity_status'],'boos_status'=>$param['boos_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'price'=>$param['price'],'price1'=>$param['price1'],'pay_aead_time'=>$param['pay_aead_time']);
                Db::table('ims_bj_shopn_member')->where('isadmin',1)->update(['activity_key'=>$param['boos_status']]);
            }else{
                $array=array('activity_status'=>$param['activity_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'price'=>$param['price'],'price1'=>$param['price1'],'pay_aead_time'=>$param['pay_aead_time']);
            }
            Db::name('queen_day_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $yearConfig=Db::name('queen_day_config')->where('id',1)->find();
        $yearConfig['begin_time']=date('Y-m-d H:i:s',$yearConfig['begin_time']);
        $yearConfig['end_time']=date('Y-m-d H:i:s',$yearConfig['end_time']);
        $this->assign('yearConfig',$yearConfig);
        return $this->fetch();
    }

    /**
     * [index 女王节列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function order(){

        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['order.pay_price|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $map['order.channel'] = ['eq','queenday'];
        $count = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->order('order.id desc')->select();
        }else{
            $lists = Db::name('activity_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->order('order.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['uid']=$v['storeid'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$v['staffid'])->field('mobile,realname')->find();
                $data[$k]['sellername']=$sellerInfo['realname'];
                $data[$k]['sellermobile']=$sellerInfo['mobile'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['order_sn']="\t".$v['order_sn'];
                $data[$k]['pay_status']=$v['pay_status']?'已支付':'未支付';
                $data[$k]['pay_price']=$v['pay_price'];
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['pay_time']=$v['pay_time'];
                $data[$k]['activity_flag']=$v['activity_flag'];
            }
            $filename = "38女王活动顾客订单列表".date('YmdHis');
            $header = array ('办事处','门店id','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话','活动订单号','支付状态','支付金额','订单创建时间','订单支付时间','顾客标识');
            $widths=array('10','20','30','20','15','15','15','15','30','30','30','30','30','30');
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

    public function switch_log(){
        $list=Db::name('activity_switch_log')->alias('log')->join(['ims_bj_shopn_member' => 'member'],'log.uid=member.id','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.flag,log.insert_time,member.realname,member.mobile,depart.st_department,bwk.title,bwk.sign')->order('log.insert_time')->select();
        if(count($list) && is_array($list)){
            $data=array();
            foreach ($list as $k=>$v){
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['flag']=$v['flag']?'打开活动':'关闭活动';
                $data[$k]['time']=date('Y-m-d H:i:s',$v['insert_time']);
            }
            $filename = "店老板打开活动开关日志".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','店老板姓名','店老板电话','开关动作','开关时间');
            $widths=array('10','30','20','15','15','30','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
    }


   public function ordercheck(){
       $store_id = input('store_id');
       $export = input('export',0);
       $map = [];
       if($store_id && $store_id!==""){
           $map['list.storeid'] = array('eq',$store_id);
       }
       $map['list.back_money']=array('eq',0);
       $map['list.pay_status']=array('eq',1);
       $map['list.channel'] = ['eq','queenday'];
       $Nowpage = input('get.page') ? input('get.page'):1;
       $limits = config('list_rows');// 获取总条数
       $count = Db::name('activity_order')->alias('list')->where($map)->group('list.storeid')->select();//计算总页面 不知道为什么group 和count为啥不能同时使用了 暂用select
       $count=count($count);
       $allpage = intval(ceil($count / $limits));
       $lists = Db::table('ims_bwk_branch')->alias('b')->join('activity_order list','b.id=list.storeid')->field('b.id,b.title,b.sign,count(list.id) count,sum(list.pay_price) total')->where($map)->page($Nowpage, $limits)->group('list.storeid')->select();//计算总页面
       if($export){
           $exportLists = Db::table('ims_bwk_branch')->alias('b')->join('activity_order list','b.id=list.storeid')->field('b.id,b.title,b.sign,count(list.id) count,sum(list.pay_price) total')->where($map)->page($Nowpage, 1000000)->group('list.storeid')->select();//计算总页面
       }
       foreach ($lists as $k=>$v){
           $lists[$k]['total']=sprintf("%1.2f",$v['total']);
           $lists[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['id'])->value('d.st_department');
           if(strlen($v['sign'])>=7){
               $mainSign=substr($v['sign'],0,7);
           }else{
               $mainSign=$v['sign'];
           }
           $bankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->count();
           $lists[$k]['bank_info']=$bankInfo?"<span class='label label-primary'>已维护</span>":"<span class='label label-default'>未维护</span>";

       }
       //导出
       if($export){
           $data=array();
           foreach ($exportLists as $k => $v) {
               if(strlen($v['sign'])>=7){
                   $mainSign=substr($v['sign'],0,7);
               }else{
                   $mainSign=$v['sign'];
               }
               $data[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['id'])->value('d.st_department');
               $data[$k]['sign']=$mainSign;
               $data[$k]['title']=$v['title'];
               $bankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->count();
               $data[$k]['bank_info']=$bankInfo?"已维护":"未维护";
               $data[$k]['count']=$v['count'];
               $data[$k]['total']=sprintf("%1.2f",$v['total']);
           }
           $filename = "待返款门店列表".date('YmdHis');
           $header = array ('所属办事处','门店编码','门店名称','打款信息','待返单数','待返金额');
           $widths=array('20','20','20','30','30','30');
           if($data) {
               excelExport($filename, $header, $data, $widths);//生成数据
           }
           die();
       }
       $this->assign('Nowpage', $Nowpage); //当前页
       $this->assign('allpage', $allpage); //总页数
       $this->assign('store_id', $store_id);
       $branchList = Db::table('ims_bwk_branch')->alias('b')->join('activity_order list','b.id=list.storeid')->field('b.id,b.title,b.sign')->where($map)->group('list.storeid')->select();//计算总页面
       $this->assign('branchList',$branchList);
       if(input('get.page')){
           return json($lists);
       }
       return $this->fetch();
   }

    /**
     * [order_list 待返款详情]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function order_list(){
        $storeid = input('storeid');
        $map = [];
        $map['order.storeid'] = ['eq',$storeid];
        $map['order.pay_status']=array('eq',1);
        $map['order.back_money']=array('eq',0);
        $map['order.channel'] = ['eq','queenday'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_order')->alias('order')->where($map)->join('pay_log log','order.transaction_id=log.transaction_id','left')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('activity_order')->alias('order')->where($map)->field('order.id,order.order_sn,order.pay_time,order.insert_time,order.pay_price,log.pay_check,member.realname,member.mobile,bwk.title,bwk.sign')->join('pay_log log','order.transaction_id=log.transaction_id','left')->page($Nowpage, $limits)->order('order.id desc')->join(['ims_bj_shopn_member' => 'member'], 'member.id=order.uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('storeid',$storeid);
        $this->assign('count',$count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    //销售申请财务返款
    public function apply_refund(){
        $refund=input('param.refund');
        try{
            $result =  Db::name('activity_order')->where('id','in',$refund)->update(['back_money' => 1,'back_money_time1'=>time()]);
            if(false === $result){
                return json(['code' => 0, 'data' => '', 'msg' =>'申请返款失败']);
            }else{
                return json(['code' => 1, 'data' =>'', 'msg' => '申请返款成功']);
            }
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }

    /*
     * 财务审核列表
     */
    public function financecheck(){
        $store_id = input('store_id');
        $map = [];
        if($store_id && $store_id!==""){
            $map['list.storeid'] = ['eq',$store_id];
        }
        $map['list.back_money']=array('eq',1);
        $map['list.pay_status']=array('eq',1);
        $map['list.channel'] = ['eq','queenday'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::name('activity_order')->alias('list')->where($map)->group('list.storeid')->select();//计算总页面 不知道为什么group 和count为啥不能同时使用了 暂用select
        $count=count($count);
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('ims_bwk_branch')->alias('b')->join('activity_order list','b.id=list.storeid')->field('b.id,b.title,b.sign,count(list.id) count,sum(list.pay_price) total')->where($map)->page($Nowpage, $limits)->group('list.storeid')->select();//计算总页面
        foreach ($lists as $k=>$v){
            $lists[$k]['total']=sprintf("%1.2f",$v['total']);
            $getSign=Db::table('ims_bwk_branch')->where('id',$v['id'])->value('sign');
            if($getSign){
                if(strlen($getSign)>=7){
                    $mainSign=substr($getSign,0,7);
                }else{
                    $mainSign=$getSign;
                }
            }
            $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
            if($getBankInfo){
                $BankInfo='客户简称：'.$getBankInfo['b_name'].'　银行开户人：'.$getBankInfo['payee'].' 　开户银行：'.$getBankInfo['bankname'].' 　银行号码：'.$getBankInfo['bankcard'];
            }else{
                $BankInfo='暂无维护打款银行卡信息';
            }
            $lists[$k]['bankInfo']=$BankInfo;
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('store_id', $store_id);
        $this->assign('start', date("Y-m-d", strtotime("-1 day")));
        $this->assign('end', date("Y-m-d", strtotime("-1 day")));
        $branchList = Db::table('ims_bwk_branch')->alias('b')->join('activity_order list','b.id=list.storeid')->field('b.id,b.title,b.sign')->where($map)->group('list.storeid')->select();//计算总页面
        $this->assign('branchList',$branchList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * [progress 财务确认返款详情]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function finance_order_list(){
        $key = input('key');
        $storeid = input('storeid');
        $map = [];
        if($key&&$key!==""){
            $map['order.p_name'] = ['like',"%" . $key . "%"];
        }
        $map['order.storeid'] = ['eq',$storeid];
        $map['order.pay_status']=array('eq',1);
        $map['order.back_money']=array('eq',1);
        $map['order.channel'] = ['eq','queenday'];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_order')->alias('order')->where($map)->join('pay_log log','order.transaction_id=log.transaction_id','left')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('activity_order')->alias('order')->where($map)->field('order.id,order.order_sn,order.pay_time,order.insert_time,order.pay_price,log.pay_check,member.realname,member.mobile,bwk.title,bwk.sign')->join('pay_log log','order.transaction_id=log.transaction_id','left')->page($Nowpage, $limits)->order('order.id desc')->join(['ims_bj_shopn_member' => 'member'], 'member.id=order.uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('storeid',$storeid);
        $this->assign('count',$count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    //财务确认返款操作
    public function finance_confrim(){
        $refund=input('param.refund');
        try{
            $result =  Db::name('activity_order')->where('id','in',$refund)->update(['back_money' => 2,'back_money_time2'=>time()]);
            if(false === $result){
                return json(['code' => 0, 'data' => '', 'msg' =>'返款确认失败']);
            }else{
                return json(['code' => 1, 'data' =>'', 'msg' => '返款确认成功']);
            }
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }


    //财务返款
    public function finance_download(){
        $ids=input('param.ids');
        $storeid=input('param.storeid');
        try{
            $data=array();
            $map['order.id']=array('in',$ids);
            $map['order.channel']=array('eq','queenday');

            $lists = Db::name('activity_order')->alias('order')->where($map)->field('order.id,order.order_sn,order.pay_time,order.insert_time,order.pay_price,log.pay_check,member.realname,member.mobile,bwk.title,bwk.sign')->join('pay_log log','order.transaction_id=log.transaction_id','left')->order('order.id desc')->join(['ims_bj_shopn_member' => 'member'], 'member.id=order.uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->select();
            //获取门店编码
            $getSign=Db::table('ims_bwk_branch')->where('id',$storeid)->value('sign');
            if($getSign){
                if(strlen($getSign)>=7){
                    $mainSign=substr($getSign,0,7);
                }else{
                    $mainSign=$getSign;
                }
            }
            $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
            foreach ($lists as $k=>$v){
                $data[$k]['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
                $data[$k]['sign']=$v['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['department']=$getBankInfo['department'];
                $data[$k]['bankname']=$getBankInfo['bankname'];
                $data[$k]['payee']=$getBankInfo['payee'];
                $data[$k]['bankcard']="\t".$getBankInfo['bankcard']."\t";
                $data[$k]['pay_check']=$v['pay_check']?'已核对':'未核对';
                $data[$k]['pay_price']=$v['pay_price'];
            }
            $filename = "活动财务线下返款列表".date('YmdHis');
            $header = array ('订单完成时间','门店编码','门店名称','所属办事处','开户行名称','开户人','银行卡号','是否对账','返款金额');
            $widths=array('15','20','20','20','20','20','20','20');
            excelExport($filename,$header,$data,$widths);//生成数据
            return json(['code' => 1, 'data' => '', 'msg' =>'11']);
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }


    //财务返款
    public function finance_download_by_branch(){
        $ids=input('param.ids');
        try{
            $data=array();
            $map['order.storeid']=array('in',$ids);
            $map['order.back_money']=array('eq',1);
            $map['order.channel']=array('eq','queenday');
            $lists = Db::name('activity_order')->alias('order')->where($map)->field('order.id,order.order_sn,order.pay_time,order.insert_time,order.pay_price,order.storeid,bwk.title,bwk.sign,count(order.id) count,sum(order.pay_price) total_price')->order('order.id desc')->join(['ims_bwk_branch' => 'bwk'], 'order.storeid=bwk.id', 'left')->group('order.storeid')->select();
            foreach ($lists as $k=>$v){
                Db::name('activity_order')->where(['storeid'=>$v['storeid'],'back_money'=>1,'channel'=>'queenday'])->update(['back_money' => 2,'back_money_time2'=>time()]);
                //获取门店编码
                $getSign=Db::table('ims_bwk_branch')->where('id',$v['storeid'])->value('sign');
                if($getSign){
                    if(strlen($getSign)>=7){
                        $mainSign=substr($getSign,0,7);
                    }else{
                        $mainSign=$getSign;
                    }
                }
                $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
                $data[$k]['sign']=$v['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['department']=$getBankInfo['department'];
                $data[$k]['bankname']=$getBankInfo['bankname'];
                $data[$k]['payee']=$getBankInfo['payee'];
                $data[$k]['bankcard']="\t".$getBankInfo['bankcard']."\t";
                $data[$k]['pay_price']=$v['pay_price'];
                $data[$k]['count']=$v['count'];
                $data[$k]['total']=$v['total_price'];
            }
            $filename = "活动财务线下返款列表".date('YmdHis');
            $header = array ('门店编码','门店名称','所属办事处','开户行名称','开户人','银行卡号','每单金额','返款单数','返款金额');
            $widths=array('15','20','20','20','20','20','20','20','20');
            excelExport($filename,$header,$data,$widths);//生成数据
            return json(['code' => 1, 'data' => '', 'msg' =>'11']);
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }
    /*
     * 活动门店列表
     */
    public function activity_branch(){
        header("Cache-control: private");
        $export = input('export',0);
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['bwk.title|bwk.sign|depart.st_department'] = ['like',"%" . $key . "%"];
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('activity_branch')->alias('b')->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')->field('b.id')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        if($export) {
            $lists = Db::name('activity_branch')->alias('b')->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')->field('b.id,b.storeid,bwk.title,bwk.sign,b.limit_num,b.ticket,depart.st_department')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->where($map)->order('b.id desc')->select();
        }else{
            $lists = Db::name('activity_branch')->alias('b')->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')->field('b.id,b.storeid,bwk.title,bwk.sign,b.limit_num,b.ticket,depart.st_department')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->where($map)->page($Nowpage, $limits)->order('b.id desc')->select();
        }

        if(count($lists)){
            foreach ($lists as $k=>$v){
                $lists[$k]['needPay']=Db::name('activity_order')->where(['pay_status'=>0,'storeid'=>$v['storeid']])->count();
                $lists[$k]['ticketNum']=Db::name('ticket_user')->where(['type'=>5,'storeid'=>$v['storeid'],'source'=>0])->count();
                $num=self::$redis->get('activity_branch'.$v['storeid']);
                $lists[$k]['redisNum']=$num?$num:0;
				$lists[$k]['number']=$v['limit_num']-$lists[$k]['ticketNum'];
				$lists[$k]['ticket']=$v['ticket']?'抽奖券':'抽奖券/代金券/闺蜜券';
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['storeid']=$v['storeid'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['limit_num']=$v['limit_num'];
                $data[$k]['ticketNum']=$v['ticketNum'];
                $data[$k]['redisNum']=$v['redisNum'];
                $data[$k]['needPay']=$v['needPay'];
                $data[$k]['number']=$v['number'];
            }
            $filename = "38门店抽奖券剩余额度列表".date('YmdHis');
            $header = array ('办事处','门店id','门店名称','门店编码','分配数量','实际使用','实际占用','即将失效','剩余数量');
            $widths=array('10','10','30','20','15','15','15','15','15');
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

    /**
     * [roleAdd 添加活动门店信息]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function activity_branch_add(){
        if(request()->isAjax()){
            $param = input('post.');
			unset($param['file']);
            try{
				if($param['pic']==''){
                    unset($param['pic']);
                }
                $check=Db::name('activity_branch')->where('storeid',$param['storeid'])->count();
                if(!$check) {
                    $result = Db::name('activity_branch')->insert($param);
                    if (false === $result) {
                        $res = ['code' => -1, 'data' => '', 'msg' => '添加活动门店失败'];
                    } else {
                        $res = ['code' => 1, 'data' => '', 'msg' => '添加活动门店成功'];
                    }
                }else{
                    $res = ['code' => -1, 'data' => '', 'msg' => '添加活动门店失败,已存在'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $branchList = Db::table('ims_bwk_branch')->field('id,title,sign')->select();//计算总页面
        $this->assign('branchList',$branchList);
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑活动门店号]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function activity_branch_edit(){
        if(request()->isAjax()){
            $param = input('post.');
			unset($param['file']);
            try{
                $result = Db::name('activity_branch')->where('id', $param['id'])->update($param);
                if (false === $result) {
                    $res = ['code' => 0, 'data' => '', 'msg' => '维护活动门店失败'];
                } else {
                    $res = ['code' => 1, 'data' => '', 'msg' => '维护活动门店成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $page = input('param.page');
        $info= Db::name('activity_branch')->where('id', $id)->find();
        $this->assign('branch',$info);
        $branchList = Db::table('ims_bwk_branch')->field('id,title,sign')->select();//计算总页面
        $this->assign('branchList',$branchList);
        $this->assign('page',$page);
        return $this->fetch();
    }

    /**
     * [roleDel 删除活动门店信息]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function activity_branch_del(){
        $id = input('param.id');
        try{
            Db::name('activity_branch')->where('id', $id)->delete();
            $res= ['code' => 1, 'data' => '', 'msg' => '删除活动门店成功'];
        }catch( \PDOException $e){
            $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
    }



    //活动门店导入
    public function activity_branch_import(){
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
                        $check=Db::name('activity_branch')->where(['storeid'=>$v[0]])->count();
                        if(!$check){
                            $data['storeid'] = $v[0];
                            $data['limit_num'] = $v[1];
                            Db::name('activity_branch')->insert($data);
                        }else{
                            $errData[]=$v[0];
                        }
                    }
                    if(count($errData)>0){
                        $flag['code'] = 0;
                        $flag['data'] = implode(',',$errData);
                        $flag['msg'] = '部分活动门店重复';
                    }else{
                        $flag['code'] = 1;
                        $flag['data'] = '';
                        $flag['msg'] = '成功';
                    }
                    unset($insertmobile);
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

    public function update_ticket_num(){
        $list=Db::name('ticket_user')->field('storeid,count(id) count')->where(['type'=>5,'source'=>0])->group('storeid')->select();
        try {
            if (count($list) && is_array($list)) {
                foreach ($list as $k => $v) {
                    self::$redis->set('activity_branch' . $v['storeid'], $v['count']);
                }
            }
            return json(['code' => 1, 'data' => '', 'msg' =>'更新成功']);
        }catch (\Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>'失败'.$e->getMessage()]);
        }

    }

}
