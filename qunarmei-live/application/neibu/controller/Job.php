<?php

namespace app\neibu\controller;
use think\Controller;
use app\neibu\service\JobService;
/**
 * 内部定时任务获取
 */
class Job extends Base
{
    /**
     * 每小时更新直播考核数据
     * @return
     */
    public function upd_assess()
    {
        $jobser = new JobService();
        $res = $jobser->updAssess();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 每10分钟查询七牛云直播流状态,更新数据库状态
     * @return
     */
    public function get_qiniu_live()
    {
        $jobser = new JobService();
        $res = $jobser->qiniuLive();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 每天定时删除过期有效券
     * @return
     */
    public function overdue_card()
    {
        $jobser = new JobService();
        $res = $jobser->overdueCard();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 定时同步用户进出聊天室记录到mysql
     * @return
     */
    public function user_log_to_mysql()
    {
        $jobser = new JobService();
        $res = $jobser->userLogToMysql();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
	/**
	 * 获取物流信息
	 * @return
	 */
    public function get_express()
    {
        $jobser = new JobService();
        $res = $jobser->getExpress();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 支付半小时后,为选择是否安心送的 is_axs=1的改为is_axs=2
     * @return
     */
    public function upd_axs()
    {
        $jobser = new JobService();
        $res = $jobser->updAxs();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 支付15分分钟没有进行相关操作时,会发送该条提示短信
     * @return
     */
    public function tip_buyter()
    {
        $jobser = new JobService();
        $res = $jobser->tipBuyter();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 每天执行1次定时任务
     * @return
     */
    public function day_sum()
    {
        $jobser = new JobService();
        $res = $jobser->daySum();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}