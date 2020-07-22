<?php

namespace app\admin\controller;
use app\admin\model\LiveModel;
use think\Db;
//整合七牛直播sdk
use pili_test\Rtmp;
//整合腾讯云通信扩展
use tencent_cloud\TimChat;

//使用redis扩展
use think\cache\driver\Redis;
//上传图片到七牛
use qiniu_transcoding\Upimg;
use app\index\controller\Index;

class LiveTent extends Base
{
    //调用直播相关接口url
    protected $url = 'http://localhost/pili_test/rtmp_test.php';

    /**
     * [liveList 直播列表]
     * @return
     * @author
     */
    public function index(){

        $key = input('key');
        $map = [];$whereT='';
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];
            // start Modify by wangqin 2017-12-28
            $whereT = " title like '%$key%' ";
            // end Modify by wangqin 2017-12-28
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 25;// 获取总条数
        $count = Db::name('live')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        //获取用户信息
        $uid = $_SESSION['think']['uid'];
        if($uid!=1)
        {
            $map['flag'] = ['<',1];
            $isadmin = 0;
            //不同账号看到自己创建的直播
            $map['user_id'] = $uid;
        }else
        {
            $isadmin = 1;
        }

        // $lists = Db::name('live')->alias('l')->join('admin ad','ad.id=l.user_id')->where($map)->limit($pre,$limits)->field('l.*,ad.username')->select();
        // start Modify by wangqin 2017-12-28
        if($isadmin)
        {
            $lists = Db::name('live')->alias('l')->where($whereT)->limit($pre,$limits)->field('l.*')->order('l.id desc')->select();
        }else
        {
            $lists = Db::name('live')->alias('l')->join('admin ad','ad.id=l.user_id')->where($map)->limit($pre,$limits)->field('l.*,ad.username')->select();
        }
        // end Modify by wangqin 2017-12-28
        foreach($lists as $k=>&$v)
        {
//            $cat_name = $this->catList($v['category_id']);
//            $v['cat_name'] = $cat_name[0]['cat_name'];
            //  $lists[$k]['create_time']=date('Y-m-d H:i:s',(int)$v['create_time']);
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);

            $v['audience'] = rand(1,10);
            if($v['statu'] == 1){
                // 从redis更新数据到数据库
//                $res2 = $this->updLivePoint();
                // 查询聊天室直播观看人数
                $v['audience'] = $this->getChatCnt($v['chat_id']);
            }

            if($v['statu'] == 0)
            {
                $v['statu'] = '直播未开始';
            }elseif($v['statu'] == 1)
            {
                $v['statu'] = '直播中';
            }elseif($v['statu'] == 2)
            {
                $v['statu'] = '直播结束';
            }

            // start Modify by wangqin 2017-12-28
            if($v['live_source'] == 1)
            {
                $v['live_source'] = 'PC端直播';
            }elseif($v['live_source'] == 2)
            {
                $v['live_source'] = '手机端直播';
            }
            // end Modify by wangqin 2017-12-28

//            $v['audience'] = $this->getNum($type='see',$v['id']);
//            $v['audience'] = $this->getChatCnt($v['chat_id']);
//            $v['point_count'] = $this->getNum($type='point',$v['id']);
            $classify_id = json_decode($v['classify_id']) ;
//            $v['zhititle'] = '';
//            if($classify_id)
//            {
//                $zhititle = '';
//                foreach ($classify_id as $v2) {
//                   $zhiti = Db::table('ims_bj_shopn_category')->field('name,id')->where('id='.$v2)->limit(1)->select();
//                   $zhititle = $zhiti[0]['name'].' '.$zhititle;
//                }
//                $v['zhititle'] = $zhititle;
//            }

            if($v['db_statu'] == 0)
            {
                $v['db_statu']='直播';
            }else
            {
                $v['db_statu']='点播';
            }
            // if($v['idstore'] == 0)
            // {
            //     $v['idstore']='正式';
            // }else
            // {
            //     $v['idstore']='测试';
            // }
            $v['db_length'] = $v['db_length']==''?'':$v['db_length'];

            if($v['see_times_flag'] == 1)
            {
                $v['see_times_flag']='开启';
            }else
            {
                $v['see_times_flag']='关闭';
            }

            if($v['idstore'] == 0)
            {
                $v['idstore']='所有人';
            }elseif($v['idstore'] == 1)
            {
                $v['idstore']='办事处';
            }elseif($v['idstore'] == 2)
            {
                $v['idstore']='测试';
            }

            $v['is_give_coupon'] = $v['is_give_coupon']==1?'赠送':'不赠送';
        }
        $this->assign('isadmin', $isadmin);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [liveAdd 添加直播]
     * @return
     * @author
     */
    public function liveAdd()
    {
        //分类列表
        $category = $this->catList();
        //直播主题分类
        $zhibo_cat = $this->zhiCat();
        if(request()->isAjax()){

            $param = input('post.');
            if($param)
            {
//               start Modify by wangqin 2017-10-25 修改流名
                //流名:live+id 正式上线时可以修改保存规则live_zb+id
                $last_id = Db::name('live')->field('id')->order('id desc')->limit(1)->select();
                if($last_id)
                {
                    $live_stream_name = 'live'.($last_id[0]['id']+1);
                }else
                {
                    $live_stream_name = 'live1';
                }
//               end Modify by wangqin 2017-10-25
                //通过七牛接口获取推流地址
                $rtmp = new Rtmp($live_stream_name);
                $push_url = $rtmp->getRtmpUrl();
                $see_url = $rtmp->getRtmpPlay();
                $hls_url = $rtmp->getHlsPlay();
                $screen_shot = $rtmp->getScreenShot();

                //获取用户信息
                $uid = $_SESSION['think']['uid'];
                $classify_id = isset($param['classify_id'])?$param['classify_id']:'';
                if(@$classify_id)
                {
                    $classify_id =  json_encode($classify_id);
                }

                $data = array('title'=>$param['title'],'push_url'=>$push_url,'see_url'=>$see_url,'hls_url'=>$hls_url,'insert_time'=>time(),'user_id'=>$uid,'content'=>$param['content'],'address'=>$param['address'],'live_stream_name'=>$live_stream_name,'user_name'=>$param['user_name'],'user_img'=>$param['user_img'],'live_img'=>$screen_shot,'category_id'=>$param['category'],'idstore'=>$param['idstore']);

                $data['is_give_coupon'] = $param['is_give_coupon'];
                //绑定聊天室
//                $rest1 = $this->chatChange($type='bd');
                $rest1 = $this->creChatRoom();
                if($uid == 1)
                {
                    $data['see_count_times'] = $param['see_count_times'];
                    $data['db_statu'] = $param['db_statu'];
                    $data['db_length'] = $param['db_length'];
                    $data['see_times_flag'] =  $param['see_times_flag'];
                    //点播
                    if($data['db_statu']==1)
                    {
                        $db_url = $param['db_url'];
                        if($db_url)
                        {
                            $data['see_url'] =  $db_url;
                            $data['hls_url'] =  $db_url;
                        }
                        $db_img = $param['db_img'];
                        if($db_img)
                        {
                            $data['live_img'] =  $db_img;
                        }
                    }
                }

                $data['classify_id'] =  $classify_id;

                $data['chat_id'] = $rest1;
                // start Modify by wangqin 2017-11-15 上传图片到七牛
                $upimg = new Upimg();
                //服务器
                $tx_path = '/home/canmay/www/live/public/uploads/face/';
                //本地
//                $tx_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\uploads\face/';
                $img_url = $upimg->upImg($tx_path.$data['user_img']);
                $data['user_img'] = $img_url;

                if($param['live_img']){
                    $live_img = $upimg->upImg($tx_path.$param['live_img']);
                    $data['live_img'] = $live_img;
                }
                // end Modify by wangqin 2017-11-15
                $rest = Db::name('live')->insert($data);
                if($rest)
                {
                    //清除redis
                    $Redis = new Redis();
                    // $Redis->rm('liveList');
                    // $Redis->rm('liveList2');
                    // 清除liveList开头的redis
                    $this->clearRedis('liveList');
                    $msg='添加成功';
                }
                $flag = array('code'=>1,'data'=>$data,'msg'=>$msg);
                return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
            }

        }
        //获取用户信息
        $uid = $_SESSION['think']['uid'];
        if($uid!=1)
        {
            $isadmin = 0;
        }else
        {
            $isadmin = 1;
        }
        $this->assign('category',$category);
        $this->assign('zhibo_cat',$zhibo_cat);
        $this->assign('isadmin',$isadmin);
        return $this->fetch();
    }


    /**
     * [liveEdit 编辑]
     * @return
     * @author
     */
    public function liveEdit()
    {
        $id = input('param.id');
        //分类列表
        $category = $this->catList();
        //直播主题分类
        $zhibo_cats = $this->zhiCat();
        $zhibo_cat = ($this->zhiCat($id)) ;
        if($zhibo_cat)
        {
            $zhibo_cat =  json_decode($zhibo_cat[0]['id']);
        }
//        var_dump(request()->isAjax());die;
        if(request()->isAjax()){

            $param = input('post.');
            $classify_id =  isset($param['classify_id'])?json_encode($param['classify_id']):'';

            $data_v =  array('title' => $param['title'],'user_name' => $param['user_name'],'user_img' => $param['user_img'],'content' => $param['content'],'category_id' => $param['category'],'classify_id' => $classify_id,'idstore' => $param['idstore']);
            $uid = $_SESSION['think']['uid'];
            if($uid == 1)
            {
                $data_v['see_count_times'] = $param['see_count_times'];
                $data_v['db_statu'] = $param['db_statu'];
                $data_v['db_length'] = $param['db_length'];
                $data_v['see_times_flag'] =  $param['see_times_flag'];
                //点播
                if($data_v['db_statu']==1)
                {
                    $db_url = $param['db_url'];
                    if($db_url)
                    {
                        $data['see_url'] =  $db_url;
                        $data['hls_url'] =  $db_url;
                    }
                    $db_img = $param['db_img'];
                    if($db_img)
                    {
                        $data['live_img'] =  $db_img;
                    }

                }
            }
            // start Modify by wangqin 2017-11-15 上传图片到七牛
            $upimg = new Upimg();
            //服务器
            $tx_path = '/home/canmay/www/live/public/uploads/face/';
            //本地
            //$tx_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\uploads\face/';
            if(!strstr($data_v['user_img'],'http://'))
            {
                $img_url = $upimg->upImg($tx_path.$data_v['user_img']);
                $data_v['user_img'] = $img_url;
            }
            if(!strstr($param['live_img'],'http://')) {
                $img_url = $upimg->upImg($tx_path.$param['live_img']);
                $data_v['live_img'] = $img_url;
            }
            $data_v['is_give_coupon'] = $param['is_give_coupon'];
            // end Modify by wangqin 2017-11-15
            $ret = Db::name('live')->where('id', $id)->update($data_v);
            //清除redis
            $Redis = new Redis();
            // $Redis->rm('liveList');
            // $Redis->rm('liveList2');
            // 清除liveList开头的redis
//            $this->clearRedis('liveList');
            $flag = array('code'=>1,'data'=>'','msg'=>'修改成功');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $list = Db::name('live')->where(array('id'=>$id))->select();
        //获取用户信息
        $uid = $_SESSION['think']['uid'];
        if($uid!=1)
        {
            $isadmin = 0;
        }else
        {
            $isadmin = 1;
        }
        $this->assign('category',$category);
        $this->assign('zhibo_cat',$zhibo_cat);
        $this->assign('zhibo_cats',$zhibo_cats);
        $this->assign('list',$list);
        $this->assign('isadmin',$isadmin);

        return $this->fetch();
    }


    /**
     * [liveDel 删除]
     * @return
     * @author
     */
    public function liveDel()
    {
        $id = input('param.id');
        //获取用户信息
        $uid = $_SESSION['think']['uid'];
        if($uid != 1)
        {
            $rest = Db::name('live')->where('id',$id)->update(['flag'=>1]);
        }else
        {
            $rest = Db::name('live')->where('id',$id)->delete();
        }
        //清除redis
        $Redis = new Redis();
        // $Redis->rm('liveList');
        // 清除liveList开头的redis
        $this->clearRedis('liveList');
        return $this->returnMsg(1,'','删除成功');
    }

    /**
     * [liveClose 关闭直播]
     * @return
     * @author
     */
    public function liveClose()
    {
        $id = input('param.id');

        if($id)
        {
            //调用禁用流方法
            //调用直播接口禁用流
            $stream = Db::name('live')->where('id',$id)->field('live_stream_name')->select();
            //关闭推流
            $rtmp = new Rtmp($stream[0]['live_stream_name']);
            $resp = $rtmp->disableStream();
            if($stream[0]['live_stream_name'])
            {
                //解绑聊天室
//                $rest1 = $this->chatChange($type='jb',$id);
                //修改直播状态
//                $rest = Db::name('live')->where('id',$id)->update(['statu'=>2]);
                //清除redis
                $Redis = new Redis();
                // $Redis->rm('liveList');
                // 清除liveList开头的redis
                $this->clearRedis('liveList');
                return $this->returnMsg(1,'','关闭成功');
            }

        }else
        {
            return $this->returnMsg(0,'','关闭失败');
        }

    }
    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
       $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
       return json($ret);
    }


    //获取观看点赞人数
    public function getNum($type='see',$id=1)
    {
        if($type=='point')
        {
          $rest = Db::field('count(id) cnt')->table('think_live_user')->where("live_id=$id and point_flag=1")->select();
        }else
        {
          $rest = Db::field('count(id) cnt')->table('think_live_user')->where("live_id=$id and audience_flag=1")->select();
        }
        $num = $rest[0]['cnt'];

        return $num;
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
            $chat_id = Db::table('think_chatroom chat,think_live live')->where('chat.chat_id=live.chat_id and live.id='.$id)->update(array('chat.flag'=>0,'upd_time'=>date('Y-m-d H:i:s',time())));
            $live_id = Db::name('live')->where('id='.$id)->update(array('chat_id'=>''));
            return true;
        }

    }

    //直播分类列表
    public function catList($id='')
    {
        if($id)
        {
            $map = 'category_id='.$id;
        }else
        {
            $map = '1=1';
        }
        $map.= " and flag=0 ";
        $rest = Db::name('live_category')->field('category_id cat_id,category_name cat_name')->where($map)->select();
        return $rest;
    }

    //添加对应的直播字幕
    public function addSubtitle()
    {
        $id = input('id');
        $type = input('type');
        if($id && $type)
        {
            //查询对应的环信通讯
            $chat_id = input('chat_id');
            $msg = input('subtitle_msg');
            //测试通讯云扩展
            $tent = new TimChat();

            //start Modify by wangqin 2017-11-15 添加禁言
            $forbid_user = @input('forbid_user')==''?'':input('forbid_user');
            if($forbid_user)
            {
                if(strstr($forbid_user,'手机用户'))
                {
                    $mob = substr($forbid_user,-3);
                    $mobile = Db::table('ims_bj_shopn_member')->field('mobile')->where("right(mobile,3)='$mob'")->limit(1)->select();
                }else
                {
                    $mobile = Db::table('ims_bj_shopn_member')->field('mobile')->where("realname like '%$forbid_user%'")->limit(1)->select();
                }
                $resp = $tent->forbidUser($chat_id,$mobile[0]['mobile']);
                return 1;
            }
            //end Modify by wangqin 2017-11-15

            //过滤多余的空格和换行符
            $search = array(" ","　","\n","\r","\t");
            $replace = array("","","","","");
            $msg = str_replace($search, $replace, $msg);
            //字幕是否滚动,0=>不滚,1=>滚动
            $intscroll = input('intscroll')==''?0:input('intscroll');
            $clear = @input('clear')==''?'':@input('clear');
            if($clear)
            {
                $res1 = $tent->sendMsgs($chat_id,$msg);
            }else
            {
                $res1 = $tent->sendMsgs($chat_id,$msg,$intscroll);
            }
//            $res1 = $tent->sendMsgs($chat_id,$msg,$intscroll);
//            echo $chat_id.'-'.$msg.'-'.$intscroll;
            return 1;
        }else{
            //显示界面
            $list = Db::name('live')->field('id,chat_id,title')->where("id=$id")->select();
            $this->assign('list',$list);
            return $this->fetch();
        }

    }

    //直播主题分类
    public function zhiCat($id='')
    {
       $data = array();
       if($id)
       {
         //分类查询列表 [1]
         $rest =  Db::table('think_live live')->field('classify_id id')->where('id='.$id)->select();
       }else
       {
         //分类列表
         $data['weid'] = 1;
         $rest = Db::table('ims_bj_shopn_category')->field('id,name')->where($data)->select();
       }

       return $rest;
    }

    //获取聊天室人数
    public function getChatCnt($chat_id)
    {
        $cnt = 0;
        if($chat_id)
        {
            $res = Db::name('chatroom')->field('chat_cnt')->where("chat_id='".$chat_id."'")->limit(1)->select();
            $cnt = $res[0]['chat_cnt'];
        }
        return $cnt;
    }

    //水军向聊天室发送消息
    public function sendMsg()
    {
        $id = input('id');
        $type = input('type');
        if($id && $type)
        {
            //查询对应的环信通讯
            $chat_id = input('chat_id');
            $msg = input('send_msg');

            //测试通讯云扩展
            $tent = new TimChat();
            $res1 = $tent->sendMsgs($chat_id,$msg,'sj');

            return 1;
        }else{
            //显示界面
            $list = Db::name('live')->field('id,chat_id,title')->where("id=$id")->select();
            $this->assign('list',$list);
            return $this->fetch();
        }

    }

    //创建聊天室
    public function creChatRoom()
    {
        $tent = new TimChat();
        $res1 = $tent->creChatRoom();
        //插入聊天室id到chatRoom表
        $data = array('chat_id'=>$res1,'chat_name'=>'去哪美聊天室'.$res1,'chat_owner'=>'admin','chat_cnt'=>1,'flag'=>1,'log_time'=>date('Y-m-d H:i:s'));
        $res2 = Db::name('chatroom')->insert($data);
        return $res1;
    }

    //获取redis点赞观看人数更新到数据库
    public function updLivePoint()
    {
        $Redis = new Redis();
        $res = Db::name('live')->field('id,point_count')->order('id desc')->select();
        if($res)
        {
            foreach ($res as $v) {
                $val = $Redis->get($v['id']);
                $res = Db::name('live')->where('id='.$v['id'])->update(array('point_count'=>$val));
            }
            return 1;
        }
    }

    //start Modify by wangqin 2017-11-27 清除keys开头的所有redis数据
    /*
     * 功能: 清除keys开头的所有redis数据
     * 请求: $paras keys前缀
     * 返回:
     * */
    public function clearRedis($paras='')
    {

        $redis = new Redis();
        if($paras)
        {
            //删除指定缓存
            $paras = $paras.'*';
            //获取指定前缀keys
            $keys = $redis->getKeys($paras);
            //删除redis
            $redis->delKeys($keys);
        }
    }
    //end Modify by wangqin 2017-11-27
    //
    // start Modify by wangqin 2018-03-06
    //   PC端直播列表
    public function zbList()
    {
//      直播列表
        $index = new Index();
        $zb_list = $index->getLiveDetail('','15921324164');
        if($zb_list)
        {
//            $img_http = 'http://'.$_SERVER['HTTP_HOST'].'/uploads/face/';

            foreach($zb_list as &$v)
            {
//                if($v['user_img'])
//                {
//                    $v['user_img'] =  $img_http.$v['user_img'];
//                }
                //获取观看点赞人数
                $v['gk_cnt'] = $index->getCnt($v['chat_id']);
                $v['dz_cnt'] = $index->getDcnt($v['id']);
            }
            $this->assign('zb_list', $zb_list);
        }else
        {
            return '暂无直播开启,请稍候再观看!';
        }
        return $this->fetch('index@index/zblist');
    }

    //   PC端直播观看
    public function zbSee()
    {

        $live_id = input('id');
        $index = new Index();
        $user = array('name'=>'15921324164','pwd'=>'a123456');
        //获取直播间数据
        $res = $index->getLiveDetail($live_id);
        if($res)
        {
            $img_http = 'http://'.$_SERVER['HTTP_HOST'].'/uploads/face/';

            foreach($res as &$v)
            {
                if($v['user_img'])
                {
                    $v['user_img'] =  $img_http.$v['user_img'];
                }
                //获取观看点赞人数
                $v['gk_cnt'] = $index->getCnt($v['chat_id']);
                $v['dz_cnt'] = $index->getDcnt($v['id']);
            }
            $this->assign('list', $res[0]);
        }
        $this->assign('user', $user);
        return $this->fetch('zbsee');
    }
    // end Modify by wangqin 2018-03-06
}