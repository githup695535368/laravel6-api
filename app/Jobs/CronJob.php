<?php namespace App\Jobs;

/**
 * 计划任务基类
 *
 * Class CronJob
 * @package App\Jobs
 */
abstract class CronJob extends Job
{
    public static function instance()
    {
        return new static();
    }

    public function prepare()
    {

    }

    public function finish()
    {

    }

    
    abstract public function registerSchedules(\SchedulePlus &$schedule);
}
