<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/5/9
 * Time: 8:48
 */

namespace app\api\service;
use think\Db;

/**
 * 商品活动规则服务类
 */
class ActivityRulesService
{
	/**
	 * 修改ims_bj_shopn_goods中activity_rules_id规则为
	 * @param string $mobile 主播账号 ,1:电脑直播,其它手机端主播
	 * @param int $type 类型,1:开始直播,2:直播结束
	 */
	public function updateGoodsActrule($mobile,$type=1)
	{
		// 根据主播账号查询
		$map['c.mobile'] = $mobile;

		// 如果不是PC端,开始直播,修改规则id
		if($mobile != 1 && $type == 1){
			$res = Db::table('think_live_see_conf c')
				->join(['ims_bj_shopn_goods_activity_rules'=>'r'],['c.id=r.live_conf_id'],'left')
				->where($map)
				->field('c.store_signs,r.goods_id,r.id')
				->select();
			if($res){
				$signs = explode(',',$res[0]['store_signs']);
				// 更新商品规则id
				foreach ($res as $v) {
					$map_bwk['sign'] = ['in',$signs];
					$res_bwk = Db::table('ims_bwk_branch')->where($map_bwk)->select();
					if($res_bwk){
						$storeids = [];
						foreach ($res_bwk as $vb) {
							$storeids[] = $vb['id'];
						}
						$mapg['pid'] = $v['goods_id'];
						$mapg['storeid'] = ['in',$storeids];
						$datag['activity_rules_id'] = $v['id'];
						$rest1 = Db::table('ims_bj_shopn_goods')->where($mapg)->update($datag);
					}
				}
			}
		}elseif($type == 2){
			// 直播结束修改为PC端直播规则id
			$mapr['storeid'] = 0;
			$mapr['pcate'] = 31;
			$resr = Db::table('ims_bj_shopn_goods')->where($mapr)->select();
			if($resr){
				foreach ($resr as $v) {
					if($v['activity_rules_id']){
						$mapg['pid'] = $v['id'];
						$datag['activity_rules_id'] = $v['activity_rules_id'];
						Db::table('ims_bj_shopn_goods')
							->where($mapg)
							->update($datag);
					}
				}
			}
		}
	}

}