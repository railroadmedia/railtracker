<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Predis\ClientInterface;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
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

    // todo: test for this (it's not used anywhere, so delete it or write test for it?)
    /**
     * @param RequestVO $requestVO
     */
    public function removeRequest(RequestVO $requestVO)
    {
        $setKey = $this->batchKeyPrefix . 'set' . '_' . $requestVO->uuid;

        $this->cache()->del([$setKey]);
    }

    /**
     * @param ExceptionVO $exceptionVO
     * @param string $uuid
     */
    public function storeException(ExceptionVO $exceptionVO)
    {
        $uuid = $exceptionVO->uuid;

        $setKey = $this->batchKeyPrefix . 'set' . '_' . $uuid;

        $this->cache()->sadd($setKey, [serialize($exceptionVO)]);
    }

    // todo: test for this (it's not used anywhere, so delete it or write test for it?)
    /**
     * @param array $data
     */
    public function removeException($data)
    {
        $setKey = $this->batchKeyPrefix . 'set' . $data['uuid'];

        $this->cache()->del([$setKey]);
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