<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-10
 * Time: 12:18
 */

namespace App\Messages\Kafka\Consumers;


use App\Messages\Kafka\AbstractConsumer;

class ReadKafkaMessage extends AbstractConsumer
{
    public $group = 'xcx_video_cut_reader';
    public $topic = 'WJVideoCutToXCX';
    public $broker_list = 'tpark.yaomall.tvm.cn';

    public function handle(\RdKafka\Message $message)
    {
        $this->logPayload($message->payload);
    }

    public function logPayload($payload)
    {

        echo date("Y-m-d H:i:s", time()) . "\n";
        echo "$payload \n";
    }
}