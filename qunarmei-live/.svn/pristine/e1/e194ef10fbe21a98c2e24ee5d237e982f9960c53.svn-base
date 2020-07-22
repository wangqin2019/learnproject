<?php

namespace app\api_test\controller;

use think\Db;
//使用redis扩展
//use think\cache\driver\Redis;
//整合腾讯云通信扩展
use tencent_cloud\TimChat;
/**
 * AppOther: App 独立出来功能的接口
 */
class AppOther  extends Base
{

    /*
     * 功能: 修改直播观看人数倍率,30秒后生效
     * 请求: $rate=>倍率
     * 返回:
     * */
    public function upd_live_see()
    {
        //初始化数据
        $code = 0;$msg='修改失败';$data=null;$flag=0;
        //获取请求数据
        $rate = input('rate')==''?1:input('rate');
        //查看直播标题
        $res1 = Db::name('live')->field('live_stream_name,title,content')->where('statu=1 and db_statu=0 and live_source=1 ')->limit(1)->select();
        if($res1)
        {
            //修改正在PC端直播的倍率数据
            $data1 = array('see_count_times'=>$rate);
            $res = Db::name('live')->where('statu=1 and db_statu=0 and live_source=1 ')->limit(1)->update($data1);
            if($res)
            {
                $code = 1;$msg='修改成功';$flag=1;
                $data = array('live_stream_name'=>$res1[0]['live_stream_name'],'title'=>$res1[0]['title'],'content'=>$res1[0]['content']);
            }
        }else
        {
            $flag = 2;
        }


        if($flag == 0)
        {
            $code = 0;$msg='修改观看直播人数倍率失败';
        }else if ($flag == 2)
        {
            $code = 0;$msg='PC端直播尚未开启,请稍候再试';
        }
        return $this->returnMsg($code,$data,$msg);
    }

    /*
     * 功能: PC端直播当前可参与抽奖人数
     * 请求:
     * 返回:
     * */
    public function get_draw_num()
    {
        //初始化数据
        $code = 0;$msg='获取失败';$data=null;$flag=0;$num=0;$mobiles=null;
        //获取电脑端直播抽奖号码
        $rest = Db::name('live')->field('chat_id')->where('db_statu=0 and statu=1 and live_source=1')->limit(1)->select();
        $chat_id = '@TGS#a6QCZE6ET';
        if($rest)
        {
            $chat_id = $rest[0]['chat_id'];
        }
        if($chat_id)
        {
            $tent = new TimChat();
            $resp = $tent->getChatMem($chat_id);
            //去除已中奖的用户
            $mobile_y = Db::name('lucky_draw')->field('mobile')->where('prize>0')->select();
            if($mobile_y)
            {
                $resp = arrCha($resp,$mobile_y);
            }

            // start Modify by wangqin 2017-12-01 去除本公司和办事处职员去抽奖
            $mobile_z = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('mem.mobile')->where("mem.storeid=ibb.id and mobile>0 and ibb.sign in ('888-888','666-666','000-000') ")->group('mobile')->select();
            if($mobile_z)
            {
                $resp = arrCha($resp,$mobile_z);
            }
            // start Modify by wangqin 2017-12-14 剔除总公司和办事处人员参与抽奖
             $mobile_a = Db::name('lucky_draw_del')->field('mobile')->where("mobile>0")->group('mobile')->select();
             if($mobile_a)
             {
                 $resp = arrCha($resp,$mobile_a);
             }
            // end Modify by wangqin 2017-12-14

            if($resp)
            {
                shuffle($resp);
                $num = count($resp);
                $flag=1;
                foreach($resp as $v)
                {
                    $mobiles[] = $v['mobile'];
                }
            }
        }

        if($flag=1)
        {
            $code = 1;$msg='获取成功';$data=array('num'=>$num,'mobiles'=>$mobiles);
        }
        return $this->returnMsg($code,$data,$msg);
    }

    // start Modify by wangqin 2018-01-19
    /*
     * 功能: 定时同步redis数据到mysql数据表
     * 请求:
     * 返回:
     * */
    public function redis_to_mysql()
    {
        $code=1;$data='';$msg='同步成功';
        //1.直播列表接口 =>同步 audience=>$liveid.'_see',point=>$live_id
        $res_livelist = Db::name('live')->field('id,see_count_times')->order('id desc')->select();
        if($res_livelist)
        {
            foreach($res_livelist as $v1)
            {
                $live_id = $v1['id'] ;
                $see_num = parent::getRedisP($live_id.'_see');
                $point_num = parent::getRedisP($live_id);
                //观看人数x倍数
                $point_num = $point_num*$v1['see_count_times'];
                $data1 = array('see_num'=>$see_num,'point_num'=>$point_num);
                //查询统计表是否有记录
                $res1 = Db::name('sum_live')->field('id')->where('live_id',$live_id)->limit(1)->select();
                if($res1)
                {
                    $data1['upd_time'] = date('Y-m-d H:i:s');
                    Db::name('sum_live')->where('live_id',$live_id)->update($data1);
                }
            }
            $data['think_sum_live'] = '同步直播间点赞、观看redis数据到mysql成功';
        }

        //2.发现-详情-获取文章评论API see_num=>,comment_num=>,
        $res_content = Db::name('find_content')->field('id')->order('id desc')->select();
        if($res_content)
        {
            foreach($res_content as $v2)
            {
                //查询统计表是否有记录
                $res2 = Db::name('sum_article')->field('id')->where('article_id',$v2['id'])->limit(1)->select();
                if($res2)
                {
                    //修改
                    $res_cnt1 = parent::getRedisHash('think_sum_article',$v2['id']);
                    if($res_cnt1)
                    {
                        //更新到数据表think_sum_article
                        $res_cnt1 = json_decode($res_cnt1);
                        $data_cnt = array('see_num'=>($res_cnt1->see_num),'comment_num'=>($res_cnt1->comment_num),'collect_num'=>($res_cnt1->collect_num),'upd_time'=>date('Y-m-d H:i:s'));
                        Db::name('sum_article')->where('article_id',$v2['id'])->update($data_cnt);
                    }
                }
            }
            $data['think_sum_article'] = '同步文章浏览数、评论数、收藏数redis数据到mysql成功';
        }

        //3.个人中心-主播资料对外-往期视频API  gz_num=>,fs_num=>, think_sum_user
        $res_user = Db::table('ims_bj_shopn_member')->field('id,mobile')->order('id desc')->select();
        if($res_user)
        {
            foreach($res_user as $v3)
            {
                //查询统计表是否有记录
                $res3 = Db::name('sum_user')->field('user_id')->where('user_id',$v3['id'])->limit(1)->select();
                if($res3)
                {
                    //修改
                    $res_user1 = parent::getRedisHash('think_sum_user',$v3['id']);
                    if($res_user1)
                    {
                        //更新到数据表think_sum_article
                        $res_user1 = json_decode($res_user1);
                        $data_cnt = array('article_num'=>($res_user1->article_num),'be_collect_num'=>($res_user1->be_collect_num),'follow_num'=>($res_user1->follow_num),'fans_num'=>($res_user1->fans_num),'scores'=>($res_user1->scores),'upd_time'=>date('Y-m-d H:i:s'));
                        Db::name('sum_user')->where('user_id',$v3['id'])->update($data_cnt);
                    }
                }
            }
            $data['think_sum_user'] = '同步用户-文章数、被收藏数、关注数、粉丝数、积分数redis数据到mysql成功';
        }
        return $this->returnMsg($code,$data,$msg);
    }
    // end Modify by wangqin 2018-01-19

    // start Modify by wangqin 2018-03-22
    /*
     * 功能: 返回诚美学院正在直播及已经播过的视频列表
     * 请求:
     * 返回:
     * */
    public function xueyuan_live()
    {
        //初始化数据
        $code = 0;$msg='获取失败';$data=[];

        //查看直播标题
        $res1 = Db::name('live l')->join(['ims_bj_shopn_member'=> 'm'],'l.user_id=m.mobile','LEFT')->join(['ims_fans'=> 'f'],'f.id_member=m.id','LEFT')->field('l.id,l.live_img img,l.see_url video_url,l.statu,l.title,f.avatar user_img,m.realname user_name')->where(' live_source=2 and (statu=1 or (db_statu=2 and statu=2) )  ')->where('l.user_id','17717416105')->order('l.insert_time desc')->select();
        if($res1)
        {
            // 获取点赞,观看人数
            $live = new Live();
            foreach ($res1 as $v) {
                $data1 = $v;
                $dz_num = $live->pointPraise($v['id'],'interface');
                $gk_num = $live->pointPraise($v['id'],'gk');
                $data1['dz_num'] = $dz_num ;
                $data1['gk_num'] = $gk_num;
                $data[] = $data1;
            }
            $code = 1;$msg='获取成功';
        }else
        {
            $msg='暂无数据';
        }
        return $this->returnMsg($code,$data,$msg);
    }
    // start Modify by wangqin 2018-03-22
    //
    // start Modify by wangqin 2018-05-31
    /*
     * 功能: 审核通过,通过流水号添加对应扫码的积分
     * 请求: $flow_number=>string
     * 返回:
     * */
    public function jifen_add($flow_number)
    {
        //初始化数据
        $code = 0;$msg='添加失败';$data=[];

        //查询积分兑换
        $res = Db::name('scores_shenhe')->field('id,flow_number,beautician_id,beautician_score,boss_id,boss_score,flag')->where(' flag=0 and flow_number='.$flow_number)->limit(1)->find();
        if($res)
        {
            $find_cont = new FindContent();
            // 添加美容师积分
            $find_cont->getUserSum1($res['beautician_id'],$type='scores',$res['beautician_score']);
            $str_remark = $res['beautician_id'].'美容师添加积分'.$res['beautician_score'];

            //添加老板积分
            $find_cont->getUserSum1($res['boss_id'],$type='scores',$res['boss_score']);
            $str_remark .= ','.$res['boss_id'].'店老板添加积分'.$res['boss_score'];

            // 修改标记位
            $data_upd = array('flag'=>1);
            Db::name('scores_shenhe')->where('id',$res['id'])->update($data_upd);

            $data['tip_msg'] = $str_remark;

            $code = 1;$msg='添加成功';
        }else
        {
            $msg='暂无数据';
        }
        return $this->returnMsg($code,$data,$msg);
    }
    // start Modify by wangqin 2018-05-31

}