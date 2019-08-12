<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Doctrine\Common\Cache\ArrayCache;
use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;
use Railroad\Doctrine\Serializers\BasicEntitySerializer;
use Railroad\DoctrineArrayHydrator\ArrayHydrator;
use Railroad\Railtracker\Entities\Request;
use Railroad\Railtracker\Entities\Request as RequestEntity;
use Railroad\Railtracker\Entities\RequestAgent;
use Railroad\Railtracker\Entities\RequestDevice;
use Railroad\Railtracker\Entities\RequestLanguage;
use Railroad\Railtracker\Entities\RequestMethod;
use Railroad\Railtracker\Entities\Route;
use Railroad\Railtracker\Entities\Url;
use Railroad\Railtracker\Entities\UrlDomain;
use Railroad\Railtracker\Entities\UrlPath;
use Railroad\Railtracker\Entities\UrlProtocol;
use Railroad\Railtracker\Entities\UrlQuery;
use Railroad\Railtracker\Events\RequestTracked;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\ValueObjects\RequestVO;

class RequestTracker extends TrackerBase
{
    /**
     * @var null|string
     */
    public static $uuid;

    /**
     * @var string
     */
    public static $cookieKey = 'railtracker_visitor';

    /**
     * @var RailtrackerEntityManager
     */
    protected $entityManager;

    /**
     * @var ArrayHydrator
     */
    private $arrayHydrator;

    /**
     * @var ArrayCache
     */
    protected $arrayCache;

    public function __construct(
        DatabaseManager $databaseManager,
        Router $router,
        CookieJar $cookieJar,
        Repository $cache = null,
        BatchService $batchService,
        BasicEntitySerializer $basicEntitySerializer,
        RailtrackerEntityManager $entityManager
    )
    {
        parent::__construct(
            $databaseManager,
            $router,
            $cookieJar,
            $cache,
            $batchService,
            $basicEntitySerializer,
            $entityManager
        );

        $this->entityManager = $entityManager;

        $this->arrayHydrator = new ArrayHydrator($this->entityManager);
        $this->arrayCache = new ArrayCache();
    }

    /**
     * @param HttpRequest $httpRequest
     * @return array
     */
    public function serializedFromHttpRequest(HttpRequest $httpRequest)
    {
        $request = new RequestEntity();
        $userAgent = new Agent($httpRequest->server->all());

        $request = new RequestVO($httpRequest);

        dd(unserialize(serialize($request)));

        return $request;
    }

    /**
     * @param array $requestSerialized
     * @return \Railroad\Railtracker\Entities\Request|Url
     * @throws Exception
     */
    public function hydrate($requestSerialized)
    {
        $request = new RequestEntity();

        // ---------- Step 1: scalar values ----------

        $request->setUuid($requestSerialized['uuid']);
        $request->setUserId($requestSerialized['userId']);
        $request->setCookieId($requestSerialized['cookieId']);
        $request->setGeoip($requestSerialized['geoip']);
        $request->setClientIp($requestSerialized['clientIp']);
        $request->setIsRobot($requestSerialized['isRobot']);
        $request->setRequestedOn(Carbon::parse($requestSerialized['requestedOn']));

        // ---------- Step 2: Associated Objects, Simple ----------
        // These objects have only scalar values - they do *not* themselves have associated objects

        $requestAgent = $this->getByData(RequestAgent::class, $requestSerialized[RequestAgent::$KEY]);

        if (empty($requestAgent)) {
            $requestAgent = new RequestAgent();
            $requestAgent->setName($requestSerialized[RequestAgent::$KEY]['name']);
            $requestAgent->setBrowserVersion($requestSerialized[RequestAgent::$KEY]['browserVersion']);
            $requestAgent->setBrowser($requestSerialized[RequestAgent::$KEY]['browser']);

            $this->persistAndFlushEntity($requestAgent);
        }

        $request->setAgent($requestAgent);

        // request device
        $requestDevice = $this->getByData(RequestDevice::class, $requestSerialized[RequestDevice::$KEY]);
    
        if (empty($requestDevice)) {
            $requestDevice = new RequestDevice();
            $requestDevice->setIsMobile($requestSerialized[RequestDevice::$KEY]['isMobile']);
            $requestDevice->setKind($requestSerialized[RequestDevice::$KEY]['kind']);
            $requestDevice->setPlatform($requestSerialized[RequestDevice::$KEY]['platform']);
            $requestDevice->setModel($requestSerialized[RequestDevice::$KEY]['model']);
            $requestDevice->setPlatformVersion($requestSerialized[RequestDevice::$KEY]['platformVersion']);

            $this->persistAndFlushEntity($requestDevice);
        }

        $request->setDevice($requestDevice);

        // request language
        $requestLanguage = $this->getByData(RequestLanguage::class, $requestSerialized[RequestLanguage::$KEY]);

        if (empty($requestLanguage)) {
            $requestLanguage = new RequestLanguage();
            $requestLanguage->setPreference($requestSerialized[RequestLanguage::$KEY]['preference']);
            $requestLanguage->setLanguageRange($requestSerialized[RequestLanguage::$KEY]['languageRange']);

            $this->persistAndFlushEntity($requestLanguage);
        }

        $request->setLanguage($requestLanguage);

        // request method

        $requestMethod = $this->getByData(RequestMethod::class, $requestSerialized[RequestMethod::$KEY]);

        if (empty($requestMethod)) {
            $requestMethod = new RequestMethod();
            $value = $requestSerialized[RequestMethod::$KEY]['method'];
            $requestMethod->setMethod($value);

            $this->persistAndFlushEntity($requestMethod);
        }

        $request->setMethod($requestMethod);

        // route

        $routeNotNull = !empty($requestSerialized[Route::$KEY]['name']) || !empty($requestSerialized[Route::$KEY]['route']);

        if($routeNotNull){
            $route = $route = $this->getByData(Route::class, $requestSerialized[Route::$KEY]);

            if (empty($route)) {

                $route = new Route();

                $route = $this->arrayHydrator->hydrate($route, $requestSerialized[Route::$KEY]);

                $this->persistAndFlushEntity($route);
            }

            if(!empty($route)){
                $request->setRoute($route);
            }
        }

        // ---------- Step 3: Associated Objects, Complex ----------
        // These objects have associated objects

        if(!empty($requestSerialized[Url::$KEY])){
            $url = $this->getOrCreateUrlForData($requestSerialized[Url::$KEY]);
            $request->setUrl($url);
        }

        if(!empty($requestSerialized['refererUrl'])){
            $refererUrl = $this->getOrCreateUrlForData($requestSerialized['refererUrl']);
            $request->setRefererUrl($refererUrl);
        }

        // ---------- Step 4: End ----------

        return $request;
    }

    /**
     * @param array $urlAsArray
     * @return Url
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getOrCreateUrlForData($urlAsArray)
    {
        // ============ 1 associated entities ============

        // 1.1 - url protocol (note that url table's protocol_id is NOT nullable)

        if(empty($urlAsArray['protocol'])){
            error_log('"$urlAsArray[\'protocol\']" empty.' );
        }

        $urlProtocolValue = $urlAsArray['protocol'];

        $urlProtocol = $this->getByData(UrlProtocol::class, ['protocol' => $urlProtocolValue]);

        if (empty($urlProtocol)) {

            $urlProtocol = new UrlProtocol();
            $urlProtocol->setProtocol($urlProtocolValue);
            $urlProtocol->setHash();

            $this->entityManager->persist($urlProtocol);
        }

        // 1.2 -  url domain (note that url table's domain_id is NOT nullable)

        if(empty($urlAsArray['domain'])){
            error_log('"$urlAsArray[\'domain\']" empty.' );
        }

        $urlDomainValue = $urlAsArray['domain'];

        $urlDomain = $this->getByData(UrlDomain::class, ['name' => $urlDomainValue]);

        if (empty($urlDomain)) {

            $urlDomain = new UrlDomain();
            $urlDomain->setName($urlDomainValue);
            $urlDomain->setHash();

            $this->entityManager->persist($urlDomain);
        }

        // 1.3 - url path (note that url table's path_id is nullable)

        if(!empty($urlAsArray['path'])){

            $urlPath = $this->getByData(UrlPath::class, ['path' => $urlAsArray['path']]);

            if (empty($urlPath)) {

                $urlPath = new UrlPath();
                $urlPath->setPath($urlAsArray['path']);
                $urlPath->setHash();

                $this->entityManager->persist($urlPath);
            }
        }

        // 1.4 - url query (note that url table's query_id is nullable)

        if (!empty($urlAsArray['query'])) {

            $urlQuery = $this->getByData(UrlQuery::class, ['string' => $urlAsArray['query']]);

            if (empty($urlQuery)) {

                $urlQuery = new UrlQuery();
                $urlQuery->setString($urlAsArray['query']);
                $urlQuery->setHash();

                $this->entityManager->persist($urlQuery);
            }
        }

        $this->entityManager->flush();

        // ============ 2 the Url entity itself ============

        // 2.1 - query

        $query =
            $this->entityManager->createQueryBuilder()
                ->from(Url::class, 'u')
                ->select([
                    'u',
                    'udomain',
                    'upath',
                    'uprotocol',
                    'uquery',
                ])
                ->leftJoin('u.protocol', 'uprotocol')
                ->leftJoin('u.domain', 'udomain')
                ->leftJoin('u.path', 'upath')
                ->leftJoin('u.query', 'uquery');

        $query->andWhere('IDENTITY(u.domain) = :domainId');
        $query->setParameter('domainId', $urlDomain->getId());

        $query->andWhere('IDENTITY(u.protocol) = :protocolId');
        $query->setParameter('protocolId', $urlProtocol->getId());

        if(!empty($urlPath)){
            $query->andWhere('IDENTITY(u.path) = :pathId');
            $query->setParameter('pathId', $urlPath->getId());

        }else{
            $query->andWhere('IDENTITY(u.path) is NULL');
        }

        if(!empty($urlQuery)){
            $query->andWhere('IDENTITY(u.query) = :queryId');
            $query->setParameter('queryId', $urlQuery->getId());

        }else{
            $query->andWhere('IDENTITY(u.query) is NULL');
        }


        $url = $query->getQuery()
            //->setResultCacheDriver($this->arrayCache) // todo: implement?
            ->getResult()[0] ?? null;

        // 2.1 - set associated entities

        if (empty($url)) {

            $url = new Url();

            $url->setProtocol($urlProtocol);
            $url->setDomain($urlDomain);

            if(!empty($urlPath)){
                $url->setPath($urlPath);
            }

            if(!empty($urlQuery)){
                $url->setQuery($urlQuery);
            }

            $this->entityManager->persist($url);
        }

        return $url;
    }

    /**
     * @param Agent $agent
     * @return string
     */
    protected function getDeviceKind(Agent $agent)
    {
        $kind = 'unavailable';

        if ($agent->isTablet()) {
            $kind = 'tablet';
        }
        elseif ($agent->isPhone()) {
            $kind = 'phone';
        }
        elseif ($agent->isDesktop()) {
            $kind = 'desktop';
        }

        return $kind;
    }

    /**
     * Delete user cookie
     */
    protected function deleteCookieForAuthenticatedUser()
    {
        $this->cookieJar->queue($this->cookieJar->forget(self::$cookieKey));
    }

    /**
     * @param Agent $agent
     * @return RequestDevice
     */
    public function fillRequestDevice(Agent $agent)
    {
        $requestDevice = new RequestDevice();

        $requestDevice->setPlatform(substr($agent->platform(), 0, 64));
        $requestDevice->setPlatformVersion(substr($agent->version($agent->platform()), 0, 16));
        $requestDevice->setKind(substr($this->getDeviceKind($agent), 0, 16));
        $requestDevice->setModel(substr($agent->device(), 0, 64));
        $requestDevice->setIsMobile($agent->isMobile());
        $requestDevice->setHash();

        return $requestDevice;
    }

    /**
     * @param Agent $agent
     * @return RequestAgent
     */
    public function fillRequestAgent(Agent $agent)
    {
        $requestAgent = new RequestAgent();

        $requestAgent->setName(substr($agent->getUserAgent() ?: 'Other', 0, 180));
        $requestAgent->setBrowser(substr($agent->browser(), 0, 64));
        $requestAgent->setBrowserVersion(substr($agent->version($agent->browser()), 0, 32));
        $requestAgent->setHash();

        return $requestAgent;
    }

    /**
     * @param string $url
     * @param bool $returnSerialized
     * @return array|null|Url
     */
    public function fillUrl($url, $returnSerialized = false)
    {
        $urlEntity = new Url();

        $urlEntity->setDomain(UrlDomain::createFromUrl($url));
        $urlEntity->setProtocol(UrlProtocol::createFromUrl($url));
        $urlEntity->setPath(UrlPath::createFromUrl($url));
        $urlEntity->setQuery(UrlQuery::createFromUrl($url));

        if (empty($url) || parse_url($url) === false) {
            return null;
        }

        $urlEntity->setHash();

        $pathSerialized = $this->serialize($urlEntity->getPath());
        $querySerialized = $this->serialize($urlEntity->getQuery());

        $path = parse_url($url)['path'] ?? null;
        $query = parse_url($url)['query'] ?? null;

        $setPath = !empty($path) ? $pathSerialized : null;
        $setQuery = !empty($query) ? $querySerialized : null;

        if ($returnSerialized) {
            return [
                'protocol' => $this->serialize($urlEntity->getProtocol()),
                'domain' => $this->serialize($urlEntity->getDomain()),
                'path' => $setPath,
                'query' => $setQuery,
                'hash' => $urlEntity->getHash(),
            ];
        }

        return $urlEntity;
    }

    /**
     * @param HttpRequest $httpRequest
     * @return Route|null
     */
    public function fillRoute(HttpRequest $httpRequest)
    {
        $routeNull = false;

        try {
            if (!empty($this->router->current())) {
                $route = $this->router->current();
            }
            else {
                $route =
                    $this->router->getRoutes()
                        ->match($httpRequest);
            }
        } catch (Exception $e) {
            $routeNull = true;
        }
        if (empty($route) || empty($route->getName()) || empty($route->getActionName())) {
            $routeNull = true;
        }

        $obj = new Route();

        $obj->setName('');
        $obj->setAction('');

        if (!$routeNull) {
            $obj->setName(substr($route->getName(), 0, 170));
            $obj->setAction(substr($route->getActionName(), 0, 170));
        }

        $obj->setHash();
        return $obj;
    }

    /**
     * @param $method
     * @return RequestMethod
     */
    public function fillMethod($method)
    {
        $obj = new RequestMethod();
        $obj->setMethod(substr($method, 0, 8));
        $obj->setHash();

        return $obj;
    }

    /**
     * @param Agent $agent
     * @return RequestLanguage
     */
    public function fillLanguage(Agent $agent)
    {
        $obj = new RequestLanguage();
        $obj->setPreference(substr($agent->languages()[0] ?? 'en', 0, 12));
        $obj->setLanguageRange(substr(implode(',', $agent->languages()), 0, 180));
        $obj->setHash();

        return $obj;
    }

    /**
     * @param Collection|RequestVO[] $requests
     * @return void
     */
    public function updateUsersAnonymousRequests(Collection $requests)
    {
        foreach($requests as $request){
            $userId = $request->user_id;
            $cookieId = $request->cookie_id;

            $table = config('railtracker.table_prefix') . 'requests'; // todo: a more proper way to get this?

            if (!empty($userId) && !empty($cookieId)) {
                $this->databaseManager->table($table)
                    ->where(['cookie_id' => $cookieId])
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId]);

                $this->deleteCookieForAuthenticatedUser();
            }
        }
    }

    /**
     * @param Collection|RequestVO[] $requestEntities
     * @param array $usersPreviousRequestsByCookieId
     */
    public function fireRequestTrackedEvents(Collection $requestEntities, $usersPreviousRequestsByCookieId = [])
    {
        foreach($requestEntities as $requestEntity){

            $userHasPreviousRequest = !empty($usersPreviousRequestsByCookieId[$requestEntity->cookie_id]);

            if($userHasPreviousRequest){
                $previousRequest = $usersPreviousRequestsByCookieId[$requestEntity->cookie_id];
                $timeOfPreviousRequest = $previousRequest->requested_on;
            }

            event(
                new RequestTracked(
                    $requestEntity->id,
                    $requestEntity->user_id,
                    $requestEntity->ip_address,
                    $requestEntity->agent_string,
                    $requestEntity->requested_on,
                    $timeOfPreviousRequest ?? null
                )
            );
        }
    }

    /**
     * @param $requestEntity
     * @return array
     */
    public function getPreviousRequestsDatabaseRows($requestEntity)
    {
        $table = config('railtracker.table_prefix') . 'requests'; // todo: a more proper way to get this?

        $results = $this->databaseManager->table($table)
            ->where(['user_id' => $requestEntity->userId])
            ->get()
            ->all();

        return $results;
    }
}
