<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:14
 */
namespace app\api\controller\v3;
use think\Request;
use app\api\controller\Base;
use app\api\model\Submeal as SubmealModel;
use app\api\validate\Submeal as SubmealValidate;
/*
 * 代餐数据档案相关API
 * */
class Submeal extends Common
{
    // 统一参数
    protected $rest = [
        'code' => 1,
        'data' => [],
        'msg' => '获取成功'
    ];


    /*
     * 功能: 初始化方法
     * 请求:
     * */
    public  function _initialize()
    {
        $this->dt = time();
        // 统一处理数据验证
        $request = Request::instance();
        $arr = $request->param();
        $action = $request->action();
        // 记录请求日志
        $data_req = $_REQUEST;
        $msg_req = '请求数据:'.http_build_query($data_req , '' , '&');
        parent::writeLog($msg_req);
        $result = $this->validate($arr,SubmealValidate::$func[$action]);
        if(true !== $result){
            $this->rest['msg'] = '请求参数错误:'.$result;
            parent::returnMsgError($this->rest['msg']);
        }
    }

    /*
     * 功能: 错误失败,返回函数
     * 请求: 'msg'=>'错误信息'
     * */
    protected  function returnMsgError($msg)
    {
        $data = [
            'code' => 0,// 错误码0
            'msg' => $msg// 错误信息
        ];
        header('Content-Type:application/json');
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        die;
    }

    /*
     * 功能: 我的档案-代餐档案
     * 请求: user_id=>用户id,store_id=>门店id,dt=>选择日期,fill_user_id=>美容师id
     * */
    public function dinnerFiles()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['fill_user_id'] = input('fill_user_id','');
        $arr['dt'] = input('dt','');
        $arch = new SubmealModel();
        $rest = $arch->dinnerFiles($arr['user_id'],$arr['dt'],$arr['fill_user_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 代餐数据-详情
     * 请求: user_id=>用户id,store_id=>门店id,record_id=>记录
     * */
    public function mealDetails()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['record_id'] = input('record_id','');

        $arch = new SubmealModel();
        $rest = $arch->mealDetails($arr['record_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 代餐对比
     * 请求: dt1=>日期1,dt2=>日期2
     * */
    public function mealCompare()
    {
        // 请求参数
        $arr['dt1'] = input('dt1','');
        $arr['dt2'] = input('dt2','');
        $arr['user_id'] = input('user_id','');
        $arch = new SubmealModel();
        $rest = $arch->mealCompare($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 代餐档案-基本信息-查询
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function mealUserInfo()
    {
        // 请求参数
        $arr['store_id'] = input('store_id','');
        $arr['user_id'] = input('user_id','');
        $arch = new SubmealModel();
        $rest = $arch->mealUserInfo($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 代餐档案-基本信息-提交
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function mealUserInfoUpd()
    {
        $arr = $_REQUEST;
        unset($arr['/api/v3/submeal/mealUserInfoUpd']);
        if(isset($arr['store_id'])){
            unset($arr['store_id']);
        }
        // 兼容传过来的int数组转为字符串

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

        $arch = new SubmealModel();
        $rest = $arch->mealUserInfoUpd($arr);
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['msg'] = '更新成功';
        }else{
            $this->rest['msg'] = '更新成功';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 13.数据记录-自己-查询
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function userMealSel()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new SubmealModel();
        $rest = $arch->userMealSel();
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 13.数据记录-自己-提交
     * 请求: user_id=>用户id,store_id=>门店id,weight=>体重,waist=>腰围,hipline=>臀围
     * */
    public function userMealUpd()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        // $arr['store_id'] = input('store_id','');
        $arr['weight'] = input('weight','');
        $arr['waist'] = input('waist','');
        $arr['hipline'] = input('hipline','');
        $arch = new SubmealModel();
        $rest = $arch->userMealUpd($arr);
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 13.数据记录-美容师
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function beauticianMealSel()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new SubmealModel();
        $rest = $arch->beauticianMealSel();
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 13.数据记录-美容师-提交
     * 请求: fill_user_id=>填写美容师id,user_id=>用户id,store_id=>门店id,weight=>体重,waist=>腰围,hipline=>臀围
     * */
    public function beauticianMealUpd()
    {
        // 请求参数
        $arr['fill_user_id'] = input('fill_user_id','');
        $arr['user_id'] = input('user_id','');
        $arr['weight_index'] = input('weight_index','');
        $arr['weight'] = input('weight','');
        $arr['bmi'] = input('bmi','');
        $arr['body_fat'] = input('body_fat','');
        $arr['fat_volume'] = input('fat_volume','');
        $arr['muscle_volume'] = input('muscle_volume','');
        $arr['bone_mass'] = input('bone_mass','');
        $arr['visceral_fat'] = input('visceral_fat','');
        $arr['metabolism'] = input('metabolism','');
        $arr['body_age'] = input('body_age','');
        $arr['body_water'] = input('body_water','');
        $arr['waist'] = input('waist','');
        $arr['hipline'] = input('hipline','');
        $arr['left_hip_height'] = input('left_hip_height','');
        $arr['right_hip_height'] = input('right_hip_height','');

        $arch = new SubmealModel();
        $rest = $arch->beauticianMealUpd($arr);
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['data'] = $rest;
            $this->rest['msg'] = '记录成功';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}