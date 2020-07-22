<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/28
 * Time: 9:29
 * Description:
 */

namespace app\websocket\model;

use think\Model;
class LiveUser extends Model {
    protected $name = 'live_user';
    protected $pk = 'id';

    public function getCreateTimeAttr($value){
        return $value;
    }
    public function getRegisterSocketTimeAttr($value){
        return $value;
    }
    public function getLastHeartbeatTimeAttr($value){
        return $value;
    }
}