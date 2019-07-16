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

    public $prefixForSet;

    /**
     * BatchService constructor.
     */
    public function __construct()
    {
        $this->batchKeyPrefix = config('railtracker.batch-prefix', 'railtracker_');
        $this->prefixForSet = $this->batchKeyPrefix . 'set';

        $this->store = Cache::store('redis');
        $this->connection = $this->store->connection();
    }

    /**
     * @param array $datum
     * @param $uuid (of the request)
     */
    public function addToBatch($datum, $uuid)
    {
        $setKey = $this->prefixForSet . $uuid;

        $this->cache()->sadd($setKey, [serialize($datum)]);
    }

    public function getUuidFromCacheKey($keyString)
    {
        return substr($keyString, strlen($this->prefixForSet));
    }

    /**
     * @return ClientInterface
     */
    public function cache()
    {
        return $this->connection;
    }

    /**
     * @param string|array $forget
     */
    public function forget($forget)
    {
        if(!is_array($forget)){
            $forget = [$forget];
        }

        foreach($forget as $key){
            $this->cache()->del($key);
        }
    }
}