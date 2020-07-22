<?php

namespace app\admin\controller;
use com\Gateway;
use think\Db;
use think\Loader;
use think\Queue;

class Marketing extends Base
{

    /**
     * [roleEdit 抽奖配置]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function config(){
        if(request()->isAjax()){
            $type=input('param.type');
            $typeInfo=Db::name('draw_scene')->where('scene_prefix',$type)->find();
            if (!empty($_FILES)) {
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = request()->file('myfile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    $exclePath = $info->getSaveName();  //获取文件名
                    $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                    $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                    array_shift($excel_array);  //删除标题;
                    Db::startTrans();
                    try {
                        foreach ($excel_array as $k => $v) {
                            //根据门店编码获取门店id
                            $getStoreId = Db::table('ims_bwk_branch')->where('sign', trimall($v[2]))->value('id');
                            $map['type'] = array('eq', $type);
                            $map['flag'] = array('eq', 0);
                            $getTicket = Db::name('ticket')->where($map)->field('id,ticket')->order('id')->limit($v[4])->select();
                            if (count($getTicket) && is_array($getTicket)) {
                                $ticketList = array();
                                $ticketIds = array();
                                foreach ($getTicket as $kk => $vv) {
                                    $ticketList[$kk]['depart'] = $v[0];
                                    $ticketList[$kk]['branch'] = $v[1];
                                    $ticketList[$kk]['sign'] = trimall($v[2]);
                                    $ticketList[$kk]['mobile'] = getNumber($v[3]);
                                    $ticketList[$kk]['par_value'] = getNumber($v[5]);
                                    $ticketList[$kk]['insert_time'] = date('Y-m-d H:i:s');
                                    $ticketList[$kk]['update_time'] = date('Y-m-d H:i:s');
                                    $ticketList[$kk]['ticket_code'] = $vv['ticket'];
                                    $ticketList[$kk]['type'] = $type;
                                    $ticketList[$kk]['storeid'] = $getStoreId;
                                    $ticketList[$kk]['draw_pic'] = $typeInfo['image1'];
                                    $ticketIds[] = $vv['id'];
                                    //记录日志
                                    sendQueue($vv['ticket'],$vv['ticket'].'分配给'.$v[1].$v[2].'下的'.trim($v[3]));
                                }
                                Db::name('ticket_user')->insertAll($ticketList);
                                Db::name('ticket')->where('id', 'in', $ticketIds)->update(['flag' => 1]);
                            }
                        }
                        Db::commit();
                        return json(['code' =>1, 'data' => '', 'msg' => '奖券发放成功']);
                    }catch (\Exception $e){
                        Db::rollback();
                        return json(['code' =>0, 'data' => '', 'msg' => '奖券发放失败'.$e->getMessage()]);
                    }
                }else {
                    return json(['code' =>0, 'data' => '', 'msg' => '文件上传失败']);
                }
            }
        }
        return $this->fetch();
    }

    public function ticket_index(){
        header("Cache-control: private");
        $key = input('key');
        $map = [];
        $export = input('export',0);
        $depart=input('param.depart');
        $storeid=input('param.storeid');
        $scene_prefix=input('param.scene_prefix');
        $status=input('param.status',100);
        $flag=input('param.flag',200);
        if($key&&$key!=="")
        {
            $map['ticket.ticket_code|ticket.mobile'] = ['like',"%" . $key . "%"];
        }
        if($depart && $depart!=="")
        {
            $map['ticket.depart'] = ['eq',$depart];
        }
        if($storeid && $storeid!=="")
        {
            $map['ticket.storeid'] = ['eq',$storeid];
        }
        if($scene_prefix && $scene_prefix!=="")
        {
            $map['s.scene_prefix'] = ['eq',$scene_prefix];
        }

        if($status!="100")
        {
            $map['ticket.status'] = ['eq',$status];
        }
        if($flag!="200")
        {
            $map['ticket.flag'] = ['eq',$flag];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('ticket_user')->alias('ticket')->join(['ims_bj_shopn_member' => 'mem'],'ticket.mobile=mem.mobile','left')->join('pt_draw_scene s','ticket.type=s.scene_prefix','left')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        if($export){
            $lists =Db::name('ticket_user')->alias('ticket')->field('ticket.*,mem.realname,s.scene_name')->join(['ims_bj_shopn_member' => 'mem'],'ticket.mobile=mem.mobile','left')->join('pt_draw_scene s','ticket.type=s.scene_prefix','left')->where($map)->order('ticket.id desc')->select();
        }else{
            $lists =Db::name('ticket_user')->alias('ticket')->field('ticket.*,mem.realname,s.scene_name')->join(['ims_bj_shopn_member' => 'mem'],'ticket.mobile=mem.mobile','left')->join('pt_draw_scene s','ticket.type=s.scene_prefix','left')->where($map)->page($Nowpage, $limits)->order('ticket.id desc')->select();
        }

        // $ticket_type=array('1'=>'老板抽奖券','2'=>'门店消费券','3'=>'老客抽奖券','4'=>'新客抽奖券');
        $ticket_status=array('0'=>'未使用','1'=>'已使用');
        foreach ($lists as $k=>$v){
            //$lists[$k]['type']=$ticket_type[$v['type']];
            //$lists[$k]['status']=$ticket_status[$v['status']];
            if($v['par_value']==0){
                $par_value='无';
            }else{
                $par_value=$v['par_value'];
            }
            $lists[$k]['par_value']=$par_value;
            $lists[$k]['draw_rank']=$v['draw_rank']?$v['draw_rank'].'<br/>'.$v['draw_name']:'';
            $lists[$k]['realname']=$v['realname']?$v['realname']:'未填写';
            if(!session('get_mobile')){
                $lists[$k]['mobile']=substr_replace($v['mobile'], '****', 3, 4);
            }
        }

        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                if($v['flag']==0){
                    switch($v['status']){
                        case -1:
                            $s='未激活';
                            break;
                        case 0:
                            $s='未使用';
                            break;
                        case 1:
                            $s='已使用';
                            break;
                        case 2:
                            $s='已使用';
                            break;
                        default:
                            $s='已失效';
                    }
                }else{
                    $s='已中奖';
                }
                $data[$k]['depart']=$v['depart'];
                $data[$k]['branch']=$v['branch'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['scene_name']=$v['scene_name'];
                $data[$k]['realname']=$v['realname'];
                $data[$k]['mobile']=$v['mobile'];
                $data[$k]['ticket_code']="`".$v['ticket_code'];
                $data[$k]['par_value']=$v['par_value'];
                $data[$k]['status']=$s;
                $data[$k]['insert_time']=$v['insert_time'];
                $data[$k]['update_time']=$v['update_time'];
            }
            $filename = "用户奖券列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','奖券类型','归属人姓名','归属人电话','奖券号码','奖券面值','奖券状态','插入时间','更新时间');
            $widths=array('10','30','20','15','15','15','15','30','30','30','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }


        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('depart', $depart);
        $this->assign('storeid', $storeid);
        $this->assign('status', $status);
        $this->assign('flag', $flag);
        $this->assign('count', $count);
        $this->assign('scene_prefix', $scene_prefix);

        //门店列表
        $branch=Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        $this->assign('branch', $branch);
        //办事处列表
        $bsc=Db::table('sys_department')->field('id_department,st_department')->select();
        $this->assign('bsc', $bsc);

        //抽奖类型列表
        $sceneList=Db::name('draw_scene')->select();
        $this->assign('sceneList', $sceneList);

        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     *删除券号
     */
    public function ticket_del(){
        $code = input('param.id');
        Db::startTrans();
        try{
            Db::name('ticket_user')->where('ticket_code', $code)->delete();
            Db::name('ticket')->where('ticket', $code)->update(['flag'=>0]);
            Db::name('ticket_log')->where('ticket_code', $code)->update(['status'=>1]);
            sendQueue($code,$code.'被管理员'.session('username').'删除',1);
            Db::commit();
            $flag= ['code' => 1, 'data' => '', 'msg' => '删除奖券成功'];
        }catch( \PDOException $e){
            Db::rollback();
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     *改变券号状态
     */
    public function ticket_state()
    {
        $code = input('param.code');
        $status = Db::name('ticket_user')->where('ticket_code',$code)->value('status');//判断当前状态情况
        if($status==1)
        {
            $update=['status'=>0,'draw_pic'=>config("ticket_pic.0")];
            $flag = Db::name('ticket_user')->where('ticket_code',$code)->update($update);
            if($flag){
                sendQueue($code,$code.'被管理员'.session('username').'设置为未使用');
            }
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '未使用']);
        } else {
            $update=['status'=>1,'draw_pic'=>config("ticket_pic.4")];
            $flag = Db::name('ticket_user')->where('ticket_code',$code)->update($update);
            if($flag){
                sendQueue($code,$code.'被管理员'.session('username').'设置为已使用');
            }
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已使用']);
        }
    }



    /*
     * 读取券号记录
     * */
    public function ticket_info(){
        header("Cache-control: private");
        $code=input('param.code');
        $getCodeInfo=Db::name('ticket_log')->where('ticket_code',$code)->select();
        $this->assign('code',$code);
        $this->assign('log',$getCodeInfo);
        return $this->fetch();
    }


    public function testQueue(){
        $arr=['ticket_code'=>'2342343243','desc'=>'测试写入日志','insert_time'=>date('Y-m-d H:i:s')];
        Queue::push('app\index\job\TicketLog', $arr, 'ticketLog');
    }


    /**
     * [roleEdit 直播活动]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function live(){
        if(request()->isAjax()){
            //获取当前中奖奖券号
            //$client = stream_socket_client('tcp://139.196.113.127:2349');
            $param = input('post.');
            //$array=array('preheat_url'=>$param['preheat_url'],'live_url'=>$param['live_url'],'flag'=>$param['flag']);
            //$array=array('preheat_url'=>$param['preheat_url'],'live_url'=>$param['live_url'],'temp_live_url1'=>$param['temp_live_url1'],'temp_live_url2'=>$param['temp_live_url2'],'flag'=>$param['flag']);
			if($param['show_end']!=''){
			    $show_end=strtotime($param['show_end']);
            }
            $array=array('preheat_url'=>$param['preheat_url'],'live_url'=>$param['live_url'],'temp_live_url1'=>$param['temp_live_url1'],'temp_live_url2'=>$param['temp_live_url2'],'flag'=>$param['flag'],'audience'=>$param['audience'],'goods_show'=>$param['goods_show'],'show_end'=>$show_end,'live_mobile'=>$param['live_mobile']);
            Db::name('live_url')->where('id',1)->update($array);
            if($param['flag']){
                //$data = 'begin';
                $data = array('scene'=>'live','live_url'=>$param['live_url']);
            }else{
                //$data = 'live end';
                $data = array('scene'=>'live','live_url'=>$param['preheat_url']);
            }
            Gateway::sendToGroup('live',json_encode($data));
            //fwrite($client, json_encode($data)."\n");
            return json(['code' => 1, 'data' => '', 'msg' => '配置成功']);
            die();
        }
        $live=Db::name('live_url')->where('id',1)->find();
        $live['show_end']=date('Y-m-d H:i:s',$live['show_end']);
        $this->assign('live',$live);
        return $this->fetch();
    }


    public function live_log(){
        $flag=input('param.flag',1);
        $data=array();
        if($flag==1){
            $list=Gateway::getUidListByGroup('live');
            if($list){
                //导出
                $i=0;
                foreach ($list as $k => $v) {
                    $info = Db::table('ims_bj_shopn_member')->alias('member')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->where('member.mobile',$v)->find();
                    $data[$i]['st_department']=$info['st_department'];
                    $data[$i]['title']=$info['title'];
                    $data[$i]['sign']=$info['sign'];
                    $sellerInfo=Db::table('ims_bj_shopn_member')->where('id',$info['staffid'])->field('mobile,realname')->find();
                    $data[$i]['sellername']=$sellerInfo['realname'];
                    $data[$i]['sellermobile']=$sellerInfo['mobile'];
                    $data[$i]['realname']=$info['realname'];
                    $data[$i]['mobile']=$info['mobile'];
                    $i++;
                }
                $filename = "当前聊天室在线用户列表".date('YmdHis');
                $header = array ('办事处','门店名称','门店编码','所属美容师','美容师电话','顾客姓名','顾客电话');
                $widths=array('10','30','20','15','15','15','15');
            }
        }elseif($flag==2){
            $map['log.item_name']=array('eq','live');
            $lists = Db::name('user_stay')->alias('log')->join(['ims_bj_shopn_member' => 'member'],'member.mobile=log.mobile','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.*,member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->where($map)->order('log.id')->select();
            if($lists) {
                //导出
                foreach ($lists as $k => $v) {
                    $data[$k]['st_department'] = $v['st_department'];
                    $data[$k]['title'] = $v['title'];
                    $data[$k]['sign'] = $v['sign'];
                    $sellerInfo = Db::table('ims_bj_shopn_member')->where('id', $v['staffid'])->field('mobile,realname')->find();
                    $data[$k]['sellername'] = $sellerInfo['realname'];
                    $data[$k]['sellermobile'] = $sellerInfo['mobile'];
                    $data[$k]['realname'] = $v['realname'];
                    $data[$k]['mobile'] = $v['mobile'];
                    $data[$k]['login_time'] = date('Y-m-d H:i:s', $v['login_time']);
                    $data[$k]['leave_time'] = date('Y-m-d H:i:s', $v['leave_time']);;
                    $data[$k]['stay_time'] = s_to_hs($v['stay_time']);
                }
                $filename = "用户停留记录" . date('YmdHis');
                $header = array('办事处', '门店名称', '门店编码', '所属美容师', '美容师电话', '顾客姓名', '顾客电话', '进入时间', '离开时间', '停留时间');
                $widths = array('10', '30', '20', '15', '15', '15', '15', '30', '30', '10');
            }
        }elseif($flag==3){
            $map['log.item_name']=array('eq','live');
            $lists = Db::name('user_stay')->alias('log')->join(['ims_bj_shopn_member' => 'member'],'member.mobile=log.mobile','left')->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty','left')->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')->field('log.*,sum(log.stay_time) stay_time,member.realname,member.mobile,member.staffid,bwk.title,bwk.sign,depart.st_department')->group('log.mobile')->where($map)->order('log.id desc')->select();
            if($lists) {
                //导出
                foreach ($lists as $k => $v) {
                    $data[$k]['st_department'] = $v['st_department'];
                    $data[$k]['title'] = $v['title'];
                    $data[$k]['sign'] = $v['sign'];
                    $sellerInfo = Db::table('ims_bj_shopn_member')->where('id', $v['staffid'])->field('mobile,realname')->find();
                    $data[$k]['sellername'] = $sellerInfo['realname'];
                    $data[$k]['sellermobile'] = $sellerInfo['mobile'];
                    $data[$k]['realname'] = $v['realname'];
                    $data[$k]['mobile'] = $v['mobile'];
                    $data[$k]['stay_time'] = s_to_hs($v['stay_time']);
                }
                $filename = "用户停留汇总记录" . date('YmdHis');
                $header = array('办事处', '门店名称', '门店编码', '所属美容师', '美容师电话', '顾客姓名', '顾客电话', '共计停留');
                $widths = array('10', '30', '20', '15', '15', '15', '15', '10');
            }
        }
        if($data) {
            excelExport($filename, $header, $data, $widths);//生成数据
        }else{
            echo "暂无数据";
        }
        die();
    }



    /**
     * [create_ticket 生成抽奖券]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function create_ticket(){
        set_time_limit(0);
        if(request()->isAjax()){
          $type=input('param.type');
          $tick_num=input('param.tick_num');
           $getTicket=generate_promotion_code($type,$tick_num,'',7);
           $data=array();
           try {
               foreach ($getTicket as $k => $v) {
                   $data[$k]['ticket'] = $v;
                   $data[$k]['type'] = $type;
               }
               Db::name('ticket')->insertAll($data);
               return json(['code' => 1, 'data' =>'', 'msg' => '生成成功']);
           }catch (\Exception $e){
               return json(['code' => 0, 'data' => '', 'msg' => '生成失败'.$e->getMessage()]);
           }
        }
        $draw_list=Db::name('draw_scene')->order('scene_prefix')->select();
        $this->assign('draw_list',$draw_list);
        return $this->fetch();
    }

    /**
     * 抽奖场景配置
     */
    public function scene(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['scene_name'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('draw_scene')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::name('draw_scene')->where($map)->page($Nowpage, $limits)->order('id')->select();
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
     * [roleAdd 添加场景]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function sceneAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                if($param['ticket_type']){
                    $param['ticket_type']=implode(',',$param['ticket_type']);
                }
                unset($param['file']);
                $result =  Db::name('draw_scene')->insert($param);
                if(false === $result){
                    $res= ['code' => -1, 'data' => '', 'msg' => '添加抽奖场景失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '添加抽奖场景成功'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $scene_prefix=  Db::name('draw_scene')->order('scene_prefix desc')->value('scene_prefix');
        $this->assign('scene_prefix',$scene_prefix+1);
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑场景]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function sceneEdit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                if($param['ticket_type']){
                    $param['ticket_type']=implode(',',$param['ticket_type']);
                }
                unset($param['file']);
                $result = Db::name('draw_scene')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护抽奖场景失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护抽奖场景成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $info= Db::name('draw_scene')->where('id', $id)->find();
        if(!$info['ticket_type']){
            $info['ticket_type']='-1';
        }
        $this->assign('info',$info);
        return $this->fetch();
    }


    public function test(){
        $seller=Db::name('tuan_list')->alias('list')->join(['ims_bj_shopn_member'=>'mem'],'list.share_uid=mem.id','left')->group('list.share_uid')->column('mem.realname');
        $create=Db::name('tuan_list')->alias('list')->join(['ims_bj_shopn_member'=>'mem'],'list.create_uid=mem.id','left')->group('list.create_uid')->column('mem.realname');
        $memberList[]=array('name'=>'拼购');
        foreach ($seller as $k=>$v){
            $memberList[]=array('name'=>$v);
        }

        $link=array();
        foreach ($seller as $k=>$v){
            $link[]=array('source '=>0,'target'=>$k);
        }

        $this->assign('memberList',json_encode($memberList));
        $this->assign('link',json_encode($link));

        return $this->fetch();
    }

    /**
     * 门店中奖配置
     */
    public function branchDraw(){
        $key = input('key');
        $map = [];
        if($key && $key!=="")
        {
            $map['bwk.title|sign'] = ['like',"%" . $key . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('draw_branch')->alias('b')->join(['ims_bwk_branch' => 'bwk'],'b.storeid=bwk.id','left')->join(['pt_draw_scene' => 's'],'b.type=s.scene_prefix','left')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::name('draw_branch')->alias('b')->field('b.id,b.num,b.flag,bwk.title,bwk.sign,s.scene_name,s.scene_prefix')->join(['ims_bwk_branch' => 'bwk'],'b.storeid=bwk.id','left')->join(['pt_draw_scene' => 's'],'b.type=s.scene_prefix','left')->where($map)->page($Nowpage, $limits)->order('b.id')->select();
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
     * [roleAdd 添加门店配额]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function branchDrawAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $check=Db::name('draw_branch')->where(['storeid'=>$param['storeid'],'type'=>$param['type']])->find();
                if($check){
                    $res= ['code' => -1, 'data' => '', 'msg' => '已存在 无需重复配置！'];
                }else{
                    $result =  Db::name('draw_branch')->insert($param);
                    if(false === $result){
                        $res= ['code' => -1, 'data' => '', 'msg' => '添加门店配额失败'];
                    }else{
                        $res= ['code' => 1, 'data' => '', 'msg' => '添加门店配额成功'];
                    }
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        //门店列表
        $branch=Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        $this->assign('branch', $branch);
        //抽奖类型列表
        $sceneList=Db::name('draw_scene')->where('join_draw',1)->select();
        $this->assign('sceneList', $sceneList);
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑门店配额]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function branchDrawEdit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::name('draw_branch')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护门店配额失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护门店配额成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $id = input('param.id');
        $info= Db::name('draw_branch')->where('id', $id)->find();
        $this->assign('info',$info);
        //门店列表
        $branch=Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        $this->assign('branch', $branch);
        //抽奖类型列表
        $sceneList=Db::name('draw_scene')->where('join_draw',1)->select();
        $this->assign('sceneList', $sceneList);
        return $this->fetch();
    }
    /**
     * [roleEdit 删除门店配额]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function branchDrawDel(){
        $id = input('param.id');
        try{
            Db::name('draw_branch')->where('id', $id)->delete();
            $flag= ['code' => 1, 'data' => '', 'msg' => '门店配额删除成功'];
        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    //导入页面
    public function branchDrawImport(){
        if(request()->isAjax()){
            if (!empty($_FILES)) {
                $type=input('param.type');
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = request()->file('myfile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    $exclePath = $info->getSaveName();  //获取文件名
                    $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                    $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                    $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                    array_shift($excel_array);  //删除标题;
                    $errData=[];
                    $errData1=[];
                    foreach ($excel_array as $k=>$v){
                        $getStoreId=Db::table('ims_bwk_branch')->where('sign',trim($v[0]))->value('id');
                        if($getStoreId){
                            $check=Db::name('draw_branch')->where(['storeid'=>$getStoreId,'type'=>$type])->count();
                            if($check){
                                $errData1[]=trim($v[0]);
                            }else{
                                $arr=array('storeid'=>$getStoreId,'type'=>$type,'num'=>trim($v[1]));
                                Db::name('draw_branch')->insert($arr);
                            }
                        }else{
                            $errData[]=trim($v[0]);
                        }
                    }
                    if(count($errData)>0 || count($errData1)>0){
                        $flag['code'] = 0;
                        $flag['data'] = "门店不存在：".implode(',',$errData)."<br>门店已配置：".implode(',',$errData1);
                        $flag['msg'] = '部分导入错误';
                    }else{
                        $flag['code'] = 1;
                        $flag['data'] = '';
                        $flag['msg'] = '成功';
                    }
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }
        }
        //抽奖类型列表
        $sceneList=Db::name('draw_scene')->where('join_draw',1)->select();
        $this->assign('sceneList', $sceneList);
        return $this->fetch();
    }





}
