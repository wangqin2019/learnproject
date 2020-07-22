<?php
/**
 * Created by PhpStorm.
 * User: HOUDJ
 * Date: 2018/8/9
 * Time: 16:20
 */

namespace app\index\job;
use think\Controller;
use think\Db;
use think\queue\job;

class Send extends Controller
{
    public function _initialize()
    {
        $config = cache('db_config_data');

        if(!$config){
            $config = load_config();
            cache('db_config_data',$config);
        }
        config($config);
    }
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data){
        // 如有必要,可以根据业务需求和数据库中的最新数据,判断该任务是否仍有必要执行.
//        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
//        if(!$isJobStillNeedToBeDone){
//            $job->delete();
//            return;
//        }



        $isJobDone = $this->doMessageJob($data);

        if ($isJobDone) {
            //如果任务执行成功， 记得删除任务
            $job->delete();
            print("<info>message send ok"."</info>\n");
        }else{
            //if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                //print("<warn>Hello Job has been retried more than 3 times!"."</warn>\n");
                $job->delete();
                // 也可以重新发布这个任务
                //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
                //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
            //}
        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function doMessageJob($data) {
            //根据消息中的数据进行实际的业务处理...
        try {
            if (is_array($data)) {
                $this->sendSms($data['name'],$data['mobile'],$data['sms_id']);
            }
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    /***
     * 发送中奖短信
     * @param $mobile
     */
    public function sendSms($name, $mobile,$smsId)
    {
        $send['mobile'] = $mobile;
        $send['pwd'] = 'admin';
        $send['name'] = 'huangwei';
        // $code = $name;
        $code = '{"mobile":"' . $mobile . '","name":"' . $name . '"}';
        $send['type'] = 1;
        $send['template'] = $smsId; //模板id
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
        $dat = json_encode(array('url' => $url, 'resp' => $dat));
        $data = array('mobile' => $mobile, 'name' => $name, 'state' => $dat, 'log_time' => date('Y-m-d H:i:s'));
        \think\Db::name('lucky_draw_sms')->insert($data);
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

}