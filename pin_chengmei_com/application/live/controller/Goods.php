<?php

namespace app\live\controller;
use app\api\model\BranchModel;
use app\api\model\GoodsModel;
use app\api\model\MemberModel;
use app\api\model\PintuanModel;
use org\QRcode;
use think\Config;
use think\Controller;
use think\Db;

/**
 * 直播商品
 */
class Goods extends Base
{

    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
//        if($token==''){
//            $code = 400;
//            $data = '';
//            $msg = '非法请求';
//            echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
//            exit;
//        }else{
//            if(!parent::checkToken($token)) {
//                $code = 400;
//                $data = '';
//                $msg = '用户登陆信息过期，请重新登录！';
//                echo json_encode(array('code'=>$code,'data'=>$data,'msg'=>$msg));
//                exit;
//            }else{
//                return true;
//            }
//        }
    }

    /**
     * 获取直播列表呢
     */
    public function goodsList()
    {
        $mobile=input('param.mobile');
        if($mobile){
			if($mobile=='15888888888'){
				 $code = 1;
                 $data=[];
                 $msg = '获取成功';
				 return parent::returnMsg($code,$data,$msg);
			}
          $postUrl = 'https://api-app.qunarmei.com/qunamei/goodslist';
          //$postUrl = 'test.api.app.qunarmei.com/qunamei/goodslist';
          try{
              $live=Db::name('live_url')->where('id',1)->field('goods_show,show_end,live_mobile')->find();
              //获取允许购买的门店
//              $allowBuyStoreArr=[];
              $allowBuyStore=Db::table('think_live_see_conf')->where('mobile',$live['live_mobile'])->value('store_signs');
              if(strlen($allowBuyStore)){
                  $allowBuyStoreArr=explode(',',$allowBuyStore);
              }else{
                  $allowBuyStoreArr=['666-666'];
              }
              $info=Db::table('ims_bj_shopn_member')->alias('m')->join(['ims_bwk_branch'=>'b'],'m.storeid=b.id','left')->where('m.mobile',$mobile)->field('m.id,m.storeid,b.sign')->find();
              if($live['goods_show']){
                  $endDate=$live['show_end'];
                  if(time()> $endDate){
                      Db::name('live_url')->where('id',1)->update(['flag'=>0,'goods_show'=>0]);
                  }
                  if($allowBuyStoreArr && in_array($info['sign'],$allowBuyStoreArr) && time()< $endDate){
                      $map['uid'] = $info['id'];
                      $map['idstore'] =$info['storeid'];
                      $map['idcategory'] = 31;
                      $map['liveFlag'] = 1;
                      $getData = sendPost($postUrl,$map);
                      $getDataArr= json_decode($getData,true);
                      if($getDataArr['code']=='S_000001' && $getDataArr['msg']=='成功'){
                          $code = 1;
                          $data=$getDataArr['obj'];
                          $msg = '获取成功';
                      }else{
                          $code = 0;
                          $data = '';
                          $msg = $getDataArr['msg'];
                      }
                  }else{
                      $code = 1;
                      $data=[];
                      $msg = '获取成功';
                      return parent::returnMsg($code,$data,$msg);
                  }
              }else{
                  $code = 1;
                  $data=[];
                  $msg = '获取成功';
                  return parent::returnMsg($code,$data,$msg);
              }
          }catch (\Exception $e){
              $code = 0;
              $data = '';
              $msg = '网络错误，请重试！'.$e->getMessage();
          }
        }else{
            $code = 0;
            $data = '';
            $msg = '网络错误，请重试！';
        }
        return parent::returnMsg($code,$data,$msg);
    }
}