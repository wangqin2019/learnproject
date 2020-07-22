<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/10/12
 * Time: 14:03
 * Description: 平人品活动 应用于老带新环节
 */

namespace app\admin\controller;

use think\Db;
use think\exception\PDOException;
use app\admin\model\BranchModel;
use app\admin\model\GoodsBargainModel;
use app\admin\model\GoodsModel;
use app\admin\model\BargainConfigModel;
use app\admin\model\BargainModel;
use app\admin\model\BargainOrderModel;

class Bargain extends Base {
    //拼人品配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array=array(
                'activity_status' => $param['activity_status'],
                'first_reba' => $param['first_reba'],
                'next_reba' => $param['next_reba'],
                'number' => $param['number'],
                'duration' => $param['duration'],
                'begin_time' => strtotime($param['begin_time']),
                'end_time' => strtotime($param['end_time']),
                'create_time' => time(),
                'show_time' => strtotime($param['show_time'])
            );
            Db::name('bargain_config')->where('id',1)->update($array);
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $a_config= BargainConfigModel::get(1)->toArray();
        $this->assign('a_config',$a_config);
        return $this->fetch();
    }
    /**
     * Commit: 门店列表
     * Function: index
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 10:08:33
     * @Return mixed|\think\response\Json
     */
    public function index()
    {
        $key = input('key');
        $map = [];
        //$map['m.isadmin'] = ['eq',1];
        if($key && $key !== ""){
            $map['bwk.title|bwk.sign|bwk.bargain_plan'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $user = new BargainModel();

        $count = $user->getBargainStoreCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getBargainStore($map, $Nowpage, $limits);
        if(!empty($lists)){
            foreach ($lists as $k=>$val){
                $activitys = '';
                $bargain_plan = $val['bargain_plan'];//活动方案
                if(!empty($bargain_plan)){
                    $bargain_plan_arr = explode(',',$bargain_plan);
                    foreach ($bargain_plan_arr as $v){
                        $bargainPlan = config("bargainPlan.$v");
                        $activitys .= $bargainPlan['name']."<br/>";
                    }
                }
                $lists[$k]['activitys'] = $activitys;
            }
        }


        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('open_store', $user->getOpenStoreCount(1));
        $this->assign('noopen_store', $user->getOpenStoreCount(0));

        $this->assign('count',$count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    public function activity_goods(){
        if(request()->isAjax()){
            try {
                $join_tk='';
                $param = input('post.');
                if(!empty($param['bargain_plan'])){
                    $join_tk=implode(',',$param['bargain_plan']);
                }
                $flag = Db::table('ims_bwk_branch')->where(array('id'=>$param['storeid']))->setField(['bargain_plan'=>$join_tk]);
                return json(['code' => 1, 'data' => $flag['data'], 'msg' => '选择成功']);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '选择失败'.$e->getMessage()]);
            }
        }
        $storeid=input('param.storeid');
        $join_tk= Db::table('ims_bwk_branch')->where(array('id'=>$storeid))->value('bargain_plan');
        $this->assign('storeid',$storeid);
        $this->assign('bargain_plan',$join_tk);
        return $this->fetch();
    }
    public function set_all(){
        //查询开通转客的门店
        $stores = Db::table('ims_bwk_branch')
            ->where('join_tk','like','%3')
            ->column('id');
        //查询活动产品
        /* $activity_goods = Db::name('goods')
             ->where('goods_cate','=',7)
             ->where('status','=',1)
             ->field('id,name,goods_sub')
             ->select();*/
        $goods = array(
            85 => [
                'goods_id' => 92
            ],
            91 => [
                'goods_id' => 93
            ]
        );
        $list = [];
        foreach ($stores as $key=>$val){
            Db::table('ims_bwk_branch')->where('id','=',$val)->setField('is_bargain',1);
            foreach ($goods as $kk=>$vv){
                $data = [];
                $time = time();
                $data['storeid'] = $val;
                $data['pid'] = 0;
                $data['goods_id'] = $kk;
                $data['create_time'] = $time;
                $list[] = $data;

                $gid = $vv['goods_id'];
                $list[] = array(
                    'storeid' => $val,
                    'pid' => $kk,
                    'goods_id' => $gid,
                    'create_time' => $time,
                );
            }
        }
        $re = Db::name('goods_bargain')->insertAll($list);
        var_dump($re);
    }
    /**
     * Commit: 一键添加门店下的活动产品、奖励产品及开启活动
     * Function: addAll
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 13:46:14
     * @Return mixed|\think\response\Json
     */
    public function addAll(){
        //获取总部默认或某一个门店拼人品产品
        $storeid = intval(input('storeid',0));//门店id
        $map = [];
        if(!empty($storeid)){
            $map['storeid'] = [ 'in', [0,$storeid]];
        }
        $map['status'] = 1;
        //活动产品
        $goodsList = Db::name('goods')
            ->field('id,name,goods_cate,is_bargain,status,stock')
            //->where($map)
            ->where('storeid','=',0)
            ->where('goods_cate','=',7)
            ->order('goods_cate asc')
            ->select();
        //奖励产品
        $goodsList1 = Db::name('goods')
            ->field('id,name,goods_cate,is_bargain,status,stock')
            //->where($map)
            ->where('storeid','=',0)
            ->where('goods_cate','=',8)
            ->order('orderby asc')
            ->select();
        if(request()->isAjax()){
            try {
                $storeid = input('param.storeid');//门店
                $promote = input('param.goods_id7/a');//活动产品
                $reward = input('param.goods_id8/a');//奖励产品
                if(empty($storeid)){
                    return json(['code' => 0, 'data' => '', 'msg' => '未选择门店']);
                }
                $goodsBargain = new GoodsBargainModel();
                if(is_array($storeid)){
                    $return = $goodsBargain->setStoreListBargainGoods($storeid,$promote,$reward);
                }else{
                    $return = $goodsBargain->setOneStoreBargainGoods($storeid,$promote,$reward);
                }
                return json($return);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '批量设置失败'.$e->getMessage()]);
            }
        }
        //获取门店
        $branch = new BranchModel();
        $storeList = $branch->getAllBranch();
        $this->assign('storeList',$storeList);
        $this->assign('goodsList',$goodsList);
        $this->assign('goodsList1',$goodsList1);//奖励产品
        return $this->fetch('all');
    }
    /**
     * Commit: 设置门店是否参与拼人品活动
     * Function: state
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:45:11
     * @Return mixed
     */
    public function state(){
        $id = input('param.id');
        $branch = new BranchModel();
        $return = $branch->setBargainState($id);
        //删除关联的商品
        Db::name('goods_bargain')->where('storeid',$id)->delete();
        return $return;
    }
    /**
     * Commit: 参与拼人品的产品及奖励产品
     * Function: goods
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 13:47:22
     * @Return mixed
     */
    public function goods()
    {
        header("Cache-control: private");
        $key = input('key');
        $flag = input('flag')?:0;//奖励产品
        $storeid = intval(input('storeid'));
        $goods_id = intval(input('goods_id'));
        $map = [];
        $map1 = [];
        //门店下的拼人品产品
        if(!empty($flag)){
            $cate = 8;
            $map1['pid'] = $goods_id;
            $map['g.goods_cate'] = ['eq',$cate];//奖励产品
            //$map['g.id'] = 55;//只有一个奖励产品 后续删除
        }else{
            $cate = 7;
            $map['g.goods_cate'] = ['eq',$cate];//拼人品产品
        }
        if($key && $key !== ""){
            $map['g.name'] = ['like',"%" . $key . "%"];
        }
        if(!empty($storeid)){
            $map['g.storeid'] = ['in', [0,$storeid]];//拼人品产品
        }else{
            $map['g.storeid'] = ['eq', 0];//拼人品产品
        }
        $limits = 10;//config('list_rows');// 获取总条数
        $ad = new GoodsModel();
        $lists = $ad->getAllPage($map, $limits);

        $this->assign('val', $key);
        $this->assign('storeid', $storeid);
        $this->assign('goods_id', $goods_id);
        $this->assign('flag', $flag);
        $this->assign('cate', $cate);
        $GoodsBargainModel = new GoodsBargainModel();
        $this->assign('stores', $GoodsBargainModel->getStoreInfo($storeid) );
        $this->assign('activity', $GoodsBargainModel->getActivityGoodsCount($storeid,0) );
        $this->assign('reward', $GoodsBargainModel->getActivityGoodsCount($storeid,0,true) );


        $map1['storeid'] = ['eq', $storeid];//拼人品产品
        $bargain_goods = Db::name('goods_bargain')->where($map1)->column('goods_id');
        $this->assign('bargain_goods', $bargain_goods);//拼人品产品集合
        $this->assign('source', 'bargain');//来源
        $this->assign('lists', $lists);

        return $this->fetch();
    }
    /**
     * Commit: 设置商品是否参与拼人品活动
     * Function: set_bargain
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:45:11
     * @return mixed
     */
    public function set_bargain(){
        $id = input('param.id');
        $branch = new GoodsModel();
        return $branch->setBargain($id);
    }

    /**
     * Commit: 设置拼人品产品 并固定设置奖励商品（goods_id=55）
     * Function: is_bargain_goods
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 13:47:38
     * @Return \think\response\Json
     */
    public function is_bargain_goods()
    {
        $storeid = input('param.storeid');//门店id
        $ids = input('param.ids');//商品集合ids
        $goods_id = input('param.goods_id',0);//活动商品id
        if(empty($storeid) || empty($ids)){
            return json(['code' => 0, 'data' => '', 'msg' => '未选择产品或门店不存在']);
        }
        //删除该门店下的商品
        $goodsBargain = new GoodsBargainModel();
        $_ids = explode(',',$ids);
        $return = $goodsBargain->setStoreBargainGoodsList($storeid,$_ids,$goods_id );

        return json($return);
    }
    /**
     * Commit: 删除拼人品产品及关联的奖励商品
     * Function: delGoods
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 16:07:38
     * @Return \think\response\Json
     */
    public function delGoods()
    {
        $id = input('id');
        $ad = new GoodsModel();

        $info = $ad->getOneInfo($id);//查询商品是否属于基类商品
        if(!empty($info) && $info['storeid'] != 0){
            $flag = $ad->delGoods($id);
        }else{
            $flag['code'] = 1;
            $flag['data'] = '';
            $flag['msg'] = '删除成功';
        }
        if($flag['code'] == 1){
            //删除关联表数据
            Db::name('goods_bargain')->where('goods_id',$id)->delete();
            Db::name('goods_bargain')->where('pid',$id)->delete();
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * Commit: 订单列表
     * Function: order
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 14:18:54
     * @Return mixed|\think\response\Json
     */
    public function order()
    {
        $key = input('key','');
        $sale_uid = input('sale_uid','');
        $pay_status = input('pay_status',88);
        $start = input("param.start",'');
        $end = input("param.end",'');
        $map = [];
        if(!empty($key)) {
            $map['order.order_sn|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if(!empty($sale_uid))
        {
            $map['order.fid'] = ['eq',$sale_uid];
        }
        if($pay_status!=88){
            switch ($pay_status){
                case 1://已支付
                    $map['order.pay_status'] = ['=',1];
                    break;
                case 2://未支付
                    $map['order.pay_status'] = ['=',0];
                    $config = Db::name('bargain_config')->where('id',1)->find();
                    $map['order.insert_time'] = ['>=', time() - $config['duration'] * 3600];
                    break;
                case 3://已失效
                    $map['order.pay_status'] = ['=',0];
                    $config = Db::name('bargain_config')->where('id',1)->find();
                    $map['order.insert_time'] = ['<=', time() - $config['duration'] * 3600];
                    break;
                default:
                    break;
            }
        }

        if(!empty($start) && !empty($end)){
            $map['order.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }
        $bargainOrderModel = new BargainOrderModel();
        $Nowpage = intval(input('get.page',1));
        $limits = config('list_rows');// 获取总条数
        $count = $bargainOrderModel->getOrderCount($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $bargainOrderModel->getOrderLists($map,$Nowpage, $limits);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('sale_uid', $sale_uid);
        $this->assign('pay_status', $pay_status);
        $this->assign('start',$start);
        $this->assign('end',$end);
        //美容师
        $seller = $bargainOrderModel->getOrderBeautician();
        $this->assign('seller', $seller);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * Commit: 导出 exc CSV
     * Function: export
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 14:19:06
     */
    public function export(){
        set_time_limit(0);
        $key = input('key');
        $sale_uid = input('sale_uid');
        $pay_status = input('pay_status',88);
        $export = input('export',0);
        $start = input("param.start");
        $end = input("param.end");
        $map = [];
        if(!empty($key)) {
            $map['order.order_sn|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if(!empty($sale_uid))
        {
            $map['order.fid'] = ['eq',$sale_uid];
        }
        $msg = '';
        if($pay_status!=88){
            switch ($pay_status){
                case 1://已支付
                    $map['order.pay_status'] = ['=',1];
                    $msg = '支付完成';
                    break;
                case 2://未支付
                    $map['order.pay_status'] = ['=',0];
                    $config = Db::name('bargain_config')->where('id',1)->find();
                    $map['order.insert_time'] = ['>=', time() - $config['duration'] * 3600];
                    $msg = '进行中';
                    break;
                case 3://已失效
                    $map['order.pay_status'] = ['=',0];
                    $config = Db::name('bargain_config')->where('id',1)->find();
                    $map['order.insert_time'] = ['<=', time() - $config['duration'] * 3600];
                    $msg = '失效';
                    break;
                default:
                    break;
            }
        }

        if(!empty($start) && !empty($end)){
            $map['order.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }
        $bargainOrderModel = new BargainOrderModel();
        $lists = $bargainOrderModel->getOrderLists($map,'','',false);

        $filename = "拼人品活动{$msg}订单列表".date('YmdHis');
        $header = array(
            array('column' => 'pertain_department_name', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),
            array('column' => 'sellername', 'name' => '美容师名称', 'width' => 15),
            array('column' => 'sellermobile', 'name' => '美容师电话', 'width' => 15),
            array('column' => 'realname', 'name' => '顾客姓名', 'width' => 15),
            array('column' => 'mobile', 'name' => '顾客电话', 'width' => 15),
            array('column' => 'activity_flag', 'name' => '顾客标识码', 'width' => 15),
            array('column' => 'name', 'name' => '商品', 'width' => 30),
            array('column' => 'order_sn', 'name' => '订单号', 'width' => 33),
            array('column' => 'pay_status', 'name' => '支付状态', 'width' => 15),
            array('column' => 'pick_type', 'name' => '取货方式', 'width' => 15),
            array('column' => 'order_status', 'name' => '取货状态', 'width' => 15),
            array('column' => 'order_price', 'name' => '订单金额', 'width' => 15),
            array('column' => 'pay_price', 'name' => '支付金额', 'width' => 15),
            array('column' => 'num', 'name' => '购买数量', 'width' => 15),
            array('column' => 'insert_time', 'name' => '创建时间', 'width' => 20),
            array('column' => 'pay_time', 'name' => '支付时间', 'width' => 20),
            array('column' => 'transaction_id', 'name' => '支付流水号', 'width' => 30),
        );
        if($export==1){
            exportExcel($lists, $header, $filename);//生成数据
        }else{
            exportCsv($lists,$header,$filename);
        }
        die();
    }

    //门店统计 金额 单数 各类型订单数 盒数 美容店
    public function statistics(){
        $key = input('key','');
        $start = input("param.start",'');
        $end = input("param.end",'');
        $type = input("param.type",1);
        $limits = config('list_rows');// 获取总条数
        $id_department = input('param.id_department','');//办事处
        $map = [];
        $map1 = [];
        if(!empty($type)){
            switch ($type){
                case 1://已支付
                    $map['order.pay_status'] = ['=',1];
                    break;
                case 2://未支付
                    $map['order.pay_status'] = ['=',0];
                    $config = Db::name('bargain_config')->where('id',1)->find();
                    $map['order.insert_time'] = ['>=', time() - $config['duration'] * 3600];
                    break;
                case 3://已失效
                    $map['order.pay_status'] = ['=',0];
                    $config = Db::name('bargain_config')->where('id',1)->find();
                    $map['order.insert_time'] = ['<=', time() - $config['duration'] * 3600];
                    break;
                default:
                    break;
            }
        }
        if(!empty($key)) {
            $map['bwk.title|bwk.sign'] = ['like',"%" . $key . "%"];
            $map1['bwk.title|bwk.sign'] = ['like',"%" . $key . "%"];
        }
        if(!empty($start)){
            $a = strtotime($start);
            $map['order.insert_time'] = array('>=', $a);
            $map1['order.insert_time'] = array('>=', $a);
        }
        if(!empty($end)){
            $a = strtotime($end);
            $map['order.insert_time'] = array('<=', $a);
            $map1['order.insert_time'] = array('<=', $a);
        }
        if(!empty($start) && !empty($end)){
            $a = strtotime($start);
            $b = strtotime($end);

            $map['order.insert_time'] = array('between', [min($a,$b), max($a,$b)]);
            $map1['order.insert_time'] = array('between', [min($a,$b), max($a,$b)]);
            $start = date('Y-m-d',min($a,$b));
            $end = date('Y-m-d',max($a,$b));
        }

        $Nowpage = intval(input('get.page',1));
        $count = Db::name('bargain_order')
            ->alias('order')
            ->join(['ims_bwk_branch' => 'bwk'],'order.storeid=bwk.id','left')
            ->where($map)
            ->count('DISTINCT order.storeid');  //总数据
        $allpage = intval(ceil($count / $limits));
        //办事处
        $depart = Db::table('sys_department')
            ->field('id_department,st_department')
            ->where('id_department','not in',['000','001'])
            ->select();
        $this->assign('depart', $depart);
        //办事处下的门店
        $showBids = Db::table('sys_departbeauty_relation')
            ->alias('r')
            ->join(['sys_department' => 'd'], 'r.id_department=d.id_department', 'left')
            ->where('d.id_department', $id_department)
            ->column('id_beauty');
        if($id_department && $id_department !== ""){
            $map['bwk.id'] = ['in',$showBids];
            $map1['bwk.id'] = ['in',$showBids];
        }
        //获取成交量
        $bargainOrderModel = new BargainOrderModel();
        $paydeal = $bargainOrderModel->getPayDeal($map);
        $nodeal = $bargainOrderModel->getUnderWayDeal($map1);
        $this->assign('paydeal', $paydeal);
        $this->assign('nodeal', $nodeal);
        $field = "order.storeid,bwk.title,bwk.sign,count(order.storeid) order_number,sum(order.pay_price) pay_price";
        $field .= ",sum(order.num) num,count(DISTINCT uid) user_number";
        $field .= ",depart.st_department";
        $lists = Db::name('bargain_order')
            ->alias('order')
            ->join(['ims_bwk_branch' => 'bwk'],'order.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and departbeauty.id_sign=bwk.sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map)
            ->page($Nowpage, $limits)
            ->group('order.storeid')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('type', $type);
        $this->assign('val', $key);
        $this->assign('id_department', $id_department);
        $this->assign('start',$start);
        $this->assign('end',$end);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    public function exportStat(){
        $key = input('key','');
        $start = input("param.start",'');
        $end = input("param.end",'');
        $limits = config('list_rows');// 获取总条数
        $map = [];
        if(!empty($key)) {
            $map['bwk.title|bwk.sign'] = ['like',"%" . $key . "%"];
        }
        if(!empty($start)){
            $a = strtotime($start);
            $map['order.insert_time'] = array('>=', $a);
        }
        if(!empty($end)){
            $a = strtotime($end);
            $map['order.insert_time'] = array('<=', $a);
        }
        if(!empty($start) && !empty($end)){
            $a = strtotime($start);
            $b = strtotime($end);

            $map['order.insert_time'] = array('between', [min($a,$b), max($a,$b)]);
        }
        $map['order.pay_status'] = 1;

        $field = "order.storeid,bwk.title,bwk.sign,count(order.storeid) order_number,sum(order.pay_price) pay_price";
        $field .= ",sum(order.num) num,count(DISTINCT uid) user_number";
        $lists = Db::name('bargain_order')
            ->alias('order')
            ->join(['ims_bwk_branch' => 'bwk'],'order.storeid=bwk.id','left')
            ->field($field)
            ->where($map)
            ->select();
        $filename = "拼人品活动订单统计".date('YmdHis');
        $header = array(
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),
            array('column' => 'pay_price', 'name' => '销售金额', 'width' => 15),
            array('column' => 'order_number', 'name' => '订单数量', 'width' => 15),
            array('column' => 'user_number', 'name' => '购买人数', 'width' => 15),
            array('column' => 'num', 'name' => '商品数量', 'width' => 15),
        );
        exportExcel($lists, $header, $filename);//生成数据
        die();
    }





























}