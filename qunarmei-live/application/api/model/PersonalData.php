<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/4
 * Time: 10:30
 */

namespace app\api\model;
use think\Db;
class PersonalData
{

    /*
     * 功能: 个人基本信息
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public static function uuiSel($user_id)
    {
        $map['user_id'] = $user_id;
        $res = Db::table('underwear_user_info u')->field('*')->where($map)->limit(1)->find();
        return $res;
    }
    /*
     * 功能: 内衣档案-个人基本信息-需要下发字段
     * 请求: $type=>类型
     * */
    public static function ucpSel($type)
    {
        $map['type'] = $type;
        $map['isshow'] = 1;
        $res = Db::table('underwear_conf_property u')->field('*')->where($map)->order('create_time desc')->select();
        return $res;
    }
    /*
     * 功能: 内衣档案-个人基本信息-需要下发字段-选择列表
     * 请求: $ucp_id=>对应的属性id
     * */
    public static function ucplSel($ucp_id)
    {
        $map['ucp_id'] = $ucp_id;
        $map['isshow'] = 1;
        $res = Db::table('underwear_conf_property_list l')->field('*')->where($map)->order('create_time desc')->select();
        return $res;
    }
    /*
     * 功能: 更新个人基本信息
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public static function uuiUpd($arr)
    {
        $map['user_id'] = $arr['user_id'];
        $res = Db::table('underwear_user_info u')->where($map)->update($arr);
        return $res;
    }
    /*
     * 功能: 查询代餐记录用户当日是否已记录
     * 请求: $user_id => 用户id,$fill_user_id=>填写美容师id
     * */
    public static function mealDtSel($user_id,$fill_user_id=0)
    {
        $rest = 0;
        $map['user_id'] = $user_id;
        $map['fill_user_id'] = $fill_user_id;
        $map['create_time'] = ['>=',strtotime(date('Y-m-d'))];
        $map1['create_time'] = ['<',strtotime("+1 day")];
        $res = Db::table('underwear_meal_record')->field('id')->where($map)->where($map1)->limit(1)->find();
        if(!empty($res)){
            $rest = $res['id'];
        }
        return $rest;
    }
    /*
     * 功能: 黄金身材尺寸
     * 请求: $height=>身高 cm
     * */
    public static function ugbsSel($height)
    {
//        underwear_gold_body_size
        $map['height'] = round($height);
        $res = Db::table('underwear_gold_body_size')->field('*')->where($map)->limit(1)->find();
        return $res;
    }
    /*
     * 功能: 内衣项目等级计算及评分标准=> 完美尺寸规则
     * 请求: $height=>身高 cm
     * */
    public static function underwearRule($height)
    {
        $arr['bb'] = 16.5;
        $arr['right_bb'] = 19;
        $arr['left_bb'] = 19;
        $arr['bust'] = round($height * 0.53,1);
        $arr['lower_bust'] = round($height * 0.45,1);
        $arr['waist'] = round($height * 0.37,1);
        $arr['hipline'] = round($height * 0.54,1);
        $arr['thighcir'] = round($height * 0.26 + 7.8,1);
        $arr['lower_leg'] = round($height * 0.18,1);
        $arr['ankle'] = round($height * 0.18 * 0.59,1);
        return $arr;
    }

}