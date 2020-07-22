<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:41
 */

namespace app\api\controller\v4;


use app\api\controller\v3\Common;
use app\api\service\BeautyCodeService;
header("Access-Control-Allow-Origin: *");
/**
 * 美容师二维码
 */
class BeautyCode extends Common
{
    // 美容师二维码服务类
    protected $beautyCodeSer;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->beautyCodeSer = new BeautyCodeService();
    }
    /**
     * 获取用户数据
     * @param int $user_id 用户id
     */
    public function get_beauty()
    {
        $user_id = input('user_id');
        
        $res = $this->beautyCodeSer->getBeauty($user_id);
        $this->rest['code'] = 1;
        if($res){
            $this->rest['msg'] = '获取成功';
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = (object)[];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
    /**
     * 获取商品数据
     * @param int $user_id 用户id
     * @param int $store_id 门店id
     * @param int $page 当前页
     * @param int $goods_id 当前商品id(子商品)
     */
    public function get_goods()
    {
        $user_id = input('user_id');
        $store_id = input('store_id');
        $page = input('page',1);
        $goods_id = input('goods_id');
        $res = $this->beautyCodeSer->getGoods($user_id , $store_id , $page , $goods_id);
        $this->rest['code'] = 1;
        if($res){
            $this->rest['msg'] = '获取成功';
            $this->rest['data'] = $res;
        }else{
            $this->rest['msg'] = '暂无数据';
            $this->rest['data'] = [];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
     /**
     * 
     * @param int $mobile 用户填写号码
     * @param int $mrs_mobile 美容师号码
     */
    public function register()
    {
        $mobile = input('mobile');
        $mrs_mobile = input('mrs_mobile');
        
        $res = $this->beautyCodeSer->Register($mobile , $mrs_mobile);
        $this->rest['code'] = 1;
        if($res > 0){
            $this->rest['msg'] = '注册成功';
            $this->rest['data'] = [];
        }else{
            $this->rest['code'] = 0;
            if ($res == -1) {
                $this->rest['msg'] = '该号码已注册';
            }elseif ($res == -2) {
                $this->rest['msg'] = '邀请人不是美容师';
            }
            $this->rest['data'] = [];
        }
        return $this->returnMsg($this->rest['code'],$this->rest['data'],$this->rest['msg']);
    }
}