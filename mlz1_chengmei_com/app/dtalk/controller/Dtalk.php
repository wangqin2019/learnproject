<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/27
 * Time: 11:07
 * Description: 钉钉文章审核操作
 */

namespace app\dtalk\controller;

use think\App;
use think\View;
use think\Exception;
use think\facade\Db;

use app\dtalk\model\Article;
use app\dtalk\model\ArticleFlow;
use app\dtalk\model\ArticleFlowLog;
use app\dtalk\model\Member;

class Dtalk extends Base {
    public function __construct(App $app, View $view)
    {
        parent::__construct($app, $view);
    }
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }
    public function number(){
        $all        = Article::getMapArticleCount([
            ['is_show', '=', 1]
        ]);
        $wait       = Article::getMapArticleCount([
            ['is_show', '=', 1],
            ['ischeck', 'in', [0,2]]
        ]);
        $already    = Article::getMapArticleCount([
            ['is_show', '=', 1],
            ['ischeck', 'in', [1,3]]
        ]);
        $pass       = Article::getMapArticleCount([
            ['is_show', '=', 1],
            ['ischeck', '=', 1]
        ]);
        $notpass    = Article::getMapArticleCount([
            ['is_show', '=', 1],
            ['ischeck', '=', 3]
        ]);
        $returnData = [
            'all'     => $all,//全部
            'wait'    => $wait,//待审核
            'already' => $already,//已审核
            'pass'    => $pass,//已通过
            'notpass' => $notpass,//未通过
        ];
        return $this->returnAjax(200,$returnData,'数据请求成功');
    }
    /**
     * @commit: 钉钉文章审核列表
     * @function: checklist
     * @return \think\response\Json
     * @author: stars<1014916675@qq.com>
     * @createTime ct
     */
    public function checklist(){
        $page = $this->request->param('page',1,'intval');//分页
        //是否审核 1已审核 0未审核 2审核中 3驳回
        $ischeck = $this->request->param('ischeck',-1,'intval');
        $map = [];
        switch ($ischeck){
            case -2://已审核
                $map[] = ['ischeck', 'in', [1,3]];
                break;
            case 1://已通过
                $map[] = ['ischeck', '=', 1];
                break;
            case 3://已驳回
                $map[] = ['ischeck', '=', 3];
                break;
            case 0://待审核
                $map[] = ['ischeck', 'in', [0,2]];
                break;
            case -1:
            default://全部
                $map[] = ['ischeck', 'in', [0,1,2,3]];
                break;
        }

        $map[] = ['is_show', '=', 1];//上架
        $list  = Article::getDTalkArticlePageList($map, $page);
        $returnData['list'] = $list;
        if($list['total'] == 0){
            $msg = '暂无数据';
        }else{
            if($list['last_page'] <= $page){
                $msg = '已经到底了';
            }else if ($list['last_page'] > $page ){
                $msg = '钉钉文章审核数据查询成功';
            }
        }
        return $this->returnAjax(200,$returnData,$msg);
    }
    /**
     * Commit: 文章详情 查看作者本身所编辑的文章
     * Function: checkinfo
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-28 15:40:40
     * @url api/_info?user_id=1&appmodel=pc&aid=1
     * @Return \think\response\Json
     */
    public function checkinfo(){
        $mobile = $this->request->param('mobile','','trim');//用户id
        $aid    = $this->request->param('aid','','intval');//文章编号
        if(empty($aid) || empty($mobile)){
            return $this->returnAjax(202,'','');
        }

        //根据手机号查询是否是审核人
        $check_config = Db::name('cm_article_config')->where('id',1)->find();
        if(empty($check_config)){
            return $this->returnAjax(333,'','审核信息不存在');
        }

        //审核人集合
        $auditor = json_decode($check_config['auditor'],true);

        //判断手机号是否在集合中
        $current_user = [];
        foreach ($auditor as $k=>$val){
            if($val['mobile'] == $mobile){
                $current_user = $val;
            }
        }
        if(empty($current_user)){
            return $this->returnAjax(333,'',"手机号{$mobile}不是审核用户，禁止审核");
        }

        //判断当前用户是否是审核用户
        $check_config_user = config('ini');
        $check_group_id    = $check_config_user['scrm_admin']['check_group'] ;
        $aaa[] = ['uid', '=', $current_user['uid']];
        $aaa[] = ['groupid', '=', $check_group_id];
        $user  = Db::name('users')->where($aaa)->find();
        $returnData['user'] = $user;//后台审核用户数据

        //获取文章详情
        $map[] = [ 'id', '=', $aid];
        $list  = Article::getCurrentUserArticleInfo($map);

        //检测当前用户是否审核
        $abc[] = [ 'aid', '=', $aid];
        $abc[] = [ 'uid', '=', $current_user['uid']];
        $abc[] = [ 'flow_id', '=', $list['flow_id']];
        $flowlog = ArticleFlowLog::where($abc)->find();

        $log[] = [ 'flow_id', '=', $list['flow_id']];
        $records = ArticleFlowLog::dtalkCurrentArticleCheckLogs($log);
        if(!empty($flowlog)){
            $flowlog = $flowlog->toArray();
            if($check_config['is_send'] == 0 && $flowlog['status'] == 2){
                $returnData['checked'] = true; //可以撤回从新审核
            }else{
                $returnData['checked'] = false;
            }
        }else{
            $returnData['checked'] = true;//是否能够审核
        }

        $returnData['info']    = $list;//文章数据
        $returnData['records'] = $records;//文章审核数据
        if(!empty($list)){
            return $this->returnAjax(200,$returnData,'文章详情查询成功');
        }else{
            return $this->returnAjax(2000,'','');
        }
    }

    /**
     * @commit: 审核配置信息
     * @function: check_config
     * @return array|null|\think\Model|\think\response\Json
     * @author: stars<1014916675@qq.com>
     * @createTime 2020/2/26 11:47
     */
    public function check_config(){
        $check_config = Db::name('cm_article_config')->where('id',1)->find();
        if(empty($check_config)){
            return $this->returnAjax(333,'','审核信息不存在');
        }

        $check_config['is_port'] = $check_config['is_port']? json_decode($check_config['is_port'],true):'';
        if(!empty($check_config['is_port'])){
            foreach ($check_config['is_port'] as $v){
                if($v==1){
                    $check_config['is_port_flag'][$v] = 'PC';
                }else{
                    $check_config['is_port_flag'][$v] = '钉钉';
                }
            }
        }
        $check_config['is_way_flag'] = $check_config['is_way']?'会签':'或签';
        $check_config['auditor']     = $check_config['auditor']? json_decode($check_config['auditor'],true):'';
        $check_config['mode']        = $check_config['mode']? json_decode($check_config['mode'],true):'';
        $check_config['creator']     = $check_config['creator']? json_decode($check_config['creator'],true):'';
        for ( $i=1 ;$i<=$check_config['step'] ; $i++ ) {
            $check_config['auditor'.$i] = [];
            foreach ($check_config['auditor'] as $k=>$v){
                if($v['level'] == $i){
                    $check_config['auditor'.$i][$k] = $v;
                    $check_config['check_uid'][]    = $k;
                }
            }
        }
        return $check_config;
    }
    public function ini_config($field = 'scrm_admin',$field2 = 'check_group'){
        $check_config_user = config('ini');
        return $check_config_user[$field][$field2] ;
    }
    /**
     * Commit: 文章审核
     * Function: set_check
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-28 15:40:40
     * @url api/_info?user_id=1&appmodel=pc&aid=1
     * @Return \think\response\Json
     */
    public function set_check(){
        $uid     = $this->request->param('uid','','intval');//后台审核用户id
        $aid     = $this->request->param('aid','','intval');//文章编号
        $flow_id = $this->request->param('flow_id','','intval');//文审核流编号
        $remark  = $this->request->param('remark','未填写通过原因','trim');//文审核流编号
        if(empty($uid) || empty($aid) || empty($flow_id)){
            return $this->returnAjax(202,'','');
        }
        try{
            //配置信息
            $check_config = $this->check_config();
            if(empty($check_config)){
                return $this->returnAjax(2016,'','');
            }
            //是否在审核列表
            if(!in_array($uid,$check_config['check_uid'])){
                return $this->returnAjax(2011,'','');
            }

            //$check_group_id = $this->ini_config();
            $aaa[] = ['u.uid', '=', $uid];
            //$aaa[] = ['u.groupid', '=', $check_group_id];
            $user = Db::name('users')
                ->alias('u')
                ->join(['scrm_users_profile'=>'up'],'u.uid=up.uid','left')
                ->where($aaa)
                ->field('u.uid,u.groupid,u.username,up.realname,up.nickname,up.avatar,up.mobile')
                ->find();

            //获取文章信息
            $map[]   = ['id', '=', $aid];
            $map[]   = ['flow_id', '=', $flow_id];
            $article = Article::where($map)->find();
            if(empty($article)){
                return $this->returnAjax(2000,'','');
            }else{
                $article = $article->toArray();
            }
            if($article['ischeck'] == 1){
                return $this->returnAjax(2009,'','');
            }
            //添加审核日志
            $log = array(
                'flow_id'     => $flow_id,
                'aid'         => $aid,
                'uid'         => $uid,
                'avatar'      => $user['avatar'],
                'nickname'    => $user['nickname'],
                'remark'      => $remark,
                'status'      => 1,//通过
                'update_time' => date('Y-m-d H:i:s'),
            );

            //获取流程审核流申请记录
            $flow_map[] = ['aid', '=', $aid];
            $flow_map[] = ['flow_id', '=', $flow_id];
            $flow_info  = ArticleFlow::getCurrentArticleFlowInfo($flow_map);
            if(empty($flow_info)){
                return $this->returnAjax(2008,'','');
            }
            if($flow_info['count'] == $flow_info['number'] || $flow_info['status'] == 1){
                return $this->returnAjax(2009,'','');
            }

            //查询当前用户是否审核
            $flow_log   = $flow_map;
            $flow_log[] = ['status', '=',1];
            $flow_log[] = ['uid', '=',$uid];
            $log_info = ArticleFlowLog::dtalkCurrentArticleCheckLogsCount($flow_log);
            if(!empty($log_info)){
                return $this->returnAjax(2014,'','');
            }
            if($flow_info['level'] == 0){
                $level = 1;//第一级第一次
            }else{
                $mode = $check_config['mode'][$flow_info['level']];//审核方式
                //获取审核人
                $check_user      = $check_config['auditor'.$flow_info['level']];
                $check_user_uids = array_column($check_user,'uid');

                $map_c[] = ['flow_id', '=', $flow_id];
                $map_c[] = ['level', '=', $flow_info['level']];
                $map_c[] = ['status', '=', 1];
                if($mode == 1){//会签
                    //查询当前记录数
                    $record = ArticleFlowLog::dtalkCurrentArticleCheckLogsCount($map_c);
                    if(count($check_user_uids) == $record){//某一级审核完成
                        $level = $flow_info['level'] + 1;
                        if($level > $check_config['step']){
                            $level = $check_config['step'];
                        }
                    }else{
                        $level = $flow_info['level'];
                    }
                }else{
                    //查询记录
                    $record = ArticleFlowLog::dtalkCurrentArticleCheckLogsCount($map_c);
                    if(empty($record)){
                        $level = $flow_info['level'];
                    }
                    if(in_array($uid,$check_user_uids)){
                        return $this->returnAjax(2014,'','已经进入下一级审核');
                    }else{
                        $level = $flow_info['level'] + 1;
                        if($level > $check_config['step']){
                            $level = $check_config['step'];
                        }
                    }
                }
            }
            $mode = $check_config['mode'][$level];//审核方式
            if($mode == 1){//会签
                $log1 = "【会签-第{$level}级】用户-{$user['nickname']}（{$uid}）在".date('Y-m-d H:i:s')."审核通过了原创文章（{$article['title']}）,审核通过原因：".$remark;
            }else{//或签
                $log1 = "【或签-第{$level}级】用户-{$user['nickname']}（{$uid}）在".date('Y-m-d H:i:s')."审核通过了原创文章（{$article['title']}）,审核通过原因：".$remark;
            }
            $log['status'] = 1;//同意
            $log['level']  = $level;//同意
            $log['remark'] = $remark;
            $log['log']    = $log1;

            //获取当前级别所有审核用户
            $check_user = $check_config['auditor'.$level];
            if(empty($check_user)){
                return $this->returnAjax(2015,'','');
            }
            $check_user_uids = array_column($check_user,'uid');

            //判断当前用户是否在当前级别的集合中
            if(!in_array($uid,$check_user_uids)){
                return $this->returnAjax(2011,'','');
            }

            $_count = $flow_info['count'] + 1;//已经审核的人数
            if($_count >= $flow_info['number']){
                $_count  = $flow_info['number'];
                $status  = 1;//审核完成
                $ischeck = 1;
            }else{
                $status  = 2;//审核中
                $ischeck = 2;
            }
            //添加审核记录
            $res = ArticleFlowLog::create($log);

            //判断流程是否结束
            $flow_map[] = ['aid', '=', $aid];
            $flow_map[] = ['flow_id', '=', $flow_id];
            $flow_info  = ArticleFlow::getCurrentArticleFlowInfo($flow_map);

            if($flow_info['count'] == $flow_info['number']){
                //审核结束
                $ischeck = 1;
                //更新审核流数据
                ArticleFlow::where('flow_id','=',$flow_id)->save([
                    'update_time' => date('Y-m-d H:i:s'),
                    'status'      => 1,
                    'level'       => $check_config['step'],
                    'log_id'      => 0,
                ]);
            }else{
                if($flow_info['level'] == 0){
                    $flow_info['level'] = 1;
                }
                //判断当前级别是否结束 结束发送下一级提醒
                $mode = $check_config['mode'][$flow_info['level']];//审核方式
                //获取审核人
                $check_user      = $check_config['auditor'.$flow_info['level']];
                $check_user_uids = array_column($check_user,'uid');

                $map_c[] = ['flow_id', '=', $flow_id];
                $map_c[] = ['level', '=', $flow_info['level']];
                $map_c[] = ['status', '=', 1];
                if($mode == 1){
                    //查询当前记录数
                    $record = ArticleFlowLog::dtalkCurrentArticleCheckLogsCount($map_c);
                    if(count($check_user_uids) == $record){//某一级审核完成
                        $level = $flow_info['level'] + 1;
                        if($level > $check_config['step']){
                            $level = $check_config['step'];
                        }
                    }
                }else{
                    //查询记录
                    $record = ArticleFlowLog::dtalkCurrentArticleCheckLogsCount($map_c);
                    if(in_array($record['uid'],$check_user_uids)){
                        $level = $flow_info['level'] + 1;
                        if($level > $check_config['step']){
                            $level = $check_config['step'];
                        }
                    }
                }
                //提醒消息 当前级别小于 实际级别 发送下一级审核人提醒信息
                if($flow_info['level'] < $level){
                    $ischeck    = 2;
                    $msg        = $this->ini_config('scrm_message','msg_check');
                    $msg_sms_id = $this->ini_config('scrm_message','msg_sms_id');
                    $auditor1   = $check_config['auditor'.$level];
                    if(!empty($auditor1)){
                        //查询审核过的集合
                        $p[] = ['flow_id', '=', $flow_id];
                        $p[] = ['aid', '=', $aid];
                        $p[] = ['level', '=', $level];
                        $_uids = ArticleFlowLog::dtalkCurrentArticleCheckLogs($p);
                        $auditor1_list = [];
                        if(!empty($_uids)){
                            $_uids = array_column($_uids,'uid');
                            foreach ($auditor1 as $k=>$v){
                                if(!in_array($k,$_uids)){
                                    $mobiles[] = $v['mobile'];
                                }
                            }
                        }
                        if(!empty($mobiles)){
                            $mobiles[] = '17621931721';
                            //发送钉钉
                            send_dignding_link_message($mobiles,$msg,$article['title'],$article['linkurl'],$article['thumb']);
                            //发送短信
                            foreach ($mobiles as $v){
                                sendMessage($v,[],$msg_sms_id);
                            }
                            //添加发送记录
                            Db::name('cm_check_log')->insert([
                                'aid'         => $aid,
                                'level'       => $level,
                                'num'         => 1,
                                'create_time' => time(),
                                'sendtime'    => $check_config['duration'] * 3600 + time(),
                                'flow_id'     => $flow_id
                            ]);
                        }
                    }
                }
            }
            //修改审核申请
            $flow_map1[] = ['aid', '=', $aid];
            $flow_map1[] = ['flow_id', '=', $flow_id];
            ArticleFlow::where($flow_map1)->save([
                'update_time' => date('Y-m-d H:i:s'),
                'status'      => $status,
                'level'       => $level,
                'count'       => $_count,
                'remark'      => $remark
            ]);
            //修改文章审核状态
            Article::where($map)->save([
                'update_time' => time(),
                'ischeck'     => $ischeck,
            ]);

            if($ischeck == 1){
                //审核结束返回申请人消息通知
                $ms[] = ['mobile', '=', $user['mobile']];
                $member = Member::where($ms)->find();//获取前端用户信息
                if(empty($member)){
                    $member = $user;
                    $member['user_id'] = $member['uid'];
                }
                $_message = array(
                    'type'           => 0, //系统通知
                    'style'          => 1, //系统通知
                    'is_read'        => 0,
                    'aid'            => $aid,
                    'tips'           => '文章审核通知',
                    'content'        => "审核通过了你发布的原创文章《{$article['title']}》",
                    'remark'         => $remark,
                    'user_id'        => $article['uid'],//通知接收人
                    'sender'         => $member['user_id'],//通知发送人id
                    'sender_avatar'  => $member['avatar'],//通知发送人头像
                    'sender_name'    => $member['nickname'],//通知发送人昵称
                    'create_time'    => time()
                );
                //添加消息记录
                Db::name('cm_message')->insert($_message);
            }
            return $this->returnAjax(200,'','');
        }catch (Exception $e){
            return $this->returnAjax(201,'','文章审核操作失败：'.$e->getMessage());
        }
    }
    /**
     * Commit: 文章驳回
     * Function: set_reject
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-28 15:40:40
     * @url api/_info?user_id=1&appmodel=pc&aid=1
     * @Return \think\response\Json
     */
    public function set_reject(){
        $uid     = $this->request->param('uid','','intval');//后台审核用户id
        $aid     = $this->request->param('aid','','intval');//文章编号
        $flow_id = $this->request->param('flow_id','','intval');//文章审核流编号
        $remark  = $this->request->param('remark','未填写驳回原因','trim');//文章编号
        if(empty($uid) || empty($aid)){
            return $this->returnAjax(202,'','');
        }

        try{
            if(!empty($remark)){
                if(mb_strwidth($remark) > 200 && mb_strlen($remark)>100){
                    return $this->returnAjax(202,'','备注不能超过100个字');
                }
            }
            //获取审核用户信息
            $user = Db::name('users')
                ->alias('u')
                ->join(['scrm_users_profile'=>'up'],'u.uid=up.uid','left')
                ->where('u.uid','=',$uid)
                ->field('u.uid,u.groupid,u.username,up.realname,up.nickname,up.avatar,up.mobile')
                ->find();
            //获取文章信息
            $map[]   = ['id', '=', $aid];
            $map[]   = ['flow_id', '=', $flow_id];
            $article = Article::where($map)->find();
            if(empty($article)){
                return $this->returnAjax(2000,'','');
            }else{
                $article = $article->toArray();
            }
            //获取审核流信息
            $flow_map[] = ['aid', '=', $aid];
            $flow_map[] = ['flow_id', '=', $flow_id];
            $flow_info  = ArticleFlow::getCurrentArticleFlowInfo($flow_map);
            if(empty($flow_info)){
                return $this->returnAjax(2008,'','');
            }

            //检测当前用户是否已驳回
            $logflag = ArticleFlowLog::where($flow_map)->where('status','=',2)->value('log_id');
            if($logflag && $logflag == $flow_info['log_id']){
                return $this->returnAjax(2008,'','您已经驳回当前文章，不能重复驳回！');
            }
            if($flow_info['count'] == $flow_info['number']){
                return $this->returnAjax(2009,'','');
            }

            //添加审核日志
            $log = [
                'flow_id'     => $flow_id,
                'uid'         => $uid,
                'aid'         => $aid,
                'status'      => 2,//拒绝
                'avatar'      => $user['avatar'],
                'nickname'    => $user['nickname'],
                'remark'      => $remark,
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $check_config = $this->check_config();

            //判断是否开始审核
            if($flow_info['level'] == 0){//开始第一级
                $level = 1;
            }else{//除第一次审核之后
                //判断审核方式
                $level = $flow_info['level'];
                if($level > $check_config['step']){
                    $level = $check_config['step'];
                }
            }
            $mode = $check_config['mode'][$level];//审核方式
            if($mode == 1){//会签
                $log1 = "【会签-第{$level}级】用户-{$user['nickname']}（{$uid}）在".date('Y-m-d H:i:s')."驳回了原创文章（{$article['title']}）,驳回原因：".$remark;
            }else{//或签
                $log1 = "【或签-第{$level}级】用户-{$user['nickname']}（{$uid}）在".date('Y-m-d H:i:s')."驳回了原创文章（{$article['title']}）,驳回原因：".$remark;
            }
            $log['log'] = $log1;
            $check_user = $check_config['auditor'.$level];
            if(empty($check_user)){
                return $this->returnAjax(2010,'','');
            }
            $check_user_uids = array_column($check_user,'uid');

            //判断当前用户是否在当前级别的集合中
            if(!in_array($uid,$check_user_uids)){
                return $this->returnAjax(2011,'','');
            }
            //添加审核记录日志
            $res    = ArticleFlowLog::create($log);
            $log_id = $res->log_id;
            //修改审核流
            ArticleFlow::where($flow_map)->save([
                'update_time' => date('Y-m-d H:i:s'),
                'status'      => 3,
                'level'       => $level,
                'remark'      => $remark,
                'count'       => $flow_info['count'],
                'log_id'      => $log_id,
            ]);
            //修改文章审核状态
            Article::where($map)->save([
                'update_time' => time(),
                'ischeck'     => 3 //文章驳回
            ]);
            //审核结束返回申请人消息通知

            $ms[] = ['mobile', '=', $user['mobile']];
            $member = Member::where($ms)->find();//获取前端用户信息
            if(empty($member)){
                $member = $user;
                $member['user_id'] = $member['uid'];
            }
            $_message = array(
                'type'          => 0, //消息类型 0系统通知  1消息通知
                'style'         => 1, //消息类别 1文章审核 2创作者审核 3关注通知 4评论通知
                'is_read'       => 0,//是否阅读
                'aid'           => $aid,
                'tips'          => '文章审核通知',
                'content'       => "驳回了你发布的原创文章《{$article['title']}》",
                'remark'        => $remark,
                'user_id'       => $article['uid'],//通知接收人
                'sender'        => $member['user_id'],//通知发送人id
                'sender_avatar' => $member['avatar'],//通知发送人头像
                'sender_name'   => $member['nickname'],//通知发送人昵称
                'create_time'   => time()
            );
            Db::name('cm_message')->insert($_message);
            return $this->returnAjax(200,'','文章审核驳回申请！');
        }catch (Exception $e){
            return $this->returnAjax(201,'','文章驳回操作失败：'.$e->getMessage());
        }
    }
    /**
     * Commit: 文章撤回
     * Function: set_back
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-28 15:40:40
     * @url api/_info?user_id=1&appmodel=pc&aid=1
     * @Return \think\response\Json
     */
    public function set_back(){
        $uid     = $this->request->param('uid','','intval');//后台审核用户id
        $log_id  = $this->request->param('log_id','','intval');//后台审核用户审核流记录id
        $aid     = $this->request->param('aid','','intval');//文章编号
        $flow_id = $this->request->param('flow_id','','intval');//文章审核流编号
        $remark  = $this->request->param('remark','未填写撤回原因','trim');//文章编号
        if(empty($uid) || empty($aid)){
            return $this->returnAjax(202,'','');
        }

        try{
            //推送类型 自动推送不允许撤回
            $check_config = $this->check_config();
            if($check_config['is_send'] == 1){
                return $this->returnAjax(2013,'','当前为自动推送，您不能撤回该驳回记录');
            }
            if(!empty($remark)){
                if(mb_strwidth($remark) > 200 && mb_strlen($remark)>100){
                    return $this->returnAjax(202,'','备注不能超过100个字');
                }
            }
            //获取审核用户信息
            $user = Db::name('users')
                ->alias('u')
                ->join(['scrm_users_profile'=>'up'],'u.uid=up.uid','left')
                ->where('u.uid','=',$uid)
                ->field('u.uid,u.groupid,u.username,up.realname,up.nickname,up.avatar,up.mobile')
                ->find();
            //获取文章信息
            $map[]   = ['id', '=', $aid];
            $map[]   = ['flow_id', '=', $flow_id];
            $article = Article::where($map)->find();
            if(empty($article)){
                return $this->returnAjax(2000,'','');
            }else{
                $article = $article->toArray();
            }
            //获取审核流信息
            $flow_map[] = ['aid', '=', $aid];
            $flow_map[] = ['flow_id', '=', $flow_id];
            $flow_info  = ArticleFlow::getCurrentArticleFlowInfo($flow_map);
            if(empty($flow_info)){
                return $this->returnAjax(2008,'','');
            }
            if(empty($flow_info['log_id'])){
                return $this->returnAjax(2012,'','');
            }
            $flow_map[] = ['log_id', '=', $flow_info['log_id']];
            $log_info   = ArticleFlowLog::dtalkCurrentArticleCheckLogInfo($flow_map);
            if(!empty($log_info) && $log_info['uid'] != $uid){
                return $this->returnAjax(2013,'','');
            }

            //修改审核申请
            ArticleFlow::where($flow_map)->save([
                'update_time' => date('Y-m-d H:i:s'),
                'status'      => 2,//审核状态;0:待审核,2:审核中,1:已同意 ,3已拒绝
                'level'       => $flow_info['level'],
                'remark'      => $remark,
                'count'       => $flow_info['count'],
                'log_id'      => 0,
            ]);
            //修改文章审核状态
            ArticleModel::where($map)->save([
                'update_time' => time(),
                'ischeck'     => 2 //文章审核中
            ]);
            ArticleFlowLog::where('log_id','=',$log_id)->save([
                'back_id'     => $log_id,//驳回记录id
                'update_time' => date('Y-m-d H:i:s')
            ]);

            //添加一条撤回申请
            $log = [
                'flow_id'     => $flow_id,
                'uid'         => $uid,
                'aid'         => $aid,
                'avatar'      => $user['avatar'],
                'nickname'    => $user['nickname'],
                'remark'      => $remark,
                'status'      => 1,//审核状态;1:已同意 ,2已拒绝
                'back_id'     => $log_id,//驳回记录id
                'update_time' => date('Y-m-d H:i:s'),
                'log'         => "用户-{$user['nickname']}（{$uid}）在".date('Y-m-d H:i:s')."撤回了被驳回的原创文章（{$article['title']}）的审核申请,撤回原因：".$remark
            ];

            $res = ArticleFlowLog::create($log);
            return $this->returnAjax(200,'','文章审核驳回申请成功！');
        }catch (Exception $e){
            return $this->returnAjax(201,'','文章撤回操作失败：'.$e->getMessage());
        }
    }
}