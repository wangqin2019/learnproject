<?php

namespace app\home\controller;
use app\home\model\BaseModel;
use think\Controller;
use My\RedisPackage;

class Base extends Controller
{
    protected static $redis;
    public function __construct()
    {
        parent::__construct();
        self::$redis=RedisPackage::getInstance();
    }
    public function _initialize()
    {
       
    	$model = new BaseModel();
    	$cate = $model->getAllCate();
    	//dump($cate);exit;
        $this->assign('cate', $cate);

    }
}