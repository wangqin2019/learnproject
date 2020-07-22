<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/10/10
 * Time: 13:48
 */
namespace app\common\exception;

use think\exception\Handle;
use think\Log;

/*
 * 自定义异常接管处理类
 * */
class Z_Exception extends Handle
{
    /**
     * 定义属性
     * */
    private $code = 500;
    private $msg = '服务器内部错误' ;
    private $errorcode = 999;

    /*
     * 重写异常处理接管方法render方法
     * */
    public function render(\Exception $e)
    {
        // 判断$e是否是实例化的BaseException类
        if($e instanceof BaseException){
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorcode = $e->errorcode;
        }else{
            $this->msg = $e->getMessage();
        }
        // 写入日志
        $data = array('code'=>$this->code,'data'=>(object)[],'msg'=>$this->msg);
        $res = json_encode($data,JSON_UNESCAPED_UNICODE);
        Log::record($res,'error');
        return $res;
    }

}