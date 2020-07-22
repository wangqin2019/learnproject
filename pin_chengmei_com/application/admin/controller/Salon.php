<?php

namespace app\admin\controller;
use app\admin\model\BankModel;
use think\Db;
use think\Loader;
use think\Request;

class Salon extends Base
{

    public function index(){

        return $this->fetch();
    }

}
