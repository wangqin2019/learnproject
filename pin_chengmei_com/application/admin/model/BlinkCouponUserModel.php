<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class BlinkCouponUserModel extends Model
{
    // 设置返回数据集的对象名
    protected $resultSetType = 'collection';
    protected $name = 'blink_box_coupon_user';

    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getInsertTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getUpdateTimeAttr($value){
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getStatusAttr($value){
        return $value ? '<div><span class="label label-info">已核销</span></div>' : '<div><span class="label label-success">未核销</span></div>';
    }
    public function getShareStatusAttr($value){
        $status = [
            0 => [
                'name' => '未分享',
                'color' => 'default'
            ],
            1 => [
                'name' => '已分享',
                'color' => 'success'
            ],
            2 => [
                'name' => '分享中',
                'color' => 'info'
            ]
        ];
        return $value>=0 ? '<div><span class="label label-'.$status[$value]['color'].'">'.$status[$value]['name'].'</span></div>':'--';
    }
    public function getSourceAttr($value){
        $status = [
            0 => '拆盲盒',
            1 => '好友赠送',
            2 => '好友助力',
            3 => '合成卡',
        ];
        return $status[$value];
    }
    /**
     * Commit: 卡券数量
     * Function: getCouponCount
     * @Param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 10:58:02
     * @return int|string
     */
    public function getCouponCount($map)
    {
        return $this
            ->alias('coupon')
            ->join('pt_goods g','coupon.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=coupon.uid','left')
            ->join(['ims_bj_shopn_member' => 'm'],'member.staffid=m.id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->where($map)
            ->count();
    }
    /**
     * Commit: 获取卡券列表及关联信息
     * Function: getCouponLists
     * @Param $map
     * @Param $nowpage
     * @Param $limit
     * @Param bool $flag
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-10-21 15:29:51
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getCouponLists($map,$nowpage,$limit,$flag = true){
        $field = "coupon.*,coupon.status as status1";
        $field .= ",IFNULL(bwk.title,'--') title,bwk.sign";
        $field .= ",g.name,g.activity_price";
        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag";
        $field .= ",member.pid,member.staffid,member.storeid,member.originfid";
        $field .= ",bwk.title,bwk.sign";
        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";
        $field .= ",m.mobile as sellermobile,m.realname as sellername,m.storeid as sellerstoreid,m.code";
        $model = $this
            ->alias('coupon')
            ->join('pt_goods g','coupon.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=coupon.uid','left')
            ->join(['ims_bj_shopn_member' => 'm'],'member.staffid=m.id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map);
        if($flag){
            $model = $model ->page($nowpage, $limit);
        }
        $list = $model->order('coupon.id desc')->select()->toArray();

        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                //查询所属美容师
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $info['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $info['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = '';//原始（发货）美容师ID
                    $list[$k]['origin_code']    = '';//原始（发货）美容师ID
                    $list[$k]['origin_name']    = '';//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = '';//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = '';//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = '';//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_storeid'] = '';//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = '';//原始（发货）美容师手所属办事处
                }
                if($val['is_deliver'] == 1){
                    $list[$k]['deliver'] = '门店核销';
                }elseif($val['is_deliver'] == 2){
                    if($val['is_apply'] == 1){
                        $list[$k]['deliver'] = '用户申请发货';
                    }elseif($val['is_apply'] == 2){
                        $list[$k]['deliver'] = '前三批已发货';
                    }else{
                        $list[$k]['deliver'] = '--';
                    }
                }else{
                    $list[$k]['deliver'] = '--';
                }
            }
        }
        return $list;
    }
    public function getExportCouponLists($map){
        $field = "coupon.*";
        $field .= ",IFNULL(bwk.title,'--') title,bwk.sign";
        $field .= ",g.name,g.activity_price";
        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag,member.pid mpid";
        $field .= ",member.pid,member.staffid,member.storeid,member.originfid";
        $field .= ",bwk.title,bwk.sign";
        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";

        $field .= ",m.mobile as sellermobile,m.realname as sellername,m.storeid as sellerstoreid,m.code";
        $model = $this
            ->alias('coupon')
            ->join('pt_goods g','coupon.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=coupon.uid','left')
            ->join(['ims_bj_shopn_member' => 'm'],'member.staffid=m.id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map);
        $list = $model->order('coupon.id desc')->select()->toArray();

        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                //查询所属美容师
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $info['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $info['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = $val['staffid'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $val['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $val['sellername'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $val['sellermobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = $val['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $val['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_storeid'] = $val['sellerstoreid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $val['pertain_department_name'];//原始（发货）美容师手所属办
                }
                $list[$k]['origin_title_sign'] = $list[$k]['origin_title'].':'.$list[$k]['origin_sign'];
                $list[$k]['title_sign'] = $list[$k]['title'].':'.$list[$k]['sign'];

                $list[$k]['batch'] = '--';
                $list[$k]['deliver'] = '--';

                if($val['is_deliver'] == 1){
                    $list[$k]['deliver'] = '已收货';
                }elseif($val['is_deliver'] == 2){
                    $list[$k]['deliver'] = '发货中';
                }else{
                    $list[$k]['deliver'] = '未发货';
                }
                if($val['is_apply'] == 1){
                    $list[$k]['is_apply'] = '用户申请发货';
                }elseif($val['is_apply'] == 2){
                    $list[$k]['is_apply'] = '前三批锁定';
                }elseif($val['is_apply'] == 3){
                    $list[$k]['is_apply'] = '特殊锁定';
                }elseif($val['is_apply'] == 4){
                    $list[$k]['is_apply'] = '后台批量锁定';
                }else{
                    $list[$k]['is_apply'] = '--';
                }
                if($val['is_batch'] > 0 ){
                    $list[$k]['batch'] = "第{$val['is_batch']}批已发货";
                }
                //查询用户分享人信息
                /*$share_info = Db::table('ims_bj_shopn_member')
                    ->field('id,mobile,realname')
                    ->where('id',$val['mpid'])
                    ->find();
                $list[$k]['shareId']       = $share_info['id'];
                $list[$k]['shareMobile']   = $share_info['mobile'];
                $list[$k]['shareRealname'] = $share_info['realname'];*/
            }
        }
        return $list;
    }
    public function getExportCouponLists1($map,$page,$limit){
        $field = "coupon.*";
        $field .= ",IFNULL(bwk.title,'--') title,bwk.sign";
        $field .= ",g.name,g.activity_price";
        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag";
        $field .= ",member.pid,member.staffid,member.storeid,member.originfid";
        $field .= ",bwk.title,bwk.sign";
        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";

        $field .= ",m.mobile as sellermobile,m.realname as sellername,m.storeid as sellerstoreid,m.code";
        $list = $this
            ->alias('coupon')
            ->join('pt_goods g','coupon.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=coupon.uid','left')
            ->join(['ims_bj_shopn_member' => 'm'],'member.staffid=m.id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map)
            ->page($page,$limit)
            ->order('coupon.id desc')
            ->select()
            ->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                //查询所属美容师
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $info['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $info['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = $val['staffid'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $val['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $val['sellername'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $val['sellermobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = $val['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $val['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_storeid'] = $val['sellerstoreid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $val['pertain_department_name'];//原始（发货）美容师手所属办
                }
            }
        }
        return $list;
    }
    /**
     * Commit: 获取美容师信息
     * Function: getOrderBeautician
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-01 13:41:26
     * @Return false|\PDOStatement|string|\think\Collection
     */
    public function getOrderBeautician(){
        return $this
            ->alias('o')
            ->join(['ims_bj_shopn_member'=>'m'],'o.fid=m.id','left')
            ->field('o.fid,m.realname')
            ->where(['pay_status'=>1])
            ->group('o.fid')
            ->select();
    }
    public function getExportCouponListsABC($map){
        $field = "coupon.id,coupon.goods_id,coupon.uid";
        $field .= ",IFNULL(bwk.title,'--') title,bwk.sign";
        $field .= ",g.name,count(coupon.goods_id) count";
        $field .= ",IFNULL(member.realname,'--') realname,IFNULL(member.mobile,'--') mobile,member.staffid,member.activity_flag";
        $field .= ",member.pid,member.staffid,member.storeid,member.originfid";

        $field .= ",IFNULL(depart.st_department,'--') pertain_department_name";

        $field .= ",m.mobile as sellermobile,m.realname as sellername,m.storeid as sellerstoreid,m.code";
        $model = $this
            ->alias('coupon')
            ->join('pt_goods g','coupon.goods_id=g.id','left')
            ->join(['ims_bj_shopn_member' => 'member'],'member.id=coupon.uid','left')
            ->join(['ims_bj_shopn_member' => 'm'],'member.staffid=m.id','left')
            ->join(['ims_bwk_branch' => 'bwk'],'member.storeid=bwk.id','left')
            ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
            ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
            ->field($field)
            ->where($map);
        $list = $model->group('coupon.goods_id,bwk.sign')->order('bwk.sign asc')->select()->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $storeid = $val['storeid'];
                //查询所属美容师
                if($storeid == 1792){
                    //查询当前用户引领人的原始美容师 及门店 originfid
                    $info = Db::table('ims_bj_shopn_member')
                        ->alias('mm')
                        ->join(['ims_bwk_branch' => 'bwk'],'mm.storeid=bwk.id','left')
                        ->join(['sys_departbeauty_relation' => 'departbeauty'],'bwk.id=departbeauty.id_beauty and bwk.sign=departbeauty.id_sign','left')
                        ->join(['sys_department' => 'depart'],'departbeauty.id_department=depart.id_department','left')
                        ->where('mm.id',$val['originfid'])
                        ->field('mm.id,mm.storeid,mm.pid,mm.code,mm.realname,mm.mobile,bwk.title,bwk.sign,depart.st_department')
                        ->find();

                    $list[$k]['origin_fid']     = $info['id'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $info['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $info['realname'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $info['mobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_storeid'] = $info['storeid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_title']   = $info['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $info['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $info['st_department'];//原始（发货）美容师手所属办事处
                }else{
                    $list[$k]['origin_fid']     = $val['staffid'];//原始（发货）美容师ID
                    $list[$k]['origin_code']    = $val['code'];//原始（发货）美容师ID
                    $list[$k]['origin_name']    = $val['sellername'];//原始（发货）美容师昵称
                    $list[$k]['origin_mobile']  = $val['sellermobile'];//原始（发货）美容师手机号
                    $list[$k]['origin_title']   = $val['title'];//原始（发货）美容师手所属门店
                    $list[$k]['origin_sign']    = $val['sign'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_storeid'] = $val['sellerstoreid'];//原始（发货）美容师手所属门店编号
                    $list[$k]['origin_department'] = $val['pertain_department_name'];//原始（发货）美容师手所属办
                }
            }
        }
        return $list;
    }
}