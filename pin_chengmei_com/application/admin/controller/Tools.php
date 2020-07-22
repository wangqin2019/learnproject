<?php

namespace app\admin\controller;
use app\admin\model\BankModel;
use think\Db;
use think\Loader;
use think\Request;

class Tools extends Base
{

    //导入美容师名单
    public function seller(){
        if(request()->isAjax()){
            $store_id=input('post.store_id');//门店id
            $lz_seller=input('post.lz_seller');//离职美容师id
            $jj_seller=input('post.jj_seller');//交接美容师id
            $customer=Request::instance()->post('customer/a');//交接客户ids
            Db::startTrans();
            try {
                $lzinfo=Db::table('ims_bj_shopn_member')->where('id',$lz_seller)->field('realname,mobile')->find();
                $jjinfo=Db::table('ims_bj_shopn_member')->where('id',$jj_seller)->field('realname,mobile')->find();
                $remark=date('Y-m-d H:i:s').$lzinfo['realname'].$lzinfo['mobile'].'离职将订单交接给'.$jjinfo['realname'].$jjinfo['mobile'];
                if(is_array($customer) && count($customer)){
                    foreach ($customer as $k=>$v){
                        //1.将自己的拼购订单发起人改为交接美容师,并订单备注 tuan_list
                        Db::name('tuan_list')->where(['create_uid'=>$v,'share_uid'=>$lz_seller])->update(['share_uid'=>$jj_seller,'remark'=>$remark]);
//                      //2.将自己的上级改为交接美容师 member
                        Db::table('ims_bj_shopn_member')->where('id',$v)->update(['pid'=>$jj_seller,'staffid'=>$jj_seller]);
                    }
                }
                //3.检测其下是否还有顾客，没顾客了 在账号后追加-1
                $check=Db::table('ims_bj_shopn_member')->where('staffid',$lz_seller)->where('id','neq',$lz_seller)->count();
                if(!$check){
                    Db::table('ims_bj_shopn_member')->where('id',$lz_seller)->update(['mobile'=>$lzinfo['mobile'].'-1']);
                    Db::table('ims_fans')->where('mobile',$lzinfo['mobile'])->update(['mobile'=>$lzinfo['mobile'].'-1']);
                    Db::name('wx_user')->where('mobile',$lzinfo['mobile'])->update(['mobile'=>$lzinfo['mobile'].'-1']);
                }
                Db::commit();
                $res= ['code' => 1, 'store_id' => $store_id, 'lz_seller' => $lz_seller, 'msg' => '客户交接成功'];
            }catch (\Exception $e){
                Db::rollback();
                $res= ['code' => 0, 'store_id' => $store_id, 'lz_seller' => $lz_seller, 'msg' => '客户交接失败'.$e->getMessage()];
            }
            return json(['code' => $res['code'], 'store_id' => $res['store_id'],'lz_seller' => $res['lz_seller'], 'msg' => $res['msg']]);
        }
        $store_id=input('param.store_id','');
        $lz_seller=input('param.lz_seller','');
        $branchList=Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        $this->assign('branchList',$branchList);
        $this->assign('store_id',$store_id);
        $this->assign('lz_seller',$lz_seller);
        return $this->fetch();
    }

    //导入页面
    public function import(){
        if(request()->isAjax()){
            if (!empty($_FILES)) {
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = request()->file('userfile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    $exclePath = $info->getSaveName();  //获取文件名
                    $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                    $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                    array_shift($excel_array);  //删除标题;
                    $errData=[];
                    //现将所有的is_seller设置为0
                    Db::table('ims_bj_shopn_member')->where('is_seller',1)->update(['is_seller'=>0]);
                    foreach ($excel_array as $k=>$v){
                        $check=Db::table('ims_bj_shopn_member')->where('mobile',$v[0])->count();
                        if($check){
                                Db::table('ims_bj_shopn_member')->where('mobile',$v[0])->update(['is_seller'=>1]);
                        }else{
                            $errData[]=$v[0];
                        }
                    }
                    if(count($errData)>0){
                        $flag['code'] = 0;
                        $flag['data'] = implode(',',$errData);
                        $flag['msg'] = '部分用户不存在';
                    }else{
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
        return $this->fetch();
    }


    /**
     * [index 银行卡号列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bankCard(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['bankcard|b_sign|b_name|department'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('bankcard')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::name('bankcard')->where($map)->page($Nowpage, $limits)->order('id desc')->select();
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
     * [roleAdd 添加银行卡号]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bankCardAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result =  Db::name('bankcard')->insert($param);
                if(false === $result){
                    $res= ['code' => -1, 'data' => '', 'msg' => '添加银行卡失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '添加银行卡成功'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑银行卡号]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bankCardEdit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::name('bankcard')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护银行卡失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护银行卡成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $info= Db::name('bankcard')->where('id', $id)->find();
        $this->assign('bank',$info);
        return $this->fetch();
    }

    /**
     * [roleDel 删除银行卡信息]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bankCardDel(){
        $id = input('param.id');
        try{
            Db::name('bankcard')->where('id', $id)->delete();
            $res= ['code' => 1, 'data' => '', 'msg' => '删除银行卡信息成功'];
        }catch( \PDOException $e){
            $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
    }



    //用户银行卡导入
    public function bankCardImport(){
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
                        $check=Db::name('bankcard')->where(['bankcard'=>$v[6]])->count();
                        if(!$check){
                            $data['department'] = $v[0];
                            $data['b_sign'] = $v[1];
                            $data['b_name'] = $v[2];
                            $data['b_type'] =$v[3];
                            $data['payee'] = $v[4];
                            $data['bankname'] = $v[5];
                            $data['bankcard'] = $v[6];
                            Db::name('bankcard')->insert($data);
                        }else{
                            $errData[]=$v[6];
                        }
                    }
                    if(count($errData)>0){
                        $flag['code'] = 0;
                        $flag['data'] = implode(',',$errData);
                        $flag['msg'] = '部分银卡卡号重复';
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

    //办事处信息列表
    public function bsclist(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['st_department'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('sys_department')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::table('sys_department')->where($map)->page($Nowpage, $limits)->order('id_department')->select();
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
     * [roleAdd 添加办事处]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bscAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result =  Db::table('sys_department')->insert($param);
                if(false === $result){
                    $res= ['code' => -1, 'data' => '', 'msg' => '添加办事处失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '添加办事处成功'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑办事处]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bscEdit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::table('sys_department')->where('id_department',$param['id_department'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护办事处失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护办事处成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id_department');
        $info= Db::table('sys_department')->where('id_department', $id)->find();
        $this->assign('bscInfo',$info);
        return $this->fetch();
    }



    //办事处管理门店
    public function bsc_branch(){
        $key = input('key');
        $id_department = input('id_department');
        $map = [];
        if($key&&$key!=="")
        {
            $map['r.id_sign'] = ['like',"%" . $key . "%"];
        }
        if($id_department && $id_department!=="")
        {
            $map['r.id_department'] = ['eq',$id_department];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where($map)->count();
        $allpage = intval(ceil($count / $limits));
        $lists =Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->field('d.st_department,r.id_department,r.id_sign,r.id_beauty')->where($map)->page($Nowpage, $limits)->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('id_department', $id_department);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * [roleAdd 添加办事处]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bsc_branch_add(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $check=Db::table('sys_departbeauty_relation')->where(['id_sign'=>$param['id_sign']])->whereOr(['id_beauty'=>$param['id_beauty']])->count();
                if(!$check) {
                    $result = Db::table('sys_departbeauty_relation')->insert($param);
                    if (false === $result) {
                        $res = ['code' => -1, 'data' => '', 'msg' => '添加办事处门店失败'];
                    } else {
                        $res = ['code' => 1, 'data' => $param['id_department'], 'msg' => '添加办事处门店成功'];
                    }
                }else{
                    $res = ['code' => -1, 'data' => '', 'msg' => '添加门店已存在'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $this->assign('id_department',input('param.id_department'));
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑办事处]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bsc_branch_edit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::table('sys_departbeauty_relation')->where('id_beauty',$param['id_beauty'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护办事处门店失败'];
                }else{
                    $res= ['code' => 1, 'data' => $param['id_department'], 'msg' => '维护办事处门店成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id_department');
        $info= Db::table('sys_departbeauty_relation')->where('id_beauty', $id)->find();
        $this->assign('bscInfo',$info);
        return $this->fetch();
    }


    /**
     * [roleDel 删除办事处门店信息]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function bsc_branch_del(){
        $id_beauty = input('param.id');
        try{
            Db::table('sys_departbeauty_relation')->where('id_beauty', $id_beauty)->delete();
            $res= ['code' => 1, 'data' => '', 'msg' => '删除办事处门店成功'];
        }catch( \PDOException $e){
            $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
    }

}
