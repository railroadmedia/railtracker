<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Predis\ClientInterface;

class BatchService
{
    /**
     * @var $store RedisStore
     */
    public $store;

    /**
     * @var ClientInterface $connection
     */
    public $connection;

    public $batchKeyPrefix;

    /**
     * BatchService constructor.
     */
    public function __construct()
    {
        $this->batchKeyPrefix = config('railtracker.batch-prefix');

        $this->store = Cache::store('redis');
        $this->connection = $this->store->connection();
    }

    /**
     * @param array $datum
     * @param string $type ('request', 'exception', 'response')
     * @param $uuid (of the request)
     * @param int $expireSeconds
     */
    public function addToBatch($datum, $type, $uuid, $expireSeconds = 604800)
    {
        $setKey = $this->batchKeyPrefix . 'set' . '_' . $uuid;

        $this->cache()
            ->sadd($setKey, [serialize($datum)]);
    }

    /**
     * @return ClientInterface
     */
    public function cache()
    {
        return $this->connection;
    }

    /**
     * @param $key
     */
    public function forget($key)
    {
        $this->cache()
            ->del($key);
    }
}