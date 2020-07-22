<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/10/12
 * Time: 14:03
 * Description: 新年上上签活动
 */

namespace app\admin\controller;

use app\admin\model\BlinkCouponUserModel;
use app\admin\model\BlinkShareLogsModel;
use think\Db;
use think\exception\PDOException;
use app\admin\model\BlinkboxCardModel;
use app\admin\model\BlinkboxConfigModel;
use app\admin\model\GoodsModel;
use app\admin\model\BlinkboxImageModel;
use app\admin\model\BlinkOrderModel;
use app\admin\model\BlinkOrderBoxModel;

class Blinkdraw extends Base {
    //上上签配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array = array(
                'status' => $param['status'],
                'start_time' => strtotime($param['start_time']),
                'end_time' => strtotime($param['end_time']),
                'create_time' => time(),
            );
            $id = $param['id'];
            $res = Db::name('blink_box_config')->where('id',2)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '新年上上签配置成功']);
        }
        $a_config= BlinkboxConfigModel::get(2);

        $this->assign('a_config',$a_config);
        return $this->fetch();
    }

    /**
     * Commit: 上上签列表
     * Function: index
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 10:08:33
     * @Return mixed|\think\response\Json
     */
    public function signlist()
    {
        $key = input('key');
        $map = [];
        if($key && $key !== ""){
            $map['c.title|i.name'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        //计算总页面
        $count = Db::name('blink_newyear_content')
            ->alias('c')
            ->join(['pt_blink_box_card_image'=>'i'],'c.rat_id=i.id','left')
            ->where($map)
            ->count();
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('blink_newyear_content')
            ->alias('c')
            ->join(['pt_blink_box_card_image'=>'i'],'c.rat_id=i.id','left')
            ->field("c.*,IFNULL(i.name,'--') name,i.thumb,i.thumb1")
            ->where($map)
            ->order('c.id','asc')
            ->page($Nowpage, $limits)
            ->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);

        $this->assign('count',$count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * Commit: 添加上上签
     * Function: addsign
     * Author: stars<1014916675@qq.com>
     * DateTime: 2020-01-13 15:20:26
     * @Return mixed|\think\response\Json
     */
    public function addsign(){
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            if(empty($param['image'])){
                return json(['code' => 0, 'data' => '', 'msg' => '请上传图片']);
            }
            $array=array(
                'cid' => $param['cid'],
                'title' => $param['title'],//合成商品
                'image' => $param['image'],//赠送商品
                'intro' => $param['intro'],
                'rat_id' => $param['thumb_id'],
                'update_time' => time(),
            );
            $id = $param['id'];
            if($id){
                $res = Db::name('blink_newyear_content')->where('id',$id)->update($array);
            }else{
                $array['create_time'] = time();
                $res = Db::name('blink_newyear_content')->insert($array);
            }
            return json(['code' => 1, 'data' => '', 'msg' => '上上签设置成功']);
        }
        $a_config= Db::name('blink_newyear_content')
            ->where('id',$id)->find();
        $this->assign('a_config',$a_config);
        //关联鼠卡
        $map['type'] = 0;
        //$map['id'] = ['lt',5];
        $rats = Db::name('blink_box_card_image')->where($map)->select();
        $this->assign('rats',$rats);
        return $this->fetch();
    }

    public function delsign()
    {
        $id = input('param.id');
        $status = Db::name('blink_newyear_content')->where('id',$id)->delete();
        if( $status ) {
            return json(['code' => 1, 'data' => '', 'msg' => '上上签删除成功']);
        } else {
            return json(['code' => 0, 'data' => '', 'msg' => '上上签删除失败']);
        }
    }




}