<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/20
 * Time: 16:56
 */

namespace app\api\controller;

use app\api\service\JobQueueSer;
use think\Db;
/**
 * 队列相关任务处理
 */
class JobQueue
{
    public function outQueue()
    {
        $chat_id = input('chat_id');
        $key = 'seeLive_'.$chat_id;
        $jobSer = new JobQueueSer();
        $jobSer->outQueueTent($key);
    }
    /**
     * 删除过期直播观看用户权限配置(每3小时执行)
     * @return [type] [description]
     */
    public function delLiveSeeConf()
    {
    	$flag = 0;
    	$msg = '';
    	// 查询已过期的直播配置
    	$map['end_time'] = ['<',time()];
    	$res = Db::table('think_live_see_conf c')->where($map)->order('id asc')->select();
    	if($res){
    		$mobiles = [];
    		$ids = [];
    		$datall = [];
    		foreach ($res as $k => $v) {
    			$mobiles[] = $v['mobile'];
    			$ids[] = $v['id'];
    			$mapl['user_id'] = $v['mobile'];
    			// 查询对应直播间
    			$resl = Db::table('think_live')->where($mapl)->order('id desc')->limit(1)->find();
    			if ($resl) {
    				// 更新live_id进去
    				$mapc['id'] = $v['id'];
    				$datac['live_id'] = $resl['id'];
    				Db::table('think_live_see_conf')->where($mapc)->update($datac);
    			}
    		}
    		// 查询配置日期记录
    		$mapd['id'] = ['in',$ids];
    		$resc = Db::table('think_live_see_conf c')->where($mapd)->order('id asc')->select();
    		if ($resc) {
    			foreach ($resc as $k => $v) {
    				$datal['live_id'] = $v['live_id'];
    				$datal['mobile'] = $v['mobile'];
    				$datal['store_signs'] = $v['store_signs'];
    				$datal['see_mobiles'] = $v['see_mobiles'];
    				$datal['create_time'] = time();
                    $datal['cre_time'] = $v['create_time'];
                    $datal['remark'] = $v['remark'];
				    $datal['start_time'] = $v['start_time'];
				    $datal['end_time'] = $v['end_time'];
				    $datal['admin_id'] = $v['admin_id'];
				    $datal['status'] = $v['status'];
				    $datal['activity_rules_id'] = $v['activity_rules_id'];
    				$datall[] = $datal;
    			}
    		}
    		// 插入配置日志记录表
    		Db::table('think_live_see_conf_log')->insertAll($datall);
    		// 删除过期
    		$resd = Db::table('think_live_see_conf')->where($mapd)->delete();
    		$flag = 1;
    	}
    	if ($flag == 1) {
    		$msg = '跨门店直播过期配置删除成功';
    	}else{
    		$msg = '无过期配置可删除';
    	}
    	$this->returnMsg(1,$msg);
    }
    /**
     * 统一下发数据格式
     * @param  integer $code [状态码,1:成功,0:失败]
     * @param  string  $msg  [提示信息]
     * @param  array   $data [下发数据]
     * @return [json]
     */
    public function returnMsg($code=1,$msg='',$data=[])
    {
    	$arr = [
    		'code'=>$code,
    		'msg'=>$msg
    	];
    	if ($code == 1) {
    		$arr['data'] = $data;
    	}
    	echo json_encode($arr,JSON_UNESCAPED_UNICODE);die;
    }
}