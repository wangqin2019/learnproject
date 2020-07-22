<?php

namespace app\blink\model;
use think\Model;
use think\Db;

class GoodsModel extends Model
{
    protected $name = 'goods';

    protected  $goods="goods";
    /**
     * 根据搜索条件获取产品列表信息
     */
    public function getGoodsByWhere($map)
    {
        return Db::name($this->goods)->where($map)->order('id desc')->field('id,name,price,intro,image,unit')->select();
    }


    public function getGoodsInfo($id)
    {
        return Db::name($this->goods)->where('id',$id)->field('id,name,price,intro,image,images,content,unit')->find();
    }

    /**
     * 根据搜索条件获取产品数量
     * @param $where
     */
    public function getAllBranch($where)
    {
        return Db::name($this->goods)->where($where)->count();
    }


    /**
     * 根据产品id获取信息
     * @param $id
     */
    public function getOneInfo($id)
    {
        return Db::name($this->table)->where('id', $id)->field('id,title,sign,location_p,location_c,location_a,address,lng,lat')->find();
    }

    //根据条件获取全部列
    public function getGoodsColumn($map,$column){
        return Db::name($this->goods)->where($map)->group($column)->column($column);

    }

    /**
     * 返回全部列
     * @param $map
     * @param string $order
     */
    public function getActivityGoods($map,$order='id desc'){
        return Db::name($this->goods)
            ->where($map)
            ->field('id,name,image,images,price,activity_price,recommend_price,model_id,stock,allow_buy_num,stock_limit,allow_buy_num1,given,pid')
            ->order($order)
            ->select();
    }

    /**
     * 获取一条数据
     * @param $id
     */
    public function getGoods($id){
        return Db::name($this->goods)
            ->field('id,name,images,xc_images,price,activity_price,recommend_price,model_id,content,status,stock,video,allow_buy_num,stock_limit,allow_buy_num1,given,pid,storeid,buy_type')
            ->where('id',$id)
            ->find();
    }


    public static function getGoodsInfoID($id = ''){
        return self::where('id',$id)->find()->toArray();
    }





}