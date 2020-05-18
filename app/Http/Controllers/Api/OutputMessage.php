<?php

namespace App\Http\Controllers\Api;

class OutputMsg
{
    const PARAMS_SUCCESS = [
        'code' => '0000',
        'msg' => ''
    ];
    // 通用错误
    const PARAMS_ERROR = [
        'code' => '1500',
        'msg' => '非法请求参数'
    ];

    const MOBILE_CODE_ERROR = [
        'code' => '1600',
        'msg' => '验证码错误'
    ];



    // video 2001 ~ 2099
    const VIDEO_UUID_ERROR = [
        'code' => '2001',
        'msg' => '无效uuid,找不到该视频'
    ];



}
