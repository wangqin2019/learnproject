<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/6/22
 * Time: 11:53
 */

namespace app\api\model;


use think\Model;

class Appc_apilog extends Model
{
	// appC端请求接口日志记录
	protected $table = 'think_appc_api_log';
}