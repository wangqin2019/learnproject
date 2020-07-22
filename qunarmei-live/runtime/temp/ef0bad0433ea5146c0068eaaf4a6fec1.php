<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:112:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\assess_detail.html";i:1594884012;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
    .layui-table-tool-self{
        display: none;
    }
</style>
<body class="gray-bg">
<div class="wrapper wrapper-content animated fadeInRight">
    <!-- Panel Other -->
    <div class="ibox float-e-margins">
        <div class="ibox-title">
            <h5>考核详情</h5>
        </div>
        <div class="ibox-content">
            <!--搜索框开始-->           
            <div class="row">
                <div class="col-sm-12">   
                <div  class="col-sm-2" style="width: 130px">
                </div>
                </div>
            </div>
            <!--搜索框结束-->
            <div class="hr-line-dashed"></div>
            <div class="layui-container">
                <div class="layui-row" style="font-size: x-large">基本信息</div>
                <div class="hr-line-dashed"></div>
                <div class="layui-row">
                    <div class="layui-col-md4">
                        <div class="grid-demo grid-demo-bg1">考核类型:&nbsp;<?php echo $res['assess_type']; ?></div>
                    </div>
                    <div class="layui-col-md4 layui-col-md-offset4">
                        <div class="grid-demo">考核项目:&nbsp;<?php echo $res['assess_project']; ?></div>
                    </div>
                </div>
                <div class="hr-line-dashed"></div>
                <div class="layui-row">
                    <div class="layui-col-md4">
                        <div class="grid-demo grid-demo-bg1">考核名称:&nbsp;<?php echo $res['assess_name']; ?></div>
                    </div>
                    <div class="layui-col-md4 layui-col-md-offset4">
                        <div class="grid-demo">考核对象:&nbsp;<?php echo $res['num']; ?></div>
                    </div>
                </div>
            </div>
            <div class="hr-line-dashed"></div>
            <div class="layui-container">
                <div class="layui-row" style="font-size: x-large">考核详情</div>
                <div class="hr-line-dashed"></div>
                <div>
                    <table class="layui-hide" id="demo" lay-filter="test3"></table>
                    <script type="text/html" id="barDemo">
                        <a class="layui-btn layui-btn-xs" lay-event="detail">分数详情</a>
                        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
                    </script>
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
    var data_user = JSON.parse('<?php echo $res_user; ?>')
//    console.log(data_user);
// [{"mobile":"15821462605","user_name":"罗晓","depart":"数字方案部","live_time":1598335200,"status":4},{"mobile":"15921324164","user_name":"王钦","depart":"数字方案部","live_time":1598335200,"status":3}]
    layui.use(['table','layer'], function(){
        var table = layui.table;

        // 渲染数据
        table.render({
            elem: '#demo'
            ,title: '考核对象'
            ,totalRow: true
            ,limit: 50
            ,cols: [[
                {field:'id', title:'ID'}
                ,{field:'user_name', title:'姓名'}
                ,{field:'depart', title:'部门名称'}
                ,{field:'mobile', title:'手机号'}
                ,{field:'live_time', title:'直播时间'}
                ,{field:'status', title:'状态'}
                ,{fixed: 'right', title:'操作', toolbar: '#barDemo'}
            ]]
            ,data: data_user
            ,page: true
        });

        // 表格内单行操作
        table.on('tool(test3)', function(obj){
            var name = obj.data.user_name;
            var mobile = obj.data.mobile;
            var assess_id = obj.data.id;
            switch(obj.event){
                // 删除
                case 'del':
                    // 确认框
                    layer.confirm("确认要删除吗，删除后不能恢复", { title: "删除确认" }, function (index) {

                        $.get("<?php echo url('delAssessUser'); ?>", { name: name , mobile: mobile , assess_id:assess_id}, function (data)
                        {
                            var num = data.code==200 ? 1 : 2;
                            layer.msg(data.msg,{icon:num,shade:0.3,offset: '40%',time:2000});

                            //layer.close(index); //如果设定了yes回调，需进行手工关闭
                            setTimeout(function(){
                                //刷新
                                location.reload();
                            },1000);
                        });
                    });
                    break;
                // 弹出遮罩层
                case 'detail':
                    layer.open({
                        //layer提供了5种层类型。可传入的值有：0（信息框，默认）1（页面层）2（iframe层）3（加载层）4（tips层）
                        type:2,
                        title:"分数详情",
                        area: ['50%','50%'],
                        content: "<?php echo url('scoreDetail'); ?>?assess_id="+assess_id
                    });
                    break;
            };
        });
    });
</script>
</body>
</html>