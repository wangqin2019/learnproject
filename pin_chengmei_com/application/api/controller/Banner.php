<?php

namespace app\api\controller;
use think\Config;
use think\Controller;
use think\Db;

/**
 * swagger: 广告
 */
class Banner extends Base
{
	/**
	 * get: banner列表
	 * path: list
	 * method: list
	 * param: position - {int} 广告位
	 */
	public function getBanner()
    {
        $type=input('param.type');
    	$map['ad_position_id'] = $type;
		$map['status'] = 1;	
		$info = Db::name('Banner')->where($map)->order('orderby')->field('id,title,images,type,flag')->select();
		foreach ($info as $k=>$v){
		    $info[$k]['images']=$v['images'];
        }
		if ($info) {
			$code = 1;
			$data = $info;
			$msg = '获取广告列表成功';
		} else {
            $code = 0;
            $data = '';
            $msg = '获取广告列表失败';
		}
		return parent::returnMsg($code,$data,$msg);
		
    }

}