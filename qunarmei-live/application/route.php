<?php

return [
    // '__pattern__' => [
    //     'name' => '\w+',
    // ],
    // '[hello]'     => [
    //     ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
    //     ':name' => ['index/hello', ['method' => 'post']],
    // ],

	// 外部接口定义路由,缩短请求网址(供java支付完成后调用)
    '[qunarmei]' => [
        'ordscore' => ['api/v4.score_rule/ordscore', ['method' => 'get|post']],
    ]
];
