jQuery.support.cors = true;
var oto_user = getQueryString('oto_user');
$(document).ready(function(){
	var getclassify = ajaxpost('getClassify','');
	if(getclassify.code == 1){
		var classify = [],types = [];
		for(var i=0;i<getclassify.data.classfy.length;i++){
			classify.push({
		      title: getclassify.data.classfy[i].cla_name,
		      value: getclassify.data.classfy[i].cla_id,
		    })
		}
		for(var i=0;i<getclassify.data.types.length;i++){
			types.push({
		      title: getclassify.data.types[i].type_name,
		      value: getclassify.data.types[i].type_id,
		    })
		}
		

		$("#studyClassify").select({
			title: "分类",
			items: classify,
			onOpen: function () {
	            console.log("open");
	        },
		});
		$("#studyType").select({
			title: "类型",
			items: types
		});
		
		
		$('#kinerDatePickerInput1').kinerDatePicker({clickMaskHide: true});
		$('#kinerDatePickerInput2').kinerDatePicker({clickMaskHide: true});
	}
	
	
	
	function dateListen(){
		let mask = document.getElementsByClassName("weui-mask")[0];
		let picker = document.getElementsByClassName("weui-picker")[0];
		if (mask && picker) {
		    var a=document.getElementsByClassName('weui-picker__action')[0]
		    var e = document.createEvent("MouseEvents");
		    e.initEvent("click", true, true);
		    a.dispatchEvent(e);
		}
			
	}
	
	
	
	var listshow = ajaxpost('getOtoRecord',{oto_user:oto_user,type:'0'})
	if(listshow.data != ''){
		$('.coin_num span').html(listshow.data.own_data.coin_num);
		$('.ranking span').html(listshow.data.own_data.ranking);
		for(var i =0;i<listshow.data.rank_list.length;i++){
			var imgpart = '',bgcolor='';
			if(i == 0){imgpart = '<img src="img/gold.png"/>'}
			else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
			else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
			else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
			if(i%2 != 0){bgcolor = 'listbg'}
			
			$('.general_list').append('<li class="'+bgcolor+'"><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
	        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].coin_num+'</p>'+
	        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
		}
	}
	
	$(".weui-navbar__item").click(function(){
		var tapid = $(this).attr('id');
		
		if($(this).hasClass('tab-top')){}
		else{
			$(this).siblings().removeClass('tab-top');
			$(this).addClass('tab-top');
			
			if(tapid == 'listShow'){//战绩榜
				listTitleChange('金币数');
				$('.rank-listtop').children('.weui-navbar__item').removeClass('tab-top');
				$('.rank-listtop').children('.weui-navbar__item:first-child').addClass('tab-top');
				
				$('.rank').removeClass('hide');
				$('.general_list').removeClass('hide');
				$('.seven_list').addClass('hide');
				$('.silk_bag').addClass('hide');
				$('.interlocution').addClass('hide');
				$('#allList').addClass('tab-top');
				$('#sevenList').removeClass('tab-top');
				
				$('.general_list').html('');
				listshow = ajaxpost('getOtoRecord',{oto_user:oto_user,type:'0'})
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.coin_num);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						var imgpart = '',bgcolor='';
						if(i == 0){imgpart = '<img src="img/gold.png"/>'}
						else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
						else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
						else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
						if(i%2 != 0){bgcolor = 'listbg'}
						$('.general_list').append('<li class="'+bgcolor+'"><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
				        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].coin_num+'</p>'+
				        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
					}
				}
			}
			else if(tapid == 'allList'){//总 榜
				listTitleChange('金币数');
				$('.rank-listtop').children('.weui-navbar__item').removeClass('tab-top');
				$('.rank-listtop').children('.weui-navbar__item:first-child').addClass('tab-top');
				
				$('.general_list').removeClass('hide');
				$('.seven_list').addClass('hide');
				
				$('.general_list').html('');
				$('.coin_num span').html('');
				$('.ranking span').html('');
				listshow = ajaxpost('getOtoRecord',{oto_user:oto_user,type:'0'})
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.coin_num);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						var imgpart = '',bgcolor='';
						if(i == 0){imgpart = '<img src="img/gold.png"/>'}
						else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
						else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
						else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
						if(i%2 != 0){bgcolor = 'listbg'}
						$('.general_list').append('<li class="'+bgcolor+'"><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
				        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].coin_num+'</p>'+
				        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
					}
				}
			}
			else if(tapid == 'sevenList'){//周 榜（近7天）
				listTitleChange('金币数');
				$('.rank-listtop').children('.weui-navbar__item').removeClass('tab-top');
				$('.rank-listtop').children('.weui-navbar__item:first-child').addClass('tab-top');
				
				$('.general_list').addClass('hide');
				$('.seven_list').removeClass('hide');
				
				$('.seven_list').html('');
				$('.coin_num span').html('');
				$('.ranking span').html('');
				listshow = ajaxpost('getOtoRecord',{oto_user:oto_user,type:'7'})
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.coin_num);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						var imgpart = '';
						if(i == 0){imgpart = '<img src="img/gold.png"/>'}
						else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
						else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
						else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
						
						$('.seven_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
				        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].coin_num+'</p>'+
				        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
					}
				}
			}
			else if(tapid == 'moneyTap'){//总金币
				listTitleChange('金币数');
				$('.general_list').html('');
				$('.seven_list').html('');
				$('.coin_num span').html('');
				$('.ranking span').html('');
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.coin_num);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						if($('.general_list').hasClass('hide')){
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.seven_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].coin_num+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
						else{
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.general_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].coin_num+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
					}
				}
			}
			else if(tapid == 'wordTap'){//总单词
				listTitleChange('单词数');
				$('.general_list').html('');
				$('.seven_list').html('');
				$('.ranking span').html('');
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.word_num);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						if($('.general_list').hasClass('hide')){
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.seven_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].word_num+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
						else{
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.general_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].word_num+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
					}
				}
			}
			else if(tapid == 'timeTap'){//总时长
				listTitleChange('总时长');
				$('.general_list').html('');
				$('.seven_list').html('');
				$('.coin_num span').html('');
				$('.ranking span').html('');
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.online_time);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						if($('.general_list').hasClass('hide')){
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.seven_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].online_time+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
						else{
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.general_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].online_time+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
					}
				}
			}
			else if(tapid == 'clearanceTap'){//通关总数
				listTitleChange('通关数');
				$('.general_list').html('');
				$('.seven_list').html('');
				$('.coin_num span').html('');
				$('.ranking span').html('');
				if(listshow.msg == '获取成功'){
					$('.coin_num span').html(listshow.data.own_data.clearance_num);
					$('.ranking span').html(listshow.data.own_data.ranking);
					for(var i =0;i<listshow.data.rank_list.length;i++){
						if($('.general_list').hasClass('hide')){
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.seven_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].clearance_num+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
						else{
							var imgpart = '';
							if(i == 0){imgpart = '<img src="img/gold.png"/>'}
							else if(i == 1){imgpart = '<img src="img/silver.png"/>'}
							else if(i == 2){imgpart = '<img src="img/copper.png"/>'}
							else{imgpart = '<img src="img/copper.png" style="opacity:0;"/>'}
							
							$('.general_list').append('<li><div class="rank-img"><p>'+(i+1)+'</p>'+imgpart+'<div><span>'+listshow.data.rank_list[i].user_name.substr(0,1)+'</span></div></div>'+
					        			'<p class="username">'+listshow.data.rank_list[i].user_name+'</p><p>'+listshow.data.rank_list[i].clearance_num+'</p>'+
					        			'<p class="rank-time">'+dataChange(listshow.data.rank_list[i].first_login_time)+'</p><p class="rank-time">'+dataChange(listshow.data.rank_list[i].last_login_time)+'</p></li>');
						}
					}
				}
			}
			else if(tapid == 'silkBag'){//锦 囊
				$('.rank').addClass('hide');
				$('.silk_bag').removeClass('hide');
				$('.interlocution').addClass('hide');
				$('.silkbag_body').html('');
			}
			else if(tapid == 'interlocution'){//Q&A
				$('.rank').addClass('hide');
				$('.silk_bag').addClass('hide');
				$('.interlocution').removeClass('hide');
				$('.inter_body ul').html('');
			}
		}
		console.log(tapid)
	})
	//锦囊
	$('#silkSelect').click(function(){
		$('.silkbag_body').html('');
		var cla_id = $('#studyClassify').attr('data-values');
		var type_id = $('#studyType').attr('data-values');
		var begin_time = $('#kinerDatePickerInput1').val();
		var end_time = $('#kinerDatePickerInput2').val();
		var silkBag = ajaxpost('getSilkBag',{cla_id:cla_id,type_id:type_id,oto_user:oto_user,begin_time:begin_time,end_time:end_time})
		if(silkBag.msg == '获取成功'){
			for(var i=0;i<silkBag.data.length;i++){
				$('.silkbag_body').append('<div class="weui-grid js_grid"><p class="weui-grid__label">'+silkBag.data[i].word+'</p><p class="weui-grid__label">'+silkBag.data[i].word_ch+'</p></div>')
			}
		}
	})
	//Q&A
	$('#searchInput').change(function(){
		$('.inter_body ul').html('');
		var search = $(this).val();
		console.log(search)
		var inter = ajaxpost('getQaList',{key:search})
		if(inter.msg == '获取成功'){
			for(var i=0;i<inter.data.length;i++){
				$('.inter_body ul').append('<li><p><span>Q: </span>'+inter.data[i].question+'</p><p><span>A: </span>'+inter.data[i].answer+'</p></li>')
			}
		}
	})
	$('#interlocution').click(function(){
		$('.inter_body ul').html('');
		var search = $(this).val();
		console.log(search)
		var inter = ajaxpost('getQaList',{key:search})
		if(inter.msg == '获取成功'){
			for(var i=0;i<inter.data.length;i++){
				$('.inter_body ul').append('<li><p><span>Q: </span>'+inter.data[i].question+'</p><p><span>A: </span>'+inter.data[i].answer+'</p></li>')
			}
		}
	})
	
	
	function listTitleChange(msg){
		$('.listnum_title').html(msg);
		$('.coin_num').html('我家学霸'+msg+':<span></span>');
	}
})
