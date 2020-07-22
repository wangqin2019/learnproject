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

class NewYear extends Base
{

    //代金券活动配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            if(time() < '1549296000'){
                $array=array('activity_status'=>$param['activity_status'],'boos_status'=>$param['boos_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'price'=>$param['price']);
                Db::table('ims_bj_shopn_member')->where('isadmin',1)->update(['activity_key'=>$param['boos_status']]);
            }else{
                $array=array('activity_status'=>$param['activity_status'],'begin_time'=>strtotime($param['begin_time']),'end_time'=>strtotime($param['end_time']),'price'=>$param['price']);
            }
            Db::name('new_year_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $yearConfig=Db::name('new_year_config')->where('id',1)->find();
        $yearConfig['begin_time']=date('Y-m-d H:i:s',$yearConfig['begin_time']);
        $yearConfig['end_time']=date('Y-m-d H:i:s',$yearConfig['end_time']);
        $this->assign('yearConfig',$yearConfig);
        return $this->fetch();
    }



    /**
     * [index 代金券列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function coupon(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['coupon_name'] = ['like',"%" . $key . "%"];
        }
        $user = new CouponModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $user->getAllCoupon($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getCouponByWhere($map, $Nowpage, $limits);
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
     * [roleAdd 添加代金券]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $role = new CouponModel();
            $flag = $role->insertCoupon($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑代金券]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit(){
        $bank = new CouponModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $bank->editCoupon($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'coupon' => $bank->getOneCoupon($id)
        ]);
        return $this->fetch();
    }


    /**
     * [roleDel 删除代金券]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $role = new CouponModel();
        $flag = $role->delCoupon($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * [role_state 代金券状态]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function state(){
        $id = input('param.id');
        $status = Db::name('new_year_coupon')->where('id',$id)->value('coupon_status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('new_year_coupon')->where('id',$id)->setField(['coupon_status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('new_year_coupon')->where('id',$id)->setField(['coupon_status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }

    }

    /**
     * [index 代金券列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function couponorder(){

        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['order.coupon_price|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('new_year_coupon_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::name('new_year_coupon_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->order('order.id desc')->select();
        }else{
            $lists = Db::name('new_year_coupon_order')->alias('order')->join(['ims_bj_shopn_member' => 'member'],'member.id=order.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('order.*,member.realname,member.mobile,member.staffid,member.activity_flag,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->order('order.id desc')->select();
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
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$v['staffid'])->field('mobile,realname')->find();
                $data[$k]['sellername']=$sellerInfo['realname'];
                $data[$k]['sellermobile']=$sellerInfo['mobile'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['order_sn']=$v['order_sn'];
                $data[$k]['pay_status']=$v['pay_status']?'已支付':'未支付';
                $data[$k]['pay_price']=$v['pay_price'];
                $data[$k]['coupon_price']=$v['coupon_price'];
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['pay_time']=$v['pay_time'];
                $data[$k]['activity_flag']=$v['activity_flag'];
            }
            $filename = "春节活动顾客订单列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话','活动订单号','支付状态','支付金额','代金券价值','订单创建时间','订单支付时间','顾客标识');
            $widths=array('10','30','20','15','15','15','15','30','30','30','30','30','30','30');
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
        $list=Db::name('new_year_boss_log')->alias('log')->join(['ims_bj_shopn_member' => 'member'],'log.uid=member.id','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.flag,log.insert_time,member.realname,member.mobile,depart.st_department,bwk.title,bwk.sign')->order('log.insert_time')->select();
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




}
