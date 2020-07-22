<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/28
 * Time: 9:29
 * Description:
 */

namespace app\dtalk\model;

use think\Model;
class Member extends Model {
    protected $name = 'cm_members';
    protected $pk = 'user_id';
    protected $autoWriteTimestamp = true;


    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }

    //关联

    /**
     * Commit: 检测昵称是否存在
     * Function: checkname
     * @Param $nickname
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 09:59:53
     * @Return bool
     */
    public static function checkname($nickname){
        return self::where('nickname','=',$nickname)->find() ? true : false;
    }
    /**
     * Commit: 获取当前用户昵称
     * Function: getNickname
     * @Param $user_id
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 09:59:53
     * @Return bool
     */
    public static function getNickname($user_id = 0){
        return self::where('user_id','=',$user_id)->value('nickname');
    }
    /**
     * Commit: 检测当前用户是否有权限编辑文章
     * Function: checkUserWhetherHaveAuthWriter
     * @Param int $user_id
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 09:59:53
     * @Return bool true|false
     */
    public static function checkUserWhetherHaveAuthWriter($user_id = 0){
        if(empty($user_id)){
            return false;
        }
        $map[] = [ 'user_id', '=', $user_id];
        $map[] = [ 'type', '>', 0];//是否是作者 1、2是 0不是
        $ischeck = self::where($map)->value('ischeck');
        return $ischeck == 1 ? true : false;
    }
    /**
     * Commit: 根据用户user_id获取当前用户信息
     * Function: checkUserWhetherHaveAuthWriter
     * @Param int $user_id
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 09:59:53
     * @Return bool true|false
     */
    public static function getCurrentUserInfoAccordUID($user_id = 0){
        return self::where('user_id', '=', $user_id)->find();
    }
    /**
     * Commit: 根据用户手机号获取当前用户信息
     * Function: getUserMobileInfo
     * @Param int $user_id
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 09:59:53
     * @Return bool true|false
     */
    public static function getUserMobileInfo($mobile = ''){
        return self::where('mobile', '=', $mobile)->find()->toArray();
    }
}