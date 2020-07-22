<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/28
 * Time: 13:58
 */

namespace app\api\service;

/**
 * 支付相关处理服务类
 */
class QnmPayService extends BaseSer
{
    // 返回提示语定义
    protected $tips = [
        'is_buy' => '可以购买',
        'no_buy' => '因系统原因,暂时无法购买',
    ];
    /**
     * 是否能支付
     * @param array $arr [goods_id:商品id]
     * @return array
     */
    public function isPay($arr)
    {
        $this->code = 1;
        $is_forbidden = 0;// 购买标记,0:能购买,1:不能购买
        $goodsser = new GoodsSer();
        if(strstr($arr['goods_id'],',')){
            $goods_id = explode(',',$arr['goods_id']);
            $map['id'] = ['in',$goods_id];
        }else{
            $map['id'] = $arr['goods_id'];
        }
        // 查询商品信息
        $res = $goodsser->getGoodss($map);
        if($res){
            $this->msg = $this->tips['is_buy'];
            foreach ($res as $v) {
                if($v['is_forbidden'] == 1){
                   $is_forbidden = 1;
                }
            }
            if($is_forbidden == 1){
                $this->code = 2;
                $this->msg = $this->tips['no_buy'];
            }
        }else{
            $this->msg = $this->tips['no_buy'];
        }
        return $this->returnArr();
    }
}