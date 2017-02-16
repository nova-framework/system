<?php

namespace Nova\Routing\Console;

use Nova\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Nova\Routing\Generators\MiddlewareGenerator;


class MiddlewareMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Middleware class';

    /**
     * The middleware generator instance.
     *
     * @var \Nova\Routing\Generators\MiddlewareGenerator
     */
    protected $generator;

    /**
     * The path to the middleware directory.
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new make middleware command instance.
     *
     * @param  \Nova\Routing\Generators\MiddlewareGenerator  $generator
     * @param  string  $path
     * @return void
     */
    public function __construct(MiddlewareGenerator $generator, $path)
    {
        parent::__construct();

        $this->path = $path;

        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->generateMiddleware();
    }

    /**
     * Generate a new resourceful middleware file.
     *
     * @return void
     */
    protected function generateMiddleware()
    {
        $middleware = str_replace('/', '\\', $this->input->getArgument('name'));

        $path = $this->getPath();

        $this->generator->make($middleware, $path);

        $this->info('Middleware created successfully!');
    }

    /**
     * Get the path in which to store the middleware.
     *
     * @return string
     */
    protected function getPath()
    {
        if (! is_null($this->input->getOption('path'))) {
            return $this->nova['path.base'] .DS .$this->input->getOption('path');
        }

        return $this->path;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'The name of the Middleware class'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('path', null, InputOption::VALUE_OPTIONAL, 'Where to place the Middleware'),
        );
    }

}
