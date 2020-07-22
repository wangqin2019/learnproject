<?php

namespace app\api\controller;
//use think\Controller;
use think\Db;
use think\Config;
////整合腾讯云通信扩展
use tencent_cloud\TimChat;
//
////使用redis扩展
//use think\cache\driver\Redis;
use app\api\controller\Live;
//整合七牛直播sdk
use pili_test\Rtmp;
use pili_test\Lianmai;
/**
 * liveMobile: 手机端推流直播
 */
class LiveMobile  extends Base
{
    // 指定门店编号
    protected $signs = [
        '666-666',
        '888-888',
        '000-000'
    ];

    public function getQnRt()
    {
        $mobile = input('mobile');
        $room_name = input('room_name');

        $lianmai = new Lianmai();
        $room_token =  $lianmai->roomToken($room_name,$mobile);
        echo "roomToken:".$room_token;
    }
    /*
     * 功能: 获取手机端直播流
     * 请求: $mobile 用户名,room_name 直播标题,head_title头衔,$type 类型,0:普通,1:文案;$copyroom_id:文案id,$assess_id:考核详情id
     * 返回:
     * */
    public function getStream($mobile='',$room_name='',$address='',$head_title='',$type = 0,$copyroom_id = 0 ,$assess_id = 0)
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
            $user_info = Db::table('ims_bj_shopn_member mem')->join(['ims_bwk_branch'=>'ibb'],['mem.storeid=ibb.id'],'LEFT')->join(['ims_fans'=>'fans'],['fans.id_member=mem.id'],'LEFT')->field('mem.realname,mem.mobile,ibb.location_p,fans.avatar,ibb.id')->where("mem.mobile='$mobile' ")->limit(1)->select();
            $realname = '';
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
            if(isset($idstore) && $idstore=='1198')
            {
                $idstore = 0;
            }
            // end Modify by wangqin 2018-02-05
            // start Modify by wangqin 2018-03-02 取消手机端直播直播间商品推荐
            $classify_id = '';
            // start Modify by wangqin 2018-02-02
            //入库
            $data = array('user_name'=>$realname,'user_img'=>$user_img,'title'=>$room_name,'content'=>$room_name,'address'=>$address,'push_url'=>$push_url,'see_url'=>$see_url,'hls_url'=>$hls_url,'user_id'=>$mobile,'insert_time'=>time(),'live_stream_name'=>$live_stream_name,'live_img'=>$screen_shot,'chat_id'=>$chat_id,'category_id'=>6,'live_source'=>2,'idstore'=>$idstore,'classify_id'=>$classify_id,'type'=>$type,'copyroom_id'=>$copyroom_id ,'assess_user_id'=>$assess_id);
            // 查询门店在指定门店,则地址显示 诚美
            $mapb['m.mobile'] = $mobile;
            $resb = Db::table('ims_bwk_branch b')->join(['ims_bj_shopn_member'=>'m'],['m.storeid=b.id'],'LEFT')->where($mapb)->limit(1)->find();
            if ($resb) {
                if (in_array($resb['sign'], $this->signs)) {
                    $data['address'] = '诚美';
                }
            }
            if ($head_title) {
                $data['address'] = $head_title;
            }
            $res = Db::name('live')->insert($data);
            $live_id = Db::name('live')->getLastInsID();;

            // 手机端开直播生成直播分享二维码
            $live_ser = new \app\api\service\LiveMobileService();
            $qrcode = $live_ser->makeShareCode($idstore,$mobile,$live_id);
            if($qrcode){
                // 更新二维码
                $mapq['id'] = $live_id;
                $dataq['qrcode'] = $qrcode;
                Db::table('think_live')->where($mapq)->update($dataq);
            }

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
     * 请求: $mobile 用户名,title 直播标题,head_title 头衔,copyroom_id:文案id,type:直播类型0:普通,1:文案,assess_id:考核详情id
     * 返回: $chat_id 聊天室id
     * */
     public function getChatId()
     {
         //获取请求数据
         $assess_id = input('assess_id',0);
         $copyroom_id = input('copyroom_id',0);
         $type = input('type',0);
         $head_title = input('head_title','');
         $mobile = input('mobile')==''?'':input('mobile');
         $room_name = input('room_name')==''?'':input('room_name');
         $address = input('address')==''?'':input('address');
         $chat_id=null;$room_id=null;$live_id=null;$classify_id=null;
         
         // 查询是否有还在直播中的直播,一个账号只容许一个直播
//         $maplive['user_id'] = $mobile;
//         $maplive['statu'] = 1;
//         $reslive = Db::table('think_live')->where($maplive)->limit(1)->find();
//         if ($reslive) {
//            $code = 0;
//            $msg = '创建失败,当前账号有1个直播正在进行中';
//            $data = (object)[];
//            return $this->returnMsg($code,$data,$msg);
//         }

         // 查询主播账号日志记录是否开启,没有的插入
         $mapsw['mobile'] = $mobile;
         $mapsw['type'] = 1;
         $res_switch = Db::table('ims_bj_shopn_member_switch')->where($mapsw)->limit(1)->find();
         if ($res_switch) {
             if ($res_switch['flag'] == 0) {
                $mapsw1['id'] = $res_switch['id'];
                $datasw['flag'] = 1;
                $datasw['update_time'] = time();
                $datasw['delete_time'] = 0;
                Db::table('ims_bj_shopn_member_switch')->where($mapsw1)->update($datasw);
             }
         }else{
            $datasw['mobile'] = $mobile;
            $datasw['flag'] = 1;
            $datasw['type_remark'] = '主播日志记录开关';
            $datasw['create_time'] = time();
            Db::table('ims_bj_shopn_member_switch')->insert($datasw);
         }

         //删除多余不用的聊天室 del_flag=1
         $this->destroy_group();
         $rest = $this->getStream($mobile,$room_name,$address,$head_title,$type,$copyroom_id,$assess_id);
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
        
        // 1.先查询号码和room_id是否存在roomToken,存在则取缓存,不存在则重新生成
        $code = 1;$msg='获取成功';$data=null;
        $key = 'room_token_'.$mobile.$room_id;
        $res_cache = $this->getRedisP($key);
        if ($res_cache) {
            $data = array('room_token'=>$res_cache);
            return $this->returnMsg($code,$data,$msg);
        }

        //token设置为6小时
        $dt = time();
        $lianmai = new Lianmai();
        // 根据room_id查询room_name
        $mapr['id'] = $room_id;
        $res_room = Db::table('think_room')->field('id,room_name,mobile')->where($mapr)->limit(1)->find();
        if ($res_room) {
            $room_token =  $lianmai->roomToken($res_room['room_name'],$mobile);
            $this->setRedisP($key,$room_token,1800);
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
        
        if(!($mobile && $room_id)){
            return $this->returnMsg(0,(object)[],'请求参数错误,不能为空!');
        }

        //初始化数据
        $see_url=null; $code = 1;$msg='获取成功';$data=null;
        //查询是否有缓存,有 去缓存,没有 查数据库
        $key = 'getSeeUrl_'.$room_id;
        // $ret = parent::getRedisP($key) ;
        $ret = '';
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

    /**
     * 获取连麦房间token
     * @param  [string] $mobile  [主播账号]
     * @param  [string] $room_id [连麦房间id]
     * @return [string]          [token数据]
     */
    public function getLianMaiToken($mobile,$room_id)
    {
        $room_token = '';
        // 获取缓存token
        $key = 'room_token_'.$mobile.$room_id;
        $room_token = $this->getRedisP($key);
        if ($room_token) {
            return $room_token;
        }

        $dt = time();
        $lianmai = new Lianmai();
        // 根据room_id查询room_name
        $mapr['id'] = $room_id;
        $res_room = Db::table('think_room')->field('id,room_name,mobile')->where($mapr)->limit(1)->find();
        if ($res_room) {
            $room_token =  $lianmai->roomToken($res_room['room_name'],$mobile);
            $this->setRedisP($key,$room_token,1800);
            $data = array('room_token'=>$room_token);
            Db::name('room')->where($mapr)->update($data);
        }
        return $room_token;
    }
}