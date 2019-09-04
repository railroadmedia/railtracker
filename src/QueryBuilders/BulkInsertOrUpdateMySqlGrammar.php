<?php

namespace Railroad\Railtracker\QueryBuilders;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;

/**
 * Class CustomBuilder
 *
 * https://gist.github.com/tonila/26f6a82c4dbe63d93b22ac67eaee2d6d
 *
 * @package Railroad\Railtracker\QueryBuilders
 */
class BulkInsertOrUpdateMySqlGrammar extends MySqlGrammar
{
    public function compileInsertOrUpdate(Builder $query, array $values)
    {
        $table = $this->wrapTable($query->from);

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $cols = array_keys(reset($values));
        $columns = $this->columnize(array_keys(reset($values)));

        $parameters = collect($values)->map(
            function ($record) {
                return '(' . $this->parameterize($record) . ')';
            }
        )->implode(', ');

        $updates = implode(
            ',',
            array_map(
                function ($value) {
                    return "`$value`=values(`$value`)";
                },
                $cols
            )
        );

        return "insert into $table ($columns) values $parameters on duplicate key update $updates";
    }
}