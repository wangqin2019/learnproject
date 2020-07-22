<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/6
 * Time: 11:51
 */

namespace app\api\service;

use app\api\model\PersonalData as PersonalDataMod;
use app\api\model\SubmealFunc;
class PersonalData
{
    /*
     * 功能: 10.我的档案-基本信息-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function underwearUserInfo($arr)
    {
        $res = [
            'head_img' => '',
            'user_info' => []
        ];
        $arr_c1 = [];$arr_zd = ['sex','height','weight','age'];
        $res_u = PersonalDataMod::uuiSel($arr['user_id']);
        if(!empty($res_u)){
            $res['head_img'] = $res_u['head_img'];
            $res_u1 = \app\api\model\ArchivesFunc::userInfo($arr['user_id']);
            if(!empty($res_u1)){
                $res['head_img'] = $res_u1['head_img'];
                $res_u['head_img'] = $res_u1['head_img'];
            }
        }
        $res_c = PersonalDataMod::ucpSel(6);
        if(!empty($res_c)){
            foreach ($res_c as $v_c) {
                $field_name = $v_c['field_name'];
                $arr_c['pro_name'] = $v_c['pro_name'];
                $arr_c['pro_val'] = $res_u[$field_name];
                $arr_c['field_name'] = $field_name;
                $arr_c['field_type'] = $v_c['flag'];
                $arr_c['pro_select_id'] = ["0"];
                $arr_c['pro_select_list'] = [];
                if($arr_c['field_type'] == 2 || $arr_c['field_type'] == 3){
                    $arr_c['pro_select_id'] = $res_u[$field_name];

                    if(in_array($field_name,$arr_zd)){
                        $arr_c['pro_select_id'] = "[\"".$res_u[$field_name]."\"]";
                    }
                    $resp_c[] = $arr_c['pro_select_id'];
                    if(!empty($arr_c['pro_select_id'])){
                        $arr_c['pro_select_id'] = json_decode($arr_c['pro_select_id'],true);
                    }
                    $res_p = PersonalDataMod::ucplSel($v_c['pro_conf_id']);
                    if(!empty($res_p)){
                        $arr_c3 = [];
                        foreach ($res_p as $v_p) {
                            $arr_c2['pro_id'] = $v_p['pro_id'];
                            $arr_c2['pro_name'] = $v_p['pro_name'];
                            $arr_c2['checked'] = 0;
                            if(@in_array($v_p['pro_id'],$arr_c['pro_select_id'])){
                                $arr_c2['checked'] = 1;
                                $arr_c['pro_val'] = $v_p['pro_name'];
                            }
                            $arr_c3[] = $arr_c2;
                        }
                        $arr_c['pro_select_list'] = $arr_c3;
                    }
                }
                $arr_c1[] = $arr_c;
            }
            $res['user_info'] = $arr_c1;
        }
//        print_r($resp_c);die;
        return $res;
    }
    /*
     * 功能: 21.代餐档案-基本信息-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function mealUserInfo($arr)
    {
        $res = [
            'head_img' => '',
            'user_info' => []
        ];
        $arr_c1 = [];
        $res_u = PersonalDataMod::uuiSel($arr['user_id']);
        if(!empty($res_u)){
            $res['head_img'] = $res_u['head_img'];
            $res_u1 = \app\api\model\ArchivesFunc::userInfo($arr['user_id']);
            if(!empty($res_u1)){
                $res['head_img'] = $res_u1['head_img'];
                $res_u['head_img'] = $res_u1['head_img'];
            }
        }
        $res_c = PersonalDataMod::ucpSel(7);
        if(!empty($res_c)){
            foreach ($res_c as $v_c) {
                $field_name = $v_c['field_name'];
                $arr_c['pro_name'] = $v_c['pro_name'];
                $arr_c['pro_val'] = $res_u[$field_name];
                $arr_c['field_name'] = $field_name;
                $arr_c['field_type'] = $v_c['flag'];
                $arr_c['pro_select_id'] = ["0"];
                $arr_c['pro_select_list'] = [];
                if($arr_c['field_type'] == 2 || $arr_c['field_type'] == 3){
                    $arr_c['pro_select_id'] = $res_u[$field_name];
                    if(!is_array($arr_c['pro_select_id'])){
                        $arr_c['pro_select_id'] = json_decode($arr_c['pro_select_id'],true);
                    }
                    if($field_name == 'sex'){
                        $arr_c['pro_select_id'] = ["$res_u[$field_name]"];
                    }
                    if(empty($arr_c['pro_select_id'])){
                        $arr_c['pro_select_id'] = ["$res_u[$field_name]"];
                    }
                    $res_p = PersonalDataMod::ucplSel($v_c['pro_conf_id']);
                    if(!empty($res_p)){
                        $arr_c3 = [];
                        foreach ($res_p as $v_p) {
                            $arr_c2['pro_id'] = $v_p['pro_id'];
                            $arr_c2['pro_name'] = $v_p['pro_name'];
                            $arr_c2['checked'] = 0;
                            if(!empty($arr_c['pro_select_id'])){
                                if(@in_array($v_p['pro_id'],$arr_c['pro_select_id'])){
                                    $arr_c2['checked'] = 1;
                                    $arr_c['pro_val'] = $v_p['pro_name'];
                                }
                            }
                            $arr_c3[] = $arr_c2;
                        }
                        $arr_c['pro_select_list'] = $arr_c3;
                    }
                }
                $arr_c1[] = $arr_c;
            }
            $res['user_info'] = $arr_c1;
        }
        return $res;
    }
    /*
     * 功能: 10.我的档案-基本信息-修改-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function underwearUserUpd($arr)
    {
        $res = PersonalDataMod::uuiUpd($arr);
        return $res;
    }
    /*
     * 功能: 21.代餐档案-基本信息-修改-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function mealUserUpd($arr)
    {
        $res = PersonalDataMod::uuiUpd($arr);
        return $res;
    }
}