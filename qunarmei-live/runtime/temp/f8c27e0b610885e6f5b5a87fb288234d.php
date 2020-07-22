<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:111:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\score_detail.html";i:1594633106;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo config('WEB_SITE_TITLE'); ?></title>
    <link href="/static/admin/css/bootstrap.min.css?v=3.3.6" rel="stylesheet">
    <link href="/static/admin/css/font-awesome.min.css?v=4.4.0" rel="stylesheet">
    <link href="/static/admin/css/animate.min.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/iCheck/custom.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/chosen/chosen.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/switchery/switchery.css" rel="stylesheet">
    <link href="/static/admin/css/style.min.css?v=4.1.0" rel="stylesheet">
    <link href="/static/admin/css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="/static/admin/css/mystyle.css" rel="stylesheet">
    <link href="/static/admin/elementUI-1.4.12/css/index.min.css" rel="stylesheet">
    <script src="/static/admin/elementUI-1.4.12/js/vue.min.js"></script>
    <script src="/static/admin/elementUI-1.4.12/js/index.min.js"></script>
    <style type="text/css">
    .long-tr th{
        text-align: center
    }
    .long-td td{
        text-align: center
    }
    </style>
</head>
<link rel="stylesheet" href="/static/admin/js/layui/layui/css/layui.css" media="all">

<style>
</style>
<body class="gray-bg">
<div class="wrapper wrapper-content animated fadeInRight">
    <!-- Panel Other -->
    <div class="ibox float-e-margins">
        <div class="ibox-title">
            <h5>分数详情</h5>
        </div>
        <div class="ibox-content">
            <!--搜索框开始-->           

            <div class="hr-line-dashed"></div>
            <div class="layui-container">
                <div class="layui-row" style="font-size: x-large">考核详情</div>
                <div class="hr-line-dashed"></div>
                <div>
                    <table class="layui-hide" id="demo" lay-filter="test3"></table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="/static/admin/js/layui/layui/layui.js"></script>
<script src="/static/admin/js/jquery.min.js?v=2.1.4"></script>
<script src="/static/admin/js/bootstrap.min.js?v=3.3.6"></script>
<script src="/static/admin/js/content.min.js?v=1.0.0"></script>
<script src="/static/admin/js/plugins/chosen/chosen.jquery.js"></script>
<script src="/static/admin/js/plugins/iCheck/icheck.min.js"></script>
<script src="/static/admin/js/plugins/layer/laydate/laydate.js"></script>
<script src="/static/admin/js/plugins/switchery/switchery.js"></script><!--IOS开关样式-->
<script src="/static/admin/js/jquery.form.js"></script>
<script src="/static/admin/js/layer/layer.js"></script>
<script src="/static/admin/js/laypage/laypage.js"></script>
<script src="/static/admin/js/laytpl/laytpl.js"></script>
<script src="/static/admin/js/lunhui.js"></script>
<script>
    $(document).ready(function(){$(".i-checks").iCheck({checkboxClass:"icheckbox_square-green",radioClass:"iradio_square-green",})});
</script>

<script type="text/javascript">
    // json字符串转为json对象
    var data_user = JSON.parse('<?php echo $res; ?>')

    console.log(data_user);
// [{"mobile":"15821462605","user_name":"罗晓","depart":"数字方案部","live_time":1598335200,"status":4},{"mobile":"15921324164","user_name":"王钦","depart":"数字方案部","live_time":1598335200,"status":3}]
    layui.use('table', function(){
        var table = layui.table;

        // 渲染数据
        table.render({
            elem: '#demo'
            ,title: '分数详情'
            ,totalRow: true
            ,limit: 50
            ,cols: [[
                {field:'user_name', title:'考核成员昵称'}
                ,{field:'depart', title:'办事处/门店' }
                ,{field:'score', title:'打分'}
            ]]
            ,data: data_user
            ,page: true
        });
    });
</script>
</body>
</html>