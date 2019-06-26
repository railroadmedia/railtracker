<?php

namespace Railroad\Railtracker\Console\Commands;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\ConfigService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class FixDuplicates extends \Illuminate\Console\Command
{
    /**
     * @var string
     */
    protected $name = 'FixDuplicates';

    /**
     * @var string
     */
    protected $description = 'FixDuplicates';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * FixDuplicates constructor.
     * @param BatchService $batchService
     * @param RequestTracker $requestTracker
     * @param ExceptionTracker $exceptionTracker
     * @param ResponseTracker $responseTracker
     * @param RailtrackerEntityManager $entityManager
     */
    public function __construct(
        DatabaseManager $databaseManager
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
    }

    /**
     * return true
     */
    public function handle()
    {
        $this->databaseManager->disableQueryLog();

        // exceptions
        $duplicatesGrouped =
            $this->databaseManager->table('railtracker_exceptions')
                ->groupBy(['code', 'line', 'exception_class', 'file', 'message', 'trace'])
                ->having($this->databaseManager->raw('COUNT(*)'), '>', 1)
                ->get();

        $this->info('railtracker_exceptions duplicates found: ' . $duplicatesGrouped->count());

        foreach ($duplicatesGrouped as $duplicate) {
            // fix the associated columns on other tables
            $duplicateRowIds =
                $this->databaseManager->table('railtracker_exceptions')
                    ->where(
                        [
                            'code' => $duplicate->code,
                            'line' => $duplicate->line,
                            'exception_class' => $duplicate->exception_class,
                            'file' => $duplicate->file,
                            'message' => $duplicate->message,
                            'trace' => $duplicate->trace
                        ]
                    )
                    ->where('id', '!=', $duplicate->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();

            $this->databaseManager->table('railtracker_request_exceptions')
                ->whereIn('exception_id', $duplicateRowIds)
                ->update(['exception_id' => $duplicate->id]);

            // delete duplicates
            $this->databaseManager->table('railtracker_exceptions')
                ->where(
                    [
                        'code' => $duplicate->code,
                        'line' => $duplicate->line,
                        'exception_class' => $duplicate->exception_class,
                        'file' => $duplicate->file,
                        'message' => $duplicate->message,
                        'trace' => $duplicate->trace
                    ]
                )
                ->where('id', '!=', $duplicate->id)
                ->delete();
        }

        // response status codes
        $duplicatesGrouped =
            $this->databaseManager->table('railtracker_response_status_codes')
                ->groupBy(['code',])
                ->having($this->databaseManager->raw('COUNT(*)'), '>', 1)
                ->get();

        $this->info('railtracker_response_status_codes duplicates found: ' . $duplicatesGrouped->count());

        foreach ($duplicatesGrouped as $duplicate) {
            // fix the associated columns on other tables
            $duplicateRowIds =
                $this->databaseManager->table('railtracker_response_status_codes')
                    ->where(
                        [
                            'code' => $duplicate->code,
                        ]
                    )
                    ->where('id', '!=', $duplicate->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();

            $this->databaseManager->table('railtracker_responses')
                ->whereIn('status_code_id', $duplicateRowIds)
                ->update(['status_code_id' => $duplicate->id]);

            // delete duplicates
            $this->databaseManager->table('railtracker_response_status_codes')
                ->where(
                    [
                        'code' => $duplicate->code,
                    ]
                )
                ->where('id', '!=', $duplicate->id)
                ->delete();
        }

        // url queries

        // we need to remove the unique index temporarily before fixing these otherwise updating the url table causes
        // unique constraint failures which are very hard to fix without just removing the constraint and re-adding it
        // after the urls table dupes have been fixed
        Schema::table(
            ConfigService::$tableUrls,
            function (Blueprint $table) {
                $table->dropIndex('railtracker_urls_protocol_id_domain_id_path_id_query_id_unique');
            }
        );

        $duplicatesGrouped =
            $this->databaseManager->table('railtracker_url_queries')
                ->groupBy(['string',])
                ->having($this->databaseManager->raw('COUNT(*)'), '>', 1)
                ->get();

        $this->info('railtracker_url_queries duplicates found: ' . $duplicatesGrouped->count());

        foreach ($duplicatesGrouped as $duplicate) {
            // fix the associated columns on other tables
            $duplicateRowIds =
                $this->databaseManager->table('railtracker_url_queries')
                    ->where(
                        [
                            'string' => $duplicate->string,
                        ]
                    )
                    ->where('id', '!=', $duplicate->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();

            foreach ($duplicateRowIds as $duplicateRowId) {
                $this->databaseManager->table('railtracker_urls')
                    ->where('query_id', $duplicateRowId)
                    ->update(['query_id' => $duplicate->id]);
            }

            // delete duplicates
            $this->databaseManager->table('railtracker_url_queries')
                ->where(
                    [
                        'string' => $duplicate->string,
                    ]
                )
                ->where('id', '!=', $duplicate->id)
                ->delete();
        }

        // urls
        $duplicatesGrouped =
            $this->databaseManager->table('railtracker_urls')
                ->groupBy(['protocol_id', 'domain_id', 'path_id', 'query_id'])
                ->having($this->databaseManager->raw('COUNT(*)'), '>', 1)
                ->get();

        $this->info('railtracker_urls duplicates found: ' . $duplicatesGrouped->count());

        foreach ($duplicatesGrouped as $duplicate) {
            // fix the associated columns on other tables
            $duplicateRowIds =
                $this->databaseManager->table('railtracker_urls')
                    ->where(
                        [
                            'protocol_id' => $duplicate->protocol_id,
                            'domain_id' => $duplicate->domain_id,
                            'path_id' => $duplicate->path_id,
                            'query_id' => $duplicate->query_id,
                        ]
                    )
                    ->where('id', '!=', $duplicate->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();

            $this->databaseManager->table('railtracker_requests')
                ->whereIn('url_id', $duplicateRowIds)
                ->update(['url_id' => $duplicate->id]);

            // delete duplicates
            $this->databaseManager->table('railtracker_urls')
                ->where(
                    [
                        'protocol_id' => $duplicate->protocol_id,
                        'domain_id' => $duplicate->domain_id,
                        'path_id' => $duplicate->path_id,
                        'query_id' => $duplicate->query_id,
                    ]
                )
                ->where('id', '!=', $duplicate->id)
                ->delete();
        }

        Schema::table(
            ConfigService::$tableUrls,
            function (Blueprint $table) {
                $table->unique(
                    ['protocol_id', 'domain_id', 'path_id', 'query_id'],
                    'railtracker_urls_protocol_id_domain_id_path_id_query_id_unique'
                );
            }
        );

        return true;
    }
}
