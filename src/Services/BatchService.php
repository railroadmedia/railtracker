<?php

namespace Railroad\Railtracker\Services;

use Exception;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Predis\ClientInterface;
use Railroad\Railtracker\ValueObjects\RequestVO;

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
        $this->batchKeyPrefix = config('railtracker.batch_prefix', 'railtracker_');

        $this->store = Cache::store('redis');
        $this->connection = $this->store->connection();
    }

    /**
     * @param RequestVO $requestVO
     */
    public function storeRequest(RequestVO $requestVO)
    {
        $setKey = $this->batchKeyPrefix . 'set' . '_' . $requestVO->uuid;

        $this->cache()->sadd($setKey, [serialize($requestVO)]);
    }

    /**
     * @param RequestVO $requestVO
     */
    public function removeRequest(RequestVO $requestVO)
    {
        $setKey = $this->batchKeyPrefix . 'set' . '_' . $requestVO->uuid;

        $this->cache()->del([$setKey]);
    }

    /**
     * @return Collection
     */
    public function getAll()
    {
        $redisIterator = null;
        $redisValues = new Collection();

        while ($redisIterator !== 0) {

            try {
                $scanResult =
                    $this->cache()
                        ->scan(
                            $redisIterator,
                            [
                                'MATCH' => $this->batchKeyPrefix . '*',
                                'COUNT' => config('railtracker.scan-size', 1000)
                            ]
                        );
                $redisIterator = (integer)$scanResult[0];
                $keys = $scanResult[1];

                if (empty($keys)) {
                    continue;
                }


                foreach ($keys as $keyThisChunk) {
                    $values = $this->cache()->smembers($keyThisChunk);

                    foreach($values as $value){
                        $redisValues->push(unserialize($value));
                    }
                }
            } catch (Exception $exception) {
                error_log($exception);
            }
        }

        return $redisValues;
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