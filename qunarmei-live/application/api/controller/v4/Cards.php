<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/21
 * Time: 11:20
 */

namespace app\api\controller\v4;

use app\api\controller\v3\Common;
use app\api\service\OrderSer;
use app\api\service\TicketSer;

header('Access-Control-Allow-Origin:*');
class Cards extends Common
{
    /**
     * 分享绑定顾客关系
     * @param string $share_mobile 分享用户号码
     * @param string $mobile 填写用户号码
     */
    public function userRegister()
    {
        $mobile = input('mobile');
        $share_mobile = input('share_mobile');
        $cardSer = new TicketSer();
        $res = $cardSer->userRegister($mobile,$share_mobile);
        $this->rest['msg'] = $res['msg'];
        if($res['code'] == 1){
            $this->rest['code'] = 1;
            $this->rest['data'] = $res['data'];
        }else{
            $this->rest['code'] = 0;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * misshop活动分享
     * @param string $user_id 用户id
     */
    public function activeShare()
    {
        $user_id = input('user_id');
        $type = input('type')==''?'missshop':input('type','missshop');
        // missshop活动分享
        $res = [];
        if($type == 'missshop'){
            $cardSer = new TicketSer();
            $res = $cardSer->missActShare($user_id);
        }
        $this->rest['msg'] = $res['msg'];
        if($res['code'] == 1){
            $this->rest['code'] = 1;
            $this->rest['data'] = $res['data'];
        }else{
            $this->rest['code'] = 0;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 可用现金券列表
     * @param string $price 价格
     * @param int $user_id 用户id
     * @param int $goods_id 商品id
     * @param int $cars_id 购物车id
     */
    public function cashList()
    {
        $user_id = input('user_id');
        $price = input('price');
        $goods_id = input('goods_id',0);
        $cars_id = input('cars_id');
        $cardSer = new TicketSer();
//        $res = $cardSer->getCashList($goods_id,$price,$user_id,$cars_id);
        $res = $cardSer->JsCashList($goods_id,$price,$user_id,$cars_id);
        $this->rest['msg'] = $res['msg'];
        $this->rest['data'] = $res['data'];
        if($res['code'] == 1){
            $this->rest['code'] = 1;
        }else{
            $this->rest['code'] = 0;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 点击礼包发送卡券
     * @param string $mobile 号码
     */
    public function sendCard()
    {
        $mobile = input('mobile');
        $type = input('type')==''?'missshop':input('type','missshop');
        $res = [
            'code' => 0,
            'msg' => '发送卡券失败'
        ];
        // missshop活动发券
        if($type == 'missshop'){
            $cardSer = new TicketSer();
            $res = $cardSer->sendMissshopCard($mobile);
            $this->rest['msg'] = $res['msg'];
        }
        if($res['code'] == 1){
            $this->rest['code'] = 1;
        }else{
            $this->rest['code'] = 0;
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 卡券激活核销处理
     * @param int $user_id 用户id
     * @param int $store_id 扫码人门店id,同一门店美容师均可扫码处理
     * @param string $mobile 号码
     * @return \think\response\Json
     */
    public function cardHandle()
    {
        $store_id = input('store_id');
        $user_id = input('user_id');
        $card_no = input('card_no');
        $type = input('type')==''?'missshop':input('type','missshop');
        $card = explode('_',$card_no);
        if(!(is_array($card) && count($card)==2)){
            return $this->returnMsg(0,[],'卡券号格式错误!');
        }
        $res = [];
        if($type == 'missshop' && $card){
            $tickSer = new TicketSer();
            $res = $tickSer->cardHandle($card[0],$card[1],$store_id);
        }
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}