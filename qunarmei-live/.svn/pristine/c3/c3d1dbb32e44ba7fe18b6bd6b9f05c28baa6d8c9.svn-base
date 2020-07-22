<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/19
 * Time: 14:20
 */

namespace app\api\controller\v3;
use think\Request;
use app\api\controller\Base;
use app\api\model\Health as HealthModel;
use app\api\validate\Health as HealthValidate;
/**
 * 健康指数自测相关
 */
class Health extends Common
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
        $result = $this->validate($arr,HealthValidate::$func[$action]);
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
     * 功能: 健康指数自测
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function healthCheck()
    {
        // 请求参数 性别,出生日期,身高,体重,腰围,臀围
        $arr['user_id'] = input('user_id','');
        $arr['store_id'] = input('store_id','');
        $arr['sex'] = input('sex',0);
        $arr['birthday'] = input('birthday','');
        $arr['weight'] = input('weight','');
        $arr['height'] = input('height','');
        $arr['waist'] = input('waist','');
        $arr['hipline'] = input('hipline','');

        $arch = new HealthModel();
        $rest = $arch->healthCheck($arr);
        if(!empty($rest)){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

}