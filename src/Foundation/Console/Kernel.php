<?php

namespace Nova\Foundation\Console;

use Nova\Console\Contracts\KernelInterface;
use Nova\Console\Application as ConsoleApplication;
use Nova\Events\Dispatcher;
use Nova\Foundation\Application;


class Kernel implements KernelInterface
{
    /**
     * The application instance.
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Nova\Events\Dispatcher
     */
    protected $events;

    /**
     * The forge console instance.
     *
     * @var  \Nova\Console\Application
     */
    protected $forge;

    /**
     * The Forge commands provided by the application.
     *
     * @var array
     */
    protected $commands = array();


    /**
     * Create a new forge command runner instance.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        if (! defined('FORGE_BINARY')) {
            define('FORGE_BINARY', 'forge');
        }

        $this->app = $app;

        $this->events = $events;
    }

    public function run()
    {
        $this->app->setRequestForConsoleEnvironment();

        // Get the Forge instance.
        $forge = $this->getForge();

        // Resolve the additional commands.
        $forge->resolveCommands($this->commands);

        // Run the Forge Application and return its status.
        return $forge->run();
    }

    /**
     * Run a Forge console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function call($command, array $parameters = array())
    {
        return $this->getForge()->call($command, $parameters);
    }

    /**
     * Get all of the commands registered with the console.
     *
     * @return array
     */
    public function all()
    {
        return $this->getForge()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->getForge()->output();
    }

    /**
     * Get the forge console instance.
     *
     * @return \Nova\Console\Application
     */
    protected function getForge()
    {
        if (isset($this->forge)) {
            return $this->forge;
        }

        $this->app->loadDeferredProviders();

        $this->forge = ConsoleApplication::make($this->app);

        return $this->forge->boot();
    }
}
