$(document).ready(function(){
	var tradinglength = tradingarea.length;
	$('.provshow .prov_name').html(provinceshow.area);
	$('.provshow .prov_detail').html(provinceshow.content);
	
	$('#provName').val(provinceshow.area);
	$('#provDel').val(provinceshow.content);
	$('#provCenter').val(provinceshow.center);
	$('#provLnglat').val(provinceshow.lnglat.lng+','+provinceshow.lnglat.lat);
	
	for(var i=0;i<tradingarea.length;i++){
		$('#tradingAdd').before(
		'<div data-tradid="'+tradingarea[i].tradid+'"><input placeholder="请输入商圈名称" value="'+tradingarea[i].name+'" class="tradName"/>'+
		'<input placeholder="请输入商圈中心" value="'+tradingarea[i].center+'" class="tradCenter"/>'+
		'<input placeholder="请输入商圈半径" value="'+tradingarea[i].radius+'" class="tradRadius"/>'+
		'<button class="tradDelect">删除</button></div>')
	}
	
	
	
	$('.provshow .edit').click(function(){
		$('.provshow').addClass('hide');
		$('.provchange').removeClass('hide');
	})
	//添加
	$('#tradingAdd').click(function(){
		$('#tradingAdd').before(
		'<div><input placeholder="请输入商圈名称" value="" class="tradName"/>'+
		'<input placeholder="请输入商圈中心" value="" class="tradCenter"/>'+
		'<input placeholder="请输入商圈半径" value="" class="tradRadius"/>'+
		'<button class="tradDelect">删除</button></div>');
		tradingarea.push({
				"tradid": tradinglength+1,
				"name":"",
				"center": "",
				"radius": "",
				"lnglat": {
					"Q": '',
					"R": '',
					"lng": '',
					"lat": ''
				},
			})
		tradinglength = tradinglength+1;
	})
	//删除
	$('#tradingList').on('click','.tradDelect',function(){
		var tradid = $(this).parent('div').attr('data-tradid'),num;
		for(var i=0;i<tradingarea.length;i++){if(tradingarea[i].tradid == tradid){num = i;}}
		tradingarea.splice(num,1);
		$(this).parent('div').remove();
	})
	
	
	//门店中心
	$('#provCenter').change(function(){
		var address = $(this).val();
		placeSearch.search(address, function(status, result) {
			console.log(result.poiList.pois[0]);
			provinceshow.lnglat = result.poiList.pois[0].location;
			$('#provLnglat').val(result.poiList.pois[0].location.lng+','+result.poiList.pois[0].location.lat)
		})
	})
	
	//核心商圈-名称
	$('#tradingList').on('change','.tradName',function(){
		var name = $(this).val(),tradid = $(this).parent('div').attr('data-tradid');
		for(var i=0;i<tradingarea.length;i++){if(tradingarea[i].tradid == tradid){tradingarea[i].name = name;}}
	})
	//核心商圈-中心点
	$('#tradingList').on('change','.tradCenter',function(){
		var address = $(this).val(),tradid = $(this).parent('div').attr('data-tradid');
		placeSearch.search(address, function(status, result) {
			for(var i=0;i<tradingarea.length;i++){
				if(tradingarea[i].tradid == tradid){
					tradingarea[i].lnglat = result.poiList.pois[0].location;
					tradingarea[i].center = address;
				}
			}
		})
	})
	//核心商圈-名称
	$('#tradingList').on('change','.tradRadius',function(){
		var radius = $(this).val(),tradid = $(this).parent('div').attr('data-tradid');
		for(var i=0;i<tradingarea.length;i++){if(tradingarea[i].tradid == tradid){tradingarea[i].radius = radius;}}
	})
	
	//保存
	$('.provchange .save').click(function(){
		var area = $('#provName').val();
		var content = $('#provDel').val();
		var center = $('#provCenter').val();
		provinceshow.area = area;
		provinceshow.content = content;
		provinceshow.center = center;
		
		$('.provshow .prov_name').html(provinceshow.area);
		$('.provshow .prov_detail').html(provinceshow.content);
		$('.provchange').addClass('hide');
		$('.provshow').removeClass('hide');
	})
})
