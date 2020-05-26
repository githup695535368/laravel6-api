<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-21
 * Time: 15:35
 */

namespace App\Models;


use App\Jobs\Queueable\IntelligentCreation\RequestCreateVidpressTimeline;

class IntelligentWriting extends BaseModel
{

    const  VIDEO_LOGO_TYPE_有 = 1;
    const  VIDEO_LOGO_TYPE_没有 = 2;

    const  VIDEO_BEGIN_TYPE_自动片头 = 1;
    const  VIDEO_BEGIN_TYPE_上传片头 = 2;

    const  VIDEO_END_TYPE_无片尾 = 1;
    const  VIDEO_END_TYPE_上传片尾 = 2;

    const STAGE_未开始 = 1;
    const STAGE_预处理 = 2;
    const STAGE_预处理完成 = 3;
    const STAGE_合成中 = 4;
    const STAGE_合成完成 = 5;

    const STATUS_已创建 = 0;
    const STATUS_生成中 = 1;
    const STATUS_生成成功 = 2 ;
    const STATUS_生成失败 = -1;


    protected $table = 'intelligent_writing';

    protected $casts = [
        'custom_config' => 'array',
        'bd_result' => 'array',
    ];


    public function resources()
    {
        return $this->hasMany(IntelligentWritingResource::class, 'iw_id', 'id');
    }

    public function bg_music()
    {
        return $this->belongsTo(IntelligentWritingBgMusic::class, 'bg_music_id', 'id');
    }

    public function tts_per()
    {
        return $this->belongsTo(IntelligentWritingTtsPer::class, 'tts_per_id', 'id');
    }


    public static function checkStage($id)
    {
        if (!$intelligent = self::find($id)) {
            return;
        }

        if (in_array($intelligent->stage, [self::STAGE_预处理完成, self::STAGE_合成中,self::STAGE_合成完成])) {
            return ;
        }

        $status = $intelligent->resources->pluck('status')->unique()->toArray();
        if (count($status) == 1 && $status[0] == IntelligentWritingResource::STATUS_处理完成) {
            $intelligent->stage = self::STAGE_预处理完成;
            $intelligent->save();
            dispatch_now(new RequestCreateVidpressTimeline($intelligent->id));
        }
    }


}