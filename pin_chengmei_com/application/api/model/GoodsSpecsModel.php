<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2019/11/1
 * Time: 10:06
 */

namespace app\api\model;


use think\Db;
use think\Model;

class GoodsSpecsModel extends Model
{
    protected  $table="goods_specs";

    public function getAllGoodsSpecs($map,$order='id desc'){
       return Db::name($this->table)->where($map)->field('specs_name,specs_item')->order($order)->select();
    }

    public function goodsSpecs($pid,$map,$order='id desc'){
        $res=[];
        $list=Db::name($this->table)->where($map)->field('id,specs_name,specs_item')->order($order)->select();
        if(is_array($list) && count($list)){
            $item=new GoodsSpecsItemModel();
            $specs_info=new GoodsSpecsInfoModel();
            foreach ($list as $k=>$v){
                $res['name']=$v['specs_name'];
                $itemInfo=$item->goodsSpecsItem(['spec_id'=>$v['id']]);
                foreach ($itemInfo as $kk=>$vv){
                    $p_info=$specs_info->getStock(['goods_id'=>$pid,'key'=>$vv['id']],'price,store_count,sku');
                    $itemInfo[$kk]["price"]=$p_info['price'];
                    $itemInfo[$kk]["store_count"]=$p_info['store_count'];
                    $itemInfo[$kk]["sku"]=$p_info['sku'];
                }
                $res['list']=$itemInfo;
            }
        }
        return $res;
    }

    public function goodsSpecs1($pid){
        $filter_spec = array();
        $keys = Db::name('goods_specs_info')->where("goods_id", $pid)->column("GROUP_CONCAT(`key` SEPARATOR '_') ");
        if ($keys) {
            $keys = str_replace('_', ',', $keys);
            $filter_spec2=Db::name('goods_specs')->alias('a')->field('a.specs_name,b.*')->join('goods_specs_item b','a.id=b.spec_id','left')->where('b.id','in',$keys[0])->order('b.id')->select();
            foreach ($filter_spec2 as $key => $val) {
                $filter_spec[$val['specs_name']][] = array(
                    'item_id' => $val['id'],
                    'item' => $val['item'],
                );
            }
        }
        return $filter_spec;

    }


}