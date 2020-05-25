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



    // 1001 ~  1099 用户相关

    const USERNAME_OR_PASSWORD_ERROR = [
        'code' => '1001',
        'msg' => '用户名或密码错误'
    ];

    const USER_NOT_EXIST = [
        'code' => '1002',
        'msg' => '用户不存在'
    ];

    //1101 ～ 1199 智能创作相关

    const UPLOAD_FILE_FAIL = [
        'code' => '1101',
        'msg' => '文件上传失败'
    ];



    // video 2001 ~ 2099
    const VIDEO_UUID_ERROR = [
        'code' => '2001',
        'msg' => '无效uuid,找不到该视频'
    ];



}
