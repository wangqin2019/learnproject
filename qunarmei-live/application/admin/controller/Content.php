<?php

namespace app\admin\controller;
use think\Db;
//上传图片到七牛
use qiniu_transcoding\Upimg;
//使用redis扩展
use think\cache\driver\Redis;
//取redis文章数据
use app\api\controller\FindContent;
//文章内容管理
set_time_limit(0);
class Content extends Base
{
    //本地测试地址
//    private $url_ym = 'http://172.16.6.120:81';
//    private $lunimg_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public';
    //服务器地址
    private $url_ym = 'http://live.qunarmei.com';
    private $lunimg_path = '/home/canmay/www/live/public';
    /**
     * [index 文章列表]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){
        $uid = $_SESSION['think']['uid'];

        $key = input('key');
        $map = '1=1 ';
        if($key&&$key!==""){
            $map .= " and (fc.article_title rlike '$key' or fc.article_content rlike '$key')";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('find_content fc')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::table('think_find_content fc')->join(['think_find_content_category'=>'cate'],['cate.id=fc.cate_id'],'LEFT')->join(['ims_bj_shopn_member'=>'mem'],['mem.id=fc.user_id'],'LEFT')->field('fc.*,mem.realname author,cate.cate_title')->where('fc.user_id=mem.id and '.$map)->order('fc.display_order desc')->limit($pre,$limits)->select();
        if($lists)
        {
            //浏览数,评论数,收藏数
            $article_res = new FindContent();
            $upimg = new Upimg();
            foreach($lists as &$v)
           {
               //多标签
               if($v['article_label'])
               {
                   $v['article_label'] = implode(',',json_decode($v['article_label']));
               }else
               {
                   $v['article_label']='';
               }
               //多图片
               if($v['article_img'])
               {
                   $v['article_img'] = json_decode($v['article_img']);
               }
               $v['see_num']=0;$v['comment_num']=0;$v['collect_num']=0;
               $num_res = $article_res->getArticelSum1($v['id']);
               if($num_res)
               {
                   $v['see_num'] = $num_res['see_num'];
                   $v['comment_num'] = $num_res['comment_num'];
                   $v['collect_num'] = $num_res['collect_num'];
               }

               //start Modify by wangqin 2018-03-05
               if($v['isshow'])
               {
                   $v['isshow'] = '显示';
               }else
               {
                   $v['isshow'] = '隐藏';
               }
               //end Modify by wangqin 2018-03-05

               //上传视频到七牛
               if($v['article_video'] && !strstr($v['article_video'],'http'))
               {
                $v['article_video'] = $upimg->upFile($this->lunimg_path.$v['article_video']);
                if($v['article_video'])
                {
                    $data_v = array('article_video'=>$v['article_video']);
                    Db::name('find_content')->where('id',$v['id'])->update($data_v);
                }
               }
           }

        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
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
    public function add_article()
    {
        $cate_id = input('cate_id',0);
        if($cate_id){
            $param = input();
//            echo '<pre>';print_r($param);die;


            $data = [
                'user_id' => 26208,
                'article_title' => $param['article_title'],
                'summary' => $param['summary'],
                'display_order' => $param['display_order'],
                'cover_img_1' => $param['cover_img_1'],
                'cover_img' => $param['cover_img'],
                'is_fashion' => 1,
                'cate_id' => $param['cate_id'],
                'flag_img' => 1,
                'comment_time' => date('Y-m-d H:i:s')
            ];
//            echo '<pre>';print_r($data);die;
            if($data['cover_img'] && $data['cover_img_1']){
                $ad_img_path='/home/canmay/www/live/public/uploads/images/';
                $upimg = new Upimg();
                $img_url = $upimg->upImg($ad_img_path.$data['cover_img']);
                $data['cover_img'] = $img_url;

                $img_url = $upimg->upImg($ad_img_path.$data['cover_img_1']);
                $data['cover_img_1'] = $img_url;
                $res = Db::table('think_find_content')->insertGetId($data);
                return json(array('code'=>1,'data' => '','msg' => '添加成功'));
            }else{
                return json(array('code'=>0,'data' => '','msg' => '文章封面图不能为空!'));
            }

        }
        return $this->fetch();
    }

    /**
     * [del_article 删除文章]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_article()
    {
        $id = input('param.id');
        if($id)
        {
            Db::name('find_content')->where('id',$id)->delete();
            //清除文章redis列表
            parent::delete_redis('get_content');
            //删除对应的评论
            Db::name('find_content_comment')->where('article_id',$id)->delete();
            parent::delete_redis('get_comment_','clear');
            //删除对应的收藏
            Db::name('find_content_collect')->where('actrile_id',$id)->delete();
            parent::delete_redis('get_collet_','clear');
            //删除对应的统计
            $uid = $_SESSION['think']['uid'];
            $user_id = $this->getUserId($uid);
            Db::name('sum_article')->where('article_id',$id)->limit(1)->delete();
            Db::name('sum_user')->where('user_id',$user_id)->limit(1)->delete();
        }
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }


    public function more_comment($article_id=1)
    {
        $type = input('type');
        $id = input('id');
        //删除
        if($type =='del')
        {
            Db::name('find_content_comment')->where('id',$id)->delete();
            //清除文章redis列表
            parent::delete_redis('get_comment_'.$id,'clear');
            return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
        }
        //查询
        $key = input('key');
        $map='';
        if($key&&$key!==""){
            $map = " and fcc.comment_content rlike '$key'";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('find_content_comment fcc,ims_bj_shopn_member mem,think_find_content tfc')->field('fcc.id,fcc.article_id,fcc.user_id,fcc.pre_comment_id,fcc.comment_content,fcc.comment_time,mem.realname,tfc.article_title')->order('fcc.comment_time desc')->where('fcc.user_id=mem.id and tfc.id=fcc.article_id and fcc.article_id='.$article_id.$map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        //获取文章评论
        $res = Db::name('find_content_comment fcc,ims_bj_shopn_member mem,think_find_content tfc')->field('fcc.id,fcc.article_id,fcc.user_id,fcc.pre_comment_id,fcc.comment_content,fcc.comment_time,mem.realname author,tfc.article_title')->order('fcc.comment_time desc')->where('fcc.user_id=mem.id and tfc.id=fcc.article_id and fcc.article_id='.$article_id.$map)->limit($pre,$limits)->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($res);
        }
        $this->assign('article_id', $article_id);
        $this->assign('list', $res);
        return $this->fetch();
    }

    /**
     * [edit_article 编辑文章]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_article()
    {
        $id = input('id',0);
        $submit = input('submit');
        $list = Db::table('think_find_content')->where('id',$id)->limit(1)->find();
        if($submit){
            $param = input();
            $id = $param['id'];
            $data = [
                'article_title' => $param['article_title'],
                'summary' => $param['summary'],
                'cate_id' => $param['cate_id'],
                'display_order' => $param['display_order']
            ];

            if(isset($data['cover_img']) && $data['cover_img']){
                $ad_img_path='/home/canmay/www/live/public/uploads/images/';
                $upimg = new Upimg();
                $img_url = $upimg->upImg($ad_img_path.$data['cover_img']);
                $data['cover_img'] = $img_url;
            }
            if(isset($data['cover_img']) && $data['cover_img_1']){
                $ad_img_path='/home/canmay/www/live/public/uploads/images/';
                $upimg = new Upimg();
                $img_url = $upimg->upImg($ad_img_path.$data['cover_img_1']);
                $data['cover_img_1'] = $img_url;
            }
            $res = Db::table('think_find_content')->where('id',$id)->update($data);
            return json(array('code'=>1,'data' => '','msg' => '修改成功'));
        }
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * [form_sess 保存数据到redis]
     * @return
     * @author
     */
    public function form_sess()
    {
        $data = input('data');
        //存预览数据到redis里
        $redis = new Redis();
        $redis->set('form_sess',$data,'1800');
        return $data;
    }

    /*
     * 功能:上传图片文件
     * 请求: 把base64图片上传到文件夹下
     * */
    public function upload_img($base_img){
        //  $base_img是获取到前端传递的src里面的值，也就是我们的数据流文件
        $type = '.jpg';
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base_img, $result)) {
            $type = '.'.$result[2];
            $base_img = str_replace($result[1], '', $base_img);
        }
        if($type = '.jpeg')
        {
            $type = '.jpg';
        }
        //  设置文件路径和文件前缀名称
        $path = "./uploads/img/";
        $prefix='nx_';
        $output_file = $prefix.time().rand(100,999).$type;
        $path = $path.$output_file;
        //  创建将数据流文件写入我们创建的文件内容中
        $ifp = fopen( $path, "wb" );
        fwrite( $ifp, base64_decode( $base_img) );
        fclose( $ifp );
        // 第二种方式
        // file_put_contents($path, base64_decode($base_img));
        // 输出文件
        echo '/uploads/img/'.$output_file;
    }

    /*
     * 功能:上传文件
     * 请求: 把文件上传到文件夹下
     * */
    public function upload_file($file_name=''){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file($file_name);
        // 移动到框架应用根目录/uploads/ 目录下
        if($file)
        {
            $info = $file->move( './uploads/video/');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
//            echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
//            $name = $info->setSaveName('video_'.date('YmdHis'));
                return '/uploads/video/'.$info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//            echo $info->getFilename();
            }else{
                // 上传失败获取错误信息
//            echo $file->getError();
                return '';
            }
        }else
        {
            return '';
        }

    }

    /*
     * 功能:根据think_admin表中的id去查找user_id对应ims_bj_shopn_member表中的id
     * 请求: uid
     * */
    public function getUserId($uid)
    {
        $user_id = $uid;
        $res = Db::name('admin')->field('user_id')->where('id',$uid)->limit(1)->select();
        if($res)
        {
            $user_id=$res[0]['user_id'];
        }
        return $user_id;
    }

    //举报列表
    public function report_list(){

        $key = input('key');
        $map = '1=1 ';
        if($key&&$key!==""){
            $map .= " and (fc.article_title rlike '$key' or fc.article_content rlike '$key')";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::name('find_content fc')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::name('find_content fc,ims_bj_shopn_member mem ')->field('fc.*,mem.realname author')->where('fc.user_id=mem.id and '.$map)->order('id desc')->limit($pre,$limits)->select();
        if($lists)
        {
            //浏览数,评论数,收藏数
            $article_res = new FindContent();

            foreach($lists as &$v)
            {
                //多标签
                if($v['article_label'])
                {
                    $v['article_label'] = implode(',',json_decode($v['article_label']));
                }else
                {
                    $v['article_label']='';
                }
                //多图片
                if($v['article_img'])
                {
                    $v['article_img'] = json_decode($v['article_img']);
                }
                $v['see_num']=0;$v['comment_num']=0;$v['collect_num']=0;
                $num_res = $article_res->getArticelSum1($v['id']);
                if($num_res)
                {
                    $v['see_num'] = $num_res['see_num'];
                    $v['comment_num'] = $num_res['comment_num'];
                    $v['collect_num'] = $num_res['collect_num'];
                }
            }

        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    public function getColors($num)
    {
        $data1 = [];
        $sql_c = Db::query('select colors from think_color order by rand() limit '.$num);
        foreach($sql_c as $k=>$v)
        {
            $data1[] = $sql_c[$k]['colors'];
        }
        return json_encode($data1);
    }

    // 文章状态修改
    public function upd_show()
    {
        $id = input('id');$type = input('type');
//        echo 'id:'.$id.'-type:'.$type;
        $flag = 0;
        if($id)
        {
            $data = array('isshow'=>$type);
            $res = Db::name('find_content')->where('id',$id)->update($data);
            if($res)
            {
                $flag=1;
            }
        }
        return $flag;
    }

    //待审核文章列表
    public function shenhe_list(){
        $uid = $_SESSION['think']['uid'];

        //修改
        $type = input('type');
        if($type == 'edit')
        {
            $id = input('id');$tj=input('tj');
            if($tj)
            {
                $status = input('status');
                $data = array('status'=>$status,'remark'=>input('remark'));
                Db::name('find_content_review')->where('id',$id)->update($data);
                //如果通过,入库
                if($status == 1)
                {
                    $sh = Db::name('find_content_review')->field('*')->where('id',$id)->limit(1)->select();
                    $data_v = array('user_id'=>$sh[0]['user_id'],'article_img'=>$sh[0]['article_img'],'article_title'=>$sh[0]['article_title'],'article_content'=>$sh[0]['article_content'],'article_video'=>$sh[0]['article_video'],'comment_time'=>$sh[0]['comment_time'],'article_label'=>$sh[0]['article_label'],'article_label_color'=>$sh[0]['article_label_color'],'resource'=>2);
                    Db::name('find_content')->insert($data_v);
                    //发布文章数+1
                    $fc = new FindContent();
                    $fc->getUserSum1($sh[0]['user_id'],'article_num',1,'ht');
                    // start Modify by wangqin 2018-03-14 添加积分
                    $arr = array('user_id'=>$sh[0]['user_id'],'type'=>'content');
                    $fc->upd_scores($arr);
                    // end Modify by wangqin 2018-03-14
                    //清除文章redis列表
                    parent::delete_redis('get_content');
                }
                return array('code'=>1,'data' => '','msg' => '修改成功');
            }else
            {
                $list = Db::name('find_content_review')->field('*')->where('id',$id)->limit(1)->select();
                $this->assign('list', $list[0]);
                return $this->fetch('shenhe_edit');
            }

        }else if($type == 'del')
        {
            $id = input('id');
            Db::name('find_content_review')->where('id',$id)->delete();
            return array('code'=>1,'data' => '','msg' => '删除成功');
        }
        else
        {
            //展示
            $key = input('key');
            $map = '1=1 ';
            if($key&&$key!==""){
                $map .= " and (fc.article_title rlike '$key' or fc.article_content rlike '$key')";
            }
            $Nowpage = input('get.page') ? input('get.page'):1;
            $limits = 30;// 获取总条数
            $count = Db::name('find_content fc')->where($map)->count();//计算总页面
            $allpage = intval(ceil($count / $limits));
            $pre = ($Nowpage-1)*$limits;
            $lists = Db::name('find_content_review fc,ims_bj_shopn_member mem ')->field('fc.*,mem.realname author')->where(' fc.status<>1 and fc.user_id=mem.id and '.$map)->order('id desc')->limit($pre,$limits)->select();
            if($lists)
            {
                foreach($lists as &$v)
                {
                    //多标签
                    if($v['article_label'])
                    {
                        $v['article_label'] = implode(',',json_decode($v['article_label']));
                    }else
                    {
                        $v['article_label']='';
                    }
                    //多图片
                    if($v['article_img'])
                    {
                        $v['article_img'] = json_decode($v['article_img']);
                    }
                    if($v['status']==2)
                    {
                        $v['status'] = '审核未通过';
                    }else
                    {
                        $v['status'] = '待审核';
                    }
                }

            }
            $this->assign('Nowpage', $Nowpage); //当前页
            $this->assign('allpage', $allpage); //总页数
            $this->assign('count', $count);
            $this->assign('val', $key);
            if(input('get.page')){
                return json($lists);
            }
            return $this->fetch();
        }
    }
    // end Modify by wangqin 2018-03-05
    public function detailIndex(){
        $article_id = input('article_id');
        $map['article_id'] = $article_id;
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('think_find_content_img fc')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::table('think_find_content_img fci')->join(['think_find_content'=>'fc'],['fci.article_id=fc.id'],'LEFT')->field('fci.*,fc.article_title')->where('fci.article_id',$article_id)->order('fci.display_order desc')->limit($pre,$limits)->select();
        if($lists) {

        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('article_id', $article_id);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    public function detailAdd()
    {
        $article_id = input('article_id',0);
        $submit = input('submit',0);
        if($submit){
            $param = input();
            $data = [
                'article_id' => $article_id,
                'img_url' => $param['img_url'],
                'display_order' => $param['display_order'],
                'insert_time' => date('Y-m-d H:i:s')
            ];
            if($data['img_url']){
                $ad_img_path='/home/canmay/www/live/public/uploads/images/';
                $upimg = new Upimg();
                $img_url = $upimg->upImg($ad_img_path.$data['img_url']);
                $data['img_url'] = $img_url;

                $res = Db::table('think_find_content_img')->insertGetId($data);
                return json(array('code'=>1,'data' => '','msg' => '添加成功'));
            }else{
                return json(array('code'=>0,'data' => '','msg' => '文章详情图不能为空!'));
            }

        }
        $this->assign('article_id', $article_id);
        return $this->fetch();
    }

    public function detailEdit()
    {
        $id = input('id',0);
        $submit = input('submit',0);
//        echo 'id1:<pre>';print_r($id);die;
        $list = Db::table('think_find_content_img')->where('id',$id)->limit(1)->find();
        if($submit){
            $param = input();
            $data = [
                'display_order' => $param['display_order']
            ];
            if($param['img_url']){
                $data['img_url'] = $param['img_url'];
                $ad_img_path='/home/canmay/www/live/public/uploads/images/';
                $upimg = new Upimg();
                $img_url = $upimg->upImg($ad_img_path.$data['img_url']);
                $data['img_url'] = $img_url;
            }
            $res = Db::table('think_find_content_img')->where('id',$param['id'])->update($data);
            return json(array('code'=>1,'data' => $list['article_id'],'msg' => '修改成功'));
        }

        $this->assign('list', $list);
        $this->assign('id', $id);
        return $this->fetch();
    }
    public function detailDel()
    {
        $id = input('id',0);
        if($id){
            $res = Db::table('think_find_content_img')->where('id',$id)->delete();
            return json(array('code'=>1,'data' => '','msg' => '删除成功'));
        }
    }
}