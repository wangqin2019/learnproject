<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/19
 * Time: 13:07
 */

namespace app\api\model;
use think\Model;
use think\Db;
use app\api\model\ArchivesFunc;
/*
 * 自测数据相关API
 * */
class HealthFunc extends Model
{
    /*
     * 功能:健康指数自测-记录数据
     * 请求:$arr=>自测数据
     * */
    public static function UserInfoUpd($arr)
    {
        $map['user_id'] = $arr['user_id'];
        $res = Db::table('underwear_user_info')->field('id,user_id,head_img')->where($map)->limit(1)->find();
        if(!empty($res)){
            $rest = Db::table('underwear_user_info')->where($map)->update($arr);
        }else{
            $rest = self::UserInfoAdd($arr);
        }
        return $rest;
    }
    /*
     * 功能:健康指数自测-查询
     * 请求:$arr=>自测数据
     * */
    public static function UserInfoAdd($arr)
    {
        $rest = Db::table('underwear_user_info')->insertGetId($arr);
        return $rest;
    }
    /*
     * 功能:健康指数自测-测试结果生成
     * 请求:$arr=>自测数据
     * */
    public static function healthRecodeMake($arr)
    {
        $flag = 0;$rest=0;
        $arr1['user_id'] = $arr['user_id'];
        $arr1['sex'] = $arr['sex'];
        $arr1['birthday'] = $arr['birthday'];
        $arr1['head_img'] = $arr['head_img'];
        $arr1['weight'] = $arr['weight'];
        $arr1['height'] = $arr['height'];
        $arr1['waist'] = $arr['waist'];
        $arr1['hipline'] = $arr['hipline'];
        $arr1['weight_standard'] = 0;
        // 男
        $arr1['height'] = round($arr1['height']);
        if($arr1['height'] < 155){
            $arr1['height'] = 155;
        }
//        if($arr['sex']){
//            $arr1['weight_standard'] = config('weight_standard.man')[$arr1['height']];
//        }else{
//            $arr1['weight_standard'] = config('weight_standard.woman')[$arr1['height']];
//        }
        $arr1['weight_standard'] = ($arr1['height']-70)*0.6;
        $arr1['weight_cosmetology'] = $arr1['weight_standard'] * 0.9; // 标准体重x0.9
        $arr1['bmi'] = round($arr1['weight']/($arr1['height'] * $arr1['height'] / 10000),2); // 体重（kg） ÷【身高×身高】
        $arr1['rate_waist_hip'] = round($arr['waist']/$arr['hipline'],2);// 腰围÷臀围
        $arr1['create_time'] = time();

//        $arr1['score'] = rand(80,100);// 生成分数逻辑规则-待写
        $arr1['tips'] = config('sub_tips.normal')[rand(0,3)]; // 提示语
        $arr1['color'] = config('data_color.green'); // 正常颜色
//        print_r($arr1);die;
        // 顾客和美容师当天只能有2条数据记录
        $dt = time();
        $dt2 = strtotime("+1 day");
        $map['create_time'] = ['>=',$dt];
        $map2['create_time'] = ['<',$dt2];
        $map['user_id'] = $arr['user_id'];
//        $res = Db::table('underwear_meal_record')->field('id,fill_user_id')->where($map)->where($map2)->select();
//        if(!empty($res)){
//            foreach ($res as $v) {
//                if(!$v['fill_user_id']){
//                    $flag = 1;
//                    $rest = $v['id'];
//                    // 更改自测记录
//                    $map1['id'] = $v['id'];
//                    Db::table('underwear_meal_record')->where($map1)->update($arr1);
//                }
//           }
//        }
//        if(!$flag){
//            $rest = Db::table('underwear_meal_record')->insertGetId($arr1);
//        }
        $rest = Db::table('underwear_self_test_record')->insertGetId($arr1);
//        $rest = Db::table('underwear_meal_record')->insertGetId($arr1);
        return $rest;
    }
    /*
     * 功能:健康指数自测-测试结果查询
     * 请求:$id=>记录id
     * */
    public static function healthRecodeSel($id)
    {
        // 用户名,头像,日期,分数,提示语,质量指数,质量指数标识是否正常,体重
        $map['r.id'] = $id;
//        $res = Db::table('underwear_meal_record r')->join(['ims_bj_shopn_member'=>'m'],['r.user_id=m.id'],'LEFT')->field('r.head_img,m.realname user_name,r.create_time,r.tips,r.score,r.bmi,weight,weight_cosmetology,weight_standard,rate_waist_hip')->where($map)->limit(1)->find();
        $res = Db::table('underwear_self_test_record r')->join(['ims_bj_shopn_member'=>'m'],['r.user_id=m.id'],'LEFT')->field('r.head_img,m.realname user_name,r.create_time,r.tips,r.bmi,weight,weight_cosmetology,weight_standard,rate_waist_hip')->where($map)->limit(1)->find();
        return $res;
    }
}