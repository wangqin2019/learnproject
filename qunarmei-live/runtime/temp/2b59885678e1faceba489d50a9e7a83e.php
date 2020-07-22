<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:107:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\set_conf.html";i:1594713131;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
<!--<link rel="stylesheet" type="text/css" media="all" href="/sldate/daterangepicker-bs3.css" />-->
<body class="gray-bg">
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <ul class="layui-nav">
                        <li class="layui-nav-item"><a href="<?php echo url('index'); ?>">考核管理</a></li>
                        <li class="layui-nav-item"><a href="<?php echo url('itemManage'); ?>">项目管理</a></li>
                        <li class="layui-nav-item"><a href="<?php echo url('setConf'); ?>">设置</a></li>
                    </ul>
                </div>
                <div class="ibox-content">
                    <form class="form-horizontal m-t" name="edit" id="edit" method="post" action="#">
                        <div class="form-group">
                            <label class="col-sm-3 control-label"  style="font-size: 20px;">考核人员设置</label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">考核人员：</label>
                            <div class="col-sm-6">
                                <button type="button" class="layui-btn" id="add">添加</button>
                            </div>
                            <div style="display: none" id="test_table">
                                <table class="layui-table" id="test" lay-filter="test3"></table>
                            </div>
                        </div>

                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">打分有效时间：</label>
                            <div class="input-group col-sm-4">
                                考核有效时间结束后<input id="score_effective_time" type="text" name="score_effective_time" placeholder="" value="<?php echo $score_effective_time; ?>" style="width:50px;" lay-verify="required">天内
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">短信提醒：</label>
                            <div class="input-group col-sm-4">
                                打分有效时间结束前<input id="sms_tips" type="text" name="sms_tips" placeholder="" value="<?php echo $sms_tips; ?>" style="width:50px;" lay-verify="required">天提醒
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label" style="font-size: 20px;">分数设置</label>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">分数设置：</label>
                            <div class="input-group col-sm-4">
                                <input id="point_system" type="text" name="point_system" placeholder="" value="<?php echo $point_system; ?>" style="width:50px;" lay-verify="required">分制
                            </div>
                        </div>


                        <script type="text/html" id="barDemo">
                            <a class="layui-btn layui-btn-xs" lay-event="edit"><i class="layui-icon layui-icon-edit" ></i></a>
                            <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="layui-icon layui-icon-delete" ></i></a>
                        </script>

                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <div class="col-sm-4 col-sm-offset-3">
                                <button class="btn btn-primary" type="submit" name="submit" value="1" lay-filter="formDemo" lay-submit=""><i class="fa fa-save"></i> 保存信息</button>&nbsp;&nbsp;&nbsp;
                                <a class="btn btn-danger" href="javascript:history.go(-1);"><i class="fa fa-close"></i> 返回</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
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
<script src="/static/admin/js/layui/layui/layui.js"></script>
<script type="text/javascript">


    var data_user = [];
    var table = [];


    // 获取子窗口的值
    function getrowselect(userdata) {
        data_user3 = getTableData();
        userdata2 = JSON.stringify(userdata);
        $.ajax({
            url:"<?php echo url('getXzData'); ?>?type=set_conf",
            data:{'mobile':userdata2,'table_data':data_user3},
            type:"Post",
            dataType:"json",
            success:function(data){
                data_user = data;
                console.log(data_user)
                // 显示选中的表格
//                $("#test_table").css("display","block");
                $("#test_table").show();
                // 表格数据渲染
                table.render({
                    elem: '#test'
                    ,cellMinWidth: 80 //全局定义常规单元格的最小宽度，layui 2.2.1 新增
                    ,cols: [[
                        {field:'name', title: '姓名'}
                        ,{field:'depart', title: '部门名称'}
                        ,{field:'mobile', title: '手机号' , edit: 'text'}
                        ,{fixed: 'right', title:'操作', toolbar: '#barDemo'}
                    ]]
                    // 赋值已知数据到表格
                    ,data: data_user
                });
            },
            error:function(data){
                $.messager.alert('错误',data.msg);
            }
        });
    }

    layui.use(['form','laydate','upload','table'], function() {
        var form = layui.form;
        table = layui.table;
        // 点击添加按钮弹窗
        $("#add").click(function(){
            layer.open({
                //layer提供了5种层类型。可传入的值有：0（信息框，默认）1（页面层）2（iframe层）3（加载层）4（tips层）
                type:2,
                title:"添加考核对象",
                area: ['50%','50%'],
                content: "<?php echo url('addAssessUser'); ?>"
            });
        });

        // 表格内单行操作
        table.on('tool(test3)', function(obj){
            var name = obj.data.name;
            var mobile = obj.data.mobile;
            switch(obj.event){
                // 删除
                case 'del':
                    obj.del(); //删除对应行（tr）的DOM结构，并更新缓存
                    layer.close(index);
                    break;
                // 修改
                case 'edit':
                    data = obj.data //得到所在行所有键值
//                    layer.msg('[mobile: '+ data.mobile +'] ' + name);
                    // 请求修改数据库
                    $.getJSON("<?php echo url('updXzData'); ?>",{name: name,mobile: data.mobile}, function(ret){
                        layer.msg('修改成功');
                    });
                    break;
            };
        });


        // 获取整个表单数据
        form.on('submit(formDemo)', function (data){
            res1 = emptyData($("#score_effective_time").val(),'打分有效时间');
            res2 = emptyData($("#sms_tips").val(),'短信提醒');
            res3 = emptyData($("#point_system").val(),'分数设置');
            if(!(res1 && res2 && res3)){
                return false;
            }


            tableData = getTableData();
            formData = getFromData();
            // 提交数据
            $.getJSON("<?php echo url('setConf'); ?>?type=submit",{tableData: tableData,formData: formData}, function(ret){
                num = ret.code;
                // 弹窗提示
                layer.msg(ret.msg,{icon:num,shade:0.1,time:2000});
//                if(ret.code == 1){
//                    window.location.href = "<?php echo url('setConf'); ?>";
//                }
            });
            return false;
        });

        // 获取初始表格数据
        var tableCsData = JSON.parse('<?php echo $res_mobile; ?>');
        console.log(tableCsData)
        if(tableCsData){
            $("#test_table").show();
            // 表格数据渲染
            table.render({
                elem: '#test'
                ,cellMinWidth: 80 //全局定义常规单元格的最小宽度，layui 2.2.1 新增
                ,cols: [[
                    {field:'name', title: '姓名'}
                    ,{field:'depart', title: '部门名称'}
                    ,{field:'mobile', title: '手机号' , edit: 'text'}
                    ,{fixed: 'right', title:'操作', toolbar: '#barDemo'}
                ]]
                // 赋值已知数据到表格
                ,data: tableCsData
            });
        }
    });

    // 获取整个表格数据
    function getTableData(){
        // 获取表格数据
        tableJsonData = JSON.stringify(table.cache);
        return tableJsonData;
    }

    // 获取整个表单数据
    function getFromData(){
        // 获取整个表单数据
        var formData = {};
        var t = $('#edit').serializeArray();
        $.each(t, function() {
            formData[this.name] = this.value;
        });
        return JSON.stringify(formData)
    }

    // 参数不能为空
    function emptyData(closeContent,msg) {
        flag = false;
        if(typeof closeContent == "undefined" || closeContent == null || closeContent == ""){
            // 不能为空
            layer.msg(msg+'不能为空',{icon:2,shade:0.1,offset: '40%',time:2000});
        }else{
            flag = true;
        }
        return flag;
    }
</script>
</body>
</html>
