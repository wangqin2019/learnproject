<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2019/9/21
 * Time: 10:44
 */

namespace app\api\service;
use qiniu_transcoding\Upimg;
use qrcode\QrcodeImg;
use think\Db;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
/**
 * 卡券服务类
 */
class TicketSer extends BaseSer
{
    // 订单支付状态
    protected $payStatus = [1,2,7];// 已支付但未核销过
    // 券号二维码存放路径
    // protected $qrcodePath = 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\static\api\images\\';
    // 异步执行上传url
    // protected $ybUrl = 'http://localhost:81/api/call_back/ybUpimg';
    // 券号二维码存放路径
    public $qrcodePath = '/home/canmay/www/live/public/static/api/images/';
    // 异步执行上传url
    public $ybUrl = 'http://live.qunarmei.com/api/call_back/ybUpimg';
    // 七牛图片地址
    public $qiniuImg = 'http://appc.qunarmei.com/';
    // 能使用现金券产品分类
    protected $pcate = [21];
    // 魔境礼券图片
    protected $magiclandImg = '';
    // 皮肤礼券图片
    protected $skinImg = '';
    // 现金券10元图片
    protected $cash10Img = '';
    protected $cash10Tips = '满100元可抵用10元';
    protected $cash10Price = '100';
    // 现金券20元图片
    protected $cash20Img = '';
    protected $cash20Tips = '满200元可抵用20元';
    protected $cash20Price = '200';
    // 现金券50元图片
    protected $cash50Img = '';
    protected $cash50Tips = '满300元可抵用50元';
    protected $cash50Price = '300';
    // missshop分享活动url
    protected $missShareUrl = 'https://pin.qunarmei.com/luck_draw/log.html';
    // 需上传的奖券二维码图片
    public $qrcodeImgs = [];
    // 分享有礼描述信息
    protected $shareDescribe = '去哪美misssshop分享有礼活动';
    // 可用现金券白名单,所有都可用
    protected $userIds = [16829,20351,16982,20180,20322,17012,20301,20380,20383];
    // 转客现金券及礼券优惠券地址
    protected $transfer_ticket = [
            'jp418' => 'http://ml.chengmei.com/jp2_0416.png',// 已使用
            'cash_10_0'=>'https://pgimg1.qunarmei.com/cash_10_0.png',//10元现金券未激活
            'cash_10_1'=>'https://pgimg1.qunarmei.com/cash_10_1.png',//10元现金券未使用
            'cash_10_2'=>'https://pgimg1.qunarmei.com/cash_10_2.png',//10元现金券已核销
            'cash_20_0'=>'https://pgimg1.qunarmei.com/cash_20_0.png',
            'cash_20_1'=>'https://pgimg1.qunarmei.com/cash_20_1.png',
            'cash_20_2'=>'https://pgimg1.qunarmei.com/cash_20_2.png',
            'cash_50_0'=>'https://pgimg1.qunarmei.com/cash_50_0.png',
            'cash_50_1'=>'https://pgimg1.qunarmei.com/cash_50_1.png',
            'cash_50_2'=>'https://pgimg1.qunarmei.com/cash_50_2.png',
            'pifu_0'=>'https://pgimg1.qunarmei.com/pifu_0.png',//皮肤检测券未使用
            'pifu_1'=>'https://pgimg1.qunarmei.com/pifu_1.png',//皮肤检测券已使用
            'shuangqing_0'=>'https://pgimg1.qunarmei.com/shuangqing_0.png',
            'shuangqing_1'=>'https://pgimg1.qunarmei.com/shuangqing_1.png'
    ];
    // 不能使用直播消费券的产品分类
    protected $no_cash_pcate = [31];
    /**
     * 礼券激活/核销处理
     * @param string $type 卡券类型
     * @param string $card_no 卡券号码
     * @param string $store_id 门店id
     * @return string
     */
    public function cardHandle($type,$card_no,$store_id)
    {
        if($type == 'activate'){
            $map['ticket_code'] = $card_no;
            $res = $this->getTick($map);
            if($res && $res['storeid'] == $store_id){

                // 现金券激活
                if($res['type'] == 10){
                    if($res['status'] == -1){
                        $this->cashActivation($card_no,$res['par_value']);
                        $this->msg = '现金券激活成功';
                        $this->code = 1;
                    }else{
                        $this->msg = '现金券已激活,请勿重复激活';
                    }
                }elseif($res['type'] == 11 || $res['type'] == 12){
                    // 礼券核销
                    if($res['status'] == 0){
                        $this->code = 1;
                        $this->giftVerification($card_no,$res['type']);
                        $this->msg = '礼券核销成功';
                    }elseif($res['status'] == 2){
                        $this->msg = '礼券已核销,请勿重复核销';
                    }
                }elseif($res['type'] == 23){
                    // 418奖券核销
                    if($res['status'] == 0){
                        $this->code = 1;
                        $this->jpVerification($card_no,$res['type']);
                        $this->msg = '418奖券核销成功';
                    }elseif($res['status'] == 1){
                        $this->msg = '418奖券已核销,请勿重复核销';
                    }
                }elseif($res['type'] > 23){
                    // 418奖券核销
                    if($res['status'] == 0){
                        $this->code = 1;
                        $this->jpVerification($card_no,$res['type']);
                        $this->msg = '卡券核销成功';
                    }elseif($res['status'] == 1){
                        $this->msg = '卡券已核销,请勿重复核销';
                    }
                }
            }else{
                $this->msg = '门店不一致,无处理权限';
            }
        }elseif($type == 'lucky'){
            // 中奖券核销
            $map['l.order_sn'] = $card_no;
            $res = $this->getLuckTickMem($map);
            if($res && $res['storeid'] == $store_id){
                if($res['flag'] == 1){
                    $this->msg = '中奖券已核销,请勿重复处理';
                }else{
                    $this->code = 1;
                    $data['flag'] = 1;
                    $data['update_time'] = time();
                    $map1['order_sn'] = $card_no;
                    $this->editLuckTick($data,$map1);
                    $this->msg = '中奖券核销成功';
                }
            }else{
                $this->msg = '门店不一致,无处理权限';
            }
        }elseif($type == 'order'){
            // missshop订单核销
            $res = $this->orderActivation($card_no);
            if($res == -1){
                $this->code = 1;
                $this->msg = '订单已核销,请勿重复处理';
            }elseif($res == 1){
                $this->code = 1;
                $this->msg = '订单核销成功';
            }elseif($res == -2){
                $this->code = 1;
                $this->msg = '订单未支付,请先完成支付';
            }else{
                $this->code = 0;
                $this->msg = '订单核销失败';
            }
        }elseif($type == 'livehelper'){
            $map['ticket_code'] = $card_no;
            $res = $this->getTick($map);
            if($res){
                if($res['storeid'] != $store_id){
                    $this->code = 0;
                    $this->msg = '门店不一致,无处理权限';
                    return $this->returnArr();
                }
                if($res['status'] == 1){
                    $this->code = 0;
                    $this->msg = '卡券已使用,请勿重复处理';
                    return $this->returnArr();
                }
                // 直播助手抽奖券核销
                $res_jp = $this->jpVerification($card_no,$res['type']);
                $this->code = 1;
                $this->msg = '卡券核销成功';
            }else{
                $this->code = 0;
                $this->msg = '奖券号不存在';
            }

        }
        return $this->returnArr();
    }
    /**
     * missshop订单券核销
     * @param string $card_no 卡券号码
     * @return string
     */
    protected function orderActivation($card_no)
    {
        $flag = 0;
        $ordSer = new OrderSer();
        $map['ordersn'] = $card_no;
        $res = $ordSer->getOrd($map);
        if($res && $res['status']==3){
            $flag = -1;
        }elseif($res && in_array($res['status'],$this->payStatus)){
            $datao['status'] = 3;
            $mapo['ordersn'] = $card_no;
            $ordSer->editOrd($datao,$mapo);
            $flag = 1;
        }else{
            $flag = -2;
        }
        return $flag;
    }
    /**
     * 418奖券核销
     * @param string $card_no 卡券号码
     * @param string $type 卡券类型
     * @return string
     */
    protected function jpVerification($card_no,$type)
    {
        $map['ticket_code'] = $card_no;
        $map['type'] = $type;

        $data['status'] = 1;
        if($type == 23){
            $data['draw_pic'] = $this->transfer_ticket['jp418'];
        }elseif($type == 29){
//            $data['draw_pic'] = $this->transfer_ticket['jp418'];// 直播助手已使用卡券是否需要换图
        }else{
            $res = Db::table('ims_bj_activity_ticket_info i')->join(['pt_ticket_user'=>'u'],['i.id=u.ticket_info_id'],'left')->where($map)->limit(1)->find();
            if ($res) {
                $data['draw_pic'] = $res['used_img'];
            }
        }
        $data['update_time'] = date('Y-m-d H:i:s');
        $this->editTick($data,$map);
    }
    /**
     * 现金券激活
     * @param string $card_no 卡券号码
     * @param string $par_val 卡券价格
     * @return string
     */
    protected function cashActivation($card_no,$par_val)
    {
        $map['ticket_code'] = $card_no;
        $data['status'] = 0;
        $data['draw_pic'] = $this->transfer_ticket['cash_10_1'];
        if($par_val == 20){
            $data['draw_pic'] = $this->transfer_ticket['cash_20_1'];
        }elseif($par_val == 50){
            $data['draw_pic'] = $this->transfer_ticket['cash_50_1'];
        }
        $data['update_time'] = date('Y-m-d H:i:s');
        $this->editTick($data,$map);
    }
    /**
     * 礼券核销
     * @param string $card_no 卡券号码
     * @param string $card_no 礼券类型
     * @return string
     */
    protected function giftVerification($card_no,$type)
    {
        $map['ticket_code'] = $card_no;
        $data['status'] = 2;
        $data['draw_pic'] = $type==11?$this->transfer_ticket['pifu_1']:$this->transfer_ticket['shuangqing_1'];
        $data['update_time'] = date('Y-m-d H:i:s');
        $this->editTick($data,$map);
    }
    /**
     * 中奖券核销
     * @param string $card_no 卡券号码
     * @param string $card_no 礼券类型
     * @return string
     */
    protected function luckVerification($card_no,$type)
    {
        $map['order_sn'] = $card_no;
        $data['status'] = 2;
        // $data['draw_pic'] = $type==11?$this->transfer_ticket['pifu_1']:$this->transfer_ticket['shuangqing_1'];
        $data['update_time'] = date('Y-m-d H:i:s');
        $this->editTick($data,$map);
    }
    /**
     * missshop分享有礼
     * @param string $user_id 分享用户id
     * @return string
     */
    public function missActShare($user_id)
    {
        // 根据id查询号码
        $userSer = new User();
        $mapu['id'] = $user_id;
        $res = $userSer->getUser($mapu);
        if($res){
            $this->code = 1;
            $this->msg = 'missshop分享有礼活动';
            $this->data['url'] = $this->missShareUrl.'?share_mobile='.$res['mobile'];
            $this->data['describe'] = $this->shareDescribe;
        }
        return $this->returnArr();
    }
    /**
     * 顾客注册
     * @param string $mobile 用户号码
     * @param string $share_mobile 分享用户号码
     * @return
     */
    public function userRegister($mobile,$share_mobile)
    {
        $userSer = new User;
        // 查询用户是否已注册
        $mapu['mobile'] = $mobile;
        $resm = $userSer->getUser($mapu);
        if($resm){
            $this->msg = '用户已注册';
            return $this->returnArr();
        }
        $map['mobile'] = $share_mobile;
        $resu = $userSer->getUser($map);
        if($resu){
            $data = [
                'weid' => 1,
                'storeid' => $resu['storeid'],
                'pid' => $resu['id'],
                'staffid' => $resu['staffid'],
                'realname' => '手机用户'.substr($mobile,-3),
                'mobile' => $mobile,
                'createtime' => time(),
                'fg_viprules' => 1,
                'fg_vipgoods' => 1,
                'id_regsource' => 6,// app分享注册
                'activity_flag' => '8808'
            ];
            $res = $userSer->addUser($data);
            if($res){
                $this->code = 1;
                $this->msg = '用户注册成功';
            }else{
                $this->msg = '用户注册失败';
            }
        }else{
            $this->msg = '分享用户已失效';
        }
        return $this->returnArr();
    }
    /**
     * 获取missshop现金券列表
     * @param array $goods_id 商品id
     * @param string $price 总价
     * @param int $user_id 用户id
     * @param array $cars_id 购物车id列表
     * @return false
     */
    public function getCashList($goods_id,$price,$user_id,$cars_id)
    {
        // 1.查询商品是否能使用现金券
        $goodsSer = new GoodsSer();
        $mapg = [];$res_goods = [];
        if($goods_id){
            $mapg['id'] = $goods_id;
            $res_goods = $goodsSer->getGoods($mapg);
        }elseif($cars_id){
            $cars_id = json_decode($cars_id,true);
            // 根据购物车id查询商品id
            $mapg['c.id_car'] = ['in',$cars_id];
            $res_goods = $goodsSer->getCarGoods($mapg);
            if($res_goods){
                $pcate = [];
                foreach ($res_goods as $v) {
                    $res_goods['pcate'] = $v['pcate'];
                }
            }
        }else{
            $this->msg = 'goods_id和cars_id必须有1个不能为空';
            $this->data = (object)[];
            return $this->returnArr();
        }
        if(!($res_goods && in_array($res_goods['pcate'],$this->pcate))){
            $this->msg = '没有使用现金券权限';
            $this->data = (object)[];
            return $this->returnArr();
        }
        // 2.查询订单总价格可以使用的现金券
        $map = [];
        if($price>=100 && $price<200){
            $map['par_value'] = 10;
        }elseif($price>=200 && $price<300){
            $map['par_value'] = ['in',[10,20]];
        }elseif($price>=300){
            $map['par_value'] = ['in',[10,20,50,320]];
        }
        $this->code = 1;
        if(!$map && !in_array($user_id,$this->userIds)){
            $this->code = 0;
            $this->msg = '暂无可用现金券1';
            $this->data = (object)[];
            return $this->returnArr();
        }

        // 如果在特殊名单中的号码,则显示所有可用优惠券
        if(in_array($user_id,$this->userIds)){
            $map = [];
        }

        $map['u.type'] = 10;
        $map['u.status'] = 0;
        $map['m.id'] = $user_id;
        $res = Db::table('pt_ticket_user u')
            ->join(['ims_bj_shopn_member'=>'m'],['u.mobile=m.mobile'],'LEFT')
            ->field('u.id,u.type,u.par_value,u.ticket_code')
            ->order('u.par_value desc')
            ->where($map)
            ->select();
        if($res){
            $ids = [];
            foreach ($res as $v) {
                $ids[] = (string)$v['id'];
            }
            $this->data['card_id'] = $ids;
            $this->msg = '可用现金券id列表';
        }else{
            $this->code = 0;
            $this->msg = '暂无可用现金券2';
            $this->data = (object)[];
        }
        return $this->returnArr();
    }
    /**
     * missshop订单支付后抽奖中奖券查询
     * @param array $map 查询条件
     * @return int|string
     */
    public function getLuckTickMem($map)
    {
        $res = Db::table('pt_order_lucky l')
            ->join(['ims_bj_shopn_order'=>'o'],['o.ordersn = l.order_sn'],'LEFT')
            ->field('o.storeid,l.flag,l.qrcode,l.uid')
            ->where($map)
            ->limit(1)
            ->find();
        return $res;
    }

    /**
     * missshop订单支付后抽奖中奖券核销
     * @param array $data 数据
     * @param array $map 查询条件
     * @return int|string
     */
    public function editLuckTick($data,$map)
    {
        $res = Db::table('pt_order_lucky')->where($map)->update($data);
        return $res;
    }
    /**
     * 修改单张卡券
     * @param array $data 修改数据
     * @param array $map 查询条件
     * @return
     */
    public function editTick($data,$map)
    {
        $res = Db::table('pt_ticket_user')->where($map)->update($data);
        return $res;
    }
    /**
     * 查询单张卡券
     * @param $map
     * @return
     */
    public function getTick($map)
    {
        $res = Db::table('pt_ticket_user')->where($map)->limit(1)->find();
        return $res;
    }

    public function sendMissshopCard($mobile)
    {
        $rest = [
            'code' => 0,
            'msg' => '用户不存在,发送卡券失败'
        ];
        // 查询是否发送过
        $map['mobile'] = $mobile;
        $map['type'] = 10;
        $resc = $this->getTick($map);
        if(!$resc){
            // 查询用户信息
            $userSer = new User();
            $mapu['m.mobile'] = $mobile;
            $resu = $userSer->getUserAll($mapu);
            if($resu){
                $user_id = (int)$resu['id'];
                $tick_code = $this->makeCode($user_id);
                $data = [
                    'depart' => $resu['bsc'],
                    'storeid' => $resu['store_id'],
                    'branch' => $resu['title'],
                    'sign' => $resu['sign'],
                    'mobile' => $resu['mobile'],
                    'ticket_code' => $tick_code,
                    'type' => 10,// 10:现金券,11:魔境礼券,12:皮肤礼券
                    'insert_time' => date('Y-m-d H:i:s'),
                    'qrcode' => $this->makeQrcode($tick_code),
                ];
                // 发魔境礼券
                $data['type'] = 11;
                if($this->transfer_ticket['pifu_0']){
                    $data['draw_pic'] = $this->transfer_ticket['pifu_0'];
                }
                $this->addTick($data);
                // 发皮肤礼券
                $data['type'] = 12;
                $data['ticket_code'] = $this->makeCode($user_id);
                $data['qrcode'] = $this->makeQrcode($data['ticket_code']);
                if($this->transfer_ticket['shuangqing_0']){
                    $data['draw_pic'] = $this->transfer_ticket['shuangqing_0'];
                }
                $this->addTick($data);
                // 发现金10礼券
                $data['par_value'] = 10;
                $data['status'] = -1;
                $data['type'] = 10;
                $data['ticket_code'] = $this->makeCode($user_id);
                $data['qrcode'] = $this->makeQrcode($data['ticket_code']);
                if($this->transfer_ticket['cash_10_0']){
                    $data['draw_pic'] = $this->transfer_ticket['cash_10_0'];
                }
                $data['price'] = $this->cash10Price;
                $data['remark'] = $this->cash10Tips;
                $this->addTick($data);
                // 发现金20礼券
                $data['par_value'] = 20;
                $data['ticket_code'] = $this->makeCode($user_id);
                $data['qrcode'] = $this->makeQrcode($data['ticket_code']);
                if($this->transfer_ticket['cash_20_0']){
                    $data['draw_pic'] = $this->transfer_ticket['cash_20_0'];
                }
                $data['price'] = $this->cash20Price;
                $data['remark'] = $this->cash20Tips;
                $this->addTick($data);
                // 发现金50礼券
                $data['par_value'] = 50;
                $data['ticket_code'] = $this->makeCode($user_id);
                $data['qrcode'] = $this->makeQrcode($data['ticket_code']);
                if($this->transfer_ticket['cash_50_0']){
                    $data['draw_pic'] = $this->transfer_ticket['cash_50_0'];
                }
                $data['price'] = $this->cash50Price;
                $data['remark'] = $this->cash50Tips;
                $this->addTick($data);

                $rest['code'] = 1;
                $rest['msg'] = '发送卡券成功';
                // 异步多张图片上传
                $this->ybUpimg($this->qrcodeImgs);
            }
        }else{
            $rest['msg'] = '卡券已经发送过';
        }
        return $rest;
    }

    /**
     * 添加卡券
     * @param $data
     * @return int|string
     */
    public function addTick($data)
    {
        $res = Db::table('pt_ticket_user')->insertGetId($data);
        return $res;
    }

    /**
     * 生成券号
     * @param int $user_id 用户id
     * @param int $val 券价格
     * @return string
     */
    public function makeCode($user_id,$val=0)
    {
//        $code = time().$user_id;
//        if($val){
//            $code .= $val;
//        }else{
//            $code .= rand(11,99);
//        }
        $code = time().rand(11,99);
        return $code;
    }
    /**
     * 生成券号二维码
     * @param string $code 券号
     * @return string
     */
    public function makeQrcode($code)
    {
        $img = 'activate_'.$code.'.png';
        // 生成二维码
//        $res = $this->makeErweima($code,$img);
        $code = 'activate_'.$code;
        // $url = $this->ybUrl.'?code='.$code.'&img='.$img;
//        $this->ybExcute($url);
        // $this->ybCurl($url);
        $data = [
            'code' => $code,
            'img' => $img
        ];
        $this->qrcodeImgs[] = $data;
        $res = $this->qiniuImg.$img;
        return $res;
    }
    /**
     * 异步上传图片
     * @param $arr
     */
    public function ybUpimg($arr)
    {
        $str = json_encode($arr);
        $url = $this->ybUrl.'?str='.$str;
        $this->ybCurl($url);
    }
    public function makeEwm($code,$img)
    {
        $qrcodeSer = new QrcodeImg();
        // 服务器路径
//        $file_path = '/home/canmay/www/test.qunarmeic.com/static/api/images/';
//        $img_url = 'http://testc.qunarmei.com:9091/static/api/images/';
        $value = $code;         //二维码内容
        $errorCorrectionLevel = 'L';  //容错级别
        $matrixPointSize = 11.5;      //生成图片大小
        //生成二维码图片
        $file_path = $this->qrcodePath.$img;
        \QRcode::png($value, $file_path, $errorCorrectionLevel, $matrixPointSize, 1.5);
        $QR = $file_path;        //已经生成的原始二维码图片文件
        $res_img_content = file_get_contents($QR);
        // echo "res_img_content:<pre>";print_r($res_img_content);
        if(empty($res_img_content)){
            // 读取不到的时候,重新生成一波
            $res_img = \QRcode::png($value, $file_path, $errorCorrectionLevel, $matrixPointSize, 1.5);
            $res_img_content = file_get_contents($QR);
            // echo "res_img:<pre>";print_r($res_img);die;
        }
        $QR = imagecreatefromstring($res_img_content);
        //输出图片
        imagepng($QR, 'qrcode.png');
        imagedestroy($QR);
//        $img_url = $this->qiniuImg.$img;

        // 上传二维码图片至七牛
        $qiniuSer = new Upimg();
        $path = $this->qrcodePath.$img;
        $img_url = $qiniuSer->upImg($path,$img);
        return $img_url;
    }
    /*
     * 功能:生成二维码
     * */
    public function makeErweima($msg='',$qrcode_name='qrcode.png')
    {
        // Create a basic QR code
        $qrCode = new QrCode($msg);
        $qrcode_path = APP_PATH.'../public/static/api/images/';
        $qrCode->setSize(300);
// Set advanced options
        $qrCode->setWriterByName('png');
        $qrCode->setMargin(10);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
//        $qrCode->setLabel('Scan the code', 16, __DIR__.'/../assets/fonts/noto_sans.otf', LabelAlignment::CENTER);
//        $qrCode->setLogoPath($qrcode_path.'normal_photo.png');
//        $qrCode->setLogoWidth(150);
//        $qrCode->setRoundBlockSize(true);
//        $qrCode->setValidateResult(false);
//        $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);
        // Directly output the QR code
//        header('Content-Type: '.$qrCode->getContentType());
//        echo $qrCode->writeString();

//        $qrcode_name = 'qrcode.png';
        // Save it to a file
        $qrCode->writeFile($qrcode_path.$qrcode_name);
//        $img_url = 'http://localhost:81/static/api/images/'.$qrcode_name;
        // 上传二维码图片至七牛
        $qiniuSer = new Upimg();
        $path = $this->qrcodePath.'/'.$qrcode_name;
        $img_url = $qiniuSer->upImg($path,$qrcode_name);
        return $img_url;
    }

    /**
     * 异步执行url方法
     * @param $url
     * @param array $post_data
     * @param array $cookie
     * @return bool
     */
    public function ybExcute($url,$post_data=array(),$cookie=array())
    {
        $url_arr = parse_url($url);
        $port = isset($url_arr['port']) ? $url_arr['port'] : 80;

        if ($url_arr['scheme'] == 'https') {
            $url_arr['host'] = 'ssl://' . $url_arr['host'];
        }
        $fp = fsockopen($url_arr['host'], $port, $errno, $errstr, 30);
        if (!$fp) return false;

        $getPath = isset($url_arr['path']) ? $url_arr['path'] : '/index.php';
        $getPath .= isset($url_arr['query']) ? '?' . $url_arr['query'] : '';

        $method = 'GET';  //默认get方式
        if (!empty($post_data)) $method = 'POST';

        $header = "$method  $getPath  HTTP/1.1\r\n";
        $header .= "Host: " . $url_arr['host'] . "\r\n";

        if (!empty($cookie)) {  //传递cookie信息
            $_cookie = strval(NULL);
            foreach ($cookie AS $k => $v) {
                $_cookie .= $k . "=" . $v . ";";
            }
            $cookie_str = "Cookie:" . base64_encode($_cookie) . "\r\n";
            $header .= $cookie_str;
        }
        if (!empty($post_data)) {  //传递post数据
            $_post = array();
            foreach ($post_data AS $_k => $_v) {
                $_post[] = $_k . "=" . urlencode($_v);
            }
            $_post = implode('&', $_post);
            $post_str = "Content-Type:application/x-www-form-urlencoded; charset=UTF-8\r\n";
            $post_str .= "Content-Length: " . strlen($_post) . "\r\n";  //数据长度
            $post_str .= "Connection:Close\r\n\r\n";
            $post_str .= $_post;  //传递post数据
            $header .= $post_str;
        } else {
            $header .= "Connection:Close\r\n\r\n";
        }
        fwrite($fp, $header);
        //echo fread($fp,1024);
        usleep(1000); // 这一句也是关键，如果没有这延时，可能在nginx服务器上就无法执行成功
        fclose($fp);
        return true;
    }

    public function ybCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 结算时可选现金券列表
     */
    public function JsCashList($goods_id,$price,$user_id,$cars_id)
    {
        $this->code = 1;
        $this->msg = '暂无可用现金券';
        $arr = [];
        // 查询用户可用现金券列表
        $map['status'] = 0;
        $map['user_id'] = $user_id;
        $map['type'] = 24;// 直播消费券
        $res_card = \app\api\model\TicketUser::all($map);
        // 查询用户门店是否开启活动
        if($res_card){
            $storeid = 0;
            $card_id = [];
            foreach ($res_card as $v) {
                $storeid = $v['storeid'];
                $card_id[] = $v['id'];
            }
            $this->msg = '获取成功';
            $arr['card_id'] = $card_id;

            // 查询商品分类是否是直播商品
            if($goods_id){
                $res_gd = \app\api\model\BjGoods::get($goods_id);
                if(!($res_gd && !in_array($res_gd['pcate'],$this->no_cash_pcate))){
                    $arr = [];
                    $this->msg = '直播类商品暂不支持使用消费券1';
                }
            }else{
                $cars_id = json_decode($cars_id,true);
                $map_car['id_car'] = ['in',$cars_id];
                $res_car = \app\api\model\Car::with('goods')->where($map_car)->select();
//                print_r($res_car);die;
                if($res_car){
                    $flag = 0;
                    foreach ($res_car as $v) {
                        if(!in_array($v['goods']['pcate'],$this->no_cash_pcate)){
                            $flag = 1;
                        }
                    }
                    if(!$flag){
                        $arr = [];
                        $this->msg = '直播类商品暂不支持使用消费券2';
                    }
                }
            }
        }
        if($arr){
            $this->data = $arr;
        }else{
            $this->data = (object)[];
        }
        return $this->returnArr();
    }

}