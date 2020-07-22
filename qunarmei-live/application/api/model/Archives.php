<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:18
 */

namespace app\api\model;

use think\Model;
use think\Db;
use app\api\model\PersonalData as PersonalDataMod;
/*
 * 内衣数据模块相关方法
 * */
class Archives extends Model
{
    // 统一返回数据
    protected $rest = [];
    /*
     * 功能: 档案列表
     * 请求: user_id=>用户id
     * */
     public function archivesList($user_id)
     {
         $this->rest['user'] = [];
         $res_u = ArchivesFunc::userInfo($user_id);
         if(!empty($res_u)){
             $res_u['qr_code'] = config('images.qrcode_sl');
         }
         $this->rest['user'] = $res_u;
         $this->rest['category'] = ArchivesFunc::archivesCategory();
         return $this->rest;
     }
    /*
     * 功能: 内衣档案
     * 请求: user_id=>用户id,$fill_user_id=>填写记录的美容师id
     * */
    public function archivesUnderwear($user_id,$fill_user_id=0)
    {
        $this->rest = [
            'user' => (object)[],
            'underwear_record' => []
        ];
        $rest = [];
        // 名字,性别,年龄,身高,体重,职业
        // 基本信息: 会员卡种类,月收入,联系方式[手机、邮箱、微信、QQ],回访服务[手机、微信],方便联系时间,住址
        // 功能性调整内衣-全程跟进记录:[用户头像,年月日,分数,提示信息]
        $arr['user'] = ArchivesFunc::underwearUserInfo($user_id);
        if(!empty($arr['user'])){
            $rest['user_name'] = $arr['user']['user_name'];
            $rest['sex'] = $arr['user']['sex'];
            $rest['age'] = $arr['user']['age'];
            $rest['height'] = $arr['user']['height'];
            $rest['weight'] = $arr['user']['weight'];
            $rest['occup_name'] = $arr['user']['occup_name']==null?'':$arr['user']['occup_name'];
            $res_c = PersonalDataMod::ucplSel(41);
            if(!empty($res_c)){
                foreach ($res_c as $v_c) {
                    if(strstr($arr['user']['occup_name_id'],$v_c['pro_id'])){
                        $arr_u['occup_name'] = $v_c['pro_name'];
                        break;
                    }
                }
            }
            $rest['card_name'] = $arr['user']['card_name']==null?'':$arr['user']['card_name'];
            $rest['range_income'] = $arr['user']['range_income']==null?'':$arr['user']['range_income'];
            $brr['mobile'] = $arr['user']['mobile'];
            $crr['mobile'] = $arr['user']['mobile'];
            $brr['email'] = $arr['user']['email'];
            $brr['weixin'] = $arr['user']['weixin'];
            $crr['weixin'] = $arr['user']['weixin'];
            $brr['qq'] = $arr['user']['qq'];
            $rest['contact_mode'] = $brr;
            if($arr['user']['is_return_visit'] == 0){
                $crr['mobile'] = '';
                $crr['weixin'] = '';
            }
            $rest['return_visit'] = $crr;
            $rest['contact_time'] = $arr['user']['contact_time'];
            $rest['address'] = $arr['user']['address'];
        }
        $this->rest['user'] = $rest;
        $head_img = 'http://appc.qunarmei.com/normal_photo.png';
        $res_u1 = ArchivesFunc::userInfo($user_id);
        if(!empty($res_u1)){
            $head_img = $res_u1['head_img'];
        }
        $rest_rec = ArchivesFunc::underwearRecord($user_id);
        if(!empty($rest_rec)){
            foreach($rest_rec as &$v){
                $v['head_img'] = $head_img;
                if($v['create_time']){
                    $y = date('Y',$v['create_time']);
                    $m = date('m',$v['create_time']);
                    $d = date('d',$v['create_time']);
                    $v['create_time'] = $y.'年'.$m.'月'.$d.'日';
                }
                $v['score'] = $v['score'].'分';
            }
        }
        $this->rest['underwear_record'] = $rest_rec;
        if($fill_user_id){
            // 美容师填写只显示美容师帮忙填写的记录
            $res_p = ArchivesFunc::isPid($user_id,$fill_user_id);
            if(!$res_p){
                // 非顾客上级美容师
                $this->rest['user'] = (object)[];
                $res_fp = ArchivesFunc::fPidRecord($user_id,$fill_user_id);
                $arr_fp_r = [];
                if(!empty($res_fp)){
                    $arr_fp = [];
                    foreach($res_fp as $v_p){
                        $arr_fp = [
                            'record_id' => $v_p['id'],
                            'head_img' => $head_img,
                            // 'score' => $v_p['score'],
                            'score' => $v_p['score'].'分',
                            'tips' => $v_p['tips'],
                            'color' => $v_p['color'],
                            'create_time' => '',
                        ];
                        if($v_p['create_time']){
                            $y = date('Y',$v_p['create_time']);
                            $m = date('m',$v_p['create_time']);
                            $d = date('d',$v_p['create_time']);
                            $arr_fp['create_time'] = $y.'年'.$m.'月'.$d.'日';
                        }
                        $arr_fp_r[] = $arr_fp;
                    }
                }
                $this->rest['underwear_record'] = $arr_fp_r;
            }
        }
        return $this->rest;
        /*"record_id": 2,
                "head_img": "http://thirdwx.qlogo.cn/mmopen/GA7e1icawUib7v1dzcpFWhu5zJib7FDbYk9UyhkdZagAyc7GLQBRxhNuOAz7KH1VO0GlgPU81icLWdPiacORqh7uyzy9rzlxd7cqH/132",
                "score": 88,
                "tips": "本次测量公有3处异常,继续努力!",
                "color": "0x81E4C1",
                "create_time": "2018年09月20日"*/
    }
    /*
     * 功能: 内衣档案查询
     * 请求: $user_id=>用户id
     * */
    public function archivesUnderwearSel($user_id)
    {
        $res = ArchivesFunc::underwearUserInfo($user_id);
        $res1 = ArchivesFunc::userInfo($user_id);
        if(!empty($res1)){
            $res['head_img'] = $res1['head_img'];
        }
        $this->rest  = $res;
        return $this->rest;
    }
    /*
     * 功能: 内衣档案修改
     * 请求: $arr
     * */
    public function archivesUnderwearUpd($arr)
    {
        $arr1 = [
            'user_id' => $arr['user_id'],
            'user_name' => $arr['user_name'],
            'sex' => $arr['sex'],
            'head_img' => $arr['head_img'],
            'age' => $arr['age'],
            'height' => $arr['height'],
            'weight' => $arr['weight'],
            'occupation' => $arr['occup_name_id'],
            'card_type' => $arr['card_name_id'],
            'month_income_id' => $arr['range_income_id'],
            'mobile' => $arr['mobile'],
            'email' => $arr['email'],
            'weixin' => $arr['weixin'],
            'qq' => $arr['qq'],
            'is_return_visit' => $arr['is_return_visit'],
            'contact_time' => $arr['contact_time'],
            'address' => $arr['address'],
        ];
        $this->rest = ArchivesFunc::underwearUserInfoUpd($arr1);
        // 同步更新数据到fans表和member表
        $arr2 = [
            'user_id' => $arr['user_id'],
            'realname' => $arr['user_name'],
            'avatar' => $arr['head_img']
        ];
        ArchivesFunc::FansUpd($arr2);
        $this->rest = 1;
        return $this->rest;
    }
    /*
     * 功能: 职业列表
     * 请求:
     * */
    public function occupList()
    {
        $this->rest = ArchivesFunc::occupList();
        return $this->rest;
    }
    /*
     * 功能: 会员卡类型列表
     * 请求:
     * */
    public function cardList()
    {
        $this->rest = ArchivesFunc::cardList();
        return $this->rest;
    }
    /*
     * 功能: 收入范围列表
     * 请求:
     * */
    public function incomeList()
    {
        $this->rest = ArchivesFunc::incomeList();
        return $this->rest;
    }
    /*
     * 功能: 我的量身
     * 请求: $record_id=>记录id
     * */
    public function measure($record_id=0)
    {

        // 身形选择
        $arr = [];$rest=[];$arr2=[];
        $res1 = ArchivesFunc::measureOne(1);
        $res2 = ArchivesFunc::measureTwo(1);
        if(!empty($res1)) {
//            $rest['measure_id'] = $res1[0]['measure_id'];
            $rest['measure_name'] = $res1[0]['name'];
            if(!empty($res2)){
                foreach ($res2 as $v2) {
                    $arr1['measure_property_id'] = $v2['measure_id'];
                    $arr1['pic_select'] = $v2['pic_select'];
                    $arr1['pic'] = $v2['pic'];
                    $arr[] = $arr1;
                }
            }
            $rest['figure_pic'] = $arr;
        }

        $res3 = ArchivesFunc::measureOne();
        if(!empty($res3)){
            $arr3['measure_id'] = 0;
            $arr3['measure_name'] = '';
            $arr3['measure_property_id'] = 0;
            $arr3['measure_property_name'] = '';
            $arr3['field_name'] = '';
            foreach ($res3 as $v3) {
                if($v3['measure_id']>1){
                    $arr4 = [];
                    $arr3['measure_id'] = $v3['measure_id'];
                    $arr3['measure_name'] = $v3['name'];
                    $res5 = ArchivesFunc::measureTwo($v3['measure_id']);
                    if(!empty($res5)){
                        $arr3['measure_property_id'] = $res5[0]['measure_id'];
                        $arr3['measure_property_name'] = $res5[0]['name'];
                        foreach ($res5 as $v5) {
                            $arr41['measure_property_id'] = $v5['measure_id'];
                            $arr41['measure_property_name'] = $v5['name'];
                            $arr4[] = $arr41;
                        }
                    }

                    $arr3['measure_property_list'] = $arr4;
                    $arr3['field_name'] = $v3['field_name'];
                    $arr2[] = $arr3;
                }
            }
        }
        $rest['measure_list'] = $arr2;

        // 量身-编辑
        if($record_id){
            $rest1 = [
                'measure_property_id' => 0,
                'measure_name' => $rest['measure_name'],
                'figure_pic' => $rest['figure_pic'],
                'measure_list' => $rest['measure_list']
            ];
            // 查询单条记录
            $res_d = ArchivesFunc::underwearRecordSel($record_id);
            if(!empty($res_d)){
                $rest1['measure_property_id'] = $res_d['figure_id'];
                foreach($rest1['measure_list'] as &$v_ml){
                    $filed_name = $v_ml['field_name'] ;
                    $v_ml['measure_property_id'] = $res_d[$filed_name];
                    foreach ($v_ml['measure_property_list'] as $v_mpl) {
                        if($v_mpl['measure_property_id'] == $v_ml['measure_property_id']){
                            $v_ml['measure_property_name'] = $v_mpl['measure_property_name'];
                        }
                    }
                }
            }
            $rest = $rest1;
        }
        $this->rest = $rest;
        return $this->rest;
    }
    /*
     * 功能: 我的量身数据添加
     * 请求: $arr
     * */
    public function measureAdd($arr)
    {
        $user = ArchivesFunc::underwearUserInfo($arr['user_id']);
        if(!empty($user)){
            $arr['head_img'] = $user['head_img'];
            $arr['height'] = $user['height'];
        }
        $arr1 = [
            'user_id' => $arr['user_id'],
            'head_img' => $arr['head_img'],
            'figure_id' => $arr['figure_id'],
            'form_state_id' => $arr['form_state_id'],
            'chest_id' => $arr['chest_id'],
            'abdomen_id' => $arr['abdomen_id'],
            'milk_id' => $arr['milk_id'],
            'waist_id' => $arr['waist_id'],
            'pelvis_id' => $arr['pelvis_id'],
            'bb' => $arr['bb'],
            'right_bb' => $arr['right_bb'],
            'left_bb' => $arr['left_bb'],
            'bust' => $arr['bust'],
            'lower_bust' => $arr['lower_bust'],
            'waist' => $arr['waist'],
            'hipline' => $arr['hipline'],
            'thighcir' => $arr['thighcir'],
            'left_hip_height' => $arr['left_hip_height'],
            'right_hip_height' => $arr['right_hip_height'],
            'lower_leg' => $arr['lower_leg'],
            'ankle' => $arr['ankle'],
            'fill_user_id' => $arr['fill_user_id'],
            'hips_id' => $arr['hips_id'],
            'thigh_id' => $arr['thigh_id'],
            'vertebra_id' => $arr['vertebra_id'],
            'fat_id' => $arr['fat_id'],
            'pain_back_id' => $arr['pain_back_id'],
            'create_time' => time()
        ];
        // 有一套逻辑规则计算分数
        $res = ArchivesFunc::underScoreRule($arr);
//        $scores = ArchivesFunc::shapeScore($arr);
        if(!empty($res)){
            $color = '';
            $arr1['score'] = $res['score'];
            $scores = $res['score'];
            if($scores>=90) {
                $color = 'green';
            }elseif($scores>=88 && $scores<90){
                $color = 'yellow';
            }elseif($scores>=86 && $scores<88){
                $color = 'red';
            }else{
                $color = 'red';
            }
            $arr1['color'] = config('data_color.'.$color);
            $arr1['tips'] = config('tips.'.$color);
            $arr1['bb_flag'] = $res['bb_flag'];
            $arr1['right_bb_flag'] = $res['right_bb_flag'];
            $arr1['left_bb_flag'] = $res['left_bb_flag'];
            $arr1['bust_flag'] = $res['bust_flag'];
            $arr1['lower_bust_flag'] = $res['lower_bust_flag'];
            $arr1['waist_flag'] = $res['waist_flag'];
            $arr1['hipline_flag'] = $res['hipline_flag'];
            $arr1['thighcir_flag'] = $res['thighcir_flag'];
            $arr1['left_hip_height_flag'] = $res['left_hip_height_flag'];
            $arr1['right_hip_height_flag'] = $res['right_hip_height_flag'];
            $arr1['lower_leg_flag'] = $res['lower_leg_flag'];
            $arr1['ankle_flag'] = $res['ankle_flag'];
            // 异常数
            $arr1['tips'] = str_replace('x',$res['yc_cnt'],$arr1['tips']);
            $arr1['yc_cnt'] = $res['yc_cnt'];
        }

        // 查询当日数据是否已记录
        // 查询当日数据是否已记录
        $res_jl_flag = ArchivesFunc::underDtSel($arr['user_id'],$arr['fill_user_id']);
        if(!$res_jl_flag){
            $res_add = ArchivesFunc::measureAdd($arr1);
            $this->rest  = $res_add;
        }else{
            $arr1['id'] = $res_jl_flag;
            $this->rest = ArchivesFunc::measureUpd($arr1);
            $this->rest = $res_jl_flag;
        }
        return $this->rest;
    }
    /*
     * 功能: 形体分查询
     * 请求:
     * */
    public function shapeScoreSel($record_id)
    {
        $arr11 = [];$arr22=[];
        $rest = ArchivesFunc::shapeScoreOne($record_id);
        if(!empty($rest)){
            $rest['color_high'] = config('data_color.red');
            $rest['color_normal'] = config('data_color.green');
            $rest['color_low'] = config('data_color.yellow');
            $rest['create_time'] = date('Y',$rest['create_time']).'年'.date('m',$rest['create_time']).'月'.date('d',$rest['create_time']).'日';
            $rest['head_img'] = $rest['head_img']==null?'':$rest['head_img'];
            $rest['user_name'] = $rest['user_name']==null?'':$rest['user_name'];
            $rest['beautician'] = '';
            $rest['bb'] = round($rest['bb'],1).'cm';
//            $rest['bb_flag'] = 3;//3=>偏高,2=>正常,1=>偏低
            $rest['right_bb'] = round($rest['right_bb'],1).'cm';
            $rest['left_bb'] = round($rest['left_bb'],1).'cm';
            $rest['bust'] = round($rest['bust'],1).'cm';
            $rest['lower_bust'] = round($rest['lower_bust'],1).'cm';
            $rest['waist'] = round($rest['waist'],1).'cm';
            $rest['hipline'] = round($rest['hipline'],1).'cm';

            $rest['thighcir'] = round($rest['thighcir'],1).'cm';
            $rest['left_hip_height'] = round($rest['left_hip_height'],1).'cm';
            $rest['right_hip_height'] = round($rest['right_hip_height'],1).'cm';
            $rest['lower_leg'] = round($rest['lower_leg'],1).'cm';

            $rest['ankle'] = round($rest['ankle'],1).'cm';
            $rest['yc_color'] = $rest['color_high'];

            if($rest['fill_user_id']){
                $rest1 = ArchivesFunc::pidMrs($rest['fill_user_id']);
                if(!empty($rest1)){
                    $rest['beautician'] = $rest1['user_name'];
                }
            }
            unset($rest['pid']);
            // 用户信息
            $arr11['head_img'] = $rest['head_img'];
            $arr11['user_name'] = $rest['user_name'];
            $arr11['score'] = $rest['score'];
            $arr11['create_time'] = $rest['create_time'];
            $arr11['tips'] = $rest['tips'];
            $arr11['yc_cnt'] = $rest['yc_cnt'];
            $arr11['yc_color'] = $rest['yc_color'];
            $arr11['beautician'] = $rest['beautician'];
            // 数据列表
            $res2 = ArchivesFunc::zwProperty();
            // 黄金身材尺寸
            $res_cc = PersonalDataMod::underwearRule($rest['height']);
            if(!empty($res_cc)){
                foreach ($res_cc as &$v_cc) {
                    $v_cc = $v_cc.'cm';
                }
            }
            if(!empty($res2)){
                // [中文名称,正常标记,颜色,值]
                $color = [
                    '3' => config('data_color.red'),
                    '2' => config('data_color.green'),
                    '1' => config('data_color.yellow'),
                ];
                foreach ($res2 as $v2) {
                    $field_name = $v2['field_name'];
                    $arr2['pro_name'] = $v2['pro_name'];
                    $arr2['flag'] = $rest[$field_name.'_flag'];
                    $arr2['color'] = $color[$arr2['flag']];
                    $arr2['pro_val'] = $rest[$field_name];

                    $arr2['pro_flag_tips'] = '标准';
                    if($field_name == 'bb'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏瘦';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '外扩';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['bb'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'right_bb'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏瘦';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '下垂';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['right_bb'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'left_bb'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏瘦';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '下垂';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['left_bb'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
//                    if($field_name == 'bust'){
//                        if($arr2['flag']==1){
//                            $arr2['pro_flag_tips'] = '偏小';
//                        }elseif($arr2['flag']==2){
//                            $arr2['pro_flag_tips'] = '标准';
//                        }elseif($arr2['flag']==3){
//                            $arr2['pro_flag_tips'] = '偏大';
//                        }
//                    }
                    if($field_name == 'bust'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏小';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏大';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['bust'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'lower_bust'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏小';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏大';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['lower_bust'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'waist'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏细';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏粗';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['waist'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'hipline'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏小';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏大';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['hipline'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'thighcir'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏细';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏粗';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['thighcir'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'lower_leg'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏细';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏粗';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['lower_leg'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    if($field_name == 'ankle'){
                        if($arr2['flag']==1){
                            $arr2['pro_flag_tips'] = '偏细';
                        }elseif($arr2['flag']==2){
                            $arr2['pro_flag_tips'] = '标准';
                        }elseif($arr2['flag']==3){
                            $arr2['pro_flag_tips'] = '偏粗';
                        }
                        $arr2['flag_popup'] = 1;// 弹窗标记
                        $arr2['data_popup'] = [];// 弹窗数据
                        if(!empty($res_cc)){
                            $arr_cc2['title'] = $arr2['pro_name'];
                            $arr_cc2['tips1'] = '根据您的身高体重';
                            $arr_cc2['tips2'] = '黄金比例'.$arr2['pro_name'].'为'.$res_cc['ankle'];
                            $arr_cc2['tips3'] = '当前'.$arr2['pro_name'].'数据为'.$arr2['pro_val'];
                            $arr2['data_popup'] = $arr_cc2;
                        }
                    }
                    $arr22[] = $arr2;
                }
            }
        }
        $rest11['user_info'] = $arr11;
        $rest11['data_list'] = $arr22;
        $article = ArchivesFunc::articleRecommend();
        // 文章推荐
        $rest11['recommend'] = [
            'title' => '形体塑身小课堂',
            'article_list' => $article
        ];
        $this->rest = $rest11;
        return $this->rest;
    }
    /*
     * 功能: 我的量身-各种属性列表
     * 请求: $proid => 属性id
     * */
    public function propertyList($proid)
    {
        $this->rest = ArchivesFunc::propertyList($proid);
        return $this->rest;
    }
    /*
     * 功能: 我的档案-数据对比
     * 请求: $arr=>[$record_id1 => 记录id1,$record_id2 => 记录id2]
     * */
    public function contrastData($arr)
    {
        $res1 = ArchivesFunc::shapeScoreOne($arr['record_id1']);
        $res2 = ArchivesFunc::shapeScoreOne($arr['record_id2']);
        $rest = [];$arr_res=[];$rest1=[];
        if(!empty($res1)){
            $arr1 = [];
            // 头像,用户名,提示语
            $rest['head_img'] = $res1['head_img'];
            $rest['user_name'] = $res1['user_name'];
            if(!empty($res2)){
                $score = $res1['score'] - $res2['score'];
                if($score > 0){
                    // 进步
                    $flag ='rise';
                }else{
                    // 下降
                    $score = abs($score);
                    $flag ='fall';
                }
                $rest['tips'] = config('compare_tips.'.$flag);
                $rest['tips'] = str_replace('x',$score,$rest['tips']);

                // 比对异常
                // r.bb,r.right_bb,r.left_bb,r.bust,r.lower_bust,r.waist,r.hipline,r.thighcir,r.left_hip_height,r.right_hip_height
                $arr1['yc_cnt'] = 0;
                $rest['color_hign'] = config('data_color.red');
                $rest['color_low'] = config('data_color.yellow');
                $rest['color_yc'] = config('data_color.red');
            }
            // [名称,日期,分数,异常项,异常项颜色,BB,BB标记,BB颜色,右BB,右BB标记,右BB颜色,左BB,左BB标记,左BB颜色]
            // 上月数据
            $dt1 = date('Y',$res1['create_time']).'年'.date('m',$res1['create_time']).'月'.date('d',$res1['create_time']).'日';
            $res3 = [
                'dt1' => date('Y-m-d',$res1['create_time']),
                'title' => '形体评分',
                'score' => $res1['score'],
                'dt11' => $dt1,
                'yc_cnt' => 0,
                'bb' => $res1['bb'],
                'bb_flag' => 2, // 需要增加判断是否是正常的逻辑 , 默认正常
                'right_bb' => $res1['right_bb'],
                'right_bb_flag' => 2,
                'left_bb' => $res1['left_bb'],
                'left_bb_flag' => 2,
                'bust' => $res1['bust'],
                'bust_flag' => 2,
                'lower_bust' => $res1['lower_bust'],
                'lower_bust_flag' => 2,
                'waist' => $res1['waist'],
                'waist_flag' => 2,
                'hipline' => $res1['hipline'],
                'hipline_flag' => 2,
                'thighcir' => $res1['thighcir'],
                'thighcir_flag' => 2,
                'left_hip_height' => $res1['left_hip_height'],
                'left_hip_height_flag' => 2,
                'right_hip_height' => $res1['right_hip_height'],
                'right_hip_height_flag' => 2,
                'lower_leg' => $res1['lower_leg'],
                'lower_leg_flag' => 2,
                'ankle' => $res1['ankle'],
                'ankle_flag' => 2,
            ];
            $dt2 = date('Y',$res2['create_time']).'年'.date('m',$res2['create_time']).'月'.date('d',$res2['create_time']).'日';
            $res4 = [
                'dt1' => date('Y-m-d',$res2['create_time']),
                'title' => '形体评分',
                'score' => $res2['score'],
                'dt11' => $dt2,
                'yc_cnt' => 0,
                'bb' => $res2['bb'],
                'bb_flag' => 2, // 需要增加判断是否是正常的逻辑 , 默认正常
                'right_bb' => $res2['right_bb'],
                'right_bb_flag' => 2,
                'left_bb' => $res2['left_bb'],
                'left_bb_flag' => 2,
                'bust' => $res2['bust'],
                'bust_flag' => 2,
                'lower_bust' => $res2['lower_bust'],
                'lower_bust_flag' => 2,
                'waist' => $res2['waist'],
                'waist_flag' => 2,
                'hipline' => $res2['hipline'],
                'hipline_flag' => 2,
                'thighcir' => $res2['thighcir'],
                'thighcir_flag' => 2,
                'left_hip_height' => $res2['left_hip_height'],
                'left_hip_height_flag' => 2,
                'right_hip_height' => $res2['right_hip_height'],
                'right_hip_height_flag' => 2,
                'lower_leg' => $res2['lower_leg'],
                'lower_leg_flag' => 2,
                'ankle' => $res2['ankle'],
                'ankle_flag' => 2,
            ];
            $rest['record_list'] = [$res3,$res4];

            // 增加判断是否不一样
            if($res1['bb'] != $res2['bb']){
                if($res1['bb'] > $res2['bb']){
                    $res3['bb_flag'] = 3;
                }else{
                    $res3['bb_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['right_bb'] != $res2['right_bb']){
                if($res1['right_bb'] > $res2['right_bb']){
                    $res3['right_bb_flag'] = 3;
                }else{
                    $res3['right_bb_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['left_bb'] != $res2['left_bb']){
                if($res1['left_bb'] > $res2['left_bb']){
                    $res3['left_bb_flag'] = 3;
                }else{
                    $res3['left_bb_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['bust'] != $res2['bust']){
                if($res1['bust'] > $res2['bust']){
                    $res3['bust_flag'] = 3;
                }else{
                    $res3['bust_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['lower_bust'] != $res2['lower_bust']){
                if($res1['lower_bust'] > $res2['lower_bust']){
                    $res3['lower_bust_flag'] = 3;
                }else{
                    $res3['lower_bust_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['waist'] != $res2['waist']){
                if($res1['waist'] > $res2['waist']){
                    $res3['waist_flag'] = 3;
                }else{
                    $res3['waist_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['hipline'] != $res2['hipline']){
                if($res1['hipline'] > $res2['hipline']){
                    $res3['hipline_flag'] = 3;
                }else{
                    $res3['hipline_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['thighcir'] != $res2['thighcir']){
                if($res1['thighcir'] > $res2['thighcir']){
                    $res3['thighcir_flag'] = 3;
                }else{
                    $res3['thighcir_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['lower_leg'] != $res2['lower_leg']){
                if($res1['lower_leg'] > $res2['lower_leg']){
                    $res3['lower_leg_flag'] = 3;
                }else{
                    $res3['lower_leg_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            if($res1['ankle'] != $res2['ankle']){
                if($res1['ankle'] > $res2['ankle']){
                    $res3['ankle_flag'] = 3;
                }else{
                    $res3['ankle_flag'] = 1;
                }
                $arr1['yc_cnt']++;
            }
            $res3['yc_cnt'] = $arr1['yc_cnt'];
            // 用户信息
            $arr_user['user_name'] = $rest['user_name'];
            $arr_user['head_img'] = $rest['head_img'];
            $arr_user['title'] = '形体评分';
            $arr_user['tips'] = $rest['tips'];
            $arr_user['dt1'] = $res4['dt1'];
            $arr_user['dt11'] = $res4['dt11'];
            $arr_user['dt2'] = $res3['dt1'];
            $arr_user['dt22'] = $res3['dt11'];
            $arr_user['score1'] = $res4['score'];
            $arr_user['score2'] = $res3['score'];
            $arr_user['yc1'] = 0;
            $arr_user['yc2'] = $res3['yc_cnt'];
            $arr_user['color_yc'] = config('data_color.red');
            $rest1['user_info'] = $arr_user;
            // 对比数据信息
            $res_zw = ArchivesFunc::zwProperty();
            if(!empty($res_zw)){
                // [中文名称,正常标记,颜色,值1,值2]
                foreach ($res_zw as $v_zw) {
                    $color = [
                        '3' => config('data_color.red'),
                        '2' => config('data_color.green'),
                        '1' => config('data_color.yellow'),
                    ];
                    $field_name = $v_zw['field_name'];
                    $arr2['pro_name'] = $v_zw['pro_name'];
                    $arr2['flag'] = $res3[$field_name.'_flag'];
                    $arr2['color'] = $color[$arr2['flag']];
                    $arr2['pro_val1'] = round($res4[$field_name],1);
                    $arr2['pro_val2'] = round($res3[$field_name],1);
                    $arr2['pro_val1'] = $res4[$field_name].'cm';
                    $arr2['pro_val2'] = $res3[$field_name].'cm';
                    $arr_res[] = $arr2;
                }
            }
            $rest1['data_list'] = $arr_res;
        }
        $this->rest = $rest1;
        return $this->rest;
    }

    /*
     * 功能: 尺码编辑-查询
     * 请求: $proid => 属性id,$record_id=>记录id
     * */
    public function measureList($record_id=0)
    {
        $rest = ArchivesFunc::measureList();
        if(!empty($rest)){
            $rest1 = [];
            foreach ($rest as $v) {
                if($v['pro_id']==1){
                    $this->rest['mote_pic'] = $v['pro_pic'];
                    continue;
                }
                $arr1['pro_name'] = $v['pro_name'];
                $arr1['field_name'] = $v['field_name'];
//                $arr1['pro_name_suffix'] = $v['pro_name_suffix'];
                $rest1[] = $arr1;
            }
            // 编辑
            if($record_id){
                // 查询单条记录
                $rest2 = $rest1;
                $res_d = ArchivesFunc::underwearRecordSel($record_id);
                if(!empty($res_d)){
                    foreach ($rest2 as &$v2) {
                        $field_name = $v2['field_name'];
                        $v2['pro_val'] = $res_d[$field_name];
                    }
                }
                $rest1 = $rest2;
            }

            $this->rest['pro_list'] = $rest1;
        }
        return $this->rest;
    }
    /*
     * 功能: 5.ta的内衣档案-编辑-修改
     * 请求: $arr=>[$record_id => 记录id,user_id=>用户id]
     * */
    public function customerUnderfilesUpd($arr)
    {
        $rest = [];
        $arr1 = [
            'id' => $arr['record_id'],
            'user_id' => $arr['user_id'],
            'figure_id' => $arr['figure_id'],
            'form_state_id' => $arr['form_state_id'],
            'chest_id' => $arr['chest_id'],
            'abdomen_id' => $arr['abdomen_id'],
            'milk_id' => $arr['milk_id'],
            'waist_id' => $arr['waist_id'],
            'pelvis_id' => $arr['pelvis_id'],
            'bb' => $arr['bb'],
            'right_bb' => $arr['right_bb'],
            'left_bb' => $arr['left_bb'],
            'bust' => $arr['bust'],
            'lower_bust' => $arr['lower_bust'],
            'waist' => $arr['waist'],
            'hipline' => $arr['hipline'],
            'thighcir' => $arr['thighcir'],
            'left_hip_height' => $arr['left_hip_height'],
            'right_hip_height' => $arr['right_hip_height'],
            'lower_leg' => $arr['lower_leg'],
            'ankle' => $arr['ankle']
        ];
        // 修改
        $res = ArchivesFunc::underwearRecordUpd($arr1);
        return $res;
    }
    /*
     * 功能: 5.ta的内衣档案-编辑-删除
     * 请求: $record_id => 记录id
     * */
    public function customerUnderfilesDel($record_id)
    {
        $res = ArchivesFunc::underwearRecordDel($record_id);
        return $res;
    }
    /*
     * 功能: 5.ta的内衣档案-编辑-查询
     * 请求: $record_id => 记录id
     * */
    public function customerUnderfilesSel($record_id)
    {

    }
    /*
     * 功能: 7.我的二维码
     * 请求: user_id=>用户id
     * */
    public function customerCode($user_id,$store_id='')
    {
        $rest = [];
        $res_u = ArchivesFunc::userInfo($user_id);
        if(!empty($res_u)){
            $res1['user_id'] = $res_u['user_id'];
            $res1['head_img'] = $res_u['head_img'];
            $res1['user_name'] = $res_u['user_name'];
            $res1['store_name'] = $res_u['store_name'];
            $res1['tips'] = config('sub_tips.code_tips');
            $res1['qrcode_img'] = $res_u['qrcode_img'];
            if(empty($res_u['qrcode_img'])){
                $commod = new Common();
                $data_json = json_encode(['user_id'=>$user_id,'type'=>'measure','store_id'=>$store_id]);// 测量数据扫码
                $qr_code = $commod->makeQrCode($data_json,'qrcode'.$user_id.'.png');
                $res1['qrcode_img'] = $qr_code;// 二维码
                // 更新二维码图片地址
                $arr1['qrcode_img'] = $qr_code;
                $arr1['user_id'] = $user_id;
                ArchivesFunc::underwearUserInfoUpd($arr1);
            }
            $rest = $res1;
        }
        // 用户id,用户头像,用户名字,门店地点,二维码,提示语
        return $rest;
    }
    /*
     * 功能:查询形体分记录
     * 请求:int $record_id=>记录id
     * */
    public function getRecord($record_id)
    {
        $rest = ArchivesFunc::shapeScoreOne($record_id);
        return $rest;
    }
    /*
     * 功能:推荐尺码规则设计
     * 请求:@paras int $record_id=>记录id
     * @paras int $type=>类型 0=>日间内衣,1=>夜间内衣
     * */
    public function recommendSizeRule($record_id,$type=0)
    {
        $res = [];
        $resp = [
            'title_list' => [
                '日间内衣配码表',
                '夜间内衣配码表'
            ],
            'size_list' => []
        ];
        $rest = $this->getRecord($record_id);
        if($rest){
            // 功能型调整日间文胸 规则
            $res = [
                'rwx_size' => '75B',
                'rdxlty_size' => '0',
                'rbxsy_size' => '0',
                'ryj_size' => '0',
                'rcsk_size' => '0',
                'rdsk_size' => '0',
                'rnk_size' => '0',
                'rtzd_size' => '0',
                'ywx_size' => 'BCD/M',
                'ynk_size' => '0',
                'yctw_size' => '0',
                'ytzd_size' => '0',
            ];
            if($type == 1){
                // 功能型夜间调整文胸
                if($rest['lower_bust']>=67 && $rest['lower_bust']<=73 ){
                    if($rest['bust']>=80 && $rest['bust']<=91){
                        $res['ywx_size'] = 'BCD/M';
                    }elseif($rest['bust']>=91 && $rest['bust']<=96){
                        $res['ywx_size'] = 'EF/M';
                    }
                }elseif($rest['lower_bust']>=73 && $rest['lower_bust']<=79 ){
                    if($rest['bust']>=86 && $rest['bust']<=97){
                        $res['ywx_size'] = 'BCD/L';
                    }elseif($rest['bust']>=97 && $rest['bust']<=102){
                        $res['ywx_size'] = 'EF/L';
                    }
                }elseif($rest['lower_bust']>=79 && $rest['lower_bust']<=85 ){
                    if($rest['bust']>=92 && $rest['bust']<=103){
                        $res['ywx_size'] = 'BCD/LL';
                    }elseif($rest['bust']>=103 && $rest['bust']<=108){
                        $res['ywx_size'] = 'EF/LL';
                    }
                }
                // 功能型夜间调整内裤
                if($rest['waist']>=64 && $rest['waist']<=70 ){
                    if($rest['hipline']>=87 && $rest['hipline']<=95){
                        $res['ynk_size'] = 'M';
                    }

                }elseif($rest['waist']>=69 && $rest['waist']<=77){
                    if($rest['hipline']>=92 && $rest['hipline']<=100){
                        $res['ynk_size'] = 'L';
                    }

                }elseif($rest['waist']>=77 && $rest['waist']<=85){
                    if($rest['hipline']>=97 && $rest['hipline']<=105){
                        $res['ynk_size'] = 'LL';
                    }

                }
                // 功能型夜间调整长筒袜
                if($rest['thighcir']>=38 && $rest['thighcir']<=48 ){
                    if($rest['lower_leg']>=27 && $rest['lower_leg']<=33){
                        if($rest['ankle']>=18 && $rest['ankle']<=22){
                            $res['yctw_size'] = 'S';
                        }
                    }
                }elseif($rest['thighcir']>=43 && $rest['thighcir']<=58){
                    if($rest['lower_leg']>=20 && $rest['lower_leg']<=24){
                        $res['ynk_size'] = 'M';
                    }
                }elseif($rest['thighcir']>=51 && $rest['thighcir']<=64){
                    if($rest['lower_leg']>=23 && $rest['lower_leg']<=27){
                        $res['ynk_size'] = 'L';
                    }
                }
                // 功能型夜间骨盆调整带
                if($rest['waist']>=58 && $rest['waist']<=64 ){
                    if($rest['hipline']>=82 && $rest['hipline']<=90){
                        $res['ytzd_size'] = 'S';
                    }
                }elseif($rest['waist']>=64 && $rest['waist']<=70){
                    if($rest['hipline']>=87 && $rest['hipline']<=95){
                        $res['ytzd_size'] = 'M';
                    }
                }elseif($rest['waist']>=69 && $rest['waist']<=77){
                    if($rest['hipline']>=92 && $rest['hipline']<=100){
                        $res['ytzd_size'] = 'L';
                    }
                }elseif($rest['waist']>=77 && $rest['waist']<=85){
                    if($rest['hipline']>=97 && $rest['hipline']<=105){
                        $res['ytzd_size'] = 'LL';
                    }
                }elseif($rest['waist']>=85 && $rest['waist']<=93){
                    if($rest['hipline']>=102 && $rest['hipline']<=110){
                        $res['ytzd_size'] = '3L';
                    }
                }
                // 如果都没匹配上,以其中一个指标为准
                if($res['ynk_size'] == 0){
                    if($rest['hipline']>=87 && $rest['lower_bust']<=95){
                        $res['ynk_size'] = 'M';
                    }elseif($rest['hipline']>=92 && $rest['lower_bust']<=100){
                        $res['ynk_size'] = 'L';
                    }elseif($rest['hipline']>=97 && $rest['lower_bust']<=105){
                        $res['ynk_size'] = 'LL';
                    }
                }
                if($res['yctw_size'] == 0){
                    if($rest['thighcir']>=38 && $rest['thighcir']<=48){
                        $res['yctw_size'] = 'S';
                    }elseif($rest['thighcir']>=43 && $rest['thighcir']<=58){
                        $res['yctw_size'] = 'M';
                    }elseif($rest['thighcir']>=51 && $rest['thighcir']<=64){
                        $res['yctw_size'] = 'L';
                    }
                }
                if($res['ytzd_size'] == 0){
                    if($rest['hipline']>=82 && $rest['hipline']<=90){
                        $res['ytzd_size'] = 'S';
                    }elseif($rest['hipline']>=87 && $rest['hipline']<=95){
                        $res['ytzd_size'] = 'M';
                    }elseif($rest['hipline']>=92 && $rest['hipline']<=100){
                        $res['ytzd_size'] = 'L';
                    }elseif($rest['hipline']>=97 && $rest['hipline']<=105){
                        $res['ytzd_size'] = 'LL';
                    }elseif($rest['hipline']>=102 && $rest['hipline']<=110){
                        $res['ytzd_size'] = '3L';
                    }
                }
                // 夜间尺码规则
                $resp['size_list'] = [
                    [
                        'goods_name' => '功能型夜间调整文胸',
                        'goods_size' => $res['ywx_size'],
                    ],
                    [
                        'goods_name' => '功能型夜间调整内裤',
                        'goods_size' => $res['ynk_size'],
                    ],
                    [
                        'goods_name' => '功能型夜间调整长筒袜',
                        'goods_size' => $res['yctw_size'],
                    ],
                    [
                        'goods_name' => '骨盆调整带',
                        'goods_size' => $res['ytzd_size'],
                    ]
                ];
            }else{
                // ywx_size,yny_size,yctw_size,ytzd_size
                // 骨盆调整带尺码
                if($rest['waist'] >= 58 && $rest['waist'] <= 64){
                    if($rest['hipline'] >= 82 && $rest['hipline'] <= 90){
                        $res['rtzd_size'] = 'S';
                    }
                }elseif($rest['waist'] >= 64 && $rest['waist'] <= 70){
                    if($rest['hipline'] >= 87 && $rest['hipline'] <= 95){
                        $res['rtzd_size'] = 'M';
                    }

                }elseif($rest['waist'] >= 69 && $rest['waist'] <= 77){
                    if($rest['hipline'] >= 92 && $rest['hipline'] <= 100){
                        $res['rtzd_size'] = 'L';
                    }

                }elseif($rest['waist'] >= 77 && $rest['waist'] <= 85){
                    if($rest['hipline'] >= 97 && $rest['hipline'] <= 105){
                        $res['rtzd_size'] = 'LL';
                    }

                }elseif($rest['waist'] >= 85 && $rest['waist'] <= 93){
                    if($rest['hipline'] >= 102 && $rest['hipline'] <= 110){
                        $res['rtzd_size'] = '3L';
                    }

                }

                // 功能型日间调整内裤 功能型日间调整长塑裤・功能型日间调整短塑裤 功能型日间调整背心上衣・功能型日间调整腰夹 功能型日间调整短袖连体衣
                if($rest['lower_bust'] >= 61 && $rest['lower_bust'] <= 67){
                    if($rest['waist'] >= 55 && $rest['waist'] <= 61){
                        if($rest['hipline'] >= 79 && $rest['hipline'] <= 89){
                            $res['rdxlty_size'] = 'S';
                            $res['rdbxsy_size'] = 'S';
                            $res['ryj_size'] = 'S';
                            $res['rcsk_size'] = 'S';
                            $res['rdsk_size'] = 'S';
                            $res['rnk_size'] = 'S';
                        }
                    }
                }elseif($rest['lower_bust'] >= 67 && $rest['lower_bust'] <= 73){
                    if($rest['waist'] >= 61 && $rest['waist'] <= 67){
                        if($rest['hipline'] >= 80 && $rest['hipline'] <= 93){
                            $res['rdxlty_size'] = 'M';
                            $res['rdbxsy_size'] = 'M';
                            $res['ryj_size'] = 'M';
                            $res['rcsk_size'] = 'M';
                            $res['rdsk_size'] = 'M';
                            $res['rnk_size'] = 'M';
                        }
                    }

                }elseif($rest['lower_bust'] >= 73 && $rest['lower_bust'] <= 79){
                    if($rest['waist'] >= 67 && $rest['waist'] <= 73){
                        if($rest['hipline'] >= 86 && $rest['hipline'] <= 96){
                            $res['rdxlty_size'] = 'L';
                            $res['rdbxsy_size'] = 'L';
                            $res['ryj_size'] = 'L';
                            $res['rcsk_size'] = 'L';
                            $res['rdsk_size'] = 'L';
                            $res['rnk_size'] = 'L';
                        }
                    }

                }elseif($rest['lower_bust'] >= 79 && $rest['lower_bust'] <= 85){
                    if($rest['waist'] >= 73 && $rest['waist'] <= 79){
                        if($rest['hipline'] >= 89 && $rest['hipline'] <= 89){
                            $res['rdxlty_size'] = 'LL';
                            $res['rdbxsy_size'] = 'LL';
                            $res['ryj_size'] = 'LL';
                            $res['rcsk_size'] = 'LL';
                            $res['rdsk_size'] = 'LL';
                            $res['rnk_size'] = 'LL';
                        }
                    }

                }elseif($rest['lower_bust'] >= 85 && $rest['lower_bust'] <= 91){
                    if($rest['waist'] >= 78 && $rest['waist'] <= 86){
                        if($rest['hipline'] >= 91 && $rest['hipline'] <= 103){
                            $res['rdxlty_size'] = 'EL';
                            $res['rdbxsy_size'] = 'EL';
                            $res['ryj_size'] = 'EL';
                            $res['rcsk_size'] = 'EL';
                            $res['rdsk_size'] = 'EL';
                            $res['rnk_size'] = 'EL';
                        }
                    }

                }elseif($rest['lower_bust'] >= 91 && $rest['lower_bust'] <= 97){
                    if($rest['waist'] >= 86 && $rest['waist'] <= 94){
                        if($rest['hipline'] >= 94 && $rest['hipline'] <= 106){
                            $res['rdxlty_size'] = 'Q';
                            $res['rdbxsy_size'] = 'Q';
                            $res['ryj_size'] = 'Q';
                            $res['rcsk_size'] = 'Q';
                            $res['rdsk_size'] = 'Q';
                            $res['rnk_size'] = 'Q';
                        }
                    }

                }elseif($rest['lower_bust'] >= 97 && $rest['lower_bust'] <= 103){
                    if($rest['waist'] >= 94 && $rest['waist'] <= 102){
                        if($rest['hipline'] >= 97 && $rest['hipline'] <= 109){
                            $res['rdxlty_size'] = 'EQ';
                            $res['rdbxsy_size'] = 'EQ';
                            $res['ryj_size'] = 'EQ';
                            $res['rcsk_size'] = 'EQ';
                            $res['rdsk_size'] = 'EQ';
                            $res['rnk_size'] = 'EQ';
                        }
                    }

                }elseif($rest['lower_bust'] >= 103 && $rest['lower_bust'] <= 109){
                    if($rest['waist'] >= 102 && $rest['waist'] <= 110){
                        if($rest['hipline'] >= 100 && $rest['hipline'] <= 112){
                            $res['rdxlty_size'] = 'EQ2';
                            $res['rdbxsy_size'] = 'EQ2';
                            $res['ryj_size'] = 'EQ2';
                            $res['rcsk_size'] = 'EQ2';
                            $res['rdsk_size'] = 'EQ2';
                            $res['rnk_size'] = 'EQ2';
                        }
                    }

                }
                // 61～67    67～73   73～79   79～85   85～91   91～97   97～103  103～109
                // 55～61    61～67   67～73   73～79   78～86   86～94   94～102  102～110
                // 79～89    80～93   86～96   89～99   91～103  94～106  97～109  100～112
                // Ｓ    Ｍ   Ｌ   ＬＬ  ＥＬ  Ｑ   ＥＱ  EQ2
                // 功能型调整日间文胸
                $bust_cha = $rest['bust'] - $rest['lower_bust'];
                if($bust_cha<=7.5){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'AA';
                    if($rest['lower_bust']>=71 && $rest['lower_bust']<=75){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=76 && $rest['lower_bust']<=80){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=81 && $rest['lower_bust']<=85){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=86 && $rest['lower_bust']<=90){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=91 && $rest['lower_bust']<=95){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=96 && $rest['lower_bust']<=100){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=101 && $rest['lower_bust']<=105){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=106 && $rest['lower_bust']<=110){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;

                }elseif($bust_cha>=7.5 && $bust_cha<=10){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'A';
                    if($rest['lower_bust']>=73 && $rest['lower_bust']<=77){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=78 && $rest['lower_bust']<=82){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=83 && $rest['lower_bust']<=87){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=88 && $rest['lower_bust']<=92){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=93 && $rest['lower_bust']<=97){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=98 && $rest['lower_bust']<=102){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=103 && $rest['lower_bust']<=107){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=108 && $rest['lower_bust']<=112){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    // 73～77    78～82   83～87   88～92   93～97   98～102  103～107 108～112
                }elseif($bust_cha>=10 && $bust_cha<=12.5){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'B';
                    if($rest['lower_bust']>=76 && $rest['lower_bust']<=80){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=81 && $rest['lower_bust']<=85){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=86 && $rest['lower_bust']<=90){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=91 && $rest['lower_bust']<=95){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=96 && $rest['lower_bust']<=100){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=101 && $rest['lower_bust']<=105){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=106 && $rest['lower_bust']<=110){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=111 && $rest['lower_bust']<=115){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //76～80 81～85   86～90   91～95   96～100  101～105 106～110 111～115
                }elseif($bust_cha>=12.5 && $bust_cha<=15){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'C';
                    if($rest['lower_bust']>=78 && $rest['lower_bust']<=82){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=83 && $rest['lower_bust']<=87){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=88 && $rest['lower_bust']<=92){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=93 && $rest['lower_bust']<=97){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=98 && $rest['lower_bust']<=102){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=103 && $rest['lower_bust']<=107){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=108 && $rest['lower_bust']<=112){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=113 && $rest['lower_bust']<=117){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //78～82 83～87   88～92   93～97   98～102  103～107 108～112 113～117

                }elseif($bust_cha>=15 && $bust_cha<=17.5){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'D';
                    if($rest['lower_bust']>=81 && $rest['lower_bust']<=85){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=86 && $rest['lower_bust']<=90){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=91 && $rest['lower_bust']<=95){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=96 && $rest['lower_bust']<=100){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=101 && $rest['lower_bust']<=105){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=106 && $rest['lower_bust']<=110){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=111 && $rest['lower_bust']<=115){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=115 && $rest['lower_bust']<=119){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //81～85 86～90   91～95   96～100  101～105 106～110 111～115 115～119

                }elseif($bust_cha>=17.5 && $bust_cha<=20){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'E';
                    if($rest['lower_bust']>=83 && $rest['lower_bust']<=87){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=88 && $rest['lower_bust']<=92){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=93 && $rest['lower_bust']<=97){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=98 && $rest['lower_bust']<=102){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=103 && $rest['lower_bust']<=107){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=108 && $rest['lower_bust']<=112){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=113 && $rest['lower_bust']<=117){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=118 && $rest['lower_bust']<=122){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //83～87 88～92   93～97   98～102  103～107 108～112 113～117 118～122
                }elseif($bust_cha>=20 && $bust_cha<=22.5){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'F';
                    if($rest['lower_bust']>=86 && $rest['lower_bust']<=90){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=91 && $rest['lower_bust']<=95){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=96 && $rest['lower_bust']<=100){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=101 && $rest['lower_bust']<=105){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=106 && $rest['lower_bust']<=110){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=111 && $rest['lower_bust']<=115){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=116 && $rest['lower_bust']<=120){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=121 && $rest['lower_bust']<=125){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //86～90 91～95   96～100  101～105 106～110 111～115 116～120 121～125
                }elseif($bust_cha>=22.5 && $bust_cha<=25){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'G';
                    if($rest['lower_bust']>=88 && $rest['lower_bust']<=92){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=93 && $rest['lower_bust']<=97){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=98 && $rest['lower_bust']<=102){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=103 && $rest['lower_bust']<=107){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=108 && $rest['lower_bust']<=112){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=113 && $rest['lower_bust']<=117){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=118 && $rest['lower_bust']<=122){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=123 && $rest['lower_bust']<=127){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //88～92 93～97   98～102  103～107 108～112 113～117 118～122 123～127
                }elseif($bust_cha>=25 && $bust_cha<=27.5){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'H';
                    if($rest['lower_bust']>=91 && $rest['lower_bust']<=95){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=96 && $rest['lower_bust']<=100){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=101 && $rest['lower_bust']<=105){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=106 && $rest['lower_bust']<=110){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=111 && $rest['lower_bust']<=115){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=116 && $rest['lower_bust']<=120){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=121 && $rest['lower_bust']<=125){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=126 && $rest['lower_bust']<=130){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //91～95 96～100  101～105 106～110 111～115 116～120 121～125 126～130

                }elseif($bust_cha>=27.5 && $bust_cha<=30){
                    $rwx_size1 = '65';
                    $rwx_size2 = 'I';
                    if($rest['lower_bust']>=93 && $rest['lower_bust']<=97){
                        $rwx_size1 = '65';
                    }elseif($rest['lower_bust']>=98 && $rest['lower_bust']<=102){
                        $rwx_size1 = '70';
                    }elseif($rest['lower_bust']>=103 && $rest['lower_bust']<=107){
                        $rwx_size1 = '75';
                    }elseif($rest['lower_bust']>=108 && $rest['lower_bust']<=112){
                        $rwx_size1 = '80';
                    }elseif($rest['lower_bust']>=113 && $rest['lower_bust']<=117){
                        $rwx_size1 = '85';
                    }elseif($rest['lower_bust']>=118 && $rest['lower_bust']<=122){
                        $rwx_size1 = '90';
                    }elseif($rest['lower_bust']>=123 && $rest['lower_bust']<=127){
                        $rwx_size1 = '95';
                    }elseif($rest['lower_bust']>=128 && $rest['lower_bust']<=132){
                        $rwx_size1 = '100';
                    }
                    $res['rwx_size'] = $rwx_size1.$rwx_size2;
                    //93～97 98～102  103～107 108～112 113～117 118～122 123～127 128～132

                }
                // 都没匹配上,按其中一个指标为标准
                if($res['rdxlty_size'] == 0){
                    if($rest['lower_bust']>=61 && $rest['lower_bust']<=67){
                        $res['rdxlty_size'] = 'S';
                    }elseif($rest['lower_bust']>=67 && $rest['lower_bust']<=73){
                        $res['rdxlty_size'] = 'M';
                    }elseif($rest['lower_bust']>=73 && $rest['lower_bust']<=79){
                        $res['rdxlty_size'] = 'L';
                    }elseif($rest['lower_bust']>=79 && $rest['lower_bust']<=85){
                        $res['rdxlty_size'] = 'LL';
                    }elseif($rest['lower_bust']>=85 && $rest['lower_bust']<=91){
                        $res['rdxlty_size'] = 'EL';
                    }elseif($rest['lower_bust']>=91 && $rest['lower_bust']<=97){
                        $res['rdxlty_size'] = 'Q';
                    }elseif($rest['lower_bust']>=97 && $rest['lower_bust']<=103){
                        $res['rdxlty_size'] = 'EQ';
                    }elseif($rest['lower_bust']>=103 && $rest['lower_bust']<=109){
                        $res['rdxlty_size'] = 'EQ2';
                    }
                }
                if($res['rbxsy_size'] == 0){
                    if($rest['waist']>=55 && $rest['waist']<=61){
                        $res['rbxsy_size'] = 'S';
                    }elseif($rest['waist']>=61 && $rest['waist']<=67){
                        $res['rbxsy_size'] = 'M';
                    }elseif($rest['waist']>=67 && $rest['waist']<=73){
                        $res['rbxsy_size'] = 'L';
                    }elseif($rest['waist']>=73 && $rest['waist']<=79){
                        $res['rbxsy_size'] = 'LL';
                    }elseif($rest['waist']>=79 && $rest['waist']<=86){
                        $res['rbxsy_size'] = 'EL';
                    }elseif($rest['waist']>=86 && $rest['waist']<=94){
                        $res['rbxsy_size'] = 'Q';
                    }elseif($rest['waist']>=94 && $rest['waist']<=102){
                        $res['rbxsy_size'] = 'EQ';
                    }elseif($rest['waist']>=102 && $rest['waist']<=110){
                        $res['rbxsy_size'] = 'EQ2';
                    }
                }
                if($res['ryj_size'] == 0){
                    if($rest['waist']>=55 && $rest['waist']<=61){
                        $res['ryj_size'] = 'S';
                    }elseif($rest['waist']>=61 && $rest['waist']<=67){
                        $res['ryj_size'] = 'M';
                    }elseif($rest['waist']>=67 && $rest['waist']<=73){
                        $res['ryj_size'] = 'L';
                    }elseif($rest['waist']>=73 && $rest['waist']<=79){
                        $res['ryj_size'] = 'LL';
                    }elseif($rest['waist']>=79 && $rest['waist']<=86){
                        $res['ryj_size'] = 'EL';
                    }elseif($rest['waist']>=86 && $rest['waist']<=94){
                        $res['ryj_size'] = 'Q';
                    }elseif($rest['waist']>=94 && $rest['waist']<=102){
                        $res['ryj_size'] = 'EQ';
                    }elseif($rest['waist']>=102 && $rest['waist']<=110){
                        $res['ryj_size'] = 'EQ2';
                    }
                }
                if($res['rcsk_size'] == 0){
                    if($rest['hipline']>=79 && $rest['waist']<=89){
                        $res['rcsk_size'] = 'S';
                    }elseif($rest['waist']>=83 && $rest['waist']<=93){
                        $res['rcsk_size'] = 'M';
                    }elseif($rest['waist']>=86 && $rest['waist']<=96){
                        $res['rcsk_size'] = 'L';
                    }elseif($rest['waist']>=89 && $rest['waist']<=99){
                        $res['rcsk_size'] = 'LL';
                    }elseif($rest['waist']>=91 && $rest['waist']<=103){
                        $res['rcsk_size'] = 'EL';
                    }elseif($rest['waist']>=94 && $rest['waist']<=106){
                        $res['rcsk_size'] = 'Q';
                    }elseif($rest['waist']>=97 && $rest['waist']<=109){
                        $res['rcsk_size'] = 'EQ';
                    }elseif($rest['waist']>=100 && $rest['waist']<=112){
                        $res['rcsk_size'] = 'EQ2';
                    }
                }
                if($res['rdsk_size'] == 0){
                    if($rest['hipline']>=79 && $rest['waist']<=89){
                        $res['rdsk_size'] = 'S';
                    }elseif($rest['waist']>=83 && $rest['waist']<=93){
                        $res['rdsk_size'] = 'M';
                    }elseif($rest['waist']>=86 && $rest['waist']<=96){
                        $res['rdsk_size'] = 'L';
                    }elseif($rest['waist']>=89 && $rest['waist']<=99){
                        $res['rdsk_size'] = 'LL';
                    }elseif($rest['waist']>=91 && $rest['waist']<=103){
                        $res['rdsk_size'] = 'EL';
                    }elseif($rest['waist']>=94 && $rest['waist']<=106){
                        $res['rdsk_size'] = 'Q';
                    }elseif($rest['waist']>=97 && $rest['waist']<=109){
                        $res['rdsk_size'] = 'EQ';
                    }elseif($rest['waist']>=100 && $rest['waist']<=112){
                        $res['rdsk_size'] = 'EQ2';
                    }
                }

                if($res['rtzd_size'] == 0){
                    if($rest['hipline']>=82 && $rest['hipline']<=90){
                        $res['rtzd_size'] = 'S';
                    }elseif($rest['hipline']>=87 && $rest['hipline']<=95){
                        $res['rtzd_size'] = 'M';
                    }elseif($rest['hipline']>=92 && $rest['hipline']<=100){
                        $res['rtzd_size'] = 'L';
                    }elseif($rest['hipline']>=97 && $rest['hipline']<=105){
                        $res['rtzd_size'] = 'LL';
                    }elseif($rest['hipline']>=102 && $rest['hipline']<=110){
                        $res['rtzd_size'] = '3L';
                    }
                }
                if($res['rnk_size'] == 0){
                    if($rest['hipline']>=79 && $rest['hipline']<=89){
                        $res['rnk_size'] = 'S';
                    }elseif($rest['hipline']>=83 && $rest['hipline']<=93){
                        $res['rnk_size'] = 'M';
                    }elseif($rest['hipline']>=86 && $rest['hipline']<=96){
                        $res['rnk_size'] = 'L';
                    }elseif($rest['hipline']>=89 && $rest['hipline']<=99){
                        $res['rnk_size'] = 'LL';
                    }elseif($rest['hipline']>=91 && $rest['hipline']<=103){
                        $res['rnk_size'] = 'EL';
                    }elseif($rest['hipline']>=94 && $rest['hipline']<=106){
                        $res['rnk_size'] = 'Q';
                    }elseif($rest['hipline']>=97 && $rest['hipline']<=109){
                        $res['rnk_size'] = 'EQ';
                    }elseif($rest['hipline']>=100 && $rest['hipline']<=112){
                        $res['rnk_size'] = 'EQ2';
                    }
                }
                $resp['size_list'] = [
                    [
                        'goods_name' => '功能型调整日间文胸',
                        'goods_size' => $res['rwx_size'],
                    ],
                    [
                        'goods_name' => '功能型日间调整短袖连体衣',
                        'goods_size' => $res['rdxlty_size'],
                    ],
                    [
                        'goods_name' => '功能型日间调整背心上衣',
                        'goods_size' => $res['rbxsy_size'],
                    ],
                    [
                        'goods_name' => '功能型日间调整腰夹',
                        'goods_size' => $res['ryj_size'],
                    ],
                    [
                        'goods_name' => '功能型日间调整长塑裤',
                        'goods_size' => $res['rcsk_size'],
                    ],
                    [
                        'goods_name' => '功能型日间调整短塑裤',
                        'goods_size' => $res['rdsk_size'],
                    ],
                    [
                        'goods_name' => '功能型日间调整内裤',
                        'goods_size' => $res['rnk_size'],
                    ],
                    [
                        'goods_name' => '骨盆调整带',
                        'goods_size' => $res['rtzd_size'],
                    ],
                ];
            }


        }
        return $resp;
    }
}