<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:18
 */

namespace app\api\model;

use phpDocumentor\Reflection\DocBlockTest;
use think\Model;
use think\Db;
use app\api\model\ArchivesFunc;
/*
 * 代餐档案数据模块辅助查询相关方法
 * */
class SubmealFunc extends Model
{
    /*
     * 功能: 查询用户个人相关信息
     * 请求: user_id=>用户id
     * */
    public static function getUserInfo($user_id)
    {
       // 查询代餐档案个人信息
        $res = ArchivesFunc::underwearUserInfo($user_id);
        // 查询自己代餐相关个人信息
        $map['user_id'] = $user_id;
        $res1 = Db::table('underwear_user_info')->field('shape,over_weight,health,eating_habits,exercise,allergy,is_reduce_weight,is_vegetarian_diet,age,occupation,qq,weixin,email,contact_time,address,sex')->where($map)->limit(1)->find();
        if($res && $res1){
            $res = array_merge($res,$res1);
        }
        $rest = $res;
        return $rest;
    }
    /*
     * 功能: 查询用户个人相关信息
     * 请求: user_id=>用户id,$dt1=>本月1号 2018-11-01,$dt2=>下月1号 2018-12-01,$fill_user_id=>美容师id
     * */
    public static function getDinerList($user_id,$dt1,$dt2,$fill_user_id=0)
    {
        // 查询代餐档案个人信息
        $map['user_id'] = $user_id;
        $map['create_time'] = ['>=',strtotime($dt1)];
        $map1['create_time'] = ['<',strtotime($dt2)];
        if($fill_user_id){
            $map['fill_user_id'] = $fill_user_id;
        }
        $res = Db::table('underwear_meal_record r')->field('*')->where($map)->where($map1)->order('r.create_time desc')->select();
        return $res;
    }
    /*
     * 功能: 每月每天日期
     * 请求: $dt=>日期2018-11
     * */
    public static function getMonth($dt)
    {
        $res = [];
        $dt1 = date('Y-m');//获取当前年月
        // 当前月,显示到截止日期
        if($dt1 == $dt){
            $t = date("d");//获取当前月份天数
        }else{
            // 非当前月,显示每月总天数
            $t = date("t",strtotime($dt));//每月总天数
        }
        $start_time = strtotime(date($dt.'-01')); //获取本月第一天时间戳
        for($i=0;$i<$t;$i++){
            $res[] = date('Y-m-d',$start_time+$i*86400); //每隔一天赋值给数组
        }
        return $res;
    }
    /*
     * 功能: 代餐记录用户信息
     * 请求: $record_id=>记录id
     * */
    public static function UserInfo($record_id)
    {
        // 用户信息[头像,名称,日期,体重,美容师,异常数,异常颜色]
        $map['r.id'] = $record_id;
        $res = Db::table('underwear_meal_record r')->join(['underwear_user_info'=>'u'],['r.user_id=u.user_id'],'LEFT')->field('u.user_id,u.head_img,u.user_name,r.create_time,r.weight,r.fill_user_name,r.tips,r.fill_user_id,r.weight_flag,r.weight_index_flag,body_fat_flag,fat_volume_flag,muscle_volume_flag,bone_mass_flag,visceral_fat_flag,metabolism_flag,body_age_flag,waist_flag,hipline_flag,bmi_flag,r.rate_waist_hip_flag')->where($map)->limit(1)->find();
        // 美容师
        return $res;
    }
    /*
     * 功能: 属性名称及字段名称查询
     * 请求: $type=>类型id
     * */
    public static function proList($type)
    {
        $res = [];
        $map['isshow'] = 1;
        if($type){
            $map['type'] = $type;
        }
        $res = Db::table('underwear_conf_property')->field('pro_name,field_name,pro_name_suffix,pro_explain,pro_pic')->where($map)->order('create_time desc')->select();
        // 美容师
        return $res;
    }
    /*
     * 功能: 获取代餐数据1条记录
     * 请求: $record_id=>记录id
     * */
    public static function getMealOne($record_id)
    {
        $map['id'] = $record_id;
        $res = Db::table('underwear_meal_record')->field('*')->where($map)->order('create_time desc')->limit(1)->find();
        return $res;
    }
    /*
     * 功能: 代餐档案个人信息列表-属性名称及字段名称查询
     * 请求: $type=>类型id
     * */
    public static function commonList($type,$pid=0)
    {
        $res = [];
        $map['isshow'] = 1;
        if($type){
            $map['type'] = $type;
        }
        $map['pid'] = $pid;
        $res = Db::table('underwear_conf_common')->field('id,name,field_name,pro_id')->where($map)->order('sort desc')->select();
        // 美容师
        return $res;
    }
    /*
     * 功能: 获取属性名
     * 请求: $ids=>属性值id数组
     * */
    public static function getProName($ids=[],$pid=0)
    {
        $map['pro_id'] = ['in',$ids];
        $map['type'] = 1;
        $map['isshow'] = 1;
        $map['pid'] = $pid;
        $res = Db::table('underwear_conf_common')->field('name')->where($map)->order('sort desc')->select();
        $rest = '';
        if(!empty($res)){
            foreach ($res as $v) {
                $rest .= $v['name'].' ';
            }
        }
        if(!empty($rest)){
            $rest = rtrim($rest);
        }
        return $rest ;
    }
    /*
     * 功能: 代餐档案-基本信息-修改
     * 请求: $arr
     * */
    public static function mealUserInfoUpd($arr)
    {
        $map['user_id'] = $arr['user_id'];
        $res = Db::table('underwear_user_info')->where($map)->update($arr);
        return $res;
    }
    /*
     * 功能: 13.数据记录-自己-查询
     * 请求:
     * */
    public static function userMealSel()
    {
        $map['type'] = 3;
        $map['isshow'] = 1;
        $res = Db::table('underwear_conf_property')->field('pro_name,pro_pic,field_name,pro_name_suffix')->where($map)->order('create_time desc')->select();
        return $res;
    }
    /*
     * 功能: 13.数据记录-自己-提交
     * 请求: $arr => [user_id=>用户id,store_id=>门店id,weight=>体重,waist=>腰围,hipline=>臀围],$record_id=>0插入
     * */
    public static function userMealAdd($arr,$record_id=0)
    {
        // 获取用户头像
        $arr['head_img'] = '';
        $map['user_id'] = $arr['user_id'];
        $res_u = Db::table('underwear_user_info')->field('head_img,height,weight,age,sex')->where($map)->limit(1)->find();
        if(!empty($res_u)){
            $arr['head_img'] = $res_u['head_img'];
            $arr['height'] = $res_u['height'];
        }
        // 标准体重
        $arr2['weight_standard'] = ($arr['height']-70)*0.6;
        $height = round($arr['height']/100,2);
        $arr2['bmi'] = 0;
        if($height){
            $arr2['bmi'] = round($arr['weight']/($height * $height),2);
        }
        $arr2['rate_waist_hip'] = round($arr['waist']/$arr['hipline'],2);
        $arr1 = [
            'user_id' => $arr['user_id'],
            'head_img' => $arr['head_img'],
            'weight' => $arr['weight'],
            'height' => $arr['height'],
            'weight_standard' => $arr2['weight_standard'],
            'weight_cosmetology' => $arr2['weight_standard']*0.9,
            'bmi' => $arr2['bmi'],
            'waist' => $arr['waist'],
            'hipline' => $arr['hipline'],
            'rate_waist_hip' => $arr2['rate_waist_hip'],
            'create_time' => time()
        ];
        $arr_f = [
            'height' => $arr1['height'],
            'bmi' => $arr1['bmi'] ,
            'age' => $res_u['age']==null?0:$res_u['age'] ,
            'body_fat' => '',
            'weight' => $arr1['weight'],
            'fat_volume' => '',
            'bone_mass' => '',
            'visceral_fat' => '',
            'metabolism' => '',
            'rate_waist_hip' => $arr1['rate_waist_hip']
        ];
        $res_f = [];
        $sex = $res_u['sex']==null?0:$res_u['sex'];
        if($sex){
            $res_f = self::manBodyStandard($arr_f);
        }else{
            $res_f = self::womanBodyStandard($arr_f);
        }
        $arr1 = array_merge($arr1,$res_f);
        // 更新
        if($record_id){
            $map11['id'] = $record_id;
            $res = Db::table('underwear_meal_record')->where($map11)->update($arr1);
        }else{
            // 插入
            $res = Db::table('underwear_meal_record')->insertGetId($arr1);
        }
        return $res;
    }
    /*
     * 功能: 13.数据记录-美容师-查询
     * 请求:
     * */
    public static function beauticianMealSel()
    {
        $map['type'] = 2;
        $map['isshow'] = 1;
        $res = Db::table('underwear_conf_property')->field('pro_name,pro_pic,field_name,pro_name_suffix')->where($map)->order('create_time desc')->select();
        return $res;
    }
    /*
     * 功能: 13.数据记录-美容师-提交
     * 请求: $arr => 提交数据,$record_id=>0插入,>0更新
     * */
    public static function beauticianMealAdd($arr,$record_id=0)
    {
        // 获取用户头像
        $arr2['head_img'] = '';
        $map['u.user_id'] = $arr['user_id'];
        $map['m.id'] = $arr['fill_user_id'];
        $res_u = Db::table('underwear_user_info u,ims_bj_shopn_member m')->field('head_img,height,weight,m.realname pidname,u.age,u.sex')->where($map)->limit(1)->find();
        if(!empty($res_u)){
            $arr2['head_img'] = $res_u['head_img'];
            $arr2['height'] = $res_u['height'];
            $arr2['fill_user_name'] = $res_u['pidname'];
        }
        // 标准体重
        $arr2['weight_standard'] = ($arr2['height']-70)*0.6;
        $height = round($arr2['height']/100,2);
        $arr2['bmi'] = 0;
        if($height){
            $arr2['bmi'] = round($arr['weight']/($height * $height),2);
        }
        $arr2['rate_waist_hip'] = round($arr['waist']/$arr['hipline'],2);
        // 查询对应的美容师名称
        $arr1 = [
            'fill_user_id'=>$arr['fill_user_id'],
            'fill_user_name'=>$arr2['fill_user_name'],
            'user_id' => $arr['user_id'],
            'head_img' => $arr2['head_img'],
            'weight' => $arr['weight'],
            'height' => $arr2['height'],
            'weight_standard' => $arr2['weight_standard'],
            'weight_cosmetology' => $arr2['weight_standard']*0.9,
            'weight_index' => $arr['weight_index'],
            'bmi' => $arr['bmi'],
            'rate_waist_hip' => $arr2['rate_waist_hip'],
            'body_fat' => $arr['body_fat'],
            'fat_volume' => $arr['fat_volume'],
            'muscle_volume' => $arr['muscle_volume'],
            'bone_mass' => $arr['bone_mass'],
            'visceral_fat' => $arr['visceral_fat'],
            'metabolism' => $arr['metabolism'],
            'body_age' => $arr['body_age'],
            'body_water' => $arr['body_water'],
            'waist' => $arr['waist'],
            'hipline' => $arr['hipline'],
            'left_hip_height' => $arr['left_hip_height'],
            'right_hip_height' => $arr['right_hip_height'],
            'create_time' => time()
        ];
        // 查询对应的标记
        // [height,bmi,age,body_fat,weight,fat_volume,bone_mass,visceral_fat,metabolism]

        $arr_f = [
            'height' => $arr1['height'],
            'bmi' => $arr1['bmi'] ,
            'age' => $res_u['age']==null?0:$res_u['age'] ,
            'body_fat' => $arr1['body_fat'],
            'weight' => $arr1['weight'],
            'fat_volume' => $arr1['fat_volume'],
            'bone_mass' => $arr1['bone_mass'],
            'visceral_fat' => $arr1['visceral_fat'],
            'metabolism' => $arr1['metabolism'],
            'rate_waist_hip' => $arr1['rate_waist_hip']
        ];
        $res_f = [];
        $sex = $res_u['sex']==null?0:$res_u['sex'];
        if($sex){
            $res_f = self::manBodyStandard($arr_f);
        }else{
            $res_f = self::womanBodyStandard($arr_f);
        }
        $arr1 = array_merge($arr1,$res_f);
        // 更新
        if($record_id){
            $map11['id'] = $record_id;
            $res = Db::table('underwear_meal_record')->where($map11)->update($arr1);
        }else{
            // 插入
            $res = Db::table('underwear_meal_record')->insertGetId($arr1);
        }
        return $res;
    }
    /*
     * 功能: 通过日期获取记录id
     * 请求: $dt1=>日期
     * */
    public static function getMealId($dt1,$user_id)
    {
        $record_id = 0;
        $dt = strtotime($dt1);
        $map['create_time'] = ['>=',$dt];
        $map['create_time'] = ['<',strtotime("+1 day",$dt)];
        $map['user_id'] = $user_id;
        $res = Db::table('underwear_meal_record')->field('id,fill_user_id')->where($map)->order('create_time asc')->select();
        if(!empty($res)){
            foreach ($res as $v) {
                $record_id = $v['id'];
                if($v['fill_user_id']){
                    $record_id = $v['id'];
                }
            }
        }
        return $record_id;
    }
    /*
     * 功能: 女性代餐数据标准判断
     * 请求: $arr=>女性代餐数据标准判断[height,bmi,age,body_fat,height,fat_volume,bone_mass,visceral_fat,metabolism]
     * */
    public static function womanBodyStandard($arr)
    {
        $rest['weight_standard'] = ($arr['height']-70)*0.6;
        $rest['weight_cosmetology'] = $rest['weight_standard']*0.9;
        $rest['weight_flag'] = 2;
        $rest['bmi_flag'] = 2;
        $rest['body_fat_flag'] = 2;
        $rest['fat_volume_flag'] = 2;
        $rest['bone_mass_flag'] = 2;
        $rest['visceral_fat_flag'] = 2;
        $rest['metabolism_flag'] = 2;
        $rest['rate_waist_hip_flag'] = 2;
        if(isset($arr['rate_waist_hip']) && $arr['rate_waist_hip']>0.8){
            $rest['rate_waist_hip_flag'] = 3;
        }
        if($arr['weight']>$rest['weight_standard']){
            $rest['weight_flag'] = 3;
        }elseif($arr['weight']<$rest['weight_standard']){
            $rest['weight_flag'] = 1;
        }
        if($arr['bmi']>=24 && $arr['bmi']<=26.9){
            $rest['bmi_flag'] = 3;
        }elseif($arr['bmi']>=27){
            $rest['bmi_flag'] = 3;
        }elseif($arr['bmi']>=18.5 && $arr['bmi']<24){
            $rest['bmi_flag'] = 2;
        }else{
            $rest['bmi_flag'] = 1;
        }
        if($arr['age']>=18 && $arr['age']<=39){
            if($arr['body_fat']<21){
                $rest['body_fat_flag'] = 1;
            }elseif($arr['body_fat']>27){
                $rest['body_fat_flag'] = 3;
            }
        }elseif($arr['age']>=40 && $arr['age']<=59){
            if($arr['body_fat']<22){
                $rest['body_fat_flag'] = 1;
            }elseif($arr['body_fat']>28){
                $rest['body_fat_flag'] = 3;
            }
        }elseif($arr['age']>=60){
            if($arr['body_fat']<23){
                $rest['body_fat_flag'] = 1;
            }elseif($arr['body_fat']>29){
                $rest['body_fat_flag'] = 3;
            }
        }
        // fat_volume
        if($arr['height']<160){
            if($arr['fat_volume']<(31.9-2.8)){
                $rest['fat_volume_flag'] = 1;
            }elseif($arr['fat_volume']>(31.9+2.8)){
                $rest['fat_volume_flag'] = 3;
            }
        }elseif($arr['height']>=160 && $arr['height']<=170){
            if($arr['fat_volume']<(35.2-2.3)){
                $rest['fat_volume_flag'] = 1;
            }elseif($arr['fat_volume']>(35.2+2.3)){
                $rest['fat_volume_flag'] = 3;
            }
        }elseif($arr['height']>170){
            if($arr['fat_volume']<(39.5-3.0)){
                $rest['fat_volume_flag'] = 1;
            }elseif($arr['fat_volume']>(39.5+3.0)){
                $rest['fat_volume_flag'] = 3;
            }
        }
        // bone_mass
        if($arr['weight']<45){
            if($arr['bone_mass']<1.8){
                $rest['bone_mass_flag'] = 1;
            }elseif($arr['fat_volume']>1.8){
                $rest['fat_volume_flag'] = 3;
            }
        }elseif($arr['weight']>=45 && $arr['weight']<=60){
            if($arr['bone_mass']<2.2){
                $rest['bone_mass_flag'] = 1;
            }elseif($arr['fat_volume']>2.2){
                $rest['fat_volume_flag'] = 3;
            }
        }elseif($arr['weight']>60){
            if($arr['bone_mass']<2.5){
                $rest['bone_mass_flag'] = 1;
            }elseif($arr['fat_volume']>2.5){
                $rest['fat_volume_flag'] = 3;
            }
        }
        // visceral_fat
        if($arr['visceral_fat']>10){
            $rest['fat_volume_flag'] = 3;
        }
        // metabolism
        if($arr['age']>=18 && $arr['age']<=29){
            if($arr['metabolism']<1210){
                $rest['metabolism_flag'] = 1;
            }elseif($arr['metabolism']>1210){
                $rest['metabolism_flag'] = 3;
            }
        }
        return $rest;
    }
    /*
     * 功能: 男性代餐数据标准判断
     * 请求: $arr=>男性代餐数据标准判断[height,bmi,age,body_fat,height,fat_volume,bone_mass,visceral_fat,metabolism]
     * */
    public static function manBodyStandard($arr)
    {
        $rest['weight_standard'] = ($arr['height']-80)*0.7;
        $rest['weight_cosmetology'] = $rest['weight_standard']*0.9;
        $rest['bmi_flag'] = 2;
        $rest['body_fat_flag'] = 2;
        $rest['fat_volume_flag'] = 2;
        $rest['bone_mass_flag'] = 2;
        $rest['visceral_fat_flag'] = 2;
        $rest['metabolism_flag'] = 2;

        $rest['rate_waist_hip_flag'] = 2;
        if(isset($arr['rate_waist_hip']) && $arr['rate_waist_hip']>0.9){
            $rest['rate_waist_hip_flag'] = 3;
        }

        if($arr['bmi']>=24 && $arr['bmi']<=26.9){
            $rest['bmi_flag'] = 3;
        }elseif($arr['bmi']>=27){
            $rest['bmi_flag'] = 3;
        }elseif($arr['bmi']>=18.5 && $arr['bmi']<24){
            $rest['bmi_flag'] = 2;
        }else{
            $rest['bmi_flag'] = 1;
        }
        if($arr['age']>=18 && $arr['age']<=39){
            if($arr['body_fat']<11){
                $rest['body_fat_flag'] = 1;
            }elseif($arr['body_fat']>16){
                $rest['body_fat_flag'] = 3;
            }
        }elseif($arr['age']>=40 && $arr['age']<=59){
            if($arr['body_fat']<12){
                $rest['body_fat_flag'] = 1;
            }elseif($arr['body_fat']>17){
                $rest['body_fat_flag'] = 3;
            }
        }elseif($arr['age']>=60){
            if($arr['body_fat']<14){
                $rest['body_fat_flag'] = 1;
            }elseif($arr['body_fat']>19){
                $rest['body_fat_flag'] = 3;
            }
        }
        // fat_volume
        if($arr['height']<160){
            if($arr['fat_volume']<(42.5-4.0)){
                $rest['fat_volume_flag'] = 1;
            }elseif($arr['fat_volume']>(42.5+4.0)){
                $rest['fat_volume_flag'] = 3;
            }
        }elseif($arr['height']>=160 && $arr['height']<=170){
            if($arr['fat_volume']<(48.2-4.2)){
                $rest['fat_volume_flag'] = 1;
            }elseif($arr['fat_volume']>(48.2+4.2)){
                $rest['fat_volume_flag'] = 3;
            }
        }elseif($arr['height']>170){
            if($arr['fat_volume']<(54.4-5.0)){
                $rest['fat_volume_flag'] = 1;
            }elseif($arr['fat_volume']>(54.4+5.0)){
                $rest['fat_volume_flag'] = 3;
            }
        }
        // bone_mass
        if($arr['weight']<=60){
            if($arr['bone_mass']<2.5){
                $rest['bone_mass_flag'] = 1;
            }elseif($arr['bone_mass']>2.5) {
                $rest['bone_mass_flag'] = 3;
            }
        }elseif($arr['weight']>60 && $arr['weight']<=75){
            if($arr['bone_mass']<2.9){
                $rest['bone_mass_flag'] = 1;
            }elseif($arr['bone_mass']>2.9){
                $rest['bone_mass_flag'] = 3;
            }
        }elseif($arr['weight']>75){
            if($arr['bone_mass']<3.2){
                $rest['bone_mass_flag'] = 1;
            }elseif($arr['bone_mass']>3.2){
                $rest['bone_mass_flag'] = 3;
            }
        }
        // visceral_fat
        if($arr['visceral_fat']>10){
            $rest['visceral_fat_flag'] = 3;
        }
        // metabolism
        if($arr['age']>=18 && $arr['age']<=29){
            if($arr['metabolism']<1550){
                $rest['metabolism_flag'] = 1;
            }elseif($arr['metabolism']>1550){
                $rest['metabolism_flag'] = 3;
            }
        }elseif($arr['age']>=30 && $arr['age']<=49){
            if($arr['metabolism']<1500){
                $rest['metabolism_flag'] = 1;
            }elseif($arr['metabolism']>1500){
                $rest['metabolism_flag'] = 3;
            }
        }elseif($arr['age']>=50 && $arr['age']<=69){
            if($arr['metabolism']<1350){
                $rest['metabolism_flag'] = 1;
            }elseif($arr['metabolism']>1350){
                $rest['metabolism_flag'] = 3;
            }
        }elseif($arr['age']>=70){
            if($arr['metabolism']<1220){
                $rest['metabolism_flag'] = 1;
            }elseif($arr['metabolism']>1220){
                $rest['metabolism_flag'] = 3;
            }
        }
        return $rest;
    }
    /*
     * 功能: 代餐档案数据记录-修改
     * 请求: $arr=>[id=>记录id]
     * */
    public static function userMealUpd($arr)
    {
        $map['id'] = $arr['id'];
        $res = Db::table('underwear_meal_record')->where($map)->update($arr);
        return $res;
    }
    /*
     * 功能: 体重标准判断
     * 请求: $weight=>体重,$height=>身高
     * */
    public static function weightRule($weight,$height)
    {
        $arr = [];
        $arr['weight_bz'] = ($height-70)*0.6;
        $arr['weight_mr'] = $arr['weight_bz'] * 0.9;
        if($weight < $arr['weight_mr']){
            $arr['weight_flag'] = '1';
            $arr['weight_flag_tips'] = '偏瘦';
        }elseif($weight >= $arr['weight_mr'] && $weight <= $arr['weight_bz']){
            $arr['weight_flag'] = '2';
            $arr['weight_flag_tips'] = '正常';
        }elseif($weight > $arr['weight_bz']){
            $arr['weight_flag'] = '3';
            $arr['weight_flag_tips'] = '偏胖';
        }
        return $arr;
    }
    /*
     * 功能: 上级美容师查询用户代餐数据,能看到帮填的和自己填的
     * 请求: user_id=>用户id,$dt1=>本月1号 2018-11-01,$dt2=>下月1号 2018-12-01,$fill_user_id=>美容师id
     * */
    public static function getDinerListPid($user_id,$dt1,$dt2,$fill_user_id=0)
    {
        // 查询代餐档案个人信息
        $map['user_id'] = $user_id;
        $map['create_time'] = ['>=',strtotime($dt1)];
        $map1['create_time'] = ['<',strtotime($dt2)];
        if($fill_user_id){
            $map1['fill_user_id'] = ['in',[0,$fill_user_id]];
        }
        $res = Db::table('underwear_meal_record r')->field('*')->where($map)->where($map1)->order('r.create_time desc')->select();
        return $res;
    }

}