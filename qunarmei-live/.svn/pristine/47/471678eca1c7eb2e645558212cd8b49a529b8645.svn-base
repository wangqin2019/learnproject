<?php

namespace app\admin\controller;
use app\admin\model\LiveModel;
use think\Db;

/*
 * App相关开关控制
 * */
class LiveSwitch extends Base
{

    /**
     * [index 开关列表]
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
        $count = Db::name('switch')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::name('switch')->where($map)->limit($pre,$limits)->select();
           
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数

        $this->assign('val', $key);
        if($lists)
        {
            foreach ($lists as &$v) {
                if($v['flag'])
                {
                    $v['flag'] = '开启';
                }else
                {
                    $v['flag'] = '关闭';
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
    public function switchAdd()
    {
        if(request()->isAjax()){

            $param = input('post.');
            if($param)
            {

                $data = array('name'=>$param['name'],'type'=>$param['type'],'flag'=>$param['flag']);
                $rest = Db::name('switch')->insert($data);
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
    public function switchEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){

            $param = input('post.');
            $ret = Db::name('switch')->where('id', $id)->update(['name' => $param['name'],'type' => $param['type'],'flag' => $param['flag']]);
            return $this->returnMsg(1,'','修改成功');
        }
        $list = Db::name('switch')->where(array('id'=>$id))->select();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [UserDel 删除]
     * @return [type] [description]
     *
     */
    public function switchDel()
    {
        $id = input('param.id');
        $rest = Db::name('switch')->where('id',$id)->delete();
        return $this->returnMsg(1,'','删除成功');
    }

    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
        $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($ret);
    }
}