<?php

namespace app\admin\controller;
use app\admin\model\BranchModel;
use app\admin\model\GoodsModel;
use app\admin\model\PintuanModel;
use app\admin\model\PintuanOrderModel;
use think\Db;
use weixin\WeixinRefund;

class Pintuan extends Base{

    /**
     * [index 拼团列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function index(){
        header("Cache-control: private");
        $key = input('key');
        $store_id = input('store_id');
        $is_custom = input('is_custom','-1');
        $map = [];
        if($key&&$key!==""){
            $map['p_name'] = ['like',"%" . $key . "%"];
        }
        if($store_id && $store_id!==""){
            $map['storeid'] = ['eq',$store_id];
        }
        if($is_custom && $is_custom!=-1){
            $map['is_custom'] = ['eq',$is_custom];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuanInfo')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $user = new PintuanModel();
        $lists = $user->getTuanByWhere($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('store_id', $store_id);
        $this->assign('is_custom', $is_custom);
        $this->assign('count',$count);
        $branchList=Db::name('tuan_info')->alias('info')->join(['ims_bwk_branch' => 'b'],'info.storeid=b.id','left')->field('b.id,b.title,b.sign')->group('info.storeid')->select();
        $this->assign('branchList',$branchList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [userAdd 添加拼团]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function add(){
        if(request()->isAjax()){
            $param = input('post.');
            $param['prizeid'] = implode(',',$param['prizeid']);
            $pt = new PintuanModel();
            $flag = $pt->insertUser($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        //获取门店
        $branch=new BranchModel();
        $storeList=$branch->getAllBranch();
        $this->assign('storeList',$storeList);
        //获取活动配赠产品
        $goods=new GoodsModel();
        $goodsList=$goods->getAllGoodsInfo(['goods_cate'=>2,'status'=>1]);
        $this->assign('prizeGoodsList',$goodsList);
        return $this->fetch();
    }


    /**
     * [userEdit 编辑拼团]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function edit(){
        $pt = new PintuanModel();
        if(request()->isAjax()){
            $param = input('post.');
            $param['prizeid'] = implode(',',$param['prizeid']);
            if($param['carousel_from_goods']){
                if($param['carousel_self']) {
                    $param['carousel_self'] = implode(',', $param['carousel_self']);
                }else{
                    $param['carousel_self']='';
                }
            }else{
                $param['carousel_self']='';
            }
            if($param['content_from_goods']){
                $content_self=$param['content_self'];
            }else{
                $content_self='';
            }
            $param['content_self']=$content_self;
            //如果该活动包含自定义配置 给标识 自定义配置的活动 不能通过一键修改 修改内容
            if($param['carousel_from_goods'] || $param['content_from_goods']){
                $param['is_custom']=1;
            }else{
                $param['is_custom']=0;
            }
            $flag = $pt->editPt($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info=$pt->getOnePy($id);
        $carousel_self=explode(',',$info['carousel_self']);
        $carousel_self=array_filter($carousel_self);
        if(count($carousel_self)){
             $info['carousel_self']=$carousel_self;
        }else{
            $info['carousel_self']='';
        }
        $this->assign('info',$info);
        //获取门店
        $branch=new BranchModel();
        $storeList=$branch->getAllBranch();
        $this->assign('storeList',$storeList);
        //获取活动配赠产品
        $goods=new GoodsModel();
        $goodsList=$goods->getAllGoodsInfo(['goods_cate'=>2,'status'=>1]);
        $this->assign('prizeGoodsList',$goodsList);
        return $this->fetch();
    }


    /**
     * [UserDel 删除拼团]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function del(){
        $id = input('param.id');
        $role = new PintuanModel();
        $flag = $role->delPt($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * [user_state 拼团状态]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function state(){
        $id = input('param.id');
        $status = Db::name('tuan_info')->where('id',$id)->value('pt_status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('tuan_info')->where('id',$id)->setField(['pt_status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已关闭']);
        } else {
            $flag = Db::name('tuan_info')->where('id',$id)->setField(['pt_status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    }


    public function addAll(){
        //获取门店拼购产品
        $getFirstBranch=Db::name('tuan_info')->limit(1)->value('storeid');
        $goodsList=Db::name('tuan_info')->field('id,p_name,pt_num_max,pid')->where('storeid',$getFirstBranch)->select();
        if(request()->isAjax()){
            try {
                $param = input('post.');
                foreach ($goodsList as $k => $v) {
                    $info = Db::name('tuan_info')->where('id', $v['id'])->find();
                    unset($info['id']);
                    $info['storeid'] = $param['storeid'];
                    $info['pt_num_max'] = $param['pt_num_max'][$k];
                    $info['create_time'] = time();
                    $info['update_time'] = time();
                    $check=Db::name('tuan_info')->where(['storeid'=>$param['storeid'],'pid'=>$v['pid']])->count();
                    if(!$check) {
                        Db::name('tuan_info')->insert($info);
                    }

                }
                return json(['code' => 1, 'data' => '', 'msg' => '批量复制成功']);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '批量复制失败'.$e->getMessage()]);
            }
        }
        //获取门店
        $branch=new BranchModel();
        $storeList=$branch->getAllBranch();
        $this->assign('storeList',$storeList);
        $this->assign('goodsList',$goodsList);
        return $this->fetch('all');
    }






//    public function addAllByKey(){
//        set_time_limit(0);
//        //获取门店拼购产品
//        $getFirstBranch=2;
//        $allBranch=Db::table('ims_bwk_branch')->field('id')->order('id desc')->select();
//        $goodsList=Db::name('tuan_info')->field('id,p_name,pt_num_max,pid')->where('storeid',$getFirstBranch)->select();
//        if(request()->isAjax()){
//            try {
//                $param = input('post.');
//                foreach ($allBranch as $key=>$val) {
//                    foreach ($goodsList as $k => $v) {
//                        $info = Db::name('tuan_info')->where('id', $v['id'])->find();
//                        unset($info['id']);
//                        $info['storeid'] = $val['id'];
//                        $info['pt_num_max'] = $param['pt_num_max'][$k];
//                        $check = Db::name('tuan_info')->where(['storeid' => $val['id'], 'pid' => $v['pid']])->count();
//                        if (!$check) {
//                            Db::name('tuan_info')->insert($info);
//                        }
//                    }
//                }
//                return json(['code' => 1, 'data' => '', 'msg' => '批量复制成功']);
//            }catch (\Exception $e){
//                return json(['code' => 0, 'data' => '', 'msg' => '批量复制失败'.$e->getMessage()]);
//            }
//        }
//        //获取门店
//        $branch=new BranchModel();
//        $storeList=$branch->getAllBranch();
//        $this->assign('storeList',$storeList);
//        $this->assign('goodsList',$goodsList);
//
//        return $this->fetch('all');
//    }






    /**
     * [order 拼购订单列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function order(){
        set_time_limit(0);
        header("Cache-control: private");
        $key = input('key');
        $store_id = input('store_id');
        $id_department = input('id_department');
        $export = input('export',0);
        $pid = input('pid');
        $status = input('status','');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $map = [];
        if($key&&$key!==""){
            $map['info.p_name|list.order_sn'] = ['like',"%" . $key . "%"];
        }
        if($store_id && $store_id!==""){
            $map['list.storeid'] = ['eq',$store_id];
        }
        if($id_department && $id_department!==""){
            $getBranch=Db::table('sys_departbeauty_relation')->where('id_department',$id_department)->column('id_beauty');
            $map['list.storeid'] = ['in',$getBranch];
        }
        if($pid && $pid!==""){
            $map['list.pid'] = ['eq',$pid];
        }
        if($status){
            $map['list.status']=array('eq',$status);
        }

        if($search_time1!='' && $search_time2!=''){
            if($export) {
                $map['list.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }else{
                $map['list.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }
        }
        $map['list.order_type']=array('eq',1);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuanList')->alias('list')->join('tuan_info info','list.tuan_id=info.id')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $user = new PintuanOrderModel();
        $lists = $user->getTuanByWhere($map, $Nowpage, $limits);
        if($export){
            //$map['list.status']=array('eq',2);
            $exportLists =$user->getTuanByWhere($map, $Nowpage, 1000000);
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['timeDiff']=timeDiff(time(),$v['end_time']);
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            $lists[$k]['end_time']=date('Y-m-d H:i:s',$v['end_time']);
            $lists[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['storeid'])->value('d.st_department');
        }
        //导出
        if($export){
            $data=array();
            foreach ($exportLists as $k => $v) {
                if($v['status']==1) {
                    $statusText = "进行中";
                }elseif ($v['status']==2){
                    $statusText = "已完成";
                }elseif ($v['status']==3){
                    $statusText = "需退款";
                }elseif ($v['status']==4){
                    $statusText = "已退款";
                }else{
                    $statusText = "已失效";
                }
                $sonList=$user->orderList1($v['order_sn']);
//                $sonListTest='';
                $join_num=0;
                foreach ($sonList as $kk=>$vv){
                    if($vv['uid']<>$v['create_uid']){
                        $join_num++;
                    }
                    if($kk!=0) {
                        $sMap['list.status'] = array('eq', 2);
                        $sMap['order.flag'] = array('eq', 1);
                        $sMap['order.pay_by_self'] = array('eq', 0);
                        $sMap['order.uid'] = array('eq', $vv['uid']);
                        $searchJoinOrder = Db::name('tuan_order')->alias('order')->join('tuan_list list', 'order.parent_order=list.order_sn', 'left')->where($sMap)->count();
                        if ($searchJoinOrder >= 2) {
                            $sonList[$kk]['join_num'] = 1;
                        } else {
                            $sonList[$kk]['join_num'] = 0;
                        }
                    }

//                    $coudan=$vv['pay_by_self']?'发起人凑单':'';
//                    if($vv['pay_status']==1){
//                        $sonListTest.=($kk+1)."、单号：".$vv['order_sn']." 状态：已付款 参团人：".$vv['realname']." 参团人电话：".$vv['mobile']." 支付时间：".date('Y-m-d H:i:s',$vv['pay_time'])." 支付金额：".$vv['pay_price']." 备注：".$coudan."\n";
//                    }elseif($vv['pay_status']==2){
//                        $sonListTest.=($kk+1)."、单号：".$vv['order_sn']." 状态：已退款 参团人：".$vv['realname']." 参团人电话：".$vv['mobile']." 支付时间：".date('Y-m-d H:i:s',$vv['pay_time'])." 支付金额：".$vv['pay_price']." 备注：".$coudan."\n";
//                    }else{
//                        $sonListTest.=($kk+1)."、单号：".$vv['order_sn']." 状态：等待成交"."\n";
//                    }
                }
                $data[$k]['realname']=$v['realname'];
                $seller=Db::table('ims_bj_shopn_member')->where('id',$v['staffid'])->field('realname,mobile')->find();
                $data[$k]['seller']=$seller['realname'];
                $data[$k]['sellerMobile']=$seller['mobile'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['storeid'])->value('d.st_department');
                $data[$k]['order_sn']="\t".$v['order_sn']."\t";
                $data[$k]['p_name']=$v['p_name'];
                $data[$k]['p_price']=$v['tuan_price'];
                $data[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
                $data[$k]['success_time']=date('Y-m-d H:i:s',$v['success_time']);
                $data[$k]['status']=$statusText;
                $data[$k]['fqr_pay']=$sonList[0]['pay_price'];
                $data[$k]['fqr_tel']=$sonList[0]['mobile'];
                $data[$k]['cantuan1']=str_replace("=","",$sonList[1]['realname']);
                $data[$k]['cantuan1_tel']=$sonList[1]['mobile'];
                $data[$k]['cantuan1_pay']=$sonList[1]['mobile']?$sonList[1]['pay_price']:'';
                $data[$k]['cantuan1_num']=$sonList[1]['join_num'];
                $data[$k]['cantuan2']=str_replace("=","",$sonList[2]['realname']);
                $data[$k]['cantuan2_tel']=$sonList[2]['mobile'];
                $data[$k]['cantuan2_pay']=$sonList[2]['mobile']?$sonList[2]['pay_price']:'';
                $data[$k]['cantuan2_num']=$sonList[2]['join_num'];;
                $data[$k]['cantuan3']=str_replace("=","",$sonList[3]['realname']);
                $data[$k]['cantuan3_tel']=$sonList[3]['mobile'];
                $data[$k]['cantuan3_pay']=$sonList[3]['mobile']?$sonList[3]['pay_price']:'';
                $data[$k]['cantuan3_num']=$sonList[3]['join_num'];;
                $searchOrder=Db::name('tuan_list')->where(['create_uid'=>$v['create_uid'],'status'=>2])->whereTime('begin_time', '<', $v['begin_time'])->count();
                $data[$k]['is_first']=$searchOrder?0:1;
                $data[$k]['number']=$join_num;
                $seaMap['list.create_uid']=array('neq',$v['create_uid']);
                $seaMap['list.status']=array('eq',2);
                $seaMap['list.order_sn']=array('neq',$v['order_sn']);
                $seaMap['order.uid']=array('eq',$v['create_uid']);
                $searchJoinOrder=Db::name('tuan_order')->alias('order')->join('tuan_list list','order.parent_order=list.order_sn','left')->where($seaMap)->whereTime('order.insert_time', '<', $v['begin_time'])->count();
                $data[$k]['is_join']=$searchJoinOrder?1:0;
            }

            $filename = "拼团订单列表".date('YmdHis');
            $header = array ('活动发起人','所属美容师','美容师电话','所属门店编码','所属门店名称','所属办事处','拼购单号','拼购商品','商品总价','发起时间','成团时间','拼购状态','发起人支付金额','发起人手机号码','参团人1','手机号码1','支付金额1','参团人1拓客留客','参团人2','手机号码2','支付金额2','参团人2拓客留客','参团人3','手机号码3','支付金额3','参团人3拓客留客','发起人是否首次发起','参团人数','发起人是否参加过拼团');
            $widths=array('15','20','20','20','30','30','30','60','10','20','20','20','20','20','20','20','20','20','20','20','20','20','20','20','20','20','20','20','20');
            if($data) {
                try {
                    if($export==1){
                        excelExport($filename, $header, $data, $widths);//生成数据
                    }else{
                        export_data($data,$header,$filename);
                    }
                }catch (\Exception $e){
                    echo $e->getMessage();
                }
                //excelExport($filename, $header, $data, $widths);//生成数据
                //csv导出修改excel导出
                //$header = array ('拼团完成时间','门店编码','门店名称','所属办事处','开户行名称','开户人','银行卡号','是否对账','返款金额');
                //export_data($data,$header,$filename);
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('store_id', $store_id);
        $this->assign('id_department', $id_department);
        $this->assign('status', $status);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        $this->assign('pid',$pid);
        $this->assign('count',$count);
        $branch=new BranchModel();
        $branchList=$branch->getAllBranch();
        $this->assign('branchList',$branchList);
        $goods=new GoodsModel();
        $goodsList=$goods->getAllByWhere(['goods_cate'=>3,'status'=>1]);
        $this->assign('goodsList',$goodsList);
        $bsc=Db::table('sys_department')->field('id_department,st_department')->select();
        $this->assign('bsc',$bsc);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }



    /**
     * [order 单独购买订单列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function order_by_self(){
        header("Cache-control: private");
        $key = input('key');
        $store_id = input('store_id');
        $id_department = input('id_department');
        $export = input('export',0);
        $pid = input('pid');
        $status = input('status','');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $map = [];
        if($key&&$key!==""){
            $map['info.p_name|list.order_sn'] = ['like',"%" . $key . "%"];
        }
        if($store_id && $store_id!==""){
            $map['list.storeid'] = ['eq',$store_id];
        }
        if($id_department && $id_department!==""){
            $getBranch=Db::table('sys_departbeauty_relation')->where('id_department',$id_department)->column('id_beauty');
            $map['list.storeid'] = ['in',$getBranch];
        }
        if($pid && $pid!==""){
            $map['list.pid'] = ['eq',$pid];
        }
        if($status){
            $map['list.status']=array('eq',$status);
        }
        if($search_time1!='' && $search_time2!=''){
            if($export) {
                $map['list.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }else{
                $map['list.insert_time'] = array('between', [strtotime($search_time1 . " 00:00:00"), strtotime($search_time2 . " 23:59:59")]);
            }
        }
        $map['list.order_type']=array('eq',2);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuanList')->alias('list')->join('tuan_info info','list.tuan_id=info.id')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $user = new PintuanOrderModel();
        $lists = $user->getTuanByWhere($map, $Nowpage, $limits);
        if($export){
            //$map['list.status']=array('eq',2);
            $exportLists =$user->getTuanByWhere($map, $Nowpage, 1000000);
        }
        foreach ($lists as $k=>$v){
            //$lists[$k]['timeDiff']=timeDiff(time(),$v['end_time']);
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);

            $lists[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['storeid'])->value('d.st_department');
            if($v['success_time']==''){
                $lists[$k]['success_time']='';
            }else{
                $lists[$k]['success_time']=date('Y-m-d H:i:s',$v['success_time']);
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($exportLists as $k => $v) {
                if($v['status']==1) {
                    $statusText = "未付款";
                }elseif ($v['status']==2){
                    $statusText = "已付款";
                }else{
                    $statusText = "已失效";
                }
                $sonList=$user->orderList1($v['order_sn']);
                $data[$k]['realname']=$v['realname'];
                $data[$k]['seller']=Db::table('ims_bj_shopn_member')->where('id',$v['staffid'])->value('realname');
                $data[$k]['sign']=$v['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['storeid'])->value('d.st_department');
                $data[$k]['order_sn']="\t".$v['order_sn']."\t";
                $data[$k]['p_name']=$v['p_name'];
                $data[$k]['p_price']=$v['tuan_price'];
                $data[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
                $data[$k]['success_time']=date('Y-m-d H:i:s',$v['success_time']);
                $data[$k]['status']=$statusText;
                $data[$k]['fqr_pay']=$sonList[0]['pay_price'];
                $data[$k]['fqr_tel']=$sonList[0]['mobile'];
            }

            $filename = "单独购买订单列表".date('YmdHis');
            $header = array ('订单购买人','所属美容师','所属门店编码','所属门店名称','所属办事处','订单单号','购买商品','商品总价','下单时间','支付时间','拼购状态','支付金额','手机号码');
            $widths=array('15','20','20','30','30','30','60','10','20','20','20','20','20');
            if($data) {
                if($export==1){
                    excelExport($filename, $header, $data, $widths);//生成数据
                }else{
                    export_data($data,$header,$filename);
                }
            }
            //csv导出修改excel导出
            //$header = array ('拼团完成时间','门店编码','门店名称','所属办事处','开户行名称','开户人','银行卡号','是否对账','返款金额');
            //if($data){
            //export_data($data,$xlsCell,'拼购订单列表');
            //}
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('store_id', $store_id);
        $this->assign('id_department', $id_department);
        $this->assign('status', $status);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        $this->assign('pid',$pid);
        $this->assign('count',$count);
        $branch=new BranchModel();
        $branchList=$branch->getAllBranch();
        $this->assign('branchList',$branchList);
        $goods=new GoodsModel();
        $goodsList=$goods->getAllByWhere(['goods_cate'=>3,'status'=>1]);
        $this->assign('goodsList',$goodsList);
        $bsc=Db::table('sys_department')->field('id_department,st_department')->select();
        $this->assign('bsc',$bsc);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [progress 拼购订单详情]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function progress(){
        $pt=new PintuanOrderModel();
        $id = input('param.id');
        $info=$pt->getOnePy($id);
        $orderList=$pt->orderList($info['order_sn']);
        $total=0;
        foreach ($orderList as $k=>$v){
            $orderList[$k]['refund_err']=Db::name('pay_log')->where(['order_sn'=>$v['order_sn'],'status'=>1])->value('refund_err');
            $total+=$v['pay_price'];
        }
        $info['total']=$total;
        $info['list']=$orderList;
        $this->assign('info',$info);
        return $this->fetch();
    }


    /**
     * [progress 单独购买订单详情]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function progress1(){
        $pt=new PintuanOrderModel();
        $id = input('param.id');
        $info=$pt->getOnePy($id);
        $orderList=$pt->orderList($info['order_sn']);
        $total=0;
        foreach ($orderList as $k=>$v){
            $orderList[$k]['refund_err']=Db::name('pay_log')->where(['order_sn'=>$v['order_sn'],'status'=>1])->value('refund_err');
            $total+=$v['pay_price'];
        }
        $info['total']=$total;
        $info['list']=$orderList;
        $this->assign('info',$info);
        return $this->fetch();
    }

    //退款
    public function refund(){
        $orderSn=input('param.ordersn');
        $map['order.parent_order']=array('eq',$orderSn);
        $map['order.pay_status']=array('eq',1);
        $map['order.order_status']=array('eq',2);
        $map['order.pay_price']=array('gt',0);
        $map['log.status']=1;
        $orderList=Db::name('tuan_order')->alias('order')->field('order.order_sn,order.uid,order.transaction_id,order.parent_order,order.pay_by_self,log.pay_amount,log.out_trade_no,log.attach')->join('pay_log log','order.transaction_id=log.transaction_id','left')->where($map)->select();
		if(is_array($orderList) && count($orderList)){
            $successNum=0;
            $successRes=[];
            $errNum=0;
            $errRes=[];
            foreach ($orderList as $k=>$v){
                if($v['pay_amount']){
                $refundData = [
                    'appid' => config('wx_pay.appid'), //应用id
                    'mchid' => config('wx_pay.mch_id'), //商户号id
                    'api_key' => config('wx_pay.api_key'), //支付key
                    'transaction_id'    => $v['transaction_id'], //微信交易号
                    'out_refund_no'    => date('YmdHis').time().rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9), //退款单号
                    'total_fee'    =>   floatval($v['pay_amount']*100), //原订单金额
                    'refund_fee'    => floatval($v['pay_amount']*100), //退款金额
                    'refund_text' => '拼购未成团退款' //退款描述
                ];
                $refund=new WeixinRefund($refundData);
                $res = $refund->orderRefund();
                $resArr=$refund->XmlToArr($res);
                    if($resArr['return_code'] == "SUCCESS") {
                        if($resArr['result_code'] == "SUCCESS"){
                            $successNum++;
                            $successRes[]=$resArr;
                            //退款成功 修改日志表记录
                            $data1 = array('refund_amont' => $resArr['refund_fee']/100, 'status'=>2, 'refund_time' => date('Y-m-d H:i:s'), 'refund_id' => $resArr['refund_id'],'refund_err'=>'');
                            Db::name('pay_log')->where('transaction_id', $v['transaction_id'])->update($data1);
                            //退款成功 修改订单表记录
                            Db::name('tuan_order')->where('transaction_id', $v['transaction_id'])->update(['order_status'=>4,'pay_status'=>2,'return_time'=>time(),'return_sms_flag'=>1]);
                            //退款成功 记录日志
                            logs(date('Y-m-d H:i:s')."：".json_encode($resArr),'refundOk');
                        }else{
                            $errNum++;
                            $errRes[]=$resArr;
                            //退款失败 修改表记录
                            $data1 = array('refund_time' => date('Y-m-d H:i:s'), 'refund_err' => $resArr['err_code_des']);
                            Db::name('pay_log')->where('transaction_id', $v['transaction_id'])->update($data1);
                            //退款失败 记录日志
                            logs(date('Y-m-d H:i:s')."：".json_encode($resArr),'refundFail');
                        }
                    }else{
                        //退款失败 记录日志
                        logs(date('Y-m-d H:i:s')."：".json_encode($resArr),'refundFail');
                    }
                }
            }
            if(count($orderList)==$successNum){
                //退款成功 修改主订单记录
                Db::name('tuan_list')->where('order_sn', $orderSn)->update(['status'=>4]);
                return json(['code' => 1, 'data' => '', 'msg' => '退款成功']);
            }else{
                return json(['code' => 0, 'data' => '', 'msg' => '退款失败']);
            }
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => '主订单下暂无退款子订单']);
        }
    }
}
