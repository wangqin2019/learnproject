<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:32
 */

namespace app\api\controller\html;

use app\api\controller\v3\Common;
use app\api\service\PayPeriodService;
use app\api\service\BeautyCodeService;
header('Access-Control-Allow-Origin:*');

/**
 * H5相关接口设计
 * Class HtmlApi
 * @package app\api\controller\v4
 */
class PayPeriod extends Common
{
    // 图片路径
    // protected $imgPath = '/home/canmay/www/test.qunarmeic.com/public/static/api/';
    protected $imgPath = '/home/canmay/www/live/public/static/api/';
    // 支付服务类
    protected $paySer;
    /**
     * 初始化方法
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->paySer = new PayPeriodService();
    }
     /**
     * H5聊天室接口
     * @param string $mobile 用户手机号
     * @param string $id 直播间id
     * @return
     */
    public function get_live()
    {
        $id = input('id');
        $mobile = input('mobile');
        $res = $this->paySer->getLive($id,$mobile);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 获取商品可选分期的列表接口
     * @param string $sign 门店编号
     * @return
     */
    public function get_good()
    {
        // $sign = input('sign');
        $res = $this->paySer->getGoods();
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 申请修改
     * @param string $id [id]
     * @param string $user_id [用户id]
     * @param string $user_name [用户名]
     * @param string $card_no [身份证号]
     * @param string $card_img_z [身份证照片正面]
     * @param string $card_img_f[身份证照片反面]
     * @param string $card_img_h [手持身份证合照]
     * @param string $cerl_img [营业执照照片]
     * @param string $signs [开通的门店编号]
     * @param int $flag [营业执照照片]
     * @param string $goods_id [开通的门店编号]
     * @param int $type [类型,0:分期,1:安心送]
     * @param string $platform [(安心送才有)平台,1:去哪美app/去哪美商城小程序,2:去哪美啊序] 多个,分割
     */
    public function edit_apply()
    {
        $arr['id'] = input('id');
        $arr['user_id'] = input('user_id');
        $arr['user_name'] = input('user_name');
        $arr['card_no'] = input('card_no');
        $arr['card_img_z'] = input('card_img_z');
        $arr['card_img_f'] = input('card_img_f');
        $arr['card_img_h'] = input('card_img_h');
        $arr['cerl_img'] = input('cerl_img');
        $arr['signs'] = input('signs');
        $arr['flag'] = input('flag');
        $arr['goods_id'] = input('goods_id');
        $arr['type'] = input('type',0);
        $arr['platform'] = input('platform',0);
        $res = $this->paySer->editApply($arr);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 申请查询
     * @param string $user_id [用户id]
     * @param int $type [类型,0:分期,1:安心送]
     * @return [type] [description]
     */
    public function get_apply()
    {
        $user_id = input('user_id');
        $type = input('type',0);
        $res = $this->paySer->getApply($user_id,$type);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    //1.真实姓名,身份证号,身份证照片正面,身份证照片反面,手持身份证合照,营业执照照片,开通的门店编号(多个,分隔)
    /**
     * 添加申请
     * @param string $user_id [用户id]
     * @param string $user_name [用户名]
     * @param string $card_no [身份证号]
     * @param string $card_img_z [身份证照片正面]
     * @param string $card_img_f[身份证照片反面]
     * @param string $card_img_h [手持身份证合照]
     * @param string $cerl_img [营业执照照片]
     * @param string $signs [开通的门店编号]
     * @param int $type [类型,0:分期,1:安心送]
     * @param string $platform [(安心送才有)平台,1:去哪美app/去哪美商城小程序,2:去哪美啊序] 多个,分割
     */
    public function add_apply()
    {
        $arr['user_id'] = input('user_id');
        $arr['user_name'] = input('user_name');
        $arr['card_no'] = input('card_no');
        $arr['card_img_z'] = input('card_img_z');
        $arr['card_img_f'] = input('card_img_f');
        $arr['card_img_h'] = input('card_img_h');
        $arr['cerl_img'] = input('cerl_img');
        $arr['signs'] = input('signs');
        $arr['flag'] = input('flag',1);
        $arr['goods_id'] = input('goods_id','');
        $arr['type'] = input('type',0);
        $arr['platform'] = input('platform',0);
        $res = $this->paySer->addApply($arr);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 获取登录验证码接口
     * @param string $mobile 手机号
     * @return
     */
    public function get_code()
    {
        $mobile = input('mobile');
        $res = $this->paySer->getCode($mobile);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 登录接口
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @param int $type 类型,0:分期申请,1:安心送申请
     * @return
     */
    public function login()
    {
        $type = input('type',0);
        $mobile = input('mobile');
        $code = input('code');
        $res = $this->paySer->login($mobile , $code , $type);
        return $this->returnMsg($res['code'],$res['data'],$res['msg']);
    }
    /**
     * 上传图片
     * @return
     */
    public function upload_img()
    {
        set_time_limit(0);
        $code = 0;
        $msg = '上传失败';
        $data = [];
        $path_img = '';
        // 获取表单上传的文件，例如上传了一张图片
        $file = request()->file('image');
        if($file){
            //将传入的图片移动到框架应用根目录/public/api/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 / 
            foreach ($file as $k => $v) {
                $info = $v->move(ROOT_PATH . 'public' . DS . 'static'. DS .'api');
                if($info){
                    $code = 1;
                    $msg = '上传成功';
                    $path_img = $info->getSaveName();
                    // $path_img = str_replace('\\', '/', $path_img);
                    //上传文件到七牛
                    $beauSer = new BeautyCodeService();
                    $filepath = $this->imgPath.$path_img;
                    $filename = 'payperiod_'.time().'.png';
                    $img_url = $beauSer->upQiniuImg($filepath, $filename);
                    $data[] = $img_url;
                }else{
                    // 上传失败获取错误信息
                    $code = 0;
                    $msg = $file->getError();
                }
            }
        }
        return $this->returnMsg($code,$data,$msg);
    }

}