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
class ArticleFlow extends Model {
    protected $name = 'cm_article_flow';
    protected $pk = 'flow_id';

    public function getEditimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }

    /**
     * @commit: 获取当前文章的审核流信息
     * @function: getCurrentArticleFlowInfo
     * @param $map
     * @return array
     * @author: stars<1014916675@qq.com>
     * @createTime ct
     */
    public static function getCurrentArticleFlowInfo($map){
        return self::where($map)->find()->toArray();
    }
}