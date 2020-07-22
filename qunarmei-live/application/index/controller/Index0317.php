<?php
namespace app\index\controller;
use think\Db;
use think\Controller;
use tencent_cloud\TimChat;
//redis
use think\cache\driver\Redis;
class Index extends Controller
{

   //创建静态变量存redis实例
  public static $redis_v = null;

  // 验证码登录观看H5直播页面
   public function logcheck()
   {
      return $this->fetch('logcheck');
   }
   // 扫码下载页面
   public function login()
   {
      return $this->fetch('login');
   }
    public function index()
    {
        echo phpinfo();
    }

	public function test() {
		return view('test');
	}

  //分享下载App地址
   public function downshare($user_name='',$mobile='')
   {
     $user_name = input('user_name');
     $mobile = input('mobile');
     $code = input('invitor');
     if($user_name && $mobile)
     {
      $type = $this->getDevice();
      if($type=='ios')
      {
        $map['type'] = 'ios';
      }else
      {
        $map['type'] = 'andriod';
      }
      if($user_name && $mobile)
      {
        $rest = Db::name('down')->field('url')->where($map)->select();
        $list = array('user_name'=>$user_name,'mobile'=>$mobile,'url'=>$rest[0]['url'],'code'=>$code);
      }else
      {
        return '参数不正确,请稍候再试!';
      }

      $this->assign('list', $list);
      return $this->fetch();
     }else
     {
      $data = array('code' => 0,'data'=>array(),'msg'=>'参数错误' );
      return json_encode($data);
     }


     // return $ret;
   }

   //分享web端观看直播/点播地址
   public function pcplay($id='')
   {
      $mobile = input('mobile');
       //头像图片全地址
//       $img_http = 'http://localhost:81/uploads/face/';
       $img_http = 'http://live.qunarmei.com/uploads/face/';
       $live_id = input('id');
     if($live_id)
     {
        // start Modify by wangqin 2017-12-28 关闭直播时,直播分享提示
        $rest = Db::table('think_live live')->field('hls_url,see_url,user_name,user_img,address,chat_id,db_statu,live_img,see_count_times,statu')->where("live.id = $live_id")->select();

        if($rest)
         {
            $rest[0]['mobile'] = $mobile;
           //获取观看人数
           // $rest[0]['gk_cnt']=($this->getCnt($rest[0]['chat_id']))*($rest[0]['see_count_times']);
           // if(!$rest[0]['gk_cnt'])
           // {
           //     $rest[0]['gk_cnt'] = ($rest[0]['cnt'])*($rest[0]['see_count_times']);
           // }
           //头像
  //         $rest[0]['user_img'] = $img_http.$rest[0]['user_img'];
          $rest[0]['gk_cnt']=$this->getCnt($rest[0]['chat_id']);
          if($rest[0]['statu'] != 1)
          {
              $rest[0]['hls_url'] = $rest[0]['see_url'];
              $this->assign('rest', $rest[0]);
              return $this->fetch('dianbo');
          }else
          {
              $this->assign('rest', $rest[0]);
              return $this->fetch('zhibo');
          }
        }else
         {
             $rest = array('user_name'=>'去哪美','user_img'=>config('qiniu_img_domain').'/20171115165410_7971.jpg','address'=>'上海','chat_id'=>'','hls_url'=>'','see_url'=>'','db_statu'=>'','gk_cnt'=>1);
             $this->assign('rest', $rest);
             return $this->fetch('zhibo');
         }
        // end Modify by wangqin 2017-12-28

     }else
     {
      $data = array('code' => 0,'data'=>array(),'msg'=>'参数错误' );
      return json_encode($data);
     }

     // return $ret;
   }

   public function getDevice()
   {
     if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
          $type = 'ios';
      }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
          $type = 'andriod';
      }else{
          $type = 'other';
      }
      return $type;
   }

   //直播分享下载
   public function zhibo_down()
   {
     // 默认版本地址
     $down_url = 'https://imtt.dd.qq.com/16891/C3DE67DA22040E90E6AB1BA90D827CEB.apk?fsname=com.qunarmei.client_2.4.3_111019.apk&csr=1bbd';
     // 查询Android在应用宝上下载地址
     $map['id_apptype'] = 1;
     $res = Db::table('app_version_update')->field('st_path')->where($map)->limit(1)->find();
     if(!empty($res)){
      $down_url = $res['st_path'];
     }
     $this->assign('down_url',$down_url);
     return $this->fetch();
   }

    //获取观看人数
    public function getCnt($id)
    {
        if($id)
        {
            if($id == '@TGS')
            {
              $id = '@TGS#a6QCZE6ET' ;
            }
            // $resp = Db::name('chatroom')->field('chat_id,chat_cnt')->where("chat_id='$id'")->select();
            // $cnt =  $resp[0]['chat_cnt'];
            $cnt = $this->getChatsCount($id);
            // 直播间人数乘以倍数
            $map['chat_id'] = $id;
            $res = Db::table('think_live')->field('see_count_times,see_times_flag')->where($map)->limit(1)->find();
            if(!empty($res)){
              if($res['see_times_flag']){
                $cnt = $cnt * $res['see_count_times'];
              }
            }
            return $cnt;
        }
    }

//   PC端直播登录
    public function zbLogin()
    {
        if(input('user_name') && input('password'))
        {
            $data = array('mobile'=>input('user_name'),'pwd'=>md5(input('password')));
            $res = Db::table('ims_bj_shopn_member')->where($data)->limit(1)->select();
            if($res)
            {
                //开启session存储用户登录数据
                session_start();
                $_SESSION['zb'] = array('name'=>input('user_name'),'pwd'=>input('password'));
                return 1;
            }else
            {
                return 2;
            }
        }
        return $this->fetch();
    }

    //   PC端直播列表
    public function zbList()
    {
        session_start();
        $name =  @$_SESSION['zb']==''?'':@$_SESSION['zb'];
        if($name)
        {
//          直播列表
//            $zb_list = Db::name('live')->field('id,chat_id,user_name,user_img,title,content,address,hls_url,live_img')->select();
            // start add by wangqin  2018-01-15 控制号码PC端观看手机端直播
            $mobile = $name['name'] ;
            $cx_mob = Db::name('live_mobile_notice')->field('mobiles')->where("mobiles rlike '$mobile'")->limit(1)->select();
            if($cx_mob)
            {
                $zb_list = $this->getLiveDetail('',$mobile);
            }else
            {
                $zb_list = $this->getLiveDetail();
            }
            // $zb_list = $this->getLiveDetail();
            // end add by wangqin  2018-01-15
            if($zb_list)
            {
                $img_http = 'http://'.$_SERVER['HTTP_HOST'].'/uploads/face/';

                foreach($zb_list as &$v)
                {
//                    if($v['user_img'])
//                    {
//                        $v['user_img'] =  $img_http.$v['user_img'];
//                    }
                    //获取观看点赞人数
                    $v['gk_cnt'] = ($this->getCnt($v['chat_id']))*$v['see_count_times'];

                    $v['dz_cnt'] = $this->getDcnt($v['id']);
                }
            }
            $this->assign('zb_list', $zb_list);
            return $this->fetch('zblist');
        } else
        {
            return '<script>location.href="zblogin.html"</script>';
        }

    }

    //   PC端直播观看
    public function zbSee()
    {
        //开启session,判断是否登录
        session_start();
        $name =  @$_SESSION['zb']==''?'':@$_SESSION['zb'];
        //判断是否注册
        $this->regChat($_SESSION['zb']['name']);
        $live_id = input('id');
        if($name)
        {
            $user = array('name'=>$_SESSION['zb']['name'],'pwd'=>$_SESSION['zb']['pwd']);

            //获取user_sig
            $user_sig = Db::name('tent_cloud')->field('user_sig')->limit(1)->where("tent_cloud='".$_SESSION['zb']['name']."'")->select();        if($user_sig)
            {
                $user['pwd'] = $user_sig[0]['user_sig'] ;
            }

            //获取直播间数据
            $res = $this->getLiveDetail($live_id);
            if($res)
            {
                $img_http = 'http://'.$_SERVER['HTTP_HOST'].'/uploads/face/';

                foreach($res as &$v)
                {
//                    if($v['user_img'])
//                    {
//                        $v['user_img'] =  $img_http.$v['user_img'];
//                    }
                    //获取观看点赞人数
                    $v['gk_cnt'] = ($this->getCnt($v['chat_id']))*($v['see_count_times']);

//                    $v['dz_cnt'] = $this->getDcnt($v['id']);
                }

            }
            $this->assign('list', $res[0]);
            $this->assign('user', $user);
            return $this->fetch('zbsee');
        } else
        {
            return '<script>location.href="zblogin.html"</script>';
        }
    }

    //获取点赞人数
    public function getDcnt($live_id='')
    {
        if($live_id)
        {
            $res = Db::name('live_user')->field('count(id) cnt')->where('live_id='.$live_id.' and point_flag=1')->select();
            return $res[0]['cnt'];
        }

    }

    //获取直播相关数据
    // start add by wangqin  2018-01-15 控制PC端观看手机端直播页面
    public function getLiveDetail($live_id='',$mobile='')
    {
        if($mobile)
        {
            //指定手机号观看手机端直播页面
            $map =' db_statu=0 and statu=1 and flag=0';
        }else
        {
            $map =' db_statu=0 and statu=1 and flag=0 ';
            // end Modify by wangqin 2018-01-04
            if($live_id)
            {
                $map .= ' and id='.$live_id;
            }
        }
        $res = Db::name('live')->field('*')->where($map)->select();
        return $res ;
    }
    // end add by wangqin  2018-01-15

    // 通过号码获取用户名字
    public function getName()
    {
      $mobile = input('mobile');
      $name = 'test1';
      $rest = Db::table('ims_bj_shopn_member')->field('realname')->where("mobile='".$mobile."'")->limit(1)->select();
      if($rest)
      {
        $name = $rest[0]['realname'];
      }
      return $name;
    }

    //直播页面
    public function yugao()
    {
        //start Modify by wangqin 2017-12-13 预告页面图片显示
        // $redis = $this->creRedis();
        // $img = $redis->get('yugao');
        // if(!$img)
        // {
          $dt = date('Y-m-d H:i:s');
          $res = Db::name('live_trailer')->field('cover_img')->order('begin_time desc')->limit(1)->select();
          if($res)
          {
            $img = $res[0]['cover_img'];
            // $redis->set('yugao',$img);
          }
        // }

        $this->assign('img', $img);
        //end Modify by wangqin 2017-12-13
        return $this->fetch();
    }

    //注册聊天室功能
    public function regChat($mobile='')
    {
        if($mobile)
        {
            $res = Db::name('tent_cloud')->field('id')->where("tent_cloud='$mobile'")->limit(1)->select();
            //没获取到,注册1个
            if(!$res)
            {
                $tent = new TimChat();
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
                $user_sig = $tent->getUserSig($mobile);
                return 1;
            }

        }
    }

    // start Modify by wangqin 2017-11-15
    //记录HLS观看人数
    public function getHlsCnt()
    {
        // $redis = new Redis();
        $redis = $this->creRedis();
        $id = $redis->get('id');
        if(!$id)
        {
            $id = $redis->set('id',1);
        }else
        {
            //每次自动+1
            if(!input('flag'))
            {
                $id = $redis->inc('id');
            }
        }

        $t = time();
        $t1 = input('time')==''?$t:input('time');
        $flag = input('flag')==''?'A'.$id:input('flag');

        //获取数据
//        $res1 = $redis->get($flag);
        $res1 = $redis->Hset('HlsCnt',$flag,$t1);
        return $flag;
    }

    //定时删除过期的redis
    // $dt => 多久定时器跑一次
    public function delHlsCnt($dt='')
    {
        $t = time();
        // $redis = new Redis();
        $redis = $this->creRedis();
        $id = $redis->get('id');
        for($i=1;$i<=$id;$i++)
        {
            $v = $redis->Hget('HlsCnt','A'.$i);
            $cha = $t-$v;
//            echo date('Y-m-d H:i:s',$t).'-'.date('Y-m-d H:i:s',$v);
            if($cha > 20)
            {
                $redis->Hdel('HlsCnt','A'.$i);
//                echo 'flag:'.'A'.$i.'-time:'.$v.'<br/>';
            }
        }
    }

    //获取redis观看人数
    public function getHlsSs()
    {
        $redis = $this->creRedis();
        $this->delHlsCnt();
        // $redis = new Redis();
        $len = $redis->Hlen('HlsCnt');
        return $len;
    }
    // end Modify by wangqin 2017-11-15


    //获取聊天室人数
    public function getChatsCount($id)
    {
        $map = array();
        //获取腾讯云聊天室人数
        $tent = new TimChat();
        $res = $tent->getChatCnt($id);
        if($res)
        {
            $data = array('chat_cnt'=>$res,'log_time'=>date('Y-m-d H:i:s'));
        }else
        {
            $res = 1;
        }
        return $res;
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
    /*
     * 显示中奖名单页面
     * */
    public function winner()
    {
        return $this->fetch();
    }

    // start Modify by wangqin 2017-12-01 获取腾讯云聊天室人数
    /*
     * req
     * res cnt =>直播聊天室人数
     * */
    public function getTenChatNum()
    {
        $tent_cnt = 1;
        //查询聊天室id
        $res = Db::name('live')->field('chat_id')->where('statu=1 and db_statu=0')->limit(1)->select();
//        var_dump($res);exit;
        if($res)
        {
           $chat_id = $res[0]['chat_id'];
           //根据聊天室查人数
           $tent_cnt = $this->getChatsCount($chat_id);
        }
//        var_dump($tent_cnt);
        return $tent_cnt;
    }

    // end Modify by wangqin 2017-12-01
    // start Modify by wangqin 2018-01-16 获取session中的表单数据
    public function mob_preview()
    {
        //取redis数据
        //存预览数据到redis里
        $list='';$article_img='';$res=''; $list1='';$url='http://live.qunarmei.com';
        $redis = new Redis();
        $data = $redis->get('form_sess');
        if($data)
        {
            parse_str($data,$list);
            $list1=$list;
            #id=&title=%E6%B5%8B%E8%AF%95%E6%96%87%E7%AB%A0111111&remark=%E6%B5%8B%E8%AF%95%E5%86%85%E5%AE%B9%E6%97%A51111111&sign1=买买买&sign2=爱爱爱&lun_img=@/uploads/img/nx_1516178178836.jpg@/uploads/img/nx_1516178178621.jpg&user_id=1
            $res = Db::name('find_content cnt,ims_bj_shopn_member mem,ims_fans fans')->field('fans.avatar user_img,mem.realname user_name,cnt.article_img,cnt.article_title,cnt.article_content')->where('mem.id=fans.id_member and mem.id=cnt.user_id and cnt.user_id='.$list['user_id'])->limit(1)->order('cnt.id desc')->select();
            $list2 = array('user_img'=>'','user_name'=>'','article_title'=>'','article_content'=>'');
            if($res)
            {
               $article_img = ltrim($list['lun_img'],'@');
                $article_img = explode('@',$article_img);
                foreach($article_img as &$v)
                {
                    $v = $url.$v;
                }
               $list2 = array('user_img'=>$res[0]['user_img'],'user_name'=>$res[0]['user_name'],'article_title'=>$list['title'],'article_content'=>$list['remark']);
            }
            if(!$list1['lun_img'] && $list1['id'])
            {
              $img_v = Db::name('find_content')->field('article_img')->where('id',$list1['id'])->limit(1)->select();
              if($img_v)
              {
                if($img_v[0]['article_img'])
                {
                  $article_img = (array)json_decode($img_v[0]['article_img']);
                  foreach ($article_img as $key => $v) {
                    $data_i[] = $v->img_url;
                  }
                  $article_img = $data_i;
                }
              }
            }
        }
        $list = $list2;
        $this->assign('article_img', $article_img);
        $this->assign('list', $list);
        return $this->fetch();
    }
    // end Modify by wangqin 2018-01-16
    // 预热网页显示
    public function yure()
    {
      // 显示长图img
      $map['type'] = 1;
      $res = Db::table('think_webpage')->field('content')->where($map)->limit(1)->find();
      $this->assign('res', $res);
      return $this->fetch();
    }

    // 中奖公示
    public function winningList ()
    {
        return $this->fetch();
    }
}
