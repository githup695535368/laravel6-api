<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-26
 * Time: 14:14
 */

namespace App\Jobs\Queueable\IntelligentCreation;


use App\Jobs\Queueable\QueueJob;
use App\Logics\IntelligentCreation\ResourceDetail;
use App\Models\IntelligentWriting;
use App\Models\IntelligentWritingResource;

class PrepareIntelligentResource extends QueueJob
{

    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;

        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!$intelligent = IntelligentWriting::find($this->id)){
            \Log::info(__METHOD__ . $this->id . "NotFound");
            return;
        }

        if($intelligent->stage != IntelligentWriting::STAGE_未开始){
            return ;
        }

        $resources = $intelligent->resources()->where('status',IntelligentWritingResource::STATUS_待处理)->get();
        if($resources->count() == 0){
            $intelligent->stage = IntelligentWriting::STAGE_预处理;
            $intelligent->status = IntelligentWriting::STATUS_生成中;
            $intelligent->save();
            IntelligentWriting::checkStage($intelligent->id);
        }else{
            $resources->each(function($resource){
                if($resource->resource_type == IntelligentWritingResource::RESOURCE_TYPE_视频){
                    if($resource->sub_type == IntelligentWritingResource::VIDEO_SUB_TYPE_剪辑){
                        $this->requestCut($resource);
                        $resource->status = IntelligentWritingResource::STATUS_处理中;
                        $resource->save();
                    }
                }
                /*elseif($resource->resource_type == IntelligentWritingResource::RESOURCE_TYPE_图片){

                }*/
            });
            $intelligent->status = IntelligentWriting::STATUS_生成中;
            $intelligent->stage = IntelligentWriting::STAGE_预处理;
            $intelligent->save();
        }


        \Log::info(__METHOD__ . $this->id);
    }

    public function requestCut(IntelligentWritingResource $resource)
    {
        $resourceDetail = ResourceDetail::create($resource->resource_detail);

        $key = 'wx:video:task:VideoCutList';
        $time = array(
            'start_ms' => $resourceDetail['start_ms'],
            'end_ms' => $resourceDetail['end_ms'],
        );

        $url = $resourceDetail['video_url'];
        $data = array(
            'video_hd_url' => $url,
            'times' => array($time),
            'task_id' => strval($resource->id),
            'callback' => config('video.intelligent_creation.cut_callback'),
            'video_bitrate' => '2000000'
        );
        $value = json_stringify($data);

        \Log::info("request_cut_" . $resource->id, [$value]);

        $redis = \RedisClient::create('video_cut');
        $success = $redis->lPush($key, $value);
        return $success;

    }
}