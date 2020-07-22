<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2020/5/6
 * Time: 14:11
 */

namespace app\api\controller;

use think\Cache;
use think\Db;

class U8 extends Base
{
    /**
     * Notes:将需要插入U8中的订单编码插入集合中  等待处理
     * User: HOUDJ
     * Date: 2020/5/6
     * Time: 14:13
     */
    public function getInsertU8Order(){
        //获取能进入erp的活动
        $aids=Db::name('activity_list')->where(['activity_status'=>1,'auto_erp'=>1])->column('id');
        $map['u8_flag']=['eq',0];
        $map['pay_status']=['eq',1];
        $map['pay_time']=['egt','1589176923'];
        $map['scene']=['in',$aids];
        $orders=Db::name('activity_order')->where($map)->where('scene','egt','18')->column('order_sn');
        if(count($orders)){
            foreach ($orders as $k=>$v){
                parent::saddset('u8OrderQuene',$v);
            }
        }
    }




    /**
     * Notes:getToken
     * User: HOUDJ
     * Date: 2020/4/30
     * Time: 10:29
     * @return mixed
     */
    public function getToken(){
        if(Cache::get('u8Token')){
            return Cache::get('u8Token');
        }else{
            $token='';
            $url=config('u8.url').'/uaa/auth/u8login?UserId='.config('u8.user').'&Password='.config('u8.pass');
            $result = http_post($url);
            $res=json_decode($result,true);
            if($res['status']==0){
                $token=$res['token'];
                Cache::set('u8Token',$token,1600);
            }
            return $token;
        }
    }

    /**
     * Notes:u8订单插入
     * User: HOUDJ
     * Date: 2020/4/30
     * Time: 11:28
     */
    public function orderInsert(){
        set_time_limit(0);
        $orderSn=parent::getMembersByNum('u8OrderQuene',20);
        //$orderSn=input('param.ordersn',parent::getMembers('u8OrderQuene',1));
        //$orderSn=parent::getMembers('u8OrderQuene',1);
        if($orderSn) {
            $orders = explode(',', $orderSn);
            $getToken = $this->getToken();
            $headers[] = "Content-Type:application/json";
            $headers[] = "Authorization:" . $getToken;
            $orderList = [];
            foreach ($orders as $k => $v) {
                $info = Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'member'], 'member.id=o.uid', 'left')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->field('o.order_sn,o.scene,o.pay_time,o.is_axs,o.num,o.pid,bwk.title,bwk.sign,bwk.receive_address,bwk.receive_consignee,bwk.receive_mobile,depart.st_department')->where(['o.order_sn' => $v])->find();
                if ($info) {
                    //如果是安心送订单 取具体收货人的收货信息  如果不是 取门店收货信息
                    if ($info['is_axs']) {
                        $delivery = Db::name('activity_order_address')->where('order_sn', trim($info['order_sn']))->find();
                        $receive_consignee = $delivery['mobile'];
                        $receive_mobile = $delivery['mobile'];
                        $receive_address = $this->getNameByParentId($delivery['province']) . $this->getNameByParentId($delivery['city']) . $this->getNameByParentId($delivery['district']) . $this->getNameByParentId($delivery['street']) . $delivery['address'];
                    } else {
                        $receive_consignee = $info['receive_consignee'];
                        $receive_mobile = $info['receive_mobile'];
                        $receive_address = $info['receive_address'];
                    }
                    $data = [];
                    $data['ccuscode'] = substr($info['sign'],0,7);//门店编码
                    $data['cdefine2'] = '日常发货';//发货类型
                    $data['cstname'] = '院线正常销售';//销售类型
                    $data['chdefine6'] = $receive_consignee;//顾客姓名
                    $data['chdefine7'] = $receive_mobile;//顾客电话
                    $data['ccusaddress'] = $receive_address;//收货地址
                    $data['chdefine38'] = $info['order_sn'];//安心送编号
                    $data['ddate'] = date('Y-m-d', $info['pay_time']);//支付日期
                    $data['cpersoncode'] = '';//业务员 某某活动
                    $data['chdefine14'] = '';//发票类型
                    $data['cdefine9'] = '';//是否代扣运费 是 否
                    $data['cdefine8'] = '';//是否新店 是 否
                    $data['cdefine10'] = config('activity_list.' . $info['scene']);//活动类型 不同活动传值不同
                    $data['cdefine11'] = '';//配送信息
                    $data['cbustype'] = '分期收款';//业务类型 默认分期收款
                    $data['cdefine12'] = "";//打款信息
                    $data['cmemo'] = "";//备注信息
                    $data['chdefine37'] = '现销';//销售方式 默认现销 必填
                    $data['csscode'] = '';//付款方式编码
                    //$data['cdepname'] = strlen($info['st_department'])< 4 ?  $info['st_department'] . '办事处':'专业线销售部总部';//销售部门
                    //获取订单详情
                    $details = [];
                    $orderInfo = Db::name('activity_order_info')->where('order_sn', $info['order_sn'])->select();
                    if ($orderInfo) {
                        foreach ($orderInfo as $val1) {
                            $personality = Db::name('goods_personality')->where('goods_code', $val1['good_specs_sku'])->find();
                            if ($personality) {
                                $goods_price = $personality['goods_price'];
                                $goods_num = $personality['goods_num'];
                            } else {
                                $goods_price = $val1['good_price'];
                                $goods_num = 1;
                            }
                            //cinvcode=>购买产品 iquantity=>1 kl=>扣率  cdefine22=>'销售发货/xxx配赠'
                            $details[] = ["cinvcode" => $val1['good_specs_sku'], "iquantity" => $val1['good_num'] * $goods_num, "iprice" => $val1['flag'] ? 0 : $goods_price, "kl" => "38", "cdefine22" => $val1['flag'] ? $val1['give_cate'] : '销售发货'];
                        }
                    } else {
                        $getInfo = Db::name('goods')->where('id', $info['pid'])->field('name,goods_code,activity_price')->find();
                        $details[] = ["cinvcode" => $getInfo['goods_code'], "iquantity" => $info['num'], "iprice" => $getInfo['activity_price'], "kl" => "38", "cdefine22" => '销售发货'];
                    }
                    $data['details'] = $details;
                }
                $orderList[] = $data;
            }
            $beginTime=date('Y-m-d H:i:s');
            $url = config('u8.url') . '/saleorder/save';
            $result = http_post($url,json_encode($orderList), false, $headers);
            $endTime=date('Y-m-d H:i:s');
            $logs='执行开始时间：'.$beginTime.' 执行结束时间:'.$endTime.PHP_EOL.'执行url：'.$url.PHP_EOL.'执行结果：'.$result.PHP_EOL;
            logs($logs,'u8_Insert');
            $resultArr = json_decode($result, true);
            if (count($resultArr) && $resultArr['status'] == 0) {
                foreach ($resultArr['data'] as $key => $val) {
                    if ($val['bSucces']) {
                        Db::name('activity_order')->where('order_sn', $val['id'])->update(['u8_flag' => 3]);
                    } else {
                        $err_list = count($val['errlist']) ? implode(',', $val['errlist']) : '';
                        Db::name('activity_order')->where('order_sn', $val['id'])->update(['u8_flag' => 2, 'u8_err' => $val['msg'] . $err_list]);
                    }
                }
            }
        }
    }

    /**
     * Notes:定时检测U8订单插入结果
     * User: HOUDJ
     * Date: 2020/5/18
     * Time: 9:56
     */
    public function checkResult(){
        set_time_limit(0);
        $list = Db::name('activity_order')->where(['u8_flag'=>3])->field('id,order_sn')->limit(50)->select();
        if($list){
            foreach ($list as $k=>$v){
                $beginTime=date('Y-m-d H:i:s');
                $getToken = $this->getToken();
                $headers[] = "Content-Type:application/json";
                $headers[] = "Authorization:" . $getToken;
                $url = config('u8.url') . '/saleorder/query?code='.$v['order_sn'];
                $result = http_post($url, [], 'false', $headers);
                $resultArr = json_decode($result, true);
                if (count($resultArr)) {
                    if ($resultArr['bSucces']) {
                        Db::name('activity_order')->where('order_sn', $resultArr['id'])->update(['u8_flag' => 1,'u8_err' =>'已导入']);
                    } else {
                        $err_list = count($resultArr['errlist']) ? implode(',', $resultArr['errlist']) : '';
                        Db::name('activity_order')->where('order_sn', $resultArr['id'])->update(['u8_flag' => 2, 'u8_err' => $resultArr['msg'] . $err_list]);
                    }
                }
                $endTime=date('Y-m-d H:i:s');
                $logs='执行开始时间：'.$beginTime.' 执行结束时间:'.$endTime.PHP_EOL.'执行url：'.$url.PHP_EOL.'执行结果：'.$result.PHP_EOL;
                logs($logs,'u8_Insert_check');
            }
        }
    }





    /**通过城市编码获取代表城市
     * Notes:
     * User: HOUDJ
     * Date: 2020/5/6
     * Time: 13:13
     * @param $id
     * @return mixed
     */
    public function getNameByParentId($id){
        return Db::table('sys_region')->where(['id'=>$id])->value('name');
    }

}