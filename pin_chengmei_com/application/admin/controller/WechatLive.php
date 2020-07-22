<?php

namespace app\admin\controller;
use app\admin\model\LiveRoomUserModel;
use app\admin\model\WechatLiveModel;
use think\Db;

class WechatLive extends Base
{

    /**
     * [index 直播列表]
     */
    public function lists(){
        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['name'] = ['like',"%" . $key . "%"];
        }
        $user = new WechatLiveModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $user->getLiveAll($map);  //总数据
        $allpage = intval(ceil($count / $limits));
        $lists = $user->getLiveByWhere($map, $Nowpage, $limits);
        foreach ($lists as $k=>$v){
          $lists[$k]['live_status']=config("live_status.".$v['live_status']);
          $lists[$k]['live_replay']=Db::name('wechat_live_replay')->where('roomid',$v['roomid'])->count();
        }
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }

    public function RefreshLive(){
        $live=new WechatLiveModel();
        $roomid=input('param.roomid');
        $data=['start'=>0,'limit'=>50];//仅能刷新前50个直播间
        $access_token=getAccessToken();
        if (!$access_token) return false;
        $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
        $result = http_post($url,json_encode($data));
        $result=json_decode($result,true);
        if($result['errcode']==0){
            try {
                if ($roomid) {//单独修改一个直播间
                    foreach ($result['room_info'] as $k => $v) {
                        $v['goods'] = json_encode($v['goods']);
                        $v['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
                        $v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                        $v['create_time'] = date('Y-m-d H:i:s');
                        if($v['roomid']==$roomid) {
                            Db::name('wechat_live')->where('roomid', $v['roomid'])->update($v);
                        }
                    }
                }else{
                    $insertData = [];
                    foreach ($result['room_info'] as $k => $v) {
                        $v['goods'] = json_encode($v['goods']);
                        $v['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
                        $v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                        $v['create_time'] = date('Y-m-d H:i:s');
                        Db::name('wechat_live')->where('roomid', $v['roomid'])->update($v);
                        if ($live->getOneLive(['roomid' => $v['roomid']])) {
                            Db::name('wechat_live')->where('roomid', $v['roomid'])->update($v);
                        } else {
                            $v['buy_begin'] = strtotime($v['start_time']);
                            $v['buy_end'] = strtotime($v['end_time']);
                            $insertData[$k] = $v;
                        }
                    }
                    Db::name('wechat_live')->insertAll(array_reverse($insertData));
                }
                return json(['code' => 1, 'data' => '', 'msg' => '刷新成功']);
            }catch (\Exception $e){
                return json(['code' => 0, 'data' => '', 'msg' => '刷新失败']);
            }
        }
    }


//    public function RefreshLive(){
//        $live=new WechatLiveModel();
//        $getLastRoomId=$live->getLastRoomId();
//        $data=['start'=>$getLastRoomId?$getLastRoomId:0,'limit'=>10];
//        $access_token=getAccessToken();
//        if (!$access_token) return false;
//        $url="http://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$access_token}";
//        $result = http_post($url,json_encode($data));
//        $result=json_decode($result,true);
//        if($result['errcode']==0){
//            $insertData=[];
//            foreach ($result['room_info'] as $k=>$v){
//                  if(!$live->getOneLive(['roomid'=>$v['roomid']])){
//                      $v['goods']=json_encode($v['goods']);
//                      $v['start_time']=date('Y-m-d H:i:s',$v['start_time']);
//                      $v['end_time']=date('Y-m-d H:i:s',$v['end_time']);
//                      $v['create_time']=date('Y-m-d H:i:s');
//                      $insertData[$k]=$v;
//                  }
//            }
//            Db::name('wechat_live')->insertAll(array_reverse($insertData));
//        }
//    }


    /**
     * [roleEdit 编辑直播间]
     */
    public function configure(){
        $live = new WechatLiveModel();
        if(request()->isAjax()){
            $param = input('post.');
            if(!empty($param['live_role'])){
                $param['live_role']=implode(',',$param['live_role']);
            }else{
                $param['live_role']='1,2,3';
            }
            if($param['buy_begin'] && $param['buy_end']){
                $param['buy_begin']=strtotime($param['buy_begin']);
                $param['buy_end']=strtotime($param['buy_end']);
            }
            $flag = $live->editLive($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $info = $live->getOneLive(['id'=>$id]);
        $info['buy_begin']=$info['buy_begin']?date('Y-m-d H:i:s',$info['buy_begin']):'';
        $info['buy_end']=$info['buy_end']?date('Y-m-d H:i:s',$info['buy_end']):'';
        $this->assign('info',$info);
        $departMent=Db::table('sys_department')->field("CONCAT('d',id_department) id,'0' pId,st_department name,st_department sign")->select();
        $branch=Db::table('sys_departbeauty_relation')->alias('r')->join(['ims_bwk_branch'=>'b'],'r.id_beauty=b.id','left')->field("id_beauty id,CONCAT('d',id_department) pId, CONCAT(title,sign) name,sign")->select();
        $branchList=array_merge($departMent,$branch);
        foreach($branchList as $k=>$v){
            $branchList[$k]['open']=$v['pId']?'true':'false';
            if(in_array($v['id'],explode(',',$info['live_object_sign']))){
                $branchList[$k]['checked']='true';
            }
        }
        $this->assign('branchList',json_encode($branchList));
        return $this->fetch();
    }


    public function getReplay(){
        $id = input('param.roomid');
        $info = Db::name('wechat_live_replay')->where('roomid',$id)->order('create_time')->column('media_url');
        $urls='';
        if($info){
            $urls.="<table class='table table-bordered'>";
            foreach ($info as $k=>$v){
                $urls.="<tr>";
                $urls.="<td>".($k+1)."</td><td>".$v."</td>";
                $urls.="<tr>";
            }
            $urls.="</table>";
            return json(['code' => 1, 'data' => $urls, 'msg' => '获取成功']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => '暂无数据']);
        }
    }


    /**
     * [roleDel 删除直播间]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function del(){
//        $id = input('param.id');
//        $role = new CouponModel();
//        $flag = $role->delCoupon($id);
//        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    public function live_user(){
        $id = input('param.id');
        if($id!=''){
            $map['roomid']=array('eq',$id);
            $live= new WechatLiveModel();
            $liveInfo=$live->getOneLive($map);
            $room= new LiveRoomUserModel();
            $user=$room->getLiveByWhere($map);
           if(count($user)){
               $data=[];
               foreach ($user as $k => $v) {
                   $data[$k]['st_department'] =$v['st_department'];
                   $data[$k]['title'] =$v['title'];
                   $data[$k]['sign'] =$v['sign'];
                   $data[$k]['mobile'] =$v['mobile'];
                   $data[$k]['realname'] =$v['realname'];
                   $data[$k]['openid'] =$v['openid'];
                   $data[$k]['role'] =$v['role'];
                   $data[$k]['live_name']=$liveInfo['name'];
                   $data[$k]['insert_time'] =$v['insert_time'];
               }
               $filename = $liveInfo['name']."直播间用户进入日志".date('YmdHis');
               $header = array ('办事处','门店名称','门店编码','用户名称','用户电话','用户openid','用户角色','观看直播间','记录时间');
               $widths=array('15','50','30','30','30','30','30','30','30');
               if($data) {
                   excelExport($filename, $header, $data, $widths);//生成数据
               }
           }
        }
    }



}