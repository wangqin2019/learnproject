<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/12/12
 * Time: 17:12
 */

namespace app\api\service;
use think\Db;

/**
 * Erp接口相关服务类
 */
class ErpService
{
    /**
     * 新增销售订单-订单进入Erp
     * @param array $arr 请求数据(order_sn:订单号,create_time:下单时间,sign:门店编号,goods:商品信息[cinvcode:商品编码,iquantity:数量,itaxunitprice:单价])
     * @param string $type 订单类型,order_score:C端积分兑换订单
     * @return string json
     */
    public function insertOrder($arr,$type='order_score')
    {
        $ordersn = '';
        $arr1['ccode'] = $arr['order_sn'];// 销售类型编码
        $arr1['cstcode'] = '01';
        $arr1['ddate'] = date('Y-m-d',$arr['create_time']);//订单日期
        $arr1['ccuscode'] = $arr['sign'];// 门店编号
        $arr1['dpredatebt'] = date('Y-m-d',$arr['create_time']+7*24*3600);//订单日期
        $arr1['itaxrate'] = '13';//税率
        $arr1['cdisptype'] = '日常发货';//发货类型
        $arr1['cmaker'] = '许媛媛';//制单人
        $arr1['Item'] = $arr['goods'];//商品信息
        $dara_req = json_encode($arr1);

//        $dara_req = '{
//            "ccode": "DW201907040950",
//            "cstcode": "01",
//            "ddate": "2019-07-11",
//            "ccuscode": "0018",
//            "dpredatebt": "2019-07-18",
//            "itaxrate": "13",
//            "cdisptype": "日常发货",
//            "cmaker": "许媛媛",
//            "Item": [{
//                "cinvcode": "C03228",
//                "iquantity": "1",
//                "itaxunitprice": "10"
//            }, {
//                "cinvcode": "C03226",
//                "iquantity": "2",
//                "itaxunitprice": "100"
//            }]
//        }';
        $url = config('url.erp_url').'/so/insertorder';
        $res = curl_post($url,$dara_req);
//        echo 'res:<pre>';print_r($res);die;
        if($res){
            $resp = json_decode($res,true);
            // 记录日志
            $result = $resp['data']==null?'':$resp['data'];
            $dara_req1 = json_decode($dara_req,true);
            $data['ordersn'] = $dara_req1['ccode'];
            $data['req_data'] = $dara_req;
            $data['ordersn_u8'] = $result;
            $data['res_data'] = $res;
            $data['create_time'] = time();
            $data['type'] = $type;
            $data['u8sign'] = $arr['sign'];
            Db::table('store_erp_saleorder')->insertGetId($data);
            $ordersn = $result;

        }
        return $ordersn;
    }
}