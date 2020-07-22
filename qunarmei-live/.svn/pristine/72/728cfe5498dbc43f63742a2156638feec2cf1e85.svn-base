<?php

namespace app\admin\controller;
use think\Db;
use think\Exception;

/**
 * 直播相关配置
 */
class Liveconf extends Base
{

    // 消费券,父商品id对应的ticket_info_id
    // 测试
    public $ticket_info = [
        '1754796' => '2020',
        '1754797' => '2020',
        '1754798' => '2020',
        '1754799' => '2020',
        '1754800' => '2880',
        '1754801' => '3580',
        '1754802' => '3380',
        '1754803' => '3880',
    ];
    // 生产
    // public $ticket_info = [
    //     '1748134' => '2880',
    //     '1941467' => '2020',
    //     '1941468' => '2020',
    //     '1941469' => '2020',
    //     '1941470' => '2880',
    //     '1941471' => '3580',
    //     '1941472' => '3380',
    //     '1941473' => '3880',
    //     '1974355' => '2020',
    // ];

    /**
     * 手机端主播商品配置
     */
    public function goods(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['l.mobile'] = ['like','%'.$key.'%'];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数

        $count = Db::table('think_live_goods l')->join(['ims_bj_shopn_member'=>'m'],['l.mobile=m.mobile'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['l.goods_id=g.id'],'LEFT')
            ->field('l.id,l.is_show,l.is_hot,l.create_time,l.mobile,l.goods_id,m.realname,b.sign,b.title')
            ->where($map)
            ->order('l.id desc')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('think_live_goods l')
            ->join(['ims_bj_shopn_member'=>'m'],['l.mobile=m.mobile'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['l.goods_id=g.id'],'LEFT')
            ->field('l.id,l.is_show,l.is_hot,l.create_time,l.mobile,l.goods_id,m.realname,b.sign,b.title,g.title goods_title')
            ->where($map)
            ->order('l.id desc')
            ->limit($pre,$limits)
            ->select();

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
     * 修改状态
     * @return [type] [description]
     */
    public function upd_goods()
    {
        $id = input('id');
        $map['id'] = $id;
        $status = Db::table('think_live_goods')->where($map)->value('is_show');//判断当前状态情况
        $dt = date('Y-m-d H:i:s');
        if($status==1) {
            $flag = Db::table('think_live_goods')->where($map)->setField(['is_show'=>0,'update_time'=>$dt]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '下架']);
        } else {
            $flag = Db::table('think_live_goods')->where($map)->setField(['is_show'=>1,'update_time'=>$dt]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '上架']);
        }
    }
    /**
     * 添加主播直播间商品
     * @return [type] [description]
     */
    public function add_goods(){
        // 添加提交
        $arr = [];
        if(request()->isAjax()){
            $param = input('post.');
            $data['mobile'] = $param['mobile'];
            $data['goods_id'] = $param['goods_id'];
            // 查询主播门店
            $map_b['mobile'] = $param['mobile'];
            $res_b = Db::table('ims_bj_shopn_member')->where($map_b)->limit(1)->find();
            if($res_b){
                $map_g['storeid'] = $res_b['storeid'];
                $map_g['pid'] = $param['goods_id'];
                $res_g = Db::table('ims_bj_shopn_goods')->where($map_g)->limit(1)->find();
                if($res_g){
                    $data['goods_id'] = $res_g['id'];
                }
            }else{
                $arr['code'] = 0;
                $arr['msg'] = '主播账号不存在';
                return json($arr);
            }
            // 查询门店对应的子商品id
            $data['is_show'] = $param['is_show'];
            $data['create_time'] = date('Y-m-d H:i:s');
            try{
                $res1 = Db::table('think_live_goods')->insert($data);
                $arr['msg'] = '添加成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '添加失败-'.$e->getMessage();
                $arr['code'] = 0;
            }
            return json($arr);
        }
        // 查询父商品可选的列表
        $map['g.isshow'] = 1;
        $map['g.status'] = 1;
        $map['g.deleted'] = 0;
        $map['g.storeid'] = 0;

        $map['c.enabled'] = 1;
        // 可选商品列表
        $res_gd = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_category'=>'c'],['c.id=g.pcate'],'left')->field('g.id,g.title,g.marketprice')->where($map)->order('g.pcate desc')->select();
        $this->assign('list',$res_gd);
        return $this->fetch();
    }
    /**
     * 修改主播直播间商品
     * @return [type] [description]
     */
    public function edit_goods(){
        $id = input('id');
        // 添加提交
        $arr = [];
        if(request()->isAjax()){
            $param = input('post.');
            $map['id'] = $id;
            $data['mobile'] = $param['mobile'];
            $data['goods_id'] = $param['goods_id'];
            $data['is_show'] = $param['is_show'];
            $data['create_time'] = date('Y-m-d H:i:s');
            try{
                $res1 = Db::table('think_live_goods')->where($map)->update($data);
                $arr['msg'] = '添加成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '添加失败-'.$e->getMessage();
                $arr['code'] = 0;
            }
            return json($arr);
        }
        // 查询父商品可选的列表
        $map['g.isshow'] = 1;
        $map['g.status'] = 1;
        $map['g.deleted'] = 0;
        $map['g.storeid'] = 0;
        $map['c.enabled'] = 1;

        // 配置列表
        $mapg['id'] = $id;
        $res_conf = Db::table('think_live_goods')->where($mapg)->order('create_time desc')->limit(1)->find();
        // 查询主播所在门店
        $mapm['mobile'] = $res_conf['mobile'];
        $res_mem = Db::table('ims_bj_shopn_member')->where($mapm)->limit(1)->find();
        $map['g.storeid'] = $res_mem['storeid'];
        // 可选商品列表
        $res_gd = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_category'=>'c'],['c.id=g.pcate'],'left')->field('g.id,g.title,g.marketprice')->where($map)->order('g.pcate desc')->select();
        $this->assign('id',$id);
        $this->assign('list',$res_gd);
        $this->assign('res',$res_conf);
        return $this->fetch();
    }
    /**
     * 删除主播直播间商品
     * @return [type] [description]
     */
    public function del_goods()
    {
        $id = input('id');
        $arr['code'] = 0;
        $arr['msg'] = '删除失败';
        try{
            $map['id'] = $id;
            Db::table('think_live_goods')->where($map)->delete();
            $arr['msg'] = '删除成功';
        }catch(Exception $e){
            $arr['msg'] .= '-'.$e->getMessage();
        }
        return json($arr);
    }
    /**
     * 直播观看权限配置
     */
    public function seeconf(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['c.mobile'] = ['like','%'.$key.'%'];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数

        $count = Db::table('think_live_see_conf c')
            ->join(['ims_bj_shopn_member'=>'m'],['c.mobile=m.mobile'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->field('c.*,m.realname,b.title,b.sign')
            ->where($map)
            ->order('c.start_time desc')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('think_live_see_conf c')
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
     * 添加主播直播观看权限
     * @return [type] [description]
     */
    public function add_seeconf(){
        // 添加提交
        $arr = [];
        $param = input('post.');
        if($param && $param['submit']){
            $data['mobile'] = $param['mobile'];
            $data['store_signs'] = $param['store_signs'];
            $data['see_mobiles'] = $param['see_mobiles'];
            $data['start_time'] = strtotime($param['start_time']);
            $data['end_time'] = strtotime($param['end_time']);
            $data['create_time'] = $param['create_time'];
            $data['remark'] = $param['remark'];
            try{
                $res1 = Db::table('think_live_see_conf')->insert($data);
                $arr['msg'] = '添加成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '添加失败-'.$e->getMessage();
                $arr['code'] = 0;
            }
            return json($arr);
        }
        return $this->fetch();
    }
    /**
     * 修改主播直播权限配置
     * @return [type] [description]
     */
    public function edit_seeconf(){
        $id = input('id');
        // 添加提交
        $arr = [];
        if(request()->isAjax()){
            $param = input('post.');
            $map['id'] = $id;
            $data['mobile'] = $param['mobile'];
            $data['store_signs'] = $param['store_signs'];
            $data['see_mobiles'] = $param['see_mobiles'];
            $data['start_time'] = strtotime($param['start_time']);
            $data['end_time'] = strtotime($param['end_time']);
            $data['create_time'] = $param['create_time'];
            $data['remark'] = $param['remark'];
            try{
                $res1 = Db::table('think_live_see_conf')->where($map)->update($data);
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
        $res_conf = Db::table('think_live_see_conf')->where($mapg)->order('create_time desc')->limit(1)->find();
        if($res_conf){
            $res_conf['start_time'] = date('Y-m-d',$res_conf['start_time']);
            $res_conf['end_time'] = date('Y-m-d',$res_conf['end_time']);
        }
        $this->assign('id',$id);
        $this->assign('res',$res_conf);
        return $this->fetch();
    }
    /**
     * 删除主播直播权限配置
     * @return [type] [description]
     */
    public function del_seeconf()
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
     * 专场直播-方案配置
     */
    public function specialliveconf(){
        $bsc = 1;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $status = [
            '0' => '待审核',
            '1' => '可使用',
            '2' => '已过期',
            '3' => '未通过',
            '4' => '审核中',
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

        $count = Db::table('think_live_see_conf c')
            ->join(['ims_bj_shopn_member'=>'m'],['c.mobile=m.mobile'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->field('c.*,m.realname,b.title,b.sign')
            ->where($map)
            ->order('c.start_time desc')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('think_live_see_conf c')
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
        $arr = [];
        $uid = $_SESSION['think']['uid'];
        $param = input('post.');
        if($param && $param['submit']){
            // 记录钉钉审核通知号码
            $map_ban['admin_id'] = $uid;
            $data_ban['mobile'] = $param['notice_mobile'];
            Db::table('think_admin_ban')->where($map_ban)->update($data_ban);
            $data['mobile'] = $param['mobile'];
            $data['see_mobiles'] = $param['see_mobiles'];
            $data['start_time'] = strtotime($param['start_time']);
            $data['end_time'] = $data['start_time']+ 3600*24;
            $data['create_time'] = isset($param['create_time'])?$param['create_time']:time();
            $data['remark'] = $param['remark'];
            $data['admin_id'] = $uid;
            $data['status'] = 0;
            if($bsc){
                $data['status'] = 1;
            }
            try{
                $res1 = Db::table('think_live_see_conf')->insert($data);
                $arr['msg'] = '添加成功';
                $arr['code'] = 1;
            }catch(Exception $e){
                $arr['msg'] = '添加失败,该主播已有配置,不能重复添加!';
                $arr['code'] = 0;
            }
            return json($arr);
        }
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
            $data['mobile'] = $param['mobile'];
            $data['see_mobiles'] = $param['see_mobiles'];
            $data['start_time'] = strtotime($param['start_time']);
            $data['end_time'] = strtotime($param['end_time']);
            $data['create_time'] = isset($param['create_time'])?$param['create_time']:time();
            $data['remark'] = $param['remark'];
            try{

                if(isset($param['status'])){
                    $data['status'] = $param['status'];
                }


                if(isset($param['status'])){
                    $msg = $param['status']==1?'已通过审核':'未通过审核' ;
                    $msg = '您的专场直播申请'.$msg;

                    $map_admin['c.id'] = $id;
                    $res_admin = Db::table('think_live_see_conf c')->join(['think_admin_ban'=>'b'],['c.admin_id=b.admin_id'],'left')->where($map_admin)->field('b.mobile,c.store_signs')->limit(1)->find();
                    if($res_admin){
                        $signs = explode(',',$res_admin['store_signs']);
                        $mobile = $res_admin['mobile'];// 发送申请人的号码
                        // 发送钉钉通知
                        $res['mobile'] = $mobile;
                        $res['title'] = '专场直播审核结果';
                        $res['content'] = $msg;
                        $this->sendDingDing($res);
                    }

                    // 审核通过,方案上线
                    if($param['status']==1){
                        $data_live_all = [];
                        $map_rule['live_conf_id'] = $id;
                        $map_rule['status'] = 0;
                        $data_rule['status'] = 1;
                        $res_rule = Db::table('ims_bj_shopn_goods_activity_rules')->where($map_rule)->update($data_rule);
                        // 更新方案id到直播配置
                        $map_live['mobile'] = $param['mobile'];
                        $map_live['live_conf_id'] = $id;
                        $res_rule = Db::table('ims_bj_shopn_goods_activity_rules')->where($map_live)->field('id,goods_id,signs')->select();
                        if($res_rule){
                            $rules_id = [];
                            foreach ($res_rule as $vr) {
                                $rules_id[] = $vr['id'];
                                // 应用方案到 ims_bj_shopn_goods
                                $map_gds['b.sign'] = ['in',$vr['signs']];
                                $map_gds['g.pid'] = $vr['goods_id'];
                                $data_gd['g.activity_rules_id'] = $vr['id'];
                                $res_gd = Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['g.storeid=b.id'],'left')->where($map_gds)->update($data_gd);
                                // 查询主播对应的子商品id
                                $map_gd_zi['g.pid'] = $vr['goods_id'];
                                $map_gd_zi['m.mobile'] = $param['mobile'];
                                $res_gd_zi = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_member'=>'m'],['g.storeid=m.storeid'],'left')->field('g.id')->where($map_gd_zi)->limit(1)->find();
                                $data_live1['mobile'] = $param['mobile'];
                                $data_live1['goods_id'] = $res_gd_zi['id'];
                                $data_live1['is_show'] = 1;
                                $data_live1['create_time'] = date('Y-m-d H:i:s');
                                $data_live_all[] = $data_live1;
                            }
                            $rules_id_str = implode(',',$rules_id);
                            $data['activity_rules_id'] = $rules_id_str;
                        }
                        // 审核通过,清空之前主播商品,当前主播商品上线
                        $map_live_gd['mobile'] = $param['mobile'];
                        Db::table('think_live_goods')->where($map_live_gd)->delete();
                        // dump($data_live_all);die;
                        if ($data_live_all) {
                            Db::table('think_live_goods')->insertAll($data_live_all);
                        }
                    }
                }
                $res1 = Db::table('think_live_see_conf')->where($map)->update($data);
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
        $res_conf = Db::table('think_live_see_conf')->where($mapg)->order('create_time desc')->limit(1)->find();
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

        $id = input('id');
        $arr['code'] = 0;
        $arr['msg'] = '申请失败';
        try{
            $map['id'] = $id;
            $data['status'] = 4;
            Db::table('think_live_see_conf')->where($map)->update($data);
            // 发送钉钉通知
            foreach ($mobile as $vm) {
                $res['mobile'] = $vm;
                $res['title'] = '专场直播审核';
                $res['content'] = '有1个专场直播待审核,请登录去哪美后台处理!';
                $this->sendDingDing($res);
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
    public function sale_list()
    {
        $bsc = 1;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $key = input('key');
        $map = [];
        $seeconf_id = input('seeconf_id');
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $map['c.id'] = $seeconf_id;
        $map['r.status'] = 1;
        $count = Db::table('ims_bj_shopn_goods_activity_rules r')->join(['think_live_see_conf'=>'c'],['r.live_conf_id=c.id'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['r.goods_id=g.id'],'LEFT')
            ->field('r.*,g.title,c.mobile')
            ->where($map)
            ->order('r.id desc')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 主播号码,主播名称,主播门店,主播已配置直播间的商品,当前热门商品,配置时间,修改时间
        $lists = Db::table('ims_bj_shopn_goods_activity_rules r')->join(['think_live_see_conf'=>'c'],['r.live_conf_id=c.id'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'g'],['r.goods_id=g.id'],'LEFT')
            ->field('r.*,g.title,c.mobile')
            ->where($map)
            ->order('r.id desc')
            ->limit($pre,$limits)
            ->select();
        if($lists){
            foreach ($lists as $k => $v) {
                $lists[$k]['card_type'] = '';
                $card_name = [];
                $card_type = $v['send_card_type']?explode(',',$v['send_card_type']):'';
                if($card_type){
                    $map_c['scene_prefix'] = ['in',$card_type];
                    $res_card = Db::table('pt_draw_scene')->field('id,scene_name')->where($map_c)->select();
                    if($res_card){
                        foreach ($res_card as $vc) {
                            $card_name[] = $vc['scene_name'];
                        }
                    }
                    if($card_name){
                        $lists[$k]['card_type'] = implode(',',$card_name);
                    }
                }
                $lists[$k]['activity_type'] = $v['activity_type']==1?'买送':'满减';
            }
        }
        // 查询审核状态
        $map_conf['id'] = $seeconf_id;
        $res_conf = Db::table('think_live_see_conf')->field('id,status')->where($map_conf)->limit(1)->find();
        $status = 0;
        if($res_conf && $res_conf['status'] == 0){
            $status = 1;
        }

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        $this->assign('seeconf_id', $seeconf_id);
        $this->assign('bsc', $bsc);
        $this->assign('status', $status);
        if(input('get.page')){
            return json($lists);
        }
        // 直播商品列表
        $mapg['pcate'] = 31;
        $mapg['storeid'] = 0;
        $live_goods = Db::table('ims_bj_shopn_goods')->where($mapg)->field('id,title')->select();
//        echo '<pre>';print_r($live_goods);die;
        $this->assign('live_goods',$live_goods);
        return $this->fetch();
    }
    /**
     * 添加商品方案配置
     * @return [type] [description]
     */
    public function add_sale(){
        $bsc = 1;
        if($_SESSION['think']['rolename'] == '办事处'){
            $bsc = 0;
        }
        $uid = $_SESSION['think']['uid'];
        // 添加提交
        $seeconf_id = input('seeconf_id');
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
            $signs55 = isset($param['signs77'])? implode(',',$param['signs77']):$param['signs66'];
            // dump($store_ids);die;
            if(empty($signs55)){
                $arr['code'] = 0;
                $arr['msg'] = '没有选择门店,请重新选择';
                return json($arr);die;
            }
            $goods_id = $param['activity_type'] == 1 ? $param['goods_id2'] : $param['goods_id1'];
            if($goods_id){
                // 启动事务
//                Db::startTrans();
                foreach ($goods_id as $vg) {
                    $param['goods_id'] = $vg;
                    $data['goods_id'] = $param['goods_id'];
                    $data['goods_num'] = $param['goods_num'];
                    $data['reduction_num'] = $param['reduction_num'];
                    $data['send_card_type'] = isset($param['card_id'])?implode(',',$param['card_id']):'';
                    $data['create_time'] = date('Y-m-d H:i:s');

                    // 查询主播和商品信息

                    $map_zb['id'] = $seeconf_id;
                    $res_zb = Db::table('think_live_see_conf')->where($map_zb)->field('mobile,store_signs')->limit(1)->find();
                    if($res_zb){
                        $data['mobile'] = $res_zb['mobile'];// 主播号码
                        $signs = explode(',',$res_zb['store_signs']);
                    }
                    $map_gd['g.pid'] = $data['goods_id'];
                    $map_gd['m.mobile'] = $res_zb['mobile'];
                    $res_goods = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_member'=>'m'],['g.storeid=m.storeid'],'left')->field('g.activity_rules_id,g.id,g.marketprice,m.mobile,m.storeid')->where($map_gd)->limit(1)->find();
//            dump($res_goods);die;
                    $price = $res_goods['marketprice']?$res_goods['marketprice']:0;

                    $data['price'] = $price * ($data['goods_num'] - $data['reduction_num']);// 商品单价x(总数-优惠数)
                    $data['discount_price'] = $price * $data['reduction_num'];// 商品单价x优惠数
                    $data['sale_price'] = floor($data['price'] / $data['goods_num']);// (支付总价/总数)
                    $data['spread_num'] = $data['price'] - ($data['sale_price'] * $data['goods_num']);// 支付总价-(实际单价x数量)
//                    $data['remark'] = $param['remark']?$param['remark']:'买几送几送优惠券';
                    $data['activity_type'] = $param['activity_type'];
                    $data['rules_name'] = '单品促销';
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
                            $data['remark'] = '';
                        }
                    }

                    $data['live_conf_id'] = $seeconf_id;

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
                    $data['signs'] = $signs55;
                    $res1 = Db::table('ims_bj_shopn_goods_activity_rules')->insertGetId($data);
                    // 查询改规格配置对应的门店信息,更改门店规则对应的商品id
                    if($bsc && $signs){
                        $map_gds['b.sign'] = ['in',$signs];
                        $map_gds['g.pid'] = $data['goods_id'];
                        $data_gd['g.activity_rules_id'] = $res1;
                        $res_gd = Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['g.storeid=b.id'],'left')->where($map_gds)->update($data_gd);
                        // 添加主播对应商品
                        $map_live['goods_id'] = $data['goods_id'];
                        $map_live['mobile'] = $res_zb['mobile'];
                        $res_live_gd = Db::table('think_live_goods g')->where($map_live)->find();
                        if($res_live_gd){
                            $data_live_gd['is_show'] = 1;
                            Db::table('think_live_goods g')->where($map_live)->update($data_live_gd);
                        }else{
                            // 插入
                            $data_live1['mobile'] = $res_zb['mobile'];
                            $data_live1['goods_id'] = $data['goods_id'];
                            $data_live1['is_show'] = 1;
                            $data_live1['create_time'] = date('Y-m-d H:i:s');
                            Db::table('think_live_goods')->insertGetId($map_live);
                        }
                    }
//                dump($res_live_goods);die;
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
        $mapg['goods_property'] = ['exp', ' is null '];
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
        $this->assign('seeconf_id',$seeconf_id);
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
            // 门店记录到对应的方案
            $signs55 = isset($param['signs77'])? implode(',',$param['signs77']):$param['signs66'];
            // dump($store_ids);die;
            if(empty($signs55)){
                $arr['code'] = 0;
                $arr['msg'] = '没有选择门店,请重新选择';
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
            $res_goods = Db::table('ims_bj_shopn_goods')->where($map_gd)->field('marketprice,activity_rules_id')->limit(1)->find();

            $price = $res_goods['marketprice']?$res_goods['marketprice']:0;

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
                $data['signs'] = $signs55;
                $res1 = Db::table('ims_bj_shopn_goods_activity_rules')->where($map)->update($data);
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
        $res_rule = Db::table('ims_bj_shopn_goods_activity_rules')->where($mapr)->limit(1)->find();
        $signs55 = $res_rule['signs'];
        $mapg['pcate'] = 31;
        $mapg['storeid'] = 0;
        $mapg['ticket_type'] = 0;
        $mapg['deleted'] = 0;
        $mapg['isshow'] = 1;
        $mapg['status'] = 1;
        $live_goods = Db::table('ims_bj_shopn_goods')->where($mapg)->field('id,title')->select();

        // 只能没有属性商品设置买送方案
        $mapg['goods_property'] = ['exp', ' is null '];
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
        $this->assign('signs55', $signs55);
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
        $arr['code'] = 0;
        $arr['msg'] = '删除失败';
        try{
            $map['id'] = $id;
            $data['status'] = 0;
            Db::table('ims_bj_shopn_goods_activity_rules')->where($map)->update($data);
            $arr['msg'] = '删除成功';
            // 删除对应的主播商品
            // 删除对应的门店商品方案
            $res = Db::table('ims_bj_shopn_goods_activity_rules')->where($map)->limit(1)->find();
            if ($res) {
                $signs1 = $res['signs']?explode(',',$res['signs']):'';
                if ($signs1) {
                    $mapg['b.sign'] = ['in',$signs1];
                    $datag['activity_rules_id'] = 0;
                    $mapg['g.pid'] = $res['goods_id'];
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
        $id = input('id');
        $arr['code'] = 1;
        $arr['msg'] = '已审核通过';
        $store_signs = ['888-888','666-666'];
        try{
            $map['c.id'] = $id;
            $res_conf = Db::table('think_live_see_conf c')->join(['think_admin_ban'=>'a'],['a.admin_id=c.admin_id'],'left')->field('a.mobile,c.mobile zb_mobile')->where($map)->limit(1)->find();
            $data['status'] = 1;


            // 2.ims_bj_shopn_goods_activity_rules方案上线
            $map_ru['live_conf_id'] = $id;
            $res_rule = Db::table('ims_bj_shopn_goods_activity_rules r')->where($map_ru)->field('id,goods_id,signs')->select();
            if ($res_rule) {
                // 4.清空之前主播商品
                $map_live['mobile'] = $res_conf['zb_mobile'];
                Db::table('think_live_goods')->where($map_live)->delete();
                $data_live = [];
                foreach ($res_rule as $v) {
                    // 3.应用方案到 ims_bj_shopn_goods表
                    $signs = explode(',',$v['signs']);
                    $store_signs = array_merge($store_signs,$signs);
                    $map_gd['g.pid'] = $v['goods_id'];
                    $map_gd['b.sign'] = ['in',$signs];
                    $data_gd['activity_rules_id'] = $v['id'];
                    Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['b.id=g.storeid'],'left')->where($map_gd)->update($data_gd);

                    $map_gd_zi['g.pid'] = $v['goods_id'];
                    $map_gd_zi['m.mobile'] = $res_conf['zb_mobile'];
                    $res_gd_zi = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_member'=>'m'],['m.storeid=g.storeid'],'left')->where($map_gd_zi)->field('g.id')->limit(1)->find();
                    $data_live1['mobile'] = $res_conf['zb_mobile'];
                    $data_live1['goods_id'] = $res_gd_zi['id'];
                    $data_live1['is_show'] = 1;
                    $data_live1['create_time'] = date('Y-m-d H:i:s');
                    // 5.当前主播方案商品上线
                    $map_lg['mobile'] = $res_conf['zb_mobile'];
                    $map_lg['goods_id'] = $data_live1['goods_id'];
                    $res_live_gd = Db::table('think_live_goods')->where($map_lg)->limit(1)->find();
                    if (empty($res_live_gd)) {
                        Db::table('think_live_goods')->insertGetId($data_live1);
                    }
                }
            }
            // 更新直播观看权限和审核状态
            $store_signs = array_unique($store_signs);
            $data['store_signs'] = implode(',',$store_signs);
            $sql = Db::table('think_live_see_conf c')->where($map)->update($data);
            // 1.专场直播审核结果钉钉通知
            $res['mobile'] = $res_conf['mobile'];
            $res['title'] = '专场直播审核';
            $res['content'] = '您的专场直播申请已审核通过!';
            $this->sendDingDing($res);
        }catch(Exception $e){
            $arr['msg'] = '审核失败-'.$e->getMessage();
        }
        return json($arr);
    }
}