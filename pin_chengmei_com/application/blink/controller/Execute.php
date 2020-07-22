<?php

namespace app\blink\controller;
use think\Db;
use think\Queue;
use weixin\WeixinAccount;
use weixin\WeixinRefund;

/**
 * swagger: 计划任务
 */
class Execute extends Base
{
    //检测内部员工
    public function staff(){
        header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
        header('Access-Control-Allow-Credentials: true'); // 设置是否允许发送 cookies
        header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin'); // 设置允许自定义请求头的字段
        set_time_limit(0);

        $url = 'http://dingding.chengmei.com/scrm/user.shtml';
        $res = httpPost($url,[1]);
        if(is_array($res) && $res['status'] == 200){
            $data = $res['obj'];
            if(!empty($data)){
                $insert = [];
                //老员工手机集合
                $old_mobiles = Db::name('blink_staff')->column('mobile');
                foreach ($data as $k=>$val){
                    $insert[] = [
                        'staff_id' => $val['jobnumber'],
                        'mobile' => $val['mobile'],
                        'name' => $val['name'],
                        'flag' => $val['flag'],
                        'position' => $val['position'],
                        'supdept' => $val['supdept'],
                        'dept' => $val['dept'],
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }

                if(!empty($insert)){
                    Db::name('blink_staff')->delete();
                    $re = Db::name('blink_staff')->insertAll($insert);
                    if($re){
                        foreach ($old_mobiles as $val){
                            $mobile = Db::name('blink_staff')->where('mobile',$val)->value('mobile');
                            if(empty($mobile)){
                                $f_mobile = self::$redis->exists('staff_' . $mobile);
                                if(!$f_mobile){
                                    self::$redis->DEL('staff_'.$val);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    //计划任务 10min 查询用户的分享记录获取分享的盒子编号，得到接收人集合并查询接收人是否购买，符合条件赠送一张清洁券
    public function getCurrentUserBoxGiveRecord(){
        set_time_limit(0);
        //1.获取支付订单（2天前）
        $orders = Db::name('blink_order')
            ->where('pay_status','=',1)
            ->where('pay_time','>=',time()- 86400*2)
            ->select();
        if(empty($orders)){
            return '当前时间暂无订单';
        }
        $config = Db::name('blink_box_config')->where('id',1)->find();
        //分享人数
        $share_number = $config['share_number'];
        //清洁卡价格
        $_price = Db::name('goods')->where('id',$config['share_goods'])->value('activity_price');
        $boxModel = Db::name('blink_order_box');
        foreach ($orders as $k=>$val){
            $order_id = $val['id'];
            $order_uid = $val['uid'];
            //获取当前用户数据
            $storeInfo = Db::table('ims_bj_shopn_member')
                ->alias('m')
                ->join(['ims_bwk_branch'=>'bwk'],'m.storeid=bwk.id','left')
                ->join(['sys_departbeauty_relation'=>'r'],'bwk.sign=r.id_sign','left')
                ->join(['sys_department'=>'dpt'],'r.id_department=dpt.id_department','left')
                ->field('bwk.title,bwk.sign,dpt.st_department,m.mobile,m.storeid')
                ->find();
            //1查询当前订单的盒子记录
            $box_list = $boxModel->where('status','=',0)
                ->where('is_give','=',1)
                ->column('blinkno,id,uid','id');
            if(empty($box_list)){
                continue;
            }
            $blinknos = array_column('blinkno',$box_list);
            $ids = array_column('id',$box_list);
            //2根据记录查询接受人uid
            $uids = $boxModel
                ->where('blinkno','in',$blinknos)
                ->where('pid','in',$ids)
                ->column('uid');
            //3根据分享数据查询用户类型
            $members_uids = Db::table('ims_bj_shopn_member')
                ->where('id','in',$uids)
                ->where('activity_flag','=',9999)//新客
                ->column('id');
            //4根据用户集合查询是否购买 数量
            $count = Db::name('blink_order')
                ->where('uid','in',$members_uids)
                ->count();
            //查询清洁卡数量type=1
            $number = Db::name('blink_box_coupon_user')
                ->where('type',1)
                ->count();
            $aaa = floor($count/$share_number);
            if($number <= $aaa){
                //卡券数量 == 新用户订单数量 / 分享数量
                continue;
            }
            if ($aaa > $number){
                $bb = $aaa - $number;//剩余未生成的卡券
                $insert = [];
                for($i=0;$i<$bb;$i++){
                    //用户添加卡券
                    $cardno = generate_promotion_code(1,1,'',8);

                    $insert[] = [
                        'user_id' => $order_uid,
                        'goods_id' => $config['share_goods'],
                        'price' => $_price,
                        'branch' => $storeInfo['title'],
                        'sign' => $storeInfo['sign'],
                        'depart' => $storeInfo['st_department'],
                        'storeid' => $storeInfo['storeid'],
                        'mobile' => $storeInfo['mobile'],
                        'qrcode' => pickUpCode('blinkcoupon_'.$cardno),//核销卡券
                        'ticket_code' => $cardno,
                        'share_status' => 0,
                        'type' => 1,
                        'insert_time' => time(),
                    ];
                }
                logs(date('Y-m-d H:i:s')."：".json_encode($insert),'a');
                if(!empty($insert)){
                    Db::name('blink_box_coupon_user')->insertAll($insert);
                }
            }
        }
    }
    public function getRats($rats = []){
        if(empty($rats)){
            $rats = Db::name('blink_box_card_image')
                ->where('type','=',0)
                ->where('cid','=',1)
                ->where('number','gt',0)
                ->column('id,number,name','id');
        }
        $arr = [];
        foreach ($rats as $key => $val) {
            $arr[$val['id']] = $val['number'];
        }
        return getRand($arr);
    }
    public function get_goods_reba_id($uid= 0){
        //获取当前用户已出现的商品
        $param['goods_cate'] = 11;
        $param['deputy_cate'] = 1;
        $param['stock'] = ['gt',0];//库存大于0
        $goods_list = GoodsModel::where($param)
            ->order('stock','desc')
            ->field('id,name,stock')->select();
        if(empty($goods_list)){
            return parent::returnMsg(0,'','单品库存不足');
        }
        $goods = [];
        foreach ($goods_list as $k=>$val){
            $goods[$k]['goods_id'] = $val['id'];
            $goods[$k]['stock'] = $val['stock'];
            $goods[$k]['count'] = Db::name('blink_box_coupon_user')
                ->where('uid',$uid)
                ->where('goods_id',$val['id'])
                ->where('type',0)
                ->count();
        }
        $last_names = array_column($goods,'count');
        array_multisort($last_names,SORT_DESC,$goods);
        //获取最后一个
        $end = end($goods);
        return $end['goods_id'];
    }
    //批量处理未拆盲盒
    public function batch_take_blink(){
        set_time_limit(0);
        //获取未拆盲盒
        $param['status']  = 0;//未拆
        $param['is_give'] = 0;//未赠送
        $param['create_time'] = ['<=',1578499199];//未赠送
        //检测数据是否存在
        $lists = Db::name('blink_order_box')->where($param)->select();
        //var_dump($lists);exit;
        if(empty($lists)){
            return parent::returnMsg(0,'','盲盒不存在');
        }
        foreach ($lists as $k=>$val){
            //检测是否已拆
            $param['id'] = $val['id'];
            $res = Db::name('blink_order_box')->where($param)->find();
            if(empty($res)){
                continue;
            }
            Db::startTrans();
            $date = 1585618611;//time();
            try {
                $box_data = [
                    'status'      => 1,
                    'close_time'  => 0,
                    'update_time' => $date
                ];
                //盲盒设置已拆
                Db::name('blink_order_box')->where($param)->update($box_data);
                echo '盲盒ID：'.$val['id'].PHP_EOL;
                //随机查询一条鼠卡图片ID
                $rat_thumb_id = $this->getRats();
                //生成一张鼠卡
                $cardno = generate_promotion_code($val['uid'], 1, '', 8)['0'];
                $ca_id = Db::name('blink_order_box_card')->insert([
                    'blinkno'     => $val['blinkno'],
                    'uid'         => $val['uid'],
                    'cardno'      => $cardno,
                    'qrcode'      => '',//使用时生成核销二维码
                    'thumb_id'    => $rat_thumb_id,
                    'status'      => 0,
                    'create_time' => $date,
                    'update_time' => $date,
                    'close_time'  => 0
                ], false, true);
                echo '鼠卡ID：'.$ca_id.PHP_EOL;
                //卡片数量减1
                $this->setDec('blink_box_card_number', 1);
                //对应鼠卡减1
                if ($this->getCacheString('blink_default_rats_' . $rat_thumb_id)) {
                    $this->setDec('blink_default_rats_' . $rat_thumb_id, 1);
                }
                Db::name('blink_box_config')->where('id', 1)->setDec('number', 1);

                //生成商品记录 以卡券记录形式展示
                $goods_id = $this->get_goods_reba_id($val['uid']);//$this->getGoods();//随机获取商品ID
                $_price = Db::name('goods')->where('id', $goods_id)->value('activity_price');
                $ticket_code = generate_promotion_code($goods_id, 1, '', 8)[0];
                //返回卡券商品ID
                $res = Db::name('blink_box_coupon_user')->insert([
                    'blinkno'      => $val['blinkno'],
                    'uid'          => $val['uid'],
                    'goods_id'     => $goods_id,
                    'price'        => $_price,
                    'par_value'    => $_price,
                    'ticket_code'  => $ticket_code,
                    'qrcode'       => '',//使用时生成核销二维码
                    'type'         => 0,//一般商品
                    'source'       => 0,//来源 0拆盲盒 1好友赠送 2好友助理 3合成卡片
                    'status'       => 0,//未核销
                    'is_batch1'    => 10,
                    'remark'       => '后台批量自动拆盲盒',//未赠送
                    'share_status' => 0,//未赠送
                    'insert_time'  => $date,
                    'update_time'  => $date,
                ], false, true);
                echo '优惠券商品ID：'.$res.PHP_EOL;
                echo '<hr/>';
                //商品库存减1
                if ($this->getCacheString('blink_box_goods_stock_' . $goods_id)) {
                    $this->setDec('blink_box_goods_stock_' . $goods_id, 1);
                }
                Db::name('goods')->where('id', $goods_id)->setDec('stock', 1);
                Db::commit();
            }catch (\Exception $e) {
                echo $e->getMessage().PHP_EOL;
                Db::rollback();
            }
        }
    }



}