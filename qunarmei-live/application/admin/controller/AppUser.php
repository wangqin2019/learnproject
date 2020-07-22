<?php

namespace app\admin\controller;
use think\Db;

/* App用户信息统计
 * */
class AppUser extends Base
{
    // 美容师注册url
    protected $beautyUrl = 'https://api-app.qunarmei.com/qunamei/beautytemregist';
    // 店老板注册url
    protected $bossUrl = 'https://api-app.qunarmei.com/qunamei/bossregist';
    /**
     * 功能: 公司和办事处用户统计
     * 请求: key 建议搜索
     * 返回:
     */
    public function index(){

        $title = input('title');
        $map = '';
        if($title)
        {
            //搜索标题
            $map = " and sd.st_department like '%$title%' ";
        }


        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 25;// 获取总条数

        $dt2 = time();$dt1 = $dt2-3600*24;

        $count = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,sys_department sd,sys_departbeauty_relation sdr')->where('mem.storeid=ibb.id and sd.id_department = sdr.id_department and sdr.id_beauty = ibb.id and mobile>0 and length(pwd)>0  '.$map )->field('count(0) cnt,sd.st_department,ibb.id,sd.id_department ')->group('sd.id_department')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $lists = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,sys_department sd,sys_departbeauty_relation sdr')->where('mem.storeid=ibb.id and sd.id_department = sdr.id_department and sdr.id_beauty = ibb.id and mobile>0 and length(pwd)>0  '.$map )->limit($pre,$limits)->field('count(0) cnt,sd.st_department title,ibb.sign,sd.id_department ')->group('sd.id_department')->select();

        $data = array();$data1=array();
        if($lists)
        {
            $i=1;
            foreach($lists as &$v)
            {
                //按角色统计,店老板,美容师,顾客
                $roleT = Db::table('`ims_bj_shopn_member` `mem`,`ims_bwk_branch` `ibb`,`sys_department` `sd`,`sys_departbeauty_relation` `sdr`')->field('mem.code,mem.isadmin,mem.pid,mem.staffid,mem.realname,mem.mobile,ibb.title,ibb.location_p,sdr.id_department,mem.id')->where('mem.storeid = ibb.id AND sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND mobile > 0 AND length(pwd) > 0 AND sdr.id_department='.$v['id_department'])->group('mem.mobile')->select();
                // start Modify by wangqin 2017-12-15 增加注册店铺数(客户数-门店数)
                $mdNum = Db::table('`ims_bwk_branch` `ibb`, `sys_department` `sd`, `sys_departbeauty_relation` `sdr`,`ims_bj_shopn_member` `mem`')->field('ibb.sign')->where('mem.storeid=ibb.id and mobile>0 and length(pwd)>0 and sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND sd.id_department = '.$v['id_department'])->group('sign')->order('sign desc')->count();
                $khNum = Db::table('`ims_bwk_branch` `ibb`, `sys_department` `sd`, `sys_departbeauty_relation` `sdr`,`ims_bj_shopn_member` `mem`')->field(" left(ibb.sign,7) sign1 ")->where(' mem.storeid=ibb.id and mobile>0 and length(pwd)>0 and sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND sd.id_department = '.$v['id_department'])->group('sign1')->order('sign1 desc')->count();
//                var_dump($mdNum);var_dump($khNum);
                // end Modify by wangqin 2017-12-15
                $adminNum=0;$mrNum=0;$gkNum=0;
                foreach($roleT as $vt)
                {
                    if($vt['isadmin'] == 1)
                    {
                        $adminNum +=1;
                    }elseif ($vt['code'] && ($vt['staffid']==$vt['id']))
                    {
                        $mrNum +=1;
                    }elseif(!$vt['code'])
                    {
                        $gkNum +=1;
                    }
                }

                if(strlen($v['title'])<10)
                {
                    $v['title'] .= '办事处';
                }
                $v['adminNum'] = $adminNum;
                $v['mrNum'] = $mrNum;
                $v['gkNum'] = $gkNum;
                $v['khNum'] = $khNum;
                $v['mdNum'] = $mdNum;
                $data1['id'] = $i;
                $data1['title'] = $v['title'];
                $data1['cnt'] = $v['cnt'];
                $data1['adminNum'] = $adminNum;
                $data1['mrNum'] = $mrNum;
                $data1['gkNum'] = $gkNum;
                // start Modify by wangqin 2017-12-15
                $data1['khNum'] = $khNum;
                $data1['mdNum'] = $mdNum;
                // end Modify by wangqin 2017-12-15
                $data[] = $data1;

                $v['id']=$i;
                $i++;
            }
//            echo '<pre>';print_r($data);
        }

        //导出报表
//        echo 'report:'. input('get.report');
        if(input('report') == 1)
        {
            $headerArr = array('编号ID','办事处名称','注册人数','店老板人数','美容师人数','顾客人数','客户数(注册店铺数)','门店数(注册店铺数)');
            $name = 'appUser';
            $res = reportCsv($headerArr,$data,$name);
            // $url = 'http://localhost:81/csv/';
//            //服务器
            $url = 'http://live.qunarmei.com/csv/';
            $res = $url.$res;
//            //浏览器下载
            return $res;
        }


        $this->assign('title', $title);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('lists', $lists);
//        print_r($lists);

        //翻页
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();
    }

    //查询子门店对应的注册人数
    public function storeList()
    {
        $id_department = input('id_department');

        $title = input('title');
        $map = '';
        if($title)
        {
            $map = " and (ibb.title like '%$title%' or ibb.sign like '%$title%')"  ;
        }

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 20;// 获取总条数


        $count = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,sys_department sd,sys_departbeauty_relation sdr')->where("mem.storeid=ibb.id and sd.id_department = sdr.id_department and sdr.id_beauty = ibb.id and mobile>0 and length(pwd)>0 and sd.id_department ='$id_department' ".$map )->field('count(0) cnt,sd.st_department,ibb.id,sd.id_department,ibb.title ')->group('ibb.id')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        $report = input('report');
        if($report == 1)
        {
            $limits =  $count;
        }

        $lists = Db::table('ims_bj_shopn_member mem,ims_bwk_branch ibb,sys_department sd,sys_departbeauty_relation sdr')->where("mem.storeid=ibb.id and sd.id_department = sdr.id_department and sdr.id_beauty = ibb.id and mobile>0 and length(pwd)>0 and sd.id_department ='$id_department' ".$map )->limit($pre,$limits)->field('ibb.title,ibb.sign,count(0) cnt,ibb.id ')->group('ibb.id')->select();
        $i=1;
        if($Nowpage > 1)
        {
            $i = ($Nowpage-1)*$limits+1;
        }
        foreach($lists as &$v)
        {
            //按角色统计,店老板,美容师,顾客
            $roleT = Db::table('`ims_bj_shopn_member` `mem`,`ims_bwk_branch` `ibb`')->field('mem.code,mem.isadmin,mem.pid,mem.staffid,mem.realname,mem.mobile,ibb.title,ibb.location_p,mem.id')->where("mem.storeid = ibb.id AND mobile > 0 AND length(pwd) > 0 and ibb.id={$v['id']}")->group('mem.mobile')->select();
            $adminNum=0;$mrNum=0;$gkNum=0;
            foreach($roleT as $vt)
            {
                if($vt['isadmin'] == 1)
                {
                    $adminNum +=1;
                }elseif ($vt['code'] && ($vt['staffid']==$vt['id']))
                {
                    $mrNum +=1;
                }elseif(!$vt['code'])
                {
                    $gkNum +=1;
                }
            }

            $v['adminNum'] = $adminNum;
            $v['mrNum'] = $mrNum;
            $v['gkNum'] = $gkNum;

            $v['id'] = $i ;
            $i++;
        }
        $this->assign('title', $title);
        $this->assign('id_department', $id_department);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('lists', $lists);
        //导出报表
        if(input('report') == 1)
        {
            $headerArr = array('编号ID','门店名称','门店编号','注册人数','店老板人数','美容师人数','顾客人数');
            $name = 'appUser_storeList';
            $res = reportCsv($headerArr,$lists,$name);
            // $url = 'http://localhost:81/csv/';
//            //服务器
            $url = 'http://live.qunarmei.com/csv/';
            $res = $url.$res;
            return $res;
        }

        //翻页
        if(input('get.page'))
        {
            return json($lists);
        }

        return $this->fetch();

    }

    //App用户信息明细表
    public function userDetail()
    {
        //搜索
        $title = input('title');
        $mobile = input('mobile');
        $dt1 = input('dt1')==''?date('Y-m-d',(time()-3600*24)):input('dt1');
        $dt2 = input('dt2')==''?date('Y-m-d',time()):input('dt2');
        $map = '';
        if($title)
        {
           $map = " and ibb.title like '%$title%'";
        }
        if($mobile)
        {
            $map .= " and mem.mobile like '%$mobile%'";
        }

        $dt3 = strtotime($dt1);
        $dt4 = strtotime($dt2);
        $map .= " and mem.createtime>=$dt3 and  mem.createtime<$dt4";


        $count = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb, sys_department sd, sys_departbeauty_relation sdr')->field('ibb.location_p, sd.st_department, ibb.sign, ibb.title, ibb.address, mem.realname, mem.mobile, mem.code, mem.staffid, mem.id, mem.isadmin, mem.pid')->where('mem.storeid = ibb.id AND sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND mem.mobile > 0 AND length(mem.pwd) > 0 '.$map)->count();//计算总页面

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $report = input('report');
        if($report == 1)
        {
            $limits =  $count;
        }
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $mem = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb, sys_department sd, sys_departbeauty_relation sdr')->field('ibb.location_p, sd.st_department, ibb.sign, ibb.title, ibb.address, mem.realname, mem.mobile, mem.code, mem.staffid, mem.id, mem.isadmin, mem.pid,mem.createtime')->where('mem.storeid = ibb.id AND sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND mem.mobile > 0 AND length(mem.pwd) > 0 '.$map)->limit($pre,$limits)->order('mem.createtime desc')->select();
        $data=array();$data1=array();
        if($mem)
        {
            $i=1;
            //分页时,显示每页的id
            if($Nowpage>1)
            {
                $i = ($Nowpage-1)*$limits+1;
            }
            foreach($mem as $v)
            {
                $data1['id'] = $i;
                $data1['location_p'] = $v['location_p'];
                if(strlen($v['st_department'])<10)
                {
                    $data1['st_department'] = $v['st_department'].'办事处';
                }
                $data1['sign'] = $v['sign'];
                $data1['title'] = $v['title'];
                $data1['address'] = str_replace(',','|',$v['address']);
                $data1['realname'] = @$v['realname']==''?'':@$v['realname'];
                $data1['mobile'] = @$v['mobile']==''?'':@$v['mobile'];
                //角色
                //店老板
                if($v['isadmin'] == 1)
                {
                    $data1['role'] = '店老板';
                }elseif(($v['staffid'] == $v['id']) &&  $v['code'])
                {
                    //美容师
                    $data1['role'] = '美容师';
                }elseif(!$v['code'])
                {
                    //顾客
                    $data1['role'] = '顾客';
                }
                //上级用户
                $pids = $this->getPidDetail($v['pid']);
//                echo '<pre>';print_r($pids); exit;
                $pid_name = @$pids['realname'] == ''?'':@$pids['realname'];
                $pid_mobile = @$pids['mobile'] == ''?'':@$pids['mobile'];
                $data1['pid_name'] =  $pid_name;
                $data1['pid_mobile'] =  $pid_mobile;
                //原始码
                $staffids = $this->getPidDetail($v['staffid']);
                $staffid_name = @$staffids['realname']==''?'':@$staffids['realname'];
                $staffid_mobile = @$staffids['mobile']==''?'':@$staffids['mobile'];
                $data1['staffid_name'] =  $staffid_name;
                $data1['staffid_mobile'] =  $staffid_mobile;
                $data1['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $data[] = $data1;
                $i++;
            }


        }

        $this->assign('title', $title); //当前页
        $this->assign('mobile', $mobile); //总页数
        $this->assign('dt1', $dt1);
        $this->assign('dt2', $dt2);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('lists', $data);

        //导出报表
        if(input('report') == 1)
        {
            $headerArr = array('编号ID','省份','办事处','门店编码','门店名称','地址','用户','用户手机号','角色','上级人','上级人手机号','原始码姓名','原始码手机号','注册时间');
            $name = 'appUser_userDetail';
            $res = reportCsv($headerArr,$data,$name);
            // $url = 'http://localhost:81/csv/';
//            //服务器
            $url = 'http://live.qunarmei.com/csv/';
            $res = $url.$res;
            return $res;
        }

        //翻页
        if(input('get.page'))
        {
            return json($data);
        }

        return $this->fetch();
    }

    //根据id查找用户信息
    public function getPidDetail($id='')
    {
        $ret = array();
        if($id)
        {
            $res = Db::table('ims_bj_shopn_member')->where('id='.$id)->field('realname,mobile')->limit(1)->select();
            if($res)
            {
                $ret = $res[0];

            }
        }
        return $ret;
    }

    // start Modify by wangqin 2017-12-27
    // 门店用户信息列表
    public function store_user_info()
    {
        $uid = $_SESSION['think']['uid'];
        $mobrule = new MobileRule();
        $flag_rule = $mobrule->checkRule($uid);
        //搜索
        $sign = input('sign','666-666');
        $mobile = input('mobile','');
        $role_id = input('role_id',0);
        //初始化数据
        $map = null;
        if($sign)
        {
            $map .= " and ibb.sign like '%$sign%'";
        }
        if($mobile)
        {
            $map .= " and mem.mobile like '%$mobile%'";
        }
        // 根据角色查询
        if($role_id){
            // 1店老板,2美容师,3顾客
            if($role_id == 1){
                $map .= ' and mem.isadmin=1';
            }elseif($role_id == 2){
                $map .= ' and mem.isadmin=0 and length(mem.code)>1';
            }if($role_id == 3){
                $map .= ' and mem.isadmin=0 and length(mem.code)<1';
            }
        }

        $count = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb')->field('ibb.location_p, ibb.sign, ibb.title, ibb.address, mem.realname, mem.mobile, mem.code, mem.staffid, mem.id, mem.isadmin, mem.pid')->where('mem.storeid = ibb.id'.$map)->group('mem.mobile')->count();//计算总页面

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $report = input('report');
        if($report == 1)
        {
            $limits =  $count;
        }
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $mem = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb')->field('ibb.location_p, ibb.sign, ibb.title, ibb.address, mem.realname, mem.mobile, mem.code, mem.staffid, mem.id, mem.isadmin, mem.pid,mem.createtime')->where('mem.storeid = ibb.id'.$map)->group('mem.mobile')->order('mem.createtime desc')->limit($pre,$limits)->select();
        $data=array();$data1=array();
        $data_csv = [];
        if($mem)
        {
            $i=1;
            //分页时,显示每页的id
            if($Nowpage>1)
            {
                $i = ($Nowpage-1)*$limits+1;
            }
            foreach($mem as $v)
            {
                $data1['location_p'] = $v['location_p'];
                $data1['sign'] = $v['sign'];
                $data1['title'] = $v['title'];
                $data1['address'] = str_replace(',','|',$v['address']);
                $data1['realname'] = @$v['realname']==''?'':@$v['realname'];
                $data1['mobile'] = @$v['mobile']==''?'':@$v['mobile'];
                if($flag_rule){
                    $data1['mobile'] = $mobrule->replaceMobile($data1['mobile']);
                }
                //角色
                //店老板
                if($v['isadmin'] == 1)
                {
                    $data1['role'] = '店老板';
                }elseif($v['code'])
                {
                    //美容师
                    $data1['role'] = '美容师';
                }elseif(!$v['code'])
                {
                    //顾客
                    $data1['role'] = '顾客';
                }
                $data1['pid_name'] = '';
                $data1['pid_mobile'] = '';
                $data1['staffid_name'] = '';
                $data1['staffid_mobile'] = '';
                $data1['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $data1['uid'] = $v['id'];
                $data1['id'] = $i;
                //上级用户
                $pids = $this->getPidDetail($v['pid']);
                
                if ($pids) {
                    $pid_name = @$pids['realname'] == ''?'':@$pids['realname'];
                    $pid_mobile = @$pids['mobile'] == ''?'':@$pids['mobile'];
                    $data1['pid_name'] =  $pid_name;
                    $data1['pid_mobile'] =  $pid_mobile;
                }
//                echo '<pre>';print_r($pids); exit;
                
                //原始码
                $staffids = $this->getPidDetail($v['staffid']);
                if ($staffids) {
                    $staffid_name = @$staffids['realname']==''?'':@$staffids['realname'];
                    $staffid_mobile = @$staffids['mobile']==''?'':@$staffids['mobile'];
                    $data1['staffid_name'] =  $staffid_name;
                    $data1['staffid_mobile'] =  $staffid_mobile;
                }
                if($flag_rule){
                    $data1['pid_mobile'] = $mobrule->replaceMobile($data1['pid_mobile']);
                    $data1['staffid_mobile'] = $mobrule->replaceMobile($data1['staffid_mobile']);
                }
                $data[] = $data1;
                $data2 = $data1;
                unset($data2['id']);
                unset($data2['uid']);
                $data_csv[] = $data2;
                $i++;
            }


        }
        $this->assign('role_id', $role_id);
        $this->assign('sign', $sign);
        $this->assign('mobile', $mobile);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('lists', $data);

        //导出报表
        if(input('report') == 1)
        {
            $headerArr = array('省份','门店编码','门店名称','地址','用户','用户手机号','角色','上级人','上级人手机号','原始码姓名','原始码手机号','注册时间');
            $filename = "store_user_info".date('YmdHis');
            // $widths=array('10','10','10','10','10','10','10','10','10','10','10','10');
            // echo "<pre>";print_r($data_csv);die;
            // if($data_csv) {
            //     excelExport($filename, $headerArr, $data_csv, $widths);//生成数据
            // }
            // die;
            $res = reportCsv($headerArr,$data_csv,$filename);
            $url = 'http://live.qunarmei.com/csv/';
            //服务器
            $url = 'http://live.qunarmei.com/csv/';
            $res = $url.$res;
            return $res;
        }

        //翻页
        if(input('get.page'))
        {
            return json($data);
        }

        return $this->fetch();
    }
    // end Modify by wangqin 2017-12-27
    /**
     * 添加用户
     */
    public function membersAdd()
    {
        $submit = input('submit',0);
        if($submit){
            $param = input();
            $data = [
                'mobile' => isset($param['mobile'])?$param['mobile']:'',
                'sign' => $param['sign'],
                'bossmobile' => isset($param['boss_mobile'])?
$param['boss_mobile']:'',
            ];
            // 注册美容师
            if($data['mobile']){
                $mapm['b.sign'] = $data['sign'];
                $mapm['isadmin'] = 1;
                $resadmin = Db::table('ims_bj_shopn_member m')->join(['ims_bwk_branch'=>'b'],['m.storeid=b.id'],'LEFT')->where($mapm)->limit(1)->find();
                if($resadmin){
                    $datam['mobile'] = $data['mobile'];
                    $datam['bossmobile'] = $resadmin['mobile'];
                    $res = curl_post_https($this->beautyUrl,$datam);
                    if($res){
                        $res = json_decode($res,true);
//                    echo '<pre>';print_r($res);die;
                        if($res['code'] == 'S_000001' && empty($res['obj'])){
                            return json(array('code'=>1,'data' => '','msg' => '美容师注册成功'));
                        }else{
                            $msg = '';
                            if($res['obj'] && isset($res['obj'][0])){
                                $msg = $res['obj'][0];
                            }
                            return json(array('code'=>0,'data' => '','msg' => '注册失败-'.$res['msg'].'-'.$msg));
                        }
                    }
                }
            }
            // 注册店老板
            if($data['bossmobile']){
                $data = [
                    'mobile' => $param['boss_mobile'],
                    'sign' => $param['sign'],
                ];
                $res = curl_post_https($this->bossUrl,$data);
                if($res){
                    $res = json_decode($res,true);
                    if($res['code'] == 'S_000001'){
                        return json(array('code'=>1,'data' => '','msg' => '店老板注册成功'));
                    }else{
                        return json(array('code'=>0,'data' => '','msg' => '注册失败-'.$res['msg']));
                    }
                }
            }
            // 注册顾客
            // xxxx
            // 记录用户行为日志
            $baseSer = new \app\admin\service\BaseSer();
            $user_id = $_SESSION['think']['uid'];
            $mobile = '';
            if($data['mobile']){
                $mobile = $data['mobile'];
            }else{
                $mobile = $data['bossmobile'];
            }
            $msg = '添加用户-'.$mobile;
            $baseSer->writeLog($user_id,$msg);
        }
        return $this->fetch();
    }

    /**
     * 删除用户
     */
    public function membersDel()
    {
        $id = input('param.id');
        if($id) {
            $map['id'] = $id;
            $res = Db::table('ims_bj_shopn_member')->where($map)->delete();
        }
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }

    /**
     * 编辑用户
     */
    public function membersEdit()
    {
        $id = input('id',0);
        $submit = input('submit');
        $list = Db::table('ims_bj_shopn_member m')
            ->join(['ims_bwk_branch'=>'b'],['b.id=m.storeid'],'LEFT')
            ->where('m.id',$id)
            ->limit(1)
            ->find();
//            echo '<pre>';print_r($list);die;
        if($submit){
            $param = input();
            $id = $param['id'];

            $res = Db::table('ims_bwk_branch')->where('sign',$param['sign'])->limit(1)->find();
            if($res){
                $data = [
                    'storeid' => $res['id']
                ];
                // 修改角色role_id,1:店老板,2:美容师,3:顾客
                if($param['role_id'] == 1){
                    // 原店老板改为美容师
//                    $map_boss['isadmin'] = 1;
//                    $map_boss['storeid'] = $res['id'];
//                    $data_boss['isadmin'] = 0;
//                    $data_boss['code'] = 'd8ontu'.$res['id'];
//                    Db::table('ims_bj_shopn_member')->where($map_boss)->update($data_boss);
                    // 用户改为店老板
                    $map_boss1['id'] = $id;
                    $data_boss1['isadmin'] = 1;
                    $data_boss1['code'] = '';
                    $data_boss1['pid'] = 0;
                    $data_boss1['staffid'] = $id;
                    Db::table('ims_bj_shopn_member')->where($map_boss1)->update($data_boss1);
                }elseif($param['role_id'] == 2){
                    $map_mrs['id'] = $id;
                    $data_mrs['pid'] = 0;
                    $data_mrs['staffid'] = $id;
                    $data_mrs['isadmin'] = 0;
                    $data_mrs['code'] = 'd8ontu'.$res['id'];
                    Db::table('ims_bj_shopn_member')->where($map_mrs)->update($data_mrs);
                }
                Db::table('ims_bj_shopn_member')->where('id',$id)->update($data);
            }
            // 记录用户行为日志
            $baseSer = new \app\admin\service\BaseSer();
            $user_id = $_SESSION['think']['uid'];
            $msg = '修改用户-'.$list['mobile'];
            $baseSer->writeLog($user_id,$msg);
            return json(array('code'=>1,'data' => '','msg' => '修改成功'));
        }
//        echo '<pre>';print_r($list);die;
        if($list){
            $list['role_id'] = 3;
            if($list['isadmin'] == 1){
                $list['role_id'] = 1;
            }elseif(strlen($list['code']) > 0){
                $list['role_id'] = 2;
            }
        }
        $this->assign('id', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 顾客转移
     */
    public function membersMoveBeauty()
    {
        $submit = input('submit');
        if($submit){
            $mobiles1 = input('mobile');
            $beauty_mobile = input('beauty_mobile');
            $msg = '';
            // 1.查询上级是否是美容师
            $map['mobile'] = $beauty_mobile;
            $res = Db::table('ims_bj_shopn_member')->where($map)->limit(1)->find();
            if($res && strlen($res['code'])>1){
                $mobiles = explode(',', $mobiles1);
                if (is_array($mobiles) && $mobiles) {
                    $map_gk['mobile'] = ['in',$mobiles];
                    $data['storeid'] = $res['storeid'];
                    $data['pid'] = $res['id'];
                    $data['staffid'] = $res['id'];
                    $res1 = Db::table('ims_bj_shopn_member')->where($map_gk)->update($data);
                    if($res1){
                        // 记录用户行为日志
                        $baseSer = new \app\admin\service\BaseSer();
                        $user_id = $_SESSION['think']['uid'];
                        $msg = '顾客转移-'.$mobiles1.'-新美容师:'.$beauty_mobile;
                        $baseSer->writeLog($user_id,$msg);
                        return json(array('code'=>1,'data' => '','msg' => '顾客转移成功'));
                    }else{
                        $msg = '修改数据库失败';
                    }
                }else{
                    $msg = '顾客号码格式错误';
                }
            }else{
                $msg = '上级不是美容师';
            }
            return json(array('code'=>0,'data' => '','msg' => '顾客转移失败-'.$msg));
        }
        return $this->fetch();
    }

    /**
     * 批量修改为美容师
     */
    public function updateMemberToBeauty()
    {
        $flag = 0;
        $submit = input('submit');
        if($submit){
            $mobiles = input('mobile');// 多个号码以,分割
            $sign = input('sign');
            if(strpos($mobiles,',')){
                $mobile = explode(',',$mobiles);
            }else{
                $mobile[] = $mobiles;
            }
            $storeid = 0;
            if($sign){
                $mapb['sign'] = $sign;
                $res_branch = Db::table('ims_bwk_branch')->where($mapb)->limit(1)->find();
                if($res_branch){
                    $storeid = $res_branch['id'];
                }
            }
            $map['mobile'] = ['in',$mobile];
            $res = Db::table('ims_bj_shopn_member')->where($map)->select();
            if($res){
                foreach ($res as $v) {
                    $datau['pid'] = 0;
                    $datau['staffid'] = $v['id'];
                    $datau['isadmin'] = 0;
                    $datau['code'] = 'd8ontu'.$v['storeid'];
                    if($storeid){
                        $datau['storeid'] = $storeid;
                    }
                    $mapu['id'] = $v['id'];
                    $res1 = Db::table('ims_bj_shopn_member')->where($mapu)->update($datau);
                    if($res1){
                        // 记录用户行为日志
                        $baseSer = new \app\admin\service\BaseSer();
                        $user_id = $_SESSION['think']['uid'];
                        $msg = '修改用户-'.$mobiles;
                        $baseSer->writeLog($user_id,$msg);
                        $flag = 1; 
                    }
                }
            }
            if ($flag == 1) {
                return json(array('code'=>1,'data' => '','msg' => '美容师修改成功'));
            }else{
                return json(array('code'=>1,'data' => '','msg' => '美容师修改失败'));
            }
        }
        return $this->fetch();
    }

    /**
     * 生成更多邀请码
     */
    public function make_invitecode()
    {
        $sign = input('sign');
        $arr = [
            'code' => 1,
            'msg' => '生成失败',
            'data' => [],
        ];
        // 查询门店id
        $mapb['sign'] = $sign;
        $resbranch = Db::table('ims_bwk_branch')->where($mapb)->limit(1)->find();
        // 查询是否有邀请码记录
        if($resbranch){
            $mapi['storeid'] = $resbranch['id'];
            $resi = Db::table('ims_bj_shopn_invitecode')->where($mapi)->limit(1)->find();
            if($resi){
                // 修改
                $data['codes'] = str_replace('"isused":1','"isused":0',$resi['codes']);
                Db::table('ims_bj_shopn_invitecode')->where($mapi)->update($data);
                $arr['msg'] = '邀请码修改成功';
            }else{
                $codes = '[{"isused":0,"code":"ho5iva0002"},{"isused":0,"code":"wm760l0002"},{"isused":0,"code":"kmyrra0002"},{"isused":0,"code":"at9ajr0002"},{"isused":0,"code":"cyu0i50002"},{"isused":0,"code":"6qk1y50002"},{"isused":0,"code":"1lhyh80002"},{"isused":0,"code":"asky910002"},{"isused":0,"code":"z9lyav0002"},{"isused":0,"code":"gdk03s0002"},{"isused":0,"code":"w086rx0002"},{"isused":0,"code":"1i897g0002"},{"isused":0,"code":"88yiwy0002"},{"isused":0,"code":"amylay0002"},{"isused":0,"code":"7h2h7v0002"},{"isused":0,"code":"0tmyq30002"},{"isused":0,"code":"mibli90002"},{"isused":0,"code":"sg7hou0002"},{"isused":0,"code":"6p6u170002"},{"isused":0,"code":"jyeb5c0002"},{"isused":0,"code":"xvw9kc0002"},{"isused":0,"code":"3xl59t0002"},{"isused":0,"code":"4rz1ye0002"},{"isused":0,"code":"mutifl0002"},{"isused":0,"code":"gp9kr50002"},{"isused":0,"code":"9yzbgl0002"},{"isused":0,"code":"n3w3gs0002"},{"isused":0,"code":"gjqbuh0002"},{"isused":0,"code":"7lbrdm0002"},{"isused":0,"code":"d9lntq0002"}]';
                // 插入
                $data['weid'] = 1;
                $data['storeid'] = $resbranch['id'];
                $data['codes'] = str_replace('0002',sprintf('%04d',$resbranch['id']),$codes);
                $data['numbers'] = 20;
                $data['createtime'] = time();
                Db::table('ims_bj_shopn_invitecode')->insert($data);
                $arr['msg'] = '邀请码添加成功';
            }
        }
        return json($arr);
    }
}