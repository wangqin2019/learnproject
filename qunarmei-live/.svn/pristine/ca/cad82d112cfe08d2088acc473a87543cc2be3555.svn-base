<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/1/8
 * Time: 14:12
 */

namespace app\api\service;
use app\api\model\Test as TestM;
use app\api\model\Fans;
use app\api\model\Branch;
use app\api\model\Live;
//使用redis扩展
use think\cache\driver\Redis;
class Test
{
    /*
     * 关联查询
     * $arr['user_id']用户id
     * */
    public function getUserFans($arr)
    {
        $res = [];
        $res = Branch::getUserById($arr['user_id']);
        return $res;
    }
    /*
     * 当前直播点赞数
     *
     * */
    public function zbDianzanNum()
    {
        $rest = 0;
        /*
         * 1.获取当前直播间id
         * 2.获取redis直播点赞数
         * */
        $res = Live::liveSel();
        if(!empty($res)){
            $live_id = $res['id'];
            $redis = new Redis();
            $rest = $redis->get($live_id);
        }
        return $rest;
    }
}