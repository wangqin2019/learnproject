<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:36
 */

namespace app\api\service;
use think\Db;

class LiveService extends BaseSer
{
    protected $url = 'http://live.qunarmei.com/api/';
    /**
     * 网页直播列表-异步刷新
     * @param string $live_id 直播间id
     * @return
     */
    public function liveList($live_id)
    {
        $this->code = 1;
        $this->msg = '暂无数据';
        $data = [];
        $arr = [
            'close_live' => [],
            'open_new_live' => []
        ];
        $map = [];
        $map2 = '';
        if($live_id){
            $data_gb1 = [];
            $ids = explode(',',$live_id);
            $map1['id'] = ['in',$ids];
            $map1['statu'] = ['neq',1];
            $res1 = Db::table('think_live')->where($map1)->order('id desc')->select();
            if($res1){
                foreach ($res1 as $v1) {
                    $data_gb1[] = $v1['id'];
                }
            }
            $arr['close_live'] = $data_gb1;
            // 取大于当前直播间的id
            rsort($ids);
            $id = $ids[0];// id最大值
            $map['id'] = ['>',$id];

            $map2 = ' update_time is not null and statu=1 and id not in ('.$live_id.')';
        }
        // 查询所有直播列表
        $map['statu'] = 1;

        $res = Db::table('think_live')->where($map)->whereOr($map2)->order('id desc')->select();
        if($res){
            foreach ($res as $v) {
                $data1['live_id'] = $v['id'];
                $data1['live_img'] = $v['live_img'];
                $data1['user_img'] = $v['user_img'];
                $data1['user_name'] = $v['user_name'];
                $data1['address'] = $v['address'];
                $data1['content'] = $v['content'];
                $data1['gk_cnt'] = 1;
                $data1['dz_cnt'] = 1;
                $data1['hls_url'] = $v['hls_url'];
                // 查询观看,点赞人数
                $url = $this->url.'live/pointpraise?live_id='.$v['id'];
                $res_gk = curl_get($url);
                if($res_gk){
                    $res_gk_ret = json_decode($res_gk,true);
                    $data1['gk_cnt'] = $res_gk_ret['data']['audience']==null?1:$res_gk_ret['data']['audience'];
                    $data1['dz_cnt'] = $res_gk_ret['data']['point_count']==null?1:$res_gk_ret['data']['audience'];
                }
                $data[] = $data1;
                $arr['open_new_live'] = $data;
            }
        }
        if($arr['open_new_live'] || $arr['close_live']){
            $this->data = $arr;
            $this->msg = '获取成功';
        }else{
            $this->data = (object)[];
        }
        return $this->returnArr();
    }
    /**
     * 是否开启315直播
     * @return
     */
    public function isLive()
    {
        $map['statu'] = 1;
        $map['user_id'] = 1;
        $res = Db::table('think_live')->where($map)->order('id desc')->limit(1)->find();
        if ($res) {
            $this->code = 1;
            $this->msg = '直播已开启';
        }else{
            $this->code = 0;
            $this->msg = '直播未开启';
        }
        return $this->returnArr();
    }
}