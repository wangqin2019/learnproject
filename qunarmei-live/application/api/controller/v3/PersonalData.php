<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/6
 * Time: 11:26
 */

namespace app\api\controller\v3;
use app\api\service\PersonalData as PersonalDataService;
class PersonalData extends Common
{
    /*
     * 功能: 10.我的档案-基本信息-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function underwearUserInfo()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $appver = new PersonalDataService();
        $rest = $appver->underwearUserInfo($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 21.代餐档案-基本信息-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function mealUserInfo()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $appver = new PersonalDataService();
        $rest = $appver->mealUserInfo($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 10.我的档案-基本信息-修改-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    /*
     * 功能: 10.我的档案-基本信息-修改-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function underwearUserUpd()
    {
        // 请求参数 store_id=2&qq=&weixin=&address=%E4%B8%8A%E6%B5%B7&user_id=20230&user_name=%E7%BD%97%E9%AA%81&contact_time=&mobile=&card_type=[2]&email=&month_income_id=&is_return_visit=&weight=&height=&occupation=&age=&sex=
//        print_r($_REQUEST);die;
//        $arr['user_id'] = input('user_id','');
//        $arr['qq'] = input('qq','');
//        $arr['weixin'] = input('weixin','');
//        $arr['address'] = input('address','');
//        $arr['user_name'] = input('user_name','');
//        $arr['contact_time'] = input('contact_time','');
//        $arr['mobile'] = input('mobile','');
//        $arr['card_type'] = input('card_type','');
//        $arr['email'] = input('email','');
//        $arr['is_return_visit'] = input('is_return_visit','');
//        $arr['month_income_id'] = input('month_income_id','');
//        $arr['weight'] = input('weight','');
//        $arr['height'] = input('height','');
//        $arr['occupation'] = input('occupation','');
//        $arr['age'] = input('age','');
//        $arr['sex'] = input('sex','');
        $arr = $_REQUEST;
        unset($arr['/api/v3/personal_data/underwearUserUpd']);
        if(isset($arr['store_id'])){
            unset($arr['store_id']);
        }
        if(isset($arr['sex']) && is_string($arr['sex'])){
            $arr['sex'] = json_decode($arr['sex'],true);
            $arr['sex'] = $arr['sex'][0];
        }

        // 数组转为json串
        foreach ($arr as &$v) {
            if(!empty($v) && strstr($v,'[') && is_string($v)){
                $v = json_decode($v,true);
                if(is_array($v)){
                    foreach ($v as &$v1) {
                        $v1 = (string)$v1;
                    }
                    $v = json_encode($v);
                }
            }

        }
//        print_r($arr);die;
        $appver = new PersonalDataService();
        $this->rest['data'] = (object)[];
        $rest = $appver->underwearUserUpd($arr);
        if(!empty($rest)){
            $this->rest['msg'] = '更新成功';
        }else{
            $this->rest['msg'] = '更新成功';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 21.代餐档案-基本信息-修改-新接口
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function mealUserUpd()
    {
        // 请求参数
        $arr = $_REQUEST;
        unset($arr['/api/v3/personal_data/mealUserUpd']);
        if(isset($arr['store_id'])){
            unset($arr['store_id']);
        }
//         if(isset($arr['sex']) && is_array($arr['sex'])){
//             $arr['sex'] = $arr['sex'][0];
//         }elseif(isset($arr['sex']) && is_string($arr['sex'])){
//             $arr['sex'] = json_decode($arr['sex'],true);
//             $arr['sex'] = $arr['sex'][0];
//         }
//         // 数组转为json串
// //        print_r($arr);die;
//         foreach ($arr as &$v) {
//             if($v && is_array($v)){
//                 $v = json_encode($v);
//             }
//         }
//         if(isset($arr['sex']) && is_string($arr['sex'])){
//             $sex = json_decode($arr['sex'],true);
//             $arr['sex'] = $sex[0];
//         }
         if(isset($arr['sex']) && is_string($arr['sex'])){
            $arr['sex'] = json_decode($arr['sex'],true);
            $arr['sex'] = $arr['sex'][0];
        }

        // 数组转为json串
        foreach ($arr as &$v) {
            if(!empty($v) && strstr($v,'[') && is_string($v)){
                $v = json_decode($v,true);
                if(is_array($v)){
                    foreach ($v as &$v1) {
                        $v1 = (string)$v1;
                    }
                    $v = json_encode($v);
                }
            }
        }

        $appver = new PersonalDataService();
        $rest = $appver->mealUserUpd($arr);
        if(!empty($rest)){
            $this->rest['msg'] = '更新成功';
        }else{
            $this->rest['msg'] = '更新成功';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}