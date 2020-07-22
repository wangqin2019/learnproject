<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/4
 * Time: 10:23
 */

namespace app\api\controller\v3;
use app\api\model\AppVer as AppVerModel;
use app\api\controller\Base;
use think\Request;
use app\api\validate\AppVer as AppVerValidate;
/*
 * App版本及一些其他相关功能API
 * */
class AppVer extends Common
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
        $result = $this->validate($arr,AppVerValidate::$func[$action]);
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
     * 功能: 关于去哪美
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function aboutQunarmei()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $appver = new AppVerModel();
        $rest = $appver->aboutQunarmei();
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /*
     * 功能: 我的卡券-列表
     * 请求: user_id=>用户id,store_id=>门店id,status=>状态(0=>可使用卡券,1=>已用卡券/过期券),type=>奖券类型(1=>店老板抽奖券)
     * */
    public function myCardList()
    {
        // 请求参数
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['status'] = input('status',0);
        $arr['type'] = input('type',1);
        $appver = new AppVerModel();
        $rest = $appver->myCardList($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['msg'] = '暂无卡券';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}