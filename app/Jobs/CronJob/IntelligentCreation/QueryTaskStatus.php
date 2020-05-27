<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-27
 * Time: 15:49
 */

namespace App\Jobs\CronJob\IntelligentCreation;


use App\Jobs\CronJob;

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
        sleep(300);
        \Log::info('QueryTaskStatus');
    }
}