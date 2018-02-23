<?php

namespace Nova\Foundation\Console;

use Nova\Console\GeneratorCommand;

use Symfony\Component\Console\Input\InputOption;


class JobMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Job class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Job';


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('queued')) {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/job-queued.stub');
        } else {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/job.stub');
        }
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace .'\Jobs';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('queued', null, InputOption::VALUE_NONE, 'Indicates that Job should be queued.'),
        );
    }
}
