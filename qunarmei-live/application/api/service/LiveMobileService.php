<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:46
 */

namespace app\api\service;
use app\api\model\AssessScore;
use app\api\model\AssessUser;
use app\api\model\Live;
use app\api\model\LiveAlgorithm;
use app\api\model\LiveSignup;
use app\api\model\User;
use tencent_cloud\TimChat;
use think\Db;
/**
 * 手机端直播服务类
 */
class LiveMobileService extends BaseSer
{
    public $sign_img = 'http://appc.qunarmei.com/dingyue0604.png';// 订阅成功提示图片
    // public $zb_detail_url = 'http://testc.qunarmei.com:9091/html/';// 直播分享html
    public $zb_detail_url = 'http://live.qunarmei.com/html/';
    /**
     * app直播当前展示人数
     * @param string $chat_id 聊天室id
     */
    public function appNumbers($chat_id)
    {
        // 1.获取当前GateWay人数
        $chat_num = 1;
        // 调用接口获取
        $url = config('gateway_http_url').'websocket/http_api/getAppNum?chat_id='.$chat_id;
        $res = curl_get($url);
        $chat_num = $res>1?$res:$chat_num;
        $chat_num1 = $chat_num;
        // 是否存在 平均递增算法: y=(nums/(minute*60))*间隔数+m
        $map['chat_id'] = $chat_id;
        $map['type'] = 2;
        $res = LiveAlgorithm::get($map);
        if ($res) {
            $ii = 10;
            $chat_num = $this->getCntNum($ii,$chat_id,$chat_num);

            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = ['num' => $chat_num];
            return $this->returnArr();
        }
        // 是否存在线性算法
        $map['type'] = 1;
        $res1 = LiveAlgorithm::get($map);
        if($res1){
            // 计算x数分钟数
            $dt_cha = (time() - strtotime($res1['create_time']))/60;
            $chat_num = ceil($dt_cha * $res1['k']) + $res1['m'] + 2;
            // 当线性数大于设置的最大值时
            if($chat_num >= $res1['nums']){
                $chat_num = $res1['nums'] + $chat_num1;
            }
            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = ['num' => $chat_num];
            return $this->returnArr();
        }
        // 是否存在倍数
        $mapl['chat_id'] = $chat_id;
        $res2 = Live::get($mapl);
        if ($res2) {
            $chat_num = $res2['see_times_flag']?$res2['see_count_times'] * $chat_num  : $chat_num;
            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = ['num' => $chat_num];
        }
        return $this->returnArr();
    }
    /**
     * 直播订阅/取消
     * @param string $live_id 直播间id
     * @param string $user_id 用户id
     * @param string $type 操作,1:订阅,0:取消
     * @return string json
     */
    public function liveSignup($live_id,$user_id,$type)
    {
        // 查询是否预告过期,过期预告不容许显示
        $map_l['id'] = $live_id;
        $map_l['end_time'] = ['<',time()];
        $res_live = Live::get($map_l);
        if ($res_live) {
            $this->code = 0;
            $this->msg = '直播已过期,无法订阅!';
            $this->data = (object)[];
            return $this->returnArr();
        }
        // 查询
        $map['live_id'] = $live_id;
        $map['user_id'] = $user_id;
        $res = LiveSignup::get($map);
        $arr['img'] = '';
        if($res){
            if($type == 1){
                $this->code = 1;
                $this->msg = '用户已订阅';
            }elseif($type == 2){
                // 取消订阅
                $this->code = 1;
                LiveSignup::where($map)->delete();
                $this->msg = '取消订阅成功';
            }
        }else{
            if($type == 1){
                $data['create_time'] = time();
                $data['live_id'] = $live_id;
                $data['user_id'] = $user_id;
                // 查询用户上级美容师,storeid,
                $mapu['id'] = $user_id;
                $resu = User::get($mapu);
                if($resu){
                    $data['fid'] = $resu['staffid']==0?$resu['pid']:$resu['staffid'];
                    $data['storeid'] = $resu['storeid'];
                    $this->code = 1;
                    $this->msg = '用户订阅成功';
                    $arr['img'] = $this->sign_img;
                }
                LiveSignup::create($data);
                // 订阅成功发送短信通知用户
                $mobile = $resu['mobile'];
                $id_template = 124;
                // 查询直播间信息
                $mapl['id'] = $live_id;
                $resl = Live::where($mapl)->limit(1)->find();
                if ($resl) {
                    $arr_json['title'] = $resl['title'];
                    $arr_json['start_time'] = date('Y-m-d H:i:s',$resl['start_time']);
                    $str = json_encode($arr_json,JSON_UNESCAPED_UNICODE);
                    send_sms($mobile,$id_template,$str);
                }
            }
        }
        $this->data = $arr;
        return $this->returnArr();
    }
    /**
     * 直播订阅是否
     * @param string $live_id 直播间id
     * @param string $user_id 用户id
     * @return string json
     */
    public function liveSign($live_id,$user_id)
    {
        // 是否订阅,是否开始直播
        $map['id'] = $live_id;
        $res = Live::where($map)->limit(1)->find();
        if($res){
            $arr['start_time'] = 0;
            $arr['statu'] = $res['statu'] == 1?1:0;// 1:直播中,点击进入直播间;0:未直播:按钮置灰
            $arr['is_signup'] = 0;
            $arr['zb_detail_url'] = $this->zb_detail_url.'zb_share/zbsharepage.html?live_id='.$live_id.'&user_id='.$user_id;
            $arr['preheat_video_url'] = '';// 预热视频url
                // 查询是否订阅
            $this->msg = '用户未订阅';
            $map_sign['live_id'] = $live_id;
            $map_sign['user_id'] = $user_id;
            $res_sign = LiveSignup::where($map_sign)->field('user_id')->limit(1)->find();
            if($res_sign){
                $arr['is_signup'] = 1;
                $this->msg = '用户已订阅';
            }
            // 查询下当前直播开播时间
            $map_live['id'] = $live_id;
            $res_live = Live::where($map_live)->limit(1)->find();
            if($res_live){
                $arr['start_time'] = $res_live['start_time'];
                if($res_live['preheat_video_url']){
                    $arr['preheat_video_url'] = $res_live['preheat_video_url'];
                }
                $arr['is_preheat_signup'] = $res_live['is_preheat_signup'];
                $arr['is_live_poster'] = $res_live['is_live_poster'];
            }
            $this->code = 1;
            $this->data = $arr;
        }
        return $this->returnArr();
    }
    /**
     * 直播详情
     * @param string $live_id 直播间id
     * @param string $user_id 用户id
     */
    public function liveDetail($live_id,$user_id)
    {
        $map['id'] = $live_id;
        // 查询该主播大于当前时间还未开始的直播配置
        $res = Live::where($map)->order('start_time asc')->limit(1)->find();
        if($res){
            // 月/日,时,时间戳,标题,封面图,主播头像,主播名称,主播地址,内容,是否订阅,是否开始预热或者直播
            $arr['day'] = date('m/d',$res['start_time']);
            $arr['time'] = date('H:i',$res['start_time']);
            $arr['dt'] = $res['start_time'];
            $arr['title'] = $res['title'];
            $arr['content'] = $res['content'];
            $arr['address'] = $res['address'];
            $arr['live_img'] = $res['live_img'];
            $arr['user_name'] = $res['user_name'];
            $arr['user_img'] = $res['user_img'];
            $arr['statu'] = $res['statu'];
            $arr['signup_num'] = 0;// 订阅人数
            $arr['qrcode'] = '';// 海报二维码
            $arr['preheat_video_url'] = $res['preheat_video_url'];// 预热视频地址
            // 查询是否已有二维码,没有生成
            $mapu['id'] = $user_id;
            $resu = User::get($mapu);

            $map_qrcode['type'] = 0;
            $map_qrcode['live_id'] = $live_id;
            $map_qrcode['mobile'] = $resu['mobile'];
            $res_qrcode = Db::table('think_live_qrcode')->field('mobile,qrcode')->where($map_qrcode)->limit(1)->find();
            $url = $this->zb_detail_url.'zb_share/zbsharepage.html?live_id='.$live_id.'&user_id='.$user_id;
            if (empty($res_qrcode)) {
                $filename = 'qrcode_'.$user_id.'_'.$live_id.'_'.mt_rand(00,99);
                $arr['qrcode'] = $this->getQrcode($url,$filename);
                // 插入对应的二维码数据表
                $dataq['live_id'] = $live_id;
                $dataq['mobile'] = $resu['mobile'];
                $dataq['qrcode'] = $arr['qrcode'];
                $dataq['create_time'] = time();
                Db::table('think_live_qrcode')->insert($dataq);
            }else{
                $arr['qrcode'] = $res_qrcode['qrcode'];
            }

            $this->code = 1;
            $this->msg = '获取成功';
            $this->data = $arr;
        }
        return $this->returnArr();
    }
    /**
     * 直播当前人数
     * @param string $chat_id 聊天室id
     */
    public function liveNumbers($chat_id)
    {
        // 1.获取当前人数 x k值系列倍数
        $tent = new TimChat();
        $rest = $tent->getChatCntList([$chat_id]);
        $chat_num = 1;
        if($rest) {
            $chat_num = $rest[0]['chat_num'];
        }
        // 获取k值
        $map['chat_id'] = $chat_id;
        $res = LiveAlgorithm::get($map);
        if($res){
            // 计算x数分钟数
            $dt_cha = (time() - strtotime($res['create_time']))/60;
//            var_dump($dt_cha);
            // y = kx + m + 随机数
            $chat_num = ceil($dt_cha * $res['k']) + $res['m'] + 2;
            // 当线性数大于设置的最大值时
            if($chat_num >= $res['nums']){
                $chat_num = $res['nums'] + rand(1,9);
//                $chat_num = $res['nums'] + 2;
            }
//            var_dump($chat_num);
        }
        $this->code = 1;
        $this->msg = '获取成功';
        $this->data = ['num' => $chat_num];
        return $this->returnArr();
    }
    /**
     * 主播关闭直播间后的直播信息
     * @param string $chat_id 聊天室id
     * @param int $minute 多少分钟
     * @param int $nums 达到多少人
     */
    public function liveNumbersAdjustment($chat_id,$minute,$nums)
    {
        // 1.记录数据表到算法表
        $map['chat_id'] = $chat_id;
        $res = LiveAlgorithm::get($map);
        $data['minute'] = $minute;
        $data['nums'] = $nums;
        $chat_num = 1;
        // 2.查询当前直播间人数
        $tent = new TimChat();
        $rest = $tent->getChatCntList([$chat_id]);
        if($rest) {
            $chat_num = $rest[0]['chat_num'];
        }
        $data['m'] = $chat_num;
        if($res){
            LiveAlgorithm::update($data,$map);
        }else{
            $data['chat_id'] = $chat_id;
            $data['remark'] = '线性算法: y=kx+m';
            LiveAlgorithm::create($data);
        }
        // 2.查询当前直播间人数

        // 通过算法计算出比例值
        $algser = new AlgorithmSer();
        $a = [0 , $chat_num];
        $b = [$minute , $nums];// 按分钟计算
        $k = $algser->linear($a , $b);
        if($k){
            $data1['k'] = $k;
            LiveAlgorithm::update($data1,$map);
            $this->code = 1;
            $this->msg = '计算成功';
            $this->data = ['k'=>$k,'minute'=>$minute,'nums'=>$nums,'chat_id'=>$chat_id];
        }

        return $this->returnArr();
    }
    /**
     * 主播关闭直播间后的直播信息
     * @param int $live_id 直播间id
     */
    public function liveEnd($live_id)
    {

        $this->code = 1;
        // 获取主播和直播间信息
        $map['id'] = $live_id;
//        $map['statu'] = 2;
        $res = Live::get($map);
        if($res){
            $arr['statu'] = $res['statu'];
            $arr['img'] = $res['user_img'];
            $arr['name'] = $res['user_name'];
            $arr['address'] = $res['address'];
            $arr['end_info1'] = '今日直播已结束';
            $arr['end_info2'] = '感谢您的支持,期待与您再次相遇!';
            // 如果时长为空,自己计算时长
            if(empty($res['db_length'])){
                // 计算时长
                $db_length = strtotime($res['update_time']) - $res['insert_time'];
                $res['db_length'] = secToTime($db_length);
            }
            $arr['db_length'] = '直播时长: '.$res['db_length'];
            $arr['push_img_url'] = '';
            $arr['push_video_url'] = '';
            // 查询直播过程中是否在推送信息
            if($res['statu'] == 1 && $res['push_status']){
                $arr['push_img_url'] = $res['push_status']==1?$res['live_img']:'';
                $arr['push_video_url'] = $res['push_status']==2?$res['preheat_video_url']:'';
            }
            $this->msg = '获取成功';
            $this->data = $arr;
        }else{
            $this->data = (object)[];
        }
        return $this->returnArr();
    }
    /**
     * 主播主动关闭记录时长
     * @param int $live_id 直播间id
     * @param string $length 时长
     */
    public function closeLive($live_id,$length)
    {
        $this->code = 0;
        $this->msg = '记录失败';
        // 通过直播间id更新直播时长
        $res = Live::get($live_id);
        if($res){
            // 更新主播直播时长
            $map['id'] = $live_id;
            $data['db_length'] = $length;
            $data['statu'] = 2;

            Live::update($data,$map);
            // 清除直播列表redis缓存
            $liveser = new \app\api\controller\Live();
            $key = 'livelist19';

            $liveser->clearRedis($key);
            // 下发信息修改
            $this->code = 1;
            $this->msg = '记录时长成功';
            $this->data = [];

            // 1.查询是否是直播考核,如果是直播考核,绑定该状态
            if($res['assess_user_id']){

                // 2.查询类型是否是直播,直播自动提交,录像不做处理
                $mape['u.id'] = $res['assess_user_id'];
                $res_examine = AssessUser::alias('u')->join(['think_live_assess' => 'a'],['u.assess_id = a.id'],'LEFT')->field('u.id,a.assess_type')->where($mape)->limit(1)->find();
                if($res_examine){
                    if($res_examine['assess_type'] == 1){
                        $mapu['id'] = $res['assess_user_id'];
                        $datau['live_id'] = $live_id;
                        $datau['status'] = 2;
                        $datau['update_time'] = time();
                        AssessUser::where($mapu)->update($datau);

                        // 更新考官状态为待打分
                        $maps['assess_user_id'] = $res['assess_user_id'];
                        $datas['status'] = 4;
                        $datas['update_time'] = time();
                        AssessScore::where($maps)->update($datas);
                    }
                }
            }
        }
        return $this->returnArr();
    }
    /**
     * 是否有可续接的流直播
     * @param string $mobile 主播号码
     */
    public function whetherContinue($mobile)
    {
        // 直播结束后1小时内方可续播
        $map['user_id'] = $mobile;
        $map['flag'] = 0;
        $map['statu'] = ['neq',3];
        $dt = time()-3600;
        // 当前1小时内
        $map1 = " (update_time >='".date("Y-m-d H:i:s",$dt)."' or start_time >='".$dt."')";
        $res = Db::table('think_live')->where($map)->where($map1)->order('id desc')->limit(1)->find();
        $this->code = 1;
        $this->msg = '暂无数据';
        $arr['flag'] = 0;
        $arr['msg'] = '您当前没有可以续接的直播';
        if ($res) {
            $this->code = 1;
            $this->msg = '获取成功';
            $arr['flag'] = 1;
            $arr['msg'] = '有可以续接的直播';
        }
        $this->data = $arr;
        return $this->returnArr();
    }
    /**
     * 断流续播
     * @param int $mobile 用户号码
     * @param int $live_id 直播间id
     * @return
     */
    public function continueLive($mobile,$live_id=0)
    {
        // 获取主播当天最后1次直播流
        $map['user_id'] = $mobile;
        $map['flag'] = 0;
        $map['statu'] = ['neq',3];
        $dt = time()-3600;
        // 当前1小时内
        $map1 = " (update_time >='".date("Y-m-d H:i:s",$dt)."'  or start_time >='".$dt."' )";
        if ($live_id) {
            // 当天中选择的预告才能续播
            $map['id'] = $live_id;
        }
        $res = Db::table('think_live')->where($map)->where($map1)->order('id desc')->limit(1)->find();
        $this->code = 1;
        $this->msg = '您当前没有可以续接的直播';
        if ($res) {
            $arr['chat_id'] = $res['chat_id'];
            $arr['room_id'] = 0;
            $arr['live_id'] = $res['id'];
            $arr['classify_id'] = $res['classify_id']==null?'':$res['classify_id'];
            $arr['push_url'] = $res['push_url'];
            $arr['see_url'] = $res['see_url'];
            $arr['title'] = $res['title'];
            $arr['content'] = $res['content'];
            $arr['address'] = $res['address'];
            $arr['head_title'] = $res['address'];
            $arr['room_name'] = '';
            $arr['room_token'] = '';
            // 查询对应的连麦房间信息
            $mapr['live_name'] = $res['live_stream_name'];
            $resroom = Db::table('think_room')->where($mapr)->limit(1)->find();
            if ($resroom) {
                $arr['room_id'] = $resroom['id'];
                $arr['room_name'] = $resroom['room_name'];
                $arr['room_token'] = $resroom['room_token'];
                $live_mobile_ser = new \app\api\controller\LiveMobile();
                $room_token = $live_mobile_ser->getLianMaiToken($mobile,$resroom['id']);
                if ($room_token) {
                    $arr['room_token'] = $room_token;
                }
            }
            $this->data = $arr;
            $this->msg = '有可以续接的直播';
        }else{
            $this->data = (object)[];
        }
        return $this->returnArr();
    }
    /**
     * 生成直播分享二维码
     */
    public function makeShareCode($storeid,$mobile,$live_id)
    {
        $urls = 'type=1&storeid='.$storeid.'&mobile='.$mobile.'&live_id='.$live_id;
        $msg = config('url.qrcode_jump_url').'?'.$urls;// 扫码后跳转的url
        $filename = $storeid.'_'.$mobile.'_'.$live_id.'.png';

        $beauty_ser = new BeautyCodeService();
        $qrcode = $beauty_ser->makeQrCode($msg , $filename);
        return $qrcode;
    }
    /**
     * commit  : 调整人数
     * function: getCnt1   ($max/($minute * 60))*(当前时间戳-添加时间戳)
     * @param int $minute 分钟
     * @param int $max  最大数
     * @param int $i  当前次数
     * @param int $ii  间隔时间
     * @return int
     */
    // public function getCnt1($minute = 5, $max = 1000, $i = 1, $ii = 20){
    //     $s   = $minute * 60;//总秒数
    //     $len = ceil($s / $ii);//总次数
    //     $avg = intval(ceil($max / $s));//每秒平均数
    //     $random  = mt_rand(min($s,$avg),max($s,$avg));
    //     //当前值  当前次数 * 间隔时间 * 每秒平均数 - 随机数
    //     $current = abs($i * $ii * $avg - $random);
    //     //当前次数小与总次数 及 当前值大于等于最大值
    //     if($i < $len && $current >= $max){
    //         //当前情况下的上一次的数值
    //         $prev = ($i-1) * $ii * $avg;
    //         $sycs     = $len - $i; //剩余次数
    //         if($prev < $max){
    //             $sycs_avg = intval(ceil(($max - $prev) / $sycs));//剩余数值的平均数
    //             $current  = $prev + $sycs_avg ;//- $random;
    //         }else{
    //             $sycs_avg = intval(ceil(($prev - $max) /$sycs));//剩余数值的平均数
    //             $next = $max - $sycs_avg;//最大值 - 剩余数值的平均数
    //             $current =  mt_rand($next, $max) ;//- $random;
    //         }
    //     }
    //     //判断次数及当前值与总数的大小 || 判断当前值与最大值的大小
    //     if($i >= $len || $current >= $max){
    //         $current = $max;
    //     }
    //     return $current;
    // }
    // 平均递增人数算法
    public function getCnt1($minute = 5, $max = 1000, $i = 1, $ii = 20){
        $s   = $minute * 60;//总秒数
        $len = ceil($s / $ii);//总次数
        $avg = intval(ceil($max / $s));//每秒平均数

        //当前次数 * 间隔时间 * 每秒平均数
        $current = $i * $ii * $avg;
        if($current > $s){
            $random  = mt_rand(min($minute,$avg),max($minute,$avg));
        }else{
            $random  = mt_rand(min($avg,$ii),max($avg,$ii));
        }
        //当前值  当前次数 * 间隔时间 * 每秒平均数 - 随机数
        $current = abs($current - $random);
        if($current == 0){
            $current = 1;
        }
        //当前次数小与总次数 及 当前值大于等于最大值
        if($i < $len && $current >= $max){
            //当前情况下的上一次的数值
            $prev = ($i-1) * $ii * $avg;
            $sycs     = $len - $i; //剩余次数
            if($prev < $max){
                $sycs_avg = intval(ceil(($max - $prev) / $sycs));//剩余数值的平均数
                $current  = $prev + $sycs_avg ;//- $random;
            }else{
                $sycs_avg = intval(ceil(($prev - $max) /$sycs));//剩余数值的平均数
                $next = $max - $sycs_avg;//最大值 - 剩余数值的平均数
                $current =  mt_rand($next, $max) ;//- $random;
            }
        }
        //判断次数及当前值与总数的大小 || 判断当前值与最大值的大小
        if($i >= $len || $current >= $max){
            $current = $max;
        }
        return $current;
    }
    public function getCntNum($ii,$chat_id,$chat_num = 1){
        // chat_num当前聊天室人数
        $current = 1;
        //获取算法数据
        $map['chat_id'] = $chat_id;
        $map['create_time'] = ['<=', date('Y-m-d H:i:s')];
        $res = Db::name('live_algorithm')->where($map)->find();
        if(empty($res)){
            return $chat_num;
        }
        $create_time = strtotime($res['create_time']);//算法开始时间
        $minute = $res['minute']; //
        $max    = $res['nums'];//
        $s      = $minute * 60;//总秒
        $len    = ceil($s/$ii);//总次数
        $time   = time();//当前时间戳
        //检测当天是否存在调整记录
        $sham_cnt = Db::name('chatroom')->where('chat_id','=',$chat_id)->value('sham_cnt');
        if($create_time + $s < $time){
            $current = $max + $chat_num;//当前值
            return $current;
        }else if($create_time > $time){
            $current = $chat_num;//当前值
        }else {
            $i    = abs(floor(($create_time - $time) / $ii));//当前次数
            $data = [];
            if(isset($sham_cnt) && $max > $sham_cnt){
                $mac       = $max - $sham_cnt; //当前算法最大值 - 上次调整值 = 本次执行最大值
                $calc      = $this->getCnt1($minute, $mac, $i, $ii);//本次执行后的值
                $data['o'] = $calc;
                $current   = $sham_cnt + $calc;//本次计算的值
            }else{
                $current   = $this->getCnt1($minute, $max, $i, $ii);//本次计算的值
            }
            if ($chat_num > $current) {
                $current = $chat_num;
            }
            //插入数据库
            Db::name('chatroom_sham_cnt')->insert([
                'chat_id'     => $chat_id,
                'sham_cnt'    => $current,
                'create_time' => $time,
            ]);
            if($len <= $i){
                Db::name('chatroom')->where('chat_id','=', $chat_id)->update([
                    'sham_cnt'    => $current,
                ]);
            }
        }
        // $data['prevMax'] = $sham_cnt;
        // $data['max'] = $max;
        // $data['num'] = $current;
        return $current;
    }

    /**
     * 生成分享二维码
     * @param  [string] $url [二维码内容url]
     * @param  [string] $filename [图片名称地址]
     * @return [string]          [二维码地址]
     */
    public function getQrcode($url,$filename='')
    {
        if (empty($filename)) {
            // 时间戳+2位随机数
            $filename = time().'_'.mt_rand(00,99);
        }
        $filename .= '.png';
        $beauty_ser = new BeautyCodeService();
        $qrcode = $beauty_ser->makeQrCode($url , $filename);
        return $qrcode;
    }
}