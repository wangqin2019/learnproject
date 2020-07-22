<?php

namespace app\api_test\controller;
use think\Controller;
use think\Db;

/**
 * swagger: 抽奖
 */
class Draw extends Controller
{
	/**
	 * desc: 抽奖开始
	 */
	public function draw_begin()
    {
		$num = input('num',1);
		$draw_type = input('draw_type',0);
		$client = stream_socket_client('tcp://139.196.232.193:5678');
		$data = array('uid'=>'uid1', 'num'=>$num,'status'=>1,'draw_type'=>$draw_type);
		fwrite($client, json_encode($data)."\n");
		$data['code'] = 1;
		$data['msg'] = '前台抽奖池已启动';
		return json($data);
    }

	/**
	 * desc: 抽奖结束
	 */
	public function draw_end()
	{
		$client = stream_socket_client('tcp://139.196.232.193:5678');
		$data = array('uid'=>'uid1', 'num'=>'0','status'=>0,'draw_type'=>'0');
		fwrite($client, json_encode($data)."\n");
		$data['code'] = 1;
		$data['msg'] = '前台抽奖池已停止';
		return json($data);
	}


}