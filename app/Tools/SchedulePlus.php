<?php

/**
 * 增强版Schedule,用以替代Laravel自带版本的schedule:run命令
 *
 * 特性:
 * 0. 多进程版本,提高整体容错能力 (原Schedule为单进程版本)
 * 1. 容错性更高的WithoutOverlapping方式 (原Event::withoutOverlapping()实现的有问题)
 * 2. 更实用的信息输出(哪些函数跑了有Log输出,方便后期排查)
 *
 * $event->debug_data 是刻意生造出来用于最终执行情况的.
 */

use Illuminate\Console\Scheduling\CacheMutex;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Container\Container;

class SchedulePlus
{

    //一堆Event对象实例
    private $events = [];

    /**
     * @var CacheMutex
     *
     * 这个东西暂时没用，只是为了兼容新接口
     */
    protected $mutex;


    public function __construct()
    {
        $container = Container::getInstance();

        $this->mutex = $container->bound(Mutex::class)
            ? $container->make(Mutex::class)
            : $container->make(CacheMutex::class);

    }

    /**
     * @param string $unique_name 需要加锁运行的Job放这里
     * @param callable $callback
     * @return CallbackEvent
     */
    public function addUniqueJob(string $unique_name, callable $callback)
    {
        $func = function () use ($unique_name, $callback) {
            $l = new Locker(__CLASS__ . __FILE__ . $unique_name);
            $is_locked = $l->lock();
            if ($is_locked) {
                call_user_func($callback);
                $l->unlock();
            }
        };

        $event = $this->addJob($func);
        $event->debug_data = $this->parseClosureClassAndCode($callback);
        return $event;
    }

    /**
     * @param callable $callback
     * @return CallbackEvent
     */
    public function addJob(callable $callback)
    {
        $this->events[] = $event = new CallbackEvent($this->mutex, $callback);

        $event->debug_data = $this->parseClosureClassAndCode($callback);
        return $event;
    }

    /**
     * @param $command
     * @return Event
     */
    public function cmd($command)
    {
        $this->events[] = $event = new Event($this->mutex, $command);
        $event->debug_data = ['raw-command', $command];

        return $event;
    }

    private $workers = [];

    public function run($app)
    {
        $list = array_filter($this->events, function ($event) use ($app) {
            /* @var Event $event */
            return $event->isDue($app) && $event->filtersPass($app);
        });

        foreach ($list as $event) {
            list($class, $code) = $event->debug_data;

            /* @var Event $event */
            $pid = pcntl_fork();
            $pid_with_debug[$pid] = ['class' => $class, 'code' => $code];
            $this->workers[$pid] = true;
            if ($pid === 0) { //子进程进入了这个岔路口，父进程直接执行if后面的代码
                $pid = posix_getpid(); //实际的进程ID
                $this->log("$pid start, job: {$class} code:###{$code}###");
                $event->run($app);
                exit; //子进程必须退出，否则还会继续执行if后面的代码
            }
            usleep(200000);
        }

        $this->log(__CLASS__ . " called! Total workers: " . count($this->workers));

        while (count($this->workers)) {
            $pid = pcntl_wait($status); //父进程中可以拿到子进程的ID
            unset($this->workers[$pid]);
            $finish_debug = array_get($pid_with_debug, $pid);
            $this->log("Job: {$finish_debug['class']} finished code: {$finish_debug['code']}, pid: {$pid} current workers: " . count($this->workers));
            usleep(50000);
        }
    }

    private function log($msg)
    {
        echo(date('Y-m-d H:i:s ') . $msg . PHP_EOL);
        if (isProduction()) {
            \Log::info('Schedule: ' . $msg);
        }
    }

    /**
     * 解析 Closure 所在的类名（即文件名）及其内容代码
     *
     * @param Closure $c
     * @return array [文件名, 代码]
     */
    private function parseClosureClassAndCode(Closure $c)
    {
        $r = new ReflectionFunction($c);

        // 文件名，例如: app/..../ArchJob.php => ArchJob
        $job = rtrim(array_last(explode('/', $r->getFileName())), '.php');

        // Closure 的代码，然后使用空字符串拼接起来
        $command = [];
        $lines = file($r->getFileName());
        for ($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
            $command [] = trim($lines[$l]);
        }
        $code = implode('', $command);

        // eg: [ArchJob, $this->statsQueue();})->everyMinute()];
        return [$job, $code];
    }
}
