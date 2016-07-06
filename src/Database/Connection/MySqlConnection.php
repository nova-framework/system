<?php

namespace Nova\Database\Connection;

use Nova\Database\Connection;
use Nova\Database\Schema\MySqlBuilder;
use Nova\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Nova\Database\Query\Processors\MySqlProcessor as QueryProcessor;
use Nova\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;

use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;


class MySqlConnection extends Connection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Nova\Database\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) { $this->useDefaultSchemaGrammar(); }

        return new MySqlBuilder($this);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Nova\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Nova\Database\Schema\Grammars\MySqlGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Nova\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new QueryProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOMySql\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }

}
