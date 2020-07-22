<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:99:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\salesplan\salelist.html";i:1592902294;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
<body class="gray-bg">
<div class="wrapper wrapper-content animated fadeInRight">
    <!-- Panel Other -->
    <div class="ibox float-e-margins">
        <div class="ibox-title">
            <h5>门店销售方案配置</h5>
        </div>
        <div class="ibox-content">
            <!--搜索框开始-->
            <div class="row">
                <div class="col-sm-12">
                </div>
                <form name="admin_list_sea" class="form-search" method="post" action="<?php echo url('salelist'); ?>">
                    <div class="col-sm-5">
                     
                        <a href="<?php echo url('add_sale'); ?>"><button class="btn btn-outline btn-primary" type="button">添加配置</button></a>
                        门店编号:<input type="text" id="key"   name="key" value="<?php echo $val; ?>" placeholder="" />
                        &nbsp; <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    </div>
                </form>
            </div>
        </div>
        <!--搜索框结束-->
        <div class="hr-line-dashed"></div>
        <div class="example-wrap">
            <div class="example">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr class="long-tr">
                        <th width="3%">ID</th>
                        <th width="5%">办事处</th>
                        <th width="5%">门店名称</th>
                        <th width="5%">门店编号</th>
                        <th width="8%">方案详情</th>
                        <th width="8%">状态</th>
                        <th width="10%">操作</th>
                    </tr>
                    </thead>
                    <script id="list-template" type="text/html">
                        {{# for(var i=0;i<d.length;i++){  }}
                        <tr class="long-td">
                            <td>{{d[i].storeid}}</td>
                            <td>{{d[i].bsc}}</td>
                            <td>{{d[i].title}}</td>
                            <td>{{d[i].sign}}</td>
                            <td><a href="#" onclick="sale_list('{{d[i].sign}}')" style="cursor: pointer">详情</a></td>
                            <td><a href="#" {{# if(<?php echo $bsc; ?> ){ }}onclick="update_statu('{{d[i].sign}}')" {{# } }}>{{d[i].statu_val}}</a></td>
                            <td>
                                <a href="javascript:;" onclick="apply('{{d[i].sign}}')" class="btn btn-primary btn-xs btn-outline">
                                            <i class="fa fa-paste"></i> 发起申请</a>&nbsp;&nbsp;
                                <a href="javascript:;" onclick="del_b('{{d[i].sign}}')" class="btn btn-danger btn-xs btn-outline">
                                    <i class="fa fa-trash-o"></i> 删除</a>
                            </td>
                        </tr>
                        {{# } }}
                    </script>
                    <tbody id="list-content"></tbody>
                </table>
                <div id="AjaxPage" style="text-align:right;"></div>
                <div style="text-align: right;">
                    共<?php echo $count; ?>条数据，<span id="allpage"></span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- 加载动画 -->
<div class="spiner-example">
    <div class="sk-spinner sk-spinner-three-bounce">
        <div class="sk-bounce1"></div>
        <div class="sk-bounce2"></div>
        <div class="sk-bounce3"></div>
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

<script type="text/javascript">

    /**
     * [Ajaxpage laypage分页]
     * @param {[type]} curr [当前页]
     */
    Ajaxpage();

    function Ajaxpage(curr){
        var key=$('#key').val();
       
        $.getJSON('<?php echo url("salelist"); ?>', {
            page: curr || 1,key:key
        }, function(data){      //data是后台返回过来的JSON数据
            $(".spiner-example").css('display','none'); //数据加载完关闭动画
            if(data==''){
                $("#list-content").html('<td colspan="20" style="padding-top:10px;padding-bottom:10px;font-size:16px;text-align:center">暂无数据</td>');
            }else{
                var tpl = document.getElementById('list-template').innerHTML;
                laytpl(tpl).render(data, function(html){
                    document.getElementById('list-content').innerHTML = html;
                });
                laypage({
                    cont: $('#AjaxPage'),//容器。值支持id名、原生dom对象，jquery对象,
                    pages:'<?php echo $allpage; ?>',//总页数
                    count:'<?php echo $count; ?>',//总条数
                    skip: true,//是否开启跳页
                    skin: '#1AB5B7',//分页组件颜色
                    curr: curr || 1,
                    groups: 3,//连续显示分页数
                    jump: function(obj, first){
                        if(!first){
                            Ajaxpage(obj.curr)
                        }
                        $('#allpage').html('第'+ obj.curr +'页，共'+ obj.pages +'页');
                    }
                });
            }
        });
    }


    //编辑
    function edit_b(sign){
        location.href = './edit_sale?sign='+sign;
    }

    //删除
    function del_b(sign){
        layer.confirm('确认删除?', {icon: 3, title:'提示'}, function(index){
            $.getJSON('<?php echo url("del_sale"); ?>', {'sign' : sign,'type' : 2}, function(res){
                location.reload();
                if(res.code == 1){
                    layer.msg(res.msg,{icon:1,time:1500,shade: 0.1});
                }else{
                    layer.msg(res.msg,{icon:0,time:1500,shade: 0.1});
                }
            });

            layer.close(index);
        })
    }
    // 点击弹窗遮罩层选择活动列表
    function sale_list(sign){
        layer.open({
            type: 2,
            area: ['768px', '500px'],
            fixed: false, //不固定
            maxmin: true,
            content: '<?php echo url("sale_list"); ?>?sign='+sign
        });
    }
    function apply(sign){
        layer.confirm('确认发起申请?', {icon: 3, title:'提示'}, function(index){
            $.getJSON('<?php echo url("apply_special"); ?>', {'sign' : sign,'type':2}, function(res){
                if(res.code == 1){
                    layer.msg(res.msg,{icon:1,time:1500,shade: 0.1});
                }else{
                    layer.msg(res.msg,{icon:0,time:1500,shade: 0.1});
                }
                window.location.reload();
            });

            layer.close(index);
        })
    }
    // 修改审核状态
    function update_statu(id){
        layer.confirm('确认申请通过?', {icon: 3, title:'提示'}, function(index){
            $.getJSON('<?php echo url("update_statu"); ?>', {'id' : id ,'type' : 2}, function(res){
                if(res.code == 1){
                    layer.msg(res.msg,{icon:1,time:1500,shade: 0.1});

                }else{
                    layer.msg(res.msg,{icon:0,time:1500,shade: 0.1});
                }
            });
            layer.close(index);
            window.location.reload();
        })
    }
</script>
</body>
</html>