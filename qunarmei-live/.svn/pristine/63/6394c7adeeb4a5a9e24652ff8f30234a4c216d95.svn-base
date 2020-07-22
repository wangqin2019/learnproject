<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:36
 */

namespace app\neibu\service;
use app\api\service\BaseSer;
use think\Db;
/**
 * 商品服务类
 */
class GoodService extends BaseSer
{
    // 需要推送的父商品id
    protected $gids = [1941464,1941466,1941467,1941468,1941469,1941470,1941471,1941472,1941473];
    /**
     * 推送到门店
     * @param [string] $sign [门店编号,多个,分割]
     */
    public function addLiveGoods($sign)
    {
        $mapg['id'] = ['in',$this->gids];
        // 1.查询父商品
        $resg = Db::table('ims_bj_shopn_goods g')->field('*')->where($mapg)->select();
        $flag = 0;
        $signs = explode(',', $sign);
        // 启动事务
Db::startTrans();
try{
        $this->msg = '';
        foreach ($signs as $k => $v) {
            // 循环添加商品到门店
            foreach ($resg as $kg => $vg) {
                // 1.查询门店是否有这些商品
                $mapg1['b.sign'] = $v;
                $mapg1['g.pid'] = $vg['id'];
                $resg1 = Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['g.storeid=b.id'],'LEFT')->where($mapg1)->limit(1)->find();
                if (!$resg1) {
                    $mapst['sign'] = $v;
                    $resstore = Db::table('ims_bwk_branch')->where($mapst)->limit(1)->find();
                    $datag = $vg;
                    unset($datag['id']);
                    $datag['pid'] = $vg['id'];
                    $datag['storeid'] = $resstore['id'];
                    $gid = Db::table('ims_bj_shopn_goods')->insertGetId($datag);

                    // 2.插入ims_branch_goods
                    $databg['title'] = $vg['title'];
                    $databg['gid'] = $vg['id'];
                    $databg['sid'] = $resstore['id'];
                    $databg['weid'] = 1;
                    $sgid = Db::table('ims_bwk_branch_goods')->insertGetId($databg);
                    
                    // 插入支付方式
                    $mapi['id_store'] = 0;
                    $mapi['id_goods'] = $vg['id'];
                    $resi1 = Db::table('ims_bj_shopn_goods_interestrate')->where($mapi)->select();
                    if ($resi1) {
                        foreach ($resi1 as $ki => $vi) {
                            $datai['id_store'] = $resstore['id'];
                            $datai['id_goods'] = $gid;
                            $datai['id_interestrate'] = $vi['id_interestrate'];
                            $datai['dt_insert'] = date('Y-m-d H:i:s');
                            Db::table('ims_bj_shopn_goods_interestrate')->insertGetId($datai);
                        }
                    }
                }else{
                    $flag = 1;
                }
                
            }
            if ($flag) {
                $this->msg .= $v.',';
                continue;
            }
        }
        // 提交事务
    Db::commit();    
} catch (\Exception $e) {
    echo "<pre>";print_r($e->getMessage());
    // 回滚事务
    Db::rollback();
}
        if ($flag) {
            $this->code = 0 ;
            $this->msg .= '门店已添加过';
        }else{
            $this->code = 1 ;
            $this->msg .= '门店商品添加成功';
        }
        return $this->returnArr();
    }
}