<?php

namespace app\api\controller;
use My\Kuaidiniao;
use think\Controller;
use think\Db;

/**
 * swagger: 订单
 */
class Order extends Base
{
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
        if($token==''){
            $code = 400;
            $data = '';
            $msg = '非法请求';
            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
            exit;
        }else{
            if(!parent::checkToken($token)) {
                $code = 400;
                $data = '';
                $msg = '用户登陆信息过期，请重新登录！';
                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
                exit;
            }else{
                return true;
            }
        }
    }
    //检查订单中的产品是否支持安心送
   public function checkOrder(){
        $storeid=input('param.storeid');
        $ids=input('param.ids');
        if(strlen($ids)){
           $branch_axs=Db::name('axs_branch')->where('store_id',$storeid)->count();
            if($branch_axs) {
                $arr = explode(',', $ids);
                $map['store_id'] = array('eq', $storeid);
                $map['goods_id'] = array('in', $arr);
                $axs = Db::name('axs_branch')->where($map)->column('goods_id');
                $diff = array_diff($arr, $axs);
                if (count($diff)) {
                    $goods = Db::name('goods')->where('id', 'in', $diff)->column('name');
                    $code = 1;
                    $data = ['is_axs' => 0, 'list' => $goods];
                    $msg = '部分产品不支持安心送';
                } else {
                    $code = 1;
                    $data = ['is_axs' => 1, 'list' => []];
                    $msg = '可以选择安心送';
                }
            }else{
                $code = 0;
                $data = [];
                $msg = '门店未开启安心送';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '产品不允许为空';
        }
       return parent::returnMsg($code,$data,$msg);
   }

   //下单后选择安心送
   public function orderAddress(){
        $order_sn=input('param.order_sn');
        $address_id=input('param.address_id');
        $order=Db::name('activity_order')->alias('o')->join(['ims_bj_shopn_member' => 'm'], 'o.uid=m.id', 'left')->field('o.order_sn,m.mobile')->where(['order_sn'=>$order_sn,'pay_status'=>1])->find();
        if($order){
            $info=Db::table('ims_bj_shopn_member_address')->where('id',$address_id)->find();
            if($info){
                try {
                    $address = ['order_sn' => $order_sn, 'consignee' => $info['consignee'], 'mobile' => $order['mobile'], 'province' => $info['province'],
                        'city' => $info['city'], 'district' => $info['district'], 'street' => $info['street'], 'address' => $info['address'], 'create_time' => date('Y-m-d H:i:s')];
                    Db::name('activity_order_address')->insert($address);
                    Db::name('activity_order')->where('order_sn',$order_sn)->setField('is_axs',1);
                    $code = 1;
                    $data = '';
                    $msg = '订单已开启安心送';
                }catch (\Exception $e){
                    $code = 0;
                    $data = '';
                    $msg = '订单安心送';
                }
                return parent::returnMsg($code,$data,$msg);
            }else{
                $code = 0;
                $data = '';
                $msg = '收货地址错误';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '订单号不存在';
        }
       return parent::returnMsg($code,$data,$msg);
   }
    /*
     * 物流信息
     */
	   public function getDelivery(){
        $order_sn=input('param.order_sn');
        $info=Db::name('activity_order_address')->where('order_sn',$order_sn)->find();
        if($info){
            if(strlen($info['express_number'])){
                $expressNumber=explode(',',$info['express_number']);
                $kdn=new Kuaidiniao(config("kdn.eBusinessID"),config("kdn.appKey"));
                $expressInfo=[];
                foreach ($expressNumber as $k=>$v){
                    $check = Db::table('ims_bj_shopn_order_express')->where(['logistic_code'=>$v,'type'=>1])->find();
                    if($check){
                        $expressInfo[$k]=json_decode($check['traces'],true);
                    }else{
                        $getInfo=$kdn->getOrderTracesByJson(config("kdn.reqURL"),$info['express_code'],$v,$info['mobile']);
                        $expressInfo[$k]['express_name']=$info['express_name'];
                        $expressInfo[$k]['express_number']=$v;
                        $expressArr=json_decode($getInfo,true);
                        $expressInfo[$k]['express_info']=isset($expressArr['Traces'])?$expressArr['Traces']:[];
                        $expressInfo[$k]['express_state']=$expressArr['State'];
                        if($expressArr['State']==3){
                            $arrEnd=end($expressArr['Traces']);
                            Db::name('activity_order')->where('order_sn',$order_sn)->setField('order_status',1);
                            //将物流信息存储到物流表
                            $data=['address_id'=>$info['id'],'e_business_id'=>$expressArr['EBusinessID'],'order_code'=>$info['order_sn'],'shipper_code'=>$expressArr['ShipperCode'],'logistic_code'=>$expressArr['LogisticCode'],'state'=>$expressArr['State'],'success'=>$expressArr['Success'],'traces'=> json_encode($expressInfo[$k]),'sign_for_time'=>$arrEnd['AcceptTime'],'create_time'=>date('Y-m-d H:i:s'),'type'=>1];
                            Db::table('ims_bj_shopn_order_express')->insert($data);
                        }
                    }
                }
                $code = 1;
                $data = $expressInfo;
                $msg = '获取成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '暂无物流信息';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '该订单不支持物流查询';
        }
       return parent::returnMsg($code,$data,$msg);
   }


}