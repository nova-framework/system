<?php

namespace Nova\Foundation\Console;

use Nova\Console\GeneratorCommand;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;


class ListenerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Event Listener class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listener';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (! $this->option('event')) {
            return $this->error('Missing required option: --event');
        }

        parent::fire();
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $event = $this->option('event');

        //
        $namespace = $this->nova->getNamespace();

        if (! Str::startsWith($event, $namespace)) {
            $event = $namespace .'Events\\' .$event;
        }

        $stub = str_replace(
            '{{event}}', class_basename($event), $stub
        );

        $stub = str_replace(
            '{{fullEvent}}', $event, $stub
        );

        return $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return realpath(__DIR__) .str_replace('/', DS, '/stubs/listener.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace .'\Listeners';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('event', null, InputOption::VALUE_REQUIRED, 'The event class being listened for.'),
        );
    }
}
