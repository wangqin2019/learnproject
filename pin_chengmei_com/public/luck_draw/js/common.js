var url = 'https://pin.qunarmei.com/api/h5/';
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
	    	if(data.code == 400){}
	    	console.log(data)
	    	res = data;
	    },
	    error: function (data) {
	    	console.log(data);
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
//Androidapp监听方法
//function callAndroid() {
//	togoodscatelog.shareApp("www.baidu.com","testmsg");
//}

//function callAndroid() {
//	var ua = window.navigator.userAgent.toLowerCase(),id=156165;
//	if (ua.indexOf('micromessenger') == -1) {//不在微信或者小程序中
//		try{
//			togoodscatelog.shareApp("https://www.baidu.com","testmsg");			
//		}
//		catch(e){}
//	}
//	else{
//		wx.miniProgram.getEnv(function(res){
//			if (res.miniprogram) {//在小程序中
//				wx.miniProgram.navigateTo({url: '../missshopConPic/missshopConPic?id='+id});
//	        } 
//	        else {//在微信中
//	        	alert('请在小程序中打开页面');
//	        }
//		})
//	}
//}