<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2019/11/1
 * Time: 13:52
 */

namespace app\api\model;


use think\Db;
use think\Model;

class CartModel extends Model
{
    protected  $table="temp_cart";

    /**
     * 添加至购物车
     * @param $uid
     * @param $ids
     * @param $main_id
     * @return int|string
     */
    public function addCart($uid,$ids,$main_id){
        $goods_list=[];
        $transaction_num=createOrderSn();
        $arr=explode(',',$ids);
        if($arr){
            foreach ($arr as $k=>$v){
                $main_flag=($v==$main_id)?1:0;
                $info=['uid'=>$uid,'transaction_num'=>$transaction_num,'good_id'=>$v,'good_num'=>1,'main_flag'=>$main_flag,'insert_time'=>time()];
                $goods_list[]=$info;
            }
            Db::name($this->table)->insertAll($goods_list);
            return $transaction_num;
        }else{
            return 0;
        }
    }

    /**
     * 获取当前用户或者流水单后下的产品信息
     * @param $uid
     * @param string $transaction_num
     */
    public function cartGoods($uid,$transaction_num=''){
        $map['c.uid']=array('eq',$uid);
        if($transaction_num){
            $map['c.transaction_num']=array('eq',$transaction_num);
        }
        return Db::name($this->table)->alias('c')->join('goods g','c.good_id=g.id')->field('c.*,g.name,images,g.price,g.activity_price,g.recommend_price,g.model_id,g.stock,g.allow_buy_num,g.stock_limit,g.allow_buy_num1,g.given,g.pid,g.storeid')->where($map)->order('main_flag desc')->select();
    }

    /**
     * 获取当前流水单号下的产品id
     * @param $transaction_num
     * @return array
     */
    public function cartGoodsId($uid,$transaction_num=''){
        $map['uid']=array('eq',$uid);
        if($transaction_num){
            $map['transaction_num']=array('eq',$transaction_num);
        }
        return Db::name($this->table)->where($map)->column('good_id');
    }

    /**
     * 删除购物车中的某一个或者全部产品
     * @param $uid
     * @param $good_id
     */
    public function cartGoodsDel($uid,$good_id='',$transaction_num=''){
        $map['uid']=array('eq',$uid);
        if($good_id) {
            $map['good_id'] = array('eq', $good_id);
        }
        if($transaction_num){
            $map['transaction_num']=array('eq',$transaction_num);
        }
        return Db::name($this->table)->where($map)->delete();
    }


    /**
     * 更新购物车中的数量和规格
     * @param $uid
     * @param $good_id
     * @param int $nun
     * @param string $specs
     * @param string $transaction_num
     * @return int|string
     */
    public function cartGoodsUpdate($uid,$good_id,$nun=1,$specs='',$transaction_num=''){
        $map['uid']=array('eq',$uid);
        $map['good_id']=array('eq',$good_id);
        if($transaction_num){
            $map['transaction_num']=array('eq',$transaction_num);
        }
        if($specs){
            $upd['good_param']=$specs;
        }
        $upd['good_num']=$nun;
        return Db::name($this->table)->where($map)->update($upd);
    }





}