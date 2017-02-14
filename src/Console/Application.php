<?php

namespace Nova\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use Exception;


class Application extends \Symfony\Component\Console\Application
{
    /**
     * The exception handler instance.
     *
     * @var \Nova\Exception\Handler
     */
    protected $exceptionHandler;

    /**
     * The Laravel application instance.
     *
     * @var \Nova\Foundation\Application
     */
    protected $nova;

    /**
     * Create and boot a new Console application.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return \Nova\Console\Application
     */
    public static function start($app)
    {
        return static::make($app)->boot();
    }

    /**
     * Create a new Console application.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return \Nova\Console\Application
     */
    public static function make($app)
    {
        $app->boot();

        $console = with($console = new static('Nova Framework', $app::VERSION))
            ->setNova($app)
            ->setExceptionHandler($app['exception'])
            ->setAutoExit(false);

        $app->instance('forge', $console);

        return $console;
    }

    /**
     * Boot the Console application.
     *
     * @return $this
     */
    public function boot()
    {
        $path = $this->nova['path'] .DS .'Boot' .DS .'Forge.php';

        if (is_readable($path)) require $path;

        // If the event dispatcher is set on the application, we will fire an event
        // with the Nova instance to provide each listener the opportunity to
        // register their commands on this application before it gets started.
        if (isset($this->nova['events'])) {
            $events = $this->nova['events'];

            $events->fire('forge.start', array($this));
        }

        return $this;
    }

    /**
     * Run an Nova console command by name.
     *
     * @param  string  $command
     * @param  array   $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function call($command, array $parameters = array(), OutputInterface $output = null)
    {
        $parameters['command'] = $command;

        // Unless an output interface implementation was specifically passed to us we
        // will use the "NullOutput" implementation by default to keep any writing
        // suppressed so it doesn't leak out to the browser or any other source.
        $output = $output ?: new NullOutput;

        $input = new ArrayInput($parameters);

        return $this->find($command)->run($input, $output);
    }

    /**
     * Add a command to the console.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function add(SymfonyCommand $command)
    {
        if ($command instanceof Command) {
            $command->setNova($this->nova);
        }

        return $this->addToParent($command);
    }

    /**
     * Add the command to the parent instance.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function addToParent(SymfonyCommand $command)
    {
        return parent::add($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param  string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolve($command)
    {
        return $this->add($this->nova[$command]);
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param  array|mixed  $commands
     * @return void
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command)  {
            $this->resolve($command);
        }
    }

    /**
     * Get the default input definitions for the applications.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption($this->getEnvironmentOption());

        return $definition;
    }

    /**
     * Get the global environment option for the definition.
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run under.';

        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
    }

    /**
     * Render the given exception.
     *
     * @param  \Exception  $e
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function renderException(Exception $e, OutputInterface $output)
    {
        // If we have an exception handler instance, we will call that first in case
        // it has some handlers that need to be run first. We will pass "true" as
        // the second parameter to indicate that it's handling a console error.
        if (isset($this->exceptionHandler)) {
            $this->exceptionHandler->handleConsole($e);
        }

        parent::renderException($e, $output);
    }

    /**
     * Set the exception handler instance.
     *
     * @param  \Nova\Exception\Handler  $handler
     * @return $this
     */
    public function setExceptionHandler($handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    /**
     * Set the Laravel application instance.
     *
     * @param  \Nova\Foundation\Application  $nova
     * @return $this
     */
    public function setNova($nova)
    {
        $this->nova = $nova;

        return $this;
    }

    /**
     * Set whether the Console app should auto-exit when done.
     *
     * @param  bool  $boolean
     * @return $this
     */
    public function setAutoExit($boolean)
    {
        parent::setAutoExit($boolean);

        return $this;
    }

}
