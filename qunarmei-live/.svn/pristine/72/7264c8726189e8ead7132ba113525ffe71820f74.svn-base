<?php

namespace app\admin\controller;
use think\Db;
//整合七牛直播sdk
use qiniu_transcoding\Token;

//生成Excel
use think\Csv;
set_time_limit(0);

class LiveLine extends Base
{
    protected $bucket='qunameilive';
    /**
     * [index 实时曲线图]
     * @return
     * @author
     */
    public function index(){
        //初始化数据取最后5条
        $res = Db::name('live_bandwidth')->field('bandwidth')->order('id desc')->limit(5)->select();
        $this->assign('res',$res);
        return $this->fetch();
    }

    /*
     * 获取实时人数曲线图
     **/
    public function livecnt()
    {
        return $this->fetch('live_cnt');
    }

    /*
     * 获取用户注册曲线图
     **/
    public function getMember()
    {
        $dt1 = input('dt1')==''?date("Y-m-d",strtotime("-5 day")):input('dt1');
        $dt2 = input('dt2')==''?date("Y-m-d"):input('dt2');
        if($dt1 && $dt2)
        {

            $res = Db::table('ims_bj_shopn_member_day')->field('cnt,source,logtime')->where("logtime>='$dt1' and logtime<'$dt2' ")->order('logtime asc')->select();
            $res1 = Db::table('ims_bj_shopn_member_day')->field('logtime')->where("logtime>='$dt1' and logtime<'$dt2'")->group('logtime')->order('logtime asc')->select();
            $res2 = Db::table('ims_bj_shopn_member_sum_day')->field('cnt,source,logtime')->where("logtime>='$dt1' and logtime<'$dt2' ")->order('logtime asc')->select();
            $res3 = Db::table('ims_bj_shopn_member_sum_day')->field('logtime')->where("logtime>='$dt1' and logtime<'$dt2'")->group('logtime')->order('logtime asc')->select();

            //统计当日实时注册用户及总量
            $dt3 = date("Y-m-d",strtotime("+1 day"));
            $res_day = Db::table('ims_bj_shopn_member')->field('count(0) cnt,id_regsource')->where("createtime>=UNIX_TIMESTAMP('$dt2') and createtime<UNIX_TIMESTAMP('$dt3') and length(pwd)>0 ")->select();
            $res_day2 = Db::table('ims_bj_shopn_member')->field('count(0) cnt,id_regsource')->where(" createtime>=UNIX_TIMESTAMP('2017-11-10') and createtime<UNIX_TIMESTAMP('$dt3') and length(pwd)>0")->select();

            if($res1)
            {
                $xdt = '';
                foreach($res1 as $v)
                {
                    $xdt = $xdt.",'".$v['logtime']."'";
                    $datav[1][$v['logtime']]=0;
                }
                $xdt .= ",'".$dt2."'";
                $xdt = ltrim($xdt,',');
                $this->assign('xdt',$xdt);

                $xdt3 = '';
                foreach($res3 as $resv3)
                {
                    $xdt3 = $xdt3.",'".$resv3['logtime']."'";
                    $datav3[1][$resv3['logtime']]=0;
                }

                $xdt3 .= ",'".$dt2."'";
                $xdt3 = ltrim($xdt3,',');
                $this->assign('xdt3',$xdt3);
//                echo '$res<pre>';print_r($res);exit;
                foreach($res as $v1)
                {
                    $datav[1][$v1['logtime']] = $v1['cnt'];
                }
                foreach($res2 as $v2)
                {
                    $datav3[1][$v2['logtime']] = $v2['cnt'];
                }

                $dataval1 = '';$dataval3 = '';
                foreach($datav[1] as $datav1)
                {
                    $dataval1 .=','.$datav1;
                }
                foreach($datav3[1] as $datav3)
                {
                    $dataval3 .=','.$datav3;
                }
                if($res_day)
                {
                    $dataval1.=','.$res_day[0]['cnt'];
                }else
                {
                    $dataval1.= ',0';
                }
                if($res_day2)
                {
                    $dataval3.=','.$res_day2[0]['cnt'];
                }else
                {
                    $dataval3.= ',0';
                }

                $datav = array(ltrim($dataval1,','));
//                echo '$datav:<pre>';print_r($datav);exit;
                $this->assign('datav',$datav);
                $datav3 = array(ltrim($dataval3,','));

                $this->assign('datav3',$datav3);
//                echo '<pre>datav:';print_r($datav);
            }


        }
        $this->assign('dt1',$dt1);
        $this->assign('dt2',$dt2);
        return $this->fetch('member_day');
    }

    /**
     * 获取直播在线人数及带宽数据
     */
    public function getLiveBandwidth()
    {
        $token = new Token();
        $resp = $token->getplaycount();
//        echo "<pre>resp:";print_r($resp);
        if($resp)
        {
            $resp = json_decode($resp);
            $resp = $resp->total;
            $data = array('cnt'=>($resp->count),'bandwidth'=>(round(($resp->bandwidth)/1000000,2)),'logtime'=>date('Y-m-d H:i:s'));
//            $res = Db::name('live_bandwidth')->insert($data);
//            $data['bandwidth'] = $data['bandwidth']/100000;

            return json_encode($data);
        }
//        echo "<pre>res:";print_r($res);

    }

    //获取token
    public function getToken()
    {
        $res = Db::name('live')->field('push_url')->where('statu=1 and db_statu=0')->limit(1)->order('id desc')->select();
        if($res)
        {
            $res1 = $res[0]['push_url'] ;
            $res2 = explode('?',$res1);
            $res3 = explode('&',$res2[1]);
            $res4 = explode('=',$res3[1]);
            return $res4;
        }
    }

    //调用外部接口 curl get方法
    public function curl_get($url='',$headers='')
    {
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //添加头文件
        if($headers)
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        return $output;
    }

    // start Modify by wangqin 2017-11-16
    //生成Excel文件
    // $dt => array('开始时间','结束时间')
    public function regExcel($dt1='',$dt2='')
    {

        //省	市	详细地址	门店名称	门店编号	姓名	手机号	角色	上级姓名	上级手机号	原始码姓名	原始码手机	注册时间	注册来源
        $dt1 = input('dt1');
        $dt2 = input('dt2');
        $headArr = array('省','市','详细地址','门店名称','门店编号','姓名','手机号','角色','上级姓名','上级手机号','原始码姓名','原始码手机','注册时间','所属办事处');
        $dt = array($dt1,$dt2);
        $arr = array();
        //获取所有注册用户信息
        $dt11 = strtotime($dt1);
        $dt21 = strtotime($dt2);
        // $res1 = Db::table('ims_bj_shopn_member')->field('realname,mobile,pid,staffid,createtime,isadmin,code,storeid,id_regsource,FROM_UNIXTIME(createtime) cre ')->where("createtime>=UNIX_TIMESTAMP('$dt1') and createtime<UNIX_TIMESTAMP('$dt2') and length(pwd)>0")->order("createtime asc")->select();
        // start Modify by wangqin 2017-12-15 增加用户所属办事处
        $res1 = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('mem.realname,mem.mobile,mem.pid,mem.staffid,mem.createtime,mem.isadmin,mem.code,mem.storeid,mem.id_regsource,ibb.title,ibb.location_p,ibb.location_c,ibb.address,ibb.sign')->where("mem.createtime>=$dt11 and mem.createtime<$dt21 and length(mem.pwd)>0 and mem.storeid=ibb.id ")->order("mem.createtime asc")->select();
        // $res1 = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb, sys_department sd, sys_departbeauty_relation sdr')->field('mem.realname,mem.mobile,mem.pid,mem.staffid,mem.createtime,mem.isadmin,mem.code,mem.storeid,mem.id_regsource,sd.st_department,ibb.title,ibb.location_p,ibb.location_c,ibb.address,ibb.sign ')->where("mem.createtime>=UNIX_TIMESTAMP('$dt1') and mem.createtime<UNIX_TIMESTAMP('$dt2') and length(pwd)>0 and mem.storeid = ibb.id AND sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id ")->order("mem.createtime asc")->select();
        // end Modify by wangqin 2017-12-15
//        echo 'res1<pre>';print_r($res1);exit;
        if($res1)
        {

            foreach($res1 as $res1_v)
            {
                //获取门店信息
                // $res2 = Db::table('ims_bwk_branch')->field('title,location_p,location_c,address,sign')->where('id='.$res1_v['storeid'])->limit(1)->select();
//                echo 'res2<pre>';print_r($res2);exit;
                //获取上级信息
                $res3 = Db::table('ims_bj_shopn_member')->field('realname,mobile')->where('id='.$res1_v['pid'])->limit(1)->select();
                //获取原始码信息
//                echo 'res3<pre>';print_r($res3);exit;
                $res4 = Db::table('ims_bj_shopn_member')->field('realname,mobile')->where('id='.$res1_v['staffid'])->limit(1)->select();
//                echo 'res4<pre>';print_r($res4);exit;
                // start Modify by wangqin 2017-12-16 查询对应办事处
                // $res5 = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb, sys_department sd, sys_departbeauty_relation sdr')->field('sd.st_department')->where("mem.storeid=ibb.id and sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id and mem.mobile='".$res1_v['mobile']."'")->limit(1)->select();
                $res5 = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb')->join(['sys_departbeauty_relation'=>'sdr'],'ibb.sign rlike sdr.id_sign','LEFT')->join(['sys_department'=>'sd'],'sd.id_department = sdr.id_department','LEFT')->field('sd.st_department')->where("mem.storeid=ibb.id and ibb.sign='".$res1_v['sign']."' and mem.mobile='".$res1_v['mobile']."'")->limit(1)->select();
                // end Modify by wangqin 2017-12-16

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
                //注册来源
                if($res1_v['id_regsource'] == 2)
                {
                    $id_regsource = 'Andriod';
                }else if($res1_v['id_regsource'] == 3)
                {
                    $id_regsource = 'IOS';
                }else
                {
                    $id_regsource = 'Wechat';
                }
//                echo 'res4<pre>';print_r($res4[0]);exit;
//
                //去掉字符串空格换行符
                $qian=array(" ","　","\t","\n","\r");
//
                $address =   str_replace($qian,'',@$res1_v['address']);
                $address =   str_replace(',','||',@$res1_v['address']);

                // start Modify by wangqin 2017-11-21
                $res31 = array('mobile'=>'','realname'=>'');$res41 = array('mobile'=>'','realname'=>'');
                if($res3)
                {
                    $res31['mobile'] = $res3[0]['mobile'];
                    $res31['realname'] = $res3[0]['realname'];
                    $res31['realname'] = @str_replace($qian,'',@$res31['realname']);
                }
                if($res4)
                {
                    $res41['mobile'] = $res4[0]['mobile'];
                    $res41['realname'] = $res4[0]['realname'];
                    $res41['realname'] = @str_replace($qian,'',@$res41['realname']);
                }
                // start Modify by wangqin 2017-12-15
//                 if(strlen($res1_v['st_department'])<10)
//                 {
                if($res5)
                {
                    $st_department = @$res5[0]['st_department']==''?'':@$res5[0]['st_department'].'办事处';
                }else
                {
                    $st_department='';
                }
                if($res1_v['sign']=='666-666')
                {
                    $st_department='总部人员';
                }

//                 }
                // end Modify by wangqin 2017-12-15

                $data_csv = array(
                    @$res1_v['location_p'],
                    @$res1_v['location_c'],
                    @$address,
                    @str_replace($qian,'',@$res1_v['title']),
                    @$res1_v['sign'],
                    @str_replace($qian,'',@$res1_v['realname']),
                    @$res1_v['mobile'],
                    @$role,
                    @$res31['realname'],
                    @$res31['mobile'],
                    @$res41['realname'],
                    @$res41['mobile'],
                    @date ( 'Y-m-d H:i:s', $res1_v['createtime'] ) ,
                    @$st_department
                    // @$res1_v['cretime']
                );
//                echo 'data_csv:<pre>';print_r($data_csv);exit;
                $arr[] = $data_csv;
            }
        }
       // echo 'arr:<pre>';print_r($arr);exit;
        $name = 'user-'.$dt1.'-'.$dt2;

//        $csv = new Csv();
//        $csv->put_csv($arr,$headArr,$name);
        $res = reportCsv($headArr,$arr,$name);
        // $url = 'http://localhost:81/csv/';
        //服务器
        $url = 'http://live.qunarmei.com/csv/';
        $res = $url.$res;
        //浏览器下载

        return $res;

        // $res = $this-> reportCsv($headArr,$arr,$name);
        //本地
        //$url = 'http://localhost:81/';
        //服务器
       // $url = 'http://live.qunarmei.com/';
        // $res = $url.$res;
        //浏览器下载
        // header("Location:".$res);
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
        //$csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\\';
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