<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/7
 * Time: 14:24
 */

namespace app\api\controller;

use think\Db;
class Service extends Base
{
    /*
     * 返回客服电话、邮箱信息
     *
     * */
    public function consultation()
    {
        $code = 1;
        $msg = '暂无数据';
        $data = [];

        $map['flag'] = 1;
        $map['id'] = 1;
        $res = Db::table('app_service_conf')->field('mobile,email,url_privacy,url_security,url_behavior,url_after_sale')->where($map)->limit(1)->find();
        if($res){
            $data['mobile'] = $res['mobile'];
            $data['email'] = $res['email'];
            $data['url_privacy'] = $res['url_privacy'];
            $data['url_security'] = $res['url_security'];
            $data['url_behavior'] = $res['url_behavior'];
            $data['url_after_sale'] = $res['url_after_sale'];
            $code = 1;
            $msg = '获取成功';
        }
        return parent::returnMsg($code,$data,$msg);
    }
}