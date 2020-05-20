<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-20
 * Time: 11:42
 */

namespace App\Jobs\CronJob;


use App\Jobs\CronJob;

class TestCronJob extends CronJob
{

    public function registerSchedules(\SchedulePlus &$schedule)
    {
        $schedule->addJob(function () {
            $this->test();
        })->everyMinute();

    }

    private function test()
    {
        \Log::info('TestCronJob');
    }
}