<?php

namespace app\admin\model;
use think\Exception;
use think\Model;
use think\Db;
//拼人品活动门店关联商品表
class GoodsBargainModel extends Model
{
    protected $name = 'goods_bargain';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * Commit: 批量设置单个门店的活动及奖励商品
     * Function: setOneStoreBargainGoods
     * @Param $storeid 门店
     * @Param $promote 活动商品集合
     * @Param $reward奖励商品集合
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 13:56:19
     * @Return array
     */
    public function setOneStoreBargainGoods($storeid,$promote,$reward){
        if(empty($storeid)){
            return ['code' => 0, 'data' => '', 'msg' => '未选择门店'];
        }
        //添加产品之后门店默认参加活动
        Db::table('ims_bwk_branch')->where('id',$storeid)->update(['is_bargain'=>1,'updatetime'=>time()]);

        if(empty($promote)){
            return ['code' => 0, 'data' => '', 'msg' => '未选择活动产品'];
        }

        $model = Db::name('goods_bargain');
        $time = time();
        //删除门店下的所有关联产品
        $model->where('storeid','=',$storeid)->delete();

        foreach ($promote as $k => $v) {
            $where = [];
            $where['goods_id'] = $v;
            $where['storeid'] = $storeid;
            $where['pid'] = 0;

            $where['create_time'] = $time;
            $model->insert($where,false,true);
            //查询活动产品下的关联商品
            if(!empty($reward)){
                foreach ($reward as $kk=>$vv){
                    $reward_where = [];
                    $reward_where['goods_id'] = $vv;
                    $reward_where['storeid'] = $storeid;
                    $reward_where['pid'] = $v;
                    $reward_where['create_time'] = $time;
                    $model->insert($reward_where);
                }
            }
        }
        return ['code' => 1, 'data' => '', 'msg' => '批量设置成功'];
    }
    //多门店设置活动及奖励商品
    public function setStoreListBargainGoods($storeid,$promote,$reward){
        if(empty($storeid)){
            return ['code' => 0, 'data' => '', 'msg' => '未选择门店'];
        }
        try{
            //添加产品之后门店默认参加活动
            Db::table('ims_bwk_branch')->where('id','in',$storeid)->update(['is_bargain'=>1]);
            if(empty($promote)){
                return ['code' => 0, 'data' => '', 'msg' => '未选择活动产品'];
            }

            $model = Db::name('goods_bargain');
            $time = time();
            //删除不在集合中的活动产品及奖励产品
            $model->where('id','in',$storeid)->delete();
            $i = 0;
            $goods_list = [];
            foreach ($storeid as $key=>$val){
                $sid = $val;
                foreach ($promote as $k => $v) {
                    $goods_list[$i]['goods_id'] = $v;
                    $goods_list[$i]['storeid'] = $sid;
                    $goods_list[$i]['pid'] = 0;
                    $goods_list[$i]['create_time'] = $time;
                    $i++;
                    //查询活动产品下的关联商品
                    if(!empty($reward)){
                        foreach ($reward as $kk=>$vv){
                            $goods_list[$i]['goods_id'] = $vv;
                            $goods_list[$i]['storeid'] = $sid;
                            $goods_list[$i]['pid'] = $v;
                            $goods_list[$i]['create_time'] = $time;
                            $i++;
                        }
                    }
                }
            }
            //添加数据
            $res = $model->insertAll($goods_list);

            return ['code' => 1, 'data' => '', 'msg' => '批量设置成功'];
        }catch (Exception $e){
            return ['code' => 0, 'data' => '', 'msg' => '批量设置失败：'.$e->getMessage()];
        }
    }

    /**
     * Commit: 设置门店拼人品活动商品
     * Function: setStoreBargainGoodsList
     * @Param $storeid 门店
     * @Param $goods 商品集合
     * @Param int $goods_id  添加奖励商品时 id=活动商品id
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 14:06:30
     * @Return array
     */
    public function setStoreBargainGoodsList($storeid,$goods,$goods_id = 0){
        if(empty($storeid)){
            return ['code' => 0, 'data' => '', 'msg' => '未选择门店'];
        }
        if(empty($goods)){
            return ['code' => 0, 'data' => '', 'msg' => '未选择产品'];
        }

        $msg = $goods_id ? '奖励' : '活动';
        try{
            $model = Db::name('goods_bargain');
            $time = time();
            //删除不在集合中的活动产品及奖励产品
            $map['storeid'] = $storeid;
            $map['pid'] = $goods_id;
            $model->where($map)->delete();
            $data = [];
            foreach ($goods as $k => $v) {
                $data[$k]['goods_id'] = $v;
                $data[$k]['storeid'] = $storeid;
                $data[$k]['pid'] = $goods_id;
                $data[$k]['create_time'] = $time;
            }
            //添加数据
            $res = $model->insertAll($data);
            return ['code' => 1, 'data' => '', 'msg' => "拼人品{$msg}商品设置"];
        }catch (Exception $e){
            return ['code' => 0, 'data' => '', 'msg' => "拼人品{$msg}商品设置失败：".$e->getMessage()];
        }
    }

    /**
     * Commit: 获取门店信息
     * Function: getStoreInfo
     * @Param int $storeid
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-02 14:44:15
     * @Return array|false|\PDOStatement|string|Model
     */
    public function getStoreInfo($storeid = 2) {
        $map['id'] = $storeid;
        return Db::table('ims_bwk_branch')->where($map)->find();
    }

    /**
     * Commit: 获取门店活动或奖励商品数量
     * Function: getActivityGoodsCount
     * @Param int $storeid 门店id
     * @Param int $goods_id 商品id或 pid
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-02 14:46:28
     * @Return int|string
     */
    public function getActivityGoodsCount($storeid = 2, $goods_id = 0,$flag = false){
        $map['storeid'] = $storeid;
        $map['pid'] = $goods_id;
        if(!$flag) $map['pid'] = ['<>',$goods_id];
        return Db::name('goods_bargain')->where($map)->count();
    }
}