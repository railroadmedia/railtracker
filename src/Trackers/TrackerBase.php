<?php

namespace Railroad\Railtracker\Trackers;

use Doctrine\ORM\ORMException;
use Illuminate\Cache\Repository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Railroad\Doctrine\Serializers\BasicEntitySerializer;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
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
     * @var CookieJar
     */
    protected $cookieJar;

    /**
     * @var BatchService
     */
    protected $batchService;
    /**
     * @var BasicEntitySerializer
     */
    protected $basicEntitySerializer;
    /**
     * @var RailtrackerEntityManager
     */
    protected $entityManager;

    /**
     * TrackerBase constructor.
     *
     * @param DatabaseManager $databaseManager
     * @param Router $router
     * @param CookieJar $cookieJar
     * @param Repository|null $cache
     * @param BatchService $batchService
     * @param BasicEntitySerializer $basicEntitySerializer
     * @param RailtrackerEntityManager $entityManager
     */
    public function __construct(
        DatabaseManager $databaseManager,
        Router $router,
        CookieJar $cookieJar,
        Repository $cache = null,
        BatchService $batchService,
        BasicEntitySerializer $basicEntitySerializer,
        RailtrackerEntityManager $entityManager
    ) {
        $this->databaseManager = $databaseManager;
        $this->router = $router;
        $this->cache = $cache;
        $this->cookieJar = $cookieJar;
        $this->batchService = $batchService;
        $this->basicEntitySerializer = $basicEntitySerializer;
        $this->entityManager = $entityManager;
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
        $cacheKey = 'railtracker_' . md5($table . '_id_' . serialize($data));

        $id = $this->cache->get($cacheKey);

        if (empty($id)) {
            $id = $this->connection()->transaction(
                function () use ($data, $table, $id) {
                    $id = $this->query($table)->where($data)->first(['id'])->id ?? null;

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
     * @return string
     */
    protected function getClientIp(Request $request)
    {
        if (!empty(config('railtracker.ip-api.test-ip'))) {
            return config('railtracker.ip-api.test-ip');
        }

        if (!empty($request->server('HTTP_X_ORIGINAL_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_ORIGINAL_FORWARDED_FOR');
        }
        elseif (!empty($request->server('HTTP_X_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_FORWARDED_FOR');
        }
        elseif (!empty($request->server('HTTP_CLIENT_IP'))) {
            $ip = $request->server('HTTP_CLIENT_IP');
        }
        else {
            $ip = $request->server('REMOTE_ADDR');
        }

        return explode(',', $ip)[0] ?? '';
    }

    protected function serialize($object)
    {
        if(!$object) return [];

        return $this->basicEntitySerializer->serialize(
            $object,
            $this->entityManager->getClassMetadata(get_class($object))
        );
    }

    /**
     * @param $entity
     * @param $data
     * @return mixed
     */
    protected function getByData($entity, $data)
    {
        $query = $this->entityManager->createQueryBuilder()->select('aliasFoo')->from($entity, 'aliasFoo');

        if(array_key_exists('id', $data)){
            unset($data['id']);
        }

        $first = true;
        foreach($data as $key => $value){
            if($first){
                $query = $query->where('aliasFoo.' . $key .' = :' . $key);
                $first = false;
            }else{
                $query = $query->andWhere('aliasFoo.' . $key .' = :' . $key);
            }
        }

        foreach($data as $key => $value){
            $query = $query->setParameter($key, $value);
        }

        $result = $query->getQuery()
            // ->setResultCacheDriver($this->arrayCache) // todo: implement?
            ->getResult()[0] ?? null;

        return $result;
    }

    /**
     * @param $entity
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function persistAndFlushEntity($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}