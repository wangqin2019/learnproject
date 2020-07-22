<?php

namespace app\admin\controller;
use app\admin\model\BranchModel;
use app\admin\model\GoodsModel;
use app\admin\model\PintuanModel;
use app\admin\model\PintuanOrderModel;
use think\Db;

class Money extends Base{

    /**
     * [index 待返款列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function index(){
        $store_id = input('store_id');
        $export = input('export',0);
        $map = [];
        if($store_id && $store_id!==""){
            $map['list.storeid'] = array('eq',$store_id);
        }
        $map['list.back_money']=array('eq',0);
        $map['list.status']=array('eq',2);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuan_list')->alias('list')->where($map)->group('list.storeid')->select();//计算总页面 不知道为什么group 和count为啥不能同时使用了 暂用select
        $count=count($count);
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('ims_bwk_branch')->alias('b')->join('tuan_list list','b.id=list.storeid')->field('b.id,b.title,b.sign,count(list.id) count,sum(list.tuan_price) total')->where($map)->page($Nowpage, $limits)->group('list.storeid')->select();//计算总页面
        if($export){
            $exportLists = Db::table('ims_bwk_branch')->alias('b')->join('tuan_list list','b.id=list.storeid')->field('b.id,b.title,b.sign,count(list.id) count,sum(list.tuan_price) total')->where($map)->page($Nowpage, 1000000)->group('list.storeid')->select();//计算总页面
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['total']=sprintf("%1.2f",$v['total']);
            $lists[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['id'])->value('d.st_department');
            if(strlen($v['sign'])>=7){
                $mainSign=substr($v['sign'],0,7);
            }else{
                $mainSign=$v['sign'];
            }
            $bankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->count();
            $lists[$k]['bank_info']=$bankInfo?"<span class='label label-primary'>已维护</span>":"<span class='label label-default'>未维护</span>";

        }
        //导出
        if($export){
            $data=array();
            foreach ($exportLists as $k => $v) {
                if(strlen($v['sign'])>=7){
                    $mainSign=substr($v['sign'],0,7);
                }else{
                    $mainSign=$v['sign'];
                }
                $data[$k]['bsc']=Db::table('sys_departbeauty_relation')->alias('r')->join(['sys_department' => 'd'],'r.id_department=d.id_department','left')->where('r.id_beauty',$v['id'])->value('d.st_department');
                $data[$k]['sign']=$mainSign;
                $data[$k]['title']=$v['title'];
                $bankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->count();
                $data[$k]['bank_info']=$bankInfo?"已维护":"未维护";
                $data[$k]['count']=$v['count'];
                $data[$k]['total']=sprintf("%1.2f",$v['total']);
            }
            $filename = "待返款门店列表".date('YmdHis');
            $header = array ('所属办事处','门店编码','门店名称','打款信息','待返单数','待返金额');
            $widths=array('20','20','20','30','30','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('store_id', $store_id);
        $branchList = Db::table('ims_bwk_branch')->alias('b')->join('tuan_list list','b.id=list.storeid')->field('b.id,b.title,b.sign')->where($map)->group('list.storeid')->select();//计算总页面
        $this->assign('branchList',$branchList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }



    /**
     * [order_list 待返款详情]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function order_list(){
        $key = input('key');
        $storeid = input('storeid');
        $pid = input('pid');
        $map = [];
        if($key&&$key!==""){
            $map['info.p_name'] = ['like',"%" . $key . "%"];
        }
        $map['list.storeid'] = ['eq',$storeid];

        if($pid && $pid!==""){
            $map['list.pid'] = ['eq',$pid];
        }
        $map['list.status']=array('eq',2);
        $map['list.back_money']=array('eq',0);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuanList')->alias('list')->join('tuan_info info','list.tuan_id=info.id')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pt=new PintuanOrderModel();
        $lists = $pt->getTuanByWhere($map, $Nowpage, $limits);
        foreach ($lists as $k=>$v){
            if($v['order_type']==2){
                $lists[$k]['order_type']='单独购';
            }else{
                $lists[$k]['order_type']='分享购';
            }

            $con='';
            $lists[$k]['process_time']=dataFormat($v['process_time']);
            $lists[$k]['success_time']=date('Y-m-d',$v['success_time']);
            $info=$pt->getOnePy($v['id']);
            $orderList=$pt->orderList1($info['order_sn']);
            $total=0;
            $ischeck=0;
            foreach ($orderList as $kk=>$vv){
                $ischeck+=$vv['pay_check'];
                $hd=$vv['pay_check']?"<span class='label label-primary'>已核对</span>":"<span class='label label-default'>未核对</span>";
                $cd=$vv['pay_by_self']?'发起人凑单':'';
                $con.="<tr class='long-td'>";
                $con.="<td>".$vv['order_sn']."</td>";
                $con.="<td>".$vv['realname']."</td>";
                $con.="<td>".$vv['mobile']."</td>";
                $con.="<td>".$vv['pay_price']."</td>";
                $con.="<td>".$hd."</td>";
                $con.="<td>".date('Y-m-d H:i:s',$vv['pay_time'])."</td>";
                $con.="<td>".$cd."</td>";
                $con.='</tr>';
                $total+=$vv['pay_price'];
            }
            if($ischeck==count($orderList)){
                $lists[$k]['check']=1;
            }else{
                $lists[$k]['check']=0;
            }
            $lists[$k]['son_list']=$con;
            $lists[$k]['money_total']=$total;
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('pid',$pid);
        $this->assign('storeid',$storeid);
        $this->assign('count',$count);
        $goods=new GoodsModel();
        $goodsList=$goods->getAllByWhere(['goods_cate'=>3,'status'=>1]);
        $this->assign('goodsList',$goodsList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    //销售申请财务返款
    public function apply_refund(){
        $refund=input('param.refund');
        try{
            $result =  Db::name('tuan_list')->where('id','in',$refund)->update(['back_money' => 1,'back_money_time1'=>time()]);
            if(false === $result){
                return json(['code' => 0, 'data' => '', 'msg' =>'申请返款失败']);
            }else{
                return json(['code' => 1, 'data' =>'', 'msg' => '申请返款成功']);
            }
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }



    /**
     * [index 待财务确认列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function finance(){
        $store_id = input('store_id');
        $channel = input('channel','pingou');
        $map = [];
        if($store_id && $store_id!==""){
            $map['list.storeid'] = ['eq',$store_id];
        }
        $map['list.back_money']=array('eq',1);
        $map['list.status']=array('eq',2);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $count = Db::name('tuan_list')->alias('list')->where($map)->group('list.storeid')->select();//计算总页面 不知道为什么group 和count为啥不能同时使用了 暂用select
        $count=count($count);
        $allpage = intval(ceil($count / $limits));
        $lists = Db::table('ims_bwk_branch')->alias('b')->join('tuan_list list','b.id=list.storeid')->field('b.id,b.title,b.sign,count(list.id) count,sum(list.tuan_price) total')->where($map)->page($Nowpage, $limits)->group('list.storeid')->select();//计算总页面
        foreach ($lists as $k=>$v){
            $lists[$k]['total']=sprintf("%1.2f",$v['total']);
            $getSign=Db::table('ims_bwk_branch')->where('id',$v['id'])->value('sign');
            if($getSign){
                if(strlen($getSign)>=7){
                    $mainSign=substr($getSign,0,7);
                }else{
                    $mainSign=$getSign;
                }
            }
            $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
            if($getBankInfo){
                $BankInfo='客户简称：'.$getBankInfo['b_name'].'　银行开户人：'.$getBankInfo['payee'].' 　开户银行：'.$getBankInfo['bankname'].' 　银行号码：'.$getBankInfo['bankcard'];
            }else{
                $BankInfo='暂无维护打款银行卡信息';
            }
            $lists[$k]['bankInfo']=$BankInfo;
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('store_id', $store_id);
        $this->assign('channel', $channel);
        $this->assign('start', date("Y-m-d", strtotime("-1 day")));
        $this->assign('end', date("Y-m-d", strtotime("-1 day")));
        $branchList = Db::table('ims_bwk_branch')->alias('b')->join('tuan_list list','b.id=list.storeid')->field('b.id,b.title,b.sign')->where($map)->group('list.storeid')->select();//计算总页面
        $this->assign('branchList',$branchList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    /**
     * [progress 财务确认返款详情]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function finance_order_list(){
        $key = input('key');
        $storeid = input('storeid');
        $pid = input('pid');
        $map = [];
        if($key&&$key!==""){
            $map['info.p_name'] = ['like',"%" . $key . "%"];
        }
        $map['list.storeid'] = ['eq',$storeid];

        if($pid && $pid!==""){
            $map['list.pid'] = ['eq',$pid];
        }
        $map['list.status']=array('eq',2);
        $map['list.back_money']=array('eq',1);
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuanList')->alias('list')->join('tuan_info info','list.tuan_id=info.id')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pt=new PintuanOrderModel();;
        $lists = $pt->getTuanByWhere($map, $Nowpage, $limits);
        $money_total = $pt->getTuanPriceByWhere($map);
        //获取门店编码
        $getSign=Db::table('ims_bwk_branch')->where('id',$storeid)->value('sign');
        if($getSign){
            if(strlen($getSign)>=7){
                $mainSign=substr($getSign,0,7);
            }else{
                $mainSign=$getSign;
            }
        }
        $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
        if($getBankInfo){
            $BankInfo='客户简称：'.$getBankInfo['b_name'].'　银行开户人：'.$getBankInfo['payee'].' 　开户银行：'.$getBankInfo['bankname'].' 　银行号码：'.$getBankInfo['bankcard'];
        }else{
            $BankInfo='暂无维护打款银行卡信息';
        }

        foreach ($lists as $k=>$v){
            if($v['order_type']==2){
                $lists[$k]['order_type']='单独购';
            }else{
                $lists[$k]['order_type']='分享购';
            }
            $con='';
            $lists[$k]['process_time']=dataFormat($v['process_time']);
            $lists[$k]['success_time']=date('Y-m-d',$v['success_time']);
            $lists[$k]['bankInfo']=$BankInfo;
            $info=$pt->getOnePy($v['id']);
            $orderList=$pt->orderList1($info['order_sn']);
            $total=0;
            $ischeck=0;
            foreach ($orderList as $kk=>$vv){
                $ischeck+=$vv['pay_check'];
                $hd=$vv['pay_check']?"<span class='label label-primary'>已核对</span>":"<span class='label label-default'>未核对</span>";
                $cd=$vv['pay_by_self']?'发起人凑单':'';
                $con.="<tr class='long-td'>";
                $con.="<td>".$vv['order_sn']."</td>";
                $con.="<td>".$vv['realname']."</td>";
                $con.="<td>".$vv['mobile']."</td>";
                $con.="<td>".$vv['pay_price']."</td>";
                $con.="<td>".$hd."</td>";
                $con.="<td>".date('Y-m-d H:i:s',$vv['pay_time'])."</td>";
                $con.="<td>".$cd."</td>";
                $con.='</tr>';
                $total+=$vv['pay_price'];
            }
            if($ischeck==count($orderList)){
                $lists[$k]['check']=1;
            }else{
                $lists[$k]['check']=0;
            }
            $lists[$k]['son_list']=$con;
            $lists[$k]['money_total']=$total;
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('pid',$pid);
        $this->assign('storeid',$storeid);
        $this->assign('count',$count);
        $this->assign('money_total',$money_total);
        $goods=new GoodsModel();
        $goodsList=$goods->getAllByWhere(['goods_cate'=>3,'status'=>1]);
        $this->assign('goodsList',$goodsList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    //财务确认返款操作
    public function finance_confrim(){
        $refund=input('param.refund');
        try{
            $result =  Db::name('tuan_list')->where('id','in',$refund)->update(['back_money' => 2,'back_money_time2'=>time()]);
            if(false === $result){
                return json(['code' => 0, 'data' => '', 'msg' =>'返款确认失败']);
            }else{
                return json(['code' => 1, 'data' =>'', 'msg' => '返款确认成功']);
            }
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }


    //财务返款
    public function finance_download(){
        $ids=input('param.ids');
        $storeid=input('param.storeid');
        try{
            $data=array();
            $map['list.id']=array('in',$ids);
            $pt=new PintuanOrderModel();;
            $lists = $pt->getTuanByWhere($map, 1, 10000000);

            //获取门店编码
            $getSign=Db::table('ims_bwk_branch')->where('id',$storeid)->value('sign');
            if($getSign){
                if(strlen($getSign)>=7){
                    $mainSign=substr($getSign,0,7);
                }else{
                    $mainSign=$getSign;
                }
            }
            $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
            foreach ($lists as $k=>$v){
                $info=$pt->getOnePy($v['id']);
                $data[$k]['succes_time']=date('Y-m-d H:i:s',$v['success_time']);
                $data[$k]['sign']=$v['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['department']=$getBankInfo['department'];
                $data[$k]['bankname']=$getBankInfo['bankname'];
                $data[$k]['payee']=$getBankInfo['payee'];
                $data[$k]['bankcard']="\t".$getBankInfo['bankcard']."\t";
                $orderList=$pt->orderList1($info['order_sn']);
                $ischeck=0;
                $total=0;
                foreach ($orderList as $kk=>$vv){
                    $ischeck+=$vv['pay_check'];
                    $total+=$vv['pay_price'];
                }
                if($ischeck==count($orderList)){
                    $data[$k]['check']='对账无误';
                }else{
                    $data[$k]['check']='对账未完成';
                }
                $data[$k]['money_total']=$total;
            }

            $filename = "拼购财务线下返款列表".date('YmdHis');
            $header = array ('订单完成时间','门店编码','门店名称','所属办事处','开户行名称','开户人','银行卡号','是否对账','返款金额');
            $widths=array('15','20','20','20','20','20','20','20');
            excelExport($filename,$header,$data,$widths);//生成数据
            return json(['code' => 1, 'data' => '', 'msg' =>'11']);
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }

//财务返款
    public function finance_download_by_branch(){
        $ids=input('param.ids');
        try{
            $data=array();
            $map['list.storeid']=array('in',$ids);
            $map['list.back_money']=array('eq',1);
            $lists =Db::name('tuan_list')->alias('list')->where($map)->field("list.storeid,order_sn,branch.title,branch.sign,count(list.id) count,sum(list.tuan_price) total_price")->join(['ims_bwk_branch' => 'branch'],'list.storeid=branch.id','left')->group('list.storeid')->select();
            foreach ($lists as $k=>$v){
                Db::name('tuan_list')->where(['storeid'=>$v['storeid'],'back_money'=>1])->update(['back_money' => 2,'back_money_time2'=>time()]);
                //获取门店编码
                $getSign=Db::table('ims_bwk_branch')->where('id',$v['storeid'])->value('sign');
                if($getSign){
                    if(strlen($getSign)>=7){
                        $mainSign=substr($getSign,0,7);
                    }else{
                        $mainSign=$getSign;
                    }
                }
                $getBankInfo=Db::name('bankcard')->where('b_sign',$mainSign)->find();
                $data[$k]['sign']=$v['sign'];
                $data[$k]['title']=$v['title'];
                $data[$k]['department']=$getBankInfo['department'];
                $data[$k]['bankname']=$getBankInfo['bankname'];
                $data[$k]['payee']=$getBankInfo['payee'];
                $data[$k]['bankcard']="\t".$getBankInfo['bankcard']."\t";
                $data[$k]['count']=$v['count'];
                $data[$k]['total']=$v['total_price'];
            }

            $filename = "拼购财务线下返款列表".date('YmdHis');
            $header = array ('门店编码','门店名称','所属办事处','开户行名称','开户人','银行卡号','返款单数','返款金额');
            $widths=array('15','20','20','20','20','20','20','20');
            excelExport($filename,$header,$data,$widths);//生成数据
            return json(['code' => 1, 'data' => '', 'msg' =>'11']);
        }catch( \Exception $e){
            return json(['code' => 0, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }





    /**
     * [index 订单列表]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function progress(){
        $key = input('key');
        $store_id = input('store_id');
        $back_money = input('back_money',888);
        $export = input('export',0);
        $pid = input('pid');
        $search_time1 = input("param.start");
        $search_time2 = input("param.end");
        $map = [];
        if($key&&$key!==""){
            $map['info.p_name|list.order_sn'] = ['like',"%" . $key . "%"];
        }
        if($store_id && $store_id!==""){
            $map['list.storeid'] = ['eq',$store_id];
        }
        if($back_money!="888"){
            $map['list.back_money'] = ['eq',$back_money];
        }
        if($pid && $pid!==""){
            $map['list.pid'] = ['eq',$pid];
        }
        $map['list.status']=array('eq',2);
        if($search_time1!='' && $search_time2!=''){
            $map['list.success_time'] = array('between', [strtotime($search_time1." 00:00:00"), strtotime($search_time2." 23:59:59")]);
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('tuanList')->alias('list')->join('tuan_info info','list.tuan_id=info.id')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $user = new PintuanOrderModel();
        $lists = $user->getTuanByWhere($map, $Nowpage, $limits);
        if($export){
            $exportLists =$user->getTuanByWhere($map, $Nowpage, 1000000);
        }
        foreach ($lists as $k=>$v){
            $lists[$k]['success_time']=date('Y-m-d H:i:s',$v['success_time']);
        }
        //导出
        if($export){
            $data=array();
            foreach ($exportLists as $k => $v) {
                if($v['back_money']==1) {
                    $statusText = "销售已申请";
                }elseif ($v['back_money']==2){
                    $statusText = "财务已处理";
                }else{
                    $statusText = "等待销售审核";
                }
                $data[$k]['realname']=$v['realname'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['order_sn']="\t".$v['order_sn']."\t";
                $data[$k]['p_name']=$v['tuan_name'];
                $data[$k]['p_price']=$v['tuan_price'];
                $data[$k]['success_time']=date('Y-m-d H:i:s',$v['success_time']);
                $data[$k]['status']=$statusText;
            }
            $filename = "返款进度列表".date('YmdHis');
            $header = array ('活动发起人','所属门店','门店编码','拼购单号','拼购产品','产品价格	','成团时间','返款进度');
            $widths=array('30','30','30','30','30','30','30','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('store_id', $store_id);
        $this->assign('start',$search_time1);
        $this->assign('end',$search_time2);
        $this->assign('pid',$pid);
        $this->assign('back_money',$back_money);
        $this->assign('count',$count);
        $branch=new BranchModel();
        $branchList=$branch->getAllBranch();
        $this->assign('branchList',$branchList);

        $goods=new GoodsModel();
        $goodsList=$goods->getAllByWhere(['goods_cate'=>3,'status'=>1]);
        $this->assign('goodsList',$goodsList);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    //导出日期区间的微信入账/出账
    function auto_download_receipts(){
        set_time_limit(0);
        ini_set ('memory_limit', '1280M');
        $number = input('param.number');
        $channel = input('param.channel');
        $start = input('param.start',date("Y-m-d", strtotime("-1 day")));
        $end = input('param.end',date("Y-m-d"));

        if($number==1){
            $map['log.upd_time'] = array('between', [$start." 00:00:00", $end." 23:59:59"]);
            $map['log.status']=array('eq',1);
        }elseif($number==2){
            $map['log.upd_time'] = array('between', [$start." 00:00:00", $end." 23:59:59"]);
            $map['log.status']=array('eq',2);
        }
        if($channel=='pingou'){
            $map['log.attach']=array('not in',['activity','missshop','bargain']);
        }else{
            $map['log.attach']=array('eq',$channel);
        }
            $data=array();
            if($number==1){
                $type='微信收入';
            }else{
                $type='微信支出';
            }
            if($channel=='pingou') {
                $list=Db::name('pay_log')->alias('log')->field('list.status,list.order_sn parent_order,order.order_sn,log.transaction_id,order.pay_time,branch.sign,branch.title,log.pay_amount,info.p_name,log.refund_time,log.refund_id,log.refund_amont')->join('tuan_order order','log.order_sn=order.order_sn','left')->join('tuan_list list','order.parent_order=list.order_sn','left')->join(['ims_bwk_branch' => 'branch'],'list.storeid=branch.id','left')->join('tuan_info info','list.tuan_id=info.id','left')->where($map)->order('order.pay_time')->select();
                    if(is_array($list) && count($list)){
                    $pt = new \app\api\model\PintuanModel();
                    foreach ($list as $k => $v) {
                        $data[$k]['type'] = $type;
                        $data[$k]['status'] = $pt->getOrderStatus($v['status']);
                        $data[$k]['parent_order'] = "\t" . $v['parent_order'] . "\t";
                        $data[$k]['order_sn'] = "\t" . $v['order_sn'] . "\t";
                        $data[$k]['transaction_id'] = "\t" . $v['transaction_id'] . "\t";
                        $data[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                        $data[$k]['sign'] = $v['sign'];
                        $data[$k]['title'] = $v['title'];
                        $data[$k]['p_name'] = $v['p_name'];
                        $data[$k]['pay_amount'] = $v['pay_amount'];
                        if ($number == 2) {
                            $data[$k]['refund_time'] = $v['refund_time'];
                            $data[$k]['refund_id'] = "\t" . $v['refund_id'] . "\t";
                            $data[$k]['refund_amont'] = $v['refund_amont'];
                        }
                    }
                    $filename = $start . '至' . $end . $type . "记录";
                    if ($number == 1) {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '微信支付单号', '支付时间', '门店编号', '门店名称', '拼购产品', '交易金额');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20');
                    } else {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '原微信支付单号', '原支付时间', '门店编号', '门店名称', '拼购产品', '原支付金额', '退款时间', '退款流水', '退款金额');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    }
                    excelExport($filename, $header, $data, $widths);//生成数据
                }else{
                    echo "暂无记录 <a href='javascript:history.back();' style='color: #000'>返回</a>";
                }
            }elseif ($channel=='bargain'){
                $list=Db::name('pay_log')->alias('log')->field('order.order_sn,log.transaction_id,order.pay_time,branch.sign,branch.title,log.pay_amount,info.name,log.refund_time,log.refund_id,log.refund_amont,log.status,order.num,info.activity_price,order.scene')->join('bargain_order order','log.order_sn=order.order_sn','left')->join('goods info','order.goods_id=info.id','left')->join(['ims_bwk_branch' => 'branch'],'order.storeid=branch.id','left')->where($map)->order('order.pay_time')->select();
                if(is_array($list) && count($list)){
                    foreach ($list as $k => $v) {
                        if($v['status']==1){
                            $status='已付款';
                        }elseif ($v['status']==2){
                            $status='已退款';
                        }else{
                            $status='未支付';
                        }
                        $data[$k]['type'] = $type;
                        $data[$k]['status'] = $status;
                        $data[$k]['parent_order'] = "`".$v['order_sn'];
                        $data[$k]['order_sn'] = "";
                        $data[$k]['transaction_id'] = "`".$v['transaction_id'];
                        $data[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                        $data[$k]['sign'] = $v['sign'];
                        $data[$k]['title'] = $v['title'];
                        $data[$k]['p_name'] = $v['name'];
                        $data[$k]['activity_price'] = $v['activity_price'];
                        $data[$k]['num'] = $v['num'];
                        $data[$k]['pay_amount'] = $v['pay_amount'];
                        $data[$k]['scene'] = '拼人品订单';
                        if ($number == 2) {
                            $data[$k]['scene'] = '';
                            $data[$k]['refund_time'] = $v['refund_time'];
                            $data[$k]['refund_id'] = "`".$v['refund_id'];
                            $data[$k]['refund_amont'] = $v['refund_amont'];
                        }
                    }
                    $filename = $start . '至' . $end . $type . "记录";
                    if ($number == 1) {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '微信支付单号', '支付时间', '门店编号', '门店名称', '产品名称', '产品单价', '购买数量', '支付金额', '订单类型');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    } else {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '原微信支付单号', '原支付时间', '门店编号', '门店名称', '产品名称', '产品单价', '原购买数量','原支付金额', '订单类型', '退款时间', '退款流水', '退款金额');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    }
                    excelExport($filename, $header, $data, $widths);//生成数据
                }else{
                    echo "暂无记录 <a href='javascript:history.back();' style='color: #000'>返回</a>";
                }
            }elseif ($channel=='blink'){
                $list=Db::name('pay_log')
                    ->alias('log')
                    ->field('order.order_sn,log.transaction_id,order.pay_time,branch.sign,branch.title,log.pay_amount,info.name,log.refund_time,log.refund_id,log.refund_amont,log.status,order.num,info.activity_price')
                    ->join('blink_order order','log.order_sn=order.order_sn','left')
                    ->join('goods info','order.goods_id=info.id','left')
                    ->join(['ims_bwk_branch' => 'branch'],'order.storeid=branch.id','left')
                    ->where($map)
                    ->order('order.pay_time')
                    ->select();
                if(is_array($list) && count($list)){
                    foreach ($list as $k => $v) {
                        if($v['status']==1){
                            $status='已付款';
                        }elseif ($v['status']==2){
                            $status='已退款';
                        }else{
                            $status='未支付';
                        }
                        $data[$k]['type'] = $type;
                        $data[$k]['status'] = $status;
                        $data[$k]['parent_order'] = "`".$v['order_sn'];
                        $data[$k]['order_sn'] = "";
                        $data[$k]['transaction_id'] = "`".$v['transaction_id'];
                        $data[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                        $data[$k]['sign'] = $v['sign'];
                        $data[$k]['title'] = $v['title'];
                        $data[$k]['p_name'] = $v['name'];
                        $data[$k]['activity_price'] = $v['activity_price'];
                        $data[$k]['num'] = $v['num'];
                        $data[$k]['pay_amount'] = $v['pay_amount'];
                        $data[$k]['scene'] = '盲盒订单';
                        if ($number == 2) {
                            $data[$k]['scene'] = '';
                            $data[$k]['refund_time'] = $v['refund_time'];
                            $data[$k]['refund_id'] = "`".$v['refund_id'];
                            $data[$k]['refund_amont'] = $v['refund_amont'];
                        }
                    }
                    $filename = $start . '至' . $end . $type . "记录";
                    if ($number == 1) {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '微信支付单号', '支付时间', '门店编号', '门店名称', '产品名称', '产品单价', '购买数量', '支付金额', '订单类型');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    } else {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '原微信支付单号', '原支付时间', '门店编号', '门店名称', '产品名称', '产品单价', '原购买数量','原支付金额', '订单类型', '退款时间', '退款流水', '退款金额');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    }
                    excelExport($filename, $header, $data, $widths);//生成数据
                }else{
                    echo "暂无记录 <a href='javascript:history.back();' style='color: #000'>返回</a>";
                }
            }else{
                $list=Db::name('pay_log')->alias('log')->field('order.order_sn,log.transaction_id,order.pay_time,branch.sign,branch.title,log.pay_amount,info.name,log.refund_time,log.refund_id,log.refund_amont,log.status,order.num,info.activity_price,order.flag,order.scene')->join('activity_order order','log.order_sn=order.order_sn','left')->join('goods info','order.pid=info.id','left')->join(['ims_bwk_branch' => 'branch'],'order.storeid=branch.id','left')->where($map)->order('order.pay_time')->select();
                if(is_array($list) && count($list)){
                    foreach ($list as $k => $v) {
                        if($v['status']==1){
                            $status='已付款';
                        }elseif ($v['status']==2){
                            $status='已退款';
                        }else{
                            $status='未支付';
                        }
                        $data[$k]['type'] = $type;
                        $data[$k]['status'] = $status;
                        $data[$k]['parent_order'] = "`".$v['order_sn'];
                        $data[$k]['order_sn'] = "";
                        $data[$k]['transaction_id'] = "`".$v['transaction_id'];
                        $data[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
                        $data[$k]['sign'] = $v['sign'];
                        $data[$k]['title'] = $v['title'];
                        if($v['flag']){
                            $goods_name=[];
                            $goods_num=0;
                            $goods_amount=0;
                            $orderInfo=Db::name('activity_order_info')->alias('info')->join('goods g','info.good_id=g.id','left')->where(['info.order_sn'=>$v['order_sn'],'flag'=>0])->field('g.name,info.good_specs,info.good_num,info.good_amount')->select();
                            foreach ($orderInfo as $kk=>$vv){
                                $goods_name[]=($kk+1).'.'.$vv['name'].$vv['good_specs'].' ×'.$vv['good_num'];
                                $goods_num+=$vv['good_num'];
                                $goods_amount+=$vv['good_amount'];
                            }
                            $data[$k]['p_name']=implode("\r\n",$goods_name);
                            $data[$k]['activity_price']=$v['pay_amount'];
                            $data[$k]['num']=$goods_num;
                            $data[$k]['pay_amount']=$v['pay_amount'];
                        }else{
                            $data[$k]['p_name'] = $v['name'];
                            $data[$k]['activity_price'] = $v['activity_price'];
                            $data[$k]['num'] = $v['num'];
                            $data[$k]['pay_amount'] = $v['pay_amount'];
                        }
                        if($v['scene']==3){
                            $scene='八大裂变订单';
                        }elseif ($v['scene']==2){
                            $scene='双十一订单';
                        }elseif ($v['scene']==4){
                            $scene='宏伟定制';
                        }elseif ($v['scene']==5){
                            $scene='春节88福袋';
                        }elseif ($v['scene']==6){
                            $scene='抗疫法宝';
                        }elseif ($v['scene']==7){
                            $scene='约惠春天订单';
                        }else{
                            $scene='拓客转客订单';
                        }
                        $data[$k]['scene'] = $scene;


                        if ($number == 2) {
                            $data[$k]['scene'] = '';
                            $data[$k]['refund_time'] = $v['refund_time'];
                            $data[$k]['refund_id'] = "`".$v['refund_id'];
                            $data[$k]['refund_amont'] = $v['refund_amont'];
                        }
                    }
                    $filename = $start . '至' . $end . $type . "记录";
                    if ($number == 1) {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '微信支付单号', '支付时间', '门店编号', '门店名称', '产品名称', '产品单价', '购买数量', '支付金额', '订单类型');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    } else {
                        $header = array('支付类型', '订单状态', '主订单号', '子订单号', '原微信支付单号', '原支付时间', '门店编号', '门店名称', '产品名称', '产品单价', '原购买数量','原支付金额', '订单类型', '退款时间', '退款流水', '退款金额');
                        $widths = array('15', '15', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20', '20');
                    }

                    excelExport($filename, $header, $data, $widths);//生成数据
                }else{
                    echo "暂无记录 <a href='javascript:history.back();' style='color: #000'>返回</a>";
                }
            }
        die();
    }

    //订单日报表 订单月报表
    public function auto_download_report(){
        $number=input('param.number');
        if($number==1){//拼购订单日报表
            $date = input('param.date',date("Y-m-d", strtotime("-1 day")));
            $map['success_time'] = array('between', [strtotime($date." 00:00:00"), strtotime($date." 23:59:59")]);
            $map['status']=array('eq',2);
            $list = Db::name('tuan_list')->where($map)->select();
            $goods=[];
            $goods[30]=0;//口服胶原
            $goods[31]=0;//胎盘
            $goods[32]=0;//无针胶原
            $goods[26]=0;//奢宠套盒
            $goods[27]=0;//经典套盒
            $goods[24]=0;//胎盘
            $goods[19]=0;//无针胶原
            $goods[23]=0;//口服胶原
            $goods[25]=0;//内衣
            $liukeNum=0;
            $data=array();
            try{
                if(count($list)){
                    foreach ($list as $k => $v) {
                        $pch=Db::name('GoodsStockInfo')->where('flgsbar',$v['code'])->value('batch_number');
                        if($v['cVouchType']=='销售出库单'){
                            $cVouchType=1;
                        }elseif($v['cVouchType']=='其他出库单'){
                            $cVouchType=2;
                        }else{
                            $cVouchType=0;
                        }
                        if(Cache::get($v['delivery_number'])){
                            $getcache=Cache::get($v['delivery_number']);
                            $orderinfo=json_decode($getcache,true);
                        }else{
                            $url="http://erpapi2.chengmei.com:7779/stock/index.php?orderno=".$v['delivery_number'].'&ordertype='.$cVouchType;
                            $listList=httpGet($url);
                            Cache::set($v['delivery_number'],$listList,3600);
                            $orderinfo=json_decode($listList,true);
                        }
                        $data[$k]['dnverifytime']=date('Y-m-d',$v['dnverifytime']);
                        $data[$k]['delivery_number']='\''.$v['delivery_number'];
                        $data[$k]['b_sign']='\''.$v['b_sign'];
                        $data[$k]['p_code']=$v['p_code'];
                        $data[$k]['p_name']=$v['p_name'];
                        $data[$k]['picihao']=$pch?$pch:'未录入';;
                        $data[$k]['picihao_jepan']='';
                        $data[$k]['number']=$v['count'];
                        $data[$k]['remark']='';
                        $data[$k]['into_status']='';
                        $data[$k]['depart']=$orderinfo['cDepName'];
                        $data[$k]['b_name']=$orderinfo['cCusName'];
                        $data[$k]['b_code']=$orderinfo['cLicenceNo'];
                        $data[$k]['b_address']=$orderinfo['chdefine8'];
                        $data[$k]['b_mam']=$orderinfo['chdefine6'];
                        $data[$k]['b_tel']=$orderinfo['chdefine7'];
                    }
                    $filename = "substitute_meal_report";
                    $header = array ('实际出库日期','出库单号','客户编码','存货编码','存货名称','批号','日本批号','数量','备注','录入情况','所属办事处','购货单位名称','购货单位统一社会信用代码','购货单位地址','购货单位联系人','购货单位联系电话');
                    $widths=array('15','20','15','15','20','10','10','10','10','10','15','50','30','80','10','15');
                    $path=ROOT_PATH.'/public/ExcelReport/'.$filename.'.xls';
                    $fname= iconv("UTF-8", "GB2312//IGNORE", @$path);
                    if(file_exists($fname)) {
                        AddExcelExport($filename,$data);//追加数据
                    }else{
                        excelExport($filename,$header,$data,$widths);//生成数据
                    };
                    }
            }catch (\Exception $e){
                echo $e->getMessage();
                die();
            }
        }else{//拼购订单月报表

        }
    }




}
