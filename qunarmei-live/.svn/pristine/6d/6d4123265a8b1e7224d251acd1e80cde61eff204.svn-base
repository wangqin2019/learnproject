<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/5/7
 * Time: 17:37
 */

namespace app\api\service;

/*门店服务相关处理类*/
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
use think\Db;
class StoreItem
{
    /**
     * 商品详情
     * @param $map 查询条件
     * @return array
     */
    public function itemDetail($map)
    {
        $rest = [];
        $res = BwkItem::get(function($query) use($map){
            $query->field('id,item_name,item_img,duration,item_price,item_detail_img,id_interestrate,item_wheplan_img')->where($map);
        });
        if($res){
            $rest['item_id'] = $res['id'];
            $rest['item_name'] = $res['item_name'];
            $rest['item_img'] = $res['item_img'];
            $rest['item_wheplan_img'] = [];
            if($res['item_wheplan_img']){
                $rest['item_wheplan_img'] = explode(',',$res['item_wheplan_img']);
            }
            $rest['duration'] = $res['duration'].'分钟';
            $rest['item_price'] = $res['item_price'];
            $rest['item_detail_img'] = [];
            if($res['item_detail_img']){
                $rest['item_detail_img'] = explode(',',$res['item_detail_img']);
            }
            $rest['bank_list'] = [];
            if($res['id_interestrate']){
                $mapi['id_interestrate'] = ['in',explode(',',$res['id_interestrate'])];
                $res_bank = BankInterestrate::all(function($query) use($mapi){
                    $query->alias('bi')->join(['sys_bank'=>'sb'],['bi.id_bank=sb.id_bank'],'LEFT')->field('sb.id_bank,sb.st_abbre_bankname,sb.st_bnkpic1,sb.st_bnkpic2,bi.no_period')->where($mapi)->group('sb.id_bank');
                });
                if($res_bank){

                    foreach ($res_bank as $v_b) {
                        $res1['id_bank'] = $v_b['id_bank'];
                        $res1['bank_name'] = $v_b['st_abbre_bankname'];
                        $res1['bank_img'] = $v_b['st_bnkpic1'];
                        $mapi['sb.id_bank'] = $v_b['id_bank'];
                        $res_i = BankInterestrate::all(function($query) use($mapi){
                            $query->alias('bi')->join(['sys_bank'=>'sb'],['bi.id_bank=sb.id_bank'],'LEFT')->field('sb.id_bank,sb.st_abbre_bankname,sb.st_bnkpic1,sb.st_bnkpic2,bi.no_period,bi.id_interestrate')->where($mapi);
                        });
                        if($res_i){
                            $res1['fenqi'] = [];
                            $res11 = [];
                            foreach($res_i as $v_i){
                                $res11['id_interestrate'] = $v_i['id_interestrate'];
                                $res11['no_period'] = $v_i['no_period']==0?1:$v_i['no_period'];
                                if($v_i['no_period']>1){
                                    $res11['evpprice'] = round($res['item_price'] / $v_i['no_period'],2);// 每期价格保留2位小数
                                }else{
                                    $res11['evpprice'] = round($res['item_price'],2) ;
                                }
                                $res11['evpprice'] = sprintf("%.2f",$res11['evpprice']);
                                $res1['fenqi'][] = $res11;
                            }
                        }
                        $rest['bank_list'][] = $res1;
                    }
                }
            }
            // 购买人数
            $mapn['service_id'] = $res['id'];
            $res_n = TxAppoint::all(function($query) use($mapn){
                $mapn['status'] = ['in',[1,2,3]];
                $query->where($mapn)->field('id');
            });
            $rest['buy_num'] = count($res_n).'人订购';
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

        // 单个订单评价
        if(isset($arr['appoint_id'])){
            $map['b.appoint_id'] = $arr['appoint_id'];
        }

        // 一级评论
        $res = BwkItemEvaluate::all(function($query) use($map){
            $query->alias('b')->join(['ims_bj_shopn_member'=>'m'],['m.id=b.user_id'],'LEFT')->join(['ims_fans'=>'f'],['m.id=f.id_member'],'LEFT')->where($map)->field('b.id,b.item_id,b.user_id,b.eva_pid,b.eva_content,b.create_time,m.realname,b.giveup_users,b.eva_pid,f.avatar,b.create_time,b.evlabel_id,b.eva_imgs,b.mrs_level,b.environment_level,b.service_level')->order('b.create_time desc');
        });
        if($res){
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
                $res1['comment_content'] = $v['eva_content'];

                $res_label = $this->getLabelByComment($res1['service_level'],$v['eva_content'],$res1['comment_time']);
                if($res_label){
                    $res1['label_id'] = $res_label;
                }

                $res1['see_num'] = isset($arr['see_num'])?$arr['see_num']:0;
                $res1['comment_imgs'] = [];
                if($v['eva_imgs']){
                    $res1['comment_imgs'] = explode(',',$v['eva_imgs']);
                }

                // 点赞用户列表
                $mapv = null;$res_dz = null;
                if($v['giveup_users']){
                    $mapv['id'] = ['in',explode(',',$v['giveup_users'])];
                    $res_dz = User::all(function($query) use($mapv){
                        $query->where($mapv)->field('id,realname');
                    });
                }
                // 1点赞 0未点赞
                $res1['is_giveup'] = 0;
//                echo '<pre>giveup_users：';print_r($v['giveup_users']);die;
                if($v['giveup_users'] && strstr($v['giveup_users'],$arr['user_id'])){
                    $res1['is_giveup'] = 1;
                }
                $res1['giveup_users'] = [];
                if($res_dz){
                    foreach ($res_dz as $v_d) {
                        $res1['giveup_users'][] = $v_d['realname'];
                    }
                }
                // 二级评论及下面多级评论 用户id,用户名,用户评论,回复用户id,回复用户名
                $mape['orig_id'] = $v['id'];
                $res_pid = BwkItemEvaluate::all(function($query) use($mape){
                    $query->alias('b')->join(['ims_bj_shopn_member'=>'m'],['m.id=b.user_id'],'LEFT')->where($mape)->field('b.id,b.user_id,m.realname,b.eva_pid,b.evlabel_id,b.eva_content')->order('b.create_time asc');
                });
                $res1['comment_list'] = [];
                if($res_pid){
                    foreach ($res_pid as $v_pid) {
                        $res2['user_id'] = $v_pid['user_id'];
                        $res2['user_name'] = $v_pid['realname'];
                        $res2['comment_id'] = $v_pid['id'];
                        $res2['comment_content'] = $v_pid['eva_content'];
                        $res2['reply_user_id'] = 0;
                        $res2['reply_user_name'] = '';
                        // 查询上级回复人
                        if($v_pid['eva_pid'] != $v['id']){
                            $map3['b.id'] = $v_pid['eva_pid'];
                            $res_p3 = BwkItemEvaluate::get(function($query) use($map3){
                                $query->alias('b')->join(['ims_bj_shopn_member'=>'m'],['m.id=b.user_id'],'LEFT')->where($map3)->field('b.user_id,m.realname')->order('b.create_time asc');
                            });
                            if($res_p3){
                                $res2['reply_user_id'] = $res_p3['user_id'];
                                $res2['reply_user_name'] = $res_p3['realname'];
                            }
                        }
                        $res1['comment_list'][] = $res2;
                    }
                }
                $rest[] = $res1;
            }
        }
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
     * 项目详情-评论总次数
     * @param int $item_id 项目id
     * @return
     */
    public function itemCommentNum($item_id)
    {
        $rest = 0;
        $map['item_id'] = $item_id;
        $rest = BwkItemEvaluate::where($map)->count();
        return $rest;
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
        // 第一次评价订单(有项目id),则更新服务确认时间
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
        $res = User::all(function($query) use($map){
            $map1 = ' length(m.code)>0 and m.id=m.staffid ';
            $query->alias('m')
                ->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')
                ->field('m.id,f.avatar,m.realname,m.mobile,f.good_at')
                ->where($map)
                ->where($map1)
                ->group('m.id')
                ->order('m.id desc');
        });
        if($res){
            foreach ($res as $k=>$v) {
                $mapl['a.mrs_id'] = $v['id'];
                $mapl['e.eva_pid'] = 0;
                // 美容师平均分 查找美容师对应的订单,查找订单对应的评价
                $res1['mrs_level'] = BwkItemEvaluate::alias('e')->join(['store_tx_appoint'=>'a'],['a.id=e.appoint_id'],'LEFT')->where($mapl)->avg('e.mrs_level');
                $res1['mrs_level'] = round($res1['mrs_level'] ,1);
                $res1['good_at'] = '';
                if($v['good_at'] && !is_null($v['good_at'])){
                    $res1['good_at'] = $v['good_at'];
                }
                $res[$k]['mrs_level'] = $res1['mrs_level'];
                $res[$k]['good_at'] = $res1['good_at'];
            }
        }
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
            $rest['mrs_level'] = BwkItemEvaluate::alias('e')->join(['store_tx_appoint'=>'a'],['a.id=e.appoint_id'],'LEFT')->where($mapm)->avg('e.mrs_level');
            $rest['mrs_level'] = round($rest['mrs_level'] ,1);

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
                            // if($arr3['appoint_time'] >= $v['dt1'] && $arr3['appoint_time']<$v['dt2']){
                            //     $v['is_use'] = 1;
                            // }
                            // 预约时间+项目时长在起始时间内
                            // elseif($arr3['appoint_time2']>$v['dt1'] && $arr3['appoint_time']<=$v['dt2']){
                            //     $v['is_use'] = 1;
                            // }
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
     * @param array $map => [$user_id 用户id,$status 订单状态,0待付款 1已付款 2已完成]
     * @param array $page => 当前页数
     */
    public function appointList($map,$page)
    {
        $arr['page_size'] = config('text.page_size');
        $arr['page'] = ($page-1)*$arr['page_size'];
        $mapr = ' a.status<>-2';
        $res1 = TxAppoint::alias('a')
            ->join(['store_bwk_item'=>'s'],['s.id=a.service_id'],'LEFT')
            ->where($map)
            ->where($mapr)
            ->field('a.id appoint_id,a.appoint_sn,a.create_time,a.status,s.item_name,s.item_img,s.duration,s.item_price,a.appoint_num,a.code_service,a.qrcode_service,a.service_id,a.id_interestrate,s.id item_id')
            ->order('a.create_time desc')
            ->limit($arr['page'],$arr['page_size'])
            ->select();
        if($res1){
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
                $res2 = TxAppoint::where($map2)->count();
                if($res2){
                    $res1[$k]['buy_num'] = $res2;
                }
                $res1[$k]['buy_num'].='人订购';
                $res1[$k]['sum_price'] = sprintf("%.2f",$v['item_price'] * $v['appoint_num']);
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
            $map5['appoint_id'] = $appoint_id;
            $res5 = $this->itemEvaluate($map5);
            if($res5){
                // 获取单个订单自己的评价
                $rest['comment'] = $res5;
            }
        }
        return $rest;
    }
    /**
     * 订单支付
     * @param int $appoint_id 预约id
     * @param int $user_id 用户id
     */
    public function ordPay($appoint_id,$user_id)
    {
        $mulser = new MultiSer();
        $singser = new SingleSer();
        $map1['a.id'] = $appoint_id;
        $res1 = $mulser->TxAppointBwkItem($map1);
        $arr = [];
        $res = [];
        $tran_paras = '';
        $pay_type = '';
        if($res1){
            // 查询是否有支付日志,有返回支付日志参数
            $singser = new SingleSer();
            $mapp['appoint_sn'] = $res1[0]['appoint_sn'];
            $res_paylog = $singser->getItemPaylog($mapp);
            if($res_paylog && $res_paylog[0]['tran_paras']){
                if($res_paylog[0]['pay_type'] == 'wx'){
                    return json_decode($res_paylog[0]['tran_paras'],true);
                }else{
                    return $res_paylog[0]['tran_paras'];
                }
            }

            $arr['item_name'] = $res1[0]['item_name'];
            $arr['appoint_sn'] = $res1[0]['appoint_sn'];
            $arr['sum_price'] = $res1[0]['item_price'] * $res1[0]['appoint_num'] * 100;// 支付总价格 分
            $arr3['id_interestrate'] = $res1[0]['id_interestrate'];
            // 根据支付利率查询支付方式及分期数
            $map2['id_interestrate'] = $arr3['id_interestrate'];
            $res2 = $singser->getBankInterestrate($map2);
            if($res2){
                $arr3['bank_id'] = $res2[0]['id_bank'];
                $arr3['no_period'] = $res2[0]['no_period']==0?1:$res2[0]['no_period'];
            }

            // 微信支付
            if($arr3['bank_id'] == 6){
                $wxpay = new WxPay();
                $arr['body'] = '';//商品名称
                $res = $wxpay->getPrePayOrder($arr['item_name'],$arr['appoint_sn'],$arr['sum_price']);
                $tran_paras = json_encode($res,JSON_UNESCAPED_UNICODE);
                $pay_type = 'wx';
            }elseif($arr3['bank_id'] == 8){
                // 支付宝支付
                $aliapay = new AliPay();
                // 30分钟后订单失效
                $arr['sum_price'] = $arr['sum_price']/100;//元
//                echo '<pre>';print_r($arr);die;
                $res = $aliapay->unifiedorder($arr['appoint_sn'],$arr['item_name'],$arr['item_name'],$arr['sum_price'],30);
                $tran_paras = $res;
                $pay_type = 'alia';
            }else{
                // 去哪美支付
                $qnmpay = new QunarmeiPay();
                $arr1['bank_id'] = $arr3['bank_id'];
                $arr1['order_no'] = $res1[0]['appoint_sn'];
                $arr1['no_period'] = $arr3['no_period'];// 分期数查询
                $arr1['order_no'] = $res1[0]['appoint_sn'];
                $arr1['sum_price'] = $arr['sum_price']/100;
                $arr1['gd_title'] = $res1[0]['item_name'];
                $arr1['sum_num'] = $res1[0]['appoint_num'];
                $arr1['mobile'] = '';// 用户手机号查询

                $map2['id_interestrate'] = $arr3['id_interestrate'];
                $res2 = $singser->getBankInterestrate($map2);
                if($res2){
                    $arr1['no_period'] = $res2[0]['no_period'];
                }
                $map3['id'] = $user_id;
                $res3 = $singser->getUser($map3);
                if($res3){
                    $arr1['mobile'] = $res3[0]['mobile'];
                }
                $res = $qnmpay->qunarmeiPayParameter($arr1);
                $pay_type = 'qnm';
                $tran_paras = $res;
            }
            if($res){
                // 插入交易日志表
                $data1['user_id'] = $res1[0]['user_id'];
                $data1['mobile'] = $res1[0]['mobile'];
                $data1['appoint_sn'] = $res1[0]['appoint_sn'];
                $data1['pay_amount'] = $res1[0]['item_price'] * $res1[0]['appoint_num'];
                $data1['tran_paras'] = $tran_paras;
                $data1['log_time'] = date('Y-m-d H:i:s');
                $data1['pay_type'] = $pay_type;
                ItemPaylog::create($data1);
            }
        }
        return $res;
    }
    /**
     * 支付回调成功后处理
     * @param array $arr=>[appoint_sn 预约订单号,transaction_id 交易流水id,pay_price 支付金额]
     */
    public function callPaySuc($arr)
    {
        // 查询是否已更新,防止多次重复回调
        $singser = new SingleSer();
        $mapp['appoint_sn'] = $arr['appoint_sn'];
        $mapp['status'] = 1;
        $res_paylog = $singser->getItemPaylog($mapp);
        if($res_paylog){
            return -1;
        }
        // 1.更改日志表状态
        $map1['appoint_sn'] = $arr['appoint_sn'];
        $data1['transaction_id'] = $arr['transaction_id'];
        $data1['status'] = 1;
        // 2.更改预约订单表状态
        $data2['pay_time'] = time();
        $data2['pay_price'] = $arr['pay_price'];
        $data2['status'] = 1;
        // 生成服务码密码+服务码二维码
        $data2['code_service'] = make_str();
//        echo '<pre>';print_r($data2);die;
        if($data2['code_service']){
            $commod = new Common();
            $data_json = json_encode(['code_service'=>$data2['code_service'],'type'=>'code_service']);// 测量数据扫码
            $qr_code = $commod->makeQrCode($data_json,'codeser'.date('YmdHis').'.png');
            $data2['qrcode_service'] = $qr_code;
        }
        Db::transaction(function() use($map1,$data1,$data2){
            ItemPaylog::where($map1)->update($data1);
            TxAppoint::where($map1)->update($data2);
        });

        // 发送短信通知+站内信通知
        $sernotice = new SerNotice();
        $map1['status'] = 1;
        $resu = TxAppoint::where($map1)->field('mobile')->limit(1)->find();
        if($resu){
            $sernotice->sendJpush(4,'alias',$resu['mobile'],'您已经成功支付一笔'.$arr['pay_price'].'的订单，商户订单号'.$arr['appoint_sn'].'，支付订单号'.$arr['transaction_id'].'，请登录去哪美支付查询!');
            // 推送短信通知
            $arrs['code'] = '{"payAmount":"'.$arr['pay_price'].'","orderId":"'.$arr['appoint_sn'].'","paymentOrderNo":"'.$arr['transaction_id'].'"}';
            $arrs['mobile'] = $resu['mobile'];
            $arrs['template_id'] = 24;
            $arrs['type'] = 2;
            $msg = $sernotice->sendSmsCode($arrs);
        }
        return 1;
    }
    /**
     * 订单-修改
     * $data=>修改数据
     * $map=>>修改条件
     */
    public function orderUpd($data,$map)
    {
        $res = TxAppoint::update($data,$map);
        return $res;
    }
    /**
     * 支付宝支付测试
     */
    public function aliaPay()
    {
        $aliapay = new AliPay();
        $res = $aliapay->unifiedorderTest();
        return $res;
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
}