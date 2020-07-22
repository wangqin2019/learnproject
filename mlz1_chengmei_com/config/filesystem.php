<?php

use think\facade\Env;

return [
    // 默认磁盘
    'default' => Env::get('filesystem.driver', 'local'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/storage',
            // 磁盘路径对应的外部URL路径
            'url'        => '/storage',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
        'qiniu' =>[									//完全可以自定义的名称
            'type'      => 'qiniu',						//可以自定义,实际上是类名小写
            'accessKey' => 'kw7pkYECHtgXDQBgZzwhu9ijMVYkYdl6iamkylMO',		//七牛云的配置,accessKey
            'secretKey' => 'Hu2DVofTTCHzge1zJTyBmPVyBG0QqV0_wTPbwdkj',//七牛云的配置,secretKey
            'bucket'    => 'scrm',					//七牛云的配置,bucket空间名
            'domain'    => 'http://img.scrm.chengmei.com'					//七牛云的配置,domain,域名
        ]
    ],
];
