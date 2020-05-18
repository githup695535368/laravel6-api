<?php

return [
    'tts' => [
        'app_id' => env('BAIDU_TTS_APP_ID'),
        'app_key' => env('BAIDU_TTS_APP_KEY'),
        'app_secret' => env('BAIDU_TTS_APP_SECRET'),
        'text2audio_url' => 'https://tsn.baidu.com/text2audio',
    ],
    'nlp' => [
        'app_key' => env('BDY_NLP_CLIENT_ID'),
        'app_secret' => env('BDY_NLP_CLIENT_SECRET'),
        'access_token_url' => 'https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=%s&client_secret=%s',
        'news_summary_url' => 'https://aip.baidubce.com/rpc/2.0/nlp/v1/news_summary',
        'lexer_url' => 'https://aip.baidubce.com/rpc/2.0/nlp/v1/lexer',
    ],
    'access_token_url' => 'https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=%s&client_secret=%s',
];