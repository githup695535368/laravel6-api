<?php

namespace App\Jobs\Queueable;


class TestJob extends QueueJob
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

        \Log::info('TestQueueJob' . $this->id);
    }
}
