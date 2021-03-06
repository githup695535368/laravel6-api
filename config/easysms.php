<?php
return  [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'aliyun',
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'aliyun' => [
            'access_key_id' => env('ALIYUN_ACCESS_KEY_ID'),
            'access_key_secret' => env('ALIYUN_ACCESS_KEY_SECRET'),
            'sign_name' => env('ALIYUN_SIGN_NAME'),
        ],
        'baidu' => [
            'ak' => env('BAIDU_ACCESS_KEY_ID'),
            'sk' => env('BAIDU_ACCESS_KEY_SECRET'),
            'invoke_id' => env('BAIDU_INVOKE_ID'),
            'domain' => env('BAIDU_SMS_DOMAIN'),
        ],
        //...
    ],
];
