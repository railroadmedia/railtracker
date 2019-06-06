<?php

namespace Railroad\Railtracker\Tests\Integration\Commands;

use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\RailtrackerTestCase;

class ProcessTrackingsTest extends RailtrackerTestCase
{
    /**
     * @var RailtrackerMiddleware
     */
    public $railtrackerMiddleware;

    protected function setUp()
    {
        parent::setUp();

        $this->railtrackerMiddleware = resolve(RailtrackerMiddleware::class);
    }

    public function test_track_response_status_code()
    {
        $testSize = 100;

        $tStart = microtime(true);

        for ($i = 0; $i < $testSize; $i++) {

            $request = $this->createRequest();

            $response = $this->createResponse(200);

            $next = function () use ($response) {
                return $response;
            };

            $this->railtrackerMiddleware->handle($request, $next);
        }

        $tEnd = microtime(true);

        echo 'Time to store ' . $testSize . ' requests in redis: ' . ($tEnd - $tStart) . ' seconds.' . "\n";

        echo 'Queries ran to store ' . $testSize . ' requests in redis: ' . $this->queryLogger->count() . "\n";

        $tStart = microtime(true);

        $this->processTrackings();

        $tEnd = microtime(true);

        echo 'Time to process ' .
            $testSize .
            ' requests and responses and store them in a fresh database: ' .
            ($tEnd - $tStart) .
            ' seconds.' .
            "\n";

        echo 'Queries ran to store ' . $testSize . ' requests in database: ' . $this->queryLogger->count() . "\n";

        $this->expectNotToPerformAssertions();
    }
}