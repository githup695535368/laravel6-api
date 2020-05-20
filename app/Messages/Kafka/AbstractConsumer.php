<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-10
 * Time: 16:09
 */

namespace App\Messages\Kafka;


abstract class AbstractConsumer
{

    public $group = 'should be rewrite';
    public $topic = 'should be rewrite';
    public $broker_list = ''; // 多个地址用逗号分割

    abstract public function handle(\RdKafka\Message $message);
}