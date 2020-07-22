<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/4
 * Time: 10:27
 */

namespace app\api\model;
use app\api\model\AppVerFunc;
use app\api\model\SubmealFunc;
use think\Db;
class AppVer
{

    // 小程序https域名服务器
    protected $card418Html = 'https://academy.chengmei.com/Public/luck_draw/card418.html';
    protected $qrcodeHtml = 'https://academy.chengmei.com/Public/luck_draw/qrcode_detail.html';
    // 418卡券二维码说明url
//    protected $card418Html = 'http://live.qunarmei.com/html/luck_draw/card418.html';
    // 卡券二维码说明url
//    protected $qrcodeHtml = 'http://live.qunarmei.com/html/luck_draw/qrcode_detail.html';
    // 显示核销二维码分类
    protected $cate = [11,12];
	/*
     * 功能: 关于去哪美
     * 请求: user_id=>用户id,store_id=>门店id
     * */
    public function aboutQunarmei()
    {
        $rest = [
            'apk_qrcode' => '',
            'tips_ver' => '',
            'tips_down' => '',
            'suggest' => '意见反馈',
            'agreement' => []
        ];

        $res = AppVerFunc::aboutQunarmei();
        if(!empty($res)){
            $rest['apk_qrcode'] = $res['apk_qrcode'];
            $rest['tips_ver'] = $res['tips_ver'];
            $rest['tips_down'] = $res['tips_down'];
            $rest['agreement'] = config('text.notice')['agreement'];
            $sale_rule['name'] = '售后声明';
            $sale_rule['url'] = 'https://www.qunarmei.com/statement/04.html';
            $rest['agreement'][] = $sale_rule;
        }
        return $rest;
    }
    /*
     * 功能: 我的卡券-列表
     * 请求: $arr=>[user_id=>用户id,store_id=>门店id,status=>状态(0=>可使用卡券,1=>已用卡券,2=>过期券),type=>奖券类型(1=>店老板抽奖券)]
     * */
    public function myCardList($arr)
    {
        $rest = '';
        if($arr['status']==1){
            $arr['status'] = [1,2];
        }
		if($arr['status']==0){
            $arr['status'] = [-1,0];
        }
        if($arr['type']==''){
            unset($arr['type']);
        }
        $res2 = AppVerFunc::cardSel($arr);
        if(!empty($res2)){
            foreach ($res2 as $v2) {
				$arr1['qrcode'] = $v2['qrcode']==null?'':$v2['qrcode'];
                $arr1['card_id'] = $v2['id'];
                $arr1['card_name'] = '优惠券';
                $arr2['card_info'] = '用户抽奖时使用';
                $arr1['card_no'] = $v2['ticket_code'];
                $arr1['card_pic'] = $v2['draw_pic']==null?config('card.card_pic'):$v2['draw_pic'];
                $arr1['status'] = $v2['status'];
                $arr1['flag'] = $v2['flag'];
                $arr1['draw_rank'] = $v2['draw_rank']==null?'':$v2['draw_rank'];
                //OTO卡券
                $arr1['type'] = $v2['type'];// 5 OTO卡券
                $arr1['oto_user'] = '';
                $arr1['oto_pwd'] = '';
                $arr1['oto_downurl'] = '';
                $arr1['is_pay'] = 0;// 是否已付款 0未支付 1已支付
                $arr1['price'] = 0;
                $arr1['link_url'] = '';// 点击跳转的url
				$arr1['card_no'] = '';
				if($arr1['type'] == 10){
                    $arr1['card_no'] = '';
                    $arr1['price'] = $v2['par_value'];
                    if($arr1['status'] == 0){
                        $arr1['qrcode'] = '';
                    }elseif($arr1['status'] == -1){
                        // 现金券显示未激活的二维码
                        $arr1['link_url'] = $this->qrcodeHtml.'?qrcode='.$arr1['qrcode'];
                    }
                }
				// 魔境礼券+皮肤礼券显示未核销的二维码
                if(in_array($arr1['type'],$this->cate) && $arr1['status']==0){
                    $arr1['card_no'] = '';
                    $arr1['link_url'] = $this->qrcodeHtml.'?qrcode='.$arr1['qrcode'];
                }
                if($arr1['type'] == 9){
                    $arr1['card_name'] = 'OTO脑力学习卡';
                    $arr1['card_no'] = '';
                    $arr1['card_pic'] = config('img.nopay_img');
                    $arr1['price'] = config('text.oto_price');
                    // 是否已购买
                    $mapo['t.id'] = $v2['id'];
                    $res_oto = Db::table('pt_ticket_user t')
                        ->join(['ims_bj_shopn_oto'=>'o'],['o.card_id=t.id'],'LEFT')
                        ->field('oto_user,oto_pwd,card_id')
                        ->where($mapo)
                        ->limit(1)
                        ->find()
                    ;
                    if($res_oto && $res_oto['card_id']){
                        $arr1['card_pic'] = config('img.pay_img');
                        $arr1['oto_user'] = $res_oto['oto_user']==null?'':$res_oto['oto_user'];
                        $arr1['oto_pwd'] = $res_oto['oto_pwd']==null?'':$res_oto['oto_pwd'];
                        $arr1['oto_downurl'] = config('url.oto_down_url');
                        $arr1['is_pay'] = 1;
                        $arr1['link_url'] = config('url.oto_link_url').'?user_id='.$arr['user_id'];

                    }
                }
                // 418卡券
                if($v2['type'] == 23){
                    $arr1['link_url'] = $this->card418Html.'?qrcode='.$arr1['qrcode'].'&goods_title='.$v2['draw_name'];
                    if($arr1['status'] == 1){
                        $arr1['link_url'] = '';
                    }
                }
                // 直播消费券
                $arr1['rule_pic'] = '';// 规则图片
                $arr1['deadline'] = '';// 截止时间
                if($v2['type'] == 24){
                    $arr1['link_url'] = '';
                    $arr1['card_name'] = '直播消费券';
                    $arr2['card_info'] = '购买非直播商品使用';
                    $arr1['price'] = $v2['par_value']; // 消费券价值
                    $arr1['rule_pic'] = $v2['status']==0?config('card.zk_ys'):config('card.zk_ws');// 过期 灰图,正常 白图
                    $arr1['deadline'] = $v2['aead_time']>0?'截止日期: '.date('Y-m-d',$v2['aead_time']):'';
                }
                if($v2['type'] > 24 && $v2['qrcode']){
                    $title = '';
                    if($v2['type'] == 25){
                        $title = '护理券';
                    }elseif($v2['type'] == 26){
                        $title = '门店定制礼品券';
                    }
                    $arr1['link_url'] = $this->qrcodeHtml.'?qrcode='.$arr1['qrcode'].'&goods_title='.$title;
                    // 定制礼品券,未激活不能打开
                    if($v2['type'] == 27 && $v2['status'] == -1){
                        $arr1['link_url'] = '';
                    }
                    if($v2['type'] == 27){
                        $arr1['rule_pic'] = $v2['status']==0?config('card.card520_ys'):config('card.card520_ws');// 过期 灰图,正常 白图
                        $arr1['deadline'] = $v2['aead_time']>0?'截止日期: '.date('Y-m-d',$v2['aead_time']):'';
                    }
                    // 直播助手抽奖券
                    if($v2['type'] >= 29){
                        $arr1['card_name'] = '直播助手抽奖券';
                        $arr1['link_url'] = $this->card418Html.'?qrcode='.$arr1['qrcode'].'&goods_title='.$v2['draw_name'];
                    }
                }
                $rest[] = $arr1;
            }
        }
        return $rest;
    }

}