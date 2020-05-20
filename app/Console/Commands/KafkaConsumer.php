<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-10
 * Time: 16:18
 */

namespace App\Console\Commands;

use App\Messages\Kafka\AbstractConsumer;
use App\Messages\Kafka\ConsumerWorker;
use Illuminate\Console\Command;


class KafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consumer
                            {--class= : 消费者类名}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Kafka Consumer';

    public function handle()
    {
        $class = $this->option('class');
        if (!class_exists($class) || !is_subclass_of($class, AbstractConsumer::class)) {
            $this->error("--class={$class} 传入了错误的消费者类名");
            return;
        }

        $worker = new ConsumerWorker($class);
        $worker->run();

        return;
    }
}
