<?php

namespace app\admin\controller;
use think\Cache;
use think\Db;
use think\Exception;

/**
 * 市场直播考核
 */
class MarketAssessment extends Base
{
    // 考生状态
    protected $studentStatus = [
        0 => '待考核',
        2 => '已完成',
        3 => '已截止',
    ];
    // 考官状态
    protected $examinerStatus = [
        0 => '待考核',
        4 => '待打分',
        2 => '已完成',
        3 => '已截止',
        5 => '',// 考生未考核
    ];
    /**
     * 列表
     */
    public function index(){

        $key = input('key');
        $assess_type = input('assess_type');
        $project_id = input('project_id');
        $map['r.delete_time'] = 0;
        if($key&&$key!==""){
            $map['r.assess_name'] = ['like','%'.$key.'%'];
        }
        if($assess_type){
            $map['r.assess_type'] = $assess_type;
        }
        if($project_id){
            $map['r.project_id'] = $project_id;
        }
        $limits = input('limit',50);// 获取每页条数
        $page = input('page',0);// 当前页
        // 查询数据
        if($page){
            $count = Db::table('think_live_assess r')
                ->where($map)
                ->count();
            $lists = Db::table('think_live_assess r')->join(['think_live_assess_project' => 'p'],['r.project_id = p.id'],'LEFT')
                ->field('r.*,p.assess_project')
                ->where($map)
                ->order('r.id desc')
                ->page($page,$limits)
                ->select();
            if($lists){
                // 查询考核人数
                foreach ($lists as $k=>$v) {
                    $mapa['assess_id'] = $v['id'];
                    $res_count = Db::table('think_live_assess_user')->where($mapa)->count();
                    $lists[$k]['num'] = $res_count;
                    $lists[$k]['assess_type'] = $v['assess_type']==1?'直播':'录像';
                    $lists[$k]['create_time'] = $v['create_time']==0?'':date('Y-m-d H:i:s');
                }
            }
            $arr['code'] = 0;
            $arr['msg'] = '获取成功';
            $arr['count'] = $count;
            $arr['data'] = $lists;

            return json($arr);
        }

        // 项目列表
        $mapp['delete_time'] = 0;
        $res_project = Db::table('think_live_assess_project')->where($mapp)->field('id,assess_project')->order('id desc')->select();
        $this->assign('assess_type', $assess_type);
        $this->assign('project_id', $project_id);
        $this->assign('projects', $res_project);
        $this->assign('val', $key);
        return $this->fetch();
    }
    /**
     * 添加
     */
    /**
     * 编辑
     */
    public function addAssess()
    {
        $type = input('type');
        Db::startTrans();
        try{
            if($type){
                $arr1 = [];$res_ass = '';
                $arr['code'] = 2;
                $arr['msg'] = '添加失败!';
                $res1 = '';$res2 = '';
                $tableData = input('tableData');
                $formData = input('formData');

                if($tableData){
                    // 查询选中人的号码
                    $arr1 = json_decode($tableData,true);
                }

                if($formData){
                    $dt = time();
                    $arr2 = json_decode($formData,true);

                    // 插入考核名称列表
                    $data_ass['assess_name'] = $arr2['assess_name'];
                    $data_ass['assess_type'] = $arr2['assess_type'];
                    $data_ass['begin_time'] = $arr2['begin_time']?strtotime($arr2['begin_time']):0;
                    $data_ass['end_time'] = $arr2['end_time']?strtotime($arr2['end_time']):0;
                    $data_ass['create_time'] = $dt;
                    $data_ass['project_id'] = $arr2['project_id'];
                    if($type == 'add'){
                        $res_ass = Db::table('think_live_assess')->insertGetId($data_ass);
                        $res1 = $res_ass;
                    }elseif($type == 'edit'){
                        // 修改
                        $id = input('id');
                        $mapa['id'] = $id;
                        unset($data_ass['create_time']);
                        $data_ass['update_time'] = time();
                        $res_ass = Db::table('think_live_assess')->where($mapa)->update($data_ass);
                        $res1 = $id;
                        $res_ass = $id;
                    }

                    $data_all = [];
                    foreach ($arr1['test'] as $k => $v) {
                        $data1['assess_id'] = $res_ass;
                        $data1['mobile'] = $v['mobile'];
                        $data1['user_name'] = $v['name'];
                        $data1['department'] = $v['depart'];
                        $data1['create_time'] = $dt;

                        // live_time查询ding_mobile
                        $mapl['mobile'] = $v['mobile'];
                        $res_live = Db::table('think_ding_mobile')->where($mapl)->limit(1)->value('live_time');

                        $data1['live_time'] = $res_live;
                        // 修改
                        if($type == 'edit'){
                            $id = input('id');
                            $mapas['assess_id'] = $id;
                            $mapas['mobile'] = $v['mobile'];

                            // 先查询,存在修改,不存在添加
                            $res3 = Db::table('think_live_assess_user')->where($mapas)->limit(1)->value('id');
                            if($res3){
                                unset($data1['create_time']);
                                $data1['update_time'] = time();
                                $res2 = Db::table('think_live_assess_user')->where($mapas)->limit(1)->update($data1);
                            }else{
                                $res2 = Db::table('think_live_assess_user')->insertGetId($data1);
                                // 查询所有评委
                                $mapc['name'] = 'assess_mobile';
                                $res_pw = Db::table('think_assess_config')->where($mapc)->value('val');
                                if($res_pw){
                                    $mobile_pw = explode(',',$res_pw);
                                    $mapd['mobile'] = ['in',$mobile_pw];
                                    $res_pw_info = Db::table('think_ding_mobile')->where($mapd)->field('mobile,name,depart')->order('id asc')->select();
                                    if($res_pw_info){
                                        $data_pw_all = [];
                                        foreach ($res_pw_info as $vw) {
                                            $data_pw['mobile'] = $vw['mobile'];
                                            $data_pw['user_name'] = $vw['name'];
                                            $data_pw['assess_user_id'] = $res2;
                                            $data_pw['status'] = 5;
                                            $data_pw['create_time'] = $dt;
                                            $data_pw_all[] = $data_pw;
                                        }
                                        // 插入所有评分表
                                        Db::table('think_live_assess_score')->insertAll($data_pw_all);
                                    }
                                }
                            }
                        }elseif($type == 'add'){
                            // 添加数据
                            $res2 = Db::table('think_live_assess_user')->insertGetId($data1);
                            // 查询所有评委
                            $mapc['name'] = 'assess_mobile';
                            $res_pw = Db::table('think_assess_config')->where($mapc)->value('val');
                            if($res_pw){
                                $mobile_pw = explode(',',$res_pw);
                                $mapd['mobile'] = ['in',$mobile_pw];
                                $res_pw_info = Db::table('think_ding_mobile')->where($mapd)->field('mobile,name,depart')->order('id asc')->select();
                                if($res_pw_info){
                                    $data_pw_all = [];
                                    foreach ($res_pw_info as $vw) {
                                        $data_pw['mobile'] = $vw['mobile'];
                                        $data_pw['user_name'] = $vw['name'];
                                        $data_pw['assess_user_id'] = $res2;
                                        $data_pw['status'] = 5;
                                        $data_pw['create_time'] = $dt;
                                        $data_pw_all[] = $data_pw;
                                    }
                                    // 插入所有评分表
                                    Db::table('think_live_assess_score')->insertAll($data_pw_all);
                                }
                            }
                        }
//                    $data_all[] = $data1;
                    }
                    // 插入用户考核详情
//                if($type == 'add'){
//                    $res2 = Db::table('think_live_assess_user')->insertAll($data_all);
//                }
                }
                if($res1 || $res2){
                    $arr['code'] = 1;
                    $arr['msg'] = '提交成功';
                    // 提交事务
                    Db::commit();
                }
                return json($arr);
            }

        }catch(Exception $e){
            $arr['code'] = 2;
            $arr['msg'] = '添加失败!'.$e->getMessage();
            // 回滚事务
            Db::rollback();
            return json($arr);
        }

        // 项目列表
        $mapp['delete_time'] = 0;
        $res_project = Db::table('think_live_assess_project')->where($mapp)->field('id,assess_project')->order('id desc')->select();
        $this->assign('projects', $res_project);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    public function editAssess()
    {
        // 刷新页面清除保存的缓存
        $id = input('id',0);
        $submit = input('submit');
        $list = Db::table('think_live_assess')->where('id',$id)->limit(1)->find();
        if($list){
            $map['assess_id'] = $id;
            $res_user = Db::table('think_live_assess_user u')
                ->join(['ims_bj_shopn_member'=>'m'],['m.mobile = u.mobile'] , 'left')
                ->join(['sys_departbeauty_relation'=>'r'],['r.id_beauty = m.storeid'] , 'left')
                ->join(['sys_department'=>'d'],['r.id_department = d.id_department'] , 'left')
                ->field('m.realname,u.user_name,u.department,d.st_department')
                ->where($map)
                ->order('u.id desc')
                ->select();
            $list['begin_time'] = $list['begin_time']==0?'':date('Y-m-d H:i',$list['begin_time']);
            $list['end_time'] = $list['end_time']==0?'':date('Y-m-d H:i',$list['end_time']);
        }
        if($submit){
            $param = input();
            $id = $param['id'];
            return json(array('code'=>1,'data' => '','msg' => '修改成功'));
        }

        // 查询已选择用户信息
        $mapu['assess_id'] = $id;
        $res = Db::table('think_live_assess_user')->where($mapu)->field('mobile,user_name name,department depart,live_time')->order('id desc')->select();
        if($res){
            foreach ($res as $k => $v) {
                $res[$k]['live_time'] = $v['live_time']==0?'':date('Y-m-d H:i',$v['live_time']);
            }
        }
        // 项目列表
        $mapp['delete_time'] = 0;
        $res_project = Db::table('think_live_assess_project')->where($mapp)->field('id,assess_project')->order('id desc')->select();
        $this->assign('projects', $res_project);
        $this->assign('id', $id);
        $this->assign('list', $list);
        $this->assign('res', json_encode($res , JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }
    /**
     * 删除
     */
    public function delAssess()
    {
        $ids = input('param.ids');
        if($ids) {
            $id = json_decode($ids,true);
            $map['id'] = ['in',$id];
            $data['delete_time'] = time();
            Db::table('think_live_assess')->where($map)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
        }
    }

    /**
     * 添加考核对象
     * @param int $assess_id 项目id
     * @return array
     */
    public function addAssessUser()
    {
        $assess_id = input('assess_id');
        $type = input('type');

        // 刷新页面清除保存的缓存
//        if(empty($type)){
//            $uid = $_SESSION['think']['uid'];
//            $key = 'xz_data_'.$uid;
//            Cache::rm($key);
//        }
        $res_data = $this->getDingDingStructure();

        $this->assign('res_data', json_encode($res_data,JSON_UNESCAPED_UNICODE));
        $this->assign('assess_id', $assess_id);
        return $this->fetch();
    }

    /**
     * 获取钉钉系统组织架构
     */
    public function getDingDingStructure()
    {
        $arr = [];
        /*data = [{
            title: '一级1'
            ,id: 1
            ,field: 'name1'
            ,checked: true
            ,spread: true*/
        $url = config('dingding_domain').'/dingding/getdepartmentTree.shtml';
        $data = '{}';
        $key = 'dingding_data';
        $rest = [];$res_data_arr = [];
        // 取缓存数据
        $res_data = Cache::get($key);
        if($res_data){
            $res_data_arr = json_decode( $res_data , true);
        }else{
            $res = dingding_curl_post($url,$data);
            $rest = json_decode($res,true);
            if($rest['status'] == 200){
                $str = json_encode($rest['obj'],JSON_UNESCAPED_UNICODE);
                // 存入缓存
                Cache::set($key , $str , 3600*24*7);

                $res_data_arr = $rest['obj'];
            }
        }
        if($res_data_arr){
            foreach ($res_data_arr as $v) {
                $arr1['title'] = $v['name'];
                $arr1['id'] = $v['id'];
                $arr1['field'] = '';
                $arr1['spread'] = 'true';
                $arr1['mobile'] = '';

                $arr2['userinfos'] = $v['userinfos'];
                $arr2['children'] = $v['children'];

//                $arr1['children'] = $this->recursionArr($arr2);
//                $rest[] = $arr1;

                $rest = $this->recursionArr($arr2);
            }
        }
        return $rest;
//        echo '<pre>';print_r($rest);die;
    }

    /**
     * 递归函数获取N个节点
     * @param $arr
     */
    public function recursionArr($arr)
    {
        $rest = [];$res = [];
        if($arr['userinfos']){
            $res = $arr['userinfos'];
        }elseif($arr['children']){
            $res = $arr['children'];
        }
        // 第二层
        foreach ($res as $v2) {
            $arr22['title'] = $v2['name'];
            $arr22['id'] = $v2['id'];
            $arr22['field'] = '';
            $arr22['spread'] = '';
            $arr22['mobile'] = isset($v2['mobile'])?$v2['mobile']:'';
            $arr22['children'] = isset($v2['children'])?$v2['children']:[];

            $res3 = $v2['userinfos']?$v2['userinfos']:$v2['children'];

            if($res3){
                $arr3 = [];
                // 第三层
                foreach ($res3 as $v3) {
                    $arr33['title'] = $v3['name'];
                    $arr33['id'] = $v3['id'];
                    $arr33['field'] = '';
                    $arr33['spread'] = '';
                    $arr33['mobile'] = isset($v3['mobile']) ? $v3['mobile'] : '';
                    $arr33['children'] = isset($v3['children']) ? $v3['children'] : [];
                    $res4 = [];
                    if($arr33['mobile']){
                        $group_name = [];
                        if($v2['name']){
                            $group_name[] = $v2['name'];
                        }
                        $arr33['group_name'] = implode('-',$group_name);
                        $this->addDingDingData($arr33);
                    }
                    if(isset($v3['children']) && $v3['children']){
                        $res4 = $v3['children'];
                    }elseif(isset($v3['userinfos']) && $v3['userinfos']){
                        $res4 = $v3['userinfos'];
                    }

                    if($res4){
                        $arr4 = [];
//                         第四层
                        foreach ($res4 as $v4) {
                            $arr44['title'] = $v4['name'];
                            $arr44['id'] = $v4['id'];
                            $arr44['field'] = '';
                            $arr44['spread'] = '';
                            $arr44['mobile'] = isset($v4['mobile']) ? $v4['mobile'] : '';
                            $arr44['children'] = isset($v4['children']) ? $v4['children'] : [];
                            if($arr44['mobile']){
                                $group_name = [];
                                if($v3['name']){
                                    $group_name[] = $v3['name'];
                                }
                                $arr44['group_name'] = implode('-',$group_name);
                                $this->addDingDingData($arr44);
                            }
                            // 第五层
                            $res5 = [];
                            if(isset($v4['children']) && $v4['children']){
                                $res5 = $v4['children'];
                            }elseif(isset($v4['userinfos']) && $v4['userinfos']){
                                $res5 = $v4['userinfos'];
                            }
                            if($res5){
                                $arr5 = [];
                                // 第六层
                                foreach ($res5 as $v5) {
                                    $arr55['title'] = $v5['name'];
                                    $arr55['id'] = $v5['id'];
                                    $arr55['field'] = '';
                                    $arr55['spread'] = '';
                                    $arr55['mobile'] = isset($v5['mobile']) ? $v5['mobile'] : '';
                                    $arr55['children'] = isset($v5['children']) ? $v5['children'] : [];
                                    if($arr55['mobile']){
                                        $group_name = [];
                                        if($v3['name']){
                                            $group_name[] = $v3['name'];
                                        }
                                        if($v4['name']){
                                            $group_name[] = $v4['name'];
                                        }
                                        $arr55['group_name'] = implode('-',$group_name);
                                        $this->addDingDingData($arr55);
                                    }
                                    // 第七层
                                    $res6 = [];
                                    if(isset($v5['children']) && $v5['children']){
                                        $res6 = $v5['children'];
                                    }elseif(isset($v5['userinfos']) && $v5['userinfos']){
                                        $res6 = $v5['userinfos'];
                                    }
                                    if($res6){
                                        $arr6 = [];
                                        // 第六层
                                        foreach ($res6 as $v6) {
                                            $arr66['title'] = $v6['name'];
                                            $arr66['id'] = $v6['id'];
                                            $arr66['field'] = '';
                                            $arr66['spread'] = '';
                                            $arr66['mobile'] = isset($v6['mobile']) ? $v6['mobile'] : '';
                                            $arr66['children'] = isset($v6['children']) ? $v6['children'] : [];
                                            $arr66['group_name'] = '';
                                            if($arr66['mobile']){
                                                $group_name = [];
                                                if($v3['name']){
                                                    $group_name[] = $v3['name'];
                                                }
                                                if($v4['name']){
                                                    $group_name[] = $v4['name'];
                                                }
                                                if($v5['name']){
                                                    $group_name[] = $v5['name'];
                                                }
                                                $arr66['group_name'] = implode('-',$group_name);
                                                $this->addDingDingData($arr66);
                                            }
                                            $arr6[] = $arr66;
                                        }
                                        $arr55['children'] = $arr6;
                                    }
                                    $arr5[] = $arr55;
                                }
                                $arr44['children'] = $arr5;
                            }
                            $arr4[] = $arr44;
                        }
                        $arr33['children'] = $arr4;
                    }
                    $arr3[] = $arr33;
                }
                $arr22['children'] = $arr3;
            }
            $rest[] = $arr22;
        }
//        dump($rest);die;
        return $rest;
    }

    /**
     * 保存钉钉组织结构到数据库
     */
    public function addDingDingData($data)
    {
        $res = [];
        $key = 'dingding_data';
        $res_cache = Cache::get($key);
        if(empty($res_cache)){
            $arr['ding_id'] = $data['id'];
            $arr['name'] = $data['title'];
            $arr['mobile'] = $data['mobile'];
            $arr['depart'] = $data['group_name'];
            $arr['create_time'] = time();

            $map['mobile'] = $data['mobile'];
            $res = Db::table('think_ding_mobile')->where($map)->limit(1)->find();
            if(empty($res)){
                $res = Db::table('think_ding_mobile')->insert($arr);
            }
        }
        return $res;
    }

    /**
     * 根据选中的号码查询对应的用户数据
     */
    public function getXzData()
    {
        $uid = $_SESSION['think']['uid'];
        $type = input('type');
        $key = 'xz_data_'.$uid;
        if($type){
            $key .= $type;
        }
        $rest = [];
        $mobile = input('mobile');
        $mobiles = json_decode($mobile,true);

//        $res_cache = Cache::get($key);
//        if($res_cache){
//            $mobiles = array_merge($mobiles,json_decode($res_cache,true));
//            $data = json_encode($mobiles);
//            Cache::set($key , $data ,3600);
//        }else{
//            $data = json_encode($mobiles);
//            Cache::set($key , $data ,3600);
//        }
        $tableData = input('table_data');
        $mob = [];
        $tableData = json_decode($tableData,true);
        if($tableData){
            foreach ($tableData['test'] as $v) {
                $mob[] = $v['mobile'];
            }
        }
        if($mob){
            $mobiles = array_merge($mobiles,$mob);
        }

        $map['mobile'] = ['in',$mobiles];
        $res = Db::table('think_ding_mobile')->where($map)->select();
        if($res){
            foreach ($res as $v) {
                $arr['name'] = $v['name'];
                $arr['depart'] = $v['depart'];
                $arr['mobile'] = $v['mobile'];
                $arr['live_time'] = '';
                if($type == 'edit_assess'){
                    $id = input('id');
                    $mapl['assess_id'] = $id;
                    $mapl['mobile'] = $v['mobile'];
                    $live_time = Db::table('think_live_assess_user')->where($mapl)->value('live_time');
                    if($live_time){
                        $arr['live_time'] = date('Y-m-d H:i',$live_time);
                    }
                }
                $rest[] = $arr;
            }
        }
        return json($rest);
    }
    /**
     * 修改选中的数据
     */
    public function updXzData()
    {
        $name = input('name');
        $mobile = input('mobile');
        $live_time = input('live_time');
        $map['name'] = $name;
        $data['mobile'] = $mobile;
        if($live_time){
            $data['live_time'] = strtotime($live_time);
        }
        $res = Db::table('think_ding_mobile')->where($map)->update($data);
        $arr['code'] = 1;
        $arr['msg'] = '修改成功';
        return json($arr);
    }
    /**
     * 考核详情
     */
    public function assessDetail()
    {
        $id = input('id');
        $map['a.id'] = $id;
        $res = Db::table('think_live_assess a')
            ->join(['think_live_assess_project' => 'p'],['a.project_id = p.id'],'LEFT')
            ->field('a.*,p.assess_project')
            ->where($map)
            ->limit(1)
            ->find();

        $mapu['assess_id'] = $id;
        $mapu['delete_time'] = 0;
        $res_user = Db::table('think_live_assess_user')
            ->where($mapu)->field('id,mobile,user_name,department depart,live_time,status')->order('id desc')->select();
        if($res){
            $res['num'] = 0;
            $res['assess_type'] = $res['assess_type']==1?'直播':'录像';
        }
        if($res_user){
            foreach ($res_user as $k => $v) {
                $res_user[$k]['live_time'] = $v['live_time']==0?'':date('Y-m-d H:i:s',$v['live_time']);
                $res['num'] += 1;
                $res_user[$k]['status'] = $this->studentStatus[$v['status']];
            }
        }
        $this->assign('res',$res);
        $this->assign('res_user',json_encode($res_user,JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }

    /**
     * 删除考核用户详情
     */
    public function delAssessUser()
    {
        $name = input('name');
        $mobile = input('mobile');
        $assess_id = input('assess_id');

        $map['user_name'] = $name;
        $map['mobile'] = $mobile;
        $map['assess_id'] = $assess_id;
        $map['delete_time'] = 0;
        $res = Db::table('think_live_assess_user')->where($map)->limit(1)->find();
        if($res){
            $data['delete_time'] = time();
            Db::table('think_live_assess_user')->where($map)->update($data);
            $arr['code'] = 200;
            $arr['msg'] = '删除成功';
            return json($arr);
        }
    }

    /**
     * 分数详情
     */
    public function scoreDetail()
    {
        $rest = [];
        $page = input('page');
        $assess_id = input('assess_id');

        $map['assess_user_id'] = $assess_id;
        $map['score'] = ['>',0];
        $res = Db::table('think_live_assess_score c')
            ->join(['think_ding_mobile' => 'd'],['c.mobile = d.mobile'],'LEFT')
            ->field('c.user_name,c.score,d.depart')
            ->where($map)
            ->select();
        if($res){
            foreach ($res as $v) {
                $arr1['user_name'] = $v['user_name']==null?'':$v['user_name'];
                $arr1['score'] = $v['score']==null?'':$v['score'];
                $arr1['depart'] = $v['depart']==null?'':$v['depart'];
                $rest[] = $arr1;
            }
        }
        $this->assign('res',json_encode($res,JSON_UNESCAPED_UNICODE));

        $this->assign('assess_id',$assess_id);
        return $this->fetch();
    }
    /**
     * 项目管理
     */
    public function itemManage()
    {
        $key = input('key');
        $page = input('page',1);
        $limit = input('limit',50);
        $rest = [];

        if($key){
            $map['assess_project'] = ['like','%'.$key.'%'];
        }

        $map['delete_time'] = 0;
        $res = Db::table('think_live_assess_project')->where($map)->order('id desc')->page($page,$limit)->select();
        if($res){
            foreach ($res as $k => $v) {
                $arr1['id'] = $v['id'];
                $arr1['assess_project'] = $v['assess_project'];
                $arr1['create_time'] = $v['create_time']==0?'':date('Y-m-d H:i:s',$v['create_time']);
                $rest[] = $arr1;
            }
        }
        $this->assign('res',json_encode($rest,JSON_UNESCAPED_UNICODE));
        $this->assign('val',$key);
        return $this->fetch();

    }
    /**
     * 修改项目
     */
    public function updItem()
    {

        $assess_name = input('assess_project');
        $type = input('type');
        $arr['code'] = 2;
        // 添加
        if($type == 'add'){
            $data['assess_project'] = $assess_name;
            $data['create_time'] = time();
            Db::table('think_live_assess_project')->insertGetId($data);
            $arr['msg'] = '添加成功!';
            $arr['code'] = 1;
            return json($arr);
        }

        // 修改 + 删除
        $id = input('id');
        $map['id'] = $id;
        $res = Db::table('think_live_assess_project')->field('id,assess_project')->where($map)->limit(1)->find();

        if($res) {
            // 删除操作
            if($type == 'del'){
                $data['delete_time'] = time();
                $arr['msg'] = '删除成功!';
            }else{
            // 修改操作
                $data['assess_project'] = $assess_name;
                $data['update_time'] = time();
                $arr['msg'] = '修改成功!';
            }
            Db::table('think_live_assess_project')->where($map)->update($data);
            $arr['code'] = 1;

            return json($arr);
        }
    }
    /**
     * 设置
     */
    public function setConf()
    {
        $type = input('type');
        $arr['code'] = 2;
        $arr['msg'] = '提交失败!';
        // 提交数据
        if($type == 'submit'){
            $res1 = '';$res2 = '';
            $tableData = input('tableData');
            $formData = input('formData');

            if($tableData){
                $mobiles = [];
                $arr1 = json_decode($tableData,true);
                foreach ($arr1['test'] as $v) {
                    if($v){
                        $mobiles[] = $v['mobile'];
                    }
                }
                // 更新考核人员名单
                $map['name'] = 'assess_mobile';
                $data['val'] = implode(',',$mobiles);
                $res1 = Db::table('think_assess_config')->where($map)->update($data);
            }

            if($formData){
                $arr2 = json_decode($formData,true);
                foreach ($arr2 as $k => $v) {
                    // 更新考核人员设置
                    $map1['name'] = $k;
                    $data1['val'] = $v;
                    $res2 = Db::table('think_assess_config')->where($map1)->update($data1);
                }
            }

            if($res1 || $res2){
                $arr['code'] = 1;
                $arr['msg'] = '提交成功';
            }
            return json($arr);
        }

        // 查询原来数据
        $ids = [1,2,3,4];
        $mapr['id'] = ['in',$ids];
        $res = Db::table('think_assess_config')->where($mapr)->select();
        if($res){
            $res_mobile = [];
            $assess_mobile = '';
            $score_effective_time = '';
            $sms_tips = '';
            $point_system = '';
            foreach ($res as $k => $v) {
                if($v['name'] == 'score_effective_time'){
                    $score_effective_time = $v['val'];
                }elseif($v['name'] == 'sms_tips'){
                    $sms_tips = $v['val'];
                }elseif($v['name'] == 'point_system'){
                    $point_system = $v['val'];
                }elseif($v['name'] == 'assess_mobile'){
                    $assess_mobile = $v['val'];
                }
            }
            // 查询考官信息
            if($assess_mobile){
                $assess_mobile = explode(',',$assess_mobile);
                $mapd['mobile'] = ['in',$assess_mobile];
                $res_mobile = Db::table('think_ding_mobile')->where($mapd)->field('id,name,mobile,depart')->select();
            }
//            dump($score_effective_time);dump($sms_tips);dump($point_system);dump(json_encode($res_mobile,JSON_UNESCAPED_UNICODE));die;
            $this->assign('score_effective_time',$score_effective_time);
            $this->assign('sms_tips',$sms_tips);
            $this->assign('point_system',$point_system);
            $this->assign('res_mobile',json_encode($res_mobile,JSON_UNESCAPED_UNICODE));
        }

        return $this->fetch();

    }
}