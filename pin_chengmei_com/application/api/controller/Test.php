<?php

namespace app\api\controller;

use think\Cache;
use think\Controller;
use think\Db;
use weixin\WeixinAccount;
use weixin\WeixinRefund;
use weixin\WeixinPay;
use weixin\WeixinRefund8171;

/**
 * swagger: 支付回调
 */
class Test extends Base
{
    public static $missshop_2_expire = 1;//秒

//    public function tuikuan(){
//        $order_sn=input('param.order_sn');
//        $type=input('param.type');
//        $key=input('param.key');
//        if($key=='hdj') {
//            $map['order_sn'] = array('eq', $order_sn);
//            $info = Db::name('activity_order')->where($map)->find();
//            if (is_array($info) && count($info)) {
//                if ($info['pay_status']) {
//                    if($type=='old'){
//                        $refundData = [
//                            'appid' => 'wx49a7ab9464c23a60', //应用id
//                            'mchid' => '1513518171', //商户号id
//                            'api_key' => 'shj13hk3h21khkasdhk1h23h12390doq', //支付key
//                            'transaction_id' => $info['transaction_id'], //微信交易号
//                            'out_refund_no' => date('YmdHis') . time() . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9), //退款单号
//                            'total_fee' => floatval($info['pay_price'] * 100), //原订单金额
//                            'refund_fee' => floatval($info['pay_price'] * 100), //退款金额
//                            'refund_text' => '购买密丝小铺产品退款' //退款描述
//                        ];
//                        $refund = new WeixinRefund8171($refundData);
//                    }elseif($type=='new'){
//                        $refundData = [
//                            'appid' => config('wx_pay.appid'), //应用id
//                            'mchid' => config('wx_pay.mch_id'), //商户号id
//                            'api_key' => config('wx_pay.api_key'), //支付key
//                            'transaction_id' => $info['transaction_id'], //微信交易号
//                            'out_refund_no' => date('YmdHis') . time() . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9), //退款单号
//                            'total_fee' => floatval($info['pay_price'] * 100), //原订单金额
//                            'refund_fee' => floatval($info['pay_price'] * 100), //退款金额
//                            'refund_text' => '购买密丝小铺产品退款' //退款描述
//                        ];
//                        $refund = new WeixinRefund($refundData);
//                    }else{
//                        echo "请选择退款账户";
//                        die();
//                    }
//                    $res = $refund->orderRefund();
//                    $resArr = $refund->XmlToArr($res);
//                    if ($resArr['return_code'] == "SUCCESS") {
//                        if ($resArr['result_code'] == "SUCCESS") {
//                            //退款成功 修改日志表记录
//                            $data1 = array('refund_amont' => $resArr['refund_fee'] / 100, 'status' => 2, 'refund_time' => date('Y-m-d H:i:s'), 'refund_id' => $resArr['refund_id'], 'refund_err' => '');
//                            Db::name('pay_log')->where('transaction_id', $info['transaction_id'])->update($data1);
//                            //退款成功 修改订单表记录
//                            Db::name('activity_order')->where('transaction_id', $info['transaction_id'])->update(['order_status' => 0, 'pay_status' => 0]);
//                            //退款成功 记录日志
//                            logs(date('Y-m-d H:i:s') . "自动退款：" . json_encode($resArr), 'refundOk');
//                        } else {
//                            //退款失败 修改表记录
//                            $data1 = array('refund_time' => date('Y-m-d H:i:s'), 'refund_err' => $resArr['err_code_des']);
//                            Db::name('pay_log')->where('transaction_id', $info['transaction_id'])->update($data1);
//                            //退款失败 记录日志
//                            logs(date('Y-m-d H:i:s') . "自动退款：" . json_encode($resArr), 'refundFail');
//                        }
//                    } else {
//                        //退款失败 记录日志
//                        logs(date('Y-m-d H:i:s') . "自动退款：" . json_encode($resArr), 'refundFail');
//                    }
//                }
//            } else {
//                echo "订单号不存在";
//            }
//        }
//    }


    public function test()
    {
        $token = input('param.token');
        $aa = $this->getInfoByToken($token);
        print_r($aa);
    }


    public function aaa()
    {
        $data['bill_date'] = '20180822';
        $refund = new WeixinAccount($data);
        $res = $refund->checkAccount();
        $aa = deal_WeChat_response($res);
        print_r($aa);
    }


    public function test1()
    {
        $getSign = '123-545555';
        $mainSign = '';
        if ($getSign) {
            if (strlen($getSign) >= 7) {
                $mainSign = substr($getSign, 0, 7);
            } else {
                $mainSign = $getSign;
            }
            echo $mainSign;
        }
    }


    //维护订单价格
    function updateOrder()
    {
        set_time_limit(0);
        $list = Db::name('tuan_list')->alias('list')->join('tuan_info info', 'list.tuan_id=info.id', 'left')->field('list.id,info.p_price,info.pid,info.prizeid,list.order_sn')->where('list.tuan_price', 0)->select();
        $count = count($list);
        $i = 0;
        $str = '';
        if (is_array($list) && count($list)) {
            foreach ($list as $k => $v) {
                try {
                    Db::name('tuan_list')->where('id', $v['id'])->update(['tuan_price' => $v['p_price']]);
                    $son = Db::name('tuan_order')->where('parent_order', $v['order_sn'])->field('orderid,flag')->select();
                    foreach ($son as $kk => $vv) {
                        if ($vv['flag'] == 0) {
                            $data = $v['pid'];
                        } else {
                            $data = $v['prizeid'];
                        }
                        Db::name('tuan_order')->where('orderid', $vv['orderid'])->update(['buy_good_ids' => $data]);
                    }
                    $i++;
                } catch (\Exception $e) {
                    $str .= $v['order_sn'] . '维护失败' . $e->getMessage();
                }
            }
        }
        echo "总计" . $count . '条 已维护' . $i . '条';
    }

    //维护发起人付款金额
    function update_fqr_pay()
    {
        set_time_limit(0);
        $list = Db::name('tuan_list')->alias('list')->join('tuan_info info', 'list.tuan_id=info.id', 'left')->field('list.id,info.p_price,info.pid,info.prizeid,list.order_sn')->where('list.initiator_pay', 0)->select();
        $count = count($list);
        $i = 0;
        $str = '';
        if (is_array($list) && count($list)) {
            foreach ($list as $k => $v) {
                try {
                    $initiator_pay = Db::name('tuan_order')->where('order_sn', $v['order_sn'])->column('pay_price');
                    foreach ($initiator_pay as $kk => $vv) {
                        Db::name('tuan_list')->where('id', $v['id'])->update(['initiator_pay' => $vv]);
                    }
                    $i++;
                } catch (\Exception $e) {
                    $str .= $v['order_sn'] . '维护失败' . $e->getMessage();
                }
            }
        }
        echo "总计" . $count . '条 已维护' . $i . '条';
    }

    //维护参团人付款金额
    function update_cyr_pay()
    {
        set_time_limit(0);
        $list = Db::name('tuan_list')->alias('list')->join('tuan_info info', 'list.tuan_id=info.id', 'left')->field('list.id,info.p_price,info.pid,info.prizeid,list.order_sn')->where('list.partner_pay', 0)->select();
        $count = count($list);
        $i = 0;
        $str = '';
        if (is_array($list) && count($list)) {
            foreach ($list as $k => $v) {
                try {
                    $initiator_pay = Db::name('tuan_order')->where(['parent_order' => $v['order_sn'], 'flag' => 1])->limit(1)->column('pay_price');
                    foreach ($initiator_pay as $kk => $vv) {
                        Db::name('tuan_list')->where('id', $v['id'])->update(['partner_pay' => $vv]);
                    }
                    $i++;
                } catch (\Exception $e) {
                    $str .= $v['order_sn'] . '维护失败' . $e->getMessage();
                }
            }
        }
        echo "总计" . $count . '条 已维护' . $i . '条';
    }


    //维护参团人付款金额
    function update_tuan_name()
    {
        set_time_limit(0);
        $list = Db::name('tuan_list')->alias('list')->join('tuan_info info', 'list.tuan_id=info.id', 'left')->field('list.id,info.p_name')->where('list.tuan_name', '')->select();
        $count = count($list);
        $i = 0;
        $str = '';
        if (is_array($list) && count($list)) {
            foreach ($list as $k => $v) {
                try {
                    Db::name('tuan_list')->where('id', $v['id'])->update(['tuan_name' => $v['p_name']]);
                    $i++;
                } catch (\Exception $e) {
                    $str .= $v['order_sn'] . '维护失败' . $e->getMessage();
                }
            }
        }
        echo "总计" . $count . '条 已维护' . $i . '条';
    }


    /**
     * Commit: 全国排名或某一个门店排名 10.30 条件最全的方法
     * Function: national_store_rank
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-11 14:20:34
     */
    public function national_rank()
    {
        $begin = 1572364800;//$activity_config['begin_time'];//活动开始时间
        $end = time();//活动结束时间
        //查询订单数据并排序 店铺及用户排序
        $field_where = "o.storeid,o.scene,o.uid,o.fid,o.pid, store.title,store.sign ";
        $map['o.channel'] = 'missshop';
        $map['o.pay_status'] = 1;
        $map['o.pay_time'] = ['between', [$begin, $end]];
        $order_where = Db::name('activity_order')
            ->alias('o')
            ->join(['ims_bwk_branch' => 'store'], 'o.storeid = store.id', 'left')
            ->field($field_where)
            ->where($map)
            ->where('o.uid<>o.fid')
            ->buildSql();
        //查询门店
        $stores = Db::name('activity_order')
            ->where('channel', '=', 'missshop')
            ->where('pay_status', '=', 1)
            ->where('pay_time', 'between', [1569859200, $end])
            ->group('storeid')
            ->column('storeid');
        //以用户分组
        #获取九月份拓客记录信息
        $map9['channel'] = 'missshop';
        $map9['pay_status'] = 1;
        $map9['scene'] = 0;
        $map9['pay_time'] = ['<', $begin];
        $toker9 = Db::name('activity_order')
            ->field('(case when scene=0 then 1 else 0 end) toker9')
            ->where($map9)
            ->where('uid<>fid')
            ->where('uid = a.uid')
            ->group('uid')
            ->buildSql();

        $field_uid = "a.storeid,a.uid,a.fid,a.title,a.sign";
        $field_uid .= ",GROUP_CONCAT(DISTINCT a.scene ORDER BY a.scene desc) tt";#去重后标识
        $field_uid .= ",IFNULL({$toker9},0) toker9";
        $order_uid = Db::table($order_where . 'a')
            ->field($field_uid)
            ->group('a.uid')
            ->order('a.storeid asc,a.uid asc')
            ->buildSql();
        //查询转客拓客用户数量
        $field_tt = "bb.storeid,bb.uid,bb.fid,bb.title,bb.sign,bb.tt";
        $field_tt .= ",( case when bb.tt = '1,0' then 1 else 0 end) transfer_part";#拓转客
        $field_tt .= ",( case when bb.tt = '0' then 1 else 0 end) toker_part";#拓客一部分
        $field_tt .= ",( case when bb.tt = '1' then 1 else 0 end) first_transfer";#首次转客
        $field_tt .= ",( case when (bb.tt = '0' or bb.tt = '1,0') then 1 else 0 end) toker_transfer";
        $field_tt .= ",bb.toker9";
        $order_tt = Db::table($order_uid . ' bb')
            ->field($field_tt)
            ->buildSql();

        //拓客转客真实数量
        $cc = "cc.storeid,cc.uid,cc.fid,cc.title,cc.sign,cc.tt,cc.toker9";
        //首次转客
        $cc .= ",( case when cc.first_transfer>0 ";
        $cc .= " then cc.first_transfer- cc.toker9 ";
        $cc .= "  else cc.first_transfer end ) first_transfer";
        //不包含首次转客
        $cc .= ",( case when cc.first_transfer>0";
        $cc .= " then ( cc.transfer_part+ cc.toker9 )";
        $cc .= "  else cc.transfer_part end ) transfer_part";
        //真实拓客
        $cc .= ",( case when ( cc.transfer_part + cc.toker_part) > 0 ";
        $cc .= "   then ( cc.transfer_part + cc.toker_part -cc.toker9 ) ";
        $cc .= "   else ( cc.transfer_part + cc.toker_part) end ) toker";
        $order_cc = Db::table($order_tt . 'cc')
            ->field($cc)
            ->buildSql();

        $dd = "dd.storeid,dd.uid,dd.fid,dd.title,dd.sign,dd.toker9";
        $dd .= ",dd.toker,dd.first_transfer,dd.transfer_part";
        $dd .= ",(dd.transfer_part + dd.first_transfer) transfer";

        $order_dd = Db::table($order_cc . 'dd')
            ->field($dd)
            ->buildSql();
        //以门店分组
        $filed_store = "d.storeid,d.title,d.sign";
        $filed_store .= ",SUM( d.transfer ) transfer";#转客总数量
        $filed_store .= ",SUM( d.first_transfer ) first_transfer";#首次转客
        $filed_store .= ",SUM( d.transfer_part ) transfer_part";#不包含首次转客
        $filed_store .= ",SUM( d.toker ) toker";#真实拓客

        $pmap['channel'] = 'missshop';
        $pmap['pay_status'] = 1;
        $pmap['pay_time'] = ['BETWEEN', [$begin, $end]];
        //门店总金额
        $price = Db::name('activity_order')
            ->field('sum(pay_price)')
            ->where($pmap)
            ->where('storeid = d.storeid')
            ->buildSql();
        $filed_store .= ",IFNULL(({$price}), 0) price";#门店全部金额
        //门店转客总金额
        $transfer_price = Db::name('activity_order')
            ->field('sum(pay_price)')
            ->where($pmap)
            ->where('scene', '=', 1)
            ->where('storeid = d.storeid')
            ->buildSql();
        $filed_store .= ",IFNULL(({$transfer_price}), 0) transfer_price";#门店转客金额
        $order_store = Db::table($order_dd . 'd')
            ->field($filed_store)
            ->group('d.storeid')
            ->order('d.storeid asc')
            ->buildSql();
        //获取用户分享数据
        $share = Db::name('activity_share')
            ->field(' storeid,uid,fid')
            ->where('item', '=', 'miss_tuoke')
            ->where('storeid', '<>', '')
            ->where('insert_time', 'in', [$begin, $end])
            ->group('uid')
            ->buildSql();
        $share_sql = Db::table($share . 'sss')
            ->field(' sss.storeid,count(sss.uid) share_num')
            ->group('sss.storeid')
            ->buildSql();
        //计算分数
        $field = "abc.storeid,abc.title,abc.sign";
        $field .= ",abc.transfer_price,abc.price";
        $field .= ",abc.transfer";#转客总数
        $field .= ",abc.toker ";#拓客总数
        $field .= ",IFNULL( share.share_num, 0 ) share_num ";#分享数量
        $field .= ",(abc.toker * 2 + abc.transfer_part * 3 + abc.first_transfer * 5 + floor( abc.price / 200 ) + IFNULL( share.share_num, 0 )) grade ";#总分数
        $new_sql = Db::table($order_store . 'abc')
            ->field($field)
            ->join([$share_sql => 'share'], 'abc.storeid = share.storeid', 'left')
            ->order('grade desc')
            ->buildSql();

        //查询10.30之前的数据
        $old = "storeid,sign,title,price,transfer_price,transfer,toker,share_num,grade";
        $old_sql = Db::name('activity_order_rank')
            ->field($old)
            ->where('storeid', 'in', $stores)
            ->buildSql();
        //合并
        $merge_field = "l.storeid,l.title,l.sign";
        $merge_field .= ",(IFNULL(l.price,0) + IFNULL(n.price,0)) price";
        $merge_field .= ",(IFNULL(l.transfer_price,0) + IFNULL(n.transfer_price,0)) transfer_price";
        $merge_field .= ",(IFNULL(l.transfer,0) + IFNULL(n.transfer,0)) transfer";
        $merge_field .= ",(IFNULL(l.toker,0) + IFNULL(n.toker,0)) toker";
        $merge_field .= ",(IFNULL(l.share_num,0) + IFNULL(n.share_num,0)) share_num";
        $merge_field .= ",(IFNULL(l.grade,0) + IFNULL(n.grade,0)) grade";

        $sql = Db::table($old_sql . 'l')
            ->field($merge_field)
            ->join([$new_sql => 'n'], 'l.storeid=n.storeid', 'left')
            ->buildSql();
        $xxx = "xxx.storeid,xxx.title,xxx.sign,xxx.price,xxx.transfer_price,xxx.transfer,xxx.toker,xxx.share_num,xxx.grade";
        $xxx .= ",(CASE WHEN (xxx.transfer_price >= 100000 AND xxx.toker >= 200 AND xxx.transfer >= 100) THEN 1 ELSE 0 END) flag";
        $list = Db::table($sql . 'xxx')
            ->field($xxx)
            ->order('xxx.grade desc')
            ->select();

        foreach ($list as $k=>$v){
            $info=Db::name('activity_order')->where(['channel'=>'missshop','pay_status'=>1,'scene'=>0,'storeid'=>$v['storeid']])->whereTime('insert_time', 'between', ['2019-10-01', $end])->field("pid,sum(num) as count,sum(pay_price) as money")->group('pid')->select();
            if(is_array($info)){
                $mm_hs=0;
                $mm_je=0;
                $xzs_hs=0;
                $xzs_je=0;
                if(array_key_exists(0,$info)){
                    $mm_hs=$info[0]['count'];
                    $mm_je=$info[0]['money'];

                }
                if(array_key_exists(1,$info)){
                    $xzs_hs=$info[1]['count'];
                    $xzs_je=$info[1]['money'];
                }
            }
            $list[$k]['num1']=$mm_hs;
            $list[$k]['money1']=$mm_je;
            $list[$k]['num2']=$xzs_hs;
            $list[$k]['money2']=$xzs_je;
        }

        print_r($list);
    }


}