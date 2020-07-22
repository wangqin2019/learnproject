<?php
/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2020/1/9
 * Time: 11:46
 */

namespace app\api\service;
use app\api\model\Copyroom;
use app\api\model\CopyroomImage;
use app\api\model\Live;
use app\api\model\User;
use app\api\model\SubjectAnswerRecord;
use think\Db;
/**
 * 手机端直播答题服务类
 */
class LiveSubjectService extends BaseSer
{
    /**
     * 用户答题答案记录
     * @param int $user_id 用户id
     * @param int $live_id 直播间id
     * @param int $subject_id 题目id
     * @param string $option 提交的选项
     * @param int $ls_id 直播题目关联ID think_live_subject表ID
     */
    public function UsersLiveAnswersAdd($user_id,$live_id,$subject_id,$option,$ls_id)
    {
        $user = [];
        // 1.查询用户信息
        $mapu['id'] = $user_id;
        $resu = User::get($mapu);

        if (empty($resu)) {
            $this->code = 0;
            $this->msg = '用户不存在';
            $this->data = (object)[];
            return $this->returnArr();
        }
        // 2.查询用户提交答案
        $mapsub['user_id'] = $user_id;
        $mapsub['live_id'] = $live_id;
        $mapsub['subject_id'] = $subject_id;
        $mapsub['ls_id'] = $ls_id;
        $ressub = SubjectAnswerRecord::get($mapsub);
        if ($ressub) {
            $this->code = 0;
            $this->msg = '答案已提交过';
            $this->data = (object)[];
            return $this->returnArr();
        }
        $user['ls_id'] = $ls_id;
        $user['user_id'] = $user_id;
        $user['storeid'] = $resu['storeid'];
        $user['fid'] = $resu['staffid']==$resu['id']?0:$resu['staffid'];
        $user['live_id'] = $live_id;
        $user['subject_id'] = $subject_id;
        $user['opt'] = $option;
        $user['create_time'] = time();
        $ressub1 = SubjectAnswerRecord::create($user);

        $this->code = 1;
        $this->msg = '提交成功';
        $this->data['record_id'] = $ressub1->id;
        // 3.用户提交答案
        return $this->returnArr();
    }

    /**
     * 直播文案选择列表
     * @param int $user_id 用户id
     * @param int $store_id 门店id
     */
    public function hasCopyroom($user_id,$store_id)
    {
        $this->code = 0;
        $this->msg = '暂无文案直播';
        $this->data = [];

//        $map = ' delete_time = 0 and (uid = '.$user_id.' and storeid = '.$store_id.' and type = 1 or type = 0)';
//        // 模型关联闭包查询
//        $res = Copyroom::where($map)->order('create_time desc')->count();
//        if($res > 0){
//            $this->code = 1;
//            $this->msg = '可选择直播类型';
//        }
        return $this->returnArr();
    }

    /**
     * 直播文案选择列表
     * @param int $user_id 用户id
     * @param int $store_id 门店id
     * @param int $page 当前页,不传查询所有
     */
    public function getCopyroom($user_id,$store_id,$page = 0)
    {
        $this->code = 1;
        $this->msg = '暂无数据';
        $this->data = [];
        $limit = 50;// 每页显示条数
        if($page == 0){
            $page = 1;
            $limit = 1000;
        }
        $rest = [];
        // 查询标题及对应的文案图片列表
        $map = ' delete_time = 0 and (uid = '.$user_id.' and storeid = '.$store_id.' and type = 1 or type = 0)';
        // 模型关联闭包查询
//        $res = Copyroom::with(['copyroomImage'=>function($query){
//            $mapc1['delete_time'] = 0;
//            $query->where($mapc1)->order('sort desc');
//        }])->where($map)->order('create_time desc')->select();
        $res = Copyroom::where($map)->order('create_time desc')->page($page,$limit)->select();
        if($res){
            $copyroom_ids = [];
            foreach ($res as $v) {
                $copyroom_ids[] = $v['id'];
            }
            $mapc['delete_time'] = 0;
            $mapc['copyroom_id'] = ['in',$copyroom_ids];
            $res_ci = CopyroomImage::where($mapc)->order('sort asc')->select();
            foreach ($res as $v) {
                $res_copy['copyroom_id'] = $v['id'];
                $res_copy['title'] = $v['title'];
                $res_copy['img'] = '';
                $res_copy['img_list'] = [];
                if($res_ci){
                    $res_copy['img'] = $res_ci[0]['image'];
                    foreach ($res_ci as $vc) {
                        if($vc['copyroom_id'] == $v['id']){
                            $copyroom_image['copyroom_image_id'] = $vc['id'];
                            $copyroom_image['image'] = $vc['image'];
                            $copyroom_image['sort'] = $vc['sort'];
                            $res_copy['img_list'][] = $copyroom_image;
                        }
                    }
                }
                $rest[] = $res_copy;
            }
        }

        if($rest){
            $this->data = $rest;
            $this->msg = '获取成功';
        }
        return $this->returnArr();
    }

    /**
     * 文案直播-当前文案
     * @param string $chat_id 当前聊天室
     */
    public function currentCopyroom($chat_id)
    {
        $this->code = 1;
        $this->msg = '当前直播已结束';
        // 当前直播是否已关闭
        $map['chat_id'] = $chat_id;
//        $map['statu'] = 1;
        $res = Live::get($map);
        if($res){
            $key = 'copyroom_'.$chat_id;
            $res_cache = $this->getRedisData($key);
//            dump(unserialize($res_cache));die;
            if($res_cache){
                $this->data = json_decode(unserialize($res_cache),true);
                $this->msg = '当前文案获取成功';
            }else{
                $this->data = (object)[];
                $this->msg = '当前直播暂时没有当前文案';
            }
        }else{
            $this->data = (object)[];
        }
        return $this->returnArr();
    }
}