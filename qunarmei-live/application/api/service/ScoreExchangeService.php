<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/17
 * Time: 19:28
 */

namespace app\api\service;


use think\Db;

class ScoreExchangeService extends BaseSer
{
    // 新上积分商品开关控制,默认只显示技术部,为空时所有门店都显示
    protected $newGoodsId = [];
    // 每周更新无针胶原和胎盘的库存量,每人限兑1次父商品id
    // protected $goodsId = [1748543,11];
    // 积分获取日志
    protected $scoreGet = ['missshop','missshop_transfer','blink'];
    // 积分兑换
    protected $scoreExchange = ['missshop_exchange'];
    // 积分活动id
    protected $scoreActid = [1];
    // 积分有效提示语
    protected $scoreDatetip = '积分于2019年12月31日前有效';
    // 商品温馨提示语
    protected $tips = '1、所有积分兑换商品均需到店取货,请自行联系所属美容院;\n2、本积分活动商品概不退换;\n3、积分兑换后可在"我的积分"明细中查询订单信息,订单不可取消\n';
    // 积分明细提示语
    protected $stips = ['本次获得','TA帮助获得','本次兑换'];
    // 生产id
   protected $goodsId = [1786376,1777175];
    /**
     * 7天自动收货
     * @param $arr
     */
    public function updAutoConfirm($arr)
    {
        $dt_week = strtotime('-1week');// 一周前时间
        $scoreser = new ScoreSer();
        $mapo['o.status'] = 1;
        $mapo['o.create_time'] = ['<',$dt_week];// 一周前还是否有订单
        $res = $scoreser->getScoresOrderGoods($mapo);
        if($res){
            $ids = [];
            foreach ($res as $v) {
                $ids[] = $v['id'];
            }
            $data['status'] = 2;
            $data['confirm_time'] = time();
            $data['is_auto'] = 1;
            $map['id'] = ['in',$ids];
            $scoreser->updScoreGoodsOrder($data,$map);
            $this->code = 1;
            $this->msg = '积分兑换订单自动收货成功';
            $this->data = $ids;
        }
        return $this->returnArr();
    }
    /**
     * 每周一上午9点自动更新库存量
     * @param $arr
     */
    public function updWeekScoreStock($arr)
    {
        // 每周一更新 胎盘和无针 50盒库存
        // 1.先查询已兑换的胎盘盒无针库存
        $arr['wz_num'] = 0;
        $arr['tp_num'] = 0;
        $scoreser = new ScoreSer();
        $map1['o.goods_id'] = ['in',$this->goodsId];
        $map1['o.act_id'] = 1;
        $res_stock = $scoreser->getScoresOrderGoods($map1);
        if($res_stock){
            foreach ($res_stock as $v) {
                if($v['goods_id'] == $this->goodsId[0]){
                    $arr['wz_num'] += 1;
                }
                if($v['goods_id'] == $this->goodsId[1]){
                    $arr['tp_num'] += 1;
                }
            }
            $arr['wz_sy_num'] = 500-$arr['wz_num'];
            $arr['tp_sy_num'] = 500-$arr['tp_num'];
            $data = [];
            $map['score_cat_id'] = 1;
            if($arr['wz_sy_num'] >= 50){
                $data['exchange_num'] = 50;
            }elseif($arr['wz_sy_num'] < 50 && $arr['wz_sy_num']>=0){
                $data['exchange_num'] = $arr['wz_sy_num'];
            }else{
                $data['exchange_num'] = 0;
            }
            $map['goods_id'] = $this->goodsId[0];
            $scoreser->updScoreGoods($data,$map);

            if($arr['tp_sy_num'] >= 50){
                $data['exchange_num'] = 50;
            }elseif($arr['tp_sy_num'] < 50 && $arr['tp_sy_num']>=0){
                $data['exchange_num'] = $arr['wz_sy_num'];
            }else{
                $data['exchange_num'] = 0;
            }
            $map['goods_id'] = $this->goodsId[1];
            $scoreser->updScoreGoods($data,$map);

            $this->code = 1;
            $this->msg = '更新成功';
        }
        // 2.如果剩余大于50,则更新为50 , 如果小于等于50则更新为剩余数量
        return $this->returnArr();
    }
    /**
     * 结算中心
     * @param $arr
     */
    public function settlementCenter($arr)
    {
        $scoreser = new ScoreSer();
        $storeser = new StoreSer();
        // 查询门店信息
        $maps['m.isadmin'] = 1;
        $maps['b.id'] = $arr['store_id'];
        $res_store = $storeser->getStoreUsers($maps);
        if($res_store){
            $res_stores = $res_store[0];
            $store['store_name'] = $res_stores['title'];
            $store['address'] = $res_stores['address']==null?'':$res_stores['address'];
            $store['mobile'] = $res_stores['mobile']==null?'':$res_stores['mobile'];
            $this->data = $store;
        }
        // 查询商品信息
        $mapg['id'] = $arr['goods_id'];
        $res_good = $scoreser->getGoods($mapg);
        if($res_good){
            $res_goods = $res_good[0];
            $good['goods_img'] = $res_goods['thumb'];
            $good['goods_title'] = $res_goods['title'];
            $good['goods_property'] = '';
            $good['goods_exchange_score'] = 0;
            $good['sum_score'] = 0;
            $good['goods_num'] = $arr['goods_num'];
            // 查询商品积分
            $mapsg['goods_id'] = $res_goods['pid'];
            $res_good_score = $scoreser->getScoreGood($mapsg);
            if($res_good_score){
                $good['goods_exchange_score'] = $res_good_score['exchange_score'];
                $good['sum_score'] = $good['goods_exchange_score'] * $arr['goods_num'];
            }
            // 根据属性id查询属性值
            if($arr['property_id']){
                $mapp['id'] = $arr['property_id'];
                $res_pro = $scoreser->getScoreGoodsPropertyVal($mapp);
                if($res_pro){
                    $good['goods_property'] = $res_pro['color'].' '.$res_pro['size'];
                }
            }
            $this->data = array_merge($this->data,$good);
        }
        $this->code = 1;
        if(empty($this->data)){
            $this->msg = '暂无数据';
            $this->data = (object)[];
        }else{
            $this->msg = '获取成功';
        }
        return $this->returnArr();
    }
    /**
     * 修改订单状态
     * @param $arr
     */
    public function updExchangeOrder($arr)
    {
        $scoreser = new ScoreSer();
        // 查询该订单
        $map['id'] = $arr['order_id'];
        $res_order = $scoreser->getScoreGoodsOrder($map);
        if($res_order && $res_order['status'] == 1){
            $data['status'] = 2;
            $data['confirm_time'] = time();
            $scoreser->updScoreGoodsOrder($data,$map);
            $this->code = 1;
            $this->msg = '修改成功';
        }else{
            $this->code = 0;
            $this->msg = '该订单状态不能修改';
        }
        // 修改订单状态
        return $this->returnArr();
    }

    /**
     * 兑换订单详情
     * @param $arr
     */
    public function exchangeOrderDetail($arr)
    {
        $scoreser = new ScoreSer();
        // 查询兑换订单
        $map['o.order_sn'] = $arr['order_sn'];
        $res_order = $scoreser->getScoresOrderGoods($map);
        if($res_order && $res_order[0]){
            $res = $res_order[0];
            $data['order_id'] = $res['id'];
            $data['order_sn'] = $res['order_sn'];
            $data['create_time'] = date('Y-m-d H:i:s',$res['create_time']);
            $data['status'] = $res['status'];// 1:已兑换,2:已完成
            $data['goods_img'] = $res['goods_img'];
            $data['goods_title'] = $res['title'];
            $data['goods_property'] = '';
            $data['goods_exchange_score'] = $res['pay_score'];
            $data['goods_num'] = $res['goods_num'];
            $data['goods_price'] = $res['goods_price'];
            $data['act_name'] = '';
            $data['act_color'] = '';
            // 根据属性id查询属性值
            if($res['property_ids']){
                $mapp['id'] = $res['property_ids'];
                $res_pro = $scoreser->getScoreGoodsPropertyVal($mapp);
                if($res_pro){
                    $data['goods_property'] = $res_pro['color'].' '.$res_pro['size'];
                }
            }
            // 根据活动id查询活动名称和颜色
            $mapa['id'] = $res['act_id'];
            $res_act = $scoreser->getScoreCategorys($mapa);
            if($res_act){
                $res_acts = $res_act[0];
                $data['act_name'] = $res_acts['act_name'];
                $data['act_color'] = $res_acts['act_color'];
            }
            $this->code = 1;
            $this->data = $data;
            $this->msg = '获取成功';
        }else{
            $this->data = (object)[];
            $this->msg = '暂无数据';
        }
        return $this->returnArr();
    }
    /**
     * 我的兑换-兑换订单
     * @param $arr
     */
    public function exchangeOrder($arr)
    {
        $datas = [];
        // 查询个人订单信息
        $scoreser = new ScoreSer();
        $map['act_id'] = $arr['act_id'];
        $map['user_id'] = $arr['user_id'];
        $res_order = $scoreser->getScoresOrderGoods($map,$arr['page']);
        if($res_order){
            foreach ($res_order as $v) {
                // [订单id,订单编号,下单时间,订单状态,商品图片,商品名称,商品内容,商品属性,积分数,商品数量,商品价格]
                $data['order_id'] = $v['id'];
                $data['order_sn'] = $v['order_sn'];
                $data['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $data['status'] = $v['status'];// 1:已兑换,2:已完成
                $data['goods_img'] = $v['goods_img'];
                $data['goods_title'] = $v['title'];
                $data['goods_property'] = '';
                $data['goods_exchange_score'] = $v['pay_score'];
                $data['goods_num'] = $v['goods_num'];
                $data['goods_price'] = $v['goods_price'];
                // 根据属性id查询属性值
                if($v['property_ids']){
                    $mapp['id'] = $v['property_ids'];
                    $res_pro = $scoreser->getScoreGoodsPropertyVal($mapp);
                    if($res_pro){
                        $data['goods_property'] = $res_pro['color'].' '.$res_pro['size'];
                    }
                }
                $datas[] = $data;
            }
        }
        $this->code = 1;
        if($datas){
            $this->data = $datas;
            $this->msg = '获取成功';
        }else{
            $this->msg = '暂无数据';
        }
        return $this->returnArr();
    }
    /**
     * 积分明细列表
     * @param $arr
     */
    public function scoreList($arr)
    {
        // [创建时间,积分来源,订单商品,加减积分数量]
        $scoreser = new ScoreSer();
        $mapc['id'] = ['in',$this->scoreActid];
        $res_score_cate = $scoreser->getScoreCategorys($mapc);
        if($res_score_cate && $res_score_cate[0]){
            $res = $res_score_cate[0];
            $map = explode(',',$res['act_type']);

            if($arr['type'] == 1){
                $map = $this->scoreGet;
            }elseif($arr['type'] == 2){
                $map = $this->scoreExchange;
            }

            $maps['type'] = ['in',$map];
            $maps['scores'] = ['neq',0];
            $maps['user_id'] = $arr['user_id'];
            $res_score_list = $scoreser->getScoresRecords($maps,$arr['page']);
            if($res_score_list){
                // 根据积分列表订单号查询用户订单
                $order_sns_xcx = [];
                $order_sns_app = [];
                $res_order_app = [];
                $res_order_xcx = [];
                $source_users = [];
                $datas = [];
                foreach ($res_score_list as $v) {
                    // 获取积分=>小程序
                    if($v['scores'] > 0){
                        $order_sns_xcx[] = $v['remark'];
                    }else{
                    // 消耗兑换积分
                        $order_sns_app[] = $v['remark'];
                    }
                }
                if($order_sns_app){
                    $mapo['o.order_sn'] = ['in',$order_sns_app];
                    $res_order_app = $scoreser->getScoresOrderGoods($mapo);
                }
                if($order_sns_xcx){
                    // pt_activity_order中的pid关联pt_goods中的id
                    $mappt['o.order_sn'] = ['in',$order_sns_xcx];
                    $res_order_xcx = $scoreser->getScoresOrderGoodsByPt($mappt);
                }
                foreach ($res_score_list as $vs) {
                    $data['score_id'] = $vs['id'];
                    $data['create_time'] = $vs['log_time'];
                    $data['goods_title'] = '';
                    $data['scores'] = $vs['scores'];
                    $data['act_name'] = $res['act_name'];
                    $data['act_color'] = $res['act_color'];
                    $data['order_sn'] = $vs['remark']==null?'':$vs['remark'];

                    // 查询用户信息和提示语
                    $data['user_name'] = '';
                    $data['head_img'] = '';
                    $data['tips'] = '';

                    // 来源用户信息
                    $data['source_user_id'] = 0;
                    if($vs['type'] == 'missshop_transfer'){
                        $data['source_user_id'] = $this->cut('uid','下单',$vs['msg']);
                    }elseif($vs['type'] == 'missshop'){
                        $data['source_user_id'] = $this->cut('uid','用户',$vs['msg']);
                    }
                    if($data['source_user_id']){
                        if($arr['user_id'] == $data['source_user_id']){
                            $data['tips'] = $this->stips[0];
                        }else{
                            $data['tips'] = $this->stips[1];
                        }
                        $source_users[] = $data['source_user_id'];
                    }

                    if($res_order_app && $vs['scores'] < 0){
                        foreach ($res_order_app as $va) {
                            if($vs['remark'] == $va['order_sn']){
                                $data['goods_title'] = $va['title'];
                                if($vs['scores'] < 0){
                                    $data['goods_title'] .= ' (兑换)';
                                    $data['tips'] = $this->stips[2];
                                    $data['scores'] = abs($vs['scores']);
                                }
                            }
                        }
                    }
                    if($res_order_xcx && $vs['scores'] > 0){
                        foreach ($res_order_xcx as $vx) {
                            if($vs['remark'] == $vx['order_sn']){
                                $data['goods_title'] = $vx['title'];
                                if($vs['scores'] > 0){
                                    $data['goods_title'] .= ' (购买)';
                                }
                            }
                        }

                    }
                    $datas[] = $data;
                }
                // 查询用户信息
                if($source_users){
                    $userser = new User();
                    $mapu['m.id'] = ['in',$source_users];
                    $res_user = $userser->getUserFans($mapu);
                    if($res_user){
                        foreach ($res_user as $vu) {
                            foreach ($datas as $k=>$v) {
                                if($v['source_user_id'] == $vu['id']){
                                    $datas[$k]['user_name'] = $vu['realname'];
                                    $datas[$k]['head_img'] = $vu['avatar']==null?config('img.head_img'):$vu['avatar'];
                                }
                            }
                        }
                    }
                }
                foreach ($datas as $k => $v) {
                    unset($datas[$k]['source_user_id']);
                }
                $this->code = 1;
                $this->msg = '获取成功';
                $this->data = $datas;
            }else{
                $this->code = 1;
                $this->data = [];
                $this->msg = '暂无数据';
            }
        }
        return $this->returnArr();
    }
    /**
     * 立即兑换
     * @param $arr
     * @return array
     */
    public function redeemNow($arr)
    {
        // 1.判断商品数量是否限制
        $scoreser = new ScoreSer();


        // 查询父商品信息
        $goodsser = new GoodsSer();
        $mappid['storeid'] = $arr['store_id'];
        $mappid['id'] = $arr['goods_id'];
        $res_pid = $goodsser->getGoods($mappid);
        if($res_pid){
            $arr['goods_id'] = $res_pid['pid'];
            if($res_pid['goods_property'] && empty($arr['property_id'])){
                $this->code = 0;
                $this->msg = '请先选择完整商品属性';
                return $this->returnArr();
            }
        }
        $map['goods_id'] = $arr['goods_id'];
        $res = $scoreser->getScoreGood($map);
        if($res){
            if($res['limit_num'] !=0 && $res['limit_num'] < $arr['goods_num']){
                $this->code = 0;
                $this->msg = '该商品每次只能限兑'.$res['limit_num'].'个';
                return $this->returnArr();
            }
            // 2.查询商品库存

            // 3.查询选定属性的商品库存
            if($arr['property_id']){
                $mapp['score_cat_id'] = 1;
                $mapp['goods_id'] = $arr['goods_id'];
                $mapp['property_id'] = $arr['property_id'];
                $res_pro = $scoreser->getScoreGoodsPropertyStock($mapp);
                if($res_pro && $res_pro['exchange_num'] < $arr['goods_num']){
                    $this->code = 0;
                    $this->msg = '您来晚了一步,商品已兑换完毕';
                    return $this->returnArr();
                }
                $datao['property_ids'] = $arr['property_id'];
            }elseif($res['exchange_num'] < $arr['goods_num']){
                $this->code = 0;
                $this->msg = '您来晚了一步,商品已兑换完毕';
                return $this->returnArr();
            }
            // 4.查询用户积分是否够用
            $mapu['user_id'] = $arr['user_id'];
            $mapu['usable'] = 1;
            $score_rule = array_merge($this->scoreGet,$this->scoreExchange);
            $mapu['type'] = ['in',$score_rule];
            $res_u = $scoreser->sumScoresRecord($mapu);
            if(($res_u && ($res_u[0]['score'] < $arr['goods_num'] * $res['exchange_score'])) || empty($res_u)){
                $this->code = 0;
                $this->msg = '用户积分不足,请稍候再试';
                return $this->returnArr();
            }

            // 6.查询是否是每人每周限兑1次的商品
            if(in_array($arr['goods_id'],$this->goodsId)){
                $mapw = [
                    'user_id' => $arr['user_id'],
                    'goods_id' => $arr['goods_id'],
                ];
                $flag = $scoreser->weekLimitOne($mapw);
                if($flag == 0){
                    $this->code = 0;
                    $this->msg = '该商品每人每周限兑1盒';
                    return $this->returnArr();
                }
            }

            // 5.兑换生成订单
            $datao['goods_id'] = $arr['goods_id'];
            $datao['user_id'] = $arr['user_id'];
            $datao['store_id'] = $arr['store_id'];
            $datao['goods_num'] = $arr['goods_num'];
            $datao['pay_score'] = $arr['goods_num'] * $res['exchange_score'];
            $datao['create_time'] = time();
            $datao['remark'] = $arr['remark'];
            $datao['order_sn'] = $scoreser->makeOrdersn($arr['user_id']);
            // 6.查询用户信息
            $userser = new User();
            $mapm['id'] = $arr['user_id'];
            $res_m = $userser->getUser($mapm);
            if($res_m){
                $datao['pid'] = $res_m['pid'];
                $datao['staffid'] = $res_m['staffid'];
            }

            // 启动事务
            Db::startTrans();
            try{
                // 生成订单
                $reso = $scoreser->addScoreGoodsOrder($datao);
                // 记录积分日志,扣减积分
                $datas = [
                    'user_id' => $arr['user_id'],
                    'type' => 'missshop_exchange',
                    'msg' => '用户uid'.$arr['user_id'].'兑换商品,扣减积分'.$datao['pay_score'].'分',
                    'scores' => -$datao['pay_score'],
                    'remark' => $datao['order_sn']
                ];
                $scoreser->addScoresRecord($datas);

                $mapsc['user_id'] = $arr['user_id'];
                $datasc['missshop_scores'] = ['exp','missshop_scores-'.$datao['pay_score']];
                $datasc['missshop_scores_upd_time'] = date('Y-m-d H:i:s');
                $res_score = $scoreser->updSumUser($datasc,$mapsc);
                // 扣减商品数量
                if(empty($arr['property_id'])){
                    $mapg['goods_id'] = $arr['goods_id'];
                    $datag['exchange_num'] = ['exp','exchange_num-'.$arr['goods_num']];
                    $resg = $scoreser->updScoreGoods($datag,$mapg);
                }else{
                    $mapg['score_cat_id'] = 1;
                    $mapg['goods_id'] = $arr['goods_id'];
                    $mapg['property_id'] = $arr['property_id'];
                    $datag['exchange_num'] = ['exp','exchange_num-'.$arr['goods_num']];
                    $resg = $scoreser->updScoreGoodsProperty($datag,$mapg);
                }
                // 提交事务
                Db::commit();
                $this->code = 1;
                $this->msg = '兑换成功';
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->code = 0;
                $this->msg = '兑换失败-'.$e->getMessage();
            }
        }
        return $this->returnArr();
    }

    /**
     * 积分商品
     * @param $act_id
     * @return array
     */
    public function scoreGoods($act_id,$store_id)
    {
        // 拼购小程序转客拓客活动
        $scoreser = new ScoreSer();
        if($act_id == 1){
            $this->code = 1;
            $map = ' find_in_set('.$act_id.',score_cat_id) ';
            $map1 = null;
            // $map1['sg.storeid'] = $store_id;
            // 非技术部,不显示新品
            if($store_id != 2 && !empty($this->newGoodsId)){
                $map1['g.goods_id'] = ['not in',$this->newGoodsId];
            }
            $res = $scoreser->getScoreGoods($map,$map1);
            if($res){
                foreach ($res as $v) {
                    // 查询商品的子id
                    $gd['goods_id'] = $v['goods_id'];
                    $gd['goods_img'] = $v['goods_img'];
                    $gd['goods_title'] = $v['goods_title'];
                    $gd['goods_price'] = $v['goods_price'];
                    $gd['goods_exchange_score'] = $v['exchange_score'];
                    $gd['goods_limit_num'] = $v['limit_num'];
                    $gd['goods_type'] = $v['special_rule'] == null? '':$v['special_rule'];
                    $gd['tips'] = $this->tips;
                    $this->data[] = $gd;
                }
            }else{
                $this->msg = '暂无数据';
                $this->data = [];
            }
        }
        return $this->returnArr();
    }
    /**
     * 用户积分
     * @param $user_id
     * @return array
     */
    public function userScore($user_id)
    {
        // 总积分,积分规则[活动名称,积分活动,积分数,活动id]
        $scoreser = new ScoreSer();
        $sum_score = 0;
        $this->data['sum_score'] = 0;
        $this->data['act_scores'] = [];
        $this->data['score_rule'] = '';
        $this->code = 1;
        $res_score_cate = $scoreser->getScoreCategorys();
        if($res_score_cate){
            foreach ($res_score_cate as $v) {
                $act['act_id'] = $v['id'];
                $act['act_name'] = $v['act_name'];
                $act['score_name'] = $v['score_name'];
                $act['act_score'] = 0;
                $act['score_date'] = " ";
                $this->data['score_rule'] = $v['score_rule'];
                $res_score = $scoreser->getUserSumScore($user_id,$v['act_type']);
                if($res_score){
                    $this->data['sum_score'] = $res_score['sum_scores'];
                    $act['act_score'] = $res_score['missshop_scores'];
                }
                // 增加不可用积分,不可用积分提示
                $act['available_score'] = '可用积分 0';
                $act['no_available_score'] = '不可用积分 0';
                $act['no_available_tip'] = config('text.no_avaliable_score_tips');
//                print_r($v['act_type']);die;
                if($v['act_type']){
                    $type = explode(',',$v['act_type']);
                    $mapsum['type'] = ['in',$type];
                    $mapsum['user_id'] = $user_id;
                    $res_sum = $scoreser->sumScoresRecord($mapsum);
                    if($res_sum){
                        foreach ($res_sum as $vs) {
                            $sum_score += $vs['score'];
                            if($vs['usable'] && $vs['score']){
                                $act['act_score'] = $vs['score'];
                                $act['available_score'] = str_replace('0',$vs['score'],$act['available_score']);
                            }elseif($vs['score']){
                                $act['no_available_score'] = str_replace('0',$vs['score'],$act['no_available_score']);
                            }
                        }
                    }
                }

                $this->data['act_scores'][] = $act;
            }
            $this->data['sum_score'] = $sum_score;
            $this->code = 1;
            $this->msg = '获取成功';
        }else{
            $this->msg = '暂无数据';
        }
        return $this->returnArr();
    }
}