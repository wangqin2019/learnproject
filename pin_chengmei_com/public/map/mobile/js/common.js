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
	    contentType: "application/json;charset=UTF-8",
	    success: function (data) {
	    	res = data;
	    },
	    crossDomain: true,
	    contentType: "application/json;charset=UTF-8",
	});
	return res;
}
//--------------------------------------------------------------------测距工具------------------------------------------------------------------------------------
//自定义样式-开始
var startMarkerOptions= {
    icon: new AMap.Icon({
        size: new AMap.Size(19, 31),//图标大小
        imageSize:new AMap.Size(19, 31),
        image: "https://webapi.amap.com/theme/v1.3/markers/b/start.png"
    })
};
//自定义样式-结束
var endMarkerOptions = {
    icon: new AMap.Icon({
        size: new AMap.Size(19, 31),//图标大小
        imageSize:new AMap.Size(19, 31),
        image: "https://webapi.amap.com/theme/v1.3/markers/b/end.png"
    }),
    offset: new AMap.Pixel(-9, -31)
};
//自定义样式-经过点
var midMarkerOptions = {
    icon: new AMap.Icon({
        size: new AMap.Size(19, 31),//图标大小
        imageSize:new AMap.Size(19, 31),
        image: "https://webapi.amap.com/theme/v1.3/markers/b/mid.png"
    }),
    offset: new AMap.Pixel(-9, -31)
};
//线绘制
var lineOptions = {
    strokeStyle: "solid",
    strokeColor: "#FF33FF",
    strokeOpacity: 1,
    strokeWeight: 2
};
var rulerOptions = {
    startMarkerOptions: startMarkerOptions,
    midMarkerOptions:midMarkerOptions,
    endMarkerOptions: endMarkerOptions,
    lineOptions: lineOptions
};

//--------------------------------------------------------------------门店状态与门店类型------------------------------------------------------------------------------------
//function stateShow(msg){
//	var show='';
//	if(msg==0){show = '处理中'}
//	else if(msg==1){show = '已合作'}
//	else if(msg==2){show = '未合作'}
//	return show;
//}
//function styleShow(msg){
//	var show='';
//	if(msg==0){show = '已合作会所型客户'}
//	else if(msg==1){show = '已合作连锁型客户'}
//	else if(msg==2){show = '已合作普通客户'}
//	else if(msg==3){show = '意向客户'}
//	else if(msg==4){show = '即将淘汰客户'}
//	else if(msg==5){show = '未合作会所型客户'}
//	else if(msg==6){show = '未合作连锁型客户'}
//	else if(msg==7){show = '未合作普通客户'}
//	return show;
//}
$(document).ready(function(){
	$('.return .floatl').click(function(){
		console.log('parent_class')
		var parent_class = $(this).parent().parent();
		if(parent_class.hasClass('provshow') || parent_class.hasClass('shopnlist')){
			$('.showpart').addClass('hide');
			$('.provshow').addClass('hide');
			$('.shopnlist').addClass('hide');
		}
		else if(parent_class.hasClass('provchange')){
			$('.provchange').addClass('hide');
			$('.provshow').removeClass('hide');
		}
		else if(parent_class.hasClass('shopndetail')){
			$('.shopndetail').addClass('hide');
			$('.shopnlist').removeClass('hide');
		}
		else if(parent_class.hasClass('shopnchange')){
			if(addNewShopn == true){
				$('.shopnchange').addClass('hide');
				$('.showpart').addClass('hide');
			}
			else{
				$('.shopndetail').removeClass('hide');
				$('.shopnchange').addClass('hide');
			}
		}
//		else{
//			$('.provchange').addClass('hide');
//		}
	})
	
})
