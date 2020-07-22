<?php
namespace app\websocket\validate;
/**
 * 验证类控制器
 */
class CommonValidate
{
	// 定义各个方法的验证规则
    public static $func = [
    	'getchatnum' => [
    		'chat_id' => 'require'
    	],
    ];

}