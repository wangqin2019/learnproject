<?php

namespace app\api_test\controller;
use think\Controller;
use think\Db;
//使用redis扩展
use think\cache\driver\Redis;

class Base extends Controller
{
    public function _initialize()
    {

        $config = cache('db_config_data');

        if(!$config){
            $config = load_config();
            cache('db_config_data',$config);
        }
        config($config);

    }

    public function returnMsg($code='1',$data=array(),$msg='获取成功')
    {
      $arr = array('code'=>$code,'data'=>$data,'msg'=>$msg);
      return json($arr);
    }

    public function checkRet($ret)
    {
      if($ret)
      {
        $ret = $this->returnMsg(1,$ret,'获取成功');
      }else
      {
        $ret = $this->returnMsg(1,$ret,'数据为空');
      }
      return $ret;
    }

    //start Modify by wangqin 2017-12-25 检测标记flag
    public function checkFlag($flag,$data=null)
    {
        $ret = null;
        if($flag == 1)
        {
            $ret = $this->returnMsg(1,$data,'获取成功');
        }elseif($flag == 2)
        {
            $ret = $this->returnMsg(0,'','请求参数错误');
        }elseif($flag == 3)
        {
            $ret = $this->returnMsg(0,'','服务器内部错误');
        }
        return $ret;
    }
    //end Modify by wangqin 2017-12-25
    //start Modify by wangqin 2017-12-13 记录接口请求和返回
    /*
     * 功能:记录接口请求及返回
     * 请求: $func=>接口作用,$url=>接口请求url,$request_paras=>接口请求数据,$respon_paras=>接口返回数据,
     * 返回: $ret=>记录接口 0=>失败;1=>成功
     *
     * */
    public function logApiRest($func='接口作用',$url='接口请求url',$request_paras='接口请求数据',$respon_paras='接口返回数据')
    {
        $ret = 0;
        if($url)
        {
//            $data = array('func'=>$func,'url'=>$url,'request_paras'=>$request_paras,'respon_paras'=>$respon_paras,'log_time'=>date('Y-m-d H:i:s'));
            $data = array('func'=>$func,'url'=>$_SERVER['HTTP_HOST'].$url,'request_paras'=>http_build_query($request_paras),'respon_paras'=>json_encode($respon_paras),'log_time'=>date('Y-m-d H:i:s'));
            $res = Db::name('query_log')->insert($data);
            if($res)
            {
                $ret = 1;
            }
        }
        return $ret;
    }

    //end Modify by wangqin 2017-12-13

    //start Modify by wangqin 2017-12-23 清除以key开头的缓存
    /*
     * 功能:清除以key开头的缓存
     * 请求: $paras=>key,
     * 返回:
     *
     * */
    public function clearRedisP($paras='key')
    {
        if($paras)
        {
            $redis = new Redis();
            //删除指定缓存
            $paras = $paras.'*';
            //获取指定前缀keys
            $keys = $redis->getKeys($paras);
            //删除redis
            $redis->delKeys($keys);
            $redis->closeReids();
            return '清除以'.$paras.'开头的缓存成功';
        }
    }

    //通过redis获取数据
    public function getRedisP($paras)
    {
        // $Redis = new Redis();
        $redis_v = new Redis();
        $res = $redis_v->get($paras);
        $redis_v->closeReids();
        return $res;
    }

    //设置redis数据
    public function setRedisP($paras,$val,$expire=null)
    {
        // $Redis = new Redis();
        $redis_v = new Redis();
        $res = $redis_v->set($paras,$val,$expire);
        $redis_v->closeReids();
        return $res;
    }
    //end Modify by wangqin 2017-12-23

    //start Modify by wangqin 2018-01-12
    /*
     * 功能: redis hash存储
     * 请求: key,hash_key,val
     * 返回:
     * */
    public function getRedisHash($key,$hash_key)
    {
        $redis_v = new Redis();
        $res = $redis_v->hget($key,$hash_key);
        $redis_v->closeReids();
        return $res;
    }

    public function setRedisHash($key,$hash_key,$val)
    {
        $redis_v = new Redis();
        $res = $redis_v->hset($key,$hash_key,$val);
        $redis_v->closeReids();
        return $res;
    }
    //end Modify by wangqin 2018-01-12

    //start Modify by wangqin 2018-03-14
    /*
     * 功能: 检测请求参数是否为空
     * 请求: $arr=>请求参数,array()
     * 返回: $flag;0=>为空;$flag=>不为空
     *
     * */
    public function checkReq($arr)
    {
        $flag = 1;
        if(is_array($arr) && !empty($arr))
        {
            foreach($arr as $v)
            {
                if(!$v)
                {
                    $flag=0;
                    break;
                }
            }
        }else
        {
            $flag=0;
        }
        return $flag;
    }
    //end Modify by wangqin 2018-03-14

}