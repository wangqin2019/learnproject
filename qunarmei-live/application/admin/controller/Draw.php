<?php

namespace app\admin\controller;
use app\admin\model\DrawModel;
use think\Db;
use think\Loader;
use think\Request;
//整合腾讯云通信扩展
use tencent_cloud\TimChat;
class Draw extends Base
{

  public function index(){
      $draw = new DrawModel();
      $list=$draw->getDrawAllByWhere();
      $this->assign('list',$list);
	  return $this->fetch();
  }

  //新增奖项
    public function add_draw(){
      if(request()->isAjax()){
          $param = input('post.');
          $draw = new DrawModel();
          $flag = $draw->insertDraw($param);
          return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
      }
      return $this->fetch();
    }
    //编辑奖项
    public function edit_draw(){
        if(request()->isAjax()){
            $param = input('post.');
            $draw = new DrawModel();
            $flag = $draw->editDraw($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id=input('param.id');
        $draw = new DrawModel();
        $info=$draw->getOneDraw($id);
        $this->assign('info',$info);
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
    public function distroy_draw(){
        try{
            Db::execute('TRUNCATE table think_lucky_draw');
            $flag= ['code' => 1, 'data' => '', 'msg' => '中奖记录已清空'];
        }catch( \PDOException $e){
            $flag= ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    //投票准备
    public function vote_ready()
    {
        $rank = input('param.rank');
        $client = stream_socket_client('tcp://139.196.232.193:5678');//生产环境
        //$client = stream_socket_client('tcp://172.16.6.163:2347');//测试环境
        //推送准备要开始的抽奖
        $data = array('uid'=>'uid1', 'flag'=>0,'rank'=>$rank);
        fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => $rank.'抽奖界面已准备！']);
    }

    //投票开始
    public function vote_begin()
    {
        $num = input('param.num');
        $draw_type = input('param.draw_type');
        $client = stream_socket_client('tcp://139.196.232.193:5678');//生产环境
        //$client = stream_socket_client('tcp://172.16.6.163:2347');//测试环境
        //获取奖池用户
       $res = Db::name('live')->field('chat_id')->where('statu=1')->order('id desc')->limit(1)->select();
       $chat_id = '@TGS#a6QCZE6ET';
        if($res)
        {
            $chat_id = $res[0]['chat_id'];
        }
        $mobileList = $this->getMobile($chat_id,$num,1);//此处第三个参数 1为获取聊天室用户 0为产生随机用户
        $counterfeitList = $this->getMobile($chat_id,$num,0);//此处第三个参数 1为获取聊天室用户 0为产生随机用户
        //shuffle($mobileList);//用户打散
        $data = array('uid'=>'uid1', 'flag'=>1,'num'=>$num,'status'=>1,'draw_type'=>$draw_type,'mobileList'=>$mobileList,'counterfeitList'=>$counterfeitList);
        fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => '前台抽奖池已启动！']);
    }

    //投票结束
    public function vote_end()
    {
        $client = stream_socket_client('tcp://139.196.232.193:5678');//生产环境
        //$client = stream_socket_client('tcp://172.16.6.163:2347');//测试环境
        $data = array('uid'=>'uid1','flag'=>1, 'num'=>'0','status'=>0,'draw_type'=>'0');
        fwrite($client, json_encode($data)."\n");
        return json(['code' => '1',  'msg' => '前台抽奖池已停止']);
    }

    //导出中奖记录
    public function export(){
        $url=config('dingding_domain')."/dingding/getstaff.shtml";
        $allDraw=Db::name('lucky_draw')->alias('l')->field('l.*,d.sms_id')->join('think_draw d','l.draw_type=d.id','left')->order('l.id')->select();
            $data=array();
            foreach ($allDraw as $k => $v) {
                $strToJson=json_encode(['mobile'=>$v['mobile']]);
                $res=dingding_curl_post($url,$strToJson);
                $res=json_decode($res,true);
                $data[$k]['prize'] = "第" . $v['prize'] . "轮";
                $data[$k]['name'] = $v['name'];
                $data[$k]['mobile'] = $v['mobile'];
                $data[$k]['draw_rank'] = $v['draw_rank'];
                $data[$k]['draw_name'] = $v['draw_name'];
                $data[$k]['sms_id'] = $v['sms_id'];
                $data[$k]['isTrue'] = $v['is_true'];
                $data[$k]['isUs'] = $res['obj']?'是':'否';
            }
            $filename = "中奖名单".date('YmdHis');
            $header = array ('抽奖轮次','中奖用户','手机号码','奖项名称','获得奖品','发送短信模版','是否有效用户','是否是我司用户');
            $widths=array('10','15','15','10','30','5','5','5');
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

    //获取聊天室在线用户号码
    public function getMobile($chat_id='',$num='',$flag)
    {
        //flag为1读取聊天室用户 为0随机生成用户
        if($flag) {
            if (!$chat_id) {
                $rest = Db::name('live')->field('chat_id')->where('db_statu=0 and statu=1 and live_source=1')->limit(1)->select();
                // end Modify by wangqin 2017-12-27
                $chat_id = $rest[0]['chat_id'];
            }
            $tent = new TimChat();
            $resp = $tent->getChatMem($chat_id);
            foreach ($resp as $k=>$v){
                $mobileList[]=$v['mobile'];
            }
        }else{
            $mobileList=$this->mobileTest(200);
        }

        //去除已中奖的用户
        $mobile_y = Db::name('lucky_draw')->column('mobile');
        if($mobile_y)
        {
            $mobileList = $this->arrCha($mobileList,$mobile_y);
        }
        return $mobileList;
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

    //随机生成测试号码
    public function mobileTest($num){
        $arr = array(
            130,131,132,133,134,135,136,137,138,139,
            144,147,
            150,151,152,153,155,156,157,158,159,
            176,177,178,
            180,181,182,183,184,185,186,187,188,189,
        );
        //循环拼接
        for($i = 0; $i < $num; $i++) {
            if($i==0){
                $tmp[] = '15821881959';
            }else{
                $tmp[] = $arr[array_rand($arr)].''.mt_rand(1000,9999).''.mt_rand(1000,9999);
            }
        }
        //去掉重复
        $phone = array_unique($tmp);
        return $phone;
    }

    //二维数组求差集
    public function arrCha($arr1,$arr2)
    {
        $arr3 = array();
        foreach ($arr1 as $key => $value) {
            if(!in_array($value,$arr2)){
                $arr3[]=$value;
            }
        }
        return $arr3;
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

}