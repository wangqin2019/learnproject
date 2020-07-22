<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkboxCardModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'blink_order_box_card';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    public function getCreateTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getIsGiveAttr($value){
        $status = [
            0=>'未赠送',
            1=>'已赠送',
            2=>'赠送中',
        ];
        return $value>=0 ? $status[$value] : '--';
    }

    /**
     * Commit: 卡片数量
     * Function: getCardCount
     * @param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 10:58:02
     * @return int|string
     */
    public function getCardCount($map)
    {
        return $this
            ->alias('card')
            ->join('pt_blink_box_card_image image','card.thumb_id=image.id','left')
            ->join(['ims_bj_shopn_member'=> 'm'],'card.uid=m.id','left')
            ->where($map)
            ->count();
    }

    /**
     * Commit: 卡片列表及关联信息
     * Function: getCardLists
     * @Param $map
     * @Param $nowpage
     * @Param $limit
     * @Param bool $flag
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 15:29:51
     */
    public function getCardLists($map,$nowpage,$limit,$flag = true){
        $model = $this
            ->alias('card')
            ->join('pt_blink_box_card_image image','card.thumb_id=image.id','left')
            ->join(['ims_bj_shopn_member'=> 'm'],'card.uid=m.id','left')
            ->field('card.*,image.name,image.intro,image.thumb,image.thumb1,m.mobile,m.realname')
            ->where($map);
        if($flag){
            $model = $model ->page($nowpage, $limit);
        }
        $list = $model->order('card.status asc')->select()->toArray();

        return $list;
    }


    /**
     * Commit: 获取美容师信息
     * Function: getOrderBeautician
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-01 13:41:26
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getOrderBeautician(){
        return Db::name('bargain_order')
            ->alias('o')
            ->join(['ims_bj_shopn_member'=>'m'],'o.fid=m.id','left')
            ->field('o.fid,m.realname')
            ->where(['pay_status'=>1])
            ->group('o.fid')
            ->select();
    }



}