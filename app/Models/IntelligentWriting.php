<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-21
 * Time: 15:35
 */

namespace App\Models;


class IntelligentWriting extends BaseModel
{

    const  VIDEO_LOGO_TYPE_有 = 1;
    const  VIDEO_LOGO_TYPE_没有 = 2;

    const  VIDEO_BEGIN_TYPE_自动片头 = 1;
    const  VIDEO_BEGIN_TYPE_上传片头 = 2;

    const  VIDEO_END_TYPE_无片尾 = 1;
    const  VIDEO_END_TYPE_上传片尾 = 2;


    protected $table = 'intelligent_writing';

    protected $casts = [
        'custom_config' => 'array'
    ];



}