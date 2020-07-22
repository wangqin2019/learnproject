<?php
namespace app\api\model;

use think\Model;
/*
 * 直播信息表
*/
class Live extends Model
{
    // 定义表名称,含前缀$name,完整表名$table
    protected $name='live';
    // 定义主键
//    protected $pk = 'id';

    // 不转换时间格式,返回原始
//    public function getInsertTimeAttr($time)
//    {
//        return $time;
//    }
//    public function getUpdateTimeAttr($time)
//    {
//        return $time;
//    }

    // 关联查询-直播分类表
    public function listLive()
    {
        $list = $this->belongsTo("LiveCategory", "category_id", "category_id",[], 'LEFT');
//        $list = $this->hasOne("LiveCategory", "category_id", "category_id",[], 'LEFT');
        // 数据表关联查询
//        $list = $this->join('live_category l','l.id=think_live.category_id','LEFT')->field('think_live.*,l.category_name')->select();
        return $list;
    }
    // 关联查询-用户表
    public function memLive()
    {
        $list = $this->belongsTo("User", "user_id", "mobile", [], 'LEFT');
        return $list;
    }
    /*
     * 功能:查询当前直播的直播间
     * */
    public static function liveSel()
    {
        $map['statu'] = 1;
        $res = self::where($map)->limit(1)->find();
        return $res;
    }

    // cate-关联预载入查询
    public function getCate($map)
    {
        $rest = [];$rest1 = [];
        // 多个模型关联查询
//        $res = self::with(['listLive','memLive'])->where($map)->select();
//        if($res){
////            var_dump($res[0]);die;
//            // 关联模型列表
//            $count = count($res);
//            for($a = 0; $a < $count ; $a++){
//                $list_live = $res[$a]->relation['list_live'];
//                $mem_live = $res[$a]->relation['mem_live'];
//                $rest1['list_live'] = empty($list_live)?[]:$list_live->toArray();
//                $rest1['mem_live'] = empty($mem_live)?[]:$mem_live->toArray();
//                $rest[] = $rest1;
//            }
//        }
        $rest = self::with('listLive')->where($map)->select();
        if($rest){
            foreach ($rest as $v) {
                $rest1[] = $v->toArray();
            }
        }
        return $rest1;
    }
    // mem-关联预载入查询
//    public function getMem()
//    {
//        return self::with('memLive')->select();
//    }

}