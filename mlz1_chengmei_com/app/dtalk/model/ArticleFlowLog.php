<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/28
 * Time: 9:29
 * Description: 文章审核流
 */

namespace app\dtalk\model;

use think\Model;
class ArticleFlowLog extends Model {
    protected $name = 'cm_article_flowlog';
    protected $pk = 'log_id';

    /**
     * @commit: 钉钉获取当前文章的审核记录列表
     * @function: dtalkCurrentArticleCheckLogs
     * @param $map
     * @return array 
     * @author: stars<1014916675@qq.com>
     * @createTime 2020/2/24 13:40
     */
    public static function dtalkCurrentArticleCheckLogs($map){
        return self::where($map)->order('create_time','asc')->select()->toArray();
    }
    /**
     * @commit: 钉钉获取当前文章的审核记录
     * @function: dtalkCurrentArticleCheckLogInfo
     * @param $map
     * @return array
     * @author: stars<1014916675@qq.com>
     * @createTime 2020/2/24 13:40
     */
    public static function dtalkCurrentArticleCheckLogInfo($map){
        return self::where($map)->order('create_time','asc')->find()->toArray();
    }
    /**
     * @commit: 钉钉获取当前文章的审核记录条数
     * @function: dtalkCurrentArticleCheckLogsCount
     * @param $map
     * @return array
     * @author: stars<1014916675@qq.com>
     * @createTime 2020/2/24 13:40
     */
    public static function dtalkCurrentArticleCheckLogsCount($map){
        return self::where($map)->count();
    }
}