<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-11
 * Time: 18:12
 */

namespace App\Messages\Kafka;


abstract class AbstractProducer
{
    protected $producerTopic;
    protected $producer;

    public function __construct()
    {
        $this->initRdKafka();

    }

    public function initRdKafka()
    {
        $conf = new \RdKafka\Conf();

        $conf->setDrMsgCb(function ($kafka, $message) {
            \Log::info("Kafka producer drmsg callback", [var_export($message, true)]);
        });
        $conf->setErrorCb(function ($kafka, $err, $reason) {
            \Log::info("Kafka producer error callback", [rd_kafka_err2str($err), $reason]);
        });

        $producer = new \RdKafka\Producer($conf);
        $producer->setLogLevel(LOG_DEBUG);
        $producer->addBrokers($this->getBrokerList());

        $topiConf = new \RdKafka\TopicConf();
        // -1必须等所有brokers同步完成的确认 1当前服务器确认 0不确认，这里如果是0回调里的offset无返回，如果是1和-1会返回offset
        $topiConf->set('request.required.acks', 1);
        $producerTopic = $producer->newTopic($this->getTopic(), $topiConf);

        $this->producer = $producer;
        $this->producerTopic = $producerTopic;

    }

    abstract function getBrokerList(): string;

    abstract function getTopic(): string;


    public function addMessage($payload, $option = null)
    {
        $this->producerTopic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $option);
        return $this;
    }

    public function send()
    {
        $this->producer->poll(50);
        /*$len = $this->producer->getOutQLen();
        while ($len > 0) {
            $len = $this->producer->getOutQLen();
            var_dump($len);
            $this->producer->poll(50);
        }*/
    }
}