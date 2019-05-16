<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Support\Facades\Cache;

class BatchService
{
    public static $batchKeyPrefix = 'railtracker_batch_';

    public function __construct()
    {
//        'railtracker_batch_request_23742973482734' => ['uuid' => '23742973482734']
//        'railtracker_batch_exceptions_23742973482734' => ['uuid' => rand()]
//        'railtracker_batch_response_23742973482734' => ['uuid' => rand()]
    }

    /**
     * @param array $datum
     * @param string $type ('request', 'exception', 'response')
     * @param $uuid (of the request)
     * @param int $expireSeconds
     */
    public function addToBatch($datum, $type, $uuid, $expireSeconds = 604800)
    {
        $key = self::$batchKeyPrefix . $type . '_' . $uuid;

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