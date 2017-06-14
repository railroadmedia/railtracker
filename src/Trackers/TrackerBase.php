<?php

namespace Railroad\Railtracker\Trackers;

use Illuminate\Cache\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Railroad\Railtracker\Services\ConfigService;

class TrackerBase
{
    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * TrackerBase constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager, Repository $cache = null)
    {
        $this->databaseManager = $databaseManager;
        $this->cache = $cache;
    }

    /**
     * @param $table
     * @return Builder
     */
    protected function query($table)
    {
        return $this->databaseManager->connection(ConfigService::$databaseConnectionName)->table($table);
    }

    /**
     * @param array $data
     * @param string $table
     * @return int
     */
    public function store(array $data, $table)
    {
        $id = $this->cache->get(md5($table . '_id_' . serialize($data)));

        if (empty($id)) {
            $id = $this->query($table)->where($data)->first(['id'])->id ?? null;

            if (empty($id)) {
                $id = $this->query($table)->insertGetId($data);
            }

            $this->cache->put(
                md5($table . '_id_' . serialize($data)),
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