<?php

namespace Nova\Foundation\Console;

use Nova\Console\GeneratorCommand;

use Symfony\Component\Console\Input\InputOption;


class MakeCommandCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Command class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Command';

    /**
     * Execute the command.
     *
     * @return void
     */
    public function fire()
    {
        parent::fire();

        if ($this->option('handler')) {
            $this->call('handler:command', array(
                'name' => $this->argument('name') .'Handler',
                '--command' => $this->parseName($this->argument('name')),
            ));
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $queued  = $this->option('queued');
        $handler = $this->option('handler');

        if ($queued && $handler) {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/command-queued-with-handler.stub');
        } else if ($queued) {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/command-queued.stub');
        } else if ($handler) {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/command-with-handler.stub');
        } else {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/command.stub');
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
        return $rootNamespace .'\Commands';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('handler', null, InputOption::VALUE_NONE, 'Indicates that Handler class should be generated.'),
            array('queued', null, InputOption::VALUE_NONE, 'Indicates that Command should be queued.'),
        );
    }
}
