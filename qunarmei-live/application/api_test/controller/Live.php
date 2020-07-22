<?php

namespace app\api_test\controller;
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

/**
 * live: 直播
 */
class Live extends Base
{
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
 private $admin_userid = 16829;
  //正试服务器
  // private $admin_userid = 18357; //admini账号对应的用户id
 private $redis_qianzhui = 'apitest';
  /**
	 * get: 直播列表
	 * path: list
	 * method: list
	 * param: position - {int} 广告位
	 */
	public function liveList($type='')
    {
    	  // start Modify by wangqin 2017-11-27 门店和办事处直播权限控制
        $idstore = input('idstore') == ''?1:input('idstore');
        //是否是办事处
        $idstore = $this->isOffice($idstore);
        $res = @$this->getRedis('liveList'.$idstore);
        // end Modify by wangqin 2017-11-27

        $keyword = input('keyword');
        $type = input('type');
        if(!$res || $type || $keyword)
        {


            $where_type = '';
            if($type)
            {
                $where_type .= ' and live.statu=1';
            }
            if($keyword)
            {
                $where_type .= " and (live.title like '%$keyword%' or live.content like '%$keyword%')" ;
            }
            if($idstore != 2)
            {
                // start Modify by wangqin 2017-11-27
                $where_type .= ' and (live.idstore='.$idstore.' or live.idstore=0)';
                // end Modify by wangqin 2017-11-27
            }
            // $where_type.='and live.statu!=2';
            $where_type.=' and (statu=1 or (db_statu=1 and statu=0))';
            //获取直播列表
            // start Modify by wangqin 2018-01-24
            $ret = Db::field('live.live_img_small,live.user_id,live.id live_id,live.user_name name,live.user_img img,live.title,live.content,live.address,live.insert_time live_time,live.statu,live.live_img,live.see_url play_url,chat_id,live.category_id,cat.category_name,video_type,live.classify_id,live.see_count_times,live.db_statu,live.db_length,live.point_count,live.live_source')->table('think_live live,think_live_category cat')->where('live.category_id=cat.category_id and cat.flag=0 '.$where_type)->group('live_id')->order('live_time desc')->select();
            // end Modify by wangqin 2018-01-24
            foreach ($ret as $k => &$v) {
//                if($v['img'])
//                {
//                    $v['img'] = 'http://'.$_SERVER['HTTP_HOST'].$this->img_url.$v['img'];
//                }
               // start Modify by wangqin 2018-01-22 增加用户id
                $v['live_img_small'] = $v['live_img_small']==''?'':$v['live_img_small'];
                if($v['user_id'] == 1)
                {
                    $v['user_id'] = $this->admin_userid;
                }else
                {
                    //根据用户号码查找
                    $mob_cx = Db::table('ims_bj_shopn_member')->field('id')->where('mobile',$v['user_id'])->limit(1)->select();
                    if($mob_cx)
                    {
                        $v['user_id'] = $mob_cx[0]['id'];

                    }
                }
                // end Modify by wangqin 2018-01-22
//              //  start Modify by wangqin 2017-12-22 增加连麦房间room_id
                $room_info = Db::name('room r,think_live l')->field('r.id as room_id')->where("r.live_name=l.live_stream_name and l.id= ".$v['live_id'])->limit(1)->select();
                $v['room_id'] = '';
                if($room_info)
                {
                    $v['room_id'] = $room_info[0]['room_id'];
                }
                //  end Modify by wangqin 2017-12-22
//
                $v['content'] = $v['content']==''?'':$v['content'];
                $v['address'] = $v['address']==''?'':$v['address'];
                // $v['statu'] = $v['statu']==''?'':$v['statu'];
                $v['live_img'] = $v['live_img']==''?'':$v['live_img'];
                $v['live_time'] = $v['live_time']==''?'':date('Y-m-d H:i:s',$v['live_time']);
                //获取直播观看点赞人数
//        $v['audience'] = $this->getNum('see',$v['live_id']);
                // $v['audience'] = intval($this->getChatsCount($v['chat_id']))*$v['see_count_times'];
                $v['audience'] = $this->pointPraise($v['live_id'],'gk')==''?1:$this->pointPraise($v['live_id'],'gk');
                //start Modify by wangqin 2017-11-04 点赞人数
                $v['point_count'] = $this->pointPraise($v['live_id'],'interface')==''?0:$this->pointPraise($v['live_id'],'interface');
                //end Modify by wangqin 2017-11-04
                $v['chat_id'] = $v['chat_id']==''?'':$v['chat_id'];
                $v['share_url'] = "http://live.qunarmei.com/index/index/pcplay?id={$v['live_id']}";
                $v['classify_id'] = $v['classify_id']==''?'':$v['classify_id'];
                $v['classify_id'] = str_replace('"','',$v['classify_id']);
//        $v['db_statu'] = $v['db_statu']==''?'':$v['db_statu'];
                $v['db_length'] = $v['db_length']==''?'':$v['db_length'];
            }
            // if($ret)
            // {
            //     //按数组某字段排序
            //     $ret = sortField($ret,'audience');
            //     //按数组某字段排序
            //     $ret = sortField($ret,'db_statu','asc');
            //     //检验数据,返回拼装json
            // }
            if($keyword || $type)
            {
                $res = $ret;
            }else
            {
                $res = $ret;
                $this->setRedis('liveList'.$idstore,$ret);

            }

        }
        // start Modify by wangqin 2018-01-20 直播列表,获取点赞、观看人数
       if($res)
        {
            foreach($res as &$v)
            {
                //只修改PC端正在直播的直播间观看和点赞人数
                if($v['statu']==1 && $v['db_statu']==0 && $v['live_source']==1)
                {
                    $v['point_count'] =  $this->getRedis($v['live_id'])==''?0:$this->getRedis($v['live_id']);
                    $v['audience'] = $this->pointPraise($v['live_id'],$type='gk')==''?0:$this->pointPraise($v['live_id'],$type='gk');
                }
            }
        }
//        echo '<pre>res:';print_r($res);exit;
        return parent::returnMsg(1,$res,'获取成功');
    }


   //调用观看点赞数据
   public function pointPraise($liveid='',$type='')
   {
     $live_id = input('live_id');

//       点赞人数存取到redis里
       // $Redis = $this->creRedis();

     $num = input('num')==''?0:input('num');
     //观看人数
     $op = input('op');
     if(!$num && $live_id)
     {
         $gk = @$this->getRedis($live_id.'_see');
         if(!$gk)
         {
             // start Modify by wangqin 2017-11-22 增加倍率开关控制 1=>开启,2=>关闭
             $see_times_flag = @$this->getRedis($live_id.'_times_flag');
             $chat_id = @$this->getRedis($live_id.'_chatid');
             if(!$see_times_flag)
             {
               $see_t = Db::name('live')->field('see_times_flag,chat_id,see_count_times')->where('id='.$live_id)->limit(1)->select();
               $flag = $see_t[0]['see_times_flag'];
               if($flag)
               {
                 $see_times_flag = 1;
                 @$this->setRedis($live_id.'_times_flag',1);
                  //聊天室人数x倍率
                  $gk_cnt = ($this->getChatsCount($see_t[0]['chat_id']))*$see_t[0]['see_count_times'];

               }else
               {
                 $see_times_flag = 2;
                 @$this->setRedis($live_id.'_times_flag',2);
                 $gk_cnt = $this->getChatsCount($see_t[0]['chat_id']);
               }
               @$this->setRedis($live_id.'_chatid',$see_t[0]['chat_id']);
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
             @$this->setRedis($live_id.'_see',$gk_cnt,30);
             $gk = @$this->getRedis($live_id.'_see');
         }
     }
     if($live_id)
     {
        $vr1 =  @$this->getRedis($live_id);
     }

     //内部接口获取点赞人数
     if($type == 'interface' && $liveid)
     {
         $vr1 =  @$this->getRedis($liveid);
         return  $vr1;
     }

     //内部接口获取观看人数 x 倍率
     if($type == 'gk' && $liveid)
     {
         $vr1 =  @$this->getRedis($liveid.'_see');
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
             @$this->setRedis($liveid.'_see',$gk_cnt,30);
             $vr1 =  $gk_cnt;
             // echo "gk_cnt:".$gk_cnt;
         }
         return  $vr1;
     }

     if(!$vr1)
     {
         @$this->setRedis($live_id,$num);
     }else
     {
         @$this->setRedis($live_id,($vr1+$num));
     }
     $vr1 = @$this->getRedis($live_id);
     if(!$num)
     {
        $vr2 = @$this->getRedis($live_id.'_see');
        $data = array('point_count'=>$vr1,'audience'=>$vr2);
     }else
     {
        $data = array('point_count'=>$vr1);
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
     //开始直播
      $type = input('type');
      //记录日志
      trace($log = input(), $level = 'log');
      //清除直播列表接口redis
      //start Modify by wangqin 2017-11-27
      // $this->delRedis('liveList');
      // $this->delRedis('liveList2');
      $this->clearRedis('liveList');
      // start Modify by wangqin 2017-11-27
      if($type == 'start')
      {


        //记录回调通知
        $back_log = Db::name('back_log')->insert(array('query_str'=>http_build_query(input()),'log_time'=>date('Y-m-d H:i:s'),'interface'=>'liveStatuCall'));
        //回调记录开始直播的流
        $back_record = Db::name('callback_record')->insert(array('stream_name'=>input('title'),'start_time'=>date('Y-m-d H:i:s'),'log_time'=>date('Y-m-d H:i:s')));
        //流名为title的开始直播
        if(input('title'))
        {
          //开始直播 修改状态
          $data = array('statu'=>1,'db_statu'=>0);
          //绑定聊天室
          // $rest1 = $this->chatChange($type='bd');
          // $data['chat_id'] = $rest1;
          //直播开始更换直播封面
          //通过七牛接口获取推流地址
          $rtmp = new Rtmp(input('title'));
          $cover_url = $rtmp->getScreenShot();
          $data['live_img'] = $cover_url;
          // 重新推流,修改rtmp播放地址
          $data['see_url'] = 'rtmp://pili-live-rtmp.qunarmei.com/qunarmeilive/'.input('title');
          // $data = array('type'=>'cover','stream_name'=>input('title'));
          $data['insert_time'] = time();
          // start Modify by wangqin 2017-12-25 保存直播封面图
          // $live_jpg = $rtmp->getSnapshot();
          // $data['live_img_keep'] = ($this->replay_domain).$live_jpg;
          // end Modify by wangqin 2017-12-25
          $rest = Db::name('live')->where("live_stream_name='".input('title')."'")->update($data);
          //通知客户端
          // start Modify by wangqin 2018-01-09 手机端直播开始时短信通知
          //是否是手机端直播
          $is_mobile_live = Db::name('live,think_live_mobile_notice')->field('live_source,mobiles,user_id')->where('live_stream_name',input('title'))->where('live_source',2)->limit(1)->select();
          if($is_mobile_live)
          {
              //下发短信通知
              $send_mobiles = $is_mobile_live[0]['mobiles'];
              $send_res = $this->sendMsg($send_mobiles,$is_mobile_live[0]['user_id']);
          }
          // end Modify by wangqin 2018-01-09
          $ret = parent::returnMsg(1,'','回调成功');
        }else
        {
          $ret = parent::returnMsg(0,'','参数错误');
        }

      }else
      {
        //记录回调通知
        $back_log = Db::name('back_log')->insert(array('query_str'=>http_build_query(input()),'log_time'=>date('Y-m-d H:i:s'),'interface'=>'liveStatuCall'));
         //断开直播
        if(input('title'))
        {
          $data = array('statu'=>2);
          //解绑聊天室
          // $resp = Db::name('live')->where("live_stream_name='".input('title')."'")->select();
          // $rest1 = $this->chatChange('jb',$resp[0]['id']);
          //查询开始直播时间
          $back1 = Db::name('callback_record')->field('start_time')->where("stream_name='".input('title')."'")->order('id desc')->limit(1)->select();
          if($back1)
          {
              $start_time = $back1[0]['start_time'];
          }
          //保存直播视频
          $rtmp = new Rtmp(input('title'));
          $resp = $rtmp->saveReplay(input('title'),strtotime($start_time),time());
          // 转码保存直播视频
          // $data['replay_trans_url'] = $this->videoTranscoding(input('title'));

          $data['replay_url'] = $this->replay_domain.$resp['fname'];
          $data['see_url'] = $data['replay_url'];
          $data['db_statu'] = 1;
          // start Modify by wangqin 2017-12-25 保存直播封面图
          #更新 封面图截图到 live_img
          $live_jpg = $rtmp->getSnapshot();
          $data['live_img_keep'] = ($this->replay_domain).$live_jpg;
          $data['live_img'] = $data['live_img_keep'];
          #转存点播去掉
          $data['classify_id'] = '';
          // end Modify by wangqin 2017-12-25
          $rest = Db::name('live')->where("live_stream_name='".input('title')."'")->update($data);
          //通知客户端
          //回调记录结束直播的流
          $back_record = Db::name('callback_record')->where(array('stream_name'=>input('title')))->update(array('replay_url'=>$data['replay_url'],'end_time'=>date('Y-m-d H:i:s')));
          //转码保存到qunarmeilive-vod空间里
          $res1 = $this->videoTranscoding(input('title'));
          if($res1)
          {
            $res2 = Db::name('live')->where("live_stream_name='".input('title')."'")->update(array('replay_url'=>$res1));
          }
          //
          $ret = parent::returnMsg(1,'','回调成功');
        }else
        {
          $ret = parent::returnMsg(0,'','参数错误');
        }
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
        $res = $this->getRedis('getSig_'.$mobile);
        if(!$res || @(!$res['usersig']))
        {
            if($type == 0)
            {
                //注册
                $res = $this->tentRegister($mobile);
            }
            $tent = new TimChat();
            $res1 = $tent->getUserSig($mobile);
            $res2 = array('usersig'=>$res1);
            // start Modify by wangqin 2017-12-02 usersig设置过期期限20天
            $this->setRedis('getSig_'.$mobile,$res2,20*3600*24);
            // end Modify by wangqin 2017-12-02
            $res =  $res2;
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
        $name = Db::table('ims_bj_shopn_member')->field('realname name')->where("mobile='$mobile'")->limit(1)->select();
        if($name)
        {
           $nick_name = $name[0]['name'] ;
        }else
        {
            $nick_name = $mobile;
        }
        //注册号码和昵称
        $res1 = $tent->tentRegister($mobile,$nick_name);
        //end Modify by wangqin 2017-11-02
        return 1;
    }
//end Modify by wangqin 2017-10-31

    //通过redis获取数据
    public function getRedis($paras)
    {
        // $Redis = new Redis();
        $redis_v = $this->creRedis();
        $res = $redis_v->get($this->redis_qianzhui.$paras);
        return $res;
    }

    //设置redis数据
    public function setRedis($paras,$val,$expire = null)
    {
        // $Redis = new Redis();
        $redis_v = $this->creRedis();
        $v = json_encode($val);
        $res = $redis_v->set($this->redis_qianzhui.$paras,$v,$expire);
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
            $redis_v->rm($this->redis_qianzhui.$paras);
            $res = $this->redis_qianzhui.$paras;
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
            $paras = $this->redis_qianzhui.$paras.'*';
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

}