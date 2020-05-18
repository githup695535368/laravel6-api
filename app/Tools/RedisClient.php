<?php


class RedisClient extends \Redis
{
    /**
     * 根据配置文件中的配置项创建 Redis Client
     *
     * @param string $config
     * @return RedisClient
     */
    public static function create($config = 'default')
    {
        $info = config('database.redis.' . $config);
        assert($info, "Redis config `{$config}` does not exists.");

        $client = new self();
        $client->connect($info['host'], $info['port']);
        if(!empty($info['password'])){
            $client->auth($info['password']);
        }
        $client->select($info['database']);

        return $client;
    }

    public static function createCacheClient()
    {
        return static::create('cache');
    }
}
