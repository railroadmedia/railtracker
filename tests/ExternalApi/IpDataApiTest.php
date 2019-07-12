<?php

namespace Railroad\Railtracker\Tests\ExternalApi;

use Railroad\Railtracker\Services\IpApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;

class IpDataApiTest extends RailtrackerTestCase
{
    /** @var IpApiSdkService */
    private $ipApiSdkService;

    protected function setup()
    {
        parent::setup();

        $this->ipApiSdkService = app(IpApiSdkService::class);
    }

    public function test_connection_made_response_received()
    {
        $data = ['foo' => 'bar'];
        $output = $this->ipApiSdkService->bulkRequest($data);
        $this->assertNotEmpty($output);
    }

    public function test_connection_made_response_successful()
    {
        $ips = [];

        $amount = 1;

        for($i = 0; $i < $amount; $i++){
            $ips[] = $this->faker->ipv4;
        }

        $output = $this->ipApiSdkService->bulkRequest($ips);

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
