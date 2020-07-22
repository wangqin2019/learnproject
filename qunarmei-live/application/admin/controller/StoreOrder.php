<?php

namespace app\admin\controller;
use think\Db;

/*
 * 订单列表
 *
 * */

class StoreOrder extends Base
{

    /**
     * [index 用户列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map = 'order_no like "%'.$key.'%" ';
        }       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('store_order')->field('*')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('store_order ord')->join(['ims_bj_shopn_member'=>'m'],['ord.user_id=m.id'],'LEFT')->join(['ims_bwk_branch'=>'b'],['b.id=ord.store_id'],'LEFT')->join(['sys_bank'=>'bank'],['ord.bank_id=bank.id_bank'],'LEFT')->field('ord.*,m.realname,m.mobile,b.title,bank.st_abbre_bankname bkname')->where($map)->page($Nowpage,$limits)->order('id desc')->select();
        if($lists){
            foreach ($lists as &$v) {
                $v['pay_time'] = $v['pay_time']==null?'':$v['pay_time'];
                $v['delivery_time'] = $v['delivery_time']==null?'':$v['delivery_time'];
                $v['bkname'] = $v['bkname']==null?'':$v['bkname'];
                if($v['order_status']==0){
                    $v['order_status'] = '待付款';
                }elseif($v['order_status']==1){
                    $v['order_status'] = '已付款';
                }elseif($v['order_status']==2){
                    $v['order_status'] = '已发货';
                }elseif($v['order_status']==100){
                    $v['order_status'] = '办事处待处理';
                }elseif($v['order_status']==101){
                    $v['order_status'] = '办事处赠品已提交';
                }

                if($v['order_type']==1){
                    $v['order_type'] = '线下订单';
                }else{
                    $v['order_type'] = '线上订单';
                }
            }
        }
        // ID,模块名,删除时间
        // id,roles_name,delete_time
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [userAdd 添加用户]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function addEnter()
    {
        if(request()->isAjax()){

            $param = input('post.');
            // ID,模块名,删除时间
            // id,roles_name,delete_time
            $param_m = ['role'=>$param['role'],'name'=>$param['name'],'mobile'=>$param['mobile'],'img_card'=>$param['img_card'],'status'=>$param['status'],'create_time'=>date('Y-m-d H:i:s'),'remark'=>$param['remark']];
            Db::table('store_enter')->insertGetId($param_m);
            return json(['code' => 1, 'data' => [], 'msg' => '添加成功']);
        }
        return $this->fetch();
    }


    /**
     * [userEdit 编辑用户]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function editOrder()
    {
        $id = input('param.id');
        if(request()->isAjax()){

            $param = input('post.');
            $param_m = ['order_status'=>$param['order_status'],'remark'=>$param['remark']];
            Db::table('store_order')->where('id',$id)->update($param_m);
            return json(['code' => 1, 'data' => [], 'msg' => '修改成功']);
        }
        $map = ['id'=>$id];
        $lists = Db::table('store_order')->field('*')->where($map)->limit(1)->find();
        // -1取消,0待付款,1已付款,2已发货,3为成功(确认收货),4申请退换,5等待退款,6退款完成,7交易完成,100-办事处申请赠品,101-总部审核订单(赠品),102-总部审核订单未通过,200-线下支付凭证已提交,201-货款审查,202-货款审查未通过
        $status=['-1'=>'取消','0'=>'待付款','1'=>'已付款','2'=>'已发货','3'=>'已收货','100'=>'办事处申请赠品','101'=>'办事处申请赠品已提交','102'=>'部审核订单未通过','200'=>'线下支付凭证已提交','201'=>'货款审查','202'=>'货款审查未通过'];
        $this->assign('id', $id);
        $this->assign('lists', $lists);
        $this->assign('status', $status);
        return $this->fetch();
    }


    /**
     * [UserDel 删除用户]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function delEnter()
    {
        $id = input('param.id');
        // 删除用户数据
        Db::table('store_enter')->where('id',$id)->limit(1)->delete();
        return json(['code' => 1, 'data' => [], 'msg' => '删除成功']);
    }

    /**
     * 门店采购-线下订单
     */
    public function ordunder()
    {
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map = 'order_no like "%'.$key.'%" ';
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('store_underline_pay p')
            ->join(['store_order'=>'o'],['o.order_no=p.order_no'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=o.store_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=p.user_id'],'LEFT')
            ->field('p.*,o.pay_time,o.order_amount_total,o.order_status,m.realname,m.mobile,b.title')
            ->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('store_underline_pay p')
            ->join(['store_order'=>'o'],['o.order_no=p.order_no'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=o.store_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=p.user_id'],'LEFT')
            ->field('p.*,o.pay_time,o.order_amount_total,o.order_status,m.realname,m.mobile,b.title,b.sign')
            ->where($map)
            ->page($Nowpage,$limits)
            ->order('id desc')
            ->select();
        if($lists){
            foreach ($lists as &$v) {
                if($v['flag']==0){
                    $v['flag'] = '待审核';
                }elseif($v['flag']==1){
                    $v['order_status'] = '审核通过';
                }elseif($v['flag']==2){
                    $v['order_status'] = '审核不通过';
                }
                $v['pay_time'] = $v['pay_time']==null?'':$v['pay_time'];
                $v['certificate_img'] = $v['certificate_img']?json_decode($v['certificate_img'],true):'';
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 门店采购-线下订单-编辑
     */
    public function ordunder_edit()
    {
        $order_no = input('param.order_no');
        if(request()->isAjax()){
            $param = input('post.');

            $datau['flag'] = $param['flag'];
            $mapu['order_no'] = $param['order_no'];
            Db::table('store_underline_pay')->where($mapu)->update($datau);

            if($datau['flag'] == 1){
                $mapo['order_no'] = $param['order_no'];
                $datao['order_status'] = 1;
                $datao['pay_time'] = date('Y-m-d H:i:s');
                Db::table('store_order')->where($mapo)->update($datao);
                // 插入erp里面
                $rest = curl_get('http://192.168.7.70:86/api/test/orderInsertErp?order_no='.$mapo['order_no']);
            }
            return json(['code' => 1, 'data' => [], 'msg' => '修改成功']);
        }
        $map = ['order_no'=>$order_no];
        $lists = Db::table('store_underline_pay')->field('order_no,flag')->where($map)->limit(1)->find();
        $this->assign('order_no', $order_no);
        $this->assign('lists', $lists);
        return $this->fetch();
    }

}