<?php

namespace app\api_test\controller;
//use think\Controller;
use think\Db;
use think\Config;
//
////使用redis扩展
//use think\cache\driver\Redis;

/**
 * FindContent: App 发现模块
 */
class FindContent  extends Base
{

    /*
     * 功能: 获取发现文章评论
     * 请求: $article_id=>文章id
     * 返回:
     * */
    public function get_comment()
    {
        //初始化数据
        $code = 1;$msg='评论成功';$data=[];$flag=0;$data2=[]; $ret='';$data3='';$pre_comment_content='';
        //获取请求数据
        $article_id = input('article_id')==''?'':input('article_id');
        $page = input('page')==''?1:input('page');
        $user_id = input('user_id')==''?'':input('user_id');
        //根据页数判断是否取缓存数据
        $key1 = 'get_comment_'.$article_id.'_'.$page.'_'.$user_id;
        $key2 = 'get_comment_'.$article_id;

        if($page == 1)
        {
            //清除整个缓存
            parent::clearRedisP($key2);
            //浏览次数+1
            $data3 = $this->getArticelSum1($article_id,'see_num',1);
        }else
        {
            //获取缓存数据
            $ret = parent::getRedisP($key1);
        }
        //缓存不为空,取缓存数据
        if($ret)
        {
            return $ret;
        }

        //分页,每页显示30条
        $limits = 30;$pre = ($page-1)*$limits;
        if($article_id && $user_id)
        {
            //查询文章
            $res1 = Db::name('find_content cnt,ims_bj_shopn_member mem,ims_fans fans')->field('cnt.article_label,cnt.id article_id,cnt.user_id,cnt.article_img,cnt.article_title,cnt.article_content,cnt.comment_time,mem.realname user_name,fans.avatar user_img,cnt.article_video')->where('mem.id=fans.id_member and mem.id=cnt.user_id  and cnt.id='.$article_id)->limit(1)->select();
            if($res1)
            {
                $res1[0]['see_num'] = 0; $res1[0]['comment_num']=0;
                if($data3)
                {
                    $res1[0]['see_num'] = $data3['see_num'];
                    $res1[0]['comment_num'] = $data3['comment_num'];
                }
                if($res1[0]['article_label'])
                {
                    $res1[0]['article_label'] = json_decode($res1[0]['article_label']);
                }else
                {
                    $res1[0]['article_label'] = [];
                }
                //查询文章评论
                // $res2 = Db::name('find_content_comment fcc,ims_bj_shopn_member mem,ims_fans fans')->field('fcc.*,mem.realname user_name,fans.avatar user_img')->where(' mem.id=fans.id_member and mem.id=fcc.user_id  and article_id='.$article_id)->order('comment_time desc')->limit($pre,$limits)->group('fcc.id')->select();
                $res2 = Db::name('find_content_comment fcc,ims_bj_shopn_member mem')->field('fcc.*,mem.realname user_name,mem.id')->where(' mem.id=fcc.user_id  and article_id='.$article_id)->order('comment_time desc')->limit($pre,$limits)->group('fcc.id')->select();
                $res1[0]['comment_list1'] = array();
                if($res2)
                {
                    foreach($res2 as $v2)
                    {
                        $data1['user_id'] = $v2['user_id'];
                        $data1['comment_id'] = $v2['id'];
                        $data1['pre_comment_id'] = $v2['pre_comment_id'];
                        $data1['pre_comment_content'] = '';
                        //@回复人及内容
                        if($v2['pre_comment_id'])
                        {
                            $pre_comment_content = Db::name('find_content_comment fcc,ims_bj_shopn_member mem')->field('mem.realname user_name,fcc.comment_content')->where('mem.id=fcc.user_id and fcc.id='.$v2['pre_comment_id'])->limit(1)->select();
                            if($pre_comment_content)
                            {
                                $data1['pre_comment_content'] = '@'.$pre_comment_content[0]['user_name'].' '.$pre_comment_content[0]['comment_content'];
                            }

                        }
                        $data1['user_name'] = $v2['user_name'];
                        // $data1['user_img'] = $v2['user_img'];
                        $data1['user_img'] = '';
                        //查询用户头像
                        $res_tx = Db::table('ims_fans')->field('avatar')->where('id_member',$res2[0]['id'])->limit(1)->select();
                        if($res_tx)
                        {
                            if($res_tx[0]['avatar'] && strstr($res_tx[0]['avatar'],'http') )
                            {
                                $data1['user_img'] = $res_tx[0]['avatar'];
                            }else
                            {
                                $data1['user_img'] = config('qiniu_img_domain').'/img_logo1.png';
                            }
                        }else
                        {
                            $data1['user_img'] = config('qiniu_img_domain').'/img_logo1.png';
                        }
                        $data1['comment_content'] = $v2['comment_content'];
                        $data1['comment_time'] = $v2['comment_time'];
                        $data2[] = $data1;

                    }
                    $res1[0]['comment_list1'] = $data2;

                }
                //文章图片
                if($res1[0]['article_img'])
                {
                    $res1[0]['article_img'] = json_decode($res1[0]['article_img']);
                }else
                {
                    $res1[0]['article_img'] = [];
                }

                //是否已收藏
                $res_sc = Db::name('find_content_collect')->field('id')->limit(1)->where('type=1 and user_id='.$user_id.' and actrile_id='.$article_id)->select();
                $res1[0]['is_collet'] = 0;
                if(@$res_sc)
                {
                    $res1[0]['is_collet'] = 1;
                }



                //第一页显示文章+评论
//                if($page == 1)
//                {
//                    $data = $res1[0];
//                }else
//                {
//                    //后面几页只显示评论
//                    $data =  $data2;
//                }

                $data = $res1[0];
                //分页没有评论时comment_list1为null
                if(@!$data['comment_list1'])
                {
                    $data['comment_list1'] = array();
                }
                if($data && !empty($data))
                {
                    $flag = 1;
                    $ret = $this->returnMsg($code,$data,$msg);
                    parent::setRedisP($key1,$ret,3600);
                }
            }
        }

        if($flag == 0)
        {
            $code = 0;$msg='获取数据失败';
            $ret = $this->returnMsg($code,$data,$msg);
        }
        return $ret ;
    }

    /*
     * 功能: 发布评论
     * 请求: $article_id=>文章id
     * 返回:
     * */
    public function set_comment()
    {
        //初始化数据
        $code = 1;$msg = '评论成功';$data = [];$flag = 0;$ret = null;
        //获取请求数据
        $article_id = input('article_id') == '' ? '' : input('article_id');
        $user_id = input('user_id') == '' ? '' : input('user_id');
        $pre_comment_id = input('pre_comment_id') == '' ? 0 : input('pre_comment_id');
        $comment_content = input('comment_content') == '' ? '' : input('comment_content');
        if(!($article_id && $user_id))
        {
            $flag = 2;
        }else
        {
            //发布评论
            $datav = array('article_id'=>$article_id,'user_id'=>$user_id,'pre_comment_id'=>$pre_comment_id,'comment_content'=>$comment_content,'comment_time'=>date('Y-m-d H:i:s'));
            $res = Db::name('find_content_comment')->insert($datav);
            if($res)
            {
                $flag=1;
                $comment_id = Db::name('find_content_comment')->getLastInsID();
                $data = array('comment_id'=>$comment_id);
                //评论数+1
                $data3 = $this->getArticelSum1($article_id,'comment_num',1);
                // start Modify by wangqin 2018-03-19
                // 每日第一次评论添加积分
                $count = Db::name('find_content_comment')->field('id,comment_time')->where("user_id=$user_id and comment_time>'".date('Y-m-d 00:00:00')."' ")->count();
                if($count<2)
                {
                    $this->upd_scores(['user_id'=>$user_id,'type'=>'comment']);
                }
                // end Modify by wangqin 2018-03-19
            }else
            {
                $flag=3;
            }
        }
        return $this->returnMsg($code,$data,$msg);
    }

    /*
     * 功能: 获取发现文章列表
     * 请求:  page=>页数,默认第一页;key=>搜索
     * 返回:
     * */
    public function get_content()
    {
        //获取请求数据
        $page = input('page')==''?1:input('page');
        $key = input('key')==''?'':input('key');
        //初始化数据
        $code = 1;$msg = '获取成功';$data = [];
        if($key || ($page==1))
        {
            parent::clearRedisP('get_content');
        }
        //获取redis里的数据
        $data1 = parent::getRedisP('get_content_'.$page);
        if(!$data1) {
            //初始化设置
            $map = '';$res1 = [];$data2 = [];
            //分页,每页显示30条
            $limits = 30;
            $pre = ($page - 1) * $limits;
            if ($key) {
                $map = " and (cnt.article_title rlike '$key' or cnt.article_content rlike '$key' or cnt.article_label rlike '$key')";
                $res = Db::name('find_content cnt,ims_bj_shopn_member mem,ims_fans fans')->field('cnt.id article_id,cnt.article_label,fans.avatar user_img,cnt.user_id,mem.realname user_name,cnt.article_title,cnt.article_content,cnt.article_img,cnt.article_video,cnt.comment_time,cnt.article_label_color')->where('mem.id=fans.id_member and mem.id=cnt.user_id ' . $map)->group('cnt.id')->order('cnt.display_order desc,cnt.comment_time desc')->limit($pre, $limits)->select();
            }else
            {
                //查询文章数据
                $res = Db::name('find_content cnt,ims_bj_shopn_member mem,ims_fans fans')->field('cnt.id article_id,cnt.article_label,fans.avatar user_img,cnt.user_id,mem.realname user_name,cnt.article_title,cnt.article_content,cnt.article_img,cnt.article_video,cnt.comment_time,cnt.article_label_color')->where('mem.id=fans.id_member and mem.id=cnt.user_id ')->group('cnt.id')->order('cnt.display_order desc,cnt.comment_time desc')->limit($pre, $limits)->select();
            }
            if ($res) {
                foreach ($res as $v) {
                    $v['article_img'] = $v['article_img'] == '' ? [] : json_decode($v['article_img']);
                    //获取浏览和评论次数
                    $v['see_num'] = 0;
                    $v['comment_num'] = 0;
                    $data1 = $this->getArticelSum1($v['article_id']);
                    if ($data1) {
                        $v['see_num'] = $data1['see_num'];
                        $v['comment_num'] = $data1['comment_num'];
                    }
                    $colors_l1=[];$label1='';
                    if ($v['article_label']) {
                        $label = json_decode($v['article_label']);
                        $colors_l = json_decode($v['article_label_color']);
                        foreach($label as $k_l=>$v_l)
                        {
                            $res_l['text'] = $v_l;
                            $res_l['color'] = $colors_l[$k_l];
                            $label1[] = $res_l;
                        }
                        $v['article_label'] = $label1;
                    } else {
                        $v['article_label'] = [];
                    }
                    if (!strstr($v['user_img'], 'http://')) {
                        $v['user_img'] = '';
                    }
                    unset($v['article_label_color']);
                    $data2[] = $v;
                }
                $data = $data2;
            }
            if(!$key)
            {
                parent::setRedisP('get_content_'.$page,$data,3600);
            }

        }
        $ret = $this->returnMsg($code,$data,$msg);
        return $ret;
    }

    /*
     * 功能: 收藏文章
     * 请求:
     * 返回:
     * */
    public function set_collet()
    {

        //获取请求数据
        $user_id = input('user_id')==''?1:input('user_id');
        $actrile_id = input('actrile_id')==''?1:input('actrile_id');
        $type = input('type')==''?1:input('type');
        //初始化设置
        $code = 0;$msg='收藏失败';$data=[];$flag=0;

        //设置收藏数据
        if(!($user_id && $actrile_id))
        {
            $flag = 2;
        }else
        {
            //查询是否存在
            $res = Db::name('find_content_collect')->field('id,type')->where('actrile_id='.$actrile_id.' and user_id='.$user_id)->limit(1)->select();
            //查找发布文章的作者
            $res_author  = Db::name('find_content')->field('id,user_id')->where('id',$actrile_id)->limit(1)->select();
            if($res)
            {
                //存在
                $data1 = array('type'=>0,'upd_time'=>date('Y-m-d H:i:s'));
                if($type == 1)
                {
                    $data1['type'] = 1;
                    $msg='收藏成功';
                    if($res[0]['type'] == 0)
                    {
                        //收藏次数+1
                        $this->getArticelSum1($actrile_id,'collect_num',1);
                        //用户被收藏总数+1
                        if($res_author)
                        {
                            $this->getUserSum1($res_author[0]['user_id'],'be_collect_num',1);
                        }
                    }
                }else
                {
                    $msg='取消收藏';
                    if($res[0]['type'] == 1)
                    {
                        //收藏次数-1
                        $this->getArticelSum1($actrile_id,'collect_num',-1);
                        //用户被收藏总数+1
                        if($res_author)
                        {
                            $this->getUserSum1($res_author[0]['user_id'],'be_collect_num',-1);
                        }
                    }
                }
                Db::name('find_content_collect')->where('id='.$res[0]['id'])->update($data1);
                $collet_id = $res[0]['id'];
            }else
            {
                //不存在 1=>入库
                if($type == 1)
                {
                    $data1 = array('user_id'=>$user_id,'actrile_id'=>$actrile_id,'ins_time'=>date('Y-m-d H:i:s'));
                    $res = Db::name('find_content_collect')->insert($data1);
                    $collet_id = Db::name('find_content_collect')->getLastInsID();
                    $msg='收藏成功';
                    //收藏次数+1
                    $this->getArticelSum1($actrile_id,'collect_num',1);
                    //用户被收藏总数+1
                    if($res_author)
                    {
                        $this->getUserSum1($res_author[0]['user_id'],'be_collect_num',1);
                    }
                }
            }
            $flag = 1;$code = 1;$data=array('id'=>$collet_id);
        }
        if($flag == 2)
        {
            $code = 0;$msg='请求参数不能为空';
        }

        return $this->returnMsg($code,$data,$msg);
    }

    /*
     * 功能: 获取收藏文章列表
     * 请求:
     * 返回:
     * */
    public function get_collet()
    {

        //获取请求数据
        $user_id = input('user_id')==''?1:input('user_id');
        $page = input('page')==''?1:input('page');
        //获取redis里的数据
        $ret = parent::getRedisP('get_collet_'.$user_id.'_'.$page);
        if($ret)
        {
            return $ret ;
        }
        //初始化设置
        $code = 0;$msg='获取失败';$data=[];$flag=0;
        //分页,每页显示30条
        $limits = 50;$pre = ($page-1)*$limits;
        //显示收藏数据
        if($user_id)
        {
            //查询是否存在    日期,文章封面图,文章标题,文章作者,头像,收藏人数
            $res = Db::name('find_content_collect fcc,think_find_content cnt,ims_bj_shopn_member mem,ims_fans fans')->field('cnt.id ,fans.avatar user_img,cnt.user_id,mem.realname user_name,cnt.article_title,cnt.article_img,cnt.comment_time,cnt.article_video')->where(' mem.id=fans.id_member and mem.id=cnt.user_id and fcc.actrile_id=cnt.id and fcc.type=1  and fcc.user_id='.$user_id)->limit($pre,$limits)->group('fcc.id')->order('cnt.comment_time desc')->select();
            if($res)
            {
                foreach($res as &$v)
                {
                    $data1['date_time'] = date('Y-m-d',strtotime($v['comment_time']));
                    $data1['article_id'] = $v['id'];
                    $v['article_img'] = $v['article_img']==''?'':json_decode($v['article_img']);
                    $data1['article_img'] = $v['article_img'][0];
                    $data1['article_title'] = $v['article_title'];
                    $data1['author'] = $v['user_name'];
                    $data1['user_id'] = $v['user_id'];
                    if(strstr($v['user_img'],'http://'))
                    {
                        $data1['img_tx'] = $v['user_img'];
                    }
                    $data1['article_collet_num'] = 0;
                    //获取发布文章和被收藏数量
                    $res2 = $this->getArticelSum1($v['id']);
                    if($res2)
                    {
                        $data1['article_collet_num'] = $res2['collect_num'];
                    }
                    //resource_type=>1=>图片,2=>视频
                    $data1['resource_type'] =1;
                    if($v['article_video'])
                    {
                        $data1['resource_type'] = 2;
                    }
                    $data[] = $data1;

                }
            }
            $flag = 1;$code = 1;$msg='获取成功';
            $ret = $this->returnMsg($code,$data,$msg);
            parent::setRedisP('get_collet_'.$user_id.'_'.$page,$ret,60);
        }else
        {
            $flag = 2;
        }
        if($flag == 2)
        {
            $code = 0;$msg='user_id和page不能为空';
            $ret = $this->returnMsg($code,$data,$msg);
        }
        return $ret;
    }

    /*
     * 功能: 他人的个人中心
     * 请求: user_id=>用户id,
     * 返回:
     * */
    public function other_info($user_id='')
    {
        //初始化设置
        $code = 1;$msg = '获取成功';$data = [];$data1=null;
        //获取redis里的数据
        $ret = parent::getRedisP('other_info_'.$user_id);
        if(!$ret) {
            //查询发布文章的个人信息
            //头像,名字,地点,发布文章数量,被收藏数量
            //分享列表:日期,文章封面图,文章标题,文章作者,头像,收藏人数

            $res = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,ims_fans fan')->field('fan.avatar,mem.realname,ibb.location_p')->where('mem.id=fan.id_member and mem.storeid=ibb.id and mem.id='.$user_id)->limit(1)->select();
            if ($res) {
                //获取发布文章和被收藏数量
                $data1['article_num'] = 0;$data1['article_collet_num'] = 0;
                $data3 = $this->getUserSum1($user_id,'see_num',1);
                if($data3)
                {
                    $data1['article_num'] = $data3['article_num'];//发布文章数
                    $data1['article_collet_num'] = $data3['be_collect_num'];//被收藏总数
                }
                $data1['img_tx'] = '';
                if(strstr($res[0]['avatar'],'http://'))
                {
                    $data1['img_tx'] = $res[0]['avatar'];
                }
                $data1['name'] = $res[0]['realname'];
                $data1['address'] = $res[0]['location_p'];

                //发布文章列表
                $data1['article_list'] = [];
                $res4 = Db::name('find_content')->field('comment_time,article_img,article_title,id,article_video')->where('user_id',$user_id)->order('comment_time desc')->select();
                if($res4)
                {
                    foreach($res4 as $v)
                    {
                        $data2['article_id'] = $v['id'];
                        $data2['date_time'] = date('Y-m-d',strtotime($v['comment_time']));//截取时间为日期
                        $img_tx = json_decode($v['article_img']);
                        $data2['article_img'] = $img_tx[0];//取第一张图片作为文章的封面
                        $data2['article_title'] = $v['article_title'];
                        $data2['author'] = $data1['name'];
                        $data2['user_id'] = $user_id;
                        $data2['img_tx'] = $data1['img_tx'];
                        $data2['article_collet_num'] = 0;
                        //每篇文章收藏数
                        $res5 = $this->getArticelSum1($v['id']);
                        if($res5)
                        {
                            $data2['article_collet_num'] = $res5['collect_num'];
                        }
                        //资源类型,1=>图片,2=>视频
                        $data2['resource_type'] = 1;
                        if($v['article_video'])
                        {
                            $data2['resource_type'] = 2;
                        }
                        $data1['article_list'][] = $data2;
                    }
                }
                $data = $data1;
//                parent::setRedisP('other_info_'.$user_id,$data,3600*24);
            }else {
                $msg = '暂无数据';
            }
        }else
        {
            $data = $ret;
        }
        $ret = $this->returnMsg($code,$data,$msg);
        return $ret;
    }

    /*
     * 功能: 个人中心-主播
     * 请求: user_id=>用户id,
     * 返回:
     * */
    public function anchor_info($user_id='')
    {
        //初始化设置
        $code = 1;$msg = '获取成功';$data = [];$data1=null;
        //获取redis里的数据
        $ret = parent::getRedisP('anchor_info_'.$user_id);
        if(!$ret) {
            //地点,关注数量,粉丝数量,积分数量
            $res = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('ibb.location_p')->where('mem.storeid=ibb.id and mem.id='.$user_id)->limit(1)->select();
            if ($res) {
                $data1['address']= $res[0]['location_p'];
                //关注数量 、粉丝数量、积分数量
                $res1 = $this->getUserSum1($user_id);
                //积分数量
                /*
                 * 预留
                 * */
                $data1['gz_num']=0;$data1['fs_num']=0;$data1['score']=0;
                if($res1)
                {
                    $data1['gz_num'] = $res1['follow_num'];
                    $data1['fs_num'] = $res1['fans_num'];
                    $data1['score'] = $res1['scores'];
                }
                $data = $data1;
//                parent::setRedisP('other_info_'.$user_id,$data,3600*24);
            }else {
                $msg = '暂无数据';
            }
        }else
        {
            $data = $ret;
        }
        $ret = $this->returnMsg($code,$data,$msg);
        return $ret;
    }

    /*
     * 功能: 个人中心-主播-资料
     * 请求: user_id=>用户id,
     * 返回:
     * */
    public function anchor_info_own($user_id='')
    {
        //初始化设置
        $code = 1;$msg = '获取成功';$data = [];$data1=null;
        //获取redis里的数据
        $ret = parent::getRedisP('anchor_info_own'.$user_id);
        if(!$ret) {
            //地点,关注数量,粉丝数量,积分数量
            $res = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,ims_fans fan')->field('fan.avatar,mem.realname,ibb.location_p,mem.mobile')->where('mem.id=fan.id_member and mem.storeid=ibb.id and mem.id='.$user_id)->limit(1)->select();
            if ($res) {
                $data1['img_tx'] = '';$data1['gz_num']='';$data1['fs_num']='';$data1['replay_video']=array();
                if(strstr($res[0]['avatar'],'http://'))
                {
                    $data1['img_tx'] = $res[0]['avatar'];
                }
                $data1['name'] = $res[0]['realname'];
                $data1['address'] = $res[0]['location_p'];
                $res1 = $this->getUserSum1($user_id);
                if($res1)
                {
                    $data1['gz_num']=$res1['follow_num'];
                    $data1['fs_num']=$res1['fans_num'];
                }

                //查询是否存在  日期,封面,观看,点赞,视频
                //后台admin直播,绑定159号码
                if($res[0]['mobile']=='15921324164')
                {
                    $res[0]['mobile'] = 1;
                }

                if($res[0]['mobile'])
                {
                    $res2 = Db::name('live')->field('id,insert_time,live_img,see_url')->where(' db_statu=2 and user_id='.$res[0]['mobile'])->order('insert_time asc')->select();
//                $res2 = Db::name('live')->field('id,insert_time,live_img,see_url')->where('user_id',$res[0]['mobile'])->order('insert_time asc')->select();
                    if ($res2) {
                        foreach($res2 as $v)
                        {
                            //取redis里的观看数和点赞数
                            $data2['time'] = date('Y-m-d',$v['insert_time']);
                            $data2['img'] = $v['live_img'];
                            $data2['gk_num'] = parent::getRedisP($v['id'].'_see')==0?1:parent::getRedisP($v['id'].'_see');
                            $data2['dz_num'] = parent::getRedisP($v['id'])==0?1:parent::getRedisP($v['id']);
                            $data2['video'] = $v['see_url'];
                            $data1['replay_video'][] = $data2;
                        }
                    }
                }
                $data = $data1;
//                parent::setRedisP('anchor_info_own'.$user_id,$data,3600*24);
            }
        }else
        {
            $data = $ret;
        }
        $ret = $this->returnMsg($code,$data,$msg);
        return $ret;
    }

    /*
     * 功能: 获取标签列表接口
     * 请求:
     * 返回:
     * */
    public function get_label_list()
    {
        //初始化数据
        $code = 1;$msg='获取成功';$data=[];$labels=[];$labels_color=[];
        // start Modify by wangqin 2018-02-05 增加标签颜色下发
        $list = Db::name('find_content')->field('article_label,article_label_color')->order('comment_time desc')->select();
        if($list)
        {
            foreach($list as $v)
            {
                 if($v['article_label'])
                 {
                     $label = json_decode($v['article_label']);
                     $labels = array_merge($labels,$label);
                     $label = [];
                     //颜色
                     $label_color = json_decode($v['article_label_color']);
                     $labels_color = array_merge($labels_color,$label_color);
                 }
            }
            $labels = array_unique($labels);
            if($labels)
            {
               foreach($labels as $k=>$v)
               {
                   $data[] = array('article_label'=>$v,'article_label_color'=>$labels_color[$k]);
               }
            }
        }
        return $this->returnMsg($code,$data,$msg) ;
    }

    /*
     * 功能: (内部调用)获取文章相关统计数据
     * 请求: id=>文章id
     * 返回:
     * */
    public function getArticelSum1($id,$type=null,$num=0)
    {
        $data=[];
        $res = Db::name('sum_article')->field('see_num,comment_num,collect_num')->where('article_id',$id)->limit(1)->select();
        if($res)
        {
            $data = array('see_num'=>$res[0]['see_num'],'comment_num'=>$res[0]['comment_num'],'collect_num'=>$res[0]['collect_num']);
        }else
        {
            //think_sum_article无数据,插入1条
            //生成统计数据
            $comment_num=0;$collect_num=0;
            $comment_num = Db::name('find_content_comment')->where('article_id',$id)->count();
            $collect_num = Db::name('find_content_collect')->where("actrile_id=$id and type=1")->count();
            $data1 = array('article_id'=>$id,'ins_time'=>date('Y-m-d H:i:s'),'see_num'=>0,'comment_num'=>$comment_num,'collect_num'=>$collect_num);
            Db::name('sum_article')->insert($data1);
            $data = array('see_num'=>0,'comment_num'=>$comment_num,'collect_num'=>$collect_num);
        }
        //增加记录数
        if($type)
        {
            //增加see_num值
            if($type=='see_num')
            {
                $data['see_num'] += $num;
            }else if($type=='comment_num')
            {
                //增加comment_num值
                $data['comment_num'] += $num;
            }else if($type=='collect_num')
            {
                //增加comment_num值
                $data['collect_num'] += $num;
            }
            if($data['see_num']<1)
            {
                $data['see_num'] = 0;
            }
            if($data['comment_num']<1)
            {
                $data['comment_num'] = 0;
            }
            if($data['collect_num']<1)
            {
                $data['collect_num'] = 0;
            }
            $data['upd_time'] = date('Y-m-d H:i:s');
            //修改次数
            Db::name('sum_article')->where('article_id',$id)->update($data);
        }
        return $data;
    }

    public function getArticelSum($id,$type=null,$num=0)
    {
        //判断redis里是否有值,有的话存redis,没有取数据库
        $data=parent::getRedisHash('think_sum_article',$id);
        if(!$data)
        {
            $res = Db::name('sum_article')->field('see_num,comment_num,collect_num')->where('article_id',$id)->limit(1)->select();
            if($res)
            {
                $data = array('see_num'=>$res[0]['see_num'],'comment_num'=>$res[0]['comment_num'],'collect_num'=>$res[0]['collect_num']);
                parent::setRedisHash('think_sum_article',$id,json_encode($data));
            }else
            {
                //think_sum_article无数据,插入1条
                //生成统计数据
                $comment_num=0;$collect_num=0;
                $comment_num = Db::name('find_content_comment')->where('article_id',$id)->count();
                $collect_num = Db::name('find_content_collect')->where("actrile_id=$id and type=1")->count();
                $data1 = array('article_id'=>$id,'ins_time'=>date('Y-m-d H:i:s'));
                Db::name('sum_article')->insert($data1);
                $data = array('see_num'=>0,'comment_num'=>$comment_num,'collect_num'=>$collect_num);
            }
            $data = json_encode($data);
        }
        $data = json_decode($data);

        //增加记录数
        if($type)
        {
            //增加see_num值
            if($type=='see_num')
            {
                $data->see_num += $num;
            }else if($type=='comment_num')
            {
                //增加comment_num值
                $data->comment_num += $num;
            }else if($type=='collect_num')
            {
                //增加comment_num值
                $data->collect_num += $num;
            }
            if($data->see_num<1)
            {
                $data->see_num = 0;
            }
            if($data->comment_num<1)
            {
                $data->comment_num = 0;
            }
            if($data->collect_num<1)
            {
                $data->collect_num = 0;
            }
            parent::setRedisHash('think_sum_article',$id,json_encode($data));
        }
        return $data;
    }

    /*
     * 功能: (内部调用)获取用户相关统计数据
     * 请求: user_id=>用户id,type=>修改哪个数据,num=>修改数量,resource前后台调用区分
     * 返回:
     * */
    public function getUserSum1($user_id,$type=null,$num=0,$resource=null)
    {
        $res = Db::name('sum_user')->field('article_num,be_collect_num,follow_num,fans_num,scores')->where('user_id',$user_id)->limit(1)->select();
        if($res)
        {
            $data = array('article_num'=>$res[0]['article_num'],'be_collect_num'=>$res[0]['be_collect_num'],'follow_num'=>$res[0]['follow_num'],'fans_num'=>$res[0]['fans_num'],'scores'=>$res[0]['scores']);
        }else
        {
            //用户统计
            $be_collect_num = 0;$article_num=0; $follow_num=0; $fans_num=0;$scores=0;
            $article_num =Db::name('find_content')->where('user_id',$user_id)->count();
            $be_collect_num =Db::name('find_content_collect fcc,think_find_content fc')->field('count(0) cnt')->where("fcc.actrile_id=fc.id and fc.user_id=$user_id")->select();
            if($be_collect_num)
            {

                $be_collect_num = $be_collect_num[0]['cnt'];
            }
            $follow_num =Db::name('follow')->where("relation_type=1 and user_id=$user_id")->count();
            $fans_num =Db::name('follow')->where("relation_type=1 and follower_id=$user_id")->count();
            //think_sum_article无数据,插入1条
            //第一次插入
            if($num==1&&$resource='ht')
            {
                $article_num = $article_num-1;
            }
            if($num==1&&$resource='gz')
            {
                $follow_num = $follow_num-1;
            }
            if($num==1&&$resource='fs')
            {
                $fans_num = $fans_num-1;
            }
            if($num==1&&$resource='jf')
            {
                $scores = $scores-1;
            }
            $data1 = array('user_id'=>$user_id,'ins_time'=>date('Y-m-d H:i:s'),'article_num'=>$article_num,'be_collect_num'=>$be_collect_num,'follow_num'=>$follow_num,'fans_num'=>$fans_num,'scores'=>0);
            $data = array('article_num'=>$article_num,'be_collect_num'=>$be_collect_num,'follow_num'=>$follow_num,'fans_num'=>$fans_num,'scores'=>0);
            Db::name('sum_user')->insert($data1);
        }
        //增加记录数
        if($type)
        {
            //增加see_num值
            if($type=='article_num')
            {
                $data['article_num'] += $num;
            }else if($type=='be_collect_num')
            {
                //增加comment_num值
                $data['be_collect_num'] += $num;
            }else if($type=='follow_num')
            {
                //增加comment_num值
                $data['follow_num'] += $num;
            }else if($type=='scores')
            {
                //增加comment_num值
                $data['scores'] += $num;
            }else if($type=='fans_num')
            {
                //增加comment_num值
                $data['fans_num'] += $num;
            }

            if($data['article_num'] <1)
            {
                $data['article_num']  = 0;
            }
            if($data['be_collect_num']<1)
            {
                $data['be_collect_num'] = 0;
            }
            if($data['follow_num']<1)
            {
                $data['follow_num'] = 0;
            }
            if($data['scores']<1)
            {
                $data['scores'] = 0;
            }
            if($data['fans_num']<1)
            {
                $data['fans_num'] = 0;
            }
            //修改次数
            $data['upd_time'] = date('Y-m-d H:i:s');
            Db::name('sum_user')->where('user_id',$user_id)->update($data);
        }
        return $data;
    }

    public function getUserSum($user_id,$type=null,$num=0)
    {
        //判断redis里是否有值,有的话存redis,没有取数据库
        $data=parent::getRedisHash('think_sum_user',$user_id);
        if(!$data)
        {
            $res = Db::name('sum_user')->field('article_num,be_collect_num,follow_num,fans_num,scores')->where('user_id',$user_id)->limit(1)->select();
            if($res)
            {
                $data = array('article_num'=>$res[0]['article_num'],'be_collect_num'=>$res[0]['be_collect_num'],'follow_num'=>$res[0]['follow_num'],'fans_num'=>$res[0]['fans_num'],'scores'=>$res[0]['scores']);
                parent::setRedisHash('think_sum_user',$user_id,json_encode($data));
            }else
            {
                //用户统计
                $be_collect_num = 0;$article_num=0; $follow_num=0; $fans_num=0;
                $article_num =Db::name('find_content')->where('user_id',$user_id)->count();
                $be_collect_num =Db::name('find_content_collect fcc,think_find_content fc')->field('count(0) cnt')->where("fcc.actrile_id=fc.id and fc.user_id=$user_id")->select();
                if($be_collect_num)
                {

                    $be_collect_num = $be_collect_num[0]['cnt'];
                }
                $follow_num =Db::name('follow')->where("relation_type=1 and user_id=$user_id")->count();
                $fans_num =Db::name('follow')->where("relation_type=1 and follower_id=$user_id")->count();
                //think_sum_article无数据,插入1条
                $data1 = array('user_id'=>$user_id,'ins_time'=>date('Y-m-d H:i:s'));
                Db::name('sum_user')->insert($data1);
                $data = array('article_num'=>$article_num,'be_collect_num'=>$be_collect_num,'follow_num'=>$follow_num,'fans_num'=>$fans_num,'scores'=>0);
            }
            $data = json_encode($data);
        }
        $data = json_decode($data);

        //增加记录数
        if($type)
        {
            //增加see_num值
            if($type=='article_num')
            {
                $data->article_num += $num;
            }else if($type=='be_collect_num')
            {
                //增加comment_num值
                $data->be_collect_num += $num;
            }else if($type=='follow_num')
            {
                //增加comment_num值
                $data->follow_num += $num;
            }else if($type=='scores')
            {
                //增加comment_num值
                $data->scores += $num;
            }else if($type=='fans_num')
            {
                //增加comment_num值
                $data->fans_num += $num;
            }

            if($data->article_num<1)
            {
                $data->article_num = 0;
            }
            if($data->be_collect_num<1)
            {
                $data->be_collect_num = 0;
            }
            if($data->follow_num<1)
            {
                $data->follow_num = 0;
            }
            if($data->scores<1)
            {
                $data->scores = 0;
            }
            if($data->fans_num<1)
            {
                $data->fans_num = 0;
            }

            parent::setRedisHash('think_sum_user',$user_id,json_encode($data));
        }
        return $data;
    }

    /*
     * 功能: (内部调用)随机获取不同颜色值
     * 请求: arr=>数组
     * 返回: arr1=>整合好颜色的数组
     * */
    public function getColor($arr,$key_name=null)
    {
        $data=[];
        if(!empty($arr))
        {
            $cnt = count($arr);
            $sql_c = Db::query('select colors from think_color order by rand() limit '.$cnt);
            foreach($arr as $k=>$v)
            {
                $data1[$key_name] = $v;
                $data1['color'] = $sql_c[$k]['colors'];
                $data[] = $data1;
            }
        }
        return $data;
    }

    // start Modify by wangqin 2018-03-02
    /*
     * 功能: 手机端发布文章及视频
     * 请求:   label=>标签名称;title=>文章标题;label=>文章内容;user_id=>用户id ;pic_url=>多张图片url json串
     * 返回:
     * */
    public function mobile_edit()
    {
        //请求数据
        $label = input('label','') ;
        $title = input('title','') ;
        $content = input('content','') ;
        $user_id = input('user_id','') ;
        $pic_url = input('pic_url');//json数组
        //初始化数据
        $code=1;$msg='发布成功';$data=['user_id'=>$user_id,'id'=>0]; $article_video='';$article_img='';
        if($pic_url)
        {
            $pic_url = json_decode($pic_url);
            foreach($pic_url as $v){
                if(preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $v))
                {
                    //获取图片大小
                    $img_size = getImageinfo($v);
                    $data1 = array('img_url'=>$v,'width'=>$img_size['width'],'height'=>$img_size['height']);
                    $article_img[] = $data1;
                }else
                {
                    $article_video = $v;
                }
                $file_url[] = $v;
            }
        }
        if($label && $title && $content && $user_id && $file_url)
        {
            //使用admin模块下类的方法
            $label = explode(',',$label);
            $label_colors = getColors(count($label));
            $data_v = array('user_id'=>$user_id,'article_img'=>json_encode($article_img,JSON_UNESCAPED_SLASHES),'article_title'=>$title,'article_content'=>$content,'article_video'=>$article_video,'comment_time'=>date('Y-m-d H:i:s'),'article_label'=>json_encode($label,JSON_UNESCAPED_UNICODE),'article_label_color'=>$label_colors,'resource'=>2);
            Db::name('find_content_review')->insert($data_v);
              $data['id'] =  Db::name('find_content_review')->getLastInsID();
        }else
        {
            $code=0;$msg='发布失败,请求参数不能为空';
        }

        return $this->returnMsg($code,$data,$msg);

    }

    // end Modify by wangqin 2018-03-02
    // start Modify by wangqin 2018-03-06
    /*
     * 功能: 排行榜
     * 请求:   label=>标签名称;title=>文章标题;label=>文章内容;user_id=>用户id ;pic_url=>多张图片url json串
     * 返回:
     * */
    public function ranking_list()
    {
        /*2017年12月,12月
ranking,head_img,name,location,scores*/
        //请求数据

        $user_id = input('user_id');
        //初始化数据
        $code=1;$msg='获取成功';$data=[];
        $y = date('Y');$m=date('m');$data2='';$a=1;$flag=1;
        $res = Db::table('think_sum_user sum')->join(['ims_bj_shopn_member'=>'mem'],'sum.user_id=mem.id','LEFT')->join(['ims_fans'=>'fan'],'sum.user_id=fan.id_member','LEFT')->join(['ims_bwk_branch'=>'ibb'],'mem.storeid=ibb.id','LEFT')->field('sum.user_id,sum.scores,mem.realname,fan.avatar,ibb.location_p')->where('sum.scores>0')->order('sum.scores desc')->group('sum.user_id')->select();
        if($res)
        {
            foreach($res as $k=>$v)
            {
                $data1['ranking'] = $k+1;
                $data1['head_img'] = '';
                if(strstr($v['avatar'],'http://'))
                {
                    $data1['head_img'] = $v['avatar'];
                }
                $data1['name'] = $v['realname'];
                $data1['location'] = $v['location_p'];
                $data1['scores'] = $v['scores'];
                $data1['user_id'] = $v['user_id'];
                $data[] = $data1;
                $a++;
            }

            //自己排名放最后
           $res1 = Db::table('ims_bj_shopn_member mem')->join(['think_sum_user'=>'sum'],'sum.user_id=mem.id','LEFT')->join(['ims_fans'=>'fan'],'mem.id=fan.id_member','LEFT')->join(['ims_bwk_branch'=>'ibb'],'mem.storeid=ibb.id','LEFT')->field('mem.id,mem.realname,fan.avatar,ibb.location_p,sum.scores,sum.id')->where('mem.id='.$user_id)->limit(1)->select();
            if($res1)
            {
                foreach($data as $v_data)
                {
                    if($v_data['user_id'] == $user_id)
                    {
                         $data[] = $v_data;
                         $flag = 0;
                    }
                }
                if($flag)
                {
                    $data2['ranking'] = $a>$res1[0]['id']?$a:$res1[0]['id'];
                    $data2['head_img'] = '';
                    if(strstr($res1[0]['avatar'],'http://'))
                    {
                        $data2['head_img'] = $res1[0]['avatar'];
                    }
                    $data2['name'] = $res1[0]['realname'];
                    $data2['location'] = $res1[0]['location_p'];
                    $data2['scores'] = $res1[0]['scores']==''?0:$res1[0]['scores'];
                    $data2['user_id'] = $res1[0]['id']==null?(int)$user_id:$res1[0]['id'];
                    $data[] = $data2;
                }

            }
        }

        return $this->returnMsg($code,$data,$msg);

    }

    /*
     * 功能: 开关控制是否显示接口
     * 请求:
     * 返回:
     * */
    public function get_switch()
    {
        //请求数据
        $type = input('type','mobile_edit');
        //初始化数据
        $code=1;$msg='获取成功';$data['flag']=0;
        $res = Db::name('switch')->field('flag')->where('type',$type)->limit(1)->select();
        if($res)
        {
            $data['flag'] = $res[0]['flag'];
            if($data['flag'] == 0)
            {
                $msg='关闭';
            }else{
                $msg='开启';
            }
        }
        return $this->returnMsg($code,$data,$msg);
    }

    // start Modify by wangqin 2018-03-14
    /*
     * 功能: 修改积分
     * 请求: user_id=>用户id,type=>类型,orderid=>支付订单id
     * */
    public function upd_scores($arr=1)
    {
        //初始化数据
        $code=1;$msg='修改成功';$data['scores']=0;
        $wms_url = 'https://wms.chengmei.com/api/app/';
        // $wms_url = 'https://wms.canmay.net/api/app/';
        //请求数据
        if(is_array($arr))
        {
            $user_id = $arr['user_id'];$type = $arr['type'];$orderid = @$arr['orderid'];
        }else
        {
            $user_id = input('user_id');$type = input('type');$orderid = input('orderid');
            //候哥库存接口需要的参数
            if($type=='scan')
            {
                $code1 = input('code');//物流追踪码
                $flag = 0;$scan_str='';$str_remark='';
                $info = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('ibb.sign,mem.pid,mem.mobile,mem.realname,mem.isadmin,ibb.id')->where(' mem.storeid=ibb.id and mem.id='.$user_id)->limit(1)->select();
                if($info)
                {
                    //店老板不能扫码
                    if($info[0]['isadmin'] == 1)
                    {
                        return $this->returnMsg(0,$data,'店老板不能使用扫码积分');
                    }
                    $sign = substr($info[0]['sign'],0,7);$mobile = $info[0]['mobile'];$pid = $info[0]['pid'];
                    // 检测物流码是否可兑换
                    $data_s = array('sign' => $sign, 'code' => $code1);
                    $result = curlPost($wms_url.'checkCode',$data_s);
                    // var_dump($result);exit;
                    if($result)
                    {
                        $res_s = json_decode($result);
                        // var_dump($res_s);exit;
                        // 成功
                        if($res_s->code)
                        {
                            // 根据物流码减库存
                            $info2 = Db::table('ims_bj_shopn_member mem')->field('realname,mobile')->where('id',$pid)->limit(1)->select();
                            if($info2)
                            {
                                $s_name = $info2[0]['realname'];$s_mobile = $info2[0]['mobile'];
                            }else
                            {
                                $s_name = $info[0]['realname'];$s_mobile = $info[0]['mobile'];
                                // 找不到的上级填自己
                                $pid=$user_id ;
                            }
                            $data_s2 = array('sign'=>$sign,'code'=>$code1,'mobile'=>$mobile,'s_name'=>$s_name,'s_mobile'=>$s_mobile);
                            $result2 = curlPost($wms_url.'exchangeCode',$data_s2);
                            // var_dump($result2);exit;
                            if($result2)
                            {
                                $res_s2 = json_decode($result2);

                                if($res_s2->code)
                                {
                                    $msg='减库存成功';
                                    $scan_str = '门店编号为'.$sign.',物流码为'.$code1.','.$msg.',';
                                    //根据追踪码获取产品积分API
                                    $data_jf = array('code' => $code1);
                                    $result_jf = curlPost($wms_url.'getScore',$data_jf);
                                    if($result_jf)
                                    {
                                        $res_jf = json_decode($result_jf);
                                        if($res_jf->code)
                                        {
                                            $data_jf2 = $res_jf->data;
                                            if($data_jf2)
                                            {
                                                $jf_customer = $data_jf2->commission_customer;
                                                $jf_seller = $data_jf2->commission_seller;
                                                $jf_boss = $data_jf2->commission_boss;
                                                // 添加自己积分
                                                $this->getUserSum1($user_id,$type='scores',$jf_customer);
                                                //添加记录
                                                $str_remark1 = $scan_str.'减库存成功,扫码,添加积分'.$jf_customer;
                                                $str_remark .= $scan_str;
                                                $str_remark .= $user_id.'顾客添加积分'.$jf_customer;
                                                // start Modify by wangqin 2018-05-31
                                                /*
                                                增加code状态,code=1原逻辑不变;code=2 , 只增加顾客积分,记录美容师+店老板积分+兑换流水号 , 提供根据兑换流水号增加 美容师和店老板积分接口
                                                 */
                                                if($res_s2->code == 1)
                                                {
                                                    // 添加上级美容师积分
                                                    $this->getUserSum1($pid,$type='scores',$jf_seller);
                                                    $str_remark .= ','.$pid.'美容师添加积分'.$jf_seller;
                                                    // 查询店老板
                                                    $boss = Db::table('ims_bj_shopn_member')->field('id')->where('isadmin=1 and storeid='.$info[0]['id'])->limit(1)->select();
                                                    if($boss)
                                                    {
                                                        //添加老板积分
                                                        $this->getUserSum1($boss[0]['id'],$type='scores',$jf_boss);
                                                        $str_remark .= ','.$boss[0]['id'].'店老板添加积分'.$jf_boss;
                                                    }
                                                }else if($res_s2->code == 2)
                                                {
                                                    // 记录兑换流水号,美容师和店老板积分
                                                    // flow_number,beautician_id,beautician_score,boss_id,boss_score,flag
                                                    // 查询店老板
                                                    $boss = Db::table('ims_bj_shopn_member')->field('id')->where('isadmin=1 and storeid='.$info[0]['id'])->limit(1)->find();
                                                    $data_shenhe = array('flow_number' =>$res_s2->data , 'beautician_id'=>$pid,'beautician_score' =>$jf_seller , 'boss_id'=>$boss['id'],'boss_score' =>$jf_boss , 'create_time'=>date('Y-m-d H:i:s'));
                                                    //记录数据
                                                    Db::name('scores_shenhe')->insert($data_shenhe);
                                                }

                                                // end Modify by wangqin 2018-05-31
                                                $data_jl1 = array('user_id'=>$user_id,'type'=>'scan','msg'=>$str_remark1,'scores'=>$jf_customer,'log_time'=>date('Y-m-d H:i:s'),'remark'=>$str_remark);
                                                Db::name('scores_record')->insert($data_jl1);

                                                $data['scores'] = $jf_customer;$msg = $str_remark1;
                                                return $this->returnMsg(1,$data,$msg);
                                            }
                                        }else
                                        {
                                            return $this->returnMsg(0,$data,'根据追踪码获取产品积分失败');
                                        }
                                    }
                                }else
                                {
                                    $msg=$res_s2->msg;
                                    $flag = 1;$code=0;
                                }
                            }else
                            {
                                $msg='减库存失败';
                                $flag = 1;$code=0;
                            }
                        }else
                        {
                            $msg = $res_s->msg;
                            $flag = 1;$code=0;
                        }
                    }else
                    {
                       $msg = '物流码兑换检测返回失败';
                       $flag = 1;$code=0;
                    }
                }

                if($flag)
                {
                    return $this->returnMsg($code,$data,$msg);
                }
            }
        }


        $res = parent::checkReq(['user_id'=>$user_id,'type'=>$type]);
        if(!$res)
        {
            return $this->returnMsg($code,$data,'必填请求参数不能为空');
        }
        $goodsid = '';$scores=0;$score='';$wen='';
        if($type=='goods' && $orderid)
        {
            //根据订单号查询父商品id
            $fu_id = Db::table('ims_bj_shopn_order_goods ordg')->join(['ims_bj_shopn_order'=>'ord'],'ordg.orderid=ord.id','LEFT')->field('ordg.goodsid')->where('ord.id='.$orderid)->select();
            if($fu_id)
            {
                foreach($fu_id as $fu_v)
                {
                     $goodsid .= $fu_v['goodsid'].',';
                }
                $goodsid =  rtrim($goodsid,',');
                //根据父商品id获取要加的积分数
                $score = Db::name('scores_config')->field('scores')->where("type_val in ($goodsid)")->select();
                if(!$score)
                {
                    $score = Db::name('scores_config')->field('scores')->where('id',4)->select();
                }
                $msg='购买商品,订单id为'.$orderid.'添加积分';
            }

        }else{
            //查询要获取的积分
            $score = Db::name('scores_config')->field('scores')->where('type',$type)->select();
            if($type=='live')
            {
                $wen = '开启直播';
            }elseif($type=='content')
            {
                $wen = '发布文章';
            }elseif($type=='sign')
            {
                $wen = '签到';
            }elseif($type=='login')
            {
                $wen = '登录';
            }elseif($type=='scan')
            {
                $wen = '扫码';
            }elseif($type=='comment')
            {
                $wen = '每日第一次评论';
            }else
            {
                $wen = '购买商品';
            }
            $msg=$wen.',添加积分';
        }
        if($score)
        {
            foreach($score as $s_v)
            {
                $scores += $s_v['scores'];
            }
            $msg .= $scores;
            if($type == 'scan')
            {
                if($scan_str)
                {
                    $msg = $scan_str.$msg;
                }
            }
            $data['scores'] = $scores;
            $data_v = array('user_id'=>$user_id,'type'=>$type,'msg'=>$msg,'scores'=>$scores,'log_time'=>date('Y-m-d H:i:s'));
            //添加积分
            $this->getUserSum1($user_id,$type='scores',$scores);
            //添加记录
            Db::name('scores_record')->insert($data_v);
        }

        return $this->returnMsg($code,$data,$msg);

    }
    // end Modify by wangqin 2018-03-14
    //
    // start Modify by wangqin 2018-03-21
    /*
     * 功能: 积分明细
     * 请求: user_id=>用户id
     * */
    public function scores_info()
    {
        //初始化数据
        $code=1;$msg='获取成功';$data=[];
        $user_id = input('user_id');
        if($user_id)
        {
            $res = Db::name('scores_record')->field('user_id,type,msg,scores,log_time')->where('user_id',$user_id)->order('log_time desc')->select();
            if($res)
            {
                foreach ($res as $v) {
                    $data1['msg'] = $v['msg'];
                    $data1['scores'] = $v['scores'];
                    $data1['log_time'] = $v['log_time'];
                    $data[] = $data1;
                }
            }

        }else
        {
          $code=0;$msg='用户id不能为空';
        }
        return $this->returnMsg($code,$data,$msg);
    }
    // end Modify by wangqin 2018-03-21
}