<?php

/**
 * Created by PhpStorm.
 * User: wq
 * Date: 2018/11/12
 * Time: 17:37
 */
namespace app\api\validate;

class Common
{
    // 定义各个方法的验证规则
    public static $func = [
	    // 定时任务-每小时-考核截止检测
	    'upd_assess' => [

	    ],
	    // 提交分数
	    'submit_score' => [
		    'assess_id' => 'require',
		    'mobile' => 'require',
		    'score' => 'require'
	    ],
	    // 考生列表-详情
	    'examinee_detail' => [
		    'assess_id' => 'require'
	    ],
        // 考生列表
        'examinee_list' => [
            'assess_id' => 'require'
        ],
        // 提交录像
        'submit_video' => [
            'assess_id' => 'require',
            'live_id' => 'require'
        ],
        // 考核详情
        'assess_detail' => [
            'assess_id' => 'require',
        ],
        // 考核列表
        'assess_list' => [
            'mobile' => 'require',
        ],
        // 属性商品添加对应产品小图
        'goods_extend_add' => [
        ],
        // 文案直播-当前文案
        'current_copyroom' => [
            'chat_id' => 'require',
        ],
        // 门店商品支付方式添加
        'goods_pay_add' => [
        ],
        // 七牛云直播流状态查询
        'get_qiniu_live' => [
        ],
        // 直播文案选择列表
        'get_copyroom' => [
            'user_id' => 'require',
            'store_id' => 'require',
        ],
        // 是否弹窗文案直播
        'has_copyroom' => [
            'user_id' => 'require',
            'store_id' => 'require',
        ],
        // 直播答题提交答案
        'user_live_answers_add' => [
            'user_id' => 'require',
            'live_id' => 'require',
            'subject_id' => 'require',
            'option' => 'require',
            'ls_id' => 'require'
        ],
        // 自动绑定注册用户信息
        'user_binding' => [
            'mobile' => 'require',
            'user_id' => 'require'
        ],
        // app直播当前展示人数
        'app_numbers' => [

        ],
        // 过期卡券回复可使用状态
        'card_recovery' => [

        ],
        // 直播订阅
        'live_signup' => [
            'live_id' => 'require',
            'user_id' => 'require'
        ],
        // 是否直播订阅
        'is_live_sign' => [
            'live_id' => 'require',
            'user_id' => 'require'
        ],
        // 直播详情
        'live_detail' => [
            'live_id' => 'require',
            'user_id' => 'require'
        ],
        // 520卡券激活
        'card_jh' => [

        ],
        // 过期消费券更新
        'overdue_card' => [

        ],
        // 直播当前调整后的人数
        'live_numbers' => [
            'chat_id' => 'require'
        ],
        // 直播信息人数调整设置
        'live_numbers_adjust' => [
            'chat_id' => 'require',
            'minute' => 'require',
            'nums' => 'require'
        ],
        // 添加主播9个子商品
        'insert_live_goods' => [
            'mobile' => 'require'
        ],
        // 更新主播账号下的观看门店权限
        'update_live_qx' => [
            'mobile' => 'require'
        ],
        // 发送418卡券-根据用户补发
        'live_end' => [
            'live_id' => 'require'
        ],
        // 主播主动关闭记录时长
        'close_live' => [
            'live_id' => 'require',
            'length' => 'require'
        ],
        // 发送418卡券-根据用户补发
        'send_card_user' => [

        ],
        // 发送418卡券-根据用户直播订单
        'send_card' => [
        ],
        // 网页版直播列表
        'live_list' => [
        ],
        // 删除腾讯云不用的聊天室
        'del_chat' => [

        ],
        // 更换美容师门店
        'upd_mrs_store'=>[
            'sign' => 'require',
            'mobile' => 'require',
        ],
        // 更新直播收看权限
        'update_see_live' => [

        ],
        // 批量开通门店412活动权限
        'open_live' => [
            'sign' => 'require'
        ],
        // 是否显示续播按钮
        'whether_continue' => [
            'mobile' => 'require'
        ],
        // 断流续播
        'continue_live' => [
            'mobile' => 'require'
        ],
        // java获取聊天室人数接口
        'get_chat_cnt'=>[
            'chat_id' => 'require'
        ],
        // 每10分钟同步1次用户进出直播间记录
        'user_log_to_mysql'=>[],
        // 每日定时任务
        'day_sum'=>[],
        // 未选择安心送超时订单15分提示用户
        'tip_buyter'=>[],
        // 未选择安心送超时订单修改
        'upd_axs'=>[],
        // 获取物流信息
        'get_express'=> [],
        // 推送商品到门店
        'add_live_goods' => [
            'sign' => 'require'
        ],  
        // 是否开启直播
        'is_live'=> [],
        // H5聊天室消息
        'get_live'=>[
            'id' => 'require',
            'mobile' => 'require'
        ],
        // 记录主播直播日志开关
        'log_collect'=>[
            'data' => 'require'
        ],
        // 记录主播直播日志开关
        'log_switch'=>[
            'mobile' => 'require|number'
        ],
        // 获取商品列表
        'get_good'=>[
        ],
        // 分期支付申请
        'get_apply'=>[
            'user_id' => 'require|number',
        ],
        // 分期支付申请
        'edit_apply'=>[
            'id' => 'require|number',
            'user_id' => 'require|number',
        ],
        // 分期支付申请
        'add_apply'=>[
            'user_id' => 'require|number',
            'user_name' => 'require',
            'card_no' => 'require',
            'card_img_z' => 'require',
            'card_img_f' => 'require',
            'card_img_h' => 'require',
            'cerl_img' => 'require',
            'signs' => 'require'
        ],
         // 上传图片
        'upload_img'=>[
        ],
        // 获取验证码
        'get_code'=>[
            'mobile' => 'require|number',
        ],
        // 登录
        'login'=>[
            'mobile' => 'require|number',
            'code' => 'require|number'
        ],
        // 注册用户
        'register'=>[
            'mobile' => 'require|number',
            'mrs_mobile' => 'require|number'
        ],
        // 获取商品信息
        'get_goods'=>[
            'user_id' => 'require|number',
            'store_id' => 'require|number'
        ],
        // 获取用户信息
        'get_beauty'=>[
            'user_id' => 'require|number',
        ],
        // 获取收藏视频
        'get_collect_video'=>[
            'user_id' => 'require|number',
        ],
        // 收藏视频
        'collect_video'=>[
            'user_id' => 'require|number',
            'live_id' => 'require|number',
        ],
        // 删除收藏视频
        'del_collect_video'=>[
            'user_id' => 'require|number',
            'live_id' => 'require|number',
        ],
        // 回放视频-主播删除
        'del_own_video'=>[
            'user_id' => 'require|number',
            'live_id' => 'require|number',
        ],
        // 同意隐私协议
        'save_user_notice'=>[
            'user_id' => 'require|number',
            'ver' => 'require|number',
        ],
        // 获取隐私协议
        'get_notice'=>[
            'type' => 'require',
            'ver' => 'require|number',
        ],
        // 商品是否能购买接口
        'is_pay'=>[
            'goods_id' => 'require',
        ],
        // 活动开关接口
        'act_switch'=>[
            'user_id' => 'require|number',
        ],
        // 7天未确认自动收货
        'updautoconfirm'=>[
        ],
        // 每周自动更新库存
        'updweekscorestock'=>[
        ],
        // 我的兑换-状态修改
        'updexchange_order'=>[
            'order_id' => 'require|number',
            'status' => 'require|number'
        ],
        // 我的兑换-订单详情
        'exchange_order_detail'=>[
            'order_sn' => 'require',
        ],
        // 结算中心
        'settlement_center'=>[
            'goods_id' => 'require|number',
            'goods_num' => 'require|number',
            'user_id' => 'require|number',
            'store_id' => 'require|number',
        ],
        // 我的兑换-订单
        'exchange_order'=>[
            'act_id' => 'require|number',
            'user_id' => 'require|number'
        ],
        // 积分明细
        'score_list'=>[
            'user_id' => 'require|number'
        ],
        // 立即兑换
        'redeem_now'=>[
            'goods_id' => 'require|number',
            'goods_num' => 'require|number',
            'user_id' => 'require|number',
            'store_id' => 'require|number',
        ],
        // 积分商品列表
        'score_goods'=>[
            'act_id' => 'require|number',
            'store_id' => 'require|number',
        ],
        // 积分兑换-个人积分
        'user_score'=>[
            'user_id' => 'require|number',
        ],
        // 用户注册校验
        'codecheck'=>[
            'mobile' => 'require|number',
            'code' => 'require|number',
            'id' => 'require|number',
        ],
        // 用户注册校验
        'mobilecheck'=>[
            'mobile' => 'require|number',
        ],
        // 分享绑定顾客关系
        'userregister'=>[
            'mobile' => 'require|number',
            'share_mobile' => 'require|number'
        ],
        // misshop活动分享
        'activeshare'=>[
            'user_id' => 'require|number',
        ],
        // 可用现金券
        'cashlist'=>[
            'user_id' => 'require|number',
            'price' => 'require',
        ],
        // 卡券激活核销
        'cardhandle'=>[
            'store_id' => 'require|number',
            'card_no' => 'require'
        ],
        // missshop发送卡券
        'sendcard'=>[
            'mobile' => 'require|number'
        ],
		// 订单积分
        'ordscore' => [
            'order_id' => 'require|number',
        ],
        // 用户画像查询
        'getuserportrait' => [
            'user_id' => 'require|number',
        ],
        // 用户画像提交
        'adduserportrait' => [
            'user_id' => 'require|number',
            'mobile' => 'require|number',
            'sex' => 'require',
            'age_group' => 'require|number',
            'birthday' => 'require',
            'interest' => 'require',
            'lat' => 'require',
            'lng' => 'require',
        ],
        // 门店服务
        'orderstatusupd' => [
            'status' => 'require|number',
            'appoint_id' => 'require|number',
        ],
        'orderpay'=>[
            'user_id' => 'require|number',
            'appoint_id' => 'require|number',
        ],
        'commentadd'=>[
            'user_id' => 'require|number',
            'store_id' => 'require|number',
//            'appoint_id' => 'require|number',
            'item_id' => 'require|number',
            'content' => 'require',
        ],
        'orderdetail'=>[
            'user_id' => 'require|number',
            'appoint_id' => 'require|number',
        ],
        'orderlist'=>[
            'user_id' => 'require|number',
        ],
        'makeorder'=>[
            'user_id' => 'require|number',
            'store_id' => 'require|number',
            'item_id' => 'require|number',
            'mrs_id' => 'require|number',
            'appoint_time' => 'require',
            'pay_price' => 'require',
            'id_interestrate' => 'require',
        ],
        'appointtimelist'=>[
            'item_id' => 'require|number',
            'mrs_id' => 'require|number',
            'appoint_time' => 'require'
        ],
        'mrslist'=>[
            'store_id' => 'require|number',
        ],
        'getsettlement'=>[
            'store_id' => 'require|number',
            'item_id' => 'require|number'
        ],
        'commentgiveup'=>[
            'comment_id' => 'require|number',
            'user_id' => 'require|number'
        ],
        'itemdetail'=>[
            'item_id' => 'require|number',
            'user_id' => 'require|number'
        ],
        'itemlist'=>[
            'store_id' => 'require|number'
        ],
        'itemcategory'=>[
            'store_id' => 'require|number'
        ],

        'underwearuserinfo'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'mealuserinfo'   =>  [
            'user_id' => 'require',
            'store_id' => 'require'
        ],
        'underwearuserupd'   =>  [
            'user_id' => 'require',
            'user_name' => 'require',
            'weight' => 'number',
            'height' => 'number',
            'mobile' => 'require|number',
            'email' => 'email'
        ],
        'mealuserupd'   =>  [
            'user_id' => 'require',
            'user_name' => 'require',
            'weight' => 'number',
            'height' => 'number'
        ],
    ];
}