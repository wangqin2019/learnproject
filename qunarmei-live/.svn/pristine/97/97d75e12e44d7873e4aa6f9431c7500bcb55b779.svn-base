<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/4/18
 * Time: 14:17
 */

namespace app\api\service;

/**
 * app直播列表相关接口服务类
 */
class LiveListSer extends BaseSer
{
    // 关闭直播列表门店
    public $closeSigns = ['241-126','241-127'];
    // 模型实例化
    protected $liveMod ; // 直播列表模型
    protected $branchMod ; // 门店模型

    public  function __construct()
    {
        $this->liveMod = new \app\api\model\Live();
        $this->branchMod = new \app\api\model\Branch();
    }

    /**
     * 直播列表
     * $arr=>[store_id:门店id,user_id:用户id,keyword:关键字搜索]
     */
    public function liveList($arr)
    {
        // 指定门店关闭直播列表
        $res_close = $this->closeLiveList($arr['store_id']);
        if($res_close){
            $this->code = 1;
            $this->msg = '暂无数据';
            $this->data = [];
            return $this->returnArr();
        }
        // 查询所有能观看的视频列表,直播+点播
        $map_live['statu'] = 1;
        $map_live1 = 'db_statu=1 and statu=0';
        $res_live = $this->liveMod->where($map_live)->whereOr($map_live1)->order('statu desc,insert_time desc')->select();
        if($res_live){
//            halt($res_live);
            // 查询模型和分类数据
            $ids = [];$vmod = [];
            foreach ($res_live as $k=>$v) {
                $ids[] = $v['id'];
            }
            // 查询分类
            $map_c['id'] =['in',$ids];
            $res_cate = $this->liveMod->getCate($map_c);


        }
        return $this->returnArr();
    }

    /**
     * 关闭直播列表
     */
    public function closeLiveList($store_id)
    {
        $flag = 0;
        $map['sign'] = ['in',$this->closeSigns];
        $res = $this->branchMod->where($map)->column('id');
        if($res && in_array($store_id,$res)){
            $flag = 1;
        }
        return $flag;
    }
}