<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/9/5
 * Time: 14:24
 */

namespace app\api\controller;
use think\Db;
/**
 * swagger: 统计数据分析-交易过程
 */
class SumTrade extends SumHour
{
    protected $msg = '暂无数据';
    // 数据分析-交易过程
    public function tradeProcess(){
        // 请求数据
        $storeid = input('param.storeid',0);// 0=>所有门店
        $dt1 = input('param.dt1',date('Y-m-d',strtotime('-1 day')));// 起始时间
        $dt2 = input('param.dt2',date('Y-m-d'));// 结束时间
        $dt3 = strtotime($dt2)+(3600*24);// 选中的后一天时间
        $uid = input('param.uid',0);// 用户id
        $action = input('param.action',-1);// -1的时候默认全部
        $page = input('param.page',1);// 分页数
        $sellerid = input('param.sellerid',0);// 美容师id
        // 初始化
        $page_size = 30;// 默认50条一页
        $data_v = [0=>'登陆',1=>'浏览列表',2=>'浏览产品',3=>'下单',4=>'支付',5=>'取货',6=>'失效'];
        $whereT = null;$user_list = [];
        // 门店id,门店名称,起始时间,结束时间 [用户id,用户名,号码,头像,美容师,行为,时间]
        $res_t = Db::table('ims_bwk_branch')->field('id storeid,title')->where('id',$storeid)->limit(1)->find();
        if($res_t){
            $arr1['storeid'] = $storeid;
            $arr1['title'] = $res_t['title'];
        }
        $arr1['dt1'] = $dt1;
        $arr1['dt2'] = $dt2;
        $arr1['next_page_flag'] = 0;//0=>没有下一页,1=>有下一页
        $arr1_1['dt1'] = strtotime($dt1);
        $arr1_1['dt2'] = strtotime($dt2);
        $whereT = ' and source=1';
        if($uid){
            // 搜索用户
            $whereT = ' and uid='.$uid;
        }
        if($storeid){
            // 搜索门店
            $whereT = ' and storeid='.$storeid;
        }
        // 浏览,1,2
        if($action == 7){
            $whereT .= ' and (action=1 or action=2)';
        }elseif($action != -1){
            // 搜索行为
            $whereT .= ' and action='.$action;
        }
        // 美容师只看属于他自己顾客行为过程
        if($sellerid){
            $whereT .= ' and sellerid='.$sellerid;
        }
        $res = Db::name('data_logs l')->field('l.uid,l.uname,l.avatar,l.mobile,l.storename,l.sellername,l.remark,l.insert_time,l.action')->where("insert_time>='".$arr1_1['dt1']."' and insert_time<='".$dt3."' $whereT")->order('insert_time desc')->limit(($page-1)*$page_size,$page_size)->select();
        if($res){
            foreach ($res as &$v) {
                $v['insert_time'] = date('Y-m-d H:i',$v['insert_time']);
            }
            $user_list = $res;
            // 统计是否有下一页
            $res_npage = Db::name('data_logs l')->field('l.uid,l.uname,l.avatar,l.mobile,l.storename,l.sellername,l.remark,l.insert_time,l.action')->where("insert_time>='".$arr1_1['dt1']."' and insert_time<='".$dt3."' $whereT")->order('insert_time desc')->count();
            if($res_npage){
                $sum_page = ceil($res_npage/$page_size);//总页数
                if(($page+1)<=$sum_page){
                    $arr1['next_page_flag'] = 1;
                }
            }
        }
        if($arr1 || $user_list){
            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = ['stores'=>$arr1,'user_list'=>$user_list];
        }
        return parent::returnMsg($this->code,$this->data,$this->msg);
    }
}