<?php

namespace Railroad\Railtracker\Tests\ExternalApi;

use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Tests\RailtrackerTestCase;

class IpDataApiTest extends RailtrackerTestCase
{
    /** @var IpDataApiSdkService */
    private $ipDataApiSdkService;

    protected function setup()
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

        $amount = 1;

        for($i = 0; $i < $amount; $i++){
            $ips[] = $this->faker->ipv4;
        }

        $output = $this->ipDataApiSdkService->bulkRequest($ips);

        foreach($output as $single){
            if(empty($single['status'])){
                $failsNoStatus[] = $single;
                continue;
            }

            if($single['status'] === 'fail'){
                continue;
            }
        }

        if(!empty($fails)){
            $this->fail(
                '$fieldCountDoesNotMatchExpected not empty: ' . var_export($fieldCountDoesNotMatchExpected ?? [], true)
            );
        }

        $this->expectNotToPerformAssertions();
    }

    // --------------- uncomment to test a specific ip ---------------

//    public function test_dump_response_for_hardcoded_ip()
//    {
//        $ips = ['108.172.176.221'];
//
//        $output = $this->ipDataApiSdkService->bulkRequest($ips);
//
//        dump($output);
//
//        $this->expectNotToPerformAssertions();
//    }
}
