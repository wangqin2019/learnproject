<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/23
 * Time: 14:11
 */

namespace app\api\model;


use think\Model;

class TicketUser extends Model
{
    // 用户卡券表
    protected $table = 'pt_ticket_user';

    // 关联用户表
    public function user()
    {
        return $this->belongsTo('User','mobile','mobile');
    }
    // 关联订单表
    public function order()
    {
        return $this->belongsTo('Order','id','ticket_id');
    }
}