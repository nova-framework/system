<?php

namespace Nova\Database\Connection;

use Nova\Database\Connection;
use Nova\Database\Query\Processors\PostgresProcessor;
use Nova\Database\Query\Grammars\PostgresGrammar as QueryGrammar;
use Nova\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use Nova\Database\Query\Processors\PostgresProcessor;

use Doctrine\DBAL\Driver\PDOPgSql\Driver as DoctrineDriver;


class PostgresConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Nova\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Nova\Database\Schema\Grammars\PostgresGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Nova\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOPgSql\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }

}
