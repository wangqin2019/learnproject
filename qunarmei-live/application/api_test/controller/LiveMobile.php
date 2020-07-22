<?php

namespace app\api_test\controller;
//use think\Controller;
use think\Db;
use think\Config;
////整合腾讯云通信扩展
use tencent_cloud\TimChat;
//
////使用redis扩展
//use think\cache\driver\Redis;
use app\api_test\controller\Live;
//整合七牛直播sdk
use pili_test\Rtmp;
use pili_test\Lianmai;
/**
 * liveMobile: 手机端推流直播
 */
class LiveMobile  extends Base
{

    /*
     * 功能: 获取手机端直播流
     * 请求: $mobile 用户名,room_name 直播标题
     * 返回:
     * */
    public function getStream($mobile='',$room_name='',$address='')
    {

        //获取用户信息
        $chat_id = null;$room_id=null;$live_id=null;$classify_id=null;
        if($mobile && $room_name)
        {
            //查询房间名是否存在
//            $isRoom = Db::name('live')->field('id')->where("title='$room_name'")->limit(1)->select();
//            if($isRoom)
//            {                                        0
//                return $chat_id;
//            }

            //获取直播用户信息
            $user_info = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,ims_fans fans')->field('mem.realname,mem.mobile,ibb.location_p,fans.avatar,ibb.id')->where("mem.storeid=ibb.id and fans.id_member=mem.id and mem.mobile='$mobile' ")->limit(1)->select();
            if($user_info)
            {
                $realname = $user_info[0]['realname']==''?'去哪美':$user_info[0]['realname'];
                $user_img = $user_info[0]['avatar'];
                $idstore = $user_info[0]['id']==''?0:$user_info[0]['id'];
                if(!@strstr($user_img,'http'))
                {
                    $user_img = config('qiniu_img_domain').'/20171115165410_7971.jpg';
                }
            }


            //获取流名
            $last_id = Db::name('live')->field('id')->order('id desc')->limit(1)->select();
            if($last_id)
            {
                $live_stream_name = 'live'.($last_id[0]['id']+1);
            }else
            {
                $live_stream_name = 'live1';
            }
            //获取推流地址
            $rtmp = new Rtmp($live_stream_name);
            $push_url = $rtmp->getRtmpUrl();
            $see_url = $rtmp->getRtmpPlay();
            $hls_url = $rtmp->getHlsPlay();
            $screen_shot = $rtmp->getScreenShot();
            //创建聊天室
            $chat_id = creChatRoom();
            //直播商品分类id
            $classify_id = '[17]';
            // start Modify by wangqin 2018-02-05 学院手机端直播的时候,所有用户都能看到
            if($idstore=='1198')
            {
                $idstore = 0;
            }
            // end Modify by wangqin 2018-02-05
            // start Modify by wangqin 2018-03-02 取消手机端直播直播间商品推荐
            $classify_id = '';
            // start Modify by wangqin 2018-02-02
            //入库
            $data = array('user_name'=>$realname,'user_img'=>$user_img,'title'=>$room_name,'address'=>$address,'push_url'=>$push_url,'see_url'=>$see_url,'hls_url'=>$hls_url,'user_id'=>$mobile,'insert_time'=>time(),'live_stream_name'=>$live_stream_name,'live_img'=>$screen_shot,'chat_id'=>$chat_id,'category_id'=>6,'live_source'=>2,'idstore'=>$idstore,'classify_id'=>$classify_id);
            $res = Db::name('live')->insert($data);
            $live_id = Db::name('live')->getLastInsID();;
            if($res)
            {
                //创建连麦房间
                $lianmai = new Lianmai();
                $room_id = $lianmai->creRoom($mobile,$room_name,$live_stream_name);
//              $this->ret = array('push_url'=>$push_url,'see_url'=>$see_url,'chat_id'=>$chat_id);
            }
        }
        return $chat_id.'@'.$room_id.'@'.$live_id.'@'.$classify_id;
    }

    /*
     * 功能: 获取聊天室id
     * 请求: $mobile 用户名,title 直播标题
     * 返回: $chat_id 聊天室id
     * */
     public function getChatId()
     {
         //获取请求数据
         $mobile = input('mobile')==''?'':input('mobile');
         $room_name = input('room_name')==''?'':input('room_name');
         $address = input('address')==''?'':input('address');
         $chat_id=null;$room_id=null;$live_id=null;$classify_id=null;
         //删除多余不用的聊天室 del_flag=1
         $this->destroy_group();
         $rest = $this->getStream($mobile,$room_name,$address);
         if($rest)
         {
             $rest = explode('@',$rest);
             $chat_id = $rest[0];
             $room_id = $rest[1];
             $live_id = $rest[2];
             $classify_id = $rest[3];
         }
         //设置内容长度


//         return $chat_id;
         $code = 1;$msg='获取成功';$data=null;
         if(!$chat_id)
         {
             $code = 0;
             $msg = '获取失败';
         }else
         {
             $data = array('chat_id'=>$chat_id,'room_id'=>$room_id,'live_id'=>$live_id,'classify_id'=>$classify_id);
         }
//         $strlen = strlen(($code.$data.$msg));
//         header('Content-Length:'.strlen($chat_id));
//         parent::logApiRest('获取聊天室id',$_SERVER['HTTP_HOST'].'/api/live_mobile/getChatId',http_build_query(input()),json_encode($data));
         $reqStr = input();$uri = '/api/live_mobile/getChatId';
         unset($reqStr[$uri]);
//         parent::logApiRest('获取聊天室id',$_SERVER['HTTP_HOST'].$uri,http_build_query($reqStr),json_encode($data));
         parent::logApiRest('获取聊天室id',$uri,$reqStr,$data);
         return $this->returnMsg($code,$data,$msg);
     }

    /*
     * 功能: 获取连麦房间Token
     * 请求: $chat_id 聊天室id
     * 返回: 聊天室id
     * */
    public function getRoomToken()
    {
        //获取请求数据
        $room_token=null;
//        $chat_id = input('chat_id')==''?'':input('chat_id');
//        $room_name = input('room_name')==''?'':input('room_name');
        $mobile = input('mobile')==''?'':input('mobile');
        $room_id = input('room_id')==''?'':input('room_id');
        //token设置为6小时
        $res = Db::name('room r,think_live l')->field('room_token,cre_time,room_name,r.mobile,r.id')->where("r.live_name=l.live_stream_name and r.id=$room_id")->order('l.id desc')->limit(1)->select();
        //超过3小时,重新获取token
        $dt = time();$dt1 = strtotime(@$res[0]['cre_time'])+1800;
        $room_token =  @$res[0]['room_token'];
        $code = 1;$msg='获取成功';$data=null;
        if(($dt>$dt1) || !$room_token)
        {
            $lianmai = new Lianmai();
//            echo '<pre>';print_r($res);exit;
            $room_token =  $lianmai->roomToken($room_id,$res[0]['mobile']);
            $data = array('r.room_token'=>$room_token,'r.cre_time'=>date('Y-m-d H:i:s'));
            Db::name('room r,think_live l')->where("r.live_name=l.live_stream_name and r.id=$room_id")->update($data);
        }
        if(!$room_token)
        {
            $code = 0;$msg='获取失败';
        }else
        {
            $data = array('room_token'=>$room_token);
        }
        //设置返回数据格式,api模块下默认为json
//        Config::set('default_return_type','html');
        //设置内容长度
//        header('Content-Length:'.strlen($room_token));
//        return $room_token;
        $reqStr = input();$uri = '/api/live_mobile/getRoomToken';
        unset($reqStr[$uri]);
//        parent::logApiRest('获取连麦房间Token',$_SERVER['HTTP_HOST'].$uri,http_build_query($reqStr),json_encode($data));
        parent::logApiRest('获取连麦房间Token',$uri,$reqStr,$data);
        return $this->returnMsg($code,$data,$msg);
    }

    // start Modify by wangqin 2017-12-13
    public function returnMsg($code='1',$data=array(),$msg='获取成功')
    {
        $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($arr);
    }
    // end Modify by wangqin 2017-12-13

    //start Modify by wangqin 2017-12-19 解散聊天室
    /*
     * 功能: 解散聊天室
     * 请求: $chat_id
     * 返回:
     * */
    public function destroy_group()
    {
        $code=0;$msg='删除聊天室失败';$res=array();$flag=0;
        //每次调用删除的个数
        $del_num = 10;
        $tent = new TimChat();
        //获取聊天室个数
        $res2 = $tent->getChatNum();
        if($res2)
        {
            $res2 = json_decode($res2);
            $num = $res2->TotalCount;
            //超过280个,删除不用的聊天室和直播间
            if($num > 280)
            {
                $res1 = Db::name('live')->field('chat_id,id')->where('del_flag=1')->order('insert_time asc')->limit($del_num)->select();
                if($res1)
                {
                    foreach($res1 as $v1)
                    {
                        //删除聊天室和直播记录
                        Db::name('live')->where('id='.$v1['id'])->delete();
                        $tent->destroyGroup($v1['chat_id']) ;
                        $flag=1;$res[] = $v1['chat_id'];
                    }
                }

            }
        }

        if($flag == 1)
        {
            $code=1;$msg='删除聊天室成功';
        }
//        $reqStr = input();$uri = '/api/live_mobile/destroy_group';
//        unset($reqStr[$uri]);
//        parent::logApiRest('删除聊天室',$_SERVER['HTTP_HOST'].$uri,http_build_query($reqStr),json_encode($res));
        return $this->returnMsg($code,$res,$msg);
    }
    //end Modify by wangqin 2017-12-19

    /*
     * 功能: 获取推流地址
     * 请求: $chat_id 聊天室id
     * 返回: $push_url  推流地址
     * */
    public function getPushUrl()
    {
        //获取请求数据
        $mobile = input('mobile')==''?'':input('mobile');
        $room_id = input('room_id')==''?'':input('room_id');
        //初始化数据
        $code = 1;$msg='获取成功';$data=null;$push_url=null;
        //查询是否有缓存,有 去缓存,没有 查数据库
        $key = 'getPushUrl_'.$room_id;
        $ret = parent::getRedisP($key) ;
        if($ret)
        {
            $data = array('push_url'=>$ret);
        }else
        {
            $res = Db::name('live l,think_room r')->field('push_url')->where("r.id=$room_id and l.live_stream_name=r.live_name")->order('l.id desc')->limit(1)->select();

            if($res)
            {
                $push_url = $res[0]['push_url'];
                $data = array('push_url'=>$push_url);
            }else
            {
                $code = 0;$msg='获取失败';
            }

            $reqStr = input();$uri = '/api/live_mobile/getPushUrl';
            unset($reqStr[$uri]);
            parent::logApiRest('获取推流地址',$uri,$reqStr,$data);
            $this->setRedisP($key,$push_url);
        }
        $ret = $this->returnMsg($code,$data,$msg);
        // start Modify by wangqin 2018-03-19
        // 手机端推流成功,添加积分
        $res1 = Db::table('ims_bj_shopn_member')->field('id')->where('mobile',$mobile)->limit(1)->select();
        $fc = new FindContent();
        $fc->upd_scores(['user_id'=>$res1[0]['id'],'type'=>'live']);
        // end Modify by wangqin 2018-03-19
        //设置缓存
        return $ret;
    }

    /*
     * 功能: 获取观看地址
     * 请求: $chat_id 聊天室id
     * 返回: 聊天室id
     * */
    public function getSeeUrl()
    {
        //获取请求数据
        $mobile = input('mobile')==''?'':input('mobile');
        $room_id = input('room_id')==''?'':input('room_id');
        //初始化数据
        $see_url=null; $code = 1;$msg='获取成功';$data=null;
        //查询是否有缓存,有 去缓存,没有 查数据库
        $key = 'getSeeUrl_'.$room_id;
        $ret = parent::getRedisP($key) ;
        if($ret)
        {
            $data = array('see_url'=>$ret);
        }else
        {
            $res = Db::name('live l,think_room r')->field('see_url')->where("r.id=$room_id and l.live_stream_name=r.live_name")->order('l.id desc')->limit(1)->select();

            if($res)
            {
                $see_url = $res[0]['see_url'];
                $data = array('see_url'=>$see_url);
            }else
            {
                $code = 0;$msg='获取失败';
            }
            $reqStr = input();$uri = '/api/live_mobile/getSeeUrl';
            unset($reqStr[$uri]);
            parent::logApiRest('获取观看地址',$uri,$reqStr,$data);
            $this->setRedisP($key,$see_url);
        }

        return $this->returnMsg($code,$data,$msg);
    }

    /*
     * 功能: 直播状态查询
     * 请求: 直播间id
     * 返回:
     * */
    public function get_live_statu()
    {
        //获取请求数据
        $live_id = input('live_id')==''?'':input('live_id');
        //初始化数据
        $code = 1;$msg='获取失败';$data=null; $flag=0;
        //查询直播状态
        $res = Db::name('live')->field('id')->where('statu=1 and id='.$live_id)->limit(1)->count();
        if($res)
        {
            $flag = 1;
        }else
        {
            //查询回调日志
            $stream_name = 'live'.$live_id;
            $res1 = Db::name('callback_record')->field('end_time')->where("stream_name='$stream_name'")->limit(1)->select();
            if($res1)
            {
                $end_time = $res1[0]['end_time'];
                if($end_time>'0000-00-00 00:00:00')
                {
                    $flag = 1;
                }
            }
        }

        if($flag == 1)
        {
            $msg='直播已关闭';$data['statu']=2;
        }else
        {
            $msg='直播未关闭';$data['statu']=1;
        }
        return $this->returnMsg($code,$data,$msg);
    }

    // start Modify by wangqin 2018-03-02
    /*
     * 功能:上传直播封面
     * 请求: img,图片请求字段
     * */
    public function img_upload(){
        //请求数据
        $live_id = input('live_id','');$pic_url = input('pic_url','');
        //初始化数据
        $code = 1;$msg='上传成功';$data=[];
        if($live_id && $pic_url)
        {
            $data['live_id'] = $live_id;
            //更新手机端直播封面
            $data_v = array('live_img'=>$pic_url);
            $rest = Db::name('live')->where('id',$live_id)->update($data_v);
        }else
        {
            $code = 0;$msg='上传失败';$data['live_id']=0;
        }
        return $this->returnMsg($code,$data,$msg);
    }
    // end Modify by wangqin 2018-03-02

}