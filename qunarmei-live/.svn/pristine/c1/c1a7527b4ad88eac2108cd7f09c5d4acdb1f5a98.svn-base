<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:41
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\VideoService;
/**
 * (直播回放)视频相关操作类
 */
class Video extends Common
{
    // 视频服务类
    protected $videoSer;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->videoSer = new VideoService();
    }
    /**
     * 获取收藏视频
     * @param int $user_id 用户id
     * @param int $other_user_id 其他用户id
     */
    public function get_collect_video()
    {
        $user_id = input('user_id');
        $other_user_id = input('other_user_id',0);
        $res = $this->videoSer->getCollectVideo($user_id,$other_user_id);
        $this->rest['code'] = 1;
        if($res){
            $this->rest['msg'] = '获取成功';
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = [];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 回放视频收藏
     * @param int $user_id 用户id
     * @param int $live_id 视频id
     */
    public function collect_video()
    {
        $user_id = input('user_id');
        $live_id = input('live_id');
        $res = $this->videoSer->collectVideo($user_id , $live_id );
        if($res){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '收藏成功';
            if ($res == 2) {
                $this->rest['msg'] .= ',直播结束后可以在我的收藏里查看直播视频回放!';
            }
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '收藏失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 回放视频收藏-取消
     * @param int $user_id 用户id
     * @param int $live_id 视频id
     */
    public function del_collect_video()
    {
        $user_id = input('user_id');
        $live_id = input('live_id');
        $res = $this->videoSer->delCollectVideo($user_id , $live_id );
        if($res){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '取消收藏成功';
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '取消收藏失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 回放视频-主播删除
     * @param int $user_id 用户id
     * @param int $live_id 视频id
     */
    public function del_own_video()
    {
        $user_id = input('user_id');
        $live_id = input('live_id');
        $res = $this->videoSer->delOwnVideo($user_id , $live_id );
        if($res){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '删除成功';
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '删除失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}