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

class DownLoadIntelligentResourceCutVideo extends QueueJob
{

    protected $taskId;
    protected $videoUrl;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($taskId,$videoUrl)
    {
        $this->taskId = $taskId;
        $this->videoUrl = $videoUrl;
    }

    /**
     * 队列中执行的处理函数
     */
    public function handle()
    {
        ini_set("memory_limit","512M");
        if(!$resource = IntelligentWritingResource::find($this->taskId)){
            return;
        }
        $filePath = put_file_path('intelligent_writing_cut_video') . '/' . Str::random('40') . ".mp4";
        $content = file_get_contents($this->videoUrl);
        $isSuccess = \Storage::put($filePath, $content);
        if ($isSuccess) {
            $resourceDetail = ResourceDetail::create($resource->resource_detail);
            $resourceDetail['file_path'] = $filePath;
            $resource->resource_detail = $resourceDetail->getData();
            $resource->status = IntelligentWritingResource::STATUS_处理完成;
            $resource->save();

            IntelligentWriting::checkStage($resource->iw_id);
        }
    }

}