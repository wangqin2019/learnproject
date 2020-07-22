<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/22
 * Time: 19:15
 */

namespace app\api\model;


use think\Model;

class LiveConf extends Model
{
    // 直播观看权限配置表
    protected $table = 'think_live_see_conf';
}