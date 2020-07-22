<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/10/12
 * Time: 14:03
 * Description: 盲盒活动
 */

namespace app\admin\controller;
use think\Db;
set_time_limit(0);
//ini_set("memory_limit","512M");
class Card extends Base {
    // 卡券类型
    protected $typeVal = [
        '23' => '周末抽奖券',
        '24' => '美播间消费券',
        '25' => '护理券',
        '26' => '门店定制礼品券',
        '27' => '520专属宠爱券',
    ];
    // 卡券状态
    protected $statuVal = [
        '-1' => '未激活',
        '0' => '未使用',
        '1' => '已使用',
        '2' => '已过期'
    ];
    /**
     * 卡券列表
     */
    public function index(){
        $storeid = input('storeid');
        $scene_prefix = input('scene_prefix');

        $begin_time = input('begin_time');
        $end_time = input('end_time');

        $export = input('export',0);
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['sign|mobile'] = ['like','%'.$key.'%'];
        }
        if($storeid){
            $map['storeid'] = $storeid;
        }
        if($begin_time){
            $map['insert_time'] = ['between time',[$begin_time,$end_time]];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数

        $map['type'] = ['in',[23,24,25,26,27]];
        if($scene_prefix){
            $map['type'] = $scene_prefix;
        }
        $count =  Db::table('pt_ticket_user')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $pre = ($Nowpage-1)*$limits;

        // 卡券列表
//        ID 所属市场	所属美容院 用户姓名 电话 卡券编号 卡券类型 卡券	添加时间	核销时间	核销状态	分享状态

        // 生成导出报表
        if($export){
            $limits = 9000;
        }
        $lists = Db::table('pt_ticket_user')->where($map)->page($Nowpage,$limits)->order('insert_time desc')->select();
        $rest = [];
        if($lists){
            $user_ids = [];
            foreach ($lists as $k=>$v) {
                $user_ids[] = $v['user_id'];
                $lists[$k]['realname'] = '';
                $lists[$k]['type'] = $this->typeVal[$v['type']];
                $lists[$k]['status'] = $this->statuVal[$v['status']];
                $lists[$k]['update_time'] = $v['update_time']==null?'':$v['update_time'];
            }
            $mapu['id'] = ['in',$user_ids];
            $resu = Db::table('ims_bj_shopn_member')->where($mapu)->select();
            if($resu){
                foreach ($resu as $vu) {
                    foreach ($lists as $k=>$v) {
                        if($v['user_id'] == $vu['id']){
                            $lists[$k]['realname'] = $vu['realname'];
                        }
                    }
                }
            }
        }
        if($export){
            $data = [];
            foreach ($lists as $v) {
                $arr1['id'] = $v['id'];
                $arr1['depart'] = $v['depart'];
                $arr1['branch'] = $v['branch'];
                $arr1['sign'] = $v['sign'];
                $arr1['realname'] = $v['realname'];
                $arr1['mobile'] = $v['mobile'];
                $arr1['ticket_code'] = $v['ticket_code'];
                $arr1['type'] = $v['type'];
                $arr1['price'] = $v['par_value'];
                $arr1['status'] = $v['status'];
                $arr1['insert_time'] = $v['insert_time'];
                $arr1['update_time'] = $v['update_time'];
                // 重置数组索引
//                $arr1 = array_values($arr1);
                $data[] = $arr1;
            }
            $filename = "卡券列表".date('YmdHis');
            $header = ['ID','办事处','门店名称','门店编号','用户名称','用户号码','卡券编号','卡券名称','卡券价值','卡券状态','添加时间','使用时间'];
            $widths = ['10','10','10','10','10','10','10','10','10','10','10','10'];
            excelExport($filename, $header, $data, $widths);//生成数据
//            csv_export($data,$header,$filename);
            die();
        }
        // 门店列表
        $res_bwk = Db::table('ims_bwk_branch')->field('id,title,sign')->select();
        // 奖券配置列表
        $res_draw = Db::table('pt_draw_scene')->select();
        $this->assign('storeid', $storeid);
        $this->assign('scene_prefix', $scene_prefix);
        $this->assign('res_bwk', $res_bwk);
        $this->assign('res_draw', $res_draw);
        $this->assign('Nowpage', $Nowpage);

        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('count', $count);
        $this->assign('val', $key);
        $this->assign('begin_time', $begin_time);
        $this->assign('end_time', $end_time);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 520卡券激活
     */
    public function card_act()
    {
        $submit = input('submit',0);
        if($submit){
            $param = input();
            $mobiles = [];// 观看过直播间用户号码汇总
            // 查询小程序直播间用户号码
            $map_wx['u.roomid'] = $param['room_id'];
            $res_wx = Db::table('think_live_room_user u')
                ->join(['ims_bj_shopn_member'=>'m'],['u.openid=m.wx_open_id'],'left')
                ->field('m.id,m.mobile,m.storeid')
                ->where($map_wx)
                ->select();
            if($res_wx){
                foreach ($res_wx as $v) {
                    $mobiles[] = $v['mobile'];
                }
            }
            // 查询app直播间用户号码
            $map_app['chat_id'] = $param['chat_id'];
            $res_app = Db::table('think_live_see_user_log')
                ->field('mobile,sign')
                ->where($map_app)
                ->select();
            if($res_app){
                foreach ($res_app as $v) {
                    $mobiles[] = $v['mobile'];
                }
            }
            if($mobiles){
                // 剔除重复号码
                $mobiles = array_unique($mobiles);
                // 激活520卡券
                // 1.查询激活替换的激活图片
                $map['send_card_type'] = 27;
                $res_card = Db::table('ims_bj_activity_ticket_info')->field('unused_img')->where($map)->limit(1)->find();
                if($res_card){
                    // 2.激活卡券状态及图片
                    $map_tick['mobile'] = ['in',$mobiles];
                    $map_tick['type'] = 27;
                    $map_tick['status'] = -1;
                    $data_tick['status'] = 0;
                    $data_tick['aead_time'] = strtotime(date('Y-m-d',strtotime('+1 month')));// 今天的一个月后的时间戳
                    $data_tick['update_time'] = date('Y-m-d H:i:s');// 更新时间
                    $data_tick['draw_pic'] = $res_card['unused_img'];
                    Db::table('pt_ticket_user')->where($map_tick)->update($data_tick);
                }
            }
            return json(array('code'=>1,'data' => '','msg' => '520卡券激活成功'));
        }
        return $this->fetch();
    }
}