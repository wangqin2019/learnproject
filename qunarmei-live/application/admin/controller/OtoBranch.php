<?php

namespace app\admin\controller;
use think\Db;

/**
 * Class OtoBranch
 * @package app\admin\controller
 * oto参与活动门店
 */
class OtoBranch extends Base
{
    protected $csv_path = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\csv\\';//上传文件路径
    //*********************************************广告列表*********************************************//
    /**
     * [index 账号列表]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function index(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['bwk.title|bwk.sign|depart.st_department'] = ['like',"%" . $key . "%"];
        }
        $export = input('export',0);
        $page = input('page');
        $Nowpage = $page?$page:1;
        $limits = 20;// 获取总条数
        /*CREATE TABLE `ims_bj_shopn_oto_branch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `storeid` int(10) NOT NULL DEFAULT '0' COMMENT '门店id',
  `limit_num` int(10) NOT NULL DEFAULT '0' COMMENT '分配数量',
  `create_time` datetime DEFAULT NULL COMMENT '插入时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店活动-脑力教育账号配置表';

*/
        $count = Db::table('ims_bj_shopn_oto_branch b')
            ->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')
            ->field('b.id')
            ->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')
            ->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')
            ->where($map)
            ->count();
        //计算总页面
        $allpage = intval(ceil($count / $limits));
        $page_limits = ($Nowpage-1)*$limits;
//        echo '<pre>';print_r($page_limits);print_r($limits);die;
        $lists = Db::table('ims_bj_shopn_oto_branch b')
            ->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')
            ->field('b.id,b.storeid,bwk.title,bwk.sign,b.limit_num,depart.st_department')
            ->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')
            ->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')
            ->where($map)
            ->field('b.id,bwk.title,bwk.sign,depart.st_department,b.limit_num,b.storeid')
            ->limit($page_limits,$limits)
            ->order('b.create_time desc')
            ->select();
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('val', $key);
        if(!empty($lists)){
            foreach ($lists as $k=>$v) {
                $map1['u.storeid'] = $v['storeid'];
                $map1['o.card_id'] = ['>',0];
                $lists[$k]['used_num'] = Db::table('ims_bj_shopn_oto o')
                    ->join(['pt_ticket_user' => 'u'], 'u.id=o.card_id', 'left')
                    ->where($map1)
                    ->count();//实际使用
                $lists[$k]['num'] = $lists[$k]['limit_num'];// 剩余个数
                $lists[$k]['limit_num'] = $lists[$k]['num'] + $lists[$k]['used_num'];// 总个数
            }
        }
        //导出
        if($export){
            $data=array();
            foreach ($lists as $k => $v) {
                $data[$k]['st_department']=$v['st_department'];
                $data[$k]['title']=$v['title'];
                $data[$k]['sign']=$v['sign'];
                $data[$k]['limit_num']= $v['limit_num'];
                $data[$k]['used_num']= $v['used_num'];
                $data[$k]['num']=$v['num'];
            }
            $filename = "OTO活动门店列表".date('YmdHis');
            $header = array ('办事处','门店名称','门店编码','分配数量','实际使用','剩余数量');
            $widths=array('10','10','10','10','10','10');
            if($data) {
                excelExport($filename, $header, $data, $widths);//生成数据
            }
            die();
        }
        if($page)
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [add_ad 添加广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function branch_add()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $arr['storeid'] = $param['storeid'];
            $arr['limit_num'] = $param['limit_num'];
            $arr['create_time'] = date('Y-m-d H:i:s');
            $res = Db::table('ims_bj_shopn_oto_branch')->insertGetId($arr);
            $flag = [
                'code' => 1,
                'data' => '',
                'msg' => '添加成功'
            ];
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $branchList = Db::table('ims_bwk_branch')->field('id,title,sign')->select();//计算总页面
        $this->assign('branchList',$branchList);
        return $this->fetch();

    }


    /**
     * [edit_ad 编辑广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function branch_edit()
    {
        $id = input('id');
        $map['b.id'] = $id;
        if(request()->isPost()){
            $param = input('post.');
            $map1['id'] = $param['id'];
            $data['limit_num'] = $param['limit_num'];
            $res = Db::table('ims_bj_shopn_oto_branch')->where($map1)->update($data);
            return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
        }
        $list = Db::table('ims_bj_shopn_oto_branch b')
            ->join(['ims_bwk_branch' => 'bwk'], 'b.storeid=bwk.id', 'left')
            ->field('b.id,bwk.title,b.limit_num')
            ->where($map)
            ->limit(1)
            ->find();
        $this->assign('list',$list);
        return $this->fetch();
    }


    /**
     * [del_ad 删除广告]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function branch_del()
    {
        $id = input('param.id');
        $map['id'] = $id;
        $res = Db::table('ims_bj_shopn_oto_branch')->where($map)->delete();
        return json(['code' => 1, 'data' => '', 'msg' => '删除成功']);
    }
    /**
     * [excelImport 导入execel数据]
     * @return [type] [description]
     * @author [田建龙] [864491238@qq.com]
     */
    public function excelImport()
    {
        if(request()->isAjax()){
            // 导入格式 sign,门店名称,门店数量
            $data_arr = excel_import();
//            echo '<pre>';print_r($data_arr);die;
            if($data_arr == -1){
                return json(['code' => 0, 'data' => [], 'msg' => '文件格式不对']);
            }elseif($data_arr){
                $dataV = [];
                $msg = '';
                $dt = date('Y-m-d H:i:s');
                foreach ($data_arr as $k=>$varr) {
                    $map['bwk.sign'] = $varr[0];
                    $res_oto = Db::table('ims_bj_shopn_oto_branch ob')
                        ->join(['ims_bwk_branch' => 'bwk'], 'ob.storeid=bwk.id', 'left')
                        ->where($map)
                        ->limit(1)
                        ->find();
                    if($res_oto){
                        $msg .= '有重复数据,';
                    }else{
                        $map1['sign'] = $varr[0];
                        $res = Db::table('ims_bwk_branch')
                            ->field('id')
                            ->where($map1)
                            ->limit(1)
                            ->find();
                        if($res){
                            $data1['storeid'] = $res['id'];
                            $data1['limit_num'] = $varr[2];
                            $data1['create_time'] = $dt;
                            $dataV[] = $data1;
                        }
                    }
                }
//                echo '<pre>';print_r($dataV);die;
                if($dataV){
                    $res_add = Db::table('ims_bj_shopn_oto_branch')->insertAll($dataV);
                    if($res_add){
                        $msg .= '添加成功,';
                    }
                }
                $msg = rtrim($msg,',');
                return json(['code' => 1, 'data' => [], 'msg' => $msg]);
            }

//            echo '<pre>';print_r($data_arr);die;
        }
        return $this->fetch();
    }
}