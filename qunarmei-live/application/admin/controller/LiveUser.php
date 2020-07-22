<?php

namespace app\admin\controller;
use think\Db;
//腾讯云
use tencent_cloud\TimChat;

class LiveUser extends Base
{

    public function index(){
        // 查询直播中的直播间id
        $chat_id = '';
        $mapc['statu'] = 1;
        $res_chat = Db::table('think_live l')->field('id,chat_id')->where($mapc)->order('id desc')->limit(1)->find();
        if ($res_chat) {
            $chat_id = $res_chat['chat_id'];
        }else{
            echo "没有直播,请稍候再试!";die;
        }
        $chatSer = new TimChat();
        $res = $chatSer->getChatMem($chat_id);
        if($res){
            $mobiles = [];
            $data = [];
            foreach ($res as $v) {
                if($v['mobile'] != 'admin'){
                    $v['mobile'] = rtrim($v['mobile'],'B');
                    $mobiles[] = $v['mobile'];
                }
            }
            $map['m.mobile'] = ['in',$mobiles];
            $resu = Db::table('ims_bj_shopn_member m')
                ->join(['ims_bwk_branch'=>'b'],['m.storeid=b.id'],'LEFT')
                ->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'LEFT')
                ->join(['sys_department'=>'sd'],['sdr.id_department=sd.id_department'],'LEFT')
                ->where($map)
                ->field('b.title,b.sign,b.address,m.realname,m.mobile,sd.st_department bsc')
                ->order('b.id asc')
                ->select();
            if($resu){
                foreach ($resu as $v) {
                    $datav['bsc'] = "\t".$v['bsc'];
                    $datav['title'] = "\t".$v['title'];
                    $datav['sign'] = "\t".$v['sign'];
                    $datav['realname'] = "\t".$v['realname'];
                    $datav['mobile'] = "\t".$v['mobile'];
                    $data[] = $datav;
                }
            }
            $filename = "在线观看直播用户列表".date('YmdHis');
            $header = array ('所属办事处','门店名称','门店编号','用户名','手机号');
            $widths=array('10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            return 1;
        }
    }

    //生成Excel文件
    // $dt => array('开始时间','结束时间')
    public function regExcel1($mobiles)
    {

        //省	市	详细地址	门店名称	门店编号	姓名	手机号	角色

        $headArr = array('省','市','详细地址','门店名称','门店编号','姓名','手机号','角色');

        $arr = array();
        //获取所有注册用户信息
//        $res1 = Db::table('ims_bj_shopn_member')->field('realname,mobile,pid,staffid,createtime,isadmin,code,storeid,id_regsource ')->where("createtime>=UNIX_TIMESTAMP('$dt1') and createtime<UNIX_TIMESTAMP('$dt2') and length(pwd)>0")->order("createtime asc")->select();
        $mobs = '';
        foreach($mobiles as $mobv)
        {
            $mobs .= "'".$mobv['mobile']."',";
        }
        $mobs = rtrim($mobs,',');

        $res1 = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('realname,mobile,isadmin,pid,code,storeid,ibb.title,ibb.location_p,ibb.location_c,ibb.address,ibb.sign')->where(" mem.storeid=ibb.id and mobile in ($mobs) and length(pwd)>0 and ibb.sign not in ('000-000','666-666','888-888')")->order("mem.id desc")->select();
//        echo 'res1<pre>';print_r($res1);
        if($res1)
        {
            foreach($res1 as $res1_v)
            {
                if($res1_v['isadmin'] == 1)
                {
                    $role = '店老板';
                }elseif($res1_v['pid'] == 0 && $res1_v['code'])
                {
                    $role = '美容师';
                }else
                {
                    $role = '顾客';
                }
                //
                $data_csv = array(
                    @$res1[0]['location_p'],
                    @$res1[0]['location_c'],
                    @str_replace(',','|',str_replace(' ','',@$res1[0]['address'])),
                    @str_replace(' ','',@$res1[0]['title']),
                    @$res1[0]['sign'],
                    @$res1_v['realname'],
                    @$res1_v['mobile'],
                    @$role
                );
//                echo 'data_csv:<pre>';print_r($data_csv);exit;
                $arr[] = $data_csv;
            }
        }
//        echo 'arr:<pre>';print_r($arr);exit;
        $dt1 = date('Y-m-d_H-i-s');
        $name = 'csv/zhibo_user_'.$dt1;
        $res = $this-> reportCsv($headArr,$arr,$name);
        //本地
        // $url = 'http://localhost:81/';
        //服务器
        $url = 'http://live.qunarmei.com/';
        $res = $url.$res;
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
        // $csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\\';
        //服务器
       $csv_path = '/home/canmay/www/live/public/';
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