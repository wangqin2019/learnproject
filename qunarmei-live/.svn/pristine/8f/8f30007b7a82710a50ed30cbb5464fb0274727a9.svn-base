<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:41
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\NoticeService;

class Notice extends Common
{
    // 公告服务类
    protected $nocticeSer;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->nocticeSer = new NoticeService();
    }
    /**
     * 获取公告
     * @param string $type 类型,1:注册,2:登录,3:个人中心
     * @param int $user_id
     * @param int $ver 公告版本id
     */
    public function get_notice()
    {
        $type = input('type');
        $user_id = input('user_id');
        $ver = input('ver',0);
        $res = $this->nocticeSer->getNotice($type,$user_id,$ver);
        $this->rest['code'] = 1;
        if($res){
            $this->rest['msg'] = '获取成功';
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsgNoTrans($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 同意公告
     * @param int $user_id 用户id
     * @param int $ver 公告版本id
     */
    public function save_user_notice()
    {
        $user_id = input('user_id');
        $ver = input('ver');
        $res = $this->nocticeSer->saveNotice($user_id , $ver );
        if($res){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '保存成功';
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '保存失败';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}