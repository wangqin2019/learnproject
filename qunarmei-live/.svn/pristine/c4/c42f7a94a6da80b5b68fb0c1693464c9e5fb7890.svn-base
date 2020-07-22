<?php

namespace app\admin\controller;
use think\Db;
//上传图片到七牛
use qiniu_transcoding\Upimg;
use app\api\controller\FindContent;
class AdApp extends Base
{
    //本地
//    private $ad_img_path='D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\uploads\images/';
    //服务器
    private $ad_img_path='/home/canmay/www/live/public/uploads/images/';
    //*********************************************广告列表*********************************************//
    /**
     * [index App 首页广告列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = '1=1';
        if($key&&$key!=="")
        {
            $map .= " hi.url rlike '$key' ";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('home_img hi')->field('*')->order('id desc')->where($map)->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists = Db::name('home_img hi')->field('*')->order('id desc')->where($map)->limit($pre,$limits)->select();
        if($lists)
        {
            foreach($lists as &$v)
            {
                if($v['type'])
                {
                    $v['type']='显示';
                }else
                {
                    $v['type']='不显示';
                }
                if($v['day_flag'])
                {
                    $v['day_flag']='开启';
                }else
                {
                    $v['day_flag']='关闭';
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
     * [add_ad 添加广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_ad()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $data = array('day_flag'=>$param['day_flag'],'img'=>$param['img'],'type'=>$param['type'],'url'=>$param['url'],'ins_time'=>date('Y-m-d H:i:s'));
            $upimg = new Upimg();
            $img_url = $upimg->upImg($this->ad_img_path.$data['img']);
            $data['img'] = $img_url;

            Db::name('home_img')->insert($data);
            //清除文章redis列表
            parent::delete_redis('home_img','clear');
            return json(['code' => 1, 'data' => '', 'msg' => '添加成功']);
        }

        return $this->fetch();

    }


    /**
     * [edit_ad 编辑广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_ad()
    {
        $id = input('id');
        if(request()->isPost()){

            $param = input('post.');
            $data = array('day_flag'=>$param['day_flag'],'type'=>$param['type'],'url'=>$param['url'],'ins_time'=>date('Y-m-d H:i:s'));
            if($param['img'])
            {
                if(!strstr('http://',$param['img']))
                {
                    $upimg = new Upimg();
                    $img_url = $upimg->upImg($this->ad_img_path.$param['img']);
                    $data['img'] = $img_url;
                }
            }
            Db::name('home_img')->where('id',$id)->update($data);
            //清除文章redis列表
            parent::delete_redis('home_img','clear');
            return json(['code' => 1, 'data' => '', 'msg' => '修改成功']);
        }

        $list = Db::name('home_img')->field('*')->where('id',$id)->limit(1)->select() ;
        $this->assign('list',$list[0]);
        return $this->fetch();
    }


    /**
     * [del_ad 删除广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_ad()
    {
        $id = input('id');
        Db::name('home_img')->where('id',$id)->delete();
        //清除文章redis列表
        parent::delete_redis('home_img','clear');
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }

    /* App用户相关数据统计列表
     * */
    public function user_statistics(){

        $key = input('key');
        $map = '';
        if($key&&$key!=="")
        {
            $map .= " and m.realname rlike '$key' ";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('sum_user user,ims_bj_shopn_member m')->field('user.*,m.realname')->order('user.id desc')->where(' user.user_id=m.id '.$map)->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists = Db::name('sum_user user,ims_bj_shopn_member m')->field('user.*,m.realname')->order('user.id desc')->where(' user.user_id=m.id '.$map)->limit($pre,$limits)->select();
        if($lists)
        {
            $fc = new FindContent();
            foreach($lists as &$v)
            {
                $res_num = $fc->getUserSum1($v['user_id']);
                if($res_num)
                {
                    $v['article_num'] = $res_num['article_num'];
                    $v['be_collect_num'] = $res_num['be_collect_num'];
                    $v['follow_num'] = $res_num['follow_num'];
                    $v['fans_num'] = $res_num['fans_num'];
                    $v['scores'] = $res_num['scores'];
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

    /* App文章相关数据统计列表
     * */
    public function article_statistics(){


        $type = input('type')==''?'':input('type');
        //编辑
        if($type == 'edit')
        {
            $id = input('id',0);
            $list = Db::name('sum_article sa,think_find_content fc')->field('sa.*,fc.article_title')->where(' sa.article_id=fc.id and sa.id='.$id)->limit(1)->select();
            if(request()->isAjax())
            {
                $data = array('see_num'=>input('see_num',0),'comment_num'=>input('comment_num',0),'collect_num'=>input('collect_num',0),'upd_time'=>date('Y-m-d H:i:s'));
                Db::name('sum_article')->where('id',$id)->update($data);
                return json(['code'=>1,'data'=>'','msg'=>'修改成功']);
            }
            $this->assign('id', $id);
            $this->assign('list', $list[0]);
//            print_r($list);exit;
            return $this->fetch('article_statistics_edit');
        }
        if($type == 'del')
        {
            $id = input('id',0);
            //删除
            Db::name('sum_article')->where('id',$id)->delete();
            return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
        }


        $key = input('key');
        $map = '';
        if($key&&$key!=="")
        {
            $map .= " and fc.article_title rlike '$key' ";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('sum_article sa,think_find_content fc')->field('sa.*,fc.article_title')->order('sa.id desc')->where(' sa.article_id=fc.id '.$map)->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists = Db::name('sum_article sa,think_find_content fc')->field('sa.*,fc.article_title')->order('sa.id desc')->where(' sa.article_id=fc.id '.$map)->limit($pre,$limits)->select();
        if($lists)
        {
            $fc = new FindContent();
            foreach($lists as &$v)
            {
                $res_num = $fc->getArticelSum1(($v['article_id']));
                if($res_num)
                {
                    $v['see_num'] = $res_num['see_num'];
                    $v['comment_num'] = $res_num['comment_num'];
                    $v['collect_num'] = $res_num['collect_num'];
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

    /* App用户举报列表
     * */
    public function report_user_list(){

        $key = input('key');
        $map = '';$lists=[];
        if($key&&$key!=="")
        {
            $map .= " and m.realname rlike '$key' ";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('report r,ims_bj_shopn_member m')->field('m.realname')->order('m.realname desc')->where(' r.user_id=m.id '.$map)->count();
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists1 = Db::name('report r,ims_bj_shopn_member m')->field('r.id,m.realname')->order('r.id,m.realname desc')->where(' r.user_id=m.id '.$map)->select();
        if($lists1)
        {
            $fc = new FindContent();
            foreach($lists1 as $v1)
            {
                $data1['id'] = $v1['id'];
                $lists2 = Db::name('report r,ims_bj_shopn_member m,think_report_reason trr')->field('m.realname,trr.reason')->order('m.realname desc')->where(' r.reason_id=trr.id and r.reporter_id=m.id and r.id= '.$v1['id'].$map)->limit(1)->select();
                $data1['name'] = $v1['realname'];$data1['reporter_name']='';$data1['reason']='';
                if($lists2)
                {
                    $data1['reporter_name'] = $lists2[0]['realname'];
                    $data1['reason']=$lists2[0]['reason'];
                }
                $lists[] = $data1;
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('lists', $lists);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
}