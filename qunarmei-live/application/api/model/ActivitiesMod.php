<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/8/7
 * Time: 11:06
 */

namespace app\api\model;

use think\Model;
use think\Db;
set_time_limit(0);
class ActivitiesMod extends Model
{
    // 设置数据表
    protected $table = 'think_activities';
    private $url = 'http://teststore.qunarmei.com/api/otherapi/smsapi/smsSend';//微商城测试服务器发短信接口地址
    private $temp_id = 44;//短信模版id
    /*
     * 功能:查询有奖活动
     * 请求:$arr=>['dt'=>时间]
     * 返回:
     * */
    public function selAct($arr){
        $rest = null;
        // 角色判断,只有美容师和顾客登录才有奖品
        $res_user = Db::table('ims_bj_shopn_member m,ims_bwk_branch b ')->field('m.id,m.isadmin')->where('(m.isadmin=1 or m.status=0 or b.sign=\'000-000\' or b.sign=\'666-666\' or b.sign=\'888-888\') and m.storeid=b.id and m.id='.$arr['user_id'])->count();
        if(!$res_user){
            // 查询用户是否已领取
            $res_d = Db::table('think_activities_winning')->where('user_id='.$arr['user_id'].' or device_type="'.$arr['device_type'].'" ')->count();
            if($res_d < 1){
                // 活动
                $res = $this->field('id,act_title')->where(" act_start_time<='".$arr['dt']."' and '".$arr['dt']."' <= act_end_time and act_status=1")->limit(1)->find();
                if($res){
                    // 奖品列表
                    $res1  =  Db::table('think_activities_prize')->field('id,prize_name,prize_img,prize_count,prize_url')->where('act_id='.$res['id'].' and prize_count>0')->select();
                    if($res1){
                        $cnt = count($res1);
                        // 随机选取奖品
                        $rand_num = rand(0,($cnt-1));
                        $id = $res1[$rand_num]['id'];
                        // 库存-1
                        $res2 = Db::table('think_activities_prize')->where('id',$id)->setDec('prize_count',1);
                        if($res2){
                            // 插入领取记录
                            $data['act_id'] = $res['id'];
                            $data['user_id'] = $arr['user_id'];
                            // 查询门店id
                            $store = Db::table('ims_bj_shopn_member')->field('id,storeid')->where('id',$arr['user_id'])->limit(1)->find();
                            if($store){
                                $data['store_id'] = $store['storeid'];
                            }
                            $data['prize_id'] = $id;
                            $data['device_type'] = $arr['device_type'];
                            $data['win_create_time'] = date('Y-m-d H:i:s');
                            Db::table('think_activities_winning')->insert($data);
                        }
                        $rest['prize_id'] = $res1[$rand_num]['id'];
                        $rest['prize_name'] = $res1[$rand_num]['prize_name'];
                        $rest['prize_img'] = $res1[$rand_num]['prize_img'];
                        $rest['prize_url'] = $res1[$rand_num]['prize_url'];
                    }
                }
            }
        }
        return $rest;
    }
    /*
     * 功能:记录发送短信数据
     * 请求:$arr=>[$user_id=>用户id,prize_id=>奖品id]
     * 返回:json请求结果
     * */
    public function sendSms($arr){
        // 根据用户id查找号码
        $res1 = Db::table('think_activities_prize p,ims_bj_shopn_member m')->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')->field('m.id,m.mobile,b.title,p.prize_name')->where('m.id='.$arr['user_id'].' and p.id='.$arr['prize_id'])->limit(1)->find();
        if($res1){
            $arr1['mobile'] = $res1['mobile'];// 手机号码
            $arr1['id_temp'] = $this->temp_id;//短信模版id
            $arr1['store'] = $res1['title'];// 门店名称
            $arr1['title'] = $res1['prize_name'];//奖品名称
            $rest = $this->smsSend($arr1);
            // 记录
            $data['act_id'] = 1;
            $data['user_id'] = $arr['user_id'];
            $data['mobile'] = $res1['mobile'];
            $data['response'] = $rest;
            $data['sms_create_time'] = date('Y-m-d H:i:s');
            Db::table('think_activities_sms')->insert($data);
        }
    }
    /*
    * 功能:生成字母和数字的随机码,默认6位
    * 请求:$num=>几位
    * 返回:
    * */
    public function set_rand_nums($num=6){
        $key = null;
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyz';
        for($i=0;$i<$num;$i++)
        {
            $key .= $pattern{mt_rand(0,35)};    //生成php随机数
        }
        return $key;
    }
    /*
     * 功能:发送短信接口
     * 请求:$arr1=>[mobile=>手机号码,id_temp=>模版id]
     * 返回:json请求结果
     * */
    public function smsSend($arr1=null){
        // 请求数据
        $jpcode = $this->set_rand_nums();
        // 亲爱的*mobile*恭喜您参与去哪美登录抽奖活动获得*title*,请凭借奖品码*jpcode*去*store*领取
        $str1 = '{"jpcode":"'.$jpcode.'","mobile":"'.$arr1['mobile'].'","store":"'.$arr1['store'].'","title":"'.$arr1['title'].'"}';
        // 接口地址
        $url = $this->url.'?mobile='.$arr1['mobile'].'&id_temp='.$arr1['id_temp'].'&str='.$str1;
        // mobile=15921324164&id_temp=44&str={"jpcode":"0go399","mobile":"15921324164","store":"诚美总部门店【技术部】","title":"头皮套蓝"}
        $rest = curl_get($url);
        return $rest;
    }
}