<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-26
 * Time: 15:02
 */

namespace App\Jobs\Queueable\IntelligentCreation;


use App\Jobs\Queueable\QueueJob;
use App\Logics\IntelligentCreation\ResourceDetail;
use App\Models\IntelligentWriting;
use App\Models\IntelligentWritingResource;
use Illuminate\Support\Str;

class DownLoadIntelligentFinishedVideo extends QueueJob
{

    protected $intelligent_id;
    protected $video_url;
    protected $cover_pic_url;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($intelligent_id, $video_url, $cover_pic_url)
    {
        $this->intelligent_id = $intelligent_id;
        $this->video_url = $video_url;
        $this->cover_pic_url = $cover_pic_url;
    }

    /**
     * 队列中执行的处理函数
     */
    public function handle()
    {
        ini_set("memory_limit","2G");
        if(!$intelligent = IntelligentWriting::find($this->intelligent_id)){
            return;
        }

        if($intelligent->is_downloading == IntelligentWriting::IS_DOWNLOADING_是){
            return ;
        }

        $intelligent->is_downloading = IntelligentWriting::IS_DOWNLOADING_是;
        $intelligent->save();

        $video_path = put_file_video_path('intelligent_writing') . '/' . Str::random('40') . ".mp4";
        $image_path = put_file_image_path('intelligent_writing') . '/' . Str::random('40') . "." . get_url_ext($this->cover_pic_url, 'jpg');
        $video_content = file_get_contents($this->video_url);
        $video_is_success = \Storage::put($video_path, $video_content);
        $video_is_success && $intelligent->video_path = $video_path;


        $image_content = file_get_contents($this->cover_pic_url);
        $image_is_success = \Storage::put($image_path, $image_content);
        $image_is_success && $intelligent->cover_pic = $image_path;

        $intelligent->stage = IntelligentWriting::STAGE_已合成;
        $intelligent->status = IntelligentWriting::STATUS_生成成功;
        $intelligent->is_downloading = IntelligentWriting::IS_DOWNLOADING_否;
        $intelligent->save();
    }

}