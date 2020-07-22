<?php

namespace app\admin\controller;
use app\admin\model\LiveModel;
use think\Db;

class Livecate extends Base
{
    //调用直播相关接口url
    protected $url = 'http://localhost/pili_test/rtmp_test.php';

    /**
     * [index 直播分类列表]
     * @return 
     * @author 
     */
    public function index(){
       
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['category_name'] = ['like',"%" . $key . "%"];          
        }       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('live_category')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        //获取用户信息
        $uid = $_SESSION['think']['uid'];
        if($uid!=1)
        {
            $map['flag'] = ['<',1];
            $isadmin = 0;
        }else
        {
            $isadmin = 1;
        }
        $map['flag'] = ['<',1];
        $lists = Db::name('live_category')->alias('cat')->where($map)->limit($pre,$limits)->field('cat.*')->select();
        
        $this->assign('isadmin', $isadmin);    
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
     * [liveAdd 添加]
     * @return 
     * @author 
     */
    public function addCate()
    {
        if(request()->isAjax()){

            $param = input('post.');
            if($param)
            {
                //获取用户信息
                $uid = $_SESSION['think']['uid'];

                $data = array('category_id'=>$param['category_id'],'category_name'=>$param['category_name'],'log_time'=>date('Y-m-d H:i:s',time()));               
                $rest = Db::name('live_category')->insert($data);
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
     * [liveEdit 编辑]
     * @return 
     * @author 
     */
    public function editCate()
    {
        $id = input('param.id');

        if(request()->isAjax()){

            $param = input('post.');
            
            $ret = Db::name('live_category')->where('id', $id)->update(['category_id' => $param['category_id'],'category_name' => $param['category_name'],'log_time' => date('Y-m-d H:i:s',time())]);
            $flag = array('code'=>1,'data'=>'','msg'=>'修改成功');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $list = Db::name('live_category')->where(array('id'=>$id))->select();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [liveDel 删除]
     * @return 
     * @author 
     */
    public function delCate()
    {
        $id = input('param.id');
        $rest = Db::name('live_category')->where('id',$id)->update(['flag'=>1]);
        return $this->returnMsg(1,'','删除成功');
    }

    
    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
       $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
       return json($ret);   
    }
}