<?php

namespace Railroad\Railtracker\Tests\Stress;

use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Tests\Resources\Models\User;
use Railroad\Railtracker\Trackers\RequestTracker;
use Ramsey\Uuid\Uuid;

class AnonymousRequestsStressTest extends RailtrackerTestCase
{
    public function test_limited_amount_of_anonymous_data_updated()
    {
        $url = 'https://www.drumeo.com/';
        $clientIp = '192.562.33.42';

        $cookies = [RequestTracker::$cookieKey => Uuid::uuid4()->toString()];

        for ($i = 0; $i < (RequestTracker::$maxAnonymousRowsUpdated + 50); $i++) {
            $request = $this->createRequest(
                $this->faker->userAgent,
                $url,
                '',
                $clientIp,
                'GET',
                $cookies
            );

            $middleware = $this->app->make(RailtrackerMiddleware::class);

            $middleware->handle(
                $request,
                function () {
                }
            );
        }

        // other random requests
        for ($i = 0; $i < 20; $i++) {
            $request = $this->createRequest($this->faker->userAgent, $url, '', $clientIp, 'GET', []);

            $middleware = $this->app->make(RailtrackerMiddleware::class);

            $middleware->handle(
                $request,
                function () {
                }
            );
        }

        $this->assertDatabaseHas(
            ConfigService::$tableRequests,
            [
                'user_id' => null,
                'cookie_id' => $cookies[RequestTracker::$cookieKey],
            ]
        );

        $userId = $this->createAndLogInNewUser();

        $request = $this->createRequest(
            $this->faker->userAgent,
            $url,
            '',
            $clientIp,
            'GET',
            $cookies
        );

        $request->setUserResolver(
            function () use ($userId) {
                return User::query()->find($userId);
            }
        );

        $middleware = $this->app->make(RailtrackerMiddleware::class);

        $middleware->handle(
            $request,
            function () {
            }
        );

        $this->assertEquals(
            RequestTracker::$maxAnonymousRowsUpdated + 1,
            $this->databaseManager->connection()
                ->table(ConfigService::$tableRequests)
                ->where('user_id', $userId)
                ->count()
        );
    }

}