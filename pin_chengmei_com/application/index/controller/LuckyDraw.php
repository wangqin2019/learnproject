<?php
//start Modify by wangqin 2017-11-03
namespace app\index\controller;
use think\Controller;
use think\Db;

/**
 * LuckyDraw: 抽奖活动
 */
set_time_limit(0);
class LuckyDraw  extends Base
{
    //测试开关
//    protected $test = 'on';

//    public function _initialize()
//    {
//        $config = cache('db_config_data');
//        if(!$config){
//            $config = load_config();
//            cache('db_config_data',$config);
//        }
//        config($config);
//    }



    //抽奖活动首页,web页面
    public function index()
    {
        return $this->fetch();
    }


    /***
     * 修改中奖用户的信息，反馈中奖用户的完整信息
     * @param $mobiles
     * @return array
     */
    public function update_user($draw_id)
    {
        $time = date('Y-m-d H:i:s');
        $map0['flag']=array('eq',0);
        $map0['draw_id']=array('eq',$draw_id);
        $ticketList=Db::name('ticket_user')->field('ticket_code')->where($map0)->select();
        $draw=Db::name('draw')->find($draw_id);
        $d_pic=config("ticket118_pic.1");
//        Db::name('lucky_mobile')->where(['draw_id'=>$draw_id])->update(['status'=>1]);
        foreach ($ticketList as $v) {
            $data = array('status'=>1,'flag'=>1,'update_time'=>$time,'draw_id'=>$draw_id,'draw_rank'=>$draw['draw_rank'],'draw_name'=>$draw['draw_name'],'draw_pic'=>$d_pic);
            Db::name('ticket_user')->where(['ticket_code'=>$v['ticket_code']])->update($data);
        }
        return true;
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

    //上传中奖名单 by houdianjing at 2018-11-14
    public function upDrawList(){
            //中奖处理
            $draw_id=input('param.draw_id');//当前抽奖奖项id
            //$tickets = input('param.tickets');
            //$ticketsCount = explode(",", $tickets);
            if ($draw_id) {
                // echo 1;
                @ob_end_flush();
                @ob_flush();
                @flush();
                @ignore_user_abort(true);
                //$this->update_user($draw_id);

                $list=self::$redis->sMembers('draw'.$draw_id.'codeList');
                print_r($list);
                die();


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