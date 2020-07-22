<?php

namespace app\admin\controller;
use app\api\controller\otherapi\Smsapi;
use app\api\model\BwkItem;
use app\api\model\User;
use think\Db;

use qiniu_transcoding\Upimg;
/*
 * 门店管理配置
 *
 * */
class StoreManage extends Base
{

    /**
     * [service 门店项目配置]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function service(){
        $status = input('status',-100);
        $is_delete = input('is_delete',-100);
        $key = input('key');
//        echo '<pre>status:';print_r($status);print_r('is_delete:');print_r($is_delete);die;
        $map = [];
        $map1 = [];
        if($key&&$key!=="")
        {
            $map = ' b.sign like "%'.$key.'%" or b.title like "%'.$key.'%" or bwk.item_name like "%'.$key.'%"';
        }
        if($status!=-100 && $status!=''){
            $map1['status']= $status;
        }
        if($is_delete!=-100 && $is_delete!=''){
            $map1['is_delete']= $is_delete;
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('store_bwk_item bwk')
            ->join(['ims_bwk_branch'=>'b'],['bwk.store_id=b.id'],'LEFT')
            ->field('bwk.id,bwk.item_name,bwk.item_img,bwk.duration,bwk.item_price,bwk.color,bwk.is_delete,b.title,b.sign,bwk.create_time')
            ->where($map)
            ->where($map1)
            ->order('bwk.create_time desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));

        $lists = Db::table('store_bwk_item bwk')
            ->join(['ims_bwk_branch'=>'b'],['bwk.store_id=b.id'],'LEFT')
            ->field('bwk.id,bwk.item_name,bwk.item_img,bwk.duration,bwk.item_price,bwk.color,bwk.is_delete,b.title,b.sign,bwk.create_time,bwk.status')
            ->where($map)
            ->where($map1)
            ->page($Nowpage,$limits)
            ->order('bwk.create_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        $this->assign('status', $status);
        $this->assign('is_delete', $is_delete);

        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    /**
     * [项目编辑]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function serviceEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            Db::table('store_bwk_item')->where('id',$id)->update($param);
            // 站内信+短信通知店老板 项目状态变更
            $arr['item_id'] = $id;
            $arr['msg'] = '您提交的门店项目已审核,请登录店务App查看详情!';
            $arr['id_temp'] = 89;
            $this->noticeUser($arr);
            return json(['code' => 1, 'data' => [], 'msg' => '修改成功']);
        }
        $map['id'] = $id;
        $list = Db::table('store_bwk_item')->field('*')->where($map)->limit(1)->find();
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * [项目删除]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function serviceDel()
    {
        $id = input('param.id');
        $data['is_delete'] = 1;
        $res = Db::table('store_bwk_item')->where('id',$id)->update($data);
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }

    /**
     * 站内信+短信通知店老板
     * @param array $arr [$item_id项目id,msg通知消息,id_temp短信模板id]
     */
    public function noticeUser($arr)
    {
        // 站内信+短信通知
        $sernotice = new \app\api\service\Smsapi();
        $map['b.id'] = $arr['item_id'];
        $res_u = BwkItem::get(function($query) use($map){
            $map['m.isadmin'] = 1;
            $query->alias('b')
                ->join(['ims_bj_shopn_member'=>'m'],['m.storeid=b.store_id'],'LEFT')
                ->field('m.id,m.mobile')
                ->where($map)
            ;
        });
        if($res_u){
            $arr1 = [
                'user_id' => [$res_u['id']],
                'msg' => $arr['msg'],
                'type' => 4
            ];
//            $sernotice->jpushSend($arr1);
        // 短信推送
            $arr_user['id_temp'] = $arr['id_temp'];
            $arr_user['mobile'] = $res_u['mobile'];
//            $sernotice->smsSend($arr_user);
        }
    }
    /**
     * [service 门店项目评价列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function itemComment(){
        $item_id = input('item_id');
        $key = input('key');
        $map['eva.is_delete'] = 0;
        if($item_id) {
            $map['eva.item_id'] = $item_id;
        }
        if($key&&$key!=="") {
            $map = ' eva.eva_content like "%'.$key.'%" ';
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('store_bwk_item_evaluate eva')
            ->join(['store_bwk_item'=>'b'],['b.id=eva.item_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=eva.user_id'],'LEFT')
            ->join(['ims_bwk_branch'=>'br'],['br.id=m.storeid'],'LEFT')
            ->field('eva.id,m.realname,m.mobile,br.sign,br.title,br.location_p,eva.eva_content,eva.eva_imgs,eva.giveup_users,eva.eva_pid,eva.create_time')
            ->where($map)
            ->order('eva.create_time desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));

        $lists = Db::table('store_bwk_item_evaluate eva')
            ->join(['store_bwk_item'=>'b'],['b.id=eva.item_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=eva.user_id'],'LEFT')
            ->join(['ims_bwk_branch'=>'br'],['br.id=m.storeid'],'LEFT')
            ->field('eva.id,m.realname,m.mobile,br.sign,br.title,br.location_p,eva.eva_content,eva.eva_imgs,eva.giveup_users,eva.eva_pid,eva.create_time')
            ->where($map)
            ->page($Nowpage,$limits)
            ->order('eva.create_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        if($lists){
            foreach ($lists as $k=>$v) {
                if($v['eva_imgs']){
                    $lists[$k]['eva_imgs'] = explode(',',$lists[$k]['eva_imgs']);
                }
                if($v['giveup_users']){
                    // 查询点赞用户
                    $users = explode(',',$v['giveup_users']);
                    $map1['id'] = ['in',$users];
                    $res1 = Db::table('ims_bj_shopn_member')->field('realname')->where($map1)->select();
                    if($res1){
                        $user_names = '';
                        foreach ($res1 as $v1) {
                            $user_names .= $v1['realname'].',';
                        }
                        $lists[$k]['giveup_users'] = rtrim($user_names,',');
                    }
                }
            }
        }
        if(input('get.page'))
        {
            return json($lists);
        }
        $this->assign('key', $key);
        $this->assign('item_id', $item_id);
        return $this->fetch();
    }
    public function itemCommentDel(){
        $map['id'] = input('id');
        $data['is_delete'] = 1;
        $res = Db::table('store_bwk_item_evaluate')->where($map)->update($data);
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }

    /**
     * [service 门店项目订单列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function itemOrder(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="") {
            $map = ' br.sign like "%'.$key.'%" or b.item_name like "%'.$key.'%" ';
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('store_tx_appoint a')
            ->join(['store_bwk_item'=>'b'],['b.id=a.service_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=a.user_id'],'LEFT')
            ->join(['ims_bwk_branch'=>'br'],['br.id=m.storeid'],'LEFT')
            ->field('a.id,m.realname,m.mobile,a.user_name,a.mobile user_mobile,br.sign,br.title,br.location_p,b.item_name,a.appoint_time,a.status,a.appoint_sn,b.item_price,a.pay_price,a.pay_time,a.id_interestrate,a.code_service,a.create_time,a.complete_time,a.service_time')
            ->where($map)
            ->order('a.create_time desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));

        $lists = Db::table('store_tx_appoint a')
            ->join(['store_bwk_item'=>'b'],['b.id=a.service_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=a.user_id'],'LEFT')
            ->join(['ims_bwk_branch'=>'br'],['br.id=m.storeid'],'LEFT')
            ->field('a.id,m.realname,m.mobile,a.user_name,a.mobile user_mobile,br.sign,br.title,br.location_p,b.item_name,a.appoint_time,a.status,a.appoint_sn,b.item_price,a.pay_price,a.pay_time,a.id_interestrate,a.code_service,a.create_time,a.complete_time,a.service_time')
            ->where($map)
            ->page($Nowpage,$limits)
            ->order('a.create_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        if($lists){
            foreach ($lists as $k=>$v) {
                $lists[$k]['bank_name'] = '';
                if($v['id_interestrate']){
                    $map1['sbi.id_interestrate'] = $v['id_interestrate'];
                    $res1 = Db::table('sys_bank_interestrate sbi')
                        ->join(['sys_bank'=>'s'],['s.id_bank=sbi.id_bank'],'LEFT')
                        ->field('sbi.no_period,s.st_abbre_bankname')
                        ->where($map1)
                        ->limit(1)
                        ->find();
                    if($res1){
                        $lists[$k]['bank_name'] = $res1['st_abbre_bankname'].'-'.$res1['no_period'];
                    }
                }
                $lists[$k]['pay_time'] = $lists[$k]['pay_time']==0?'':$lists[$k]['pay_time'];
                $lists[$k]['complete_time'] = $lists[$k]['complete_time']==0?'':$lists[$k]['complete_time'];
                $lists[$k]['service_time'] = $lists[$k]['service_time']==0?'':$lists[$k]['service_time'];
                if($v['pay_time']){
                    $lists[$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
                }
            }
        }
        if(input('get.page'))
        {
            return json($lists);
        }
        $this->assign('key', $key);
        return $this->fetch();
    }

    /**
     * 平台审核商品/服务
     */
    public function ptGoodsApproval(){
        $key = input('key');
        $map = [];
        $map1 = [];
        if($key&&$key!=="")
        {
            $map = ' b.sign like "%'.$key.'%" or b.title like "%'.$key.'%" or bwk.item_name like "%'.$key.'%"';
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数

        $map1['sg.good_status'] = 0;
        $count = Db::table('store_approval a ')
            ->join(['store_goods_ims_bj_shopn_goods'=>'sg'],['sg.audit_id=a.id'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['sg.storeid=b.id'],'LEFT')
            ->field('sg.good_status,sg.id goods_id,b.title store_name,b.sign,a.id approval_id,a.user_id,sg.title goods_title,a.create_time')
            ->where($map)
            ->where($map1)
            ->order('a.create_time desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));

        $lists = Db::table('store_approval a ')
            ->join(['store_goods_ims_bj_shopn_goods'=>'sg'],['sg.audit_id=a.id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['a.user_id=m.id'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['sg.storeid=b.id'],'LEFT')
            ->field('m.realname user_name,m.mobile,sg.good_status,sg.id goods_id,b.title store_name,b.sign,a.id approval_id,a.user_id,sg.title goods_title,a.create_time')
            ->where($map)
            ->where($map1)
            ->page($Nowpage,$limits)
            ->order('a.create_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    public function ptGoodsApprovalEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            $param1['good_status'] = $param['good_status'];
            // 商品状态
            Db::table('store_goods_ims_bj_shopn_goods')->where('id',$id)->update($param1);

            // 调用平台审批接口api
            $data = [
                'user_id' => -1,
                'goods_id' => $param['id'],
                'type' => $param['good_status']
            ];
            $data = json_encode($data);
            $url = 'http://192.168.7.70:86/api/test/ptGoodsSh?data='.$data;
            $res = curl_get($url);

            return json(['code' => 1, 'data' => [], 'msg' => '修改成功']);
        }
        $map['id'] = $id;
        $list = Db::table('store_goods_ims_bj_shopn_goods')->field('*')->where($map)->limit(1)->find();
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }
    /**
     * 平台审核服务
     */
    public function ptItemApproval(){
        $key = input('key');
        $map = [];
        $map1 = [];
        if($key&&$key!=="")
        {
            $map = ' b.sign like "%'.$key.'%" or b.title like "%'.$key.'%" or bwk.item_name like "%'.$key.'%"';
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数

        $map1['i.status'] = 0;
        $count = Db::table('store_approval a ')
            ->join(['store_bwk_item'=>'i'],['i.audit_id=a.id'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['i.store_id=b.id'],'LEFT')
            ->field('i.status,i.id item_id,b.title store_name,b.sign,a.id approval_id,a.user_id,i.item_name,a.create_time')
            ->where($map)
            ->where($map1)
            ->order('a.create_time desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));

        $lists = Db::table('store_approval a ')
            ->join(['store_bwk_item'=>'i'],['i.audit_id=a.id'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['i.store_id=b.id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['a.user_id=m.id'],'LEFT')
            ->field('m.realname user_name,m.mobile,i.status,i.id item_id,b.title store_name,b.sign,a.id approval_id,a.user_id,i.item_name,a.create_time')
            ->where($map)
            ->where($map1)
            ->page($Nowpage,$limits)
            ->order('a.create_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }
    public function ptItemApprovalEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            $param1['status'] = $param['status'];
            // 商品状态
            Db::table('store_bwk_item')->where('id',$id)->update($param1);

            // 调用平台审批接口api
            $data = [
                'user_id' => -1,
                'item_id' => $param['id'],
                'type' => $param['status']
            ];
            $data = json_encode($data);
            $url = 'http://192.168.7.70:86/api/test/ptItemSh?data='.$data;
            $res = curl_get($url);

            return json(['code' => 1, 'data' => [], 'msg' => '修改成功']);
        }
        $map['id'] = $id;
        $list = Db::table('store_bwk_item')->field('*')->where($map)->limit(1)->find();
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 商品详情
     */
    public function goodsInfo(){
        $id = input('id');
        // 商品封面,商品轮播图,商品名称,商品分类名称,销售价,体验价,划线价,体验价开关,商品详情
        // [商品型号,售价,库存]
        $map['id'] = $id;
        $lists = Db::table('store_goods_ims_bj_shopn_goods sg ')
            ->field('id,good_status,thumbhome,thumb_url,title,pcate,marketprice,experience_price,productprice,experience_price_flag,content,createtime')
            ->where($map)
            ->limit(1)
            ->find();
        if($lists){
            if(strlen($lists['thumb_url'])>5){
                $thumb_url = null;
                $lists['thumb_url'] = unserialize($lists['thumb_url']);
                foreach ($lists['thumb_url'] as $v) {
                    $thumb_url[] = $v['attachment'];
                }
                $lists['thumb_url'] = $thumb_url;
            }else{
                $lists['thumb_url'] = '';
            }

            $lists['experience_price_flag'] = $lists['experience_price_flag']==1?'开':'关';
            $good_status = [0=>'<span style="color:red">审核中</span>',1=>'<span style="color:green">出售中</span>',-1=>'<span style="color:yellow">已下架</span>'];
            $lists['good_status'] = $good_status[$lists['good_status']];
            $lists['createtime'] = $lists['createtime']>0?date('Y-m-d H:i:s',$lists['createtime']):'';
            $lists['xh'] = '';
            // 查询型号列表
            $mapx['goods_id'] = $id;
            $res_xh = Db::table('store_goods_spec')
                ->field('id,goods_id,goods_spec,sale_price,stock_num')
                ->where($mapx)
                ->select();
            if($res_xh){
                foreach ($res_xh as $v) {
                    $lists['xh'] .= '型号:'.$v['goods_spec'].'-售价:'.$v['sale_price'].'-库存:'.$v['stock_num'].'<br/>';
                }
            }
            // 查询分类名称
            $mapf['id'] = $lists['pcate'];
            $res_f = Db::table('store_goods_ims_bj_shopn_category')
                ->field('id,name')
                ->where($mapf)
                ->limit(1)
                ->find();
            if($res_f){
                $lists['pcate'] = $res_f['name'];
            }
        }
//        echo '<pre>';print_r($lists);
        $this->assign('id', $id);
        $this->assign('list', $lists);

        return $this->fetch();
    }
    /**
     * 服务详情
     */
    public function itemInfo(){
        $id = input('id');
        $map['id'] = $id;
        $lists = Db::table('store_bwk_item')
            ->field('id,item_name,item_img,item_wheplan_img,item_detail,label_id,experience_price_flag,item_price,experience_price,line_price,duration,create_time,status')
            ->where($map)
            ->limit(1)
            ->find();
        if($lists){
            // 服务轮播图,服务封面图,服务名称,服务时长,体验价商品,销售价,体验价,划线价,商品详情,服务分类
            $lists['cate_name'] = '';
            $lists['duration'] = $lists['duration']>0?$lists['duration'].'分钟':'';
            $lists['experience_price_flag'] = $lists['experience_price_flag']==1?'开':'关';
            $lists['item_wheplan_img'] = $lists['item_wheplan_img']==''?'':explode(',',$lists['item_wheplan_img']);
            $item_status = [0=>'<span style="color:red">审核中</span>',1=>'<span style="color:green">出售中</span>',-1=>'<span style="color:yellow">已下架</span>'];
            $lists['status'] = $item_status[$lists['status']];
//            $lists['createtime'] = $lists['createtime']>0?date('Y-m-d H:i:s',$lists['createtime']):'';
//            $lists['xh'] = '';
//            // 查询型号列表
//            $mapx['goods_id'] = $id;
//            $res_xh = Db::table('store_goods_spec')
//                ->field('id,goods_id,goods_spec,sale_price,stock_num')
//                ->where($mapx)
//                ->select();
//            if($res_xh){
//                foreach ($res_xh as $v) {
//                    $lists['xh'] .= '型号:'.$v['goods_spec'].'-售价:'.$v['sale_price'].'-库存:'.$v['stock_num'].'<br/>';
//                }
//            }
            // 查询分类名称
            $mapf['id'] = $lists['label_id'];
            $res_f = Db::table('store_bwk_item_evalabel')
                ->field('id,evl_name')
                ->where($mapf)
                ->limit(1)
                ->find();
            if($res_f){
                $lists['cate_name'] = $res_f['evl_name'];
            }
        }
//        echo '<pre>';print_r($lists);die;
        $this->assign('id', $id);
        $this->assign('list', $lists);
        return $this->fetch();
    }

    /**
     * 申请门店-审核
     */
    public function store_check(){
        $key = input('key');
        $map = [];
        $map1 = [];
        if($key&&$key!=="")
        {
            $map = ' b.sign like "%'.$key.'%" or b.title like "%'.$key.'%" ';
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数

        $count = Db::table('ims_bwk_branch_review a ')
            ->field('*')
            ->where($map)
            ->order('a.createtime desc')
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $map2['status'] = ['<',4];
        $lists = Db::table('ims_bwk_branch_review a ')
            ->field('*')
            ->where($map)
            ->where($map2)
            ->page($Nowpage,$limits)
            ->order('a.createtime desc')
            ->select();
        if($lists){
            $status = [
                1=>'待审核',
                2=>'<span style="color: red;">审核失败</span>',
                3=>'初审中',
                4=>'审核成功'
            ];
            $bscs = [];
            foreach ($lists as $k=>$v) {
                $bscs[] = $v['bsc'];
                $lists[$k]['createtime'] = date('Y-m-d H:i:s',$lists[$k]['createtime']);
                $lists[$k]['status'] = $status[$lists[$k]['status']];
            }
            // 查询对应的办事处
            $map1['id_department'] = ['in',$bscs];
            $res_bsc = Db::table('sys_department')
                ->field('id_department,st_department')
                ->where($map1)
                ->select();
            if($res_bsc){
                foreach($res_bsc as $vb){
                    foreach ($lists as $k=>$v) {
                        if($vb['id_department'] == $v['bsc']){
                            $lists[$k]['bsc'] = $vb['st_department'];
                        }
                    }
                }
            }
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count); //总条数
        $this->assign('val', $key);
        $this->assign('lists', $lists);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 申请门店-审核-修改
     */
    public function store_check_edit()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $list = Db::table('ims_bwk_branch_review')->field('*')->where($map)->limit(1)->find();
        if(request()->isAjax()){
            $param = input('post.');
            $param1['status'] = $param['status'];
            $param1['sign'] = $param['sign'];
            // 商品状态
            Db::table('ims_bwk_branch_review')->where('id',$id)->update($param1);
            // 如果审核成功,则入库ims_bwk_branch
            if($param1['status'] == 4){
                $data = [
                    'weid' => 1,
                    'title' => $list['title'],
                    'sign' => $param1['sign'],
                    'location_p' => $list['location_p'],
                    'location_c' => $list['location_c'],
                    'location_a' => $list['location_a'],
                    'address' => $list['address'],
                    'lat' => $list['lat'],
                    'lng' => $list['lng'],
                    'summary' => $list['summary'],
                    'content' => $list['content'],
                    'isshow' => $list['isshow'],
                    'createtime' => time()
                ];
                Db::table('ims_bwk_branch')->insertGetId($data);

                /*审核通过: 1.自动添加微商城商品 2.自动添加所对应的办事处管理 3.自动添加拼购活动商品 */

            }
            return json(['code' => 1, 'data' => [], 'msg' => '修改成功']);
        }
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }
}