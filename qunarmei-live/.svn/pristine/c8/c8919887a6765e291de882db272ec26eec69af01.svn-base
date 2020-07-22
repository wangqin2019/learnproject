<?php
//start Modify by wangqin 2017-11-03
namespace app\index\controller;
use think\Controller;
use think\Db;

//整合腾讯云通信扩展
use tencent_cloud\TimChat;


/**
 * LuckyDraw: 抽奖活动
 */
set_time_limit(0);
class LuckyDraw  extends Controller
{
    //测试开关
//    protected $test = 'on';

    public function _initialize()
    {
        $config = cache('db_config_data');
        if(!$config){
            $config = load_config();
            cache('db_config_data',$config);
        }
        config($config);
    }



    //抽奖活动首页,web页面
    public function index()
    {
        return $this->fetch();
    }

//    //获取聊天室在线用户号码
//    public function getMobile($chat_id='',$num='')
//    {
//        if(!$chat_id)
//        {
////            $chat_id = '@TGS#a6QCZE6ET';
////          // start Modify by wangqin 2017-12-27
//            $rest = Db::name('live')->field('chat_id')->where('db_statu=0 and statu=1 and live_source=1')->limit(1)->select();
//            // end Modify by wangqin 2017-12-27
//            $chat_id = $rest[0]['chat_id'];
//        }
//        $tent = new TimChat();
//        $resp = $tent->getChatMem($chat_id);
//        //读取文件中的号码
////        $resp = './txt/luckDraw1509688570.txt';
////        $mobiles = file_get_contents($resp);
////        $mobiles = explode('\n',$mobiles);
////        print_r($mobiles);
////        echo  'mobiles:'.$mobiles;
//
//        //去除已中奖的用户
//        $mobile_y = Db::name('lucky_draw')->field('mobile')->where('prize>0')->select();
//        if($mobile_y)
//        {
//            $resp = $this->arrCha($resp,$mobile_y);
//        }
//        // start Modify by wangqin 2017-12-04 去除本公司和办事处人员参与抽奖
//        // $mobile_z = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb')->field('mem.mobile')->where("mem.storeid=ibb.id and mobile>0 and ibb.sign in ('888-888','666-666','000-000') ")->group('mobile')->select();
//        // if($mobile_z)
//        // {
//        //     $resp = $this->arrCha($resp,$mobile_z);
//        // }
//        // end Modify by wangqin 2017-12-04
//        // start Modify by wangqin 2017-12-14 剔除总公司和办事处人员参与抽奖
//        // $mobile_a = Db::name('lucky_draw_del')->field('mobile')->where("mobile>0")->group('mobile')->select();
//        // if($mobile_a)
//        // {
//        //     $resp = $this->arrCha($resp,$mobile_a);
//        // }
//        // end Modify by wangqin 2017-12-14
//        if($resp)
//        {
//            shuffle($resp);
//            if (sizeof($resp) < $num) {
//                return false;
//            }
//            for ($i = 0; $i < $num; $i++) {
//                $b[] = $resp[$i];
//            }
//            return $b;
//        }
//
//    }
//
//    /***
//     * 返回毫秒级时间戳
//     * @return string
//     */
//    public function msectime()
//    {
//        list($tmp1, $tmp2) = explode(' ', microtime());
//        return $tmp2 . $tmp1;
//    }

    /***
     * 抽奖
     * @param $name 传入待抽奖名单
     * @param int $num 抽奖人数
     * @return array 返回中奖人信息
     */
//    public  function soiree_($name, $num = 50)
//    {
//        shuffle($name);//打乱数组
//        $rename = array();
//        for ($i = 0; $i < $num; $i++) {
//            $rename[] = $name[$i];
//        }
//        return $rename;
//    }

    /***
     * 修改中奖用户的信息，反馈中奖用户的完整信息
     * @param $mobiles
     * @return array
     */
    public function update_user($mobiles,$draw_type,$isTrue)
    {
        $re = Db::name('lucky_draw')->field('prize')->order('prize desc')->limit(1)->select();
        if($re)
        {
            $j = $re[0]['prize'] + 1;//次数
        }else
        {
            $j = 1;
        }

        $time = date('Y-m-d H:i:s');
        $mobiles = explode(',',$mobiles);
        foreach ($mobiles as $v) {
            $name = Db::table('ims_bj_shopn_member')->field('realname name')->where("mobile='$v'")->limit(1)->select();
            if($name)
            {
                $name = $name[0]['name'];
            }else
            {
                $name = $v;
            }
            $draw=Db::name('draw')->find($draw_type);
            $data = array('mobile'=>$v,'name'=>$name,'prize'=>$j,'log_time'=>$time,'draw_type'=>$draw_type,'draw_rank'=>$draw['draw_rank'],'draw_name'=>$draw['draw_name'],'is_true'=>$isTrue);
            $sql = Db::name('lucky_draw')->insert($data);
        }
        $res = Db::name('lucky_draw')->alias('l')->join('think_draw d','l.draw_type=d.id','left')->field('l.name,l.mobile,d.sms_id')->where('l.prize='.$j)->select();
        return $res;
    }

    /***
     * 发送中奖短信
     * @param $mobile
     */
    public function sms($name, $mobile,$draw_type)
    {
//        if (self::$test == 'on') {
        $send['mobile'] = $mobile;
        $send['pwd'] = 'admin';
        $send['name'] = 'huangwei';
        // $code = $name;
        $code = '{"mobile":"'.$mobile.'","name":"'.$name.'"}';
        $send['type'] = 1;
        $send['template'] = $draw_type; //模板id
        $send['code'] = $code;
        $send['code2'] = $send['mobile'];
        $str = '';
        ksort($send);
        foreach ($send as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = substr($str, 0, -1);
        $key = md5($str);
        $str .= '&key=' . $key;
        $send['key'] = $key;
        $url = 'http://sms.qunarmei.com/sms.php?' . $str;
        $dat = $this->curl_get($url);
//        $dat = file_get_contents("'$url'");//发送短信
//        $dat = file_get_contents($url) ;
        $dat = json_encode(array('url'=>$url,'resp'=>$dat));
        $data = array('mobile'=>$mobile,'name'=>$name,'state'=>$dat,'log_time'=>date('Y-m-d H:i:s'));
        Db::name('lucky_draw_sms')->insert($data);
    }

    // public function curl_get($url)
    // {
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_HEADER, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // $data = curl_exec($ch);
        // curl_close($ch);
// //        return json_decode($data, true)
        // return $data;
    // }

//
//	    public function mtest(){
//        $url='http://sms.qunarmei.com/sms.php?code={"mobile":"13621934965","name":"焖鱼浓墨"}&code2=13621934965&mobile=13621934965&name=huangwei&pwd=admin&template=35&type=1&key=f456417a292061917c68e8a459394750';
//        $sss=$this->curl_get($url);
//        echo $sss;
//
//    }

    public function curl_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }


    //前端页面抽奖开始/结束请求处理
    /*
     *  act抽奖状态
     *  num抽奖人数
     * */
//    public function actDraw($act='',$num='')
//    {
//
//        $act = input('act');
//        $num = input('num');
//        if($act == 'start' && $num)
//        {
//
//           //获取用户
////            $res = Db::name('live')->field('chat_id')->where('statu=1')->order('id desc')->limit(1)->select();
////            $chat_id = '@TGS#a6QCZE6ET';
////            if($res)
////            {
////                $chat_id = $res[0]['chat_id'];
////            }
////            $name = $this->getMobile($chat_id,$num);
////            shuffle($name);
////            if($name)
////            {
////                $n = json_encode($name, JSON_UNESCAPED_UNICODE);
////                echo $n;
////                exit;
////            }
//            $arr=array(
//                array('mobile'=>'1111'),
//                array('mobile'=>'2222'),
//                array('mobile'=>'3333'),
//                array('mobile'=>'4444'),
//                array('mobile'=>'5555'),
//                array('mobile'=>'6666'),
//                array('mobile'=>'7777'),
//            );
//            echo json_encode($arr);
//
//        }else if($act == 'end' && input('name'))
//        {
////            $draw_type=input('draw_type');
////            //中奖处理
////            $name = substr(input('name'), 0, -1);
////            $rname = explode(",", $name);
////            if (sizeof($rname) >= 1 && sizeof($rname) <= 100) {
////                // echo 1;
////                @ob_end_flush();
////                @ob_flush();
////                @flush();
////                @ignore_user_abort(true);
////                $users = $this->update_user($name,$draw_type);
////                // if ($this->test_check() == 'on') {
////                //     foreach ($users as $k => $v) {
////                //         $this->sms($v['name'], $v['mobile'],$draw_type);
////                //     }
////                // } else {
////                //     //$this->sms('侯典敬', 15821881959,$draw_type);
////                //     // $this->sms('许文宇', 15618021758);
////                //     $this->sms('王钦', 15921324164,$draw_type);
////                // }
////
////            } else {
////                echo -1;
////                exit;
////            }
//
//        }
//    }

    //上传中奖名单 by houdianjing at 2018-11-14
    public function upDrawList(){
            //中奖处理
            $draw_type=input('param.draw_type');
            $luckMobile = input('param.luckMobile');
            $isTrue = input('param.isTrue');
            $luckMobileCount = explode(",", $luckMobile);
            if (sizeof($luckMobileCount) >= 1 && sizeof($luckMobileCount) <= 200) {
                // echo 1;
                @ob_end_flush();
                @ob_flush();
                @flush();
                @ignore_user_abort(true);
                $users = $this->update_user($luckMobile,$draw_type,$isTrue);
                $draw_sms_flag=config('draw_sms');//是否及时发送短信
                echo  $draw_sms_flag;
                if($draw_sms_flag){
                 foreach ($users as $k => $v) {
                     $this->sms($v['name'], $v['mobile'],$v['sms_id']);
                 }
                }
            } else {
                echo -1;
                exit;
            }
    }

    //二维数组求差集
//    public function arrCha($arr1,$arr2)
//    {
//        $arr3 = array();
//        foreach ($arr1 as $key => $value) {
//            if(!in_array($value,$arr2)){
//                $arr3[]=$value;
//            }
//        }
//        return $arr3;
//    }

}
//end Modify by wangqin 2017-11-03