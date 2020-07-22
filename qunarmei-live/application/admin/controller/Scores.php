<?php

namespace app\admin\controller;
use app\api\service\ErpService;
use think\Db;

/**
 * Class OtoOrder
 * @package app\admin\controller
 * 积分兑换管理模块
 */
set_time_limit(0);
class Scores extends Base
{
    // 统计积分标记
    protected $type = ['missshop','missshop_transfer','missshop_exchange','blink'];
    //*********************************************列表*********************************************//
    /**
     * 美容师积分列表
     * @return mixed|\think\response\Json
     */
    public function mrs_score(){
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        set_time_limit(0);
        ini_set("memory_limit", "1024M");
        $map = [];$user_ids = [];
        $key = input('key');
        $key1 = input('key1');
        $export = input('export',0);
        if($key&&$key!=="")
        {
            $key = trim($key);
            $map['bwk.sign|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($key1){
            $key1 = trim($key1);
            $map['depart.st_department'] = ['like','%'.$key1.'%'];
        }
        $maps['type'] = ['in',$this->type];
        $user_ids = [];

        $res_mem_score = Db::table('think_scores_record')->where($maps)->group('user_id')->order('user_id desc')->select();
        if($res_mem_score){
            foreach ($res_mem_score as $vs) {
                $user_ids[] = $vs['user_id'];
            }
            $user_ids = array_unique($user_ids);
            $map['member.id'] = ['in',$user_ids];
        }
        //搜索支付时间
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::table('ims_bj_shopn_member member')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field('depart.st_department,bwk.title,member.realname,member.mobile,bwk.sign,member.staffid')
            ->where($map)
            ->group('member.id')
            ->order('member.storeid desc')
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));

        if($export){
            $Nowpage = 1;
            $limits = 9000;
        }
        $lists = Db::table('ims_bj_shopn_member member')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field('member.id,depart.st_department,bwk.title,member.realname,member.mobile,bwk.sign,member.staffid')
            ->where($map)
            ->group('member.id')
            ->order('member.storeid desc')
            ->page($Nowpage, $limits)
            ->select();
        // 1.查询用户,根据用户查询积分,用过积分,剩余积分
        foreach ($lists as $k=>$v){
            if($flag_rule){
                $lists[$k]['mobile'] = $mobrule->replaceMobile($v['mobile']);
            }

            $lists[$k]['use_score'] = 0;
            $lists[$k]['sum_score'] = 0;
            $lists[$k]['use_ava_score'] = 0;
            $lists[$k]['use_noava_score'] = 0;
            $lists[$k]['title'] = $v['title']==null?'':$v['title'];
            $lists[$k]['sign'] = $v['sign']==null?'':$v['sign'];
            $lists[$k]['st_department'] = $v['st_department']==null?'':$v['st_department'];

            // 查询积分
            $maps1['user_id'] = $v['id'];
            $maps1['type'] = ['in',$this->type];
            $maps2['user_id'] = $v['id'];
            $maps2['type'] = 'missshop_exchange';
            $maps1['usable'] = 1;
            $arr1['use_ava_score'] = Db::table('think_scores_record')->where($maps1)->sum('scores');
            $maps1['usable'] = 0;
            $arr1['use_noava_score'] = Db::table('think_scores_record')->where($maps1)->sum('scores');
            $lists[$k]['use_ava_score'] = $arr1['use_ava_score'];
            $lists[$k]['use_noava_score'] = $arr1['use_noava_score'];
            $arr1['use_score'] = Db::table('think_scores_record')->where($maps2)->sum('scores');
            $lists[$k]['use_score'] = abs($arr1['use_score']);
            $lists[$k]['sum_score'] = $lists[$k]['use_ava_score'] + $lists[$k]['use_noava_score'] + $lists[$k]['use_score'];
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile'] = $v['mobile'];
                $data[$k]['sum_score']= $v['sum_score'];
                $data[$k]['use_score']=$v['use_score'];
                $data[$k]['use_ava_score']= $v['use_ava_score'];
                $data[$k]['use_noava_score']=$v['use_noava_score'];

            }
            $filename = "美容师积分列表".date('YmdHis');
            $header = array ('所属市场','所属美容院','门店编号','用户姓名','用户电话','总积分','使用积分','可使用积分','为核销积分');
            $widths=array('10','10','10','10','10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('count', $count);
        $this->assign('val1', $key1);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    /**
     * 积分兑换订单列表
     * @return mixed|\think\response\Json
     */
    public function index(){
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        $map = [];
        $key = input('key');
        $key1 = input('key1');
        $export = input('export',0);
        if($key&&$key!=="")
        {
            $key = trim($key);
            $map['bwk.sign|member.mobile'] = ['like',"%" . $key . "%"];
        }
        if($key1){
            $key1 = trim($key1);
            $map['depart.st_department'] = ['like','%'.$key1.'%'];
        }
        //搜索支付时间
        $dt1 = '';$dt2 = '';
        if(input('dt1') && input('dt2')) {
            $dt1 = input('dt1');
            $dt2 = input('dt2');
            $map['o.create_time'] = ['between time',[strtotime($dt1),strtotime($dt2)]];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 2000;// 获取总条数
        $count = Db::table('ims_bj_shopn_score_goods_order o')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=o.user_id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'o.store_id=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field('o.id,depart.st_department,bwk.title,member.realname,member.mobile,o.order_sn,o.pay_score,o.create_time,o.confirm_time,bwk.sign,o.status,member.staffid')
            ->where($map)
            ->order('o.create_time desc')
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));

        if($export){
            $Nowpage = 1;
            $limits = 9000;
        }
        $lists = Db::table('ims_bj_shopn_score_goods_order o')
            ->join(['ims_bj_shopn_goods' => 'g'],'o.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=o.user_id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'o.store_id=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field('o.order_sn_merge,o.open_order_time,o.is_open_order,g.title goods_title,o.goods_num,o.id,depart.st_department,bwk.title,member.realname,member.mobile,o.order_sn,o.pay_score,o.create_time,o.confirm_time,bwk.sign,o.status,member.staffid,o.property_ids,g.id goods_id')
            ->where($map)
            ->page($Nowpage, $limits)
            ->order('o.create_time desc')
            ->select();
        $propertys = [];
        $goods_ids = [];
        $ordersns = [];
        foreach ($lists as $k=>$v){
            if($flag_rule){
                $lists[$k]['mobile'] = $mobrule->replaceMobile($lists[$k]['mobile']);
            }
            $lists[$k]['create_time'] = $v['create_time']==null?'':date('Y-m-d H:i:s',$v['create_time']);
            $lists[$k]['confirm_time'] = $v['confirm_time']==null?'':date('Y-m-d H:i:s',$v['confirm_time']);
            $lists[$k]['st_department'] = $v['st_department']==null?'':$v['st_department'];
            $lists[$k]['goods_property'] = '';
            $lists[$k]['goods_code'] = '';
            $lists[$k]['open_order_time'] = $v['open_order_time']=='0'?'':date('Y-m-d',$v['open_order_time']);
            $lists[$k]['ordersn_u8'] = '';
            $lists[$k]['u8sign'] = '';
            // 商品属性
            if($v['property_ids']){
                $propertys[] = $v['property_ids'];
            }
            $goods_ids[] = $v['goods_id'];
            if($v['order_sn_merge']){
                $ordersns[] = $v['order_sn_merge'];
            }
        }
        if($ordersns){
            // 查询属性
            $mapord['ordersn'] = ['in',$ordersns];
            $res_ord = Db::table('store_erp_saleorder')->where($mapord)->select();
            if($res_ord){
                foreach ($res_ord as $vord) {
                    foreach ($lists as $k3 => $v3) {
                        if($vord['ordersn'] == $v3['order_sn_merge']){
                            $lists[$k3]['ordersn_u8'] = $vord['ordersn_u8'];
                            $lists[$k3]['u8sign'] = $vord['u8sign'];
                        }
                    }
                }
            }
        }
        if($propertys){
            // 查询属性
            $mapp['id'] = ['in',$propertys];
            $res_pro = Db::table('ims_bj_shopn_goods_extend')->where($mapp)->select();
            if($res_pro){
                foreach ($res_pro as $v) {
                    foreach ($lists as $k => $v1) {
                        if($v['id'] == $v1['property_ids']){
                            $lists[$k]['goods_property'] = $v['color'].' '.$v['size'];
                        }
                    }
                }
            }
        }
        if($goods_ids){
            $mapg['goods_id'] = ['in',$goods_ids];
            $res_pro = Db::table('ims_bj_shopn_goods_code')->where($mapg)->select();
            if($res_pro){
                foreach ($res_pro as $v) {
                    foreach ($lists as $k => $v1) {
                        if($v['goods_id'] == $v1['goods_id']){
                            if($v1['property_ids']){
                                if($v1['property_ids']==$v['goods_proid']){
                                    $lists[$k]['goods_code'] = $v['goods_code'];
                                }
                            }else{
                                $lists[$k]['goods_code'] = $v['goods_code'];
                            }
                        }
                    }
                }
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['goods_title']="\t".$v['goods_title'];
                $data[$k]['goods_property']= $v['goods_property'];
                $data[$k]['goods_num']=$v['goods_num'];
                $data[$k]['goods_code']=$v['goods_code'];
                $data[$k]['order_sn']="\t".$v['order_sn'];
                $data[$k]['pay_score']=$v['pay_score'];
                $data[$k]['ordersn_u8']=$v['ordersn_u8'];
                $data[$k]['u8sign']=$v['u8sign'];
                $data[$k]['create_time']= $v['create_time'];
                $data[$k]['confirm_time']= $v['confirm_time'];
                $data[$k]['status'] = $v['status']==1?'已兑换':'已完成';
                $data[$k]['is_open_order'] = $v['is_open_order']==1?'已开单':'未开单';
                $data[$k]['open_order_time'] = $v['open_order_time'];
            }
            // 所属市场	所属美容院	用户姓名	用户电话	商品名称	商品属性	商品数量	订单编号	订单积分	下单时间	确认收货时间	订单状态
            $filename = "积分兑换订单列表".date('YmdHis');
            $header = array ('所属市场','所属美容院','门店编号','用户姓名','用户电话','商品名称','商品属性','商品数量','商品编码','订单编号','订单积分','u8单据号','u8门店编号','下单时间','确认收货时间','订单状态','是否开单','开单日期');
            $widths=array('10','10','10','10','10','10','15','15','15','15','15','30','30','30','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('count', $count);
        $this->assign('dt1', $dt1);
        $this->assign('dt2', $dt2);
        $this->assign('val1', $key1);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 积分兑换订单修改
     * @param $id:订单id,$is_open_order:是否开单 0:未开单(默认),1:已开单,$open_order_time: 发放日期
     * @return array
     */
    public function score_order_edit()
    {
        $id = input('id');
        $map['id'] = $id;
        // 查询订单
        $res = Db::table('ims_bj_shopn_score_goods_order o')->where($map)->limit(1)->find();
        if($res){
            $data['open_order_time'] = time();
            if($res['is_open_order'] == 1){
                $data['is_open_order'] = 0;
            }else{
                $data['is_open_order'] = 1;
            }
            $res = Db::table('ims_bj_shopn_score_goods_order')->where($map)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
    }

    /**
     * 积分订单删除
     * @param int $id 订单id
     * @return \think\response\Json
     */
    public function score_order_del()
    {
        $id = input('id');
        // 开启事物
        Db::startTrans();
        try{
            // 1.查询用户订单
            $map['id'] = $id;
            $res = Db::table('ims_bj_shopn_score_goods_order')->where($map)->limit(1)->find();
            if($res){
                // 2.删除用户订单
                Db::table('ims_bj_shopn_score_goods_order')->where($map)->delete();
                // 3.删除积分日志记录 think_scores_record
                $mapr['remark'] = $res['order_sn'];
                Db::table('think_scores_record')->where($mapr)->delete();
                // 4.退回扣减掉用户积分统计
                $maps['user_id'] = $res['user_id'];
                Db::table('think_sum_user')->where($maps)->setInc('missshop_scores',$res['pay_score']);
                // 5.退回商品库存 ims_bj_shopn_score_goods(无属性) ims_bj_shopn_score_goods_property(多属性库存)
                $mapp['goods_id'] = $res['goods_id'];
                if($res['property_ids']){
                    // 多属性
                    $mapp['property_id'] = (int)$res['property_ids'];
                    Db::table('ims_bj_shopn_score_goods_property')->where($mapp)->setInc('exchange_num',$res['goods_num']);
                }else{
                    // 无属性
                    Db::table('ims_bj_shopn_score_goods')->where($mapp)->setInc('exchange_num',$res['goods_num']);
                }
            }
            // 提交事务
            Db::commit();
            return json(['code' => 1, 'data' => '', 'msg' => '删除成功,积分已退回']);
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code' => 0, 'data' => '', 'msg' => $e->getMessage()]);
        }
    }
    /**
     * 积分商品配置
     * @return mixed|\think\response\Json
     */
    public function goodsScore(){
        $map = [];
        $key = input('key');
        if($key&&$key!=="")
        {
            $map['bwk.sign|member.mobile'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::table('ims_bj_shopn_score_goods sg')
            ->join(['ims_bj_shopn_goods' => 'g'],'g.id=sg.goods_id','left')
            ->join(['ims_bj_shopn_score_category' => 'c'],'c.id=sg.score_cat_id','left')
            ->field('c.act_name,c.score_name,sg.goods_id,sg.exchange_score,sg.is_show,sg.special_rule,sg.limit_num,sg.exchange_num,g.title')
            ->where($map)
            ->order('sg.create_time desc,sg.id desc')
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('ims_bj_shopn_score_goods sg')
            ->join(['ims_bj_shopn_goods' => 'g'],'g.id=sg.goods_id','left')
            ->join(['ims_bj_shopn_score_category' => 'c'],'c.id=sg.score_cat_id','left')
            ->field('sg.id,c.act_name,c.score_name,sg.goods_id,sg.exchange_score,sg.is_show,sg.special_rule,sg.limit_num,sg.exchange_num,g.title,g.goods_property')
            ->where($map)
            ->page($Nowpage, $limits)
            ->order('sg.create_time desc,sg.id desc')
            ->select();
        $goods = [];
        if($lists){
            foreach ($lists as $k => $v) {
                $lists[$k]['special_rule'] = $v['special_rule']=='week_new_update'?'每周一9点上新商品':'';
                $lists[$k]['is_show'] = $v['is_show']==1?'<div><span class="label label-info">显示</span></div>':'<div><span class="label label-danger">不显示</span></div>';
                $lists[$k]['pro'] = '';
                $goods[] = $v['goods_id'];
                if($v['goods_property']){
                    $lists[$k]['pro'] = '多属性配置';
                }
                $lists[$k]['already_exchange_num'] = 0;
           }
           // 查询每个商品的已兑换数量
            $mapo['goods_id'] = ['in',$goods];
           $res_gd = Db::table('ims_bj_shopn_score_goods_order')->field('sum(goods_num) gd_num,goods_id')->where($mapo)->group('goods_id')->select();
            if($res_gd){
                foreach($res_gd as $vg){
                    foreach ($lists as $k => $v) {
                        if($v['goods_id'] == $vg['goods_id']){
                            $lists[$k]['already_exchange_num'] = $vg['gd_num'];
                        }
                    }
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 积分商品添加
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function goodsscore_add()
    {
        if(request()->isPost()){
            $param = input('post.');
            $data['score_cat_id'] = $param['act_id'];
            $data['goods_id'] = $param['goods_id'];
            $data['exchange_score'] = $param['exchange_score'];
            $data['exchange_num'] = $param['exchange_num'];
            $data['limit_num'] = $param['limit_num'];
            $data['is_show'] = $param['is_show'];
            $data['special_rule'] = $param['special_rule'];
//            $data['act_id'] = $param['act_id'];
            $res = Db::table('ims_bj_shopn_score_goods')->insertGetId($data);
            return json(['code' => 1, 'data' => '', 'msg' => '添加成功']);
        }
        $map['status'] = 1;
        $map['isshow'] = 1;
        $map['storeid'] = 0;
        $list = Db::table('ims_bj_shopn_goods')->where($map)->order('createtime desc')->select();
        $mapa['is_show'] = 1;
        $actlist = Db::table('ims_bj_shopn_score_category')->where($mapa)->order('create_time desc')->select();
        $this->assign('actList', $actlist);
        $this->assign('goodsList', $list);
        return $this->fetch();
    }
    /**
     * 积分商品编辑
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function goodsscore_edit()
    {
        $id = input('id');
//        echo 'id:'.$id;die;
        if(request()->isPost()){
            $param = input('post.');
            $map1['id'] = $param['id'];
            $data['exchange_score'] = $param['exchange_score'];
            $data['exchange_num'] = $param['exchange_num'];
            $data['limit_num'] = $param['limit_num'];
            $data['is_show'] = $param['is_show'];
            $data['special_rule'] = $param['special_rule'];
//            $data['act_id'] = $param['act_id'];
            $res = Db::table('ims_bj_shopn_score_goods')->where($map1)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $map['sg.id'] = $id;
        $list = Db::table('ims_bj_shopn_score_goods sg')
            ->join(['ims_bj_shopn_goods' => 'g'], 'sg.goods_id=g.id', 'left')
            ->field('g.id,g.title,sg.exchange_score,sg.exchange_num,sg.limit_num,sg.is_show,sg.special_rule,sg.score_cat_id')
            ->where($map)
            ->limit(1)
            ->find();
        $this->assign('list',$list);
        $this->assign('id',$id);
        return $this->fetch();
    }
    /**
     * 积分多属性商品编辑
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function goodsscore_pro()
    {
        $goods_id = input('goods_id');
//        echo 'id:'.$id;die;
        $key = input('key');
        if($key&&$key!=="") {
            $map['g.title'] = ['like',"%" . $key . "%"];
        }
        $map['g.id'] = $goods_id;
        $list = Db::table('ims_bj_shopn_score_goods_property p')
            ->join(['ims_bj_shopn_score_goods' => 'sc'], 'sc.goods_id=p.goods_id', 'left')
            ->join(['ims_bj_shopn_goods' => 'g'], 'p.goods_id=g.id', 'left')
            ->join(['ims_bj_shopn_score_category' => 'c'], 'c.id=p.score_cat_id', 'left')
            ->join(['ims_bj_shopn_goods_extend' => 'e'], 'p.property_id=e.id', 'left')
            ->field('sc.exchange_score,c.act_name,p.id,p.exchange_num,g.title,e.id pro_id,e.name,e.size,e.color,g.goods_property')
            ->where($map)
            ->order('p.create_time desc,p.id desc')
            ->select();
        if($list){
            $pros = [];
            foreach ($list as $k => $v) {
                $list[$k]['goods_property'] = $v['color'].' '.$v['size'];
                $pros[] = $v['pro_id'];
                $list[$k]['already_exchange_num'] = 0;
            }
            // 查询商品已兑换数量
            $mapo['goods_id'] = $goods_id;
            $mapo['property_ids'] = ['in',$pros];
            $res_pro = Db::table('ims_bj_shopn_score_goods_order o')->where($mapo)->field('sum(goods_num) gd_num,property_ids')->group('property_ids')->select();
            if($res_pro){
                foreach ($res_pro as $vp) {
                    foreach ($list as $k => $v) {
                        if($vp['property_ids'] == $v['pro_id']){
                            $list[$k]['already_exchange_num'] = $vp['gd_num'];

                        }
                    }
                }
            }
        }
        // CREATE TABLE `ims_bj_shopn_score_goods_property` (
        /*
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `score_cat_id` varchar(10) NOT NULL DEFAULT '' COMMENT '积分分类id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父商品id',
  `property_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品属性id,ims_bj_shopn_goods_extend中id',
  `exchange_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品可兑换数量',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='去哪美C-积分商品多属性可兑换数量';

*/
//        echo '<pre>';print_r($list);die;
        $this->assign('list',$list);
        $this->assign('goods_id',$goods_id);
        $this->assign('val',$key);
        $this->assign('Nowpage', 1); //当前页
        $this->assign('allpage', 1); //总页数
        return $this->fetch();
    }
    /**
     * 积分商品多属性库存添加
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function goodsscore_pro_add()
    {
        $goods_id = input('goods_id');
        $prolist = [];
        if(request()->isPost()){
            $param = input('post.');
            $data['score_cat_id'] = isset($param['act_id'])?$param['act_id']:1;
            $data['goods_id'] = $param['goods_id'];
            $data['property_id'] = $param['property_id'];
            $data['exchange_num'] = $param['exchange_num'];
            $data['create_time'] = time();
            $res = Db::table('ims_bj_shopn_score_goods_property')->insert($data);
            return json(['code' => 1, 'data' => '', 'msg' => '添加成功']);
        }
        $res_goods = Db::table('ims_bj_shopn_goods')->where('id',$goods_id)->limit(1)->find();
        if($res_goods && $res_goods['goods_property']){
            $pros = json_decode($res_goods['goods_property'],true);
            $pro = [];
            foreach ($pros as $vp) {
                $vps = explode('x',$vp);
                $pro[] = $vps[0];
            }
            $mapp['goods_type'] = ['in',$pro];
            $prolist = Db::table('ims_bj_shopn_goods_extend e')->where($mapp)->select();
        }
        $actlist = Db::table('ims_bj_shopn_score_category c')->where('is_show',1)->select();
        $this->assign('actlist',$actlist);
        $this->assign('prolist',$prolist);
        $this->assign('goodslist',$res_goods);
        $this->assign('goods_id',$goods_id);
        return $this->fetch();
    }
    /**
     * 积分商品多属性库存编辑
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function goodsscore_pro_edit()
    {
        $id = input('id');
        if(request()->isPost()){
            $param = input('post.');
            $data['exchange_num'] = $param['exchange_num'];
            $map1['id'] = $id;
            $res = Db::table('ims_bj_shopn_score_goods_property')->where($map1)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $map['p.id'] = $id;
        $list = Db::table('ims_bj_shopn_score_goods_property p')
            ->join(['ims_bj_shopn_goods' => 'g'], 'p.goods_id=g.id', 'left')
            ->join(['ims_bj_shopn_goods_extend' => 'e'], 'p.property_id=e.id', 'left')
            ->field('p.goods_id,p.id,p.exchange_num,g.title,e.name,e.size,e.color')
            ->where($map)
            ->order('p.create_time desc,p.id desc')
            ->limit(1)
            ->find();
        if($list){
            $list['goods_property'] = $list['color'].' '.$list['size'];
            $goods_id = $list['goods_id'];
        }
        $this->assign('list',$list);
        $this->assign('id',$id);
        $this->assign('goods_id',$goods_id);
        return $this->fetch();
    }

    /**
     * 用户积分日志
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function goodsScorelog()
    {
        $key = input('key');
        $map['r.type'] = ['in',['missshop','missshop_transfer','missshop_exchange']];
        if($key&&$key!=="") {
            $map['g.title'] = ['like',"%" . $key . "%"];
        }
        $map['r.scores'] = ['neq',0];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::table('think_scores_record r')
            ->join(['ims_bj_shopn_score_goods_order' => 'o'], 'o.order_sn=r.remark', 'left')
            ->join(['ims_bj_shopn_score_category' => 'c'], 'c.id=o.act_id', 'left')
            ->join(['ims_bj_shopn_goods' => 'g'], 'o.goods_id=g.id', 'left')
            ->join(['ims_bj_shopn_member' => 'm'], 'r.user_id=m.id', 'left')
            ->field('m.realname,m.mobile,r.id,r.user_id,r.type,r.msg,r.scores,r.remark,r.log_time,g.title,c.act_name')
            ->where($map)
            ->order('r.log_time desc,r.id desc')
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        $list = Db::table('think_scores_record r')
            ->join(['ims_bj_shopn_score_goods_order' => 'o'], 'o.order_sn=r.remark', 'left')
            ->join(['ims_bj_shopn_score_category' => 'c'], 'c.id=o.act_id', 'left')
            ->join(['ims_bj_shopn_goods' => 'g'], 'o.goods_id=g.id', 'left')
            ->join(['ims_bj_shopn_member' => 'm'], 'r.user_id=m.id', 'left')
            ->field('m.realname,m.mobile,r.id,r.user_id,r.type,r.msg,r.scores,r.remark,r.log_time,g.title,c.act_name')
            ->where($map)
            ->page($Nowpage, $limits)
            ->order('r.log_time desc,r.id desc')
            ->select();
        if($list){
            $types = [];
            foreach ($list as $v) {
                $types[] = $v['type'];
            }
            $types1 = [];
            foreach ($types as $v) {
                $mapc['act_type'] = ['like','%'.$v.'%'];
                $res_c = Db::table('ims_bj_shopn_score_category c')->where($mapc)->limit(1)->find();
                if($res_c){
                    $types2['type'] = $v;
                    $types2['act_name'] = $res_c['act_name'];
                    $types1[] = $types2;
                }
            }
            foreach ($list as $k => $v) {
                foreach ($types1 as $vt) {
                    if($v['type'] == $vt['type']){
                        $list[$k]['act_name'] = $vt['act_name'];

                    }
                }
            }


        }
        $this->assign('list',$list);
        $this->assign('val',$key);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        return $this->fetch();
    }

    /**
     * 活动积分开关配置
     * @return array
     */
    public function actControll(){
        $map = [];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::table('ims_bj_shopn_act_switch a')
            ->where($map)
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));

        $lists = Db::table('ims_bj_shopn_act_switch a')
            ->where($map)
            ->page($Nowpage, $limits)
            ->order('a.type desc')
            ->select();

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('list', $lists);
        return $this->fetch();
    }

    /**
     * 活动开关配置-编辑
     * @return array
     */
    public function actControllEdit()
    {
        $id = input('id');
        if(request()->isPost()){
            $param = input('post.');
            $data['is_show'] = $param['is_show'];
            $map1['id'] = $id;
            $res = Db::table('ims_bj_shopn_act_switch')->where($map1)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $map['id'] = $id;
        $list = Db::table('ims_bj_shopn_act_switch a')
            ->where($map)
            ->limit(1)
            ->find();
        $this->assign('list',$list);
        $this->assign('id',$id);
        return $this->fetch();
    }
    /**
     * 批量开单操作
     * @return array
     */
    public function score_kaidan()
    {
        $chk_value = input('chk_value');
        $type = input('type');// 1:取消,0:开单
        $chk_value = rtrim($chk_value,',');
        $chk_v_xz = explode(',',$chk_value);
        $map['id'] = ['in',$chk_v_xz];
        $map['is_open_order'] = 0;
        if($type == 1){
            $data['open_order_time'] = 0;
            $data['is_open_order'] = 0;
        }else{
            $data['open_order_time'] = time();
            $data['is_open_order'] = 1;
        }

        $res = Db::table('ims_bj_shopn_score_goods_order')->where($map)->update($data);
        return json(['code' => 1, 'data' => '', 'msg' => '批量处理成功']);
    }
    /**
     * 批量合并订单进入erp操作
     * @return array
     */
    public function score_inserp()
    {
        $erp_sign = input('erp_sign');
        $chk_value = input('chk_value');
        $type = input('type');// 1:取消,0:开单
        $chk_value = rtrim($chk_value,',');
        $chk_v_xz = explode(',',$chk_value);
        $map = [];
        // 选中订单合并
        if($type == 0){
            $map['o.id'] = ['in',$chk_v_xz];
        }else{
            // 搜索出来所有订单合并
            $arr['sign'] = input('key');
            $arr['bsc'] = input('key1');
            $arr['begin_time'] = input('dt1');
            $arr['end_time'] = input('dt2');
            if($arr['begin_time'] && $arr['end_time']){
                $map['o.create_time'] = ['between time',[strtotime($arr['begin_time']),strtotime($arr['end_time'])]];
            }
            if($arr['sign']){
                $map['bwk.sign'] = $arr['sign'];
            }

        }
        // 查询订单
        $res = Db::table('ims_bj_shopn_score_goods_order o')
            ->join(['ims_bj_shopn_goods' => 'g'],'o.goods_id=g.id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'o.store_id=bwk.id','left')
            ->join(['ims_bj_shopn_goods_code' => 'gc'],'gc.goods_id=g.id','left')
            ->field('o.id,o.create_time,o.goods_num,bwk.sign,gc.goods_code,g.marketprice')
            ->where($map)
            ->where('gc.goods_proid=o.property_ids')
            ->order('o.create_time desc')
            ->select();
        // 合并订单
        $arr1 = [];$goods = [];$id_order = [];
        foreach ($res as $v) {
            $id_order[] = $v['id'];
            $arr1['order_sn'] = 'AppScoreOrd_'.time();
            $arr1['create_time'] = $v['create_time'];
            $arr1['sign'] = $v['sign'];
            if(strlen($v['sign']) > 7){
                $arr1['sign'] = substr($v['sign'],0,7);
            }
            $goods1['category'] = '仪器维修';
            $goods1['cinvcode'] = $v['goods_code'];
            $goods1['iquantity'] = $v['goods_num'];
            $goods1['itaxunitprice'] = $v['marketprice'];
            $goods[] = $goods1;
        }
        $arr1['goods'] = $goods;
        if($erp_sign){
            $arr1['sign'] = $erp_sign;
        }
        $erpser = new ErpService();
        $res_erp = $erpser->insertOrder($arr1);
        // 修改订单状态为已开单
        $mapo['id'] = ['in',$id_order];
        $data['open_order_time'] = time();
        $data['is_open_order'] = 1;
        $data['order_sn_merge'] = $arr1['order_sn'];
        Db::table('ims_bj_shopn_score_goods_order')->where($mapo)->update($data);
        return json(['code' => 1, 'data' => '', 'msg' => '合并订单进入Erp系统成功']);
    }
}