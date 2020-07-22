<?php

namespace app\api_test\controller;
use think\Db;

/**
 * Home: App 首页广告图片
 */

class Home  extends Base
{
    /*
     * 功能: 首页广告图片
     * 请求:
     * 返回:
     * */
    public function home_img()
    {
        //初始化设置
        $code = 1;$msg = '获取成功';$data = (object)array();
        //查询是否存在
        $res = Db::name('home_img')->field('img,url,day_flag')->where('type',1)->order('ins_time desc')->limit(1)->select();
        if ($res) {
            $data = $res[0];
        }else {
            $msg = '暂无数据';
        }
        $ret = $this->returnMsg($code,$data,$msg);
        return $ret;
    }

    /*
     * 功能: 首页广告图片
     * 请求:
     * 返回:
     * */
//    public function home_img($user_id=0)
//    {
//        //初始化设置  用户每天登录弹1次/每次登录弹
//        $code = 1;$msg = '获取成功';$data = (object)array();
//        $res = Db::name('home_img')->field('img,url,day_flag')->where('type',1)->order('ins_time desc')->limit(1)->select();
//        if ($res) {
//            if($res[0]['day_flag'])
//            {
//                //开启每日弹一次
//                if($user_id)
//                {
//                    //判断是否已有登录标记
//                    $flag = parent::getRedisP('home_img_'.$user_id);
//                    if(!$flag)
//                    {
//                        //没登录过
//                        $data = $res[0];
//                        parent::setRedisP('home_img_'.$user_id,1);
//                    }
//                }
//            }else
//            {
//                //每次请求弹一次
//                $data = $res[0];
//                parent::setRedisP('home_img',$data,3600);
//            }
//        }else {
//            $msg = '暂无数据';
//        }
//        $ret = $this->returnMsg($code,$data,$msg);
//        return $ret;
//    }

    /*
     * 功能: 举报
     * 请求: user_id=>用户id; reporter_id=>被举报者用户id;
     * 返回:
     * */
    public function report($user_id='',$reporter_id='',$reason_id=1,$type='list')
    {
        //初始化设置
        $code = 1;$msg = '举报成功';$data=[];
        if($user_id && $reporter_id)
        {
            $data['is_report'] = 1;
            $datav = array('user_id'=>$user_id,'reporter_id'=>$reporter_id,'reason_id'=>$reason_id,'ins_time'=>date('Y-m-d H:i:s'));
            $res = Db::name('report')->insert($datav) ;
            if(!$res)
            {
                $msg = '举报失败';$data['is_report'] = 0;
            }
        }else
        {
            //获取举报原因列表
            $list = Db::name('report_reason')->field('id,reason')->order('display_order desc')->select();
            if($list)
            {
                foreach($list as $v)
                {
                    $data1['msg_id'] = $v['id'];
                    $data1['reason'] = $v['reason'];
                    $data[] = $data1;
                }
            }
            $msg='获取成功';
        }

        return  $this->returnMsg($code,$data,$msg);
    }
}