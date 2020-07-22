<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/5/29
 * Time: 17:15
 */

namespace app\api\service\cont;
use app\api\controller\SerNotice;
use app\api\model\ApiVisitlog;
use app\api\model\AppointLevel;
use app\api\model\BankInterestrate;
use app\api\model\Branch;
use app\api\model\BwkItem;
use app\api\model\BwkItemEvalabel;
use app\api\model\BwkItemEvaluate;
use app\api\model\Common;
use app\api\model\ItemPaylog;
use app\api\model\TxAppoint;
use app\api\model\TxDkConf;
use app\api\model\User;
use app\api\service\SingleSer;

/**
 * 控制器服务类
 * @package app\api\service\cont
 */
class StoreItem
{
    //定义一个私有化静态变量用来判断是否为新对象
//    private static $instance;
    // 返回数据
    private $rest = [];

    //私有化构造函数
//    private function __construct()
//    {
//
//    }
    /**
     * 获取单个门店项目服务对象单例
     * @return object
     */
//    public static function getInstance()
//    {
//        if(empty(self::$instance instanceof self)){
//            self::$instance = new self();
//        }
//        return self::$instance;
//    }

    /**
     * 项目服务分类
     * @param array $map 查询条件
     * @return array
     */
    public function itemCategory($map)
    {
        $res_num = BwkItem::where($map)->count();
        if($res_num){
            $res['cate_id'] = config('text.item_cate_id');
            $res['cate_img'] = config('img.item_cate_img');
            $this->rest = $res;
        }
        return $this->rest;
    }
    /**
     * 服务标签列表
     * @param array $map 查询条件
     * @return array
     */
    public function itemLabels($map)
    {
        $this->rest = [];
        $res_label = BwkItemLabel::where($map)->field('id,label_name')->select();
        if($res_label){
            foreach ($res_label as $v) {
                $res_l['label_id'] = $v['id'];
                $res_l['label_name'] = $v['label_name'];
                $this->rest[] = $res_l;
            }
        }
        return $this->rest;
    }
    /**
     * 门店项目信息列表
     * @param array $map 查询条件
     * @return
     */
    public function itemList($map)
    {
        $res = BwkItem::where($map)
            ->field('id,store_id,item_name,item_img,is_delete,status,color,duration,item_price,item_detail,item_wheplan_img,item_detail_img,label_id,id_interestrate,create_time')
            ->order('id desc')
            ->select();
        if($res){
            $service_id = null;
            foreach ($res as $k=>$v) {
                $item_wheplan_img = [];
                if($v['item_wheplan_img']){
                    $item_wheplan_img = explode(',',$v['item_wheplan_img']);
                }
                $res[$k]['item_wheplan_img'] = $item_wheplan_img;
                $item_detail_img = [];
                if($v['item_detail_img']){
                    $item_detail_img = explode(',',$v['item_detail_img']);
                }
                $res[$k]['item_detail_img'] = $item_detail_img;
                $res[$k]['duration'] = $res[$k]['duration'].'分钟';
                $res[$k]['buy_num'] = '0人订购';

                $service_id[] = $v['id'];
            }
            if($service_id){
                $mapb['service_id'] = ['in',$service_id];
                $mapb['status'] = ['in',[1,2,3]];
                $res_buy = $this->itemBuyNum($mapb);
                if($res_buy){
                    foreach ($res as $k=>$v) {
                        foreach ($res_buy as $vb) {
                            if($vb['service_id'] == $v['id']){
                                $res[$k]['buy_num'] = $vb['cnt'].'人订购';
                            }
                        }
                    }

                }
            }
        }
        return $res;
    }
    /**
     * 门店项目购买人数
     * @param array $map 查询条件[ service_id=>项目id]
     * @return
     */
    public function itemBuyNum($map)
    {
        $res = TxAppoint::where($map)->field('service_id,count(id) cnt')->group('service_id')->select();
        return $res;
    }
    /**
     * 商品详情
     * @param $map 查询条件
     * @return array
     */
    public function itemDetail($map)
    {
        $rest = [];
        $res_bw = $this->itemList($map);
        if($res_bw){
            $res = $res_bw[0];
            $rest['item_id'] = $res['id'];
            $rest['item_name'] = $res['item_name'];
            $rest['item_wheplan_img'] = $res['item_wheplan_img'];
            $rest['item_img'] = $res['item_img'];
            $rest['duration'] = $res['duration'];
            $rest['item_price'] = $res['item_price'];
            $rest['item_detail_img'] = $res['item_detail_img'];
            $rest['buy_num'] = $res['buy_num'];
            $rest['bank_list'] = [];
            if($res['id_interestrate']){
                $mapi['id_interestrate'] = ['in',explode(',',$res['id_interestrate'])];
                $res_bank = $this->payTypeList($mapi,$rest['item_price']);
                if($res_bank){
                    $rest['bank_list'] = $res_bank;
                }
            }
        }
        return $rest;
    }
    /**
     * 支付方式列表-支付银行
     * @param array $map 查询条件
     * @param string $item_price 商品价格
     * @return array
     */
    public function payTypeList($map,$item_price=0)
    {
        $res1 = [];
        $this->rest = [];
        // 支付银行列表
        $res_bank = BankInterestrate::alias('bi')->join(['sys_bank'=>'sb'],['bi.id_bank=sb.id_bank'],'LEFT')->field('sb.id_bank,sb.st_abbre_bankname,sb.st_bnkpic1,sb.st_bnkpic2,bi.no_period')->where($map)->group('sb.id_bank')->select();
        if($res_bank){
            $banks = [];
            foreach ($res_bank as $v) {
                $banks[] = $v['id_bank'];
            }
            if($banks){
                // 支付分期列表
                $map['sb.id_bank'] = ['in',$banks];
                $res_i = BankInterestrate::alias('bi')->join(['sys_bank'=>'sb'],['bi.id_bank=sb.id_bank'],'LEFT')->field('sb.id_bank,sb.st_abbre_bankname,sb.st_bnkpic1,sb.st_bnkpic2,bi.no_period,bi.id_interestrate')->where($map)->select();
                if($res_i){
                    foreach ($res_bank as $v) {
                        $res1['fenqi'] = [];
                        $res1['id_bank'] = $v['id_bank'];
                        $res1['bank_name'] = $v['st_abbre_bankname'];
                        $res1['bank_img'] = $v['st_bnkpic1'];
                        foreach ($res_i as $vi) {
                            if($vi['id_bank'] == $v['id_bank']){
                                $res11['id_interestrate'] = $vi['id_interestrate'];
                                $res11['no_period'] = $vi['no_period']==0?1:$vi['no_period'];
                                if($vi['no_period']>1){
                                    $res11['evpprice'] = round($item_price / $vi['no_period'],2);// 每期价格保留2位小数
                                }else{
                                    $res11['evpprice'] = round($item_price,2) ;
                                }
                                $res11['evpprice'] = sprintf("%.2f",$res11['evpprice']);
                                $res1['fenqi'][] = $res11;
                            }
                        }
                        $this->rest[] = $res1;
                    }
                }
            }
        }
        return $this->rest;
    }
    /**
     * 项目详情-评论总次数
     * @param int $item_id 项目id
     * @return
     */
    public function itemCommentNum($item_id)
    {
        $rest = 0;
        $map['item_id'] = $item_id;
        $map['eva_pid'] = 0;
        $rest = BwkItemEvaluate::where($map)->count();
        return $rest;
    }
    /**
     * 项目详情-浏览总次数
     * @param int $item_id 项目id
     * @return
     */
    public function itemSeeNum($item_id)
    {
        $rest = 0;
        $map['api_url'] = 'itemDetail';
        $map['item_id'] = $item_id;
        $res = ApiVisitlog::where($map)->field('id,visit_num')->find();
        if($res){
            ApiVisitlog::where($map)->setInc('visit_num');
            $rest = $res['visit_num']+1;
        }else{
            $data['api_url'] = 'itemDetail';
            $data['item_id'] = $item_id;
            $data['explain'] = '门店项目详情API';
            $data['create_time'] = date('Y-m-d H:i:s');
            ApiVisitlog::create($data);
            $rest = 1;
        }
        return $rest;
    }
    /**
     * 项目评论-标签列表
     * @param $map[item_id 项目id]
     * @return
     */
    public function itemEvaluateLabel($map)
    {
        $res = BwkItemEvalabel::all(function($query){
            $query->field('id label_id,evl_name label_name')->order('create_time asc');
        });
        if($res){
            foreach ($res as $k=>$v) {
                $mape['item_id'] = $map['item_id'];
                $mape['eva_pid'] = 0;// 全部只统计一级评论数
                // 每个标签下评论数
                $res_evlu = 0;
                if($v['label_id'] < 2){
                    $res_evlu = BwkItemEvaluate::where($mape)->count();
                }else if($v['label_id']>4){
                    $mape1 = ' eva_content like \'%'.$v['label_name'].'%\'';
                    $res_evlu = BwkItemEvaluate::where($mape)->where($mape1)->count();
                }elseif($v['label_id']==2){
                    $dt = date('Y-m-d H:i:s',strtotime('-3 day'));// 最新评论3天前
                    $mape1['create_time'] = ['>=',$dt];
                    $res_evlu = BwkItemEvaluate::where($mape)->where($mape1)->count();
                }elseif($v['label_id']==3){
                    // 好评4分及以上
                    $mape1 = ' sum(environment_level+mrs_level+service_level) >= 12 ';
                    $res_evlu = BwkItemEvaluate::where($mape)->group('id')->having($mape1)->count();
                }elseif($v['label_id']==4){
                    // 差评3分及以下
                    $mape1 = ' sum(environment_level+mrs_level+service_level) < 12 ';
                    $res_evlu = BwkItemEvaluate::where($mape)->group('id')->having($mape1)->count();
                }
                $res[$k]['num'] = $res_evlu;
            }
        }
        return $res;
    }
    /**
     * 项目评论-评论列表
     * @param $arr[item_id 项目id,user_id 用户id,see_num 浏览数]
     * @return
     */
    public function itemEvaluate($arr)
    {
        $rest = [];
        $map['b.item_id'] = $arr['item_id'];
        $map['b.eva_pid'] = 0;
        // 一级评论
        $res = BwkItemEvaluate::alias('b')->join(['ims_bj_shopn_member'=>'m'],['m.id=b.user_id'],'LEFT')->join(['ims_fans'=>'f'],['m.id=f.id_member'],'LEFT')->where($map)->field('b.id,b.item_id,b.user_id,b.eva_pid,b.eva_content,b.create_time,m.realname,b.giveup_users,b.eva_pid,f.avatar,b.create_time,b.evlabel_id,b.eva_imgs,b.mrs_level,b.environment_level,b.service_level,b.orig_id')->group('b.id')->order('b.create_time desc')->select();
        if($res){
            $user_ids=[];$orig_ids=[];$eva_pids=[];
            foreach ($res as $k=>$v) {
                // 评论id,用户id,用户名,用户头像,评分,评论日期,评论内容
                $res1['comment_id'] = $v['id'];
                $res1['user_id'] = $v['user_id'];
                $res1['user_name'] = $v['realname'];
                $res1['head_img'] = $v['avatar']==''?config('img.head_img'):$v['avatar'];
                $res1['service_level'] = 0;
                $res1['label_id'] = [1];

                $map_2['s.service_id'] = $v['item_id'];
                $map_2['a.user_id'] = $v['user_id'];
                // 用户评分
                $res1['service_level'] = round(($v['mrs_level']+$v['environment_level']+$v['service_level'])/3,1);
                $res1['comment_time'] = date('Y-m-d',strtotime($v['create_time']));

                $res_label = $this->getLabelByComment($res1['service_level'],$v['eva_content'],$res1['comment_time']);
                if($res_label){
                    $res1['label_id'] = $res_label;
                }

                $res1['comment_content'] = $v['eva_content'];
                $res1['see_num'] = isset($arr['see_num'])?$arr['see_num']:0;
                $res1['comment_imgs'] = [];
                if($v['eva_imgs']){
                    $res1['comment_imgs'] = explode(',',$v['eva_imgs']);
                }

                // 点赞用户列表
                $mapv = null;$res_dz = null;
                $res1['giveup_users'] = [];
                if($v['giveup_users']){
                    $users = explode(',',$v['giveup_users']);
                    $user_ids = array_merge($user_ids,$users);
                    $user_ids = array_unique($user_ids);
                    $res1['giveup_users'] = $users;
                }
                // 1点赞 0未点赞
                $res1['is_giveup'] = 0;
                $res1['orig_id'] = $v['orig_id'];
                if($v['giveup_users'] && strstr($v['giveup_users'],$arr['user_id'])){
                    $res1['is_giveup'] = 1;
                }
                // 二级评论及下面多级评论 列表 用户id,用户名,用户评论,回复用户id,回复用户名
                $orig_ids[] = $v['id'];
                $res1['comment_list'] = [];
                $rest[] = $res1;
            }
            // 点赞用户列表
            if($user_ids){
                $mapu['id'] = ['in',$user_ids];
                $res_users = $this->giveUpUser($mapu);
                if($res_users){
                    foreach ($rest as $k=>$v) {
                        $rest[$k]['giveup_users'] = [];
                        foreach ($res_users as $vu) {
                            if($v['giveup_users'] && in_array($vu['id'],$v['giveup_users'])){
                                $rest[$k]['giveup_users'][] = $vu['realname'];
                            }
                        }
                    }
                }
            }
            // 二级及多级评论列表
            if($orig_ids){
                $mape['orig_id'] = ['in',$orig_ids];
                $res_pid = $this->moreEvaluates($mape);
                $res1['comment_list'] = [];
                if($res_pid){
                    foreach ($rest as $k=>$v) {
                        $res1['comment_list'] = [];
                        foreach ($res_pid as $v_pid) {
                            if($v['comment_id'] == $v_pid['orig_id']){
                                $res2['user_id'] = $v_pid['user_id'];
                                $res2['user_name'] = $v_pid['realname'];
                                $res2['comment_id'] = $v_pid['id'];
                                $res2['comment_content'] = $v_pid['eva_content'];
                                $res2['reply_user_id'] = 0;
                                $res2['reply_user_name'] = '';
                                $res2['eva_pid'] = $v_pid['eva_pid'];
                                if ($v_pid['eva_pid'] != $v['comment_id']) {
                                    $eva_pids[] = $v_pid['eva_pid'];
                                }
                                $res1['comment_list'][] = $res2;
                            }
                        }
                        $rest[$k]['comment_list'] = $res1['comment_list'];
                    }
                }
            }
            // 二级及多级评论回复人姓名
            if($eva_pids){
                $mapp['b.id'] = ['in',$eva_pids];
                $res_reply = $this->pidReply($mapp);
                if($res_reply){
                    foreach ($rest as $k=>$v) {
                        foreach ($v['comment_list'] as $kc=>$vc) {
                            foreach ($res_reply as $vp) {
                                if ($vp['id'] == $vc['eva_pid']) {
                                    $rest[$k]['comment_list'][$kc]['reply_user_id'] = $vp['user_id'];
                                    $rest[$k]['comment_list'][$kc]['reply_user_name'] = $vp['realname'];
                                }
                            }
                            unset($rest[$k]['comment_list'][$kc]['eva_pid']);
                        }
                        unset($rest[$k]['orig_id']);
                    }
                }
            }
        }
        return $rest;
    }
    /**
     * 根据评分查询对应的标签
     * @params int $service_level 评分
     * @params string $content 评论内容
     * @params string $dt 评论时间
     * @return array 标签id
     */
    public function getLabelByComment($service_level,$content='',$dt='')
    {
        $arr_label = [1];
        // 好评4分及以上,差评3分及一下
        if($service_level>=4){
            $arr_label[] = 3;
        }else{
            $arr_label[] = 4;
        }

        // 内容标签是否包含
        if($content){
            $arr1 = [5=>'手法独到',6=>'干净卫生',7=>'老师奈斯'];
            foreach ($arr1 as $k=>$v) {
                if(strstr($content,$v)){
                    $arr_label[] = $v;
                }
            }
        }

        // 最新点评,3天以内
        if($dt){
            $dt1 = time()-(strtotime($dt)+3600*24*3);
            if($dt1<0){
                $arr_label[] = 2;
            }
        }
        return $arr_label;
    }
    /**
     * 点赞用户列表
     * @params array $map 查询条件 [id=>用户id]
     * @return array
     */
    public function giveUpUser($map)
    {
//        echo '<pre>';print_r($map);die;
        $res = User::where($map)->field('id,realname')->order('id desc')->select();
//        echo '<pre>';print_r($res);die;
        return $res;
    }
    /**
     * 二级及多级评论列表
     * @params array $map 查询条件 [id=>用户id]
     * @return array
     */
    public function moreEvaluates($map)
    {
        $res = BwkItemEvaluate::alias('b')->join(['ims_bj_shopn_member'=>'m'],['m.id=b.user_id'],'LEFT')->where($map)->field('b.id,b.user_id,m.realname,b.eva_pid,b.evlabel_id,b.eva_content,b.eva_level,b.orig_id')->group('b.id')->order('b.create_time asc')->select();
        return $res;
    }
    /**
     * 上级回复人
     * @params array $map 查询条件 [id=>用户id]
     * @return array
     */
    public function pidReply($map)
    {
        $res =  BwkItemEvaluate::alias('b')->join(['ims_bj_shopn_member'=>'m'],['m.id=b.user_id'],'LEFT')->where($map)->field('b.id,b.user_id,m.realname')->group('b.id')->order('b.create_time asc')->select();
        return $res;
    }
    /**
     * 项目详情-评论点赞
     * @param int $comment_id 评论id
     * @param int $user_id 用户id
     * @return
     */
    public function commentGiveup($comment_id,$user_id)
    {
        $map['id'] = $comment_id;
        $res = BwkItemEvaluate::where($map)->field('id,giveup_users')->find();
        if($res){
            if($res['giveup_users']){
                $data['giveup_users'] = explode(',',$res['giveup_users']);// 数组
            }
            // 已赞,双击取消
            if(strstr($res['giveup_users'],$user_id)){
                foreach ($data['giveup_users'] as $k=>$v) {
                    if($v == $user_id){
                        unset($data['giveup_users'][$k]);
                        $res['msg'] = '取消成功';
                    }
                }
            }else{
                // 点赞添加
                $data['giveup_users'][] = $user_id;
                $res['msg'] = '点赞成功';
            }
            $data['giveup_users'] = implode(',',$data['giveup_users']);
            $map1['id'] = $res['id'];
            $res['flag'] = BwkItemEvaluate::where($map1)->update($data);
        }
        return $res;
    }
    /**
     * 项目详情-评论添加
     * @param array $arr []
     * @return
     */
    public function commentAdd($arr)
    {
        $arr['create_time'] = date('Y-m-d H:i:s');
        // 评分表记录
        $arr2['user_id'] = $arr['user_id'];
        $arr2['appoint_id'] = $arr['appoint_id'];
        if($arr2['appoint_id']){
            $sinser = new SingleSer();
            $map3['id'] = $arr['appoint_id'];
            $res3 = $sinser->getTxAppoint($map3);
            if($res3){
                $arr2['mrs_id'] = $res3[0]['mrs_id'];
            }
        }
        $arr2['environment_level'] = $arr['environment_level'];
        $arr2['mrs_level'] = $arr['mrs_level'];
        $arr2['service_level'] = $arr['service_level'];
        $arr2['create_time'] = $arr['create_time'];
        $res2 = AppointLevel::create($arr2);
        // 评论表记录
        $arr1['item_id'] = $arr['item_id'];
        $arr1['user_id'] = $arr['user_id'];
        $arr1['eva_pid'] = $arr['eva_pid'];
        $arr1['evlabel_id'] = $arr['evlabel_id'];
        $arr1['eva_content'] = $arr['eva_content'];
        if($arr['eva_imgs']){
            $arr['eva_imgs'] = json_decode($arr['eva_imgs'],true);
            $arr1['eva_imgs'] = implode(',',$arr['eva_imgs']);
        }
        $arr1['create_time'] = $arr['create_time'];
        $arr1['appoint_id'] = $arr['appoint_id'];
        $arr1['environment_level'] = $arr['environment_level'];
        $arr1['mrs_level'] = $arr['mrs_level'];
        $arr1['service_level'] = $arr['service_level'];
        if($arr['eva_pid']){
            $map['id'] = $arr['eva_pid'];
            $res1 = BwkItemEvaluate::where($map)->field('id,orig_id,eva_level')->find();
            if($res1){
                $arr1['orig_id'] = $res1['orig_id']==0?$res1['id']:$res1['orig_id'];
                $arr1['eva_level'] = $res1['eva_level']+1;
            }
        }
        $res = BwkItemEvaluate::create($arr1);
        // 如果有项目id,则更新服务确认时间
        if(isset($arr['appoint_id']) && $arr['appoint_id']){
            $datat['complete_time'] = time();
            $datat['status'] = 3;
            $mapt['id'] = $arr['appoint_id'];
            TxAppoint::update($datat,$mapt);
        }
        return $res;
    }
    /**
     * 门店信息
     * @param array $map 查询条件
     * @return
     */
    public function storeInfo($map)
    {
        $res = Branch::where($map)->field('id,title,address,tel')->find();
        return $res;
    }

    /**
     * 查询门店下的所有美容师
     * @param array $map 查询条件
     */
    public function mrsList($map)
    {
        // 美容师列表
        $map1 = ' length(m.code)>0 and m.id=m.staffid ';
        $res = User::alias('m')
                ->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')
                ->field('m.id,f.avatar,m.realname,m.mobile,f.good_at')
                ->where($map)
                ->where($map1)
                ->group('m.id')
                ->order('m.id desc')
                ->select();
        if($res){
            $mrs_ids = [];
            foreach ($res as $k=>$v) {

                // 美容师平均分 查找美容师对应的订单,查找订单对应的评价
                $res1['mrs_level'] = 0;
                $mrs_ids[] = $v['id'];
                $res1['mrs_level'] = round($res1['mrs_level'] ,1);
                $res1['good_at'] = '';
                if($v['good_at'] && !is_null($v['good_at'])){
                    $res1['good_at'] = $v['good_at'];
                }
                $res[$k]['mrs_level'] = $res1['mrs_level'];
                $res[$k]['good_at'] = $res1['good_at'];
            }
            if($mrs_ids){
                $maple['a.mrs_id'] = ['in',$mrs_ids];
                $maple['e.eva_pid'] = 0;
                $res_ml = $this->mrsAvgLevel($maple);
                if($res_ml){
                    foreach ($res as $k=>$v) {
                        foreach ($res_ml as $vm) {
                            if($vm['mrs_id'] == $v['id']){
                                $res[$k]['mrs_level'] = round($vm['mrs_level'],1);
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }

    /**
     * 查询门店下的美容师平均分
     * @param array $map 查询条件
     */
    public function mrsAvgLevel($map)
    {
        $res = null;
        $res = BwkItemEvaluate::alias('e')->join(['store_tx_appoint'=>'a'],['a.id=e.appoint_id'],'LEFT')->where($map)->field('a.mrs_id,avg(e.mrs_level) mrs_level')->group('a.mrs_id')->select();
        return $res;
    }
    /**
     * 选择预约时间,已选择项目和美容师
     * @param array $map 查询条件
     */
    public function appointTimeList($map)
    {
        $rest = [];
        $map1['m.id'] = $map['mrs_id'];
        $res1 = $this->mrsList($map1);
        if($res1){
            $rest['user_name'] = $res1[0]['realname'];
            $rest['head_img'] = $res1[0]['avatar']==''?config('img.head_img'):$res1[0]['avatar'];
            $rest['mrs_level'] = 0;

            $mapm['a.mrs_id'] = $map['mrs_id'];
            $mapm['e.eva_pid'] = 0;
            // 美容师平均分 查找美容师对应的订单,查找订单对应的评价
            $rest['mrs_level'] = round($res1[0]['mrs_level'] ,1);

            $rest['appoint_time'] = $map['appoint_time'];
            $rest['good_at'] = $res1[0]['good_at'];
        }

        // 按门店上下班时间分段
        $mapd['storeid'] = $map['store_id'];
        $res_md_dk = TxDkConf::get(function($query) use($mapd){
            $mapd1['storeid'] = 0;
            $query->where($mapd)->whereOr($mapd1)->order('storeid desc');
        });
        $res_sc3 = [];
        if($res_md_dk){
            $arr['appoint_time'] = $map['appoint_time'];
            $arr1['dk_begin_time'] = strtotime(date('Y-m-d',strtotime($map['appoint_time'])).' '.$res_md_dk['dk_begin_time']);
            $arr['appoint_time2'] = date('Y-m-d',strtotime($map['appoint_time'])+3600*24);// 已选择时间+1天
            $arr1['dk_end_time'] = strtotime(date('Y-m-d',strtotime($arr['appoint_time'])).' '.$res_md_dk['dk_end_time']);
            // 查询项目所需时长
            $mapb['id'] = $map['item_id'];
            $res_sc = BwkItem::get($mapb);
            if($res_sc){
                $duration = $res_sc['duration'];//时长-分钟
                $mapa['mrs_id'] = $map['mrs_id'];
                $mapa['appoint_time'] = ['>',$arr['appoint_time']];
                $mapa['appoint_time2'] = $arr['appoint_time2'];
                $res_a = TxAppoint::all(function($query) use($mapa){
                    $mapa['status'] = ['in',[0,1,2]];
                    $mapa1['appoint_time'] = ['<',$mapa['appoint_time2']];
                    unset($mapa['appoint_time2']);
                    $query->where($mapa)->where($mapa1)->order('appoint_time asc');
                });
                $arr4 = [];
                for($t=$arr1['dk_begin_time'];$t<$arr1['dk_end_time'];$t = $t + $duration*60){
                    $arr2['dt'] = date('H:i',$t);
                    $arr2['is_use'] = 0;// 是否占用 0未占用 1已占用
                    $arr2['dt1'] = $t;
                    $arr2['dt2'] =  $t + $duration*60;
                    $arr4[] = $arr2;
                }
                if($arr4){
                    foreach ($arr4 as $v) {
                        foreach ($res_a as $v1) {
                            $arr3['appoint_time'] = strtotime($v1['appoint_time']);
                            $arr3['appoint_time2'] = strtotime($v1['appoint_time']) + $duration*60;
//                                echo '<pre>';print_r($arr3);
//                                echo '<pre>';print_r($v);
//                                die;
                            // 预约时间在起始时间内
                            if($arr3['appoint_time'] == $v['dt1']){
                                $v['is_use'] = 1;
                            }
                        }
                        $res_sc2['dt'] = $v['dt'];
                        $res_sc2['is_use'] = $v['is_use'];
                        $res_sc3[] = $res_sc2;
                    }
                }
            }
        }
        if($res_sc3){
            $rest['time_list'] = $res_sc3;
        }
        return $rest;
    }
    /**
     * 生成订单
     * @param $arr[]
     */
    public function createAppoint($arr)
    {
        $rest = null;
        $data['user_id'] = $arr['user_id'];
        $data['store_id'] = $arr['store_id'];
        $data['service_id'] = $arr['item_id'];
        $data['remark'] = $arr['remark'];
        $data['appoint_time'] = $arr['appoint_time'];
        $data['appoint_num'] = $arr['item_num'];
        $data['id_interestrate'] = $arr['id_interestrate'];
        $data['pay_price'] = $arr['pay_price'];
        $data['mrs_id'] = $arr['mrs_id'];
        $map['id'] = $arr['user_id'];
        $res1 = User::where($map)->field('realname,mobile')->find();
        if($res1){
            $data['user_name'] = $res1['realname'];
            $data['mobile'] = $res1['mobile'];
            $data['appoint_sn'] = make_ordersn($data['service_id']);
            $data['create_time'] = date('Y-m-d H:i:s');
            $res = TxAppoint::create($data);
            if($res){
                $rest = $res->id;
            }
        }
        return $rest;
    }
    /**
     * 订单列表
     * @param array $map =>查询条件 [$user_id 用户id,$status 订单状态,0待付款 1已付款 2已完成]
     * @param array $page => 当前页数
     */
    public function appointList($map,$page=1)
    {
        $arr['page_size'] = config('text.page_size');
        $arr['page'] = ($page-1)*$arr['page_size'];
        $mapr = ' a.status<>-2';
        $res1 = TxAppoint::alias('a')
            ->join(['store_bwk_item'=>'s'],['s.id=a.service_id'],'LEFT')
            ->where($map)
            ->where($mapr)
            ->field('a.id appoint_id,a.appoint_sn,a.create_time,a.status,s.item_name,s.item_img,s.duration,s.item_price,a.appoint_num,a.code_service,a.qrcode_service,a.service_id,a.id_interestrate,s.id item_id')
            ->limit($arr['page'],$arr['page_size'])
            ->order('a.create_time desc')
            ->select();
        if($res1){
            $item_ids = [];
            foreach ($res1 as $k=>$v) {
                // 订单编号,订单id,订单创建时间,订单状态,商品名称,商品封面图,项目时长,订购人数,价格,预约人数,合计价格
                $res1[$k]['duration'] = $v['duration'].'分钟';
                $res1[$k]['buy_num'] = 0;
                $res1[$k]['code_service_tips'] = '';
                if($v['status'] == 1){
                    $res1[$k]['code_service_tips'] = config('text.code_service_tips');
                }
                $map2['service_id'] = $v['service_id'];
                $map2['status'] = ['in',[1,2,3]];
                $res1[$k]['sum_price'] = sprintf("%.2f",$v['item_price'] * $v['appoint_num']);
                $item_ids[]=$v['item_id'] ;
            }

            $mapb['id'] = ['in',$item_ids];
            $res_buy = $this->itemList($mapb);
            if($res_buy){
                foreach ($res1 as $k=>$v) {
                    foreach ($res_buy as $vb) {
                        if($vb['id'] == $v['item_id']){
                            $res1[$k]['buy_num'] = $vb['buy_num'];
                        }
                    }
                }
            }

        }
        return $res1;
    }
    /**
     * 订单详情
     * @param int $appoint_id 预约id
     * @param int $user_id 用户id
     */
    public function ordDetail($appoint_id,$user_id)
    {
        $rest = [];
        $sinser = new SingleSer();
        $map1['id'] = $appoint_id;
        $res1 = $sinser->getTxAppoint($map1);
        if($res1){
            // 门店信息
            $map2['id'] = $res1[0]['store_id'];
            $res2 = $sinser->getBranch($map2);
            if($res2){
                $rest['store_name'] = $res2[0]['title'];
                $rest['store_tel'] = $res2[0]['tel'];
                $rest['address'] = $res2[0]['address'];
            }
            // 商品信息
            $map3['id'] = $res1[0]['service_id'];
            $res3 = $sinser->getBwkItem($map3);
            if($res3){
                $rest['item_id'] = $res3[0]['id'];
                $rest['item_name'] = $res3[0]['item_name'];
                $rest['item_img'] = $res3[0]['item_img'];
                $rest['duration'] = $res3[0]['duration'];
                $rest['buy_num'] = $res3[0]['buy_num'];
                $rest['item_price'] = sprintf("%.2f",$res3[0]['item_price']);
                $rest['appoint_num'] = $res1[0]['appoint_num'];
                $rest['sum_price'] = sprintf("%.2f",$rest['item_price'] * $rest['appoint_num']);
                $rest['status'] = $res1[0]['status'];
            }
            // 订单信息
            $map4['id'] = $res1[0]['mrs_id'];
            $res4 = $sinser->getUser($map4);
            if($res4){
                $rest['mrs_name'] = $res4[0]['realname'];
            }
            $rest['appoint_time'] = $res1[0]['appoint_time'];
            $rest['service_time'] = $res1[0]['service_time']==0?'':date('Y-m-d H:i:s',$res1[0]['service_time']);
            $rest['confirm_time'] = $res1[0]['complete_time']==0?'':date('Y-m-d H:i:s',$res1[0]['complete_time']);
            $rest['pay_time'] = $res1[0]['pay_time']==0?'':date('Y-m-d H:i:s',$res1[0]['pay_time']);
            $rest['appoint_sn'] = $res1[0]['appoint_sn'];
            $rest['id_interestrate'] = $res1[0]['id_interestrate'];
            $rest['code_service'] = $res1[0]['code_service'];
            $rest['qrcode_service'] = $res1[0]['qrcode_service'];
            $rest['comment'] = [];
            // 评价
            $map5['item_id'] = $res1[0]['service_id'];
            $map5['user_id'] = $user_id;
            $res5 = $this->itemEvaluate($map5);
            if($res5){
                $rest['comment'] = $res5;
            }
        }
        return $rest;
    }


}