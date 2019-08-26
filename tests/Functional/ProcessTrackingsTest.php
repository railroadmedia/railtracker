<?php namespace Railroad\Railtracker\Tests\Functional;

use Railroad\Railtracker\Tests\RailtrackerTestCase;
use Carbon\Carbon;

// from ExceptionTrackerTest
// todo: organize|cull|tidy|whatever

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Railroad\Railtracker\Middleware\RailtrackerMiddleware;
use Railroad\Railtracker\Tests\Resources\Exceptions\Handler;
use Railroad\Railtracker\ValueObjects\RequestVO;

class ProcessTrackingsTest extends RailtrackerTestCase
{
    public function test_clear_already_processed_uuids()
    {
        $this->markTestIncomplete('todo');
    }

    public function test_track_response_status_code()
    {
        $request = $this->randomRequest();

        $this->sendRequest($request);
        $this->processTrackings();

        $this->assertDatabaseHas(
            config('railtracker.table_prefix') . 'requests',
            [
                'response_status_code' => 200,
            ]
        );
    }

    public function test_track_response_status_code_404()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(404);

        $this->sendRequest($request, $response);
        $this->processTrackings();

        $this->assertDatabaseHas(
            config('railtracker.table_prefix') . 'requests',
            [
                'response_status_code' => 404
            ]
        );
    }

    public function test_track_response()
    {
        $request = $this->randomRequest();
        $response = $this->createResponse(200);

        $this->sendRequest($request, $response);
        $this->processTrackings();

        $this->assertDatabaseHas(
            config('railtracker.table_prefix') . 'requests',
            [
                'id' => 1,
                'response_status_code' => 200,
                'responded_on' => Carbon::now()->format(RequestVO::$TIME_FORMAT),
            ]
        );
    }

    public function test_track_404_exception()
    {
        app()->singleton(
            ExceptionHandler::class,
            Handler::class
        );

        $kernel = app()->make(HttpKernel::class);
        $kernel->pushMiddleware(RailtrackerMiddleware::class);

        $request = $this->randomRequest();
        $kernel->handle($request);

        try {
            $this->processTrackings();
        }catch(\Exception $exception){
            $this->fail(
                'RailtrackerTestCase::processTrackings threw exception with message: "' . $exception->getMessage() . '"'
            );
        }

        $this->assertDatabaseHas(
            config('railtracker.table_prefix') . 'requests',
            [
                'id' => 1,
                'exception_class' => "Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException",
                'response_status_code' => 404
            ]
        );
    }
}
