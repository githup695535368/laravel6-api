<?php

class Firewall
{
    private $name;
    private $limit;
    private $ttl;
    private $key;

    /**
     * @var \Redis
     */
    private $client;

    /**
     * @param string $name
     * @param int $ttl 超时时间(s)
     * @param int $limit 次数限制
     */
    public function __construct(string $name, int $ttl, int $limit)
    {
        $this->name = $name;
        $this->ttl = $ttl;
        $this->limit = $limit;

        $this->key = 'Firewall:' . $name;

        $this->client = RedisClient::create();
    }

    public function hit($amount = 1)
    {
        $count = $this->client->incrBy($this->key, $amount);
        if ($count === $amount) {
            $this->refresh();
        }

        return $count > $this->limit;
    }

    public function value()
    {
        return intval($this->client->get($this->key));
    }

    public function isBlocked()
    {
        return $this->value() >= $this->limit;
    }

    //refresh the ttl of cache key
    public function refresh()
    {
        $this->client->expire($this->key, $this->ttl);
    }

    public function reset()
    {
        $this->client->del($this->key);
    }
}
