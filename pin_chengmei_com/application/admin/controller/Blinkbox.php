<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/10/12
 * Time: 14:03
 * Description: 盲盒活动
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
use think\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Blinkbox extends Base {
    public function text(){
        set_time_limit(0);
        $uid = [
            22102,25225,26595,28121,32376,64613,70089,70693,
        ];
        $param['type'] = 2;
        $param['par_value'] = 100;
        $list = Db::name('blink_box_coupon_user')
            ->where($param)
            ->select();
        $insert = [];
        $j = 0;
        foreach ($list as $k=>$val){
            $code = $val['ticket_code'];
            $res = Db::name('blink_box_coupon_user')->where('id',$val['id'])->update([
                'qrcode' => pickUpCode('blinkcompose_'.$code)
            ]);
            var_dump($res);
        }
    }
    //盲盒配置
    public function config(){
        if(request()->isAjax()){
            $param = input('post.');
            $array = array(
                'status' => $param['status'],
                'number' => $param['number'],
                'win_number' => $param['win_number'],
                'box_number' => $param['box_number'],
                'share_goods' => $param['share_goods'],//赠送商品
                'start_time' => strtotime($param['start_time']),
                'end_time' => strtotime($param['end_time']),
                'create_time' => time(),
            );
            $id = $param['id'];
            $res = Db::name('blink_box_config')->where('id',1)->update($array);
            //更新卡表
            /*if($id){
                //删除商品
                Db::name('blink_box_goods')->where('cid',$id)->delete();
            }
            //盲盒开盒商品
            $goods = $param['goods_id'];
            $insert = [];
            foreach ($goods as $val){
                //默认产品
                $insert[] = [
                    'goods_id' => $val,
                    'cid' => 1,
                    'type' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ];
            }
            //合成鼠卡赠送的产品
            $compose = $param['compose_goods'];
            if(!empty($compose)){
                foreach ($compose as $val) {
                    $insert[] = [
                        'goods_id' => $val,
                        'cid' => 1,
                        'type' => 2,
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                }
            }
            Db::name('blink_box_goods')->insertAll($insert);*/
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $a_config= BlinkboxConfigModel::get(1);
        //->where('deputy_cate',1 )
        $goods = GoodsModel::where('goods_cate',11)->select();
        if(!empty($a_config)){
            $a_config['goods'] = Db::name('blink_box_goods')
                ->where('cid',$a_config['id'])
                ->where('type',0)
                ->column('goods_id');
            $a_config['compose_goods'] = Db::name('blink_box_goods')
                ->where('cid',$a_config['id'])
                ->where('type',2)
                ->column('goods_id');
        }
        $this->assign('a_config',$a_config);
        $this->assign('goods',$goods);
        return $this->fetch();
    }
    //盲盒商品
    public function goods(){
        $key = input('key');
        $map = [];
        if($key && $key !== ""){
            $map['c.name|g.name'] = ['like',"%" . $key . "%"];
        }
        $map['c.id'] = 1;
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数
        //计算总页面
        $count = Db::name('blink_box_goods')
            ->alias('bbg')
            ->field('bbg.*,g.name,g.image,c.name activity_name')
            ->join(['pt_goods'=>'g'],'g.id=bbg.goods_id','left')
            ->join(['pt_blink_box_config'=>'c'],'c.id=bbg.cid','left')
            ->where($map)
            ->count();
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('blink_box_goods')
            ->alias('bbg')
            ->field('bbg.*,g.name,g.image,c.name activity_name')
            ->join(['pt_goods'=>'g'],'g.id=bbg.goods_id','left')
            ->join(['pt_blink_box_config'=>'c'],'c.id=bbg.cid','left')
            ->where($map)
            ->page($Nowpage, $limits)
            ->select();
        if(!empty($lists)){
            foreach ($lists as $k=>$val){
                if($val['type'] == 1){
                    $lists[$k]['type'] =  '分享赠送产品';
                }else if($val['type'] == 2){
                    $lists[$k]['type'] =  '合成鼠卡赠送产品';
                }else{
                    $lists[$k]['type'] =  '盲盒中产品';
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);

        $this->assign('count',$count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    public function addgoods(){
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            $array=array(
                'cid' => $param['cid'],
                'num' => $param['num'],
                'type' => $param['type'],
                'goods_id' => $param['goods_id'],
                'update_time' => time(),
            );
            $id = $param['id'];
            if($id){
                $res = Db::name('blink_box_goods')->where('id',$id)->update($array);
            }else{
                $array['create_time'] = time();
                $res = Db::name('blink_box_goods')->insert($array);
            }
            return json(['code' => 1, 'data' => '', 'msg' => '商品配置成功']);
        }
        $info = Db::name('blink_box_goods')
            ->alias('bbg')
            ->field('bbg.*,g.name,g.image,c.name activity_name')
            ->join(['pt_goods'=>'g'],'g.id=bbg.goods_id','left')
            ->join(['pt_blink_box_config'=>'c'],'c.id=bbg.cid','left')
            ->where('bbg.id',$id)
            ->find();
        $this->assign('info',$info);
        $this->assign('activity',Db::name('blink_box_config')->select());
        $this->assign('goods',Db::name('goods')->where('goods_cate',11)->select());
        return $this->fetch();
    }

    /**
     * Commit: 门店列表
     * Function: index
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 10:08:33
     * @Return mixed|\think\response\Json
     */
    public function store()
    {
        $key = input('key');
        $map = [];
        //$map['m.isadmin'] = ['eq',1];
        if($key && $key !== ""){
            $map['bwk.title|bwk.sign'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        //计算总页面
        $count = Db::table('ims_bwk_branch')
            ->alias('bwk')
            ->where($map)
            ->count();
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('ims_bwk_branch')
            ->alias('bwk')
            ->field('bwk.id,bwk.title,bwk.sign,bwk.is_blink,m.realname,m.mobile,s.status,dep.st_department')
            ->join(['ims_bj_shopn_member'=>'m'],'bwk.id=m.storeid and m.isadmin=1','left')
            ->join(['pt_blink_box_store'=>'s'],'bwk.id=s.storeid','left')
            ->join(['sys_departbeauty_relation'=>'deprel'],'bwk.id=deprel.id_beauty','left')
            ->join(['sys_department'=>'dep'],'dep.id_department=deprel.id_department','left')
            ->where($map)
            ->page($Nowpage, $limits)
            ->group('bwk.id')
            ->order(['bwk.is_blink'=>'desc','bwk.id'=>'asc'])
            ->select();
        if(!empty($lists)){
            foreach ($lists as $k=>$val){
                if($val['status'] == 1){
                    $lists[$k]['status1'] =  '非missshop顾客';
                }else if($val['status'] == 2){
                    $lists[$k]['status1'] =  'missshop顾客';
                }else if($val['status'] == 3 ){
                    $lists[$k]['status1'] =  '全部顾客';
                }else{
                    $lists[$k]['status1'] =  '--';
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('open_store', Db::table('ims_bwk_branch')->where('is_blink',1)->count());
        $this->assign('noopen_store', Db::table('ims_bwk_branch')->where('is_blink',0)->count());

        $this->assign('count',$count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    public function storeExport()
    {
        set_time_limit(0);
        ini_set("memory_limit", "4024M");
        $export = input('export',0);
        $key = input('key');
        $map = [];
        //$map['m.isadmin'] = ['eq',1];
        if($key && $key !== ""){
            $map['bwk.title|bwk.sign'] = ['like',"%" . $key . "%"];
        }

        $lists = Db::table('ims_bwk_branch')
            ->alias('bwk')
            ->field('bwk.id,bwk.title,bwk.sign,bwk.is_blink,m.realname,m.mobile,s.status,dep.st_department')
            ->join(['ims_bj_shopn_member'=>'m'],'bwk.id=m.storeid  and m.isadmin=1','left')
            ->join(['pt_blink_box_store'=>'s'],'bwk.id=s.storeid','left')
            ->join(['sys_departbeauty_relation'=>'deprel'],'bwk.id=deprel.id_beauty','left')
            ->join(['sys_department'=>'dep'],'dep.id_department=deprel.id_department','left')
            ->where($map)
            ->group('bwk.id')
            ->order(['bwk.is_blink'=>'desc','bwk.id'=>'asc'])
            ->select();
        if(!empty($lists)){
            foreach ($lists as $k=>$val){
                if($val['status'] == 1){
                    $lists[$k]['status'] =  '非missshop顾客';
                }else if($val['status'] == 2){
                    $lists[$k]['status'] =  'missshop顾客';
                }else if($val['status'] == 3 ){
                    $lists[$k]['status'] =  '全部顾客';
                }else{
                    $lists[$k]['status'] =  '--';
                }

                if($val['is_blink'] == 1){
                    $lists[$k]['is_blink'] = '已开通';
                }else{
                    $lists[$k]['is_blink'] = '未开通';
                }
            }
        }


        $filename = "盲盒活动门店列表".date('YmdHis');
        $header = array(
            array('column' => 'st_department', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),

            array('column' => 'realname', 'name' => '姓名', 'width' => 15),
            array('column' => 'mobile', 'name' => '电话', 'width' => 15),
            array('column' => 'status', 'name' => '使用人群', 'width' => 15),
            array('column' => 'is_blink', 'name' => '是否开通', 'width' => 15),
        );
        if($export==1){
            exportExcel($lists, $header, $filename);//生成数据
        }else{
            exportCsv($lists,$header,$filename);
        }
        die();
    }
    /**
     * Commit: 设置门店是否参与活动
     * Function: state
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 09:45:11
     * @Return mixed
     */
    public function state(){
        $id = input('param.id');
        try{
            $status = Db::table('ims_bwk_branch')->where('id',$id)->value('is_blink');//判断当前状态情况
            if($status == 1){
                Db::table('ims_bwk_branch')->where('id',$id)->setField(['is_blink'=>0]);
                return json(['code' => 1, 'data' => '', 'msg' => '已取消参与']);
            }else{
                Db::table('ims_bwk_branch')->where('id',$id)->setField(['is_blink'=>1]);
                //检测是否添加门店数据
                //添加使用人群
                $sid = Db::name('blink_box_store')->where('storeid',$id)->value('id');
                if(empty($sid)){
                    Db::name('blink_box_store')->insert([
                        'storeid' => $id,
                        'status' => 3,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                }
                return json(['code' => 0, 'data' => '', 'msg' => '参与成功']);
            }
        }catch (PDOException $e){
            return json(['code' => 0, 'data' => '', 'msg' => $e->getMessage()]);
        }
    }
    public function activity_goods(){
        if(request()->isAjax()){
            try {
                $param = input('post.');
                //检测门店是否开通
                $is_blink = Db::table('ims_bwk_branch')->where('id',$param['storeid'])->value('is_blink');
                if(empty($is_blink)){
                    Db::table('ims_bwk_branch')->where('id',$param['storeid'])->update([
                        'is_blink' => 1,
                        'updatetime' => time()
                    ]);
                }
                $id = Db::name('blink_box_store')->where('storeid',$param['storeid'])->value('id');
                if(!empty($id)){
                    Db::name('blink_box_store')->where('storeid',$param['storeid'])->setField('status',$param['status']);
                }else {
                    Db::name('blink_box_store')->insert([
                        'storeid' => $param['storeid'],
                        'status' => $param['status'],
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                }

                return json(['code' => 1, 'data' => '', 'msg' => '选择成功']);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '选择失败'.$e->getMessage()]);
            }
        }
        $storeid = input('param.storeid');
        $this->assign('storeid',$storeid);
        $this->assign('status',Db::name('blink_box_store')->where('storeid',$storeid)->value('status'));
        return $this->fetch();
    }
    public function addAll(){
        if(request()->isAjax()){
            try {
                $sign = input('param.sign');//门店
                $status = input('param.status');//活动产品
                if(empty($sign)){
                    return json(['code' => 0, 'data' => '', 'msg' => '未选择门店']);
                }
                $sign_arr = explode("\n",$sign);
                //查询门店并开通
                if(!empty($sign_arr)){
                    $tmp = '';
                    foreach ($sign_arr as $v){
                        $sid = Db::table('ims_bwk_branch')->where('sign',$v)->find();
                        if(empty($sid)){
                            $tmp .= "{$v}  ";
                            continue;
                        }
                        if($sid['is_blink'] == 0){
                            Db::table('ims_bwk_branch')->where('sign',$v)->setField('is_blink',1);
                            Db::name('blink_box_store')->insert([
                                'storeid' => $sid['id'],
                                'status' => $status,
                                'create_time' => time(),
                                'update_time' => time(),
                            ]);
                        }
                    }
                    if(!empty($tmp)){
                        $msg = '以下门店不存在：'.$tmp;
                    }else{
                        $msg = '';
                    }
                    return json(['code' => 1, 'data' => '', 'msg' => '门店设置成功'.$msg]);
                }else{
                    return json(['code' => 0, 'data' => '', 'msg' => '门店不存在']);
                }
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '批量设置失败'.$e->getMessage()]);
            }
        }

        return $this->fetch('all');
    }


    /**
     * Commit: 鼠卡列表
     * Function: ratcard
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-30 10:52:06
     * @Return mixed|\think\response\Json
     */
    public function ratcard()
    {
        $key = input('key');
        $map = [];
        if($key && $key !== ""){
            $map['name'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        //计算总页面
        $count = BlinkboxImageModel::where($map)->count();
        $allpage = intval(ceil($count / $limits));
        $lists = BlinkboxImageModel::where($map)
            ->order('cid','asc')
            ->paginate();
        if(!empty($limits)){
            foreach ($lists as $k=>$val){
                $lists[$k]['activity_name'] = Db::name('blink_box_config')->where('id',$val['cid'])->value('name');
            }
        }

        $this->assign('page', $lists->render()); //当前页
        $this->assign('val', $key);
        $this->assign('lists', $lists);

        $this->assign('count',$count);
        return $this->fetch();
    }
    public function addrat(){
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            $array=array(
                'cid' => $param['cid'],
                'number' => $param['number'],
                'name' => $param['name'],//合成商品
                'thumb' => $param['thumb'],//赠送商品
                'thumb1' => $param['thumb1'],
                'type' => $param['type'],
                'intro' => $param['intro'],
                'update_time' => time(),
            );
            $id = $param['id'];
            if($id){
                $res = BlinkboxImageModel::where('id',$id)->update($array);
            }else{
                $array['create_time'] = time();
                $res = BlinkboxImageModel::insert($array);
            }
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
        }
        $a_config= BlinkboxImageModel::get($id);
        $this->assign('a_config',$a_config);
        $this->assign('activity',Db::name('blink_box_config')->select());
        return $this->fetch();
    }


    /**
     * Commit: 卡片列表
     * Function: card
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-15 10:08:33
     * @Return mixed|\think\response\Json
     */
    public function card()
    {
        $key = input('key');
        $map = [];
        if($key && $key !== ""){
            $map['card.cardno'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $BlinkboxCard = new BlinkboxCardModel();
        //计算总页面
        $count = $BlinkboxCard->getCardCount($map);
        $allpage = intval(ceil($count / $limits));
        $lists = $BlinkboxCard->getCardLists($map,$Nowpage,$limits);

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
     * Commit: 订单列表
     * Function: order
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 14:18:54
     * @Return mixed|\think\response\Json
     */
    public function order()
    {
        $key = trim(input('key'));
        $sale_uid = input('sale_uid','');
        $pay_status = input('pay_status',88);
        $start = input("param.start",'');
        $end = input("param.end",'');
        $map = [];
        if(!empty($key)) {
            $map['order.order_sn|bwk.title|depart.st_department|member.realname|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if(!empty($sale_uid))
        {
            //检测美容师门店是否是1792

            $map['order.fid'] = ['eq',$sale_uid];
        }
        if($pay_status!=88){
            $map['order.pay_status'] = ['=',$pay_status];
        }

        if(!empty($start) && !empty($end)){
            $map['order.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }
        $BlinkOrderModel = new BlinkOrderModel();
        $Nowpage = intval(input('get.page',1));
        $limits = config('list_rows');// 获取总条数
        $count = $BlinkOrderModel->getOrderCount($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $BlinkOrderModel->getOrderLists($map,$Nowpage, $limits);
        if(!empty($lists)){
            foreach ($lists as $k=>$val){
                $lists[$k]['operate'] = url('box',array('order_id'=>$val['id']));
            }
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('sale_uid', $sale_uid);
        $this->assign('pay_status', $pay_status);
        $this->assign('start',$start);
        $this->assign('end',$end);
        //订单统计
        $order = Db::name('blink_order')
            ->where('pay_status',1)
            ->field('sum(pay_price) pay_price,count(id) count,count(DISTINCT uid) number,count(DISTINCT storeid) storeNum')
            ->find();
        $this->assign('order',$order);
        //美容师
        $seller = $BlinkOrderModel->getOrderBeautician();
        $this->assign('seller', $seller);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    public function customReport(){
        $start = input("param.start",'');
        $end = input("param.end",'');


        $this->assign('start',$start);
        $this->assign('end',$end);
        return $this->fetch();
    }
    public function customReport1(){
        set_time_limit(0);
        $start = input("param.start",'');
        $end = input("param.end",'');

        $map = [];
        if(!empty($start) && !empty($end)){
            $start = strtotime($start);
            $end = strtotime($end);
            $map['pay_time'] = ['between',[$start,$end]];
            $map['pay_status'] = 1;
        }
        //查询当前办事处
        $department = Db::table('sys_department')
            ->alias('a')
            ->field('a.*,b.id_beauty')
            ->join(['sys_departbeauty_relation'=>'b'],"a.id_department = b.id_department and b.id_sign='000-000'",'left')
            ->select();
        $depart = [];
        $sort = [];
        foreach ($department as $k=>$val){
            $id_department = $val['id_department'];
            //办事处下属门店集合
            $storeids = Db::table('sys_departbeauty_relation')
                ->where('id_department',$id_department)
                ->column('id_beauty');
            if(!empty($storeids)){
                //1.查询当前门店是否有交易
                $count = Db::name('blink_order')
                    ->where('storeid','in',$storeids)
                    ->where($map)
                    ->count('DISTINCT storeid');

            }else{
                $count = 0;
            }
            $sort[] = $count;
            //第一张报表
            $depart[$k] = [
                'st_department' => $val['st_department'],//市场
                'id_department' => $val['id_department'],
                'id_beauty'     => $val['id_beauty'],
                'count'         => $count,//门店成绩数量
                'total'         => count($storeids),//门店数量
            ];
        }
        array_multisort($sort,SORT_DESC,$depart);
        //var_dump($depart);

        //报表2
        $custom = [];
        foreach ($department as $k=>$val){
            $id_department = $val['id_department'];
            //办事处下属门店集合
            $storeids = Db::table('sys_departbeauty_relation')
                ->where('id_department',$id_department)
                ->column('id_beauty');
            //------------成交客数-----------------
            //查询办事处对应的门店ID
            $banshichu_id = Db::table('sys_departbeauty_relation')
                ->where('id_department',$id_department)
                ->where('id_sign','000-000')
                ->value('id_beauty');
            if(!empty($banshichu_id)) {
                //1.查询办事处员工(美容师 店老板)
                $aaa['isadmin'] = 1;
                $aaa['storeid'] = $banshichu_id;
                $bsc_staff_ids = Db::table('ims_bj_shopn_member')
                    ->where("code <> '' and id = staffid and storeid={$banshichu_id}")
                    ->whereOr("isadmin=1 and storeid={$banshichu_id}")
                    ->column('id');
                if (!empty($bsc_staff_ids)) {
                    //1.2.1员工成交客数
                    $count = Db::name('blink_order')
                        ->where('storeid', 'in', $banshichu_id)
                        ->where($map)
                        ->where('uid', 'in', $bsc_staff_ids)
                        ->count('DISTINCT uid');
                } else {
                    $count = 0;
                }
                //2.查询办事处顾客
                $bsc_kh_ids = Db::table('ims_bj_shopn_member')
                    ->where("code = '' and id != staffid and storeid={$banshichu_id}")
                    ->column('id');
                if (!empty($bsc_kh_ids)) {
                    //2.2.1顾客成交客数
                    $bsc_kh_count = Db::name('blink_order')
                        ->where('storeid', 'in', $banshichu_id)
                        ->where($map)
                        ->where('uid', 'in', $bsc_kh_ids)
                        ->count('DISTINCT uid');
                } else {
                    $bsc_kh_count = 0;
                }
                //3.查询办事处下门店美容师(美容师 店老板)
                $bbb['isadmin'] = 1;
                $bbb['storeid'] = ['in',$storeids];
                $md_staff_ids = Db::table('ims_bj_shopn_member')
                    ->where('storeid','in', $storeids)
                    ->where("code <> '' and id = staffid")
                    ->whereOr($bbb)
                    ->column('id');
                if (!empty($md_staff_ids)) {
                    //2.2.1顾客成交客数
                    $fid_kh_count = Db::name('blink_order')
                        ->where('storeid', 'in', $storeids)
                        ->where($map)
                        ->where('uid', 'in', $md_staff_ids)
                        ->count('DISTINCT uid');
                } else {
                    $fid_kh_count = 0;
                }
                //4.查询办事处下门店顾客
                $md_kh_ids = Db::table('ims_bj_shopn_member')
                    ->where('storeid','in', $storeids)
                    ->where("code = '' and id != staffid")
                    ->column('id');
                if (!empty($md_kh_ids)) {
                    //2.2.1顾客成交客数
                    $md_kh_count = Db::name('blink_order')
                        ->where('storeid', 'in', $storeids)
                        ->where($map)
                        ->where('uid', 'in', $md_kh_ids)
                        ->count('DISTINCT uid');
                } else {
                    $md_kh_count = 0;
                }
                //合计
                $total = $md_kh_count + $fid_kh_count + $bsc_kh_count + $count;


                //第2张报表
                $custom[$k] = [
                    'st_department'   => $val['st_department'],//市场
                    'id_department'   => $val['id_department'],
                    'storeid'         => $banshichu_id,
                    'bsc_count'       => $count,//办事处员工成交客数
                    'bsc_kh_count'    => $bsc_kh_count,//办事处客户成交客数
                    'bsc_md_count'    => $fid_kh_count,//办事处下门店美容师成交客数
                    'bsc_md_kh_count' => $md_kh_count,//办事处下门店美容师客户成交客数
                    'total'           => $total,//小计
                ];
            }else{
                $custom[$k] = [
                    'st_department'   => $val['st_department'],//市场
                    'id_department'   => $val['id_department'],
                    'storeid'         => $val['id_beauty'],
                    'bsc_count'       => 0,//办事处员工成交客数
                    'bsc_kh_count'    => 0,//办事处客户成交客数
                    'bsc_md_count'    => 0,//办事处下门店美容师成交客数
                    'bsc_md_kh_count' => 0,//办事处下门店美容师客户成交客数
                    'total'           => 0,//小计
                ];
            }
        }

        var_dump($custom);exit;

    }
    //盲盒列表
    public function box(){
        $order_id = input("param.order_id",'');
        $key = input("param.key",'');
        $map = [];
        if(!empty($order_id)) {
            $map['box.order_id'] = $order_id;
        }
        if(!empty($key)) {
            $map['box.blinkno'] = $key;
        }
        $BlinkOrderBoxModel = new BlinkOrderBoxModel();
        $Nowpage = intval(input('get.page',1));
        $limits = config('list_rows');// 获取总条数
        $count = $BlinkOrderBoxModel->getBoxCount($map);  //总数据

        $member = $BlinkOrderBoxModel->getCurrentOrderUserInfo($order_id);

        $allpage = intval(ceil($count / $limits));
        $lists = $BlinkOrderBoxModel->getBoxLists($map,$Nowpage, $limits);
        if(!empty($lists)){
            foreach ($lists as $k=>$val){
                $uid = $val['parent_owner'];
                if(empty($uid)){
                    $lists[$k]['parent_name'] = $member['realname'];
                }else{
                    $lists[$k]['parent_name'] = Db::table('ims_bj_shopn_member')->where('id',$uid)
                        ->value('realname');
                }
            }
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('key', $key);
        $this->assign('order_id', $order_id);
        $this->assign('member', $member);

        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    //查询盲盒分享记录
    public function boxShareRecord(){
        $uid = input('param.uid');
        $blinkno = trim(input('param.blinkno'));
        $map = [];
        if(!empty($uid)){
            $map['uid'] = $uid;
        }
        if(!empty($blinkno)){
            $map['code'] = $blinkno;
        }
        $BlinkShareLogsModel = new BlinkShareLogsModel();
        $list = $BlinkShareLogsModel->where($map)->select();

        $this->assign('list',$list);
        return $this->fetch();
    }
    //查询盲盒中鼠卡记录
    public function boxRatsRecord(){
        $uid = input('param.uid');
        $blinkno = trim(input('param.blinkno'));
        $map = [];
        if(!empty($uid)){
            $map['card.uid'] = $uid;
        }
        if(!empty($blinkno)){
            $map['card.blinkno'] = $blinkno;
        }
        $list = Db::name('blink_order_box_card')
            ->alias('card')
            ->join(['pt_blink_box_card_image'=>'image'],'card.thumb_id=image.id','left')
            ->field('card.*,image.name,image.thumb')
            ->where($map)
            ->select();
        $this->assign('list',$list);

        return $this->fetch();
    }
    //查询盲盒中鼠卡记录
    public function boxCouponsRecord(){
        $uid = input('param.uid');
        $blinkno = trim(input('param.blinkno'));
        $map = [];
        if(!empty($uid)){
            $map['cu.uid'] = $uid;
        }
        if(!empty($blinkno)){
            $map['cu.blinkno'] = $blinkno;
        }
        $list = Db::name('blink_box_coupon_user')
            ->alias('cu')
            ->join(['pt_goods'=>'g'],'cu.goods_id=g.id','left')
            ->field('cu.*,g.name,g.image')
            ->where($map)
            ->select();
        if($list){
            foreach ($list as $k=>$val){
                $status = $val['status'];
                $list[$k]['status'] = $status==1 ? '好友赠送' : ($status== 2 ? '好友助力': ($status == 3 ? '合成' : '拆盲盒'));
            }
        }
        $this->assign('list',$list);

        return $this->fetch();
    }
    //分享日志
    public function share(){
        header("Cache-control: private");
        $code = trim(input('param.code'));
        $uid = intval(input('param.uid'));
        $flag = trim(input('param.flag',0));
        $getCodeInfo = Db::name('blink_share_logs')
            ->where('code',$code)
            ->where('uid',$uid)
            ->select();
        $this->assign('code',$code);
        $this->assign('log',$getCodeInfo);
        if($flag == 1){
            $title = '卡券商品';
        }else{
            $title = '鼠卡';
        }
        $this->assign('title',$title);

        return $this->fetch();
    }

    /**
     * Commit: 导出 exc CSV
     * Function: export
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-31 14:19:06
     */
    public function orderExportExcel(){
        set_time_limit(0);
        ini_set("memory_limit", "1024M");
        debug('begin');
        $key = trim(input('key'));
        $sale_uid = input('sale_uid');
        $pay_status = input('pay_status',88);
        $start = input("param.start");
        $end = input("param.end");
        $map = [];
        if(!empty($key)) {
            $map['order.order_sn|bwk.title|depart.st_department|member.realname'] = ['like',"%" . $key . "%"];
        }
        if(!empty($sale_uid))
        {
            $map['order.fid'] = ['eq',$sale_uid];
        }
        $msg = '';
        if($pay_status!=88){
            $map['order.pay_status'] = ['=',$pay_status];
        }

        if(!empty($start) && !empty($end)){
            $map['order.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }
        $BlinkOrderModel = new BlinkOrderModel();
        $total = $BlinkOrderModel->getOrderCount($map);
        $limit = 1000;
        $page = ceil($total / $limit);


        $filename = "盲盒活动{$msg}订单列表".date('YmdHis');
        $header = array(
            array('column' => 'id', 'name' => '订单编号', 'width' => 10),
            array('column' => 'pertain_department_name', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),
            array('column' => 'sellername', 'name' => '美容师名称', 'width' => 15),
            array('column' => 'sellermobile', 'name' => '美容师电话', 'width' => 15),

            array('column' => 'origin_department', 'name' => '发货办事处', 'width' => 15),
            array('column' => 'origin_title', 'name' => '发货门店名称', 'width' => 30),
            array('column' => 'origin_sign', 'name' => '发货门店编码', 'width' => 12),
            array('column' => 'origin_name', 'name' => '发货美容师名称', 'width' => 15),
            array('column' => 'origin_mobile', 'name' => '发货美容师电话', 'width' => 15),

            array('column' => 'realname', 'name' => '顾客姓名', 'width' => 15),
            array('column' => 'mobile', 'name' => '顾客电话', 'width' => 15),
            array('column' => 'activity_flag', 'name' => '顾客标识码', 'width' => 15),
            array('column' => 'name', 'name' => '商品', 'width' => 30),
            array('column' => 'order_sn', 'name' => '订单号', 'width' => 33),
            array('column' => 'pay_status', 'name' => '支付状态', 'width' => 15),
            array('column' => 'order_price', 'name' => '订单金额', 'width' => 15),
            array('column' => 'pay_price', 'name' => '支付金额', 'width' => 15),
            array('column' => 'num', 'name' => '购买数量', 'width' => 15),
            array('column' => 'insert_time', 'name' => '创建时间', 'width' => 20),
            array('column' => 'pay_time', 'name' => '支付时间', 'width' => 20),
            array('column' => 'transaction_id', 'name' => '支付流水号', 'width' => 30),
        );


        Loader::import('PHPExcel.PHPExcel');
        Loader::import('PHPExcel.PHPExcel.IOFactory');
        Loader::import('PHPExcel.PHPExcel.Writer.Excel2007');
        Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $xlsTitle = iconv('utf-8', 'gb2312', $filename);//文件名称
        $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($header);

        $objPHPExcel = new \PHPExcel();
        $cellName = array(
            'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X',
            'Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR',
            'AS','AT','AU','AV','AW','AX','AY','AZ'
        );
        //单个单元格居中
        $objPHPExcel->getActiveSheet(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //行高
        $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
        //设置excel第一行数据
        foreach ($header as $key=>$val){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$key].'1', $val['name']);
            //设置所有格居中显示
            $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            // 设置垂直居中
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            //设置单元格自动宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$key])->setWidth($val['width']?:15);
            //第二行加粗 true false
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getFont()->setBold(true);
        }

        $a = 0;
        for ($i=0;$i<$page;$i++){
            $lists = $BlinkOrderModel->getExportOrderLists1($map,$i,$limit);
            if(!empty($lists)){
                foreach ($lists as $k=>$val){
                    for($j=0;$j<$cellNum;$j++){
                        $column = strip_tags($val[$header[$j]['column']]);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue(
                            $cellName[$j].($a+2),
                            $column ."\t"
                        );
                    }
                    $a++;
                }
                unset($lists);
            }
        }
        debug('end');

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($a+4),' '.debug('begin','end',8).'s ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($a+4),' '.debug('begin','end','m').' ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($a+4),$total);

        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Description: File Transfer');
        header('pragma:public');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//excel2007
        header('Content-Disposition:attachment;filename='.$fileName.'.xlsx');//attachment新窗口打印inline本窗口打印
        header("Content-Transfer-Encoding:binary");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        // header('Pragma: no-cache');
        header("Expires: 0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    public function export(){
        set_time_limit(0);
        ini_set("memory_limit", "4024M");
        $export = input('export',0);
        $key = trim(input('key'));
        $sale_uid = input('sale_uid');
        $pay_status = input('pay_status',88);
        $start = input("param.start");
        $end = input("param.end");
        $map = [];
        if(!empty($key)) {
            $map['order.order_sn|bwk.title|depart.st_department|member.realname'] = ['like',"%" . $key . "%"];
        }
        if(!empty($sale_uid))
        {
            $map['order.fid'] = ['eq',$sale_uid];
        }
        $msg = '';
        if($pay_status!=88){
            $map['order.pay_status'] = ['=',$pay_status];
        }

        if(!empty($start) && !empty($end)){
            $map['order.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }
        $BlinkOrderModel = new BlinkOrderModel();
        $lists = $BlinkOrderModel->getExportOrderLists($map);


        $filename = "盲盒活动{$msg}订单列表".date('YmdHis');
        $header = array(
            array('column' => 'pertain_department_name', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),
            array('column' => 'sellername', 'name' => '美容师名称', 'width' => 15),
//            array('column' => 'sellermobile', 'name' => '美容师电话', 'width' => 15),

            array('column' => 'origin_department', 'name' => '发货办事处', 'width' => 15),
            array('column' => 'origin_title', 'name' => '发货门店名称', 'width' => 30),
            array('column' => 'origin_sign', 'name' => '发货门店编码', 'width' => 12),
            array('column' => 'origin_name', 'name' => '发货美容师名称', 'width' => 15),
//            array('column' => 'origin_mobile', 'name' => '发货美容师电话', 'width' => 15),

            array('column' => 'realname', 'name' => '顾客姓名', 'width' => 15),
//            array('column' => 'mobile', 'name' => '顾客电话', 'width' => 15),
            array('column' => 'activity_flag', 'name' => '顾客标识码', 'width' => 15),
            array('column' => 'name', 'name' => '商品', 'width' => 30),
            array('column' => 'order_sn', 'name' => '订单号', 'width' => 33),
            array('column' => 'pay_status', 'name' => '支付状态', 'width' => 15),
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
            ->count();  //总数据
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

    public function coupons(){
        $key = trim(input('key',''));
        $status = input('status',88);
        $share_status = input('share_status',88);
        $is_deliver = input('is_deliver',88);
        $start = input("param.start",'');
        $end = input("param.end",'');
        $type = input('param.type',88);
        $map = [];
        if(!empty($key)) {
            $map['coupon.ticket_code|coupon.blinkno|depart.st_department|g.name|member.realname|member.mobile|bwk.title'] = ['like',"%" . $key . "%"];
        }
        if($status!=88){
            $map['coupon.status'] = ['=',$status];
        }
        if($share_status!=88){
            $map['coupon.share_status'] = ['=',$share_status];
        }
        if($is_deliver!=88){
            if($is_deliver == 11){
                //申请发货
                $map['coupon.is_deliver'] = ['=',2];
                $map['coupon.is_apply'] = ['=',1];
                $is_deliver = 11;
            }elseif($is_deliver == 2){
                //申请发货
                $map['coupon.is_deliver'] = ['=',2];
                $map['coupon.is_apply'] = ['=',2];
            }else{
                $map['coupon.is_deliver'] = ['=',$is_deliver];
            }
        }
        if($type!=88){
            $map['coupon.type'] = ['=',$type];
        }


        if(!empty($start) && !empty($end)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }else if (!empty($start)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), time()]);
        }else if (!empty($end)){
            $map['coupon.insert_time'] = array('elt',  strtotime($end . " 23:59:59"));
        }
        $BlinkCouponUserModel = new BlinkCouponUserModel();
        $Nowpage = intval(input('get.page',1));
        $limits = config('list_rows');// 获取总条数
        $count = $BlinkCouponUserModel->getCouponCount($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $BlinkCouponUserModel->getCouponLists($map,$Nowpage, $limits);


        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('status', $status);
        $this->assign('share_status', $share_status);
        $this->assign('is_deliver', $is_deliver);
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('type',$type);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    public function export1(){
        set_time_limit(0);
        ini_set("memory_limit", "6024M");
        $key = trim(input('key'));
        $status = input('status',88);
        $export = input('export',0);
        $share_status = input('share_status',88);
        $is_deliver = input('is_deliver',88);
        $start = input("param.start",'');
        $end = input("param.end",'');
        $type = input('param.type',88);
        $map = [];
        if(!empty($key)) {
            $map['coupon.ticket_code|coupon.blinkno|depart.st_department|g.name|member.realname|member.mobile|bwk.title'] = ['like',"%" . $key . "%"];
        }

        if($status!=88){
            $map['coupon.status'] = ['=',$status];
        }
        if($share_status!=88){
            $map['coupon.share_status'] = ['=',$share_status];
        }

        if($is_deliver!=88){
            if($is_deliver == 11){
                //申请发货
                $map['coupon.is_deliver'] = ['=',2];
                $map['coupon.is_apply'] = ['=',1];
            }elseif($is_deliver == 2){
                //申请发货
                $map['coupon.is_deliver'] = ['=',2];
                $map['coupon.is_apply'] = ['=',2];
            }else{
                $map['coupon.is_deliver'] = ['=',$is_deliver];
            }
        }
        if($type!=88){
            $map['coupon.type'] = ['=',$type];
        }
        $time = '';
        if(!empty($start) && !empty($end)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
            $time = $start.'---'.$end;
        }else if (!empty($start)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), time()]);
            $time = $start;
        }else if (!empty($end)){
            $map['coupon.insert_time'] = array('elt',  strtotime($end . " 23:59:59"));
            $time = $end;
        }
        $BlinkCouponUserModel = new BlinkCouponUserModel();
        $lists = $BlinkCouponUserModel->getExportCouponLists($map);


        $filename = "盲盒活动商品列表".$time;
        $header = array(
            array('column' => 'pertain_department_name', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),
            array('column' => 'sellername', 'name' => '美容师名称', 'width' => 15),
//            array('column' => 'sellermobile', 'name' => '美容师电话', 'width' => 15),

            array('column' => 'origin_department', 'name' => '发货办事处', 'width' => 15),
            array('column' => 'origin_title', 'name' => '发货门店名称', 'width' => 30),
            array('column' => 'origin_sign', 'name' => '发货门店编码', 'width' => 12),
            array('column' => 'origin_name', 'name' => '发货美容师名称', 'width' => 15),
//            array('column' => 'origin_mobile', 'name' => '发货美容师电话', 'width' => 15),


            //array('column' => 'shareMobile', 'name' => '分享人手机号', 'width' => 12),
            //array('column' => 'shareRealname', 'name' => '分享人名称', 'width' => 15),

            array('column' => 'realname', 'name' => '顾客姓名', 'width' => 15),
//            array('column' => 'mobile', 'name' => '顾客电话', 'width' => 15),
            array('column' => 'activity_flag', 'name' => '顾客标识码', 'width' => 15),
            array('column' => 'id', 'name' => '卡券id', 'width' => 10),
            array('column' => 'ticket_code', 'name' => '卡券编号', 'width' => 30),
            array('column' => 'name', 'name' => '商品', 'width' => 30),
            array('column' => 'activity_price', 'name' => '金额', 'width' => 15),
            array('column' => 'source', 'name' => '来源', 'width' => 15),
            array('column' => 'insert_time', 'name' => '创建时间', 'width' => 20),
            array('column' => 'share_status', 'name' => '分享状态', 'width' => 15),
            array('column' => 'status', 'name' => '核销状态', 'width' => 15),
            array('column' => 'deliver', 'name' => '发货状态', 'width' => 20),
            array('column' => 'batch', 'name' => '发货批次', 'width' => 15),
            array('column' => 'is_apply', 'name' => '申请状态', 'width' => 15),
            array('column' => 'remark', 'name' => '备注', 'width' => 45),
        );
        if($export==1){
            exportExcel($lists, $header, $filename);//生成数据
        }else{
            exportCsv($lists,$header,$filename);
        }
        die();
    }

    public function couponExportExcel(){
        set_time_limit(0);
        ini_set("memory_limit", "4024M");
        debug('begin');
        $key = trim(input('key'));
        $status = input('status',88);
        $export = input('export',0);
        $share_status = input('share_status',88);
        $start = input("param.start",'');
        $end = input("param.end",'');
        $type = input('param.type',88);
        $map = [];
        if(!empty($key)) {
            $map['coupon.ticket_code|coupon.blinkno|depart.st_department|g.name|member.realname|member.mobile|bwk.title'] = ['like',"%" . $key . "%"];
        }

        if($status!=88){
            $map['coupon.status'] = ['=',$status];
        }
        if($share_status!=88){
            $map['coupon.share_status'] = ['=',$share_status];
        }
        if($type!=88){
            $map['coupon.type'] = ['=',$type];
        }

        if(!empty($start) && !empty($end)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
        }
        $BlinkCouponUserModel = new BlinkCouponUserModel();
        $total = $BlinkCouponUserModel->getCouponCount($map);
        $limit = 1000;
        $page = ceil($total / $limit);


        $filename = "盲盒活动商品列表".date('YmdHis');
         $header = array(
            array('column' => 'pertain_department_name', 'name' => '办事处', 'width' => 15),
            array('column' => 'title', 'name' => '门店名称', 'width' => 30),
            array('column' => 'sign', 'name' => '门店编码', 'width' => 12),
            array('column' => 'sellername', 'name' => '美容师名称', 'width' => 15),
            array('column' => 'sellermobile', 'name' => '美容师电话', 'width' => 15),

            array('column' => 'origin_department', 'name' => '发货办事处', 'width' => 15),
            array('column' => 'origin_title', 'name' => '发货门店名称', 'width' => 30),
            array('column' => 'origin_sign', 'name' => '发货门店编码', 'width' => 12),
            array('column' => 'origin_name', 'name' => '发货美容师名称', 'width' => 15),
            array('column' => 'origin_mobile', 'name' => '发货美容师电话', 'width' => 15),

            array('column' => 'realname', 'name' => '顾客姓名', 'width' => 15),
            array('column' => 'mobile', 'name' => '顾客电话', 'width' => 15),
            array('column' => 'activity_flag', 'name' => '顾客标识码', 'width' => 15),
            array('column' => 'ticket_code', 'name' => '卡券编号', 'width' => 30),
            array('column' => 'name', 'name' => '商品', 'width' => 30),
            array('column' => 'activity_price', 'name' => '金额', 'width' => 15),
            array('column' => 'insert_time', 'name' => '创建时间', 'width' => 20),
            array('column' => 'status', 'name' => '核销状态', 'width' => 15),
            array('column' => 'share_status', 'name' => '分享状态', 'width' => 15),
            array('column' => 'source', 'name' => '来源', 'width' => 15),
        );


        Loader::import('PHPExcel.PHPExcel');
        Loader::import('PHPExcel.PHPExcel.IOFactory');
        Loader::import('PHPExcel.PHPExcel.Writer.Excel2007');
        Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $xlsTitle = iconv('utf-8', 'gb2312', $filename);//文件名称
        $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($header);

        $objPHPExcel = new \PHPExcel();
        $cellName = array(
            'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X',
            'Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR',
            'AS','AT','AU','AV','AW','AX','AY','AZ'
        );
        //单个单元格居中
        $objPHPExcel->getActiveSheet(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //行高
        $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
        //设置excel第一行数据
        foreach ($header as $key=>$val){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$key].'1', $val['name']);
            //设置所有格居中显示
            $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            // 设置垂直居中
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            //设置单元格自动宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$key])->setWidth($val['width']?:15);
            //第二行加粗 true false
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$key].'1')->getFont()->setBold(true);
        }

        $a = 0;
        for ($i=0;$i<$page;$i++){
            $lists = $BlinkCouponUserModel->getExportCouponLists1($map,$i,$limit);
            if(!empty($lists)){
                foreach ($lists as $k=>$val){
                    for($j=0;$j<$cellNum;$j++){
                        $column = strip_tags($val[$header[$j]['column']]);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue(
                            $cellName[$j].($a+2),
                            $column ."\t"
                        );
                    }
                    $a++;
                }
                unset($lists);
            }
        }
        debug('end');

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.($a+4),' '.debug('begin','end',8).'s ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.($a+4),' '.debug('begin','end','m').' ');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.($a+4),$total);

        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Description: File Transfer');
        header('pragma:public');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//excel2007
        header('Content-Disposition:attachment;filename='.$fileName.'.xlsx');//attachment新窗口打印inline本窗口打印
        header("Content-Transfer-Encoding:binary");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        // header('Pragma: no-cache');
        header("Expires: 0");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }


//商品门店总数据
    public function getabc(){
        set_time_limit(0);
        ini_set("memory_limit", "4024M");
        $key = trim(input('key'));
        $status = input('status',88);
        $export = input('export',0);
        $share_status = input('share_status',88);
        $is_deliver = input('is_deliver',88);
        $start = input("param.start",'');
        $end = input("param.end",'');
        $type = input('param.type',88);
        $map = [];
        if(!empty($key)) {
            $map['coupon.ticket_code|coupon.blinkno|depart.st_department|g.name|member.realname|member.mobile|bwk.title'] = ['like',"%" . $key . "%"];
        }

        if($status!=88){
            $map['coupon.status'] = ['=',$status];
        }
        if($share_status!=88){
            $map['coupon.share_status'] = ['=',$share_status];
        }

        if($is_deliver!=88){
            if($is_deliver == 11){
                //申请发货
                $map['coupon.is_deliver'] = ['=',2];
                $map['coupon.is_apply'] = ['=',1];
            }elseif($is_deliver == 2){
                //申请发货
                $map['coupon.is_deliver'] = ['=',2];
                $map['coupon.is_apply'] = ['=',2];
            }else{
                $map['coupon.is_deliver'] = ['=',$is_deliver];
            }
        }
        if($type!=88){
            $map['coupon.type'] = ['=',$type];
        }
        $time = '';
        if(!empty($start) && !empty($end)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), strtotime($end . " 23:59:59")]);
            $time = $start.'---'.$end;
        }else if (!empty($start)){
            $map['coupon.insert_time'] = array('between', [strtotime($start), time()]);
            $time = $start;
        }else if (!empty($end)){
            $map['coupon.insert_time'] = array('elt',  strtotime($end . " 23:59:59"));
            $time = $end;
        }
        $BlinkCouponUserModel = new BlinkCouponUserModel();
        $lists = $BlinkCouponUserModel->getExportCouponListsABC($map);


        $filename = "盲盒活动商品列表".$time;
        $header = array(
            array('column' => 'pertain_department_name', 'name' => '办事处', 'width' => 15),

            array('column' => 'origin_department', 'name' => '发货办事处', 'width' => 15),
            array('column' => 'origin_title', 'name' => '发货门店名称', 'width' => 30),
            array('column' => 'origin_sign', 'name' => '发货门店编号', 'width' => 15),
            array('column' => 'name', 'name' => '商品', 'width' => 15),
            array('column' => 'count', 'name' => '数量', 'width' => 15),
        );
        if($export==1){
            exportExcel($lists, $header, $filename);//生成数据
        }else{
            exportCsv($lists,$header,$filename);
        }
        die();
    }
}