<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/7/7
 * Time: 13:29
 */

namespace app\admin\controller;
use think\Db;


/**
 * 直播考核管理
 */
class LiveAssessment extends Base
{
	/**
	 * 考核列表
	 */
	public function assessmentList()
	{
		// 接收参数
		$export = input('export');// 是否导出数据
		$mobile = input('mobile');
		$page = input('page');// 当前页
		$limit = input('limit');// 每页显示条数
		$map = [];

		$assessment_mobile = config('assessment_mobile');
		$signs = ['666-666','888-888','001-001'];
		$map['b.sign'] = ['in',$signs];

		if($mobile){
			$map['m.mobile'] = ['like','%'.$mobile.'%'];
		}

		if($export){
			$limit = 5000;
		}

		$res_count = Db::table('ims_bj_shopn_member m')
			->join(['ims_bwk_branch' => 'b'],['m.storeid = b.id'],'left')
			->field('m.id,m.realname,m.mobile,b.title,b.sign')
			->where($map)
			->count();
		$res = Db::table('ims_bj_shopn_member m')
			->join(['ims_bwk_branch' => 'b'],['m.storeid = b.id'],'left')
			->field('m.id,m.realname,m.mobile,b.title,b.sign')
			->where($map)
			->page($page , $limit)
			->order('id desc')
			->select();

		// 导出excel数据
		if($export){
			$arr['code'] = 0;
			$arr['msg'] = '获取成功';
			$arr['headerarr'] = [
				'ID','考核人名称','考核人号码','门店名称','门店编号'
			];
			$arr['data'][0] = $arr['headerarr'];
			foreach ($res as $v) {
				$rest1[0] = $v['id'];
				$rest1[1] = $v['realname'];
				$rest1[2] = $v['mobile'];
				$rest1[3] = $v['title'];
				$rest1[4] = $v['sign'];
				$arr['data'][] = $rest1;
			}
			return json($arr);
		}

		if($page){
			$arr['code'] = 0;
			$arr['msg'] = '获取成功';
			$count = ceil($res_count / $limit);// 总页数
			$arr['count'] = $count;
			$arr['data'] = $res;
			return json($arr);
		}


		// 赋值模板变量
		$this->assign('assessment_mobile',$assessment_mobile);
		// 加载模板
		return $this->fetch();
	}
	/**
	 * 添加考核
	 */
	public function assessmentAdd()
	{
		if(request()->isAjax()){
			$param = input();

			$arr['code'] = 1;
			$arr['msg'] = '添加成功';
			return json($arr);
		}
		// 加载模板
		return $this->fetch();
	}
}