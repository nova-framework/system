<?php

namespace Nova\Database\Query\Processors;

use Nova\Database\Query\Processor;


class SQLiteProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_values(array_map(function ($result)
        {
            $result = (object) $result;

            return $result->name;

        }, $results));
    }
}
