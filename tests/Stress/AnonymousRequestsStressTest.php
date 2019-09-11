<?php

namespace Railroad\Railtracker\Tests\Stress;

use Railroad\Railtracker\Console\Commands\ProcessTrackings;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Railroad\Railtracker\Tests\Resources\IpDataApiStubDataProvider;
use Railroad\Railtracker\Tests\Resources\Models\User;
use Ramsey\Uuid\Uuid;

class AnonymousRequestsStressTest extends RailtrackerTestCase
{



    public function test_limited_amount_of_anonymous_data_updated()
    {
        $input = IpDataApiStubDataProvider::$INPUT;
        $output = IpDataApiStubDataProvider::output();

        // -------------------------------------------------------------------------------------------------------------

        $numberOfRequestsFromUser = rand(50,150);
//        $numberOfRequestsFromUser = 1000;

        $ipDataApiSdkServiceMock = $this
            ->getMockBuilder(IpDataApiSdkService::class)
            ->setMethods(['bulkRequest'])
            ->getMock();

        // first *not* empty
        $ipDataApiSdkServiceMock
            ->expects($this->at(0))
            ->method('bulkRequest')
            ->with($this->callback(function($array){
                return !empty($array);
            }))
            ->willReturn(collect($output));

        // subsequently no API calls required
        for($i = 1; $i <= $numberOfRequestsFromUser; $i++){
            $ipDataApiSdkServiceMock
                ->expects($this->at($i))
                ->method('bulkRequest')
                ->with($this->callback(function($array){
                    return empty($array);
                }))
                ->willReturn([]);
        }

        app()->instance(IpDataApiSdkService::class, $ipDataApiSdkServiceMock);

        $url = 'https://www.drumeo.com/';
        $clientIp = $input[0];

        $cookies = [ProcessTrackings::$cookieKey => Uuid::uuid4()->toString()];

        $response = $this->createResponse(200);

        $tStart = microtime(true);

        for ($i = 0; $i < $numberOfRequestsFromUser; $i++) {
            $request = $this->createRequest($this->faker->userAgent, $url, '', $clientIp, 'GET', $cookies);

            $this->sendRequest($request, $response);
            $this->processTrackings();
        }

        $secondsToProcessInitialRequests = microtime(true) - $tStart;

        $this->assertDatabaseHas(
            config('railtracker.table_prefix') . 'requests',
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
        $anonUpdateTime = $tEnd * 1000;

        $this->assertEquals(
            $numberOfRequestsFromUser + 1,
            $this->databaseManager->connection()
                ->table(config('railtracker.table_prefix') . 'requests')
                ->where('user_id', $userId)
                ->count()
        );

        $this->assertLessThan(100, $anonUpdateTime);

        dump($numberOfRequestsFromUser . ' requests from an anonymous were saved in about ' .
            round($secondsToProcessInitialRequests, 1) . ' seconds');

        dump('The user then authenticated and those previously anonymous requests were updated with the user\'s id ' .
            'in about ' . round($anonUpdateTime) . ' milliseconds.');
    }
}
