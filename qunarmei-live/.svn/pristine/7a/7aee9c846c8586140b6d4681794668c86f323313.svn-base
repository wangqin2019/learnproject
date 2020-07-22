<?php

namespace app\admin\controller;
use think\Db;

//整合腾讯云通信扩展
use tencent_cloud\TimChat;

//敏感字过滤
class Sensitive extends Base
{

    /**
     * 功能: 敏感字列表
     * 请求: id 聊天室id
     * 返回:
     */
    public function index(){

        $key = input('key');
        $map = '';
        if($key&&$key!=="")
        {
            $map = " sensitive_word like '%$key%' ";
        }       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tent_sensitive')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        //获取用户信息
        $uid = $_SESSION['think']['uid'];
        if($uid!=1)
        {
            $map['flag'] = ['<',1];
            $isadmin = 0;
            //不同账号看到自己创建的直播
            $map['user_id'] = $uid;
        }else
        {
            $isadmin = 1;
        }
        $lists = Db::table('think_tent_sensitive sens')->where($map)->limit($pre,$limits)->field('sens.id,sens.sensitive_word,sens.logtime')->select();

//        $tim = new TimChat();
//        $res = $tim->sensWord('sel');
//        print_r($res);
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
     * [添加]
     * @return 
     * @author 
     */
    public function sensAdd()
    {
        if(request()->isAjax()){
            $param = 1;
            if($param)
            {
//              //获取表单上传文件
                $file_name = $_FILES['sens_word']['name'];
                $file = request()->file('sens_word');
                if (empty($file)) {
                    $this->error('请选择上传文件');
                }
                //移动到框架应用根目录/public/uploads/txt/ 目录下
                $path = ROOT_PATH . 'public' . DS . 'uploads/txt/';
                $info = $file->move($path,$file_name);
                if ($info) {
//                    $this->success('文件上传成功');
                    //读取文件内容,插入数据库,按行读取拼接入库
                    $fp = fopen($path.$file_name, "r");
                    $str = '';$str1='';
                    while(!feof($fp)) {
                        $str .= trim(fgets($fp)).',';
                    }
                    $str = rtrim($str,',');
                    $str1 = str_replace(',','","',$str);

                    //入库
                    $data = array('sensitive_word'=>$str,'logtime'=>date('Y-m-d H:i:s'));
                    $rest = Db::name('tent_sensitive')->insert($data);
                    if($rest)
                    {
                        $msg='上传成功';
                    }
                    //添加敏感字到腾讯云通信
                    $tim = new TimChat();
                    $tim->sensWord('add',$str1);
                } else {
                    //上传失败获取错误信息
                    $this->error($file->getError());
                }
                $flag = array('code'=>1,'data'=>'','msg'=>$msg);
                return json(['code' => $flag['code'], 'data' => '', 'msg' => $flag['msg']]);
            }
            
        }
        return $this->fetch();
    }


    /**
     * [sensEdit 编辑]
     * @return 
     * @author 
     */
    public function sensEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){

            $param = input('post.');

            $data = array('sensitive_word' => $param['sensitive_word']);
            $ret = Db::name('tent_sensitive')->where('id', $id)->update($data);
            //添加敏感字到腾讯云通信
            $tim = new TimChat();
            $sensitive = $param['sensitive_word'];
            if(strstr($sensitive,','))
            {
                $sensitive = str_replace(',','","',$sensitive);
            }
            $tim->sensWord('add',$sensitive);

            $flag = array('code'=>1,'data'=>'','msg'=>'修改成功');
            return json(['code' => $flag['code'], 'data' => '', 'msg' => $flag['msg']]);
        }
        $list = Db::name('tent_sensitive')->field('id,sensitive_word')->where(array('id'=>$id))->select();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [sensDel 删除]
     * @return 
     * @author 
     */
    public function sensDel()
    {
        $id = input('param.id');

        $ret = Db::name('tent_sensitive')->field('sensitive_word')->where('id',$id)->limit(1)->select();
        //添加敏感字到腾讯云通信
        $tim = new TimChat();
        $tim->sensWord('del',$ret[0]['sensitive_word']);
        $rest = Db::name('tent_sensitive')->field('sensitive_word')->where('id',$id)->delete();

        return json(['code' =>1, 'data' => '', 'msg' => '删除成功']);
    }

}