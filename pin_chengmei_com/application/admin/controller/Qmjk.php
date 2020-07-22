<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2019/7/11
 * Time: 10:15
 */

namespace app\admin\controller;


use think\Db;
use think\Debug;

class Qmjk extends Base
{

    //全民集客配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array=array('appId'=>$param['appId'],'appSecret'=>$param['appSecret'],'flag'=>$param['flag'],'superAdmin'=>$param['superAdmin']);
            Db::name('qmjk_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $config=Db::name('qmjk_config')->where('id',1)->find();
        $this->assign('config',$config);
        return $this->fetch();
    }


    public function lists(){
        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key && $key!=="")
        {
            $map['title|name|sign|mobile'] = ['like',"%" . trim($key) . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('qmjk_branch')->where($map)->count();  //总数据
        $allpage = intval($count/ $limits);
        if($export){
            $lists = Db::name('qmjk_branch')->where($map)->order('insert_time desc')->select();
        }else{
            $lists = Db::name('qmjk_branch')->where($map)->page($Nowpage, $limits)->order('insert_time desc')->select();
        }
        foreach ($lists as $k => $v) {
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
            $report=Db::name('qmjk_branch_report')->where('branch_id',$v['id'])->find();
            if(is_array($report) && count($report)){
                $lists[$k]['report']= $report;
            }else{
                $lists[$k]['report']['report_date']=date('Y-m-d',strtotime('-1 day'));
                $lists[$k]['report']['union_total']= "0";
                $lists[$k]['report']['customer_total']= "0";
                $lists[$k]['report']['customer_pay_total']= "0";
                $lists[$k]['report']['customer_order_total']="0";
                $lists[$k]['report']['order_avg']= "0";
                $lists[$k]['report']['conversion_rate']= "0";
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                if($v['status']==1){
                    $status='已审核';
                }elseif($v['status']==2){
                    $status='已禁止';
                }else{
                    $status='待审核';
                }

                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['name']=$v['name'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['status']=$status;
                $reportData='';
                if(is_array($v['report']) && count($v['report'])) {
                    $reportData .= "报告日期：" . $v['report']['report_date'] . "\r\n";
                    $reportData .= "联盟商数量：" . $v['report']['union_total'] . "个\r\n";
                    $reportData .= "集客推广人数：" . $v['report']['customer_total'] . "人\r\n";
                    $reportData .= "转化交易集客人数：" . $v['report']['customer_pay_total'] . "人\r\n";
                    $reportData .= "集客订单总额：" . $v['report']['customer_order_total'] . "元\r\n";
                    $reportData .= "集客订单平均金额：" . $v['report']['order_avg'] . "元\r\n";
                    $reportData .= "集客转化率：" . $v['report']['conversion_rate'] . "%\r\n";
                }
                $data[$k]['data']=$reportData;
                $data[$k]['insert_time']=$v['insert_time'];
            }
            $filename = "全民集客门店列表".date('YmdHis');
            $header = array ('美容院名称','美容院编码','联系人','联系电话','状态','门店报告','注册时间');
            $widths=array('30','20','10','15','15','30','15');
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

    public function branch_edit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::name('qmjk_branch')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护门店失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护门店成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $info= Db::name('qmjk_branch')->where('id', $id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }

    /*
     *门店管理员
     */
    public function branch_admin(){
        header("Cache-control: private");
        $key = input('key');
        $bid = input('bid');
        $map['m.branch_id'] = array('eq',$bid);
        $map['m.union_id'] = array('eq',0);
        if($key && $key!=="")
        {
            $map['m.name|m.mobile'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('qmjk_member')->alias('m')->join('qmjk_branch b','m.branch_id=b.id','left')->where($map)->count();  //总数据
        $allpage = intval($count/ $limits);
        $lists = Db::name('qmjk_member')->alias('m')->join('qmjk_branch b','m.branch_id=b.id','left')->field('m.*,b.title,b.sign')->where($map)->page($Nowpage, $limits)->order('m.insert_time desc')->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['sign']= $v['sign']?$v['sign']:'';
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('bid', $bid);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /*
     *门店联盟商
     */
    public function branch_union(){
        $key = input('key');
        $bid = input('bid');
        $export = input('export',0);
        $map['r.branch_id'] = array('eq',$bid);
        if($key && $key!=="")
        {
            $map['u.title|u.name|u.mobile'] = ['like',"%" . trim($key). "%"];
        }
        $branchInfo=Db::name('qmjk_branch')->where('id',$bid)->find();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where($map)->count();  //总数据
        $allpage = intval($count/ $limits);
        if($export){
            $lists = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where($map)->field('r.id,r.step,r.pay_role,r.branch_id,r.union_id,r.insert_time,r.status,u.title,u.name,u.mobile,u.pay_code,u.address')->order('r.insert_time desc')->select();
        }else{
            $lists = Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where($map)->page($Nowpage, $limits)->field('r.id,r.step,r.pay_role,r.branch_id,r.union_id,r.insert_time,r.status,u.title,u.name,u.mobile,u.pay_code,u.address')->order('r.insert_time desc')->select();
        }
        foreach ($lists as $k => $v) {
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
            $report=Db::name('qmjk_union_report')->where(['branch_id'=>$v['branch_id'],'union_id'=>$v['union_id']])->find();
            if(is_array($report) && count($report)){
                $lists[$k]['report']= $report;
            }else{
                $lists[$k]['report']['report_date']=date('Y-m-d',strtotime('-1 day'));
                $lists[$k]['report']['total_customer']= "0";
                $lists[$k]['report']['week_customer']= "0";
                $lists[$k]['report']['month_customer']= "0";
                $lists[$k]['report']['year_customer']= "0";
                $lists[$k]['report']['customer_pay_total']= "0";
                $lists[$k]['report']['customer_order_total']="0";
                $lists[$k]['report']['order_avg']= "0";
                $lists[$k]['report']['conversion_rate']= "0";
            }
            $role=json_decode($v['pay_role'],true);
            if(is_array($role)){
                switch ($role['roleType']){
                    case 1:
                        $role_text="&nbsp;&nbsp;结算方式：按人数<br/> &nbsp;&nbsp;每人单价：".$role['firstPrice']."元<br/>&nbsp;&nbsp;每月结算日：".$role['payDay']."日";
                        break;
                    case 2:
                        $role_text="&nbsp;&nbsp;结算方式：按首单次单<br/> &nbsp;&nbsp;首单价：".$role['firstPrice']."元 &nbsp;&nbsp;次单价：".$role['otherPrice']."元<br/>&nbsp;&nbsp;每月结算日：".$role['payDay']."日";
                        break;
                    default:
                        $role_text="&nbsp;&nbsp;结算方式：按订单价格折扣<br/> &nbsp;&nbsp;折扣：".$role['firstPrice']."%<br/>&nbsp;&nbsp;每月结算日：".$role['payDay']."日";
                }
            }else{
                $role_text='暂时没有配置结算方式';
            }
            $lists[$k]['role_text']=$role_text;
        }

        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                if($v['step']==1){
                    $step='美容院已确认';
                }elseif($v['status']==2){
                    $step='双方已确认';
                }else{
                    $step='等待美容院确定';
                }

                $data[$k]['btitle']=$branchInfo['title'];
                $data[$k]['sign']=$branchInfo['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['name']=$v['name'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['address']=$v['address'];
                $data[$k]['status']=$v['address']?'正常':'失效';
                $data[$k]['step']=$step;
                $reportData='';
                if(is_array($v['report']) && count($v['report'])) {
                    $reportData .= "报告日期：" . $v['report']['report_date'] . "\r\n";
                    $reportData .= "集客推广人数：" . $v['report']['total_customer'] . "人\r\n";
                    $reportData .= "周集客数量：" . $v['report']['week_customer'] . "人\r\n";
                    $reportData .= "月集客数量：" . $v['report']['month_customer'] . "人\r\n";
                    $reportData .= "年集客数量：" . $v['report']['year_customer'] . "人\r\n";
                    $reportData .= "转化交易集客人数：" . $v['report']['customer_pay_total'] . "人\r\n";
                    $reportData .= "集客订单总额：" . $v['report']['customer_order_total'] . "元\r\n";
                    $reportData .= "集客订单平均金额：" . $v['report']['order_avg'] . "元\r\n";
                    $reportData .= "集客转化率：" . $v['report']['conversion_rate'] . "%\r\n";
                }
                $data[$k]['data']=$reportData;
                $data[$k]['insert_time']=$v['insert_time'];
            }
            $filename = $branchInfo['title'].$branchInfo['sign']."下联盟商列表".date('YmdHis');
            $header = array ('美容院名称','美容院编码','联盟商名称','联系人','联系电话','门店地址','状态','结算协议','门店报告','注册时间');
            $widths=array('30','20','30','30','20','10','15','15','30','15');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }



        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('bid', $bid);
        $this->assign('branchInfo', $branchInfo);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /*
     * 门店支付日志
     */
    public function branch_pay_log(){
        $branch_id=input('param.branch_id');
        $union_id=input('param.union_id');
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $lists = Db::name('qmjk_order_pay')->alias('p')->join('qmjk_branch b','p.bid=b.id','left')->where(['p.bid'=>$branch_id,'p.union_id'=>$union_id])->field("p.pay_number,p.pay_month,sum(pay_money) money,p.status,b.title,p.insert_time,p.pay_evidence")->page($Nowpage, $limits)->group('p.pay_month')->order('pay_month desc')->select();
        $allpage = intval(count($lists)/ $limits);
        foreach ($lists as $k => $v) {
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('branch_id', $branch_id);
        $this->assign('union_id', $union_id); //总页数
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /*
     *门店管理员
     */
    public function branch_pay_log_list(){
        header("Cache-control: private");
        $key = input('key');
        $number = input('number');
        if($key && $key!=="")
        {
            $map['m.name|m.mobile'] = ['like',"%" . $key . "%"];
        }
        $map['pay_number'] = array('eq',$number);


        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('qmjk_order_pay')->alias('p')->where($map)->count();  //总数据
        $allpage = intval($count/ $limits);
        $lists = Db::name('qmjk_order_pay')->where($map)->page($Nowpage, $limits)->order('insert_time desc')->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
            if($v['pay_type']==1){
                $info=Db::name('qmjk_member')->where('id',$v['order_id'])->find();
                $lists[$k]['desc']=date('Y-m-d H:i:s',$info['insert_time']).'引导'.$info['name'].'注册奖励'.$v['pay_money'].'元';
            }else{
                $info=Db::table('ims_bj_shopn_order')->where('id',$v['order_id'])->field('createtime,ordersn')->find();
                $lists[$k]['desc']=date('Y-m-d H:i:s',$info['createtime']).'集客用户下单，单号'.$info['ordersn'].'，下单奖励'.$v['pay_money'].'元';
            }
        }


        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('number', $number);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /*
     * 集客数据
     */
    public function branch_customer(){
        $bid=input('param.bid');
        $union_id=input('param.union_id');
        $key = input('key');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        if($key && $key!=="")
        {
            $map['m.name|m.mobile'] = ['like',"%" . $key . "%"];
        }
        if($union_id && $union_id!=="")
        {
            $map['m.union_id'] = ['eq',$union_id];
        }
        if($search_time1!='' && $search_time2!=''){
            $map['m.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
        }
        $map['m.branch_id'] = array('eq',$bid);
        $map['m.type'] = array('eq',3);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('qmjk_member')->alias('m')->join('qmjk_union u','m.union_id=u.id','left')->where($map)->count();  //总数据
        $allpage = intval($count/ $limits);
        $lists = Db::name('qmjk_member')->alias('m')->join('qmjk_union u','m.union_id=u.id','left')->where($map)->field('m.name,m.mobile,m.insert_time,u.title')->page($Nowpage, $limits)->order('m.insert_time desc')->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['insert_time']= date('Y-m-d H:i:s',$v['insert_time']);
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('bid', $bid);
        $this->assign('union_id', $union_id);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        if(input('get.page'))
        {
            return json($lists);
        }
        $unionList=Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['r.branch_id'=>$bid,'r.step'=>2,'r.status'=>1])->field('r.union_id,u.title')->select();
        $this->assign('unionList',$unionList);
        return $this->fetch();
    }

    /*
     * 集客订单
     */
    public function branch_customer_order(){
        $bid=input('param.bid');
        $union_id=input('param.union_id');
        $key = input('key');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        if($key && $key!=="")
        {
            $map['mm.name|mm.mobile|o.ordersn'] = ['like',"%" . $key . "%"];
        }
        if($union_id && $union_id!=="")
        {
            $map['mm.union_id'] = ['eq',$union_id];
        }
        if($search_time1!='' && $search_time2!=''){
            $map['o.createtime'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
        }
        $map['mm.branch_id'] = array('eq',$bid);
        $map['mm.type'] = array('eq',3);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('ims_bj_shopn_order')->alias('o')->join(['ims_bj_shopn_member m'],'m.id=o.uid','left')->join('pt_qmjk_member mm','mm.mobile=m.mobile','left')->join('qmjk_union u','mm.union_id=u.id','left')->where($map)->count();  //总数据
        $allpage = intval($count/ $limits);
        $lists = Db::table('ims_bj_shopn_order')->alias('o')->join(['ims_bj_shopn_member m'],'m.id=o.uid','left')->join('pt_qmjk_member mm','mm.mobile=m.mobile','left')->join('qmjk_union u','mm.union_id=u.id','left')->where($map)->field('o.ordersn,o.createtime,o.price,mm.name,mm.mobile,u.title')->page($Nowpage, $limits)->order('o.createtime desc')->select();

        foreach ($lists as $k => $v) {
            $lists[$k]['createtime']= date('Y-m-d H:i:s',$v['createtime']);

        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('bid', $bid);
        $this->assign('union_id', $union_id);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        if(input('get.page'))
        {
            return json($lists);
        }
        $unionList=Db::name('qmjk_union_relation')->alias('r')->join('qmjk_union u','r.union_id=u.id','left')->where(['r.branch_id'=>$bid,'r.step'=>2,'r.status'=>1])->field('r.union_id,u.title')->select();
        $this->assign('unionList',$unionList);
        return $this->fetch();
    }




}