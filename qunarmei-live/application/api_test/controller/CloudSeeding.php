<?php

namespace app\api_test\controller;
use think\Controller;
use think\Db;

/**
 * swagger: 广告
 */
class CloudSeeding extends Base
{
	/**
	 * get: 广告列表
	 * path: list
	 * method: list
	 * param: position - {int} 广告位
	 */
	public function test($map='')
    {

			$info = Db::name('article')->where($map)->order('id desc')->select();
			if ($info) {
				$rest = parent::returnMsg('200',$info);
			}else
			{
				$rest = parent::returnMsg('400','','返回失败');
			}
			return $rest;
    }

   public function index()
   {
   	// $id = input('id');
    // echo($id);
    // return 'CloudSeeding';
    $url = 'localhost/Test/tt.php';
    $data = array('aa'=>'bb');
    $rest = curl_post($url,$data);
    // $rest = parent::returnMsg('200',$rest);
    echo $rest;
    // return $rest;
   }
}