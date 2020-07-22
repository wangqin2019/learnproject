<?php

namespace app\api_test\controller;
use think\Db;
//使用redis扩展
use think\cache\driver\Redis;
/**
 * TestApi: 本地测试相关接口
 */
set_time_limit(0);
class TestApi extends Base
{

    /*
     * 功能: 插入门店到对应的办事处下
     * 请求:
     * 返回:
     * */
    public function insBan()
    {
       //查询不再办事处下面的门店
        $serM = Db::table('ims_bwk_branch ibb')->field('id,title,sign,location_p,address')->where('id not in (select id_beauty from sys_departbeauty_relation) and sign not in (\'000-000\',\'666-666\',\'888-888\')')->order('location_p asc')->select();
        $i=1;
        foreach($serM as $v)
        {
            $name = mb_substr($v['location_p'],0,2,'utf-8');
            $serM1 = Db::table('sys_department sd')->field('id_department,st_department')->where("st_address like '%$name%'")->limit(1)->select();
//            print_r($serM1);exit;
            $id_department = @$serM1[0]['id_department']==''?'':@$serM1[0]['id_department'];
            $st_department = @$serM1[0]['st_department']==''?'':@$serM1[0]['st_department'];
            $data = array('id_department'=>$id_department,'id_sign'=>$v['sign'],'id_beauty'=>$v['id']);
            $i++;
            $insB = Db::table('sys_departbeauty_relation')->insert($data);
        }
        echo 'success';

    }

    //更新办事处id_department和门店id_beauty
    public function updBan()
    {
        $res = Db::table('sys_departbeauty_relation_copy1215 sdr,ims_bwk_branch ibb,sys_department sd')->field('id_sign,st_department,st_title')->where('sdr.id_department=sd.id_department');
    }

    public function testL()
    {
        $sql = Db::name('live')->field('*')->limit(5)->fetchSql(true)->select();
        $res = Db::query($sql);
        //手动记录日志
        \think\Log::record('测试日志信息，这是警告级别','notice');
        return $res;
    }

    //清除所有redis数据记录
    public function clear_all_redis()
    {
        $code = 1;$data=[];$msg='清空所有redis数据成功';
        $redis = new Redis();
        $redis->clear();
        return $this->returnMsg($code,$data,$msg);
    }


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

    public function ins_fans()
    {
        $code = 1;$data=[];$msg='ims_fans表入库成功';$cnt=0;$cnt_upd=0;

        $res = Db::table('ims_bj_shopn_member')->field('id,realname,mobile')->group('mobile')->select();
        if($res)
        {
            foreach($res as $v)
            {
                $res1 = Db::table('ims_fans')->field('id,id_member,avatar')->where('id_member',$v['id'])->select();
                if(!$res1)
                {
                    $data_v = array('weid'=>1,'createtime'=>time(),'realname'=>$v['realname'],'nickname'=>$v['realname'],'avatar'=>config('qiniu_img_domain').'/img_logo1.png','mobile'=>$v['mobile'],'id_member'=>$v['id']);
                    Db::table('ims_fans')->insert($data_v);
                    $cnt++;
                }else
                {
                    if(empty($res1[0]['avatar']) || !$res1[0]['avatar'] || !strstr($res1[0]['avatar'],'http'))
                    {
                        $data_v = array('avatar'=>config('qiniu_img_domain').'/img_logo1.png');
                        Db::table('ims_fans')->where('id',$res1[0]['id'])->update($data_v);
                        $cnt_upd++;
                    }

                }
            }
            $data = array('cnt'=>$cnt,'cnt_upd'=>$cnt_upd);
        }
        return $this->returnMsg($code,$data,$msg);
    }

    public function get_ok()
    {
        $code = 1;$data=[];$msg='返回get_ok接口数据';
        return $this->returnMsg($code,$data,$msg);
    }

}