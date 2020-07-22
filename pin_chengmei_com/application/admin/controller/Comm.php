<?php

namespace app\admin\controller;
use app\admin\model\UserType;
use think\Controller;
use think\Db;
use org\Verify;
use com\Geetestlib;

class Comm extends Controller{

    /**
     * 获取活动正常售卖产品
     * @return string
     */
    public function storeGoodsList(){
        $map['status']=array('eq',1);
        $map['goods_cate']=array('eq',3);
        $pro=Db::name('goods')->where($map)->field('id,name,price,intro,image')->select();
        if(count($pro)){
            $res['code']=1;
            $res['data']=$pro;
        }else{
            $res['code']=0;
            $res['data']='';
        }
        return json_encode($res);
    }

    /**
     * 获取活动正常售卖产品
     * @return string
     */
    public function storeGoodsInfo(){
        $map['id']=array('eq',input('param.pid'));
        $pro=Db::name('goods')->where($map)->field('id,name,price,intro,image')->find();
        if(count($pro)){
            $res['code']=1;
            $res['data']=$pro;
        }else{
            $res['code']=0;
            $res['data']='';
        }
        return json_encode($res);
    }


    /**
     * 获取门店下美容师
     * @return string
     */
    public function storeSellerList(){
        $map['storeid']=array('eq',input('param.store_id'));
        $map['isadmin']=array('neq',1);
        $list=Db::table('ims_bj_shopn_member')->where($map)->field('id,code,staffid,realname,mobile')->select();
        $seller=[];
        foreach ($list as $key=>$val){
            if(strlen($val['code'])>1 && $val['id']=$val['staffid']){
                $seller[]=$val;
            }
        }
        if(count($seller)){
            $res['code']=1;
            $res['data']=$seller;
        }else{
            $res['code']=0;
            $res['data']='';
        }
        return json_encode($res);
    }
    /**
     * 获取美容师下顾客
     * @return string
     */
    public function SellerCustomer(){
        $map['staffid']=array('eq',input('param.staffid'));
        $map['id']=array('neq',input('param.staffid'));
        $list=Db::table('ims_bj_shopn_member')->where($map)->field('id,code,staffid,realname,mobile')->select();
        if(count($list)){
            $res['code']=1;
            $res['data']=$list;
        }else{
            $res['code']=0;
            $res['data']='';
        }
        return json_encode($res);
    }

}
