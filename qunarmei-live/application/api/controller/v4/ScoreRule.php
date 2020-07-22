<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/10
 * Time: 9:58
 */

namespace app\api\controller\v4;
use app\api\controller\v3\Common;
use app\api\service\OrderSer;
use app\api\service\ScoreSer;

/**
 * 积分规则
 */
class ScoreRule extends Common
{
    // 订单积分
    public function ordScore()
    {
        $order_id = input('order_id');
        $this->rest['code'] = 0;
        $this->rest['data'] = [];
        $this->rest['msg'] = '处理失败';

        $ordSer = new OrderSer();
        $map_ord['o.id'] = $order_id;
        // 1.根据订单id查询订单金额及订单商品
        $res_ord = $ordSer->getOrderDetail($map_ord);
        if($res_ord){
            // 2.根据商品计算积分规则
            $scoreSer = new ScoreSer();

            // 查询积分是否已处理过
            $map_score_record['remark'] = $res_ord[0]['ordersn'];
            $map_score_record['user_id'] = $res_ord[0]['user_id'];
            $res_score_record = $scoreSer->getScoresRecord($map_score_record);
            if($res_score_record){
                $this->rest['msg'] = '该订单积分已处理过,请勿重复请求!';
                return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
            }

            foreach($res_ord as $v){
                $map_score = [
                    'goods_id' => $v['goods_id'],
                    'price' => $v['price']
                ];
                $res_score = $scoreSer->scoreRule($map_score);
                if($res_score){
                    // 3.记录用户积分日志
                    $data = [
                        'user_id' => $v['user_id'],
                        'type' => 'missshop',
                        'msg' => 'uid'.$v['user_id'].'用户下单，奖励用户'.$res_score.'分',
                        'scores' => $res_score,
                        'remark' => $v['ordersn'],
                    ];
                    $res_score_record = $scoreSer->addScoresRecord($data);
                    $this->rest['code'] = 1;
                    $this->rest['msg'] = '订单积分处理成功';
                }else{
                    $this->rest['msg'] = '该商品暂无积分';
                }
            }

        }else{
            $this->rest['msg'] = '不是missshop产品的支付订单';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}