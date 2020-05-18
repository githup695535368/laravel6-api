<?php namespace App\Jobs\Queueable;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;


abstract class QueueJob  implements ShouldQueue
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 队列中执行的处理函数
     */
    abstract public function handle();


    /**
     * 慢队列，耗时较长的任务放入此队列
     */
    public function onDelayedQueue()
    {
        return $this->onQueue(config('queue.special_queue.delayed'));
    }

    /**
     * 实时队列，执行时间超过 1s 的请不要放入此队列
     */
    public function onRealtimeQueue()
    {
        return $this->onQueue(config('queue.special_queue.realtime'));
    }



}
