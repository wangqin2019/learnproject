<?php

namespace app\api\controller;
use think\Db;

/**
 * Wechat:操作微商城数据表的一些手动执行的接口
 */
class Wechat extends Base
{

    /**
	 * get: 增加对应的办事处和门店
	 * path: $ban 办事处名称,$sign 门店编号
	 * method: list
	 * param: position - {int} 广告位
	 */
	public function addBanMen()
    {
        //请求数据
        $ban = input('ban');
        $sign = input('sign');
        //
        $code=1;$msg='添加成功';$res='';$flag=null;
        if($ban && $sign)
        {


            //添加到对应的sys_departbeauty_relation表里
            //获取办事处id
            $resp1 = Db::table('sys_department sd,ims_bwk_branch ibb')->field('id_department,id')->where("st_department='$ban' and sign='$sign'")->limit(1)->select();
            if($resp1)
            {
                $data = array('id_department'=>$resp1[0]['id_department'],'id_sign'=>$sign,'id_beauty'=>$resp1[0]['id']);
                //查询是否存在
                $resp3 = Db::table('sys_departbeauty_relation')->field('id_beauty')->where("id_department='".$resp1[0]['id_department']."' and id_beauty='".$resp1[0]['id']."' ")->limit(1)->select();
                if(!$resp3)
                {
                    $resp = Db::table('sys_departbeauty_relation')->insert($data);
                    if($resp)
                    {
                        $flag=1;
                    }
                }
            }

            if(!$flag)
            {
                $code = 0;
                $msg='添加失败';
            }

        }else
        {
            $code = 0;
            $msg='门店或编号不能为空';

        }
        return parent::returnMsg($code,$res,$msg);
    }
    /**
     * get: 修改
     * path:
     * method: list
     * param:
     */
    public function upd_realname()
    {
        $code = 1;$data=[];$msg='修改没名字的用户姓名';

        $res = Db::table('ims_bj_shopn_member')->field('id,realname,mobile')->where("length(realname)<1 or realname is NULL")->select();
        if($res)
        {
           foreach($res as $v)
           {
               $mob_name = '';
               if($v['mobile'])
               {
                   $mob_name = '手机用户'.substr($v['mobile'],-3);
               }

               if(!$v['realname'])
               {
                   if($mob_name)
                   {
                       $data_v = array('realname'=>$mob_name);
                       Db::table('ims_bj_shopn_member')->where('id',$v['id'])->update($data_v);
                   }
               }
           }
        }

        return $this->returnMsg($code,$data,$msg);
    }

}