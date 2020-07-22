<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/28
 * Time: 9:29
 * Description: 消息通知表
 */

namespace app\dtalk\model;

use think\Model;
class Message extends Model {
    protected $name = 'cm_message';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;

    /**
     * Commit:  根据条件获取当前用户的消息通知
     * Function: getUserMapNoticeList
     * @Param $map['user_id']=1
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 11:44:58
     * @Return array
     */
    public static function getUserMapNoticeList($map,$page = 1,$limit = 10){
        $map[] = ['delete_time','=', 0];//未删除
        return self::where($map)->page($page,$limit)->select()->toArray();
    }

    /**
     * Commit:
     * Function: getUserMapNoticePageList
     * @Param $map 条件
     * @Param int $limit 每页条数
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-13 17:19:39
     * @Return array
     */
    public static function getUserMapNoticePageList($map,$page = 1,$limit = 10){
        $map[] = ['delete_time','=', 0];//未删除
        $model = self::where($map);

        $total = $model->count();
        $list = $model->page($page,$limit)->select()->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$val){
                $aid = $val['aid'];
                $list[$k]['cate_id'] = $aid ? Article::where('id','=',$aid)->value('cate_id') :0;
            }
        }
        return [
            'total'        => $total,//总条数
            'per_page'     => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page'    => $total ? ceil($total / $limit) : 0,//最后一页
            'data'         => $list,//每页数据
        ];
    }
    /**
     * Commit:  根据条件获取当前用户的未读消息通知数
     * Function: getUserNoticeUnreadCount
     * @Param $map['user_id']=1;$map['is_read']=0
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-11 11:44:58
     * @Return array
     */
    public static function getUserNoticeUnreadCount($map){
        $map['delete_time'] = 0;
        return self::where($map)->count() ?: 0;
    }
}