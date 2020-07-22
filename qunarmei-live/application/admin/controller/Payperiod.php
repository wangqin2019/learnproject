<?php

namespace app\admin\controller;
use app\admin\model\AdModel;
use app\admin\model\AdPositionModel;
use think\Db;

class PayPeriod extends Base
{
    // 拼购域名
    protected $pgurl = 'https://pin.qunarmei.com/';
    // 默认添加分期支付方式 1,16,17 工行 12,6,3期;15 银联商务支付
    protected $pay_ids = [1,15,16,17];
    //*********************************************广告列表*********************************************//
    /**
     * [index 广告列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['user_name|signs'] = ['like',"%" . $key . "%"];          
        }             
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::table('ims_bj_shopn_payperiod_apply')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('ims_bj_shopn_payperiod_apply')->where($map)->order('create_time desc')->select();
        if ($lists) {
            $status = [
                0=>'待处理',1=>'处理中',2=>'已通过',-1=>'已拒绝'
            ];
            $status_color = [
                0=>'label-default',1=>'label-primary',2=>'label-success',-1=>'label-danger'
            ];
            
            foreach ($lists as $k => $v) {
                $lists[$k]['typeval'] = $v['type']==1?'<font color="red">安心送</font>':'<font color="green">分期支付</font>';
                $lists[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $lists[$k]['status'] = $status[$v['status']];
                $lists[$k]['status_color'] = $status_color[$v['status']];
                $lists[$k]['goods_id'] = '';
                // 查询对应的商品信息
                if ($v['goods_id']) {
                    $mapg = ' id in ('.$v['goods_id'].')';
                    $resg = Db::table('ims_bj_shopn_goods')->where($mapg)->select();
                    if ($resg) {
                        $gd = '';
                        foreach ($resg as $k1 => $v1) {
                            $gd .= $v1['title'].',';
                        }
                        $gd = rtrim($gd,',');
                        $lists[$k]['goods_id'] = $gd;
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
     * [add_ad 添加广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_ad()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['closed'] = 0;
            $ad = new AdModel();
            $flag = $ad->insertAd($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $position = new AdPositionModel();
        $this->assign('position',$position->getAllPosition());
        return $this->fetch();

    }


    /**
     * [edit_ad 编辑广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_ad()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $list = Db::table('ims_bj_shopn_payperiod_apply')->where($map)->limit(1)->find();
        if(request()->isPost()){
            $status = [
                0=>'待处理',1=>'处理中',2=>'已通过',-1=>'已拒绝'
            ];
            $types = [
                0=>'分期支付',1=>'安心送'
            ];
            $param = input('post.');
            $map['id'] = $param['id'];
            $data['status'] = $param['status'];
            $flag = Db::table('ims_bj_shopn_payperiod_apply')->where($map)->update($data);
            // 添加商品分期支付方式
            // 查询门店
            $signs = $list['signs'];
            $signs1 = explode(',',$signs);
            // 安心送申请
            if ($list['type'] == 1) {
                $flag = 0;
                // 门店所有商品开通
                if ($data['status'] == 2) {
                    // 查询门店
                    $mapb['sign'] = ['in',$signs1];
                    $resb = Db::table('ims_bwk_branch')->where($mapb)->select();
                    if ($resb) {
                        $storeids = [];
                        foreach ($resb as $kb => $vb) {
                            // 查询是否开通拼购小程序安心送活动商品
                            $platform = explode(',', $list['platform']);
                            if (in_array('2', $platform)) {
                                // 开通拼购小程序安心送
                                curl_get($this->pgurl.'/api/execute/open_axs/storeid/'.$vb['id']);
                            }
                            $storeids[] = $vb['id'];
                        }
                        $mapg['storeid'] = ['in',$storeids];
                        $datag['is_reassuring'] = 1;
                        $resg = Db::table('ims_bj_shopn_goods')->where($mapg)->update($datag);
                    }
                }
                // 短信通知申请人
                if($data['status'] != 0){
                    $msg1 = ['type'=>$types[$list['type']],'status'=>$status[$data['status']]];
                    $msg = json_encode($msg1,JSON_UNESCAPED_UNICODE);
                    send_sms($list['mobile'],121,$msg);
                }
                // 返回结果
                return json(['code' => 1,'msg' => '修改成功']);
            }
            // 查询商品
            $gd_id = $list['goods_id'];
            $gd_id1 = explode(',',$gd_id);
            // var_dump($signs1);var_dump($gd_id1);die;
            $mapgd['g.pid'] = ['in',$gd_id1];
            $mapgd['b.sign'] = ['in',$signs1];
            $res_gd = Db::table('ims_bj_shopn_goods g')->join(['ims_bwk_branch'=>'b'],['b.id=g.storeid'],'LEFT')->field('g.id,g.storeid')->where($mapgd)->select();
            if($res_gd){
                $datas = [];
                $id_interestrates = [];
                foreach ($res_gd as $kg => $vg) {
                    foreach ($this->pay_ids as $v1) {
                        $pay1['id_store'] = $vg['storeid'];
                        $pay1['id_goods'] = $vg['id'];
                        $pay1['id_interestrate'] = $v1;
                        $datas[] = $pay1;
                    }
                }
                $resi = Db::table('ims_bj_shopn_goods_interestrate')->insertAll($datas);
            }
            // 如果拒绝或成功发短信通知申请人
            $mapm['id'] = $list['user_id'];
            $user = Db::table('ims_bj_shopn_member')->where($mapm)->limit(1)->find();
            $msg1 = ['status'=>$status[$data['status']]];
            $msg = json_encode($msg1,JSON_UNESCAPED_UNICODE);
            send_sms($user['mobile'],116,$msg);
            return json(['code' => 1,'msg' => '修改成功']);
        }
        $this->assign('ad',$list);
        $this->assign('id',$id);
        return $this->fetch();
    }


    /**
     * [del_ad 删除广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_ad()
    {
        $id = input('param.id');
        $ad = new AdModel();
        $flag = $ad->delAd($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * [ad_state 广告状态]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function ad_state()
    {
        $id=input('param.id');
        $status = Db::name('ad')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('ad')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('ad')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }  
    } 



    //*********************************************广告位*********************************************//
    /**
     * [index_position 广告位列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index_position(){

        $ad = new AdPositionModel();    
        $nowpage = input('get.page') ? input('get.page'):1;
        $limits = 10;// 获取总条数
        $count = Db::name('ad_position')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));     
        $list = $ad->getAll($nowpage, $limits); 
        $this->assign('nowpage', $nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * [add_position 添加广告位]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function add_position()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $ad = new AdPositionModel();
            $flag = $ad->insertAdPosition($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return $this->fetch();
    }


    /**
     * [edit_position 编辑广告位]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function edit_position()
    {
        $ad = new AdPositionModel();
        if(request()->isAjax()){
            $param = input('post.');
            $flag = $ad->editAdPosition($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign('ad',$ad->getOne($id));
        return $this->fetch();
    }


    /**
     * [del_position 删除广告位]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del_position()
    {
        $id = input('param.id');
        $ad = new AdPositionModel();
        $flag = $ad->delAdPosition($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [position_state 广告位状态]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function position_state()
    {
        $id=input('param.id');
        $status = Db::name('ad_position')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('ad_position')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('ad_position')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }  
    }  

}