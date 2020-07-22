<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/4
 * Time: 10:23
 */

namespace app\api\controller\v4;
use app\api\controller\v3\Common;
use app\api\model\BwkItem;
use app\api\model\BwkItemLabel;
use app\api\model\TxAppoint;
use app\api\service\cont\StoreItem as StoreItemSer;
/*
 * 门店服务相关类
 *
 * */

class StoreItem extends Common
{
    // 单个门店项目服务对象
    protected $storeItemSer;

    /**
     * 初始化
     */
    public function _initialize()
    {
        parent::_initialize();
        // 获取单个实例 storeItemSer
        $this->storeItemSer = new StoreItemSer();
    }
    /**
     * 服务分类[3.立即选购]
     * @param int $store_id 门店id
     */
    public function itemCategory()
    {
        $arr['store_id'] = input('store_id');
        // 门店有服务项目才显示
        $flag = config('text.serviceitem_flag');
        // 测试技术门店开放
        if($flag == 0 && $arr['store_id']!=2){
            $this->rest['code'] = 1;
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '其它门店暂未开放服务项目';
        }else{
            $map['store_id'] = $arr['store_id'];
            $res_num = BwkItem::where($map)->count();
            if($res_num){
                $res['cate_id'] = config('text.item_cate_id');
                $res['cate_img'] = config('img.item_cate_img');
                $this->rest['data'] = $res;
            }else{
                $this->rest['data'] = (object)[];
                $this->rest['msg'] = '暂无数据';
            }
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 服务列表[2.服务项目]
     * @param int $store_id 门店id
     * @param int $label_id 标签id
     */
    public function itemList()
    {
        $arr['store_id'] = input('store_id');
        $arr['label_id'] = input('label_id',1);
        // 分类图片,分类列表[标签名,标签id],商品列表[商品id,商品封面图,商品名称,项目时长,订购人数,价格]
        $map['store_id'] = $arr['store_id'];
        $map['is_delete'] = 0;
        $map['status'] = 1;
        // 标签列表
        $res_label = BwkItemLabel::all(function($query){
            $mapl['isshow'] = 1;
            $query->field('id,label_name')->where($mapl)->order('create_time asc');
        });
        $rest['cate_img'] = config('img.item_cate_img');
        if($res_label){
            foreach ($res_label as $v) {
                $res_l['label_id'] = $v['id'];
                $res_l['label_name'] = $v['label_name'];
                $rest['label_list'][] = $res_l;
            }
        }
        $res = $this->storeItemSer->itemList($map);
        if($res){
            foreach ($res as $v) {
                $res_i['item_id'] = $v['id'];
                $res_i['label_id'] = $v['label_id'];
                $res_i['item_name'] = $v['item_name'];
                $res_i['item_img'] = $v['item_img'];
                $res_i['duration'] = $v['duration'];
                $res_i['item_price'] = $v['item_price'];
                $res_i['buy_num'] = $v['buy_num'];
                // 订购人数
                // $mapn['status'] = ['in',[1,2]];
                // $mapn['service_id'] = $v['item_id'];
                // $res_num = TxAppoint::where($mapn)->count();
                // $res_i['buy_num'] = $res_num.'人订购';
                $rest['item_list'][] = $res_i;
            }
            $rest['item_list'] = sortField($rest['item_list'],'buy_num');
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 服务列表[4.项目详情]
     * @param int $item_id 项目id
     * @param int $user_id 用户id
     */
    public function itemDetail()
    {
        $arr['item_id'] = input('item_id');
        $arr['user_id'] = input('user_id');
        $rest = [];
        $map['id'] = $arr['item_id'];
        $res = $this->storeItemSer->itemDetail($map);
        if($res){
            unset($res['item_img']);
            $rest = $res;
            $map1['item_id'] = $arr['item_id'];
            $rest['comment_num'] = $this->storeItemSer->itemCommentNum($arr['item_id']);
            $rest['see_num'] = $this->storeItemSer->itemSeeNum($arr['item_id']);
            $map1['item_id'] = $arr['item_id'];
            $rest['label_list'] = $this->storeItemSer->itemEvaluateLabel($map1);
            $arr['see_num'] = $rest['see_num'];
            $rest['comment']  = $this->storeItemSer->itemEvaluate($arr);
        }
        // if($res){
        //     unset($res['item_img']);
        //     // 浏览次数+1
        //     $rest = $res;
        //     $rest['comment_num'] = $stoser->itemCommentNum($arr['item_id']);
        //     $rest['see_num'] = $stoser->itemSeeNum($arr['item_id']);
        //     $map1['item_id'] = $arr['item_id'];
        //     $rest['label_list'] = $stoser->itemEvaluateLabel($map1);
        //     $arr['see_num'] = isset($rest['see_num'])?$rest['see_num']:0;
        //     $rest['comment']  = $stoser->itemEvaluate($arr);

        // }
        if($rest){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 服务列表[4.项目详情-评论点赞]
     * @param int $comment_id 评论id
     * @param int $user_id 用户id
     */
    public function commentGiveup()
    {
        $arr['comment_id'] = input('comment_id');
        $arr['user_id'] = input('user_id');

        $stoser = new \app\api\service\StoreItem();
        $res = $stoser->commentGiveup($arr['comment_id'],$arr['user_id']);
        if($res['flag']){
            $this->rest['msg'] = $res['msg'];
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 服务列表[4.项目详情-添加评论]
     * @param int $comment_content 评论内容
     * @param int $user_id 用户id
     * @param int $item_id 项目id
     * @param int $comment_pid 上级评论id
     * @param int $label_id 评论标签id
     */
    public function commentAdd()
    {
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');
        $arr['appoint_id'] = input('appoint_id',0);
        $arr['item_id'] = input('item_id');
        $arr['mrs_level'] = input('mrs_level',0);
        $arr['service_level'] = input('service_level',0);
        $arr['environment_level'] = input('environment_level',0);
        $arr['eva_pid'] = input('comment_pid');
        $arr['eva_content'] = input('content');
        $arr['evlabel_id'] = input('label_id',1);
        $arr['eva_imgs'] = input('comment_imgs',[]);// 多个用json[]
        // 用户id,门店id,预约id,项目id,美容师评分,环境评分,服务评分,评论内容,评论图片[]
        $stoser = new \app\api\service\StoreItem();
        $res = $stoser->commentAdd($arr);
        if($res){
            $this->rest['msg'] = '评论成功';
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '评论失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 结算中心
     * @param int $store_id 门店id
     * @param int $item_id 项目id
     * @return
     */
    public function getSettlement()
    {
        $arr['store_id'] = input('store_id');
        $arr['item_id'] = input('item_id');
        $rest = [
            'store_name' => '',
            'address' => '',
            'store_tel' => '',
            'item_id' => 0,
            'item_name' => '',
            'item_img' => '',
            'duration' => 0,
            'item_price' => '0.00',
            'buy_num' => 0,
            'num' => 1,
            'sum_price' => '0.00',
        ];

        $stoser = new \app\api\service\StoreItem();
        // 门店信息
        $map1['id'] = $arr['store_id'];
        $res1 = $stoser->storeInfo($map1);
        if($res1){
            $rest['store_name'] = $res1['title'];
            $rest['address'] = $res1['address'];
            $rest['store_tel'] = $res1['tel'];
        }
        // 商品信息
        $map2['id'] = $arr['item_id'];
        // $res2 = $stoser->itemDetail($map2);
        $res2 = $this->storeItemSer->itemDetail($map2);
        if($res2){
            $rest['item_id'] = $res2['item_id'];
            $rest['item_name'] = $res2['item_name'];
            $rest['item_img'] = $res2['item_img'];
            $rest['duration'] = $res2['duration'];
            $rest['item_price'] = $res2['item_price'];
            $rest['buy_num'] = $res2['buy_num'];
            $rest['num'] = 1;
            $rest['sum_price'] = sprintf("%.2f",$rest['item_price'] * $rest['num']);
        }
        if($rest){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 5.结算中心-预约美容师列表
     * @param int $store_id 门店id
     * @return
     */
    public function mrsList()
    {
        $arr['store_id'] = input('store_id');
        // 美容师列表
        $rest = [];
        $map3['m.storeid'] = $arr['store_id'];
        // $stoser = new \app\api\service\StoreItem();
        // $res3 = $stoser->mrsList($map3);
        $res3 = $this->storeItemSer->mrsList($map3);
        if($res3){
            foreach ($res3 as $v) {
                $res31['user_id'] = $v['id'];
                $res31['user_name'] = $v['realname'];
                $res31['head_img'] = $v['avatar']==''?config('img.head_img'):$v['avatar'];
                $res31['mrs_level'] = $v['mrs_level'];
                $res31['good_at'] = $v['good_at'];
                $rest[] = $res31;
            }
        }
        if($rest){
            // 按美容师评分排序
            $rest = sortField($rest,'mrs_level');
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = [];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 5.结算中心-预约时间
     * @return \think\response\Json
     */
    public function appointTimeList()
    {
        $arr['item_id'] = input('item_id');
        $arr['store_id'] = input('store_id');
        $arr['mrs_id'] = input('mrs_id');
        $arr['appoint_time'] = input('appoint_time')==''?date('Y-m-d'):date('Y-m-d',strtotime(input('appoint_time')));// 默认当前时间
        $stoser = new \app\api\service\StoreItem();
        $rest = $stoser->appointTimeList($arr);
        if($rest){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 5.生成订单
     * @return mixed
     */
    public function makeOrder()
    {
        $arr['item_id'] = input('item_id');
        $arr['item_num'] = input('item_num',1);
        $arr['pay_price'] = input('pay_price');
        $arr['user_id'] = input('user_id');
        $arr['store_id'] = input('store_id');
        $arr['mrs_id'] = input('mrs_id');
        $arr['appoint_time'] = input('appoint_time');
        $arr['remark'] = input('remark');
        $arr['id_interestrate'] = input('id_interestrate');
        $rest = [];
        $stoser = new \app\api\service\StoreItem();
        $rest = $stoser->createAppoint($arr);
        if($rest){
            $this->rest['data']['appoint_id'] = $rest;
            $this->rest['msg'] = '生成订单成功';
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '生成订单失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 6.订单列表
     * @return mixed
     */
    public function orderList()
    {
        $arr['user_id'] = input('user_id');
        $arr['status'] = input('status',100);
        $arr['page'] = input('page',1);
        if($arr['page']<1){
            $arr['page'] = 1;
        }
        $rest = [];
        // $stoser = new \app\api\service\StoreItem();
        $map['a.user_id'] = $arr['user_id'];
        if($arr['status']!=100){
            $map['a.status'] = $arr['status'];
            // 已完成订单 包含[2,3]
            if($map['a.status'] == 2){
                $map['a.status'] = ['in',[2,3]];
            }
        }
        // $rest = $stoser->appointList($map,$arr['page']);
        $rest = $this->storeItemSer->appointList($map,$arr['page']);
        if($rest){
            foreach ($rest as $k=>$v) {
                unset($rest[$k]['service_id']);
            }
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = [];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 8.订单详情|10.订单详情-已评价
     * @return mixed
     */
    public function orderDetail()
    {
        $arr['appoint_id'] = input('appoint_id');
        $arr['user_id'] = input('user_id');
        $rest = [];
        $stoser = new \app\api\service\StoreItem();
        $rest = $stoser->ordDetail($arr['appoint_id'],$arr['user_id']);
        if($rest){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 6.订单支付
     * @return mixed
     */
    public function orderPay()
    {
        $arr['appoint_id'] = input('appoint_id');
        $arr['user_id'] = input('user_id');
        $stoser = new \app\api\service\StoreItem();
        $rest = $stoser->ordPay($arr['appoint_id'],$arr['user_id']);
        if($rest){
            $this->rest['data'] = $rest;
        }else{
            $this->rest['data'] = (object)[];
            $this->rest['msg'] = '获取支付失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 5.订单-状态修改
     * @return mixed
     */
    public function orderStatusUpd()
    {
        $arr['appoint_id'] = input('appoint_id');
        $arr['status'] = input('status');
        $stoser = new \app\api\service\StoreItem();
        if($arr['status'] == -1){
            $map['status'] = 0;
        }elseif($arr['status'] == -2){
            $map['status'] = -1;
        }
        $map['id'] = $arr['appoint_id'];
        $data['status'] = $arr['status'];
        $rest = $stoser->orderUpd($data,$map);
        if($rest){
            $this->rest['msg'] = '修改成功';
        }else{
            $this->rest['msg'] = '修改失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 5.支付宝支付签名测试
     * @return mixed
     */
    public function aliaPay()
    {
        $stoser = new \app\api\service\StoreItem();
        $rest = $stoser->aliaPay();
        $this->rest['data'] = $rest ;
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}