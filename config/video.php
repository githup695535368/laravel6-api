<?php

return [

    /*
    micro_cup api config
    */

    'tvm_search' => [
        'base_url' => "https://cloud.mtq.tvm.cn",
        'access_key' => env('TVM_SEARCH_ACCESS_KEY', 'db8549f815aea5bc'),
        'secret_key' => env('TVM_SEARCH_SECRET_KEY', 'dY3E72aQdtdugDrVVdQd26Maj3xJZk4hVqbh4+Zbvk6soRRN'),
    ],

    'intelligent_creation' => [
        'cut_callback' => env('APP_URL') . '/api/v1/intelligent-creation/cut-video-done',
    ],


];
