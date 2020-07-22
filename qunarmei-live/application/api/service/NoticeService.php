<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:46
 */

namespace app\api\service;


class NoticeService
{
    // 用户日志模型
    protected $userLogMod;
    // 公告版本
    protected $ver;
    // 公告标题
    protected $title;
    // 公告内容第1部分
    protected $content1;
    // 公告内容第2部分
    protected $content2;
    // 公告内容对应的url
    protected $agreement;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        $this->userLogMod = new \app\api\model\UserLogMod();
        $this->ver = config('text.notice')['ver'];
        $this->title = config('text.notice')['title'];
        $this->title1 = config('text.notice')['title1'];
        $this->content1 = config('text.notice')['content1'];
        $this->content2 = config('text.notice')['content2'];
        $this->content3 = config('text.notice')['content3'];
        $this->agreement = config('text.notice')['agreement'];
    }
    /**
     * 获取公告
     * @param string $type 类型,1:注册,2:登录,3:个人中心
     * @param int $user_id 用户id
     * @param int $ver 公告版本
     */
    public function getNotice($type , $user_id=0 , $ver=0)
    {
        $data = [
            'ver' => $this->ver,
            'title' => $this->title,
            'content1' => $this->content1,
            'content2' => $this->content2,
            'content3' => $this->content3,
            'agreement' => $this->agreement
        ];
        if($type == 2){
            $data['title'] = $this->title1;
            // 查询是否同意弹过,同意弹过则不弹
            $map['user_id'] = $user_id;
            $map['type'] = 2;
            $map['val'] = '1';
            $resu = $this->userLogMod->where($map)->limit(1)->find();
            if($resu){
                $data = [];
            }
        }else{
            // 注册弹3个
            $data['content1'] = config('text.notice')['content4'];
            $data['content2'] = '';
            $data['content3'] = config('text.notice')['content5'];
        }
        return $data;
    }
    /**
     * 同意公告
     * @param int $user_id 用户id
     * @param int $ver 公告版本
     */
    public function saveNotice($user_id , $ver = 1)
    {
        $flag = 0;
        $data = [
            'type' => 2,
            'ver' => $ver,
            'user_id' => $user_id,
            'val' => 1,
            'msg' => '用户'.$user_id.'同意了去哪美app隐私协议',
            'create_time' => time(),
        ];
        $res = $this->userLogMod->save($data);
        if($res){
            $flag = 1;
        }
        return $flag;
    }
}