<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:104:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\index.html";i:1594783894;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
            <ul class="layui-nav">
                <li class="layui-nav-item"><a href="">考核管理</a></li>
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
                    <form name="admin_list_sea" class="form-search" method="post" action="#">
                        <div class="col-sm-12">
                            <div class="layui-input-inline">
                                类型:
                                <select name="assess_type"  lay-search="" id="assess_type">
                                    <option value="">全部</option>
                                    <option value="1" <?php if($assess_type == 1): ?>selected<?php endif; ?>>直播</option>
                                    <option value="2" <?php if($assess_type == 2): ?>selected<?php endif; ?>>录像</option>
                                </select>
                            </div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <div class="layui-input-inline">
                                考核项目:
                                <select name="project_id"  lay-search="" id="project_id">
                                    <option value="">全部</option>
                                    <?php if($projects): foreach($projects as $v): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php if($project_id == $v['id']): ?>selected<?php endif; ?>><?php echo $v['assess_project']; ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <div class="layui-input-inline">
                                <input type="text" id="key" class="form-control" name="key" value="<?php echo $val; ?>" placeholder="输入考核名称" />
                            </div>
                            <div class="layui-input-inline">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
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

                    <script type="text/html" id="toolbarDemo">
                        <div class="layui-btn-container">
                            <a href="<?php echo url('addAssess'); ?>"><button class="layui-btn layui-btn" lay-event="">新建考核</button></a>
                            <button class="layui-btn layui-btnm" lay-event="getCheckLength">批量删除</button>
                        </div>
                    </script>
                    <script type="text/html" id="barDemo">
                        <a class="layui-btn layui-btn-xs" lay-event="detail">详情</a>
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
    var assess_type = $("#assess_type").val();
    var project_id = $("#project_id").val();
    var key = $("#key").val();

    layui.use('table', function(){
        var table = layui.table;

        //温馨提示：默认由前端自动合计当前行数据。从 layui 2.5.6 开始： 若接口直接返回了合计行数据，则优先读取接口合计行数据。
        //详见：https://www.layui.com/doc/modules/table.html#totalRow
        table.render({
            elem: '#test'
            ,url: "<?php echo url('index'); ?>?assess_type="+assess_type+"&project_id="+project_id+"&key="+key
            ,toolbar: '#toolbarDemo'
            ,title: '用户数据表'
            ,totalRow: true
            ,limit: 50
            ,cols: [[
                {type: 'checkbox', fixed: 'left'}
                ,{field:'id', title:'ID', width:80, fixed: 'left'}
                ,{field:'assess_name', title:'考核名称'}
                ,{field:'assess_type', title:'考核类型'}
                ,{field:'assess_project', title:'考核项目'}
                ,{field:'create_time', title:'更新时间'}
                ,{field:'num', title:'考核人数'}
                ,{fixed: 'right', title:'操作', toolbar: '#barDemo'}
            ]]
            ,page: true
            ,id: 'testReload'
        });

        //工具栏事件批量操作
        table.on('toolbar(test)', function(obj){
            var checkStatus = table.checkStatus(obj.config.id);
            switch(obj.event){
                case 'getCheckData':
                    var data = checkStatus.data;
                    layer.alert(JSON.stringify(data));
                    break;
                case 'getCheckLength':
                    var data = checkStatus.data;
                    var data_length = data.length;
                    var ids = [];
                    for(var i = 0 ; i < data_length ; i++){
                        ids.push(data[i].id);

                    }
                    id = JSON.stringify(ids)
                    del(id)
                    break;
                case 'del':
                    var id = obj.data.id;
                    layer.alert(id);
                    break;
            };
        });

        // 表格内单行操作
        table.on('tool(test)', function(obj){
            var id = obj.data.id;
            switch(obj.event){
                // 删除
                case 'del':
                    del(id);
                    break;
                // 修改
                case 'edit':
                    window.location.href = "<?php echo url('editAssess'); ?>?id="+id;
                    break;
                // 详情
                case 'detail':
                    window.location.href = "<?php echo url('assessDetail'); ?>?id="+id;
                    break;
            };
        });

        function del(ids){
            // 弹窗删除操作
            layer.confirm('确认删除么', function(index){
                //向服务端发送删除指令og
                $.getJSON("<?php echo url('delAssess'); ?>",{ids: ids}, function(ret){
                    layer.close(index);//关闭弹窗
                    table.reload('testReload', )
                });
                layer.close(index);
            });
        }
    });
</script>
</body>
</html>