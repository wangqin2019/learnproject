<?php

namespace app\admin\controller;
use think\Db;

/* App用户信息统计
 * */
class AppUserAct extends Base
{
    //App登录中奖用户
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
        $map .= " and win.win_create_time>='$dt1' and  win.win_create_time<'$dt2'";


        $count = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb, sys_department sd, sys_departbeauty_relation sdr,think_activities_winning win,think_activities_prize pri')->field('ibb.location_p, sd.st_department, ibb.sign, ibb.title, ibb.address, mem.realname, mem.mobile, mem.code, mem.staffid, mem.id, mem.isadmin, mem.pid ,pri.prize_name,win.win_create_time')->where('mem.storeid = ibb.id AND sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND mem.mobile > 0 AND length(mem.pwd) > 0 and win.user_id=mem.id and win.prize_id=pri.id '.$map)->count();//计算总页面

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $report = input('report');
        if($report == 1)
        {
            $limits =  $count;
        }
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $mem = Db::table('ims_bj_shopn_member mem, ims_bwk_branch ibb, sys_department sd, sys_departbeauty_relation sdr,think_activities_winning win,think_activities_prize pri')->field('ibb.location_p, sd.st_department, ibb.sign, ibb.title, ibb.address, mem.realname, mem.mobile, mem.code, mem.staffid, mem.id, mem.isadmin, mem.pid,mem.createtime,pri.prize_name,win.win_create_time')->where('mem.storeid = ibb.id AND sd.id_department = sdr.id_department AND sdr.id_beauty = ibb.id AND mem.mobile > 0 AND length(mem.pwd) > 0 and win.user_id=mem.id  and win.prize_id=pri.id '.$map)->limit($pre,$limits)->order('mem.createtime desc')->select();
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

                $data1['prize_name'] =  $v['prize_name'];
                $data1['win_create_time'] = $v['win_create_time'];
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
            $headerArr = array('编号ID','省份','办事处','门店编码','门店名称','地址','用户','用户手机号','角色','上级人','上级人手机号','原始码姓名','原始码手机号','注册时间','奖品名称','中奖时间');
            $name = 'appUser_userDetail';
            $res = reportCsv($headerArr,$data,$name);
            $url = 'http://localhost:81/csv/';
//            //服务器
////          $url = 'http://live.qunarmei.com/csv/';
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


    // 登录有奖奖品列表
    public function prize_info()
    {
        //搜索
        $name = input('name','');
        //初始化数据
        $map = null;
        if($name)
        {
            $map .= " and prize_name like '%$name%'";
        }

        $count = Db::table('think_activities_prize pri,think_activities act')->field('pri.id,act.act_title,pri.prize_name,pri.prize_count,pri.prize_img,pri.prize_create_time,pri.prize_url')->where('pri.act_id = act.id'.$map)->count();//计算总页面

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $report = input('report');
        if($report == 1)
        {
            $limits =  $count;
        }
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $mem = Db::table('think_activities_prize pri,think_activities act')->field('pri.id,act.act_title,pri.prize_name,pri.prize_count,pri.prize_img,pri.prize_create_time,pri.prize_url')->where('pri.act_id = act.id'.$map)->order('pri.prize_create_time desc')->limit($pre,$limits)->select();
        $data=array();$data1=array();
        if($mem)
        {
            foreach($mem as $v)
            {
                $data1['id'] = $v['id'];
                $data1['act_title'] = $v['act_title'];
                $data1['prize_name'] = $v['prize_name'];
                $data1['prize_count'] = $v['prize_count'];
                $data1['prize_img'] = $v['prize_img'];
                $data1['prize_url'] = $v['prize_url'];
                $data1['prize_create_time'] = $v['prize_create_time'];
                $data[] = $data1;
            }
        }

        $this->assign('name', $name);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('lists', $data);
        //翻页
        if(input('get.page'))
        {
            return json($data);
        }

        return $this->fetch();
    }
    /**
     * [userEdit 奖品编辑]
     * @return [type] [description]
     *
     */
    public function priEdit()
    {
        $id = input('param.id');
        if(request()->isAjax()){
            $param = input('post.');
            $data = array('act_id'=>1,'prize_name'=>$param['prize_name'],'prize_count'=>$param['prize_count'],'prize_img'=>$param['prize_img'],'prize_url'=>$param['prize_url']);
            $ret = Db::table('think_activities_prize')->where('id', $id)->update($data);
            return $this->returnMsg(1,'','修改成功');
        }
        $list = Db::table('think_activities_prize')->where(array('id'=>$id))->limit(1)->find();
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * [userAdd 奖品添加]
     * @return [type] [description]
     *
     */
    public function priAdd()
    {
        if(request()->isAjax()){
            $param = input('post.');
            if($param)
            {
                $data = array('act_id'=>1,'prize_name'=>$param['prize_name'],'prize_count'=>$param['prize_count'],'prize_img'=>$param['prize_img'],'prize_url'=>$param['prize_url'],'prize_create_time'=>date('Y-m-d H:i:s'));
                $rest = Db::table('think_activities_prize')->insert($data);
                return $this->returnMsg(1,'','添加成功');
            }

        }
        return $this->fetch();
    }
    //返回json数据
    public function returnMsg($code=1,$data='',$msg='')
    {
        $ret = array('code'=>$code,'data'=>$data,'msg'=>$msg);
        return json($ret);
    }
}