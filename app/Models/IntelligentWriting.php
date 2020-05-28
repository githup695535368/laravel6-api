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

    const STAGE_未开始 = "未开始";
    const STAGE_预处理 = "预处理";
    const STAGE_待合成 = "待合成";
    const STAGE_合成中 = "合成中";
    const STAGE_已合成 = "已合成";

    const STATUS_已创建 = "已创建";
    const STATUS_生成中 = "生成中";
    const STATUS_生成成功 = "生成成功";
    const STATUS_生成失败 = "生成失败";

    const IS_DOWNLOADING_是 = 1;
    const IS_DOWNLOADING_否 = 0;

    protected $table = 'intelligent_writing';

    protected $dates = [
        'finished_at' //视频生成完成时间
    ];

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

        if (in_array($intelligent->stage, [self::STAGE_待合成, self::STAGE_合成中,self::STAGE_已合成])) {
            return ;
        }

        $status = $intelligent->resources->pluck('status')->unique()->toArray();
        if (count($status) == 1 && $status[0] == IntelligentWritingResource::STATUS_处理完成) {
            $intelligent->stage = self::STAGE_待合成;
            $intelligent->save();
            dispatch_now(new RequestCreateVidpressTimeline($intelligent->id));
        }
    }


}