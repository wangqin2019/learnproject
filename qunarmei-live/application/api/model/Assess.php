<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/7/10
 * Time: 17:26
 */

namespace app\api\model;


use think\Model;

class Assess extends Model
{
	// 直播考核列表-appc端
	protected $table = 'think_live_assess';

	/**
	 * 一对一关联 think_live_assess_project
	 * @return \think\model\relation\HasOne
	 */
	public function project()
	{
		return $this->hasOne('AssessProject','id','project_id');
	}

	/**
	 * 一对多关联 think_live_assess_user
	 * @return \think\model\relation\HasMany
	 */
	public function user()
	{
		return $this->hasMany('AssessUser','assess_id','id');
	}

	static public function getProject($mapa , $mapp)
	{
		$rest = [];
		$res = self::with(['project' => function($query) use($mapp){
			$query->where($mapp);
		}])->where($mapa)->select();
		foreach ($res as $v) {
			$rest[] = $v->toArray();
		}
//		dump($rest);die;
		return $rest;
	}
}