<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class CronJob extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cronjob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[Laputa] Run defined jobs.';

    public function handle()
    {
        $schedule = new \SchedulePlus();
        $this->fireRegisteredJobs($schedule);

        // Job 定义在此行之前
        $schedule->run($this->laravel);
    }

    /**
     * 注册上方 register 函数中的 Job
     */
    private function fireRegisteredJobs(\SchedulePlus &$schedule)
    {
        $registered = $this->register();

        foreach ($registered as $job) {
            if (is_subclass_of($job, \App\Jobs\CronJob::class)) {
                /** @var \App\Jobs\CronJob $instance */
                @$instance = new $job;
                @$instance->prepare();
                @$instance->registerSchedules($schedule);
                @$instance->finish();
            }
        }
    }

    private function register()
    {
        return [
            \App\Jobs\CronJob\TestCronJob::class,
        ];
    }
}
