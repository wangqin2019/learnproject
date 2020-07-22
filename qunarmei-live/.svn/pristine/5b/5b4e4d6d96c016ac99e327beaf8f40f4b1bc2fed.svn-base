<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/6/24
 * Time: 10:57
 */

namespace app\admin\controller;

/**
 * 日常行为操作界面化
 * @package app\admin\controller
 */
use think\Db;
use think\Exception;


class DailyBehavior extends Base
{
	// 日常操作主界面
	public function index()
	{
		return $this->fetch();
	}
	
	// 补发直播消费券
	public function sendLiveCardAdd()
	{
		$type = input('type');
		// 提交数据
		if($type){
			$arr1['mobile'] = input('mobile');
			$arr1['sign'] = input('sign');
			$arr1['price'] = input('price');
			$arr1['card_num'] = (int)input('num');
			$arr[] = $arr1;

			$resq = json_encode($arr);
			$url = config('domain').'neibu/daily_work/send_card_user?arr='.$resq;
			$code = 0;$msg = '发送失败';
			try{
				$rest = curl_get($url);
				$res = json_decode($rest,true);
				if($res['code'] == 1){
					$code = 1;
					$msg = '发送成功';
				}
			}catch(Exception $e){
				$msg = $e->getMessage();
			}
			$data['code'] = $code;
			$data['msg'] = $msg;
			return json($data);
		}
		return $this->fetch();
	}
	// 线性修改直播人数
	public function liveUserUpd()
	{
		$type = input('type');
		// 提交数据
		if($type){
			$chat_id = input('chat_id');
			$minute = input('minute');
			$nums = input('nums');

			$url = config('domain').'api/v4/live_mobile/live_numbers_adjust?chat_id='.$chat_id.'&minute='.$minute.'&nums='.$nums;
			$code = 0;$msg = '人数调整失败';
			try{
				$rest = curl_get($url);
				$res = json_decode($rest,true);

				if($res['code'] == 1){
					$code = 1;
					$msg = '人数调整成功';
				}
			}catch(Exception $e){
				$msg = $e->getMessage();
			}
			$data['code'] = $code;
			$data['msg'] = $msg;
			return json($data);
		}
		return $this->fetch();
	}

	// 门店直播商品方案调回总部
	public function storeLivegoodsToPc()
	{
		$type = input('type');
		// 提交数据
		if($type){
			$signs = input('sign');// 多个,分割

			$code = 0;$msg = '方案调回总部失败';$res = [];
			try{
				$map['g.deleted'] = 0;
				$map['g.pcate'] = 31;
				$map['g.ticket_type'] = 0;
				$map['g.storeid'] = 0;
				// 查询总部方案
				$res_pc = Db::table('ims_bj_shopn_goods g')->where($map)->select();
				if($res_pc){
					$map['b.sign'] = ['in',explode(',',$signs)];
					unset($map['g.storeid']);
					foreach ($res_pc as $v) {
						$map['g.pid'] = $v['id'];
						$data['g.activity_rules_id'] = $v['activity_rules_id'];
						$res = Db::table('ims_bj_shopn_goods g')
							->join(['ims_bwk_branch' => 'b'],['g.storeid = b.id'],'left')
							->where($map)
							->update($data);
					}
				}
				if($res){
					$code = 1;
					$msg = '调回总部方案成功';
				}

			}catch(Exception $e){
				$msg = $e->getMessage();
			}
			$data['code'] = $code;
			$data['msg'] = $msg;
			return json($data);
		}
		return $this->fetch();
	}

	// 日常总部直播观看权限更新
	public function liveConfUpd()
	{
		$type = input('type');
		// 提交数据
		if($type){
			$mobile = input('mobile');// 主播号码
			$url = config('domain').'neibu/daily_work/update_live_qx?mobile='.$mobile.'&type=2';
			$code = 0;$msg = '日常总部直播观看权限更新失败';$res = [];
			try{
				$rest = curl_get($url);
				$res = json_decode($rest,true);
				if($res){
					$code = 1;
					$msg = '日常总部直播观看权限更新成功';
				}
			}catch(Exception $e){
				$msg = $e->getMessage();
			}
			$data['code'] = $code;
			$data['msg'] = $msg;
			return json($data);
		}
		return $this->fetch();
	}
}