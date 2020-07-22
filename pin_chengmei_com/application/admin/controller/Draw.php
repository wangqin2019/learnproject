<?php

namespace app\admin\controller;
use app\admin\model\DrawModel;
use com\Gateway;
use think\Cache;
use think\Db;
use think\Loader;

class Draw extends Base
{
    static protected $luckyCode = array();
    protected $draw_flag=0;//测试时候改为0 正式改为1
    protected $draw_table;
    protected $draw_ticket;
    public function _initialize() {
        parent::_initialize();
        //检测当前抽奖是正式还是测试
        $get_draw_flag=Cache::get('drawAmbient');
        $drawAmbient=$get_draw_flag?$get_draw_flag:$this->draw_flag;
        $this->draw_flag=$drawAmbient;
        if($drawAmbient==2){
            $this->draw_table='ticket_user';
            $this->draw_ticket='ticket';
        }else{
            $this->draw_table='ticket_user1';
            $this->draw_ticket='ticket1';
        }
        $this->assign('drawAmbient', $this->draw_flag);
    }
  public function index(){
      //$get_type=Db::name('draw_scene')->where('scene_check',1)->value('scene_prefix');
      //$cur=$get_type?$get_type:1;
      $draw_type=input('param.draw_type',1);
      $this->assign('d_type',$draw_type);
      //获取持券人数
      $ticketMan=Db::name($this->draw_table)->where(['type'=>23])->group('mobile')->field('id')->select();
      $this->assign('ticketMan',count($ticketMan));
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
        $info['codeList']=Db::name('draw_lucky_code')->where('did',$info['id'])->select();
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
            //$list[$k]['count']=Db::name('lucky_mobile')->where($map1)->count();
			$list[$k]['draw_status']=$v['draw_status']?'已抽':'';
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

    //当前抽奖轮次奖品介绍
    public function vote_intro()
    {
        $id = input('param.id');
        $getDrawInfo=Db::name('draw')->where('id',$id)->field('id,draw_flag')->find();
        //$client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
        //推送准备要开始的抽奖
        $data = array('scene'=>'draw','flag'=>-1,'rank'=>$getDrawInfo);
        Gateway::sendToGroup('live',json_encode($data));
        //fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => '奖品介绍已推送！']);
    }


    //抽奖准备
    public function vote_ready()
    {
        $id = input('param.id');
        $getDrawInfo=Db::name('draw')->where('id',$id)->field('id,draw_flag')->find();
        //$client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
        //推送准备要开始的抽奖
        $data = array('scene'=>'draw', 'flag'=>0,'rank'=>$getDrawInfo,'type'=>'one');
        Gateway::sendToGroup('live',json_encode($data));
        //fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => '抽奖界面已准备！']);
    }

    //抽奖开始
    public function vote_begin()
    {
        $draw_id = input('param.draw_id');//抽奖id
        //获取奖池奖券
		$getDrawInfo=Db::name('draw')->where('id',$draw_id)->find();
        if($getDrawInfo['draw_status']){
            return json(['code' => '0',  'msg' => '当前奖项已经抽过了,请勿重复抽取！']);
        }else{
            //$client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
//            if($draw_id==65){
//                //获取app上直播间美容师
//                $app_seller=$this->get_app_chat_user();
//                //获取小程序上直播间美容师
//                $xcx_seller=Db::name('user_stay')->where('role',1)->group('mobile')->column('mobile');
//                $lucky_seller=array_merge($app_seller,$xcx_seller);
//                $unique=array_unique($lucky_seller);//去重
//                $unique1=array_values($unique);//重新索引数组
//                $phone_count=count($lucky_seller);
//                if($phone_count){
//                    self::$redis->set('lucky_mobile', json_encode($unique1));//将美容师存入redis
//                }
//                if($phone_count<100){
//                    $need_num=100-intval($phone_count);
//                    $need_arr= $this->rand_phone($need_num);
//                    $lucky_seller1=array_merge($unique1,$need_arr);
//                    $unique2=array_unique($lucky_seller1);//去重
//                    $unique1=array_values($unique2);//重新索引数组
//                }
//                $prizeList = $unique1;
//                $tickList = $this->rand_phone(100);
//            }elseif($draw_id==66){
//                $prizeList = $this->getSunTicket($draw_id);//获取中奖奖券
//                $tickList = $this->getTicket($draw_id,0);//1为获取中奖用户 0为获取随机滚动奖券
//            }else{
                $prizeList = $this->getTicket($draw_id,1);//1为获取中奖奖券
                $tickList = $this->getTicket($draw_id,0);//1为获取中奖用户 0为获取随机滚动奖券
//            }
            $data = array('scene'=>'draw', 'flag'=>1,'num'=>$getDrawInfo['draw_num'],'status'=>1,'draw_id'=>$draw_id,'prizeList'=>$prizeList,'tickList'=>$tickList,'type'=>'one');
            //fwrite($client, json_encode($data)."\n");
            Gateway::sendToGroup('live',json_encode($data));
            return json(['code' => '1',  'msg' => '前台抽奖池已启动！']);
        }
    }

//    public function get_app_chat_user(){
//      $url='http://live.qunarmei.com/api_public/live/getLiveMrs';
////      $url='http://testc.qunarmei.com:9091/api_public/live/getLiveMrs';
//      $res=file_get_contents($url);
//      $resArr=json_decode($res,true);
//      $result=[];
//      if($resArr['data'] && is_array($resArr['data'])){
//          $result=$resArr['data'];
//      }
//      return $result;
//    }


    //抽奖结束
    public function vote_end()
    {
//      $client = stream_socket_client('tcp://139.196.113.127:2349');//生产环境
        $data = array('scene'=>'draw','flag'=>1, 'num'=>'0','status'=>0,'draw_type'=>'0','type'=>'one');
        Gateway::sendToGroup('live',json_encode($data));
//        fwrite($client, json_encode($data)."\n");
        $draw_id = input('param.draw_id');//抽奖id
        if($draw_id){
            Db::name('draw')->where('id', $draw_id)->update(['draw_status' => 1]);
//            if($draw_id==65){
//                $this->send_blink($draw_id);
//            }elseif($draw_id==66){
//                $this->update_sun_user($draw_id);
//            }else {
                $this->update_user($draw_id);
//            }
        }
        return json(['code' => '1',  'msg' => '前台抽奖池已停止']);
    }

    //发送阳光普照奖品
//    public function getSunTicket($draw_id){
//        $drawInfo=Db::name('draw')->field('id,draw_num,old_draw_num')->where('id',$draw_id)->find();
//        self::$redis->DEL('draw'.$drawInfo['id'].'codeList');
//        $notAllowMobile=config('draw_not_allow');
//        $notAllowMobileArr=explode('#',$notAllowMobile);
//        $map1['type']=array('eq',22);
//        $map1['flag']=array('eq',0);
//        $map1['mobile']=array('not in',$notAllowMobileArr);
//        $getAllMember = Db::name('ticket_user')->where($map1)->field('mobile,ticket_code')->group('mobile')->select();
//        self::$redis->set('actualDrawNum'.$drawInfo['id'], count($getAllMember));//将当前奖项实际抽奖数量存储
//        foreach ($getAllMember as $k=>$v){
//            self::$redis->sadd('draw' . $drawInfo['id'] . 'codeList', $v['ticket_code']);
//        }
//        $codeList=self::$redis->SRANDMEMBER('draw'.$drawInfo['id'].'codeList',150);
//        return $codeList;
//    }


//    public function SunTicketByHand($draw_id){
//        $drawInfo=Db::name('draw')->field('id,draw_num,old_draw_num')->where('id',$draw_id)->find();
//        self::$redis->DEL('draw'.$drawInfo['id'].'codeList');
//        $notAllowMobile=config('draw_not_allow');
//        $notAllowMobileArr=explode('#',$notAllowMobile);
//        $map1['type']=array('eq',22);
//        $map1['flag']=array('eq',0);
//        $map1['mobile']=array('not in',$notAllowMobileArr);
//        $getAllMember = Db::name('ticket_user')->where($map1)->field('mobile,ticket_code')->group('mobile')->select();
//        self::$redis->set('actualDrawNum'.$drawInfo['id'], count($getAllMember));//将当前奖项实际抽奖数量存储
//        foreach ($getAllMember as $k=>$v){
//            self::$redis->sadd('draw' . $drawInfo['id'] . 'codeList', $v['ticket_code']);
//            self::$redis->sadd('draw' . $drawInfo['id'] . 'codeList-1', $v['ticket_code']);
//        }
//        echo "女王专属礼包发放成功";
//        die();
//    }



//    /**
//     * 给中奖美容师 发送盲盒
//     */
//    public function send_blink($draw_id){
//        $lucky=self::$redis->get('lucky_mobile');
//        $lucky=json_decode($lucky,true);
//        //生成盒子记录
//        try {
//            if (count($lucky)) {
//                $insert = [];
//                $i=0;
//                foreach ($lucky as $v) {
//                    if($i<100) {
//                        $uid = Db::table('ims_bj_shopn_member')->where('mobile', $v)->value('id');
//                        $blinks = generate_promotion_code('', 1, '', 8);
//                        $insert[] = [
//                            'order_id' => 0,
//                            'uid' => $uid,
//                            'blinkno' => $uid . $blinks[0],
//                            'goods_id' => 188,
//                            'price' => 20.2,
//                            'status' => 0,
//                            'is_give' => 0,//未赠送
//                            'is_pay' => 1,//未支付
//                            'source' => 2,
//                            'create_time' => time(),
//                            'update_time' => time()
//                        ];
//                        logs(date('Y-m-d H:i:s')."：".json_encode($insert),'test_send_blink');
//                    }else{
//                        break;
//                    }
//                    $i++;
//                }
//                Db::name('blink_order_box')->insertAll($insert);
//                Db::name('draw')->where('id', $draw_id)->update(['draw_status' => 1]);
//                return true;
//            }
//        }catch (\Exception $e){
//                return false;
//        }
//    }

    //发放阳光普照奖项
//    public function update_sun_user($draw_id){
//        self::$redis->DEL('actualDrawNum'.$draw_id);
//        $list=self::$redis->sMembers('draw'.$draw_id.'codeList');
//        if($list) {
//            $draw_code=self::$redis->exists('draw' . $draw_id . 'codeList-1');
//            if(!$draw_code) {
//                foreach ($list as $k => $v) {
//                    self::$redis->sadd('draw' . $draw_id . 'codeList-1', $v);
//                }
//            }

//            $time = date('Y-m-d H:i:s');
//            $draw = Db::name('draw')->find($draw_id);
//            Db::startTrans();
//            try {
//                $x=0;
//                $data = array('status' => 1, 'flag' => 1, 'update_time' => $time, 'draw_rank' => $draw['draw_rank'], 'draw_name' => $draw['draw_name']);
//                foreach ($list as $k=>$v){
//                    $map0['ticket_code'] = array('in', $list);
//                    Db::name('ticket_user')->where($map0)->update($data);
//                    Db::name('ticket_user')->where($map0)->find();
//                    echo Db::name('ticket_user')->getLastSql();
//                    if($x%200==0){
//                        Db::commit();
//                        Db::startTrans();
//                    }
//                    $x++;
//                }
//                Db::commit();
//            }catch (\Exception $e){
//                Db::rollback();
//            }
//            Db::name('draw')->where('id', $draw_id)->update(['draw_status' => 1]);
//        }
//        return true;
//    }


	    /***
     * 修改中奖用户的信息，反馈中奖用户的完整信息
     * @param $mobiles
     */
    public function update_user($draw_id)
    {
        $info=Db::name('draw')->where('id', $draw_id)->find();
        if($info['lucky_role']){
            $list=Db::name('draw_lucky_code')->where('did',$draw_id)->field('did,title,code,name')->select();
            if(count($list)) {
                foreach ($list as $v) {
                    $arr['scene'] = 'draw_code';
                    $arr['data'] = ['draw_table' =>$this->draw_table,'draw_code' =>$v['code'],'draw_data' => $v];
                    \think\Queue::push('app\index\job\MyQueue', $arr, 'my_queue');
                }
            }
        }else{
            $lucky=self::$redis->get('lucky_draw_'.$draw_id);
            $list=json_decode($lucky,true);
            if($list) {
                //堵塞方式处理
                //$this->send_draw_goods($draw,$list);
                //队列方式处理
                foreach ($list as $k=>$v) {
                    $arr['scene'] = 'draw_mobile';
                    $arr['data'] = ['draw_table' =>$this->draw_table,'draw_id' => $draw_id, 'draw_code' => $v];
                    \think\Queue::push('app\index\job\MyQueue', $arr, 'my_queue');
                }
            }
        }
        return true;
    }

    //发奖 改变奖券状态
    public function send_draw_goods($draw_id,$list){
        set_time_limit(0);
        Db::startTrans();
        try {
            $drawInfo = Db::name('draw')->where('id',$draw_id)->find();
            foreach ($list as $k=>$v) {
                $map['stock'] = array('gt', 0);
                $map['fid'] = array('eq', $drawInfo['id']);
                $list = Db::name('draw_goods')->field('id,name,stock')->where($map)->select();
                if ($list) {
                    foreach ($list as $key => $val) {
                        $arr[$val['id']] = $val['stock'];
                    }
                    $coupon_id = comm_getRand($arr); //根据概率获取奖品id
                    $draw = Db::name('draw_goods')->find($coupon_id);
                    $data = array('status' => 1, 'flag' => 1, 'update_time' => date('Y-m-d H:i:s'), 'draw_rank' => $drawInfo['draw_rank'], 'draw_name' => $draw['name'],'draw_pic'=>'http://ml.chengmei.com/jp1_0416.png');
                    $map0['ticket_code'] = array('eq', $v);
                    $res = Db::name($this->draw_table)->where($map0)->update($data);
                    if ($res) {
                        Db::name('draw_goods')->where('id', $coupon_id)->setDec('stock');
                    }
                    if ($k % 100 == 0) {
                        Db::commit();
                        Db::startTrans();
                    }
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
        }
    }

    //导出中奖记录
    public function export(){
        $allDraw=Db::name($this->draw_table)->where(['flag'=>1,'type'=>23])->order('id desc')->select();
            $data=array();
            foreach ($allDraw as $k => $v) {
                $data[$k]['depart'] = $v['depart'];
                $data[$k]['branch'] = $v['branch'];
                $data[$k]['sign'] = $v['sign'];
                $data[$k]['mobile'] = $v['mobile'];
                $data[$k]['code'] = ' '.$v['ticket_code'];
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

    //报名通道结束 存储所有用户信息
    public function join_close(){
        $is_ok=input('param.is_ok',0);
        $close_flag=self::$redis->exists('channel_close_flag');
        if(!$close_flag || ($is_ok==1 && $close_flag)) {
            self::$redis->DEL('drawMobileList');
            $notAllowMobile=config('draw_not_allow');
            $notAllowMobileArr=explode('#',$notAllowMobile);
            $vip=Db::name('draw_lucky_mobile')->column('mobile');
            if($vip){
                $notAllowMobileArr=array_merge($notAllowMobileArr,$vip);
            }
            $map1['type']=array('eq',23);
            $map1['flag']=array('eq',0);
            $map1['mobile']=array('not in',$notAllowMobileArr);
            $getAllMember = Db::name($this->draw_table)->where($map1)->field('mobile,count(id) as count')->group('mobile')->select();
            if (count($getAllMember)) {
                self::$redis->set('channel_close_flag', 1);
                foreach ($getAllMember as $val) {
                    //if($val['count']>1){
                        self::$redis->sadd('drawMobileList', $val['mobile']);
                    //}
                }
            }
            return json(['code' => 1, 'data' => '', 'msg' => '抽奖用户已存储成功']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => '是否确认二次存储？']);
        }
    }

    //获取奖券号码
    public function getTicket($draw_id,$flag)
    {
        //flag为1生成本次中奖券号码 为0生成滚送测试号码
        if($flag){
            $drawInfo=Db::name('draw')->field('id,draw_num,old_draw_num')->where('id',$draw_id)->find();
            if($drawInfo){
                $ticketList=$this->luckyTicket($drawInfo);
            }
        }else{
            $map['type'] = array('eq', '23');
            $map['flag'] = array('eq', 0);
            $ticketList = Db::name($this->draw_ticket)->where($map)->limit(150)->column('ticket');
        }
        return $ticketList;
    }


    //获取中奖奖券
    public function luckyTicket($drawInfo){
        $old_draw_num=$drawInfo['old_draw_num'];//将当前奖项实际抽奖数量存储
        //1、如果当前奖项设置有vip名单 则先在vip名单中读取中奖电话
        $getVipMobile=Db::name('draw_lucky_mobile')->where(['did'=>$drawInfo['id'],'status'=>1])->column('mobile');
        if($getVipMobile){
            foreach ($getVipMobile as $val){
                if($old_draw_num){
                    $map['mobile']=array('eq',$val);
                    $map['type']=array('eq',23);
                    $map['flag']=array('eq',0);
                    $getTicketData=$this->getRandomCode($map);
                    if($getTicketData) {
                        array_push( self::$luckyCode, $getTicketData['ticket_code']);
                        self::$redis->sadd('draw_lucky_mobile', $getTicketData['mobile']);
                        $old_draw_num--;
                    }
                }
            }
        }

        //2、如果顾客vip名单中的数量不满足抽奖数量，随机抽取未中奖的用户奖券（要遵循用户不能重复中奖的规则）
        $getNum=$old_draw_num;
        if($getNum){
            $count=self::$redis->scard('drawMobileList');
            if($count){
                for($i=0;$i<$getNum;$i++){
                    $rMobile = self::$redis->spop('drawMobileList');
                    if($rMobile) {
                        if (!self::$redis->sismember('draw_lucky_mobile', $rMobile)) {
                            $map7['type'] = array('eq', 23);
                            $map7['flag'] = array('eq', 0);
                            $map7['mobile'] = array('eq', $rMobile);
                            $getTicketData3 = $this->getRandomCode($map7);
                            if ($getTicketData3) {
                                array_push(self::$luckyCode, $getTicketData3['ticket_code']);
                                self::$redis->sadd('draw_lucky_mobile', $getTicketData3['mobile']);
                                $old_draw_num--;
                            } else {
                                $i--;
                                continue;
                            }
                        } else {
                            $i--;
                            continue;
                        }
                    }else{
                       break;
                    }
                }
            }
        }
        self::$redis->set('lucky_draw_'.$drawInfo['id'], json_encode(self::$luckyCode));//将中奖记录存入redis
        //数量不够 拿未分配给用户的奖券凑
        $drawCount=$drawInfo['draw_num'];
        $num=$drawCount-count(self::$luckyCode);
        if($num){
            $getTicket=Db::name($this->draw_ticket)->where(['type'=>23,'flag'=>0])->order('id desc')->limit($num)->column('ticket');
            if(is_array($getTicket) && count($getTicket)){
				Db::name($this->draw_ticket)->where('ticket', 'in', $getTicket)->update(['flag' => 1, 'draw_id' => $drawInfo['id']]);
            }
            $newCodeList=array_merge(self::$luckyCode,$getTicket);
        }else{
            $newCodeList=self::$luckyCode;
        }
        $codeList=array_rand($newCodeList,$drawCount<150?$drawCount:150);
        $showCodes=[];
        foreach ($codeList as $a) {
            $showCodes[]=$newCodeList[$a];
        }
        return $showCodes;
    }


    //从用户的未抽奖券中随机拿出来一个
    public function getRandomCode($condition){
        $tickets=Db::name($this->draw_table)->where($condition)->field('ticket_code,storeid,mobile')->select();
        if(count($tickets) && is_array($tickets)){
            $getRand=array_rand($tickets,1);
            return $tickets[$getRand];
        }else{
            return false;
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

    //导入部分中奖电话号码
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

    //导入中奖电话号码
    public function appointLuckyUserImport(){
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
                            $list[$k]['title'] = trim($v[0]);
                            $list[$k]['code'] = trim($v[1]);
                            $list[$k]['name'] = trim($v[2]);
                        }
                        Db::name('draw_lucky_code')->insertAll($list);
                        $getList = Db::name('draw_lucky_code')->where('did', $did)->select();
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
     * [appointLuckyMobileDelete 清空预设中奖号码]
     * @return [type] [description]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function appointLuckyMobileDelete(){
        try{
            $did = input('param.did');
            Db::name('draw_lucky_code')->where('did', $did)->delete();
            $flag= ['code' => 1, 'data' => '', 'msg' => '清空成功'];

        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
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
        $getMobile=Db::name($this->draw_table)->alias('t')->join('pt_draw d','t.draw_id=d.id')->where(['t.draw_id'=>$id,'t.flag'=>1])->field('t.mobile,d.sms_id,d.draw_name,d.draw_sms')->select();
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

    //随机手机号
    public function rand_phone($num){
        $tel_arr = array(
            '130','131','132','133','134','135','136','137','138','139','144','147','150','151','152','153','155','156','157','158','159','176','177','178','180','181','182','183','184','185','186','187','188','189',
        );
        for($i = 0; $i < $num; $i++) {
            $tmp[] = $tel_arr[array_rand($tel_arr)].mt_rand(1000,9999).mt_rand(1000,9999);
        }
        $s= array_unique($tmp);
         return $s;
    }



    /**
     * 抽奖奖品配置
     */
    public function drawGoods(){

        $fid = input('fid');
        $map = [];
        $map['fid'] = ['eq',$fid];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('draw_goods')->where($map)->count(); //总数据
        $allpage = intval(ceil($count / $limits));
        $lists =Db::name('draw_goods')->where($map)->page($Nowpage, $limits)->order('id')->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('fid', $fid);
        $drawName=Db::name('draw')->where('id',$fid)->value('draw_name');
        $this->assign('drawName', $drawName);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [roleAdd 添加抽奖奖品]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function drawGoodsAdd(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result =  Db::name('draw_goods')->insert($param);
                if(false === $result){
                    $res= ['code' => -1, 'data' => '', 'msg' => '添加中奖产品失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '添加中奖产品成功'];
                }
            }catch( \PDOException $e){
                $res= ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }
        $fid=input('param.fid');
        $this->assign('fid', $fid);
        $drawName=Db::name('draw')->where('id',$fid)->value('draw_name');
        $this->assign('drawName', $drawName);
        return $this->fetch();
    }



    /**
     * [roleEdit 编辑抽奖奖品]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function drawGoodsEdit(){
        if(request()->isAjax()){
            $param = input('post.');
            try{
                $result = Db::name('draw_goods')->where('id',$param['id'])->update($param);
                if(false === $result){
                    $res= ['code' => 0, 'data' => '', 'msg' => '维护中奖产品失败'];
                }else{
                    $res= ['code' => 1, 'data' => '', 'msg' => '维护中奖产品成功'];
                }
            }catch(\PDOException $e){
                $res= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
            }
            return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
        }

        $fid=input('param.fid');
        $this->assign('fid', $fid);
        $drawName=Db::name('draw')->where('id',$fid)->value('draw_name');
        $this->assign('drawName', $drawName);

        $id = input('param.id');
        $info= Db::name('draw_goods')->where('id', $id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }
    /**
     * [drawGoodsDel 编辑抽奖奖品]
     * @author [侯典敬] [451035207@qq.com]
     */
    public function drawGoodsDel(){
        $id = input('param.id');
        try{
            Db::name('draw_goods')->where('id', $id)->delete();
            $flag= ['code' => 1, 'data' => '', 'msg' => '产品删除成功'];
        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    //改变抽奖环境
    public function drawAmbient(){
        $status=input('param.status');
        if($status){
            Db::name('draw')->where('draw_status',1)->update(['draw_status'=>0]);
            Cache::set('drawAmbient',$status);
            return json(['code' => 1, 'data' => '', 'msg' => '环境切换成功']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => '请选择抽奖环境']);
        }
    }
}