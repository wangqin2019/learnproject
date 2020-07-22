<?php

namespace app\admin\controller;
use app\admin\model\BankInterestrateModel;
use app\admin\model\BankModel;
use app\admin\model\BranchModel;
use app\admin\model\Node;
use app\admin\model\SmsModel;
use app\admin\model\UserType;
use think\Db;

class Sms extends Base
{

    /**
     * [index 短信列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){
        $key = input('key');
        $map = [];
        $sms = new SmsModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $sms->getAllSms($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $sms->getSmsByWhere($map, $Nowpage, $limits);
        $scene=config('notice');
        $noticeRole=config('noticerole');
        foreach ($lists as $k=>$v){
           $lists[$k]['sms_scene']=$scene[$v['sms_scene']];
           $lists[$k]['sms_to']=$noticeRole[$v['sms_to']];
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
     * [roleAdd 添加短信]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $sms = new SmsModel();
            $flag = $sms->insertSms($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $scene=config('notice');
        $noticeRole=config('noticerole');
        $this->assign('scene',$scene);
        $this->assign('noticeRole',$noticeRole);
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑短信]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit(){
        $sms = new SmsModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $sms->editSms($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'sms' => $sms->getOneSms($id)
        ]);
        $scene=config('notice');
        $noticeRole=config('noticerole');
        $this->assign('scene',$scene);
        $this->assign('noticeRole',$noticeRole);
        return $this->fetch();
    }




    /**
     * [roleDel 删除短信]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $sms = new SmsModel();
        $flag = $sms->delSms($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * [role_state 用户状态]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function state(){
        $id = input('param.id');
        $status = Db::name('sms_template')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('sms_template')->where('id',$id)->setField(['status'=>2]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('sms_template')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    }


    public function apply(){
        $key = input('key');
        $status = input('status',0);
        $map = [];
        if($key&&$key!==""){
            $map['w.mobile'] = ['like',"%" . $key . "%"];
        }
        $map['w.status']=array('eq',$status);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('waiting_user')->alias('w')->where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('waiting_user')->alias('w')->join(['ims_bj_shopn_member' => 'm'],'w.mobile=m.mobile','left')->where($map)->page($Nowpage, $limits)->field('w.*,m.id mid,m.realname,code,staffid,isadmin,m.storeid')->order('w.id desc')->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['realname']=$v['realname']?$v['realname']:'';
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            if($v['isadmin']==1){
                $lists[$k]['role']='店老板';
            } elseif (strlen($v['code']) > 1 && $v['mid'] == $v['staffid']) {
                $lists[$k]['role']='美容师';
            }else{
                if($v['mid']){
                    $lists[$k]['role']='顾客';
                }else{
                    $lists[$k]['role']='';
                }
            }
           $barnch=Db::table('ims_bwk_branch')->where('id',$v['storeid'])->field('sign,title')->find();
            $lists[$k]['sign']=$barnch['sign']?$barnch['sign']:'';
            $lists[$k]['title']=$barnch['title']?$barnch['title']:'';
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('status', $status);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function applyState(){
        $id = input('param.id');
        $status = Db::name('waiting_user')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('waiting_user')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '未处理']);
        } else {
            $flag = Db::name('waiting_user')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已处理']);
        }
    }


}
