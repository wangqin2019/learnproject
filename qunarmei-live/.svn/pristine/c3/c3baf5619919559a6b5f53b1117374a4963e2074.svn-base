<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/4/17
 * Time: 10:48
 */

namespace app\api_public\controller;
use app\common\controller\Base;
use app\common\model\OTO;
use app\common\model\OtoQa;
use app\common\model\OtoRecord;
use think\Cache;
use think\Exception;
use think\Db;
class OtoEducation extends Base
{
    // 状态码
    protected $code = 1;// 1成功 0失败
    // 数据
    protected $data = [];// 数据
    // 提示信息
    protected $msg = '获取成功';// 提示信息


    /**
     * 每天定时任务请求oto相关数据
     * @param
     * @return
     */
    public function queryOto()
    {
        $this->userStatus();
        $this->queryRanking();
        $this->queryTypes();
        $this->queryFAQ();
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }

    /**
     * oto脑力账号状态查询
     * @param
     * @return
     *
     */
    public function userStatus()
    {
        // 查询账号列表
        $otoList = OTO::all();
        // 查询账号有效状态
        if($otoList){
            $url = config('url.oto_url').'userStatus';
            $data['api_token'] = 'FjzwHSXRtgRYRo9qELfNdg==';
            $data['time'] = time();
            try{
                foreach ($otoList as $v) {
                    // 数据查询
                    $data['user'] = $v->oto_user;
                    $rest = $this->curlPost($url,$data);
                    $this->writeLog('oto查询数据返回userStatus:'.$rest);
                    if($rest){
                        $rest = json_decode($rest,true);
                        // 更新过期账号
                        if($rest['code'] == 0 && $rest['data']['status'] == 0){
                            $dataStatu['status'] = 0;
                            $condition['oto_user'] = $v->oto_user;
                            OTO::update($dataStatu,$condition);
                            $this->msg = '过期账号账号已更新成功!';
                        }
                    }
                }
            }catch(Exception $e){
                $this->code = 0;
                $this->msg = $e->getMessage();
            }
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    /**
     * 3.oto脑力战绩榜列表查询API
     * @return string
     */
    public function queryRanking()
    {
        $url = config('url.oto_url').'queryRanking';
        try{
            // 查询所有已使用oto账号
            $map['card_id'] = ['>',0];
            $res_u = OTO::all($map);
            if($res_u){
                $types = [
                    0=>'coin_num',
                    5=>'word_num',
                    1=>'online_time',
                    2=>'clearance_num',
                    6=>'coin_num',
                    10=>'word_num',
                    8=>'clearance_num'
                ];
                foreach ($res_u as $v) {
                    // oto接口查询
                    foreach ($types as $k=>$vt) {
                        $url = config('url.oto_url').'queryRanking';
                        $url .= '?user='.$v['oto_user'].'&type='.$k.'&time='.time().'&api_token='.config('text.oto_api_token');
                        $rest = $this->curlGet($url);
                        $this->writeLog('oto请求url:'.$url.'-oto查询数据返回queryRanking:'.$rest);
                        if($rest){
                            $res = json_decode($rest,true);
                            // 成功,更新排名情况进去
                            if($res['code'] == 0){
//                                echo '<pre>';print_r($res['rks'][0]);die;
                                $mapu['oto_user'] = $v['oto_user'];
                                // 查询改记录是否存在,不存在插入,存在更新
                                // 周榜
                                if($k == 6 || $k == 8 || $k == 10){
                                    $mapu['type'] = 7;
                                }else{
                                // 总榜
                                    $mapu['type'] = 0;
                                }
                                $res_c = OtoRecord::get($mapu);
                                if($res_c){
                                    $datau['user_name'] = $res['rks'][0]['name'];
                                    $datau[$vt] = $res['rks'][0]['val'];
                                    $datau['first_login_time'] = strtotime('20'.$res['rks'][0]['flTime'].':00:00');
                                    $datau['last_login_time'] = strtotime('20'.$res['rks'][0]['llTime'].':00:00');
                                    $datau['ranking'] = $res['rks'][0]['rank'];
                                    $res = OtoRecord::update($datau,$mapu);
                                    $this->msg .= '更新成功-'.$v['oto_user'];
                                }else{
                                    $datau['oto_user'] = $v['oto_user'];
                                    $datau['user_name'] = $res['rks'][0]['name'];
                                    $datau[$vt] = $res['rks'][0]['val'];
                                    $datau['first_login_time'] = strtotime('20'.$res['rks'][0]['flTime'].':00:00');
                                    $datau['last_login_time'] = strtotime('20'.$res['rks'][0]['llTime'].':00:00');
                                    $datau['ranking'] = $res['rks'][0]['rank'];
                                    $datau['type'] = $mapu['type'];
                                    $datau['create_time'] = date('Y-m-d H:i:s');
                                    $res = OtoRecord::create($datau);
                                    $this->msg .= '插入成功-'.$v['oto_user'];
                                }
                            }
                        }
                    }
                }
            }else{
                $this->msg = '暂无账号使用';
            }
        }catch(Exception $e){
            $this->code = 0;
            $this->msg = $e->getMessage();
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    /**
     * 4.oto脑力锦囊分类学习类型查询
     * @return string
     */
    public function queryTypes()
    {
        $url = config('url.oto_url').'queryTypes';
        try{
            // oto接口查询
            $url .= '?time='.time().'&api_token='.config('text.oto_api_token');
            $rest = $this->curlGet($url);
            $this->writeLog('oto查询数据返回queryTypes:'.$rest);
            if($rest){
                $res = json_decode($rest,true);
                // 成功,更新缓存进去
                if($res['code'] == 0){
                    // 每次查询存入缓存
                    Cache::set('oto_queryTypes_types',$res['types'],60*60*24*7);
                    Cache::set('oto_queryTypes_classfy',$res['classfy'],60*60*24*7);
                }else{
                    $this->code = 0;
                    $this->msg = $res['msg'];
                }
            }
        }catch(Exception $e){
            $this->code = 0;
            $this->msg = $e->getMessage();
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }
    /**
     * 5.oto脑力锦囊分类学习类型查询
     * @param array $arr [cla_id 分类id,type_id 类型id,user 用户账号 ,begin_time 开始时间 ,end_time 结束时间]
     * @return array
     */
    public function queryTips($arr)
    {
        $url = config('url.oto_url').'queryTips';
        $api_token = config('text.oto_api_token');
        try{
            $url .= '?cla_id='.$arr['cla_id'].'&type_id='.$arr['type_id'].'&user='.$arr['oto_user'].'&begin_time='.$arr['begin_time'].'&end_time='.$arr['end_time'].'&api_token='.$api_token;
            $rest = $this->curlGet($url);
            $this->writeLog('oto请求url:'.$url.'-oto查询数据返回queryTips:'.$rest);
            if($rest){
                $res = json_decode($rest,true);
                if($res['code'] == 0){
                    foreach ($res['list'] as $v) {
                        $datau['word'] = $v['word'];
                        $datau['word_ch'] = $v['wordTranslation'];
                        $this->data[] = $datau;
                    }
                }else{
                    $this->code = 0;
                    $this->msg = $res['msg'];
                }
            }
        }catch(Exception $e){
            $this->code = 0;
            $this->msg = $e->getMessage();
        }
        return $this->data;
    }
    /**
     * 6.oto脑力常见问题及解答列表API
     * @return string
     */
    public function queryFAQ()
    {
        $url = config('url.oto_url').'queryFAQ';
        $api_token = config('text.oto_api_token');
        try{
            // oto接口查询
            $url .= '?time='.time().'&api_token='.$api_token;
            $rest = $this->curlGet($url);
            $this->writeLog('oto请求url:'.$url.'-oto查询数据返回queryFAQ:'.$rest);
            if($rest){
                $res = json_decode($rest,true);
                // 成功,更新缓存进去
                if($res['code'] == 0){
                    foreach ($res['data'] as $v) {
                        $datau1['id'] = $v[0];
                        // 查询
                        $res_c = OtoQa::where($datau1)->count();
                        $datau['question'] = $v[1];
                        $datau['answer'] = $v[2];
                        $datau['create_time'] = date('Y-m-d H:i:s');
                        if($res_c>0){
                            $res_c = OtoQa::update($datau,$datau1);
                            $this->msg = 'Q&A更新成功';
                        }else{
                            OtoQa::create($datau);
                            $this->msg = 'Q&A添加成功';
                        }
                    }
                }else{
                    $this->code = 0;
                    $this->msg = $res['msg'];
                }
            }
        }catch(Exception $e){
            $this->code = 0;
            $this->msg = $e->getMessage();
        }
        return $this->returnMsg($this->code,$this->data,$this->msg);
    }

}