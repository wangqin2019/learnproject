<?php

namespace app\admin\controller;
use app\admin\model\ConfigModel;
use think\Db;

class Config extends Base{


    /**
     * 获取配置参数
     * @author [田建龙] [864491238@qq.com]
     */
    public function index() {
        $configModel = new ConfigModel();
        $list = $configModel->getAllConfig();
        $config = [];
        foreach ($list as $k => $v) {
            $config[trim($v['name'])] = $v['value'];
        }
        $this->assign('config',$config);
        //获取诚美总部人员信息
        $zbMember=Db::table('ims_bj_shopn_member')->where('storeid','in','1,2')->field('id,realname,mobile')->select();
        $this->assign('zbMember',$zbMember);
        return $this->fetch();
    }


    /**
     * 批量保存配置
     * @author [田建龙] [864491238@qq.com]
     */
    public function save($config){
        $configModel = new ConfigModel();
        if($config && is_array($config)){
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                $configModel->SaveConfig($map,$value);
            }
        }
        cache('db_config_data',null);
        $this->success('保存成功！');
    }

    public function change_check_time(){
        $time=date("Y-m-d H:i:s",strtotime("+3 day"));
        Db::name('wx_user')->where('mobile','15888888888')->update(['time_out'=>strtotime($time)]);
        return json(['code' => 1, 'data' => '', 'msg' => '延时成功，有效期到'.$time]);
    }
    public function change_check_time1(){
        $time=date("Y-m-d H:i:s",strtotime("+3 day"));
        Db::name('blink_wx_user')->where('mobile','15888888888')->update(['time_out'=>strtotime($time)]);
        return json(['code' => 1, 'data' => '', 'msg' => '延时成功，有效期到'.$time]);
    }
}
