<?php

namespace Railroad\Railtracker\Loggers;

use Doctrine\DBAL\Logging\SQLLogger;

class RailtrackerQueryLogger implements SQLLogger
{
    /**
     * @var array
     */
    protected $queries;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->queries[] = $sql;

        if (config('railtracker.enable_query_log_dumper', false) == true) {
            echo "Query count: " . $this->count() . "\n";

            echo $sql . PHP_EOL;

            if ($params) {
                var_dump($params);
            }

            if (!$types) {
                return;
            }

            var_dump($types);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }

    public function reset()
    {
        $this->queries = [];
    }

    public function count()
    {
        return count($this->queries);
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}