$(document).ready(function(){
	var shopnnum = -1,labelarray=[],imgarray=[];
	//标签导入
	for(var i=0;i<labellist.length;i++){
		$('#labelSearch').append('<option data-name="'+labellist[i].name+'">'+labellist[i].name+'</option>');
	}
	//类型导入
	for(var i=0;i<stylelist.length;i++){
		$('#styleSearch').append('<option value="'+stylelist[i].id+'">'+stylelist[i].name+'</option>');
		$('#styleSearch').css('display','inline-block');
	}
	//状态导入
	for(var i=0;i<typelist.length;i++){
		$('#typeSearch').append('<option value="'+typelist[i].id+'">'+typelist[i].name+'</option>');
		$('#typeSearch').css('display','inline-block');
	}
	//地图导入
	for(var i=0;i<province.length;i++){
		$('#areaSearch').append('<option value="'+province[i].area+'">'+province[i].area+'</option>');
		$('#areaSearch').css('display','inline-block');
	}
	
	
	shopnShow();
	//详细信息
	$('.shopnlist').on('click','li',function(){
		var shopnid = $(this).attr('data-shopnid');
		for(var i=0;i<citysshow.length;i++){
			if(citysshow[i].id == shopnid){
				shopnnum = i;
				$('.shopn_name').html(citysshow[i].name);
				$('.shopn_tel').html(citysshow[i].tel);
				$('.shopn_address').html(citysshow[i].address);
				$('.shopn_boss').html(citysshow[i].boss);
				$('.shopn_style').html(styleShow(citysshow[i].style));
				$('.shopn_type').html(typeShow(citysshow[i].type));
				$('.shopn_label').html('');
				$('.shopn_img').html('');
				for(var j=0;j<citysshow[i].label.length;j++){
					$('.shopn_label').append('<p>'+citysshow[i].label[j]+'</p>');
				}
				for(var j=0;j<citysshow[i].image.length;j++){
					$('.shopn_img').append('<img src="'+citysshow[i].image[j].path+'"/>');
				}
				
				labelarray = citysshow[i].label;
				imgarray = citysshow[i].image;
				$('.shopnlist').addClass('hide');
				$('.shopndetail').removeClass('hide');
			}
		}
	})
//------------------------------------------------------------修改--------------------------------------------------------------------------------
	$('#positionChange').click(function(){
		$('#shopn_name').val(citysshow[shopnnum].name);
		$('#shopn_tel').val(citysshow[shopnnum].tel);
		$('#shopn_address').val(citysshow[shopnnum].address);
		$('#shopn_boss').val(citysshow[shopnnum].boss);
		$('#styleSearch').val(citysshow[shopnnum].style);
		$('#typeSearch').val(citysshow[shopnnum].type);
		$('#areaSearch').val(citysshow[shopnnum].area);
		$('#shopn_label').html('');
		$('#shopn_img').html('');
		labelarray = citysshow[shopnnum].label;
		imgarray = citysshow[shopnnum].image;
		for(var j=0;j<citysshow[shopnnum].label.length;j++){
			$('#shopn_label').append('<div><p>'+citysshow[shopnnum].label[j]+'</p><span class="iconfont floatr lab_del">&#xe61f;</span></div>');
		}
		for(var j=0;j<citysshow[shopnnum].image.length;j++){
			$('#shopn_img').append('<div><img src="'+citysshow[shopnnum].image[j].path+'"/><p><span class="iconfont">&#xe61f;</span></p></div>');
		}
		
		
		$('.shopndetail').addClass('hide');
		$('.shopnchange').removeClass('hide');
	})
	//详细地址
	$('#shopn_address').change(function(){
		if(shopnnum == -1){shopnnum = citysshow.length-1}
		var address = $(this).val();
		placeSearch.search(address, function(status, result) {
			citysshow[shopnnum].lnglat = result.poiList.pois[0].location;
		})
	})
	
	
	
	//标签删除
	$('#shopn_label').on('click','span',function(){
		var msg = $(this).siblings('p').text();
		var num = labelarray.indexOf(msg);
		labelarray.splice(num,1);
		$(this).parent().remove();
	})
	//标签添加
	$('#labelSearch').change(function(){
		var msg = $('#labelSearch').val();
		if(labelarray.indexOf(msg) == -1){
			labelarray.push(msg);
			$('#shopn_label').append('<div><p>'+msg+'</p><span class="iconfont floatr lab_del">&#xe61f;</span></div>');
		}
	})
	
	//图片删除
	$('#shopn_img').on('click','p',function(){
		console.log(imgarray)
		var imgurl = $(this).siblings('img').attr('src');
		for(var i=0;i<imgarray.length;i++){
			if(imgarray[i].path == imgurl){
				imgarray.splice(i,1);
				$(this).parent().remove();
			}
		}
	})
	//图片添加
	$('#shopnImgBut').change(function(){
		var reads= new FileReader();
		f=document.getElementById('shopnImgBut').files[0];
		reads.readAsDataURL(f);
		reads.onload=function (e) {
			imgarray.push({
				"id":imgarray.length+1,
				"path":this.result,
			});
			$('#shopn_img').append('<div><img src="'+this.result+'"/><p><span class="iconfont">&#xe61f;</span></p></div>');
		};
	})
	
	
	
    //显示图片--------------------------------------------------------------------------------------------------
    $('.shopn_img').on('click','img',function(){
		var imgurl = $(this).attr('src');
		for(var i=0;i<imgarray.length;i++){
			if(imgarray[i].path == imgurl){
				imgarray.splice(i,1);
			}
		}
	})
	
	
	
	
	
//------------------------------------------------------------修改end--------------------------------------------------------------------------------
	//保存
	$('.shopnchange .save').click(function(){
		
		citysshow[shopnnum].name = $('#shopn_name').val();
		citysshow[shopnnum].tel = $('#shopn_tel').val();
		citysshow[shopnnum].address = $('#shopn_address').val();
		citysshow[shopnnum].boss = $('#shopn_boss').val();
		citysshow[shopnnum].style = $('#styleSearch').val();
		citysshow[shopnnum].type = $('#typeSearch').val();
		citysshow[shopnnum].area = $('#areaSearch').val();
		
		citysshow[shopnnum].label = labelarray;
		citysshow[shopnnum].image = imgarray;
	
		
		
		window.location.reload();
	})
	
	//地图定位
	$('#positionMap').click(function(){
		var lnglat = citysshow[shopnnum].lnglat;
		
		map.setCenter(lnglat);
		var infoWindow = new AMap.InfoWindow({ //创建信息窗体
	        isCustom: false,  //使用自定义窗体
	        content:'<div>门店</div><div>名称：'+citysshow[shopnnum].name+'</div><div>电话：'+citysshow[shopnnum].tel+'</div><div>地址：'+citysshow[shopnnum].address+'</div><div class="detail"><button data-shopnid="'+citysshow[shopnnum].id+'">详情</button></div>', //信息窗体的内容可以是任意html片段
	        offset: new AMap.Pixel(10, -10)
	    });
        infoWindow.open(map, lnglat);
		
		$('.showpart').addClass('hide');
		$('.shopndetail').addClass('hide');
		shopnnum = -1;
	})
	
	//详情显示
	$('#container').on('click','.detail',function(){
		var shopnid = $(this).children('button').attr('data-shopnid');
		console.log(shopnid)
		for(var i=0;i<citysshow.length;i++){
			if(citysshow[i].id == shopnid){
				shopnnum = i;
				
				$('.shopn_name').html(citysshow[i].name);
				$('.shopn_tel').html(citysshow[i].tel);
				$('.shopn_address').html(citysshow[i].address);
				$('.shopn_boss').html(citysshow[i].boss);
				$('.shopn_style').html(styleShow(citysshow[i].style));
				$('.shopn_type').html(typeShow(citysshow[i].type));
				$('.shopn_label').html('');
				$('.shopn_img').html('');
				for(var j=0;j<citysshow[i].label.length;j++){
					$('.shopn_label').append('<p>'+citysshow[i].label[j]+'</p>');
				}
				for(var j=0;j<citysshow[i].image.length;j++){
					$('.shopn_img').append('<img src="'+citysshow[i].image[j].path+'"/>');
				}
				$('.showpart').removeClass('hide');
				$('.shopndetail').removeClass('hide');
				
			}
		}
	})
	//删除
	$('.shopndetail .delect').click(function(){
		citysshow.splice(shopnnum,1);
		shopnShow();
		shopnnum = -1;
		$('.shopndetail').addClass('hide');
		$('.shopnlist').removeClass('hide');
	})
	
	function shopnShow(){
		$('.shopnlist .body').html('');
		for(var i=0;i<citysshow.length;i++){
			$('.shopnlist .body').append('<li data-shopnid="'+citysshow[i].id+'">'+citysshow[i].name+'<span class="iconfont floatr shopn_del_icon">&#xe661;</span></li>');
		}
	}
	
	function styleShow(num){
		var msg;
		if(num == 0){msg = '已合作会所型客户'}
		else if(num == 1){msg = '已合作连锁型客户'}
		else if(num == 2){msg = '已合作普通客户'}
		else if(num == 3){msg = '意向客户'}
		else if(num == 4){msg = '即将淘汰客户'}
		else if(num == 5){msg = '未合作会所型客户'}
		else if(num == 6){msg = '未合作连锁型客户'}
		else if(num == 7){msg = '未合作普通客户'}
		return msg;
	}
	function typeShow(num){
		var msg;
		if(num == 0){msg = '处理中'}
		else if(num == 1){msg = '已合作'}
		else if(num == 2){msg = '未合作'}
		return msg;
	}
	
})
