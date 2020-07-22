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

class MyQueue extends Controller
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
            print(date('Y-m-d H:i:s')."：my_queue发送记录：".json_encode($data)." send ok\n");
        }else{
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                //print("<warn>Hello Job has been retried more than 3 times!"."</warn>\n");
                $job->delete();
                // 也可以重新发布这个任务
                //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
                //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
            }
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
                switch ($data['scene']){
                    case 'log':
                        Db::name($data['table_name'])->insert($data['insert_data']);
                        break;
                    case 'draw_mobile':
                        $this->sendDrawByGoods($data['data']['draw_table'],$data['data']['draw_id'],$data['data']['draw_code']);
                        break;
                    case 'draw_code':
                        $this->sendDrawByCode($data['data']['draw_table'],$data['data']['draw_code'],$data['data']['draw_data']);
                }
            }
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * 发奖 改变奖券状态
     * @param $draw_id
     * @param $draw_code
     */
    public function sendDrawByGoods($draw_table,$draw_id,$draw_code){
        set_time_limit(0);
        $check=Db::name('ticket_user')->where(['ticket_code'=>$draw_code,'flag'=>0])->count();
        if($check) {
            $drawInfo = Db::name('draw')->where('id',$draw_id)->find();
            $map['stock'] = array('gt', 0);
            $map['fid'] = array('eq', $drawInfo['id']);
            $list = Db::name('draw_goods')->field('id,name,stock')->where($map)->select();
            if ($list) {
                foreach ($list as $key => $val) {
                    $arr[$val['id']] = $val['stock'];
                }
                $coupon_id = comm_getRand($arr); //根据概率获取奖品id
                if ($coupon_id) {
                    $draw = Db::name('draw_goods')->find($coupon_id);
                    $data = array('flag' => 1, 'update_time' => date('Y-m-d H:i:s'),'draw_id' =>$draw_id, 'draw_rank' => $drawInfo['draw_rank'], 'draw_name' => $draw['name'], 'draw_pic' => 'http://ml.chengmei.com/jp1_0416.png');
                    $map0['ticket_code'] = array('eq', $draw_code);
                    $res = Db::name($draw_table)->where($map0)->update($data);
                    if ($res) {
                        Db::name('draw_goods')->where('id', $coupon_id)->setDec('stock');
                    }
                }
            }
        }
    }

    /**
     * 发放指定奖项的奖
     * @param $data
     */
    public function sendDrawByCode($draw_table,$code,$data){
        $data = array('flag' => 1, 'update_time' => date('Y-m-d H:i:s'), 'draw_id' => $data['did'],'draw_rank' => $data['title'], 'draw_name' => $data['name'], 'draw_pic' => 'http://ml.chengmei.com/jp1_0416.png');
        Db::name($draw_table)->where('ticket_code',$code)->update($data);
    }



}