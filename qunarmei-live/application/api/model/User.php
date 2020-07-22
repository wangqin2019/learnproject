<?php
namespace app\api\model;

use think\Model;

class User extends Model
{
	protected $table = 'ims_bj_shopn_member';

	// 关联门店表
	public function branch()
	{
		return $this->hasOne('Branch','id','storeid');
	}
}