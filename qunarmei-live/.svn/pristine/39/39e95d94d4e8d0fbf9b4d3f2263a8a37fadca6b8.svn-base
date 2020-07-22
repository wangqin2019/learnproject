<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/1/10
 * Time: 11:39
 */

namespace app\api\service;


use app\api\model\DataSel;
use app\api\model\ActiviteM;
use think\Db;
class ActiviteSer
{
    protected $popUrl = 'http://live.qunarmei.com/userportrait/index.html';
	/*
     * 功能:1.18直播活动预热,每天登陆弹窗显示不同活动预热图片
     * 返回:json
     * */
    public function homePopImg($user_id)
    {
        $rest = [
                'prize_id' => 1,
                'prize_name' => '',
                'prize_img' => '',
                'prize_url' => '',
                'is_popup' => 0,
                'popup_url' => ''
            ];

        $arr_dt['dt'] = date('Y-m-d H:i:s');
        // $res = ActiviteM::activitieszbSel($arr_dt);
        $res = '';
        if(!empty($res)){
            $rest['prize_name'] = 'missshop活动';
            $rest['prize_img'] = $res['act_img'];
            $rest['prize_url'] = 'http://live.qunarmei.com/html/banner_h5.html';
        }
        // 查询是否画过像
        $userSer = new User();

        // 判断用户角色,顾客才画像
        $res_role = $userSer->getUserRole($user_id);
        if($res_role != 3){
            return $rest;
        }

        $map['mid'] = $user_id;
        $res_user = $userSer->getUserPortrait($map);
        if(empty($res_user)){
            $rest['is_popup'] = 1;
            $rest['popup_url'] = 'http://live.qunarmei.com/html/userportrait/index0918.html?user_id='.$user_id;
        }
        return $rest;
    }
	/**
     * 首页弹窗广告版本2
     * @param $user_id
     * @param int $ver 版本号
     * @return array
     */
    public function homePopImgV2($user_id,$ver = 0)
    {
        $rest = [
            'is_popup' => 0,
            'popup_url' => '',
            'popup_title' => '',
            'ad_list' => []
        ];
        $arr_dt['dt'] = date('Y-m-d H:i:s');
        // 查询有效期间广告
        $map['act_start_time'] = ['<=',$arr_dt['dt']];
        $map['act_end_time'] = ['>',$arr_dt['dt']];
        $map['act_status'] = 1;
        $map['img_isshow'] = 1;

        // 判断用户是否参与
        $missshopSer = new MissshopTransferActiveSer();
        $resmiss = $missshopSer->isInActive($user_id);
//        echo '<pre>';print_r($resmiss);die;
        if($resmiss['code'] != 1){
            $map['act_title'] = ['neq','missshop'];
        }

        $res = Db::table('think_activities_zb')
            ->field('act_title,act_img,act_type,act_val,act_img_height,act_img_width')
            ->where($map)
            ->order('act_create_time desc')
            ->select();
        if($res){
            $userSer = new User();
            $mapu['id'] = $user_id;
            $resu = $userSer->getUser($mapu);
            foreach ($res as $v) {
                $res_ad['ad_title'] = $v['act_title'];
                $res_ad['ad_type'] = $v['act_type'];
                $res_ad['ad_val'] = $v['act_val'];
                $res_ad['ad_img'] = $v['act_img'];
                $res_ad['ad_img_height'] = $v['act_img_height'];
                $res_ad['ad_img_width'] = $v['act_img_width'];
                if($res_ad['ad_title'] == 'missshop' && $resu){
                    $res_ad['ad_val'] .= '?mobile='.$resu['mobile'].'&type=missshop';
                }
                if ($v['act_title'] == 'updatepay') {
                    if ($ver >= 1) {
                        $res_ad = [];
                    }
                }
                if ($res_ad) {
                    $rest['ad_list'][] = $res_ad;
                }
            }
        }

        // 查询是否画过像
        $userSer = new User();

        // 判断用户角色,顾客才画像
        $res_role = $userSer->getUserRole($user_id);
        if($res_role != 3){
            return $rest;
        }

        $map1['mid'] = $user_id;
        $res_user = $userSer->getUserPortrait($map1);
        if(empty($res_user)){
            $rest['popup_title'] = '用户画像';
            $rest['is_popup'] = 1;
            $rest['popup_url'] = $this->popUrl.'?user_id='.$user_id;
        }
        return $rest;
    }
}