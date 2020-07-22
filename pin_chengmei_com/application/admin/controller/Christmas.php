<?php

namespace app\admin\controller;
use app\admin\model\BankInterestrateModel;
use app\admin\model\BankModel;
use app\admin\model\BranchModel;
use app\admin\model\ChristmasModel;
use app\admin\model\Node;
use app\admin\model\UserType;
use think\Db;

class Christmas extends Base
{

    /**
     * [index 奖品列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function prize(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['prize_name'] = ['like',"%" . $key . "%"];
        }
        $user = new ChristmasModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $user->getAllPrize($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getPrizeByWhere($map, $Nowpage, $limits);
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
     * [roleAdd 添加奖品]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $role = new ChristmasModel();
            $flag = $role->insertPrize($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑奖品]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit(){
        $bank = new ChristmasModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $bank->editPrize($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'prize' => $bank->getOnePrize($id)
        ]);
        return $this->fetch();
    }


    /**
     * [roleDel 删除奖品]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $role = new ChristmasModel();
        $flag = $role->delPrize($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * [role_state 奖品状态]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function state(){
        $id = input('param.id');
        $status = Db::name('christmas_prize')->where('id',$id)->value('prize_status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('christmas_prize')->where('id',$id)->setField(['prize_status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('christmas_prize')->where('id',$id)->setField(['prize_status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }

    }

    /**
     * [index 奖品列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function prizelog(){

        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['prize.prize_name|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('christmas_prize_log')->alias('log')->join('christmas_prize prize','log.prize_id=prize.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=log.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::name('christmas_prize_log')->alias('log')->join('christmas_prize prize','log.prize_id=prize.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=log.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.id,log.insert_time,prize.prize_name,prize.prize_price,prize.prize_pic,member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->where($map)->order('log.id desc')->select();
        }else{
            $lists = Db::name('christmas_prize_log')->alias('log')->join('christmas_prize prize','log.prize_id=prize.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=log.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.id,log.insert_time,prize.prize_name,prize.prize_price,prize.prize_pic,member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->order('log.id desc')->select();
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
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
                $data[$k]['prize_name']=$v['prize_name'];
                $data[$k]['insert_time']=$v['insert_time'];
            }
            $filename = "圣诞中奖顾客列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话','中奖奖品','中奖时间');
            $widths=array('10','30','20','15','15','15','15','30','30');
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
     * [index 推广排名列表]
     */
    public function rank(){
        $export = input('export',0);
        $map1['activity_flag']=array('in','5,6');
        $customerList=Db::table('ims_bj_shopn_member')->alias('member')->join('wx_user u','member.mobile=u.mobile','left')->field('member.staffid,count(member.id) as count,u.avatar')->where(['member.id_regsource'=>7])->where($map1)->group('member.staffid')->order('count desc')->select();
        if(count($customerList) && is_array($customerList)){
            $rank=1;
            foreach ($customerList as $key=>$val){
                $customerList[$key]['rank']=$rank++;
                $getSellerInfo=Db::table('ims_bj_shopn_member')->alias('member')->field('member.realname,member.mobile,u.avatar,depart.st_department,bwk.title,bwk.sign')->join('wx_user u','member.mobile=u.mobile','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where('member.id',$val['staffid'])->find();
                $customerList[$key]['seller_name']=$getSellerInfo['realname']?$getSellerInfo['realname']:$getSellerInfo['mobile'];
                $customerList[$key]['avatar']=$getSellerInfo['avatar']?$getSellerInfo['avatar']:config('qiniu.image_url').'/avatar.png';
                $customerList[$key]['st_department']=$getSellerInfo['st_department'];
                $customerList[$key]['title']=$getSellerInfo['title'];
                $customerList[$key]['sign']=$getSellerInfo['sign'];
                $customerList[$key]['mobile']=$getSellerInfo['mobile'];
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($customerList as $k => $v) {
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['seller_name']=$v['seller_name'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['count']=$v['count'];
            }
            $filename = "圣诞美容师推广排名".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','美容师姓名','美容师电话','推广新客人数');
            $widths=array('10','30','20','15','15','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('customerList', $customerList);
        return $this->fetch();
    }




}
