<?php
namespace app\api\controller;
use app\api\model\MemberModel;
use think\Controller;
use think\Db;
use weixinaes\wxBizDataCrypt;
header('Access-Control-Allow-Origin:*');
/**
 * swagger: 2019Pk签到
 */
class PkSign extends Base
{
    //根据办事处获取门店信息
    public function getBranch(){
        $bsc=input('param.bsc');
        if($bsc!=''){
            $list=Db::name('pk_store')->where(['bsc_name'=>$bsc])->group('cus_title')->order('cus_sign')->select();
            $code = 1;
            $data = $list;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '办事处名称不能为空';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    //签到
    public function reg(){
        $bsc_name=input('param.bsc_name');//办事处名称
        $cus_sign=input('param.cus_sign');//客户编码
        $cus_title=input('param.cus_title');//客户名称
        $seller_name=input('param.seller_name');//美容师名称
        $seller_sex=input('param.seller_sex');//美容师性别
        $seller_tel=input('param.seller_tel');//美容师电话
        $back_date=input('param.back_date');//返程时间
        $back_station=input('param.back_station');//返程车站
        if($seller_name!='' || $seller_tel!=''){
            $memberInfo=Db::table('ims_bj_shopn_member')->where('mobile',$seller_tel)->find();
            //美容师不存在将注册到missShop门店
            if(!count($memberInfo)){
                $sellerData = array('weid' => 1, 'storeid' => 1550, 'code'=>'11111111', 'realname' => $seller_name, 'mobile' => $seller_tel, 'createtime' => time(), 'level' => 1, 'fg_viprules' => 1, 'fg_vipgoods' => 1, 'id_regsource' => 7, 'relation_bind' => 1, 'activity_flag' => 8888);
                $sellerId = Db::table('ims_bj_shopn_member')->insertGetId($sellerData);
                Db::table('ims_bj_shopn_member')->where('id',$sellerId)->update(['pid' => $sellerId, 'staffid' => $sellerId]);
                $sellerStore=1550;
            }else{
                $sellerId=$memberInfo['id'];
                $sellerStore=$memberInfo['storeid'];
            }
            //记录签到信息
            $signCheck=Db::name('pk_reg')->where(['uid'=>$sellerId])->count();
            if(!$signCheck){
                $signData=array('uid'=>$sellerId,'sid'=>$sellerStore,'bsc_name'=>$bsc_name,'cus_sign'=>$cus_sign,'cus_title'=>$cus_title,'seller_name'=>$seller_name,'seller_sex'=>$seller_sex,'seller_tel'=>$seller_tel,'back_date'=>$back_date,'back_station'=>$back_station,'insert_time'=>time());
                Db::name('pk_reg')->insert($signData);
                $code = 1;
                $data = '';
                $msg = '签到成功';
            }else{
                $code = 0;
                $data = '';
                $msg = '您已经签过到了，请勿重复签到！';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数不完整';
        }
        return parent::returnMsg($code,$data,$msg);
    }
}