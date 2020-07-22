<?php

namespace app\websocket\controller;
use app\BaseController;
use app\websocket\validate\CommonValidate;
use My\RedisPackage;
use think\facade\Log;
use think\facade\Request;
use think\exception\ValidateException;
/**
 * 基础控制器类,给子类继承使用通用方法
 */
class Base extends BaseController
{
    public static $redis;
    // 统一下发数据
    public $code = 0;
    public $msg = '暂无数据';
    public $data = [];

    public function __construct()
    {
        self::$redis=RedisPackage::getInstance();
        // 请求数据操作
        $this->requestMsg();
    }

    /**
     * 统一下发数据
     */
    public function returnMsg()
    {
        $arr['code'] = $this->code;
        $arr['msg'] = $this->msg;
        // 状态码为1,成功时才下发data数据
        if ($arr['code'] == 1) {
            $arr['data'] = $this->data;
        }
        $res = json_encode($arr,JSON_UNESCAPED_UNICODE);
        $dt = date('YmdHis');
        Log::info('下发数据-'.$dt.'-'.$res);
        if ($arr['code'] == 0) {
            header('Content-type: application/json');
            echo $res;exit();
        }
        return json($arr);
    }
    /**
     * 记录请求数据并校验
     */
    public function requestMsg()
    {
        // 获取完整URL地址 不带域名
        $url = Request::url();
        // 获取请求数据
        $arr = Request::param();
        // 获取请求方法
        $action = Request::action(true);
        // 记录请求数据
        $res = json_encode($arr,JSON_UNESCAPED_UNICODE);
        $dt = date('YmdHis');
        Log::info('请求url-'.$dt.'-url:'.$url);
        Log::info('请求数据-'.$dt.'-'.$res);
        
        // 验证器验证
        try{
            $result = $this->validate($arr,CommonValidate::$func[$action]);
        }catch (ValidateException $e) {
            // 验证失败 输出错误信息
            $this->msg = '参数错误:'.$e->getError();
            // 返回错误信息,终止运行
            return $this->returnMsg();
            
        }
    }
}