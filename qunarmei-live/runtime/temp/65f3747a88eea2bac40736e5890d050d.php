<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:110:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\edit_assess.html";i:1594783365;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
                    <h5>编辑考核</h5>
                </div>
                <div class="ibox-content">
                    <form class="form-horizontal m-t" name="edit" id="edit" method="post" action="<?php echo url('addAssess'); ?>?type=edit">
                        <input id="id" type="hidden" class="form-control" name="id" placeholder="" value="<?php echo $id; ?>">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">考核类型：</label>
                            <div class="col-sm-6">
                                <div class="radio i-checks">
                                    <input type="radio" name='assess_type' value="1" <?php if($list['assess_type'] == 1): ?>checked="checked<?php endif; ?>"/>直播&nbsp;&nbsp;
                                    <input type="radio" name='assess_type' value="2" <?php if($list['assess_type'] == 2): ?>checked="checked<?php endif; ?>"/>录像
                                </div>
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">考核名称：</label>
                            <div class="input-group col-sm-4">
                                <input id="assess_name" type="text" class="form-control" name="assess_name" placeholder="" value="<?php echo $list['assess_name']; ?>">
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                                <label class="col-sm-3 control-label">起止时间：</label>
                                <input type="text" id="begin_time" name="begin_time" required  lay-verify="required" placeholder="请输入开始时间" autocomplete="off" value="<?php echo $list['begin_time']; ?>">至
                                <input type="text" id="end_time" name="end_time" required  lay-verify="required" placeholder="请输入结束时间" autocomplete="off" value="<?php echo $list['end_time']; ?>" >
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">考核项目：</label>
                            <div class="col-sm-6">
                                <select name="project_id"  lay-search="" id="project_id">
                                    <option value="">全部</option>
                                    <?php if($projects): foreach($projects as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php if($list['project_id'] == $v['id']): ?>selected<?php endif; ?>><?php echo $v['assess_project']; ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">考核对象：</label>
                            <div class="col-sm-6">
                                <button type="button" class="layui-btn" id="add">添加</button>
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>

                        <div style="" id="test_table">
                            <table class="layui-table" id="test" lay-filter="test3"></table>
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
    $(function(){
        $('#edit').ajaxForm({
            beforeSubmit: checkForm, // 此方法主要是提交前执行的方法，根据需要设置
            success: complete, // 这是提交后的方法
            dataType: 'json'
        });

        function checkForm(){
            var assess_name = $("#assess_name").val();
            var begin_time = $("#begin_time").val();
            var end_time = $("#end_time").val();
            var project_id = $("#project_id").val();

            res1 = emptyData(assess_name,'考核名称');
            res4 = emptyData(project_id,'考核项目');

            if(!(res1 && res4)){
                return false;
            }
        }

        function complete(data){

        }

    });

    // 获取子窗口的值
    function getrowselect(userdata) {

        data_user3 = getTableData();
        userdata2 = JSON.stringify(userdata);
//        console.log(data_user3)
//        console.log(userdata2)
        $.ajax({
            url:"<?php echo url('getXzData'); ?>?type=edit_assess&id=<?php echo $id; ?>",
            data:{'mobile':userdata2 , 'table_data':data_user3},
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
                        ,{field:'live_time', title: '直播时间' , edit: 'text',event:'date',data_field: "dBeginDate"}
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
        var laydate = layui.laydate;
        table = layui.table;

        var endDate= laydate.render({
            elem: '#end_time',//选择器结束时间
            type: 'datetime',
            min:"1970-1-1",//设置min默认最小值
            done: function(value,date){
                startDate.config.max={
                    year:date.year,
                    month:date.month-1,//关键
                    date: date.date,
                    hours: 0,
                    minutes: 0,
                    seconds : 0
                }
            }
        });
        //日期范围
        var startDate=laydate.render({
            elem: '#begin_time',
            type: 'datetime',
            max:"2099-12-31",//设置一个默认最大值
            done: function(value, date){
                endDate.config.min ={
                    year:date.year,
                    month:date.month-1, //关键
                    date: date.date,
                    hours: 0,
                    minutes: 0,
                    seconds : 0
                };
            }
        });

        // 初始表格数据渲染
        var data_cs = JSON.parse('<?php echo $res; ?>');
        table.render({
            elem: '#test'
            ,cellMinWidth: 80 //全局定义常规单元格的最小宽度，layui 2.2.1 新增
            ,cols: [[
                {field:'name', title: '姓名'}
                ,{field:'depart', title: '部门名称'}
                ,{field:'mobile', title: '手机号' , edit: 'text'}
                ,{field:'live_time', title: '直播时间' , edit: 'text',event:'date',data_field: "dBeginDate"}
                ,{fixed: 'right', title:'操作', toolbar: '#barDemo'}
            ]]
            // 赋值已知数据到表格
            ,data: data_cs
        });

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
        var live_time = '';
        table.on('tool(test3)', function(obj){
            var name = obj.data.name;
            var mobile = obj.data.mobile;
            var newdata = {};
            switch(obj.event){
                // 删除
                case 'del':
                    obj.del(); //删除对应行（tr）的DOM结构，并更新缓存
                    layer.close(index);
                    break;
                // 修改
                case 'edit':
                    data = obj.data; //得到所在行所有键值
//                    layer.msg('[mobile: '+ data.mobile +'] ' + name);
                    // 请求修改数据库
                    $.getJSON("<?php echo url('updXzData'); ?>",{name: name,mobile: data.mobile,live_time:live_time}, function(ret){
                        console.log('修改成功2');
                        layer.msg('修改成功!',{icon:1,shade:0.1,time:2000});
                    });
                    break;
                // 时间控件
                case 'date':
                    var field = $(this).data('field');
                    laydate.render({
                        elem: this.firstChild
                        , show: true //直接显示
                        , closeStop: this
                        , type: 'datetime'
                        , format: "yyyy-MM-dd HH:mm"
                        , done: function (value, date) {
                            newdata[field] = value;
                            obj.update(newdata);
                            live_time = value;
                        }
                    });
                    break;
            }
        });

        // 获取整个表单数据
        form.on('submit(formDemo)', function (data){

            tableData = getTableData();
            formData = getFromData();
            // 提交数据
            $.getJSON("<?php echo url('addAssess'); ?>?type=edit&id=<?php echo $id; ?>",{tableData: tableData,formData: formData}, function(ret){
                num = ret.code;
                // 弹窗提示
                layer.msg(ret.msg,{icon:num,shade:0.1,time:2000});
//                if(ret.code == 1){
//                    window.location.href = "<?php echo url('setConf'); ?>";
//                }
            });
            return false;
        });

//        function del(ids){
//            // 弹窗删除操作
//            layer.confirm('确认删除么', function(index){
//                //向服务端发送删除指令og
//                $.getJSON("<?php echo url('delXzData'); ?>",{ids: ids}, function(ret){
//                    layer.close(index);//关闭弹窗
//                    table.reload('test3', )
//                });
//                layer.close(index);
//            });
//        }
    });

    // 参数不能为空
    function emptyData(closeContent , msg) {
        flag = false
        if(typeof closeContent == "undefined" || closeContent == null || closeContent == ""){
            // 不能为空
            layer.msg(msg+'不能为空',{icon:2,shade:0.1,offset: '40%',time:2000});
        }else{
            flag = true
        }
        return flag;
    }

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
</script>
</body>
</html>
