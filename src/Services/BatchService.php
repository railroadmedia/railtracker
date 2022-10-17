<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Predis\ClientInterface;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;

class BatchService
{
    public $connection;

    public $batchKeyPrefix;

    /**
     * BatchService constructor.
     */
    public function __construct()
    {
        $this->batchKeyPrefix = config('railtracker.batch_prefix', 'railtracker_');

        try {
            $this->connection = Redis::connection(config('railtracker.redis_connection_name'));
        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }

    /**
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * @param RequestVO $requestVO
     */
    public function storeRequest(RequestVO $requestVO)
    {
        if ($this->connection) {
            $setKey = $this->batchKeyPrefix . 'set' . '_' . $requestVO->uuid;

            $this->connection->sadd($setKey, serialize($requestVO));
        }
    }

    /**
     * @param RequestVO $requestVO
     */
    public function removeRequest(RequestVO $requestVO)
    {
        if ($this->connection) {
            $setKey = $this->batchKeyPrefix . 'set' . '_' . $requestVO->uuid;

            $this->connection->del([$setKey]);
        }
    }

    /**
     * @param ExceptionVO $exceptionVO
     * @param string $uuid
     */
    public function storeException(ExceptionVO $exceptionVO)
    {
        if ($this->connection) {
            $uuid = $exceptionVO->uuid;

            $setKey = $this->batchKeyPrefix . 'set' . '_' . $uuid;

            $this->connection->sadd($setKey, [serialize($exceptionVO)]);
        }
    }

    // todo: test for this (it's not used anywhere, so delete it or write test for it?)

    /**
     * @param array $data
     */
    public function removeException($data)
    {
        if ($this->connection) {
            $setKey = $this->batchKeyPrefix . 'set' . $data['uuid'];

            $this->connection->del([$setKey]);
        }
    }

    /**
     * @param string|array $forget
     */
    public function forget($forget)
    {
        if ($this->connection) {
            if (!is_array($forget)) {
                $forget = [$forget];
            }

            foreach ($forget as $key) {
                $this->connection->del($key);
            }
        }
    }
}