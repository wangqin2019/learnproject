<?php

namespace app\api_test\controller;
use think\Db;
/**
 * 调用环信通讯服务端接口类
 *
 */
class RingLetter extends Base
{
    /**
     *
     */
    protected $org_name='741777896';
    protected $app_name='qunarmei';
    protected $client_id='YXA6m3OQwEEvEeeN7tmXtV_Ffw';
    protected $client_secret='YXA6hLtYiAq5VM6rQiZ7pTKnbxvb3Ns';
    protected $url='https://a1.easemob.com/';

    /**
     * [getToken 获取token值]
     * @return [type]
     * @author
     */
    public function getToken()
    {
        $res = Db::name('ring_letter')->field('token,log_time,expires')->limit(1)->select();
        if($res)
        {
            //判断token值是否在有效期内
            $dt = strtotime($res[0]['log_time'])+3600;
            if($dt > time())
            {
                return   $res[0]['token'];
            }else
            {
                $token_url = $this->url.$this->org_name.'/'.$this->app_name.'/token';
                $data = '{"grant_type":"client_credentials","client_id":"'.$this->client_id.'","client_secret":"'.$this->client_secret.'"}';
                $rest = curlPost($token_url,$data);
                if($rest)
                {
                    $rest = json_decode($rest);
                    $token = $rest->access_token;
                    $expires = $rest->expires_in;
                    $ret = Db::name('ring_letter')->where('id=1')->update(array('token'=>$token,'expires'=>$expires,'log_time'=>date('Y-m-d H:i:s',time())));
                    return $token;
                }
            }
        }


    }

    /**
     * [sendMsg 向聊天室发送消息]
     * @return [type]
     * @author
     */
    public function sendMsg($chat_id,$msg,$fromer,$live_id)
    {
        $token = $this->getToken();
//        $headers = array('Authorization:Bearer'.$token,);
        $res = Db::name('ring_letter')->field('token,log_time,expires')->limit(1)->select();
        $msg_url = $this->url.$this->org_name.'/'.$this->app_name.'/messages';
        $data = '{"target_type" : "chatrooms","target" : ["'.$chat_id.'"],"msg" : {"type" : "txt","msg" : "'.$msg.'"},"from" : "'.$fromer.'"}';

        $rest = curlPost($msg_url,$data,$token);
        if($rest)
        {
//            $rest = json_decode($rest);
//            if($rest->error)
//            {
//                $token_url = $this->url.$this->org_name.'/'.$this->app_name.'/token';
//                $data = '{"grant_type":"client_credentials","client_id":"'.$this->client_id.'","client_secret":"'.$this->client_secret.'"}';
//                $ret = curlPost($token_url,$data);
//                if($ret)
//                {
//                    $ret = json_decode($rest);
//                    $token = $rest->access_token;
//                    $expires = $rest->expires_in;
//                    $ret = Db::name('ring_letter')->where('id=1')->update(array('token'=>$token,'expires'=>$expires,'log_time'=>date('Y-m-d H:i:s',time())));
//                    $rest = curlPost($msg_url,$data,$headers);
//                }
//            }
            $data_v =array('live_id'=>$live_id,'chat_id'=>$chat_id,'fromer'=>$fromer,'msg'=>$msg,'respone_msg'=>$rest,'log_time'=>date('Y-m:d H:i:s',time()));
            $resp = Db::name('ring_letter_log')->insert($data_v);
        }
    }

    /**
     * [getChatrooms 获取聊天室人数]
     * @return [type]
     * @author
     */

    public function getChatrooms()
    {
        $token = $this->getToken();
        if($token)
        {
            //获取聊天室人数
            $rooms_url = $this->url.$this->org_name.'/'.$this->app_name.'/chatrooms?pagenum=1&pagesize=20';
            $rest = curlGet($rooms_url,$token);
            if($rest)
            {
                $rest = json_decode($rest);
                $data = $rest->data;
                if($data)
                {
                    foreach($data as $v)
                    {
                        $ins_data = array('chat_owner'=>($v->owner),'chat_cnt'=>($v->affiliations_count),'log_time'=>date('Y-m-d H:i:s',time()));
                        $resp = Db::name('chatroom')->field('*')->select();
                        if($resp)
                        {
                            foreach($resp as $v1)
                            {
                                if($v1['chat_id'] == ($v->id))
                                {
                                    $ret = Db::name('chatroom')->where('chat_id='.$v1['chat_id'])->update($ins_data);
                                }
                            }
                        }
                    }

                }
            }
        }
    }

}
