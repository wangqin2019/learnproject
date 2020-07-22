<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/1/10
 * Time: 11:42
 */

namespace app\api\model;

use think\Db;
use think\Model;
/*
 * oto教育相关数据表
 * */
class OtoMod extends Model
{
    // 对应数据表明
    protected $table = 'ims_bj_shopn_oto';
    /*
     * 功能:账号和用户表联查
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function getOtoMem($map)
    {
        $res = self::alias('o')
            ->join(['pt_ticket_user'=>'u'],['u.id=o.card_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['u.user_id=m.id'],'LEFT')
            ->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')
            ->field('m.realname,m.mobile,f.avatar,o.oto_user,o.oto_pwd')
            ->where($map)
            ->limit(1)
            ->find();
        return $res;
    }
    /*
     * 功能:账户战绩数据查询
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function getRecordList($map)
    {
        $res = self::alias('o')
            ->join(['ims_bj_shopn_oto_records'=>'r'],['o.oto_user=r.oto_user'],'LEFT')
            ->field('r.oto_user,r.ranking,r.user_name,r.coin_num,r.first_login_time,r.last_login_time,r.word_num,r.online_time,r.clearance_num')
            ->where($map)
            ->order('r.coin_num desc')
            ->select();
        return $res;
    }
    /*
     * 功能:获取锦囊数据列表
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function getSilkbags($map)
    {
        $res = Db::table('ims_bj_shopn_oto_silkbag')
            ->where($map)
            ->field('word,word_ch')
            ->order('create_time desc')
            ->select();
        return $res;
    }
    /*
     * 功能:获取常见问题列表
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function getQas($map)
    {
        $res = Db::table('ims_bj_shopn_oto_qa')
            ->where($map)
            ->field('question,answer')
            ->order('create_time desc')
            ->select();
        return $res;
    }
    /*
     * 功能:获取OTO学习卡
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function getTicketUser($map)
    {
        $res = Db::table('pt_ticket_user u')
            ->where($map)
            ->field('type,id')
            ->limit(1)
            ->find();
        return $res;
    }
    /*
     * 功能:查询用户所在门店及办事处
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function getUserBranch($map)
    {
        $res = Db::table('ims_bj_shopn_member m')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->join(['sys_departbeauty_relation'=>'sdy'],['sdy.id_beauty=b.id'],'LEFT')
            ->join(['sys_department'=>'sd'],['sd.id_department=sdy.id_department'],'LEFT')
            ->where($map)
            ->field('b.title,b.sign,b.id,sd.st_department,m.id')
            ->limit(1)
            ->find();
        return $res;
    }

    /*
     * 功能:插入OTO教育卡
     * 请求:$map=>[查询条件]
     * 返回:json
     * */
    public function insTicket($data)
    {
        $res = Db::table('pt_ticket_user')
            ->insertGetId($data);
        return $res;
    }
}