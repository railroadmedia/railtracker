<?php

namespace Railroad\Railtracker\Repositories;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class TrackerRepositoryBase
{
    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * TrackerDataRepository constructor.
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * @param Collection $collection
     * @return array
     */
    protected function collectionToMultiDimensionalArray(Collection $collection)
    {
        return $collection->map(
            function ($x) {
                return (array)$x;
            }
        )->toArray();
    }
}