<?php

namespace app\admin\controller;
use think\Db;
use app\api\service\JobQueueSer;
/**
 * Class UserLog
 * @package app\admin\controller
 * 直播配置记录
 */
class LiveLog extends Base
{
    //*********************************************手机直播用户列表*********************************************//
    /**
     * [index 观看直播用户列表]
     * @return [type] [description]
     */
    public function index(){
        // echo "index:<pre>";die;
        $key = input('key');
        $map = [];
        // echo "map:<pre>";print_r($map);die;
        if($key&&$key!==""){
            $map['title|chat_id'] = ['like',"%" . $key . "%"];
        }
        // echo "map:<pre>";print_r($map);die;
        $map1['id'] = ['<',709];
        $map3 = ' length(update_time)>1';
        $map1['user_id'] = ['in',['18602716559','18005911839']];
        // echo "map1:<pre>";print_r($map1);die;
        $map2 = 'live_source=2 and id>709 and length(update_time)>1 and statu in (2,3)';
        $export = input('export',0);
        $page = input('page');
        $Nowpage = $page?$page:1;
        $limits = 50;// 获取总条数
        $count = Db::table('think_live')->where($map3)->where($map1)->whereOr($map2)->order('id desc')->count();
        // echo "count:<pre>";print_r($count);die;
        //计算总页面
        $allpage = intval(ceil($count / $limits));
        $page_limits = ($Nowpage-1)*$limits;
        if($export){
            $page_limits = 0;
            $limits = 9999;
        }
        // 查询直播配置记录
        $res_live = Db::table('think_live')->where($map3)->where($map1)->whereOr($map2)->order('id desc')->limit($page_limits,$limits)->select();
        
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        // echo "res_live:<pre>";print_r($res_live);die;
        if($res_live){
            $store_ids = [];
            foreach ($res_live as $k => $v) {
                $res_live[$k]['bsc'] = '';
                $store_ids[] = $v['idstore'];
            }
            // echo "res_live1:<pre>";print_r($res_live);die;
            // 查询门店对应办事处
            $mapb['r.id_beauty'] = ['in',$store_ids];
            $res_bsc = Db::table('sys_departbeauty_relation r')->join(['sys_department'=>'d'],['d.id_department=r.id_department'],'LEFT')->where($mapb)->select();

            if ($res_bsc) {
                foreach ($res_live as $k => $v) {
                    foreach ($res_bsc as $k1 => $v1) {
                        if ($v['idstore'] == $v1['id_beauty']) {
                            $res_live[$k]['bsc'] = $v1['st_department'];
                        }
                            
                    }
                }
            }

            $live_ids = [];$chat_ids = [];
            foreach ($res_live as $k => $v) {
                $res_live[$k]['start_time']= date('Y-m-d H:i:s',$v['insert_time']);
                $res_live[$k]['end_time']= $v['update_time']==null?'':$v['update_time'];
                $res_live[$k]['length'] = 0;
                if ($res_live[$k]['end_time']) {
                     $res_live[$k]['length'] = round((strtotime($res_live[$k]['end_time'])-strtotime($res_live[$k]['start_time']))%86400/3600,2); 
                }
                $res_live[$k]['stores']= 1;
                $res_live[$k]['see_users']= 0;
                $live_ids[] = $v['id'];
                $chat_ids[] = $v['chat_id'];
            }
            // echo "res_live3:<pre>";print_r($res_live);die;
            // 查询配置门店数
            $mapcl['live_id'] = ['in',$live_ids];
            $res_store = Db::table('think_live_see_conf_log l')->where($mapcl)->select();
            if ($res_store) {
                foreach ($res_live as $k3 => $v3) {
                    foreach ($res_store as $k4 => $v4) {
                        if ($v4['live_id'] == $v3['id']) {
                            $signs = $v4['store_signs'];
                            $stores = explode(',', $signs);
                            $res_live[$k3]['stores'] = count($stores);
                        } 
                    }
                }
            }

            // // 查询门店观看累计用户数
            $maplog['chat_id'] = ['in',$chat_ids];
            $res_log = Db::table('think_live_see_user_log log')->field('count(DISTINCT mobile) cnt,chat_id')->where($maplog)->group('chat_id')->select();
             // echo "res_log:<pre>";print_r($res_log);die;
            if ($res_log) {
                foreach ($res_log as $k5 => $v5) {
                    foreach ($res_live as $k6 => $v6) {
                        if ($v5['chat_id'] == $v6['chat_id']) {
                            $res_live[$k6]['see_users'] = $v5['cnt'];
                        } 
                    }
                }
            }
             // echo "res_log22:<pre>";print_r($res_log);die;
        }
        //导出
        if($export){
            $data=array();
            foreach ($res_live as $k => $v) {
                $data[$k]['chat_id']= $v['chat_id'];
                $data[$k]['user_name']= $v['user_name'];
                $data[$k]['bsc']=$v['bsc'];
                $data[$k]['mobile']= $v['user_id'];
                $data[$k]['start_time']= date('Y-m-d H:i:s',$v['insert_time']);
                $data[$k]['end_time']= $v['end_time'];
                $data[$k]['length'] = $v['length'];
                $data[$k]['stores']= $v['stores'];
                $data[$k]['see_users']= $v['see_users'];

            }
            $filename = "主播直播配置记录".date('YmdHis');
            $header = array ('直播间ID','直播用户','办事处','用户手机号','开始时间','结束时间','直播时长（时）','开通门店数','观看人数');
            $widths=array('10','10','10','10','10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        if($page){
            return json($res_live);
        }
        $this->assign('lists', $res_live);
        return $this->fetch();
    }
    /**
     * [zb_conf 主播直播统计报表]
     * @return [type] [description]
     */
    public function zb_conf(){
        set_time_limit(0);
        $key = input('key');
        $mapall = "(  (length(l.update_time)>1  AND `l`.`id` < 709  AND `l`.`user_id` IN ('18602716559','18005911839'))  OR (l.live_source=2 and l.id>709 and length(l.update_time)>1)  OR (`l`.`live_source` = 1  AND `l`.`user_id` = 1) )";
        if ($key) {
            $mapall .= ' and ( l.user_name like "%'.$key.'%" or l.user_id like "%'.$key.'%" )';
        }
        $export = input('export',0);
        $csv_export = input('csv_export',0);
        $page = input('page');
        $Nowpage = $page?$page:1;
        $limits = 50;// 获取总条数
        // $count = Db::table('think_live l')->join(['think_live_see_conf_log'=>'c'],['l.id=c.live_id'],'RIGHT')->where($map3)->where($map1)->whereOr($map2)->whereOr($map4)->order('l.id desc')->where($map)->field('l.*,c.store_signs,c.see_mobiles ')->count();
        $count = Db::table('think_live l')->join(['think_live_see_conf_log'=>'c'],['l.id=c.live_id'],'RIGHT')->where($mapall)->field('l.*,c.store_signs,c.see_mobiles ')->count();
        // echo "count:<pre>";print_r($count);die;
        //计算总页面
        $allpage = intval(ceil($count / $limits));
        $page_limits = ($Nowpage-1)*$limits;
        if($export){
            $page_limits = 0;
            $limits = 9999;
        }
        if($csv_export){
            $page_limits = 0;
            $limits = 99999;
        }
        
        
        // $res_live = Db::table('think_live l')->join(['think_live_see_conf_log'=>'c'],['l.id=c.live_id'],'RIGHT')->where($map3)->where($map1)->whereOr($map2)->whereOr($map4)->where($map)->order('l.id desc')->field('l.*,c.store_signs,c.see_mobiles ')->limit($page_limits,$limits)->fetchSql(true)->select();
        $res_live = Db::table('think_live l')->join(['think_live_see_conf_log'=>'c'],['l.id=c.live_id'],'RIGHT')->where($mapall)->order('l.id desc')->field('l.*,c.store_signs,c.see_mobiles,c.remark ')->limit($page_limits,$limits)->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        // echo "res_live:<pre>";print_r($res_live);die;
        if($res_live){
            $store_ids = [];
            $live_ids = [];
            foreach ($res_live as $k => $v) {
                $res_live[$k]['insert_time'] = date('Y-m-d H:i:s',$v['insert_time']);
                $res_live[$k]['zb_zl_name'] = '';
                $res_live[$k]['zb_zl_mobile'] = '';
                $res_live[$k]['plan_store'] = '';
                $res_live[$k]['plan_user'] = '';
                $res_live[$k]['bsc'] = '';
                $store_ids[] = $v['idstore'];
                $live_ids[] = $v['id'];
            }
            // echo "res_live1:<pre>";print_r($res_live);die;
            // 查询门店对应办事处
            $mapb['r.id_beauty'] = ['in',$store_ids];
            $res_bsc = Db::table('sys_departbeauty_relation r')->join(['sys_department'=>'d'],['d.id_department=r.id_department'],'LEFT')->where($mapb)->select();

            if ($res_bsc) {
                foreach ($res_live as $k => $v) {
                    foreach ($res_bsc as $k1 => $v1) {
                        if ($v['idstore'] == $v1['id_beauty']) {
                            $res_live[$k]['bsc'] = $v1['st_department'];
                        }
                            
                    }
                }
            }

            $live_ids = [];$chat_ids = [];
            foreach ($res_live as $k => $v) {
                $res_live[$k]['start_time']= $v['insert_time'];
                $res_live[$k]['end_time']= $v['update_time']==null?'':$v['update_time'];
                $res_live[$k]['length'] = 0;
                if ($res_live[$k]['end_time']) {
                     $res_live[$k]['length'] = round((strtotime($res_live[$k]['end_time'])-strtotime($res_live[$k]['start_time']))%86400/3600,2); 
                }
                $res_live[$k]['stores']= 1;
                $res_live[$k]['see_users']= 0;
                $live_ids[] = $v['id'];
                $chat_ids[] = $v['chat_id'];
            }
            // echo "res_live3:<pre>";print_r($res_live);die;
            // 查询配置门店数
            $mapcl['live_id'] = ['in',$live_ids];
            $res_store = Db::table('think_live_see_conf_log l')->where($mapcl)->select();
            if ($res_store) {
                foreach ($res_live as $k3 => $v3) {
                    foreach ($res_store as $k4 => $v4) {
                        if ($v4['live_id'] == $v3['id']) {
                            $signs = $v4['store_signs'];
                            $stores = explode(',', $signs);
                            $res_live[$k3]['plan_store'] = count($stores);
                            $zl_mobiles = $v4['see_mobiles'];
                            $res_live[$k3]['zl_mobiles'] = $zl_mobiles;
                            // 助理名称
                            $res_live[$k3]['zb_zl_mobile'] = $zl_mobiles;
                        } 
                    }
                }
            }

            // // 查询门店观看累计用户数
            $maplog['chat_id'] = ['in',$chat_ids];
            $res_log = Db::table('think_live_see_user_log log')->field('count(DISTINCT mobile) cnt,count(DISTINCT sign) stores,chat_id')->where($maplog)->group('chat_id')->select();
             // echo "res_log:<pre>";print_r($res_log);die;
            if ($res_log) {
                foreach ($res_log as $k5 => $v5) {
                    foreach ($res_live as $k6 => $v6) {
                        if ($v5['chat_id'] == $v6['chat_id']) {
                            $res_live[$k6]['see_users'] = $v5['cnt'];
                            $res_live[$k6]['stores'] = $v5['stores'];
                        } 
                    }
                }
            }
             // echo "res_log22:<pre>";print_r($res_log);die;
             foreach ($res_live as $k=>$v) {
                if ((int)$v['stores'] > (int)$v['plan_store']) {
                    $res_live[$k]['stores'] = $v['plan_store'];
                }
            }
        }
        
        //导出
        if($export || $csv_export){
            $data=array();
            foreach ($res_live as $k => $v) {
                $data[$k]['id']= $v['id'];
                $data[$k]['insert_time']= $v['insert_time'];
                $data[$k]['length']= $v['length'];
                $data[$k]['bsc']= $v['bsc'];
                $data[$k]['user_name']= $v['user_name'];
                $data[$k]['user_id']= $v['user_id'];
                $data[$k]['zb_zl_name']= $v['zb_zl_name'];
                $data[$k]['zb_zl_mobile']= $v['zb_zl_mobile'];
                $data[$k]['title']= $v['title'];
                $data[$k]['plan_store']= $v['plan_store'];
                $data[$k]['plan_user']= $v['plan_user'];
                $data[$k]['stores']= $v['stores'];
                $data[$k]['see_users']= $v['see_users'];
                $data[$k]['remark']= $v['remark'];
            }    
            $filename = "主播直播配置记录".date('YmdHis');
            $header = array ('直播间ID','直播日期','直播时间','办事处','主播老师','主播老师去哪美账号','主播助理','主播助理去哪美账号','主题','计划参与门店数','计划参会人数','实际参与门店数','实际参会人数','备注说明');
            $widths=array('10','10','10','10','10','10','10','10','10','10','10','10','10','10');
            if($data && $export) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }elseif ($data && $csv_export) {
                //csv导出
                reportCsv($header,$data,$filename);
                  //服务器
                $url = 'http://www.testlive.com:9091/csv/';
                $res = $url.$res;
                //浏览器下载
                return $res;
            }
            die();
        }
        if($page){
            return json($res_live);
        }
        $this->assign('lists', $res_live);
        return $this->fetch();
    }
}