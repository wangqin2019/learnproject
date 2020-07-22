<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:41
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\LiveLogService;

/**
 * 直播性能相关日志
 */
class LiveLog extends Common
{
    // 服务类
    protected $liveSer;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->liveSer = new LiveLogService();
    }
    
    /**
     * 记录主播直播日志开关
     * @param string $mobile 用户手机号码
     */
    public function log_switch()
    {
        $mobile = input('mobile');
        
        $res = $this->liveSer->getLogSwitch($mobile);
        $this->rest['code'] = 1;
        $this->rest['msg'] = '获取成功';
        $this->rest['data'] = $res;

        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 记录主播直播日志信息
     * @param string $data 日志总信息[json字符串]
     */
    public function log_collect()
    {
        $data = input('data');
        $data1 = json_decode($data,true);
        // var_dump($data);var_dump($data1);die;
        if ($data && $data1) {
            $res = $this->liveSer->logCollect($data,$data1);
            $this->rest['code'] = 1;
            $this->rest['msg'] = '上传成功';
            $this->rest['data'] = [];
        }else{
            $this->rest['code'] = 0;
            $this->rest['msg'] = '请求参数不能为空';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}