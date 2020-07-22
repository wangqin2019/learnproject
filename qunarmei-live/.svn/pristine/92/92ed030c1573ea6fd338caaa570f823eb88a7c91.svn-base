<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/1/10
 * Time: 11:42
 */

namespace app\api\model;


use think\Model;

class ActiviteM extends Model
{
    // 对应数据表明
    protected $table = 'think_activities_zb';
    /*
     * 功能:1.18直播活动预热,每天登陆弹窗显示不同活动预热图片
     * 请求:$arr=>[dt=>当前时间]
     * 返回:json
     * */
    public static function activitieszbSel($arr)
    {
        $map['act_status'] = 1;
        $map['act_type'] = 1;
        $map['img_isshow'] = 1;
        $map1 = [];
        if(isset($arr['dt'])){
            $map1['act_start_time'] = ['<=',$arr['dt']];
            $map1['act_end_time'] = ['>',$arr['dt']];
        }
        $res = self::where($map)->where($map1)->order('act_create_time desc')->limit(1)->find();
        return $res;
    }

}