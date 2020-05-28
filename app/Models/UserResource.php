<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-21
 * Time: 15:49
 */

namespace App\Models;


class UserResource extends BaseModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    const TYPE_图片 = 'image';
    const TYPE_视频 = 'video';

    const TAG_片头  = 'video_begin';
    const TAG_片尾  = 'video_end';
    const TAG_角标  = 'video_logo';

    protected $dates = ['deleted_at'];

}