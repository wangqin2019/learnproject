<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:46
 */

namespace app\api\service;
/**
 * 直播日志服务类
 */
class LiveLogService
{
    // 主播直播日志模型
    protected $liveLog;
    // 用户开关配置模型
    protected $memSwitch;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        $this->memSwitch = new \app\api\model\MemberSwitch();
        $this->liveLog = new \app\api\model\LiveZbLog();
    }
            /*0>当前时间
1>用户app版本
2>用户手机号
3>用户系统信息版本
4>用户手机型号
5>当前网络码率信息、音频帧、视频帧
6>app内存信息（总内存、APP当前占用、系统可用内存）？需要技术验证*/
    /**
     * 记录主播直播日志信息
     * @param string $data 原日志总信息
     * @param array $data json解析后的日志总信息
     */
    public function logCollect($data,$data1)
    {
        $dataall = [];
        $arr['type'] = isset($data1['type'])?$data1['type']:'';
        $arr['app_ver'] = $data1['app_ver'];
        $arr['mobile'] = $data1['mobile'];
        $arr['sys_info'] = $data1['sys_info'];
        $arr['mobile_model'] = $data1['mobile_model'];
        $arr['create_time'] = time();
        // 下发直播信息
        foreach ($data1['live_data'] as $k => $v) {
            $arr['log_time'] = $v['log_time'];
            $arr['net_rate'] = $v['net_rate'];
            $arr['audio_rate'] = $v['audio_rate'];
            $arr['video_rate'] = $v['video_rate'];
            // memory
            $arr['sum_memory'] = $v['sum_memory'];
            $arr['app_memory'] = $v['app_memory'];
            $arr['sys_enable_memory'] = $v['sys_enable_memory'];
            $arr['data'] = $data;
            $dataall[] = $arr;
        }

        // 插入
        $res = $this->liveLog->insertAll($dataall);
        return $res;
    }
    /**
     * 主播直播日志开关配置
     * @param int $mobile 用户号码
     * @return [int] [$flag 0:关闭,1:开关]
     */
    public function getLogSwitch($mobile)
    {
        // 默认关闭
        $flag = 0;

        $map['mobile'] = $mobile;
        $map['type'] = 1;
        $map['delete_time'] = 0;
        $map['flag'] = 1;
        $res = $this->memSwitch->where($map)->limit(1)->find();
        if ($res) {
            $flag = 1;
        }
        $arr['flag'] = $flag;
        return $arr;
    }
}