<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/10
 * Time: 10:07
 */

namespace app\api\service;
use think\Db;
/**
 * 积分服务
 */
class ScoreSer
{
    // missshop商品单品每20元积一分
    public $missshop_id = [1754639,1754638,1754637,1754631,1754636,1754635,1754634,1754633,1754629];

    protected $act_type = ['missshop','missshop_transfer','blink'];
    // missshop商品双倍积分
    public $missshop_double_id= [];

    /*********************添加***********************/
    public function addScoreGoodsOrder($data)
    {
        $res = Db::table('ims_bj_shopn_score_goods_order')->insertGetId($data);
        return $res;
    }
    /*********************添加***********************/
    /*********************修改***********************/
    /**
     * 修改积分订单
     * @param array $data 修改数据
     * @param array $map 查询条件
     * @return int|string
     */
    public function updScoreGoodsOrder($data,$map)
    {
        $res = Db::table('ims_bj_shopn_score_goods_order')->where($map)->update($data);
        return $res;
    }
    /**
     * 修改有属性商品库存
     * @param array $data 修改数据
     * @param array $map 查询条件
     * @return int|string
     */
    public function updScoreGoodsProperty($data,$map)
    {
        $res = Db::table('ims_bj_shopn_score_goods_property')->where($map)->update($data);
        return $res;
    }
    /**
     * 修改商品库存
     * @param array $data 修改数据
     * @param array $map 查询条件
     * @return int|string
     */
    public function updScoreGoods($data,$map)
    {
        $res = Db::table('ims_bj_shopn_score_goods')->where($map)->update($data);
        return $res;
    }
    /**
     * 修改用户积分
     * @param array $data 修改数据
     * @param array $map 查询条件
     * @return int|string
     */
    public function updSumUser($data,$map)
    {
        $res = Db::table('think_sum_user')->where($map)->update($data);
        return $res;
    }
    /*********************修改***********************/
    /**
     * 查询统计用户积分情况
     * @param array $map 查询条件
     * @return array
     */
    public function sumScoresRecord($map,$group = 'usable')
    {
        $res = Db::table('think_scores_record r')->field('sum(scores) score,r.*')->where($map)->group($group)->select();
        return $res;
    }
    /**
     * 每人每周限兑1次1个
     * @param $map
     * @return array
     */
    public function weekLimitOne($map)
    {
        $flag = 0;
        $mapo['user_id'] = $map['user_id'];
        $mapo['goods_id'] = $map['goods_id'];
        $mapo['create_time'] = ['>=',strtotime('-1week')];// 一周前还是否有订单
        $res = $this->getScoreGoodsOrder($mapo);
        if(empty($res)){
            $flag = 1;
        }
        return $flag;
    }
    /**
     * 获取多个商品信息
     * @param $map
     * @return array
     */
    public function getGoods($map)
    {
        $res = Db::table('ims_bj_shopn_goods')->where($map)->select();
        return $res;
    }
    /**
     * 获取单个积分订单
     * @param $map
     * @return array
     */
    public function getScoreGoodsOrder($map)
    {
        $res = Db::table('ims_bj_shopn_score_goods_order')->where($map)->limit(1)->find();
        return $res;
    }
    /**
     * 获取用户积分来源小程序订单商品
     * @param $map
     * @return array
     */
    public function getScoresOrderGoodsByPt($map)
    {
        $res = Db::table('pt_activity_order o')
            ->join(['pt_goods'=>'g'],['o.pid = g.id'],'LEFT')
            ->field('o.id,o.order_sn,g.name title,o.insert_time')
            ->where($map)
            ->order('o.insert_time desc')
            ->select();
        return $res;
    }
    /**
     * 获取用户积分兑换订单商品信息
     * @param $map
     * @return array
     */
    public function getScoresOrderGoods($map,$page = 0)
    {
        $pages = 0;
        $pageSize = 50;
        if($page > 0){
            $pageSize = config('text.page_size');
            $pages = ($page - 1) * $pageSize;
        }
        $res = Db::table('ims_bj_shopn_score_goods_order o')
            ->join(['ims_bj_shopn_goods'=>'sg'],['o.goods_id = sg.id'],'LEFT')
            ->field('o.act_id,o.goods_id,o.id,o.order_sn,o.create_time,o.status,o.goods_num,o.pay_score,o.property_ids,sg.title,sg.thumb goods_img,sg.marketprice goods_price')
            ->where($map)
            ->order('o.create_time desc')
            ->limit($pages,$pageSize)
            ->select();
        return $res;
    }
    /**
     * 获取用户积分日志
     * @param $map
     * @return array
     */
    public function getScoresRecords($map,$page = 1)
    {
        $pageSize = config('text.page_size');
        $page = ($page - 1) * $pageSize;
        $res = Db::table('think_scores_record')->where($map)->order('log_time desc')->limit($page,$pageSize)->select();
        return $res;
    }
    /**
     * 生成订单号
     * @param int $user_id 用户id
     * @return array
     */
    public function makeOrdersn($user_id)
    {
        $user_id = sprintf("%07d", $user_id);
        $ordersn = date('YmdHis').rand(10,99).$user_id;
        return $ordersn;
    }
    /**
     * 获取单个用户积分
     * @param $map
     * @return array
     */
    public function getSumUser($map)
    {
        $res = Db::table('think_sum_user')->where($map)->limit(1)->find();
        return $res;
    }
    /**
     * 获取属性值
     * @param $map
     */
    public function getScoreGoodsPropertyVal($map)
    {
        $res = Db::table('ims_bj_shopn_goods_extend')->where($map)->limit(1)->find();
        return $res;
    }
    /**
     * 获取积分商品多属性库存量
     * @param $map
     */
    public function getScoreGoodsPropertyStock($map)
    {
//        $res = Db::table('ims_bj_shopn_goods_extend')->where($map)->limit(1)->find();
//        return $res;

        $res = Db::table('ims_bj_shopn_score_goods_property')->where($map)->limit(1)->find();
        return $res;
    }
    /**
     * 获取积分商品单个信息
     * @param $map
     */
    public function getScoreGood($map)
    {
        $res = Db::table('ims_bj_shopn_score_goods')->where($map)->limit(1)->find();
        return $res;
    }
    /**
     * 获取积分商品信息
     * @param array $map 查询条件
     * @return array
     */
    public function getScoreGoods($map = null,$map2 = null)
    {
        $map1['g.is_show'] = 1;
        $res = Db::table('ims_bj_shopn_score_goods g')
            ->join(['ims_bj_shopn_goods'=>'sg'],['g.goods_id = sg.pid'],'LEFT')
            ->field('g.limit_num,g.exchange_score,g.special_rule,sg.thumbhome goods_img,sg.title goods_title,sg.marketprice goods_price,sg.id goods_id')
            ->where($map)
            ->where($map1)
            ->where($map2)
            ->order('g.special_rule desc,g.create_time desc')
            ->select();
        return $res;
    }
    /**
     * 获取积分分类
     * @param array $maps 查询条件
     * @return array
     */
    public function getScoreCategorys($maps=null)
    {
        $map['is_show'] = 1;
        $res = Db::table('ims_bj_shopn_score_category')->where($map)->where($maps)->order('create_time desc')->select();
        return $res;
    }
    /**
     * 获取用户总积分
     * @param int $user_id 用户id
     * @param string $map 查询条件,多个,分割
     * @return array
     */
    public function getUserSumScore($user_id,$map=null)
    {
        $data = [];
        // 查询积分统计表
        $maps['user_id'] = $user_id;
        $ress = Db::table('think_sum_user')->where($maps)->limit(1)->find();
        if($map){
            $map = explode(',',$map);
            $maps['type'] = ['in',$map];
        }
        // $maps['log_time'] = ['>',$ress['missshop_scores_upd_time']];
        $res = Db::table('think_scores_record')->where($maps)->sum('scores');
        if($ress){
            $data['missshop_scores'] = $res;
            $data['missshop_scores_upd_time'] = date('Y-m-d H:i:s');
            $mapsr['id'] = $ress['id'];
            $ress = Db::table('think_sum_user')->where($mapsr)->update($data);
            $data['sum_scores'] = $data['missshop_scores'];
        }else{
            // 查询积分日志表
            $data = [
                'user_id' => $user_id,
                'missshop_scores' => $res,
                'missshop_scores_upd_time' => date('Y-m-d H:i:s'),
                'ins_time' => date('Y-m-d H:i:s')
            ];
            Db::table('think_sum_user')->insertGetId($data);
            $data['sum_scores'] = $data['missshop_scores'];
        }
        return $data;
    }
    /**
     * 计算积分规则
     * @param array $map 查询条件
     */
    public function scoreRule($map)
    {
        $score = 0;
        if(in_array($map['goods_id'],$this->missshop_id)){
            $score = floor($map['price'] / 20);
        }elseif(in_array($map['goods_id'],$this->missshop_double_id)){
            $score = floor($map['price'] / 20) * 2;
        }
        return $score;
    }

    /**
     * 添加积分记录
     * @param array $data
     * @return int|string
     */
    public function addScoresRecord($data)
    {
        $data_arr  = [
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'msg' => $data['msg'],
            'scores' => $data['scores'],
            'remark' => $data['remark'],
            'log_time' => date('Y-m-d H:i:s'),
        ];
        $res = Db::table('think_scores_record')->insertGetId($data_arr);
        return $res;
    }

    /**
     * 查询积分记录
     * @param array $map 查询条件
     * @return array
     */
    public function getScoresRecord($map)
    {
        $res = Db::table('think_scores_record')->where($map)->limit(1)->find();
        return $res;
    }
}