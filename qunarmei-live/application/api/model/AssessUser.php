<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/7/10
 * Time: 17:26
 */

namespace app\api\model;


use think\Model;

class AssessUser extends Model
{
	// 直播考核详情-appc端
	protected $table = 'think_live_assess_user';

	/**
	 * 一对多关联 think_live_assess_score
	 * @return \think\model\relation\HasMany
	 */
	public function score()
	{
		return $this->hasMany('AssessScore','assess_user_id','id');
	}

	/**
	 * 一对一关联 think_live_assess
	 * @return \think\model\relation\HasOne
	 */
	public function assess()
	{
		return $this->hasOne('Assess','id','assess_id');
	}

	/**
	 * think_live_assess think_live_assess_project 3表关联查询
	 * @param array $mapu 当前表查询条件
	 * @param array $mapa assess表查询条件
	 * @param array $mapp project表查询条件
	 * @return array $arr
	 */
	static public function getAssessProject($mapu , $mapa = null , $mapp = null)
	{
		$rest = [];
		$res = self::with(['assess' => function($query) use ($mapa , $mapp){
			$query->with(['project' => function($query) use ($mapp){
				$query->where($mapp);
			}])->where($mapa);
		}])
			->where($mapu)
			->select();
		foreach ($res as $v) {
			$rest[] = $v->toArray();
		}
//		dump($rest);die;
		return $rest;
	}
}