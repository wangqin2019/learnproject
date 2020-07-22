<?php
namespace app\api_public\controller;
use think\Db;
class Index extends Base
{
   //当前号码是否允许购买
   public function check_restrict()
   {
       $mobile=input('param.mobile');
       $money=input('param.money',0);
       if($mobile && $mobile!==""){
           try {
               $mobile = trim($mobile);//手机号码过滤
               //白名单
               $whiteList=config('order_allow_mobile');
               if(strlen($whiteList)>0){
                   $whiteAr=explode('#',$whiteList);
                   if(in_array($mobile,$whiteAr)){
                       return json(['code' => 1, 'msg' => '允许购买']);
                   }
               }
               //1.调取钉钉接口，检测是否是总部人员或者办事处人员
               $getMobile = self::$redis->exists('staff_' . $mobile);
               if ($getMobile) {
                   return json(['code' => 0, 'msg' => '公司内部人员不允许购买！']);
               }
               //2.调用erp接口，检测是否是法人
               $url1 = config('erpUrl') . "/web/index.php?mobile=" . $mobile;
               $res1 = curl_get($url1);
               $resToArr1 = json_decode($res1, true);
               if ($resToArr1['msg'] == "success" && $resToArr1['obj'] && $resToArr1['status'] == "200") {
                   return json(['code' => 0, 'msg' => '门店法人不允许购买！']);
               }
               //3.如果是美容师 检测是否当前购买限额是否超过6万 超6万不允许购买
               $userInfo=Db::table('ims_bj_shopn_member')->field('id,mobile,isadmin,staffid,code')->where('mobile',$mobile)->find();
               //$userInfo = Db::connect('second_db')->table('ims_bj_shopn_member')->field('id,mobile,isadmin,staffid,code')->where('mobile', $mobile)->find();
               if ($userInfo['isadmin'] == 1 || ($userInfo['id'] == $userInfo['staffid'] && strlen($userInfo['code']) > 1)) {
                   $currentYear = date('Y');
                   $map['sellerid'] = array('eq', $userInfo['id']);
                   $map['year'] = array('eq', $currentYear);
                   $countAmount=Db::name('purchase_amount')->where($map)->field('count')->find();
                   //$countAmount = Db::connect('second_db')->name('purchase_amount')->where($map)->field('count')->find();
                   if (count($countAmount) && is_array($countAmount)) {
                       if (($countAmount['count'] + $money) > 60000) {
                           $count=60000 - $countAmount['count'];
                           if($count>0){
                               $tip='您当年还剩'.$count.'元购买额度！';
                           }else{
                               $tip='您当年的购买额度已用完！';
                           }
                           return json(['code' => 0, 'msg' => $tip]);

                       }
                   } else {
                       //Db::connect('second_db')->name('purchase_amount')->insert(['sellerid' => $userInfo['id'], 'count' => 0, 'year' => $currentYear]);
                       Db::name('purchase_amount')->insert(['sellerid'=>$userInfo['id'],'count'=>0,'year'=>$currentYear]);
                       if ($money > 60000) {
                           $tip='您当年还剩60000元购买额度！';
                           return json(['code' => 0, 'msg' => $tip]);
                       }
                   }
               }
               return json(['code' => 1, 'msg' => '允许购买']);
           }catch (\Exception $e){
               file_put_contents('api_public.txt',$e->getMessage(),FILE_APPEND);
               return json(['code' => 1, 'msg' => '允许购买!']);
           }
       }else{
           return json(['code' => 0, 'msg' => '参数错误']);
       }
   }

}
