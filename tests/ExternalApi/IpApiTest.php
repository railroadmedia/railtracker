<?php

namespace Railroad\Railtracker\Tests\ExternalApi;

use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;

class IpApiTest extends RailtrackerTestCase
{
    /** @var IpDataApiSdkService */
    private $ipDataApiSdkService;

    protected function setUp(): void
    {
        parent::setup();

        $this->ipDataApiSdkService = app(IpDataApiSdkService::class);
    }

    public function test_connection_made_response_received()
    {
        $data = ['foo' => 'bar'];
        $output = $this->ipDataApiSdkService->bulkRequest($data);
        $this->assertNotEmpty($output);
    }

    public function test_connection_made_response_successful()
    {
        $ips = [];

        $amount = 100;

        for($i = 0; $i < $amount; $i++){
            $ips[] = $this->faker->ipv4;
        }

        $output = $this->ipDataApiSdkService->bulkRequest($ips);

        $fields = explode(',', config('railtracker.ip-api.default-fields'));

        foreach($output as $single){
            if(empty($single['status'])){
                $failsNoStatus[] = $single;
                continue;
            }

            if($single['status'] === 'fail'){
                continue;
            }

            if(count($single) !== count($fields)){
                $fieldCountDoesNotMatchExpected[] = $single;
            }
        }

        if(!empty($fails)){
            $this->fail(
                '$fieldCountDoesNotMatchExpected not empty: ' . var_export($fieldCountDoesNotMatchExpected ?? [], true)
            );
        }

        $this->expectNotToPerformAssertions();
    }
}
