<?php

namespace app\admin\controller;
use app\admin\model\BankInterestrateModel;
use app\admin\model\BankModel;
use app\admin\model\BranchModel;
use app\admin\model\Node;
use app\admin\model\UserType;
use think\Db;

class Bank extends Base
{

    /**
     * [index 银行列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['st_abbre_bankname'] = ['like',"%" . $key . "%"];
        }
        $user = new BankModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $user->getAllBank($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getBankByWhere($map, $Nowpage, $limits);
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
     * [roleAdd 添加银行]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $role = new BankModel();
            $flag = $role->insertBank($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑银行]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit(){
        $bank = new BankModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $bank->editBank($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'bank' => $bank->getOneBank($id)
        ]);
        return $this->fetch();
    }

    /**
     * [roleEdit 编辑分期]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function fenqi(){
        $bid = input('id');
        $map = [];
        if($bid&&$bid!=="")
        {
            $map['i.id_bank'] = ['eq',$bid];
        }
        $user = new BankInterestrateModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $user->getAllFenqi($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getFenqiByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('bid', $bid); //
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [roleAdd 添加银行]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function fenqi_add(){
        if(request()->isAjax()){
            $param = input('post.');
            $fq = new BankInterestrateModel();
            $flag = $fq->insertFenqi($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $bid = input('bid');
        $bank=new BankModel();
        $bankInfo=$bank->getOneBank($bid);
        $this->assign('bankInfo', $bankInfo);
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑银行]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function fenqi_edit(){
        $fq = new BankInterestrateModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $fq->editFenqi($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign([
            'fenqi' => $fq->getOneFenqi($id)
        ]);
        return $this->fetch();
    }

    /**
     * [roleDel 删除分期]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function fenqi_del(){
        $id = input('param.id');
        $role = new BankInterestrateModel();
        $flag = $role->delFenqi($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [roleDel 删除银行]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $role = new BankModel();
        $flag = $role->delBank($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [role_state 用户状态]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function state(){
        $id = input('param.id');
        $status = Db::name('bank')->where('id_bank',$id)->value('id_status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('bank')->where('id_bank',$id)->setField(['id_status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        } else {
            $flag = Db::name('bank')->where('id_bank',$id)->setField(['id_status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }

    }

}
