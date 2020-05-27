<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-27
 * Time: 15:49
 */

namespace App\Jobs\CronJob\IntelligentCreation;


use App\Jobs\CronJob;
use App\Logics\BaiduOpenPlatfrom\IntelligentWriting\BaiduIntelligentWriting;
use App\Models\IntelligentWriting;

class QueryTaskStatus extends CronJob
{
    public function registerSchedules(\SchedulePlus &$schedule)
    {
        $schedule->addJob(function () {
            $this->queryTaskStatus();
        })->everyMinute()->name('QueryTaskStatus')->withoutOverlapping();;

    }

    private function queryTaskStatus()
    {
        $BDIntelligent = new BaiduIntelligentWriting();
        IntelligentWriting::whereStage(IntelligentWriting::STAGE_合成中)->whereNotNull('job_id')->get()
            ->each(function ($intelligent) use ($BDIntelligent) {
                $response  = $BDIntelligent->query_vidpress($intelligent->job_id);
                if($response){
                    if($response['error_code'] == 0){

                    } else {

                    }
                }
            });
        \Log::info('QueryTaskStatus');
    }
}