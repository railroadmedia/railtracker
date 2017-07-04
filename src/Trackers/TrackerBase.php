<?php

namespace Railroad\Railtracker\Trackers;

use Illuminate\Cache\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Railroad\Railtracker\Services\ConfigService;

class TrackerBase
{
    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * TrackerBase constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager, Router $router, Repository $cache = null)
    {
        $this->databaseManager = $databaseManager;
        $this->router = $router;
        $this->cache = $cache;
    }

    /**
     * @param $table
     * @return Builder
     */
    protected function query($table)
    {
        return $this->connection()->table($table);
    }

    /**
     * @return Connection
     */
    protected function connection()
    {
        return $this->databaseManager->connection(ConfigService::$databaseConnectionName);
    }

    /**
     * @param array $data
     * @param string $table
     * @return int
     */
    public function storeAndCache(array $data, $table)
    {
        $cacheKey = md5($table . '_id_' . serialize($data));

        $id = $this->cache->get($cacheKey);

        if (empty($id)) {
            $id = $this->connection()->transaction(
                function () use ($data, $table, $id) {
                    $this->query($table)->$id = $this->query($table)->where($data)->first(['id'])->id ?? null;

                    if (empty($id)) {
                        $id = $this->query($table)->insertGetId($data);
                    }

                    return $id;
                }
            );

            $this->cache->put(
                $cacheKey,
                $id,
                ConfigService::$cacheTime
            );
        }

        return $id;
    }

    /**
     * @param Request $request
     * @return int|null
     */
    protected function getAuthenticatedUserId(Request $request)
    {
        return $request->user()->id ?? null;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request)
    {
        if (!empty($request->server('HTTP_CLIENT_IP'))) {
            $ip = $request->server('HTTP_CLIENT_IP');
        } elseif (!empty($request->server('HTTP_X_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $request->server('REMOTE_ADDR');
        }

        return explode(',', $ip)[0] ?? '';
    }
}