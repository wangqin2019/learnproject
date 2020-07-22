<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/10/30
 * Time: 14:34
 */

namespace app\index\controller;
use think\Db;
use think\Controller;
/*
* 分享类相关接口功能
**/
class Share extends Controller
{

    /**
     * 发现模块-分享文章
     * @param  [string] $user_id [用户id], $article_id [文章id]
     * @return [string]       [最终的json数据]
     */
    public function shareArticle()
    {
        // 请求参数
//        $user_id = input('user_id');
        $article_id = input('article_id');
        // 初始化
        $rest_img=[];$rest=[];
        // 查询文章
        if($article_id){
            $map['c.id'] = $article_id;
            $rest = Db::table('think_find_content c')->join(['ims_bj_shopn_member'=>'m'],['c.user_id=m.id'],'LEFT')->field('c.id,c.article_img,c.article_title,c.article_content,c.comment_time,c.summary,c.cover_img,c.cover_img_1,c.cate_id,c.flag_img,m.realname')->where($map)->limit(1)->find();
            if($rest){
                // 内容为图片
                if($rest['flag_img']){
                    $map1['article_id'] = $rest['id'];
                    $map1['isshow'] = 1;
                    $rest1 = Db::table('think_find_content_img ci')->field('id,img_url,goods_id')->where($map1)->order('display_order desc')->select();
                    if($rest1){
                        $rest_img = $rest1;
                    }
                    // $this->assign('rest_img', $rest_img);
                }
            }
            $this->assign('rest', $rest);
            $this->assign('rest_img', $rest_img);
            return $this->fetch();
        }
    }
}