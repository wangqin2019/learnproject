<?php

namespace app\admin\controller;
use app\admin\model\DrawModel;
use think\Db;
use think\Loader;
use think\Request;

class Draw extends Base
{

  public function index(){
      //$get_type=Db::name('draw_scene')->where('scene_check',1)->value('scene_prefix');
      //$cur=$get_type?$get_type:1;
      $draw_type=input('param.draw_type',1);
      $this->assign('d_type',$draw_type);

      //获取持券人数
      $ticketMan=Db::name('ticket_user')->where(['type'=>3])->group('mobile')->field('id')->select();
      $ticketMan1=Db::name('ticket_user')->where(['type'=>4,'share_status'=>5])->group('mobile')->field('id')->select();
      $this->assign('ticketMan',count($ticketMan));
      $this->assign('ticketMan1',count($ticketMan1));
      //抽奖类型列表
      $sceneList=Db::name('draw_scene')->where('join_draw',1)->select();
      $this->assign('sceneList', $sceneList);
	  return $this->fetch();
  }

  //新增奖项
    public function add_draw(){
      if(request()->isAjax()){
          $param = input('post.');
          $draw = new DrawModel();
          $flag = $draw->insertDraw($param);
          return json(['code' => $flag['code'], 'data' => $param, 'msg' => $flag['msg']]);
      }
        //抽奖类型列表
        $sceneList=Db::name('draw_scene')->where('join_draw',1)->select();
        $this->assign('sceneList', $sceneList);
      return $this->fetch();
    }
    //编辑奖项
    public function edit_draw(){
        if(request()->isAjax()){
            $param = input('post.');
            $draw = new DrawModel();
            $flag = $draw->editDraw($param);
            return json(['code' => $flag['code'], 'data' => $param, 'msg' => $flag['msg']]);
        }
        $id=input('param.id');
        $draw = new DrawModel();
        $info=$draw->getOneDraw($id);
        $info['mobileList']=Db::name('draw_lucky_mobile')->where('did',$info['id'])->select();
        $this->assign('info',$info);
        //抽奖类型列表
        $sceneList=Db::name('draw_scene')->where('join_draw',1)->select();
        $this->assign('sceneList', $sceneList);
        return $this->fetch();
    }

    //删除奖项
    public function del_draw(){
        $id = input('param.id');
        $draw = new DrawModel();
        $flag =$draw->delDraw($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    //清空中奖记录
//    public function distroy_draw(){
//        try{
//            Db::execute('TRUNCATE table think_lucky_draw');
//            $flag= ['code' => 1, 'data' => '', 'msg' => '中奖记录已清空'];
//        }catch( \PDOException $e){
//            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
//        }
//        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
//    }

    //获取角色抽奖奖项
    public function drawList(){
        $draw_type=input('param.draw_type');
        $map['draw_type']=array('eq',$draw_type);
        $list=Db::name('draw')->where($map)->order('orderby')->select();
        foreach ($list as $k=>$v){
            $map1['status']=array('eq',1);
            $map1['draw_id']=array('eq',$v['id']);
            $list[$k]['count']=Db::name('lucky_mobile')->where($map1)->count();
        }
        if(count($list)){
            $res['code']=1;
            $res['data']=$list;
        }else{
            $res['code']=0;
            $res['data']='';
        }
        return json_encode($res);
    }



    //抽奖准备
    public function vote_ready()
    {
        $id = input('param.id');
        $getDrawInfo=Db::name('draw')->where('id',$id)->field('id,draw_rank,draw_flag,draw_name,draw_num')->find();
        //$client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
        $client = stream_socket_client('tcp://172.16.6.163:2349');//测试环境
        //推送准备要开始的抽奖
        $data = array('scene'=>'draw', 'flag'=>0,'rank'=>$getDrawInfo,'type'=>'one');
        fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => $getDrawInfo['draw_name'].'抽奖界面已准备！']);
    }

    //抽奖开始
    public function vote_begin()
    {
        //$num = input('param.num');
        $draw_id = input('param.draw_id');//抽奖id
        //$draw_type = input('param.draw_type');//场景id
        //$client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
        $client = stream_socket_client('tcp://172.16.6.163:2349');//测试环境
        //获取奖池奖券
        $getDrawInfo=Db::name('draw')->where('id',$draw_id)->find();
        $prizeList = $this->getTicket($draw_id,1);//此处第三个参数 1为获取中奖奖券
        $tickList = $this->getTicket($draw_id,0);//此处第三个参数 1为获取中奖用户 0为获取随机滚动奖券
        $data = array('scene'=>'draw', 'flag'=>1,'num'=>$getDrawInfo['draw_num'],'status'=>1,'draw_id'=>$draw_id,'prizeList'=>$prizeList,'tickList'=>$tickList,'type'=>'one');
        fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => '前台抽奖池已启动！']);
    }

    //抽奖结束
    public function vote_end()
    {
        //$client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
        $client = stream_socket_client('tcp://172.16.6.163:2349');//测试环境
        $data = array('scene'=>'draw','flag'=>1, 'num'=>'0','status'=>0,'draw_type'=>'0','type'=>'one');
        fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => '前台抽奖池已停止']);
    }

    //导出中奖记录
    public function export(){
        $allDraw=Db::name('ticket_user')->where('flag',1)->order('id desc')->select();
            $data=array();
            foreach ($allDraw as $k => $v) {
                $data[$k]['depart'] = $v['depart'];
                $data[$k]['branch'] = $v['branch'];
                $data[$k]['sign'] = $v['sign'];
                $data[$k]['mobile'] = $v['mobile'];
                $data[$k]['code'] = $v['ticket_code'];
                $data[$k]['draw_rank'] = $v['draw_rank'];
                $data[$k]['draw_name'] = $v['draw_name'];
            }
            $filename = "中奖名单".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','手机号码','奖券号','中奖奖项','奖品名称');
            $widths=array('10','15','15','15','15','15','30');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
    }

    //导入中奖记录发送短信
    public function import(){
        set_time_limit(0);
        if(request()->isAjax()){
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
                    foreach ($excel_array as $k=>$v){
                        $this->sms($v[0], $v[1],$v[2]);
                    }
                    $flag['code'] = 1;
                    $flag['data'] = '';
                    $flag['msg'] = '成功';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }
        }
        return $this->fetch();
    }


    //获取奖券号码
    public function getTicket($draw_id,$flag)
    {
        //flag为1生成本次中奖券号码
        if($flag){
            //将当前抽奖信息存入redis
            $getDrawInfo=Db::name('draw')->where('id',$draw_id)->find();
            $getOldAllBranch=Db::name('ticket_user')->where(['type'=>3,'flag'=>0])->group('storeid')->column('storeid');
            if($getOldAllBranch){
                foreach ($getOldAllBranch as $val){
                    self::$redis->sAddExist('draw'.$draw_id.'getBranchOld', $val);
                }
            }
            $getNewAllBranch=Db::name('ticket_user')->where(['type'=>4,'flag'=>0,'share_status'=>5])->group('storeid')->column('storeid');
            if($getNewAllBranch){
                foreach ($getNewAllBranch as $val){
                    self::$redis->sAddExist('draw'.$draw_id.'getBranchNew', $val);
                }
            }







            if($getDrawInfo){
                self::$redis->set('draw'.$draw_id.'count', $getDrawInfo['draw_num']);
                self::$redis->set('draw'.$draw_id.'old', $getDrawInfo['old_draw_num']);
                self::$redis->set('draw'.$draw_id.'new', $getDrawInfo['new_draw_num']);
                $ticketList=$this->luckyTicket($draw_id);
            }
        }else{
            $map['type'] = array('in', '3,4');
            $map['flag'] = array('eq', 0);
            $ticketList = Db::name('ticket')->where($map)->limit(300)->column('ticket');
        }
        return $ticketList;
    }


    //获取中奖奖券
    public function luckyTicket($draw_id){

        //获取当前抽奖信息
        $getDrawInfo=Db::name('draw')->where('id',$draw_id)->find();
        $draw_num=self::$redis->get('draw'.$draw_id.'count');
        $old_draw_num=self::$redis->get('draw'.$draw_id.'old');
        $new_draw_num=self::$redis->get('draw'.$draw_id.'new');
        //获取当前已中奖的用户电话号码
        $getMobiles=self::$redis->sMembers('drawMobile');
        //获取当前新客已中奖的用户电话号码
        //$getMobiles1=Db::name('ticket_user')->where(['flag'=>1,'type'=>4])->group('mobile')->column('mobile');
        //获取当前老客抽奖场景配置的门店保底中奖人数
        $getOldDrawNum=Db::name('draw_scene')->where('scene_prefix',3)->value('branch_num');
        $vipMobile=Db::name('draw_lucky_mobile')->column('mobile');

        $oldList=[];
        $newList=[];
        $oldCodeList=[];
        $oldStoreList=[];
        $oldMobileList=[];
        $newCodeList=[];
        $newStoreList=[];
        $newMobileList=[];
        $removeMobile=[];
        $removeMobiles=[];
        //1、如果有老客设置vip名单 则先在vip名单中读取中奖电话
        $getVipMobile=Db::name('draw_lucky_mobile')->where(['did'=>$draw_id,'status'=>1])->column('mobile');
        if($getVipMobile){
            foreach ($getVipMobile as $val){
                    if(self::$redis->get('draw'.$draw_id.'old')){
                        $map['mobile']=array('eq',$val);
                        $map['type']=array('eq',3);
                        $map['flag']=array('eq',0);
                        $map['ticket_code']=array('not in',self::$redis->sMembers('draw'.$draw_id.'oldCodeList'));
                        $getTicketData=$this->getRandomCode($map);
                        if($getTicketData) {
                            self::$redis->sAddExist('draw'.$draw_id.'oldCodeList', $getTicketData['ticket_code']);
                            self::$redis->sAddExist('draw'.$draw_id.'oldStoreList', $getTicketData['storeid']);
                            self::$redis->sAddExist('draw'.$draw_id.'oldMobileList', $getTicketData['mobile']);
                            self::$redis->DECR('draw'.$draw_id.'old');
                        }
                    }
            }
        }
//        echo self::$redis->get('draw'.$draw_id.'old');
//        $aaa=self::$redis->sMembers('draw'.$draw_id.'getBranchOld');
//        print_r($aaa);
//        self::$redis->sAddExist('draw'.$draw_id.'getBranchOld', $val);


        //2、如果老顾客vip名单中的数量不满足抽奖数量，获取参与门店中用户（先门店配置后全局配置）
        if(self::$redis->get('draw'.$draw_id.'old')){
            $cha=self::$redis->get('draw'.$draw_id.'old');//获取不满足个数
            $storeArr = array_count_values(self::$redis->sMembers('draw'.$draw_id.'oldStoreList'));
            $getAllBranch=self::$redis->sMembers('draw'.$draw_id.'getBranchOld');
            foreach ($getAllBranch as $val1){
                if(self::$redis->get('draw'.$draw_id.'old')){
                    $map2['storeid']=array('eq',$val1);
                    $map2['flag']=array('eq',1);
                    $map2['type']=array('eq',3);
                    $getNumberList = Db::name('ticket_user')->where($map2)->group('mobile')->column('id');
                    $getNumber=count($getNumberList);
                    if(self::$redis->scard('draw'.$draw_id.'oldStoreList')) {
                        if (array_key_exists($val1, $storeArr)) {
                            $getNumber = $getNumber + $storeArr[$val1];
                        }
                    }
                    //检测该门店是否独立配置了保底人数
                    $getBranchNum = Db::name('draw_branch')->where(['storeid' => $val1, 'type' => 3])->value('num');
                    if ($getBranchNum) {
                        $limit = $getBranchNum;
                    } else {
                        $limit = $getOldDrawNum;
                    }
                    if ($getNumber < $limit) {
                        $map3['storeid'] = array('eq', $val1);
                        $map3['type'] = array('eq', 3);
                        $map3['flag'] = array('eq', 0);
                        $removeMobile=array_merge($getMobiles,self::$redis->sMembers('draw'.$draw_id.'oldMobileList'));
                        $map3['mobile']=array('not in',$removeMobile);
                        $getTicketData1 = $this->getRandomCode($map3);
                        if($getTicketData1){
                            if(!in_array($getTicketData1['mobile'],$vipMobile)){
                                self::$redis->sAddExist('draw'.$draw_id.'oldCodeList', $getTicketData1['ticket_code']);
                                self::$redis->sAddExist('draw'.$draw_id.'oldStoreList', $getTicketData1['storeid']);
                                self::$redis->sAddExist('draw'.$draw_id.'oldMobileList', $getTicketData1['mobile']);
                                self::$redis->DECR('draw'.$draw_id.'old');
                            }
                        }
                    }else{
                        self::$redis->srem('draw'.$draw_id.'oldStoreList', $val1);
                    }
                }else{
                    break;
                }
            }
        }


        //3、如果门店配置后仍不满足老客抽奖数量，随机抽取未中奖的用户奖券（要遵循用户不能重复中奖的规则）
        if(self::$redis->get('draw'.$draw_id.'old')){
            $cha1=$old_draw_num-count($oldCodeList);//获取不满足个数
            if(self::$redis->scard('draw'.$draw_id.'oldMobileList')) {
                $removeMobiles=array_merge(self::$redis->sMembers('draw'.$draw_id.'oldMobileList'),$getMobiles);
                $map6['mobile']=array('not in',$removeMobiles);
            }else{
                $removeMobiles=$getMobiles;
                $map6['mobile']=array('not in',$removeMobiles);
            }
            $map6['type'] = array('eq', 3);
            $map6['flag'] = array('eq', 0);
            $mobiles = Db::name('ticket_user')->where($map6)->group('mobile')->column('mobile');
            shuffle($mobiles);
            foreach ($mobiles as $val3){
                if(self::$redis->get('draw'.$draw_id.'old')){
                    $map7['type'] = array('eq', 3);
                    $map7['flag'] = array('eq', 0);
                    $map7['mobile'] = array('eq', $val3);
                    $getTicketData3 = $this->getRandomCode($map7);
                    if($getTicketData3) {
                        if(!in_array($getTicketData3['mobile'],$vipMobile)) {
                            self::$redis->sAddExist('draw'.$draw_id.'oldCodeList', $getTicketData3['ticket_code']);
                            self::$redis->sAddExist('draw'.$draw_id.'oldStoreList', $getTicketData3['storeid']);
                            self::$redis->sAddExist('draw'.$draw_id.'oldMobileList', $getTicketData3['mobile']);
                            self::$redis->DECR('draw'.$draw_id.'old');
                        }
                    }
                }else{
                    break;
                }
            }
        }

//        echo self::$redis->get('draw'.$draw_id.'old');
//        $aaa=self::$redis->sMembers('draw'.$draw_id.'oldCodeList');
//        $bbb=self::$redis->sMembers('draw'.$draw_id.'oldStoreList');
//        $ccc=self::$redis->sMembers('draw'.$draw_id.'oldMobileList');
//        print_r($aaa);
//        print_r($bbb);
//        print_r($ccc);
//        die();

        //4、新客抽奖 获取参与门店中用户（全局配置）
        //获取当前新客抽奖场景配置的门店保底中奖人数
        $getNewDrawNum=Db::name('draw_scene')->where('scene_prefix',4)->value('branch_num');
        $getNewAllBranch=self::$redis->sMembers('draw'.$draw_id.'getBranchNew');
        foreach ($getNewAllBranch as $val2){
            if(self::$redis->get('draw'.$draw_id.'new')){
                $getNewNumber = Db::name('ticket_user')->where(['storeid' => $val2, 'flag' => 1,'type'=>4])->count();
                if ($getNewNumber < $getNewDrawNum) {
                        $map5['storeid'] = array('eq', $val2);
                        $map5['type'] = array('eq', 4);
                        $map5['flag'] = array('eq', 0);
                        $map5['share_status']=array('eq',5);
                        //$remove=array_merge($getMobiles,$newMobileList,$oldMobileList);
                        $remove=array_merge($getMobiles,self::$redis->sMembers('draw'.$draw_id.'oldMobileList'),self::$redis->sMembers('draw'.$draw_id.'newMobileList'));
                        $map5['mobile']=array('not in',$remove);
                        $getTicketData4 = $this->getRandomCode($map5);
                        if($getTicketData4) {
                            $check1=self::$redis->sismember('drawMobile',$getTicketData4['mobile']);
                            if(!$check1) {
                                self::$redis->sAddExist('draw'.$draw_id.'newCodeList', $getTicketData4['ticket_code']);
                                self::$redis->sAddExist('draw'.$draw_id.'newStoreList', $getTicketData4['storeid']);
                                self::$redis->sAddExist('draw'.$draw_id.'newMobileList', $getTicketData4['mobile']);
                                self::$redis->DECR('draw'.$draw_id.'new');
                            }
                        }
                }
            }else{
                break;
            }
        }
//        echo self::$redis->get('draw'.$draw_id.'new');
//        $aaa=self::$redis->sMembers('draw'.$draw_id.'newCodeList');
//        $bbb=self::$redis->sMembers('draw'.$draw_id.'newStoreList');
//        $ccc=self::$redis->sMembers('draw'.$draw_id.'newMobileList');
//        print_r($aaa);
//        print_r($bbb);
//        print_r($ccc);
//        die();

        //5、如果门店配置后仍不满足新客抽奖数量，随机抽取未中奖的用户奖券（要遵循用户不能重复中奖的规则）
        if(self::$redis->get('draw'.$draw_id.'new')){
            $cha11=$new_draw_num-count($newCodeList);//获取不满足个数
            if(self::$redis->scard('draw'.$draw_id.'newMobileList')) {
                $removeNewMobiles=array_merge($getMobiles,self::$redis->sMembers('draw'.$draw_id.'newMobileList'),self::$redis->sMembers('draw'.$draw_id.'oldMobileList'));
                $map8['mobile']=array('not in',$removeNewMobiles);
            }else{
                $removeNewMobiles=array_merge($getMobiles,self::$redis->sMembers('draw'.$draw_id.'oldMobileList'));
                $map8['mobile']=array('not in',$removeNewMobiles);
            }
            $map8['type'] = array('eq', 4);
            $map8['flag'] = array('eq', 0);
            $map8['share_status']=array('eq',5);
            $mobiless = Db::name('ticket_user')->where($map8)->group('mobile')->column('mobile');
            shuffle($mobiless);
            foreach ($mobiless as $val4){
                if(self::$redis->get('draw'.$draw_id.'new')){
                    $map9['type'] = array('eq', 4);
                    $map9['flag'] = array('eq', 0);
                    $map9['share_status']=array('eq',5);
                    $map9['mobile']=array('eq',$val4);
                    $getTicketData5 = $this->getRandomCode($map9);
                    if($getTicketData5) {
                        $check2=self::$redis->sismember('drawMobile',$getTicketData4['mobile']);
                        if(!$check2) {
                            self::$redis->sAddExist('draw'.$draw_id.'newCodeList', $getTicketData5['ticket_code']);
                            self::$redis->sAddExist('draw'.$draw_id.'newStoreList', $getTicketData5['storeid']);
                            self::$redis->sAddExist('draw'.$draw_id.'newMobileList', $getTicketData5['mobile']);
                            self::$redis->DECR('draw'.$draw_id.'new');
                        }
                    }
                }else{
                    break;
                }
            }
        }

        $codeList=array_merge(self::$redis->sMembers('draw'.$draw_id.'oldCodeList'),self::$redis->sMembers('draw'.$draw_id.'newCodeList'));
        if(count($codeList)){
            foreach ($codeList as $val){
                self::$redis->sAddExist('drawMobile', $val);
            }
        }
        //$list=array_merge($oldList,$newList);
        Db::name('ticket_user')->where('ticket_code','in',$codeList)->update(['draw_id'=>$draw_id]);
        //数量不够 拿未分配给用户的奖券凑
//        if(count($codeList)<$draw_num){
//            $num1=$draw_num-count($codeList);
//            $getTicket=Db::name('ticket')->where(['type'=>3,'flag'=>0])->order('id desc')->limit($num1)->column('ticket');
//            if(is_array($getTicket) && count($getTicket)){
//                $codeList=array_merge($codeList,$getTicket);
//                foreach ($getTicket as $kk=>$vv){
//                    $insertData1[$kk]=array('draw_id'=>$draw_id,'mobile'=>'','ticket'=>$vv);
//                }
//                Db::name('lucky_mobile')->insertAll($insertData1);
//            }
//        }
//        if(is_array($list) && count($list)){
//            foreach ($list as $k=>$v){
//               $insertData[$k]=array('draw_id'=>$draw_id,'mobile'=>$v['mobile'],'ticket'=>$v['ticket_code']);
//            }
//            Db::name('lucky_mobile')->insertAll($insertData);
//        }
        //Db::name('ticket')->where('ticket','in',$codeList)->update(['draw_id'=>$draw_id,'flag'=>1]);
        return $codeList;
    }



    public function getRandomCode($condition){
        $ids=Db::name('ticket_user')->where($condition)->field('id')->column('id');
        if(count($ids) && is_array($ids)){
            $getRand=array_rand($ids,1);
            $codeInfo=Db::name('ticket_user')->where('id',$ids[$getRand])->field('ticket_code,storeid,mobile')->find();
            return $codeInfo;
        }
    }



    /***
     * 发送中奖短信
     * @param $mobile
     */
    public function sms($name, $mobile,$draw_type)
    {
//        if (self::$test == 'on') {
        $send['mobile'] = $mobile;
        $send['pwd'] = 'admin';
        $send['name'] = 'huangwei';
        // $code = $name;
        $code = '{"mobile":"'.$mobile.'","name":"'.$name.'"}';
        $send['type'] = 1;
        $send['template'] = $draw_type; //模板id
        $send['code'] = $code;
        $send['code2'] = $send['mobile'];
        $str = '';
        ksort($send);
        foreach ($send as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = substr($str, 0, -1);
        $key = md5($str);
        $str .= '&key=' . $key;
        $send['key'] = $key;
        $url = 'http://sms.qunarmei.com/sms.php?' . $str;
        $dat = $this->curl_get($url);
//        $dat = file_get_contents("'$url'");//发送短信
//        $dat = file_get_contents($url) ;
        $dat = json_encode(array('url'=>$url,'resp'=>$dat));
        $data = array('mobile'=>$mobile,'name'=>$name,'state'=>$dat,'log_time'=>date('Y-m-d H:i:s'));
        Db::name('lucky_draw_sms')->insert($data);
    }



    public function curl_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    //导入中奖电话号码
    public function luckyMobileImport(){
        set_time_limit(0);
        if(request()->isAjax()){
            if (!empty($_FILES)) {
                $did=input('param.did');
                Loader::import('PHPExcel.PHPExcel');
                Loader::import('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
                Loader::import('PHPExcel.PHPExcel.Reader.Excel5');
                $file = input('file.myFile');
                $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
                if ($info) {
                    try {
                        $exclePath = $info->getSaveName();  //获取文件名
                        $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
                        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                        $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                        $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                        array_shift($excel_array);  //删除标题;
                        $list = array();
                        foreach ($excel_array as $k => $v) {
                            $list[$k]['did'] = $did;
                            $list[$k]['mobile'] = chafenbacom($v[0]);
                        }
                        Db::name('draw_lucky_mobile')->insertAll($list);
                        $getList = Db::name('draw_lucky_mobile')->where('did', $did)->select();
                        $flag['code'] = 1;
                        $flag['data'] = json_encode($getList);
                        $flag['msg'] = '导入成功';
                        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                    }catch (\Exception $e){
                        $flag['code'] = 0;
                        $flag['data'] = '';
                        $flag['msg'] = $e->getMessage();
                        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                    }
                }else {
                    $flag['code'] = 0;
                    $flag['data'] = '';
                    $flag['msg'] = '文件上传失败';
                    return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
                }
            }
        }
        return $this->fetch();
    }

    /**
     * [roleEdit 删除门店配额]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function luckyMobileDelete(){
        $flag = input('param.flag',0);
        try{
            if($flag==1){
                $did = input('param.did');
                Db::name('draw_lucky_mobile')->where('did', $did)->delete();
                $flag= ['code' => 1, 'data' => '', 'msg' => '清空成功'];
            }else{
                $id = input('param.id');
                Db::name('draw_lucky_mobile')->where('id', $id)->delete();
                $flag= ['code' => 1, 'data' => '', 'msg' => '删除成功'];
            }
        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    //发送中奖短信
    public function send_message(){
        $id = input('param.id');
        $getMobile=Db::name('ticket_user')->alias('t')->join('pt_draw d','t.draw_id=d.id')->where(['t.draw_id'=>$id,'t.flag'=>1])->field('t.mobile,d.sms_id,d.draw_name,d.draw_sms')->select();
        if(is_array($getMobile) && count($getMobile)){
            foreach ($getMobile as $k=>$v){
                //$arr['mobile']=$v['mobile'];
                $arr['mobile']='15821881959';
                $arr['name']=$v['draw_sms'];
                $arr['sms_id'] = $v['sms_id'];
                \think\Queue::push( 'app\index\job\Send' , $arr,'testSend');
                $flag= ['code' => 1, 'data' => '', 'msg' => '发送成功'];
            }
        }else{
            $flag= ['code' => 0, 'data' => '', 'msg' => '发送失败'];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



}