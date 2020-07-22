<?php

namespace app\api\controller;
use app\api\model\ActivityOrderInfoModel;
use app\api\model\GoodsModel;
use app\api\model\MemberModel;
use think\Controller;
use think\Db;

/**
 * desc:胎盘618活动
 */
class Placenta618 extends Base
{
    protected $aid;
    protected $actInfo;
    public function _initialize() {
        parent::_initialize();
        parent::c_checkLogin(1);
        $this->aid=20;
        $this->actInfo=Db::name('activity_list')->where('id',$this->aid)->find();
    }

    //missshop活动产品列表
    public function goods_info(){
        $goodsId=input('param.goodsId','');
        $uid=input('param.uid');
        if($goodsId!='' && $uid!='') {
            $member=new MemberModel();
            $user=$member->getInfoByField(['id'=>$uid],'storeid');
            $map['id']=array('eq',$goodsId);
            $map['goods_cate']=array('eq',15);
            $map['status']=array('eq',1);
            $goods=new GoodsModel();
            $storeId=$user['storeid'];
            $map['storeid']=array('eq',$storeId);
            $check=$goods->getAllBranch($map);
            if(!$check){
                $storeId=0;
            }
            $map['storeid']=array('eq',$storeId);
            $goods = new GoodsModel();
            $info = $goods->getGoodsByMap($map);
            if (isset($info['goods_code'])) {
                $info['stock'] = parent::get_no_stock_goods($info['goods_code'],1);
                $info['given'] = parent::c_get_given($info['given'], $info['id']);
            }
            if($info){
                $code = 1;
                $data = $info;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无数据！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //购买
    public function buy(){
        parent::c_check_activity($this->aid);
        $uid=input('param.uid');
        $goodId=input('param.goodId');
        $num=input('param.num',1);
        if($goodId && $uid) {
            $mobile=Db::table('ims_bj_shopn_member')->where('id',$uid)->value('mobile');
            if($mobile) {
                $fid = input('param.fid', $uid);//引导分享uid
                //获取购买者的上级uid
                $fidInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch' => 'b'], 'm.storeid=b.id', 'left')->where('m.id', $uid)->field('m.code,m.isadmin,m.storeid,m.staffid,m.activity_flag,b.sign,b.join_tk')->find();
                $sellerId = $fidInfo['staffid'];
                $storeid = $fidInfo['storeid'];
                //密丝小铺门店用户重新归属
                if ($storeid == 1550) {
                    $getFidBid = Db::table('ims_bj_shopn_member')->where('id', $fid)->value('storeid');
                    Db::table('ims_bj_shopn_member')->where('id', $uid)->update(['storeid' => $getFidBid, 'pid' => $fid, 'staffid' => $fid]);
                }
                if ($fidInfo['sign'] == '000-000') {
                    $code = 0;
                    $data = '';
                    $msg = '您为办事处人员，无活动商品购买权限！';
                    return parent::returnMsg($code, $data, $msg);
                }
                //密丝小铺门店用户禁止下单
                if ($storeid == 1550) {
                    $code = 0;
                    $data = '';
                    $msg = '请联系您的所属美容师，再进行活动商品购买';
                    return parent::returnMsg($code, $data, $msg);
                }
                //判断门店是否参加该活动
                $branch_rule=parent::c_branch_rule($storeid,$this->aid);
                if(!$branch_rule){
                    $code = 0;
                    $data = '';
                    $msg = '您所在门店没有开通活动，请联系所属美容师';
                    return parent::returnMsg($code, $data, $msg);
                }
                $ordersn = date('YmdHis') . rand(0, 9) . rand(0, 9) . rand(0, 9) . $uid;
                $goods = Db::name('goods')->where('id', $goodId)->find();
                //检测限购
                $getUserOrder = $this->hashGet('missshop', $uid . '_' . $goodId);
                if (($getUserOrder >= $goods['allow_buy_num']) && $goods['allow_buy_num'] > 0) {
                    return parent::returnMsg(0, '', '失败，' . $goods['name'] . '每人仅限购买一次');
                }
                //检测库存
                $stock =  parent::get_no_stock_goods($goods['goods_code']);
                if (!$stock) {
                    return parent::returnMsg(0, '', '失败，' . $goods['name'] . '库存不足');
                }
                $amount = $goods['activity_price'] * $num;
                //订单详情
                $orderInfo[] = ['order_sn' => $ordersn, 'good_id' => $goodId, 'good_num' => $num, 'good_specs_sku' => $goods['goods_code'], 'good_price' => $goods['activity_price'], 'good_amount' => $amount, 'main_flag' => 1, 'good_specs' => '', 'source' => 0, 'flag' => 0, 'insert_time' => time(),'give_cate'=>''];
                //赠送2片面膜 2支胎盘
                $giveIds=Db::name('goods')->where('id','in', $goods['given'])->field('id,goods_code')->select();
                if(count($giveIds)){
                    foreach ($giveIds as $k=>$v){
                        $orderInfo[$k+1] = ['order_sn' => $ordersn, 'good_id' => $v['id'], 'good_num' =>$num*2, 'good_specs_sku' => $v['goods_code'], 'good_price' => 0, 'good_amount' =>0, 'main_flag' => 0, 'good_specs' => '', 'source' => 0, 'flag' => 1, 'insert_time' => time(),'give_cate'=>''];
                    }
                }
                $arr = array('uid' => $uid, 'storeid' => $storeid, 'fid' => $sellerId, 'share_uid' => $fid, 'num' => $num+(($num*2)+($num*2)), 'order_sn' => $ordersn, 'order_price' => $amount, 'pay_price' => $amount, 'channel' => 'missshop', 'pick_type' => 1, 'insert_time' => time(), 'pid' => '', 'scene' => $this->aid, 'flag' => 4, 'specs' => '','remark'=>'');
                Db::name('activity_order')->insert($arr);
                Db::name('activity_order_info')->insertAll($orderInfo);
                $res['order_sn'] = $ordersn;
                $res['attach'] = 'missshop';
                $res['total_fee'] = $amount;
                $res['user_id'] = $uid;
                $res['buy_type'] = 3;
                $res['body'] = $goods['name'];
                $code = 1;
                $data = $res;
                $msg = '订单已生成，去付款！';
            }else{
                $code = 0;
                $data = '';
                $msg = '错误，请重新登陆';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '错误，请重新登陆';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    //顾客购买列表 美容师看顾客 顾客店老板看自己
    public function orderList(){
        $uid=input('param.uid');
        if($uid!='') {
            $userInfo = Db::table('ims_bj_shopn_member')->field('id,staffid,code,isadmin')->where('id', $uid)->find();
            if ($userInfo) {
                if ($userInfo['isadmin']) {
                    $map['uid'] = array('eq', $uid);
                } elseif (($userInfo['id'] == $userInfo['staffid']) || strlen($userInfo['code']) > 1) {
                    $map['fid'] = array('eq', $uid);
                } else {
                    $map['uid'] = array('eq', $uid);
                }
                $map['scene'] = array('eq', 20);
                $map['pay_status'] = array('eq', 1);
                $map['channel'] = array('eq', 'missshop');
                $Nowpage = input('param.page') ? input('param.page') : 1;
                $limits = 10;// 显示条数
                $count = Db::name('activity_order')->where($map)->count();
                $allpage = intval(ceil($count / $limits));
                if ($Nowpage >= $allpage) {
                    $info['next_page_flag'] = 0;//是否有下一页
                } else {
                    $info['next_page_flag'] = 1;
                }
                $list = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'], 'o.uid=m.id', 'left')->join('wx_user u', 'm.mobile=u.mobile', 'left')->join('goods p', 'o.pid=p.id', 'left')->field('uid,fid,pay_status,pay_price,order_sn,m.realname,m.mobile,u.avatar,u.nickname,num,pick_code,pick_type,order_status,o.is_axs')->where($map)->page($Nowpage, $limits)->order('o.id desc')->select();
                if (count($list) && is_array($list)) {
                    foreach ($list as $k => $v) {
                        $list[$k]['pay_status'] = $v['pay_status'] ? '已支付' : '未付款';
                        $list[$k]['pick_up']=$v['order_status']?'已取货':'未取货';
                        $orderInfo=new ActivityOrderInfoModel();
                        $getInfo=$orderInfo->getOrderInfoByWhere(['order_sn'=>$v['order_sn'],'main_flag'=>1]);
                        $list[$k]['list']=$getInfo;
                        $branchInfo = Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch' => 'b'], 'm.storeid=b.id', 'left')->where('m.id', $v['fid'])->field('m.realname,m.mobile,b.title,b.sign,b.address')->find();
                        $list[$k]['pick_info'] = $branchInfo;
                        if ($v['pick_type'] == 1 && $v['pick_code'] == '') {
                            $codeUrl = pickUpCode('missshop_' . $v['order_sn']);
                            if ($codeUrl) {
                                $list[$k]['pick_code'] = $codeUrl;
                                Db::name('activity_order')->where('order_sn', $v['order_sn'])->update(['pick_code' => $codeUrl]);
                            } else {
                                $list[$k]['pick_code'] = '';
                            }
                        }
                    }
                    $info['list'] = $list;
                    $code = 1;
                    $data = $info;
                    $msg = '获取成功';
                } else {
                    $code = 0;
                    $data = '';
                    $msg = '暂无订单';
                }
            } else {
                $code = 0;
                $data = '';
                $msg = '参数错误！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误！';
        }
        return parent::returnMsg($code,$data,$msg);
    }









}