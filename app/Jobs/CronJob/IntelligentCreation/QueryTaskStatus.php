<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-27
 * Time: 15:49
 */

namespace App\Jobs\CronJob\IntelligentCreation;


use App\Jobs\CronJob;
use App\Jobs\Queueable\IntelligentCreation\DownLoadIntelligentFinishedVideo;
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
        IntelligentWriting::whereStage(IntelligentWriting::STAGE_合成中)
            ->whereNotNull('job_id')
            ->where('is_downloading', IntelligentWriting::IS_DOWNLOADING_否)
            ->get()
            ->each(function ($intelligent) use ($BDIntelligent) {
                $response = $BDIntelligent->query_vidpress($intelligent->job_id);
                if ($response) {
                    if ($response['error_code'] == 0) {
                        $job_detail = $response['result'][$intelligent->job_id];
                        $status = $job_detail['status'];
                        if ($status == 4) {
                            $video_url = $job_detail['video_addr'];
                            $cover_pic_url = $job_detail['video_cover_addr'];
                            $duration = $job_detail['video_duration'];
                            $intelligent->duration = $duration;
                            $intelligent->save();
                            dispatch(new DownLoadIntelligentFinishedVideo($intelligent->id, $video_url, $cover_pic_url));
                            \Log::info('queryTaskStatusSuccess' . $intelligent->id, [$response]);
                        } elseif ($status == 5) {
                            $intelligent->stage = IntelligentWriting::STAGE_已合成;
                            $intelligent->status = IntelligentWriting::STATUS_生成失败;
                            $intelligent->fail_msg = $job_detail['fail_reason'] ?? '';
                            $intelligent->save();
                            \Log::info('queryTaskStatusFail' . $intelligent->id, [$response]);
                        }
                    } else {
                        \Log::error('queryTaskStatusError' . $intelligent->id, [$response]);
                    }
                }
            });
    }
}