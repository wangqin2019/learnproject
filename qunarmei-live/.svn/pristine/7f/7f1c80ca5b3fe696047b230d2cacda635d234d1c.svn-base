<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/5/14
 * Time: 10:03
 */

namespace app\api\service;

use app\api\model\BankInterestrate;
use app\api\model\Branch;
use app\api\model\BwkItem;
use app\api\model\BwkItemEvaluate;
use app\api\model\ItemPaylog;
use app\api\model\TxAppoint;
use app\api\model\User;

/**
 * 单表数据查询服务
 * Class SingleSer
 * @package app\api\service
 */
class SingleSer
{
    /**
     * 门店项目信息
     * @param array $map 查询条件
     * @return
     */
    public function getBwkItem($map)
    {
        $res = BwkItem::where($map)
            ->field('id,store_id,item_name,item_img,is_delete,status,color,duration,item_price,item_detail,item_wheplan_img,item_detail_img,label_id,id_interestrate,create_time')
            ->order('id desc')
            ->select();
        if($res){
            foreach ($res as $k=>$v) {
                $item_wheplan_img = [];
                if($v['item_wheplan_img']){
                    $item_wheplan_img = explode(',',$v['item_wheplan_img']);
                }
                $res[$k]['item_wheplan_img'] = $item_wheplan_img;
                $item_detail_img = [];
                if($v['item_detail_img']){
                    $item_detail_img = explode(',',$v['item_detail_img']);
                }
                $res[$k]['item_detail_img'] = $item_detail_img;
                $res[$k]['duration'] = $res[$k]['duration'].'分钟';
                $res[$k]['buy_num'] = 0;

                $map1['service_id'] = $v['id'];
                $map1['status'] = ['in',[1,2,3]];
                $res1 = TxAppoint::where($map1)->count();
                if($res1){
                    $res[$k]['buy_num'] = $res1;
                }
                $res[$k]['buy_num'] .= '人订购';
            }
        }
        return $res;

    }
    /**
     * 预约项目订单信息
     * @param array $map 查询条件
     * @return
     */
    public function getTxAppoint($map)
    {
        $res = TxAppoint::where($map)
            ->field('id,user_id,user_name,mobile,store_id,mrs_id,service_id,remark,appoint_time,status,appoint_num,appoint_sn,pay_price,pay_time,id_interestrate,code_service,qrcode_service,create_time,complete_time,service_time,appoint_sn')
            ->order('id desc')
            ->select();
        return $res;
    }
    /**
     * 门店信息
     * @param array $map 查询条件
     * @return
     */
    public function getBranch($map)
    {
        $res = Branch::where($map)
            ->field('id,title,address,tel,sign')
            ->order('id desc')
            ->select();
        return $res;
    }
    /**
     * 用户信息
     * @param array $map 查询条件
     * @return
     */
    public function getUser($map)
    {
        $res = User::where($map)
            ->field('id,realname,mobile,storeid,role_id,isadmin,code,staffid')
            ->order('id desc')
            ->select();
        return $res;
    }
    /**
     * 支付利率信息
     * @param array $map 查询条件
     * @return
     */
    public function getBankInterestrate($map)
    {
        $res = BankInterestrate::where($map)
            ->field('id_interestrate,id_bank,no_period')
            ->order('id_interestrate desc')
            ->select();
        return $res;
    }
    /**
     * 支付日志信息
     * @param array $map 查询条件
     * @return
     */
    public function getItemPaylog($map)
    {
        $res = ItemPaylog::where($map)
            ->field('user_id,mobile,appoint_sn,pay_amount,transaction_id,tran_paras,status,log_time,pay_type')
            ->order('id desc')
            ->select();
        return $res;
    }
}