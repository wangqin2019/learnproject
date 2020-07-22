<?php
namespace app\api\controller;
use app\api\service\ApiLogSer;
use app\api\service\LiveMobileService;
use app\api\service\RuleSer;
use think\Cache;
use think\Controller;
use think\Db;

//整合七牛直播sdk
use pili_test\Rtmp;
//整合七牛转码sdk
use qiniu_transcoding\Transcoding;
//整合腾讯云通信扩展
use tencent_cloud\TimChat;

//使用redis扩展
use think\cache\driver\Redis;
use think\Exception;
use think\Log;
/**
 * live: 直播
 */
class Live extends Base
{
    public $pre_html = 'http://live.qunarmei.com/html/';

	//上传图片路径
  protected $img_url = '/uploads/face/';
  //调用直播相关接口url
  protected $url = 'http://localhost/pili_test/rtmp_test.php';
  //点播回放域名
  protected $replay_domain = 'http://pili-vod.qunarmei.com/';
  //qunarmeilive-vod存储空间域名
  protected $replay_domain_vod = 'http://pili-live-vod.qunarmei.com/';

  //创建静态变量存redis实例
  public static $redis_v = null;

  //测试服务器
//  private $admin_userid = 16829;
  //正试服务器
  private $admin_userid = 18357; //admini账号对应的用户id

    // 需要关闭直播列表的门店
    protected $close_signs = ['241-126','241-127'];
    // 关闭直播列表的门店
    protected function closeLiveStore($store_id){
        $flag = 0;
        // 查询用户门店是否在关闭列表中
        $mapb['sign'] = ['in',$this->close_signs];
        $res_b = Db::table('ims_bwk_branch')->where($mapb)->select();
        if($res_b){
            $stores = [];
            foreach ($res_b as $vb) {
                $stores[] = $vb['id'];
            }
            if(in_array($store_id,$stores)){
                $flag = 1;
            }
        }
        return $flag;
    }
    
  // 指定id直播 , 指定用户观看
  public function zdLive($user_id , $res)
  {
    // 查询用户号码是否在指定能观看的号码里
//    foreach ($res as $k => $v) {
//      if ($v['live_id'] == 130 && $v['statu'] == 1) {
//        $map['id'] = $user_id;
//        $res1 = Db::table('ims_bj_shopn_member')->where($map)->limit(1)->find();
//        $mapc['live_id'] = 130;
//        $res2 = Db::table('think_live_see_conf')->where($mapc)->limit(1)->find();
//        if (!($res2 && strpos($res2['see_mobiles'],$res1['mobile']))) {
//          unset($res[$k]);
//        }
//      }
//    }
    return array_values($res);
  }  
    // 视频是否收藏
  public function videoCollect($user_id,$res)
  {
    $mapc['type'] = 1;
    $mapc['user_id'] = $user_id;
    $mapc['delete_time'] = 0;
    $res1 = Db::table('think_user_conf')->where($mapc)->select();
    $live_ids = [];
    if ($res1) {
        foreach ($res1 as $k1 => $v1) {
            $live_ids[] = $v1['content'];
        }
    }
    foreach ($res as $k => $v) {
        $res[$k]['is_collect'] = 0;
        if ($live_ids && in_array($v['live_id'],$live_ids)) {
            $res[$k]['is_collect'] = 1;
        }
    }
    return $res;
  }
    // 指定门店能观看的视频
    protected function zdStoreSee($user_id,$res)
    {
        $signs = config('text.signs');
        $zd_liveids = config('text.zd_liveid');
        $live_ids = [];
        foreach ($res as $k => $v) {
            if (in_array($v['live_id'], $zd_liveids)) {
                $map['m.id'] = $user_id;
                $map['b.sign'] = ['in',$signs];
                $resm = Db::table('ims_bj_shopn_member m')->join(['ims_bwk_branch' => 'b'],['b.id = m.storeid'],'LEFT')->where($map)->limit(1)->find();
                if(empty($resm)){
                    // unset($res[$k]);
                }
            }
        }
        return $res;
      }  
        
   /**
     * 直播列表
     * @param int $idstore 门店id
     * @param int $user_id 用户id
     * @param string $keyword 关键字
     * @param int $type 类型 3未开始 2回放 1直播 0全部
     * @return json
     */
    public function liveList()
    {
        $idstore = input('idstore') == ''?1:input('idstore');
        $user_id = input('user_id',0);
        $keyword = input('keyword');
        $type = input('type');
        $page = input('page',0);// 当前页,默认不传显示所有
        $limit = 50;// 每页显示条数
        if($page == 0){
            $page = 1;
            $limit = 1000;
        }
        $see_flag = $this->closeLiveStore($idstore);
        if($see_flag){
            return parent::returnMsg(1,[],'暂无数据');
        }

        // 记录请求日志
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $qq_msg = 'livelist请求日志:user_id='.$user_id.'&idstore='.$idstore.'&keyword='.$keyword.'&type='.$type.'-agent:'.$agent;
        Log::record($qq_msg);
        // 记录请求到数据库
        $api_path = '/api/live/livelist';
        $request_data = 'user_id='.$user_id.'&idstore='.$idstore.'&keyword='.$keyword.'&type='.$type;
        $apiarr = ApiLogSer::addLog($api_path,$request_data,$agent);


        $map['cat.flag'] = 0;
        $map1 = null;$live_names=[];$chat_ids=[];
        // 标题/内容查询
        if($keyword){
            $map['live.title|live.content'] = ['like','%'.$keyword.'%'];
        }
        // 直播/点播视频列表
        if($type == 1){
            // 直播
            $map1['live.statu'] = 1;
        }elseif($type == 2){
            // 回放
            $map1 = 'live.flag = 0 and live.statu>=0 and live.db_statu=1 and live.statu=0 and live.start_time = 0';
        }elseif($type == 3){
            // 预告未开始
            $map1 = 'live.flag = 0 and live.statu>=0 and live.end_time > '.time();
        }else{
            $map1 = ' (live.statu=1 or (live.db_statu=1 and live.statu=0 and live.start_time = 0) or live.end_time > '.time().'  ) and live.flag = 0 and live.statu>=0  ';
        }
        $map['live.flag'] = 0;
        $map['live.type'] = ['neq',2];// 过滤实操考核直播视频
        $res = Db::table('think_live live')
            ->join(['think_live_category'=>'cat'],['live.category_id=cat.category_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['live.user_id=m.mobile'],'LEFT')
            ->field('live.video_cover,live.wxc_path,live.wxc_appid,live.start_time,live.idstore,live.live_img_small,live.user_id,live.id live_id,live.user_name name,live.user_img img,live.title,live.content,live.address,live.insert_time live_time,live.statu,live.live_img,live.see_url play_url,chat_id,live.category_id,cat.category_name,video_type,live.classify_id,live.see_count_times,live.db_statu,live.db_length,live.point_count,live.live_source,live.range_stores,live.range_roles,live.see_count_times,live.see_times_flag,live.live_stream_name,m.id user_ids,live.copyroom_id,live.type')
            ->where($map)
            ->where($map1)
            ->group('live_id')
            // ->order('live_time desc,statu desc')
            ->order('(case when live.statu=1 then 100 when live.statu=0 and live.start_time>0 then 90  else 80 end) desc,live_time desc')
            ->page($page,$limit)
            ->select();
        $rule_ser = new RuleSer();
        foreach ($res as $k => &$v) {
            $v['live_img_small'] = $v['live_img_small']==''?'':$v['live_img_small'];
            $v['content'] = $v['content']==''?'':$v['content'];
            $v['address'] = $v['address']==''?'':$v['address'];
            $v['live_img'] = $v['live_img']==''?'':$v['live_img'];
            $v['live_time'] = $v['live_time']==''?'':date('Y-m-d H:i:s',$v['live_time']);
            //获取直播观看点赞人数
            $v['audience'] = 1;
            $v['point_count'] = $this->getDzs($v['live_id']);
            $v['chat_id'] = $v['chat_id']==''?'':$v['chat_id'];
            $v['room_id'] = '';
            $v['share_url'] = "http://live.qunarmei.com/index/index/login?id={$v['live_id']}";
            $v['classify_id'] = $v['classify_id']==''?'':$v['classify_id'];
            $v['classify_id'] = str_replace('"','',$v['classify_id']);
            $v['db_length'] = $v['db_length']==''?'':$v['db_length'];

            // 直播预告
            $v['pre_type'] = 0;
            $v['pre_html'] = '';
            if ($v['start_time']) {
                $v['pre_type'] = 1;// 预告,状态显示为直播未开始
                if ($v['statu'] == 0) {
                    $v['audience'] = 0;
                    $v['point_count'] = 0;
                }
                $v['pre_html'] = $this->pre_html.'zb_share/zbshare.html?live_id='.$v['live_id'].'&user_id='.$user_id;
            }
            $v['img_list'] = [];
            // 回放文案列表
            if($v['copyroom_id'] && $v['type']){
                $mapcp['delete_time'] = 0;
                $mapcp['copyroom_id'] = $v['copyroom_id'];
                $res_cp = Db::table('think_zb_copyroom_image')->where($mapcp)->order('sort asc')->select();
                if($res_cp){
                    foreach ($res_cp as $vc) {
                        $copyroom1['copyroom_image_id'] = $vc['id'];
                        $copyroom1['image'] = $vc['image'];
                        $v['img_list'][] = $copyroom1;
                    }
                }
            }

            if($v['live_stream_name']){
                $live_names[] = $v['live_stream_name'];
            }
            $chat_ids = [];
            if($v['chat_id']){
                $chat_ids[] = $v['chat_id'];
            }
            $live_ids[] = $v['live_id'];

            // 如果是直播,控制权限
            if($v['statu'] == 1 || $v['start_time']){
                // 查询直播类型
                $res_type = $rule_ser->getLiveType($v['user_id']);
                // 查询收看权限
                $res_flag = $rule_ser->getSeeAuth($res_type,$v['live_id'],$user_id);
//                print_r($v['live_id']);print_r($res_flag);
                if($res_flag == 0){
                    unset($res[$k]);
                    continue;
                }
            }
            if($v['user_id'] == 1){
                $v['user_id'] = 18357;
            }else{
                $v['user_id'] = $v['user_ids']==null?0:$v['user_ids'];
            }
            $v['user_ids'] = $v['user_ids']==null?$v['user_id']:$v['user_ids'];
            // 观看点赞数
            if($chat_ids && $v['statu']==1){
                $res_gk = $this->getGkSum($chat_ids[0],$v['see_count_times']);
                $res[$k]['audience'] = $res_gk;
            }
        }


        // 查询直播间连麦房间
        if($live_names){
            $mapr['live_name'] = ['in',$live_names];
            $res_room = Db::name('room r')->field('r.id,live_name')->where($mapr)->select();
            if($res_room){
                foreach ($res as $k1=>$v1) {
                    foreach ($res_room as $vr) {
                        if($vr['live_name'] == $v1['live_stream_name']){
                            $res[$k1]['room_id'] = $vr['id'];
                        }
                    }
                }
            }
        }

        // 判断用户是否能观看,过滤用户无权限观看的视频
        $res = $this->isSeeLiveFlag($user_id,$res,$idstore);
        $live_arr = [];
        if($res){
            foreach ($res as $k1=>$v1) {
                unset($res[$k1]['range_stores']);
                unset($res[$k1]['range_roles']);
                unset($res[$k1]['see_times_flag']);
                unset($res[$k1]['live_stream_name']);
                unset($res[$k1]['user_ids']);
                unset($res[$k1]['idstore']);
            }
        }

        $res = $this->zdStoreSee($user_id,$res);
        // 是否收藏
        $res = $this->videoCollect($user_id,$res);
        $res = $this->zdLive($user_id,$res);
        $res = array_values($res);

        // 记录下发日志
        $xf_msg = 'livelist下发数据库日志:'.json_encode($res,JSON_UNESCAPED_UNICODE);
        Log::record($xf_msg);

        if($apiarr['code'] == 1){
            $resp_msg['code'] = 1;
            $resp_msg['msg'] = '获取成功';
            $resp_msg['data'] = $res;
            $resp_msg2 = json_encode($resp_msg,JSON_UNESCAPED_UNICODE);
            ApiLogSer::updLog($apiarr['data'],$resp_msg2);
        }

        return parent::returnMsg(1,$res,'获取成功');
    }
    
    /**
     * 直播-观看授权
     * @param  array $user [观看用户信息,mobile:用户号码,sign:用户门店编号,store_id:用户门店id]
     * @param  array $res [视频列表信息]
     * @return [type]        [description]
     */
    protected function seeLiveConf($user,$res)
    {
        $map['l.statu'] = 1;
        // $map['l.live_source'] = 2;
        // 查询授权名单
        $res1 = Db::table('think_live l')->join(['think_live_see_conf'=>'c'],['c.mobile=l.user_id'],'LEFT')->field('l.idstore,l.id live_id,c.store_signs,c.see_mobiles,c.roles')->where($map)->select();
        if ($res1) {
            $live_ids = [];
            // 查询用户能观看的手机直播
            foreach ($res1 as $k => $v) {
                // 查询用户是否在授权门店中
                $see_flag = 0;
                if ($v['store_signs']) {
                    $ressign1 = explode(',', $v['store_signs']);
                    if (in_array($user['sign'], $ressign1)) {
                        // 查询用户是否在指定角色中
                        if (strpos($v['roles'],$user['role_id'])){
                            $see_flag = 1;
                        }
                    }
                }
                // 查询是否在指定号码中
                if ($see_flag == 0) {
                    $resmobile1 = explode(',', $v['see_mobiles']);
                    if (in_array($user['mobile'], $resmobile1)){
                        $see_flag = 2;
                    }
                }
                // 查询是否和主播一个门店
                if ($see_flag == 0) {
                    if ($v['idstore'] == $user['store_id']) {
                        $see_flag = 3;
                    }
                }
                // 用户能看到的直播
                if ($see_flag > 0) {
                    // 记录授权用户所在
                    $sq_msg = '权限控制:see_flag:'.$see_flag.'-live_id:'.$v['live_id'];
                    Log::record($sq_msg);
                    $live_ids[] = $v['live_id'];
                }
            }
            // 删除不能观看的直播
//            foreach ($res as $k1 => $v1) {
//                if ($v1['statu'] == 1) {
//                    if (empty($live_ids) || !in_array($v1['live_id'],$live_ids)) {
//                        unset($res[$k1]);
//                    }
//                }
//            }
            $res = array_values($res);
            
        }
        return $res;
    }
    /**
     * 用户是否有查看视频权限过滤
     * @param int $user_id => 用户id
     * @param array $res => 直播列表
     * @param int $idstore => 门店id
     * @return array
     */
    private function isSeeLiveFlag($user_id,$res,$idstore=0)
    {
        $rest = [];
        // 查询用户角色
        $map['m.id'] = $user_id;
        $resusers = Db::table('ims_bj_shopn_member m')
            ->join(['ims_bwk_branch'=>'b'],['m.storeid=b.id'],'LEFT')
            ->field('b.sign,m.code,m.isadmin,m.id,m.staffid,m.storeid,m.mobile')
            ->where($map)
            ->limit(1)
            ->find();
        if($resusers){
            if($resusers['isadmin']){
                //店老板
                $arr['role_id'] = 1;
            }elseif(strlen($resusers['code'])>1){
                //美容师
                $arr['role_id'] = 2;
            }else{
                // 顾客
                $arr['role_id'] = 3;
            }
            if($resusers['sign'] == '000-000'){
                // 办事处
                $arr['role_id'] = -1;
            }
            // 直播观看授权
            $user['mobile'] = $resusers['mobile'];
            $user['sign'] = $resusers['sign'];
            $user['store_id'] = $resusers['storeid'];
            $user['role_id'] = (string)$arr['role_id'];
            $res = $this->seeLiveConf($user,$res);
        // echo '<pre>res0：';print_r($res);
            //手机端直播->只容许 店老板 + 美容师 观看
            // foreach($res as $k=>$v){
            //     if($v['live_source'] == 2 && $v['statu'] == 1){
            //         if($arr['role_id'] == 3){
            //             // unset($res[$k]);
            //         }
            //     }
            // }
        // echo '<pre>res1：';print_r($res);die;  
            // 白名单过滤
            // $mapb['mobile'] = $resusers['mobile'];
            // $mapb['type'] = 1;
            // $res_blacklist = Db::table('think_appoint_list')->where($mapb)->field('id')->find();

            // if(empty($res_blacklist)){
                // 视频过滤
                foreach ($res as $k1=>$v1) {
                    if(!isset($v1['range_stores'])){
                        continue;
                    }
                    if(!isset($v1['range_roles'])){
                        continue;
                    }
                    // 门店过滤
                    if(!(($v1['range_stores']=='["0"]') || strstr($v1['range_stores'],(string)$resusers['storeid']))){
//                        echo '11';
                        unset($res[$k1]);
                    }
                    // 角色过滤
                    if(!(($v1['range_roles']=='["0"]') || strstr($v1['range_roles'],(string)$arr['role_id']))){
//                        echo '22';
                        unset($res[$k1]);
                    }
                }
            // }
            $rest = array_values($res) ;
        }else{
          // 用户id为0,查询门店是否在
          $live_ids1 = [];
          // echo "<br/>idstore1:";print_r($idstore);
          // echo "<br/>user_id1:";print_r($user_id);
          if ($idstore && $user_id == 0) {
             $maplive['l.statu'] = 1;
             $reslive = Db::table('think_live l')->join(['think_live_see_conf'=>'c'],['l.user_id=c.mobile'],'LEFT')->field('l.id,c.store_signs')->where($maplive)->select();
             // echo "<br/>reslive1:<pre>";print_r($reslive);
             if ($reslive) {
                // 查询用户门店编号
                $mapstoreid['id'] = $idstore;
                $resstoreid = Db::table('ims_bwk_branch')->where($mapstoreid)->limit(1)->find();
                foreach ($reslive as $kl => $vl) {
                  if ($vl['store_signs']) {
                    $signs = explode(',', $vl['store_signs']);
                    // echo "signs:<pre>";print_r($signs);
                    // echo "<br/>store_signs:<pre>";print_r($vl['store_signs']);print_r($resstoreid['sign']);die;
                    if (in_array($resstoreid['sign'],$signs)) {
                      $live_ids1[] = $vl['id'];
                    }
                  }
                }
             }
          }
          // echo "<br/>live_ids1:<pre>";print_r($live_ids1);die;
          foreach ($res as $k => $v) {
            if (empty($live_ids1) && $v['statu'] == 1) {
              unset($res[$k]);
            }elseif ($v['statu'] == 1 && !in_array($v['live_id'], $live_ids1)) {
              unset($res[$k]);
            }
          }
        }
        $rest = array_values($res) ;
        // echo '<pre>res2：';print_r($res);die;
        return $rest;
    }
    /**
     * 获取观看总人数,翻过倍 / 线性倍数
     * @param string $chat_ids [聊天室id]
     * @param int $see_count_time [倍数]
     * @return int
     */
    public function getGkSum($chat_id,$see_count_time)
    {
        $live_mobile_ser = new LiveMobileService();
        // $res = $live_mobile_ser->liveNumbers($chat_id);
        $res = $live_mobile_ser->appNumbers($chat_id);
        $chat_num1 = 1;
        if($res['code'] == 1){
            // 线性倍数
            $chat_num1 = $res['data']['num'];
        }
        return $chat_num1;
    }
    /**
     * 内部Api获取观看人数
     * @param array $chat_ids => [聊天室列表]
     * @return array
     */
    public function getGks($chat_ids)
    {
        $rest = [];
        $key = 'chat_id_'.$chat_ids[0];
        $res = Cache::get($key);
        if($res){
            $res1 = json_decode($res,true);
            return $res1;
        }
        if($chat_ids){
            $tent = new TimChat();
            $rest = $tent->getChatCntList($chat_ids);
            $res = json_encode($rest);
            Cache::set($key,$res,300);
        }
        return $rest;
    }
    /**
     * 内部Api获取点赞人数
     * @param int $live_id => [直播id]
     * @return array
     */
    public function getDzs($live_id)
    {
        $redisSer = $this->creRedis();
        $rest = $redisSer->get($live_id)==false?0:$redisSer->get($live_id);
        return $rest;
    }

   //调用观看点赞数据
   public function pointPraise($liveid='',$type='')
   {
     $live_id = input('live_id');

//       点赞人数存取到redis里
       $Redis = $this->creRedis();
        $vr1 = rand(1,10);
     $num = input('num')==''?0:input('num');
     //观看人数
     $op = input('op');
     if(!$num && $live_id)
     {
         $gk = @$Redis->get($live_id.'_see');
         if(!$gk)
         {
             // start Modify by wangqin 2017-11-22 增加倍率开关控制 1=>开启,2=>关闭
             $see_times_flag = @$Redis->get($live_id.'_times_flag');
             $chat_id = @$Redis->get($live_id.'_chatid');
             if(!$see_times_flag)
             {
               $see_t = Db::name('live')->field('see_times_flag,chat_id,see_count_times')->where('id='.$live_id)->limit(1)->select();
               $flag = $see_t[0]['see_times_flag'];
               if($flag)
               {
                 $see_times_flag = 1;
                 @$Redis->set($live_id.'_times_flag',1);
                  //聊天室人数x倍率
                  $gk_cnt = ($this->getChatsCount($see_t[0]['chat_id']))*$see_t[0]['see_count_times'];

               }else
               {
                 $see_times_flag = 2;
                 @$Redis->set($live_id.'_times_flag',2);
                 $gk_cnt = $this->getChatsCount($see_t[0]['chat_id']);
               }
               @$Redis->set($live_id.'_chatid',$see_t[0]['chat_id']);
             }else
             {
               //开启倍率
               if($see_times_flag == 1)
               {
                 //直播间观看人数,根据live_id找chat_id和观看倍率
                 $see_b = Db::name('live')->field('chat_id,see_count_times')->where('id='.$live_id)->limit(1)->select();
                 if($see_b)
                 {
                  //聊天室人数x倍率
                  $gk_cnt = ($this->getChatsCount($see_b[0]['chat_id']))*$see_b[0]['see_count_times'];
                 }
               }else
               {
                 //关掉倍率
                 $gk_cnt = $this->getChatsCount($chat_id);
               }
             }
             // start Modify by wangqin 2017-11-22

             //直播间观看人数,根据live_id找chat_id和观看倍率
             // $see_b = Db::name('live')->field('chat_id,see_count_times')->where('id='.$live_id)->limit(1)->select();
             // if($see_b)
             // {
             //  //聊天室人数x倍率
             //  $gk_cnt = ($this->getChatsCount($see_b[0]['chat_id']))*$see_b[0]['see_count_times'];
             // }
             //设置过期时间
             @$Redis->set($live_id.'_see',$gk_cnt,30);
             $gk = @$Redis->get($live_id.'_see');
         }
     }
     if($live_id)
     {
        $vr1 =  @$Redis->get($live_id);
     }

     //内部接口获取点赞人数
     if($type == 'interface' && $liveid)
     {
         $vr1 =  @$Redis->get($liveid);
         return  $vr1;
     }

     //内部接口获取观看人数 x 倍率
     if($type == 'gk' && $liveid)
     {
         $vr1 =  @$Redis->get($liveid.'_see');
         if(!$vr1)
         {
           //直播间观看人数,根据live_id找chat_id和观看倍率
             $see_b = Db::name('live')->field('chat_id,see_count_times')->where('id='.$liveid)->limit(1)->select();
             if($see_b)
             {
              //聊天室人数x倍率
              $gk_cnt = ($this->getChatsCount($see_b[0]['chat_id']))*$see_b[0]['see_count_times'];
             }
             //设置过期时间
             @$Redis->set($liveid.'_see',$gk_cnt,30);
             $vr1 =  $gk_cnt;
             // echo "gk_cnt:".$gk_cnt;
         }
         return  $vr1;
     }

     if(!$vr1)
     {
         @$Redis->set($live_id,$num);
     }else
     {
         @$Redis->set($live_id,($vr1+$num));
     }
     $vr1 = @$Redis->get($live_id);
     if(!$num)
     {
        $vr2 = @$Redis->get($live_id.'_see');
        $data = array('point_count'=>$vr1,'audience'=>$vr2);
     }else
     {
        $data = array('point_count'=>$vr1);
     }

       // 查询直播间
       $res_live = \app\api\model\Live::get($live_id);
       if($res_live){
           $gk_num = $this->getGkSum($res_live['chat_id'],$res_live['see_count_times']);
           $data['audience'] = $gk_num;
       }
     $ret = parent::returnMsg(1,$data,'获取成功');
     //end Modify by wangqin 2017-11-04
    return $ret;
   }

   //直播分享url
   public function share($id)
   {
     $id = input('id');
     if($id)
     {
      $rest = Db::name('live')->field('hls_url url')->where("id=$id")->select();
      if($rest[0]['url'])
      {
        $ret = parent::returnMsg(1,array('url'=>$rest[0]['url']),'获取成功');
      }else
      {
        $ret = parent::returnMsg(0,'','参数错误');
      }
     }else
     {
      $ret = parent::returnMsg(0,'','参数错误');
     }
     return $ret;

   }

   //互动消息
   public function interaction($user_id='',$msg='',$live_id='',$chat_id='')
   {
//     $user_id = input('user_id');
//     $msg = input('msg');
//     $live_id = input('live_id');
//     $chat_id = input('chat_id');
//     if($user_id && $msg)
//     {
//      $data = array('user_id'=>$user_id,'msg'=>$msg,'log_time'=>date('Y-m-d H:i:s',time()),'live_id'=>$live_id,'chat_id'=>$chat_id);
//      $rest = Db::name('interaction')->insert($data);
//      $ret = parent::returnMsg(1,'','添加成功');
//     }else
//     {
//      $ret = parent::returnMsg(0,'','参数错误');
//     }
       $ret = parent::returnMsg(1,'','添加成功');
     return $ret;
   }

   public function index()
   {
   	// $id = input('id');
    // echo($id);
    // return 'CloudSeeding';
    $url = 'localhost/Test/tt.php';
    $data = array('aa'=>'bb');
    $rest = curl_post($url,$data);
    // $rest = parent::returnMsg('200',$rest);
    echo $rest;
    // return $rest;
   }

   //开始直播和断开直播回调通知
   public function liveStatuCall($type='start')
   {
       set_time_limit(0);
     //开始直播
      $type = input('type');
      //记录日志
//      trace($log = input(), $level = 'log');
      //清除直播列表接口redis
      //start Modify by wangqin 2017-11-27
      // $this->delRedis('liveList');
      // $this->delRedis('liveList2');
      $this->clearRedis('livelist19');
      // start Modify by wangqin 2017-11-27
      if($type == 'start') {
          $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
          $qq_msg = '七牛直播回调请求日志liveStatuCall:'.date('Y-m-d H:i:s').' agent:'.$agent.'-请求参数:'.http_build_query(input());
          Log::record($qq_msg);
        //记录回调通知
        $back_log = Db::name('back_log')->insert(array('query_str'=>http_build_query(input()),'log_time'=>date('Y-m-d H:i:s'),'interface'=>'liveStatuCall'));
        //回调记录开始直播的流
        $back_record = Db::name('callback_record')->insert(array('stream_name'=>input('title'),'start_time'=>date('Y-m-d H:i:s'),'log_time'=>date('Y-m-d H:i:s')));
        //流名为title的开始直播
        if(input('title'))
        {
          $mapch['live_stream_name'] = input('title');
          $reschat = Db::table('think_live')->where($mapch)->limit(1)->find();
          //开始直播 修改状态
          $data = array('statu'=>1,'db_statu'=>0);
          //绑定聊天室
          // $rest1 = $this->chatChange($type='bd');
          // $data['chat_id'] = $rest1;
          //直播开始更换直播封面
          //通过七牛接口获取推流地址
          if ($reschat) {
            $rtmp = new Rtmp(input('title'));
              if (empty($reschat['live_img'])) {
                $cover_url = $rtmp->getScreenShot();
                  $data['live_img'] = $cover_url;
              }
          }
          // $rtmp = new Rtmp(input('title'));
          // $cover_url = $rtmp->getScreenShot();
          // $data['live_img'] = $cover_url;
          // 重新推流,修改rtmp播放地址
          $data['see_url'] = 'rtmp://pili-live-rtmp.qunarmei.com/qunarmeilive/'.input('title');
          $data['hls_url'] = 'http://pili-live-hls.qunarmei.com/qunarmeilive/'.input('title').'.m3u8';
          // // $data = array('type'=>'cover','stream_name'=>input('title'));
          // $data['insert_time'] = time();
          // start Modify by wangqin 2017-12-25 保存直播封面图
          // $live_jpg = $rtmp->getSnapshot();
          // $data['live_img_keep'] = ($this->replay_domain).$live_jpg;
          // end Modify by wangqin 2017-12-25
          $data['begin_time'] = time();
          $rest = Db::name('live')->where("live_stream_name='".input('title')."'")->update($data);
          //通知客户端
          // start Modify by wangqin 2018-01-09 手机端直播开始时短信通知
          //是否是手机端直播
          // $is_mobile_live = Db::name('live,think_live_mobile_notice')->field('live_source,mobiles,user_id')->where('live_stream_name',input('title'))->where('live_source',2)->limit(1)->select();
          // if($is_mobile_live)
          // {
              //下发短信通知
              // $send_mobiles = $is_mobile_live[0]['mobiles'];
              // $send_res = $this->sendMsg($send_mobiles,$is_mobile_live[0]['user_id']);
          // }
          // end Modify by wangqin 2018-01-09
          
            
            if($reschat){
                // 直播间发送系统消息通知
                $tent = new \tencent_cloud\TimChat();
                $sys_msg = '您的直播间已创建成功，粉丝正在赶来的路上，请耐心等待！';
                // 是否续播
                if($reschat['update_time']){
                    $sys_msg = '主播回来啦，感谢您的等待，很高兴再次遇到您！';
                }
                $ressys = $tent->sendMsgs($reschat['chat_id'],$sys_msg,'sj',1);
            }
          // 直播开启发送钉钉消息通知
          $dingSer = new DingDing();
          $res_mobiles = $dingSer->getNoticeConf();
          if ($res_mobiles) {
              // 查询主播用户信息
              $mapl['live_stream_name'] = input('title');
              $res_zb_user = Db::table('think_live l')->where($mapl)->limit(1)->find();
              if ($res_zb_user) {
                  $url = config('url.live_see_url').'?id='.$res_zb_user['id'];
                  $content = $res_zb_user['address'].'-'.$res_zb_user['user_name'].'开启了直播 '.$url;
                  foreach ($res_mobiles as $k => $v) {
                    $arr1['mobile'] = $v;
                    $arr1['title'] = $v;
                    $arr1['content'] = $content;
                    $dingSer->sendMsg($arr1);
                  }
              }
          }
          $ret = parent::returnMsg(1,'','回调成功');
        }else
        {
          $ret = parent::returnMsg(0,'','参数错误');
        }

      }else {
          // 回调直播结速,更改直播状态
          $data_req = input();
          $map['live_stream_name'] = $data_req['title'];
          $data['statu'] = 2;
          $fname = $data_req['title'].rand(10,99);
          $data['see_url'] = 'http://pili-vod.qunarmei.com/'.$fname.'.mp4';
          $data['hls_url'] = $data['see_url'];
          $data['replay_url'] = $data['see_url'];

          //保存直播视频,查询开始时间
          $start_time = date('Y-m-d H:i:s');
          //回调记录结束直播的流
          $back_record = Db::name('callback_record')->where(array('stream_name'=>input('title')))->update(array('replay_url'=>$data['replay_url'],'end_time'=>date('Y-m-d H:i:s')));
          $back1 = Db::name('callback_record')->field('start_time')->where("stream_name='".$data_req['title']."'")->order('id asc')->limit(1)->find();
          if($back1) {
              $start_time = $back1['start_time'];
          }
          $rtmp = new Rtmp($data_req['title']);
          // 查询直播开始
          $reslive = Db::table('think_live')->where($map)->limit(1)->find();
          if ($reslive) {
              $start_time = $reslive['insert_time'];
          }else{
              $start_time = strtotime($start_time);
          }
          // 保存封面图
          $live_jpg = $rtmp->getSnapshot();
          $data['live_img_keep'] = $this->replay_domain.$live_jpg;
          if(strstr($reslive['live_img'],'snapshot')){
              $data['live_img'] = $data['live_img_keep'];
          }
//          $data['live_img_keep'] = $data['live_img'];
          $data['update_time'] = date('Y-m-d H:i:s');
          // $data['end_time'] = time();
          $res111 = Db::table('think_live')->where($map)->update($data);

          $resp = $rtmp->saveReplay($fname,$start_time,time());
          // $resp = $rtmp->saveReplay($fname,strtotime($start_time),time());
          // 转码保存直播视频
          
          //转码保存到qunarmeilive-vod空间里
          $msg = '回调成功';
          try{
              $res1 = $this->videoTranscoding(input('title'));
          }catch(Exception $e){
              $msg = '转码失败-'.$e->getMessage();
          }
          $ret = parent::returnMsg(1,'',$msg);

          //记录回调通知
//        $back_log = Db::name('back_log')->insert(array('query_str'=>http_build_query(input()),'log_time'=>date('Y-m-d H:i:s'),'interface'=>'liveStatuCall'));
//         //断开直播
//        if(input('title'))
//        {
//          $data = array('statu'=>2);
//          //解绑聊天室
//          // $resp = Db::name('live')->where("live_stream_name='".input('title')."'")->select();
//          // $rest1 = $this->chatChange('jb',$resp[0]['id']);
//          //查询开始直播时间
//          $back1 = Db::name('callback_record')->field('start_time')->where("stream_name='".input('title')."'")->order('id desc')->limit(1)->select();
//          if($back1)
//          {
//              $start_time = $back1[0]['start_time'];
//          }
//          //保存直播视频
//          $rtmp = new Rtmp(input('title'));
//          $resp = $rtmp->saveReplay(input('title'),strtotime($start_time),time());
//          // 转码保存直播视频
//          // $data['replay_trans_url'] = $this->videoTranscoding(input('title'));
//
//          $data['replay_url'] = $this->replay_domain.$resp['fname'];
//          $data['see_url'] = $data['replay_url'];
//          $data['db_statu'] = 1;
//          // start Modify by wangqin 2017-12-25 保存直播封面图
//          #更新 封面图截图到 live_img
//          $live_jpg = $rtmp->getSnapshot();
//          $data['live_img_keep'] = ($this->replay_domain).$live_jpg;
//          $data['live_img'] = $data['live_img_keep'];
//          #转存点播去掉
//          $data['classify_id'] = '';
//          // end Modify by wangqin 2017-12-25
//          $rest = Db::name('live')->where("live_stream_name='".input('title')."'")->update($data);
//          //通知客户端
//          //回调记录结束直播的流
//          $back_record = Db::name('callback_record')->where(array('stream_name'=>input('title')))->update(array('replay_url'=>$data['replay_url'],'end_time'=>date('Y-m-d H:i:s')));
//          //转码保存到qunarmeilive-vod空间里
//          $res1 = $this->videoTranscoding(input('title'));
//          if($res1)
//          {
//            $res2 = Db::name('live')->where("live_stream_name='".input('title')."'")->update(array('replay_url'=>$res1));
//          }
          //
//          $ret = parent::returnMsg(1,'','回调成功');
        }
      return $ret;
   }

   //绑定解绑聊天室
    public function chatChange($type='bd',$id='')
    {
        if($type=='bd')
        {
            //绑定聊天室id与直播间id
            $chat_data = Db::name('chatroom')->field('chat_id')->where('flag=0')->limit(1)->select();
            if($chat_data)
            {
                $chat_id = $chat_data[0]['chat_id'];
                //修改是否绑定标记
                $rest1 = Db::name('chatroom')->where("chat_id=$chat_id")->update(array('flag'=>1,'log_time'=>date('Y-m-d H:i:s',time())));
                return $chat_id;
            }
        }else
        {
            //解绑 $id 直播间id
            $chat_id = Db::table('think_chatroom chat,think_live live')->where('chat.chat_id=live.chat_id and live.id='.$id)->update(array('chat.flag'=>0,'log_time'=>date('Y-m-d H:i:s',time())));
            $live_id = Db::name('live')->where('id='.$id)->update(array('chat_id'=>''));
            return true;
        }

    }

    //直播预告
    public function liveTrailer()
    {
        $res = @$this->getRedis('liveTrailer');
        if(!$res)
        {
            $pic_url = 'http://live.qunarmei.com/uploads/face/';
            $rest = Db::name('live_trailer tra')->field('tra.id,tra.user,tra.user_img,tra.address,tra.cover_img,tra.cover_img_desc,tra.title,tra.begin_time')->order('begin_time asc')->where("begin_time>'".date("Y-m-d H:i:s",time())."'")->select();
            if($rest)
            {
                foreach ($rest as &$item) {
                    $item['user_img'] = $item['user_img']==''?'':$item['user_img'];
                    $item['cover_img'] = $item['user_img']==''?'':$item['cover_img'];
                    $item['cover_img_desc'] = $item['cover_img_desc']==''?'':$item['cover_img_desc'];
                }
            }
            //检验数据,返回拼装json
            $res = $rest;
            $this->setRedis('liveTrailer',$res);
        }
        return parent::returnMsg(1,$res,'获取成功');
    }

    //获取聊天室人数
    public function getChatsCount($id)
    {
        $map = array();
        //调用admin模块下的RingeLetterL环信接口控制器
//        $ring_letter = new RingLetter();
//        $res = $ring_letter->getChatrooms();
//        if($id)
//        {
//            $map['chat_id'] = $id;
//            $resp = Db::name('chatroom')->field('chat_id,chat_cnt')->where($map)->select();
//            $cnt =  $resp[0]['chat_cnt'];
//            return $cnt;
//        }

        //获取腾讯云聊天室人数
        $tent = new TimChat();
        $res = $tent->getChatCnt($id);
        if($res)
        {
            $data = array('chat_cnt'=>$res,'log_time'=>date('Y-m-d H:i:s'));
            $ret = Db::name('chatroom')->where("chat_id='$id'")->update($data);
        }else
        {
            $res = 1;
        }
        return $res;
    }

    //七牛直播视频录制转码保存
    public function videoTranscoding($live_stream_name='')
    {
        //测试转码扩展
        $transc = new Transcoding();
        $ret = $transc->tranScode($live_stream_name);
        return $this->replay_domain_vod.$ret;
    }

    //七牛视频转码完成后回调通知
    public function videoTranscodingBack($type='')
    {
      //记录回调通知到日志里
      $data = array('query_str'=>http_build_query(input()),'interface'=>'videoTranscodingBack','log_time'=>date('Y-m-d H:i:s'));
      $rest = DB::name('back_log')->insert($data);
      // 回调成功,修改点播地址
      // start Modify by wangqin 2018-01-25 转码完成后上架显示
      $inputKey = input('inputKey');
      if($inputKey)
      {
        $inputKey = explode('.',$inputKey);
        $live_stream_name = $inputKey[0];
        //根据流名查找对应直播,手机端直播转码完成后,db_statu改为2
        $sql_m = Db::name('live')->field('id,db_statu,live_source')->where('live_stream_name',$live_stream_name)->select();
        if($sql_m)
        {
          if($sql_m[0]['live_source'] == 2)
          {
            $data1 = array('db_statu'=>2);
            Db::name('live')->where('id',$sql_m[0]['id'])->update($data1);
          }
        }
      }
      // end Modify by wangqin 2018-01-25

    }

//start Modify by wangqin 2017-10-31
    public function getSig()
    {
        //获取user_sig
        $type = input('type');
        $mobile = input('mobile');
        if(!$mobile)
        {
            parent::returnMsg(1,'','参数错误');
        }
        $tent = new TimChat();
        if(empty($res))
        {
            // echo '<pre>666';var_dump($res);die;
            if($type == 0)
            {
                //注册
                $res = $this->tentRegister($mobile);
            }

            $res1 = $tent->getUserSig($mobile);
            $res2 = array('usersig'=>$res1);
            // start Modify by wangqin 2017-12-02 usersig设置过期期限20天
            $this->setRedis('getSig_'.$mobile,$res2,20*3600*24);
            // end Modify by wangqin 2017-12-02
            $res =  $res2;
        }
        // 查询用户资料
        $arr1['mobile'] = rtrim($mobile,'B');
        $map['m.mobile'] = $arr1['mobile'];
        $res_u = Db::table('ims_bj_shopn_member m')->join(['ims_fans'=>'f'],['m.id=f.id_member'],'LEFT')->field('m.realname,m.mobile,f.avatar')->where($map)->limit(1)->find();
        if($res_u){
            $arr1['user_name'] = $res_u['realname']==null?'手机用户'.substr($arr1['mobile'],-3):$res_u['realname'];
            $arr1['head_img'] = $res_u['avatar']==null?config('img.head_img'):$res_u['avatar'];
            // 设置用户腾讯IM资料
            $tent->setUserImInfo($mobile,$arr1['user_name'],$arr1['head_img']);
        }

//        echo $res1;
        return parent::returnMsg(1,$res,'回调成功');
    }

    //注册腾讯云账号
    public function tentRegister($mobile='')
    {
        $tent = new TimChat();
        // start Modify by wangqin 2017-11-02
        //获取用户昵称
        // $name = Db::table('ims_bj_shopn_member')->field('realname name')->where("mobile='$mobile'")->limit(1)->find();
        // if($name && !empty($name['name']))
        // {
        //    $nick_name = $name['name'];
        // }else
        // {
        //     $nick_name = $mobile;
        // }
        // //注册号码和昵称
        // $res1 = $tent->tentRegister($mobile,$nick_name);
        // //end Modify by wangqin 2017-11-02
        // return 1;

        $name = Db::table('ims_bj_shopn_member m')->join(['think_tent_cloud'=>'tc'],['m.mobile=tc.tent_cloud'],'LEFT')->field('m.realname name,tc.id,tc.user_sig')->where("mobile='$mobile'")->limit(1)->find();
        $flag = 0;
        if(!empty($name)){
            $nick_name = $name['name']==''?$mobile:'';
            if(empty($name['id'])){
                //注册号码和昵称
                $res1 = $tent->tentRegister($mobile,$nick_name);
                $flag = 1;
            }
        }
    }
//end Modify by wangqin 2017-10-31

    //通过redis获取数据
    public function getRedis($paras)
    {
        // $Redis = new Redis();
        $redis_v = $this->creRedis();
        $res = $redis_v->get($paras);
        return $res;
    }

    //设置redis数据
    public function setRedis($paras,$val,$expire = null)
    {
        // $Redis = new Redis();
        $redis_v = $this->creRedis();
        $v = json_encode($val);
        $res = $redis_v->set($paras,$v,$expire);
        return 1;
    }

    //清除redis数据
    public function delRedis($paras='')
    {
        if(input($paras))
        {
            $paras = input($paras);
        }
        // $Redis = new Redis();
        $redis_v = $this->creRedis();
        if($paras)
        {
            //删除指定缓存
            $redis_v->rm($paras);
            $res = $paras;
        }else
        {
            //清空所有redis
            $redis_v->clear();
            $res = 'all';
        }
        return parent::returnMsg(1,'','清除'.$res.'的redis成功');
    }

    // start Modify by wangqin 2017-11-17
    //实例化一次redis
    public function creRedis()
    {
        if(!self::$redis_v)
        {
            self::$redis_v = new Redis();
        }
        return self::$redis_v;
    }
    // end Modify by wangqin 2017-11-17
    //
    //start Modify by wangqin 2017-11-27 根据门店id判断是否是办事处
    /*
     * 功能: 判断是否是办事处
       请求: $id 门店id
       返回: $storeid 门店id
     * */
    public function isOffice($id)
    {
        $storeid = $id;
        if($id)
        {
            $res = Db::table('ims_bwk_branch')->field('sign')->where('id='.$id)->limit(1)->select();
            //办事处
            if($res)
            {
                if($res[0]['sign'] == '000-000')
                {
                    $storeid = 1;
                }
            }
        }
        return $storeid;
    }

    //清除keys开头的所有redis数据
    /*
     * 功能: 清除keys开头的所有redis数据
     * 请求: $paras keys前缀
     * 返回:
     * */
    public function clearRedis($paras='')
    {

        $redis = $this->creRedis();

        $paras1 = input('paras');
        if($paras1)
        {
          $paras = $paras1;
        }

        if($paras)
        {
            //删除指定缓存
            $paras = $paras.'*';
            //获取指定前缀keys
            $keys = $redis->getKeys($paras);
            //删除redis
            $redis->delKeys($keys);
        }
        return parent::returnMsg(1,'','清除'.$paras.'前缀的redis成功');
    }
    //end Modify by wangqin 2017-11-27

    //start Modify by wangqin 2017-12-25 修改用户在腾讯聊天室昵称
    /*
     * 功能: 修改用户在腾讯聊天室昵称
     * 请求:
     * 返回:
     * */
    public function set_nick_name($mobile='',$nickname='')
    {
        //初始化数据
        $code=0;$msg='修改失败';$res=null;
        //获取请求数据
        $mobile = input('mobile');
        $nickname = input('nickname');
        if($mobile && $nickname)
        {
            $tent = new TimChat();
            $res = $tent->setChatName($mobile,$nickname);
            $res = json_decode($res);
            $code=1;$msg='修改昵称成功';
        }
        return parent::returnMsg($code,$res,$msg);
    }
    //end Modify by wangqin 2017-12-25
    // start Modify by wangqin 2018-01-09
    /*
     * 功能: 发送短信通知
     * 请求: $mobiles =>下发短信用户,多个,号隔开;$live_mobile=>开启直播用户
     * 返回:
     * */
    public function sendMsg($mobiles,$live_mobile)
    {
        $mobile = explode(',',$mobiles);
        foreach($mobile as $v)
        {
            //哪个门店下的谁开启了直播
            $res = Db::table('ims_bwk_branch ibb,ims_bj_shopn_member mem')->field('ibb.title,ibb.sign,ibb.location_p,mem.realname')->where("ibb.id=mem.storeid and mem.mobile='$live_mobile'")->limit(1)->select();
            if($res)
            {
                #*address*门店编号为的*sign*的*name*用手机*mobile*开了1个直播
                $str = 'code={"title":"'.$res[0]['title'].'","address":"'.$res[0]['location_p'].'","sign":"'.$res[0]['sign'].'","name":"'.$res[0]['realname'].'","mobile":"'.$live_mobile.'"}&mobile='.$v.'&name=qunarmeiApp&pwd=qunarmeiApp&template=39&type=1';
                $key = md5($str);
                //本地
                // $url1 = 'http://sms.qunarmei.com/sms.php?'.$str.'&key='.$key;
                //服务器
                $url1 = 'http://sms.qunarmei.com/sms.php?'.$str.'&key='.$key;
                // $url1 = 'http://sms.qunarmei.com/sms.php?'.$str.'&key='.$key;
                // start Modify by wangqin 2018-03-02  file_get_contents超时设置
                $opts = array('http'=>array('method'=>"GET", 'timeout'=>60));
                $rest = file_get_contents($url1,false,stream_context_create($opts));
                // end Modify by wangqin 2018-03-02
            }
        }
        return $rest;
    }
    // end Modify by wangqin 2018-01-09
    /*
     * 功能: 判断是否能观看直播
     * 请求: $user_id =>用户id;$store_id=>门店id
     * 返回:
     * */
    private function isSeeLive($user_id,$store_id)
    {
        $flag = 0;
        $arr['role_id']=0;$arr['sign']=0;
        // 查询正在直播的视频
        $map['statu'] = 1;
        $reslive = Db::table('think_live')->field('id,range_stores,range_roles')->where($map)->limit(1)->find();
        if($reslive){
            $arr['range_stores'] = json_decode($reslive['range_stores'],true);
            $arr['range_roles'] = json_decode($reslive['range_roles'],true);

            // 查询用户所在门店及编号
            $resusers = $this->userInfo($user_id);
            if(!empty($resusers)){
                if($resusers['isadmin']){
                    //店老板
                    $arr['role_id'] = 1;
                }elseif(strlen($resusers['code'])>0 && ($resusers['staffid'] == $resusers['id'])){
                    //美容师
                    $arr['role_id'] = 2;
                }else{
                    // 顾客
                    $arr['role_id'] = 3;
                }

                if($resusers['sign'] == '000-000'){
                    // 办事处
                    $arr['sign']=1;
                }elseif($store_id == 2){
                    // 技术部
                    $arr['sign']=2;
                }
            }

            // 不是所有门店都能看
            if(in_array(0,$arr['range_stores'])){
                // 角色是否能观看
                if(in_array(0,$arr['range_roles']) || in_array($arr['role_id'],$arr['range_roles']) || ($store_id ==2)){
                    $flag = 1;
                }
            }else{
                // 门店是否能观看
                if(in_array($store_id,$arr['range_stores']) || (in_array(1,$arr['range_stores']) && $arr['sign']==1) || ($store_id ==2)){
                    // 角色是否能观看
                    if(in_array(0,$arr['range_roles']) || in_array($arr['role_id'],$arr['range_roles']) || ($store_id ==2)){
                        $flag = 1;
                    }
                }
            }
        }
        // 判断是否是指定能观看的号码
        if(!$flag){
            $flag = $this->appointList($user_id);
        }
        return $flag;
    }
    /*
     * 功能: 查询用户所在门店及编号
     * 请求: $user_id =>用户id;$store_id=>门店id
     * 返回:
     * */
    private function userInfo($user_id)
    {
        $rest = null;
        $map['m.id'] = $user_id;
        $resuser = Db::table('ims_bj_shopn_member m')->join(['ims_bwk_branch'=>'b'],['m.storeid=b.id'],'LEFT')->field('b.sign,m.code,m.isadmin,m.id,m.staffid')->where($map)->limit(1)->find();
        if($resuser){
            $rest = $resuser;
        }
        return $rest;
    }
    /*
     * 功能: 直播指定号码观看
     * 请求: $user_id =>用户id
     * 返回:
     * */
    private function appointList($user_id)
    {
        $flag = 0;
        $map['m.id'] = $user_id;
        $map['l.type'] = 1;
        $res = Db::table('think_appoint_list l')->join(['ims_bj_shopn_member'=>'m'],['m.mobile=l.mobile'],'LEFT')->field('l.id')->where($map)->limit(1)->find();
        if(!empty($res)){
            if($res['id']){
                $flag = 1;
            }
        }
        return $flag;
    }
    /*
     * 功能: 判断用户是否能观看点播
     * 请求: $data_list=>[点播视频列表],$user_id =>用户id;$store_id=>门店id
     * 返回:
     * */
    private function isSeeReturnLive($data_list,$user_id,$store_id)
    {
        $rest = [];
        // 查询用户所在门店及编号
        $resusers = $this->userInfo($user_id);
        if(!empty($resusers)){
            if($resusers['isadmin']){
                //店老板
                $arr['role_id'] = 1;
            }elseif(strlen($resusers['code'])>0 && ($resusers['staffid'] == $resusers['id'])){
                //美容师
                $arr['role_id'] = 2;
            }else{
                // 顾客
                $arr['role_id'] = 3;
            }

            if($resusers['sign'] == '000-000'){
                // 办事处
                $arr['sign']=1;
            }elseif($store_id == 2){
                // 技术部
                $arr['sign']=2;
            }
        }
        if(!empty($data_list)){
            foreach ($data_list as $v) {
                $flag = 0;
                $arr['range_stores'] = json_decode($v['range_stores'],true);
                $arr['range_roles'] = json_decode($v['range_roles'],true);
                // 不是所有门店都能看
                if(in_array(0,$arr['range_stores'])){
                    // 角色是否能观看
                    if(in_array(0,$arr['range_roles']) || in_array($arr['role_id'],$arr['range_roles']) || ($store_id ==2)){
                        $flag = 1;
                    }
                }else{
                    // 门店是否能观看
                    if(in_array($store_id,$arr['range_stores']) || (in_array(1,$arr['range_stores']) && $arr['sign']==1) || ($store_id ==2)){
                        // 角色是否能观看
                        if(in_array(0,$arr['range_roles']) || in_array($arr['role_id'],$arr['range_roles']) || ($store_id ==2)){
                            $flag = 1;
                        }
                    }
                }
//                print_r($arr);
                if($flag == 0){
                    continue;
                }
                $rest[] = $v;
            }
        }
        return $rest;
    }

}