<?php

namespace app\admin\controller;
use think\Db;
//腾讯云
use tencent_cloud\TimChat;

class LiveUserList extends Base
{

    public function index()
    {
        // 搜索条件
        $map = [];$mobiles=[];$map1 = [];
        $key = input('key');
        $id = input('id');
        $report = input('report');
        if($key){
            $map = " chat_id like '%".$key."%' or title like '%".$key."%'";
        }
        if($report && $id){
            $map1['id'] = $id;
        }
        //获取腾讯聊天室在线用户
        $tim = new TimChat();
        $res = Db::table('think_live')->field('id,title,chat_id,live_stream_name')->where($map)->where($map1)->order('id desc')->select();
        if(!empty($res)) {
            foreach ($res as &$v) {
                $mobiles = $tim->getChatMem($v['chat_id']);
                // 聊天室人数
                $v['cnt'] = count($mobiles);
            }
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 100;// 获取总条数
        $count = Db::table('think_live')->field('id,title,chat_id,live_stream_name')->where($map)->where($map1)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));

        // 导出数据
//        echo '<pre>';print_r($mobiles);die;
        if($report){
            $res = $this->regExcel($mobiles);
            return $res;
        }
//        echo '<pre>';print_r($mobiles);die;
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($res);
        }
        $this->assign('val',$key);
        $this->assign('res',$res);
        return $this->fetch();
    }

    //生成Excel文件
    public function regExcel($mobiles)
    {
        $res_mob = [];
        if(!empty($mobiles)){
            foreach ($mobiles as $v) {
                $res_mob[] = $v['mobile'];
            }
        }
        //省	市	详细地址	门店名称	门店编号	姓名	手机号	角色

        $headArr = array('姓名','手机','门店名称','省','市','详细地址','门店编号');
        $arr = array();
        $map2['mem.mobile'] = ['in',$res_mob];
        $res1 = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('mem.realname,mem.mobile,ibb.title,ibb.location_p,ibb.location_c,ibb.address,ibb.sign')->where(" mem.storeid=ibb.id ")->where($map2)->order("mem.createtime desc")->group('mem.mobile')->select();
        if($res1) {
            foreach($res1 as $res1_v) {
                $data_csv = array(
                    $res1_v['realname'] ,
                    $res1_v['mobile'] ,
                    $res1_v['title'] ,
                    $res1_v['location_p'] ,
                    $res1_v['location_c'] ,
                    $res1_v['address'] ,
                    $res1_v['sign']
                );
                $arr[] = $data_csv;
            }
        }
        $dt1 = '';
        $name = 'csv/live_user_'.$dt1;
        $res = $this-> reportCsv($headArr,$arr,$name);
//        $url = 'http://live.qunarmei.com/';
        $res = config('domain').$res;
        //浏览器下载
        return $res;
//        header("Location:".$res);
    }

    public function reportCsv ($csv_header,$csv_body,$csv_name)
    {
        // $csv_name = dirname(__FILE__).$csv_name;
        if(!strstr($csv_name,'2017'))
        {
            $csv_name = $csv_name.date('Y-m-d').'.csv';
        }else{
            $csv_name = $csv_name.'.csv';
        }
        if(is_file($csv_name))
        {
            unlink($csv_name);
        }
        $file_n = $csv_name;
//        $file_n = explode('/',$file_n);
        // CSV名称转中文试下
        @$csv_name = iconv("UTF-8", "GB2312//IGNORE", @$csv_name);
        //本地
//        $csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\\';
        $csv_path = config('csv_path');
        //服务器
//        $csv_path = '/home/canmay/www/live/public/';
        $fp = fopen($csv_path.$csv_name,'a');
        // 处理头部标题
        $header = implode(',', $csv_header) . PHP_EOL;
        // 处理内容
        $content = '';
        foreach ($csv_body as $k => $v) {
            $content .= implode(',', $v) . PHP_EOL;
        }
        // 拼接
        $csv = $header.$content;
        $csv = iconv("UTF-8", "GB2312//IGNORE", $csv);
        // 写入并关闭资源
        fwrite($fp, $csv);
        fclose($fp);
//        echo 'csv_name:'.$csv_name;exit;
        return $file_n;


    }
    // end Modify by wangqin 2017-11-16

}