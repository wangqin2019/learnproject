<?php

namespace app\admin\controller;
use think\Db;


class Vod extends Base
{
    /**
     * [index 点播列表]
     * @return 
     * @author 
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];          
        }       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('vod')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists = Db::name('vod')->where($map)->limit($pre,$limits)->field('*')->select();
        if($lists)
        {
            foreach($lists as &$v)
            {
                if($v['type'] == 1)
                {
                    $v['type'] = '直播录制';
                }else{
                    $v['type'] = '自己上传';
                }
            }
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
     * [vodAdd 添加点播]
     * @return 
     * @author 
     */
    public function vodAdd()
    {

        if(request()->isAjax()){

            $param = input('post.');
            if($param)
            {

                $data = array('type'=>$param['type'],'play_url'=>$param['play_url'],'pic_url'=>$param['pic_url'],'title'=>$param['title'],'content'=>$param['content'],'log_time'=>date('Y-m-d H:i:s',time()));
                $rest = Db::name('vod')->insert($data);
                if($rest)
                {
                  $msg='添加成功';
                }
                $flag = array('code'=>1,'data'=>$data,'msg'=>$msg);
                return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
            }
            
        }
        return $this->fetch();
    }


    /**
     * [vodEdit 编辑]
     * @return 
     * @author 
     */
    public function vodEdit()
    {
        $id = input('param.id');

        if(request()->isAjax()){

            $param = input('post.');
            $ret = Db::name('vod')->where('id', $id)->update(['type' => $param['type'],'play_url' => $param['play_url'],'pic_url' => $param['pic_url'],'content' => $param['content'],'title' => $param['title']]);
            $flag = array('code'=>1,'data'=>'','msg'=>'修改成功');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $list = Db::name('vod')->where(array('id'=>$id))->select();
        $list = $list[0];
        $this->assign('list',$list);

        return $this->fetch();
    }


    /**
     * [vodDel 删除]
     * @return 
     * @author 
     */
    public function vodDel()
    {
        $id = input('param.id');
        $rest = Db::name('vod')->where('id',$id)->delete();

        return $this->returnMsg(1,'','删除成功');
    }

    /**
     * [vodXia 下架]
     * @return 
     * @author
     */
    public function vodXia()
    {
        $id = input('param.id');
        
        if($id)
        {
            //修改直播状态
            $rest = Db::name('vod')->where('id',$id)->update(['flag'=>1]);
            return $this->returnMsg(1,'','下架成功');
        }else
        {
            return $this->returnMsg(0,'','下架失败');
        }
        
    }
    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
       $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
       return json($ret);   
    }
}