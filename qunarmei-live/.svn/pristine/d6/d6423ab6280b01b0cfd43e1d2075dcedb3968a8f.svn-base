<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/8/14
 * Time: 10:14
 */

namespace app\api\service;
use app\api\model\BjBranchGoods;
use app\api\model\BjGoods;
use app\api\model\BjGoodsInterestrate;
use app\api\model\Branch as Store;
use app\api\model\StoreReview;
use app\api\model\SysDepartRelation;
use app\api\model\User;
use think\Db;
use think\Log;

/**
 * 后台调用的相关服务类
 */
set_time_limit(0);
class AdminService
{
    /**
     * 门店注册服务:1.添加门店,2.添加店老板,3.通知注册人,4.添加微商城商品,5.添加办事处门店对应关系,6.添加拼购活动商品
     * @param string $sign 门店编号
     * @return int $flag 0:失败,-1:门店编号已存在,-2:
     */
    public function storeRegister($sign)
    {
        // 使用数据库事务
        Db::transaction(function () use($sign) {
            // 1.查询门店编号是否存在,不存在插入
            $map['sign'] = $sign;
            $res_bwk1 = Store::get($map);
            if($res_bwk1){
                return -1;
            }
            $res_bwk = StoreReview::get($map);
            if($res_bwk){
                $ins_data = [
                    'weid'=>1,
                    'title'=>$res_bwk['title'],
                    'star'=>4,
                    'sign'=>$res_bwk['sign'],// '门店签名',
                    'location_p'=>$res_bwk['location_p'],//'省',
                    'location_c'=>$res_bwk['location_c'],//'市',
                    'location_a'=>$res_bwk['location_a'],//'区',
                    'address'=>$res_bwk['address'],// '具体地址',
                    'lat'=>$res_bwk['lat'],// '纬度',
                    'lng'=>$res_bwk['lng'],//'经度',
                    'tel'=>$res_bwk['tel'],
                    'price'=>$res_bwk['price'],// '人均价格',
                    'open'=>$res_bwk['open'],//'营业时间',
                    //-- 特色推荐
                    'recommend'=>$res_bwk['recommend'],// '特色推荐',
                    'summary'=>$res_bwk['summary'],//'简介' ,
                    'content'=>htmlspecialchars_decode($res_bwk['content']),//'介绍',
                    'isshow'=>1,//'显示或隐藏'
                    'updatetime'=>time(),
                    'createtime'=>time()
                ];
                $res_bwk2 = Store::create($ins_data);
                $store_id = $res_bwk2->id;

                // 2.查询店老板是否存在,不存在插入
                $mapu['mobile'] = $res_bwk['mobile_lb'];
                $res_mem = User::get($mapu);
                if(!$res_mem){
                    $url = 'https://api-app.qunarmei.com/qunamei/bossregist';
                    $data = [
                        'mobile' => $res_bwk['mobile_lb'],
                        'sign' => $res_bwk['sign']
                    ];
                    $res_boss = curl_post($url,$data);
                    Log::info('res_boss:'.$res_boss);
                    $res_mem = User::get($mapu);
                    $boss_id = $res_mem['id'];
                }else{
                    $boss_id = $res_mem['id'];
                }
                $upd_data = [
                    'id' => $store_id,
                    'updatetime'=>time(),
                    'boss_id' => $boss_id
                ];
                Store::update($upd_data);

                // 3.通知注册人
                $str2 = '{"status":"审核通过","urls":"http://salon.qunarmei.com/index/index/zhibo_down.html"}';
                send_sms($res_bwk['mobile_txr'],105,$str2);
                // 4.添加微商城商品pid=0 and deleted=0 and status=1 and isshow=1
                $mappg['pid'] = 0;
                $mappg['deleted'] = 0;
                $mappg['status'] = 1;
                $mappg['isshow'] = 1;
                $pids = [
                    11,12,13,14,15,17354,17446,17454,17690,17701,17702,17715,17716,17717,17742,17743,17744,17780,17781,17782,1746720,1746721,1746722,1746723,1746724,1748466,1748470,1748471,1748486,1748488,1748489,1748490,1748491,1748492,1748514,1748515,1748516,1748517,1748543,1748544,1748545,
                    1751096,1751097,1751098
                ];
                $mappg['id'] = ['in',$pids];
                $res_pgd = BjGoods::all($mappg);
                if($res_pgd){
                    foreach ($res_pgd as $v) {
                        $v['pid'] = $v['id'];
                        $v['storeid'] = $store_id;

                        $mapi['id_store'] = 0;
                        $mapi['id_goods'] = $v['id'];
                        $res_bji = BjGoodsInterestrate::all($mapi);
                        unset($v['id']);
//                        echo '<pre>';print_r($v->getData());die;
                        $gd = BjGoods::create($v->getData());
                        $goods_id = $gd->id;

                        $insert_fq = array (
                            'id_store' => $store_id,
                            'id_goods' => $goods_id,
                            'dt_insert' => date("Y-m-d H:i:s",time()),
                            'dt_update' => date("Y-m-d H:i:s",time())
                        );
                        foreach ($res_bji as $vb) {
                            $insert_fq['id_interestrate'] = $vb['id_interestrate'];
                            BjGoodsInterestrate::create($insert_fq);
                        }
                        $ins_bbg = [
                            'title' => $v['title'],
                            'gid' => $v['pid'],
                            'sid' => $store_id,
                            'weid' => 1
                        ];
                        BjBranchGoods::create($ins_bbg);


                    }
                }
                //5.添加办事处门店对应关系
                $data_sdr = [
                    'id_department' => $res_bwk['bsc'],
                    'id_sign' => $res_bwk['sign'],
                    'id_beauty' => $store_id
                ];
                SysDepartRelation::create($data_sdr);
                //6.添加拼购活动商品
                // 保留调用接口
                return 1;
            }
        });
    }
}