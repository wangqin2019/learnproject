<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:46
 */

namespace app\api\service;

class VideoService
{
    // 视频用户收藏-删除配置模型
    protected $userConfMod;
    // 视频列表模型
    protected $liveMod;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        $this->userConfMod = new \app\api\model\UserConfMod();
        $this->liveMod = new \app\api\model\Live();
    }
    /**
     * 获取收藏视频
     * @param int $user_id 用户id
     * @param int $other_user_id 其他用户id
     */
    public function getCollectVideo($user_id, $other_user_id = 0)
    {
        $data = [];$other_live_ids = [];
        // 查询收藏视频
        $map['type'] = 1;
        $map['user_id'] = $user_id;
        $map['delete_time'] = 0;
        $res = $this->userConfMod->where($map)->order('update_time desc,id desc')->select();
        if ($res) {
            $live_ids = [];
            foreach ($res as $v) {
                $live_ids[] = $v['content'];
            }
            // 查询其他用户收藏视频列表
            if ($other_user_id) {
                $mapo['type'] = 1;
                $mapo['user_id'] = $other_user_id;
                $mapo['delete_time'] = 0;
                $reso = $this->userConfMod->where($mapo)->order('update_time desc,id desc')->select();
                if ($reso) {
                    foreach ($reso as $v1) {
                        $other_live_ids[] = $v1['content'];
                    }
                }
            }
            // 查询视频列表
            // 视频id,视频时间,视频封面,主播头像,主播名称,视频地址
            $mapl['l.id'] = ['in',$live_ids];
            $mapl['l.statu'] = 2;
            $mapl['c.type'] = 1;
            $mapl['c.user_id'] = $user_id;
            $mapl['c.delete_time'] = 0;
            $res_live = $this->userConfMod->alias('c')->join(['think_live'=>'l'],['c.content=l.id'],'LEFT')->where($mapl)->order('c.update_time desc,l.insert_time desc')->select();
            if ($res_live) {
                foreach ($res_live as $v) {
                    $resl['is_collect'] = 0;
                    if(!$other_user_id){
                        $resl['is_collect'] = 1;
                    }else{
                        if ($other_live_ids && in_array($v['id'],$other_live_ids)) {
                            $resl['is_collect'] = 1;
                        }
                    }
                    $share_url = config('url.live_see_url').'?id='.$v['id'];
                    $resl['live_id'] = $v['id'];
                    $resl['time'] = date('Y-m-d',$v['insert_time']);
                    $resl['img'] = $v['live_img'];
                    $resl['anchor_img'] = $v['user_img'];
                    $resl['anchor_name'] = $v['user_name'];
                    $resl['video_url'] = $v['see_url'];
                    $resl['share_url'] = $share_url;
                    $resl['address'] = $v['address'];
                    $resl['gk_num'] = rand(1,9);
                    $data[] = $resl;
                }
            }
        }
        return $data;
    }
    /**
     * 回放视频收藏
     * @param int $user_id 用户id
     * @param int $live_id 视频id
     */
    public function collectVideo($user_id , $live_id)
    {
        $flag = 1;
        // 先查询该视频收藏是否有记录
        $map['user_id'] = $user_id;
        $map['content'] = $live_id;
        $map['type'] = 1;
        $res = $this->userConfMod->where($map)->limit(1)->find();
        if ($res) {
            // 修改
            $mapu['id'] = $res['id'];
            $datau['delete_time'] = 0;
            $this->userConfMod->where($mapu)->update($datau);
        }else{
            // 插入
            $datau['user_id'] = $user_id;
            $datau['content'] = $live_id;
            $datau['type'] = 1;
            $datau['create_time'] = time();
            $this->userConfMod->save($datau);
        }
        // 查询该视频是否在直播中
        $mapl['id'] = $live_id;
        $mapl['statu'] = 1;
        $resl = $this->liveMod->where($mapl)->limit(1)->find();
        if ($resl) {
            $flag = 2;
        }
        return $flag;
    }
    /**
     * 回放视频收藏-取消
     * @param int $user_id 用户id
     * @param int $live_id 视频id
     */
    public function delCollectVideo($user_id , $live_id)
    {
        // 先查询该视频收藏是否有记录
        $map['user_id'] = $user_id;
        $map['content'] = $live_id;
        $map['type'] = 1;
        $res = $this->userConfMod->where($map)->limit(1)->find();
        if ($res) {
            // 修改
            $mapu['id'] = $res['id'];
            $datau['delete_time'] = time();
            $this->userConfMod->where($mapu)->update($datau);
        }
        return 1;
    }
    /**
     * 回放视频-主播删除
     * @param int $user_id 用户id
     * @param int $live_id 视频id
     */
    public function delOwnVideo($user_id , $live_id)
    {
        $map['id'] = $live_id;
        $res = $this->liveMod->where($map)->limit(1)->find();
        if ($res) {
            // 修改
            $datau['statu'] = 3;// 主播删除
            $this->liveMod->where($map)->update($datau);
            // 查询已收藏视频,修改状态
            $mapc['content'] = $live_id;
            $mapc['type'] = 1;
            $datac['delete_time'] = time();
            $this->userConfMod ->where($mapc)->update($datac);
        }
        return 1;
    }
}