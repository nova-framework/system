<?php

namespace Nova\Foundation\Console;

use Nova\Console\GeneratorCommand;


class RequestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Request class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return realpath(__DIR__) .str_replace('/', DS, '/stubs/request.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace .'\Http\Requests';
    }
}
