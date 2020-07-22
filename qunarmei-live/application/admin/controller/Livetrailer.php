<?php

namespace app\admin\controller;
use think\Db;

//使用redis扩展
use think\cache\driver\Redis;
use qiniu_transcoding\Upimg;

class Livetrailer extends Base
{


    /**
     * [index 直播预告列表]
     * @return 
     * @author 
     */
    public function index(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['title'] = ['like',"%" . $key . "%"];          
        }       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('live_trailer')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists = Db::name('live_trailer')->alias('l')->where($map)->limit($pre,$limits)->field('l.*')->select();

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
     * [livetrailerAdd 添加]
     * @return 
     * @author 
     */
    public function livetrailerAdd()
    {

        if(request()->isAjax()){
            $param = input('post.');
            if($param)
            {
                $data = array('user'=>$param['user'],'user_img'=>$param['user_img'],'address'=>$param['address'],'cover_img'=>$param['cover_img'],'cover_img_desc' => $param['cover_img_desc'],'title'=>$param['title'],'begin_time'=>$param['begin_time'],'log_time'=>date('Y-m-d H:i:s',time()));

                // start Modify by wangqin 2017-11-15 上传图片到七牛
                $upimg = new Upimg();
                //服务器
                $tx_path = '/home/canmay/www/live/public/uploads/face/';
                //本地
                //$tx_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\uploads\face/';
                $img_url = $upimg->upImg($tx_path.$data['user_img']);
                $data['user_img'] = $img_url;
                $img_url1 = $upimg->upImg($tx_path.$data['cover_img']);
                $data['cover_img'] = $img_url1;
                // end Modify by wangqin 2017-11-15
                $rest = Db::name('live_trailer')->insert($data);
                if($rest)
                {
                  $msg='添加成功';
                    //清除redis数据
                    $Redis = new Redis();
                    $Redis->rm('liveTrailer');
                }
                $flag = array('code'=>1,'data'=>$data,'msg'=>$msg);
                return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
            }
            
        }

        return $this->fetch();
    }


    /**
     * [livetrailerEdit 编辑]
     * @return 
     * @author 
     */
    public function livetrailerEdit()
    {
        $id = input('param.id');

        if(request()->isAjax()){
            $param = input('post.');
            // start Modify by wangqin 2017-11-15 上传图片到七牛

            $upimg = new Upimg();
            //服务器
            $tx_path = '/home/canmay/www/live/public/uploads/face/';
            //本地
            //$tx_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\uploads\face/';
            if(!strstr($param['user_img'],'http://'))
            {
                $img_url = $upimg->upImg($tx_path.$param['user_img']);
                $param['user_img'] = $img_url;
            }
            if(!strstr($param['cover_img'],'http://'))
            {
                $img_url1 = $upimg->upImg($tx_path.$param['cover_img']);
                $param['cover_img'] = $img_url1;
            }

            // end Modify by wangqin 2017-11-15
            $ret = Db::name('live_trailer')->where('id', $id)->update(['user' => $param['user'],'user_img' => $param['user_img'],'address' => $param['address'],'cover_img' => $param['cover_img'],'cover_img_desc' => $param['cover_img_desc'],'title' => $param['title'],'begin_time' => $param['begin_time']]);
            $flag = array('code'=>1,'data'=>'','msg'=>'修改成功');
            //清除redis数据
            $Redis = new Redis();
            $Redis->rm('liveTrailer');
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $list = Db::name('live_trailer')->where(array('id'=>$id))->select();
        $this->assign('list',$list);

        return $this->fetch();
    }


    /**
     * [livetrailerDel 删除]
     * @return 
     * @author 
     */
    public function livetrailerDel()
    {
        $id = input('param.id');
        $rest = Db::name('live_trailer')->where('id',$id)->delete();
        //清除redis数据
        $Redis = new Redis();
        $Redis->rm('liveTrailer');
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
            $chat_data = Db::name('chat')->field('chat_id')->where('flag=0')->limit(1)->select();
            if($chat_data)
            {
                $chat_id = $chat_data[0]['chat_id'];
                //修改是否绑定标记
                $rest1 = Db::name('chat')->where("chat_id=$chat_id")->update(array('flag'=>1,'log_time'=>date('Y-m-d H:i:s',time())));
                return $chat_id;
            }
        }else
        {
            //解绑 $id 直播间id
            $chat_id = Db::table('think_chat chat,think_live live')->where('chat.chat_id=live.chat_id and live.id='.$id)->update(array('chat.flag'=>0,'upd_time'=>date('Y-m-d H:i:s',time())));
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
            $resp = $ring_letter->sendMsg($chat_id,$msg,$fromer,$id);
//            var_dump($ring_letter);die();
            return json(['code' => 1, 'data' => '', 'msg' => '发送成功!']);
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
         $rest = Db::table('ims_bj_shopn_category')->field('id,name')->where($data)->select();
       }

       return $rest;
    }
}