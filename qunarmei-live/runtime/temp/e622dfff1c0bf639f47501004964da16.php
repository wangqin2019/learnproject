<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:110:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\item_manage.html";i:1594712597;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
<link rel="stylesheet" href="/static/admin/js/layui/layui/css/layui.css?v=1" media="all">

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
            <ul class="layui-nav">
                <li class="layui-nav-item"><a href="<?php echo url('index'); ?>">考核管理</a></li>
                <li class="layui-nav-item"><a href="<?php echo url('itemManage'); ?>">项目管理</a></li>
                <li class="layui-nav-item"><a href="<?php echo url('setConf'); ?>">设置</a></li>
            </ul>
        </div>
        <div class="ibox-content">
            <!--搜索框开始-->           
            <div class="row">
                <div class="col-sm-12">   
                <div  class="col-sm-2" style="width: 130px">

                </div>                                            
                    <form name="admin_list_sea" class="form-search" method="post" action="<?php echo url('itemManage'); ?>">
                        <div class="col-sm-12">
                            <div class="layui-input-inline">
                                <input type="text" id="key" class="form-control" name="key" value="<?php echo $val; ?>" placeholder="输入考核名称" />
                            </div>
                            <div class="layui-input-inline">
                                <button class="btn btn-primary" type="submit" id="serach"><i class="fa fa-search"></i> 搜索</button>
                            </div>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <div class="layui-input-inline">
                                <button type="submit" class="btn btn-primary" id="add"> 新建项目</button>
                            </div>
                        </div>
                    </form>                         
                </div>
            </div>
            <!--搜索框结束-->
            <div class="hr-line-dashed"></div>
            <div class="example-wrap">
                <div class="example">
                    <table class="layui-hide" id="test" lay-filter="test"></table>
                    <script type="text/html" id="barDemo">
                        <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
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
    var table ;
    // 点击添加按钮弹窗
    $("#add").click(function(){
        layer.open({
            id:1,
            type: 1,
            area: ['350px','150px'],
            title:'项目管理',
            content: "<div class='layui-form-item'><label class='layui-form-label' style='width: 90px;'>项目名称:</label><div class='layui-input-block'><input type='text' name='assess_project' lay-verify='required'  autocomplete='' placeholder='请输入' class='layui-input' value='' id='area' style='width: auto'></div></div>",
            btn:['保存','取消'],
            yes:function (index,layero) {
                //获取输入框里面的值
                var closeContent = top.$("#area").val() || $("#area").val();
                code = emptyData(closeContent);
                console.log(closeContent);
                console.log(code);
                if(code){
                    layer.close(index);
                    // 在这里提交数据
                    $.get("<?php echo url('updItem'); ?>?type=add", {assess_project: closeContent}, function (data) {
                        var num = data.code;
                        layer.msg(data.msg,{icon:num,shade:0.1,offset: '40%',time:2000});
                        layer.close(index); //如果设定了yes回调，需进行手工关闭
                        setTimeout(function(){
                            //刷新
                            location.reload();
                        },1000);

                    });
                }
            },
            no:function (index,layero) {
                layer.close(index);
            }
        });
        return false;
    });



    var data_arr = JSON.parse('<?php echo $res; ?>')
    layui.use('table', function(){
        table = layui.table;

        //温馨提示：默认由前端自动合计当前行数据。从 layui 2.5.6 开始： 若接口直接返回了合计行数据，则优先读取接口合计行数据。
        //详见：https://www.layui.com/doc/modules/table.html#totalRow
        table.render({
            elem: '#test'
            ,toolbar: '#toolbarDemo'
            ,title: '用户数据表'
            ,totalRow: true
            ,limit: 50
            ,cols: [[
                {field:'id', title:'序号', width:80, fixed: 'left'}
                ,{field:'assess_project', title:'项目名称'}
                ,{field:'create_time', title:'创建时间'}
                ,{fixed: 'right', title:'操作', toolbar: '#barDemo'}
            ]]
            ,data: data_arr
            ,page: true
            ,even: true
            ,id: 'testReload'
        });

        // 表格内单行操作
        table.on('tool(test)', function(obj){
            var id = obj.data.id;
            var assess_project = obj.data.assess_project;
            switch(obj.event){
                // 删除
                case 'del':
                    // 确认框
                    layer.confirm("确认要删除吗,删除后不能恢复?", { title: "删除确认" }, function (index) {

                        $.get("<?php echo url('updItem'); ?>?type=del", { id: id}, function (data) {
                            var num = data.code;
                            layer.msg(data.msg,{icon:num,shade:0.3,offset: '40%',time:2000});
                        });
                        // 删除dom结构
                        obj.del();
                    });
                    break;
                // 修改
                case 'edit':

                    layer.open({
                        id:1,
                        type: 1,
                        area: ['350px','150px'],
                        title:'项目管理',
                        content: "<div class='layui-form-item'><label class='layui-form-label' style='width: 90px;'>项目名称:</label><div class='layui-input-block'><input type='text' name='assess_project' lay-verify='assess_project' autocomplete='off' placeholder='请输入' class='layui-input' value='"+assess_project+"' id='area' style='width: auto'></div></div>",
                        btn:['保存','取消'],
                        yes:function (index,layero) {
                            //获取输入框里面的值
                            var closeContent = top.$("#area").val() || $("#area").val();
//                            if(closeContent){
//                                console.log(closeContent);
//                            }
                            code = emptyData(closeContent);
                            if(code){
                                layer.close(index);
                                // 在这里提交数据
                                $.get("<?php echo url('updItem'); ?>", { id: id , assess_project: closeContent}, function (data) {
                                    var num = data.code;
                                    layer.msg(data.msg,{icon:num,shade:0.3,offset: '40%',time:2000});
                                    //layer.close(index); //如果设定了yes回调，需进行手工关闭
//                                setTimeout(function(){
//                                    //刷新
//                                    location.reload();
//                                },1000);
                                    // 修改dom结构数据
                                    obj.update({
                                        assess_project:closeContent
                                    });
                                });
                            }

                        },
                        no:function (index,layero) {
                            layer.close(index);
                        }
                    });
                    break;

            };
        });
    });

    // 参数不能为空
    function emptyData(closeContent) {
        flag = false
        if(typeof closeContent == "undefined" || closeContent == null || closeContent == ""){
            // 不能为空
            layer.msg('项目名称不能为空',{icon:2,shade:0.1,offset: '40%',time:2000});
        }else{
            flag = true
        }
        return flag;
    }
</script>
</body>
</html>