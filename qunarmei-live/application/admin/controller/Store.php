<?php

namespace app\admin\controller;
use think\Db;

/*
 * 门店管理
 *
 * */
class Store extends Base
{

    /**
     * [index 办事处审核页面]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = '';
        if($key&&$key!==""){
            $map = " (title like '%$key%' or name_lb like '%$key%' or mobile_lb like '%$key%' or sign like '%$key%') ";
        }
        $map['status'] = ['<',4];
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数
        $count = Db::table('ims_bwk_branch_review')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;
        $lists = Db::table('ims_bwk_branch_review')->field('*')->where($map)->select();

        $p = new Page($lists,30,$key);
        //把分页后的对象$p渲染到模板
        $this->assign([
            'p' => $p,
        ]);
//        echo '<pre>';print_r($p);exit;
        $this->assign('lists', $p->data);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);

//        echo '<pre>';print_r($lists);
//        if(input('get.page')){
//            return json($lists);
//        }
        return $this->fetch();
    }

    /**
     * [sh_xs 销售审核页面]
     * @author [田建龙] [864491238@qq.com]
     */
    public function sh_xs(){

        $key = input('key');
        $map = '';
        if($key&&$key!==""){
            $map = " and (title like '%$key%' or name_lb like '%$key%' or mobile_lb like '%$key%' or sign like '%$key%')";
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 30;// 获取总条数

        $type  = input('type');
        if($type != 'll')
        {
            $count = Db::table('ims_bwk_branch_review')->where(' status>2 '.$map)->count();//计算总页面
            $lists = Db::table('ims_bwk_branch_review')->field('*')->where(' status>2 '.$map)->select();
            foreach($lists as &$v)
            {
                $list1 = Db::table('sys_department')->field('st_department')->where("id_department='".$v['bsc']."'")->limit(1)->select();            $v['department'] = '';
                if($list1)
                {
                    $v['department'] = $list1[0]['st_department'].'办事处';
                }
//                $v['department'] = '上海办事处';
            }
        }else
        {
            //显示所有门店信息
            $lists = array();
            if($key)
            {
                $map = " and (ibb.title like '%$key%' or mem.realname like '%$key%' or mem.mobile like '%$key%' or ibb.sign like '%$key%')";
            }
            $count = Db::table('ims_bwk_branch ibb, ims_bj_shopn_member mem')->field('ibb.title, ibb.sign, mem.realname, mem.mobile,ibb.id')->where(" ibb.id = mem.storeid and mem.isadmin=1 and ibb.sign not in ('888-888','666-666','000-000') ".$map)->count();//计算总页面
            $lists1 = Db::table('ims_bwk_branch ibb, ims_bj_shopn_member mem')->field('ibb.title, ibb.sign, mem.realname,mem.mobile,ibb.id')->where(" ibb.id = mem.storeid and mem.isadmin=1 and ibb.sign not in ('888-888','666-666','000-000') ".$map)->select();
            //查询门店对应的办事处
            $lists2 = Db::table('sys_departbeauty_relation sdr,sys_department sd')->field('sdr.id_sign, sdr.id_beauty, sd.st_department')->where('sdr.id_department=sd.id_department')->select();

            foreach($lists1 as $v1)
            {
                $v1['department'] = '';
                foreach($lists2 as $v2)
                {
                    if($v1['id'] == $v2['id_beauty'])
                    {
                        $v1['department'] = $v2['st_department'].'办事处';
                    }
                }
                #门店名称 门店编号 所属办事处 老板姓名 老板手机

                $lists3['title']=$v1['title'];
                $lists3['sign']=$v1['sign'];
                $lists3['department']=$v1['department'];
                $lists3['name_lb']=$v1['realname'];
                $lists3['mobile_lb']=$v1['mobile'];
                $lists[] =  $lists3;
            }
        }
        $count = count($lists);
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;


        $p = new Page($lists,30,$key);
        //把分页后的对象$p渲染到模板
        $this->assign([
            'p' => $p,
        ]);
//        echo '<pre>';print_r($p->data);
        $this->assign('lists', $p->data);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('type', $type);
        return $this->fetch();
    }


    /**
     * [check_store 检查门店]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
     public function check_store()
     {
         header('Access-Control-Allow-Origin:*');
         $sign = input('sign');
         $id = input('id');
         $flag=2;
         if($sign)
         {
             $res = Db::table('ims_bwk_branch')->field('id')->where("sign='$sign'")->limit(1)->select();
             if($res)
             {
                 $flag=1;
             }else{
                // 不存在的门店编号更新进去
                $map['id'] = $id;
                $data['sign'] = $sign;
                Db::table('ims_bwk_branch_review')
                ->where($map)
                ->update($data);
             }
         }
         return $flag;

     }

    /**
     * [check_store 审核门店]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function shenhe_store()
    {
        // 指定允许其他域名访问  
        header('Access-Control-Allow-Origin:*'); 
        $flag=2;$id_all='';
        $datar=[];$msg = '';
        $ids = input('ids');
        if($ids)
        {
            $ids = rtrim($ids,'@');
            $type = input('type');
            #1182|555-555
            if($ids)
            {
                $ids1 = explode('@',$ids);
                foreach($ids1 as $v1)
                {
                    $v2 = explode('|',$v1);
                    $map['id'] = $v2[0];
                    $res1 = Db::table('ims_bwk_branch_review')
                        ->field('id,title,sign,mobile_lb,name_lb,mobile_txr')
                        ->where($map)
                        ->limit(1)
                        ->find();
                    // 审核通过
                    if($type == 'yes'){
                        // 查询门店是否存在,不存在插入
                        if($res1){
                            $map2['title'] = ['like','%'.$res1['title'].'%'];
                            $map3['sign'] = ['like','%'.$res1['sign'].'%'];
                            $res2 = Db::table('ims_bwk_branch')
                                ->field('id')
                                ->where($map2)
                                ->whereOr($map3)
                                ->limit(1)
                                ->find();
                            // 没有插入
                            if(!$res2){
                                Db::execute(" INSERT INTO ims_bwk_branch ( weid, title, subtitle, star, timescard, promotion, sign, cat_f, cat_s, cat, location_p, location_c, location_a, address, lng, lat, logo, slideArr, thumbArr, tel, price, prices, OPEN, enable_wifi, enable_card, enable_room, enable_park, recommend, summary, content, pnum, isshow, admin, codenum, createtime, updatetime, sm_proportion ) SELECT weid, title, subtitle, star, timescard, promotion, sign, cat_f, cat_s, cat, location_p, location_c, location_a, address, lng, lat, logo, slideArr, thumbArr, tel, price, prices, OPEN, enable_wifi, enable_card, enable_room, enable_park, recommend, summary, content, pnum, isshow, admin, codenum, createtime, updatetime, sm_proportion FROM ims_bwk_branch_review WHERE id = ".$v2[0]);
                                // 插入的门店id
                                $mapst['sign'] = $res1['sign'];
                                $res_store = Db::table('ims_bwk_branch')
                                ->where($mapst)
                                ->field('id')
                                ->limit(1)
                                ->find();

                                if ($res_store) {
                                    // 插入老板是否存在
                                    $mapu['mobile'] = $res1['mobile_lb'];
                                    $resu = Db::table('ims_bj_shopn_member')
                                        ->where($mapu)
                                        ->limit(1)
                                        ->find();
                                    if(!$resu){
                                        // 插入
                                        $datau = [
                                            'weid' => 1,
                                            'storeid' => $res_store['id'],
                                            'realname' => $res1['name_lb'],
                                            'mobile' => $res1['mobile_lb'],
                                            'createtime' => time(),
                                            'isadmin' => 1
                                        ];
                                        Db::table('ims_bj_shopn_member')->insertGetId($datau);
                                    }
                                    $datar['status'] = 4;
                                    $msg = '通过';
                                    $resd = send_dingding('15921324164','新门店'.$res1['title'].'-门店id:'.$storeid.'-注册成功,请登录微商城后台和拼购后台为新门店配置商品!');
                                    
                                }
                            }
                        }
                    }else{
                    // 审核不通过
                        $datar['status'] = 2;
                        $msg = '未通过';
                    }
                    // 修改申请表
                    $mapr['id'] = $v2[0];
                    Db::table('ims_bwk_branch_review')
                        ->where($mapr)
                        ->update($datar);
                    $flag=1;

                    // 发短信通知申请人申请结果
                    if($res1['mobile_txr']){
                        $url = send_sms($res1['mobile_txr'],96,'{"status":"'.$res1['title'].'已经审核'.$msg.'"}');
                    }
                }
            }
        }
        return $flag;
    }

    /**
     * [store_info 门店信息页面]
     * @author [田建龙] [864491238@qq.com]
     */
    public function store_info(){

        $key = input('id');$map='';
        if($key)
        {
            $map = " (ibb.sign = '$key' )";
        }

        $type= input('type');
        //办事处查询申请门店信息
        if($type=='bsc')
        {
            $map = " ibb.title rlike '$key' ";
            $lists1 = Db::table('ims_bwk_branch_review ibb')->field('title,tel,open,summary,address,createtime,mobile_lb,name_lb')->where($map)->limit(1)->select();
            $lists2 = Db::table('sys_department sd,ims_bwk_branch_review ibb')->field('sd.st_department')->where("sd.id_department=ibb.bsc  and ".$map)->limit(1)->select();

            if($lists1)
            {
                $lists3[0]['title'] = $lists1[0]['title'];
                $lists3[0]['department'] = @$lists2[0]['st_department']==''?'':$lists2[0]['st_department'].'办事处';
                $lists3[0]['md_mobile'] = $lists1[0]['tel'];
                $lists3[0]['dlb_name'] = @$lists1[0]['name_lb'];
                $lists3[0]['dlb_mobile'] = $lists1[0]['mobile_lb'];
                $lists3[0]['open_time'] = @$lists1[0]['open'];
                $lists3[0]['summary'] = $lists1[0]['summary'];
                $lists3[0]['address'] = @$lists1[0]['address'];
                $lists3[0]['cretime'] = date('Y-m-d H:i:s',@$lists1[0]['createtime']);
            }
        }else
        {
            if($type=='sh')
            {
                $lists1 = Db::table('ims_bwk_branch_review ibb')->field('title,tel,open,summary,address,createtime')->where($map)->limit(1)->select();
                $lists2 = Db::table('sys_departbeauty_relation sdr,sys_department sd,ims_bwk_branch_review ibb')->field('sdr.id_sign, sdr.id_beauty, sd.st_department')->where("sdr.id_department=sd.id_department and ibb.id=sdr.id_beauty and ".$map)->limit(1)->select();
                $lists3 = Db::table('ims_bwk_branch_review ibb,ims_bj_shopn_member mem')->field('mem.mobile,mem.realname')->where(" ibb.id=mem.storeid  and mem.isadmin=1 and ".$map)->limit(1)->select();
            }else
            {
                $lists1 = Db::table('ims_bwk_branch ibb')->field('title,tel,open,summary,address,createtime')->where($map)->limit(1)->select();
                $lists2 = Db::table('sys_departbeauty_relation sdr,sys_department sd,ims_bwk_branch ibb')->field('sdr.id_sign, sdr.id_beauty, sd.st_department')->where("sdr.id_department=sd.id_department and ibb.id=sdr.id_beauty and ".$map)->limit(1)->select();
                $lists3 = Db::table('ims_bwk_branch ibb,ims_bj_shopn_member mem')->field('mem.mobile,mem.realname')->where(" ibb.id=mem.storeid  and mem.isadmin=1 and ".$map)->limit(1)->select();
            }

            if($lists1)
            {
                $lists3[0]['title'] = @$lists1[0]['title'];
                $lists3[0]['department'] = @$lists2[0]['st_department']==''?'':@$lists2[0]['st_department'].'办事处';
                $lists3[0]['md_mobile'] = @$lists1[0]['tel'];
                $lists3[0]['dlb_name'] = @$lists3[0]['realname'];
                $lists3[0]['dlb_mobile'] = @$lists3[0]['mobile'];
                $lists3[0]['open_time'] = @$lists1[0]['open'];
                $lists3[0]['summary'] = @$lists1[0]['summary'];
                $lists3[0]['address'] = @$lists1[0]['address'];
                $lists3[0]['cretime'] = date('Y-m-d H:i:s',@$lists1[0]['createtime']);
            }
        }


        $this->assign('lists', $lists3);
        $this->assign('val', $key);

        return $this->fetch();
    }
    /**
     * 门店注册审核
     */
    public function storeExa()
    {
        $page = input('page');
        $Nowpage = $page?$page:1;
        $limits = 20;// 获取总条数
        $map['status'] = ['<',4];
        $count = Db::table('ims_bwk_branch_review')
            ->field('id')
            ->where($map)
            ->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $page_limits = ($Nowpage-1)*$limits;

        $lists = Db::table('ims_bwk_branch_review')
            ->field('*')
            ->where($map)
            ->limit($page_limits,$limits)
            ->order('createtime desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        if($page) {
            return json($lists);
        }
        if($lists){
            $status = [
                1=>'<span style="color:blue;">待审核</span>',
                2=>'<span style="color:red;">审核不通过</span>',
                4=>'<span style="color:green;">审核通过</span>'
            ];
            foreach ($lists as $k=>$v) {
                if($v['createtime']){
                    $lists[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                }
                $lists[$k]['status'] = $status[$v['status']];
            }
        }
        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /**
     * 门店申请修改
     */
    public function storeExaEdit()
    {
        if(request()->isPost()){
            $param = input('post.');
            $map['id'] = $param['id'];
            $list = Db::table('ims_bwk_branch_review')->where($map)->limit(1)->find();
            $data['sign'] = $param['sign'];
            $data['status'] = $param['status'];
            $res = Db::table('ims_bwk_branch_review')->where($map)->update($data);
            // 审核不通过
            $msg = '';
            if ($data['status'] == 2) {
                $msg = '-'.$list['title'].'-门店注册未通过审核!';
            }elseif ($data['status'] == 4) {
                // 审核通过
                $msg = '-'.$list['title'].'-门店注册已通过审核!';

                // 插入门店表
                $datab = [
                    'weid' => 1,
                    'title' => $list['title'],
                    'sign' => $list['sign'],
                    'location_p' => $list['location_p'],
                    'location_c' => $list['location_c'],
                    'location_a' => $list['location_a'],
                    'address' => $list['address'],
                    'lng' => $list['lng'],
                    'lat' => $list['lat'],
                    'tel' => $list['tel'],
                    'open' => $list['open'],
                    'summary' => $list['summary'],
                    'content' => $list['content'],
                    'isshow' => 1,
                    'createtime' => time(),
                ];  
                $resb = Db::table('ims_bwk_branch')->insertGetId($datab);

                // 查询店老板是否已存在,不存在则进行注册
                // $mapu['mobile'] = $list['mobile_lb'];
                // $resu = Db::table('ims_bj_shopn_member')->where($mapu)->count();
                // if($resu<1){
                //     // 注册店老板
                //     $boss_url = 'https://api-app.qunarmei.com/qunamei/bossregist';
                //     $boss_data = [
                //         'mobile' => $list['mobile_lb'],
                //         'sign' => $list['sign']
                //     ];
                // }

                // 发送钉钉通知王伟俊,给新添加门店配置商品
                $msgd = '新门店-'.$list['title'].'-'.$list['sign'].'-已添加,请登录微商城和拼购后台配置门店商品!';
                send_dingding('15921324164',$msgd);
            }

            // 发送结果通知申请人
            if ($list['mobile_txr'] && $msg) {
                $str = '{"status":"'.$msg.'"}';
                send_sms($list['mobile_txr'],96,$str);
            }
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $id = input('id');
        $map['id'] = $id;
        $list = Db::table('ims_bwk_branch_review')->where($map)->limit(1)->find();
        $this->assign('list',$list);
        return $this->fetch();
    }

}