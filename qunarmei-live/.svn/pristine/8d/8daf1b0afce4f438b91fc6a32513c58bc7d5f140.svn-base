<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/4/19
 * Time: 10:31
 */

namespace app\common\controller;


use think\Controller;
use think\Log;
class Base extends Controller
{
    /**
     * 调用外部接口 curl post方法
     * @param string $url
     * @param array $data
     * @return mixed
     */
    public function curlPost($url='',$data=array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        $output = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }
    //调用外部接口 curl get方法
    function curlGet($url='',$type='http')
    {
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if($type == 'https'){
            //重要！
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        $errno = curl_errno( $ch );
        $curlInfo = curl_getinfo($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        return $output;
    }

    /**
     * 统一返回数据结构
     * @param string $code
     * @param array $data
     * @param string $msg
     * @return \think\response\Json
     */
    function returnMsg($code='1',$data=array(),$msg='获取成功')
    {
        $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        $msg_resp = '下发数据:'.json_encode($arr,JSON_UNESCAPED_UNICODE);
        $this->writeLog($msg_resp);
        return json($arr);
    }

    /**
     * [writeLog 写入日志]
     * @param  string $msg       [description]
     * @param  string $log_level [description]
     * @return [type]            [description]
     */
    function writeLog($msg='',$log_level='info')
    {
        /*********** 获取参数的验证规则  ***********/
        if($msg)
        {
            $dt = date('Y-m-d H:i:s');
            Log::record($dt.'-'.$msg,$log_level);
        }
    }
}