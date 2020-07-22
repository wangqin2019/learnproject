<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/21
 * Time: 10:22
 */

namespace app\api\service;

/**
 * missshop商品转客活动服务类
 */
class MissshopTransferActiveSer
{
    // 参与活动用户标识
    protected $actFlag = [8806,8805,8808];

    /**
     * 该用户是否能参与missshop活动
     * @param $user_id
     * @return array
     */
    public function isInActive($user_id)
    {
        // 顾客参与->判断角色,判断active_flag
        $data = [
            'code' => 0,
            'msg' => '该用户没有参与missshop活动权限'
        ];
        $userSer = new User();
        $mapu['id'] = $user_id;
        $resu = $userSer->getUser($mapu);
        if($resu && $resu['activity_flag'] && in_array($resu['activity_flag'],$this->actFlag)){
            // 判断该门店是否能参与
            $mapa['isadmin'] = 1;
            $mapa['storeid'] = $resu['storeid'];
            $mapa['activity_key'] = 1;
            $resadmin = $userSer->getUser($mapa);
            if(!$resadmin){
                $data['msg'] = '该门店没有参与missshop活动权限';
                return $data;
            }
            // 判断角色
            $resrole = $userSer->getUserRole($user_id);
            if($resrole == 3){
                // 判断是否参与过
                $tickSer = new TicketSer();
                $mapt['mobile'] = $resu['mobile'];
                $mapt['type'] = 10;
                $rest = $tickSer->getTick($mapt);
                if(!$rest){
                    // 可以参与
                    $data['code'] = 1;
                    $data['msg'] = '可以参与missshop活动';
                }else{
                    // 已经参与
                    $data['code'] = -1;
                    $data['msg'] = '已经参与过missshop活动';
                }
            }
        }
        return $data;
    }
}