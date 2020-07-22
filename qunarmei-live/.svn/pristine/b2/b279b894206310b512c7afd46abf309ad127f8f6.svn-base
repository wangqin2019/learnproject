<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/19
 * Time: 13:07
 */

namespace app\api\model;
use app\api\model\HealthFunc;
use app\api\model\ArchivesFunc;
/*
 * 代餐档案数据相关API
 * */
class Health
{
    /*
     * 功能:健康指数自测
     * 请求:$arr=>自测数据
     * */
    public function healthCheck($arr)
    {
        $rest = [];
        /*用户名,头像,日期,分数,提示语,质量指数,质量指数标识是否正常,体重
IBM是否正常标准[],标准体重,美容体重
腰臀比,腰臀比提示语,综合信息结果提示语
         * */
        // 更新基础信息数据
        $arr1['user_id'] = $arr['user_id'];
        $arr1['sex'] = $arr['sex'];
        $arr1['birthday'] = $arr['birthday'];
        $arr1['weight'] = $arr['weight'];
        $arr1['height'] = $arr['height'];
        if($arr1['height'] > 200){
            $arr1['height'] = 200;
        }elseif($arr1['height'] <150){
            $arr1['height'] = 150;
        }
        $arr['head_img'] = '';
        $res1 = ArchivesFunc::userInfo($arr['user_id']);
        if(!empty($res1)){
            $arr1['head_img'] = $res1['head_img']==null?'':$res1['head_img'];
            $arr['head_img'] = $arr1['head_img'];
        }
        $res1 = HealthFunc::UserInfoUpd($arr1);
        // 生成测试结果数据
        $res2 = HealthFunc::healthRecodeMake($arr);
        if(!empty($res2)){
            $res3 = HealthFunc::healthRecodeSel($res2);
            if(!empty($res3)){
                // 用户名,头像,日期,分数,提示语,质量指数,质量指数标识是否正常,体重
                $rest['user_name'] = $res3['user_name']==null?'':$res3['user_name'];
                $rest['head_img'] = $res3['head_img'];
                $rest['dt'] = date('Y-m-d',$res3['create_time']);
//                $rest['score'] = $res3['score'];
                $rest['tips'] = $res3['tips'];
                $rest['bmi'] = $res3['bmi'];
                $rest['bmi_flag'] = '偏胖';
                if($rest['bmi']>=18.5 && $rest['bmi']<=23.9 ){
                    $rest['bmi_flag'] = '正常';// 正常
                }elseif($rest['bmi'] >24 && $rest['bmi']<=26.9){
                    $rest['bmi_flag'] = '偏胖';// 偏胖
                }elseif($rest['bmi'] >27){
                    $rest['bmi_flag'] = '肥胖';// 肥胖
                }elseif($rest['bmi'] <18.5){
                    $rest['bmi_flag'] = '偏瘦';// 偏瘦
                }
                $rest['color_ps'] = config('data_color.green');
                $rest['range_ps'] = '<18.5';
                $rest['wz_ps'] = '偏瘦';
                $rest['color_normal'] = config('data_color.green');
                $rest['range_normal'] = '18.5-23.9';
                $rest['wz_normal'] = '正常';
                $rest['color_fat'] = config('data_color.yellow');
                $rest['range_fat'] = '24-26.9';
                $rest['wz_fat'] = '偏胖';
                $rest['color_obesity'] = config('data_color.red');
                $rest['range_obesity'] = '>27';
                $rest['wz_obesity'] = '肥胖';
                $rest['weight'] = $res3['weight']*2;//体重转换为斤
                $rest['weight'] .= '斤';
                $rest['weight_cosmetology'] = $res3['weight_cosmetology'];
                $rest['weight_standard'] = $res3['weight_standard'];
                $rest['rate_waist_hip'] = $res3['rate_waist_hip'];
                // 男性
                $rest['rate_waist_hip_tips'] = config('sub_tips.rate_waist_hip_tips')['ffp'];
                if($arr['sex']==1){
                    if($rest['rate_waist_hip']>0.9){
                        $rest['rate_waist_hip_tips'] = config('sub_tips.rate_waist_hip_tips')['fp'];
                    }else{
                        $rest['rate_waist_hip_tips'] = config('sub_tips.rate_waist_hip_tips')['ffp'];
                    }
                }else{
                    // 女性
                    if($rest['rate_waist_hip']>0.8){
                        $rest['rate_waist_hip_tips'] = config('sub_tips.rate_waist_hip_tips')['fp'];
                    }else{
                        $rest['rate_waist_hip_tips'] = config('sub_tips.rate_waist_hip_tips')['ffp'];
                    }
                }
//                $rest['rate_waist_hip_tips'] = config('sub_tips.rate_waist_hip_tips');
                $rest['content'] = config('sub_tips.suc_tip') ;
            }
            // 推荐文章
            $rest['recommend'] = ArchivesFunc::articleRecommend();
        }
        return $rest;
    }
}