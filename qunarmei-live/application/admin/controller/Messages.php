<?php

namespace app\admin\controller;
use app\admin\model\LiveModel;
use think\Db;

class Messages extends Base
{

    /**
     * [index 推送消息列表]
     * @return [type] [description]
     * @author
     */
    public function index(){
        // 请求接口调用数据
        // $url = 'https://wms.chengmei.com/api/stock/getBranchStock';
        // $data = array('allshuxing'=>'NW3700B3L','bid'=>'666-666','type'=>1);
        // $rest = http($url,$data, 'GET', array("Content-type: text/html; charset=utf-8"));
        // echo 'rest:'.$rest;

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['message_msg'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('message')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::name('message')->where($map)->limit($pre,$limits)->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if($lists)
        {
            foreach ($lists as $k => &$v) {
                $v['logtime'] = date('Y-m-d H:i:s',$v['logtime']);
                if($v['message_type'] == 4)
                {
                    $v['msg_type'] = '普通文本';
                }elseif($v['message_type'] == 5)
                {
                    $v['msg_type'] = '富文本';
                }

                if($v['message_push_type'] == 'tag')
                {
                    $v['msg_push_type'] = '群推';
                }elseif($v['message_push_type'] == 'alias')
                {
                    $v['msg_push_type'] = '单推';
                }elseif($v['message_push_type'] == 'all')
                {
                    $v['msg_push_type'] = '所有人';
                }

                if($v['message_target'] == 'qunarmei0')
                {
                    $v['message_target'] = '店老板';
                }elseif($v['message_target'] == 'qunarmei1')
                {
                    $v['message_target'] = '美容师';
                }elseif($v['message_target'] == 'qunarmei2')
                {
                    $v['message_target'] = '普通顾客';
                }elseif($v['message_target'] == 'all')
                {
                    $v['message_target'] = '所有人';
                }
            }
        }
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [userAdd 添加直播]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function messagesAdd()
    {
        if(request()->isAjax()){

            $param = input('post.');
            if($param)
            {
                $data = array('message_type'=>$param['message_type'],'message_push_type'=>$param['message_push_type'],'message_target'=>$param['message_target'],'message_content'=>$param['message_content'],'message_html'=>$param['message_content'],'logtime'=>time());
                $rest = Db::name('message')->insert($data);
                return $this->returnMsg(1,'','添加成功');
            }

        }

        return $this->fetch();
    }


    /**
     * [userEdit 编辑用户]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function messagesEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){

            $param = input('post.');
            $ret = Db::name('message')->where('id', $id)->update(['message_type' => $param['message_type'],'message_push_type' => $param['message_push_type'],'message_target' => $param['message_target'],'message_content' => $param['message_content'],'message_html' => $param['message_content']]);
            return $this->returnMsg(1,'','修改成功');
        }
        $list = Db::name('message')->where(array('id'=>$id))->select();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [UserDel 删除用户]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function messagesDel()
    {
        $id = input('param.id');
        $rest = Db::name('message')->where('id',$id)->delete();
        return $this->returnMsg(1,'','删除成功');
    }



    /**
     * 消息推送
     * @return [type] [description]
     * @author
     */
    public function messagesSend()
    {
        $id = input('param.id');

        $rest = Db::name('message')->where('id',$id)->select();
        $data = array('type'=>$rest[0]['message_type'],'pushtype'=>$rest[0]['message_push_type'],'target'=>$rest[0]['message_target'],'content'=>$rest[0]['message_content']);
        $url = 'https://api-app.qunarmei.com/qunamei/pushmessage';
        $ret = curl_post($url,$data);
        //记录日志表
        $data_v = array('url'=>$url,'request_paras'=>json_encode($data),'respon_paras'=>$ret,'log_time'=>date('Y-m-d H:i:s',time()));
        $rest = Db::name('query_log')->insert($data_v);
        //调接口
        return $this->returnMsg(1,'','推送成功');
    }

    public function returnMsg($code=1,$data='',$msg='')
    {
       $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
       return json($ret);
    }

    /**
     * [index 已推送消息列表]
     * @return [type] [description]
     * @author
     */
    public function ypush(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['content'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('ims_bj_shopn_message')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
//        $lists = Db::table('ims_bj_shopn_message msg,ims_bj_shopn_member mem')->field('msg.*,mem.realname')->where("msg.content like '%$key%' and msg.id_user=mem.id ")->order('msg.id desc')->limit($pre,$limits)->select();
        $sql = "select msg.*,mem.realname from ims_bj_shopn_message msg LEFT JOIN ims_bj_shopn_member mem on msg.id_user=mem.id WHERE msg.content like '%$key%' ORDER BY msg.id desc limit $pre,$limits";
        $lists = Db::query($sql);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);

        foreach ($lists as $k => &$v) {
            //消息类型
            if($v['type'] == 1)
            {
                $v['type'] = '订单消息';
            }elseif($v['type'] == 2)
            {
                $v['type'] = '会员注册';
            }elseif($v['type'] == 3)
            {
                $v['type'] = '邀约码';
            }elseif($v['type'] == 4)
            {
                $v['type'] = '普通文本';
            }elseif($v['type'] == 5)
            {
                $v['type'] = '富文本';
            }
            // 消息状态
            if($v['status'] == 0)
            {
                $v['status'] = '未读';
            }elseif($v['status'] == 1)
            {
                $v['status'] = '已读';
            }
            //用户类型
            if($v['id_user'] == 0)
            {
                $v['realname']='所有人';
            }elseif($v['id_user'] == -1)
            {
                $v['realname'] = '店老板';
            }elseif($v['id_user'] == -2)
            {
                $v['realname'] = '美容师';
            }elseif($v['id_user'] == -3)
            {
                $v['realname'] = '普通顾客';
            }
        }

        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch('ypush_list');
    }

}