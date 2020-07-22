<?php
/**
 * Created by PhpStorm.
 * User: php
 * Date: 2019/11/28
 * Time: 9:29
 * Description:
 */

namespace app\dtalk\model;

use think\Model;
class Article extends Model {
    protected $name = 'cm_article';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;


    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getPublishTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s',$value) : '--';
    }
    public function getPublishTime1Attr($value,$data){
        return $data['publish_time'] ?: time();
    }
   /* public function getIscheckAttr($value){
        $status = ['未审核','已审核','审核中','已驳回'];
        return $status[$value];
    }*/
    public function getIscheckStrAttr($value,$data){
        $status = [0=>'未审核',1=>'已审核',2=>'审核中',3=>'已驳回'];
        return $status[$data['ischeck']];
    }


    //关联
    //关联分类
    public function category()
    {
        return $this->hasOne(Category::class, 'id','cate_id')->bind([
            'cate_name' => 'name',
            'parentid',
        ]);
    }
    //关联标签
    public function labels()
    {
        return $this->belongsToMany(Labels::class, ArticleLabels::class,'label_id','aid');
    }
    //关联素材提供者
    public function staff()
    {
        return $this->belongsToMany(Staff::class, ArticleProvider::class,'provider','aid');
    }
    //关联评论
    public function comments(){
        return $this->hasMany(Comment::class,'aid','id');
    }
    //关联作者
    public function members(){
        return $this->hasOne(Member::class,'user_id','uid');
    }
    //关联专题广告
    public function adv(){
        return $this->hasOne(Adv::class,'id','adv_id');
    }

    /**
     * Commit: 获取当前用户的所有点赞量
     * Function: getUserClickCount
     * @Param $user_id
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-06 17:34:18
     * @Return int|string
     */
    public static function getUserClickCount($user_id){
        return self::where('uid','=',$user_id)->sum('click');
    }
    /**
     * Commit: 根据条件获取文章数量
     * Function: getMapArticleCount
     * @Param $map
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-12-06 17:34:18
     * @Return int|string
     */
    public static function getMapArticleCount($map){
        return self::where($map)->count();
    }

    /**
     * Commit: 获取当前用户文章列表(单纯文章列表)
     * Function: getCurrentUserArticlePageList
     * @Param $map 条件
     * @Param int $page
     * @Param int $limit 每页条数
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-13 17:43:03
     */
    public static function getCurrentUserArticlePageList($map,$page = 1,$limit = 10){

        $total = self::with(['category'])->where($map)->count();
        $list = self::with(['category'])->where($map)
            ->order('create_time','desc')
            ->page($page,$limit)
            ->select()
            ->toArray();
        if(!empty($list)){
            $status = [0=>'未审核',1=>'已审核',2=>'审核中',3=>'已驳回'];
            foreach ($list as $k=>$val){
                $ischeck = $val['ischeck'];
                $publish_time = $val['publish_time'];
                $list[$k]['publish_time1'] = strtotime($publish_time);
                $list[$k]['ischeck_str'] = $status[$ischeck];
            }
        }
        return [
            'total'=>$total,//总条数
            'per_page' => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page' => $total ? ceil($total / $limit) : 0,//最后一页
            'data' => $list,//每页数据
        ];
    }
    /**
     * Commit: 获取当前用户文章列表（pc 管理其他数据）
     * Function: getCurrentUserArticleWithPageList
     * @Param $map 条件
     * @Param int $page
     * @Param int $limit 每页条数
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-13 17:43:03
     */
    public static function getCurrentUserArticleWithPageList($map,$page = 1,$limit = 10){
        $model = self::with(['category','labels'])->where($map);

        $total = $model->count();
        $list = $model
            ->order('create_time','desc')
            ->page($page,$limit)
            ->select()
            ->toArray();
        if(!empty($list)){
            $status = [0=>'未审核',1=>'已审核',2=>'审核中',3=>'已驳回'];
            foreach ($list as $k=>$val){
                $ischeck = $val['ischeck'];
                $publish_time = $val['publish_time'];
                $list[$k]['publish_time1'] = strtotime($publish_time);
                $list[$k]['ischeck_str'] = $status[$ischeck];
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
     * Commit: 获取钉钉文章审核列表
     * Function: getDTalkArticlePageList
     * @Param $map 条件
     * @Param int $page
     * @Param int $limit 每页条数
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-13 17:43:03
     */
    public static function getDTalkArticlePageList($map,$page = 1,$limit = 10){
        $model = self::with(['category','labels','members'])->where($map);

        $total = $model->count();
        $list = $model
            ->order('create_time','desc')
            ->page($page,$limit)
            ->select()
            ->toArray();
        if(!empty($list)){
            $status = [0=>'未审核',1=>'已审核',2=>'审核中',3=>'已驳回'];
            foreach ($list as $k=>$val){
                $ischeck = $val['ischeck'];
                $publish_time = $val['publish_time'];
                $list[$k]['publish_time1'] = strtotime($publish_time);
                $list[$k]['ischeck_str'] = $status[$ischeck];
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
     * Commit: 获取当前用户文章列表（草稿箱 单纯文章数据不关联其他数据）
     * Function: getCurrentUserArticleList
     * @Param $map 条件
     * @Param int $page
     * @Param int $limit 每页条数
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-13 17:43:03
     */
    public static function getCurrentUserArticleList($map,$page = 1,$limit = 10){
        $field = 'id,uid,cate_id,title,ischeck,is_show,description,thumb,author,create_time,click,zan,collection,share,comment,type,publish_time';
        $total = self::with(['category'])->where($map)->count();
        $list = self::with(['category'])->where($map)
            ->field($field)
            ->order('create_time','desc')
            ->page($page,$limit)
            ->select()
            ->toArray();
        return [
            'total'        => $total,//总条数
            'per_page'     => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page'    => $total ? ceil($total / $limit) : 0,//最后一页
            'data'         => $list,//每页数据
        ];
    }

    /**
     * Commit: 获取当前用户的文章详情
     * Function: getCurrentUserArticleInfo
     * @Param $map
     * @Param string $appmodel PC
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-16 09:06:07
     * @Return array|Model|null
     */
    public static function getCurrentUserArticleInfo($map,$appmodel = 'pc'){
        if($appmodel == 'pc'){
            $model = self::with(['staff','labels','members'=>function($query){
                $query->field('user_id,nickname,avatar');
            },'adv','category']);
        }else{
            $model = self::with(['labels','members'=>function($query){
                $query->field('user_id,nickname,avatar');
            },'adv','category']);
        }
        $list = $model->where($map)->find();

        if(!empty($list)){
            $list = $list->append(['ischeck_str','publish_time1'])->toArray();
        }
        return $list;
    }

    /**
     * Commit: 获取当前分类下文章列表（首页）
     * Function: getCurrentCateUserArticleList
     * @Param $map 条件
     * @Param int $page
     * @Param int $limit 每页条数
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-13 17:43:03
     */
    public static function getCurrentCateUserArticleList($map,$page = 1,$limit = 10){
        $field = 'id,cate_id,title,description,thumb,author,comment,zan,share,collection,publish_time,1 flag,type';
        $total = self::where($map)->count();
        $list = self::where($map)
            ->field($field)
            ->order('publish_time','desc')
            ->page($page,$limit)
            ->select()
            ->toArray();
        return [
            'total'        => $total,//总条数
            'per_page'     => $limit,//每页条数
            'current_page' => $page,//当前第几页
            'last_page'    => $total ? ceil($total / $limit) : 0,//最后一页
            'data'         => $list,//每页数据
        ];
    }

    /**
     * Commit: 当前用户的所有数据之和 点赞 收藏 评论 分享 阅读
     * Function: getCurrentUserSumArticleData
     * @Param int $map
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-17 09:54:29
     * @Return array
     */
    public static function getCurrentUserSumArticleData($map){
        /*$map[] = ['uid', '=', $user_id];
        $map[] = ['ischeck', '=', 1];//已审核
        $map[] = ['is_show', '=', 1]; //上架*/
        $field = "sum(click) click,sum(share) share,sum(comment) comment,sum(collection) collection,sum(zan) zan";
        $res = self::where($map)->field($field)->group('uid')->find();
        return $res ?: [
            'click'      => 0,
            'share'      => 0,
            'comment'    => 0,
            'collection' => 0,
            'zan'        => 0,
        ];
    }

    /**
     * Commit: 获取文章详情中随机推荐几条视频文章
     * Function: getRandomRecommentVideoArticle
     * @Param $map
     * @Param int $limit
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-19 15:51:40
     * @Return array
     */
    public static function getRandomRecommentVideoArticle($map,$limit = 3){
        return self::where($map)
            ->field('id,cate_id,title,author,click,thumb,description,type,zan,collection')
            ->orderRaw('rand()')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}