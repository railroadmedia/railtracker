<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Support\Facades\Cache;

class BatchService
{
    public $batchKeyPrefix;

    public function __construct()
    {
        $this->batchKeyPrefix = config('railtracker.batch-prefix');
    }

    /**
     * @param array $datum
     * @param string $type ('request', 'exception', 'response')
     * @param $uuid (of the request)
     * @param int $expireSeconds
     */
    public function addToBatch($datum, $type, $uuid, $expireSeconds = 604800)
    {
        $key = $this->batchKeyPrefix . $type . '_' . $uuid;

        $this->cache()->setex($key, $expireSeconds, serialize($datum));
    }

    /**
     * @return \Predis\ClientInterface
     */
    public function cache()
    {
        /** @var $store \Illuminate\Cache\RedisStore */
        $store = Cache::store('redis');

        /** @var \Predis\ClientInterface $connection */
        return $store->connection();
    }

    /**
     * @param $key
     */
    public function forget($key)
    {
        $this->cache()->del($key);
    }

    /**
     * @param $key
     * @return bool|string
     */
    public function getTypeFromKey($key)
    {
        $startPositionOfTypeLabel = strpos($key, $this->typeLabel) + strlen($this->typeLabel);
        $startPositionOfUuidLabel = strpos($key, $this->uuidLabel) + strlen($this->uuidLabel);

        $lengthOfTypeLabel = $startPositionOfUuidLabel - $startPositionOfTypeLabel - strlen($this->uuidLabel);

        return substr($key, $startPositionOfTypeLabel, $lengthOfTypeLabel);
    }
}