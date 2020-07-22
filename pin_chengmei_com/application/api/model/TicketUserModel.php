<?php

namespace app\api\model;
use think\Model;
use think\Db;

class TicketUserModel extends Model
{

    protected  $name="ticket_user";

    /**
     * 根据订单号获取信息
     * @param $id
     */
    public function getOneInfo($order_sn)
    {
        return $this->alias('t')->join('activity_order o','t.order_sn=o.order_sn','left')->where('t.order_sn',$order_sn)->field('t.storeid,t.mobile,t.ticket_code,t.ticket_num,o.uid')->find();
    }

    public function getInfoByWhere($where,$field){
        return  $this->where($where)->field($field)->find();
    }

    /**
     * 统计
     * @param $where
     * @return int|string
     */
    public function getCount($where)
    {
        return $this->where($where)->count();
    }

    /**复制卡券
     * @param $uid
     * @param $ticket_sn
     */
    public function copyTicket($uid,$mobile,$ticket_sn){
        $ticket_info=Db::name('ticket_user')->where('ticket_code',$ticket_sn)->find();
        unset($ticket_info['id']);
        $ticket_info['mobile']=$mobile;
        $code=time() . $uid . rand(11, 99);
        $ticket_info['share_code']= $code;
        $ticket_info['qrcode'] = pickUpCode('sharing_'.$code);
        $ticket_info['insert_time']=date('Y-m-d H:i:s');
        $ticket_info['update_time']=date('Y-m-d H:i:s');
        $ticket_info['remark']='接收好友同享券';
        return $this->insert($ticket_info);
    }

    public function insertTicket($uid,$type,$prefix='activate_',$order_sn='',$name='',$image='',$status=0,$remark='',$unique=0){
        try {
            $uidInfo = Db::table('ims_bj_shopn_member')->alias('member')->field('member.id,member.mobile,member.storeid,bwk.title,bwk.sign,depart.st_department')->join(['ims_bwk_branch' => 'bwk'], 'member.storeid=bwk.id', 'left')->join(['sys_departbeauty_relation' => 'departbeauty'], 'bwk.id=departbeauty.id_beauty', 'left')->join(['sys_department' => 'depart'], 'departbeauty.id_department=depart.id_department', 'left')->where('member.id', $uid)->find();
            $ticket_code = time() . $uid . rand(11, 99).$unique;
            $ticketList['depart'] = $uidInfo['st_department'];
            $ticketList['branch'] = $uidInfo['title'];
            $ticketList['sign'] = $uidInfo['sign'];
            $ticketList['mobile'] = $uidInfo['mobile'];
            $ticketList['storeid'] = $uidInfo['storeid'];
            $ticketList['insert_time'] = date('Y-m-d H:i:s');
            $ticketList['update_time'] = date('Y-m-d H:i:s');
            $ticketList['status'] = $status;
            $ticketList['ticket_code'] = $ticket_code;
            $ticketList['type'] = $type;
            $ticketList['draw_pic'] = $image;
            $ticketList['draw_name'] = $name;
            $ticketList['order_sn'] = $order_sn;
            $ticketList['remark'] = $remark;
//            $codeCon=$prefix.$ticket_code;
//            $ticketList['qrcode'] = pickUpCode($codeCon);
            $this->insert($ticketList);
            //记录日志
            sendQueue($ticket_code, $ticket_code . '分配给' . $uidInfo['st_department'] . $uidInfo['title'] . $uidInfo['sign'] . '下的' . $uidInfo['mobile']);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
}