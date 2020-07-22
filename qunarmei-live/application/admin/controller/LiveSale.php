<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/27
 * Time: 13:09
 */

namespace app\admin\controller;
use app\api\model\Live;
use app\api\model\LiveSeeUserLog;
use app\api\model\User;

/**
 * 给销售用的直播列表
 */
class LiveSale extends Base
{
    protected $statu = [
        '-1' => '未发布',
        '0' => '未开始',
        '1' => '直播中',
        '2' => '直播结束',
        '3' => '回放视频删除'
    ];
    /**
     * 销售直播列表
     */
    public function index()
    {
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        $key = input('key');

        $map = [];
        if($key&&$key!=="") {
            $map['title'] = ['like',"%" . $key . "%"];
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = \app\api\model\Live::where($map)->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = \app\api\model\Live::where($map)->page($Nowpage,$limits)->order('insert_time desc')->select();
//        echo 'lists1<pre>';print_r($lists);die;
        if($lists){
            foreach ($lists as $k=>$v) {
                $lists[$k]['start_time'] = $v['insert_time']>0?date('Y-m-d H:i:s',$v['insert_time']):'';
                $lists[$k]['end_time'] = $v['update_time']==null?'':$v['update_time'];
                $lists[$k]['see_url'] = $v['statu'] == 2?$v['see_url']:'';
                $lists[$k]['statu'] = $this->statu[$v['statu']];
            }
        }
//        echo 'lists2<pre>';print_r($lists);die;
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总数量
        $this->assign('val', $key);
        if(input('get.page')) {
            return json($lists);
        }
        return $this->fetch();
    }

    //直播间用户导出
    public function live_user(){
        set_time_limit(0);
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        $arr = [];$limit = 100;// 每500条写入1次
        $chat_id = input('param.chat_id');
        $map_live['chat_id'] = $chat_id;
        $res_live = Live::get($map_live);
        // 根据聊天室查询直播用户记录
        $map['chat_id'] = $chat_id;
        $res = LiveSeeUserLog::all($map);
//        echo '<pre>';print_r($res);die;
        if($res){
            // 生成csv文件 办事处	门店名称	门店编码	用户名称	用户电话		用户角色	观看直播间	记录时间

            $csv_header = ['办事处','门店名称','门店编码','用户名称','用户电话','用户角色','观看直播间','记录时间','观看类型','进出状态'];
            $csv_name = 'live_user_'.date('YmdHis');
//            $csv_path = config('csv_path');
//            $res_csv = csv_header($csv_header,$csv_name,$csv_path);

            $mobiles = [];
            foreach ($res as $k=>$v) {
                $res[$k]['role'] = '';
                $res[$k]['zb_title'] = $res_live['title'];
                $res[$k]['live_time'] = $v['live_time']>0?date('Y-m-d H:i:s',$v['live_time']):'';
                $res[$k]['see_type'] = $v['see_type']==1?'app观看':'h5观看';
                $mobiles[] = $v['mobile'];
            }
            // 根据号码查询用户角色
            $mobiles = array_unique($mobiles);
            $map_user['mobile'] = ['in',$mobiles];
            $res_user = User::all($map_user);
            if($res_user){
                foreach ($res_user as $vu) {
                    foreach ($res as $k=>$v){
                        if($vu['mobile'] == $v['mobile']){
                            if($vu['isadmin']){
                                $res[$k]['role'] = '店老板';
                            }elseif(strlen($vu['code']) > 1){
                                $res[$k]['role'] = '美容师';
                            }else{
                                $res[$k]['role'] = '顾客';
                            }
                        }
                    }
                }
            }
            foreach ($res as $k=>$v) {
                $arr1[0] = $v['bsc'];
                $arr1[1] = $v['title'];
                $arr1[2] = $v['sign'];
                $arr1[3] = $v['user_name'];
                $arr1[4] = $v['mobile'];
                if($flag_rule){
                    $arr1[4] = $mobrule->replaceMobile($arr1[4]);
                }
                $arr1[5] = $v['role'];
                $arr1[6] = $v['zb_title'];
                $arr1[7] = $v['live_time'];
                $arr1[8] = $v['see_type'];
                $arr1[9] = $v['type'];
                $arr[] = $arr1;
//                if($arr && ($k % $limit == 0)){
//                    csv_body($res_csv['fp'],$arr);
//                    $arr = [];
//                }
            }
            if($arr){
                csv_export($arr,$csv_header,$csv_name);die;
            }
//            if($arr){
//                csv_body($res_csv['fp'],$arr);
//                $url = config('domain').'/csv/'.$res_csv['file_name'];
//                header("Location: $url"); ;
//            }
        }
    }
}