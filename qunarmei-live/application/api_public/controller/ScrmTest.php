<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/4/25
 * Time: 11:17
 */

namespace app\api_public\controller;


use app\api\controller\v3\Common;
use app\api\model\Branch;
use app\api\model\Goods;
use app\api\model\User;

/**
 * scrm测试相关接口
 * Class ScrmTest
 * @package app\api_public\controller
 */
class ScrmTest extends Common
{
    /**
     * 获取所有门店
     * @return \think\response\Json
     */
    public function getStores()
    {
        $res = Branch::all(function($query){
            $query->alias('b')->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'LEFT')->join(['sys_department'=>'sd'],['sdr.id_department=sd.id_department'],'LEFT')->field('b.id storeid,b.title,b.sign,b.address,sd.st_department,b.createtime')->order('createtime desc');
        });
        if($res){
            foreach ($res as $k=>$v) {
                $res[$k]['st_department'] = $v['st_department']==null?'':$v['st_department'];
                $res[$k]['createtime'] = $v['createtime']==0?'':date('Y-m-d H:i:s',$v['createtime']);
            }
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 获取所有商品
     * @return \think\response\Json
     */
    public function getGoods()
    {
        $res = Goods::all(function($query){
            $query->alias('g')->field('g.id goods_id,g.storeid,g.pid,g.pcate,g.isshow,g.title,g.thumb,g.thumbhome,g.marketprice,g.thumb_url,g.isnew,g.ishot,g.rules_good,g.title_detail,g.goods_property,g.live_flag,g.is_give_oto')->order('g.createtime desc');
        });
        if($res){
//            foreach ($res as $k=>$v) {
//                $res[$k]['st_department'] = $v['st_department']==null?'':$v['st_department'];
//                $res[$k]['createtime'] = $v['createtime']==0?'':date('Y-m-d H:i:s',$v['createtime']);
//            }
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);

    }
    /**
     * 获取所有用户
     * @return \think\response\Json
     */
    public function getUsers()
    {
        $res = User::all(function($query){
            $query->alias('m')->join(['ims_fans'=>'f'],['f.id_member=m.id'],'LEFT')->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')->field('m.id user_id,m.storeid,f.avatar,m.realname,m.pid,b.title,b.sign,b.address,m.code,m.isadmin,m.staffid,m.role_id')->order('g.createtime desc');
        });
        if($res){
            foreach ($res as $k=>$v) {
                $arr1['role_id'] = 0;//顾客
                if($v['isadmin']){
                    $arr1['role_id'] = 4;//店老板
                }elseif(strlen($v['code'])>1 && $v['id']==$v['staffid']){
                    if($v['role_id'] != 1){
                        $arr1['role_id'] = 2;//美容师
                    }else{
                        $arr1['role_id'] = 1;//店长
                    }
                }
                $arr1['user_id'] = $v['user_id'];
                $arr1['storeid'] = $v['storeid'];
                $arr1['head_img'] = $v['avatar']==''?config('head_img'):$v['avatar'];
                $arr1['user_name'] = $v['realname'];
                $arr1['pid'] = $v['pid'];
                $arr1['title'] = $v['title'];
                $arr1['sign'] = $v['sign'];
                $arr1['address'] = $v['address'];
            }
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}