<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/7/3
 * Time: 10:27
 */

namespace app\api\service;
use app\api\model\Appc_apilog;
use think\Exception;

/**
 * 请求日志服务类
 */
class ApiLogSer
{
	/**
	 * 记录请求日志
	 * @param string $api_path 接口请求路径
	 * @param string $request_data 请求参数
	 * @param string $user_agent 请求头信息
	 */
	static public function addLog($api_path , $request_data = '' , $user_agent = '')
	{
		$arr['code'] = 0;
		$arr['data'] = '';

		$data['api_path'] = $api_path;
		$data['request_data'] = $request_data;
		$data['user_agent'] = $user_agent;
		$data['create_time'] = time();
		try{
			$appc = Appc_apilog::create($data);
			$arr['code'] = 1;
			$arr['data'] = $appc->id;
		}catch(Exception $e){
			$arr['data'] = $e->getMessage();
		}
		return $arr;
	}

	/**
	 * 修改请求日志
	 * @param int $id 日志id
	 * @param string $response_data 下发数据
	 */
	static public function updLog($id , $response_data)
	{
		$arr['code'] = 0;
		$arr['data'] = '';

		$map['id'] = $id;
		$data['response_data'] = $response_data;
		$data['update_time'] = time();
		try{
			$appc = Appc_apilog::where($map)->update($data);
			$arr['code'] = 1;
			$arr['data'] = $appc;
		}catch(Exception $e){
			$arr['data'] = $e->getMessage();
		}
		return $arr;
	}
}