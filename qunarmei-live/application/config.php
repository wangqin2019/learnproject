<?php

return [
    // erp接口地址配置
    'erp_url' =>'http://erpapi2.chengmei.com:7779/',
    // erp数据库连接配置
    'erp_database' => [
        'type'           => 'sqlsrv',
        'hostname'       => '192.168.2.50',
        'database'       => 'ufdata_998_2015',
        'username'       => 'backup',
        'password'       => 'Chengmei2019',
        'hostport'       => '1420'
    ],

    // 快递鸟物流信息查询配置
    'kdn'  => [
        'eBusinessID'    => '1409114',//eBusinessID
        'appKey'    => 'e20f8fc0-d660-4378-80f2-8887969694dd',// appKey
        'reqURL'    => 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx',// 请求url
    ],
    // +----------------------------------------------------------------------
    // | auth配置
    // +----------------------------------------------------------------------
    'auth_config'  => [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'think_auth_group', // 用户组数据不带前缀表名
        'auth_group_access' => 'think_auth_group_access', // 用户-用户组关系不带前缀表
        'auth_rule'         => 'think_auth_rule', // 权限规则不带前缀表
        'auth_user'         => 'think_admin', // 用户信息不带前缀表
    ],

    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    'url_route_on' => true,     //开启路由功能
    'route_config_file' =>  ['route','admin'],   // 设置路由配置文件列表

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------

//     'app_debug'              => true,
    'app_trace' =>  false,      //开启应用Trace调试
    'trace' => [
        'type' => 'html',       // 在当前Html页面显示Trace信息,显示方式console、html
    ],
    'sql_explain' => false,     // 是否需要进行SQL性能分析
    'extra_config_list' => ['database', 'route', 'validate'],//各模块公用配置
    'app_debug' => false,
	'default_module' => 'admin',//默认模块
    //'default_filter' => ['strip_tags', 'htmlspecialchars'],

    //默认错误跳转对应的模板文件
    'dispatch_error_tmpl' => APP_PATH.'admin/view/public/error.tpl',
    //默认成功跳转对应的模板文件
    'dispatch_success_tmpl' => APP_PATH.'admin/view/public/success.tpl',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------
    'log'       => [
        'type'  => 'File',// 日志记录方式，内置 file socket 支持扩展
        'path'  => LOG_PATH,// 日志保存目录
        'level' => [],// 日志记录级别
        'apart_level' => ['error','sql'],

//        'type' => 'socket',
//        'host' => 'localhost',
//        'show_included_files' => true,
//        'force_client_ids' => ['slog_bead1'],
//        'allow_client_ids' => ['slog_bead1'],
    ],


    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache' => [
        'type'   => 'file',// 驱动方式
        'path'   => CACHE_PATH,// 缓存保存目录
        'prefix' => '',// 缓存前缀
        'expire' => 0,// 缓存有效期 0表示永久缓存
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------
    'session'            => [
        'id'             => '',
        'var_session_id' => '',// SESSION_ID的提交变量,解决flash上传跨域
        'prefix'         => 'think',// SESSION 前缀
        'type'           => '',// 驱动方式 支持redis memcache memcached
        'auto_start'     => true,// 是否自动开启 SESSION
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'        => [
        'prefix'    => '',// cookie 名称前缀
        'expire'    => 0,// cookie 保存时间
        'path'      => '/',// cookie 保存路径
        'domain'    => '',// cookie 有效域名
        'secure'    => false,//  cookie 启用安全传输
        'httponly'  => '',// httponly设置
        'setcookie' => true,// 是否使用 setcookie
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],


    // +----------------------------------------------------------------------
    // | 数据库设置
    // +----------------------------------------------------------------------
    'data_backup_path'     => '../data/',   //数据库备份路径必须以 / 结尾；
    'data_backup_part_size' => '20971520',  //该值用于限制压缩后的分卷最大长度。单位：B；建议设置20M
    'data_backup_compress' => '1',          //压缩备份文件需要PHP环境支持gzopen,gzwrite函数        0:不压缩 1:启用压缩
    'data_backup_compress_level' => '9',    //压缩级别   1:普通   4:一般   9:最高


    // +----------------------------------------------------------------------
    // | 验证类型
    // +----------------------------------------------------------------------
    'verify_type' => '0',   //验证码类型：0拖动滑块验证， 1数字验证码
    'auth_key' => 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN', //默认数据加密KEY
    'pages'    => '10',//分页数
    'salt'     => 'wZPb~yxvA!ir38&Z',//加密串
    // 自动定位控制器 => 接口版本控制时,多层目录访问
    'controller_auto_search'=>true,

    // 自定义参数
    'qiniu_img_domain' => 'http://appc.qunarmei.com',
    'dingding_domain' => 'http://dingding.chengmei.com',
];