<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/20
 * Time: 16:58
 */

namespace app\api\service;


use think\Db;

class JobQueueSer
{
    public function outQueueTent($key)
    {
        $redisSer = new RedisSer();
        $res = $redisSer->pullQueue($key);
        // echo "key:".$key."<pre>";print_r($res);die;
        if($res){
            $datav = [];
            // $file_name = '/home/canmay/www/live/public/zbsee'.date('Ymd').'.txt';
            // 所属办事处,门店编号,门店名称,用户名称,用户号码,进入直播间时间,出直播间时间,进出时长差,插入时间
            // $datastr1 = '所属办事处 门店编号 门店名称 用户名称 用户号码 插入直播间时间 进出 聊天室id';
            // file_put_contents($file_name, $datastr1.PHP_EOL, FILE_APPEND);
            foreach ($res as $v) {
                $vr = json_decode($v,true);
                // 根据号码查询办事处,门店
                $vr['mobile'] = rtrim($vr['mobile'],'B');
                $map['m.mobile'] = $vr['mobile'];
                $resm = Db::table('ims_bj_shopn_member m')
                    ->join(['ims_bwk_branch'=>'b'],['m.storeid=b.id'],'LEFT')
                    ->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'LEFT')
                    ->join(['sys_department'=>'sd'],['sd.id_department=sdr.id_department'],'LEFT')->field('m.id,m.realname,m.mobile,b.sign,b.title,sd.st_department bsc')
                    ->where($map)
                    ->limit(1)
                    ->find();
                if ($resm) {
                    $data = [
                        'bsc' => $resm['bsc'],
                        'sign' => $resm['sign'],
                        'title' => $resm['title'],
                        'realname' => $resm['realname'],
                        'mobile' => $resm['mobile'],
                        'create_time' => date('Y-m-d H:i:s',$vr['create_time']),
                        'type' => $vr['type']==1?'进':'出',
                        'see_type' => isset($vr['see_type'])?$vr['see_type']:1,
                        'chat_id' => $vr['chat_id']
                    ];
                }else{
                    $data = [
                        'bsc' => '',
                        'sign' => '',
                        'title' => '',
                        'realname' => '未注册用户',
                        'mobile' => $vr['mobile'],
                        'create_time' => date('Y-m-d H:i:s',$vr['create_time']),
                        'type' => $vr['type']==1?'进':'出',
                        'see_type' => isset($vr['see_type'])?$vr['see_type']:1,
                        'chat_id' => $vr['chat_id']
                    ];
                }
                $status = isset($vr['status'])?$vr['status']:'';
                if($status){
                    $status = $status==1?'直播中':'直播结束';
                }
                $datalog = [
                    'bsc' => $data['bsc'],
                    'sign' => $data['sign'],
                    'title' => $data['title'],
                    'user_name' => $data['realname'],
                    'mobile' => $data['mobile'],
                    'type' => $data['type'],
                    'see_type' => $data['see_type'],
                    'chat_id' => $data['chat_id'],
                    'live_time' => strtotime($data['create_time']),
                    'insert_time' => time(),
                    'status' => $status,
                ];
                $res_log = Db::table('think_live_see_user_log')->insertGetId($datalog);
                
                // $datastr = implode(' ', $data);
                // file_put_contents($file_name, $datastr.PHP_EOL, FILE_APPEND);
                // $datav[] = $data;
            }
            // $file_name = '观看直播用户列表'.(string)date('YmdHis');
            // $head_arr = ['所属办事处','门店编号','门店名称','用户名称','用户号码','进出直播间时间','进出类型','聊天室id'];
            // $width = ['10','10','10','10','10','12','3','10'];
            // echo "<pre>";print_r($datav);
            // excelExport21($file_name,$head_arr,$datav,$width);
        }
        // echo "<br/>";print_r('outQueueTent');
    }
}