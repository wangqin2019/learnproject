<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/10/10
 * Time: 13:48
 */
namespace app\common\exception;

/*
 * 异常基类
 * */
class BaseException extends \Exception
{
    /**
     * 定义公共属性
     * */
    public $code = 400;
    public $msg = '参数错误';
    public $errorcode = 1000;

    public function __construct($code = 400, $msg = '参数错误', $errorcode = 1000)
    {
        $this->msg = $msg;
        $this->errorcode = $errorcode;
        $this->code = $code;
    }
}