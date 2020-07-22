<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/5/14
 * Time: 11:31
 */

namespace app\admin\controller;

use think\Db;

set_time_limit(0);
ini_set('memory_limit', '200M'); 
/**
 * 办事处订单报表
 */
class OrderDepart extends Base
{
	public function index()
	{
		// 办事处只能查询自己下面的门店
		$bsc = 0;$storeids = [];
		if($_SESSION['think']['rolename'] == '办事处'){
			$bsc = 1;
		}
		if($bsc){
			$uid = $_SESSION['think']['uid'];
			$map_bwk = null;
			// 查询办事处对应下的门店
			$mobile_rule_ser = new MobileRule();
			$storeids = $mobile_rule_ser->getAdminBranch($uid);
		}

		$key = input('key');
		$map = [];$map1 = [];$map_tick = [];$map_tick1 = [];
		if($key&&$key!=="") {
			$map['b.sign'] = ['like',"%" . $key . "%"];
		}
		$dt1 = input('dt1',date('Y-m-d',strtotime('-1 day')));
		$dt2 = input('dt2',date('Y-m-d'));
		if($dt1){
			$map['o.payTime'] = ['>=',strtotime($dt1)];
			$map_tick['insert_time'] = ['>=',$dt1];
		}
		if($dt2){
			$dt22 = strtotime($dt2);
			if($dt2 == $dt1){
				$dt22 = strtotime($dt1) + 3600*24;// 相等基础上,再加1天日期查询
			}
			$map1['o.payTime'] = ['<',$dt22];
			$map_tick1['insert_time'] = ['<',date('Y-m-d',$dt22)];
		}
		$map['g.pcate'] = 31;
		$map['g.ticket_type'] = 0;

		if($storeids){
			$map['o.storeid'] = ['in',$storeids];
		}
		$order_sql = Db::table('ims_bj_shopn_order o')
			->join(['ims_bj_shopn_order_goods'=>'og'],['o.id=og.orderid'],'left')
			->join(['ims_bj_shopn_goods'=>'g'],['g.id=og.goodsid'],'left')
			->field('o.id')
			->group('o.id')
			->where($map)
			->where($map1)
			->order('b.id desc')
			->buildSql();

		$Nowpage = input('get.page') ? input('get.page'):1;
		$limits = 50;// 获取总条数
		$export = input('export');// 导出报表
		if($export){
			$limits = 2000;
		}
		$map_order = ' o.id in '.$order_sql;
		$count = Db::table('ims_bj_shopn_order o')
			->join(['ims_bwk_branch'=>'b'],['o.storeid=b.id'],'left')
			->join(['sys_departbeauty_relation'=>'r'],['r.id_beauty=b.id'],'left')
			->join(['sys_department'=>'d'],['d.id_department=r.id_department'],'left')
			->field('o.id orderid,b.id,b.title,b.sign,d.st_department,sum(o.price) sum_price')
			->group('b.id')
			->where($map_order)
			->order('b.id desc')
			->count();  //总数据
		$allpage = intval(ceil($count / $limits));
		$lists = Db::table('ims_bj_shopn_order o')
//			->join(['ims_bj_shopn_order_goods'=>'og'],['o.id=og.orderid'],'left')
//			->join(['ims_bj_shopn_goods'=>'g'],['g.id=og.goodsid'],'left')
			->join(['ims_bwk_branch'=>'b'],['o.storeid=b.id'],'left')
			->join(['sys_departbeauty_relation'=>'r'],['r.id_beauty=b.id'],'left')
			->join(['sys_department'=>'d'],['d.id_department=r.id_department'],'left')
			->field('o.id orderid,b.id,b.sign,b.title,b.sign,d.st_department,sum(o.price) sum_price')
			->group('b.id')
			->where($map_order)
			->page($Nowpage,$limits)
			->order('b.id desc')
			->select();
		foreach ($lists as $k => $v){
			$lists[$k]['type'] = 'app订单';
			$lists[$k]['gift_price'] = 0;
			$lists[$k]['tick_price'] = 0;

			// 查询直播订单id
			unset($map['b.sign']);
			$map_ord = $map;
			$map_ord['o.storeid'] = $v['id'];
			$res_ord = Db::table('ims_bj_shopn_order o')
				->join(['ims_bj_shopn_order_goods'=>'og'],['o.id=og.orderid'],'left')
				->join(['ims_bj_shopn_goods'=>'g'],['g.id=og.goodsid'],'left')
				->field('o.id,o.storeid')
				->where($map_ord)
				->group('o.id')
				->select();
			if($res_ord){
				$order_ids = [];
				foreach ($res_ord as $vo) {
					$order_ids[] = $vo['id'];
				}
				// 线上已发配赠金额
				$map_og['group'] = ['>',0];
				$map_og['orderid'] = ['in',$order_ids];
				$res_og = Db::table('ims_bj_shopn_order_goods og')
					->where($map_og)
					->field('group')
					->group('group')
					->select();
				if($res_og){
					foreach ($res_og as $v_og) {
						if($v_og['group']){
							$act_rule_id = explode('-',$v_og['group'])[0];
							// 根据规则查询优惠价格
							$map_act['id'] = $act_rule_id;
							$res_rule = Db::table('ims_bj_shopn_goods_activity_rules a')->where($map_act)->field('gift_price')->limit(1)->find();
							if($res_rule && $res_rule['gift_price']){
								$lists[$k]['gift_price'] += $res_rule['gift_price'];
							}
						}
					}
				}
				// 线上已发消费券金额
				$map_tick['storeid'] = $v['id'];
				$map_tick['type'] = 24;
				$res_tick = Db::table('pt_ticket_user t')->where($map_tick)->where($map_tick1)->field(' sum(par_value) price,orderid ')->limit(1)->find();
				if($res_tick){
					$lists[$k]['tick_price'] += $res_tick['price'];
				}
			}
			// 线上业绩 订单金额*0.38
			$lists[$k]['sum_price'] = $lists[$k]['sum_price'] * 0.38;
			// 线上已发配赠金额 线上已发配赠金额*0.38
			$lists[$k]['gift_price'] = $lists[$k]['gift_price'] * 0.38;
			// 线上已发消费券金额 线上已发消费券金额*0.38
			$lists[$k]['tick_price'] = $lists[$k]['tick_price'] * 0.38;
			// 剩余配赠
			$lists[$k]['offline_price'] = 0;
			$lists[$k]['offline_gift_price'] = 0;
			$lists[$k]['total_price'] = 0;
			$lists[$k]['surplus_gift_price'] = 0;
			// 线下打款累计（U8）
			$url = config('erp_url').'web/getGiftData.php?type=2&cCusCode='.$v['sign'].'&beginDate='.$dt1.'&endDate='.$dt2;
			$res_erp = curl_get($url);
//			var_dump($url);var_dump($res_erp);die;
			if($res_erp){
				$res_erp_data = json_decode($res_erp,true);
				if($res_erp_data['status'] == 200){
					$lists[$k]['offline_price'] = $res_erp_data['data'];
				}
			}
			// 线下已发配赠
			$url = config('erp_url').'web/getGiftData.php?type=1&cCusCode='.$v['sign'].'&beginDate='.$dt1.'&endDate='.$dt2.'&giveName=2020年线上直播配赠';
			$res_erp = curl_get($url);
			if($res_erp){
				$res_erp_data = json_decode($res_erp,true);
				if($res_erp_data['status'] == 200){
					$lists[$k]['offline_gift_price'] = $res_erp_data['data'];
				}
			}
			// 业绩合计
			$lists[$k]['total_price'] = $lists[$k]['sum_price'] + $lists[$k]['offline_price'];
			// 剩余配赠
			if($lists[$k]['total_price'] >= 100000){
				$lists[$k]['surplus_gift_price'] = $lists[$k]['total_price'] - $lists[$k]['gift_price'] - $lists[$k]['tick_price'] - $lists[$k]['offline_gift_price'];
			}else{
				$lists[$k]['surplus_gift_price'] = 0;
			}
		}
		$export = input('export');// 导出报表
		if($export){
			$header = array('类别','办事处','客户编号','客户名称','线上业绩','线上已发配赠金额','线上已发消费券金额','线下打款累计','线下已发配赠','业绩合计','剩余配赠');
			$data1[0] = $header;
			foreach ($lists as $v) {
				$data = [];
				$data[] = $v['type'];
				$data[] = $v['st_department'];
				$data[] = $v['sign'];
				$data[] = $v['title'];
				$data[] = $v['sum_price'];
				$data[] = $v['gift_price'];
				$data[] = $v['tick_price'];
				$data[] = $v['offline_price'];
				$data[] = $v['offline_gift_price'];
				$data[] = $v['total_price'];
				$data[] = $v['surplus_gift_price'];
				$data1[] = $data;
			}
			$datav['data'] = $data1;// 具体数据
			$datav['msg'] = '办事处直播订单统计'.date('YmdHis');// 数据表名称
			echo json_encode($datav,JSON_UNESCAPED_UNICODE);die;
		}
		$this->assign('Nowpage', $Nowpage); //当前页
		$this->assign('allpage', $allpage); //总页数
		$this->assign('val', $key);
		$this->assign('dt1', $dt1); // 支付开始时间
		$this->assign('dt2', $dt2); // 支付结速时间
//		echo '<pre>';print_r($lists);die;
		if(input('get.page'))
		{
			return json($lists);
		}
		return $this->fetch();
	}

	/**
	 * 门店订单详情查询
	 * @param int $store_id 门店id
	 * @param string $dt1 开始日期
	 * @param string $dt2 截止日期
	 */
	public function order_detail()
	{
		$key = input('key');
		$map = [];$map1 = [];$map_tick = [];$map_tick1 = [];
		$order_sum['price'] = 0;
		$order_sum['num'] = 0;
		$sign = input('sign');
		$store_id = input('store_id');
		$dt1 = input('dt1',date('Y-m-d',strtotime('-1 day')));
		$dt2 = input('dt2',date('Y-m-d'));
		// 按时间段切分到每天
		$res_day = $this->Date_segmentation($dt1,$dt2);
		$day_list = [];
		if ($res_day) {
			$day_list = $res_day['days_list'];
			array_pop($day_list);
		}
		
		if($dt2){
			$dt22 = strtotime($dt2);
			if($dt2 == $dt1){
				$dt22 = strtotime($dt1) + 3600*24;// 相等基础上,再加1天日期查询
			}
			$map1['o.payTime'] = ['<',$dt22];
			$map_tick1['insert_time'] = ['<',date('Y-m-d',$dt22)];
		}
		$map['g.pcate'] = 31;
		$map['g.ticket_type'] = 0;
		$map['o.storeid'] = $store_id;

		// 查询门店信息
		$map_st['b.id'] = $store_id;
		$res_bwk = Db::table('ims_bwk_branch b')
		->join(['sys_departbeauty_relation'=>'r'],['r.id_beauty=b.id'],'left')
		->join(['sys_department'=>'d'],['d.id_department=r.id_department'],'left')
		->where($map_st)
		->field('b.id,b.title,b.sign,d.st_department bsc')
		->limit(1)
		->find();

		// 循环每日查询
		foreach ($day_list as $vd) {
			$vd2 = date('Y-m-d',strtotime($vd)+3600*24);
			$rest1['title'] = $res_bwk['title'];
			$rest1['sign'] = $res_bwk['sign'];
			$rest1['bsc'] = $res_bwk['bsc'];
			$rest1['day'] = $vd;
			$rest1['offline_price'] = 0;
			$rest1['offline_gift_price'] = 0;
			$rest1['sum_price'] = 0;
			$rest1['gift_price'] = 0;
			$rest1['tick_price'] = 0;
			// 查询线下直播订单
			$map['o.payTime'] = ['>=',strtotime($vd)];
			$map_tick['insert_time'] = ['>=',$vd];
			$map1['o.payTime'] = ['<',strtotime($vd2)];
			$map_tick1['insert_time'] = ['<',$vd2];
			$map['o.storeid'] = $store_id;
			$order_sql = Db::table('ims_bj_shopn_order o')
			->join(['ims_bj_shopn_order_goods'=>'og'],['o.id=og.orderid'],'left')
			->join(['ims_bj_shopn_goods'=>'g'],['g.id=og.goodsid'],'left')
			->field('o.id')
			->group('o.id')
			->where($map)
			->where($map1)
			->buildSql();
			$map_order = ' o.id in '.$order_sql;
			$res_order = Db::table('ims_bj_shopn_order o')
			->where($map_order)->field('o.price,o.id')->select();
			if ($res_order) {
				$order_ids = [];
				foreach ($res_order as $vo) {
					$order_sum['price'] += $vo['price'];
					$order_sum['num'] += 1;
					$rest1['sum_price'] += $vo['price'];
					$order_ids[] = $vo['id'];
				}
				// 线上已发配赠金额
				$map_og['group'] = ['>',0];
				$map_og['orderid'] = ['in',$order_ids];
				$res_og = Db::table('ims_bj_shopn_order_goods og')
					->where($map_og)
					->field('group')
					->group('`group`,`orderid`')
					->select();
				if($res_og){
					foreach ($res_og as $v_og) {
						if($v_og['group']){
							$act_rule_id = explode('-',$v_og['group'])[0];
							// 根据规则查询优惠价格
							$map_act['id'] = $act_rule_id;
							$res_rule = Db::table('ims_bj_shopn_goods_activity_rules a')->where($map_act)->field('gift_price')->limit(1)->find();
							if($res_rule && $res_rule['gift_price']){
								$rest1['gift_price'] += $res_rule['gift_price'];
							}
						}
					}
				}
				// 线上已发消费券金额
				$map_tick['storeid'] = $store_id;
				$map_tick['type'] = 24;
				$res_tick = Db::table('pt_ticket_user t')->where($map_tick)->where($map_tick1)->field(' sum(par_value) price,orderid ')->limit(1)->find();
				if($res_tick){
					$rest1['tick_price'] += $res_tick['price'];
				}
			}
			$rest1['sum_price'] = $rest1['sum_price'] * 0.38;
			$rest1['gift_price'] = $rest1['gift_price'] * 0.38;
			$rest1['tick_price'] = $rest1['tick_price'] * 0.38;
			// 查询线下打款
			$url = config('erp_url').'web/getGiftData.php?type=2&cCusCode='.$sign.'&beginDate='.$vd.'&endDate='.$vd2;
			$res_erp = curl_get($url);
			if($res_erp){
				$res_erp_data = json_decode($res_erp,true);
				if($res_erp_data['status'] == 200){
					$rest1['offline_price'] = $res_erp_data['data'] ;
				}
			}
			// dump($url);dump($res_erp);die;
			// 查询线下配赠
			$url = config('erp_url').'web/getGiftData.php?type=1&cCusCode='.$sign.'&beginDate='.$vd.'&endDate='.$vd2.'&giveName=2020年线上直播配赠|2020年二季度渠道消费券配赠';
			$res_erp = curl_get($url);
			if($res_erp){
				$res_erp_data = json_decode($res_erp,true);
				if($res_erp_data['status'] == 200){
					$rest1['offline_gift_price'] = $res_erp_data['data'] ;
				}
			}
			// 只插入有数据的
			if($rest1['sum_price'] || $rest1['offline_price'] || $rest1['offline_gift_price']){
				$rest[] = $rest1;
			}
			
		}
		$lists = $rest;
		// dump($lists);die;
		$export = input('export');// 导出报表
		if($export){
			$header = array('类别','日期','办事处','客户编号','客户名称','线上业绩','线上已发配赠金额','线上已发消费券金额','线下打款累计','线下已发配赠');
			$data1[0] = $header;
			foreach ($lists as $v) {
				$data = [];
				$data[] = 'app订单';
				$data[] = $v['day'];
				$data[] = $v['bsc'];
				$data[] = $v['sign'];
				$data[] = $v['title'];
				$data[] = $v['sum_price'];
				$data[] = $v['gift_price'];
				$data[] = $v['tick_price'];
				$data[] = $v['offline_price'];
				$data[] = $v['offline_gift_price'];
				$data1[] = $data;
			}
			$datav['data'] = $data1;// 具体数据
			$datav['msg'] = '办事处直播订单明细'.date('YmdHis');// 数据表名称
			echo json_encode($datav,JSON_UNESCAPED_UNICODE);die;
		}
		$this->assign('store_id', $store_id);
		$this->assign('sign', $sign);
		$this->assign('val', $key);
		$this->assign('dt1', $dt1); // 支付开始时间
		$this->assign('dt2', $dt2); // 支付结速时间
		$this->assign('list', $lists);
		$this->assign('order_sum', $order_sum);
		return $this->fetch();
	}
	// 直播订单合并u8数据
	public function order_u8()
	{
		$key = input('key');
		$map = [];$map1 = [];$map_tick = [];$map_tick1 = [];$rest = [];
		if($key&&$key!=="") {
			$map['b.sign'] = ['like',"%" . $key . "%"];
		}
		$dt1 = input('dt1',date('Y-m-d',strtotime('-1 day')));
		$dt2 = input('dt2',date('Y-m-d'));
		// dump(input());die;
		// 办事处只能查询自己下面的门店
		$bsc = 0;$storeids = [];
		if($_SESSION['think']['rolename'] == '办事处'){
			$bsc = 1;
		}
		if($bsc){
			$uid = $_SESSION['think']['uid'];
			$map_bwk = null;
			// 查询办事处对应下的门店
			$mobile_rule_ser = new MobileRule();
			$storeids = $mobile_rule_ser->getAdminBranch($uid);
		}

		// 循环查询每个门店是否有线下打款金额
		// 1.查询所有门店
		$map_store = [];$arr_store = [];$stores = [];$arr_erp1 = [];$arr_erp2 = [];$signs = [];
		if ($storeids) {
			$map1['b.id'] = ['in',$storeids];
			$map_store['id'] = ['in',$storeids];
		}
		// 线下打款累计（U8）
		$url = config('erp_url').'web/getGiftData.php?type=3&beginDate='.$dt1.'&endDate='.$dt2;
		$res_erp = curl_get($url);
		if($res_erp){
			$res_erp_data = json_decode($res_erp,true);
			if($res_erp_data['status'] == 200 && $res_erp_data['data']){
				foreach ($res_erp_data['data'] as $vr) {
					$arr_erp11['sign'] = $vr['cDwCode'];
					$arr_erp11['money'] = $vr['money'];
					if ($key) {
						if ($key == $arr_erp11['sign']) {
							$signs[] = $arr_erp11['sign'];
						}
					}else{
						$signs[] = $arr_erp11['sign'];
					}
					$arr_erp1[] = $arr_erp11;
				}
			}
		}
		// 线下已发配赠
		$url = config('erp_url').'web/getGiftData.php?type=4&beginDate='.$dt1.'&endDate='.$dt2.'&giveName=2020年线上直播配赠|2020年二季度渠道消费券配赠';
		$res_erp = curl_get($url);
		if($res_erp){
			$res_erp_data = json_decode($res_erp,true);
			if($res_erp_data['status'] == 200 && $res_erp_data['data']){
				foreach ($res_erp_data['data'] as $vr) {
					$arr_erp22['sign'] = $vr['cCusCode'];
					$arr_erp22['money'] = $vr['money'];
					if ($key) {
						if ($key == $arr_erp22['sign']) {
							$signs[] = $arr_erp22['sign'];
						}
					}else{
						$signs[] = $arr_erp22['sign'];
					}
					$arr_erp2[] = $arr_erp22;
				}
			}
		}
		
		if($dt1){
			$map['o.payTime'] = ['>=',strtotime($dt1)];
			$map_tick['insert_time'] = ['>=',$dt1];
		}
		if($dt2){
			$dt22 = strtotime($dt2);
			if($dt2 == $dt1){
				$dt22 = strtotime($dt1) + 3600*24;// 相等基础上,再加1天日期查询
			}
			$map1['o.payTime'] = ['<',$dt22];
			$map_tick1['insert_time'] = ['<',date('Y-m-d',$dt22)];
		}
		$map['g.pcate'] = 31;
		$map['g.ticket_type'] = 0;

		if($storeids){
			$map['o.storeid'] = ['in',$storeids];
		}
		$order_sql = Db::table('ims_bj_shopn_order o')
			->join(['ims_bwk_branch'=> 'b'],['o.storeid=b.id'],'left')
			->join(['ims_bj_shopn_order_goods'=>'og'],['o.id=og.orderid'],'left')
			->join(['ims_bj_shopn_goods'=>'g'],['g.id=og.goodsid'],'left')
			->field('o.id')
			->group('o.id')
			->where($map)
			->where($map1)
			->order('b.id desc')
			->buildSql();

		$Nowpage = input('get.page') ? input('get.page'):1;
		$limits = 50;// 获取总条数
		$export = input('export');// 导出报表
		if($export){
			$limits = 2000;
		}
		$map_order = ' o.id in '.$order_sql;
		$count = Db::table('ims_bj_shopn_order o')
			->join(['ims_bwk_branch'=> 'b'],['o.storeid=b.id'],'left')
			->field('o.id orderid,sum(o.price) sum_price,b.sign')
			->group('b.id')
			->where($map_order)
			->order('b.id desc')
			->count();  //总数据
		$allpage = intval(ceil($count / $limits));
		$lists = Db::table('ims_bj_shopn_order o')
		    ->join(['ims_bwk_branch'=> 'b'],['o.storeid=b.id'],'left')
			->field('o.id orderid,o.storeid,sum(o.price) sum_price,b.sign')
			->group('b.id')
			->where($map_order)
			->page($Nowpage,$limits)
			->order('b.id desc')
			->select();
			foreach ($lists as $k => $v){
			$signs[] = $v['sign'];
			$lists[$k]['sign'] = $v['sign'];
			$lists[$k]['store_id'] = $v['storeid'];
			$lists[$k]['type'] = 'app订单';
			$lists[$k]['gift_price'] = 0;
			$lists[$k]['tick_price'] = 0;

			// 查询直播订单id
			unset($map['b.sign']);
			$map_ord = $map;
			$map_ord['o.storeid'] = $v['storeid'];
			$res_ord = Db::table('ims_bj_shopn_order o')
				->join(['ims_bj_shopn_order_goods'=>'og'],['o.id=og.orderid'],'left')
				->join(['ims_bj_shopn_goods'=>'g'],['g.id=og.goodsid'],'left')
				->field('o.id,o.storeid')
				->where($map_ord)
				->group('o.id')
				->select();
			if($res_ord){
				$order_ids = [];
				foreach ($res_ord as $vo) {
					$stores[] = $v['storeid'];
					$order_ids[] = $vo['id'];
				}
				// 线上已发配赠金额
				$map_og['group'] = ['>',0];
				$map_og['orderid'] = ['in',$order_ids];
				$res_og = Db::table('ims_bj_shopn_order_goods og')
					->where($map_og)
					->field('group')
					->group('`group`,`orderid`')
					->select();
				if($res_og){
					foreach ($res_og as $v_og) {
						if($v_og['group']){
							$act_rule_id = explode('-',$v_og['group'])[0];
							// 根据规则查询优惠价格
							$map_act['id'] = $act_rule_id;
							$res_rule = Db::table('ims_bj_shopn_goods_activity_rules a')->where($map_act)->field('gift_price')->limit(1)->find();
							if($res_rule && $res_rule['gift_price']){
								$lists[$k]['gift_price'] += $res_rule['gift_price'];
							}
						}
					}
				}
				// 线上已发消费券金额
				$map_tick['storeid'] = $v['storeid'];
				$map_tick['type'] = 24;
				$res_tick = Db::table('pt_ticket_user t')->where($map_tick)->where($map_tick1)->field(' sum(par_value) price,orderid ')->limit(1)->find();
				if($res_tick){
					$lists[$k]['tick_price'] += $res_tick['price'];
				}
			}
			// 线上业绩 订单金额*0.38
			$lists[$k]['sum_price'] = $lists[$k]['sum_price'] * 0.38;
			// 线上已发配赠金额 线上已发配赠金额*0.38
			$lists[$k]['gift_price'] = $lists[$k]['gift_price'] * 0.38;
			// 线上已发消费券金额 线上已发消费券金额*0.38
			$lists[$k]['tick_price'] = $lists[$k]['tick_price'] * 0.38;
			// 剩余配赠
			$lists[$k]['offline_price'] = 0;
			$lists[$k]['offline_gift_price'] = 0;
			$lists[$k]['total_price'] = 0;
			$lists[$k]['surplus_gift_price'] = 0;
		}
		
		
		// 门店编号去重
		$signs = array_unique($signs);
		if ($signs) {
			$map_st['b.sign'] = ['in',$signs];
			$res_bsc = Db::table('ims_bwk_branch b')->join(['sys_departbeauty_relation'=>'r'],['r.id_beauty=b.id'],'left')
            ->join(['sys_department'=>'d'],['d.id_department=r.id_department'],'left')->where($map_st)->field('b.id,b.title,b.sign,d.st_department bsc')->select();
			foreach ($res_bsc as $kt => $vt) {
				// 查询办事处及门店信息
				$rest1['id'] = $vt['id'];
				$rest1['type'] = 'app订单';
				$rest1['st_department'] = $vt['bsc'];
				$rest1['sign'] = $vt['sign'];
				$rest1['title'] = $vt['title'];
				$rest1['sum_price'] = 0;
				$rest1['gift_price'] = 0;
				$rest1['tick_price'] = 0;
				$rest1['offline_price'] = 0;
				$rest1['offline_gift_price'] = 0;
				$rest1['total_price'] = 0;
				$rest1['surplus_gift_price'] = 0;
				// 线上
				if($lists){
					foreach ($lists as $ks => $vs) {
						if ($vt['sign'] == $vs['sign']) {
							$rest1['sum_price'] = $vs['sum_price'];
							$rest1['gift_price'] = $vs['gift_price'];
							$rest1['tick_price'] = $vs['tick_price'];
						}
					}
				}
				// 线下打款
				if($arr_erp1){
					foreach ($arr_erp1 as $ke => $ve) {
						if ($vt['sign'] == $ve['sign']) {
							$rest1['offline_price'] = $ve['money'];
						}
					}
				}
				// 线下配赠
				if($arr_erp2){
					foreach ($arr_erp2 as $ke => $ve) {
						if ($vt['sign'] == $ve['sign']) {
							$rest1['offline_gift_price'] = $ve['money'];
						}
					}
				}
				$rest1['total_price'] = $rest1['sum_price'] + $rest1['offline_price'];
				// 剩余配赠
	            if($rest1['total_price'] > 100000){
	                $rest1['surplus_gift_price'] = $rest1['total_price'] - $rest1['gift_price'] - $rest1['tick_price'] - $rest1['offline_gift_price'];
	            }else{
	                $rest1['surplus_gift_price'] = 0;
	            }
				$rest[] = $rest1;
			}
		}
		$lists = $rest;
		// dump($lists);die;
		$export = input('export');// 导出报表
		if($export){
			$header = array('类别','办事处','客户编号','客户名称','线上业绩','线上已发配赠金额','线上已发消费券金额','线下打款累计','线下已发配赠','业绩合计','剩余配赠');
			$data1[0] = $header;
			foreach ($lists as $v) {
				$data = [];
				$data[] = $v['type'];
				$data[] = $v['st_department'];
				$data[] = $v['sign'];
				$data[] = $v['title'];
				$data[] = $v['sum_price'];
				$data[] = $v['gift_price'];
				$data[] = $v['tick_price'];
				$data[] = $v['offline_price'];
				$data[] = $v['offline_gift_price'];
				$data[] = $v['total_price'];
				$data[] = $v['surplus_gift_price'];
				$data1[] = $data;
			}
			$datav['data'] = $data1;// 具体数据
			$datav['msg'] = '办事处直播订单统计'.date('YmdHis');// 数据表名称
			echo json_encode($datav,JSON_UNESCAPED_UNICODE);die;
		}
		$this->assign('Nowpage', $Nowpage); //当前页
		$this->assign('allpage', $allpage); //总页数
		$this->assign('val', $key);
		$this->assign('dt1', $dt1); // 支付开始时间
		$this->assign('dt2', $dt2); // 支付结速时间
//		echo '<pre>';print_r($lists);die;
		if(input('get.page'))
		{
			return json($lists);
		}
		return $this->fetch();
	}


	/**
	 * 服务：将时间段按天进行分割
	 * @param string $start_date   @起始日期('Y-m-d H:i:s')
	 * @param string $end_date     @结束日期('Y-m-d H:i:s')
	 * @return array $mix_time_data=array(
	'start_date'=>array([N]'Y-m-d H:i:s'),
	'end_date'=>array([N]'Y-m-d H:i:s'),
	'days_list'=>array([N]'Y-m-d'),
	'days_inline'=>array([N]'Y-m-d H:i:s'),
	'times_inline'=>array([N]'time()')
	)
	 */
	public function Date_segmentation($start_date, $end_date)
	{
	    /******************************* 时间分割 ***************************/
	    //如果为空，则从今天的0点为开始时间
	    if(!empty($start_date))
	        $start_date=date('Y-m-d H:i:s',strtotime($start_date));
	    else
	        $start_date=date('Y-m-d 00:00:00',time());
	 
	 
	 
	    //如果为空，则以明天的0点为结束时间（不存在24:00:00，只会有00:00:00）
	    if(!empty($end_date))
	        $end_date=date('Y-m-d H:i:s',strtotime($end_date));
	    else
	        $end_date=date('Y-m-d 00:00:00',strtotime('+1 day'));
	 
	 
	 
	    //between 查询 要求必须是从低到高
	    if($start_date>$end_date)
	    {
	        $ttt=$start_date;
	        $start_date=$end_date;
	        $end_date=$ttt;
	    }elseif($start_date==$end_date){
	        echo '时间输入错误';die;
	    }
	 
	 
	    $time_s=strtotime($start_date);
	    $time_e=strtotime($end_date);
	    $seconds_in_a_day=86400;
	 
	    //生成中间时间点数组（时间戳格式、日期时间格式、日期序列）
	    $days_inline_array=array();
	    $times_inline_array=array();
	 
	    //日期序列
	    $days_list=array();
	    //判断开始和结束时间是不是在同一天
	    $days_inline_array[0]=$start_date;  //初始化第一个时间点
	    $times_inline_array[0]=$time_s;     //初始化第一个时间点
	    $days_list[]=date('Y-m-d',$time_s);//初始化第一天
	    if(
	        date('Y-m-d',$time_s)
	        ==date('Y-m-d',$time_e)
	    ){
	        $days_inline_array[1]=$end_date;
	        $times_inline_array[1]=$time_e;
	    }
	    else
	    {
	        /**
	         * A.取开始时间的第二天凌晨0点
	         * B.用结束时间减去A
	         * C.用B除86400取商，取余
	         * D.用A按C的商循环+86400，取得分割时间点，如果C没有余数，则最后一个时间点 与 循环最后一个时间点一致
	         */
	        $A_temp=date('Y-m-d 00:00:00',$time_s+$seconds_in_a_day);
	        $A=strtotime($A_temp);
	        $B=$time_e-$A;
	        $C_quotient=floor($B/$seconds_in_a_day);    //商舍去法取整
	        $C_remainder=fmod($B,$seconds_in_a_day);               //余数
	        $days_inline_array[1]=$A_temp;
	        $times_inline_array[1]=$A;
	        $days_list[]=date('Y-m-d',$A);              //第二天
	        for($increase_time=$A,$c_count_t=1;$c_count_t<=$C_quotient;$c_count_t++)
	        {
	            $increase_time+=$seconds_in_a_day;
	            $days_inline_array[]=date('Y-m-d H:i:s',$increase_time);
	            $times_inline_array[]=$increase_time;
	            $days_list[]=date('Y-m-d',$increase_time);
	        }
	        $days_inline_array[]=$end_date;
	        $times_inline_array[]=$time_e;
	    }
	 
	    return array(
	        'start_date'=>$start_date,
	        'end_date'=>$end_date,
	        'days_list'=>$days_list,
	        'days_inline'=>$days_inline_array,
	        'times_inline'=>$times_inline_array
	    );
	}
}