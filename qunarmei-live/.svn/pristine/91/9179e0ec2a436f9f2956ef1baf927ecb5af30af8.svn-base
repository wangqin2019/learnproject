<?php

namespace app\admin\controller;
use think\Db;

/*
 * App登录有奖活动
 * */
class AppActConf extends Base
{

    /**
     * [index 活动列表]
     * @return [type] [description]
     * @author
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['name'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('think_activities')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::table('think_activities')->where($map)->limit($pre,$limits)->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数

        $this->assign('val', $key);
        if($lists)
        {
            foreach($lists as &$v){
                if($v['act_status']){
                    $v['act_status'] = '开启';
                }else{
                    $v['act_status'] = '关闭';
                }
            }

        }
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [userAdd 添加]
     * @return [type] [description]
     *
     */
    public function actAdd()
    {
        if(request()->isAjax()){

            $param = input('post.');
            if($param)
            {

                $data = array('act_title'=>$param['act_title'],'act_start_time'=>$param['act_start_time'],'act_end_time'=>$param['act_end_time'],'act_create_time'=>date('Y-m-d H:i:s'),'act_status'=>$param['act_status']);
                $rest = Db::table('think_activities')->insert($data);
                return $this->returnMsg(1,'','添加成功');
            }

        }
        return $this->fetch();
    }


    /**
     * [userEdit 编辑用户]
     * @return [type] [description]
     *
     */
    public function actEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){

            $param = input('post.');
            $data = array('act_title'=>$param['act_title'],'act_start_time'=>$param['act_start_time'],'act_end_time'=>$param['act_end_time'],'act_status'=>$param['act_status']);
            $ret = Db::table('think_activities')->where('id', $id)->update($data);
            return $this->returnMsg(1,'','修改成功');
        }
        $list = Db::table('think_activities')->where(array('id'=>$id))->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * [UserDel 删除]
     * @return [type] [description]
     *
     */
    public function actDel()
    {
        $id = input('param.id');
        $rest = Db::table('think_activities')->where('id',$id)->delete();
        return $this->returnMsg(1,'','删除成功');
    }

    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
        $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($ret);
    }
}