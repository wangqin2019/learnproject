<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/12/6
 * Time: 11:26
 */

namespace app\api\controller\v3;
use app\api\model\Appc_apilog;
use app\api\validate\Common as CommonValidate;
use think\Controller;
use think\Request;
use think\Log;
class Common extends Controller
{
    // 统一参数
    protected $rest = [
        'code' => 1,
        'data' => [],
        'msg' => '获取成功'
    ];
    // 时间戳
    protected $dt;
    // 请求下发同一key设置
    protected $key_id;
    /*
     * 功能: 返回正常数据
     * 请求: 统一下发数据
     * */
    public  function returnMsgNoTrans($code=0,$data=[],$msg='暂无数据')
    {
        $res = ['code'=>$code,'data'=>$data,'msg'=>$msg];
        $rest = json_encode($res,320);
        $rest = str_replace("\\\\", "\\",$rest);
        $msg_resp = '下发数据:'.$rest;
        $this->writeLog($msg_resp);
        $this->writeDbLog($msg_resp,2);
        echo $rest;die;
    }
    /*
     * 功能: 初始化方法
     * 请求:
     * */
    public  function _initialize()
    {
        $this->dt = time();
        // 统一处理数据验证
        $request = Request::instance();
        $arr = $request->param();
        $action = $request->action();
         // 记录请求日志
        $data_req = $_REQUEST;
        // 将请求数组拼接成url地址参数
        $msg_req = '请求数据:'.http_build_query($data_req , '' , '&');
        $this->writeLog($msg_req);

        $key = $request->baseUrl();
        $request_data = input();
        unset($request_data[$key]);
        $this->writeDbLog(http_build_query($request_data,'','&'),1);

        $result = $this->validate($arr,CommonValidate::$func[$action]);
        if(true !== $result){
            // $this->rest['msg'] = '请求参数错误:'.$result;
            $this->rest['msg'] = $result;
            $this->returnMsgError($this->rest['msg']);
        }
    }
    /*
     * 功能: 错误失败,返回函数
     * 请求: 'msg'=>'错误信息'
     * */
    protected  function returnMsgError($msg)
    {
        $data = [
            'code' => 0,// 错误码0
            'msg' => $msg// 错误信息
        ];
        header('Content-Type:application/json');
        $data_resp = json_encode($data,JSON_UNESCAPED_UNICODE);
        echo $data_resp;
        $msg_resp = '下发数据:'.$data_resp;
        $this->writeLog($msg_resp);
        $this->writeDbLog($msg_resp,2);
        die;
    }

    /*
     * 功能: 返回正常数据
     * 请求:
     * */
    protected  function returnMsg($code=0,$data=[],$msg='暂无数据')
    {
        $res = ['code'=>$code,'msg'=>$msg];
        // code为0时过滤data体
        if($code == 1){
            $res['data'] = $data;
        }
        $rest = json($res);
        $msg_resp = '下发数据:'.json_encode($res,JSON_UNESCAPED_UNICODE);
        $this->writeLog($msg_resp);
        $this->writeDbLog($msg_resp,2);
        return $rest;
    }
    /*
     * 功能: 记录日志
     * 请求: $msg => 记录日志
     * */
    protected  function writeLog($msg='')
    {
        $res = Log::record($this->dt.'-'.$msg);
    }

    /*
     * 功能: 记录请求接口日志
     * 请求: $msg => 记录日志 (日志内容) ; $type => 类型,1:请求,2:下发;
     * */
    protected function writeDbLog($msg , $type)
    {
        // 插入
        if($type == 1){
            $info = Request::instance()->header();
            $data['user_agent'] = isset($info['user-agent'])?$info['user-agent']:'';
            $data['api_path'] = Request::instance()->baseUrl();
            $data['request_data'] = $msg ;
            $data['create_time'] = time();
            $res = Appc_apilog::create($data);
            $this->key_id = $res->id;
        }else if($type == 2){
        // 更新
            $map['id'] = $this->key_id;
            $data['response_data'] = $msg;
            $data['update_time'] = time();
            Appc_apilog::where($map)->update($data);
        }
    }
}