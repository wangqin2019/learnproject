<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:18
 */

namespace app\api\model;

use think\Model;
use think\Db;
/*
 * 内衣数据模块辅助查询相关方法
 * */
class ArchivesFunc extends Model
{
    /*
     * 功能: 查询用户个人相关信息
     * 请求: user_id=>用户id
     * */
    public static function userInfo($user_id)
    {
     $map['m.id'] = $user_id;
     $res_user = Db::table('ims_bj_shopn_member m')->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')->join(['underwear_user_info'=>'u'],['m.id=u.user_id'],'LEFT')->field('m.realname user_name,m.mobile,f.avatar head_img,b.title store_name,b.location_p,m.id user_id,u.qrcode_img')->where($map)->limit(1)->find();
     return $res_user;
    }

    /*
     * 功能: 会员档案分类列表
     * 请求: user_id=>用户id
     * */
    public static function archivesCategory()
    {
        $map['isshow'] = 1;
        $res_cat = Db::table('underwear_archives_category c')->field('c.id category_id,c.archives_pic')->where($map)->order('sort desc')->select();
        return $res_cat;
    }
    /*
     * 功能: 查询内衣档案基本信息
     * 请求: user_id=>用户id
     * */
    public static function underwearUserInfo($user_id)
    {
        $map['user_id'] = $user_id;
        $res_user = Db::table('underwear_user_info u')->join(['underwear_conf_month_income'=>'i'],['i.id=u.month_income_id'],'LEFT')->join(['underwear_conf_card'=>'c'],['c.id=u.card_type'],'LEFT')->join(['underwear_conf_occupation'=>'o'],['o.id=u.occupation'],'LEFT')->join(['ims_fans'=>'f'],['f.id_member=u.user_id'],'LEFT')->field('u.user_id,u.user_name,u.sex,u.head_img,u.age,u.height,u.weight,o.occup_name,u.occupation occup_name_id,u.mobile,u.email,u.weixin,u.qq,u.is_return_visit,u.contact_time,u.address,c.card_name,card_type card_name_id,i.range_income,month_income_id range_income_id,u.birthday,f.avatar,f.realname')->where($map)->limit(1)->find();
        if(empty($res_user)){
            // 把个人资料中的信息插进去
            $res_user1 = self::userInfo($user_id);
            if(!empty($res_user1)){
                $data['user_id'] = $user_id;
                $data['user_name'] = $res_user1['user_name'];
                $data['head_img'] = $res_user1['head_img']==null?'':$res_user1['head_img'];
                $data['mobile'] = $res_user1['mobile'];
                $data['create_time'] = time();
                Db::table('underwear_user_info')->insert($data);
                $res_user = Db::table('underwear_user_info u')->join(['underwear_conf_month_income'=>'i'],['i.id=u.month_income_id'],'LEFT')->join(['underwear_conf_card'=>'c'],['c.id=u.card_type'],'LEFT')->join(['underwear_conf_occupation'=>'o'],['o.id=u.occupation'],'LEFT')->join(['ims_fans'=>'f'],['f.id_member=u.user_id'],'LEFT')->field('u.user_id,u.user_name,u.sex,u.head_img,u.age,u.height,u.weight,o.occup_name,u.occupation occup_name_id,u.mobile,u.email,u.weixin,u.qq,u.is_return_visit,u.contact_time,u.address,c.card_name,card_type card_name_id,i.range_income,month_income_id range_income_id,u.birthday,f.avatar,f.realname')->where($map)->limit(1)->find();
            }
        }
        if(!empty($res_user)){
            $data_u = [];
            if($res_user['avatar']){
                $data_u['head_img'] = $res_user['avatar'];
            }
            if($res_user['realname']){
                $data_u['user_name'] = $res_user['realname'];
            }
            $map_u['user_id'] = $user_id;
            Db::table('underwear_user_info')->where($map_u)->update($data_u);
        }
        if(!empty($res_user)){
            $res112 = [];$res113 = [
                'occup' => '',
                'card' => '',
                'range_income' => ''
            ];
            if(!empty($res_user['occup_name_id'])){
                $res111 = json_decode($res_user['occup_name_id'],true);
                $res112['occup_id'] = $res111[0];
                $arr_p = ['ucp_id'=> 41,'pro_id'=>$res112['occup_id']];
                $res111 = self::userProSel($arr_p);
                if(!empty($res111)){
                    $res113['occup'] = $res111['pro_name'];
                }
            }
            if(!empty($res_user['card_name_id'])){
                $res111 = json_decode($res_user['card_name_id'],true);
                $res112['card_name_id'] = $res111[0];
                $arr_p = ['ucp_id'=> 45,'pro_id'=>$res112['card_name_id']];
                $rest11 = self::userProSel($arr_p);
                if(!empty($res111)){
                    $res113['card'] = $rest11['pro_name'];
                }
            }
            if(!empty($res_user['range_income_id'])){
                $res111 = json_decode($res_user['range_income_id'],true);
                $res112['range_income_id'] = $res111[0];
                $arr_p = ['ucp_id'=> 46,'pro_id'=>$res112['range_income_id']];
                $rest11 = self::userProSel($arr_p);
                if(!empty($rest11)){
                    $res113['range_income'] = $rest11['pro_name'];
                }
            }
            if(!empty($res113)){
                $res_user['occup_name'] = $res113['occup'];
                $res_user['card_name'] = $res113['card'];
                $res_user['range_income'] = $res113['range_income'];
            }
            // $arr_p = ['ucp_id'=> 41,'pro_id'=>$res_user['occup_name_id']];
//            $rest = self::userProSel($arr_p);
        }
        return $res_user;
    }
    /*
     * 功能: 查询内衣数据记录
     * 请求: user_id=>用户id
     * */
    public static function underwearRecord($user_id)
    {
        // 功能性调整内衣-全程跟进记录:[用户头像,年月日,分数,提示信息]
        $map['user_id'] = $user_id;
        $res_user = Db::table('underwear_record ')->field('id record_id,head_img,score,tips,color,create_time')->where($map)->order('create_time desc')->select();
        return $res_user;
    }
    /*
     * 功能: 修改内衣档案信息
     * 请求: user_id=>用户id
     * */
    public static function underwearUserInfoUpd($arr)
    {
        $map['user_id'] = $arr['user_id'];
        $res_user = Db::table('underwear_user_info ')->where($map)->update($arr);
        return $res_user;
    }
    /*
     * 功能: 职业列表
     * 请求:
     * */
    public static function occupList()
    {
        $map['isshow'] = 1;
        $res_user = Db::table('underwear_conf_occupation')->field('id occup_id,occup_name')->where($map)->order('id asc')->select();
        return $res_user;
    }
    /*
     * 功能: 会员卡类型列表
     * 请求:
     * */
    public static function cardList()
    {
        $map['isshow'] = 1;
        $res_user = Db::table('underwear_conf_card')->field('id card_id,card_name')->where($map)->order('id asc')->select();
        return $res_user;
    }
    /*
     * 功能: 收入范围列表
     * 请求:
     * */
    public static function incomeList()
    {
        $map['isshow'] = 1;
        $res_user = Db::table('underwear_conf_month_income')->field('id income_id,range_income')->where($map)->order('id asc')->select();
        return $res_user;
    }
    /*
     * 功能: 我的量身-一级目录列表
     * 请求: $id=>目录id
     * */
    public static function measureOne($id=0)
    {
        $map['isshow'] = 1;
        $map['pid'] = 0;
        $map['type'] = 0;
        if($id){
            $map['id'] = $id;
        }
        $res_user = Db::table('underwear_conf_common')->field('id measure_id,name,pic,field_name')->where($map)->order('sort desc')->select();
        return $res_user;
    }
    /*
     * 功能: 我的量身-二级目录
     * 请求: $pid=>上级id
     * */
    public static function measureTwo($pid)
    {
        $map['isshow'] = 1;
        $map['pid'] = $pid;
        $map['type'] = 0;
        $res_user = Db::table('underwear_conf_common')->field('id measure_id,pid,name,pic_select,pic')->where($map)->order('sort desc')->select();
        return $res_user;
    }
    /*
     * 功能: 我的量身-添加记录
     * 请求: $arr
     * */
    public static function measureAdd($arr)
    {
        $res_user = Db::table('underwear_record')->insertGetId($arr);
        return $res_user;
    }
    /*
     * 功能: 我的量身-修改记录-提交
     * 请求: $arr
     * */
    public static function measureUpd($arr)
    {
        $map['id'] = $arr['id'];
        $res_user = Db::table('underwear_record')->where($map)->update($arr);
        return $res_user;
    }
    /*
     * 功能: 内衣档案计算分数规则
     * 请求: $arr
     * */
    public static function shapeScore($arr)
    {
        $score = 0;
        if($arr['bb'] < 28 && $arr['waist']>64 ){
            $score = 90;
        }elseif($arr['bb']>28 && $arr['waist']<64 ){
            $score = 88;
        }else{
            $score = rand(60,88);
        }
        // 内衣档案计算分数
        $res = self::underScoreRule($arr);
        if(!empty($res)){
            $score = $res['score'];
        }
        return $score;
    }
    /*
     * 功能: 查询形体分记录单条
     * 请求:
     * */
    public static function shapeScoreOne($record_id)
    {
        $map['r.id'] = $record_id;
        // 日期,头像,名字,分数,提示语,上级美容师,异常项,bb,右bb,左bb,胸围,下胸围,腰围,臀围,左臀高,右臀高,大腿围
        // [推荐文章列表: 文章封面,标题,简介]
        $res_user = Db::table('underwear_record r')->join(['underwear_user_info'=>'u'],['r.user_id=u.user_id'],'LEFT')->join(['ims_bj_shopn_member'=>'m'],['r.user_id=m.id'],'LEFT')->field('r.create_time,u.head_img,u.user_name,r.score,r.tips,r.pid,r.bb,r.right_bb,r.left_bb,r.bust,r.lower_bust,r.waist,r.hipline,r.thighcir,r.left_hip_height,r.right_hip_height,r.lower_leg,r.ankle,r.bb_flag,r.right_bb_flag,r.left_bb_flag,r.bust_flag,r.lower_bust_flag,r.waist_flag,r.hipline_flag,r.thighcir_flag,r.left_hip_height_flag,r.right_hip_height_flag,r.lower_leg_flag,r.ankle_flag,r.yc_cnt,u.height,r.fill_user_id')->where($map)->limit(1)->find();
        return $res_user;
    }
    /*
     * 功能: 查询推荐文章
     * 请求: $score=>根据分数推荐
     * */
    public static function articleRecommend()
    {
        // 分数推荐文章规则
        // xxx
        // [推荐文章列表: 文章封面,标题,简介]
        $res_art = Db::table('think_find_content')->field('id article_id,cover_img')->order('id desc')->select();
        return $res_art;
    }
    /*
     * 功能: 我的量身-各种属性列表
     * 请求: $proid => 属性id
     * */
    public static function propertyList($proid)
    {
        $map['pid'] = $proid;
        $res = Db::table('underwear_conf_common ')->field('id measure_property_id,name measure_property_name')->where($map)->order('sort desc')->select();
        return $res;
    }
    /*
     * 功能: 查询上级美容师
     * 请求: $id => 美容师id
     * */
    public static function pidMrs($id)
    {
        $map['id'] = $id;
        $res = Db::table('ims_bj_shopn_member ')->field('id user_id,realname user_name')->where($map)->limit(1)->find();
        return $res;
    }
    /*
     * 功能: 尺码编辑-查询
     * 请求:
     * */
    public static function measureList()
    {
        $map['isshow'] = 1;
        $map['type'] = 1;
        $res = Db::table('underwear_conf_property ')->field('id pro_id,pro_name,pro_pic,field_name,pro_name_suffix')->where($map)->select();
        return $res;
    }
    /*
     * 功能: 属性中文名称-查询
     * 请求:
     * */
    public static function zwProperty()
    {
        $map['isshow'] = 1;
        $map['type'] = 1;
        $map['id'] = ['>',1];
        $res = Db::table('underwear_conf_property ')->field('pro_name,field_name')->where($map)->order('id asc')->select();
        return $res;
    }
    /*
     * 功能: 更新fans和member表用户名和头像
     * 请求: $arr => 个人信息
     * */
    public static function FansUpd($arr)
    {
        $map['id'] = $arr['user_id'];
        $arr1['realname'] = $arr['realname'];
        $res_m = Db::table('ims_bj_shopn_member')->where($map)->update($arr1);
        $map1['id_member'] = $arr['user_id'];
        $arr1['avatar'] = $arr['avatar'];
        $res_f = Db::table('ims_fans')->where($map1)->update($arr1);
    }
    /*
     * 功能: 内衣档案-计算分数和标记
     * 请求: $arr => 个人内衣信息资料
     * */
    public static function underScoreRule($arr)
    {
        $flag_times = 0;// 标记不为2的次数
        $rest = [
            'score' => 100,
            'bb_flag' => 2,
            'right_bb_flag' => 2,
            'left_bb_flag' => 2,
            'bust_flag' => 2,
            'lower_bust_flag' => 2,
            'waist_flag' => 2,
            'hipline_flag' => 2,
            'thighcir_flag' => 2,
            'left_hip_height_flag' => 2,
            'right_hip_height_flag' => 2,
            'lower_leg_flag' => 2,
            'ankle_flag' => 2,
            'yc_cnt' => 0
        ];
        // 上升下降标记
        if($arr['bb']>=17){
            $rest['bb_flag'] = 3;
        }elseif($arr['bb']<16.5){
            $rest['bb_flag'] = 1;
        }
        // 上升下降标记
        // r.bb,r.right_bb,r.left_bb,r.bust,r.lower_bust,r.waist,r.hipline,r.thighcir,r.left_hip_height,r.right_hip_height
        if($arr['right_bb']>21){
            $rest['right_bb_flag'] = 3;
        }elseif($arr['right_bb']<19){
            $rest['right_bb_flag'] = 1;
        }
        if($arr['left_bb']>21){
            $rest['left_bb_flag'] = 3;
        }elseif($arr['left_bb']<19){
            $rest['left_bb_flag'] = 1;
        }
        if($arr['bust']>($arr['height']*0.53+2)){
            $rest['bust_flag'] = 3;
        }elseif($arr['bust']<($arr['height']*0.53)){
            $rest['bust_flag'] = 1;
        }
        if($arr['lower_bust']>($arr['height']*0.45+2)){
            $rest['lower_bust_flag'] = 3;
        }elseif($arr['lower_bust']<($arr['height']*0.45)){
            $rest['lower_bust_flag'] = 1;
        }
        if($arr['waist']>($arr['height']*0.37 + 2)){
            $rest['waist_flag'] = 3;
        }elseif($arr['waist']<($arr['height']*0.37)){
            $rest['waist_flag'] = 1;
        }
        if($arr['hipline']>($arr['height']*0.54+ 2)){
            $rest['hipline_flag'] = 3;
        }elseif($arr['hipline']<($arr['height']*0.54)){
            $rest['hipline_flag'] = 1;
        }
        if($arr['hipline']>($arr['height']*0.54 + 2)){
            $rest['hipline_flag'] = 3;
        }elseif($arr['hipline']<($arr['height']*0.54)){
            $rest['hipline_flag'] = 1;
        }
        if($arr['thighcir']>($arr['height']*0.26+7.8+ 2)){
            $rest['thighcir_flag'] = 3;
        }elseif($arr['thighcir']<($arr['height']*0.26+7.8)){
            $rest['thighcir_flag'] = 1;
        }
//        if($arr['left_hip_height']>=($arr['height']*0.26+7.8)){
//            $rest['left_hip_height_flag'] = 3;
//        }elseif($arr['left_hip_height']<($arr['height']*0.26+7.8 + 2)){
//            $rest['left_hip_height_flag'] = 1;
//        }
//        if($arr['right_hip_height']>=($arr['height']*0.26+7.8)){
//            $rest['right_hip_height_flag'] = 3;
//        }elseif($arr['right_hip_height']<($arr['height']*0.26+7.8 + 2)){
//            $rest['right_hip_height_flag'] = 1;
//        }
        // 小腿
        if($arr['lower_leg']>($arr['height']*0.18+ 2)){
            $rest['lower_leg_flag'] = 3;
        }elseif($arr['lower_leg']<($arr['height']*0.18)){
            $rest['lower_leg_flag'] = 1;
        }
        // 脚踝
        if($arr['ankle']>($arr['height']*0.18*0.59+2)){
            $rest['ankle_flag'] = 3;
        }elseif($arr['ankle']<($arr['height']*0.18*0.59)){
            $rest['ankle_flag'] = 1;
        }

        $res = array_count_values($rest);
        if(!empty($res)){
            if(isset($res[1]) && $res[1]){
                $flag_times += $res[1];
            }
            if(isset($res[3]) && $res[3]){
                $flag_times += $res[3];
            }
//            $flag_times = @$res[1] + @$res[3];
        }
        // 每项异常扣减10分
        $rest['score'] = $rest['score'] - $flag_times*10;
        // 每项异常扣减10分,最低显示50分
        $rest['score'] = $rest['score'] - $flag_times*10;
        if($rest['score']<50){
            $rest['score'] = 50;
        }
        $rest['yc_cnt'] = $flag_times;
        return $rest;
    }
    /*
     * 功能: 是否是顾客直属上级美容师
     * 请求: $user_id => 用户id,$pid => 美容师id
     * */
    public static function isPid($user_id,$pid)
    {
        $flag = 0;
        $map['id'] = $user_id;
        $res = Db::table('ims_bj_shopn_member')->field('pid')->where($map)->limit(1)->find();
        if(!empty($res)){
            if($pid == $res['pid']){
                $flag = 1;
            }
        }
        return $flag;
    }
    /*
     * 功能: 非顾客上级美容师记录
     * 请求: $user_id => 用户id,$fill_user_id => 填写美容师id
     * */
    public static function fPidRecord($user_id,$fill_user_id)
    {
        // record_id,head_img,score,tips,color,create_time
        $map['fill_user_id'] = $fill_user_id;
        $map['user_id'] = $user_id;
        $res = Db::table('underwear_record')->field('id,fill_user_id,fill_user_name,user_id,head_img,score,tips,color,create_time')->where($map)->order('create_time desc')->select();
        return $res;
    }
    /*
     * 功能: 修改内衣档案记录数据
     * 请求: $arr => 修改数据
     * */
    public static function underwearRecordUpd($arr)
    {
        $map['id'] = $arr['id'];
        $res = Db::table('underwear_record')->where($map)->update($arr);
        return $res;
    }
    /*
     * 功能: 删除内衣档案记录数据
     * 请求: $id => 记录id
     * */
    public static function underwearRecordDel($id)
    {
        $map['id'] = $id;
        $res = Db::table('underwear_record')->where($map)->delete();
        return $res;
    }
    /*
     * 功能: 查询内衣数据单条记录
     * 请求: $id => 记录id
     * */
    public static function underwearRecordSel($id)
    {
        $map['id'] = $id;
        $res = Db::table('underwear_record')->where($map)->limit(1)->find();
        return $res;
    }
    /*
     * 功能: 个人职业\卡片类型\收入类型
     * 请求: ucp_id => 类型id,pro_id=>选中id
     * */
    public static function userProSel($arr)
    {
        $map['ucp_id'] = $arr['ucp_id'];
        $map['pro_id'] = $arr['pro_id'];
        $res = Db::table('underwear_conf_property_list')->field('*')->where($map)->limit(1)->find();
        return $res;
    }
    /*
     * 功能: 查询用户当日是否已记录
     * 请求: $user_id => 用户id,$fill_user_id=>记录美容师id
     * */
    public static function underDtSel($user_id,$fill_user_id=0)
    {
        $rest = 0;
        $map['user_id'] = $user_id;
        $map['fill_user_id'] = $fill_user_id;
        $map['create_time'] = ['>=',strtotime(date('Y-m-d'))];
        $map1['create_time'] = ['<',strtotime("+1 day")];
        $res = Db::table('underwear_record')->field('id')->where($map)->where($map1)->limit(1)->find();
        if(!empty($res)){
            $rest = $res['id'];
        }
        return $rest;
    }
}