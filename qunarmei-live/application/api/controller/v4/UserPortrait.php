<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/6
 * Time: 17:20
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\User;
header("Access-Control-Allow-Origin:*");
class UserPortrait extends Common
{
    /**
     * 获取用户画像
     * @param int $user_id 用户id
     * @return array
     */
    public function getUserPortrait()
    {
        $user_id = input('user_id');

        $this->rest['code'] = 0;
        $this->rest['data'] = (object)[];
        $this->rest['msg'] = '获取失败';

        $userSer = new User();
        // 判断用户角色,顾客才画像
        $res_role = $userSer->getUserRole($user_id);
        if($res_role != 3){
            $this->rest['code'] = 0;
            $this->rest['msg'] = '顾客角色才能画像';
            return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
        }
        $mapu['mid'] = $user_id;
        // 1.查询用户是否画过像
        $res_user = $userSer->getUserPortrait($mapu);
        if($res_user){
            $this->rest['code'] = 0;
            $this->rest['msg'] = '用户已经画过像';
            return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
        }

        // 2.没画过,查询用户信息
        $map_user['m.id'] = $user_id;
        $res_user = $userSer->getUserFan($map_user);
        if($res_user){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '获取用户信息成功';
            $this->rest['data'] = [
                'user_name' => $res_user['user_name'],
                'head_img' => $res_user['avatar']==null?config('img.head_img'):$res_user['avatar'],
                'mobile' => $res_user['mobile'],
            ];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }

    /**
     * 用户画像信息提交
     * @param int $user_id 用户id
     * @param string $mobile 用户手机号
     * @param string $sex 性别 男,女
     * @param int $age_group 年龄定位 80
     * @param string $birthday 生日
     * @param string $interest 兴趣爱好
     * @param string $lat 经度
     * @param string $lng 纬度
     * @return array
     */
    public function addUserPortrait()
    {
        $user_id = input('user_id');
        $mobile = input('mobile');
        $sex = input('sex');
        $age_group = input('age_group');
        $birthday = input('birthday');
        $interest = input('interest');
        $lat = input('lat');
        $lng = input('lng');

        // 性别,年龄定位,生日,消费倾向,经纬度,
        $this->rest['code'] = 0;
        $this->rest['data'] = [];
        $this->rest['msg'] = '提交失败';

        $userSer = new User();
        $mapu['mid'] = $user_id;
        // 1.查询用户是否画过像
        $res_user = $userSer->getUserPortrait($mapu);
        if($res_user){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '用户已经画过像';
            return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
        }

        // 2.没画过,插入用户信息
        $data = [
            'user_id' => $user_id,
            'mobile' => $mobile,
            'sex' => $sex,
            'age_group' => $age_group,
            'birthday' => $birthday,
            'interest' => $interest,
            'lat' => $lat,
            'lng' => $lng,
        ];
        $res_user = $userSer->addUserPortrait($data);
        if($res_user){
            $this->rest['code'] = 1;
            $this->rest['msg'] = '用户画像成功';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}