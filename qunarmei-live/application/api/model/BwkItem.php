<?php
namespace app\api\model;

use think\Model;
use think\Db;

class BwkItem extends Model
{
    // 主键
//    protected $pk = 'id';
    // 设置当前模型对应的完整数据表名称
    // 银行支付表
    protected $table = 'store_bwk_item';

    /*
     * 查询门店项目名称列表
     * $arr => [store_id=>门店id]
     * */
    public function selBi($arr)
    {
        $rest = '';
        if(isset($arr['store_id'])){
            $res1 = $this->field('*')->where('store_id ='.$arr['store_id'].' and is_delete=0')->order('create_time desc')->select();
            $rest = $res1;
        }
        return $rest;
    }
    /*
     * 修改门店项目
     * $arr => [store_id=>门店id,store_item=>门店项目[item_name=>项目名称,item_img=>项目图片]]
     * */
    public function updBi($arr)
    {
        $rest = '';
        if(isset($arr['store_id']) && isset($arr['store_item'])){
            if($arr['store_item']){
                $res1 = '';
                foreach ($arr['store_item'] as $v) {
                    $res1[] = ['store_id'=>$arr['store_id'],'item_name'=>$v['item_name'],'item_img'=>$v['item_img'],'create_time'=>date('Y-m-d H:i:s')];
                }
                // 批量删除
                $this->where('store_id',$arr['store_id'])->delete();
                // 批量加入
                $res2 = $this->saveAll($res1);
            }
        }
        return $rest;
    }

}
