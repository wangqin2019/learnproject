//type 门店状态:0处理中；1已合作；2未合作
//style 门店类型：0已合作会所型客户；1已合作连锁型客户；2已合作普通客户；3意向客户；4即将淘汰客户；5未合作会所型客户；6未合作连锁型客户；7未合作普通客户
//label 标签：
var province=[
	{
		"id": "1",
		"agency":"上海办事处",
		"area":"上海",
		"content": "地名地址信息;普通地名;省级地名",
		"center": "上海省政府",
		"lnglat": {
			"Q": 31.230378,
			"R": 121.473658,
			"lng": 121.473658,
			"lat": 31.230378
		},
		"tradingarea":[
			{
				"tradid": "1",
				"name":"省政府",
				"center": "上海省政府",
				"radius": "1000",
				"lnglat": {
					"Q": 31.230378,
					"R": 121.473658,
					"lng": 121.473658,
					"lat": 31.230378
				},
			},
			{
				"tradid": "2",
				"name":"徐家汇",
				"center": "徐家汇",
				"radius": "1000",
				"lnglat": {
					"Q": 31.193908,
					"R": 121.44064100000003,
					"lat": 31.193908,
					"lng": 121.440641
				},
			},
			{
				"tradid": "3",
				"name":"闵行区",
				"center": "闵行区",
				"radius": "1000",
				"lnglat": {
					"Q": 31.112813,
					"R": 121.381709,
					"lat": 31.112813,
					"lng": 121.381709
				},
			},
			{
				"tradid": "4",
				"name":"外滩",
				"center": "外滩",
				"radius": "1000",
				"lnglat": {
					"Q": 31.237767,
					"R": 121.49060199999997,
					"lat": 31.237767,
					"lng": 121.490602
				},
			},
		]
	},
	{
		"id": "2",
		"agency":"广州办事处",
		"area":"广州",
		"content": "地名地址信息;普通地名;省级地名",
		"center": "广州省政府",
		"lnglat": {
			"Q": 23.129112,
			"R": 113.264385,
			"lat": 23.129112,
			"lng": 113.264385
		},
		"tradingarea":[
			{
				"tradid": "5",
				"name":"省政府",
				"center": "广州省政府",
				"radius": "1000",
				"lnglat": {
					"Q": 23.129112,
					"R": 113.264385,
					"lat": 23.129112,
					"lng": 113.264385
				},
			},
			{
				"tradid": "6",
				"name":"广州塔",
				"center": "广州塔",
				"radius": "1000",
				"lnglat": {
					"Q": 23.106487,
					"R": 113.32458700000001,
					"lng": 113.324587,
					"lat": 23.106487
				},
			},
		],
	},
	{
		"id": "3",
		"agency":"深圳办事处",
		"area":"深圳市",
		"content": "地名地址信息;普通地名;省级地名",
		"center": "深圳省政府",
		"lnglat": {
			"Q": 22.547,
			"R": 114.08594700000003,
			"lat": 22.547,
			"lng": 114.085947
		},
		"tradingarea":[],
	},
	{
		"id": "4",
		"agency":"杭州办事处",
		"area":"杭州市",
		"content": "地名地址信息;普通地名;省级地名",
		"center": "杭州省政府",
		"lnglat": {
			"Q": 30.245853,
			"R": 120.209947,
			"lat": 30.245853,
			"lng": 120.209947
		},
		"tradingarea":[],
	},
]

var citys = [
	{
		"id": "1",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名1",
		"name": "上海市",
		"content": "地名地址信息;普通地名;省级地名",
		"lnglat": {
			"Q": 31.230378,
			"R": 121.473658,
			"lng": 121.473658,
			"lat": 31.230378
		},
		"address": "黄浦区",
		"tel": "18017288957",
		"type": 0,
		"label": ['标签1','标签2','标签3','标签4','标签5'],
		"style": 0,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"2",
				"path":"img/add.png",
			},
			{
				"id":"3",
				"path":"img/ic_cooperation_club_azure.png",
			},
			{
				"id":"4",
				"path":"img/ruler.png",
			},
			{
				"id":"5",
				"path":"img/name.png",
			},
		]
	}, 
	{
		"id": "2",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名2",
		"name": "上海贝岭",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 31.173041,
			"R": 121.40756699999997,
			"lng": 121.407567,
			"lat": 31.173041
		},
		"address": "宜山路810号中国电子贝岭大厦",
		"tel": "021-64850700",
		"type": 0,
		"label": [],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	}, 
	{
		"id": "3",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名2",
		"name": "上海ABB工程有限公司(叠桥路)",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 31.122516,
			"R": 121.61945200000002,
			"lng": 121.619452,
			"lat": 31.122516
		},
		"address": "康新公路4528号",
		"tel": "021-61056666;021-61298888",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 2,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "4",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名4",
		"name": "上海电气集团股份有限公司",
		"content": "公司企业;公司;机械电子",
		"lnglat": {
			"Q": 31.176435,
			"R": 121.40453200000002,
			"lng": 121.404532,
			"lat": 31.176435
		},
		"address": "钦江路212号",
		"tel": "021-33261888",
		"type": 2,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "5",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名5",
		"name": "上海站",
		"content": "交通设施服务;火车站;火车站",
		"lnglat": {
			"Q": 31.249563,
			"R": 121.455739,
			"lng": 121.455739,
			"lat": 31.249563
		},
		"address": "秣陵路303号",
		"tel": "021-33261666",
		"type": 2,
		"label": ['标签1','标签2'],
		"style": 3,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "6",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名6",
		"name": "上海卷烟厂",
		"content": "公司企业;工厂;工厂",
		"lnglat": {
			"Q": 31.26321,
			"R": 121.518055,
			"lng": 121.518055,
			"lat": 31.26321
		},
		"address": "长阳路733号(近通北路)",
		"tel": "021-65356893",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 4,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "7",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名7",
		"name": "上海化学工业区(目华北路)",
		"content": "商务住宅;产业园区;产业园区",
		"lnglat": {
			"Q": 30.819405,
			"R": 121.46518400000002,
			"lng": 121.465184,
			"lat": 30.819405
		},
		"address": "柘林镇",
		"tel": "021-67126666",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "8",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名8",
		"name": "上海汇众汽车制造有限公司",
		"content": "公司企业;知名企业;知名企业",
		"lnglat": {
			"Q": 31.221639,
			"R": 121.519585,
			"lng": 121.519585,
			"lat": 31.221639
		},
		"address": "上海浦东南路1493号",
		"tel": "021-58201188",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 4,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "9",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名9",
		"name": "上海电气(四川中路)",
		"content": "公司企业;公司;机械电子",
		"lnglat": {
			"Q": 31.235024,
			"R": 121.48884499999997,
			"lng": 121.488845,
			"lat": 31.235024
		},
		"address": "四川中路149号",
		"tel": "021-33261888;021-63215530",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 4,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "10",
		"agency":"上海办事处",
		"area":"上海",
		"boss":"佚名9",
		"name": "上海热线",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 31.227232,
			"R": 121.44801799999999,
			"lng": 121.448018,
			"lat": 31.227232
		},
		"address": "北京西路1465国立大厦9-10层",
		"tel": "021-52122211",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	}
];

var citys2 = [
	{
		"id": "B00141JEHS",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州市",
		"content": "地名地址信息;普通地名;地市级地名",
		"lnglat": {
			"Q": 23.129112,
			"R": 113.264385,
			"lng": 113.264385,
			"lat": 23.129112
		},
		"address": "越秀区",
		"tel": "",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B001408CE4",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州石化",
		"content": "公司企业;公司;冶金化工",
		"lnglat": {
			"Q": 23.137791,
			"R": 113.47023999999999,
			"lng": 113.47024,
			"lat": 23.137791
		},
		"address": "石化路176号",
		"tel": "",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B001406A63",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州石化",
		"content": "公司企业;公司;冶金化工",
		"lnglat": {
			"Q": 23.121125,
			"R": 113.47983999999997,
			"lng": 113.47984,
			"lat": 23.121125
		},
		"address": "石化路550号",
		"tel": "020-62128088",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00140WBI1",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州塔",
		"content": "风景名胜;风景名胜;风景名胜",
		"lnglat": {
			"Q": 23.106487,
			"R": 113.32458700000001,
			"lng": 113.324587,
			"lat": 23.106487
		},
		"address": "阅江西路222号",
		"tel": "020-89338222;020-89338225",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00141JML7",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州博韬",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 22.952398,
			"R": 113.45896099999999,
			"lng": 113.458961,
			"lat": 22.952398
		},
		"address": "官南永工业区官涌路6号",
		"tel": "020-34880828",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00141JJWP",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "苹果(中国)广州总部",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 23.198146,
			"R": 113.27679499999999,
			"lng": 113.276795,
			"lat": 23.198146
		},
		"address": "云城西路88号苹果大厦1层",
		"tel": "",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00141K54N",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广东中烟工业有限责任公司广州卷烟厂",
		"content": "公司企业;工厂;工厂",
		"lnglat": {
			"Q": 23.048974,
			"R": 113.24185399999999,
			"lng": 113.241854,
			"lat": 23.048974
		},
		"address": "东沙环翠南路88号",
		"tel": "",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00140TY64",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州东站",
		"content": "交通设施服务;火车站;火车站",
		"lnglat": {
			"Q": 23.150566,
			"R": 113.32490000000001,
			"lng": 113.3249,
			"lat": 23.150566
		},
		"address": "东站路1号",
		"tel": "020-87146222",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00140HG06",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州万宝",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 23.403077,
			"R": 113.25649399999998,
			"lng": 113.256494,
			"lat": 23.403077
		},
		"address": "东华一路2",
		"tel": "020-86780010",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	},
	{
		"id": "B00140HKUH",
		"agency":"广州办事处",
		"area":"广州",
		"boss":"佚名9",
		"name": "广州机务段",
		"content": "公司企业;公司;公司",
		"lnglat": {
			"Q": 23.155809,
			"R": 113.244573,
			"lng": 113.244573,
			"lat": 23.155809
		},
		"address": "矿泉街",
		"tel": "",
		"type": 1,
		"label": ['标签1','标签2'],
		"style": 1,
		"image":[
			{
				"id":"1",
				"path":"img/pwd.png",
			},
			{
				"id":"1",
				"path":"img/add.png",
			},
		]
	}
]

var labellist=[
	{"id":0,"name":"标签1","content":"标签说明"},
	{"id":1,"name":"标签2","content":"标签说明"},
	{"id":2,"name":"标签3","content":"标签说明标签说明标签说明标签说明标签说明标签说明标签说明标签说明标签说明标签说明标签说明"},
	{"id":3,"name":"标签4","content":"标签说明标签说明标签说明"},
	{"id":4,"name":"标签5","content":"标签说明标签说明"},
	{"id":5,"name":"标签6","content":"标签说明"},
]

var stylelist=[
	{"id":0,"name":"已合作会所型客户","url":"img/icon/normal_azure.png","color":"azure","shape":"normal"},
	{"id":1,"name":"已合作连锁型客户","url":"img/icon/normal_blue.png","color":"blue","shape":"normal"},
	{"id":2,"name":"已合作普通客户","url":"img/icon/normal_gray.png","color":"gray","shape":"normal"},
	{"id":3,"name":"意向客户","url":"img/icon/normal_green.png","color":"green","shape":"normal"},
	{"id":4,"name":"即将淘汰客户","url":"img/icon/normal_orange.png","color":"orange","shape":"normal"},
	{"id":5,"name":"未合作会所型客户","url":"img/icon/normal_purple.png","color":"purple","shape":"normal"},
	{"id":6,"name":"未合作连锁型客户","url":"img/icon/normal_red.png","color":"red","shape":"normal"},
	{"id":7,"name":"未合作普通客户","url":"img/icon/normal_yellow.png","color":"yellow","shape":"normal"},
]

var typelist=[
	{"id":0,"name":"处理中"},
	{"id":1,"name":"已合作"},
	{"id":2,"name":"未合作"},
]

var stafflist=[
	{"id":0,"name":"张雨绮","tel":"18017288957","shopnid":"1","provid":[1,2,3]},
	{"id":1,"name":"张治中","tel":"18017288957","shopnid":"2","provid":[1]},
	{"id":2,"name":"张耀扬","tel":"18017288957","shopnid":"2","provid":[1,2]},
	{"id":3,"name":"张作霖","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":4,"name":"张庭","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":5,"name":"张云川","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":6,"name":"张恭庆","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":7,"name":"张开明","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":8,"name":"张海峰","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":9,"name":"张静初","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":10,"name":"张达明","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":11,"name":"张惠春","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":12,"name":"张燮林","tel":"18017288957","shopnid":"2","provid":[1,2]},
	{"id":13,"name":"张艾嘉","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":14,"name":"张洪量","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":15,"name":"张开明","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":16,"name":"张文慈","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":17,"name":"张钧宁","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":18,"name":"张曼玉","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":19,"name":"张恩照","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":20,"name":"张信哲","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":21,"name":"张大山","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":22,"name":"张敬轩","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":23,"name":"张涵予","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":24,"name":"张维桢","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":25,"name":"张也","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":26,"name":"张佑赫","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":27,"name":"张铁林","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":28,"name":"张峻宁","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":29,"name":"张闻天","tel":"18017288957","shopnid":"1","provid":[1,2]},
	{"id":30,"name":"张智霖","tel":"18017288957","shopnid":"1","provid":[1,2]},
	
]

//var style = [
//	{
//      url: './img/icon/club_azure.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_blue.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_gray.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_green.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_red.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_orange.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_purple.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(19, 21.5),
//  },
//  {
//      url: './img/icon/club_yellow.png',
//      anchor: new AMap.Pixel(3, 3),
//      size: new AMap.Size(12, 20)
//  }
//];
