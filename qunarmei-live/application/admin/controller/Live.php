<?php

namespace app\admin\controller;
use app\admin\model\LiveModel;
use think\Db;
//整合七牛直播sdk
use pili_test\Rtmp;

class Live extends Base
{
    //调用直播相关接口url
    protected $url = 'http://localhost/pili_test/rtmp_test.php';

    /**
     * [liveList 直播列表]
     * @return
     * @author
     */
    public function liveList(){
        // 请求调用接口数据
        // $url = 'https://wms.chengmei.com/api/stock/getBranchStock';
        // $url = 'http://localhost/Test/tt.php';
        // $data = array('allshuxing'=>'NW3700B3L','bid'=>'666-666','type'=>1);
        // $rest = http($url,$data, 'GET', array("Content-type: text/html; charset=utf-8"));
        // $rest = http($url,$data, 'POST', array("Content-type: text/html; charset=utf-8"));
        // $rest = curlPost($url,$data);
        // echo 'rest:'.$rest;

        //获取session信息
        // print_r($_SESSION);

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
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
        $lists = Db::name('live')->alias('l')->join('admin ad','ad.id=l.user_id')->where($map)->limit($pre,$limits)->field('l.*,ad.username')->select();
        foreach($lists as $k=>&$v)
        {
            $cat_name = $this->catList($v['category_id']);
            $v['cat_name'] = $cat_name[0]['cat_name'];
            //  $lists[$k]['create_time']=date('Y-m-d H:i:s',(int)$v['create_time']);
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
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

//            $v['audience'] = $this->getNum($type='see',$v['id']);
            $v['audience'] = $this->getChatCnt($v['chat_id']);
            // start Modify by wangqin 2017-11-04 点赞人数
//            $v['point_count'] = $this->getNum($type='point',$v['id']);
            // end Modify by wangqin 2017-11-04
            $classify_id = json_decode($v['classify_id']) ;
            $v['zhititle'] = '';
            if($classify_id)
            {
                $zhititle = '';
                foreach ($classify_id as $v2) {
                   $zhiti = Db::table('ims_bj_shopn_category')->field('name,id')->where('id='.$v2)->limit(1)->select();
                   $zhititle = $zhiti[0]['name'].' '.$zhititle;
                }
                $v['zhititle'] = $zhititle;
            }

            if($v['db_statu'] == 0)
            {
                $v['db_statu']='直播';
            }else
            {
                $v['db_statu']='点播';
            }
            $v['db_length'] = $v['db_length']==''?'':$v['db_length'];
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
                //流名:live+id
                $last_id = Db::name('live')->field('id')->order('id desc')->limit(1)->select();
                if($last_id)
                {
                    $live_stream_name = 'live'.($last_id[0]['id']+1);
                }else
                {
                    $live_stream_name = 'live1';
                }
                //通过七牛接口获取推流地址
                $rtmp = new Rtmp($live_stream_name);
                $push_url = $rtmp->getRtmpUrl();
                $see_url = $rtmp->getRtmpPlay();
                $hls_url = $rtmp->getHlsPlay();
                $screen_shot = $rtmp->getScreenShot();

                //获取用户信息
                $uid = $_SESSION['think']['uid'];
                $classify_id = $param['classify_id']==''?'':$param['classify_id'];
                if($classify_id)
                {
                    $classify_id =  json_encode($classify_id);
                }
                $data = array('title'=>$param['title'],'push_url'=>$push_url,'see_url'=>$see_url,'hls_url'=>$hls_url,'insert_time'=>time(),'user_id'=>$uid,'content'=>$param['content'],'address'=>$param['address'],'live_stream_name'=>$live_stream_name,'user_name'=>$param['user_name'],'user_img'=>$param['user_img'],'live_img'=>$screen_shot,'category_id'=>$param['category']);
                //绑定聊天室
                $rest1 = $this->chatChange($type='bd');
                $data['chat_id'] = $rest1;
                if($uid == 1)
                {
                    $data['see_count_times'] = $param['see_count_times'];
                    $data['db_statu'] = $param['db_statu'];
                    $data['db_length'] = $param['db_length'];
                }
                if($classify_id)
                {
                    $data['classify_id'] = $classify_id;
                }
                $rest = Db::name('live')->insert($data);
                if($rest)
                {
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
        $zhibo_cat =  json_decode($zhibo_cat[0]['id']);
        if(request()->isAjax()){

            $param = input('post.');
            $classify_id = $param['classify_id']==''?'':$param['classify_id'];
            if(@$classify_id)
            {
                $classify_id =  json_encode($classify_id);
            }
            $data_v = array('title' => $param['title'],'user_name' => $param['user_name'],'user_img' => $param['user_img'],'content' => $param['content'],'category_id' => $param['category']);
            $uid = $_SESSION['think']['uid'];
            if($uid == 1)
            {
                $data_v['see_count_times'] = $param['see_count_times'];
                $data_v['db_statu'] = $param['db_statu'];
                $data_v['db_length'] = $param['db_length'];
            }
            if($classify_id)
            {
                $data_v['classify_id'] = $classify_id;
            }
            $ret = Db::name('live')->where('id='.$id)->update($data_v);
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
                $rest1 = $this->chatChange($type='jb',$id);
                //修改直播状态
                $rest = Db::name('live')->where('id',$id)->update(['statu'=>2]);
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
            $ring_letter = new RingLetter();
            $fromer = '15821462605';//获取聊天室创建者号码
            //过滤多余的空格和换行符
            $search = array(" ","　","\n","\r","\t");
            $replace = array("","","","","");
            $msg = str_replace($search, $replace, $msg);
            //字幕是否滚动,0=>不滚,1=>滚动
            $intscroll = input('intscroll');
            $resp = $ring_letter->sendMsg($chat_id,$msg,$fromer,$id,$intscroll);
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
            $ring_letter = new RingLetter();
            $fromer = 'test';//获取聊天室创建者号码
            //过滤多余的空格和换行符
            $search = array(" ","　","\n","\r","\t");
            $replace = array("","","","","");
            $msg = str_replace($search, $replace, $msg);

            $intscroll = input('intscroll');
            $resp = $ring_letter->sendMsg($chat_id,$msg,$fromer,$id,'ext');
            return 1;
        }else{
            //显示界面
            $list = Db::name('live')->field('id,chat_id,title')->where("id=$id")->select();
            $this->assign('list',$list);
            return $this->fetch();
        }

    }
}