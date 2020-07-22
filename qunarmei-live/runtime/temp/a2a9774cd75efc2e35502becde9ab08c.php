<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:99:"D:\software\phpstudy_pro\WWW\qunarmei-live\public/../application/admin\view\salesplan\add_sale.html";i:1592007080;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\header.html";i:1587693029;s:84:"D:\software\phpstudy_pro\WWW\qunarmei-live\application\admin\view\public\footer.html";i:1574039645;}*/ ?>
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
<!--<link rel="stylesheet" type="text/css" media="all" href="/sldate/daterangepicker-bs3.css" />-->
<script type="text/javascript" src="/sldate/moment.js"></script>
<!-- <link rel="stylesheet" href="/static/admin/js/layui/layui/css/layui.css">
<script type="text/javascript" src="./static/admin/js/layui/layui/layui.all.js"></script> -->
<!--<script type="text/javascript" src="/sldate/daterangepicker.js"></script>-->
<style>
    .file-item{float: left; position: relative; width: 110px;height: 110px; margin: 0 20px 20px 0; padding: 4px;}
    .file-item .info{overflow: hidden;}
    .uploader-list{width: 100%; overflow: hidden;}


</style>
<body class="gray-bg">
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>添加直播商品方案</h5>
                </div>
                <div class="ibox-content">
                    <form class="form-horizontal m-t" name="add" id="add" method="post" action="<?php echo url('add_sale'); ?>">
                        <div class="form-group layui-form-item">
                            <label class="col-sm-3 control-label">促销活动名称</label>
                            <input type="text" name="rules_name" value="单品促销">(建议填写:<font color="red">夜间3件套促销,形体促销,代餐促销,单品促销</font>)
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">方案类型</label>
                            <div class="input-group col-sm-4">
                                <input type="radio" name='activity_type' value="1" id="ms"/>买送&nbsp;
                                <input type="radio" name='activity_type' value="0" id="mj" checked/>满减&nbsp;
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">产品组合</label>
                            <div class="input-group col-sm-4">
                                <input type="radio" name='goods_type' value="1" />跨产品&nbsp;
                                <input type="radio" name='goods_type' value="0" checked/>拆分单品&nbsp;
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <label class="col-sm-3 control-label">商品选择：</label>
                        <div class="input-group col-sm-8">
                            <!--<input id="store_signs" type="text" class="form-control" name="store_signs" placeholder="">-->
                            <ul id="goods_id1">
                                <?php foreach($live_goods as $k => $v): ?>
                                <li style="float: left;list-style-type: none;width:300px;">
                                    <input type="checkbox" name="goods_id1[]" title="<?php echo $v['id']; ?>" lay-skin="primary" value="<?php echo $v['id']; ?>"><?php echo $v['title']; ?>-<?php echo $v['id']; ?>&nbsp;&nbsp;
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <ul id="goods_id2" style="display: none">
                                <?php foreach($live_goods2 as $k => $v): ?>
                                <li style="float: left;list-style-type: none;width:300px;">
                                    <input type="checkbox" name="goods_id2[]" title="<?php echo $v['id']; ?>" lay-skin="primary" value="<?php echo $v['id']; ?>"><?php echo $v['title']; ?>-<?php echo $v['id']; ?>&nbsp;&nbsp;
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <!--<div class="input-group" style="margin-top: -5px;">-->
                            <!--<select class="form-control m-b chosen-select" name="goods_id[]" id="goods_id1" style="width: 250px" lay-search="" multiple="multiple">-->
                                <!--<option value=""></option>-->
                                <!--<?php if(!empty($live_goods)): ?>-->
                                <!--<?php if(is_array($live_goods) || $live_goods instanceof \think\Collection || $live_goods instanceof \think\Paginator): if( count($live_goods)==0 ) : echo "" ;else: foreach($live_goods as $key=>$v): ?>-->
                                <!--<option value="<?php echo $v['id']; ?>"><?php echo $v['title']; ?>-<?php echo $v['id']; ?></option>-->
                                <!--<?php endforeach; endif; else: echo "" ;endif; ?>-->
                                <!--<?php endif; ?>-->
                            <!--</select>-->
                            <!--<select class="form-control m-b chosen-select" name="goods_id[]" id="goods_id2" style="width: 250px;display: none" lay-search="">-->
                                <!--<option value=""></option>-->
                                <!--<?php if(!empty($live_goods2)): ?>-->
                                <!--<?php if(is_array($live_goods2) || $live_goods2 instanceof \think\Collection || $live_goods2 instanceof \think\Paginator): if( count($live_goods2)==0 ) : echo "" ;else: foreach($live_goods2 as $key=>$v): ?>-->
                                <!--<option value="<?php echo $v['id']; ?>"><?php echo $v['title']; ?>-<?php echo $v['id']; ?></option>-->
                                <!--<?php endforeach; endif; else: echo "" ;endif; ?>-->
                                <!--<?php endif; ?>-->
                            <!--</select>-->
                        <!--</div>-->
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">实施方案</label>
                            <div class="input-group col-sm-4">
                                <span id="man">满</span>&nbsp;<input id="goods_num" type="text"  name="goods_num" placeholder="请输入" style="with: 100px;">&nbsp;个<br/>
                                <span id="jian">减</span>&nbsp;<input id="reduction_num" type="text" name="reduction_num" placeholder="请输入" style="with: 100px;" onblur="numDx()">&nbsp;个<br/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">赠送优惠券</label>
                            <div class="input-group col-sm-4">
                                <input type="checkbox" name='card_id[]' value="24" />消费券&nbsp;
                                <input type="checkbox" name='card_id[]' value="25" />护理券&nbsp;
                                <input type="checkbox" name='card_id[]' value="26" />门店定制礼券&nbsp;
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group layui-form-item">
                            <label class="col-sm-3 control-label">选择执行门店</label>
                            <input type="text"  id="serach_store" placeholder="请输入完整门店编号,如:666-666-1" style="width:200px;"><button value="搜索" onclick="serach_bwk()" type='button'>搜索</button>
                            <!-- <input type="text" name="signs66" value=""> -->
                            <div class="input-group col-sm-8">
                            <ul id="store_ids">
                                <?php foreach($branch as $k => $v): ?>
                                <li style="float: left;list-style-type: none;width:20%;">
                                    <input type="checkbox" name="signs77[]" value="<?php echo $v['sign']; ?>" id="<?php echo $v['sign']; ?>"><span id = "<?php echo $v['sign']; ?>666"><?php echo $v['title']; ?>-<?php echo $v['sign']; ?></span>&nbsp;&nbsp;
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            </div>
                        </div>
                        <!--<div class="form-group">-->
                            <!--<label class="col-sm-3 control-label" id="man_num">满多少个</label>-->
                            <!--<div class="input-group col-sm-4">-->
                                <!--<input id="goods_num" type="text" class="form-control" name="goods_num" placeholder="">-->
                            <!--</div>-->
                        <!--</div>-->
                        <!--<div class="hr-line-dashed"></div>-->
                        <!--<div class="form-group">-->
                            <!--<label class="col-sm-3 control-label" id="youhui_num">优惠多少个</label>-->
                            <!--<div class="input-group col-sm-4">-->
                                <!--<input id="reduction_num" type="text" class="form-control" name="reduction_num" placeholder="" onblur="numDx()">-->
                            <!--</div>-->
                        <!--</div>-->
                        <!--<div class="form-group">-->
                            <!--<label class="col-sm-3 control-label">备注说明</label>-->
                            <!--<div class="input-group col-sm-4">-->
                                <!--<input id="remark" type="text" class="form-control" name="remark" placeholder="">-->
                            <!--</div>-->
                        <!--</div>-->
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

<script type="text/javascript">

    var ms = document.getElementById('ms'); // 分别拿到对版应权的元素
    var mj = document.getElementById('mj');
    ms.onclick = function() {
//        alert('ms');
        $("#man").html('买');
        $("#jian").html('送');
        $("#goods_id1").css("display",'none');
        $("#goods_id2").css("display",'block');

    }
    mj.onclick = function() {
//        alert('mj');
        $("#man").html('满');
        $("#jian").html('减');
        $("#goods_id1").css("display",'block');
        $("#goods_id2").css("display",'none');
    }
    function numDx(){
        var n1 = $("#goods_num").val();
        var n2 = $("#reduction_num").val();
        var type = $("input[name='activity_type']:checked").val();
        // 买送
        if(type == 1){
            if(n1 < n2){
                layer.msg('送个数不能大于买个数', {icon: 5,time:1500,shade: 0.1}, function(index){
                    layer.close(index);
                });
            }
        }else{
            // 满减
            if(n1 <= n2){
                layer.msg('减个数不能大于买个数', {icon: 5,time:1500,shade: 0.1}, function(index){
                    layer.close(index);
                });
            }
        }

        return false;
    }

    $(function(){

        $('#add').ajaxForm({
            beforeSubmit: checkForm, // 此方法主要是提交前执行的方法，根据需要设置
            success: complete, // 这是提交后的方法
            dataType: 'json'
        });

        function checkForm(){
            var n1 = $("#goods_num").val();
            var n2 = $("#reduction_num").val();
            var type = $("input[name='activity_type']:checked").val();
            // 买送
            if(type == 1){
                if(n1 < n2){
                    layer.msg('送个数不能大于买个数', {icon: 5,time:1500,shade: 0.1}, function(index){
                        layer.close(index);
                        return false;
                    });
                }
            }else{
                // 满减
                if(n1 <= n2){
                    layer.msg('减个数不能大于买个数', {icon: 5,time:1500,shade: 0.1}, function(index){
                        layer.close(index);
                        return false;
                    });
                }
            }
        }

        function complete(data){
            if(data.code == 1){
                layer.msg(data.msg, {icon: 6,time:1500,shade: 0.1}, function(index){
                    layer.close(index);
                    window.location.href="<?php echo url('salelist'); ?>";
                });
            }else{
                layer.msg(data.msg, {icon: 5,time:1500,shade: 0.1}, function(index){
                    layer.close(index);
                });
                return false;
            }
        }

    });

    // 查询搜索门店
    function serach_bwk() {
        // 获取输入数据
        var data11 = $("#serach_store").val();
        console.log('#'+data11);
        window.location.hash = "#"+data11;
        $("#"+data11+"666").css("color","red");
    }
</script>
</body>
</html>
