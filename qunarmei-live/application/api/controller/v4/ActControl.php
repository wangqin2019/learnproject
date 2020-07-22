<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/28
 * Time: 10:43
 */

namespace app\api\controller\v4;

use app\api\controller\v3\Common;
use app\api\service\ActControlService;

/**
 * 活动控制
 * Class ActControl
 * @package app\api\controller\v4
 */
class ActControl extends Common
{
    /**
     * 获取活动控制开关
     * @param int $user_id 用户id
     * @param string $type 活动入口id,多个以,分割
     * @return \think\response\Json
     */
    public function act_switch()
    {
        $arr['user_id'] = input('user_id',1);
        $arr['type'] = input('type',0);
        $res = [];
        $actser = new ActControlService();
        $res = $actser->actSwitch($arr);

        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
}