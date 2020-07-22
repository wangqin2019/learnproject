<?php
//配置文件
return [
	'default_return_type'	=> 'json',
    // 默认时区
	'default_timezone'       => 'PRC',
    'map_key'      => '065fe85d9b2bfde0dcd1b205aa4ec282',//高德地图key
	// 微信支付参数
//    'wx_pay' => [
//        'appid' => 'wx49a7ab9464c23a60',    /*微信开放平台上的应用id*/
////        'mch_id' => "1513518171",   /*微信申请成功之后邮件中的商户id*/
//        'mch_id' => "1248782701",   /*微信申请成功之后邮件中的商户id*/
////        'api_key' => "shj13hk3h21khkasdhk1h23h12390doq",    /*在微信商户平台上自己设定的api密钥 32位*/
//        'api_key' => "GifdUuhcf2mvuccHQbdvSK8b6ILbMTDQ",    /*在微信商户平台上自己设定的api密钥 32位*/
//        'appsecret' => "257106439babef0e0adc560f32edd7da",    /*在微信商户平台上自己设定的api密钥 32位*/
//    ],
	//外部接口url配置
	'other_url'=>[
    'erp_url' => 'http://erpapi.chengmei.com:7777/U8API.asmx/',// erp接口url地址
    'sms_url' => 'http://sms.qunarmei.com/sms.php',//短信接口地址 , 服务器地址=>加8080端口
    'stock_url' => 'https://wms.chengmei.com/api/' , // 库存接口地址
    'dingding_url' => 'http://dingding.chengmei.com/',//钉钉接口url
    ],
	//打卡页面文字短语 随机显示
	'tips1'=>[
        '0'=>'记录下自己的努力和美丽',
        '1'=>'不积跬步，无以至千里',
        '2'=>'由内而外的自我改变',
        '3'=>'坚持就是胜利',
        '4'=>'自律是生命最好的防腐剂',
        '5'=>'发现每一天微小的蜕变',
        '6'=>'不积小流，无以成江海',
        '7'=>'让美成为你的习惯',
        '8'=>'不减下来，你都不知道自己有多好',
    ],
    //鼓励页面文字短短 开营日不显示 随机显示
    'tips2'=>[
        '0'=>'适当的运动，可以提高身体代谢增强体质，更会达到紧肤年轻的状态哦！',
        '1'=>'补充胶原蛋白能够达到养颜等多重功效，还有助于肌肉紧实使肌肤富有弹性和光泽。',
        '2'=>'减脂+塑形，您想要的肩薄腰细，翘臀美腿都能实现。',
        '3'=>'减脂期健康的饮食，适量优质低碳水+蛋白质+蔬菜才是减脂期间最推荐的饮食结构。',
        '4'=>'让基础体温保持在36.5C，温暧我们的身体，燃烧多余的脂肪。',
        '5'=>'充足的水分可以提高我们代谢，在饭前半小时之前喝水，还有助于抑制食欲，减少摄入。',
        '6'=>'保持肠道的健康可以使小“腹”婆 变成小“腰”精。',
        '7'=>'每天记得给自己的身体适当按摩，恢复年轻体态。',
        '8'=>'久坐对身体健康不好，对减脂也不利。与站着相比，坐着会消耗的能量更少。',
        '9'=>'警惕身体的衰老信号：脱发，驼背，便秘，松弛。',
        '10'=>'紫外线对肌肤具有很大的伤害，一旦伤害很难修复，一年四季的防晒功课要做足噢。',
        '11'=>'晨起一杯温开水，排毒又养颜，吃一顿营养又丰富的早餐，开启元气满满的一天。',
        '12'=>'减脂期间多食新鲜蔬菜水果，配合白肉类（鸡、鸭、鱼、牛肉）可以让你美美的瘦下来。',
        '13'=>'用小餐具进食，并放慢你的进食速度，可以增添你的饱腹感。',
        '14'=>'饮食不规律或不良饮食习惯，都会造成内脏脂肪升高，所以养成健康的生活习惯很重要。'
    ],
    //美容师查看打卡情况背景图 随机
    'seller_poster'=>[
        '0'=>'https://pgimg1.qunarmei.com/seller_poster1.png',
        '1'=>'https://pgimg1.qunarmei.com/seller_poster2.png',
        '2'=>'https://pgimg1.qunarmei.com/seller_poster3.png',
        '3'=>'https://pgimg1.qunarmei.com/seller_poster4.png',
        '4'=>'https://pgimg1.qunarmei.com/seller_poster5.png',
        '5'=>'https://pgimg1.qunarmei.com/seller_poster6.png',
        '6'=>'https://pgimg1.qunarmei.com/seller_poster7.png',
        '7'=>'https://pgimg1.qunarmei.com/seller_poster8.png',
        '8'=>'https://pgimg1.qunarmei.com/seller_poster9.png',
        '9'=>'https://pgimg1.qunarmei.com/seller_poster10.png',
        '10'=>'https://pgimg1.qunarmei.com/seller_poster11.png',
        '11'=>'https://pgimg1.qunarmei.com/seller_poster12.png',
        '12'=>'https://pgimg1.qunarmei.com/seller_poster13.png',
        '13'=>'https://pgimg1.qunarmei.com/seller_poster14.png',
        '14'=>'https://pgimg1.qunarmei.com/seller_poster15.png',
        '15'=>'https://pgimg1.qunarmei.com/seller_poster16.png',
        '16'=>'https://pgimg1.qunarmei.com/seller_poster17.png',
        '17'=>'https://pgimg1.qunarmei.com/seller_poster18.png',
        '18'=>'https://pgimg1.qunarmei.com/seller_poster19.png',
        '19'=>'https://pgimg1.qunarmei.com/seller_poster20.png'
    ],
    'tips5'=>[
        '0'=>'意想不到的好成绩#美魔女，你真的太优秀了',//和昨天对比体重轻了大于0.5kg 鼓励话语
        '1'=>'每天都有小变化#使用正确的饮食习惯和搭配技巧#减脂更快哦',//和昨天对比体重轻了0-0.5kg 鼓励话语
        '2'=>'蜕变，这段时间减的慢或者不掉秤#是身体的自我调节期#坚持代餐#念念不忘，终有回响',//和昨天对比体重相等 鼓励话语
        '3'=>'加油哦！低碳水低糖饮食#一定严格按要求执行。每#一份努力都会让你遇见更#美的自己！',//和昨天对比体重重了0-0.5kg 鼓励话语
        '4'=>'不要放松，不要偷吃#不要放弃，不要跳餐#养成健康的生活习惯#向内脏脂肪说再见！'//和昨天对比体重重了大于0.5kg 鼓励话语
    ],
   //体重和昨日相等 随机显示图片
    'suggest_pics1'=>[
        '0'=>'https://pgimg1.qunarmei.com/customer_poster1.jpg',
        '1'=>'https://pgimg1.qunarmei.com/customer_poster2.jpg',
        '2'=>'https://pgimg1.qunarmei.com/customer_poster3.jpg',
        '3'=>'https://pgimg1.qunarmei.com/customer_poster4.jpg',
        '4'=>'https://pgimg1.qunarmei.com/customer_poster5.jpg',
        '5'=>'https://pgimg1.qunarmei.com/customer_poster6.jpg',
        '6'=>'https://pgimg1.qunarmei.com/customer_poster7.jpg'
    ],
    //体重比昨日轻了0-0.5kg 随机显示图片
    'suggest_pics2'=>[
        '0'=>'https://pgimg1.qunarmei.com/customer_poster8.jpg',
        '1'=>'https://pgimg1.qunarmei.com/customer_poster9.jpg',
        '2'=>'https://pgimg1.qunarmei.com/customer_poster10.jpg',
        '3'=>'https://pgimg1.qunarmei.com/customer_poster11.jpg',
        '4'=>'https://pgimg1.qunarmei.com/customer_poster12.jpg',
        '5'=>'https://pgimg1.qunarmei.com/customer_poster13.jpg',
        '6'=>'https://pgimg1.qunarmei.com/customer_poster14.jpg',
        '7'=>'https://pgimg1.qunarmei.com/customer_poster15.jpg'
    ],
    //体重比昨日轻了大于0.5kg 随机显示图片
    'suggest_pics3'=>[
        '0'=>'https://pgimg1.qunarmei.com/customer_poster16.jpg',
        '1'=>'https://pgimg1.qunarmei.com/customer_poster17.jpg',
        '2'=>'https://pgimg1.qunarmei.com/customer_poster18.jpg',
        '3'=>'https://pgimg1.qunarmei.com/customer_poster19.jpg',
        '4'=>'https://pgimg1.qunarmei.com/customer_poster20.jpg'
    ],
    //体重比昨日重了0-0.5kg 随机显示图片
    'suggest_pics4'=>[
        '0'=>'https://pgimg1.qunarmei.com/customer_poster21.jpg',
        '1'=>'https://pgimg1.qunarmei.com/customer_poster22.jpg',
        '2'=>'https://pgimg1.qunarmei.com/customer_poster23.jpg'
    ],
    //体重比昨日重了大于0.5kg 随机显示图片
    'suggest_pics5'=>[
        '0'=>'https://pgimg1.qunarmei.com/customer_poster24.jpg',
        '1'=>'https://pgimg1.qunarmei.com/customer_poster25.jpg'
    ],
    //美容师提醒顾客打卡 根据顾客打卡天数发送的对应日期短信提醒
	'send_text'=>[
        '0'=>'考虑一千次，不如去做一次',
        '1'=>'瘦下来时，剩下的便是完美',
        '2'=>'蜕变，为了更好的释放自己',
        '3'=>'改变现在 就是改变未来',
        '4'=>'比三观更重要的是三围',
        '5'=>'“享瘦”充满力量的女人会发光',
        '6'=>'运动于外是锻炼，于内是修炼',
        '7'=>'自律是生命最好的防腐剂',
        '8'=>'身的节奏，心的旋律，共同创造美的协奏曲',
        '9'=>'短短21天，你要72变',
        '10'=>'越努力，越幸运',
        '11'=>'最动听的三个字不是“我爱你”而是“你瘦了”',
        '12'=>'自律的女人有多美，她的人生就有多幸福',
        '13'=>'快去击败困难，快去夺取胜利',
        '14'=>'成功没有秘决，只要保持专注',
        '15'=>'塑造自己，过程很疼，你能收获一个更好的自己',
        '16'=>'成功的路上并不拥挤，因为像你一样坚持的人并不多',
        '17'=>'牢记所得到的，忘记所付出的',
        '18'=>'再多一点坚持，就多一点成功',
        '19'=>'宝宝！你每天的坚持，藏着你未来的样子',
        '20'=>' 21天的付出，就是为了今天的收获'
    ],
    //转客现金券及礼券
    'transfer_ticket'=>[
        'cash_10_0'=>'https://pgimg1.qunarmei.com/cash_10_0.png',//10元现金券未激活
        'cash_10_1'=>'https://pgimg1.qunarmei.com/cash_10_1.png',//10元现金券未使用
        'cash_10_2'=>'https://pgimg1.qunarmei.com/cash_10_2.png',//10元现金券已核销
        'cash_20_0'=>'https://pgimg1.qunarmei.com/cash_20_0.png',
        'cash_20_1'=>'https://pgimg1.qunarmei.com/cash_20_1.png',
        'cash_20_2'=>'https://pgimg1.qunarmei.com/cash_20_2.png',
        'cash_50_0'=>'https://pgimg1.qunarmei.com/cash_50_0.png',
        'cash_50_1'=>'https://pgimg1.qunarmei.com/cash_50_1.png',
        'cash_50_2'=>'https://pgimg1.qunarmei.com/cash_50_2.png',
        'pifu_0'=>'https://pgimg1.qunarmei.com/pifu_0.png',//皮肤检测券未使用
        'pifu_1'=>'https://pgimg1.qunarmei.com/pifu_1.png',//皮肤检测券已使用
        'shuangqing_0'=>'https://pgimg1.qunarmei.com/shuangqing_0.png',
        'shuangqing_1'=>'https://pgimg1.qunarmei.com/shuangqing_1.png'
    ],

];