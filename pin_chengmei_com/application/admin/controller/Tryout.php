<?php

namespace app\admin\controller;
use app\admin\model\BankInterestrateModel;
use app\admin\model\BankModel;
use app\admin\model\BranchModel;
use app\admin\model\ChristmasModel;
use app\admin\model\Node;
use app\admin\model\TryoutModel;
use app\admin\model\UserType;
use think\Db;

class Tryout extends Base
{

    /**
     * [index 试用列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function index(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['tryout_name'] = ['like',"%" . $key . "%"];
        }
        $user = new TryoutModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $user->getAllTryout($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getTryoutByWhere($map, $Nowpage, $limits);
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
     * [roleAdd 添加试用]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $role = new TryoutModel();
            $flag = $role->insertTryout($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑试用]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function edit(){
        $bank = new TryoutModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $bank->editTryout($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'tryout' => $bank->getOneTryout($id)
        ]);
        return $this->fetch();
    }


    /**
     * [roleDel 删除试用]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $role = new TryoutModel();
        $flag = $role->delTryout($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [index 试用列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function lists(){

        $key = input('key');
        $export = input('export',0);
        $map = [];
        if($key&&$key!=="")
        {
            $map['tryout.tryout_name|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tryout_log')->alias('log')->join('tryout tryout','log.tryout_id=tryout.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=log.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists = Db::name('tryout_log')->alias('log')->join('tryout tryout','log.tryout_id=tryout.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=log.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.id,log.insert_time,tryout.tryout_name,tryout.tryout_num,member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->where($map)->order('log.id desc')->select();
        }else{
            $lists = Db::name('tryout_log')->alias('log')->join('tryout tryout','log.tryout_id=tryout.id','left')->join(['ims_bj_shopn_member' => 'member'],'member.id=log.uid','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.id,log.insert_time,tryout.tryout_name,tryout.tryout_num,member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->where($map)->page($Nowpage, $limits)->order('log.id desc')->select();
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
                $data[$k]['tryout_name']=$v['tryout_name'];
                $data[$k]['insert_time']=$v['insert_time'];
            }
            $filename = "产品试用顾客列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话','试用产品','申请时间');
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





}
