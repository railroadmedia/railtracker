<?php

namespace Railroad\Railtracker\QueryBuilders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

/**
 * Class CustomBuilder
 *
 * https://gist.github.com/tonila/26f6a82c4dbe63d93b22ac67eaee2d6d
 *
 * @package Railroad\Railtracker\QueryBuilders
 */
class BulkInsertOrUpdateBuilder extends Builder
{
    public function insertOrUpdate(array $values)
    {
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        else {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        return $this->connection->insert(
            $this->grammar->compileInsertOrUpdate($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }
}