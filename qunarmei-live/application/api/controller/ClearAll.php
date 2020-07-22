<?php

namespace app\api\controller;
//使用redis扩展
use think\cache\driver\Redis;
use think\Exception;
use think\Log;
class ClearAll
{

	/**
	 * 清除去哪美C端测试环境中的缓存和redis(java 和 php)
	 * @return [string] [josn]
	 */
	public function clearFlush()
	{
		$rest = [
			'code' => 400,
			'data' => [],
			'msg' => '清除缓存失败!'
		];
		// 清除缓存
		$java_url = 'https://api-app.qunarmei.com/qunamei/flush';
		$php_url = 'http://live.qunarmei.com/api/live/delRedis';
		try{
			Log::info('java清除缓存请求:'.$java_url);
			$res_mem = curl_get($java_url,'https');
			Log::info('java清除缓存下发:'.$res_mem);
			Log::info('php清除缓存请求:'.$php_url);
			$res_redis = curl_get($php_url);
			Log::info('php清除缓存下发:'.$res_redis);
			if ($res_mem && $res_redis) {
				$rest['code'] = '200';
				$rest['msg'] = '清除缓存成功';
			}
		}catch(Exception $e){
			$rest['msg'] = $e->getMessage;
			Log::info('清除缓存异常数据:'.$e->getMessage);
		}
		return return_msg($rest);
	}
}