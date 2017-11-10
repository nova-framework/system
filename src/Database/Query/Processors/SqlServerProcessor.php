<?php

namespace Nova\Database\Query\Processors;

use Nova\Database\Query\Builder;
use Nova\Database\Query\Processor;


class SqlServerProcessor extends Processor
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
            return $result->name;

        }, $results));
    }
}
