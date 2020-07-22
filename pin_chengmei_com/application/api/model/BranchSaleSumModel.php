<?php

namespace app\api\model;
use think\Model;
use think\Db;

class BranchSaleSumModel extends Model
{

    protected  $table="branch_sale_sum";
    /**
     * 根据搜索条件获取门店数据
     */
    public function getSum($map)
    {
        $total=Db::name($this->table)->where($map)->sum('total');
        //昨天销量
        $map['d_time']=array('eq',date('Y-m-d',strtotime("-1 day")));
        $yesterday=Db::name($this->table)->where($map)->sum('total');
        //前天销量
        $map['d_time']=array('eq',date('Y-m-d',strtotime("-2 day")));
        $before_yesterday=Db::name($this->table)->where($map)->sum('total');
        if($yesterday>$before_yesterday){
            $tip_text='恭喜您，业绩比前一天增加'.($yesterday-$before_yesterday).'元';
        }else{
            $tip_text='遗憾，您的销售业绩比前一天没有增加，加油！';
        }
        return ['total'=>$total,'yesterday'=>$yesterday,'before_yesterday'=>$before_yesterday,'tip_text'=>$tip_text];
    }

    /**
     * 获取销售额趋势
     * @param $map
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getSaleData($map){
        $getday=$this->getDay();
        $map['d_time']=array('between',$getday);
        return Db::name($this->table)->where($map)->field('d_time,sum(total) total')->group('d_time')->select();
    }

    /**
     * 获取前三十天的日期
     * @return array
     */
    public function getDay(){
        $current_day=date('Y-m-d',strtotime("-1 day"));
        $history_day=date("Y-m-d",strtotime("-31 day"));
        return [$history_day,$current_day];
    }



}