<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/26
 * Time: 13:05
 */

namespace app\api\service;
use app\api\model\DepartbeautyRelation;
use app\api\model\Department;
use app\api\model\Live;
use app\api\model\LiveConf;
use app\api\model\User;

/**
 * 规则权限服务类
 */
class RuleSer
{
    /**
     * 查询用户是否有观看权限
     * @param int $user_id 用户id
     * @param int $store_id 门店id
     * @param string $see_store 能观看门店id
     * @param string $see_role 能观看角色id
     */
    public function getSeeRule($user_id,$store_id,$see_store,$see_role)
    {
        $flag = 0;
        $store_flag = 0;// 门店标记
        $zd_store = [1,2,1913];//666-666,888-888,001-001[总部门店]
        if($see_store == 0 || in_array($store_id,$zd_store)){
            $store_flag = 1;
            // 所有角色
            if($see_role == 0){
                return 1;
            }
        }else{
            $stores = explode(',',$see_store);
            if(in_array($store_id,$stores)){
                $store_flag = 1;
                // 所有角色
                if($see_role == 0){
                    return 1;
                }
            }
        }
        if($flag == 0 && $store_flag){
            // 查询角色是否有权限
            $res = User::get($user_id);
            $role_id = 0;
            if($res){
                // 店老板
                if($res['isadmin']){
                    $role_id = 1;
                }elseif(strlen($res['code']) > 1){
                    $role_id = 2;
                }else{
                    $role_id = 3;
                }
                $roles = explode(',',$see_role);
                if(in_array($role_id,$roles)){
                    return 1;
                }
            }
        }
        return $flag;
    }

    /**
     * 查询直播开设的类型
     * @param string $mobile 主播账号
     */
    public function getLiveType($mobile)
    {
        $type = 5;
        // PC端直播,总部
        if($mobile == 1){
            $type = 1;
            return $type ;
        }
        // 1.查询主播账号和类型
        $map['mobile'] = $mobile;
        $resu = User::with('branch')->where($map)->find();
        if($resu){
            // 技术部
            if($resu['branch']['sign'] == '666-666'){
                $type = 2;
            }elseif($resu['branch']['sign'] == '888-888'){
                // 总部
                $type = 1;
            }elseif($resu['branch']['sign'] == '000-000'){
                // 办事处
                $type = 3;
            }elseif($resu['branch']['sign'] == '998-998'){
                // 诚美club直播
                $type = 4;
            }else{
                // 美容院直播
                $type = 5;
            }
        }
        return $type ;
    }

    /**
     * 查询是否有观看权限
     * @param int $type 直播开设类型
     * @param int $live_id 直播间id
     * @param int $user_id 观看者id
     */
    public function getSeeAuth($type,$live_id,$user_id)
    {
        $flag = 0;

        // 根据用户账号查询对应门店
        $mapu['id'] = $user_id;
        $resu = User::with('branch')->where($mapu)->find();
        if($resu){
            $sign = $resu['branch']['sign'];

            // 开直播门店和观看同一门店可以观看
            $resl = Live::get($live_id);
            if($resl){
                if($resl['idstore'] == $resu['storeid']){
                    $flag = 1;
                    return $flag;
                }
                // 如果是技术部测试,只能技术部能看
            }

            // 总部直播 => 技术部,总部,办事处 可以  诚美club,美容院 报名
            $mapc = [];
            if($type==1){
                $see_signs = ['666-666','888-888','000-000'];
                if(in_array($sign,$see_signs)){
                    $flag = 1;
                }
            }elseif($type==2){
//                print_r($sign);print_r($flag);die;
                // 技术部直播 => 技术部 可以
                if($sign == '666-666'){
                    $flag = 1;
                }
            }elseif($type==3){
                // 办事处直播 => 技术部,总部,本办事处 可以
                $see_signs = ['666-666','888-888'];
                if(in_array($sign,$see_signs)){
                    $flag = 1;
                }
            }elseif($type==4){
                // 诚美club直播 => 技术部
                if($sign == '998-998'){
                    $flag = 1;
                }
            }elseif($type==5){
                // 美容院直播直播 => 技术部, 所属办事处
                if($sign == '666-666'){
                    $flag = 1;
                }elseif($sign == '000-000'){
                    // 是否是该门店上级办事处:找到办事处编号,根据编号查询下面美容院
                    $mapd['st_department'] = mb_substr($resu['branch']['title'],0,-3,'utf-8');
                    $resd = Department::get($mapd);
                    if($resd){
                        $storeids = [];
                        $mapdr['id_department'] = $resd['id_department'];
                        $resdr = DepartbeautyRelation::all($mapdr);
                        if($resdr){
                            foreach ($resdr as $v) {
                                $storeids[] = $v['id_beauty'];
                            }
                        }
                        if(in_array($resu['storeid'],$storeids)){
                            $flag = 1;
                        }
                    }
                }
            }
            if($flag == 0){
                // 是否存在指定直播间权限,不存在取主播权限
                $mapc1['mobile'] = $resl['user_id'];
                $mapc1['live_id'] = $live_id;
                $resl1 = LiveConf::get($mapc1);
                if ($resl1) {
                    $signs = explode(',',$resl1['store_signs']);
                    $mobiles = explode(',',$resl1['see_mobiles']);
                    // 查询下观看者角色是否在权限中
                    $role = 0;
                    // 店老板
                    if($resu['isadmin']){
                        $role = 1;
                    }elseif(strlen($resu['code']) > 1){
                        $role = 2;// 美容师
                    }else{
                        $role = 3;// 顾客
                    }
                    if($sign == '000-000'){
                        $role = -1;// 办事处
                    }
                    $role = (string)$role;
                    // 在配置的门店权限中
                    if(in_array($sign,$signs) && strstr($resl1['roles'],$role)){
                        $flag = 1;
                    }elseif(in_array($resu['mobile'],$mobiles)){
                        // 在配置的号码权限中
                        $flag = 1;
                    }
                    return $flag;
                }

                // 是否在报名权限中
                $mapc['mobile'] = $resl['user_id'];
                $resl = LiveConf::get($mapc);
                if($resl){
                    $signs = explode(',',$resl['store_signs']);
                    $mobiles = explode(',',$resl['see_mobiles']);

                    // 查询下观看者角色是否在权限中
                    $role = 0;
                    // 店老板
                    if($resu['isadmin']){
                        $role = 1;
                    }elseif(strlen($resu['code']) > 1){
                        $role = 2;// 美容师
                    }else{
                        $role = 3;// 顾客
                    }
                    if($sign == '000-000'){
                        $role = -1;// 办事处
                    }
                    $role = (string)$role;
                    // 在配置的门店权限中
                    if(in_array($sign,$signs) && strstr($resl['roles'],$role)){
                        $flag = 1;
                    }elseif(in_array($resu['mobile'],$mobiles)){
                        // 在配置的号码权限中
                        $flag = 1;
                    }
                }
            }
        }
        return $flag;
    }
    
}