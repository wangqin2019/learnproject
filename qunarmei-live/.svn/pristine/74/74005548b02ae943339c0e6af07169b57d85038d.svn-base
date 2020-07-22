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
use app\api\model\Archives as ArchivesModel;
use app\api\validate\Archives as ArchivesValidate;
use app\api\model\Fans;
/*
 * 内衣数据档案相关API
 * */
class Archives extends Common
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
        // 将请求数组拼接成url地址参数
        $msg_req = '请求数据:'.http_build_query($data_req , '' , '&');
        parent::writeLog($msg_req);
        $result = $this->validate($arr,ArchivesValidate::$func[$action]);
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
     * 功能: 我的档案-列表
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function archivesList()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new ArchivesModel();
        $rest = $arch->archivesList($arr['user_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-内衣档案
     * 请求: user_id=>用户id,store_id=>门店id,fill_user_id=>填写记录的美容师id
     * */
    public function archivesUnderwear()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['fill_user_id'] = input('fill_user_id','');
        $arch = new ArchivesModel();
        $rest = $arch->archivesUnderwear($arr['user_id'],$arr['fill_user_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-内衣档案-基本信息修改
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function archivesUnderwearUpd()
    {
        // 请求参数 头像,名字,性别,职业,年龄,身高,体重,会员卡种类,月收入,手机,邮箱,微信,QQ,是否愿意回访服务,方便联系时间,住址
        // $arr['user_id'] = input('user_id','');
        // $arr['store_id'] = input('store_id','');
        // $arr['head_img'] = input('head_img','');
        // $arr['user_name'] = input('user_name','');
        // $arr['sex'] = input('sex','');
        // $arr['age'] = input('age','');
        // $arr['occup_name_id'] = input('occup_name_id','');
        // $arr['height'] = input('height','');
        // $arr['weight'] = input('weight','');
        // $arr['card_name_id'] = input('card_name_id','');
        // $arr['range_income_id'] = input('range_income_id','');
        // $arr['mobile'] = input('mobile','');
        // $arr['email'] = input('email','');
        // $arr['weixin'] = input('weixin','');
        // $arr['qq'] = input('qq','');
        // $arr['is_return_visit'] = input('is_return_visit','');
        // $arr['contact_time'] = input('contact_time','');
        // $arr['address'] = input('address','');
        $arr = $_REQUEST;
        unset($arr['/api/v3/archives/archivesUnderwearUpd']);
        if(isset($arr['store_id'])){
            unset($arr['store_id']);
        }
        // 兼容传过来的int数组转为字符串

        foreach ($arr as &$v) {
            if(!empty($v) && strstr($v,'[') && !is_array($v)){
                $v = json_decode($v,true);
                if(is_array($v)){
                    foreach ($v as &$v1) {
                        $v1 = '"'.$v1.'"';
                    }
                }
            }
        }
        $arch = new ArchivesModel();
        $rest = $arch->archivesUnderwearUpd($arr);
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['msg'] = '更新成功';
        }else{
            $this->rest['msg'] = '更新失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-内衣档案-基本信息查询
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function archivesUnderwearSel()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new ArchivesModel();
        $rest = $arch->archivesUnderwearSel($arr['user_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-内衣档案-职业列表
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function occupList()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new ArchivesModel();
        $rest = $arch->occupList();
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-内衣档案-会员卡类型列表
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function cardList()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new ArchivesModel();
        $rest = $arch->cardList();
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-内衣档案-职业列表
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function incomeList()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arch = new ArchivesModel();
        $rest = $arch->incomeList();
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的量身
     * 请求: user_id=>用户id,store_id=>门店id,record_id=>记录id
     * */
    public function measure()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['record_id'] = input('record_id','');
        $arch = new ArchivesModel();
        $rest = $arch->measure($arr['record_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的量身-记录数据
     * 请求: user_id=>用户id,store_id=>门店id,figure_id=>身形选择id,form_state_id=>形体状态id,chest_id=>胸部id,milk_id=>副乳id,abdomen_id=>腹部id,waist_id=>腰部id,pelvis_id=>骨盆id,bb=>BB,right_bb=>右BB,left_bb=>左BB,bust=>胸围,lower_bust=>下胸围,waist=>腰围,hipline=>臀围,thighcir=>大腿围,right_hip_height=>右臀高,left_hip_height=>左臀高,record_id=>记录id,fill_user_id=>美容师id
     * */
    public function measureAdd()
    {
        // 请求参数

        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['figure_id'] = input('figure_id','');
        $arr['form_state_id'] = input('form_state_id','');
        $arr['chest_id'] = input('chest_id','');
        $arr['milk_id'] = input('milk_id','');
        $arr['abdomen_id'] = input('abdomen_id','');
        $arr['waist_id'] = input('waist_id','');
        $arr['pelvis_id'] = input('pelvis_id','');
        $arr['bb'] = input('bb','');
        $arr['right_bb'] = input('right_bb','');
        $arr['left_bb'] = input('left_bb','');
        $arr['bust'] = input('bust','');
        $arr['lower_bust'] = input('lower_bust','');
        $arr['waist'] = input('waist','');
        $arr['hipline'] = input('hipline','');
        $arr['thighcir'] = input('thighcir','');
        $arr['right_hip_height'] = input('right_hip_height','');
        $arr['left_hip_height'] = input('left_hip_height','');
        $arr['lower_leg'] = input('lower_leg','');
        $arr['ankle'] = input('ankle','');
        $arr['hips_id'] = input('hips_id','');
        $arr['thigh_id'] = input('store_id','');
        $arr['vertebra_id'] = input('vertebra_id','');
        $arr['fat_id'] = input('fat_id','');
        $arr['pain_back_id'] = input('pain_back_id','');
        $arr['record_id'] = input('record_id','');
        $arr['fill_user_id'] = input('fill_user_id','');
        $arch = new ArchivesModel();
        if(!$arr['record_id']){
            $arr['type'] = 'add';
        }else{
            $arr['type'] = 'upd';
        }
       // 查询用户资料是否存在
        $res_f = Fans::get(['id_member'=>$arr['user_id']]);
        if(empty($res_f)){
            $this->rest['code'] = 0;
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '请先设置个人资料信息再填写!';
        }else{
            $rest = $arch->measureAdd($arr);
            $this->rest['data']['record_id'] = $rest;
            $this->rest['msg'] = '记录成功';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 形体分
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function shapeScore()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['record_id'] = input('record_id','');
        $arch = new ArchivesModel();
        $rest = $arch->shapeScoreSel($arr['record_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的量身-各种属性列表
     * 请求: $proid => 属性id
     * */
    public function propertyList()
    {
        // 请求参数
        $arr['proid'] = input('proid','');
        $arch = new ArchivesModel();
        $rest = $arch->propertyList($arr['proid']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的档案-对比
     * 请求: $record_id1 => 记录id1,$record_id2 => 记录id2
     * */
    public function contrastData()
    {
        // 请求参数
        $arr['record_id1'] = input('record_id1');
        $arr['record_id2'] = input('record_id2');
        $arch = new ArchivesModel();
        $rest = $arch->contrastData($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 尺码编辑-查询
     * 请求: user_id => 用户id,store_id => 门店id,record_id => 记录id
     * */
    public function measureList()
    {
        // 请求参数
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');
        $arr['record_id'] = input('record_id');
        $arch = new ArchivesModel();
        $rest = $arch->measureList($arr['record_id']);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 5.ta的内衣档案-编辑-删除
     * 请求: $record_id => 记录id,user_id=>用户id
     * */
    public function customerUnderfilesDel()
    {
        // 请求参数
        $arr['record_id'] = input('record_id');
        $arr['user_id'] = input('user_id');
        $arch = new ArchivesModel();
        $rest = $arch->customerUnderfilesDel($arr['record_id']);
        $this->rest['data'] = (object)[];
        if(!empty($rest)){
            $this->rest['msg'] = '删除成功';
        }else{
            $this->rest['msg'] = '删除失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 7.我的二维码
     * 请求: user_id=>用户id
     * */
    public function customerCode()
    {
        // 请求参数
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');
        $arch = new ArchivesModel();
        $rest = $arch->customerCode($arr['user_id'],$arr['store_id']);
        $this->rest['data'] = (object)[];
        // 用户id,用户头像,用户名字,门店地点,二维码,提示语
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 推荐尺码
     * 请求: record_id=>记录id ,type=>类型 0=>日间,1=>夜间
     * */
    public function recommendSize()
    {
        // 请求参数
        $arr['record_id'] = input('record_id');
        $arr['type'] = input('type',0);
        $arch = new ArchivesModel();
        $res = $arch->recommendSizeRule($arr['record_id'],$arr['type']);
        if($res){
            $this->rest['data'] = $res;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}