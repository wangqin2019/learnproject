<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/19
 * Time: 13:07
 */

namespace app\api\model;
/*
 * 代餐档案数据相关API
 * */
use app\api\model\PersonalData as PersonalDataMod;
class Submeal
{
    /*
     * 功能:我的档案-代餐档案
     * 请求:user_id=>用户id,$dt=>日期
     * */
    public function dinnerFiles($user_id,$dt,$fill_user_id=0)
    {
        // 基本资料
        $rest['user_info'] = (object)[];
        $res_u = SubmealFunc::getUserInfo($user_id);
        if(!empty($res_u)){
            $arr_u['user_id'] = $res_u['user_id'];
            $arr_u['user_name'] = $res_u['user_name'];
            $arr_u['head_img'] = $res_u['head_img'];
            $arr_u['sex'] = $res_u['sex'];
            $arr_u['age'] = $res_u['age'];
            $arr_u['height'] = $res_u['height'];
            $arr_u['weight'] = $res_u['weight'];
            $arr_u['occup_name'] = '';
            $res_c = PersonalDataMod::ucplSel(41);
            if(!empty($res_c)){
                foreach ($res_c as $v_c) {
                    if(strstr($res_u['occupation'],$v_c['pro_id'])){
                        $arr_u['occup_name'] = $v_c['pro_name'];
                        break;
                    }
                }
            }
            $arr_u['mobile'] = $res_u['mobile'];
            $arr_u['birthday'] = $res_u['birthday'];
            $arr_u['qq'] = $res_u['qq'];
            $arr_u['weixin'] = $res_u['weixin'];
            $arr_u['email'] = $res_u['email'];
            $arr_u['contact_time'] = $res_u['contact_time'];
            $arr_u['address'] = $res_u['address'];
            $rest['user_info'] = $arr_u;
        }
        // 日期记录
        $dt1 = date('Y-m',strtotime($dt));
        // 获取当前请求月的总天数
        $res1 = SubmealFunc::getMonth($dt1);
        // 获取当前月记录日期
        $dt11 = date("Y-m-01",strtotime($dt));// 当前月1号
        $dt12 = date("Y-m-d",strtotime("$dt11 +1 month"));// 下月1号
        $res2 = SubmealFunc::getDinerList($user_id,$dt11,$dt12,$fill_user_id);
        // 判断是否是用户上级美容师
        // 上级美容师能看到顾客自己填写记录+上级美容师填写记录
        if($fill_user_id){
            $res_p = ArchivesFunc::isPid($user_id,$fill_user_id);
            if($res_p){
                $res2 = SubmealFunc::getDinerListPid($user_id,$dt11,$dt12,$fill_user_id);
            }
        }
        $arr2 = [];
        // 循环比对每月日期
        foreach ($res1 as $v1) {
            $arr_r=[];
            $arr1['dt'] = $v1;
            // dt,title,pro_val1,pro_name2,pro_val2,pro_name3,pro_val3,pro_name4,pro_val4
            if(!empty($res2)){
                $arr_r1 = [];$arr_r2 = [];
                foreach ($res2 as $v2) {
                    $dt2 = date('Y-m-d',$v2['create_time']);
                    if($v1 == $dt2){
                        // 美容师记录
                        if($v2['fill_user_id']){
                            $arr_r1['record_id'] = $v2['id'];
                            $arr_r1['dt1'] = date('Y-m-d H:i:s',$v2['create_time']);
                            $arr_r1['pro_val1'] = $v2['weight'].'kg';
                            $arr_r1['title'] = '美容师:'.$v2['fill_user_name'];
                            $arr_r1['pro_name2'] = '体脂肪率(%)';
                            $arr_r1['pro_val2'] = $v2['body_fat'];
                            $arr_r1['pro_name3'] = '内脏脂肪(kg)';
                            $arr_r1['pro_val3'] = $v2['visceral_fat'];
                            $arr_r1['pro_name4'] = '基础代谢';
                            $arr_r1['pro_val4'] = $v2['metabolism'];
                        }else{
                            // 自测记录
                            $arr_r2['record_id'] = $v2['id'];
                            $arr_r2['dt1'] = date('Y-m-d H:i:s',$v2['create_time']);
                            $arr_r2['pro_val1'] = $v2['weight'].'kg';
                            $arr_r2['title'] = '自测';
                            $arr_r2['pro_name2'] = '标准体重(kg)';
                            $arr_r2['pro_val2'] = $v2['weight_standard'];
                            $arr_r2['pro_name3'] = '美容体重(kg)';
                            $arr_r2['pro_val3'] = $v2['weight_cosmetology'];
                            $arr_r2['pro_name4'] = 'BMI';
                            $arr_r2['pro_val4'] = $v2['bmi'];
                        }
                    }
                }
                if(!empty($arr_r2)){
                    $arr_r[] = $arr_r2;
                }
                if(!empty($arr_r1)){
                    $arr_r[] = $arr_r1;
                }
            }
            $arr1['data_list'] = $arr_r;
            $arr2[] = $arr1;
        }
        $rest['calendar'] = $arr2;
        return $rest;
    }
    /*
     * 功能:代餐数据-详情
     * 请求:$record_id=>记录id
     * */
    public function mealDetails($record_id)
    {
        $rest = [];$resp=[];
        // 用户信息[头像,名称,日期,体重,美容师,异常数,异常颜色]
        $res_u = SubmealFunc::UserInfo($record_id);
        if(!empty($res_u)){
            $dt = date('Y',$res_u['create_time']).'年'.date('m',$res_u['create_time']).'月'.date('d',$res_u['create_time']).'日';
            $res_u['create_time'] = $dt;
            $res_u1 = ArchivesFunc::userInfo($res_u['user_id']);
            if(!empty($res_u1)){
                $res_u['head_img'] = $res_u1['head_img'] ;
            }
        }
        // 异常数,异常颜色
        $res_u['yc_cnt'] = 0; // 异常数逻辑规则判断
        $res_u['yc_color'] = config('data_color.red');
        // 属性列表[属性名称,标记,颜色,属性值]
        $res_p = SubmealFunc::proList(2);
        $res_r = SubmealFunc::getMealOne($record_id);

        // 标记判断规则
        $res = [
            'weight_flag' => 2,
            'weight_index_flag' => 2,
            'body_fat_flag' => 2,
            'fat_volume_flag' => 2,
            'muscle_volume_flag' => 2,
            'bone_mass_flag' => 2,
            'visceral_fat_flag' => 2,
            'metabolism_flag' => 2,
            'body_age_flag' => 2,
            'body_water_flag' => 2,
            'waist_flag' => 2,
            'hipline_flag' => 2,
            'left_hip_height_flag' => 2,
            'right_hip_height_flag' => 2,

            'weight_standard_flag' => 2,
            'weight_cosmetology_flag' => 2,
            'bmi_flag' => 2,
            'rate_waist_hip_flag' => 2
        ];
        if(!empty($res_u)){
            foreach ($res as $k=>$v) {
                if(isset($res_u[$k]) && $res_u[$k]){
                    $res[$k] = $res_u[$k];
                }
           }
        }
        // 颜色
        $color = [
            '1' => config('data_color.red'),
            '2' => config('data_color.green'),
            '3' => config('data_color.yellow'),
        ];
        // 自测显示数据
        $arr_zc = ['waist_flag','hipline_flag','weight_standard_flag','weight_cosmetology_flag','bmi_flag','rate_waist_hip_flag'];
        $arr_mrs = ['weight_index_flag','body_fat_flag','muscle_volume_flag','bone_mass_flag','visceral_fat_flag','metabolism_flag','body_age_flag','body_water_flag','waist_flag','hipline_flag','left_hip_height_flag','right_hip_height_flag'];
        foreach ($res as $k=>$v) {
            if($v!=2){
                if(!$res_r['fill_user_id']){
                    if(in_array($k,$arr_zc)){
                        $res_u['yc_cnt'] += 1;
                    }
                }else{
                    if(in_array($k,$arr_mrs)){
                        $res_u['yc_cnt'] += 1;
                    }
                }
            }
        }

        // 判断是自测还是美容师记录 (默认美容师记录)
        if(!empty($res_r)){
            // 自测记录
            if(!$res_r['fill_user_id']){
                $res_p3 = SubmealFunc::proList(3);
                $res_p4 = SubmealFunc::proList(4);
                if(!empty($res_p3) && !empty($res_p4)){
                    $res_p = array_merge($res_p3,$res_p4);
                }
            }
        }

        if(!empty($res_p)){
            foreach ($res_p as $v) {
                $field_name = $v['field_name'];
                $resp1['pro_name'] = $v['pro_name'];
                $resp1['flag'] = $res[$field_name.'_flag'];
                $resp1['color'] = $color[$resp1['flag']];
                $resp1['pro_val'] = '';
                $resp1['pro_explain'] = $v['pro_explain'];
                if(!empty($res_r)){
                    $resp1['pro_val'] = round($res_r[$field_name],1);// 保留1位小数
                    $resp1['pro_val'] = $res_r[$field_name].$v['pro_name_suffix'];
                }
                $resp1['pro_flag_tips'] = '正常';
                $resp1['pro_flag_tips'] = config('sub_tips.daican')[$resp1['flag']];
                // 是否显示标签'正常'
                $resp1['isshow_pro_flag_tips'] = 1;// 1=>显示,0=>不显示
                $noshow_arr = ['weight_standard','weight_cosmetology'];
                if(in_array($field_name,$noshow_arr)){
                    $resp1['isshow_pro_flag_tips'] = 0;
                    $resp1['pro_flag_tips'] = '';
                }
                $resp[] = $resp1;
            }
            // 体重判断标准
            $arr_w11 = [];
            if(!$res_r['height']){
                $res_r1 = PersonalDataMod::uuiSel($res_r['user_id']);
                if(!empty($res_r1)){
                    $res_r['height'] = $res_r1['height'];
                }
            }
            $res_w = SubmealFunc::weightRule($res_r['weight'],$res_r['height']);
            if(!empty($res_w)){
                $arr_w11['flag'] = $res_w['weight_flag'];
                $arr_w11['pro_flag_tips'] = $res_w['weight_flag_tips'];
                $arr_w11['weight_bz'] = $res_w['weight_bz'];
                $arr_w11['weight_mr'] = $res_w['weight_mr'];
            }
            foreach ($resp as &$v) {
                if($v['pro_name'] == '体重'){
                    $v['flag'] = $arr_w11['flag'];
                    $v['pro_flag_tips'] = $arr_w11['pro_flag_tips'];
                    if($v['flag']!=2){
                        $res_u['yc_cnt'] += 1;
                    }
                }
                if($v['pro_name'] == '标准体重'){
                    $arr_w11['weight_bz'] = round($arr_w11['weight_bz'],1);
                    $v['pro_val'] = $arr_w11['weight_bz'].'kg';
                }
                if($v['pro_name'] == '美容体重'){
                    $arr_w11['weight_mr'] = round($arr_w11['weight_mr'],1);
                    $v['pro_val'] = $arr_w11['weight_mr'].'kg';
                }
            }
        }
        $rest['user_info'] = $res_u;
        $rest['data_list'] = $resp;
        $rest['recommend'] = [
            'title' => '美体塑形小课堂',
            'article_list' => []
        ];
        // 推荐文章
        $res_a = ArchivesFunc::articleRecommend();
        if(!empty($res_a)){
            $rest['recommend']['article_list'] = $res_a;
        }
        return $rest ;
    }
    /*
     * 功能:代餐数据-详情
     * 请求:$arr=>[user_id,record_id1=>记录1id,record_id2=>记录2id]
     * */
    public function mealCompare($arr)
    {
        $arr22 = [];
        $res1 = [
            'user_info' => [
                'user_name' => '',
                'head_img' => '',
                'dt1' => '',
                'dt11' => '',
                'dt2' => '',
                'dt22' => '',
                'title' => '健康指数',
                'score1' => 0,
                'score2' => 0,
                'tips' => '',
                'yc1_cnt' => 0,
                'yc2_cnt' => 0,
                'color_yc' => config('data_color.red'),
            ]
        ];
        $res_f = [
            'weight_flag' => 2,
            'weight_index_flag' => 2,
            'body_fat_flag' => 2,
            'fat_volume_flag' => 2,
            'muscle_volume_flag' => 2,
            'bone_mass_flag' => 2,
            'visceral_fat_flag' => 2,
            'metabolism_flag' => 2,
            'body_age_flag' => 2,
            'body_water_flag' => 2,
            'waist_flag' => 2,
            'hipline_flag' => 2,
            'left_hip_height_flag' => 2,
            'right_hip_height_flag' => 2,
        ];
        // 获取用户信息
        $res_u = SubmealFunc::getUserInfo($arr['user_id']);
        if(!empty($res_u)){
            $res1['user_info']['user_name'] = $res_u['user_name'];
            $res1['user_info']['head_img'] = $res_u['head_img'];
        }

        // 通过日期获取记录id
        $res_r1 = SubmealFunc::getMealId($arr['dt1'],$arr['user_id']);
        $res_r2 = SubmealFunc::getMealId($arr['dt2'],$arr['user_id']);
        // 获取老记录
        $res2 = SubmealFunc::getMealOne($res_r1);
        if(!empty($res2)){
            $dt1 = date('Y-m-d',$res2['create_time']);
            $dt11 = date('Y',$res2['create_time']).'年'.date('m',$res2['create_time']).'月'.date('d',$res2['create_time']).'日';
            $res1['user_info']['dt1'] = $dt1;
            $res1['user_info']['dt11'] = $dt11;
            $res1['user_info']['score1'] = $res2['score'];
        }
        // 获取新记录
        $res3 = SubmealFunc::getMealOne($res_r2);
        if(!empty($res3)){
            $dt2 = date('Y-m-d',$res3['create_time']);
            $dt22 = date('Y',$res3['create_time']).'年'.date('m',$res3['create_time']).'月'.date('d',$res3['create_time']).'日';
            $res1['user_info']['dt2'] = $dt2;
            $res1['user_info']['dt22'] = $dt22;
            $res1['user_info']['score2'] = $res3['score'];
            if($res3['weight']!=$res2['weight']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['weight'] > $res2['weight']){
                    $res_f['weight_flag'] = 3;
                }else{
                    $res_f['weight_flag'] = 1;
                }
            }
            if($res3['weight_index']!=$res2['weight_index']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['weight_index'] > $res2['weight_index']){
                    $res_f['weight_index_flag'] = 3;
                }else{
                    $res_f['weight_index_flag'] = 1;
                }
            }
            if($res3['body_fat']!=$res2['body_fat']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['body_fat'] > $res2['body_fat']){
                    $res_f['body_fat_flag'] = 3;
                }else{
                    $res_f['body_fat_flag'] = 1;
                }
            }
            if($res3['fat_volume']!=$res2['fat_volume']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['fat_volume'] > $res2['fat_volume']){
                    $res_f['fat_volume_flag'] = 3;
                }else{
                    $res_f['fat_volume_flag'] = 1;
                }
            }
            if($res3['muscle_volume']!=$res2['muscle_volume']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['muscle_volume'] > $res2['muscle_volume']){
                    $res_f['muscle_volume_flag'] = 3;
                }else{
                    $res_f['muscle_volume_flag'] = 1;
                }
            }
            if($res3['bone_mass']!=$res2['bone_mass']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['bone_mass'] > $res2['bone_mass']){
                    $res_f['bone_mass_flag'] = 3;
                }else{
                    $res_f['bone_mass_flag'] = 1;
                }
            }
            if($res3['visceral_fat']!=$res2['visceral_fat']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['visceral_fat'] > $res2['visceral_fat']){
                    $res_f['visceral_fat_flag'] = 3;
                }else{
                    $res_f['visceral_fat_flag'] = 1;
                }
            }
            if($res3['metabolism']!=$res2['metabolism']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['metabolism'] > $res2['metabolism']){
                    $res_f['metabolism_flag'] = 3;
                }else{
                    $res_f['metabolism_flag'] = 1;
                }
            }
            if($res3['body_age']!=$res2['body_age']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['body_age'] > $res2['body_age']){
                    $res_f['body_age_flag'] = 3;
                }else{
                    $res_f['body_age_flag'] = 1;
                }
            }
            if($res3['waist']!=$res2['waist']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['waist'] > $res2['waist']){
                    $res_f['waist_flag'] = 3;
                }else{
                    $res_f['waist_flag'] = 1;
                }
            }
            if($res3['hipline']!=$res2['hipline']){
                $res1['user_info']['yc2_cnt'] += 1;
                if($res3['hipline'] > $res2['hipline']){
                    $res_f['hipline_flag'] = 3;
                }else{
                    $res_f['hipline_flag'] = 1;
                }
            }
        }
        $score = $res1['user_info']['score2']-$res1['user_info']['score1'];
        $flag = 'rise';
        if($score<0){
            $score = abs($score);
            $flag = 'fall';
        }
        $res1['user_info']['tips'] = config('compare_tips.'.$flag);
        $res1['user_info']['tips'] = str_replace('x',$score,$res1['user_info']['tips']);

        // 数据列表
        $res_d = SubmealFunc::proList(2);
        if(!empty($res_d)){
            // [中文名称,正常标记,颜色,值]
            $color = [
                '3' => config('data_color.red'),
                '2' => config('data_color.green'),
                '1' => config('data_color.yellow'),
            ];
            foreach ($res_d as $v_d) {
                $field_name = $v_d['field_name'];
                $arr2['pro_name'] = $v_d['pro_name'];
                $arr2['flag'] = $res_f[$field_name.'_flag'];
                $arr2['color'] = $color[$arr2['flag']];
                $arr2['pro_val1'] = round($res2[$field_name],1);
                $arr2['pro_val2'] = round($res3[$field_name],1);
                // $arr2['pro_val1'] = $res2[$field_name].$v_d['pro_name_suffix'];
                // $arr2['pro_val2'] = $res3[$field_name].$v_d['pro_name_suffix'];
                 if($arr2['pro_val1'] < 1 ){
                    $arr2['pro_val1'] = '-';
                }else{
                    $arr2['pro_val1'] = $res2[$field_name].$v_d['pro_name_suffix'];
                }
                if($arr2['pro_val2'] < 1 ){
                    $arr2['pro_val2'] = '-';
                }else{
                    $arr2['pro_val2'] = $res3[$field_name].$v_d['pro_name_suffix'];
                }
                $arr2['pro_explain'] = $v_d['pro_explain'];// 名词解释说明
                $arr22[] = $arr2;
            }
            $res1['data_list'] = $arr22;
        }
        return $res1;
    }
    /*
     * 功能: 代餐档案-基本信息
     * 请求: $arr=>user_id=>用户id,store_id=>门店id
     * */
    public function mealUserInfo($arr)
    {
        $res1 = [
            'head_img' => '',
            'user_name' => '',
            'sex' =>0,
            'age' => 0,
            'height' => '',
            'weight' => '',
            'mobile' =>'',
            'pro_list' => []
        ];
        $arr1 = [];$arr_r = [];$res2 = [];
        // 个人基本信息 head_img,user_name,sex,age,height,weight,mobile
        $res_u = SubmealFunc::getUserInfo($arr['user_id']);
        if(!empty($res_u)){
            $res1['head_img'] = $res_u['head_img'];
            $res1['user_name'] = $res_u['user_name'];
            $res1['sex'] = $res_u['sex'];
            $res1['age'] = $res_u['age'];
            $res1['height'] = $res_u['height'];
            $res1['weight'] = $res_u['weight'];
            $res1['mobile'] = $res_u['mobile'];
            $res2['shape'] = $res_u['shape'];
            $res2['over_weight'] = $res_u['over_weight'];
            $res2['health'] = $res_u['health'];
            $res2['eating_habits'] = $res_u['eating_habits'];
            $res2['exercise'] = $res_u['exercise'];
            $res2['allergy'] = $res_u['allergy'];
            $res2['is_reduce_weight'] = $res_u['is_reduce_weight'];
            $res2['is_vegetarian_diet'] = $res_u['is_vegetarian_diet'];
        }
        // 多属性选择列表
        // [属性名称,属性值,属性字段,属性选择列表[]]
        $res_p = SubmealFunc::commonList(1);
        if(!empty($res_p)){
            foreach ($res_p as $v_p) {
                $arr3 = [];
                $arr1['field_name'] = $v_p['field_name'];
                $arr1['measure_name'] = $v_p['name']; // 属性名
                $arr1['measure_property_name'] = ''; // 默认选中属性值
                $arr1['measure_property_ids'] = []; // 已选中属性id,进去要在里面勾选上
                $arr1['measure_property_list'] = []; // 属性值列表
                $arr1['measure_property_ids'] = json_decode($res2[$arr1['field_name']]);
                $arr1['measure_property_name'] = SubmealFunc::getProName($arr1['measure_property_ids'],$v_p['id']);
                $res_p1 = SubmealFunc::commonList(1,$v_p['id']);
                if(!empty($res_p1)){
                    foreach ($res_p1 as $v_p1) {
                        $arr2['measure_property_id'] = $v_p1['pro_id'];
                        $arr2['measure_property_name'] = $v_p1['name'];
                        $arr2['checked'] = false;
                        if(!empty($arr1['measure_property_ids'])){
                            if(in_array($arr2['measure_property_id'],$arr1['measure_property_ids'])){
                                $arr2['checked'] = true;
//                                $arr2['measure_property_name'] = $v_p1['name'];
                            }
                        }
                        $arr3[] = $arr2;
                    }
                }
                if(!empty($arr3)){
                    $arr1['measure_property_list'] = $arr3;
                }
                $arr_r[] = $arr1;
            }
            $res1['pro_list'] = $arr_r;
        }
        return $res1;
    }
    /*
     * 功能: 代餐档案-基本信息-修改
     * 请求: $arr=>user_id=>用户id,store_id=>门店id
     * */
    public function mealUserInfoUpd($arr)
    {
//        if(!empty($arr)){
//            foreach ($arr as &$v) {
//                $v['shape'] = json_encode($v['shape'],true);
//                $v['over_weight'] = json_encode($v['over_weight'],true);
//                $v['health'] = json_encode($v['health'],true);
//                $v['eating_habits'] = json_encode($v['eating_habits'],true);
//                $v['exercise'] = json_encode($v['exercise'],true);
//                $v['allergy'] = json_encode($v['allergy'],true);
//                $v['is_reduce_weight'] = json_encode($v['is_reduce_weight'],true);
//                $v['is_vegetarian_diet'] = json_encode($v['is_vegetarian_diet'],true);
//            }
//        }

        $res = SubmealFunc::mealUserInfoUpd($arr);
        return $res;
    }
    /*
     * 功能: 13.数据记录-自己-查询
     * 请求:
     * */
    public function userMealSel()
    {
        $rest = [
            'tips' => '身型信息仅用于尺寸推荐及自测服务,请放心',
            'pro_list' => []
        ];
        $arr_r = [];
        $res = SubmealFunc::userMealSel();
        if(!empty($res)){
            foreach ($res as $v) {
                $arr1['pro_name1'] = $v['pro_name'];
                $arr1['pro_pic'] = $v['pro_pic'];
                $arr1['pro_name2'] = $v['pro_name'].'('.$v['pro_name_suffix'].')';
                $arr1['pro_suffix'] = $v['pro_name_suffix'];
                $arr1['field_name'] = $v['field_name'];
                $arr_r[] = $arr1;
            }
            $rest['pro_list'] = $arr_r;
        }
        return $rest;
    }
    /*
     * 功能: 13.数据记录-自己-提交
     * 请求: $arr => [user_id=>用户id,store_id=>门店id,weight=>体重,waist=>腰围,hipline=>臀围]
     * */
    public function userMealUpd($arr)
    {
        $rest = [];
        //判断是否当日数据已提交
        $res_dr_flag = PersonalDataMod::mealDtSel($arr['user_id']);
        if($res_dr_flag){
            $arr['id'] = $res_dr_flag;
            // 获取身高,体重,bmi,标准体重,美容体重更新数据
            $res = SubmealFunc::userMealAdd($arr,$res_dr_flag);
            $res = $arr['id'];
        }else{
            $res = SubmealFunc::userMealAdd($arr);
        }
//        $res = SubmealFunc::userMealAdd($arr);
        if(!empty($res)){
            $rest['record_id'] = $res;
        }
        return $rest;
    }
    /*
     * 功能: 13.数据记录-美容师
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function beauticianMealSel()
    {
        $rest = [
            'tips' => '身型信息仅用于尺寸推荐及自测服务,请放心',
            'pro_list' => []
        ];
        $arr = [];
        $res = SubmealFunc::beauticianMealSel();
        if(!empty($res)){
            foreach ($res as $v) {
                $arr1['pro_name1'] = preg_replace('/\(.*?\)/', '', $v['pro_name']);
                $arr1['pro_pic'] = $v['pro_pic'];
                $arr1['pro_name2'] = $v['pro_name'];
                $arr1['pro_suffix'] = $v['pro_name_suffix'];
                $arr1['field_name'] = $v['field_name'];
                $arr[] = $arr1;
            }
            $rest['pro_list'] = $arr;
        }
        return $rest;
    }
    /*
     * 功能: 13.数据记录-美容师-提交
     * 请求: $arr=>提交数据
     * */
    public function beauticianMealUpd($arr)
    {
        $rest = [];
        //判断是否当日数据已提交
        $res_dr_flag = PersonalDataMod::mealDtSel($arr['user_id'],$arr['fill_user_id']);
        if($res_dr_flag){
            $arr['id'] = $res_dr_flag;
            $res = SubmealFunc::beauticianMealAdd($arr,$res_dr_flag);
            $res = $arr['id'];
        }else{
            $res = SubmealFunc::beauticianMealAdd($arr);
        }
        if(!empty($res)){
            $rest['record_id'] = $res;
        }
        return $rest;
    }
}