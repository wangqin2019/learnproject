<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\View;
use think\Validate;
use think\facade\Db;
use app\api\model\SmsRecord;
use think\facade\Filesystem;
use think\exception\ValidateException;

header('Access-Control-Allow-Origin:*');
/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 数据返回值
     * @var array
     */
    protected $_data = [];
    /**
     * 错误码返回值
     * @var array
     */

    protected $_error = [
        '200' => '数据操作成功！',
        '201' => '未知错误！',
        '202' => '参数缺失！',
        '203' => '数据操作失败！',
        '205' => '记录不存在或已删除！',

        '501' => '验证码不存在或已失效！',
        '502' => '验证码错误！',
        '503' => '密码不合法！',
        '504' => '两次密码不一致！',
        '505' => '当前用户已冻结，请联系管理员解封！',
        '506' => '未填写密码！',

        '1000' => '操作失败，请核对数据！',
        '1001' => '参数缺失！',

        '1002' => '验证码错误或超时！',
        '1017' => '未填写验证码！',

        '1003' => '登录失败！',

        '1004' => '手机号不合法',
        '1005' => '用户不存在',
        '1006' => '账号或密码错误',
        '1007' => '该账号被禁用',

        '1008' => '请输入昵称',
        '1009' => '您输入的用户昵称已存在，请重新输入！',
        '1015' => '用户个人介绍不能超过30字！',
        '1016' => '用户昵称不能超过20字！',
        '1018' => '昵称已存在，请重新输入！',
        '1019' => '昵称不合法(数字、字母或组合)！',

        '1010' => '请输入工号！',
        '1011' => '工号错误！',

        '1012' => '您不是内部员工（手机号不匹配）！',
        '1013' => '身份证错误或不合法！',
        '1014' => '请输入手机号！',



        '2000' => '当前文章不存在或已删除',
        '2001' => '文章标题不能为空',
        '2002' => '文章标题限定5-30字',
        '2003' => '请选择文章分类',
        '2004' => '文章内容不能为空',
        '2005' => '文章字数100字以上',
        '2006' => '请上传视频',
        '2007' => '当前文章审核中，禁止编辑',

        '2050' => '标签不能超过3个',


        '300' => 'token不存在',
        '301' => '用户未登录',
        '302' => 'token在黑名单中',
        '299' => 'token已过期',
        '298' => 'token已过期,无法再次刷新',
        '297' => '发布时间（iat）不能大于当前时间',
    ] ;
    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app, View $view)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->view = $view;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * Commit:发送验证码
     * Function: sendSms
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-26 17:36:27
     * @Return \think\response\Json
     */
    public function sendSms()
    {
        $mobile = request()->param('mobile', '');
        if (empty($mobile)) {
            return $this->returnAjax(1014, '', '');
        }
        $sms_id = config('sms.cl_sms_original_register_id')?:1;
        $code   = random_int(100, 999).random_int(300, 899);
        $result = sendMessage($mobile, ['code'=>$code], $sms_id);
        $result = json_decode($result, true);

        if ($result['code']!=0)
        {
            return $this->returnAjax($result['code'], $result, $result['errorMsg']);
        } else {//成功
            //验证码插入数据库
            $data = array(
                'mobile'      => $mobile,
                'code'        => $code,
                'create_time' => time(),
                'expire_time' => time() + config('sms.cl_sms_original_register_expire'),
            );
            Db::name('cm_sms_record')->insert($data);
            return $this->returnAjax(200, ['result'=>$result,'info'=>$data], $result['errorMsg']);
        }
    }
    /**
     * Commit:验证验证码
     * Function: checkSms
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-26 17:48:00
     * @Return bool|\think\response\Json
     */
    public function checkSms(){
        $mobile = request()->param('mobile');
        $code   = request()->param('verify');
        //查询最靠近当前时间的一条数据
        $param[] = ['mobile', '=', $mobile];
        $param[] = ['expire_time', '>', time()];
        $param[] = ['status', '=', 1];
        $info = SmsRecord::getMobileSmsRecord($param);
        if(empty($info)) {
            return $this->returnAjax(501, '');
        }

        if($code != $info['code']){
            return $this->returnAjax(502, '');
        }
        return true;
    }
    /**
     * Commit: 图片上传
     * Function: upload
     * @Param string $filedir 文件存放目录
     * @Param string $field 上传字段名
     * @Param bool $type true false
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-29 09:48:51
     * @Return \think\response\Json
     */
    public function upload($filedir = 'article',$field = 'file',$type = false){
        set_time_limit(0);
        $filedir = request()->param('filedir') ?: $filedir;
        $field   = request()->param('field') ?: $field;
        $name    = request()->param('name') ?: $field;
        $type    = request()->param('type') ?: $type;
        if(!empty($type)){
            return $this->_upload($filedir,$field,$name);
        }else{
            return $this->_uploadAll($filedir,$field,$name);
        }
    }
    /**
     * Commit: 单文件上传
     * Function: _upload
     * @Param string $filedir 文件存放目录
     * @Param string $field 上传字段名
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-28 16:24:44
     */
    public function _upload($filedir ,$field, $name){
        set_time_limit(0);
        $file = request()->file($field);

        try{
            if(null == $file){
                return $this->returnAjax(100000, '', '请重新上传文件');
            }
            if($name == 'video'){
                validate([
                    'file'=>[
                        'fileSize' => 8388608,
                        'fileExt' => 'mp4',
                    ]
                ])->check([$field => $file]);
            }else if($name == 'image'){
                validate([
                    'file'=>[
                        'fileSize' => 5242880,
                        'fileExt' => 'jpg,jpeg,png,gif,bmp',
                        //'image' => '200,200'
                    ]
                ])->check([$field => $file]);
            }

            // 上传到本地服务器
            $savename = Filesystem::disk('qiniu')->putFile( $filedir, $file);
            $domain   =  Filesystem::getDiskConfig('qiniu','domain');
            if(is_string($savename)){
                $filepath = $domain . DIRECTORY_SEPARATOR .$savename;
            }else{
                $filepath = $domain .Filesystem::disk('qiniu')->path( $savename);
            }

            $info['path'] = $savename;//文件路径
            $info['url']  = $filepath;//url路径

            $returnData['info'] = $info;
            return $this->returnAjax(200,$returnData,'单文件上传成功');
        }catch (\think\exception\ValidateException $e){
            return $this->returnAjax(10000,'','上传失败：'.$e->getMessage());
        }
    }
    /*public function _upload($filedir ,$field ){
        $file = request()->file($field);

        try{
            if(null == $file){
                return $this->returnAjax(100000,'','请重新上传文件');
            }
            $rule = validate([
                'image' => [
                    'fileSize' => 1024 * 1024 * 5,
                    'fileExt' => 'jpg,jpeg,png,gif,bmp',
                    //'image' => '200,200'
                ],
                //['image'=>'fileSize:20240|fileExt:jpg|image:200,200,jpg']
                'video' => [
                    'fileSize' => 1024 * 1024 * 8,
                    'fileExt' => 'mp4',
                ],
                //'video'=>'fileSize:1024 * 1024 * 8|fileExt:mp4'
                'file' => [
                    'fileSize' => 1024 * 1024 * 5,
                    'fileExt' => 'jpg,jpeg,png,gif,bmp',
                    //'image' => '200,200'
                ],
            ])->check([$field => $file]);
            // 上传到本地服务器
            $savename = Filesystem::disk('qiniu')->putFile( $filedir, $file);
            $domain   =  Filesystem::getDiskConfig('qiniu','domain');
            if(is_string($savename)){
                $filepath = $domain . DIRECTORY_SEPARATOR .$savename;
            }else{
                $filepath = $domain .Filesystem::disk('qiniu')->path( $savename);
            }

            $info['path'] = $savename;//文件路径
            $info['url']  = $filepath;//url路径
            //$info['size'] = $file->getSize();//文件大小
            //$info['mine'] = $file->getMime();//文件类型

            $returnData['info'] = $info;
            return $this->returnAjax(200,$returnData,'单文件上传成功');
        }catch (\think\exception\ValidateException $e){
            return $this->returnAjax(10000,'','上传失败：'.$e->getMessage());
        }
    }*/
    /**
     * Commit: 多文件上传
     * Function: _uploadAll
     * @Param string $filedir 文件存放目录
     * @Param string $field 上传字段名
     * User: stars<1014916675@qq.com>
     * DateTime: 2019-11-28 16:25:17
     */
    public function _uploadAll($filedir ,$field,$name){
        set_time_limit(0);
        $files = request()->file($field);
        //$file->move();
        try{
            if(null == $files){
                return $this->returnAjax(10000,'','请重新上传文件');
            }
            if($name == 'video'){
                validate([
                    'file' => [
                        'fileSize' => 1024 * 1024 * 8,
                        'fileExt' => 'mp4',
                    ]
                ])->check([$field => $files]);
                //'video'=>'fileSize:1024 * 1024 * 8|fileExt:mp4'
            }else if($name == 'image'){
                validate([
                    'file' => [
                        'fileSize' => 1024 * 1024 * 5,
                        'fileExt' => 'jpg,jpeg,png,gif,bmp',
                        //'image' => '200,200'
                    ]
                ])->check([$field => $files]);
                //['image'=>'fileSize:20240|fileExt:jpg|image:200,200,jpg']
            }

            // 上传到本地服务器
            $savename = [];
            foreach($files as $file){
                $tmpfile = Filesystem::disk('qiniu')->putFile( $filedir, $file);
                $domain  =  Filesystem::getDiskConfig('qiniu','domain');
                if(is_string($tmpfile)){
                    $filepath = $domain . DIRECTORY_SEPARATOR .$tmpfile;
                }else{
                    $filepath = $domain .Filesystem::disk('qiniu')->path( $tmpfile);
                }

                $info['path'] = $tmpfile;//文件路径
                $info['url']  = $filepath;//url路径
                $savename[]   = $info;
            }

            $returnData['info'] = $savename;
            return $this->returnAjax(200,$returnData,'批量上传成功');
        }catch (\think\exception\ValidateException $e){
            $this->returnAjax(100000,'',$e->getMessage());
        }
    }

    /**
     * Commit: 返回输出
     * Function: returnAjax
     * @Param int $code 返回值
     * @Param array $data 返回数组
     * @Param string $msg 返回信息
     * Author: stars<1014916675@qq.com>
     * DateTime: 2019-12-10 17:48:08
     * @Return \think\response\Json
     */
    protected function returnAjax($code = 200, $data = [], $msg = ''){
        if(array_key_exists($code,$this->_error)){
            $msg = $msg ?: $this->_error[$code];
        }
        if(!empty($data)){
            if(is_array($data)){
                $this->_data = array_merge($this->_data,$data);
            }else if (is_string($data)){
                $this->_data = array_merge($this->_data,['return'=>$data]);
            }
        }

        return json(['code'=>$code,'data'=>$this->_data,'msg'=>$msg]);
    }
}
