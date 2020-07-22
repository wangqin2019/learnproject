<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2017/11/14
 * Time: 10:10
 */
//命名空间
namespace app\api\controller;
use app\api\model\Live;
// 引入extend下的第三方扩展邮件类
use phpmailer_kz\SendMail;
// 引入vendor下composer安装的第三方扩展邮件类
//use phpmailer\phpmailer\SendMail11;
// 通过loader加载vendor扩展类
use qiniu_transcoding\QnFile;
use think\Db;
use think\Loader;
use app\api\service\RedisSer;
//api测试使用
class Test extends Base
{
    protected $code = 0;
    protected $data = [];
    protected $msg = '获取失败';

    // 格式化字符串
    public function forMat()
    {
        $arr = [
            '151-097',
            '151-134',
            '151-101',
            '211-072',
            '151-118',
            '151-062',
            '151-131',
            '211-050',
            '151-119',
            '151-106',
            '151-088',
            '151-108',

        ];
        return implode(',',$arr);
    }
    /**
     * sqlserver数据库连接测试
     */
    public function connectSqlserver()
    {
        $db = Db::connect(config('erp_database'));
        $sql = "select sum(aa.total)*0.38 from
(select res.cInvCode,res.iquantity,res.isaleprice1,(res.iquantity*res.isaleprice1) total from (
 select
  d.cInvCode,
  d.iquantity,
  (select top 1 isaleprice1 from SA_InvPriceJustDetail where cinvcode=d.cInvCode ORDER by id desc
  ) as isaleprice1
 from
  SO_SOMain m
 left join SO_SODetails d on
  m.cSOCode = d.cSOCode
 where
  m.cCusCode = '231-134'
  and m.dDate >= '2020-01-12 00:00:00.000'
  and d.cDefine22='2020年线上直播配赠'
) res) aa";
        var_dump($db->query($sql));
    }

    public function smsSend($mobile,$id_template,$str=null)
    {
        $queryStr = 'mobile='.$mobile.'&name=qunarmeiApp&pwd=qunarmeiApp&template='.$id_template.'&type=1';
        if($str){
            $queryStr = 'code='.$str.'&'.$queryStr;
        }
        $key = md5($queryStr);
        $queryStr = $queryStr.'&key='.$key;
        $url = 'http://sms.qunarmei.com/sms.php?'.$queryStr;
        $res = curl_get($url);
        var_dump($url);var_dump($res);die;
        return $res;
    }
    public function sms()
    {
        $mobile = input('mobile');
        $msg1 = ['status'=>'已通过'];
        $msg = json_encode($msg1,JSON_UNESCAPED_UNICODE);
        $res = $this->smsSend($mobile,116,$msg);
        if (!$res) {
            $this->smsSend($mobile,1,$code);
        }
        var_dump($res);die;
    }
    public function testRedis()
    {
        $redisSer = new RedisSer();
        $key = 'testRedis_';
        $val = [
            'func' => 'testRedis',
            'time' => date('Y-m-d H:i:s')
        ];
        $val = json_encode($val);
        $redisSer->pushQueue($key,$val);
        echo '加入队列成功';
    }
    public function testOutRedis()
    {
        $redisSer = new RedisSer();
        $key = 'testRedis_';
        $res = $redisSer->pullQueue($key);
        echo '<pre>';print_r($res);
        echo '出队列成功';
    }
    public function course()
    {
        $url = 'http://test.chengmei.com/it_api/courseapi.php';
        $str = 'user=tiyantest&time='.time().'&msg=已学习完成第四课时';
        $token = md5($str.'&pwd=qunarmei');
        $str = $str.'&api_token='.$token;
        $url = $url.'?'.$str;
        print_r($url);die;
        $res = curl_get($url);
        echo '<pre>';print_r($res) ;
    }


    public function test()
     {
        $tent = new \tencent_cloud\TimChat();
        $chat_id = input('chat_id');
        $content = input('content');
        $res = $tent->sendMsgs($chat_id,$content,'sj',1);
        return $res;
     }

    /*
     * 功能: 发送邮件
     * 请求: toMail=>收件人邮箱;多个时,通过数组传值
     * 返回: json=>发送结果通知
     *
     * */
    public function sendM($toMail='wangqin@chengmei.com')
    {

        $mail = new SendMail();
//        $toMail = 'wangqin@chengmei.com';
        $subject = '邮件测试';
        $content = '你好, <b>朋友</b>! <br/>这是一封来自<a href="http://www.jb51.net"
target="_blank">jb51.net</a>的测试邮件！<br/>';
        $attachment = 'C:\Users\wq\Pictures\1223\nan.jpg';
        $res = $mail->sendMailS($toMail, $subject, $content, $attachment);
        if($res)
        {
            $this->code = 1;
            $this->data = $res;
            $this->msg = '邮件发送成功';
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    /*
     * 功能: 生成pdf
     * 请求: toMail=>收件人邮箱;多个时,通过数组传值
     * 返回: json=>发送结果通知
     *
     * */
    public function makePdf()
    {

        // 加载pdf扩展
        Loader::import('tcpdf.tcpdf');
//        vendor('tcpdf.tcpdf');
        $pdf = new \TCPDF('A4-L');
        $pdf->AddPage();
        $pdf->writeHTML('<h2 style="color:red;text-align:center;">Hello World</h3>', true, false, true, false, '');
//        $pdf->Output('1.pdf', 'I');
         $pdf->Output('2.pdf', 'D');
        // 在"D"输出方式下，下载下来的1.pdf文件能正常打开并显示
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }

    /*
     * 功能: 测试异步调用方法
     * 请求: api/test/test1 => 模块/控制器/方法
     * 返回:
     * */
    public function yibu_test()
    {
        $res = $this->asyncronous('api/test/test1');
        if($res['flag'])
        {
            $this->code=1;
            $this->msg = $res['msg'];
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }

    function test1()
    {
        $sum = 0;
        for($i=1;$i<1000000;$i++)
        {
            $sum+=$i;
        }
        $msg = date('Y-m-d H:i:s').'-sum:'.$sum;
        file_put_contents('a.txt',$msg);
//        return $sum;
    }

    /*
     * 异常捕获
     * */
    public function dealExcep(){
        try{
//            $res = 3/0;
//            return 11;
            
        //////当前查询出来的数据
        $redata = array(
            0=>['id'=>1,'name'=>'张三','age'=>22,'hobby'=>'学习'],
            1=>['id'=>1,'name'=>'张三','age'=>22,'hobby'=>'骑行'],
            2=>['id'=>2,'name'=>'李四','age'=>20,'hobby'=>'旅游'],
            3=>['id'=>2,'name'=>'李四','age'=>20,'hobby'=>'测试']
        );
            $data1 = [];
            $data2 = [];

            foreach ($redata as $k=>$v) {
                // 过滤掉相同id键值数据
                if(!isset($data1[$v['id']])){
                    $data1[$v['id']] = $v;
                }
                // 根据id将hobby组装成二维数组
                $data2[$v['id']][] = $v['hobby'];
            }

            foreach ($data1 as $k=>$v) {
                // 根据id赋值hobby值
                $data1[$k]['hobby'] = $data2[$k];
            }
            $data1 = array_values($data1);
            echo '<pre>';print_r($data1);
        //怎么样才能转换成下面这种形式的？  写了一上午 愣是没写出来
//        $arr = array(
//            0=>[
//                'id'=>1,'name'=>'张三','age'=>22,'hobby'=> ['学习', '骑行']
//            ],
//            1=>[
//                'id'=>2,'name'=>'李四','age'=>20,'hobby'=> ['旅游']
//            ]
//        );
            
        }catch(\Exception $e){
            return $this->returnMsg(400,(object)[],$e->getMessage());
//            throw new \Exception('参数错误');
        }
    }

    /*
     * 关联查询
     *
     * */
    public function userInfo()
    {
        $arr['user_id'] = input('user_id');
        $user_sev = new \app\api\service\Test();
        $res = $user_sev->getUserFans($arr);
        return $res;
    }

    /*
     * 直播点赞数
     *
     * */
    public function zbDianzanNum()
    {
        $rest = [
            'code' => 0,
            'data' => (object)[],
            'msg' => '直播暂未开启,请耐心等候'
        ];
        $testser = new \app\api\service\Test();
        $res = $testser->zbDianzanNum();
        if(!empty($res)){
            $rest['code'] = 1;
            $rest['data'] = ['dianzanNum'=>$res];
            $rest['msg'] = '获取成功';
        }
        return $this->returnMsg($rest['code'],$rest['data'],$rest['msg']);
    }

    /**
     * pdf转图片测试
     */
    public function pdfTojpg()
    {
//        $_SERVER['GS_BIN'] = 'gs-905-osx';
//        $pdfXtractor = new \PdfXtractor\PdfXtractor(isset($_SERVER['GS_BIN']) ? $_SERVER['GS_BIN'] : false);
//        $input = __DIR__;
//        $output = __DIR__;
//        dump($pdfXtractor);
////        chmod('D:\software\phpstudy_pro\WWW\qunarmei-live\vendor\guillaumepotier\pdfxtractor\bin\\', 0777);
//
//        $res = $pdfXtractor->load($input.'/dhsj.pdf')->set($output, 'dhsj0629.jpg')->extract(true);
//        $pdfXtractor->set($output, 'test');
////        $res2 = $res->set($output, 'dhsj0629.jpg');
////        $res3 = $res2->extract(true);
//        dump($pdfXtractor->load($input.'/dhsj.pdf')->set($output,'123456.jpg'));
//        $pdfXtractor = new \PdfXtractor\PdfXtractor();
//        $pdfXtractor->load(__DIR__.'/dhsj.pdf')->set(__DIR__.'/output', 'extract');
//        dump($pdfXtractor);
//        dump($pdfXtractor->extract());

        $pdfPath = __DIR__ . '\dhsj.pdf';
        $imagePath = __DIR__ . '\dhsj0629.jpg';

// Render PDF to image and save to disk.
        \Baraja\PdfToImage\Convertor::convert($pdfPath, $imagePath, 'jpg');
    }

    public function info()
    {
        echo phpinfo();
    }

    public function getQnFile()
    {
        $res_lives = [];
        $qnser = new QnFile();
        $rest = $qnser->fileList();
        // 查询视频及数据库中对应的信息
//        $live_id = [3041,
//            3037,
//            3035,
//            3024,
//            3016,
//            3008,
//            3002,
//            2984,
//            2975,
//            2969,
//            2957,
//            2920,
//            2909,
//            2895,
//            2867,
//            2809,
//            2794,
//            2779,
//            2764,
//            2665,
//            2633,
//            2617,
//            2602,
//            2534,
//            2521,
//            2423,
//            2286,
//            2121,
//            2119,
//            2120,
//            2128,
//            2134,
//            2041,
//            2001,
//            1962,
//            1990,
//            1964,
//            1965,
//            1963,
//            1526,
//            602,
//            427,
//            426,
//            425,
//            424,
//            423,
//            422,
//            1,
//            6,
//            2,
//            7,
//            4,
//            8];
//        $map['id'] = ['in',$live_id];
        $res_live = Db::table('think_live')->select();
        $file_names = [];$del_file = [];
        if($res_live){
            $url = 'http://pili-vod.qunarmei.com/';
            foreach ($res_live as $v) {
                $arr1['file_name'] = str_replace($url,'',$v['see_url']);
                $arr1['live_id'] = $v['id'];
                $file_names[] = $arr1['file_name'];
                $res_lives[] = $arr1;
            }

            foreach ($rest as $v1) {
                if(!in_array($v1['file_name'],$file_names)){
                    $del_file[] = $v1['file_name'];
                }
            }
            // 删除不存在的文件
            foreach ($del_file as $vd) {
                $qnser->delFile($vd);
            }
        }
        dump($del_file);die;
        return $rest;

    }

    public function delQnFile()
    {
        return $this->code;
    }
}