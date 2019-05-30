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
    public function serializedFromHttpResponse($httpResponse)
    {
        $response = new Response();

        $response->setResponseDurationMs((microtime(true) - LARAVEL_START) * 1000);

        // Note: use Carbon rather than vanilla php because testNow is set, otherwise tests will break.
        $response->setRespondedOn(Carbon::now()->toDateTimeString());

        $response = $this->serialize($response);

        $response['status_code'] = $httpResponse->getStatusCode();

        return $response;
    }

    public function process($data, Request $request)
    {
        try {
            $hydratedResponse = $this->hydrate($data);
            $hydratedResponse->setRequest($request);

            $this->entityManager->persist($hydratedResponse);
            $this->entityManager->flush();
        } catch (Exception $exception) {
            error_log($exception);
        }

        return true;
    }

    /**
     * @param string $responseData
     * @return Response
     * @throws Exception
     */
    public function hydrate($responseData)
    {
        $response = new Response();

        $response->setResponseDurationMs($responseData['responseDurationMs']);
        $response->setRespondedOn($responseData['respondedOn']);

        $statusCode = $this->getByData(
            ResponseStatusCode::class,
            ['code' => $responseData['status_code']]
        );
        if(empty($statusCode)){
            $statusCode = new ResponseStatusCode();
            $statusCode->setCode($responseData['status_code']);
            $this->persistAndFlushEntity($statusCode);
        }
        $response->setStatusCode($statusCode);

        return $response;
    }
}
