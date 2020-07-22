<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:46
 */

namespace app\api\service;
use qiniu_transcoding\Upimg;
use org\QRcode;
/**
 * 美容师服务类
 */
class BeautyCodeService
{
    // 用户模型
    protected $userMod;
    // 产品模型
    protected $goodsMod;
    // 用户产品二维码模型
    protected $qrcodeMod;
    // 本地url
    protected $domain;
    // 本地图片地址
    protected $imgPath;
    /**
     * 初始化方法
     */
    public function __construct()
    {
        // 本地测试
        $this->domain = 'http://live.qunarmei.com/';
        $this->imgPath = '/home/canmay/www/live/public/static/api/images';
        // 服务器路径
        // $this->domain = 'http://testc.qunarmei.com:9091/';
        // $this->imgPath = '/home/canmay/www/test.qunarmeic.com/public/static/api/images';
        $this->userMod = new \app\api\model\User();
        $this->goodsMod = new \app\api\model\BjGoods();
        $this->qrcodeMod = new \app\api\model\Qrcode();
    }
    /**
     * @param int $mobile 用户填写号码
     * @param int $mrs_mobile 美容师号码
     */
    public function Register($mobile , $mrs_mobile)
    {
        $flag = 0;
        // 查询用户号码和美容师号码
        $arr = [$mobile,$mrs_mobile];
        $map['mobile'] = ['in',$arr];
        $res = $this->userMod->where($map)->limit(2)->select();
        if ($res) {
            $store_id = 0;
            $pid = 0;
            foreach ($res as $k => $v) {
                if ($v['mobile'] == $mobile) {
                    $flag = -1;// 已注册
                }elseif($v['mobile'] == $mrs_mobile){
                    if (strlen($v['code'])<1) {
                        $flag = -2;// 邀请人不是美容师
                    }
                    $pid = $v['id'];
                }
                $store_id = $v['storeid'];
            }
            if ($flag == 0) {
                // 开始注册
                $user['weid'] = 1;
                $user['storeid'] = $store_id;
                $user['pid'] = $pid;
                $user['staffid'] = $pid;
                $user['mobile'] = $mobile;
                $user['fg_viprules'] = 1;
                $user['fg_vipgoods'] = 1;
                $user['realname'] = '手机用户'.substr($mobile,-3);
                $user['createtime'] = time();
                $user['id_regsource'] = 1;
                $user['register_source'] = 1;// 分享H5页面注册
                $this->userMod->save($user);
                $flag = 1;
            }
        }
        return $flag;
    }
    /**
     * 获取商品信息
     * @param int $user_id 用户id
     * @param int $store_id 门店id
     * @param int $page 当前页
     * @return array
     */
    public function getGoods($user_id , $store_id ,$page)
    {
        $data = [];
        //2.产品id,产品封面图,产品名称,产品价格,产品二维码(带用户信息和门店信息和产品信息)
        $map['g.storeid'] = $store_id;
        $map['g.status'] = 1;
        $map['g.isshow'] = 1;
        $map['g.deleted'] = 0;
        $map['g.live_flag'] = 0;
        $map['c.enabled'] = 1;
        $res = $this->goodsMod->alias('g')->join(['ims_bj_shopn_category'=>'c'],['c.id=g.pcate'],'INNER')->field('g.*')->where($map)->order('g.pcate asc,g.displayorder desc,g.id asc')->page($page,10)->select();
        if ($res) {
            // 查询用户信息
            $mapu['id'] = $user_id;
            $res_mobile = $this->userMod->where($mapu)->limit(1)->find();
            foreach ($res as $k => $v) {
                $data1['goods_id'] = $v['id'];
                $data1['goods_img'] = $v['thumb'];
                $data1['goods_title'] = $v['title'];
                $data1['goods_price'] = $v['marketprice'];
                $data1['goods_qrcode'] = '';
                // 查询门店产品是否已生成二维码
                $mapq['store_id'] = $store_id;
                $mapq['content'] = $v['id'];
                $mapq['category'] = 1;
                $res_g = $this->qrcodeMod->where($mapq)->limit(1)->find();
                if ($res_g) {
                    $data1['goods_qrcode'] = $res_g['qrcode'];
                }else{
                    // 生成产品二维码
                    $urls = 'type=2&store_id='.$v['storeid'].'&mobile='.$res_mobile['mobile'].'&goods_id='.$v['id'];
                    $msg = config('url.qrcode_jump_url').'?'.$urls;// 扫码后跳转的url
                    $filename = $v['storeid'].'_'.$v['id'].'_'.time().'.png';
                    $qrcode = $this->makeQrCode($msg , $filename);
                    $data1['goods_qrcode'] = $qrcode;
                    // 插入二维码数据表
                    $datab['type'] = 2;
                    $datab['store_id'] = $v['storeid'];
                    $datab['content'] = $v['id'];
                    $datab['qrcode'] = $qrcode;
                    $datab['qrcode_data'] = $msg;
                    $datab['create_time'] = time();
                    $this->qrcodeMod->save($datab);
                }
                $data[] = $data1;
            }
        }
        return $data;
    }
    /**
     * 获取用户信息
     * @param int $user_id 用户id
     * @return array
     */
    public function getBeauty($user_id)
    {
        $data = [];
        // 1.用户id,用户头像,用户名称,门店编号,门店名称,用户门店码,美容师号码
        $mapu['u.id'] = $user_id;
        $res = $this->userMod->alias('u')->join(['ims_fans'=>'f'],['u.id=f.id_member'],'LEFT')->join(['ims_bwk_branch'=>'b'],['u.storeid=b.id'],'LEFT')->where($mapu)->limit(1)->find();
        if ($res) {
            $data['user_id'] = $user_id;
            $data['user_name'] = $res['realname']==null?'':$res['realname'];
            $data['user_img'] = $res['avatar']==null?config('img.head_img'):$res['avatar'];
            $data['sign'] = $res['sign'];
            $data['store_name'] = $res['title'];
            $data['mobile'] = $res['mobile'];
            $data['qrcode'] = '';
            // 如果美容师二维码是空的,则生成二维码
            $mapc['type'] = 1;
            $mapc['content'] = $data['user_id'];
            $mapc['category'] = 1;
            $res_code = $this->qrcodeMod->where($mapc)->limit(1)->find();
            if ($res_code) {
                $data['qrcode'] = $res_code['qrcode'];
            }else{
                // 生成二维码
                $urls = 'type=1&store_id='.$res['storeid'].'&mobile='.$res['mobile'].'&goods_id=0';
                $msg = config('url.qrcode_jump_url').'?'.$urls;// 扫码后跳转的url
                // $msg = json_encode($msg);
                $filename = $user_id.'_'.time().'.png';
                $data['qrcode'] = $this->makeQrCode($msg , $filename);
                if ($data['qrcode']) {
                    // 插入
                    $datab['type'] = 1;
                    $datab['store_id'] = $res['storeid'];
                    $datab['content'] = $user_id;
                    $datab['qrcode'] = $data['qrcode'];
                    $datab['qrcode_data'] = $msg;
                    $datab['create_time'] = time();
                    $this->qrcodeMod->save($datab);
                }
            }
        }
        return $data;
    }
    
    /*
     * 功能:生成二维码
     * */
    public function makeQrCode($msg='',$qrcode_name='qrcode.png')
    {
        $domain = $this->domain;
        $qrcode = new QRcode();
        $errorCorrectionLevel = 'H';    //容错级别
        $matrixPointSize = 6;           //生成图片大小
        //生成二维码图片
        $filename = $this->imgPath.'/'.$qrcode_name;
        $qrcode::png($msg,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        // 返回二维码图片完整地址
        $img_url = $domain.'static/api/images/'.$qrcode_name;
        $img_url = $this->upQiniuImg($filename, $qrcode_name);
        return $img_url;
    }
    /*
     * 功能:上传图片到七牛
     * 请求:$imgPath=>图片全路径
     *      $filename=>图片名称
     * */
    public function upQiniuImg($imgPath , $filename='')
    {
        $upimg = new Upimg();
        $img_url = $upimg->upImg($imgPath , $filename);
        return $img_url;
    }
}