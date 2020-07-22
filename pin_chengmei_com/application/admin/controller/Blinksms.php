<?php

namespace app\admin\controller;
use think\Db;

class Blinksms extends Base
{
    public function apply(){
        $key = input('key');
        $status = input('status',88);
        $map = [];
        if($key&&$key!==""){
            $map['w.mobile'] = ['like',"%" . $key . "%"];
        }
        if($status!=88){
            $map['w.status'] = ['=',$status];
        }
        $this->aaa($map);

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('blink_waiting_user')
            ->alias('w')
            ->where($map)
            ->count();  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('blink_waiting_user')
            ->alias('w')
            ->join(['ims_bj_shopn_member' => 'm'],'w.mobile=m.mobile','left')
            ->where($map)
            ->page($Nowpage, $limits)
            ->field('w.*,m.id mid,m.realname,code,staffid,isadmin,m.storeid')
            ->order('w.id desc')
            ->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['equipment'] = $v['equipment'] ? json_decode($v['equipment'],JSON_UNESCAPED_UNICODE) : '';
            $lists[$k]['realname']=$v['realname']?$v['realname']:'';
            $lists[$k]['insert_time']=date('Y-m-d H:i:s',$v['insert_time']);
            if($v['isadmin']==1){
                $lists[$k]['role']='店老板';
            } elseif (strlen($v['code']) > 1 && $v['mid'] == $v['staffid']) {
                $lists[$k]['role']='美容师';
            }else{
                if($v['mid']){
                    $lists[$k]['role']='顾客';
                }else{
                    $lists[$k]['role']='';
                }
            }
            $barnch = Db::table('ims_bwk_branch')
                ->where('id',$v['storeid'])
                ->field('sign,title,is_blink')
                ->find();
            $lists[$k]['sign'] = $barnch['sign']?$barnch['sign']:'';
            $lists[$k]['title'] = $barnch['title']?$barnch['title']:'';
            $lists[$k]['is_blink']= $barnch['is_blink'];

            if(empty($barnch)){
                continue;
            }
        }
        //var_dump($lists);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        $this->assign('status', $status);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    public function aaa($map){
        $map['w.status'] = ['eq',0];
        $map['w.insert_time'] = ['gt',strtotime('-2 days')];
        $map['m.storeid'] = ['gt',0];
        $lists = Db::name('blink_waiting_user')
            ->alias('w')
            ->join(['ims_bj_shopn_member' => 'm'],'w.mobile=m.mobile','left')
            ->where($map)
            ->field('w.*,m.id mid,m.realname,code,staffid,isadmin,m.storeid')
            ->order('w.id desc')
            ->select();
        foreach ($lists as $k=>$v){
            $barnch = Db::table('ims_bwk_branch')
                ->where('id',$v['storeid'])
                ->field('sign,title,is_blink')
                ->find();
            if(empty($barnch)){
                continue;
            }
            //检测门店是否开通
            if($barnch['is_blink'] == 1 && $v['status'] == 0){
                //检测用户是否存在订单
                if(!empty($v['mid'])){
                    $re = Db::name('blink_order')->where('uid',$v['mid'])->count();
                    if(!empty($re)){
                        Db::name('blink_waiting_user')->where('id',$v['id'])->update([
                            'status' => 1,
                            'update_time' => time()
                        ]);
                    }
                }
            }else if($barnch['is_blink'] == 0 && $v['status'] == 0){
                Db::name('blink_waiting_user')->where('id',$v['id'])->update([
                    'status' => 1,
                    'update_time' => time()
                ]);
            }

        }
    }

    public function applyState(){
        $id = input('param.id');
        $status = Db::name('blink_waiting_user')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('blink_waiting_user')->where('id',$id)->setField([
                'status'=>0,
                'update_time' => time()
            ]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '未处理']);
        } else {
            $flag = Db::name('blink_waiting_user')->where('id',$id)->setField([
                'status'=>1,
                'update_time' => time()
            ]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已处理']);
        }
    }


}
