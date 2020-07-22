<?php

namespace app\api\controller;
use think\Controller;
use think\Db;

/**
 * swagger: 用户地址
 */
class UserAddress extends Base
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
    /*
     * 获取用户收货地址
     */
    public function myAddress(){
        $uid=input('param.uid');
        $list=Db::table('ims_bj_shopn_member_address')->where('user_id',$uid)->field('')->select();
       if($list){
           if($list){
               foreach ($list as $k=>$v){
                   $list[$k]['province']=$this->getNameByParentId($v['province']);
                   $list[$k]['city']=$this->getNameByParentId($v['city']);
                   $list[$k]['district']=$this->getNameByParentId($v['district']);
                   $list[$k]['street']=$this->getNameByParentId($v['street']);
               }
           }
           $code = 1;
           $data = $list;
           $msg = '获取成功';
       }else{
           $code = 0;
           $data = '';
           $msg = '暂无收货地址';
       }
        return parent::returnMsg($code,$data,$msg);
    }
    /*
     * 获取地址详细
     */
    public function addressInfo(){
        $id=input('param.id');
        $info=Db::table('ims_bj_shopn_member_address')->where('id',$id)->field('')->find();
        if($info){
            $info['province']=array('id'=>$info['province'],'name'=>$this->getNameByParentId($info['province']));
            $info['city']=array('id'=>$info['city'],'name'=>$this->getNameByParentId($info['city']));
            $info['district']=array('id'=>$info['district'],'name'=>$this->getNameByParentId($info['district']));
            $info['street']=array('id'=>$info['street'],'name'=>$this->getNameByParentId($info['street']));
            $code = 1;
            $data = $info;
            $msg = '获取成功';
        }else{
            $code = 0;
            $data = '';
            $msg = '暂无收货地址';
        }
        return parent::returnMsg($code,$data,$msg);
    }


    /*
     * 新增获取用户收货地址
    */
    public function addAddress(){
        $user_id=input('param.user_id');
        $consignee=input('param.consignee');
        $mobile=input('param.mobile');
        $province=input('param.province');
        $city=input('param.city');
        $district=input('param.district');
        $street=input('param.street');
        $address=input('param.address');
        $is_default=input('param.is_default',0);
        $user=Db::table('ims_bj_shopn_member')->where('id',$user_id)->count();
       if($user && $consignee!='' && $mobile!='') {
           $addressCount=Db::table('ims_bj_shopn_member_address')->where('user_id',$user_id)->count();
           if($addressCount<1) {
               $data = ['user_id' => $user_id, 'consignee' => $consignee, 'mobile' => $mobile, 'province' => $province, 'city' => $city, 'district' => $district, 'street' => $street, 'address' => $address, 'is_default' => $is_default, 'create_time' => date('Y-m-d H:i:s')];
               $result = Db::table('ims_bj_shopn_member_address')->insertGetId($data);
               if (false === $result) {
                   $code = 0;
                   $data = '';
                   $msg = '添加失败';
               } else {
                   if ($is_default) {
                       $map['user_id'] = array('eq', $user_id);
                       $map['id'] = array('neq', $result);
                       Db::table('ims_bj_shopn_member_address')->where($map)->setField('is_default', 0);
                   }
                   $code = 1;
                   $data = '';
                   $msg = '新增成功';
               }
           }else{
               $code = 0;
               $data = '';
               $msg = '只允许添加1条收货地址';
           }
       }else{
           $code = 0;
           $data = '';
           $msg = '参数错误';
       }
        return parent::returnMsg($code,$data,$msg);
    }

    /*
    * 修改用户收货地址
   */
    public function editAddress(){
        $id=input('param.id');
        $user_id=input('param.user_id');
        $consignee=input('param.consignee');
        $mobile=input('param.mobile');
        $province=input('param.province');
        $city=input('param.city');
        $district=input('param.district');
        $street=input('param.street');
        $address=input('param.address');
        $is_default=input('param.is_default',0);
        $user=Db::table('ims_bj_shopn_member')->where('id',$user_id)->count();
        if($user && $consignee!='' && $mobile!='') {
            $data = ['user_id' => $user_id, 'consignee' => $consignee, 'mobile' => $mobile, 'province' => $province, 'city' => $city, 'district' => $district, 'street' => $street, 'address' => $address, 'is_default' => $is_default, 'update_time' => date('Y-m-d H:i:s')];
            $result = Db::table('ims_bj_shopn_member_address')->where('id',$id)->update($data);
            if (false === $result) {
                $code = 0;
                $data = '';
                $msg = '添加失败';
            } else {
                if ($is_default) {
                    $map['user_id'] = array('eq', $user_id);
                    $map['id'] = array('neq', $id);
                    Db::table('ims_bj_shopn_member_address')->where($map)->setField('is_default', 0);
                }
                $code = 1;
                $data = '';
                $msg = '修改成功';
            }
        }else{
            $code = 0;
            $data = '';
            $msg = '参数错误';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 获取省
     */
    public function getProvince()
    {
        $province = Db::table('sys_region')->field('id,name')->where(array('level' => 1))->cache(true)->select();
        $code = 1;
        $data = $province;
        $msg = '获取成功';
        return parent::returnMsg($code,$data,$msg);
    }

    /**
     * 获取市或者区县
     */
    public function getRegionByParentId()
    {
        $parent_id = input('parent_id');
        $code = 1;
        $data = '';
        $msg = '获取失败，参数错误';
        if($parent_id){
            $region_list = Db::table('sys_region')->field('id,name')->where(['parent_id'=>$parent_id])->select();
            $code = 1;
            $data = $region_list;
            $msg = '获取成功';
        }
        return parent::returnMsg($code,$data,$msg);
    }

    public function getNameByParentId($id){
       return Db::table('sys_region')->where(['id'=>$id])->value('name');
    }

}