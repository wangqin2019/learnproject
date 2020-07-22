var url = 'http://live.qunarmei.com/api/v4/oto_education/';
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
	    	if(data.code == 400){
//	    		history.go(-1);
	    	}
	    	console.log(data)
	    	res = data;
	    },
	    error: function (data) {
	    	console.log(data);
//	    	history.go(-1);
	    },

	});
	return res;
}
//获取地址
function getQueryString(name) {
  var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
  var r = window.location.search.substr(1).match(reg);
  if (r != null) {
    return unescape(r[2]);
  }
  return null;
}
//提示
function tishi(msg,time){
	$('.tishi').html(msg);
	$('.tishi').removeClass('hide');
	var t = setTimeout(function(){
		$('.tishi').addClass('hide');
	},time)
}
//日期替换
function dataChange(arr){
	var msg = arr.replace(/\-/g,".")
	return msg;
}
//Androidapp监听方法
function callAndroid() {test.hello("js调用了android中的hello方法");}
//$(document).ready(function(){
//	$(".weui-navbar__item").click(function(){
//		$(this).siblings().removeClass('tab-top');
//		$(this).addClass('tab-top');
//	})
//
//})
