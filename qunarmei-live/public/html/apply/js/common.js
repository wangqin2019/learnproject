// var url = 'http://testc.qunarmei.com:9091/api/html/';
var url = 'http://live.qunarmei.com/api/html/';
//日期计算
function Appendzero(obj){
    if(obj<10) return "0" +""+ obj;
    else return obj;
}
//手机校验
function checkPhone(phone){
    var pattern = /^1[34578]\d{9}$/;
	if (pattern.test(phone)) {
		return true;
	}
	return false;
}
//获取地址
function getQueryString(name) {
	var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
	var r = window.location.search.substr(1).match(reg);
	if(r != null) {
		return unescape(r[2]);
	}
	return null;
}
//ajax异步
function ajaxpost(path,data){
	var res;
	$.ajax({
		type: 'post',
	    url: url + path,
	    dataType: 'json',
	    data: data,
	    crossDomain: true,
	    async: false,
	    cache: false,
	    success: function (data) {
	    	res = data;
	    },
	});
	return res;
}
//ajax上传图片
function ajaximg(path,data){
	var res;
	$.ajax({
		type: 'post',
	    url: url + path,
	    data: data,
        contentType: false,
        processData: false,
        crossDomain: true,
	    async: false,
	    cache: false,
	    success: function (data) {
	    	res = data;
	    },
	});
	return res;
}

//iPhone后退键刷新
//$(function() {
//  pushHistory();
//});
function tishi(msg){
	$('.tishi span').html(msg);
	
	$('.tishi').removeClass('hide');
	var t=setTimeout(function(){
		$('.tishi').addClass('hide');
	},1000)
}