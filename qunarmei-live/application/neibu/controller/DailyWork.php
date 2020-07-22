<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/10
 * Time: 16:25
 */

namespace app\neibu\controller;
use app\neibu\service\DailyWorkService;
set_time_limit(0);
ini_set('memory_limit', '128M');
/**
 * 日常工作服务
 * Class DailyWork
 * @package app\neibu\controller
 */
class DailyWork extends Base
{
    /**
     * 属性商品添加对应产品小图
     * @param string $goods_type 属性类型id
     */
    public function goods_extend_add()
    {
        // 接收参数
        $goods_type = input('goods_type');
        $dwser = new DailyWorkService();

        $res = $dwser->goodsExtendAdd($goods_type);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);

    }
    /**
     * 门店商品支付方式添加
     * @param string $storeid 门店id(多个,分隔)
     * @param string $id_interestrate 支付方式id(多个,分隔)
     */
    public function goods_pay_add()
    {
        // 接收参数
        $storeids = input('storeid');
        $id_interestrates = input('id_interestrate');
        $dwser = new DailyWorkService();

        $res = $dwser->goodsPayAdd($storeids,$id_interestrates);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);

    }
    /**
     * 过期卡券回复可使用状态
     * @param string $tick_code 卡券号(多个,分隔)
     */
    public function card_recovery()
    {
        // 接收参数
        $tick_code = input('tick_code');

        $dwser = new DailyWorkService();

        $res = $dwser->cardRecovery($tick_code);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);

    }
    /**
     * 520专属宠爱券激活
     * @param string $mobile(多个,分隔)
     */
    public function card_jh()
    {
        // 接收参数
        $mobile = input('mobile');

        $dwser = new DailyWorkService();

        $res = $dwser->cardJh($mobile);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);

    }
    /**
     * 添加主播9个子商品
     * @param string $mobile(多个,分隔)
     */
    public function insert_live_goods()
    {
        // 接收参数
        $mobile = input('mobile');

        $dwser = new DailyWorkService();

        $res = $dwser->insertLiveGoods($mobile);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);

    }
    /**
     * 更新主播账号下的观看门店权限
     * @param string $mobile
     * @param string $type 类型,1:日常活动门店412,2:直播消费券门店430
     */
    public function update_live_qx()
    {
        // 接收参数
        $mobile = input('mobile');
        $type = input('type',1);
        $dwser = new DailyWorkService();

        $res = $dwser->updateLiveQx($mobile,$type);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);

    }
    /**
     * 手动补发418奖券-根据用户补发
     * $arr = [{'mobile':15921324164,'card_num':1,'sign':'666-666','price':'2020'}];
     * @param string $mobile [用户手机号码,多个,分割,中间4位带*]
     * @param string $card_num [奖券数量]
     * @param string $sign [门店编号]]
     */
    public function send_card_user()
    {
        // 接收参数
        $arr = input('arr');// json,1次性传入多个用户和奖券数量
        if(empty($arr)){
            $res['code'] = 0;
            $res['data'] = [];
            $res['msg'] = '请求参数不能为空';
            return $this->returnMsg($res['code'],$res['data'],$res['msg']);
        }
        $dwser = new DailyWorkService();

        $res = $dwser->sendCardByUser($arr);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 手动补发418奖券-根据订单发送
     */
    public function send_card()
    {
        // 接收参数
        $dwser = new DailyWorkService();

        $res = $dwser->sendCard();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 删除腾讯云不用的聊天室
     */
    public function del_chat()
    {
        // 接收参数
        $dwser = new DailyWorkService();

        $res = $dwser->delChat();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 更换美容师门店
     * $sign 需要更换到的门店
     * $mobile 美容师号码
     */
    public function upd_mrs_store()
    {
        // 接收参数
        $sign = input('sign');
        $mobile = input('mobile');
        $dwser = new DailyWorkService();

        $res = $dwser->updMrsStore($mobile,$sign);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 批量开通门店活动权限
     * @param string $sign [门店编号,多个,分割]
     * @return \think\response\Json
     */
    public function open_live()
    {
        // 接收参数
        $sign = input('sign');

        $dwser = new DailyWorkService();

        $res = $dwser->openLive($sign);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 更新412直播门店观看权限
     * @return \think\response\Json
     */
    public function update_see_live()
    {
        // 接收参数
        $dwser = new DailyWorkService();
        $res = $dwser->updateSeeLive();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}