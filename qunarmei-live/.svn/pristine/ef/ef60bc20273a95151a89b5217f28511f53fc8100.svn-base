<?php

namespace app\api\controller;

use think\Db;
//使用redis扩展
//use think\cache\driver\Redis;
/**
 * Follow: App 关注粉丝相关功能的接口
 */
class Follow  extends Base
{

    /*
     * 功能: 关注
     * 请求: user_id=>用户id;follower_id=>被关注者用户id;relation_type=>0=>取消关注;1=>关注;live_id=>直播间id
     * 返回:
     * */
    public function follow_user()
    {
        //初始化数据
        $code = 0;$msg='关注失败';$data=[];$flag=0;
        //获取请求数据
        $user_id = input('user_id')==''?1:input('user_id');
        $follower_id = input('follower_id')==''?1:input('follower_id');
        $relation_type = input('relation_type')==''?1:input('relation_type');
        $live_id = input('live_id')==''?'':input('live_id');
        if(!($user_id && ($follower_id || $live_id)))
        {
            $flag = 2;
        }else
        {
            //直播间关注
            if($live_id)
            {
                $mobile='';
                $res_uid = Db::name('live')->field('user_id')->where('id',$live_id)->limit(1)->select();
                //admin,后台开的直播
                if($res_uid[0]['user_id']==1)
                {
                    $mobile = '15921324164';
                }else
                {
                    $mobile = $res_uid[0]['user_id'];
                }
                $follower_id = Db::table('ims_bj_shopn_member m')->field('m.id')->where("m.mobile='$mobile'")->limit(1)->select();
                if($follower_id)
                {
                    $follower_id = $follower_id[0]['id'];
                }
            }
            //查询是否已关注
            $res_gz = new FindContent();
            $res1 = Db::name('follow')->field('relation_type')->where('user_id='.$user_id.' and follower_id='.$follower_id)->limit(1)->select();
            if($relation_type==0)
            {
                //取消关注
                $data3 = array('relation_type'=>0,'upd_time'=>date('Y-m-d H:i:s'));
                if($res1)
                {
                    if($res1[0]['relation_type']==1)
                    {
                        //关注数-1
                        $res_gz->getUserSum1($user_id,'follow_num',-1);
                        //粉丝数-1
                        $res_gz->getUserSum1($follower_id,'fans_num',-1);
                    }
                }
                $res4 = Db::name('follow')->where('follower_id='.$follower_id.' and user_id='.$user_id)->limit(1)->update($data3);
                $msg='取消关注成功'; $flag = 1;

            }else
            {
                if($res1)
                {
                    $flag = 1;
                    if($res1[0]['relation_type'] ==0)
                    {
                        //修改关系为0=>1 取消关注到关注;
                        $data3 = array('relation_type'=>1,'upd_time'=>date('Y-m-d H:i:s'));
                        //关注数+1
                        $res_gz->getUserSum1($user_id,'follow_num',1);
                        //粉丝数+1
                        $res_gz->getUserSum1($follower_id,'fans_num',1);
                        $res4 = Db::name('follow')->where('follower_id='.$follower_id.' and user_id='.$user_id)->limit(1)->update($data3);
                    }
                }else
                {
                    //插入
                    $data2 = array('user_id'=>$user_id,'follower_id'=>$follower_id,'ins_time'=>date('Y-m-d H:i:s'));
                    $res2 = Db::name('follow')->insert($data2);
                    $flag = 1;
                    //关注数+1
                    $res_gz->getUserSum1($user_id,'follow_num',1,'gz');
                    //粉丝数+1
                    $res_gz->getUserSum1($follower_id,'fans_num',1,'fs');
                }
                $msg='关注成功';

            }

        }

        if($flag == 1)
        {
            $code = 1;
            $data = array('user_id'=>$user_id,'follower_id'=>$follower_id,'relation_type'=>1);
        }else if ($flag == 2)
        {
            $code = 0;$msg='请求参数不对';
        }
        return $this->returnMsg($code,$data,$msg);
    }

    /*
     * 功能: 关注用户列表/粉丝列表
     * 请求: user_id=>用户id;follower_id=>被关注者用户id;other_user_id=>被关注者用户id;
     * 返回:
     * */
    public function follow_list()
    {
        //初始化数据
        $code = 0;$msg='修改失败';$data=[];$flag=0;$res1=null;
        //获取请求数据
        $user_id = input('user_id')==''?1:input('user_id');
        $type = input('type')==''?'fs':input('type');
        $other_user_id = input('other_user_id',0);
        if(!($user_id && $type))
        {
            $flag = 2;
        }else
        {
            if($type == 'fs')
            {
                //粉丝列表
                $res1 = $res1 = Db::name('follow f')->join(['ims_bj_shopn_member'=>'mem'],['f.user_id=mem.id'],'LEFT')->join(['ims_fans'=>'fans'],['mem.id=fans.id_member'],'LEFT')->field('f.user_id,fans.avatar user_img,mem.realname user_name,fans.address')->where(' f.relation_type=1  and f.follower_id='.$user_id)->select();
                if($res1)
                {
                    $flag=1;
                    $data = $res1;
                }else
                {
                    $flag = 3;
                }

            }elseif($type == 'gz')
            {
                //关注列表
                $res1 = Db::name('follow f')->join(['ims_bj_shopn_member'=>'mem'],['f.user_id=mem.id'],'LEFT')->join(['ims_fans'=>'fans'],['mem.id=fans.id_member'],'LEFT')->field('f.follower_id as user_id,fans.avatar user_img,mem.realname user_name,fans.address')->where(' f.relation_type=1  and f.user_id='.$user_id)->select();
                if($res1)
                {
                    $flag=1;
                    $data = $res1;
                }else
                {
                    $flag = 4;
                }

            }
            $mapu = [];$user_ids = [];
            if ($data) {
                foreach ($data as $k => $v) {
                    $data[$k]['user_name'] = $v['user_name'] = null?'':$v['user_name'];
                    $data[$k]['user_img'] = $v['user_img'] = null?'':$v['user_img'];
                    $data[$k]['address'] = $v['address'] = null?'':$v['address'];
                    $user_ids[] = $v['user_id'];
                }
                $mapu['m.id'] = ['in',$user_ids];
            }
            // 查询用户头像和名称和地址
            if ($mapu) {
                $res_user = Db::table('ims_bj_shopn_member m')->join(['ims_fans'=>'fans'],['m.id=fans.id_member'],'LEFT')->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')->field('m.id,fans.avatar user_img,m.realname user_name,b.title address')->where($mapu)->select();
                if ($res_user) {
                    foreach ($res_user as $ku => $vu) {
                        foreach ($data as $k => $v) {
                            if ($v['user_id'] == $vu['id']) {
                                $data[$k]['user_name'] = $vu['user_name']==null?'':$vu['user_name'];
                                $data[$k]['user_img'] = $vu['user_img']==null?'http://appc.qunarmei.com/normal_photo.png':$vu['user_img'];
                                $data[$k]['address'] = $vu['address']==null?'':$vu['address'];
                            }
                        }
                    }
                }
            }
            // 查询用户关注/粉丝列表
            $mapf = [];$gz_user_ids = [];
            if ($other_user_id) {
                $mapf['user_id'] = $other_user_id;
            }
            $mapf['relation_type'] = 1;
            $res_gz = Db::table('think_follow')->where($mapf)->select();
            if ($res_gz) {
                foreach ($res_gz as $k1 => $v1) {
                    $gz_user_ids[] = $v1['follower_id'];
                }
            }
            foreach ($data as $k => $v) {
                $data[$k]['relation_type'] = 0;//0:未相互,1:相互关注
                if ($gz_user_ids && in_array($v['user_id'],$gz_user_ids)) {
                    $data[$k]['relation_type'] = 1;
                }
                if($type == 'gz' && empty($other_user_id)){
                    $data[$k]['relation_type'] = 1;
                }
            }
//            //相互关注列表
//            $res3 = Db::name('follow f,ims_bj_shopn_member mem,ims_fans fans')->field('fans.avatar user_img,mem.realname user_name,fans.address')->where(' mem.id=fans.id_member and f.follower_id=mem.id and f.relation_type=2 and f.user_id='.$user_id)->select();
//            if($res3)
//            {
//                $flag = 1;
//                if($res1)
//                {
//                    $data = array_merge($res1,$res3);
//                }else
//                {
//                    $data = $res3;
//                }
//
//            }

        }

        if($flag == 1)
        {
            $code = 1;$msg='获取成功';
        }else if ($flag == 2)
        {
            $code = 0;$msg='请求参数不对';
        }else
        {
            $code = 1;$msg='暂无数据';
        }
        return $this->returnMsg($code,$data,$msg);
    }

    // start add by wangqin  2018-01-15
    /*
     * 功能: 直播间-主播-直播中-粉丝数
     * 请求: user_id=>用户id;
     * 返回:
     * */
    public function get_followers($user_id='')
    {
        //初始化数据
        $code = 1;$msg='获取成功';$data=[];$followers=0;
        if($user_id)
        {
            $res = new FindContent();
            $res1 = $res->getUserSum1($user_id);
            if($res1)
            {
                $followers = $res1['fans_num'];
            }
            $data = array('followers'=>$followers);
        }else
        {
            $code = 0;$msg='请求参数不对';
        }
        $ret = $this->returnMsg($code,$data,$msg);
        return $ret;
    }
    // end add by wangqin  2018-01-22

    // start add by wangqin  2018-01-22
    /*
     * 功能: 是否关注/收藏
     * 请求: user_id=>用户id;follower_id=>被关注者id;actrile_id=>文章id
     * 返回:
     * */
    public function is_be_func($user_id,$follower_id=null,$actrile_id=null)
    {
        //初始化数据
        $code = 1;$msg='获取成功';$data=array();$is_be=0;
        //是否关注
        if($follower_id)
        {
            $res = Db::name('follow')->where("user_id=$user_id and follower_id=$follower_id and relation_type=1")->count();
            if($res)
            {
                $is_be=1;
                $msg = '已关注';
            }else
            {
                $msg = '未关注';
            }
        }
        //是否收藏
        if($actrile_id)
        {
            $res = Db::name('find_content_collect')->where("user_id=$user_id and actrile_id=$actrile_id and type=1")->count();
            if($res)
            {
                $is_be=1;
                $msg = '已收藏';
            }else
            {
                $msg = '未收藏';
            }
        }
        $data['is_be'] = $is_be;
        return $this->returnMsg($code,$data,$msg);
    }
    // end add by wangqin  2018-01-22
}