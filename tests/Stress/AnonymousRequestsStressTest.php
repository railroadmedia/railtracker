<?php

namespace Railroad\Railtracker\Tests\Stress;

use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Tests\Resources\Models\User;
use Ramsey\Uuid\Uuid;

class AnonymousRequestsStressTest extends RailtrackerTestCase
{
    public function test_limited_amount_of_anonymous_data_updated()
    {
        $this->markTestIncomplete(
            'Broken. Also needs to incorporate IpDataApiStubDataProvider rather than ping IpData API.'
        );

        $numberOfRequestsFromUser = 50;

        $url = 'https://www.drumeo.com/';
        $clientIp = '192.562.33.42';

        $cookies = [ProcessTrackings::$cookieKey => Uuid::uuid4()->toString()];

        $response = $this->createResponse(200);

        for ($i = 0; $i < $numberOfRequestsFromUser; $i++) {
            $request = $this->createRequest($this->faker->userAgent, $url, '', $clientIp, 'GET', $cookies);

            $this->sendRequest($request, $response);
            $this->processTrackings();
        }

        $this->assertDatabaseHas(
            ConfigService::$tableRequests,
            [
                'user_id' => null,
                'cookie_id' => $cookies[ProcessTrackings::$cookieKey],
            ]
        );

        $userId = $this->createAndLogInNewUser();

        $request = $this->createRequest($this->faker->userAgent, $url, '', $clientIp, 'GET', $cookies);
        $request->setUserResolver(
            function () use ($userId) {
                return User::query()->find($userId);
            }
        );

        $tStart = microtime(true);

        $this->sendRequest($request, $response);
        $this->processTrackings();

        $tEnd = microtime(true) - $tStart;

        $this->assertEquals(
            $numberOfRequestsFromUser + 1,
            $this->databaseManager->connection()
                ->table(ConfigService::$tableRequests)
                ->where('user_id', $userId)
                ->count()
        );

        $this->assertLessThan(1, $tEnd);
    }
}
