<?php

namespace app\blink\model;
use think\Model;
use think\Db;

class BranchUserSumModel extends Model
{

    protected  $table="branch_user_sum";
    /**
     * 根据搜索条件获取用户数据
     */
    public function getSum($map)
    {
        $map['d_time']=array('eq',date('Y-m-d',strtotime("-1 day")));
        $total_query=Db::name($this->table)->where($map)->sum('total');
        $total=$total_query?$total_query:0;
        //昨天用户数
        $yesterday_query=Db::name($this->table)->where($map)->sum('total');
        $yesterday=$yesterday_query?$yesterday_query:0;
        //前天用户数
        $map['d_time']=array('eq',date('Y-m-d',strtotime("-2 day")));
        $before_yesterday_query=Db::name($this->table)->where($map)->sum('total');
        $before_yesterday=$before_yesterday_query?$before_yesterday_query:0;
        if($yesterday>$before_yesterday){
            $tip_text='恭喜您，人数比前一天增加'.($yesterday-$before_yesterday).'人';
        }else{
            $tip_text='遗憾，您的用户数比前一天没有增加，加油！';
        }
        return ['total'=>$total,'yesterday'=>$yesterday,'before_yesterday'=>$before_yesterday,'tip_text'=>$tip_text];
    }

    /**
     * 获取用户数趋势
     * @param $map
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getUserData($map){
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