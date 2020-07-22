<?php

namespace app\admin\controller;
use think\Db;

/**
 * Class OtoOrder
 * @package app\admin\controller
 * oto参与活动订单
 */
class OtoOrder extends Base
{

    //*********************************************列表*********************************************//
    /**
     * oto活动订单列表
     * @return mixed|\think\response\Json
     */
    public function index(){
        $map = [];
        $pid = input('pid',0);// 查看门店详情,活动门店id
        if($pid){
            $map['bwk.id'] = $pid;
        }
        $key = input('key');
        $export = input('export',0);
        if($key&&$key!=="")
        {
            $map['member.realname|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $map['o.card_id'] = ['>',0];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('ims_bj_shopn_oto')->alias('o')
            ->join(['pt_ticket_user' => 'u'],'o.card_id=u.id','left')
            ->join(['ims_bj_shopn_order' => 'ord'],'ord.id=u.orderid','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=u.user_id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->where($map)
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::table('ims_bj_shopn_oto')->alias('o')->join(['pt_ticket_user' => 'u'],'o.card_id=u.id','left')->join(['ims_bj_shopn_order' => 'ord'],'ord.id=u.orderid','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=u.user_id','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('o.id,depart.st_department,bwk.title,member.realname,member.mobile,ord.ordersn,ord.price pay_price,ord.createtime create_time,ord.payTime pay_time,bwk.sign,ord.id orderid,ord.status,o.oto_user,ord.content,member.staffid')->where($map)->order('u.pay_time desc,o.id desc')->select();
        }else{
            $lists = Db::table('ims_bj_shopn_oto')->alias('o')->join(['pt_ticket_user' => 'u'],'o.card_id=u.id','left')->join(['ims_bj_shopn_order' => 'ord'],'ord.id=u.orderid','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=u.user_id','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('o.id,depart.st_department,bwk.title,member.realname,member.mobile,ord.ordersn,ord.price pay_price,ord.createtime create_time,ord.payTime pay_time,bwk.sign,ord.id orderid,ord.status,o.oto_user,ord.content')->where($map)->page($Nowpage, $limits)->order('u.pay_time desc,o.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['create_time'] = $v['create_time']==null?'':date('Y-m-d H:i:s',$v['create_time']);
            $lists[$k]['order_sn'] = $v['ordersn']==null?'':$v['ordersn'];
            $lists[$k]['pay_price'] = $v['pay_price']==null?'':$v['pay_price'];
            $lists[$k]['pay_time'] = $v['pay_time']==null?'':date('Y-m-d H:i:s',$v['pay_time']);
            // 订单编号
            $lists[$k]['type'] = '线下';
            if($v['orderid']){
                $lists[$k]['type'] = '线上';
            }
            $lists[$k]['pay_status'] = 1;
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
                $data[$k]['order_sn']="\t".$v['order_sn'];
                $data[$k]['content']="\t".$v['content'];
                $data[$k]['oto_user']= $v['oto_user'];
                $data[$k]['pay_price']=$v['pay_price'];
                $data[$k]['create_time']=$v['create_time'];
                $data[$k]['pay_time']=$v['pay_time'];
                $data[$k]['type']=$v['type'];
            }
            $filename = "OTO脑力教育活动顾客订单列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话','活动订单号','商品名称','oto账号','支付金额','订单创建时间','订单支付时间','订单类型');
            $widths=array('10','10','10','10','10','15','15','15','15','30','30','30','30','5');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('pid', $pid);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [add_ad 添加广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function branch_add()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $arr['storeid'] = $param['storeid'];
            $arr['limit_num'] = $param['limit_num'];
            $arr['create_time'] = date('Y-m-d H:i:s');
            $res = Db::table('ims_bj_shopn_oto_branch')->insertGetId($arr);
            $flag = [
                'code' => 1,
                'data' => '',
                'msg' => '添加成功'
            ];
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $branchList = Db::table('ims_bwk_branch')->field('id,title,sign')->select();//计算总页面
        $this->assign('branchList',$branchList);
        return $this->fetch();

    }


    /**
     * [edit_ad 编辑广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function branch_edit()
    {
        $id = input('id');
        $map['b.id'] = $id;
        if(request()->isPost()){
            $param = input('post.');
            $map1['id'] = $param['id'];
            $data['limit_num'] = $param['limit_num'];
            $res = Db::table('ims_bj_shopn_oto_branch')->where($map1)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $list = Db::table('ims_bj_shopn_oto_branch b')
            ->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')
            ->field('b.id,bwk.title,b.limit_num')
            ->where($map)
            ->limit(1)
            ->find();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [del_ad 删除广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function branch_del()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $res = Db::table('ims_bj_shopn_oto_branch')->where($map)->delete();
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }
}