<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/10/14
 * Time: 15:36
 */

namespace app\api\service;
use app\api\model\User;
use think\Db;
use think\Cache;
use think\Log;
class PayPeriodService extends BaseSer
{
    // 发送短信url
    // protected $sms_url = 'http://sms.qunarmei.com:8080/sms.php';// 测试
    protected $sms_url = 'http://sms.qunarmei.com/sms.php';// 生产
    // 钉钉通知销售号码
    // protected $xiaoshou_mobiles = ['15921324164','18818219125'];// 测试
    protected $xiaoshou_mobiles = ['15921324164','13564501181'];// 正式销售
    /**
     * H5聊天室接口
     * @param string $mobile 用户手机号
     * @param string $id 直播间id
     * @return
     */
    public function getLive($id,$mobile)
    {
        // groupid，userid，usersig，SDKAppID
        $mapl['id'] = $id;
        $res = Db::table('think_live')->where($mapl)->limit(1)->find();
        if ($res) {
            $arr['group_id'] = $res['chat_id'];
            $arr['user_id'] = $mobile;
            $arr['user_sig'] = '';
            $arr['sdk_appid'] = '1400047679';
            $mapt['tent_cloud'] = $mobile;
            $rest = Db::table('think_tent_cloud l')->where($mapt)->limit(1)->find();
            if ($rest) {
                $arr['user_sig'] = $rest['user_sig'];
            }else{
                // 新用户创建user_sig
                $tent = new \tencent_cloud\TimChat();
                // 注册
                $tent->tentRegister($mobile);
                // 获取
                $arr['user_sig'] = $tent->getUserSig($mobile);
            }
            $this->msg = '获取成功';
            $this->data = $arr;
        }else{
            $this->msg = '暂无数据';
            $this->data = (object)[];
        }
        $this->code = 1;
        return $this->returnArr();
    }
    /**
     * 获取商品可选分期的列表接口
     * @param string $sign 门店编号
     * @return
     */
    public function getGoods()
    {
        // 查询商品
        $map1['g.marketprice'] = ['>=',3000];
        $map1['g.status'] = 1;
        $map1['g.isshow'] = 1;
        $map1['g.deleted'] = 0;
        $map1['g.live_flag'] = 0;
        $map1['g.storeid'] = 0;
        $map1['c.enabled'] = 1;
        $res = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_category' => 'c'],['g.pcate=c.id'])->field('g.id,g.title')->where($map1)->order('g.pcate asc,g.displayorder desc,g.id asc')->select();
        $goods = [];
        if ($res) {
            foreach ($res as $k => $v) {
                $gd1['goods_id'] = $v['id'];
                $gd1['goods_title'] = $v['title'];
                $goods[] = $gd1;
            }
        }
        $this->code = 1;
        $this->msg = '暂无数据';
        $this->data = [];
        if ($goods) {
            $this->data = $goods;
            $this->msg = '获取成功';
        }
        return $this->returnArr();
    }
    /**
     * 申请查询
     * @param string $user_id [用户id]
     * @param int $type [类型,0:分期,1:安心送]
     * @return [type] [description]
     */
    public function getApply($user_id,$type)
    {
        $this->code = 1;
        $this->msg = '暂无数据';
        $this->data = (object)[];
        $map1['user_id'] = $user_id;
        $map1['type'] = $type;
        $res = Db::table('ims_bj_shopn_payperiod_apply')->where($map1)->limit(1)->find();
        if ($res) {
            $data['id'] = $res['id'];
            $data['user_name'] = $res['user_name'];
            $data['card_no'] = $res['card_no'];
            $data['card_img_z'] = $res['card_img_z'];
            $data['card_img_f'] = $res['card_img_f'];
            $data['card_img_h'] = $res['card_img_h'];
            $data['cerl_img'] = $res['cerl_img'];
            $data['signs'] = $res['signs'];
            $data['status'] = $res['status'];
            $data['flag'] = 1;
            $data['goods_id'] = $res['goods_id'];
            $data['goods_name'] = '';
            $data['platform'] = $res['platform'];
            // 根据商品id查询商品名称
            // 查询商品
            $map2['g.marketprice'] = ['>=',3000];
            $map2['g.status'] = 1;
            $map2['g.isshow'] = 1;
            $map2['g.deleted'] = 0;
            $map2['g.live_flag'] = 0;
            $map2['g.storeid'] = 0;
            $map2['c.enabled'] = 1;
            $map2['g.id'] = ['in',explode(',', $data['goods_id'])];
            $resgd = Db::table('ims_bj_shopn_goods g')->join(['ims_bj_shopn_category' => 'c'],['g.pcate=c.id'])->field('g.id,g.title')->where($map2)->order('g.pcate asc,g.displayorder desc,g.id asc')->select();
            if ($resgd) {
                $gdname = [];
                foreach ($resgd as $kg => $vg) {
                    $gdname[] = $vg['title'];
                }
                $data['goods_name'] = implode(',', $gdname);
            }
            $this->data = [];
            $this->data = $data;
            $this->msg = '获取成功';
        }
        return $this->returnArr();
    }
    /**
     * 修改申请
     * @param string $id [id]
     * @param string $user_id[用户id]
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
    public function editApply($arr)
    {
        // 插入
        $data = [];
        if ($arr['user_name']) {
            $data['user_name'] = $arr['user_name'];
        }
        if ($arr['card_no']) {
            $data['card_no'] = $arr['card_no'];
        }
        if ($arr['card_img_z']) {
            $data['card_img_z'] = $arr['card_img_z'];
        }
        if ($arr['card_img_f']) {
            $data['card_img_f'] = $arr['card_img_f'];
        }
        if ($arr['card_img_h']) {
            $data['card_img_h'] = $arr['card_img_h'];
        }
        if ($arr['cerl_img']) {
            $data['cerl_img'] = $arr['cerl_img'];
        }
        if ($arr['signs']) {
            $data['signs'] = $arr['signs'];
        }
        if ($arr['flag']) {
            $data['flag'] = $arr['flag'];
        }
        if ($arr['goods_id']) {
            $data['goods_id'] = $arr['goods_id'];
        }
        if ($arr['platform']) {
            $data['platform'] = $arr['platform'];
        }
        $data['status'] = 0;
        $data['update_time'] = time();
        $map['id'] = $arr['id'];
        $res = Db::table('ims_bj_shopn_payperiod_apply')->where($map)->update($data);
        // 发送1条钉钉通知给销售相关人员
        $mobiles = $this->xiaoshou_mobiles;
        $msg = '有1条新的分期支付申请已修改,请登录去哪美后台处理!';
        if ($arr['type'] == 1) {
            $msg = '有1条新的安心送申请已修改,请登录去哪美后台处理!';
        }
        foreach ($mobiles as $k => $v) {
            send_dingding($v,$msg);
        }
        $this->code = 1;
        $this->msg = '申请修改成功,稍后我们将会给您答复!';
        $this->data = [];
        return $this->returnArr();
    }
    /**
     * 添加申请
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
    public function addApply($arr)
    {
        // 插入
        $data['user_id'] = $arr['user_id'];
        $data['user_name'] = $arr['user_name'];
        $data['card_no'] = $arr['card_no'];
        $data['card_img_z'] = $arr['card_img_z'];
        $data['card_img_f'] = $arr['card_img_f'];
        $data['card_img_h'] = $arr['card_img_h'];
        $data['cerl_img'] = $arr['cerl_img'];
        $data['signs'] = $arr['signs'];
        $data['goods_id'] = $arr['goods_id'];
        $data['flag'] = $arr['flag'];
        $data['create_time'] = time();
        $data['type'] = $arr['type'];
        $data['platform'] = $arr['platform'];
        $mapu['id'] = $data['user_id'];
        $resu = Db::table('ims_bj_shopn_member')->where($mapu)->limit(1)->find();
        if ($resu) {
            $data['mobile'] = $resu['mobile'];
        }
        $res = Db::table('ims_bj_shopn_payperiod_apply')->insert($data);
        // 发送1条钉钉通知给销售相关人员
        $mobiles = $this->xiaoshou_mobiles;
        $msg = '有1条新的分期支付申请,请上后台处理!';
        if ($arr['type'] == 1) {
            // 安心送
            $msg = '有1条新的安心送开通申请,请上去哪美后台处理!';
        }
        foreach ($mobiles as $k => $v) {
            send_dingding($v,$msg);
        }
        $this->code = 1;
        $this->msg = '申请成功,稍后我们将会给您答复!';
        $this->data = [];
        return $this->returnArr();
    }
    /**
     * 登录接口
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @param int $type 类型,0:分期申请,1:安心送申请
     * @return
     */
    public function login($mobile,$code,$type)
    {
        $map['mobile'] = $mobile;
        $this->code = 0;
        $this->data = (object)[];
        $resu = User::get($map);
        if ($resu) {
            if ($resu['isadmin'] == 0) {
                $this->msg = '该手机号不是门店老板,无法使用此平台!';
                return $this->returnArr();
            }
            // 比较验证码
            $key = 'code_'.$mobile;
            $code_cache = Cache::get($key);
            if ($code_cache) {
                if ($code_cache == $code) {
                    $this->code = 1;
                    $this->msg = '登录成功';
                    $this->data = [];
                    $this->data['user_id'] = $resu['id'];
                    // 查询是否提交过
                    $this->data['is_submit'] = 0;
                    // 分期
                    if ($type == 0) {
                        $map1['type'] = 0;
                    }elseif ($type == 1) {
                        // 安心送
                        $map1['type'] = 1;
                    }
                    $map1['user_id'] = $resu['id'];
                    $resg = Db::table('ims_bj_shopn_payperiod_apply')->where($map1)->limit(1)->find();
                    if ($resg) {
                        $this->data['is_submit'] = 1;
                    }
                }else{
                    $this->msg = '验证码错误,请重新输入!';
                }
            }else{
                $this->msg = '验证码已失效,请重新获取!';
            }
        }else{

            $this->msg = '该手机号暂未注册去哪美平台';
        }
        return $this->returnArr();
    }
    /**
     * 获取登录验证码接口
     * @param string $mobile 手机号
     * @return [type]         [description]
     */
    public function getCode($mobile)
    {
        // 获取用户信息
        $map['mobile'] = $mobile;
        $this->code = 0;
        $resu = User::get($map);
        if ($resu) {
            if ($resu['isadmin'] == 0) {
                $this->msg = '该手机号不是门店老板,无法使用此平台!';
                return $this->returnArr();
            }
            // $this->code = 1;
            // $this->msg = '获取成功';
            $key = 'code_'.$mobile;
            $rest = $this->makeCode($mobile,$key);
            $this->data['code'] = $rest['code'];
            $rest1 = $rest['res'];
            $rest1 = json_decode($rest1,true);
            if ($rest1['code'] == 0) {
                $this->code = 1;
                $this->msg = '获取成功';
            }else{
                $this->msg = '短信发送失败!';
            }
        }else{
            $this->data = (object)[];
            $this->msg = '该手机号暂未注册去哪美平台';
        }
        return $this->returnArr();
    }
    /**
     * 生成验证码
     * @param  [string] $mobile   手机号
     * @param  [string] $code_key [验证码缓存key值]
     */
    public function makeCode($mobile,$code_key)
    {
        $code = rand(100000,999999);
        // 下发短信验证码
        $res = $this->smsSend($mobile,1,$code);
        // 验证码存入缓存比较
        Cache::set($code_key,$code,600);
        $arr = [
            'code' => $code,
            'res' => $res
        ];
        return $arr;
    }

    /**
     * 发送短信
     * @param string $mobile 号码
     * @param int $id_template 模板id
     * @param string $str 接送格式-模板中需替换的变量数据
     */
    public function smsSend($mobile,$id_template,$str=null)
    {
        $queryStr = 'mobile='.$mobile.'&name=qunarmeiApp&pwd=qunarmeiApp&template='.$id_template.'&type=1';
        if($str){
            $queryStr = 'code='.$str.'&'.$queryStr;
        }
        $key = md5($queryStr);
        $queryStr = $queryStr.'&key='.$key;
        $url = $this->sms_url.'?'.$queryStr;
        Log::info('下发短信11:'.$url);
        $res = curl_get($url);
        Log::info('下发短信结果11:'.$res);
        if (!$res) {
            Log::info('重发短信:'.$url);
            $res = file_get_contents($url);
            Log::info('重发短信结果:'.$res);
        }
        // var_dump($url);var_dump($res);die;
        return $res;
    }
}