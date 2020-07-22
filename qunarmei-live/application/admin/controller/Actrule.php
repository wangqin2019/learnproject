<?php

namespace app\admin\controller;
use think\Db;

/**
 * 活动商品规则配置
 */
class Actrule extends Base
{

    // 规则备注说明
    protected $remark = [
        1 => '满多少支付多少',
        2 => '任满3件,价格固定',
        3 => '满3减1',
    ];
    // 活动规则有个多个商品id的活动类型
    protected $rules = [2];

    /**
     * 列表
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['sign'] = ['like','%'.$key.'%'];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 90;// 获取总条数
        $count = Db::table('ims_bj_shopn_goods_activity_rules r')
            ->join(['ims_bj_shopn_goods'=>'g'],['g.id=r.goods_id'],'LEFT')
            ->field('r.*,g.title,g.marketprice')
            ->where($map)
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::table('ims_bj_shopn_goods_activity_rules r')
            ->join(['ims_bj_shopn_goods'=>'g'],['g.id=r.goods_id'],'LEFT')
            ->field('r.*,g.title,g.marketprice')
            ->where($map)
            ->order('r.id desc')
            ->limit($pre,$limits)
            ->select();
        if($lists){
            foreach ($lists as $k=>$v) {
                // 状态
                if($v['status']){
                    $lists[$k]['status'] = '<span class="label label-info">开启</span></div>';
                }else{
                    $lists[$k]['status'] = '<span class="label label-danger">关闭</span></div>';
                }
                // 多个商品
                if(in_array($v['type'],$this->rules)){
                    $lists[$k]['title'] = $this->getGoodsTitle($v['goods_id']);
                }
                // 方案类型
                if($v['activity_type']){
                    $lists[$k]['activity_type'] = '买送方案';
                }else{
                    $lists[$k]['activity_type'] = '买赠方案';
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    /**
     * 添加
     */
    public function actruleadd()
    {
        $submit = input('submit',0);
        if($submit){
            $param = input();
            $remark = '';
            if($param['type']){
                $remark = $this->remark[$param['type']];
            }
            $data = [
                'type' => $param['type'],
                'goods_id' => $param['goods_id'],
                'goods_num' => $param['goods_num'],
                'price' => $param['price'],
                'create_time' => date('Y-m-d H:i:s'),
                'status' => $param['status'],
                'remark' => $remark,
                'rules_name' => $param['rules_name'],
                'discount_price' => $param['discount_price'],
                'sale_price' => $param['sale_price'],
                'spread_num' => $param['spread_num'],
                'activity_type' => $param['activity_type'],
                'reduction_num' => $param['reduction_num']
            ];
            // 如果是买赠,配赠价格是优惠价格x商品单价
            if($data['activity_type'] == 1){
                $map['id'] = $data['goods_id'];
                $res_gd = Db::table('ims_bj_shopn_goods')->field('id,marketprice')->where($map)->limit(1)->find();
                if($res_gd){
                    $data['gift_price'] = $res_gd['marketprice'] * $data['reduction_num'];
                }
            }
            $res = Db::table('ims_bj_shopn_goods_activity_rules')->insert($data);
            return json(array('code'=>1,'data' => '','msg' => '添加成功'));
        }
        return $this->fetch();
    }
    /**
     * 编辑
     */
    public function actruleedit()
    {
        $id = input('id',0);
        $submit = input('submit');
        $list = Db::table('ims_bj_shopn_goods_activity_rules')->where('id',$id)->limit(1)->find();
        if($submit){
            $param = input();
            $id = $param['id'];
            $data = [
                'type' => $param['type'],
                'goods_id' => $param['goods_id'],
                'goods_num' => $param['goods_num'],
                'price' => $param['price'],
                'create_time' => date('Y-m-d H:i:s'),
                'status' => $param['status'],
                'rules_name' => $param['rules_name'],
                'discount_price' => $param['discount_price'],
                'sale_price' => $param['sale_price'],
                'spread_num' => $param['spread_num'],
                'activity_type' => $param['activity_type'],
                'reduction_num' => $param['reduction_num']
            ];
            // 如果是买赠,配赠价格是优惠价格x商品单价
            if($data['activity_type'] == 1){
                $map['id'] = $data['goods_id'];
                $res_gd = Db::table('ims_bj_shopn_goods')->field('id,marketprice')->where($map)->limit(1)->find();
                if($res_gd){
                    $data['gift_price'] = $res_gd['marketprice'] * $data['reduction_num'];
                }
            }
            $res = Db::table('ims_bj_shopn_goods_activity_rules')->where('id',$id)->update($data);
            return json(array('code'=>1,'data' => '','msg' => '修改成功'));
        }
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }
    /**
     * 删除
     */
    public function actruledel()
    {
        $id = input('param.id');
        if($id) {
            Db::table('ims_bj_shopn_goods_activity_rules')->where('id',$id)->delete();
            return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
        }
    }

    /**
     * 获取多个商品名称
     * @param string $goods_id 多个商品id,分割
     * @return array
     */
    protected function getGoodsTitle($goods_id)
    {
        $goods_title1 = [];
        $goods_ids = explode(',',$goods_id);
        $map['id'] = ['in',$goods_ids];
        $res = Db::table('ims_bj_shopn_goods')->where($map)->select();
        if($res){
            foreach ($res as $v) {
                $goods_title1[] = $v['title'];
            }
        }
        $goods_title = '';
        if($goods_title1){
            $goods_title = implode(',',$goods_title1);
        }
        return $goods_title;
    }
}