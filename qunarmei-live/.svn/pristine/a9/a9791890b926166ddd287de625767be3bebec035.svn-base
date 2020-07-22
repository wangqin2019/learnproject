<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/17
 * Time: 19:24
 */

namespace app\api\controller\v4;

use app\api\controller\v3\Common;
use app\api\service\ScoreExchangeService;

/**
 * 积分兑换
 * Class ScoreExchange
 * @package app\api\controller\v4
 */
class ScoreExchange3 extends Common
{
    /**
     * 7天自动收货,每天跑一次
     * @return mixed
     */
    public function updAutoConfirm()
    {
        $arr['act_id'] = input('act_id',1);
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->updAutoConfirm($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 每周一上午9点,更新 无针胶原/胎盘 50盒库存
     * @return mixed
     */
    public function updWeekScoreStock()
    {
        $arr['act_id'] = input('act_id',1);
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->updWeekScoreStock($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 结算中心
     * @return \think\response\Json
     */
    public function settlement_center()
    {
        $arr['goods_id'] = input('goods_id');
        $arr['goods_num'] = input('goods_num');
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');
        $arr['property_id'] = input('property_ids','');//
        // 转客拓客活动
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->settlementCenter($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 兑换详情
     * @return \think\response\Json
     */
    public function exchange_order_detail()
    {
        $arr['order_sn'] = input('order_sn');
        // 转客拓客活动
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->exchangeOrderDetail($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 修改订单状态
     * @return \think\response\Json
     */
    public function updexchange_order()
    {
        $arr['order_id'] = input('order_id');
        $arr['status'] = input('status');
        // 转客拓客活动
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->updExchangeOrder($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 我的兑换
     * @return \think\response\Json
     */
    public function exchange_order()
    {
        $arr['user_id'] = input('user_id');
        $arr['act_id'] = input('act_id');
        $arr['page'] = input('page',1);
        // 转客拓客活动
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->exchangeOrder($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 积分明细
     * @return \think\response\Json
     */
    public function score_list()
    {
        $arr['user_id'] = input('user_id');
//        $arr['act_id'] = input('act_id',1);
        $arr['page'] = input('page',1);
        $arr['type'] = input('type',1);// 1:积分获取,2:我的兑换
        // 转客拓客活动
        $res = [];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->scoreList($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 立即兑换
     * @return \think\response\Json
     */
    public function redeem_now()
    {
        // 商品id,商品属性id[],兑换数量,用户id,门店id
        $arr['goods_id'] = input('goods_id');
        $arr['goods_num'] = input('goods_num');
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');
        $arr['property_id'] = input('property_ids','');//多个,分割
        $arr['remark'] = input('remark');
        // 转客拓客活动
        $res = [];
        $arr['property_id'] = (int)$arr['property_id'];
        $scoreser = new ScoreExchangeService();
        $res = $scoreser->redeemNow($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 积分商品列表
     * @return \think\response\Json
     */
    public function score_goods()
    {
        $act_id = input('act_id');
        $store_id = input('store_id');
        // 转客拓客活动
        $res = [];
        $scoreser = new ScoreExchangeService3();
        $res = $scoreser->scoreGoods($act_id,$store_id);

        return $this->returnMsgNoTrans($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 用户积分
     * @return \think\response\Json
     */
    public function user_score()
    {
        $user_id = input('user_id');

        $htmlser = new ScoreExchangeService();
        $res = $htmlser->userScore($user_id);

        return $this->returnMsgNoTrans($res['code'],$res['data'],$res['msg']);
    }
}