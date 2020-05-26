<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-21
 * Time: 15:35
 */

namespace App\Models;


class IntelligentWritingResource extends BaseModel
{

    const RESOURCE_TYPE_视频 = 'video';
    const RESOURCE_TYPE_图片 = 'image';
    const RESOURCE_TYPE_字幕 = 'caption';

    const VIDEO_SUB_TYPE_剪辑 = 1;
    const VIDEO_SUB_TYPE_用户素材 = 2;

    const IMAGE_SUB_TYPE_原图 = 1;
    const IMAGE_SUB_TYPE_用户素材 = 2;

    const STATUS_待处理 = 1;
    const STATUS_处理中 = 2;
    const STATUS_处理完成 = 3;


    protected $table = 'intelligent_writing_resource';
    protected $casts = [
        'resource_detail' => 'array'
    ];

}