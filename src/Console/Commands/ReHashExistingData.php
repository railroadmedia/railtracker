<?php

namespace Railroad\Railtracker\Console\Commands;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Managers\RailtrackerEntityManager;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\Trackers\RequestTracker;
use Railroad\Railtracker\Trackers\ResponseTracker;

class ReHashExistingData extends \Illuminate\Console\Command
{
    /**
     * @var string
     */
    protected $name = 'ReHashExistingData';

    /**
     * @var string
     */
    protected $description = 'ReHashExistingData';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * ReHashExistingData constructor.
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
        $this->databaseManager->statement('SET autocommit=0;');
        $this->databaseManager->statement('SET unique_checks=0;');
        $this->databaseManager->statement('SET foreign_key_checks=0;');

        // exceptions
        $this->info(
            'Starting exceptions, total rows: ' .
            ($this->databaseManager->table('railtracker_exceptions')
                ->count())
        );

        $this->databaseManager->table('railtracker_exceptions')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {

                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->code,
                                            $row->line,
                                            $row->exception_class,
                                            $row->file,
                                            $row->message,
                                            $row->trace,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_exceptions')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);

                        }
                    );
                }
            );

        // todo: geoip

        // request agents
        $this->info(
            'Starting request agents, total rows: ' .
            ($this->databaseManager->table('railtracker_request_agents')
                ->count())
        );

        $this->databaseManager->table('railtracker_request_agents')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(implode('-', [$row->name, $row->browser, $row->browser_version]));

                                $this->databaseManager->table('railtracker_request_agents')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // request devices
        $this->info(
            'Starting request devices, total rows: ' .
            ($this->databaseManager->table('railtracker_request_devices')
                ->count())
        );

        $this->databaseManager->table('railtracker_request_devices')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->kind,
                                            $row->model,
                                            $row->platform,
                                            $row->platform_version,
                                            $row->is_mobile
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_request_devices')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // request languages
        $this->info(
            'Starting request languages, total rows: ' .
            ($this->databaseManager->table('railtracker_request_languages')
                ->count())
        );

        $this->databaseManager->table('railtracker_request_languages')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->preference,
                                            $row->language_range,
                                        ]
                                    )
                                );;

                                $this->databaseManager->table('railtracker_request_languages')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // request methods
        $this->info(
            'Starting request methods, total rows: ' .
            ($this->databaseManager->table('railtracker_request_methods')
                ->count())
        );

        $this->databaseManager->table('railtracker_request_methods')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->method,
                                        ]
                                    )
                                );;

                                $this->databaseManager->table('railtracker_request_methods')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // response status codes
        $this->info(
            'Starting response status codes, total rows: ' .
            ($this->databaseManager->table('railtracker_response_status_codes')
                ->count())
        );

        $this->databaseManager->table('railtracker_response_status_codes')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(implode(['-', $row->code]));

                                $this->databaseManager->table('railtracker_response_status_codes')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // routes
        $this->info(
            'Starting routes, total rows: ' .
            ($this->databaseManager->table('railtracker_routes')
                ->count())
        );

        $this->databaseManager->table('railtracker_routes')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->name,
                                            $row->action,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_routes')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // urls
        $this->info(
            'Starting urls, total rows: ' .
            ($this->databaseManager->table('railtracker_urls')
                ->count())
        );

        $this->databaseManager->table('railtracker_urls')
            ->select(
                [
                    'railtracker_urls.id',
                    'railtracker_url_domains.name',
                    'railtracker_url_protocols.protocol',
                    'railtracker_url_paths.path',
                    'railtracker_url_queries.string'
                ]
            )
            ->join('railtracker_url_domains', 'railtracker_url_domains.id', '=', 'railtracker_urls.domain_id')
            ->join('railtracker_url_protocols', 'railtracker_url_protocols.id', '=', 'railtracker_urls.protocol_id')
            ->leftJoin('railtracker_url_paths', 'railtracker_url_paths.id', '=', 'railtracker_urls.path_id')
            ->leftJoin('railtracker_url_queries', 'railtracker_url_queries.id', '=', 'railtracker_urls.query_id')
            ->orderBy('railtracker_urls.id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->protocol,
                                            $row->name,
                                            $row->path,
                                            $row->string,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_urls')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // url domains
        $this->info(
            'Starting railtracker_url_domains, total rows: ' .
            ($this->databaseManager->table('railtracker_url_domains')
                ->count())
        );

        $this->databaseManager->table('railtracker_url_domains')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->name,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_url_domains')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // url paths
        $this->info(
            'Starting railtracker_url_paths, total rows: ' .
            ($this->databaseManager->table('railtracker_url_paths')
                ->count())
        );

        $this->databaseManager->table('railtracker_url_paths')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->path,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_url_paths')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // url protocols
        $this->info(
            'Starting railtracker_url_protocols, total rows: ' .
            ($this->databaseManager->table('railtracker_url_protocols')
                ->count())
        );

        $this->databaseManager->table('railtracker_url_protocols')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->protocol,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_url_protocols')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        // url protocols
        $this->info(
            'Starting railtracker_url_queries, total rows: ' .
            ($this->databaseManager->table('railtracker_url_queries')
                ->count())
        );

        $this->databaseManager->table('railtracker_url_queries')
            ->orderBy('id')
            ->chunk(
                10000,
                function (Collection $rows) {
                    $this->databaseManager->transaction(
                        function () use ($rows) {

                            foreach ($rows as $row) {
                                $hash = md5(
                                    implode(
                                        '-',
                                        [
                                            $row->string,
                                        ]
                                    )
                                );

                                $this->databaseManager->table('railtracker_url_queries')
                                    ->where('id', $row->id)
                                    ->update(['hash' => $hash]);
                            }

                            $this->info('Done ' . $rows->last()->id);
                        }
                    );

                }
            );

        $this->databaseManager->statement('SET autocommit=1;');
        $this->databaseManager->statement('SET unique_checks=1;');
        $this->databaseManager->statement('SET foreign_key_checks=1;');

        return true;
    }
}
