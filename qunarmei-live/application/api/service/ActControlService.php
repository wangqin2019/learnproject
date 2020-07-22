<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/28
 * Time: 10:46
 */

namespace app\api\service;

/**
 * 活动控制相关服务类
 * Class ActControlService
 * @package app\api\service
 */
class ActControlService extends BaseSer
{
    /**
     * 活动开关控制
     * @param $arr [type:入口类型,user_id:用户id]
     * @return array
     */
    public function actSwitch($arr)
    {
        $this->code = 1;
        $actser = new ActSer();
        if(strstr($arr['type'],',')){
            $type = explode(',',$arr['type']);
        }else{
            $type[] = $arr['type'];
        }
        $map['type'] = ['in',$type];
        $res = $actser->getActSwitchs($map);
        if($res){
            foreach ($res as $v) {
                $act['type'] = $v['type'];
                $act['is_show'] = $v['is_show'];
                $this->data[] = $act;
            }
            $this->msg = '获取成功';
        }else{
            $this->msg = '暂无数据';
        }
        return $this->returnArr();
    }
}