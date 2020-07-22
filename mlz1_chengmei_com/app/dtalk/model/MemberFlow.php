<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/28
 * Time: 9:29
 * Description: 作者审核流
 */

namespace app\dtalk\model;

use think\Model;
class MemberFlow extends Model {
    protected $name = 'cm_members_flow';
    protected $pk = 'flow_id';
    protected $autoWriteTimestamp = true;
}