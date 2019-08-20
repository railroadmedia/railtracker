<?php

namespace Railroad\Railtracker\Console\Commands;

use Illuminate\Http\Request;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class RailtrackerTestingData extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'RailtrackerTestingData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create data in cache for testing ProcessTrackings command';

    const USER_AGENT_CHROME_WINDOWS_10 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like ' .
        'Gecko) Chrome/58.0.3029.110 Safari/537.36';

    /**
     * @var BatchService
     */
    private $batchService;

    /**
     * @var RequestTracker
     */
    private $requestTracker;

    /**
     * @var ExceptionTracker
     */
    private $exceptionTracker;

    /**
     * @var ResponseTracker
     */
    private $responseTracker;
    /**
     * @var \Faker\Generator
     */
    private $faker;

    public function __construct(
        BatchService $batchService,
        RequestTracker $requestTracker,
        ExceptionTracker $exceptionTracker,
        ResponseTracker $responseTracker
    )
    {
        parent::__construct();

        $this->batchService = $batchService;
        $this->requestTracker = $requestTracker;
        $this->exceptionTracker = $exceptionTracker;
        $this->responseTracker = $responseTracker;

        $this->faker = \Faker\Factory::create();
    }

    /**
     * @return true
     */

    public function handle()
    {
        $printGraphicRepresentationOrStatusCodes = false;
        $printProgressUpdates = false;

        $requestKeys = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . 'request*');
        $responseKeys = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . 'response*');

        $this->info('requestKeys count before: ' . count($requestKeys));
        $this->info('responseKeys count before: ' . count($responseKeys));

        $amountToCreate = 100;

        $startTime = time();
        $this->info('$startTime: ' . $startTime);

        $this->info('creating ' . $amountToCreate . ' request and response pairs');

        // -------------------------------------------------------------------------------------------------------------

        $poolOfDomains = [];
        $poolOfPathsCommon = [];
        $poolOfPathsRare = [];
        $poolOfUrlQueries = [];
        $poolOfUserAgents = [];
        $poolOfUrlQueryPermutations = [];
        $poolOfUrls = [];
        $poolOfIpAddresses = [];

        $numberOfDomains = 3;
        for($i = 0; $i < $numberOfDomains; $i++){
            $poolOfDomains[] = $this->faker->domainName;
        }

        $numberOfPathsCommon = 3;
        for($i = 0; $i < $numberOfPathsCommon; $i++){
            $poolOfPathsCommon[] = $this->faker->word . '-' . $this->faker->word;
        }

        $numberOfPathsRare = 20;
        for($i = 0; $i < $numberOfPathsRare; $i++){
            $poolOfPathsRare[] = $this->faker->word . '-' . $this->faker->word . '-' . $this->faker->word;
        }

        $numberOfUrlQueries = 10;
        for($i = 0; $i < $numberOfUrlQueries; $i++){
            $poolOfUrlQueries[] = $this->faker->word;
        }

        $numberOfUrlQueryPermutationsPerQuery = 4;
        foreach($poolOfUrlQueries as $urlQueryFromPool){
            for($i = 0; $i < $numberOfUrlQueryPermutationsPerQuery; $i++){
                $poolOfUrlQueryPermutations[] = '?' . $urlQueryFromPool . '=' . $i;
            }
        }

        $numberOfUrlQueryPermutationsTotal = $numberOfUrlQueries * $numberOfUrlQueryPermutationsPerQuery;

        $numberOrUrlToCreate = $numberOfDomains * $numberOfPathsCommon+$numberOfPathsRare * $numberOfUrlQueryPermutationsTotal;

        for($i = 0; $i < $numberOrUrlToCreate; $i++){
            $domainToUse = $poolOfDomains[rand(0,$numberOfDomains-1)];

            $pathToUse = $this->faker->randomElement($poolOfPathsCommon);
            if(rand(0, 1)){ // half the time use a rare one
                $pathToUse = $this->faker->randomElement($poolOfPathsRare);
            }

            $url = 'http://www.' . $domainToUse . '/' . $pathToUse;

            $percentageOrTime = 20;
            $weight = $percentageOrTime * 0.01;

            if($this->faker->optional($weight)->boolean){
                $urlQuery = $poolOfUrlQueryPermutations[rand(0, $numberOfUrlQueryPermutationsTotal-1)];
                $url = $url . '?' . $urlQuery.'';
            }

            $poolOfUrls[] = $url;
        }

        $numberOfUserAgents = 10;
        for($i = 0; $i < $numberOfUserAgents; $i++){
            $poolOfUserAgents[] = $this->faker->userAgent;
        }

        $numberOfIpAddresses = 10;
        for($i = 0; $i < $numberOfIpAddresses; $i++){
            $poolOfIpAddresses[] = $this->faker->ipv4;
        }

        // -------------------------------------------------------------------------------------------------------------

        for($i = 0; $i < $amountToCreate; $i++){
            \Railroad\Railtracker\ValueObjects\RequestVO::$UUID = $this->faker->uuid;

            $userAgent = $poolOfUserAgents[rand(0, $numberOfUserAgents-1)];;
            $url = $this->faker->randomElement($poolOfUrls);
            $referer = $this->faker->randomElement($poolOfUrls);
            $clientIp = $this->faker->randomElement($poolOfIpAddresses);
            $method = $this->faker->optional(0.8, 'POST')->passthrough('GET');
            $cookies = [];

            $request = $this->createRequest(
                $userAgent,
                $url,
                $referer,
                $clientIp,
                $method,
                $cookies
            );

            $requestSerialized = $this->requestTracker->serializedFromHttpRequest($request);

            $this->batchService->addToBatch($requestSerialized, $requestSerialized['uuid']);

            $randomNumber = rand(1, 10);

            if($randomNumber === 1){
                $statusCode = '404';
            }elseif($randomNumber === 2){
                $statusCode = '500';
            }else{
                $statusCode = '200';
            }

            if($printGraphicRepresentationOrStatusCodes){
                if($statusCode === '200'){
                    $this->info('200-------------------------------------------------------------------');
                }elseif($statusCode === '404'){
                    $this->info('------------------------------404-------------------------------------');
                }else{
                    $this->info('-------------------------------------------------------------------500');
                }
            }

            $response = $this->createResponse($statusCode);

            $responseData = $this->responseTracker->serializedFromHttpResponse($response);
            $this->batchService->addToBatch($responseData, \Railroad\Railtracker\ValueObjects\RequestVO::$UUID);

            // $this->info(\Railroad\Railtracker\ValueObjects\RequestVO::$UUID);

            if($printProgressUpdates){
                if($i % 100 === 0 && $i > 0){
                    $this->info('One-hundred more created! (total: ' . $i . ')');
                }
                if($i % 1000 === 0 && $i > 0){
                    $this->info($i . ' TOTAL!');
                }
                if($i === 1337){
                    $this->info('ELITE LEVEL REACHED!!!');
                }
            }
        }

        $this->info('Complete');

        $requestKeys = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . 'request*');
        $responseKeys = $this->batchService->cache()->keys($this->batchService->batchKeyPrefix . 'response*');

        $timeTaken = time() - $startTime;
        $this->info('total time in seconds: ' .  $timeTaken);

        $timeTakenMinutesRoundedDown = floor($timeTaken/60);

        $secondsLeft = $timeTaken % 60;

        $this->info('total time in minutes: ' .  $timeTakenMinutesRoundedDown . ':' . $secondsLeft);

        $this->info('requestKeys count after: ' . count($requestKeys));
        $this->info('responseKeys count after: ' . count($responseKeys));

        return true;
    }

    // ----------------- ↓ copied from RailtrackerTestCase ---------------------------------
    // ----------------- ↓ copied from RailtrackerTestCase ---------------------------------
    // ----------------- ↓ copied from RailtrackerTestCase ---------------------------------

    /**
     * @return int
     */
    public function createAndLogInNewUser()
    {
        $userId =
            $this->databaseManager->connection()
                ->query()
                ->from('users')
                ->insertGetId(
                    ['email' => $this->faker->email]
                );

        $this->authManager->guard()
            ->onceUsingId($userId);

        return $userId;
    }

    /**
     * @param string $statusCode
     * @return \Illuminate\Http\Response
     */
    public function createResponse($statusCode)
    {
        return response()->json([true], $statusCode);
    }

    /**
     * @param string $userAgent
     * @param string $url
     * @param string $referer
     * @param string $clientIp
     * @param string $method
     * @param array $cookies
     * @return Request
     */
    public function createRequest(
        $userAgent = self::USER_AGENT_CHROME_WINDOWS_10,
        $url = 'https://www.testing.com/?test=1',
        $referer = 'http://www.referer-testing.com/?test=2',
        $clientIp = '183.22.98.51',
        $method = 'GET',
        $cookies = []
    ) {
        return Request::create(
            $url,
            $method,
            [],
            $cookies,
            [],
            [
                'SCRIPT_NAME' => parse_url($url)['path'] ?? '',
                'REQUEST_URI' => parse_url($url)['path'] ?? '',
                'QUERY_STRING' => parse_url($url)['query'] ?? '',
                'REQUEST_METHOD' => 'GET',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'GATEWAY_INTERFACE' => 'CGI/1.1',
                'REMOTE_PORT' => '62517',
                'SCRIPT_FILENAME' => '/var/www/index.php',
                'SERVER_ADMIN' => '[no address given]',
                'CONTEXT_DOCUMENT_ROOT' => '/var/www/',
                'CONTEXT_PREFIX' => '',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => '/var/www/',
                'REMOTE_ADDR' => $clientIp,
                'HTTP_X_FORWARDED_FOR' => $clientIp,
                'SERVER_PORT' => '80',
                'SERVER_ADDR' => '172.21.0.7',
                'SERVER_NAME' => parse_url($url)['host'],
                'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_USER_AGENT' => $userAgent,
                'HTTP_REFERER' => $referer,
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => parse_url($url)['host'],
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' => ['test=1'],
            ]
        );
    }

    /**
     * @param string $userAgent
     * @param string $url
     * @param string $referer
     * @param string $clientIp
     * @param string $method
     * @param array $cookies
     * @return Request
     */
    public function createRequestThatThrowsException(
        $userAgent = self::USER_AGENT_CHROME_WINDOWS_10,
        $url = 'https://www.testing.com/?test=1',
        $referer = 'http://www.referer-testing.com/?test=2',
        $clientIp = '183.22.98.51',
        $method = 'GET',
        $cookies = []
    ) {
        return Request::create(
            $url,
            $method,
            [],
            $cookies,
            [],
            [
                'SCRIPT_NAME' => parse_url($url)['path'] ?? '',
                'REQUEST_URI' => parse_url($url)['path'] ?? '',
                'QUERY_STRING' => parse_url($url)['query'] ?? '',
                'REQUEST_METHOD' => 'GET',
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'GATEWAY_INTERFACE' => 'CGI/1.1',
                'REMOTE_PORT' => '62517',
                'SCRIPT_FILENAME' => '/var/www/index.php',
                'SERVER_ADMIN' => '[no address given]',
                'CONTEXT_DOCUMENT_ROOT' => '/var/www/',
                'CONTEXT_PREFIX' => '',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => '/var/www/',
                'REMOTE_ADDR' => $clientIp,
                'HTTP_X_FORWARDED_FOR' => $clientIp,
                'SERVER_PORT' => '80',
                'SERVER_ADDR' => '172.21.0.7',
                'SERVER_NAME' => parse_url($url)['host'],
                'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_USER_AGENT' => $userAgent,
                'HTTP_REFERER' => $referer,
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => parse_url($url)['host'],
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' => ['test=1'],
            ]
        );
    }

    /**
     * @return Request
     */
    public function randomRequest()
    {
        $method = $this->faker->randomElement(['GET', 'POST']);

        $protocol = $this->faker->randomElement(['HTTP', 'HTTPS']);

        $domain = $this->faker->randomElement(
            [$this->faker->domainName, $this->faker->domainWord . '.' . $this->faker->domainName]
        );

        $path = $this->faker->randomElement(
            [
                '',
                '/',
                '/' . $this->faker->word,
                '/' . $this->faker->word . '/',
                '/' . $this->faker->word . '/' . rand() . '/' . $this->faker->password,
                '/' . implode('/', range(1000000, 1000100)),
            ]
        );

        $queryString = $this->faker->randomElement(
            [
                '',
                '?',
                '?' . $this->faker->word . '=' . rand(),
                '?' . $this->faker->word . '=' . rand() . '&' . $this->faker->word . '=' . rand(),
                '?' . implode('&' . rand() . '=', range(2000000, 2000100)),
            ]
        );

        $clientIp = $this->faker->randomElement([$this->faker->ipv4, $this->faker->ipv6]);

        if ($this->faker->boolean()) {
            $routeName = $this->faker->word . '.' . $this->faker->word . '.' . $this->faker->word;
            $routeAction = ucwords($this->faker->word) . ucwords($this->faker->word) . '@' . $this->faker->word;

            $route = $this->router->get(
                $path,
                [
                    'as' => $routeName,
                    'uses' => $routeAction,
                ]
            );
        }

        if ($this->faker->boolean()) {
            $userId = $this->createAndLogInNewUser();
        }

        $request = Request::create(
            $this->faker->url,
            $method,
            [],
            [],
            [],
            [
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => $path . $queryString,
                'QUERY_STRING' => $queryString,
                'REQUEST_METHOD' => $method,
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'GATEWAY_INTERFACE' => 'CGI/1.1',
                'REMOTE_PORT' => '62517',
                'SCRIPT_FILENAME' => '/var/www/index.php',
                'SERVER_ADMIN' => '[no address given]',
                'CONTEXT_DOCUMENT_ROOT' => '/var/www/',
                'CONTEXT_PREFIX' => '',
                'REQUEST_SCHEME' => 'http',
                'DOCUMENT_ROOT' => '/var/www/',
                'REMOTE_ADDR' => $clientIp,
                'HTTP_X_FORWARDED_FOR' => $clientIp,
                'SERVER_PORT' => '80',
                'SERVER_ADDR' => '172.21.0.7',
                'SERVER_NAME' => $protocol,
                'SERVER_SOFTWARE' => 'Apache/2.4.18 (Ubuntu)',
                'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en-US;q=0.8,en;q=0.6',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'HTTP_USER_AGENT' => $this->faker->userAgent,
                'HTTP_REFERER' => $this->faker->randomElement([$this->faker->url, '']),
                'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
                'HTTP_CONNECTION' => 'keep-alive',
                'HTTP_HOST' => $domain,
                'FCGI_ROLE' => 'RESPONDER',
                'PHP_SELF' => '/index.php',
                'REQUEST_TIME_FLOAT' => 1496790020.5194,
                'REQUEST_TIME' => 1496790020,
                'argv' => ['test=1'],
            ]
        );

        if (isset($route)) {
            $request->setRouteResolver(
                function () use ($route) {
                    return $route;
                }
            );
        }

        if (isset($userId)) {
            $request->setUserResolver(
                function () use ($userId) {
                    return User::query()
                        ->find($userId);
                }
            );
        }

        return $request;
    }

    // ----------------- ↑ copied from RailtrackerTestCase ---------------------------------
    // ----------------- ↑ copied from RailtrackerTestCase ---------------------------------
    // ----------------- ↑ copied from RailtrackerTestCase ---------------------------------

}
