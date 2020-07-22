<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/6/22
 * Time: 10:21
 */

namespace app\api\model;


use think\Model;

class Copyroom extends Model
{
	// 直播文案库
	protected $table = 'think_zb_copyroom';

	/**
	 * 获取文案下的所有图片
	 */
	public function copyroomImage()
	{
		return $this->hasMany('CopyroomImage','copyroom_id','id');
	}

}