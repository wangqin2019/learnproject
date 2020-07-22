<?php
//配置文件
return [

	'text' => [
		'zd_liveid'=>[602],
		'signs' => [
			'000-000',
			'666-666',
			'888-888'
		],
		// 公告配置
		'notice' => [
			'agreement' => [
				[
					'name' => '<<账户使用协议>>',
					'url' => 'http://testc.qunarmei.com:9091/html/privacy/index.html?navid=1',
				],
				[
					'name' => '<<隐私政策>>',
					'url' => 'http://testc.qunarmei.com:9091/html/privacy/index.html?navid=2',
				],
				[
					'name' => '<<用户服务协议>>',
					'url' => 'http://testc.qunarmei.com:9091/html/privacy/index.html?navid=3',
				]
			],
			'ver' => 1,
			'title1' => '隐私声明',
			'title' => '声明与政策',
			'content1' => '感谢您信任并使用"去哪美"!\n我们非常注重您的个人信息和隐私保护,并依据最新的法律要求更新了<<隐私政策>>,特向您推送本提示。请您仔细阅读并充分理解相关条款,我们将严格按照您同意的条款使用您的个人信息,以便为您提供更好的服务。',
			'content2' => '如果您同意以上的政策及声明,请点击"同意"并开始使用我们的产品和服务。我们将尽全力保护您的个人信息及合法权益,再次感谢您的信任!',
			'content3' => '感谢您信任并使用"去哪美"!<br/><br/>我们非常注重您的个人信息和隐私保护,并依据最新的法律要求更新了<<隐私政策>>,特向您推送本提示。请您仔细阅读并充分理解相关条款,我们将严格按照您同意的条款使用您的个人信息,以便为您提供更好的服务。<br/><br/><font color=\'gray\'>如果您同意以上的政策及声明,请点击"同意"并开始使用我们的产品和服务。我们将尽全力保护您的个人信息及合法权益,再次感谢您的信任!</font>',
			'content4' => '感谢您信任并使用去哪美！\n\n您可使用本应用，进行商品浏览、支付购买等功能。我们将严格遵守相关法律法规和隐私政策以保护您的个人信息。请您阅读并同意<<账户使用协议>>、<<用户服务协议>>和<<隐私政策>>。',
			'content5' => '感谢您信任并使用去哪美！<br/><br/>您可使用本应用，进行商品浏览、支付购买等功能。我们将严格遵守相关法律法规和隐私政策以保护您的个人信息。请您阅读并同意<<账户使用协议>>、<<用户服务协议>>和<<隐私政策>>。',
		],
		// 能观看直播的门店编号白名单
		'store_live' => [
			'666-666',
			'121-091',
			'121-099',
			'121-010',
			'121-074',
			'121-056',
			'121-045',
			'121-109',
			'121-079',
			'121-036',
			'121-110',
			'121-101'
		],
		// 不可用积分提示语
		'no_avaliable_score_tips' => '您有顾客订单尚未完成核销,所以部分积分暂不可用,请在顾客到店时在相关交易平台上完成订单核销过程,即可恢复积分,谢谢!',
		// 手机直播白名单
		'mobile_live' => [
			'mobiles' => ["15921324164","18602716559"],// 开启直播用户名单
			'store_ids' => [2],// 指定门店观看
			'mobiles_see' => [],// 观看用户名单
		],
		// 'oto_price' => 168,// OTO商品价格
		'oto_price' => 0.01,// OTO商品价格
		'page_size' => '30',// 每页30条
		'item_cate_id' => 66,// 门店服务分类id
		'serviceitem_flag' => 0,// 服务项目是否开启 , 0关闭(只对技术门店2开放) 1开启(针对所有门店开放)
		'code_service_tips' => '* 到店店员扫码,开始服务',// 服务码提示语
		// 去哪美分期支付
		'qnm_fq_partnerid' => '10000008988',
		'qnm_fq_returnurl' => 'https://api-app.qunarmei.com/qunamei/payReturn',// 商户回调地址
		'org_code' => ['1'=>'icbc','5'=>'cmb','7'=>'cmbnet','9'=>'union','6'=>'weixin','8'=>'alia'] ,//根sys_bank表对应 cmbnet,cmb,icbc,union 支付通道中的支付方式
		'qnm_call_url'=>'http://live.qunarmei.com/api/v4/call_back/qnmPayReturn',// 支付回调地址
		'qnm_md5_key' => '30819f300d06092a864886f70d010101050003818d0030818902818100bab45d6b2c8a5a3e76bef24051e9f9c116187842d4d48950509e5d8be450002277eed683da6d4993e9e3f681e0e15e1705483285811e69d02a30b35fac0a71a72828a9ac1ed43f420ab8c232d582f43d0510627967d4298d51a70089fa58e54faf5a5aab53aa9bafb0538da7bb082812211f6c8178cc2767024022cdceb7a98d0203010001',// 支付秘钥,生成签名
		// 去哪美微信支付相关参数
		'qnm_wx_appid' => 'wxa9d06b0c8c7c0689',// 去哪美微信支付app_id
		'qnm_mch_id'=>'1362738002',//去哪美微信商户号
		'qnm_wx_key'=>'viQxfX2Uo1QITrg2q9jM4pXOKCUIsEKh',// 微信key
		'qnm_sign_type'=>'MD5',//微信签名方式
		'qnm_wx_ip'=>'139.196.210.29',//
		'qnm_wx_pre_pay_url'=>'https://api.mch.weixin.qq.com/pay/unifiedorder',// 生成预支付订单
		'qnm_wxcall_url'=>'http://live.qunarmei.com/api/v4/call_back/wxPayReturn',// 支付回调地址
		// 去哪美支付宝相关参数
		'qnm_alipay_privatekey'=>'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAMtIFPOyoQZO/oeh SUL3OYlrGUQilIFuIB90Yv+p2XFwVyWpwbzj7/fQPwvlGZbKICAscBK8x/iDQW5V R2DzeqoVMjWZyrdfGndENb6PrniPIGx5wq8UxTA/3P7uP4S1gZ9oQl5t8Tp+rLcG AQ8AKXrpmd/NNwWaRdrOy7CRserHAgMBAAECgYEAuOjiliKgsrpccUdW+uEjp0qS exXxfCKOec5G10TLFJMZ0pquLoIwoHz/PHSzaCSIQHvrnj+2runGNPTBrwELS9dG juvBTEdFbbanJSGddRmjqWEghQCkcufTn+RQ6WxG1fQhT+7g8otNzrfDg5OcHqEi hbhdX44XcmSwzg2tLkECQQD1csZSLrLULQLQR9THNQo+sd30ffYUpq7PCcy5O7qX lYHdt6SG40R5OiJYJ8wsABCe9NlTzjN5i+duzl8JLyJRAkEA1AVAHlK7L2RD4lCU b1+yScOLhRAQwyJLjgPrniTs53YDQ7k7rN8lqT0veRi5lj96bsfL+2+bK/keHqja 6gmdlwJBAJtNOvTm/VnI/R3CRXyiL9BJhLHdPWYFrVfs0G9nvoGJJLmgJ+b9R+cY hICY9dPlWp7pN9WSA/nlLtNqmrFZ9HECQFPvdC/M/s/HONyqm+jvkKyFHoHiF1C5 DgI96RRld+g2Hxq7hTVt7gHu+BFPyYECxlx++nEjAOJKsDLhFDFc9ycCQQDueu0s TP2xIaAfDxoaQuc2ALWB9pZ2edALBs+taAyPYi0COZiOdk1F1NnD1sMVioZ0b12o yVIJ001K1yildy5T',
		'qnm_alipay_publickey'=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB',
		'qnm_alipay_timeout'=>'30m',
		'qnm_aliapy_partner'=> '2088421360665476',
		'qnm_alipay_sellerid'=>'it@qunarmei.com',
		'qnm_alipay_check'=>'https://mapi.alipay.com/gateway.do',
		'qnm_aliacall_url'=>'http://live.qunarmei.com/api/v4/call_back/aliaPayReturn',// 支付回调地址
	],
	'img' => [
		'nopay_img' => 'http://appc.qunarmei.com/study_nopay0416.jpg',// OTO未支付卡券背景图
		'pay_img' => 'http://appc.qunarmei.com/study_nopay0415.png',// OTO已支付卡券背景图
		'item_cate_img' => 'http://appc.qunarmei.com/seritem0513.jpg',// 门店服务分类封面图
		'head_img' => 'http://appc.qunarmei.com/normal_photo.png',// 默认用户头像
	],
	'url' => [
		'sms_url' => 'http://sms.qunarmei.com/sms.php',// 发送短信url
		'oto_down_url' => 'https://www.qunarmei.com/otodown.html',// otoPC端下载地址
		'oto_link_url' => 'http://live.qunarmei.com/html/learn_plansc/index.html',//oto进入url
	],

	'default_return_type'	=> 'json',
  // 'app_debug' => true,
  // 'app_trace' => true
  // 默认时区
  'default_timezone'       => 'PRC',
  'limits' => 20 ,// 分页时每页条数
	'head_img' => 'http://appc.qunarmei.com/20180815144316_4601.jpg',// 默认没有头像用户给与默认头像显示
	// 'share_content_url' => 'http://testc.qunarmei.com:9091/index/share/shareArticle', // 测试服务器-发现模块-文章分享url
	'share_content_url' => 'http://live.qunarmei.com/index/share/shareArticle', // 正式服务器-发现模块-文章分享url
	// 内衣模块配置
	'images' => [
		'qrcode_sl' => 'http://appc.qunarmei.com/qrcode26.png',
		// 'img_path' => 'D:\software\phpstudy\PHPTutorial\WWW\lunhui_tp5\public\static\api\images',//图片所在全路径
		// 'img_path' => '/home/canmay/www/test.qunarmeic.com/public/static/api/images',//测试服务器-图片所在全路径
		'img_path' => '/home/canmay/www/live/public/static/api/images',//正式服务器-图片所在全路径
	],
	'data_color' => [
		'red' => '#363641', // 偏高颜色
		'green' => '#B6B6B6', // 正常颜色
		'yellow' => '#363641',// 偏低颜色
		'rise' => '#363641',// 上升颜色
		'fall' => '#363641'// 下降颜色
	],
	// 内衣模块配置
	'tips' => [
		'red' => '本次测量共有x处异常,相信你可以更好!', // 偏高颜色
		'green' => '本次测量暂无异常,很棒哦!', // 正常提示语
		'yellow' => '本次测量共有x处异常,继续努力!',// 偏低颜色
	],
	// 对比提示语
	'compare_tips' => [
		'rise' => '比上个月进步x分,离完美身材又进了一步~!',
		'fall' => '比上个月下降x分,继续努力!'
	],
	// 带参数据提示语
	'sub_tips' => [
		'daican' => [
			'1' => '偏低',
			'2' => '正常',
			'3' => '偏高',
		],
		'normal' => [
			'形体保持的不错哦，请继续加油!',
			'健康指数良好，请继续努力哦！',
			'再努力一点点，一次成就S曲线！',
			'轻松享瘦，逆生长，保持的不错哦!'
		],
		'two_normal' => [
			'拥有健康好身材，你会拥有全世界!',
			'好体质，好身材，您值得拥有!',
		],
		'more_high' => [
			'患慢性病的概率增加，请联系形体专家帮你制定塑形方案!',
			'请减少高脂肪、高糖分食物的摄入哦!',
			'愿你永不与身上的赘肉妥协，加油哦!',
			'一起运动，快来燃烧你的卡路里吧!',
			'好身材，三分运动，七分饮食，十分情绪!'
		],
		'low_normal' => [
			'健康指数偏低，可联系形体专家了解详情哦!'
		],
		'more_low' => [
			'情况不太乐观，要注意饮食均衡哦!',
			'吃出来的美丽和健康，你值得拥有!',
			'健康指数偏低，可联系形体专家了解详情哦!',
		],
		'two_low' => [
			'健康指数偏低，可联系形体专家了解详情哦!',
			'好身材，三分运动，七分饮食，十分情绪!',
			'健康指数不乐观，离健康女神只差一步之遥哦!',
		],
		// 代餐自测结果提示语
		'suc_tip' => '男性WHR大于0.9，女性WHR大于0.8，为中心性肥胖，影响形体美观和健康，更容易患三高、心血管疾病和经期不调等',
		// 腰臀比提示语
		'rate_waist_hip_tips' => [
			'fp'=>'你属于中心性肥胖',
			'ffp'=>'你不属于中心性肥胖',
		],
		// 二维码提示语
		'code_tips' => '用去哪美扫一扫,记录测量数据',
	],
	// 标准体重
	'weight_standard' => [
		'woman' => [
			'150'=>'48',
			'151'=>'48.6',
			'152'=>'49.2',
			'153'=>'49.8',
			'154'=>'50.4',
			'155'=>'51',
			'156'=>'51.6',
			'157'=>'52.2',
			'158'=>'52.8',
			'159'=>'53.4',
			'160'=>'54',
			'161'=>'54.6',
			'162'=>'55.2',
			'163'=>'55.8',
			'164'=>'56.4',
			'165'=>'57',
			'166'=>'57.6',
			'167'=>'58.2',
			'168'=>'58.8',
			'169'=>'59.4',
			'170'=>'60',
			'171'=>'60.6',
			'172'=>'61.2',
			'173'=>'61.8',
			'174'=>'62.4',
			'175'=>'63',
			'176'=>'63.6',
			'177'=>'64.2',
			'178'=>'64.8',
			'179'=>'65.4',
			'180'=>'66',
			'181'=>'70.7',
			'182'=>'71.4',
			'183'=>'72.1',
			'184'=>'72.8',
			'185'=>'73.5',
			'186'=>'74.2',
			'187'=>'74.9',
			'188'=>'75.6',
			'189'=>'76.3',
			'190'=>'77',
			'191'=>'77.7',
			'192'=>'78.4',
			'193'=>'79.1',
			'194'=>'79.8',
			'195'=>'80.5',
			'196'=>'81.2',
			'197'=>'81.9',
			'198'=>'82.6',
			'199'=>'83.3',
			'200'=>'84'
		],
		'man' => [
			'155'=>'52.5',
			'156'=>'53.2',
			'157'=>'53.9',
			'158'=>'54.6',
			'159'=>'55.3',
			'160'=>'56',
			'161'=>'56.7',
			'162'=>'57.4',
			'163'=>'58.1',
			'164'=>'58.8',
			'165'=>'59.5',
			'166'=>'60.2',
			'167'=>'60.9',
			'168'=>'61.6',
			'169'=>'62.3',
			'170'=>'63',
			'171'=>'63.7',
			'172'=>'64.4',
			'173'=>'65.1',
			'174'=>'65.8',
			'175'=>'66.5',
			'176'=>'67.2',
			'177'=>'67.9',
			'178'=>'68.6',
			'179'=>'69.3',
			'180'=>'70',
			'181'=>'70.7',
			'182'=>'71.4',
			'183'=>'72.1',
			'184'=>'72.8',
			'185'=>'73.5',
			'186'=>'74.2',
			'187'=>'74.9',
			'188'=>'75.6',
			'189'=>'76.3',
			'190'=>'77',
			'191'=>'77.7',
			'192'=>'78.4',
			'193'=>'79.1',
			'194'=>'79.8',
			'195'=>'80.5',
			'196'=>'81.2',
			'197'=>'81.9',
			'198'=>'82.6',
			'199'=>'83.3',
			'200'=>'84'
		],
	],
	// 我的卡券
	'card' => [
		'card_pic' => 'http://appc.qunarmei.com/card_xxns.png',// 卡券默认图片
	]
];