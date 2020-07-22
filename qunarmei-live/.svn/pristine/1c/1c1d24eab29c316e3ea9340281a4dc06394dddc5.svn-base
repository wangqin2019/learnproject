<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/6
 * Time: 17:24
 */

namespace app\api\service;


use think\Db;

class User
{
    /**
     * 查询用户信息
     */
    public function getUserFans($map)
    {
        $res = Db::table('ims_bj_shopn_member m')
            ->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')
            ->field('m.id,m.mobile,m.realname,f.avatar,m.storeid,m.pid,m.staffid')
            ->where($map)
            ->select();
        return $res;
    }
    /**
     * 添加用户
     * @param $map
     * @return array
     */
    public function addUser($data)
    {
        $resm = Db::table('ims_bj_shopn_member')
            ->insertGetId($data);
        return $resm;
    }
	/**
     * 获取单个用户所有信息
     * @param $map
     * @return array
     */
    public function getUserAll($map)
    {
        $resm = Db::table('ims_bj_shopn_member m')
            ->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['m.storeid=b.id'],'LEFT')
            ->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'LEFT')
            ->join(['sys_department'=>'sd'],['sd.id_department=sdr.id_department'],'LEFT')
            ->field('m.id,m.realname,m.mobile,b.sign,b.title,sd.st_department bsc,f.avatar,b.id store_id')
            ->where($map)
            ->limit(1)
            ->find();
        return $resm;
    }
	/**
     * 获取用户信息
     */
    public function getUser($map)
    {
        $res = Db::table('ims_bj_shopn_member')
            ->where($map)
            ->limit(1)
            ->find();
        return $res;
    }
	/**
     * 获取用户角色
     * @param int $user_id 用户id
     * @return int $role_id 1:店老板,2:美容师,3:顾客
     */
    public function getUserRole($user_id)
    {
        $role_id = 0;
        $map['id'] = $user_id;
        $res = Db::table('ims_bj_shopn_member')
            ->where($map)
            ->limit(1)
            ->find();
        if($res){
            if($res['isadmin'] == 1){
                $role_id = 1;
            }elseif(strlen($res['code']) > 1){
                $role_id = 2;
            }else{
                $role_id = 3;
            }
        }
        return $role_id;
    }
    /**
     * 获取用户画像
     * @param array $map 查询条件
     */
    public function getUserPortrait($map)
    {
        $res = Db::table('ims_bj_shopn_member_extend')
            ->where($map)
            ->limit(1)
            ->find();
        return $res;
    }

    /**
     * 获取用户信息
     * @param array $map 查询条件
     */
    public function getUserFan($map)
    {
        $res = Db::table('ims_bj_shopn_member m')
            ->join(['ims_fans' => 'f'],['f.id_member = m.id '],'LEFT')
            ->field('m.id user_id,m.realname user_name,m.mobile,f.avatar,f.birthday')
            ->where($map)
            ->limit(1)
            ->find();
        return $res;
    }

    /**
     * 添加用户画像信息
     * @param array $data 插入数据
     */
    public function addUserPortrait($data)
    {
        $data_arr = [
            'mid' => $data['user_id'],
            'mobile' => $data['mobile'],
            'sex' => $data['sex'],
            'age_group' => $data['age_group'],
            'birthday' => $data['birthday'],
            'interest' => $data['interest'],
            'location' => $data['lat'].','.$data['lng'],
            'insert_time' => time(),
        ];
        $res = Db::table('ims_bj_shopn_member_extend')
            ->insertGetId($data_arr);
        return $res;
    }
}