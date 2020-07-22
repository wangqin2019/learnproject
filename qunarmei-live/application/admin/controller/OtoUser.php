<?php

namespace app\admin\controller;
use think\Db;

class OtoUser extends Base
{
    protected $csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\csv\\';//上传文件路径
    //*********************************************广告列表*********************************************//
    /**
     * [index 账号列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['o.oto_user'] = ['like',"%" . $key . "%"];
        }
        $export = input('export',0);
        $page = input('page');
        $Nowpage = $page?$page:1;
        $limits = 20;// 获取总条数
        $count = Db::table('ims_bj_shopn_oto o')->join(['pt_ticket_user'=>'u'],['u.id=o.card_id'],'LEFT')->join(['ims_bj_shopn_member'=>'m'],['m.id=u.user_id'],'LEFT')->join(['ims_bj_shopn_order'=>'ord'],['ord.id=u.orderid'],'LEFT')->join(['ims_bj_shopn_goods'=>'gd'],['gd.id=u.goods_id'],'LEFT')->field('o.id,o.oto_user,o.oto_pwd,o.status,ord.ordersn,gd.title goods_name,m.realname user_name')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $page_limits = ($Nowpage-1)*$limits;
//        echo '<pre>';print_r($page_limits);print_r($limits);die;
        $lists = Db::table('ims_bj_shopn_oto o')
            ->join(['pt_ticket_user'=>'u'],['u.id=o.card_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=u.user_id'],'LEFT')
            ->join(['ims_bj_shopn_order'=>'ord'],['ord.id=u.orderid'],'LEFT')
            ->join(['ims_bj_shopn_goods'=>'gd'],['gd.id=u.goods_id'],'LEFT')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->join(['sys_departbeauty_relation'=>'sdy'],['sdy.id_beauty=b.id'],'LEFT')
            ->join(['sys_department'=>'sy'],['sy.id_department=sdy.id_department'],'LEFT')
            ->field('o.id,o.oto_user,o.oto_pwd,o.status,ord.ordersn,gd.title goods_name,m.realname user_name,o.msg,o.create_time,m.mobile,b.title,b.sign,sy.st_department')
            ->where($map)
            ->limit($page_limits,$limits)->order('o.create_time desc')->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(!empty($lists)){
            foreach ($lists as &$v) {
                $v['st_department'] = $v['st_department']==null?'':$v['st_department'];
                $v['sign'] = $v['sign']==null?'':$v['sign'];
                $v['title'] = $v['title']==null?'':$v['title'];
                $v['mobile'] = $v['mobile']==null?'':$v['mobile'];
                $v['ordersn'] = $v['ordersn']==null?'':$v['ordersn'];
                $v['goods_name'] = $v['goods_name']==null?'':$v['goods_name'];
                $v['user_name'] = $v['user_name']==null?'':$v['user_name'];
                if($v['status'] == 1){
                    $v['status'] = '<font color="green">有效</font>';
                }else{
                    $v['status'] = '<font color="red">已过期</font>';
                }
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v1) {
                $data[$k]['oto_user']= $v1['oto_user'];
                $data[$k]['oto_pwd']= $v1['oto_pwd'];
                $data[$k]['ordersn']="\t".$v1['ordersn'];
                $data[$k]['user_name']=$v1['user_name'];
                $data[$k]['mobile']=$v1['mobile'];
                $data[$k]['title']=$v1['title'];
                $data[$k]['st_department']=$v1['st_department'];
                $data[$k]['sign']=$v1['sign'];
                $data[$k]['create_time']=$v1['create_time'];
            }
            // echo "<pre>";print_r($data);die;
            $filename = "OTO脑力教育账号列表".date('YmdHis');
            $header = array ('oto账号','oto密码','订单编号','用户名称','用户号码','所在门店','所属市场','门店编号','添加时间');
            $widths=array('10','10','10','10','10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        if($page)
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
    public function user_ad()
    {
        if(request()->isAjax()){
            $flag = ['code'=>0,'msg'=>'上传失败','data'=>(object)[]];
            if($_FILES['upload_file']['name']){
                $type=$_FILES['upload_file']['name'];//获取上传文件名称
                $types=strtolower(strstr($type,'.'));//获取上传文件后缀
                if($types == '.csv'){
                    $path = $this->csv_path.date('YmdHis').'_'.$type;// 上传文件路径
                    if(move_uploaded_file($_FILES['upload_file']['tmp_name'],$path)){
                        $flag['code'] = 1;
                        $flag['msg'] = '上传成功';
                        // 导入文件账号,读取csv文件内容
                        $file = fopen($path,'r');
                        $i = 1;
                        $goods_list = [];
                        while ($data = fgetcsv($file)) {
                            if(!empty($data) && $i>=2){
                                if(strlen($data[0])>0){
                                    $goods_list[] = $data;
                                }
                            }
                            $i++;
                        }
//                        echo '<pre>';print_r($goods_list);die;
                        if($goods_list){
                            // 读取缺少的账号更新进去,并发送短信通知
                            $map['user_id'] = ['>',0];
                            $map['oto_user'] = ['eq',''];
                            $num_yy = 0;
                            $res_num_qs = Db::table('ims_bj_shopn_oto o')->join(['ims_bj_shopn_member'=>'m'],['o.user_id=m.id'],'LEFT')->field('o.id,m.mobile')->where($map)->select();

                            if(!empty($res_num_qs) && !empty($goods_list)){
                                foreach ($res_num_qs as $ks=>$v_qs) {
                                    $map1['id'] = $v_qs['id'];
                                    $data_upd['oto_user'] = $goods_list[$ks][0];
                                    $data_upd['oto_pwd'] = $goods_list[$ks][1];
                                    $res_gx = Db::table('ims_bj_shopn_oto')->where($map1)->update($data_upd);
                                    $num_yy ++;
                                    $res_sms = $this->smsSend($v_qs['mobile'],71,'{"oto_user":"'.$goods_list[$ks][0].'","oto_pwd":"'.$goods_list[$ks][1].'"}');//号码,模板id,code
                                }
                            }
                            // 其它多余的账号插入数据库
                            $res_crs = [];
//                            echo '<pre>';print_r($goods_list);die;
                            foreach ($goods_list as $k_gl=>$v_gl) {
                                if($k_gl>=$num_yy){
                                    $res_cr['oto_user'] = $v_gl[0];
                                    $res_cr['oto_pwd'] = $v_gl[1];
                                    $res_cr['create_time'] = date('Y-m-d H:i:s');
                                    $res_crs[] = $res_cr;
                                }
                            }
                            if(!empty($res_crs)){
                                Db::table('ims_bj_shopn_oto')->insertAll($res_crs);
                            }
                        }else{
                            $flag['msg'] = '文件格式不对,请上传csv文件数据为空';
                        }

                    }
                }else{
                    $flag['msg'] = '文件格式不对,请上传csv文件';
                }
            }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();

    }


    /**
     * [edit_ad 编辑广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function user_edit()
    {
        $id = input('id');
        $map['id'] = $id;
        if(request()->isPost()){
            $param = input('post.');
            $map['id'] = $param['id'];
            $data['oto_user'] = $param['oto_user'];
            $data['oto_pwd'] = $param['oto_pwd'];
            $data['orderid'] = $param['orderid'];
            $data['goods_id'] = $param['goods_id'];
            $data['user_id'] = $param['user_id'];
            $data['status'] = $param['status'];
            $data['msg'] =  $param['msg'];
            $res = Db::table('ims_bj_shopn_oto')->where($map)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $list = Db::table('ims_bj_shopn_oto')->where($map)->limit(1)->find();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [del_ad 删除广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function user_del()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $res = Db::table('ims_bj_shopn_oto')->where($map)->delete();
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
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
    /*
     * 功能:发送短信
     * 请求:$mobile=>号码,$template_id=>模板id,$code=>code里的变量
     * */
    public function smsSend($mobile,$template_id,$code='')
    {
        $str = 'mobile='.$mobile.'&name=qunarmeiApp&pwd=qunarmeiApp&template='.$template_id.'&type=1';
        if($code){
            $str = 'code='.$code.'&'.$str;
        }
        $key = md5($str);
        $url1 = 'http://sms.qunarmei.com/sms.php?'.$str.'&key='.$key;// 服务器短信需要加8080端口
        $rest = file_get_contents($url1);
        return $rest;
    }
    /**
     * [send_oto 发送oto学习卡]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function send_oto()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $map['sign'] = $param['sign'];
            $res = Db::table('ims_bwk_branch b')->where($map)->limit(1)->find();
            if($res){
                $arr['store_id'] = $res['id'];
                $arr['mobile'] = $param['mobile'];
                // 多个号码,分割
                $mobiles = $arr['mobile'];
                if (strstr($arr['mobile'],',')) {
                    $mobiles = explode(',', $arr['mobile']);
                }
                if (is_array($mobiles)) {
                    foreach ($mobiles as $k => $v) {
                        // 调用发送学习卡接口
                        $url = 'http://live.qunarmei.com/api/v4/oto_education/sendOtoCard?mobile='.$v.'&store_id='.$arr['store_id'];
                        $rest = curl_get($url);
                    }
                }else{
                    // 调用发送学习卡接口
                    $url = 'http://live.qunarmei.com/api/v4/oto_education/sendOtoCard?mobile='.$mobiles.'&store_id='.$arr['store_id'];
                    $rest = curl_get($url);
                }

//                $res = Db::table('ims_bj_shopn_oto')->where($map)->update($arr);
                // 推送站内信通知
//                $sernotice = new SerNotice();
//                $sernotice->sendJpush(4,'alias',$mapm['mobile'],'赠送的OTO脑力课程优惠,请查收!');
                // 推送短信通知
//                $skernotice->sendSms($mapm['mobile'],71);
//                echo '<pre>';print_r($rest);die;
                return json(['code' => 1, 'data' => [], 'msg' => '发送成功'.$rest]);
            }

        }
        $mapo['status'] = 1;
        $mapo['card_id'] = 0;
        $res_oto = Db::table('ims_bj_shopn_oto')->where($mapo)->order('create_time asc')->select();
        $this->assign('otoList',$res_oto);
        return $this->fetch();
    }
    /**
     * [excelImport 导入execel数据]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function excelImport()
    {
        if(request()->isAjax()){
            $data_arr = excel_import();
            if($data_arr == -1){
                return json(['code' => 0, 'data' => [], 'msg' => '文件格式不对']);
            }elseif($data_arr){
                $dataV = [];
                $msg = '';
                $dt = date('Y-m-d H:i:s');
                $sernotice = new SerNotice();
                foreach ($data_arr as $k=>$varr) {
                    if($k > 1){
                        $map['oto_user'] = $varr[0];
                        $res_oto = Db::table('ims_bj_shopn_oto')->where($map)->limit(1)->find();
                        if($res_oto){
                            $msg .= '有重复账号,';
                        }else{
                            $data1['oto_user'] = $varr[0];
                            $data1['oto_pwd'] = $varr[1];
                            $data1['create_time'] = $dt;
                            // 导入账号时判断是否有未补发用户 , 如有则需要补发并进行通知
                            $mapb['o.oto_user'] = ['eq',''];
                            $mapb['o.card_id'] = ['>',0];
                            $res_bf = Db::table('ims_bj_shopn_oto o')
                                ->where($mapb)
                                ->field('id,oto_user,card_id')
                                ->limit(1)
                                ->find()
                            ;
                            if($res_bf){
                                $datab['oto_user'] = $data1['oto_user'];
                                $datab['oto_pwd'] = $data1['oto_pwd'];
                                $mapb1['id'] = $res_bf['id'];
                                $res_b = Db::table('ims_bj_shopn_oto o')->where($mapb1)->update($datab);
                                if($res_b){
                                    // 短信和站内信通知
                                    $mapu['id'] = $res_bf['card_id'];
                                    $res_u = Db::table('pt_ticket_user u')
                                        ->where($mapu)
                                        ->field('mobile')
                                        ->limit(1)
                                        ->find()
                                    ;
                                    // 补发通知
                                    if($res_u){
                                        $sernotice->sendJpush(4,'alias',$res_u['mobile'],'您的诚美VIP全脑家教平台账户已补发成功,您的请前往个人中心－卡券，立刻获取您的VIP全脑家教平台专属帐号和密码。有效期180天!');
                                        $sernotice->sendSms($res_u['mobile'],72);
                                    }
                                }
                            }else{
                                $dataV[] = $data1;
                            }
                        }
                    }
                }
                if($dataV){
                    $res_add = Db::table('ims_bj_shopn_oto')->insertAll($dataV);
                    if($res_add){
                        $msg .= '添加成功,';
                    }
                }
                $msg = rtrim($msg,',');
                return json(['code' => 1, 'data' => [], 'msg' => $msg]);
            }

//            echo '<pre>';print_r($data_arr);die;
        }
        return $this->fetch();
    }

    /**
     * [index OTO学习卡列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function otocard(){
        $page = input('page');
        $key = input('key');
        $map['type'] = 9;
        if($key){
            $map['u.mobile|u.sign'] = ['like',"%$key%"];
        }
        $export = input('export',0);
        $Nowpage = $page?$page:1;
        $limits = 30;// 获取总条数
        $count = Db::table('pt_ticket_user u')
            ->join(['ims_bj_shopn_oto'=>'o'],['u.id=o.card_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=u.user_id'],'LEFT')
            ->field('u.id,o.oto_user,m.realname user_name,u.mobile,u.depart,u.branch,u.sign,u.insert_time,u.id_interestrate,u.pay_time,u.ordersn')               ->where($map)
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $page_limits = ($Nowpage-1)*$limits;
//        echo '<pre>';print_r($page_limits);print_r($limits);die;
        $lists = Db::table('pt_ticket_user u')
            ->join(['ims_bj_shopn_oto'=>'o'],['u.id=o.card_id'],'LEFT')
            ->join(['ims_bj_shopn_member'=>'m'],['m.id=u.user_id'],'LEFT')
            ->field('u.id,o.oto_user,m.realname user_name,u.mobile,u.depart,u.branch,u.sign,u.insert_time,u.id_interestrate,u.pay_time,u.ordersn')
            ->limit($page_limits,$limits)
            ->where($map)
            ->order('u.insert_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        if(!empty($lists)){
            $typv = [6=>'微信',8=>'支付宝'];
            foreach ($lists as $k => $v) {
                $lists[$k]['pay_type'] = $v['id_interestrate']>0?$typv[$v['id_interestrate']]:'';
                $lists[$k]['user_name'] = $v['user_name']==null?'':$v['user_name'];
                $lists[$k]['price'] = 168;
                $lists[$k]['oto_user'] = $v['oto_user']==null?'':$v['oto_user'];
                $lists[$k]['create_time'] = $v['insert_time']==null?'':$v['insert_time'];
                $lists[$k]['pay_time'] = $v['pay_time']==null?'':$v['pay_time'];
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['depart']=$v['depart'];
                $data[$k]['branch']= $v['branch'];
                $data[$k]['sign']= $v['sign'];
                $data[$k]['user_name']= $v['user_name'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['price']=$v['price'];
                $data[$k]['oto_user']=$v['oto_user'];
                $data[$k]['pay_type']=$v['pay_type'];
                $data[$k]['create_time']=$v['create_time'];
                $data[$k]['pay_time']=$v['pay_time'];
            }
            $filename = "OTO学习卡列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编号','用户名称','用户号码','价格','OTO账号','支付方式','创建时间','支付时间');
            $widths=array('10','10','10','10','10','10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        if($page)
        {
            return json($lists);
        }
        $this->assign('val', $key);
        return $this->fetch();
    }
}