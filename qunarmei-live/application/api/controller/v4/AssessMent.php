<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/7/10
 * Time: 17:19
 */

namespace app\api\controller\v4;

use app\api\controller\v3\Common;
use app\api\service\AssessMentService;

/**
 * 直播考核api
 */
class AssessMent extends Common
{
    protected $assessSer; // 直播考核服务类
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->assessSer = new AssessMentService();
    }
    /**
     * 考核列表
     * @param int $mobile 号码
     * @param string $serach 搜索
     * @param int $page 分页
     * @return string json
     */
    public function assess_list()
    {
        $mobile = input('mobile');
        $serach = input('serach');
        $page = input('page', 1);

        $res = $this->assessSer->getAssessList($mobile, $serach, $page);

        return $this->returnMsg($res['code'], $res['data'], $res['msg']);
    }

    /**
     * 考核详情
     * @param int $assess_id 考核id
     * @return string json
     */
    public function assess_detail()
    {
        $assess_id = input('assess_id');

        $res = $this->assessSer->assessDetail($assess_id);

        return $this->returnMsg($res['code'], $res['data'], $res['msg']);
    }

    /**
     * 考核详情-提交录像
     * @param int $assess_id 考核详情id
     * @param int $live_id 直播间id
     * @return string json
     */
    public function submit_video()
    {
        $assess_id = input('assess_id');
        $live_id = input('live_id');

        $res = $this->assessSer->submitVideo($assess_id, $live_id);
        if ($res['code'] == 0) {
            $this->returnMsgError($res['msg']);
        }
        return $this->returnMsg($res['code'], $res['data'], $res['msg']);
    }

    /**
     * 考生列表
     * @param int $assess_id 考核列表id
     * @param string $mobile 考官号码
     * @param int $page 当前页
     * @return string json
     */
    public function examinee_list()
    {
        $assess_id = input('assess_id');
        $mobile = input('mobile');
        $page = input('page', 1);
        $res = $this->assessSer->examineeList($assess_id, $mobile, $page);

        return $this->returnMsg($res['code'], $res['data'], $res['msg']);
    }

    /**
     * 考生录像详情
     * @param int $assess_id 考生列表id
     * @param string $mobile 考官号码
     * @return string json
     */
    public function examinee_detail()
    {
        $assess_id = input('assess_id');
        $mobile = input('mobile');
        $res = $this->assessSer->examineeDetail($assess_id, $mobile);
        if (empty($res['data'])) {
            $res['data'] = (object) [];
        }
        return $this->returnMsg($res['code'], $res['data'], $res['msg']);
    }

    /**
     * 提交分数
     * @param string $mobile 打分人号码
     * @param int $assess_id 考生列表id
     * @param int $score 分数
     * @return string json
     */
    public function submit_score()
    {
        $assess_id = input('assess_id');
        $score = input('score');
        $mobile = input('mobile');

        $res = $this->assessSer->submitScore($assess_id, $score, $mobile);
        //        if($res['code'] == 0){
        //            $this->returnMsgError($res['msg']);
        //        }
        return $this->returnMsg($res['code'], $res['data'], $res['msg']);
    }
}
