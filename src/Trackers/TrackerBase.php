<?php

namespace Railroad\Railtracker\Trackers;

use Doctrine\ORM\Mapping\ClassMetadata;
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
        if (!empty($request->server('HTTP_CLIENT_IP'))) {
            $ip = $request->server('HTTP_CLIENT_IP');
        } elseif (!empty($request->server('HTTP_X_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_FORWARDED_FOR');
        } else {
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
     * @param $className
     * @param $serializedData
     * @return null|object
     *
     * todo: add sort option
     */
    protected function getEntityByTypeAndData($className, $serializedData)
    {
        $results = $this->getEntitiesByTypeAndData($className, $serializedData);

        if(!empty($results)){
            return $results[0];
        }
        return null;
    }

    /**
     * @param $className
     * @param $serializedData
     * @return null|array
     *
     * todo: add sort option
     */
    protected function getEntitiesByTypeAndData($className, $serializedData)
    {
        if(!$serializedData){
            return null;
        }

        if(array_key_exists('id', $serializedData)){
            unset($serializedData['id']);
        }

        $repo = $this->entityManager->getRepository($className);

        /** @var ClassMetadata $classMetaData */
        foreach($this->entityManager->getMetadataFactory()->getAllMetadata() as $classMetaData){
            if($classMetaData->getName() === $className){
                $mappings = $classMetaData->getAssociationMappings();
                break;
            }
        }

        if(!empty($mappings)){

            foreach($mappings as $mapping){
                foreach(get_class_methods($mapping['targetEntity']) as $var){
                    if(substr($var, 0, 3) === 'set'){
                        $relevantProperties[$mapping['fieldName']][] = strtolower(substr_replace($var, '', 0, 3));
                    }
                }
            }

            $aliasSet = [];
            $letter = 'A';
            while ($letter !== 'AAA') {
                $aliasSet[] = $letter++;
            }

            $rootAlias = 'rootAlias';
            $aliasIndexToUse = 0;

            $queryBuilder = $repo->createQueryBuilder($rootAlias);

            foreach($relevantProperties ?? [] as $key => $values){

                if(empty($serializedData[$key])){
                    continue;
                }

                foreach($values as $value) {
                    $i = $aliasSet[$aliasIndexToUse];
                    $join = $rootAlias . '.' . $key;

                    $queryBuilder = $queryBuilder->join($join, $i);

                    $expected = $i . '.' . $value;
                    $actual = '\'' . $serializedData[$key] . '\'';

                    $addToAdd[] = $queryBuilder->expr()->eq($expected,$actual);

                    $aliasIndexToUse++;
                }
            }

            // todo: add sort

            if(!empty($addToAdd)){
                $and = $queryBuilder->expr()->andX();
                foreach($addToAdd as $add){
                    $and->add($add);
                }
                return $queryBuilder->where($and)->getQuery()->getResult();
            }

            return $queryBuilder->getQuery()->getResult();
        }
        return $repo->findBy($serializedData);
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