<?php

namespace app\admin\controller;
use think\Db;
use think\Exception;

/**
 * 千店千面销售方案相关配置
 */
class Salesplan extends Base
{

    // 消费券,父商品id对应的ticket_info_id
    // 测试
   // public $ticket_info = [
   //     '1754796' => '2020',
   //     '1754797' => '2020',
   //     '1754798' => '2020',
   //     '1754799' => '2020',
   //     '1754800' => '2880',
   //     '1754801' => '3580',
   //     '1754802' => '3380',
   //     '1754803' => '3880',
   // ];
    // 生产
    public $ticket_info = [
        '1748134' => '2880',
        '1941467' => '2020',
        '1941468' => '2020',
        '1941469' => '2020',
        '1941470' => '2880',
        '1941471' => '3580',
        '1941472' => '3380',
        '1941473' => '3880',
        '1974355' => '2020',
    ];

    /**
     * 直播观看权限配置
     */
    public function index(){
        $uid = $_SESSION['think']['uid'];
        $bsc = 1;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $status = [
            '0' => '待申请',
            '1' => '可使用',
            '2' => '已过期',
            '3' => '未通过',
            '4' => '待审核',
        ];
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['c.mobile'] = ['like','%'.$key.'%'];
        }
        // 是否是办事处角色
        $uid = $_SESSION['think']['uid'];
        $map_bwk = null;
        $map['c.admin_id'] = ['>',0];
        if($_SESSION['think']['rolename'] == '办事处'){
            // 非admin角色查看自己创建的配置
            $map['c.admin_id'] = $uid;
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数

        $count = Db::table('think_live_see_conf_examine c')
            ->join(['ims_bj_shopn_member'=>'m'],['c.mobile=m.mobile'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->field('c.*,m.realname,b.title,b.sign')
            ->where($map)
            ->order('c.start_time desc')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('think_live_see_conf_examine c')
            ->join(['ims_bj_shopn_member'=>'m'],['c.mobile=m.mobile'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->field('c.*,m.realname,b.title,b.sign')
            ->where($map)
            ->order('c.id desc')
            ->limit($pre,$limits)
            ->select();
        if($lists){
            foreach($lists as $k=>$v){
                $lists[$k]['realname'] = $v['realname']==null?'':$v['realname'];
                $lists[$k]['title'] = $v['title']==null?'':$v['title'];
                $lists[$k]['sign'] = $v['sign']==null?'':$v['sign'];
                $lists[$k]['start_time'] = $v['start_time']>0?date('Y-m-d H:i:s',$v['start_time']):'';
                $lists[$k]['end_time'] = $v['end_time']>0?date('Y-m-d H:i:s',$v['end_time']):'';
                $lists[$k]['statu_val'] = $status[$v['status']];
            }
        }
        $this->assign('bsc', $bsc);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    // 添加观看权限配置
    public function add_special()
    {
        $bsc = 1;
        $uid = $_SESSION['think']['uid'];
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        // 添加提交
        $arr = [];$data = [];
        $uid = $_SESSION['think']['uid'];
        $param = input('post.');
        if($param && $param['submit']){
            // 记录钉钉审核通知号码
            $data['store_signs'] = isset($param['signs'])?implode(',',$param['signs']):'';
            $data['mobile_examine'] = $param['notice_mobile'];
            $data['mobile'] = $param['mobile'];
            $data['see_mobiles'] = $param['see_mobiles'];
            $data['start_time'] = strtotime($param['start_time']);
            $data['end_time'] = $data['start_time']+ 3600*24;
            $data['create_time'] = isset($param['create_time'])?$param['create_time']:time();
            $data['remark'] = $param['remark'];
            $data['admin_id'] = $uid;
            $data['status'] = 0;
            $data['live_id'] = $param['live_id'];
            if($bsc){
                $data['status'] = 1;
            }
            try{
                $res1 = Db::table('think_live_see_conf_examine')->insert($data);
                if ($bsc) {
                    $data['status'] = 1;
                    unset($data['mobile_examine']);
                    Db::table('think_live_see_conf')->insert($data);
                }
                $arr['msg'] = '添加成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '添加失败-'.$e->getMessage();
                $arr['code'] = 0;
            }
            return json($arr);
        }
        $map_bwk = null;
        if($_SESSION['think']['rolename'] == '办事处'){
            // 查询办事处对应下的门店
            $mobile_rule_ser = new MobileRule();
            $storeids = $mobile_rule_ser->getAdminBranch($uid);
            if($storeids){
                $map_bwk['id'] = ['in',$storeids];
            }
        }
        // 查询账号下的门店列表
        $res_bwk = Db::table('ims_bwk_branch')->field('id,title,sign')->where($map_bwk)->select();
        $this->assign('branch', $res_bwk);
        return $this->fetch();
    }
    // 修改观看权限配置
    public function edit_special()
    {
        $bsc = 1;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $id = input('id');
        // 添加提交
        $arr = [];$signs = [];
        if(request()->isAjax()){
            $param = input('post.');
            $map['id'] = $id;
            $data['store_signs'] = isset($param['signs'])?implode(',',$param['signs']):'';
            $data['mobile'] = $param['mobile'];
            $data['see_mobiles'] = $param['see_mobiles'];
            $data['start_time'] = strtotime($param['start_time']);
            $data['end_time'] = strtotime($param['end_time']);
            $data['create_time'] = isset($param['create_time'])?$param['create_time']:time();
            $data['remark'] = $param['remark'];
            $data['live_id'] = $param['live_id'];
            try{
                if(isset($param['status'])){
                    $data['status'] = $param['status'];
                }
                $res1 = Db::table('think_live_see_conf_examine')->where($map)->update($data);
                $arr['msg'] = '修改成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '修改失败-'.$e->getMessage();
                $arr['code'] = 0;
            }
            return json($arr);
        }
        // 配置列表
        $mapg['id'] = $id;
        $res_conf = Db::table('think_live_see_conf_examine')->where($mapg)->order('create_time desc')->limit(1)->find();
        $signs = '';
        if($res_conf){
            $res_conf['start_time'] = date('Y-m-d',$res_conf['start_time']);
            $res_conf['end_time'] = date('Y-m-d',$res_conf['end_time']);
            $signs = $res_conf['store_signs'];
        }
        // 是否是办事处角色
        $uid = $_SESSION['think']['uid'];
        $map_bwk = null;
        if($_SESSION['think']['rolename'] == '办事处'){
            // 查询办事处对应下的门店
            $mobile_rule_ser = new MobileRule();
            $storeids = $mobile_rule_ser->getAdminBranch($uid);
            if($storeids){
                $map_bwk['id'] = ['in',$storeids];
            }
        }
        // 查询账号下的门店列表
        $res_bwk = Db::table('ims_bwk_branch')->field('id,title,sign')->where($map_bwk)->select();
        $this->assign('branch', $res_bwk);
        $this->assign('id',$id);
        $this->assign('signs',$signs);
        $this->assign('res',$res_conf);
        $this->assign('bsc', $bsc);
        return $this->fetch();
    }
    // 发起申请
    public function apply_special()
    {
        $mobile = ['15921324164','13564501181'];// 通知销售审核的钉钉号码
        // $mobile = ['15921324164'];
        $id = input('id');
        $type = input('type');// 1:直播配置申请审核,2:方案配置申请审核
        $arr['code'] = 0;
        $arr['msg'] = '申请失败';
        try{
            if ($type == 1) {
                $map['id'] = $id;
                // 查询门店
                $res_examine = Db::table('think_live_see_conf_examine')->where($map)->limit(1)->find();
                $data['status'] = 4;
                Db::table('think_live_see_conf_examine')->where($map)->update($data);
                // 发送钉钉通知
                foreach ($mobile as $vm) {
                    $res['mobile'] = $vm;
                    $res['title'] = '专场直播审核';
                    $res['content'] = $res_examine['store_signs'].'有1个专场直播待审核,请登录去哪美后台处理!';
                    // $this->sendDingDing($res);//通知太多,暂时取消
                }
            }elseif ($type == 2) {
                $sign = input('sign');
                $map['signs'] = $sign;
                $map['status'] = 0;
                $data['status'] = 4;
                Db::table('ims_bj_shopn_goods_activity_rules_examine')->where($map)->update($data);
                // 发送钉钉通知
                foreach ($mobile as $vm) {
                    $res['mobile'] = $vm;
                    $res['title'] = '专场直播审核';
                    $res['content'] = $sign.'有1个专场直播销售方案待审核,请登录去哪美后台处理!';
                    // $this->sendDingDing($res);//通知太多,暂时取消
                }
            }
            $arr['msg'] = '申请成功,请耐心等待';
        }catch(Exception $e){
            $arr['msg'] .= '-'.$e->getMessage();
        }
        return json($arr);
    }
    // 删除观看权限配置
    public function del_special()
    {
        $id = input('id');
        $arr['code'] = 0;
        $arr['msg'] = '删除失败';
        try{
            $map['id'] = $id;
            Db::table('think_live_see_conf')->where($map)->delete();
            $arr['msg'] = '删除成功';
        }catch(Exception $e){
            $arr['msg'] .= '-'.$e->getMessage();
        }
        return json($arr);
    }
    /**
     * 商品销售列表--弹窗选择活动开关
     */
    public function salelist()
    {
        $bsc = 1;
        $uid = $_SESSION['think']['uid'] ;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $status = [
            '0' => '待申请',
            '1' => '可使用',
            '2' => '已过期',
            '3' => '未通过',
            '4' => '待审核',
        ];
        $map = [];$sign = [];// 共有的门店编号
        $key = input('key');
        if($key){
            $map['r.signs'] = ['like','%'.$key.'%'];
        }

        if ($bsc == 0) {
            $map['r.admin_id'] = $uid;
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        // 门店列表
        $count = Db::table('ims_bj_shopn_goods_activity_rules_examine r')
            ->join(['ims_bwk_branch'=>'b'],['b.sign=r.signs'],'left')
            ->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'left')
            ->join(['sys_department'=>'sd'],['sd.id_department=sdr.id_department'],'left')
            ->where($map)
            ->field('r.*,b.id storeid,b.title,b.sign,sd.st_department bsc')
            ->group('signs')
            ->order('signs desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('ims_bj_shopn_goods_activity_rules_examine r')
            ->join(['ims_bwk_branch'=>'b'],['b.sign=r.signs'],'left')
            ->join(['sys_departbeauty_relation'=>'sdr'],['sdr.id_beauty=b.id'],'left')
            ->join(['sys_department'=>'sd'],['sd.id_department=sdr.id_department'],'left')
            ->where($map)
            ->field('r.*,b.id storeid,b.title,b.sign,sd.st_department bsc')
            ->group('signs')
            ->order('r.status desc,r.id desc')
            ->limit($pre,$limits)
            ->select();
        if ($lists) {
                foreach ($lists as $k => $v) {
                    $statu = 1;
                    // 查询最后1条记录状态
                    $map_statu['signs'] = $v['signs'];
                    $res_statu = Db::table('ims_bj_shopn_goods_activity_rules_examine r')->where($map_statu)->order('id desc')->limit(1)->find();
                    if ($res_statu) {
                        $statu = $res_statu['status'];
                    }
                    $lists[$k]['statu_val'] = $status[$statu];
                }
            }    
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        $this->assign('bsc', $bsc);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    /**
     * 添加门店商品方案配置
     * @return [type] [description]
     */
    public function add_sale(){
        $bsc = 1;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $uid = $_SESSION['think']['uid'];
        // sale账号自己加自己审核
        if($uid == 20){
            $bsc = 0;
        }
        // 添加提交
        $sign = input('sign');
        $arr = [];$signs = [];
        $param = input('post.');
        if($param && $param['submit']){
            $flag = 0;
            // 买送 送个数<=买个数
            if($param['activity_type']){
                if($param['goods_num'] < $param['reduction_num']){
                    $flag = 1;
                    $arr['code'] = 0;
                    $arr['msg'] = '买送方案-送个数不能大于买个数';
                }
            }else{
                // 满减  减个数<买个数
                if($param['goods_num'] <= $param['reduction_num']){
                    $flag = 1;
                    $arr['code'] = 0;
                    $arr['msg'] = '满减方案-减个数不能大于买个数';
                }
            }
            if($flag == 1){
                return json($arr);die;
            }
            // 门店记录到对应的方案
            $signs55 = $param['signs77'];
            // dump($store_ids);die;
            if(empty($signs55)){
                $arr['code'] = 0;
                $arr['msg'] = '没有选择门店,请重新选择';
                return json($arr);die;
            }
            $goods_id = $param['activity_type'] == 1 ? $param['goods_id2'] : $param['goods_id1'];
            if($goods_id){
                $goods_type = $param['goods_type'];// 是否跨产品组合
                // 启动事务
//                Db::startTrans();
                foreach ($goods_id as $vg) {
                    foreach ($signs55 as $ks => $vs) {
                        $data['admin_id'] = $uid;
                        $data['signs'] = $vs;
                        $data['goods_id'] = $vg;
                        $data['goods_num'] = $param['goods_num'];
                        $data['reduction_num'] = $param['reduction_num'];
                        $data['send_card_type'] = isset($param['card_id'])?implode(',',$param['card_id']):'';
                        $data['create_time'] = date('Y-m-d H:i:s');
                        // 查询商品子信息
                    $map_gd['id'] = $data['goods_id'];
                    $res_goods = Db::table('ims_bj_shopn_goods g')->field('g.activity_rules_id,g.id,g.marketprice,g.live_price')->where($map_gd)->limit(1)->find();
                    $price = $res_goods['marketprice']?$res_goods['marketprice']:0;
                    if($res_goods['live_price'] >= 0.01){
                        $price = $res_goods['live_price'];
                    }
                    $data['price'] = $price * ($data['goods_num'] - $data['reduction_num']);// 商品单价x(总数-优惠数)
                    $data['discount_price'] = $price * $data['reduction_num'];// 商品单价x优惠数
                    $data['sale_price'] = floor($data['price'] / $data['goods_num']);// (支付总价/总数)
                    $data['spread_num'] = $data['price'] - ($data['sale_price'] * $data['goods_num']);// 支付总价-(实际单价x数量)
//                    $data['remark'] = $param['remark']?$param['remark']:'买几送几送优惠券';
                    $data['activity_type'] = $param['activity_type'];
                    $data['rules_name'] = isset($param['rules_name'])?$param['rules_name']:'单品促销';
                    $data['remark'] = $param['activity_type']?'买'.$data['goods_num'].'送'.$data['reduction_num']:'满'.$data['goods_num'].'减'.$data['reduction_num'];
                    if(empty($data['reduction_num']) || $data['reduction_num']<1){
                        if($data['send_card_type']){
                            $data['remark'] = $param['activity_type']?'买'.$data['goods_num'].'送优惠券':'满'.$data['goods_num'].'送优惠券';
                        }else{
                            $data['remark'] = '';
                        }
                    }elseif($data['reduction_num'] > 0){
                        if($data['send_card_type']){
                            $data['remark'] = $param['activity_type']?'买'.$data['goods_num'].'送'.$data['reduction_num'].'送优惠券':'满'.$data['goods_num'].'减'.$data['reduction_num'].'送优惠券';
                        }else{
                            $data['remark'] = $param['activity_type']?'买'.$data['goods_num'].'送'.$data['reduction_num']:'满'.$data['goods_num'].'减'.$data['reduction_num'];
                        }
                    }
                    $ticket_info_id = [];
                    // 查询消费券应该送的是哪个
                    if(isset($param['card_id'])){
                        if(in_array('24',$param['card_id'])){
                        $mapt['send_card_type'] = 24;
                        $res_tick = $this->getTicketInfo($data['goods_id']);
                        $mapt['id'] = $res_tick['id'];
                        $res_ticket_info = Db::table('ims_bj_activity_ticket_info')->where($mapt)->limit(1)->find();
                        if($res_ticket_info){
                            $ticket_info_id[] = $res_ticket_info['id'];
                        }
                    }
//                        dump($param);die;
                    if(in_array('25',$param['card_id']) || in_array('26',$param['card_id'])){
                        $card_id = [];
                        if(in_array('25',$param['card_id'])){
                            $card_id[] = 25;
                        }
                        if(in_array('26',$param['card_id'])){
                            $card_id[] = 26;
                        }
                        $mapt1['send_card_type'] = ['in',$card_id];
                        $res_ticket_info = Db::table('ims_bj_activity_ticket_info')->where($mapt1)->select();
                        if($res_ticket_info){
                            foreach ($res_ticket_info as $vt) {
                                $ticket_info_id[] = $vt['id'];
                            }
                        }
                    }
                    if($ticket_info_id){
                        $data['ticket_info_id'] = implode(',',$ticket_info_id);
                    }
                }
                // 买送
                if($data['activity_type'] == 1){
                    $data['price'] = $price * $data['goods_num'];
                    $data['discount_price'] = 0;
                    $data['sale_price'] = $price;
                    $data['gift_price'] = $price * $data['reduction_num'];//买送配赠价格
                    $data['spread_num'] = 0; //不补差价
                }else{
                    $data['gift_price'] = $data['discount_price'];
                }
                $data['status'] = 0;
                // 如果是组合产品
                if ($goods_type) {
                    $data['goods_id'] = implode(',',$goods_id);
                    // $data['rules_name'] = '跨产品促销';
                }
                foreach ($signs55 as $signv) {
                    unset($data['examine_id']);
                    $data['signs'] = $signv;
                    $res1 = Db::table('ims_bj_shopn_goods_activity_rules_examine')->insertGetId($data);
                    if ($bsc) {
                        $data['status'] = 1;
                        $data['examine_id'] = $res1;
                        $res1 = Db::table('ims_bj_shopn_goods_activity_rules')->insertGetId($data);
                        // 总部添加,直接应用
                        $mapg['b.sign'] = ['in',$vs];
                        $datag['activity_rules_id'] = $res1;
                        $mapg['g.pid'] = $data['goods_id'];
                         Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['g.storeid=b.id'],'left')->where($mapg)->update($datag);
                    }
                }
                // 跨产品
                if ($goods_type){
                    $arr['msg'] = '添加成功';
                    $arr['code'] = 1;
                    return json($arr);
                }
             }
            }
                // 提交事务
//                Db::commit();
                $arr['msg'] = '添加成功';
                $arr['code'] = 1;
            }else{
                $arr['code'] = 0;
                $arr['msg'] = '没有选择商品,请重新选择';
            }
            return json($arr);
        }
        // 直播商品列表
        $mapg['pcate'] = 31;
        $mapg['storeid'] = 0;
        $mapg['ticket_type'] = 0;
        $mapg['deleted'] = 0;
        $mapg['isshow'] = 1;
        $mapg['status'] = 1;
        $live_goods = Db::table('ims_bj_shopn_goods')->where($mapg)->field('id,title')->select();

        // 只能没有属性商品设置买送方案
        $mapg['goods_property'] = null;
        $live_goods2 = Db::table('ims_bj_shopn_goods')->where($mapg)->field('id,title')->select();
//        echo '<pre>';print_r($live_goods);die;
        $map_bwk = null;
        if($_SESSION['think']['rolename'] == '办事处'){
            // 查询办事处对应下的门店
            $mobile_rule_ser = new MobileRule();
            $storeids = $mobile_rule_ser->getAdminBranch($uid);
            if($storeids){
                $map_bwk['id'] = ['in',$storeids];
            }
        }
        // 查询账号下的门店列表
        $res_bwk = Db::table('ims_bwk_branch')->field('id,title,sign')->where($map_bwk)->select();
        $this->assign('branch', $res_bwk);
        $this->assign('live_goods',$live_goods);
        $this->assign('live_goods2',$live_goods2);
        $this->assign('sign',$sign);
        return $this->fetch();
    }
    // 修改商品方案配置
    public function edit_sale(){
        $uid = $_SESSION['think']['uid'];
        $id = input('id');
        $seeconf_id = input('seeconf_id');
        // 添加提交
        $arr = [];$signs55 = '';
        $param = input('post.');
        if(request()->isAjax()){
            $flag = 0;
            // 买送 送个数<=买个数
            if($param['activity_type']){
                if($param['goods_num'] < $param['reduction_num']){
                    $flag = 1;
                    $arr['code'] = 0;
                    $arr['msg'] = '买送方案-送个数不能大于买个数';
                }
            }else{
                // 满减  减个数<买个数
                if($param['goods_num'] <= $param['reduction_num']){
                    $flag = 1;
                    $arr['code'] = 0;
                    $arr['msg'] = '满减方案-减个数不能大于买个数';
                }
            }
            if($flag == 1){
                return json($arr);die;
            }
            $goods_id = $param['activity_type'] == 1 ? $param['goods_id'][1]:$param['goods_id'][0];
            $param['goods_id'] = $goods_id;
            $data['goods_id'] = $param['goods_id'];
            $data['goods_num'] = $param['goods_num'];
            $data['reduction_num'] = $param['reduction_num'];
            $data['send_card_type'] = isset($param['card_id'])?implode(',',$param['card_id']):'';

            // 查询主播和商品信息
            $map_gd['id'] = $data['goods_id'];
            $res_goods = Db::table('ims_bj_shopn_goods')->where($map_gd)->field('marketprice,activity_rules_id,live_price')->limit(1)->find();

            $price = $res_goods['marketprice']?$res_goods['marketprice']:0;
            if($res_goods['live_price'] >= 0.01){
                $price = $res_goods['live_price'];
            }
            $data['price'] = $price * ($data['goods_num'] - $data['reduction_num']);// 商品单价x(总数-优惠数)
            $data['discount_price'] = $price * $data['reduction_num'];// 商品单价x优惠数
            $data['sale_price'] = floor($data['price'] / $data['goods_num']);// (支付总价/总数)
            $data['spread_num'] = $data['price'] - ($data['sale_price'] * $data['goods_num']);// 支付总价-(实际单价x数量)
            $data['activity_type'] = $param['activity_type'];
            if(empty($data['reduction_num']) || $data['reduction_num']<1){
                if($data['send_card_type']){
                    $data['remark'] = $param['activity_type']?'买'.$data['goods_num'].'送优惠券':'满'.$data['goods_num'].'送优惠券';
                }else{
                    $data['remark'] = '';
                }
            }elseif($data['reduction_num'] > 0){
                if($data['send_card_type']){
                    $data['remark'] = $param['activity_type']?'买'.$data['goods_num'].'送'.$data['reduction_num'].'送优惠券':'满'.$data['goods_num'].'减'.$data['reduction_num'].'送优惠券';
                }else{
                    $data['remark'] = '';
                }
            }
            try{
                $map['id'] = $id;
                $ticket_info_id = [];
                // 查询消费券应该送的是哪个
                if(isset($param['card_id'])){
                    if(in_array('24',$param['card_id'])){
                    $mapt['send_card_type'] = 24;
                    $res_tick = $this->getTicketInfo($data['goods_id']);
                    $mapt['id'] = $res_tick['id'];
                    $res_ticket_info = Db::table('ims_bj_activity_ticket_info')->where($mapt)->limit(1)->find();
                    if($res_ticket_info){
                        $ticket_info_id[] = $res_ticket_info['id'];
                    }
                }

                if(in_array('25',$param['card_id']) || in_array('26',$param['card_id'])){
                    $card_id = [];
                    if(in_array('25',$param['card_id'])){
                        $card_id[] = 25;
                    }
                    if(in_array('26',$param['card_id'])){
                        $card_id[] = 26;
                    }
                    $mapt1['send_card_type'] = ['in',$card_id];
                    $res_ticket_info = Db::table('ims_bj_activity_ticket_info')->where($mapt1)->select();
                    if($res_ticket_info){
                        foreach ($res_ticket_info as $vt) {
                            $ticket_info_id[] = $vt['id'];
                        }
                    }
                }
                if($ticket_info_id){
                    $data['ticket_info_id'] = implode(',',$ticket_info_id);
                }
                }
                
                // 买送
                if($data['activity_type'] == 1){
                    $data['price'] = $price * $data['goods_num'];
                    $data['discount_price'] = 0;
                    $data['sale_price'] = $price;
                    $data['gift_price'] = $price * $data['reduction_num'];//买送配赠价格
                    $data['spread_num'] = 0; //不补差价
                }else{
                    $data['gift_price'] = $data['discount_price'];
                }
                $res1 = Db::table('ims_bj_shopn_goods_activity_rules_examine')->where($map)->update($data);
                $arr['msg'] = '修改成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '修改失败-'.$e->getMessage();
                $arr['code'] = 0;
            }
            return json($arr);
        }

        // 查询配置
        $mapr['id'] = $id;
        $res_rule = Db::table('ims_bj_shopn_goods_activity_rules_examine')->where($mapr)->limit(1)->find();
        $signs55 = $res_rule['signs'];
        $mapg['pcate'] = 31;
        $mapg['storeid'] = 0;
        $mapg['ticket_type'] = 0;
        $mapg['deleted'] = 0;
        $mapg['isshow'] = 1;
        $mapg['status'] = 1;
        $live_goods = Db::table('ims_bj_shopn_goods')->where($mapg)->field('id,title')->select();

        // 只能没有属性商品设置买送方案
        $mapg['goods_property'] = null;
        $live_goods2 = Db::table('ims_bj_shopn_goods')->where($mapg)->field('id,title')->select();
        $map_bwk = null;
        if($_SESSION['think']['rolename'] == '办事处'){
            // 查询办事处对应下的门店
            $mobile_rule_ser = new MobileRule();
            $storeids = $mobile_rule_ser->getAdminBranch($uid);
            if($storeids){
                $map_bwk['id'] = ['in',$storeids];
            }
        }
        // 查询账号下的门店列表
        $res_bwk = Db::table('ims_bwk_branch')->field('id,title,sign')->where($map_bwk)->select();
        $this->assign('branch', $res_bwk);
        $this->assign('live_goods2',$live_goods2);
        $this->assign('live_goods',$live_goods);
        $this->assign('res_rule',$res_rule);
        $this->assign('id',$id);
        $this->assign('seeconf_id',$seeconf_id);
        return $this->fetch();
    }
    // 删除产品方案配置
    public function del_sale()
    {
        $id = input('id');
        $type = input('type');//2:删除整个门店方案
        $arr['code'] = 0;
        $arr['msg'] = '删除失败';
        try{
            if ($type == 2) {
                $sign = input('sign');
                $map['signs'] = $sign;
                Db::table('ims_bj_shopn_goods_activity_rules_examine')->where($map)->delete();
                $arr['msg'] = '删除成功';
                // 删除对应的门店商品方案
                $map['signs'] = $sign;
                $res = Db::table('ims_bj_shopn_goods_activity_rules')->where($map)->select();
                if ($res) {
                    $goods_id = [];
                    foreach ($res as $k => $v) {
                        $gd_id = explode(',',$v['goods_id']);
                        foreach ($gd_id as $vg) {
                            $goods_id[] = $vg;
                        }
                    }
                    $mapg['b.sign'] = $sign;
                    $mapg['g.pid'] = ['in',$goods_id];
                    $datag['activity_rules_id'] = 0;
                    Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['g.storeid=b.id'],'left')->where($mapg)->update($datag);
                }
            }else{
                $map['id'] = $id;
                Db::table('ims_bj_shopn_goods_activity_rules_examine')->where($map)->delete();
                $arr['msg'] = '删除成功';
                // 删除对应的门店商品方案
                $map1['examine_id'] = $id;
                $res = Db::table('ims_bj_shopn_goods_activity_rules')->where($map1)->limit(1)->find();
                if ($res) {
                    $mapg['b.sign'] = $res['signs'];
                    $mapg['g.pid'] = $res['goods_id'];
                    $datag['activity_rules_id'] = 0;
                     Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['g.storeid=b.id'],'left')->where($mapg)->update($datag);
                }
            }
            
        }catch(Exception $e){
            $arr['msg'] .= '-'.$e->getMessage();
        }
        return json($arr);
    }
    /**
     * 发送钉钉消息通知
     * @param array $res 数组,[mobile:号码,title:标题,content:内容]
     */
    public function sendDingDing($res)
    {
        $url = config('dingding_domain')."/dingding/message.shtml";
        $data['mobiles'] = [$res['mobile']];
        $data['type'] = 1;
        $data['title'] = $res['title'];
        $data['content'] = $res['content'].'('.date('Y-m-d H:i:s').')';
        $data1 = json_encode($data,JSON_UNESCAPED_UNICODE);

        $res = dingding_curl_post($url,$data1);
    }
    /**
     * 根据商品id查找对应的消费券id
     */
    public function getTicketInfo($goods_id)
    {
        // 商品对应的优惠价格
        $price = $this->ticket_info[$goods_id];

        // 根据优惠价格对应的卡券id
        $map['par_value'] = $price;
        $res = Db::table('ims_bj_activity_ticket_info')->where($map)->limit(1)->find();

        return $res;
    }
    // 修改审核状态
    public function update_statu()
    {
        $uid = $_SESSION['think']['uid'];
        $id = input('id');
        $type = input('type');// 1:直播配置申请审核,2:方案配置申请审核
        $arr['code'] = 0;
        $arr['msg'] = '审核失败';
        $store_signs = ',888-888,666-666,001-001';
        $res = [];
        try{
            // 直播配置审核
            if ($type == 1) {
                $map['id'] = $id;
                $data['status'] = 1;
                $res_conf = Db::table('think_live_see_conf_examine e')->where($map)->limit(1)->find();
                if ($res_conf) {
                    $data['store_signs'] = $res_conf['store_signs'].$store_signs;
                    // 更新
                    Db::table('think_live_see_conf_examine e')->where($map)->update($data);
                    // 插入
                    $datav = $res_conf;
                    $datav['status'] = 1;
                    $datav['store_signs'] = $data['store_signs'];
                    $datav['examine_id'] = $res_conf['id'];
                    unset($datav['id']);
                    unset($datav['mobile_examine']);
                    Db::table('think_live_see_conf')->where($map)->insert($datav);
                }

                $res['content'] = $res_conf['store_signs'].'您的专场直播配置申请已审核通过!';
            }elseif ($type == 2) {
                // 方案配置审核
                $sign = input('id');
                $map['signs'] = $sign;
                $map['status'] = ['in',[0,4]];
                $data['status'] = 1;
                $res_exam = Db::table('ims_bj_shopn_goods_activity_rules_examine')->where($map)->select();
                if ($res_exam) {
                    // 更新
                    Db::table('ims_bj_shopn_goods_activity_rules_examine e')->where($map)->update($data);
                    // 插入
                    foreach ($res_exam as $v) {
                        $datav = $v;
                        $datav['status'] = 1;
                        $datav['examine_id'] = $v['id'];
                        unset($datav['id']);
                        $rule_id = Db::table('ims_bj_shopn_goods_activity_rules')->where($map)->insertGetId($datav);
                        // 更新商品表方案
                        
                        $goods_id = explode(',',$v['goods_id']);
                        $mapg['g.pid'] = ['in',$goods_id];
                        $mapg['b.sign'] = $sign;
                        $datag['g.activity_rules_id'] = $rule_id;
                        Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch' => 'b'],['g.storeid=b.id'],'left')->where($mapg)->update($datag);
                    }
                }
                $res['content'] = $sign.'您的专场直播方案配置申请已审核通过!';
            }
            $arr['code'] = 1;
            $arr['msg'] = '审核通过成功';
            $mape['admin_id'] = $uid;
            $res_notice = Db::table('think_live_see_conf_examine')->where($mape)->order('id desc')->limit(1)->find();
            if ($res_notice) {
                // 1.专场直播审核结果钉钉通知
                $res['mobile'] = '18774840910';// 结果通知胡云凤
                $res['title'] = '专场直播审核';
                // $this->sendDingDing($res); // 通知太多,暂时取消
            }
        }catch(Exception $e){
            $arr['msg'] = '审核失败-'.$e->getMessage();
        }
        return json($arr);
    }
    /**
     * 方案-弹窗列表
     */
    public function sale_list()
    {
        $bsc = 1;
        $uid = $_SESSION['think']['uid'];
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $sign = input('sign');
        $map['signs'] = $sign;
        if ($bsc == 0) {
            $map['e.admin_id'] = $uid;
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        // 门店列表
        $count = Db::table('ims_bj_shopn_goods_activity_rules_examine e')
            ->join(['ims_bj_shopn_goods'=>'g'],['e.goods_id=g.id'],'left')
            ->where($map)
            ->field('e.*,g.title')
            ->order('e.id desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('ims_bj_shopn_goods_activity_rules_examine e')
            ->join(['ims_bj_shopn_goods'=>'g'],['e.goods_id=g.id'],'left')
            ->where($map)
            ->field('e.*,g.title')
            ->order('e.id desc')
            ->limit($pre,$limits)
            ->select();
        if ($lists) {
                foreach ($lists as $k => $v) {
                    // 如果是组合产品
                    if (strstr($v['goods_id'],',')) {
                        $title = [];
                        $gd_id = explode(',',$v['goods_id']);
                        $map_gd['id'] = ['in',$gd_id];
                        $res_gd = Db::table('ims_bj_shopn_goods')->where($map_gd)->select();
                        if ($res_gd) {
                            foreach ($res_gd as $vgd) {
                                $title[] = $vgd['title'];
                            }
                            $lists[$k]['title'] = implode(',',$title);
                        }
                    }

                    $lists[$k]['activity_type'] = $v['activity_type']?'买送':'满减';
                    $lists[$k]['card_type'] = '';
                    $cards = [];
                    if ($v['send_card_type']) {
                        $map_card['scene_prefix'] = ['in',explode(',',$v['send_card_type'])];
                        $res_card = Db::table('pt_draw_scene')->where($map_card)->order('scene_prefix asc')->select();
                        if ($res_card) {
                            foreach ($res_card as $vc) {
                                $cards[] = $vc['scene_name'];
                            }
                        }
                    }
                    if ($cards) {
                        $lists[$k]['card_type'] = implode(',',$cards);
                    }
                    
                }
            }    
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('sign', $sign);
        $this->assign('bsc', $bsc);
        $this->assign('lists', $lists);
        return $this->fetch();
    }
}