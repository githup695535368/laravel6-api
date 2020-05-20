<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-10
 * Time: 16:23
 */

namespace App\Messages\Kafka;


class ConsumerWorker
{
    protected $consumerClass;
    protected $consumer;

    public function __construct($consumerClass)
    {
        $this->consumerClass = $consumerClass;
        $this->consumer = new $this->consumerClass();
    }

    public function run()
    {
        $conf = new \RdKafka\Conf();

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            \Log::info('Kafka rebalance callback:' . $this->consumerClass, [$err, $partitions]);
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo "Assign: ";
                    var_dump($partitions);
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo "Revoke: ";
                    var_dump($partitions);
                    $kafka->assign(null);
                    break;

                default:
                    throw new \Exception($err);
            }
        });


        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', $this->consumer->group);

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', $this->consumer->broker_list);
        // 取消自动提交 offset 高级消费者使用这个
        $conf->set('enable.auto.commit', 'false');

        $topicConf = new \RdKafka\TopicConf();

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $topicConf->set('auto.offset.reset', 'smallest');
        // 取消自动提交 offset 低级消费者使用这个
        //$topicConf->set('auto.commit.enable', 'false');

        // Set the configuration to use for subscribed/assigned topics
        $conf->setDefaultTopicConf($topicConf);

        $kafka_consumer = new \RdKafka\KafkaConsumer($conf);

        // Subscribe to topic 'test'
        $kafka_consumer->subscribe([$this->consumer->topic]);

        echo "Waiting for partition assignment... (make take some time when\n";
        echo "quickly re-joining the group after leaving it.)\n";

        while (true) {
            $message = $kafka_consumer->consume(120 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    try {
                        echo "===========================>Message::topic->" . $message->topic_name . '  partition->' . $message->partition . '  offset->' . $message->offset ."\n";
                        //echo $message->payload . "\n";
                        $this->consumer->handle($message);
                    } catch (\Exception $exception) {
                        echo \carbon() . ' Consumer handle error:' . $exception->getMessage() . "\n";
                        echo $message->payload . "\n";
                        \Log::error('Consumer handle error:' . $this->consumerClass, [$exception->getMessage(), $message]);
                    }
                    $kafka_consumer->commit($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:
                    echo "Kafka cosume error:" . $message->errstr() . $message->err;
                    \Log::error('Kafka cosume error:', [$message->errstr(), $message->err]);
                    break;
            }
        }

    }

}