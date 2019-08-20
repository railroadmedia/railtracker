<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Routing\Router;
use Railroad\Doctrine\Serializers\BasicEntitySerializer;
use Railroad\Railtracker\Entities\Request;
use Railroad\Railtracker\Entities\Response;
use Railroad\Railtracker\Entities\ResponseStatusCode;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResponseTracker extends TrackerBase
{
    /**
     * @var int|null
     */
    public static $lastCreatedResponseId;
    /**
     * @var BasicEntitySerializer
     */
    protected $basicEntitySerializer;
    /**
     * @var RailtrackerEntityManager
     */
    protected $entityManager;
    /**
     * @var RequestTracker
     */
    private $requestTracker;
    /**
     * @var RequestTracker
     */
    protected $batchService;

    public function __construct(
        DatabaseManager $databaseManager,
        Router $router,
        CookieJar $cookieJar,
        Repository $cache = null,
        BatchService $batchService,
        BasicEntitySerializer $basicEntitySerializer,
        RailtrackerEntityManager $entityManager,
        RequestTracker $requestTracker
    ){
        parent::__construct(
            $databaseManager,
            $router,
            $cookieJar,
            $cache,
            $batchService,
            $basicEntitySerializer,
            $entityManager
        );

        $this->databaseManager = $databaseManager;
        $this->router = $router;
        $this->cookieJar = $cookieJar;
        $this->cache = $cache;
        $this->batchService = $batchService;
        $this->basicEntitySerializer = $basicEntitySerializer;
        $this->entityManager = $entityManager;
        $this->requestTracker = $requestTracker;
    }

    /**
     * @param HttpResponse $httpResponse
     * @return array
     */
    public function serializedFromHttpResponse($httpResponse) // todo: any reason to not typehint with "HttpResponse"? If not, do it.
    {
        $response = new Response();

        $response->setResponseDurationMs((microtime(true) - LARAVEL_START) * 1000);

        // Note: use Carbon rather than vanilla php because testNow is set, otherwise tests will break.
        $response->setRespondedOn(Carbon::now()->toDateTimeString());

        $response = $this->serialize($response);

        $response[ResponseStatusCode::$KEY] = $this->serialize($this->fillResponseStatusCode($httpResponse));
        $response['type'] = 'response';
        $response['uuid'] = \Railroad\Railtracker\ValueObjects\RequestVO::$UUID;

        return $response;
    }

    /**
     * @param HttpResponse $httpResponse
     * @return ResponseStatusCode
     */
    private function fillResponseStatusCode(HttpResponse $httpResponse)
    {
        $statusCode = $httpResponse->getStatusCode();

        $responseStatusCode = new ResponseStatusCode();
        $responseStatusCode->setCode($statusCode);
        $responseStatusCode->setHash();

        return $responseStatusCode;
    }

    /**
     * @param $data
     * @param Request $request
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function process($data, Request $request)
    {
        $hydratedResponse = $this->hydrate($data);
        $hydratedResponse->setRequest($request);

        $this->entityManager->persist($hydratedResponse);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param array $responseData
     * @return Response
     * @throws Exception
     */
    public function hydrate($responseData)
    {
        if (empty($responseData) ||
            empty($responseData[ResponseStatusCode::$KEY]) ||
            empty($responseData['responseDurationMs']) ||
            empty($responseData['respondedOn'])) {
            throw new Exception('Response data is empty from the cache, request uuid: ' . \Railroad\Railtracker\ValueObjects\RequestVO::$UUID);
        }

        $response = new Response();

        $response->setResponseDurationMs($responseData['responseDurationMs']);
        $response->setRespondedOn($responseData['respondedOn']);

        $statusCode = $this->getByData(
            ResponseStatusCode::class,
            ['code' => $responseData[ResponseStatusCode::$KEY]]
        );
        if(empty($statusCode)){
            $statusCode = new ResponseStatusCode();
            $statusCode->setCode($responseData[ResponseStatusCode::$KEY]);
            $this->persistAndFlushEntity($statusCode);
        }
        $response->setStatusCode($statusCode);

        return $response;
    }
}
