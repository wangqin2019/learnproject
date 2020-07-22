<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:114:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\market_assessment\add_assess_user.html";i:1594719561;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
<!--<link rel="stylesheet" type="text/css" media="all" href="/sldate/daterangepicker-bs3.css" />-->
<body class="gray-bg">
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>添加考核对象</h5>
                </div>
                <div class="ibox-content">
                    <form class="form-horizontal m-t" name="edit" id="edit" method="post" action="<?php echo url('addAssessUser'); ?>?type=1">
                        <input id="assess_id" type="hidden" class="form-control" name="id" placeholder="" value="<?php echo $assess_id; ?>">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">选择人员：</label>
                            <div class="col-sm-6">
                                <div id="test12" class="demo-tree-more"></div>
                            </div>
                        </div>

                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <div class="col-sm-4 col-sm-offset-3">
                                <button class="btn btn-primary" type="submit" name="submit" value="1"><i class="fa fa-save"></i> 保存信息</button>&nbsp;&nbsp;&nbsp;
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
<script src="/static/admin/js/layui/layui/layui.js?v=1"></script>
<script src="/static/admin/js/layui/layui/layui.all.js?v=2"></script>
<script type="text/javascript">

    layui.use(["form","tree", "util"], function() {
        var form = layui.form;
        tree = layui.tree;
        layer = layui.layer;
        util = layui.util;
        var data = <?php echo $res_data; ?>;
        //模拟数据
//        data = [{
//            title: '一级1'
//            ,id: 1
//            ,field: 'name1'
//            ,checked: true
//            ,spread: true
//            ,children: [{
//                title: '二级1-1 可允许跳转'
//                ,id: 3
//                ,field: 'name11'
//                ,href: 'https://www.layui.com/'
//                ,children: [{
//                    title: '三级1-1-3'
//                    ,id: 23
//                    ,field: ''
//                    ,children: [{
//                        title: '四级1-1-3-1'
//                        ,id: 24
//                        ,field: ''
//                        ,children: [{
//                            title: '五级1-1-3-1-1'
//                            ,id: 30
//                            ,field: ''
//                        },{
//                            title: '五级1-1-3-1-2'
//                            ,id: 31
//                            ,field: ''
//                        }]
//                    }]
//                },{
//                    title: '三级1-1-1'
//                    ,id: 7
//                    ,field: ''
//                    ,children: [{
//                        title: '四级1-1-1-1 可允许跳转'
//                        ,id: 15
//                        ,field: ''
//                        ,href: 'https://www.layui.com/doc/'
//                    }]
//                },{
//                    title: '三级1-1-2'
//                    ,id: 8
//                    ,field: ''
//                    ,children: [{
//                        title: '四级1-1-2-1'
//                        ,id: 32
//                        ,field: ''
//                    }]
//                }]
//            },{
//                title: '二级1-2'
//                ,id: 4
//                ,spread: true
//                ,children: [{
//                    title: '三级1-2-1'
//                    ,id: 9
//                    ,field: ''
//                    ,disabled: true
//                },{
//                    title: '三级1-2-2'
//                    ,id: 10
//                    ,field: ''
//                }]
//            },{
//                title: '二级1-3'
//                ,id: 20
//                ,field: ''
//                ,children: [{
//                    title: '三级1-3-1'
//                    ,id: 21
//                    ,field: ''
//                },{
//                    title: '三级1-3-2'
//                    ,id: 22
//                    ,field: ''
//                }]
//            }]
//        },{
//            title: '一级2'
//            ,id: 2
//            ,field: ''
//            ,spread: true
//            ,children: [{
//                title: '二级2-1'
//                ,id: 5
//                ,field: ''
//                ,spread: true
//                ,children: [{
//                    title: '三级2-1-1'
//                    ,id: 11
//                    ,field: ''
//                },{
//                    title: '三级2-1-2'
//                    ,id: 12
//                    ,field: ''
//                }]
//            },{
//                title: '二级2-2'
//                ,id: 6
//                ,field: ''
//                ,children: [{
//                    title: '三级2-2-1'
//                    ,id: 13
//                    ,field: ''
//                },{
//                    title: '三级2-2-2'
//                    ,id: 14
//                    ,field: ''
//                    ,disabled: true
//                }]
//            }]
//        },{
//            title: '一级3'
//            ,id: 16
//            ,field: ''
//            ,children: [{
//                title: '二级3-1'
//                ,id: 17
//                ,field: ''
//                ,fixed: true
//                ,children: [{
//                    title: '三级3-1-1'
//                    ,id: 18
//                    ,field: ''
//                },{
//                    title: '三级3-1-2'
//                    ,id: 19
//                    ,field: ''
//                }]
//            },{
//                title: '二级3-2'
//                ,id: 27
//                ,field: ''
//                ,children: [{
//                    title: '三级3-2-1'
//                    ,id: 28
//                    ,field: ''
//                },{
//                    title: '三级3-2-2'
//                    ,id: 29
//                    ,field: ''
//                }]
//            }]
//        }];

        //基本演示
        tree.render({
            elem: '#test12'
            ,data: data
            ,showCheckbox: true  //是否显示复选框
            ,id: 'demoId1'
            ,isJump: true //是否允许点击节点时弹出新窗口跳转
            ,click: function(obj){
                var data = obj.data;  //获取当前点击的节点数据
                layer.msg('状态：'+ obj.state + '<br>节点数据：' + JSON.stringify(data));
            }
        });

        // 点击添加按钮弹窗
        $("#add").click(function(){
            layer.open({
                //layer提供了5种层类型。可传入的值有：0（信息框，默认）1（页面层）2（iframe层）3（加载层）4（tips层）
                type:1,
                title:"添加考核对象",
                area: ['50%','50%'],
                content: "<?php echo url('addAssessUser'); ?>"
            });
        });
    });

    $(function(){
        $('#edit').ajaxForm({
            beforeSubmit: checkForm, // 此方法主要是提交前执行的方法，根据需要设置
            success: complete, // 这是提交后的方法
            dataType: 'json'
        });

        function checkForm(){

        }

        function complete(data){
            var checkedData = tree.getChecked('demoId1'); //获取选中节点的数据
            console.log(checkedData);
//            if(data.code == 1){
//                layer.msg(data.msg, {icon: 6,time:1500,shade: 0.1}, function(index){
//                    layer.close(index);
//                    window.location.href="<?php echo url('index'); ?>";
//                });
//            }else{
//                layer.msg(data.msg, {icon: 5,time:1500,shade: 0.1}, function(index){
//                    layer.close(index);
//                });
//                return false;
//            }
            var mob = getTreeUid(checkedData)
            console.log(mob);
            // 传值给父窗口
            window.parent.getrowselect(mob);

            save();
//            return false;
        }
        // 关闭弹出层
        function save() {
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);//关闭当前页
        }

        // 递归获取选中树形的用户号码

        //获取属性用户ID集合
        function getTreeUid(checkData){
//            let arrs = []
            let uids = []
//            let groups = []
//            let usernames = []
            if(checkData){
                $.each(checkData,function(i1,val1){
                    if(val1.children){
                        $.each(val1.children,function(i2,val2){
                            if(val2.children){
                                $.each(val2.children,function(i3,val3){
                                    // console.log(val3.children)
                                    if(val3.children){
                                        $.each(val3.children,function(i4,val4){
                                            if(val4.children){
                                                $.each(val4.children,function(i5,val5){
                                                    if(val5.children){
                                                        $.each(val5.children,function(i5,val6){
                                                            if(val6.mobile && uids.indexOf(val6.mobile) == -1){uids.push(val6.mobile)};
//                                                            if(val6.group_name && groups.indexOf(val6.group_name) == -1){groups.push(val6.group_name)};
//                                                            if(val6.title && usernames.indexOf(val6.title) == -1){usernames.push(val6.title)};
                                                        })
                                                    }
                                                    if(val5.mobile && uids.indexOf(val5.mobile) == -1){uids.push(val5.mobile)};
//                                                    if(val5.group_name && groups.indexOf(val5.group_name) == -1){groups.push(val5.group_name)};
//                                                    if(val5.title && usernames.indexOf(val5.title) == -1){usernames.push(val5.title)};
                                                })
                                            }
                                            if(val4.mobile && uids.indexOf(val4.mobile) == -1){uids.push(val4.mobile)};
//                                            if(val4.group_name && groups.indexOf(val4.group_name) == -1){groups.push(val4.group_name)};
                                        })
                                    }
                                    if(val3.mobile && uids.indexOf(val3.mobile) == -1){uids.push(val3.mobile)};
//                                    if(val3.group_name && groups.indexOf(val3.group_name) == -1){groups.push(val3.group_name)};
                                })
                            }
                            if(val2.mobiles && uids.indexOf(val2.mobile) == -1){uids.push(val2.mobile)};
//                            if(val2.group_name && groups.indexOf(val2.group_name) == -1){groups.push(val2.group_name)};
                        })
                    }
                    if(val1.mobiles && uids.indexOf(val1.mobile) == -1){uids.push(val1.mobile)};
//                    if(val1.group_name && groups.indexOf(val1.group_name) == -1){groups.push(val1.group_name)};
                })
            }
//            arrs[0] = uids;
//            arrs[1] = groups;
//            arrs[2] = usernames;
//            return arrs;
            return uids;
        }
    });
</script>
</body>
</html>