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

class DownLoadIntelligentResourceOriginImage extends QueueJob
{

    protected $intelligent_resource_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($intelligent_resource_id)
    {
        $this->intelligent_resource_id = $intelligent_resource_id;
    }

    /**
     * 队列中执行的处理函数
     */
    public function handle()
    {

        if(!$resource = IntelligentWritingResource::find($this->intelligent_resource_id)){
            return;
        }

        $resourceDetail = ResourceDetail::create($resource->resource_detail);
        $image_url = $resourceDetail['image_url'];
        $img = \Image::make($image_url);
        $filePath = storage_put_image($img,'jpg','intelligent_writing');
        if ($filePath) {
            $resourceDetail['file_path'] = $filePath;
            $resource->resource_detail = $resourceDetail->getData();
            $resource->status = IntelligentWritingResource::STATUS_处理完成;
            $resource->save();

            IntelligentWriting::checkStage($resource->iw_id);
        }
    }

}