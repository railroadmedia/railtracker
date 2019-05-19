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

        $request->setUuid(self::$uuid);
        $request->setUserId(auth()->id());
        $request->setCookieId($httpRequest->cookie(self::$cookieKey));
        $request->setGeoip(null);
        $request->setClientIp(substr($this->getClientIp($httpRequest), 0, 64));
        $request->setIsRobot($userAgent->isRobot());
        $request->setRequestedOn(
            Carbon::now()
                ->toDateTimeString()
        );

        $request = $this->serialize($request);

        // Because these have nested|associated objects we can't use $this->serialize()
        $request['url'] = $this->fillUrl($httpRequest->fullUrl(), true);
        $request['refererUrl'] = $this->fillUrl($httpRequest->headers->get('referer'), true);

        $request['agent'] = $this->serialize($this->fillRequestAgent($userAgent));
        $request['device'] = $this->serialize($this->fillRequestDevice($userAgent));
        $request['language'] = $this->serialize($this->fillLanguage($userAgent));
        $request['method'] = $this->serialize($this->fillMethod($httpRequest->method()));
        $request['route'] = $this->serialize($this->fillRoute($httpRequest));

        return $request;
    }


    private function getByData($entity, $data)
    {
        $query = $this->entityManager->createQueryBuilder()->select('aliasFoo')->from($entity, 'aliasFoo');

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

        return $query->getQuery()->setResultCacheDriver($this->arrayCache)->getOneOrNullResult();
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

        $requestAgent = $this->getByData(RequestAgent::class, $requestSerialized['agent']);

        if (empty($requestAgent)) {
            $requestAgent = new RequestAgent();
            $requestAgent->setName($requestSerialized['agent']['name']);
            $requestAgent->setBrowserVersion($requestSerialized['agent']['browserVersion']);
            $requestAgent->setBrowser($requestSerialized['agent']['browser']);

            $this->persistAndFlushEntity($requestAgent);
        }

        $request->setAgent($requestAgent);

        // request device
        $requestDevice = $requestAgent = $this->getByData(RequestDevice::class, $requestSerialized['device']);

        if (empty($requestDevice)) {
            $requestDevice = new RequestDevice();
            $requestDevice->setIsMobile($requestSerialized['device']['isMobile']);
            $requestDevice->setKind($requestSerialized['device']['kind']);
            $requestDevice->setPlatform($requestSerialized['device']['platform']);
            $requestDevice->setModel($requestSerialized['device']['model']);
            $requestDevice->setPlatformVersion($requestSerialized['device']['platformVersion']);

            $this->persistAndFlushEntity($requestDevice);
        }

        $request->setDevice($requestDevice);

        // request language
        $requestLanguage = $this->getByData(RequestLanguage::class, $requestSerialized['language']);

        if (empty($requestLanguage)) {
            $requestLanguage = new RequestLanguage();
            $requestLanguage->setPreference($requestSerialized['language']['preference']);
            $requestLanguage->setLanguageRange($requestSerialized['language']['languageRange']);

            $this->persistAndFlushEntity($requestLanguage);
        }

        $request->setLanguage($requestLanguage);

        // request method

        $requestMethod = $this->getByData(RequestMethod::class, $requestSerialized['method']);

        if (empty($requestMethod)) {
            $requestMethod = new RequestMethod();
            $value = $requestSerialized['method']['method'];
            $requestMethod->setMethod($value);

            $this->persistAndFlushEntity($requestMethod);
        }

        $request->setMethod($requestMethod);

        // route

        $route = $route = $this->getByData(Route::class, $requestSerialized['route']);

        if (empty($route)) {
            $route = new Route();
            /** @var Route $route */
            $route = $this->arrayHydrator->hydrate($route, $requestSerialized['route']);

            $this->persistAndFlushEntity($route);
        }

        $request->setRoute($route);

        // url and referer url

        $url = $this->setUrl($requestSerialized);

        $request->setUrl($url);

        $refererUrl = $this->setUrl($requestSerialized['refererUrl']);

        $request->setRefererUrl($refererUrl);

        // ---------- Step 4: End ----------

        return $request;
    }

    /**
     * @param $requestSerialized
     * @return mixed|Url
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function setUrl($requestSerialized)
    {
        // ============ 1 associated entities ============

        // 1.1 -  url domain

        if (!empty($requestSerialized['url']['domain'])) {
            $urlDomain = $this->getByData(UrlDomain::class, ['name' => $requestSerialized['url']['domain']]);
        }

        if (empty($urlDomain)) {
            $urlDomain = new UrlDomain();
            $urlDomain->setName($requestSerialized['url']['domain'] ?? '');
            $this->entityManager->persist($urlDomain);
        }

        // 1.2 - url path

        if (!empty($requestSerialized['url']['path'])) {
            $urlPath = $this->getByData(UrlPath::class, ['path' => $requestSerialized['url']['path']]);
        }

        if (empty($urlPath)) {
            $urlPath = new UrlPath();
            $urlPath->setPath($requestSerialized['url']['path'] ?? '');
            $this->entityManager->persist($urlPath);
        }

        // 1.3 - url protocol

        if (!empty($requestSerialized['url']['protocol'])) {
            $urlProtocol = $this->getByData(UrlProtocol::class, ['protocol' => $requestSerialized['url']['protocol']]);
        }

        if (empty($urlProtocol)) {
            $urlProtocol = new UrlProtocol();
            $urlProtocol->setProtocol($requestSerialized['url']['protocol'] ?? '');
            $this->entityManager->persist($urlProtocol);
        }

        // 1.4 - url query

        if (!empty($requestSerialized['url']['query'])) {
            $urlQuery = $this->getByData(UrlQuery::class, ['string' => $requestSerialized['url']['query']]);
        }

        if (empty($urlQuery)) {
            $urlQuery = new UrlQuery();
            $urlQuery->setString($requestSerialized['url']['query'] ?? '');
            $this->entityManager->persist($urlQuery);
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
                ->join('u.domain', 'udomain')
                ->join('u.path', 'upath')
                ->join('u.protocol', 'uprotocol')
                ->join('u.query', 'uquery');

        if(!empty($urlDomain)) {
            $query->where('IDENTITY(u.domain) = ' . $urlDomain->getId());
        }
        if(!empty($urlPath)){
            $query->andWhere('IDENTITY(u.path) = ' . $urlPath->getId());
        }
        if(!empty($urlProtocol)){
            $query->andWhere('IDENTITY(u.protocol) = ' . $urlProtocol->getId());
        }
        if(!empty($urlQuery)){
            $query->andWhere('IDENTITY(u.query) = ' . $urlQuery->getId());
        }

        $url =$query->getQuery()
            ->setResultCacheDriver($this->arrayCache)
            ->getOneOrNullResult();

        // 2.1 - set associated entities

        if (empty($url)) {
            $url = new Url();
            $url->setDomain($urlDomain);
            $url->setPath($urlPath);
            $url->setProtocol($urlProtocol);
            $url->setQuery($urlQuery);
            $this->persistAndFlushEntity($url);
        }

        return $url;
    }

    /**
     * @param $data
     * @return null|RequestEntity|Url
     * @throws Exception
     */
    public function process($data)
    {
        $previousRequestsDatabaseRows = [];
        $usersPreviousRequests = null;

        $userId = $data['userId'];

        if ($userId !== null) {
            $previousRequestsDatabaseRows =
                $this->databaseManager->table(ConfigService::$tableRequests)
                    ->where(['user_id' => $userId])
                    ->get()
                    ->all();
        }

        $hydratedRequest = $this->hydrate($data);
        $this->entityManager->persist($hydratedRequest);
        $this->entityManager->flush();

        if (!empty($previousRequestsDatabaseRows)) {
            $timeOfUsersPreviousRequest =
                Carbon::parse(end($previousRequestsDatabaseRows)->requested_on)
                    ->toDateTimeString();
        }

        event(
            new RequestTracked(
                $hydratedRequest->getId(),
                $hydratedRequest->getUserId(),
                $hydratedRequest->getClientIp(),
                $hydratedRequest->getAgent()
                    ->getName(),
                $hydratedRequest->getRequestedOn(),
                $timeOfUsersPreviousRequest ?? null
            )
        );

        $this->updateUsersAnonymousRequests($hydratedRequest);

        return $hydratedRequest ?? null;
    }

    /**
     * @param $data
     * @return Url
     */
    private function manualUrlHydration($data)
    {
        $url = new Url();

        $urlDomain =
            $this->getEntityByTypeAndData('Railroad\Railtracker\Entities\UrlDomain', ['name' => $data['domain']]);
        if (!empty($data['domain'])) {
            if (empty($urlDomain)) {
                $urlDomain = new UrlDomain();
                $urlDomain->setName($data['domain']);
            }
            $url->setDomain($urlDomain);
        }

        $urlPath = $this->getEntityByTypeAndData('Railroad\Railtracker\Entities\UrlPath', ['path' => $data['path']]);
        if (!empty($data['path'])) {
            if (empty($urlPath)) {
                $urlPath = new UrlPath();
                $urlPath->setPath($data['path']);
            }
            $url->setPath($urlPath);
        }

        $urlProtocol = $this->getEntityByTypeAndData(
            'Railroad\Railtracker\Entities\UrlProtocol',
            ['protocol' => $data['protocol']]
        );
        if (!empty($data['protocol'])) {
            if (empty($urlProtocol)) {
                $urlProtocol = new UrlProtocol();
                $urlProtocol->setProtocol($data['protocol']);
            }
            $url->setProtocol($urlProtocol);
        }

        $urlQuery =
            $this->getEntityByTypeAndData('Railroad\Railtracker\Entities\UrlQuery', ['string' => $data['query']]);
        if (!empty($data['query'])) {
            if (empty($urlQuery)) {
                $urlQuery = new UrlQuery();
                $urlQuery->setString($data['query']);
            }
            $url->setQuery($urlQuery);
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

        if ($returnSerialized) {
            return [
                'id' => $urlEntity->getId(),
                'protocol' => $urlEntity->getProtocolValue(),
                'domain' => $urlEntity->getDomainValue(),
                'path' => $urlEntity->getPathValue(),
                'query' => $urlEntity->getQueryValue(),
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

        if ($routeNull ?? false) {
            $obj = new Route();
            $obj->setName('');
            $obj->setAction('');

            return $obj;
        }

        $obj = new Route();
        $routeName = substr($route->getName(), 0, 170);
        $obj->setName($routeName);
        $obj->setAction(substr($route->getActionName(), 0, 170));

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

        return $obj;
    }

    /**
     * @param Request $request
     * @return void
     */
    private function updateUsersAnonymousRequests(Request $request)
    {
        $userId = $request->getUserId();
        $cookieId = $request->getCookieId();

        if ($userId && $cookieId) {
            $this->databaseManager->table(ConfigService::$tableRequests)
                ->where(['cookie_id' => $cookieId])
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);

            $this->deleteCookieForAuthenticatedUser();
        }
    }
}