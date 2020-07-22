<?php

namespace app\admin\controller;
use think\Db;
// 引入extend下的第三方扩展邮件类
use phpmailer_kz\SendMail;
//上传图片到七牛
use qiniu_transcoding\Upimg;
/*
 * App推广邮件配置
 * */

class MailPro extends Base
{
    // 本地
    // protected $url = 'http://172.16.6.120:81';
    // protected $img_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public';
    // 服务器
   protected $url = 'http://live.qunarmei.com';
   protected $img_path = '/home/canmay/www/live/public';
    // 七牛图片url
    protected $qiniu_img = 'http://appc.qunarmei.com';
    /**
     * [index 文章列表]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){
//        $test=$this->send_mail();
//        var_dump($test);exit;

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['content'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('mail_config')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $map['delete_time'] = NULL;
        $lists = Db::name('mail_config')->field('*')->where($map)->order('id desc')->limit($pre,$limits)->select();
        if($lists)
        {
            foreach ($lists as &$v) {
                if($v['type']==1)
                {
                    $v['type'] = '所有人';
                }elseif($v['type']==2)
                {
                    $v['type'] = '门店';
                }else
                {
                    $v['type'] = '个人';
                }

                if(is_null($v['update_time']))
                {
                    $v['update_time']='';
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
//        $this->assign('lists', $lists);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();

    }


    /**
     * [add_article 添加文章]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_mail()
    {
        if(request()->isAjax()){
            $content = $this->updImg(input('content'));
            $param = array('type'=>input('type'),'type_val'=>input('type_val'),'content'=>$content,'create_time'=>date('Y-m-d H:i:d'));
            $flag = Db::name('mail_config')->insert($param);
            if($flag)
            {
                return json(['code' => 1, 'data' => '', 'msg' => '添加成功']);
            }else
            {
                return json(['code' => 1, 'data' => '', 'msg' => '添加失败']);
            }

        }
        return $this->fetch();

    }


    /**
     * [edit_article 编辑文章]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_mail()
    {

        if(input('post.')){
//            var_dump(input());exit;
            $id = input('post.id');
            $content = $this->updImg(input('content'));
            $param = array('type'=>input('type'),'type_val'=>input('type_val'),'content'=>$content,'update_time'=>date('Y-m-d H:i:d'));
            $flag = Db::name('mail_config')->where('id',$id)->update($param);
            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }
        $id = input('id');
        $list = Db::name('mail_config')->field('*')->where('id',$id)->limit(1)->find();
        $this->assign('list',$list);
        return $this->fetch();

    }



    /**
     * [del_article 删除文章]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_mail()
    {
        $id = input('param.id');
        $data = array('delete_time'=>date('Y-m-d H:i:s'));
        $cate = Db::name('mail_config')->where('id',$id)->update($data);
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }



    /**
     * [article_state App邮件推广日志记录]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function mail_log()
    {
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['content'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('article')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $list = Db::name('mail_log')->field('*')->where($map)->order('id desc')->limit($pre,$limits)->select();
        if($list)
        {
            foreach($list as &$v)
            {
                if($v['type'] == 1)
                {
                    $v['type']='所有人';
                }elseif($v['type'] == 2)
                {
                    $v['type']='门店';
                }else
                {
                    $v['type']='个人';
                }
                $v['user_id'] = $v['user_id']==null?'':$v['user_id'];
                $v['user_name'] = $v['user_name']==null?'':$v['user_name'];
                $v['user_mail'] = $v['user_mail']==null?'':$v['user_mail'];
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
//        $this->assign('list', $list);
        if(input('get.page')){
            return json($list);
        }
        return $this->fetch();
    }

    /**
     * [article_state App邮件推广推送邮件]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function send_mail()
    {
        // 初始化数据
        $code=0;$data=[];$msg='发送失败';

        $map['delete_time'] = null;
        $map['id'] = input('id')==''?6:input('id');

        $list = Db::name('mail_config')->field('type,type_val,content')->where($map)->limit(1)->find();
        if($list)
        {
            $mail_address = $this->getMails($list['type'],$list['type_val']);
            if($mail_address)
            {
                $mail = new SendMail();
                $res = $mail->sendMailS($mail_address, '邮件测试', $list['content']);
                if($res)
                {
                    $code = 1;
                    $data = $res;
                    $msg = '邮件发送成功';

                    // 插入记录日志表

                    // 个人
                    if($list['type']==3)
                    {
                        $user_id = $list['type_val'];
                        $user_id = "'".$user_id."'";
                        $user_id = str_replace(',',"','",$user_id);
                        if($user_id)
                        {
                            // 查询配置里面的所有个人用户信息
                            $user_info = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_fans'=>'f'],' f.id_member=m.id','left')->field('m.id,m.realname,f.email')->where("m.mobile in ($user_id)")->select();
//                            var_dump($user_info);
                            if($user_info)
                            {
                                $data_v1 = [];

                                foreach ($user_info as $v) {
                                    $data_v = array('type'=>$list['type'],'type_val'=>$list['type_val'],'user_id'=>$v['id'],'user_name'=>$v['realname'],'user_mail'=>$v['email'],'content'=>$list['content'],'create_time'=>date('Y-m-d H:i:s'));
                                    $data_v1[] = $data_v;
                                }
                                // 批量插入
                                Db::name('mail_log')->insertAll($data_v1);
                            }
                        }
                    }else
                    {
                        // 门店/所有人
                        $data_v = array('type'=>$list['type'],'type_val'=>$list['type_val'],'content'=>$list['content'],'create_time'=>date('Y-m-d H:i:s'));
                        Db::name('mail_log')->insert($data_v);
                    }

                }else
                {
                    $data = $res;
                }
            }
        }
        return json(['code'=>$code,'data'=>$data,'msg'=>$msg]);
    }

    /**
     * [article_state 查询用户邮箱和推送标记]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    protected function getMails($type=1,$type_val='')
    {
        // 所有人
        $map['email_flag'] = 1;
        $list = Db::table('ims_fans')->field('email')->where($map)->order('id desc')->select();
        $list1 = [];
        $type_val = "'".$type_val."'";
        $type_val = str_replace(',',"','",$type_val);
        if($type==2)
        {
            // 查询门店
//            $list = Db::table('ims_fans')->alias('f')->join(['ims_bj_shopn_member'=>'m'],'f.id_member=m.id','left')->field('f.email')->where("f.email_flag=1 and m.storeid in ($type_val)")->select();
            // 根据门店编号查询
            $joins_arr = [[['ims_bj_shopn_member'=> 'm'],'f.id_member=m.id','left'],[['ims_bwk_branch' =>'b'],'b.id=m.storeid','left']];
            $list = Db::table('ims_fans')->alias('f')->join($joins_arr)->field('f.email')->where("f.email_flag=1 and b.sign in ($type_val)")->select();

        }elseif($type==3)
        {
            // 查询个人
            // 根据手机号查询
            $list = Db::table('ims_fans')->alias('f')->join(['ims_bj_shopn_member'=>'m'],' f.id_member=m.id','left')->field('f.email')->where("f.email_flag=1 and m.mobile in ($type_val)")->select();
//            $list = Db::table('ims_fans')->alias('f')->join(['ims_bj_shopn_member'=>'m'],' f.id_member=m.id','left')->field('f.email')->where("f.email_flag=1 and m.id in ($type_val)")->select();

        }
        if($list)
        {
            foreach($list as $v)
            {
                $list_v = $v['email'];
                $list1[] = $list_v;
            }
        }
        return $list1;
    }

    /**
     * [Ueditor富文本框图片上传到七牛并返回地址]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function updImg($content='')
    {
        if($content)
        {
            $content = str_replace('<img src="/ueditor','<img src="'.$this->url.'/ueditor',$content);
            // 匹配图片url
            $img_urls = [];
//            if (preg_match_all('|("http:[^"]+")|',$content,$reg))
            if (preg_match_all('|("'.$this->url.'[^"]+")|',$content,$reg))
            {
                // start Modify by wangqin 2017-11-15 上传图片到七牛
                $upimg = new Upimg();
                foreach($reg[1] as $v1)
                {
                    $img_path = $this->img_path.str_replace($this->url,'',$v1);
                    $img_path = str_replace('"','',$img_path);
                    $img_url = $upimg->upImg($img_path);
//                    $img_urls[] = $img_url;
                    $content = str_replace($v1,'"'.$img_url.'"',$content);
                }
            }
            return $content;
        }
//        return $res;
    }
}