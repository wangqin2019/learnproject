<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/3
 * Time: 10:53
 */

namespace app\api_public\controller;
use app\api_public\service\LiveService;

/**
 * 直播相关服务类
 */
class Live
{
    protected $code = 400;
    protected $msg = '获取失败';
    protected $data;
    /**
     * 下发统一数据
     * @param int $code 状态码,200:成功,400:失败
     * @param string $msg 提示信息
     * @param array $data 下发数据
     * @return string
     */
    public function returnMsg($code , $msg , $data=[])
    {
        $arr = [
            'code' => $code,
            'msg' => $msg
        ];
        if($code == 200){
            $arr['data'] = $data;
        }
        header('Content-type: application/json');
        $rest = json_encode($arr,JSON_UNESCAPED_UNICODE);
        return $rest;
    }

    /**
     * 获取直播间用户号码
     * @return string
     */
    public function getLiveMrs()
    {
        $liveser = new LiveService();
        $res = $liveser->mrsMobiles();

        $this->code = 200;
        $this->data = [];
        if($res){
            $this->msg = '获取成功';
            $this->data = $res;
        }else{
            $this->msg = '暂无数据';
        }
        return $this->returnMsg($this->code,$this->msg,$this->data);
    }

}