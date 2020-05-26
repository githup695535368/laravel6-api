<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-26
 * Time: 15:33
 */

namespace App\Jobs\Queueable\IntelligentCreation;


use App\Jobs\Queueable\QueueJob;
use App\Logics\BaiduOpenPlatfrom\IntelligentWriting\BaiduIntelligentWriting;
use App\Models\IntelligentWriting;

class RequestCreateVidpressTimeline extends QueueJob
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

        if (!$intelligent = IntelligentWriting::find($this->id)) {
            \Log::info(__METHOD__ . $this->id . "NotFound");
            return;
        }

        $BDIntelligent = new BaiduIntelligentWriting();

        $response = $BDIntelligent->createVidpressTimeline($intelligent);


        if($response['error_code'] == 0){
            $intelligent->job_id = $response['result']['job_id'];
            $intelligent->bd_result = $response['result'];
            $intelligent->stage = IntelligentWriting::STAGE_合成中;
        } else {
            $intelligent->fail_msg = $response['error_msg'];
        }

        $intelligent->save();
    }

}