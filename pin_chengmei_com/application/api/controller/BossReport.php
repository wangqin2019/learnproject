<?php
/**
 * Created by PhpStorm.
 * User: houdj
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\api\controller;
use app\api\model\BranchSaleSumModel;
use app\api\model\BranchSellerSaleSumModel;
use app\api\model\BranchUserSumModel;
use think\Cache;
use think\Db;
use think\Debug;

/**
 * swagger: 统计数据分析-访问概况
 */
class BossReport extends Base
{
    public function _initialize() {
        parent::_initialize();
        $token = input('param.token');
        /* if($token==''){
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
         }*/
    }
    public function report(){
        $role=input('param.role',0);// 0=>店老板 1=>诚美管理
        $storeid=input('param.storeid',0);//门店编码
        $type=input('param.type',0);//0=>销售业绩及趋势 1=>美容师 2=>门店及趋势
        if($storeid){
            $map=[];
            if($role==0){
                $map['storeid']=$storeid;
            }
            $res=[];
            switch ($type){
                case 1://美容师销售
                    $seller=new BranchSellerSaleSumModel();
                    //$between=$seller->getDay();
                    //$res['res_between']=$between[0].'至'.$between[1];
                    $res['res_list']=$seller->getSellerData($role,$storeid);
                    break;
                case 2://门店及趋势
                    $user=new BranchUserSumModel();
                    $res['res_total']=$user->getSum($map);
                    $between=$user->getDay();
                    $res['res_between']=$between[0].'至'.$between[1];
                    $res['res_list']=$user->getUserData($map);
                    break;
                default://销售业绩及趋势
                    $sale=new BranchSaleSumModel();
                    $res['res_total']=$sale->getSum($map);
                    $between=$sale->getDay();
                    $res['res_between']=$between[0].'至'.$between[1];
                    $res['res_list']=$sale->getSaleData($map);
            }
            return parent::returnMsg(1,$res,'获取成功');
        }else{
            return parent::returnMsg(0,'','门店id不能为空');
        }
    }
}